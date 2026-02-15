<?php

namespace App\Core;

class DownloadHelper
{
    /**
     * Dosyayı güvenli şekilde indirir.
     * - Path traversal koruması: realpath ile STORAGE_PATH içinde olduğu doğrulanır
     * - Header injection koruması: dosya adından CR/LF karakterleri temizlenir
     * - Dosya varlık kontrolü yapılır
     */
    public static function outputFile(string $FilePath, string $DosyaAdi, ?string $MimeType = null): void
    {
        // Path traversal koruması
        $GercekYol = realpath($FilePath);
        if ($GercekYol === false) {
            http_response_code(404);
            echo json_encode(['basarili' => false, 'mesaj' => 'Dosya bulunamadı.']);
            exit;
        }

        // STORAGE_PATH sabiti tanımlıysa, dosyanın bu dizin içinde olduğunu doğrula
        if (defined('STORAGE_PATH')) {
            $IzinliDizin = realpath(STORAGE_PATH);
            if ($IzinliDizin !== false && strpos($GercekYol, $IzinliDizin) !== 0) {
                http_response_code(403);
                echo json_encode(['basarili' => false, 'mesaj' => 'Bu dosyaya erişim izniniz yok.']);
                exit;
            }
        }

        if (!is_file($GercekYol) || !is_readable($GercekYol)) {
            http_response_code(404);
            echo json_encode(['basarili' => false, 'mesaj' => 'Dosya okunamıyor.']);
            exit;
        }

        if ($MimeType === null || $MimeType === '') {
            $MimeType = mime_content_type($GercekYol) ?: 'application/octet-stream';
        }

        // Header injection koruması: CR/LF ve kontrol karakterlerini temizle
        $GuvenliDosyaAdi = preg_replace('/[\r\n\x00-\x1f\x7f]/', '', $DosyaAdi);
        // Boş dosya adı kontrolü
        if (empty($GuvenliDosyaAdi)) {
            $GuvenliDosyaAdi = 'dosya';
        }

        header('Content-Type: ' . $MimeType);
        header('Content-Disposition: attachment; filename="' . addslashes($GuvenliDosyaAdi) . '"');
        header('Content-Length: ' . filesize($GercekYol));
        header('X-Content-Type-Options: nosniff');
        readfile($GercekYol);
        exit;
    }
}
