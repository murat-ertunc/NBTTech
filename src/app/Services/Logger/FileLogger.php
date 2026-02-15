<?php
/**
 * File Logger - dosya tabanlı log sürücüsü.
 * LoggerInterface implementasyonu.
 */

namespace App\Services\Logger;

use App\Core\Context;

class FileLogger implements LoggerInterface
{
    private string $LogDosyaYolu;

    public function __construct()
    {
        $this->LogDosyaYolu = config('log.file_path', STORAGE_PATH . 'logs');

        if (!is_dir($this->LogDosyaYolu)) {
            mkdir($this->LogDosyaYolu, 0755, true);
        }
    }

    public function log(array $Yukleme): void
    {
        try {
            $Simdi = date('Y-m-d H:i:s');
            $KullaniciId = Context::kullaniciId();
            $IpAdresi = $Yukleme['IpAdresi'] ?? Context::ipAdresi();

            $Veri = isset($Yukleme['Veri']) ? json_decode($Yukleme['Veri'], true) : [];
            $KayitId = null;
            if (isset($Veri['Yukleme']['Kimlik']['Id'])) {
                $KayitId = (int) $Veri['Yukleme']['Kimlik']['Id'];
            } elseif (isset($Veri['Yukleme']['Filtreler']['Id'])) {
                $KayitId = (int) $Veri['Yukleme']['Filtreler']['Id'];
            }

            $LogSatiri = json_encode([
                'Zaman' => $Simdi,
                'KullaniciId' => $KullaniciId,
                'Islem' => $Yukleme['Islem'] ?? 'UNKNOWN',
                'Tablo' => $Yukleme['Tablo'] ?? 'unknown',
                'KayitId' => $KayitId,
                'IpAdresi' => $IpAdresi,
                'Veri' => $Veri,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $DosyaAdi = $this->LogDosyaYolu . DIRECTORY_SEPARATOR . date('Y-m-d') . '.log';

            file_put_contents($DosyaAdi, $LogSatiri . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $E) {
            error_log('[FileLogger] Log yazılamadı: ' . $E->getMessage());
        }
    }
}
