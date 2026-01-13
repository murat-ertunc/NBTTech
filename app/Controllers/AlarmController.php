<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Database;

/**
 * AlarmController
 * 
 * Dashboard alarm sistemi icin endpoint'ler.
 * Temel alarm fonksiyonlari:
 * 1. Odenmemis faturalar
 * 2. Yaklasan takvim isleri
 * 3. Vadesi gecen teminatlar
 * 4. Sozlesme bitis yaklasanlari
 */
class AlarmController
{
    /**
     * Tum alarmlari getir
     * GET /api/alarms
     */
    public function index(): void
    {
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::unauthorized('Oturum gerekli');
        }

        $Alarmlar = [];
        
        // 1. Odenmemis faturalar
        $OdenmemisFaturalar = $this->odenmemisFaturalariGetir();
        if ($OdenmemisFaturalar['count'] > 0) {
            $Alarmlar[] = [
                'id' => 'unpaid_invoices',
                'type' => 'invoice',
                'priority' => 'high',
                'title' => 'Ödenmemiş Faturalar',
                'description' => $OdenmemisFaturalar['count'] . ' adet fatura ödeme bekliyor',
                'total' => $OdenmemisFaturalar['total'],
                'count' => $OdenmemisFaturalar['count'],
                'items' => $OdenmemisFaturalar['items']
            ];
        }

        // 2. Yaklasan takvim isleri (7 gun icinde)
        $YaklasanIsler = $this->yaklasanIsleriGetir(7);
        if ($YaklasanIsler['count'] > 0) {
            $Alarmlar[] = [
                'id' => 'upcoming_events',
                'type' => 'calendar',
                'priority' => 'medium',
                'title' => 'Yaklaşan İşler',
                'description' => 'Bu hafta ' . $YaklasanIsler['count'] . ' görev var',
                'count' => $YaklasanIsler['count'],
                'items' => $YaklasanIsler['items']
            ];
        }

        // 3. Vadesi gecen teminatlar
        $VadesiGecenTeminatlar = $this->vadesiGecenTeminatlariGetir();
        if ($VadesiGecenTeminatlar['count'] > 0) {
            $Alarmlar[] = [
                'id' => 'expired_guarantees',
                'type' => 'guarantee',
                'priority' => 'high',
                'title' => 'Vadesi Geçen Teminatlar',
                'description' => $VadesiGecenTeminatlar['count'] . ' teminatın vadesi geçmiş',
                'count' => $VadesiGecenTeminatlar['count'],
                'items' => $VadesiGecenTeminatlar['items']
            ];
        }

        // 4. Yaklasan sozlesme bitisleri (30 gun icinde)
        $BitenSozlesmeler = $this->bitenSozlesmeleriGetir(30);
        if ($BitenSozlesmeler['count'] > 0) {
            $Alarmlar[] = [
                'id' => 'expiring_contracts',
                'type' => 'contract',
                'priority' => 'medium',
                'title' => 'Sözleşme Bitiş Yaklaşıyor',
                'description' => $BitenSozlesmeler['count'] . ' sözleşme 30 gün içinde bitiyor',
                'count' => $BitenSozlesmeler['count'],
                'items' => $BitenSozlesmeler['items']
            ];
        }

