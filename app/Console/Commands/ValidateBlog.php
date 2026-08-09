<?php

namespace App\Console\Commands;

use App\Content\DocumentReader;
use Illuminate\Console\Command;

/**
 * Checks that every Document on disk can be read, without building anything.
 *
 * This runs in CI on every push, which is the point: a malformed Document fails
 * there, on the commit that introduced it, instead of failing the deploy and
 * blocking whatever unrelated work was queued behind it.
 */
class ValidateBlog extends Command
{
    protected $signature = 'blog:validate';

    protected $description = 'Check every Document parses and describes itself correctly';

    public function handle(DocumentReader $reader): int
    {
        [$documents, $failures] = $reader->readAll();

        if ($failures === []) {
            $this->components->info("All {$documents->count()} Documents are valid.");

            return self::SUCCESS;
        }

        foreach ($failures as $path => $reasons) {
            foreach ($reasons as $reason) {
                $this->components->twoColumnDetail("<fg=red>{$path}</>", $reason);
            }
        }

        $this->newLine();
        $this->components->error(count($failures).' of '.(count($failures) + $documents->count()).' Documents are invalid.');

        return self::FAILURE;
    }
}
