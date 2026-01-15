<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Database;
use App\Core\Response;
use App\Core\Rol;

/**
 * Islem Loglari Kontrolcusu
 * 
 * log_action tablosundan loglari listeler.
 * Sadece superadmin erisebilir.
 */
class LogController
{
    /**
     * Log Kayitlarini Listele
     * 
     * Query parametreleri:
     * - tarih: Y-m-d formatinda filtreleme
     * - limit: Sonuc limiti (varsayilan 50, max 500)
     * - tablo: Tablo adina gore filtreleme
     * - islem: Islem tipine gore filtreleme (CREATE, UPDATE, DELETE, SELECT)
     */
    public static function index(): void
    {
        // Authentication check
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::unauthorized('Oturum gerekli');
            return;
        }
        
        // Superadmin role check
        $Rol = Context::rol();
        if ($Rol !== Rol::SUPERADMIN) {
            Response::forbidden('Bu alana sadece superadmin erisebilir');
            return;
        }
        
        $Db = Database::connection();
        $Tarih = $_GET['tarih'] ?? null;
        $Tablo = $_GET['tablo'] ?? null;
        $Islem = $_GET['islem'] ?? null;
        $Limit = min((int) ($_GET['limit'] ?? 50), 500);
        
        $Sql = "SELECT TOP {$Limit}
                    la.Id,
                    la.Tablo,
                    la.Islem,
                    la.KayitId,
                    la.EskiDeger,
                    la.YeniDeger,
                    la.IpAdresi,
                    la.EklemeZamani,
                    COALESCE(u.KullaniciAdi, 'system') AS KullaniciAdi,
                    COALESCE(u.AdSoyad, 'Sistem') AS KullaniciAdSoyad
                FROM log_action la
                LEFT JOIN tnm_user u ON la.EkleyenUserId = u.Id
                WHERE 1=1";
        
        $Parametreler = [];
        
        if ($Tarih) {
            $Sql .= " AND CONVERT(date, la.EklemeZamani) = :Tarih";
            $Parametreler['Tarih'] = $Tarih;
        }
        
        if ($Tablo) {
            $Sql .= " AND la.Tablo = :Tablo";
            $Parametreler['Tablo'] = $Tablo;
        }
        
        if ($Islem) {
            $Sql .= " AND la.Islem = :Islem";
            $Parametreler['Islem'] = strtoupper($Islem);
        }
        
        $Sql .= " ORDER BY la.EklemeZamani DESC";
        
        $Stmt = $Db->prepare($Sql);
        $Stmt->execute($Parametreler);
        $Loglar = $Stmt->fetchAll();
        
        Response::json([
            'success' => true,
            'data' => $Loglar,
            'count' => count($Loglar)
        ]);
    }
}
