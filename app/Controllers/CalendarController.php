<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Database;

/**
 * CalendarController
 * 
 * Dashboard takvim sistemi için endpoint'ler.
 * Müşteriye bağlı etkinlikler (proje tarihleri, sözleşme tarihleri, vb.)
 */
class CalendarController
{
    /**
     * Takvim etkinliklerini getir
     * GET /api/calendar
     * 
     * Query params:
     * - customerId: Belirli müşteriye ait etkinlikler
     * - month: Ay (1-12)
     * - year: Yıl
     * - includeCompleted: Tamamlanan işleri dahil et (0/1)
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

        // 1. Proje başlangıç ve bitiş tarihleri
        $ProjeEtkinlikleri = $this->getProjectEvents($MusteriId, $Ay, $Yil, $TamamlananlarDahil);
        $Etkinlikler = array_merge($Etkinlikler, $ProjeEtkinlikleri);

        // 2. Sözleşme başlangıç ve bitiş tarihleri
        $SozlesmeEtkinlikleri = $this->getContractEvents($MusteriId, $Ay, $Yil, $TamamlananlarDahil);
        $Etkinlikler = array_merge($Etkinlikler, $SozlesmeEtkinlikleri);

        // 3. Teminat vade tarihleri
        $TeminatEtkinlikleri = $this->getGuaranteeEvents($MusteriId, $Ay, $Yil, $TamamlananlarDahil);
        $Etkinlikler = array_merge($Etkinlikler, $TeminatEtkinlikleri);

        // 4. Fatura tarihleri
        $FaturaEtkinlikleri = $this->getInvoiceEvents($MusteriId, $Ay, $Yil);
        $Etkinlikler = array_merge($Etkinlikler, $FaturaEtkinlikleri);

        // Tarihe göre sırala
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
     * Belirli bir gündeki etkinlikleri getir
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
            Response::badRequest('Geçersiz tarih formatı. YYYY-MM-DD olmalı.');
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
                // Başlangıç tarihi
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
                            'title' => 'Proje Başlangıç: ' . $Proje['ProjeAdi'],
                            'date' => $Proje['BaslangicTarihi'],
                            'color' => '#198754', // green
                            'completed' => $Proje['Durum'] != 1,
                            'relatedId' => $Proje['Id'],
                            'relatedType' => 'project'
                        ];
                    }
                }
                
                // Bitiş tarihi
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
                            'title' => 'Proje Bitiş: ' . $Proje['ProjeAdi'],
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
     * Sözleşme etkinliklerini getir
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
                    s.SozlesmeNo,
                    s.BaslangicTarihi,
                    s.BitisTarihi,
                    s.Durum
                FROM tbl_sozlesme s
                LEFT JOIN tbl_musteri m ON s.MusteriId = m.Id
                WHERE {$Kosullar}
                  AND (
                    (MONTH(s.BaslangicTarihi) = :month AND YEAR(s.BaslangicTarihi) = :year)
                    OR (MONTH(s.BitisTarihi) = :month AND YEAR(s.BitisTarihi) = :year)
                  )
                ORDER BY s.BaslangicTarihi ASC
            ";
            
            $Stmt = $Db->prepare($Sql); 
            $Stmt->execute($Parametreler);
            $Sozlesmeler = $Stmt->fetchAll();
            
            $Etkinlikler = [];
            foreach ($Sozlesmeler as $Sozlesme) {
                if ($Sozlesme['BaslangicTarihi']) {
                    $BaslangicAy = (int)date('n', strtotime($Sozlesme['BaslangicTarihi']));
                    $BaslangicYil = (int)date('Y', strtotime($Sozlesme['BaslangicTarihi']));
                    if ($BaslangicAy === $Ay && $BaslangicYil === $Yil) {
                        $Etkinlikler[] = [
                            'id' => 'contract_start_' . $Sozlesme['Id'],
                            'type' => 'contract_start',
                            'category' => 'contract',
                            'customerId' => $Sozlesme['MusteriId'],
                            'customer' => $Sozlesme['MusteriUnvan'],
                            'title' => 'Sözleşme Başlangıç: ' . $Sozlesme['SozlesmeNo'],
                            'date' => $Sozlesme['BaslangicTarihi'],
                            'color' => '#0d6efd', // blue
                            'completed' => $Sozlesme['Durum'] != 1,
                            'relatedId' => $Sozlesme['Id'],
                            'relatedType' => 'contract'
                        ];
                    }
                }
                
                if ($Sozlesme['BitisTarihi']) {
                    $BitisAy = (int)date('n', strtotime($Sozlesme['BitisTarihi']));
                    $BitisYil = (int)date('Y', strtotime($Sozlesme['BitisTarihi']));
                    if ($BitisAy === $Ay && $BitisYil === $Yil) {
                        $Etkinlikler[] = [
                            'id' => 'contract_end_' . $Sozlesme['Id'],
                            'type' => 'contract_end',
                            'category' => 'contract',
                            'customerId' => $Sozlesme['MusteriId'],
                            'customer' => $Sozlesme['MusteriUnvan'],
                            'title' => 'Sözleşme Bitiş: ' . $Sozlesme['SozlesmeNo'],
                            'date' => $Sozlesme['BitisTarihi'],
                            'color' => '#6f42c1', // purple
                            'completed' => $Sozlesme['Durum'] != 1,
                            'relatedId' => $Sozlesme['Id'],
                            'relatedType' => 'contract'
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
     * Teminat vade etkinliklerini getir
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
                    t.BelgeNo,
                    t.Tur,
                    t.VadeTarihi,
                    t.Tutar,
                    t.DovizCinsi,
                    t.Durum
                FROM tbl_teminat t
                LEFT JOIN tbl_musteri m ON t.MusteriId = m.Id
                WHERE {$Kosullar}
                  AND MONTH(t.VadeTarihi) = :month 
                  AND YEAR(t.VadeTarihi) = :year
                ORDER BY t.VadeTarihi ASC
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
                    'title' => 'Teminat Vadesi: ' . $Teminat['BelgeNo'] . ' (' . $Teminat['Tur'] . ')',
                    'date' => $Teminat['VadeTarihi'],
                    'color' => '#fd7e14', // orange
                    'completed' => $Teminat['Durum'] != 1,
                    'relatedId' => $Teminat['Id'],
                    'relatedType' => 'guarantee',
                    'amount' => $Teminat['Tutar'],
                    'currency' => $Teminat['DovizCinsi']
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
     * Belirli bir gündeki etkinlikleri getir
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
            
            // Sözleşmeler
            $Sql = "
                SELECT Id, MusteriId, SozlesmeNo,
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
                    'title' => $Sozlesme['SozlesmeNo'],
                    'customerId' => $Sozlesme['MusteriId']
                ];
            }
            
            // Teminatlar
            $Sql = "
                SELECT Id, MusteriId, BelgeNo, Tur
                FROM tbl_teminat 
                WHERE Sil = 0 {$MusteriKosulu}
                  AND CAST(VadeTarihi AS DATE) = :date
            ";
            $Stmt = $Db->prepare($Sql); 
            $Stmt->execute($Parametreler);
            $Teminatlar = $Stmt->fetchAll();
            
            foreach ($Teminatlar as $Teminat) {
                $Etkinlikler[] = [
                    'id' => $Teminat['Id'],
                    'type' => 'guarantee',
                    'eventType' => 'due',
                    'title' => $Teminat['BelgeNo'] . ' (' . $Teminat['Tur'] . ')',
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
            
            return $Etkinlikler;
        } catch (\Exception $e) {
            return [];
        }
    }
}
