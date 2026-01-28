<?php

namespace App\Core;

class UploadValidator
{
    private const IzinliUzantilar = ['pdf', 'doc', 'docx'];
    private const IzinliMimler = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    public static function validateDocument(array $Dosya, int $MaksimumBoyut): ?string
    {
        $HataMesaji = 'Sadece PDF veya Word (.pdf, .doc, .docx) yüklenebilir.';
        $OrijinalAd = $Dosya['name'] ?? '';
        $Uzanti = strtolower(pathinfo($OrijinalAd, PATHINFO_EXTENSION));
        $Boyut = (int)($Dosya['size'] ?? 0);

        if ($Boyut <= 0 || $Boyut > $MaksimumBoyut) {
            return $HataMesaji;
        }

        if (!in_array($Uzanti, self::IzinliUzantilar, true)) {
            return $HataMesaji;
        }

        $MimeType = self::tespitEtMime($Dosya);
        if ($Uzanti === 'pdf') {
            if ($MimeType !== '' && $MimeType !== 'application/pdf') {
                return $HataMesaji;
            }
        }

        return null;
    }

    private static function tespitEtMime(array $Dosya): string
    {
        $MimeType = '';
        if (!empty($Dosya['tmp_name']) && is_uploaded_file($Dosya['tmp_name'])) {
            $Finfo = new \finfo(FILEINFO_MIME_TYPE);
            $MimeType = $Finfo->file($Dosya['tmp_name']) ?: '';
        }

        if ($MimeType === '') {
            $MimeType = $Dosya['type'] ?? '';
        }

        if ($MimeType !== '' && !in_array($MimeType, self::IzinliMimler, true)) {
            return $MimeType;
        }

        return $MimeType;
    }
}
