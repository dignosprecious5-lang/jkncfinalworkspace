<?php

namespace Tests\Support;

use Illuminate\Http\UploadedFile;

trait FakeUploads
{
    /**
     * GD is unavailable here, and UploadedFile::fake()->create() produces sparse
     *  0-byte files on this filesystem, so fixtures carry real bytes.
     */
    protected function fakeImage(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'ordo-test-');
        file_put_contents($path, (string) base64_decode(
            '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0a'
            .'HBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCAABAAEBAREA/8QAFAABAAAAAAAA'
            .'AAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AKp//2Q==',
            true,
        ));

        return new UploadedFile($path, $name, 'image/jpeg', null, true);
    }

    protected function fakePdf(string $name, int $bytes = 64): UploadedFile
    {
        return $this->fakeFile($name, $bytes, 'application/pdf');
    }

    protected function fakeFile(string $name, int $bytes, string $mime): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'ordo-test-');
        file_put_contents($path, str_repeat('a', $bytes));

        return new UploadedFile($path, $name, $mime, null, true);
    }
}
