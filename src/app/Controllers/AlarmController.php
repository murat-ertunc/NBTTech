<?php
/**
 * Alarm Controller için HTTP isteklerini yönetir.
 * Gelen talepleri doğrular ve yanıt akışını oluşturur.
 */

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Database;

class AlarmController
{

    public function index(): void
    {
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::unauthorized('Oturum gerekli');
        }

        $Alarmlar = [];

        $OdenmemisFaturalar = $this->odenmemisFaturalariGetir();
        if ($OdenmemisFaturalar['count'] > 0) {
            $Alarmlar[] = [
                'id' => 'unpaid_invoices',
                'type' => 'invoice',
                'priority' => 'high',
                'title' => 'Ödenmemiş Faturalar',
                'description' => $OdenmemisFaturalar['count'] . ' adet fatura ödeme bekliyor',
                'total' => $OdenmemisFaturalar['total'],
                'totalByCurrency' => $OdenmemisFaturalar['totalByCurrency'] ?? [],
                'count' => $OdenmemisFaturalar['count'],
                'items' => $OdenmemisFaturalar['items']
            ];
        }

        $YaklasanIsler = $this->yaklasanTakvimIsleriniGetir(7);
        if ($YaklasanIsler['count'] > 0) {
            $Alarmlar[] = [
                'id' => 'upcoming_events',
                'type' => 'calendar',
                'priority' => 'medium',
                'title' => 'Yaklaşan Takvim İşleri',
                'description' => 'Bu hafta ' . $YaklasanIsler['count'] . ' görev var',
                'count' => $YaklasanIsler['count'],
                'items' => $YaklasanIsler['items']
            ];
        }

        $TerminTarihiGecenTeminatlar = $this->terminTarihiGecenTeminatlariGetir();
        if ($TerminTarihiGecenTeminatlar['count'] > 0) {
            $Alarmlar[] = [
                'id' => 'expired_guarantees',
                'type' => 'guarantee',
                'priority' => 'high',
                'title' => 'Termin Tarihi Geçen Teminatlar',
                'description' => $TerminTarihiGecenTeminatlar['count'] . ' teminatın termin tarihi geçmiş',
                'total' => $TerminTarihiGecenTeminatlar['total'] ?? 0,
                'totalByCurrency' => $TerminTarihiGecenTeminatlar['totalByCurrency'] ?? [],
                'count' => $TerminTarihiGecenTeminatlar['count'],
                'items' => $TerminTarihiGecenTeminatlar['items']
            ];
        }

        $GecerililigiDolanTeklifler = $this->gecerliligiDolanTeklifleriGetir(7);
        if ($GecerililigiDolanTeklifler['count'] > 0) {
            $Alarmlar[] = [
                'id' => 'expiring_offers',
                'type' => 'offer',
                'priority' => 'medium',
                'title' => 'Geçerliliği Dolan Teklifler',
                'description' => $GecerililigiDolanTeklifler['count'] . ' teklifin geçerliliği dolmuş veya dolmak üzere',
                'count' => $GecerililigiDolanTeklifler['count'],
                'items' => $GecerililigiDolanTeklifler['items']
            ];
        }

        $SupheliAlacaklar = $this->supheliAlacakFaturalariniGetir();
        if ($SupheliAlacaklar['count'] > 0) {
            $Alarmlar[] = [
                'id' => 'doubtful_receivables',
                'type' => 'doubtful',
                'priority' => 'high',
                'title' => 'Şüpheli Alacaklar',
                'description' => $SupheliAlacaklar['count'] . ' adet şüpheli alacak kaydı mevcut',
                'total' => $SupheliAlacaklar['total'],
                'totalByCurrency' => $SupheliAlacaklar['totalByCurrency'] ?? [],
                'count' => $SupheliAlacaklar['count'],
                'items' => $SupheliAlacaklar['items']
            ];
        }

