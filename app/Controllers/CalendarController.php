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
        $userId = Context::kullaniciId();
        if (!$userId) {
            Response::unauthorized('Oturum gerekli');
        }

        $customerId = isset($_GET['customerId']) ? (int)$_GET['customerId'] : null;
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $includeCompleted = isset($_GET['includeCompleted']) && $_GET['includeCompleted'] === '1';

        $events = [];

        // 1. Proje başlangıç ve bitiş tarihleri
        $projectEvents = $this->getProjectEvents($customerId, $month, $year, $includeCompleted);
        $events = array_merge($events, $projectEvents);

        // 2. Sözleşme başlangıç ve bitiş tarihleri
        $contractEvents = $this->getContractEvents($customerId, $month, $year, $includeCompleted);
        $events = array_merge($events, $contractEvents);

        // 3. Teminat vade tarihleri
        $guaranteeEvents = $this->getGuaranteeEvents($customerId, $month, $year, $includeCompleted);
        $events = array_merge($events, $guaranteeEvents);

        // 4. Fatura tarihleri
        $invoiceEvents = $this->getInvoiceEvents($customerId, $month, $year);
        $events = array_merge($events, $invoiceEvents);

        // Tarihe göre sırala
        usort($events, function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });

        Response::json([
            'success' => true,
            'data' => $events,
            'meta' => [
                'month' => $month,
                'year' => $year,
                'customerId' => $customerId,
                'totalCount' => count($events)
            ]
        ]);
    }

    /**
     * Belirli bir gündeki etkinlikleri getir
     * GET /api/calendar/day/{date}
     */
    public function day(string $date): void
    {
        $userId = Context::kullaniciId();
        if (!$userId) {
            Response::unauthorized('Oturum gerekli');
        }

        // Date format: YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            Response::badRequest('Geçersiz tarih formatı. YYYY-MM-DD olmalı.');
        }

        $customerId = isset($_GET['customerId']) ? (int)$_GET['customerId'] : null;
        
        $events = $this->getEventsForDay($date, $customerId);

        Response::json([
            'success' => true,
            'data' => $events,
            'date' => $date
        ]);
    }

    /**
     * Proje etkinliklerini getir
     */
    private function getProjectEvents(?int $customerId, int $month, int $year, bool $includeCompleted): array
    {
        try {
            $db = Database::connection();
            
            $conditions = "p.Sil = 0";
            $params = ['month' => $month, 'year' => $year];
            
            if ($customerId) {
                $conditions .= " AND p.MusteriId = :customerId";
                $params['customerId'] = $customerId;
            }
            
            if (!$includeCompleted) {
                $conditions .= " AND p.Durum = 1"; // Sadece aktif projeler
            }
            
            $sql = "
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
                WHERE {$conditions}
                  AND (
                    (MONTH(p.BaslangicTarihi) = :month AND YEAR(p.BaslangicTarihi) = :year)
                    OR (MONTH(p.BitisTarihi) = :month AND YEAR(p.BitisTarihi) = :year)
                  )
                ORDER BY p.BaslangicTarihi ASC
            ";
            
            $stmt = $db->prepare($sql); $stmt->execute($params);
            $projects = $stmt->fetchAll();
            
            $events = [];
            foreach ($projects as $p) {
                // Başlangıç tarihi
                if ($p['BaslangicTarihi']) {
                    $startMonth = (int)date('n', strtotime($p['BaslangicTarihi']));
                    $startYear = (int)date('Y', strtotime($p['BaslangicTarihi']));
                    if ($startMonth === $month && $startYear === $year) {
                        $events[] = [
                            'id' => 'project_start_' . $p['Id'],
                            'type' => 'project_start',
                            'category' => 'project',
                            'customerId' => $p['MusteriId'],
                            'customer' => $p['MusteriUnvan'],
                            'title' => 'Proje Başlangıç: ' . $p['ProjeAdi'],
                            'date' => $p['BaslangicTarihi'],
                            'color' => '#198754', // green
                            'completed' => $p['Durum'] != 1,
                            'relatedId' => $p['Id'],
                            'relatedType' => 'project'
                        ];
                    }
                }
                
                // Bitiş tarihi
                if ($p['BitisTarihi']) {
                    $endMonth = (int)date('n', strtotime($p['BitisTarihi']));
                    $endYear = (int)date('Y', strtotime($p['BitisTarihi']));
                    if ($endMonth === $month && $endYear === $year) {
                        $events[] = [
                            'id' => 'project_end_' . $p['Id'],
                            'type' => 'project_end',
                            'category' => 'project',
                            'customerId' => $p['MusteriId'],
                            'customer' => $p['MusteriUnvan'],
                            'title' => 'Proje Bitiş: ' . $p['ProjeAdi'],
                            'date' => $p['BitisTarihi'],
                            'color' => '#dc3545', // red
                            'completed' => $p['Durum'] != 1,
                            'relatedId' => $p['Id'],
                            'relatedType' => 'project'
                        ];
                    }
                }
            }
            
            return $events;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Sözleşme etkinliklerini getir
     */
    private function getContractEvents(?int $customerId, int $month, int $year, bool $includeCompleted): array
    {
        try {
            $db = Database::connection();
            
            $conditions = "s.Sil = 0";
            $params = ['month' => $month, 'year' => $year];
            
            if ($customerId) {
                $conditions .= " AND s.MusteriId = :customerId";
                $params['customerId'] = $customerId;
            }
            
            if (!$includeCompleted) {
                $conditions .= " AND s.Durum = 1";
            }
            
            $sql = "
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
                WHERE {$conditions}
                  AND (
                    (MONTH(s.BaslangicTarihi) = :month AND YEAR(s.BaslangicTarihi) = :year)
                    OR (MONTH(s.BitisTarihi) = :month AND YEAR(s.BitisTarihi) = :year)
                  )
                ORDER BY s.BaslangicTarihi ASC
            ";
            
            $stmt = $db->prepare($sql); $stmt->execute($params);
            $contracts = $stmt->fetchAll();
            
            $events = [];
            foreach ($contracts as $c) {
                if ($c['BaslangicTarihi']) {
                    $startMonth = (int)date('n', strtotime($c['BaslangicTarihi']));
                    $startYear = (int)date('Y', strtotime($c['BaslangicTarihi']));
                    if ($startMonth === $month && $startYear === $year) {
                        $events[] = [
                            'id' => 'contract_start_' . $c['Id'],
                            'type' => 'contract_start',
                            'category' => 'contract',
                            'customerId' => $c['MusteriId'],
                            'customer' => $c['MusteriUnvan'],
                            'title' => 'Sözleşme Başlangıç: ' . $c['SozlesmeNo'],
                            'date' => $c['BaslangicTarihi'],
                            'color' => '#0d6efd', // blue
                            'completed' => $c['Durum'] != 1,
                            'relatedId' => $c['Id'],
                            'relatedType' => 'contract'
                        ];
                    }
                }
                
                if ($c['BitisTarihi']) {
                    $endMonth = (int)date('n', strtotime($c['BitisTarihi']));
                    $endYear = (int)date('Y', strtotime($c['BitisTarihi']));
                    if ($endMonth === $month && $endYear === $year) {
                        $events[] = [
                            'id' => 'contract_end_' . $c['Id'],
                            'type' => 'contract_end',
                            'category' => 'contract',
                            'customerId' => $c['MusteriId'],
                            'customer' => $c['MusteriUnvan'],
                            'title' => 'Sözleşme Bitiş: ' . $c['SozlesmeNo'],
                            'date' => $c['BitisTarihi'],
                            'color' => '#6f42c1', // purple
                            'completed' => $c['Durum'] != 1,
                            'relatedId' => $c['Id'],
                            'relatedType' => 'contract'
                        ];
                    }
                }
            }
            
            return $events;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Teminat vade etkinliklerini getir
     */
    private function getGuaranteeEvents(?int $customerId, int $month, int $year, bool $includeCompleted): array
    {
        try {
            $db = Database::connection();
            
            $conditions = "t.Sil = 0";
            $params = ['month' => $month, 'year' => $year];
            
            if ($customerId) {
                $conditions .= " AND t.MusteriId = :customerId";
                $params['customerId'] = $customerId;
            }
            
            if (!$includeCompleted) {
                $conditions .= " AND t.Durum = 1"; // Sadece bekleyen teminatlar
            }
            
            $sql = "
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
                WHERE {$conditions}
                  AND MONTH(t.VadeTarihi) = :month 
                  AND YEAR(t.VadeTarihi) = :year
                ORDER BY t.VadeTarihi ASC
            ";
            
            $stmt = $db->prepare($sql); $stmt->execute($params);
            $guarantees = $stmt->fetchAll();
            
            $events = [];
            foreach ($guarantees as $g) {
                $events[] = [
                    'id' => 'guarantee_' . $g['Id'],
                    'type' => 'guarantee_due',
                    'category' => 'guarantee',
                    'customerId' => $g['MusteriId'],
                    'customer' => $g['MusteriUnvan'],
                    'title' => 'Teminat Vadesi: ' . $g['BelgeNo'] . ' (' . $g['Tur'] . ')',
                    'date' => $g['VadeTarihi'],
                    'color' => '#fd7e14', // orange
                    'completed' => $g['Durum'] != 1,
                    'relatedId' => $g['Id'],
                    'relatedType' => 'guarantee',
                    'amount' => $g['Tutar'],
                    'currency' => $g['DovizCinsi']
                ];
            }
            
            return $events;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Fatura tarihlerini getir
     */
    private function getInvoiceEvents(?int $customerId, int $month, int $year): array
    {
        try {
            $db = Database::connection();
            
            $conditions = "f.Sil = 0";
            $params = ['month' => $month, 'year' => $year];
            
            if ($customerId) {
                $conditions .= " AND f.MusteriId = :customerId";
                $params['customerId'] = $customerId;
            }
            
            $sql = "
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
                WHERE {$conditions}
                  AND MONTH(f.Tarih) = :month 
                  AND YEAR(f.Tarih) = :year
                ORDER BY f.Tarih ASC
            ";
            
            $stmt = $db->prepare($sql); $stmt->execute($params);
            $invoices = $stmt->fetchAll();
            
            $events = [];
            foreach ($invoices as $inv) {
                $events[] = [
                    'id' => 'invoice_' . $inv['Id'],
                    'type' => 'invoice',
                    'category' => 'invoice',
                    'customerId' => $inv['MusteriId'],
                    'customer' => $inv['MusteriUnvan'],
                    'title' => 'Fatura: ' . ($inv['Aciklama'] ?: 'Fatura #' . $inv['Id']),
                    'date' => $inv['Tarih'],
                    'color' => '#20c997', // teal
                    'completed' => false,
                    'relatedId' => $inv['Id'],
                    'relatedType' => 'invoice',
                    'amount' => $inv['Tutar'],
                    'currency' => $inv['DovizCinsi']
                ];
            }
            
            return $events;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Belirli bir gündeki etkinlikleri getir
     */
    private function getEventsForDay(string $date, ?int $customerId): array
    {
        try {
            $db = Database::connection();
            $events = [];
            $params = ['date' => $date];
            
            $customerCondition = $customerId ? " AND MusteriId = :customerId" : "";
            if ($customerId) {
                $params['customerId'] = $customerId;
            }
            
            // Projeler
            $sql = "
                SELECT Id, MusteriId, ProjeAdi, 
                       CASE WHEN CAST(BaslangicTarihi AS DATE) = :date THEN 'start' ELSE 'end' END as EventType
                FROM tbl_proje 
                WHERE Sil = 0 {$customerCondition}
                  AND (CAST(BaslangicTarihi AS DATE) = :date OR CAST(BitisTarihi AS DATE) = :date)
            ";
            $stmt = $db->prepare($sql); $stmt->execute($params);
            $projects = $stmt->fetchAll();
            
            foreach ($projects as $p) {
                $events[] = [
                    'id' => $p['Id'],
                    'type' => 'project',
                    'eventType' => $p['EventType'],
                    'title' => $p['ProjeAdi'],
                    'customerId' => $p['MusteriId']
                ];
            }
            
            // Sözleşmeler
            $sql = "
                SELECT Id, MusteriId, SozlesmeNo,
                       CASE WHEN CAST(BaslangicTarihi AS DATE) = :date THEN 'start' ELSE 'end' END as EventType
                FROM tbl_sozlesme 
                WHERE Sil = 0 {$customerCondition}
                  AND (CAST(BaslangicTarihi AS DATE) = :date OR CAST(BitisTarihi AS DATE) = :date)
            ";
            $stmt = $db->prepare($sql); $stmt->execute($params);
            $contracts = $stmt->fetchAll();
            
            foreach ($contracts as $c) {
                $events[] = [
                    'id' => $c['Id'],
                    'type' => 'contract',
                    'eventType' => $c['EventType'],
                    'title' => $c['SozlesmeNo'],
                    'customerId' => $c['MusteriId']
                ];
            }
            
            // Teminatlar
            $sql = "
                SELECT Id, MusteriId, BelgeNo, Tur
                FROM tbl_teminat 
                WHERE Sil = 0 {$customerCondition}
                  AND CAST(VadeTarihi AS DATE) = :date
            ";
            $stmt = $db->prepare($sql); $stmt->execute($params);
            $guarantees = $stmt->fetchAll();
            
            foreach ($guarantees as $g) {
                $events[] = [
                    'id' => $g['Id'],
                    'type' => 'guarantee',
                    'eventType' => 'due',
                    'title' => $g['BelgeNo'] . ' (' . $g['Tur'] . ')',
                    'customerId' => $g['MusteriId']
                ];
            }
            
            // Faturalar
            $sql = "
                SELECT Id, MusteriId, Aciklama
                FROM tbl_fatura 
                WHERE Sil = 0 {$customerCondition}
                  AND CAST(Tarih AS DATE) = :date
            ";
            $stmt = $db->prepare($sql); $stmt->execute($params);
            $invoices = $stmt->fetchAll();
            
            foreach ($invoices as $inv) {
                $events[] = [
                    'id' => $inv['Id'],
                    'type' => 'invoice',
                    'eventType' => 'created',
                    'title' => $inv['Aciklama'] ?: 'Fatura #' . $inv['Id'],
                    'customerId' => $inv['MusteriId']
                ];
            }
            
            return $events;
        } catch (\Exception $e) {
            return [];
        }
    }
}
