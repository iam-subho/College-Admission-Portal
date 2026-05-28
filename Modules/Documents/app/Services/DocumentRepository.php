<?php

namespace Modules\Documents\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Modules\Documents\Models\UploadedDocument;

class DocumentRepository
{
    public function disk(UploadedDocument $doc): Filesystem
    {
        return Storage::disk($doc->disk);
    }

    public function exists(UploadedDocument $doc): bool
    {
        return $this->disk($doc)->exists($doc->path);
    }

    public function url(UploadedDocument $doc, int $minutes = null): string
    {
        $minutes ??= (int) config('docs.signed_url_minutes', 5);

        $disk = $this->disk($doc);

        if (method_exists($disk, 'temporaryUrl')) {
            try {
                return $disk->temporaryUrl($doc->path, now()->addMinutes($minutes));
            } catch (\Throwable $e) {
                // Local disk doesn't support temporary URLs — fall through.
            }
        }

        return route('documents.download', ['document' => $doc->id]);
    }

    public function delete(UploadedDocument $doc): bool
    {
        if ($this->exists($doc)) {
            return $this->disk($doc)->delete($doc->path);
        }

        return true;
    }
}
