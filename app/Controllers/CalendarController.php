<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Database;

/**
 * CalendarController
 * 
 * Dashboard takvim sistemi icin endpoint'ler.
 * Musteriye bagli etkinlikler (proje tarihleri, sozlesme tarihleri, vb.)
 */
class CalendarController
{
    /**
     * Takvim etkinliklerini getir
     * GET /api/calendar
     * 
     * Query params:
     * - customerId: Belirli musteriye ait etkinlikler
     * - month: Ay (1-12)
     * - year: Yil
     * - includeCompleted: Tamamlanan isleri dahil et (0/1)
     */
    public function index(): void
    {
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::unauthorized('Oturum gerekli');
            return;
        }

        $MusteriId = isset($_GET['customerId']) ? (int)$_GET['customerId'] : null;
        $Ay = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        $Yil = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $TamamlananlarDahil = isset($_GET['includeCompleted']) && $_GET['includeCompleted'] === '1';

        $Etkinlikler = [];

        // 1. Proje baslangic ve bitis tarihleri
        $ProjeEtkinlikleri = $this->getProjectEvents($MusteriId, $Ay, $Yil, $TamamlananlarDahil);
        $Etkinlikler = array_merge($Etkinlikler, $ProjeEtkinlikleri);

        // 2. Sozlesme baslangic ve bitis tarihleri
        $SozlesmeEtkinlikleri = $this->getContractEvents($MusteriId, $Ay, $Yil, $TamamlananlarDahil);
        $Etkinlikler = array_merge($Etkinlikler, $SozlesmeEtkinlikleri);

        // 3. Teminat Termin Tarihi tarihleri
        $TeminatEtkinlikleri = $this->getGuaranteeEvents($MusteriId, $Ay, $Yil, $TamamlananlarDahil);
        $Etkinlikler = array_merge($Etkinlikler, $TeminatEtkinlikleri);

        // 4. Fatura tarihleri
        $FaturaEtkinlikleri = $this->getInvoiceEvents($MusteriId, $Ay, $Yil);
        $Etkinlikler = array_merge($Etkinlikler, $FaturaEtkinlikleri);

        // 5. Takvim kayitlari (tbl_takvim)
        $TakvimEtkinlikleri = $this->getTakvimEvents($MusteriId, $Ay, $Yil);
        $Etkinlikler = array_merge($Etkinlikler, $TakvimEtkinlikleri);

