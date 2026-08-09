<?php

namespace App\Console\Commands;

use App\Content\Document;
use App\Content\IndexBuilder;
use App\Content\MarkdownRenderer;
use App\Content\OgImage;
use App\Content\RevisionDates;
use App\Content\SearchIndex;
use App\Content\Sitemap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Rebuilds everything the site derives from the Documents.
 *
 * Runs on the server after each deploy. Always a full rebuild — the outputs are
 * disposable, and rebuilding them from scratch is the only way to be certain
 * they agree with the markdown.
 */
class BuildBlog extends Command
{
    protected $signature = 'blog:build {--no-warm : Skip pre-rendering, leaving the first reader of each page to pay for it}';

    protected $description = 'Rebuild the Index, sitemap and search index from the Documents';

    public function handle(
        IndexBuilder $builder,
        RevisionDates $revisions,
        Sitemap $sitemap,
        SearchIndex $searchIndex,
        MarkdownRenderer $renderer,
    ): int {
        ['documents' => $documents, 'headings' => $headings, 'failures' => $failures] = $builder->build();

        if ($failures !== []) {
            $this->reportFailures($failures);

            return self::FAILURE;
        }

        $documents = $documents->map(fn (Document $d) => $d->withUpdatedAt($revisions->for($d)));

        $builder->write($documents, $headings);
        $this->components->info("Indexed {$documents->count()} Documents.");

        file_put_contents(public_path(Sitemap::PATH), $sitemap->xml($documents));
        $this->components->info('Wrote '.Sitemap::PATH.'.');

        file_put_contents(public_path(SearchIndex::PATH), $searchIndex->json($documents, $headings));
        $this->components->info('Wrote '.SearchIndex::PATH.'.');

        $this->drawSocialCards($documents, app(OgImage::class));

        if (! $this->option('no-warm')) {
            $this->warm($documents, $renderer);
        }

        return self::SUCCESS;
    }

    /**
     * A card for every Document that has no image of its own.
     *
     * @param  \Illuminate\Support\Collection<int, Document>  $documents
     */
    private function drawSocialCards($documents, OgImage $cards): void
    {
        if (! $cards->available()) {
            $this->components->warn('No font at '.$cards->fontPath().' — social cards skipped.');

            return;
        }

        $directory = public_path('og');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $drawn = 0;

        foreach ($documents as $document) {
            if (filled($document->frontmatter->image)) {
                continue;
            }

            $png = $cards->render($document);

            if ($png !== null) {
                file_put_contents($directory.'/'.OgImage::filename($document), $png);
                $drawn++;
            }
        }

        $this->components->info("Drew {$drawn} social cards.");
    }

    /**
     * Rendering every Document now means readers never wait for a first render,
     * and it is the moment a Document that no longer renders says so.
     *
     * @param  \Illuminate\Support\Collection<int, Document>  $documents
     */
    private function warm($documents, MarkdownRenderer $renderer): void
    {
        Cache::flush();

        $bar = $this->output->createProgressBar($documents->count());

        foreach ($documents as $document) {
            $renderer->render($document);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->components->info("Pre-rendered {$documents->count()} Documents.");
    }

    /**
     * @param  array<string, array<int, string>>  $failures
     */
    private function reportFailures(array $failures): void
    {
        $this->components->error(count($failures).' Document(s) could not be read:');

        foreach ($failures as $path => $reasons) {
            foreach ($reasons as $reason) {
                $this->line("  <fg=red>{$path}</>: {$reason}");
            }
        }

        $this->newLine();
        $this->line('  Nothing was written. The site still serves its previous build.');
    }
}
