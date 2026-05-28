<?php

namespace Modules\Documents\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Documents\Models\UploadedDocument;

class MigrateDiskCommand extends Command
{
    protected $signature = 'docs:migrate-disk
        {--from= : Source disk (e.g. documents_local)}
        {--to= : Destination disk (e.g. documents_s3)}
        {--dry-run : Show what would be moved without copying}
        {--chunk=100 : Number of docs per batch}
        {--delete-source : Delete from source disk after verified copy (default: keep)}';

    protected $description = 'Move uploaded documents from one Storage disk to another with checksum verification.';

    public function handle(): int
    {
        $from = $this->option('from');
        $to = $this->option('to');
        $dry = (bool) $this->option('dry-run');
        $deleteSource = (bool) $this->option('delete-source');
        $chunk = (int) $this->option('chunk');

        if (! $from || ! $to) {
            $this->error('Both --from and --to are required.');

            return self::INVALID;
        }

        $allowed = config('docs.allowed_disks', []);
        if (! in_array($from, $allowed, true) || ! in_array($to, $allowed, true)) {
            $this->error('Disks must be in config(docs.allowed_disks). Allowed: '.implode(',', $allowed));

            return self::INVALID;
        }

        $total = UploadedDocument::where('disk', $from)->count();
        if ($total === 0) {
            $this->info("No documents on disk [{$from}].");

            return self::SUCCESS;
        }

        $this->info(($dry ? '[DRY RUN] ' : '')."Migrating {$total} documents from [{$from}] to [{$to}]...");

        $migrated = 0;
        $failed = 0;

        UploadedDocument::where('disk', $from)
            ->orderBy('id')
            ->chunkById($chunk, function ($docs) use ($from, $to, $dry, $deleteSource, &$migrated, &$failed) {
                foreach ($docs as $doc) {
                    if ($dry) {
                        $this->line("would move #{$doc->id} {$doc->path}");
                        $migrated++;

                        continue;
                    }

                    try {
                        $contents = Storage::disk($from)->get($doc->path);
                        if ($contents === null) {
                            throw new \RuntimeException("Source file missing on [{$from}]");
                        }

                        $checksum = hash('sha256', $contents);
                        if ($doc->checksum_sha256 && $doc->checksum_sha256 !== $checksum) {
                            $this->warn("Checksum mismatch on doc #{$doc->id}; aborting move.");
                            $failed++;

                            continue;
                        }

                        Storage::disk($to)->put($doc->path, $contents);

                        // Verify the write
                        $verifyChecksum = hash('sha256', Storage::disk($to)->get($doc->path));
                        if ($verifyChecksum !== $checksum) {
                            Storage::disk($to)->delete($doc->path);
                            throw new \RuntimeException("Checksum verification failed after write to [{$to}]");
                        }

                        DB::transaction(function () use ($doc, $to, $from, $deleteSource) {
                            $doc->forceFill(['disk' => $to])->save();
                            if ($deleteSource) {
                                Storage::disk($from)->delete($doc->path);
                            }
                        });

                        $migrated++;
                    } catch (\Throwable $e) {
                        $this->error("Failed doc #{$doc->id}: {$e->getMessage()}");
                        $failed++;
                    }
                }
            });

        $this->newLine();
        $this->info("Migrated: {$migrated}");
        if ($failed) {
            $this->warn("Failed: {$failed}");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