        // Tarihe gore sirala
        usort($Etkinlikler, function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });

        Response::json([
            'success' => true,
            'data' => $Etkinlikler,
            'meta' => [
                'month' => $Ay,
                'year' => $Yil,
                'customerId' => $MusteriId,
                'totalCount' => count($Etkinlikler)
            ]
        ]);
    }

    /**
     * Belirli bir gundeki etkinlikleri getir
     * GET /api/calendar/day/{date}
     */
    public function day(string $date): void
    {
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::unauthorized('Oturum gerekli');
            return;
        }

        // Date format: YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            Response::badRequest('Gecersiz tarih formati. YYYY-MM-DD olmali.');
            return;
        }

        $MusteriId = isset($_GET['customerId']) ? (int)$_GET['customerId'] : null;
        
        $Etkinlikler = $this->getEventsForDay($date, $MusteriId);

        Response::json([
            'success' => true,
            'data' => $Etkinlikler,
            'date' => $date
        ]);
    }

    /**
     * Proje etkinliklerini getir
     */
    private function getProjectEvents(?int $MusteriId, int $Ay, int $Yil, bool $TamamlananlarDahil): array
    {
        try {
            $Db = Database::connection();
            
            $Kosullar = "p.Sil = 0";
            $Parametreler = ['month' => $Ay, 'year' => $Yil];
            
            if ($MusteriId) {
                $Kosullar .= " AND p.MusteriId = :customerId";
                $Parametreler['customerId'] = $MusteriId;
            }
            
            if (!$TamamlananlarDahil) {
                $Kosullar .= " AND p.Durum = 1"; // Sadece aktif projeler
            }
            
            $Sql = "
                SELECT 
                    p.Id,
                    p.MusteriId,
                    m.Unvan as MusteriUnvan,
                    p.ProjeAdi,
                    p.BaslangicTarihi,
                    p.BitisTarihi,
                    p.Durum
                FROM tbl_proje p
                LEFT JOIN tbl_musteri m ON p.MusteriId = m.Id
                WHERE {$Kosullar}
                  AND (
                    (MONTH(p.BaslangicTarihi) = :month AND YEAR(p.BaslangicTarihi) = :year)
                    OR (MONTH(p.BitisTarihi) = :month AND YEAR(p.BitisTarihi) = :year)
                  )
                ORDER BY p.BaslangicTarihi ASC
            ";
            
            $Stmt = $Db->prepare($Sql); 
            $Stmt->execute($Parametreler);
            $Projeler = $Stmt->fetchAll();
            
            $Etkinlikler = [];
            foreach ($Projeler as $Proje) {
                // Baslangic tarihi
                if ($Proje['BaslangicTarihi']) {
                    $BaslangicAy = (int)date('n', strtotime($Proje['BaslangicTarihi']));
                    $BaslangicYil = (int)date('Y', strtotime($Proje['BaslangicTarihi']));
                    if ($BaslangicAy === $Ay && $BaslangicYil === $Yil) {
                        $Etkinlikler[] = [
                            'id' => 'project_start_' . $Proje['Id'],
                            'type' => 'project_start',
                            'category' => 'project',
                            'customerId' => $Proje['MusteriId'],
                            'customer' => $Proje['MusteriUnvan'],
                            'title' => 'Proje Baslangic: ' . $Proje['ProjeAdi'],
                            'date' => $Proje['BaslangicTarihi'],
                            'color' => '#198754', // green
                            'completed' => $Proje['Durum'] != 1,
                            'relatedId' => $Proje['Id'],
                            'relatedType' => 'project'
                        ];
                    }
                }
                
                // Bitis tarihi
                if ($Proje['BitisTarihi']) {
                    $BitisAy = (int)date('n', strtotime($Proje['BitisTarihi']));
                    $BitisYil = (int)date('Y', strtotime($Proje['BitisTarihi']));
                    if ($BitisAy === $Ay && $BitisYil === $Yil) {
                        $Etkinlikler[] = [
                            'id' => 'project_end_' . $Proje['Id'],
                            'type' => 'project_end',
                            'category' => 'project',
                            'customerId' => $Proje['MusteriId'],
                            'customer' => $Proje['MusteriUnvan'],
                            'title' => 'Proje Bitis: ' . $Proje['ProjeAdi'],
                            'date' => $Proje['BitisTarihi'],
                            'color' => '#dc3545', // red
                            'completed' => $Proje['Durum'] != 1,
                            'relatedId' => $Proje['Id'],
                            'relatedType' => 'project'
                        ];
                    }
                }
            }
            
            return $Etkinlikler;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Sozlesme etkinliklerini getir
     */
    private function getContractEvents(?int $MusteriId, int $Ay, int $Yil, bool $TamamlananlarDahil): array
    {
        try {
            $Db = Database::connection();
            
            $Kosullar = "s.Sil = 0";
            $Parametreler = ['month' => $Ay, 'year' => $Yil];
            
            if ($MusteriId) {
                $Kosullar .= " AND s.MusteriId = :customerId";
                $Parametreler['customerId'] = $MusteriId;
            }
            
            if (!$TamamlananlarDahil) {
                $Kosullar .= " AND s.Durum = 1";
            }
            
            $Sql = "
                SELECT 
                    s.Id,
                    s.MusteriId,
                    m.Unvan as MusteriUnvan,
                    s.SozlesmeTarihi,
                    s.Durum
                FROM tbl_sozlesme s
                LEFT JOIN tbl_musteri m ON s.MusteriId = m.Id
                WHERE {$Kosullar}
                  AND MONTH(s.SozlesmeTarihi) = :month 
                  AND YEAR(s.SozlesmeTarihi) = :year
                ORDER BY s.SozlesmeTarihi ASC
            ";
            
            $Stmt = $Db->prepare($Sql); 
            $Stmt->execute($Parametreler);
            $Sozlesmeler = $Stmt->fetchAll();
            
            $Etkinlikler = [];
            foreach ($Sozlesmeler as $Sozlesme) {
                if ($Sozlesme['SozlesmeTarihi']) {
                    $SozlesmeAy = (int)date('n', strtotime($Sozlesme['SozlesmeTarihi']));
                    $SozlesmeYil = (int)date('Y', strtotime($Sozlesme['SozlesmeTarihi']));
                    if ($SozlesmeAy === $Ay && $SozlesmeYil === $Yil) {
                        $Etkinlikler[] = [
                            'id' => 'contract_' . $Sozlesme['Id'],
                            'type' => 'contract',
                            'category' => 'contract',
                            'customerId' => $Sozlesme['MusteriId'],
                            'customer' => $Sozlesme['MusteriUnvan'],
                            'date' => $Sozlesme['SozlesmeTarihi'],
                            'color' => '#0d6efd', // blue
                            'completed' => $Sozlesme['Durum'] != 1,
                            'relatedId' => $Sozlesme['Id'],
                            'relatedType' => 'contract'
                        ];
                    }
                }
                    }
                }
            }
            
            return $Etkinlikler;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Teminat termin tarihi etkinliklerini getir
     */
    private function getGuaranteeEvents(?int $MusteriId, int $Ay, int $Yil, bool $TamamlananlarDahil): array
    {
        try {
            $Db = Database::connection();
            
            $Kosullar = "t.Sil = 0";
            $Parametreler = ['month' => $Ay, 'year' => $Yil];
            
            if ($MusteriId) {
                $Kosullar .= " AND t.MusteriId = :customerId";
                $Parametreler['customerId'] = $MusteriId;
            }
            
            if (!$TamamlananlarDahil) {
                $Kosullar .= " AND t.Durum = 1"; // Sadece bekleyen teminatlar
            }
            
            $Sql = "
                SELECT 
                    t.Id,
                    t.MusteriId,
                    m.Unvan as MusteriUnvan,
                    t.Tur,
                    t.TerminTarihi,
                    t.Tutar,
                    t.ParaBirimi,
                    t.Durum
                FROM tbl_teminat t
                LEFT JOIN tbl_musteri m ON t.MusteriId = m.Id
                WHERE {$Kosullar}
                  AND MONTH(t.TerminTarihi) = :month 
                  AND YEAR(t.TerminTarihi) = :year
                ORDER BY t.TerminTarihi ASC
            ";
            
            $Stmt = $Db->prepare($Sql); 
            $Stmt->execute($Parametreler);
            $Teminatlar = $Stmt->fetchAll();
            
            $Etkinlikler = [];
            foreach ($Teminatlar as $Teminat) {
                $Etkinlikler[] = [
                    'id' => 'guarantee_' . $Teminat['Id'],
                    'type' => 'guarantee_due',
                    'category' => 'guarantee',
                    'customerId' => $Teminat['MusteriId'],
                    'customer' => $Teminat['MusteriUnvan'],
                    'title' => 'Teminat Termin Tarihi: ' . $Teminat['Tur'],
                    'date' => $Teminat['TerminTarihi'],
                    'color' => '#fd7e14', // orange
                    'completed' => $Teminat['Durum'] != 1,
                    'relatedId' => $Teminat['Id'],
                    'relatedType' => 'guarantee',
                    'amount' => $Teminat['Tutar'],
                    'currency' => $Teminat['ParaBirimi']
                ];
            }
            
            return $Etkinlikler;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Fatura tarihlerini getir
     */
    private function getInvoiceEvents(?int $MusteriId, int $Ay, int $Yil): array
    {
        try {
            $Db = Database::connection();
            
            $Kosullar = "f.Sil = 0";
            $Parametreler = ['month' => $Ay, 'year' => $Yil];
            
            if ($MusteriId) {
                $Kosullar .= " AND f.MusteriId = :customerId";
                $Parametreler['customerId'] = $MusteriId;
            }
            
            $Sql = "
                SELECT 
                    f.Id,
                    f.MusteriId,
                    m.Unvan as MusteriUnvan,
                    f.Tarih,
                    f.Tutar,
                    f.DovizCinsi,
                    f.Aciklama
                FROM tbl_fatura f
                LEFT JOIN tbl_musteri m ON f.MusteriId = m.Id
                WHERE {$Kosullar}
                  AND MONTH(f.Tarih) = :month 
                  AND YEAR(f.Tarih) = :year
                ORDER BY f.Tarih ASC
            ";
            
            $Stmt = $Db->prepare($Sql); 
            $Stmt->execute($Parametreler);
            $Faturalar = $Stmt->fetchAll();
            
            $Etkinlikler = [];
            foreach ($Faturalar as $Fatura) {
                $Etkinlikler[] = [
                    'id' => 'invoice_' . $Fatura['Id'],
                    'type' => 'invoice',
                    'category' => 'invoice',
                    'customerId' => $Fatura['MusteriId'],
                    'customer' => $Fatura['MusteriUnvan'],
                    'title' => 'Fatura: ' . ($Fatura['Aciklama'] ?: 'Fatura #' . $Fatura['Id']),
                    'date' => $Fatura['Tarih'],
                    'color' => '#20c997', // teal
                    'completed' => false,
                    'relatedId' => $Fatura['Id'],
                    'relatedType' => 'invoice',
                    'amount' => $Fatura['Tutar'],
                    'currency' => $Fatura['DovizCinsi']
                ];
            }
            
            return $Etkinlikler;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Belirli bir gundeki etkinlikleri getir
     */
    private function getEventsForDay(string $Tarih, ?int $MusteriId): array
    {
        try {
            $Db = Database::connection();
            $Etkinlikler = [];
            $Parametreler = ['date' => $Tarih];
            
            $MusteriKosulu = $MusteriId ? " AND MusteriId = :customerId" : "";
            if ($MusteriId) {
                $Parametreler['customerId'] = $MusteriId;
            }
            
            // Projeler
            $Sql = "
                SELECT Id, MusteriId, ProjeAdi, 
                       CASE WHEN CAST(BaslangicTarihi AS DATE) = :date THEN 'start' ELSE 'end' END as EventType
                FROM tbl_proje 
                WHERE Sil = 0 {$MusteriKosulu}
                  AND (CAST(BaslangicTarihi AS DATE) = :date OR CAST(BitisTarihi AS DATE) = :date)
            ";
            $Stmt = $Db->prepare($Sql); 
            $Stmt->execute($Parametreler);
            $Projeler = $Stmt->fetchAll();
            
            foreach ($Projeler as $Proje) {
                $Etkinlikler[] = [
                    'id' => $Proje['Id'],
                    'type' => 'project',
                    'eventType' => $Proje['EventType'],
                    'title' => $Proje['ProjeAdi'],
                    'customerId' => $Proje['MusteriId']
                ];
            }
            
            // Sozlesmeler
            $Sql = "
                SELECT Id, MusteriId,
                       CASE WHEN CAST(BaslangicTarihi AS DATE) = :date THEN 'start' ELSE 'end' END as EventType
                FROM tbl_sozlesme 
                WHERE Sil = 0 {$MusteriKosulu}
                  AND (CAST(BaslangicTarihi AS DATE) = :date OR CAST(BitisTarihi AS DATE) = :date)
            ";
            $Stmt = $Db->prepare($Sql); 
            $Stmt->execute($Parametreler);
            $Sozlesmeler = $Stmt->fetchAll();
            
            foreach ($Sozlesmeler as $Sozlesme) {
                $Etkinlikler[] = [
                    'id' => $Sozlesme['Id'],
                    'type' => 'contract',
                    'eventType' => $Sozlesme['EventType'],
                    'title' => 'Sözleşme',
                    'customerId' => $Sozlesme['MusteriId']
                ];
            }
            
            // Teminatlar
            $Sql = "
                SELECT Id, MusteriId, Tur
                FROM tbl_teminat 
                WHERE Sil = 0 {$MusteriKosulu}
                  AND CAST(TerminTarihi AS DATE) = :date
            ";
            $Stmt = $Db->prepare($Sql); 
            $Stmt->execute($Parametreler);
            $Teminatlar = $Stmt->fetchAll();
            
            foreach ($Teminatlar as $Teminat) {
                $Etkinlikler[] = [
                    'id' => $Teminat['Id'],
                    'type' => 'guarantee',
                    'eventType' => 'due',
                    'title' => $Teminat['Tur'],
                    'customerId' => $Teminat['MusteriId']
                ];
            }
            
            // Faturalar
            $Sql = "
                SELECT Id, MusteriId, Aciklama
                FROM tbl_fatura 
                WHERE Sil = 0 {$MusteriKosulu}
                  AND CAST(Tarih AS DATE) = :date
            ";
            $Stmt = $Db->prepare($Sql); 
            $Stmt->execute($Parametreler);
            $Faturalar = $Stmt->fetchAll();
            
            foreach ($Faturalar as $Fatura) {
                $Etkinlikler[] = [
                    'id' => $Fatura['Id'],
                    'type' => 'invoice',
                    'eventType' => 'created',
                    'title' => $Fatura['Aciklama'] ?: 'Fatura #' . $Fatura['Id'],
                    'customerId' => $Fatura['MusteriId']
                ];
            }
            
            // Takvim kayitlari
            $Sql = "
                SELECT t.Id, t.MusteriId, t.Ozet, m.Unvan as MusteriUnvan
                FROM tbl_takvim t
                LEFT JOIN tbl_musteri m ON t.MusteriId = m.Id
                WHERE t.Sil = 0 
                  AND CAST(t.TerminTarihi AS DATE) = :date
            ";
            if ($MusteriId) {
                $Sql .= " AND t.MusteriId = :customerId";
            }
            $Stmt = $Db->prepare($Sql); 
            $Stmt->execute($Parametreler);
            $TakvimKayitlari = $Stmt->fetchAll();
            
            foreach ($TakvimKayitlari as $Kayit) {
                $Etkinlikler[] = [
                    'id' => $Kayit['Id'],
                    'type' => 'takvim',
                    'eventType' => 'reminder',
                    'title' => $Kayit['Ozet'],
                    'customer' => $Kayit['MusteriUnvan'],
                    'customerId' => $Kayit['MusteriId']
                ];
            }
            
            return $Etkinlikler;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Takvim kayitlarini getir (tbl_takvim)
     */
    private function getTakvimEvents(?int $MusteriId, int $Ay, int $Yil): array
    {
        try {
            $Db = Database::connection();
            
            $Kosullar = "t.Sil = 0";
            $Parametreler = ['month' => $Ay, 'year' => $Yil];
            
            if ($MusteriId) {
                $Kosullar .= " AND t.MusteriId = :customerId";
                $Parametreler['customerId'] = $MusteriId;
            }
            
            $Sql = "
                SELECT 
                    t.Id,
                    t.MusteriId,
                    m.Unvan as MusteriUnvan,
                    t.ProjeId,
                    p.ProjeAdi,
                    t.TerminTarihi,
                    t.Ozet
                FROM tbl_takvim t
                LEFT JOIN tbl_musteri m ON t.MusteriId = m.Id
                LEFT JOIN tbl_proje p ON t.ProjeId = p.Id
                WHERE {$Kosullar}
                  AND MONTH(t.TerminTarihi) = :month AND YEAR(t.TerminTarihi) = :year
                ORDER BY t.TerminTarihi ASC
            ";
            
            $Stmt = $Db->prepare($Sql); 
            $Stmt->execute($Parametreler);
            $TakvimKayitlari = $Stmt->fetchAll();
            
            $Etkinlikler = [];
            foreach ($TakvimKayitlari as $Kayit) {
                $Etkinlikler[] = [
                    'id' => 'takvim_' . $Kayit['Id'],
                    'type' => 'takvim',
                    'category' => 'takvim',
                    'customerId' => $Kayit['MusteriId'],
                    'customer' => $Kayit['MusteriUnvan'],
                    'title' => $Kayit['Ozet'],
                    'description' => $Kayit['ProjeAdi'] ? 'Proje: ' . $Kayit['ProjeAdi'] : null,
                    'date' => $Kayit['TerminTarihi'],
                    'color' => '#198754', // green
                    'completed' => false,
                    'relatedId' => $Kayit['Id'],
                    'relatedType' => 'takvim'
                ];
            }
            
            return $Etkinlikler;
        } catch (\Exception $e) {
            return [];
        }
    }
}
