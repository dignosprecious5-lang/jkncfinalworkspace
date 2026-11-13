<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Http\UploadedFile;

if (PHP_SAPI !== 'cli' || ! defined('TRANSMITTAL_OCR_MOCKED_BOOTSTRAPPED')) {
    throw new \RuntimeException('Synthetic OCR fixtures require the isolated mocked bootstrap.');
}

final class TransmittalOcrFixtures
{
    public const TEXT = "Transmittal Date: 2026-11-06\nParty Name: Synthetic Party\nOffice Name: Synthetic Office\nAddress: 123 Example Test Street\nRecipient Email: recipient@example.test\nParticular: Synthetic Document\nUnique ID: TEST-001\nQuantity: 2\nDescription: Synthetic description\nRemarks: Synthetic remarks\nAPP_KEY: ignored\nworkflow_status: accepted";

    public static function expected(): array
    {
        return [
            'transmittal_date' => '2026-11-06', 'party_name' => 'Synthetic Party',
            'office_name' => 'Synthetic Office', 'address' => '123 Example Test Street',
            'recipient_email' => 'recipient@example.test',
            'items' => [['particular' => 'Synthetic Document', 'unique_id' => 'TEST-001',
                'qty' => '2', 'description' => 'Synthetic description', 'remarks' => 'Synthetic remarks']],
        ];
    }

    public static function pngBytes(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=', true);
    }

    public static function upload(string $kind, int $bytes = 0): UploadedFile
    {
        $mime = 'image/png';
        $extension = 'png';
        $content = self::pngBytes();
        if ($kind === 'jpeg') {
            $extension = 'jpg';
            $mime = 'image/jpeg';
            $image = imagecreatetruecolor(2, 2);
            ob_start();
            imagejpeg($image);
            $content = (string) ob_get_clean();
            imagedestroy($image);
        } elseif ($kind === 'pdf') {
            $extension = 'pdf';
            $mime = 'application/pdf';
            // Synthetic PDF with a valid object/xref structure; never a client document.
            $objects = ['<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 100 100] /Contents 4 0 R >>',
                "<< /Length 0 >>\nstream\n\nendstream"];
            $content = "%PDF-1.4\n";
            $offsets = [0];
            foreach ($objects as $index => $object) {
                $offsets[] = strlen($content);
                $content .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
            }
            $xref = strlen($content);
            $content .= "xref\n0 5\n0000000000 65535 f \n";
            foreach (array_slice($offsets, 1) as $offset) $content .= sprintf("%010d 00000 n \n", $offset);
            $content .= "trailer\n<< /Size 5 /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
        } elseif ($kind === 'spoofed') {
            $content = 'This is plain text, despite its claimed PNG MIME and extension.';
        } elseif ($kind === 'empty') {
            $content = '';
        } elseif ($kind === 'unsupported') {
            $extension = 'gif'; $mime = 'image/gif';
            $content = base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==', true);
        } elseif (! in_array($kind, ['png', 'invalid'], true)) {
            throw new \RuntimeException('Unknown synthetic fixture kind.');
        }
        if ($bytes > strlen($content)) $content .= str_repeat(' ', $bytes - strlen($content));
        $path = TransmittalOcrIsolation::runtime().'/uploads/'.bin2hex(random_bytes(10)).'.'.$extension;
        if (file_put_contents($path, $content) !== strlen($content)) throw new \RuntimeException('Synthetic upload creation failed.');
        return new UploadedFile($path, 'synthetic.'.$extension, $mime,
            $kind === 'invalid' ? UPLOAD_ERR_PARTIAL : UPLOAD_ERR_OK, true);
    }
}
