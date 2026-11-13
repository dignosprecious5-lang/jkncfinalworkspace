<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Tracks only exclusively-created files/directories belonging to this one save attempt. */
class TransmittalAttachmentAttempt
{
    private array $directories = [];
    private array $files = [];
    private bool $retain = false;

    public function __construct(private readonly string $reference)
    {
        if (preg_match('/^[a-f0-9-]{36}$/D', $reference) !== 1) throw new RuntimeException('Invalid upload attempt identity.');
    }

    public function retain(): void { $this->retain = true; }

    public function store(UploadedFile $file, string $category): string
    {
        if ($this->retain || ! in_array($category, ['item-attachments', 'attachments'], true)
            || config('filesystems.disks.public.driver') !== 'local') {
            throw new RuntimeException('Unsupported attachment attempt or storage configuration.');
        }
        $disk = Storage::disk('public');
        $root = realpath($disk->path(''));
        if ($root === false || ! is_dir($root)) throw new RuntimeException('Attachment storage is unavailable.');
        if (! isset($this->directories[$category])) {
            $parent = $root;
            foreach (['transmittal', $category] as $component) {
                $parent .= DIRECTORY_SEPARATOR.$component;
                if (! file_exists($parent) && ! @mkdir($parent, 0755)) throw new RuntimeException('Attachment directory creation failed.');
                if (is_link($parent) || ! $this->same(realpath($parent), $parent)) throw new RuntimeException('Unsafe attachment directory.');
            }
            $directory = $parent.DIRECTORY_SEPARATOR.$this->reference;
            // Exclusive mkdir: never adopt, enumerate, or clean a pre-existing attempt directory.
            if (! @mkdir($directory, 0755)) throw new RuntimeException('Attachment attempt directory already exists or is unavailable.');
            if (is_link($directory) || ! $this->same(realpath($directory), $directory)) throw new RuntimeException('Unsafe attachment attempt directory.');
            $this->directories[$category] = $directory;
        }
        $directory = $this->directories[$category];
        if (is_link($directory) || ! $this->same(realpath($directory), $directory)) throw new RuntimeException('Attachment attempt directory changed.');
        $extension = strtolower((string) $file->guessExtension());
        if (preg_match('/^[a-z0-9]{1,10}$/D', $extension) !== 1) $extension = 'bin';
        $name = bin2hex(random_bytes(16)).'.'.$extension;
        $absolute = $directory.DIRECTORY_SEPARATOR.$name;
        $output = @fopen($absolute, 'x+b');
        if ($output === false) throw new RuntimeException('Attachment destination creation failed.');
        $this->files[] = ['absolute' => $absolute, 'directory' => $directory, 'identity' => fstat($output)];
        $input = null;
        try {
            $source = $file->getRealPath();
            if (! is_string($source) || ! $file->isValid()) throw new RuntimeException('Upload source is unavailable.');
            $input = @fopen($source, 'rb');
            if ($input === false) throw new RuntimeException('Upload source is unreadable.');
            $copied = @stream_copy_to_stream($input, $output);
            if ($copied !== $file->getSize() || ! @fflush($output)) throw new RuntimeException('Attachment write was incomplete.');
        } finally {
            if (is_resource($input)) fclose($input);
            fclose($output);
        }
        $relative = 'transmittal/'.$category.'/'.$this->reference.'/'.$name;
        if ($disk->setVisibility($relative, 'public') === false) throw new RuntimeException('Attachment visibility could not be preserved.');
        return $relative;
    }

    /** No automatic deletion: even an acknowledged rollback is not an all-table guarantee. */
    public function retainForReconciliation(): array
    {
        $this->retain = true;
        return ['deleted' => 0, 'failed' => 0, 'retained' => count($this->files), 'policy' => 'retain_for_reconciliation'];
    }

    /** @deprecated Compatibility only: the boolean never authorizes deletion. */
    public function cleanupAfterRollback(bool $confirmed): array
    {
        return $this->retainForReconciliation();
    }

    private function same(string|false $left, string $right): bool
    {
        if ($left === false) return false;
        $left = str_replace('\\', '/', $left);
        $right = str_replace('\\', '/', $right);
        return DIRECTORY_SEPARATOR === '\\' ? strcasecmp($left, $right) === 0 : $left === $right;
    }
}
