<?php

namespace App\Http\Controllers\Concerns;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

trait GeneratesPdfPreview
{
    protected function generatePdfPreview(string $view, array $data, string $targetPath): ?string
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }

        $browserBinary = $this->previewBrowserBinary();

        if (!$browserBinary) {
            return $this->generatePdfPreviewWithDompdf($view, $data, $targetPath);
        }

        $html = view($view, $data)->render();

        $tempDirectory = storage_path('app/temp');
        if (!is_dir($tempDirectory)) {
            mkdir($tempDirectory, 0777, true);
        }

        $basename = Str::slug(pathinfo($targetPath, PATHINFO_FILENAME) ?: 'preview');
        $htmlPath = $tempDirectory . DIRECTORY_SEPARATOR . $basename . '-' . Str::uuid() . '.html';
        $pdfPath = $tempDirectory . DIRECTORY_SEPARATOR . $basename . '-' . Str::uuid() . '.pdf';
        $profilePath = $tempDirectory . DIRECTORY_SEPARATOR . $basename . '-profile-' . Str::uuid();

        file_put_contents($htmlPath, $html);
        if (!is_dir($profilePath)) {
            mkdir($profilePath, 0777, true);
        }

        $process = new Process([
            $browserBinary,
            '--headless',
            '--disable-gpu',
            '--user-data-dir=' . $profilePath,
            '--no-first-run',
            '--no-default-browser-check',
            '--disable-crash-reporter',
            '--disable-features=Crashpad',
            '--noerrdialogs',
            '--allow-file-access-from-files',
            '--disable-web-security',
            '--print-to-pdf=' . $pdfPath,
            '--no-pdf-header-footer',
            'file:///' . str_replace(DIRECTORY_SEPARATOR, '/', $htmlPath),
        ]);

        $process->setTimeout(60);
        $process->setEnv([
            'TEMP' => $tempDirectory,
            'TMP' => $tempDirectory,
            'LOCALAPPDATA' => $tempDirectory,
            'APPDATA' => $tempDirectory,
        ]);
        $process->run();

        @unlink($htmlPath);
        $this->deletePdfPreviewDirectory($profilePath);

        if (!file_exists($pdfPath) || filesize($pdfPath) === 0) {
            @unlink($pdfPath);

            return $this->generatePdfPreviewWithDompdf($view, $data, $targetPath);
        }

        Storage::disk('public')->delete($targetPath);
        Storage::disk('public')->put($targetPath, file_get_contents($pdfPath));
        @unlink($pdfPath);

        return $targetPath;
    }

    protected function generatePdfPreviewWithDompdf(string $view, array $data, string $targetPath): ?string
    {
        try {
            $pdf = Pdf::loadView($view, $data)->setPaper('a4', 'portrait');
            Storage::disk('public')->delete($targetPath);
            Storage::disk('public')->put($targetPath, $pdf->output());

            return $targetPath;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function previewBrowserBinary(): ?string
    {
        $candidates = [
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function deletePdfPreviewDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = array_diff(scandir($directory) ?: [], ['.', '..']);

        foreach ($items as $item) {
            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->deletePdfPreviewDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
