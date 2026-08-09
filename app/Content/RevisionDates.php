<?php

namespace App\Content;

use Carbon\Carbon;
use Illuminate\Support\Facades\Process;

/**
 * When each Document was last actually edited.
 *
 * Modification time is the obvious answer and the wrong one: a deploy checks
 * the repository out fresh, so every file's mtime becomes the moment of the
 * deploy. A sitemap built from mtimes would announce that all 157 Documents
 * changed, every single deploy, and 'dateModified' in the structured data would
 * say the same. Git knows the truth, and the build runs where the repository
 * is, so ask git.
 */
class RevisionDates
{
    /** @var array<string, Carbon>|null */
    private ?array $dates = null;

    public function __construct(private readonly DocumentReader $reader) {}

    public function for(Document $document): Carbon
    {
        $dates = $this->dates ??= $this->fromGit();

        return $dates[$document->filepath]
            ?? Carbon::createFromTimestamp(filemtime($this->reader->absolute($document->filepath)) ?: 0);
    }

    /**
     * One `git log` walk over the content directory, rather than one process
     * per Document — 157 git invocations would dominate the build.
     *
     * @return array<string, Carbon>
     */
    private function fromGit(): array
    {
        $root = $this->reader->root();

        $result = Process::path($root)->run([
            'git', 'log', '--name-only', '--format=%cI', '--', '.',
        ]);

        if (! $result->successful()) {
            return [];
        }

        $dates = [];
        $commitDate = null;

        foreach (explode("\n", $result->output()) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}T/', $line)) {
                $commitDate = Carbon::parse($line);

                continue;
            }

            // Paths are repository-relative; Documents are content-root relative.
            $relative = preg_replace('#^'.preg_quote(basename($root), '#').'/#', '', $line);

            // git log lists newest first, so the first sighting of a path wins.
            if ($commitDate instanceof Carbon && ! isset($dates[$relative])) {
                $dates[$relative] = $commitDate;
            }
        }

        return $dates;
    }
}
