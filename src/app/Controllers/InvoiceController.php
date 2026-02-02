<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\InvoiceRepository;
use App\Services\Logger\ActionLogger;
use App\Services\CalendarService;
use App\Repositories\CalendarRepository;

class InvoiceController
{
    private const TAKVIM_SURE_TIPLERI = ['gun', 'hafta', 'ay', 'yil'];

    private static function takvimHatirlatmaDogrula(int $TakvimAktif, ?int $TakvimSure, ?string $TakvimSureTipi): ?string
    {
        if ($TakvimAktif !== 1) {
            return null;
        }
        if ($TakvimSure === null && ($TakvimSureTipi === null || $TakvimSureTipi === '')) {
            return null;
        }
        if ($TakvimSure === null || $TakvimSure <= 0) {
            return 'Takvim hatirlatma suresi gecersiz.';
        }
        if ($TakvimSureTipi === null || !in_array($TakvimSureTipi, self::TAKVIM_SURE_TIPLERI, true)) {
            return 'Takvim hatirlatma birimi gecersiz.';
        }
        return null;
    }

    private static function takvimTarihEkle(string $Tarih, int $Sure, string $SureTipi): string
    {
        $T = new \DateTime($Tarih);
        $Gun = (int)$T->format('d');
        $Ay = (int)$T->format('m');
        $Yil = (int)$T->format('Y');
        $AySonu = $T->format('Y-m-d') === $T->format('Y-m-t');
        switch ($SureTipi) {
            case 'hafta':
                $T->add(new \DateInterval('P' . $Sure . 'W'));
                break;
            case 'ay':
                $T->setDate($Yil, $Ay, 1);
                $T->modify('first day of +' . $Sure . ' month');
                if ($AySonu) {
                    $T->modify('last day of this month');
                } else {
                    $HedefAySonu = (int)$T->format('t');
                    $T->setDate((int)$T->format('Y'), (int)$T->format('m'), min($Gun, $HedefAySonu));
                }
                break;
            case 'yil':
                $T->setDate($Yil + $Sure, $Ay, 1);
                $T->modify('first day of this month');
                if ($AySonu) {
                    $T->modify('last day of this month');
                } else {
                    $HedefAySonu = (int)$T->format('t');
                    $T->setDate((int)$T->format('Y'), (int)$T->format('m'), min($Gun, $HedefAySonu));
                }
                break;
            default:
                $T->add(new \DateInterval('P' . $Sure . 'D'));
                break;
        }
        return $T->format('Y-m-d');
    }
    public static function index(): void
    {
        $Repo = new InvoiceRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $MusteriId = isset($_GET['musteri_id']) ? (int)$_GET['musteri_id'] : 0;
        $Sayfa = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $Limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : (int)env('PAGINATION_DEFAULT', 10);
        
        // Backend kalan>0 filtresi - ödeme formu için sadece ödenmemiş faturaları döndür
        $SadeceOdenmemis = isset($_GET['sadece_odenmemis']) && $_GET['sadece_odenmemis'] === '1';

        if ($MusteriId > 0) {
            if (isset($_GET['page']) || isset($_GET['limit'])) {
                $Sonuc = $Repo->musteriyeGorePaginated($MusteriId, $Sayfa, $Limit);
                Response::json($Sonuc);
            } else {
                $Satirlar = $Repo->musteriyeGore($MusteriId, $SadeceOdenmemis);
                Response::json(['data' => $Satirlar]);
            }
        } else {
            // Standalone sayfa - pagination ile tum faturalar
            if (isset($_GET['page']) || isset($_GET['limit'])) {
                $Sonuc = $Repo->tumAktiflerPaginated($Sayfa, $Limit);
                Response::json($Sonuc);
            } else {
                $Satirlar = $Repo->tumAktifler();
                Response::json(['data' => $Satirlar]);
            }
        }
    }

