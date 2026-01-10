<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Database;

/**
 * AlarmController
 * 
 * Dashboard alarm sistemi için endpoint'ler.
 * 2 temel alarm fonksiyonu:
 * 1. Ödenmemiş faturalar
 * 2. Yaklaşan takvim işleri
 */
class AlarmController
{
    /**
     * Tüm alarmları getir
     * GET /api/alarms
     */
    public function index(): void
    {
        $userId = Context::kullaniciId();
        if (!$userId) {
            Response::unauthorized('Oturum gerekli');
        }

        $alarms = [];
        
        // 1. Ödenmemiş faturalar
        $unpaidInvoices = $this->getUnpaidInvoices();
        if ($unpaidInvoices['count'] > 0) {
            $alarms[] = [
                'id' => 'unpaid_invoices',
                'type' => 'invoice',
                'priority' => 'high',
                'title' => 'Ödenmemiş Faturalar',
                'description' => $unpaidInvoices['count'] . ' adet fatura ödeme bekliyor',
                'total' => $unpaidInvoices['total'],
                'count' => $unpaidInvoices['count'],
                'items' => $unpaidInvoices['items']
            ];
        }

        // 2. Yaklaşan takvim işleri (7 gün içinde)
        $upcomingEvents = $this->getUpcomingEvents(7);
        if ($upcomingEvents['count'] > 0) {
            $alarms[] = [
                'id' => 'upcoming_events',
                'type' => 'calendar',
                'priority' => 'medium',
                'title' => 'Yaklaşan İşler',
                'description' => 'Bu hafta ' . $upcomingEvents['count'] . ' görev var',
                'count' => $upcomingEvents['count'],
                'items' => $upcomingEvents['items']
            ];
        }

        // 3. Vadesi geçen teminatlar
        $expiredGuarantees = $this->getExpiredGuarantees();
        if ($expiredGuarantees['count'] > 0) {
            $alarms[] = [
                'id' => 'expired_guarantees',
                'type' => 'guarantee',
                'priority' => 'high',
                'title' => 'Vadesi Geçen Teminatlar',
                'description' => $expiredGuarantees['count'] . ' teminatın vadesi geçmiş',
                'count' => $expiredGuarantees['count'],
                'items' => $expiredGuarantees['items']
            ];
        }

        // 4. Yaklaşan sözleşme bitişleri (30 gün içinde)
        $expiringContracts = $this->getExpiringContracts(30);
        if ($expiringContracts['count'] > 0) {
            $alarms[] = [
                'id' => 'expiring_contracts',
                'type' => 'contract',
                'priority' => 'medium',
                'title' => 'Sözleşme Bitiş Yaklaşıyor',
                'description' => $expiringContracts['count'] . ' sözleşme 30 gün içinde bitiyor',
                'count' => $expiringContracts['count'],
                'items' => $expiringContracts['items']
            ];
        }

        Response::json([
            'success' => true,
            'data' => $alarms,
            'totalCount' => count($alarms)
        ]);
    }

    /**
     * Ödenmemiş faturaları getir
     */
    private function getUnpaidInvoices(): array
    {
        try {
            $db = Database::connection();
            
            // Fatura - Ödeme farkı ile ödenmemiş faturaları bul
            // Not: Bu sorgu fatura tutarı ile ilişkili ödemelerin toplamını karşılaştırır
            $sql = "
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
            
            $stmt = $db->query($sql);
            $invoices = $stmt->fetchAll();
            
            $unpaidItems = [];
            $totalUnpaid = 0;
            
            // Müşteri bazında ödenmemiş faturaları hesapla
            $customerTotals = [];
            
            foreach ($invoices as $inv) {
                $customerId = $inv['MusteriId'];
                if (!isset($customerTotals[$customerId])) {
                    $customerTotals[$customerId] = [
                        'invoiceTotal' => 0,
                        'paymentTotal' => (float)$inv['ToplamOdeme'],
                        'customer' => $inv['MusteriUnvan'],
                        'currency' => $inv['DovizCinsi'] ?? 'TRY'
                    ];
                }
                $customerTotals[$customerId]['invoiceTotal'] += (float)$inv['Tutar'];
            }
            
            foreach ($customerTotals as $customerId => $data) {
                $unpaid = $data['invoiceTotal'] - $data['paymentTotal'];
                if ($unpaid > 0) {
                    $unpaidItems[] = [
                        'customerId' => $customerId,
                        'customer' => $data['customer'],
                        'amount' => $unpaid,
                        'currency' => $data['currency']
                    ];
                    $totalUnpaid += $unpaid;
                }
            }
            
            return [
                'count' => count($unpaidItems),
                'total' => $totalUnpaid,
                'items' => array_slice($unpaidItems, 0, 5) // İlk 5 kayıt
            ];
        } catch (\Exception $e) {
            return ['count' => 0, 'total' => 0, 'items' => []];
        }
    }

