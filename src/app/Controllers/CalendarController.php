<?php
/**
 * Calendar Controller için HTTP isteklerini yönetir.
 * Gelen talepleri doğrular ve yanıt akışını oluşturur.
 */

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Database;

class CalendarController
{

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

        $Etkinlikler = $this->getTakvimEvents($MusteriId, $Ay, $Yil);

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

    public function day(string $date): void
    {
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::unauthorized('Oturum gerekli');
            return;
        }

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
                            'color' => '#0d6efd',
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
                $Kosullar .= " AND t.Durum = 1";
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
                    'color' => '#fd7e14',
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
                    'color' => '#20c997',
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
                    m.MusteriKodu,
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

                $TerminTarihi = $Kayit['TerminTarihi'];
                if ($TerminTarihi) {
                    $TerminTarihi = date('Y-m-d', strtotime($TerminTarihi));
                }

                $MusteriKodu = $Kayit['MusteriKodu'] ?: 'MÜŞ-' . str_pad($Kayit['MusteriId'], 5, '0', STR_PAD_LEFT);

                $Etkinlikler[] = [
                    'id' => 'takvim_' . $Kayit['Id'],
                    'type' => 'takvim',
                    'category' => 'takvim',
                    'customerId' => $Kayit['MusteriId'],
                    'customer' => $Kayit['MusteriUnvan'],
                    'customerCode' => $MusteriKodu,
                    'title' => $Kayit['Ozet'],
                    'description' => $Kayit['ProjeAdi'] ? 'Proje: ' . $Kayit['ProjeAdi'] : null,
                    'date' => $TerminTarihi,
                    'color' => '#198754',
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