    public static function show(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Repo = new InvoiceRepository();
        $Fatura = $Repo->bul($Id);
        if (!$Fatura) {
            Response::error('Fatura bulunamadi.', 404);
            return;
        }

        // Fatura kalemlerini ekle
        $Fatura['Kalemler'] = $Repo->getKalemler($Id);
        
        // Fatura ile iliskili dosyalari ekle
        $Fatura['Dosyalar'] = $Repo->getDosyalar($Id);

        Response::json($Fatura);
    }

    public static function store(): void
    {
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $Zorunlu = ['MusteriId', 'Tarih'];
        foreach ($Zorunlu as $Alan) {
            if (empty($Girdi[$Alan])) {
                Response::error("$Alan alani zorunludur.", 422);
                return;
            }
        }
        
        $MusteriId = (int)$Girdi['MusteriId'];
        $ProjeId = isset($Girdi['ProjeId']) && $Girdi['ProjeId'] ? (int)$Girdi['ProjeId'] : null;
        $Tutar = (float)$Girdi['Tutar'];
        $Tarih = trim((string)$Girdi['Tarih']); // YYYY-MM-DD formatinda gelmeli
        $Doviz = isset($Girdi['DovizCinsi']) ? trim((string)$Girdi['DovizCinsi']) : 'TL';
        
        // Yeni alanlar
        $FaturaNo = isset($Girdi['FaturaNo']) ? trim((string)$Girdi['FaturaNo']) : null;
        $SupheliAlacak = isset($Girdi['SupheliAlacak']) ? (int)$Girdi['SupheliAlacak'] : 0;
        $TevkifatAktif = isset($Girdi['TevkifatAktif']) ? (int)$Girdi['TevkifatAktif'] : 0;
        $TevkifatOran1 = isset($Girdi['TevkifatOran1']) ? (float)$Girdi['TevkifatOran1'] : null;
        $TevkifatOran2 = isset($Girdi['TevkifatOran2']) ? (float)$Girdi['TevkifatOran2'] : null;
        // Takvim hatirlatma alanlari (backend'de de sanitize et)
        $TakvimAktif = !empty($Girdi['TakvimAktif']) ? 1 : 0;
        $TakvimSure = isset($Girdi['TakvimSure']) ? (int)$Girdi['TakvimSure'] : null;
        $TakvimSure = $TakvimSure > 0 ? $TakvimSure : null;
        $TakvimSureTipi = isset($Girdi['TakvimSureTipi']) ? trim((string)$Girdi['TakvimSureTipi']) : null;
        $TakvimSureTipi = $TakvimSureTipi && in_array($TakvimSureTipi, self::TAKVIM_SURE_TIPLERI, true) ? $TakvimSureTipi : null;
        $HatirlatmaHatasi = self::takvimHatirlatmaDogrula($TakvimAktif, $TakvimSure, $TakvimSureTipi);
        if ($HatirlatmaHatasi) {
            Response::error($HatirlatmaHatasi, 422);
            return;
        }
        $Kalemler = isset($Girdi['Kalemler']) && is_array($Girdi['Kalemler']) ? $Girdi['Kalemler'] : [];

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Repo = new InvoiceRepository();
        $Id = Transaction::wrap(function () use ($Repo, $MusteriId, $ProjeId, $Tarih, $Tutar, $Doviz, $FaturaNo, $SupheliAlacak, $TevkifatAktif, $TevkifatOran1, $TevkifatOran2, $TakvimAktif, $TakvimSure, $TakvimSureTipi, $Kalemler, $KullaniciId) {
            $FaturaId = $Repo->ekle([
                'MusteriId' => $MusteriId,
                'ProjeId' => $ProjeId,
                'Tarih' => $Tarih,
                'Tutar' => $Tutar,
                'DovizCinsi' => $Doviz,
                'FaturaNo' => $FaturaNo,
                'SupheliAlacak' => $SupheliAlacak,
                'TevkifatAktif' => $TevkifatAktif,
                'TevkifatOran1' => $TevkifatOran1,
                'TevkifatOran2' => $TevkifatOran2,
                'TakvimAktif' => $TakvimAktif,
                'TakvimSure' => $TakvimSure,
                'TakvimSureTipi' => $TakvimSureTipi
            ], $KullaniciId);
            
            // Fatura kalemlerini kaydet
            if (!empty($Kalemler)) {
                $Repo->kaydetKalemler($FaturaId, $Kalemler, $KullaniciId);
            }

            // Normal takvim hatirlatmasi (mevcut davranis)
            if (!empty($Tarih)) {
                $FaturaAciklama = !empty($FaturaNo) ? 'Fatura No: ' . $FaturaNo : 'Fatura';
                CalendarService::createOrUpdateReminder(
                    $MusteriId,
                    'fatura',
                    $FaturaId,
                    $Tarih,
                    $FaturaAciklama,
                    $KullaniciId
                );
            }

            // Ek takvim hatirlatmasi (checkbox + alanlar dolu)
            if ($TakvimAktif === 1 && $TakvimSure !== null && $TakvimSureTipi !== null && !empty($Tarih)) {
                $EkTarih = self::takvimTarihEkle($Tarih, $TakvimSure, $TakvimSureTipi);
                $TakvimRepo = new CalendarRepository();
                $Ozet = !empty($FaturaNo)
                    ? 'Fatura Hatırlatma: ' . $FaturaNo
                    : 'Fatura Hatırlatma';
                $TakvimRepo->ekle([
                    'MusteriId' => $MusteriId,
                    'ProjeId' => $ProjeId,
                    'TerminTarihi' => $EkTarih,
                    'Ozet' => $Ozet,
                    'Durum' => CalendarService::getDefaultTakvimDurum(),
                    'KaynakTuru' => 'fatura_hatirlatma',
                    'KaynakId' => $FaturaId
                ], $KullaniciId);
            }
            
            return $FaturaId;
        });

        Response::json(['id' => $Id], 201);
    }