    /**
     * Yaklaşan takvim işlerini getir
     */
    private function getUpcomingEvents(int $days = 7): array
    {
        try {
            $db = Database::connection();
            
            // Proje bitiş tarihleri yaklaşan projeler
            $sql = "
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
                  AND p.BitisTarihi BETWEEN GETDATE() AND DATEADD(day, :days, GETDATE())
                ORDER BY p.BitisTarihi ASC
            ";
            
            $stmt = $db->prepare($sql); $stmt->execute(['days' => $days]);
            $projects = $stmt->fetchAll();
            
            $items = [];
            foreach ($projects as $p) {
                $items[] = [
                    'id' => $p['Id'],
                    'type' => 'project',
                    'customerId' => $p['MusteriId'],
                    'customer' => $p['MusteriUnvan'],
                    'title' => $p['ProjeAdi'],
                    'date' => $p['BitisTarihi']
                ];
            }
            
            return [
                'count' => count($items),
                'items' => array_slice($items, 0, 10)
            ];
        } catch (\Exception $e) {
            return ['count' => 0, 'items' => []];
        }
    }

    /**
     * Vadesi geçen teminatları getir
     */
    private function getExpiredGuarantees(): array
    {
        try {
            $db = Database::connection();
            
            $sql = "
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
            
            $stmt = $db->query($sql);
            $guarantees = $stmt->fetchAll();
            
            $items = [];
            foreach ($guarantees as $g) {
                $items[] = [
                    'id' => $g['Id'],
                    'customerId' => $g['MusteriId'],
                    'customer' => $g['MusteriUnvan'],
                    'documentNo' => $g['BelgeNo'],
                    'type' => $g['Tur'],
                    'amount' => $g['Tutar'],
                    'currency' => $g['DovizCinsi'],
                    'dueDate' => $g['VadeTarihi']
                ];
            }
            
            return [
                'count' => count($items),
                'items' => array_slice($items, 0, 5)
            ];
        } catch (\Exception $e) {
            return ['count' => 0, 'items' => []];
        }
    }

    /**
     * Yaklaşan sözleşme bitişlerini getir
     */
    private function getExpiringContracts(int $days = 30): array
    {
        try {
            $db = Database::connection();
            
            $sql = "
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
                  AND s.BitisTarihi BETWEEN GETDATE() AND DATEADD(day, :days, GETDATE())
                ORDER BY s.BitisTarihi ASC
            ";
            
            $stmt = $db->prepare($sql); $stmt->execute(['days' => $days]);
            $contracts = $stmt->fetchAll();
            
            $items = [];
            foreach ($contracts as $c) {
                $items[] = [
                    'id' => $c['Id'],
                    'customerId' => $c['MusteriId'],
                    'customer' => $c['MusteriUnvan'],
                    'contractNo' => $c['SozlesmeNo'],
                    'endDate' => $c['BitisTarihi'],
                    'amount' => $c['Tutar'],
                    'currency' => $c['DovizCinsi']
                ];
            }
            
            return [
                'count' => count($items),
                'items' => array_slice($items, 0, 5)
            ];
        } catch (\Exception $e) {
            return ['count' => 0, 'items' => []];
        }
    }
}