        Response::json([
            'success' => true,
            'data' => $Alarmlar,
            'totalCount' => count($Alarmlar)
        ]);
    }

    private function odenmemisFaturalariGetir(): array
    {
        try {
            $Db = Database::connection();

            $Sql = "
                SELECT
                    f.Id,
                    f.MusteriId,
                    m.Unvan as MusteriUnvan,
                    f.ProjeId,
                    p.ProjeAdi,
                    f.FaturaNo,
                    f.Tarih,
                    f.Tutar,
                    f.DovizCinsi,
                    f.SupheliAlacak,
                    ISNULL(
                        (SELECT SUM(o.Tutar) FROM tbl_odeme o WHERE o.FaturaId = f.Id AND o.Sil = 0),
                        0
                    ) as ToplamOdeme
                FROM tbl_fatura f
                LEFT JOIN tbl_musteri m ON f.MusteriId = m.Id
                LEFT JOIN tbl_proje p ON f.ProjeId = p.Id
                WHERE f.Sil = 0
                  AND m.Sil = 0
                ORDER BY f.Tarih ASC
            ";

            $Stmt = $Db->query($Sql);
            $Faturalar = $Stmt->fetchAll();

            $OdenmemisKalemler = [];
            $ToplamOdenmemis = 0;

            $ParaBirimiToplam = [];

            $Bugun = new \DateTime();

            foreach ($Faturalar as $Fatura) {
                $FaturaTutari = (float)$Fatura['Tutar'];
                $OdenenTutar = (float)$Fatura['ToplamOdeme'];
                $Kalan = $FaturaTutari - $OdenenTutar;
                $ParaBirimi = $Fatura['DovizCinsi'] ?? 'TRY';

                if ($Kalan > 0.01) {
                    $FaturaTarihi = new \DateTime($Fatura['Tarih']);
                    $Fark = $Bugun->diff($FaturaTarihi);
                    $GecikmeGun = $Fark->days;

                    if ($FaturaTarihi > $Bugun) {
                        $GecikmeGun = -$GecikmeGun;
                    }

                    if (!isset($ParaBirimiToplam[$ParaBirimi])) {
                        $ParaBirimiToplam[$ParaBirimi] = 0;
                    }
                    $ParaBirimiToplam[$ParaBirimi] += $Kalan;

                    $OdenmemisKalemler[] = [
                        'id' => $Fatura['Id'],
                        'customerId' => $Fatura['MusteriId'],
                        'customer' => $Fatura['MusteriUnvan'],
                        'projectId' => $Fatura['ProjeId'],
                        'project' => $Fatura['ProjeAdi'] ?? '-',
                        'invoiceNo' => $Fatura['FaturaNo'] ?? '-',
                        'invoiceDate' => $Fatura['Tarih'],
                        'delayDays' => $GecikmeGun,
                        'invoiceAmount' => $FaturaTutari,
                        'balance' => $Kalan,
                        'currency' => $ParaBirimi,
                        'supheliAlacak' => (int)($Fatura['SupheliAlacak'] ?? 0) === 1
                    ];
                    $ToplamOdenmemis += $Kalan;
                }
            }

            usort($OdenmemisKalemler, function($a, $b) {
                return $b['delayDays'] - $a['delayDays'];
            });

            return [
                'count' => count($OdenmemisKalemler),
                'total' => $ToplamOdenmemis,
                'totalByCurrency' => $ParaBirimiToplam,
                'items' => $OdenmemisKalemler
            ];
        } catch (\Exception $e) {
            return ['count' => 0, 'total' => 0, 'totalByCurrency' => [], 'items' => []];
        }
    }

    private function yaklasanTakvimIsleriniGetir(int $Gun = 7): array
    {
        try {
            $Db = Database::connection();

            $GunInt = (int)$Gun;
            $Sql = "
                SELECT
                    t.Id,
                    t.MusteriId,
                    m.Unvan as MusteriUnvan,
                    t.ProjeId,
                    p.ProjeAdi,
                    t.Ozet,
                    t.TerminTarihi
                FROM tbl_takvim t
                LEFT JOIN tbl_musteri m ON t.MusteriId = m.Id
                LEFT JOIN tbl_proje p ON t.ProjeId = p.Id
                WHERE t.Sil = 0
                  AND m.Sil = 0
                  AND t.TerminTarihi IS NOT NULL
                  AND t.TerminTarihi <= DATEADD(day, :GunSayisi, SYSUTCDATETIME())
                ORDER BY t.TerminTarihi ASC
            ";

            $Stmt = $Db->prepare($Sql);
            $Stmt->bindValue(':GunSayisi', $GunInt, \PDO::PARAM_INT);
            $Stmt->execute();
            $Takvimler = $Stmt->fetchAll();

            $Kalemler = [];
            $Bugun = new \DateTime();

            foreach ($Takvimler as $Takvim) {
                $TerminTarihi = new \DateTime($Takvim['TerminTarihi']);
                $Fark = $Bugun->diff($TerminTarihi);
                $KalanGun = $TerminTarihi >= $Bugun ? $Fark->days : -$Fark->days;

                $Kalemler[] = [
                    'id' => $Takvim['Id'],
                    'type' => 'takvim',
                    'customerId' => $Takvim['MusteriId'],
                    'customer' => $Takvim['MusteriUnvan'],
                    'projectId' => $Takvim['ProjeId'],
                    'project' => $Takvim['ProjeAdi'] ?? '-',
                    'title' => $Takvim['Ozet'],
                    'date' => $Takvim['TerminTarihi'],
                    'daysRemaining' => $KalanGun
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

    private function terminTarihiGecenTeminatlariGetir(): array
    {
        try {
            $Db = Database::connection();

            $Sql = "
                SELECT
                    t.Id,
                    t.MusteriId,
                    m.Unvan as MusteriUnvan,
                    t.ProjeId,
                    p.ProjeAdi,
                    t.Tur,
                    t.Tutar,
                    t.ParaBirimi,
                    t.TerminTarihi,
                    t.Durum
                FROM tbl_teminat t
                LEFT JOIN tbl_musteri m ON t.MusteriId = m.Id
                LEFT JOIN tbl_proje p ON t.ProjeId = p.Id
                WHERE t.Sil = 0
                  AND m.Sil = 0
                  AND t.TerminTarihi IS NOT NULL
                  AND t.TerminTarihi < SYSUTCDATETIME()
                ORDER BY t.TerminTarihi ASC
            ";

            $Stmt = $Db->query($Sql);
            $Teminatlar = $Stmt->fetchAll();

            $Kalemler = [];
            $Bugun = new \DateTime();
            $ToplamTutar = 0;

            $ParaBirimiToplam = [];

            foreach ($Teminatlar as $Teminat) {
                $TerminTarihi = new \DateTime($Teminat['TerminTarihi']);
                $Fark = $Bugun->diff($TerminTarihi);
                $GecenGun = $Fark->days;
                $Tutar = (float)$Teminat['Tutar'];
                $ParaBirimi = $Teminat['ParaBirimi'] ?? 'TRY';

                if (!isset($ParaBirimiToplam[$ParaBirimi])) {
                    $ParaBirimiToplam[$ParaBirimi] = 0;
                }
                $ParaBirimiToplam[$ParaBirimi] += $Tutar;
                $ToplamTutar += $Tutar;

                $Kalemler[] = [
                    'id' => $Teminat['Id'],
                    'customerId' => $Teminat['MusteriId'],
                    'customer' => $Teminat['MusteriUnvan'],
                    'projectId' => $Teminat['ProjeId'],
                    'project' => $Teminat['ProjeAdi'] ?? '-',
                    'type' => $Teminat['Tur'],
                    'amount' => $Tutar,
                    'currency' => $ParaBirimi,
                    'dueDate' => $Teminat['TerminTarihi'],
                    'daysOverdue' => $GecenGun
                ];
            }

            return [
                'count' => count($Kalemler),
                'total' => $ToplamTutar,
                'totalByCurrency' => $ParaBirimiToplam,
                'items' => $Kalemler
            ];
        } catch (\Exception $e) {
            return ['count' => 0, 'total' => 0, 'totalByCurrency' => [], 'items' => []];
        }
    }

    private function gecerliligiDolanTeklifleriGetir(int $Gun = 7): array
    {
        try {
            $Db = Database::connection();

            $GunInt = (int)$Gun;
            $Sql = "
                SELECT
                    t.Id,
                    t.MusteriId,
                    m.Unvan as MusteriUnvan,
                    t.ProjeId,
                    p.ProjeAdi,
                    t.Konu,
                    t.Tutar,
                    t.ParaBirimi,
                    t.TeklifTarihi,
                    t.GecerlilikTarihi,
                    t.Durum
                FROM tbl_teklif t
                LEFT JOIN tbl_musteri m ON t.MusteriId = m.Id
                LEFT JOIN tbl_proje p ON t.ProjeId = p.Id
                WHERE t.Sil = 0
                  AND m.Sil = 0
                  AND t.GecerlilikTarihi IS NOT NULL
                  AND t.GecerlilikTarihi <= DATEADD(day, :GunSayisi, SYSUTCDATETIME())
                  AND t.Durum IN (0, 1)
                ORDER BY t.GecerlilikTarihi ASC
            ";

            $Stmt = $Db->prepare($Sql);
            $Stmt->bindValue(':GunSayisi', $GunInt, \PDO::PARAM_INT);
            $Stmt->execute();
            $Teklifler = $Stmt->fetchAll();

            $Kalemler = [];
            $Bugun = new \DateTime();

            foreach ($Teklifler as $Teklif) {
                $GecerlilikTarihi = new \DateTime($Teklif['GecerlilikTarihi']);
                $Fark = $Bugun->diff($GecerlilikTarihi);
                $KalanGun = $GecerlilikTarihi >= $Bugun ? $Fark->days : -$Fark->days;

                $Kalemler[] = [
                    'id' => $Teklif['Id'],
                    'customerId' => $Teklif['MusteriId'],
                    'customer' => $Teklif['MusteriUnvan'],
                    'projectId' => $Teklif['ProjeId'],
                    'project' => $Teklif['ProjeAdi'] ?? '-',
                    'title' => $Teklif['Konu'] ?? 'Teklif #' . $Teklif['Id'],
                    'amount' => $Teklif['Tutar'],
                    'currency' => $Teklif['ParaBirimi'],
                    'offerDate' => $Teklif['TeklifTarihi'],
                    'validUntil' => $Teklif['GecerlilikTarihi'],
                    'daysRemaining' => $KalanGun
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

    private function supheliAlacakFaturalariniGetir(): array
    {
        try {
            $Db = Database::connection();

            $Sql = "
                SELECT
                    f.Id,
                    f.MusteriId,
                    m.Unvan as MusteriUnvan,
                    f.ProjeId,
                    p.ProjeAdi,
                    f.FaturaNo,
                    f.Tarih,
                    f.Tutar,
                    f.DovizCinsi,
                    ISNULL(
                        (SELECT SUM(o.Tutar) FROM tbl_odeme o WHERE o.FaturaId = f.Id AND o.Sil = 0),
                        0
                    ) as ToplamOdeme
                FROM tbl_fatura f
                LEFT JOIN tbl_musteri m ON f.MusteriId = m.Id
                LEFT JOIN tbl_proje p ON f.ProjeId = p.Id
                WHERE f.Sil = 0
                  AND m.Sil = 0
                  AND f.SupheliAlacak = 1
                ORDER BY f.Tarih ASC
            ";

            $Stmt = $Db->query($Sql);
            $Faturalar = $Stmt->fetchAll();

            $Kalemler = [];
            $ToplamAlacak = 0;
            $ParaBirimiToplam = [];
            $Bugun = new \DateTime();

            foreach ($Faturalar as $Fatura) {
                $FaturaTutari = (float)$Fatura['Tutar'];
                $OdenenTutar = (float)$Fatura['ToplamOdeme'];
                $Kalan = $FaturaTutari - $OdenenTutar;
                $ParaBirimi = $Fatura['DovizCinsi'] ?? 'TRY';

                $FaturaTarihi = new \DateTime($Fatura['Tarih']);
                $Fark = $Bugun->diff($FaturaTarihi);
                $GecikmeGun = $Fark->days;

                if ($FaturaTarihi > $Bugun) {
                    $GecikmeGun = -$GecikmeGun;
                }

                if ($Kalan > 0.01) {
                    if (!isset($ParaBirimiToplam[$ParaBirimi])) {
                        $ParaBirimiToplam[$ParaBirimi] = 0;
                    }
                    $ParaBirimiToplam[$ParaBirimi] += $Kalan;
                    $ToplamAlacak += $Kalan;
                }

                $Kalemler[] = [
                    'id' => $Fatura['Id'],
                    'customerId' => $Fatura['MusteriId'],
                    'customer' => $Fatura['MusteriUnvan'],
                    'projectId' => $Fatura['ProjeId'],
                    'project' => $Fatura['ProjeAdi'] ?? '-',
                    'invoiceNo' => $Fatura['FaturaNo'] ?? '-',
                    'invoiceDate' => $Fatura['Tarih'],
                    'delayDays' => $GecikmeGun,
                    'invoiceAmount' => $FaturaTutari,
                    'balance' => $Kalan,
                    'currency' => $ParaBirimi
                ];
            }

            usort($Kalemler, function($a, $b) {
                return $b['delayDays'] - $a['delayDays'];
            });

            return [
                'count' => count($Kalemler),
                'total' => $ToplamAlacak,
                'totalByCurrency' => $ParaBirimiToplam,
                'items' => $Kalemler
            ];
        } catch (\Exception $e) {
            return ['count' => 0, 'total' => 0, 'totalByCurrency' => [], 'items' => []];
        }
    }
}