    public static function update(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $Repo = new InvoiceRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            Response::error('Fatura bulunamadi.', 404);
            return;
        }

        $TakvimAktif = array_key_exists('TakvimAktif', $Girdi) && !empty($Girdi['TakvimAktif']) ? 1 : 0;
        $TakvimSure = array_key_exists('TakvimSure', $Girdi) ? (int)$Girdi['TakvimSure'] : null;
        $TakvimSure = $TakvimSure > 0 ? $TakvimSure : null;
        $TakvimSureTipi = array_key_exists('TakvimSureTipi', $Girdi) ? trim((string)$Girdi['TakvimSureTipi']) : null;
        $TakvimSureTipi = $TakvimSureTipi && in_array($TakvimSureTipi, self::TAKVIM_SURE_TIPLERI, true) ? $TakvimSureTipi : null;
        $HatirlatmaHatasi = self::takvimHatirlatmaDogrula($TakvimAktif, $TakvimSure, $TakvimSureTipi);
        if ($HatirlatmaHatasi) {
            Response::error($HatirlatmaHatasi, 422);
            return;
        }

        // Basit validasyonlar eklenebilir.

        $FaturaTarihi = isset($Girdi['Tarih']) ? $Girdi['Tarih'] : ($Mevcut['Tarih'] ?? null);
        $FaturaNo = isset($Girdi['FaturaNo']) ? $Girdi['FaturaNo'] : ($Mevcut['FaturaNo'] ?? '');
        $MusteriId = (int)($Mevcut['MusteriId'] ?? 0);
        $ProjeId = array_key_exists('ProjeId', $Girdi)
            ? ($Girdi['ProjeId'] ? (int)$Girdi['ProjeId'] : null)
            : ($Mevcut['ProjeId'] ?? null);

        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId, $Mevcut, $FaturaTarihi, $FaturaNo, $MusteriId, $ProjeId, $TakvimAktif, $TakvimSure, $TakvimSureTipi) {
            $Guncellenecek = [];
            if (isset($Girdi['Tarih'])) $Guncellenecek['Tarih'] = $Girdi['Tarih'];
            if (isset($Girdi['Tutar'])) $Guncellenecek['Tutar'] = (float)$Girdi['Tutar'];
            if (isset($Girdi['DovizCinsi'])) $Guncellenecek['DovizCinsi'] = $Girdi['DovizCinsi'];
            if (array_key_exists('ProjeId', $Girdi)) $Guncellenecek['ProjeId'] = $Girdi['ProjeId'] ? (int)$Girdi['ProjeId'] : null;
            
            // Yeni alanlar
            if (isset($Girdi['FaturaNo'])) $Guncellenecek['FaturaNo'] = $Girdi['FaturaNo'];
            if (isset($Girdi['SupheliAlacak'])) $Guncellenecek['SupheliAlacak'] = (int)$Girdi['SupheliAlacak'];
            if (isset($Girdi['TevkifatAktif'])) $Guncellenecek['TevkifatAktif'] = (int)$Girdi['TevkifatAktif'];
            if (isset($Girdi['TevkifatOran1'])) $Guncellenecek['TevkifatOran1'] = $Girdi['TevkifatOran1'] ? (float)$Girdi['TevkifatOran1'] : null;
            if (isset($Girdi['TevkifatOran2'])) $Guncellenecek['TevkifatOran2'] = $Girdi['TevkifatOran2'] ? (float)$Girdi['TevkifatOran2'] : null;
            if (array_key_exists('TakvimAktif', $Girdi)) $Guncellenecek['TakvimAktif'] = $TakvimAktif;
            if (array_key_exists('TakvimSure', $Girdi)) $Guncellenecek['TakvimSure'] = $TakvimSure;
            if (array_key_exists('TakvimSureTipi', $Girdi)) $Guncellenecek['TakvimSureTipi'] = $TakvimSureTipi;

            if (!empty($Guncellenecek)) {
                $Repo->guncelle($Id, $Guncellenecek, $KullaniciId);
            }
            
            // Fatura kalemlerini guncelle
            if (isset($Girdi['Kalemler']) && is_array($Girdi['Kalemler'])) {
                $Repo->kaydetKalemler($Id, $Girdi['Kalemler'], $KullaniciId);
            }

            // Normal takvim hatirlatmasi (mevcut davranis)
            if (isset($Girdi['Tarih']) && !empty($FaturaTarihi)) {
                $FaturaAciklama = !empty($FaturaNo) ? 'Fatura No: ' . $FaturaNo : 'Fatura';
                CalendarService::createOrUpdateReminder(
                    $MusteriId,
                    'fatura',
                    $Id,
                    $FaturaTarihi,
                    $FaturaAciklama,
                    $KullaniciId
                );
            }

            // Ek takvim hatirlatmasi (checkbox + alanlar dolu)
            if ($TakvimAktif === 1 && $TakvimSure !== null && $TakvimSureTipi !== null && !empty($FaturaTarihi)) {
                $EkTarih = self::takvimTarihEkle($FaturaTarihi, $TakvimSure, $TakvimSureTipi);
                $TakvimRepo = new CalendarRepository();
                $Ozet = !empty($FaturaNo)
                    ? 'Fatura Hatırlatma: ' . $FaturaNo
                    : 'Fatura Hatırlatma';
                $TakvimRepo->ekle([
                    'MusteriId' => $MusteriId,
                    'ProjeId' => $ProjeId,
                    'TerminTarihi' => $EkTarih,
                    'Ozet' => $Ozet,
                    'KaynakTuru' => 'fatura_hatirlatma',
                    'KaynakId' => $Id
                ], $KullaniciId);
            }
        });

        Response::json(['status' => 'success']);
    }

    public static function delete(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $Repo = new InvoiceRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            Response::error('Fatura bulunamadi.', 404);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $KullaniciId) {
            $Repo->yedekle($Id, 'bck_tbl_fatura', $KullaniciId);
            $Repo->softSil($Id, $KullaniciId);
            ActionLogger::delete('tbl_fatura', ['Id' => $Id]);
        });

        // Takvim hatirlatmasini sil
        CalendarService::deleteReminder('fatura', $Id);

        Response::json(['status' => 'success']);
    }
}
