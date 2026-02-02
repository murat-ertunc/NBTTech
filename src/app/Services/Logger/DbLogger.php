<?php

namespace App\Services\Logger;

use App\Core\Context;
use App\Core\Database;
use PDO;







class DbLogger implements LoggerInterface
{
    private function db(): PDO
    {
        return Database::connection();
    }

    private function tablo(): string
    {
        return config('log.table', 'log_action');
    }

    








    public function log(array $Yukleme): void
    {
        try {
            $Simdi = date('Y-m-d H:i:s');
            $KullaniciId = Context::kullaniciId();
            
            $Veri = isset($Yukleme['Veri']) ? json_decode($Yukleme['Veri'], true) : [];
            $KayitId = null;
            if (isset($Veri['Yukleme']['Kimlik']['Id'])) {
                $KayitId = (int) $Veri['Yukleme']['Kimlik']['Id'];
            } elseif (isset($Veri['Yukleme']['Filtreler']['Id'])) {
                $KayitId = (int) $Veri['Yukleme']['Filtreler']['Id'];
            }
            
            $Guid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            
            $Sql = "INSERT INTO {$this->tablo()} 
                    (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, 
                     Islem, Tablo, KayitId, IpAdresi, YeniDeger) 
                    VALUES 
                    (:Guid, :EklemeZamani, :EkleyenUserId, :DegisiklikZamani, :DegistirenUserId, 0,
                     :Islem, :Tablo, :KayitId, :IpAdresi, :YeniDeger)";
            
            $Stmt = $this->db()->prepare($Sql);
            $Stmt->execute([
                'Guid' => $Guid,
                'EklemeZamani' => $Simdi,
                'EkleyenUserId' => $KullaniciId,
                'DegisiklikZamani' => $Simdi,
                'DegistirenUserId' => $KullaniciId,
                'Islem' => $Yukleme['Islem'] ?? 'UNKNOWN',
                'Tablo' => $Yukleme['Tablo'] ?? 'unknown',
                'KayitId' => $KayitId,
                'IpAdresi' => $Yukleme['IpAdresi'] ?? Context::ipAdresi(),
                'YeniDeger' => $Yukleme['Veri'] ?? null,
            ]);
        } catch (\Throwable $E) {
            error_log('[DbLogger] Log yazilamadi: ' . $E->getMessage());
        }
    }
}
