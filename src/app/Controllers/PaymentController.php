<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\PaymentRepository;
use App\Services\Logger\ActionLogger;
use App\Services\CalendarService;

class PaymentController
{
    public static function index(): void
    {
        $Repo = new PaymentRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $MusteriId = isset($_GET['musteri_id']) ? (int)$_GET['musteri_id'] : 0;
        $Sayfa = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $Limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : (int)env('PAGINATION_DEFAULT', 10);

        if ($MusteriId > 0) {
            if (isset($_GET['page']) || isset($_GET['limit'])) {
                $Sonuc = $Repo->musteriyeGorePaginated($MusteriId, $Sayfa, $Limit);
                Response::json($Sonuc);
            } else {
                $Satirlar = $Repo->musteriyeGore($MusteriId);
                Response::json(['data' => $Satirlar]);
            }
        } else {
            // Standalone sayfalarda pagination varsa paginated sonuc dondur
            if (isset($_GET['page']) || isset($_GET['limit'])) {
                $Sonuc = $Repo->tumAktiflerPaginated($Sayfa, $Limit);
                Response::json($Sonuc);
            } else {
                $Satirlar = $Repo->tumAktifler();
                Response::json(['data' => $Satirlar]);
            }
        }
    }

    /**
     * Tek Odeme Detayi Getir
     */
    public static function show(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 404);
            return;
        }

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Repo = new PaymentRepository();
        $Odeme = $Repo->bul($Id);

        if (!$Odeme) {
            Response::error('Odeme bulunamadi.', 404);
            return;
        }

        Response::json(['data' => $Odeme]);
    }

    public static function store(): void
    {
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $Zorunlu = ['MusteriId', 'ProjeId', 'FaturaId', 'Tarih', 'Tutar'];
        foreach ($Zorunlu as $Alan) {
            if (empty($Girdi[$Alan])) {
                Response::error("$Alan alani zorunludur.", 422);
                return;
            }
        }
        
        $MusteriId = (int)$Girdi['MusteriId'];
        $ProjeId = (int)$Girdi['ProjeId'];
        $FaturaId = (int)$Girdi['FaturaId'];
        $Tutar = (float)$Girdi['Tutar'];
        $Tarih = trim((string)$Girdi['Tarih']); 
        $Aciklama = isset($Girdi['Aciklama']) ? trim((string)$Girdi['Aciklama']) : null;

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Repo = new PaymentRepository();
        $Id = Transaction::wrap(function () use ($Repo, $MusteriId, $ProjeId, $FaturaId, $Tarih, $Tutar, $Aciklama, $KullaniciId) {
            return $Repo->ekle([
                'MusteriId' => $MusteriId,
                'ProjeId' => $ProjeId,
                'FaturaId' => $FaturaId,
                'Tarih' => $Tarih,
                'Tutar' => $Tutar,
                'Aciklama' => $Aciklama
            ], $KullaniciId);
        });

        // Takvim hatirlatmasi olustur - tarih varsa
        if (!empty($Tarih)) {
            $OdemeAciklama = !empty($Aciklama) ? $Aciklama : 'Ödeme';
            CalendarService::createOrUpdateReminder(
                $MusteriId,
                'odeme',
                $Id,
                $Tarih,
                'Ödeme: ' . $OdemeAciklama,
                $KullaniciId
            );
        }

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
        $Repo = new PaymentRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            Response::error('Odeme bulunamadi.', 404);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId) {
            $Guncellenecek = [];
            if (isset($Girdi['ProjeId'])) $Guncellenecek['ProjeId'] = (int)$Girdi['ProjeId'];
            if (isset($Girdi['Tarih'])) $Guncellenecek['Tarih'] = $Girdi['Tarih'];
            if (isset($Girdi['Tutar'])) $Guncellenecek['Tutar'] = (float)$Girdi['Tutar'];
            if (isset($Girdi['FaturaId'])) $Guncellenecek['FaturaId'] = !empty($Girdi['FaturaId']) ? (int)$Girdi['FaturaId'] : null;
            if (isset($Girdi['Aciklama'])) $Guncellenecek['Aciklama'] = $Girdi['Aciklama'];

            if (!empty($Guncellenecek)) {
                $Repo->guncelle($Id, $Guncellenecek, $KullaniciId);
            }
        });

        // Takvim hatirlatmasi guncelle - tarih varsa
        if (isset($Girdi['Tarih'])) {
            $Mevcut = $Repo->bul($Id);
            if ($Mevcut) {
                $Aciklama = isset($Girdi['Aciklama']) ? $Girdi['Aciklama'] : ($Mevcut['Aciklama'] ?? 'Ödeme');
                CalendarService::createOrUpdateReminder(
                    (int)$Mevcut['MusteriId'],
                    'odeme',
                    $Id,
                    $Girdi['Tarih'],
                    'Ödeme: ' . $Aciklama,
                    $KullaniciId
                );
            }
        }

        Response::json(['status' => 'success']);
    }

    public static function delete(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $Repo = new PaymentRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            Response::error('Odeme bulunamadi.', 404);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $KullaniciId) {
            $Repo->yedekle($Id, 'bck_tbl_odeme', $KullaniciId);
            $Repo->softSil($Id, $KullaniciId);
            ActionLogger::delete('tbl_odeme', ['Id' => $Id]);
        });

        // Takvim hatirlatmasini sil
        CalendarService::deleteReminder('odeme', $Id);

        Response::json(['status' => 'success']);
    }
}
