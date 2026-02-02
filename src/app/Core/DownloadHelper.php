<?php

namespace App\Core;

class DownloadHelper
{
    public static function outputFile(string $FilePath, string $DosyaAdi, ?string $MimeType = null): void
    {
        if ($MimeType === null || $MimeType === '') {
            $MimeType = mime_content_type($FilePath) ?: 'application/octet-stream';
        }

        header('Content-Type: ' . $MimeType);
        header('Content-Disposition: attachment; filename="' . $DosyaAdi . '"');
        header('Content-Length: ' . filesize($FilePath));
        readfile($FilePath);
        exit;
    }
}