        Response::json([
            'success' => true,
            'data' => $Alarmlar,
            'totalCount' => count($Alarmlar)
        ]);
    }

    /**
     * Odenmemis faturalari getir
     */
    private function odenmemisFaturalariGetir(): array
    {
        try {
            $Db = Database::connection();
            
            // Fatura - Odeme farki ile odenmemis faturalari bul
            $Sql = "
                SELECT 
                    f.Id,
                    f.MusteriId,
                    m.Unvan as MusteriUnvan,
                    f.Tarih,
                    f.Tutar,
                    f.DovizCinsi,
                    f.Aciklama,
                    ISNULL(
                        (SELECT SUM(Tutar) FROM tbl_odeme WHERE MusteriId = f.MusteriId AND Sil = 0), 
                        0
                    ) as ToplamOdeme
                FROM tbl_fatura f
                LEFT JOIN tbl_musteri m ON f.MusteriId = m.Id
                WHERE f.Sil = 0 
                  AND m.Sil = 0
                ORDER BY f.Tarih DESC
            ";
            
            $Stmt = $Db->query($Sql);
            $Faturalar = $Stmt->fetchAll();
            
            $OdenmemisKalemler = [];
            $ToplamOdenmemis = 0;
            
            // Musteri bazinda odenmemis faturalari hesapla
            $MusteriToplamlari = [];
            
            foreach ($Faturalar as $Fatura) {
                $MusteriId = $Fatura['MusteriId'];
                if (!isset($MusteriToplamlari[$MusteriId])) {
                    $MusteriToplamlari[$MusteriId] = [
                        'FaturaToplami' => 0,
                        'OdemeToplami' => (float)$Fatura['ToplamOdeme'],
                        'Musteri' => $Fatura['MusteriUnvan'],
                        'DovizCinsi' => $Fatura['DovizCinsi'] ?? 'TRY'
                    ];
                }
                $MusteriToplamlari[$MusteriId]['FaturaToplami'] += (float)$Fatura['Tutar'];
            }
            
            foreach ($MusteriToplamlari as $MusteriId => $Veri) {
                $Odenmemis = $Veri['FaturaToplami'] - $Veri['OdemeToplami'];
                if ($Odenmemis > 0) {
                    $OdenmemisKalemler[] = [
                        'customerId' => $MusteriId,
                        'customer' => $Veri['Musteri'],
                        'amount' => $Odenmemis,
                        'currency' => $Veri['DovizCinsi']
                    ];
                    $ToplamOdenmemis += $Odenmemis;
                }
            }
            
            return [
                'count' => count($OdenmemisKalemler),
                'total' => $ToplamOdenmemis,
                'items' => array_slice($OdenmemisKalemler, 0, 5)
            ];
        } catch (\Exception $e) {
            return ['count' => 0, 'total' => 0, 'items' => []];
        }
    }

    /**
     * Yaklasan takvim islerini getir
     */
    private function yaklasanIsleriGetir(int $Gun = 7): array
    {
        try {
            $Db = Database::connection();
            
            // Proje bitis tarihleri yaklasan projeler
            $Sql = "
                SELECT 
                    p.Id,
                    p.MusteriId,
                    m.Unvan as MusteriUnvan,
                    p.ProjeAdi,
                    p.BitisTarihi,
                    'project' as EventType
                FROM tbl_proje p
                LEFT JOIN tbl_musteri m ON p.MusteriId = m.Id
                WHERE p.Sil = 0
                  AND p.Durum = 1
                  AND p.BitisTarihi IS NOT NULL
                  AND p.BitisTarihi BETWEEN GETDATE() AND DATEADD(day, :gun, GETDATE())
                ORDER BY p.BitisTarihi ASC
            ";
            
            $Stmt = $Db->prepare($Sql); 
            $Stmt->execute(['gun' => $Gun]);
            $Projeler = $Stmt->fetchAll();
            
            $Kalemler = [];
            foreach ($Projeler as $Proje) {
                $Kalemler[] = [
                    'id' => $Proje['Id'],
                    'type' => 'project',
                    'customerId' => $Proje['MusteriId'],
                    'customer' => $Proje['MusteriUnvan'],
                    'title' => $Proje['ProjeAdi'],
                    'date' => $Proje['BitisTarihi']
                ];
            }
            
            return [
                'count' => count($Kalemler),
                'items' => array_slice($Kalemler, 0, 10)
            ];
        } catch (\Exception $e) {
            return ['count' => 0, 'items' => []];
        }
    }

    /**
     * Vadesi gecen teminatlari getir
     */
    private function vadesiGecenTeminatlariGetir(): array
    {
        try {
            $Db = Database::connection();
            
            $Sql = "
                SELECT 
                    t.Id,
                    t.MusteriId,
                    m.Unvan as MusteriUnvan,
                    t.BelgeNo,
                    t.Tur,
                    t.Tutar,
                    t.DovizCinsi,
                    t.VadeTarihi
                FROM tbl_teminat t
                LEFT JOIN tbl_musteri m ON t.MusteriId = m.Id
                WHERE t.Sil = 0
                  AND t.Durum = 1
                  AND t.VadeTarihi < GETDATE()
                ORDER BY t.VadeTarihi ASC
            ";
            
            $Stmt = $Db->query($Sql);
            $Teminatlar = $Stmt->fetchAll();
            
            $Kalemler = [];
            foreach ($Teminatlar as $Teminat) {
                $Kalemler[] = [
                    'id' => $Teminat['Id'],
                    'customerId' => $Teminat['MusteriId'],
                    'customer' => $Teminat['MusteriUnvan'],
                    'documentNo' => $Teminat['BelgeNo'],
                    'type' => $Teminat['Tur'],
                    'amount' => $Teminat['Tutar'],
                    'currency' => $Teminat['DovizCinsi'],
                    'dueDate' => $Teminat['VadeTarihi']
                ];
            }
            
            return [
                'count' => count($Kalemler),
                'items' => array_slice($Kalemler, 0, 5)
            ];
        } catch (\Exception $e) {
            return ['count' => 0, 'items' => []];
        }
    }

    /**
     * Yaklasan sozlesme bitislerini getir
     */
    private function bitenSozlesmeleriGetir(int $Gun = 30): array
    {
        try {
            $Db = Database::connection();
            
            $Sql = "
                SELECT 
                    s.Id,
                    s.MusteriId,
                    m.Unvan as MusteriUnvan,
                    s.SozlesmeNo,
                    s.BitisTarihi,
                    s.Tutar,
                    s.DovizCinsi
                FROM tbl_sozlesme s
                LEFT JOIN tbl_musteri m ON s.MusteriId = m.Id
                WHERE s.Sil = 0
                  AND s.Durum = 1
                  AND s.BitisTarihi IS NOT NULL
                  AND s.BitisTarihi BETWEEN GETDATE() AND DATEADD(day, :gun, GETDATE())
                ORDER BY s.BitisTarihi ASC
            ";
            
            $Stmt = $Db->prepare($Sql); 
            $Stmt->execute(['gun' => $Gun]);
            $Sozlesmeler = $Stmt->fetchAll();
            
            $Kalemler = [];
            foreach ($Sozlesmeler as $Sozlesme) {
                $Kalemler[] = [
                    'id' => $Sozlesme['Id'],
                    'customerId' => $Sozlesme['MusteriId'],
                    'customer' => $Sozlesme['MusteriUnvan'],
                    'contractNo' => $Sozlesme['SozlesmeNo'],
                    'endDate' => $Sozlesme['BitisTarihi'],
                    'amount' => $Sozlesme['Tutar'],
                    'currency' => $Sozlesme['DovizCinsi']
                ];
            }
            
            return [
                'count' => count($Kalemler),
                'items' => array_slice($Kalemler, 0, 5)
            ];
        } catch (\Exception $e) {
            return ['count' => 0, 'items' => []];
        }
    }
}
