<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Database;
use App\Core\Response;
use App\Services\Authorization\AuthorizationService;

/**
 * Islem Loglari Kontrolcusu
 * 
 * log_action tablosundan loglari listeler.
 * Sadece logs.read izni olan kullanicilar erisebilir.
 */
class LogController
{
    /**
     * Log Kayitlarini Listele
     * 
     * Query parametreleri:
     * - page: Sayfa numarasi (varsayilan 1)
     * - limit: Sayfa basina kayit (varsayilan 10, max 500)
     * - tarih: Y-m-d formatinda filtreleme
     * - tablo: Tablo adina gore filtreleme
     * - islem: Islem tipine gore filtreleme (INSERT, UPDATE, DELETE, SELECT)
     * - kullanici: Kullanici adina gore filtreleme
     */
    public static function index(): void
    {
        // Authentication check
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::unauthorized('Oturum gerekli');
            return;
        }
        
        // Permission kontrolu
        $AuthService = AuthorizationService::getInstance();
        if (!$AuthService->can($KullaniciId, 'logs.read')) {
            Response::forbidden('Bu alana erisim yetkiniz yok');
            return;
        }
        
        $Db = Database::connection();
        
        // Pagination parametreleri
        $Sayfa = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $Limit = isset($_GET['limit']) ? max(1, min(500, (int)$_GET['limit'])) : (int)env('PAGINATION_DEFAULT', 10);
        $Offset = ($Sayfa - 1) * $Limit;
        
        // Filtre parametreleri
        $Tarih = $_GET['tarih'] ?? null;
        $TarihStart = $_GET['tarih_start'] ?? null;
        $TarihEnd = $_GET['tarih_end'] ?? null;
        $Tablo = $_GET['tablo'] ?? null;
        $Islem = $_GET['islem'] ?? null;
        $KullaniciAdi = $_GET['kullanici'] ?? null;
        
        $WhereClause = "WHERE 1=1";
        $Parametreler = [];
        
        // Tek tarih filtresi (geriye uyumluluk)
        if ($Tarih) {
            $WhereClause .= " AND CONVERT(date, la.EklemeZamani) = :Tarih";
            $Parametreler['Tarih'] = $Tarih;
        }
        
        // Tarih araligi - baslangic
        if ($TarihStart) {
            $WhereClause .= " AND CONVERT(date, la.EklemeZamani) >= :TarihStart";
            $Parametreler['TarihStart'] = $TarihStart;
        }
        
        // Tarih araligi - bitis
        if ($TarihEnd) {
            $WhereClause .= " AND CONVERT(date, la.EklemeZamani) <= :TarihEnd";
            $Parametreler['TarihEnd'] = $TarihEnd;
        }
        
        if ($Tablo) {
            $WhereClause .= " AND la.Tablo LIKE :Tablo";
            $Parametreler['Tablo'] = '%' . $Tablo . '%';
        }
        
        if ($Islem) {
            $WhereClause .= " AND la.Islem = :Islem";
            $Parametreler['Islem'] = strtoupper($Islem);
        }
        
        if ($KullaniciAdi) {
            $WhereClause .= " AND (u.KullaniciAdi LIKE :KullaniciAdi OR u.AdSoyad LIKE :KullaniciAdiSoyad)";
            $Parametreler['KullaniciAdi'] = '%' . $KullaniciAdi . '%';
            $Parametreler['KullaniciAdiSoyad'] = '%' . $KullaniciAdi . '%';
        }
        
        // Toplam kayit sayisini hesapla
        $CountSql = "SELECT COUNT(*) as Toplam
                FROM log_action la
                LEFT JOIN tnm_user u ON la.EkleyenUserId = u.Id
                {$WhereClause}";
        
        $CountStmt = $Db->prepare($CountSql);
        $CountStmt->execute($Parametreler);
        $ToplamKayit = (int) $CountStmt->fetchColumn();
        $ToplamSayfa = ceil($ToplamKayit / $Limit);
        
        // Verileri cek (pagination ile)
        $Sql = "SELECT 
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
                {$WhereClause}
                ORDER BY la.EklemeZamani DESC
                OFFSET {$Offset} ROWS FETCH NEXT {$Limit} ROWS ONLY";
        
        $Stmt = $Db->prepare($Sql);
        $Stmt->execute($Parametreler);
        $Loglar = $Stmt->fetchAll();
        
        Response::json([
            'success' => true,
            'data' => $Loglar,
            'pagination' => [
                'page' => $Sayfa,
                'limit' => $Limit,
                'total' => $ToplamKayit,
                'totalPages' => $ToplamSayfa,
                'hasNext' => $Sayfa < $ToplamSayfa,
                'hasPrev' => $Sayfa > 1
            ]
        ]);
    }
}
