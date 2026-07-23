<?php

namespace App\Support;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;
use RuntimeException;
use Symfony\Component\Process\Process;

class ReceiptPdfRenderer
{
    public static function render(string $view, array $data, string $filename): Response
    {
        $html = View::make($view, $data)->render();
        $base = is_dir('/home/spmm-receipts') ? '/home/spmm-receipts' : storage_path('app/temp-receipts');

        if (! is_dir($base)) {
            mkdir($base, 0755, true);
        }

        $token = bin2hex(random_bytes(10));
        $htmlPath = $base.'/'.$token.'.html';
        $pdfPath = $base.'/'.$token.'.pdf';

        file_put_contents($htmlPath, $html);

        $wkhtmltopdf = trim((string) shell_exec('command -v wkhtmltopdf 2>/dev/null'));

        if ($wkhtmltopdf !== '') {
            $process = new Process([
                $wkhtmltopdf,
                '--enable-local-file-access',
                '--encoding',
                'utf-8',
                '--page-size',
                'A4',
                '--margin-top',
                '10mm',
                '--margin-right',
                '10mm',
                '--margin-bottom',
                '10mm',
                '--margin-left',
                '10mm',
                '--quiet',
                $htmlPath,
                $pdfPath,
            ]);
        } else {
            $binary = trim((string) shell_exec('command -v chromium-browser 2>/dev/null || command -v chromium 2>/dev/null || command -v google-chrome 2>/dev/null'));

            if ($binary === '') {
                @unlink($htmlPath);
                throw new RuntimeException('Renderer PDF tidak tersedia untuk membuat kwitansi.');
            }

            $profilePath = $base.'/chromium-profile';

            if (! is_dir($profilePath)) {
                mkdir($profilePath, 0777, true);
            }

            $process = new Process([
                $binary,
                '--headless',
                '--disable-gpu',
                '--no-sandbox',
                '--disable-dev-shm-usage',
                '--user-data-dir='.$profilePath,
                '--print-to-pdf='.$pdfPath,
                '--print-to-pdf-no-header',
                'file://'.$htmlPath,
            ]);
            $process->setEnv(array_merge($_ENV, [
                'HOME' => $base,
                'XDG_CONFIG_HOME' => $base.'/config',
                'XDG_CACHE_HOME' => $base.'/cache',
                'TMPDIR' => $base,
            ]));
        }

        $process->setTimeout(45);
        $process->run();

        @unlink($htmlPath);

        if (! $process->isSuccessful() || ! is_file($pdfPath)) {
            @unlink($pdfPath);
            throw new RuntimeException('PDF kwitansi gagal dibuat: '.$process->getErrorOutput());
        }

        $content = file_get_contents($pdfPath);
        @unlink($pdfPath);

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}


