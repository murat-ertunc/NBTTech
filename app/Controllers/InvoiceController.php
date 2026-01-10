<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\InvoiceRepository;
use App\Services\Logger\ActionLogger;

class InvoiceController
{
    public static function index(): void
    {
        $Repo = new InvoiceRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }

        // MusteriId filtresi opsiyonel mi zorunlu mu? UI tasarımında musteri detayda listelenecek.
        // Eger MusteriId gelmezse tum faturalar (belki rapor ekrani icin).
        $MusteriId = isset($_GET['musteri_id']) ? (int)$_GET['musteri_id'] : 0;

        if ($MusteriId > 0) {
            $Satirlar = $Repo->musteriyeGore($MusteriId);
        } else {
            // Tum faturalari getir (veya yetkiye gore) -> Simdilik tum aktifler
            $Satirlar = $Repo->tumAktifler();
        }
        
        Response::json(['data' => $Satirlar]);
    }

    public static function store(): void
    {
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $Zorunlu = ['MusteriId', 'Tarih', 'Tutar'];
        foreach ($Zorunlu as $Alan) {
            if (empty($Girdi[$Alan])) {
                Response::error("$Alan alanı zorunludur.", 422);
                return;
            }
        }
        
        $MusteriId = (int)$Girdi['MusteriId'];
        $Tutar = (float)$Girdi['Tutar'];
        $Tarih = trim((string)$Girdi['Tarih']); // YYYY-MM-DD formatinda gelmeli
        $Doviz = isset($Girdi['DovizCinsi']) ? trim((string)$Girdi['DovizCinsi']) : 'TL';
        $Aciklama = isset($Girdi['Aciklama']) ? trim((string)$Girdi['Aciklama']) : null;

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }

        $Repo = new InvoiceRepository();
        $Id = Transaction::wrap(function () use ($Repo, $MusteriId, $Tarih, $Tutar, $Doviz, $Aciklama, $KullaniciId) {
            return $Repo->ekle([
                'MusteriId' => $MusteriId,
                'Tarih' => $Tarih,
                'Tutar' => $Tutar,
                'DovizCinsi' => $Doviz,
                'Aciklama' => $Aciklama
            ], $KullaniciId);
        });

        Response::json(['id' => $Id], 201);
    }

    public static function update(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Geçersiz kayıt.', 422);
            return;
        }

        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $Repo = new InvoiceRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }

        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            Response::error('Fatura bulunamadı.', 404);
            return;
        }

        // Basit validasyonlar eklenebilir.

        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId) {
            $Guncellenecek = [];
            if (isset($Girdi['Tarih'])) $Guncellenecek['Tarih'] = $Girdi['Tarih'];
            if (isset($Girdi['Tutar'])) $Guncellenecek['Tutar'] = (float)$Girdi['Tutar'];
            if (isset($Girdi['DovizCinsi'])) $Guncellenecek['DovizCinsi'] = $Girdi['DovizCinsi'];
            if (isset($Girdi['Aciklama'])) $Guncellenecek['Aciklama'] = $Girdi['Aciklama'];

            if (!empty($Guncellenecek)) {
                $Repo->guncelle($Id, $Guncellenecek, $KullaniciId);
            }
        });

        Response::json(['status' => 'success']);
    }

    public static function delete(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Geçersiz kayıt.', 422);
            return;
        }

        $Repo = new InvoiceRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }

        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            Response::error('Fatura bulunamadı.', 404);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $KullaniciId) {
            $Repo->yedekle($Id, 'bck_tbl_fatura', $KullaniciId);
            $Repo->softSil($Id, $KullaniciId);
            ActionLogger::delete('tbl_fatura', ['Id' => $Id]);
        });

        Response::json(['status' => 'success']);
    }
}
