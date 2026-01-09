<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\PaymentRepository;
use App\Services\Logger\ActionLogger;

class PaymentController
{
    public static function index(): void
    {
        $Repo = new PaymentRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Kullanıcı bilgisi bulunamadı.', 401);
            return;
        }

        $MusteriId = isset($_GET['musteri_id']) ? (int)$_GET['musteri_id'] : 0;

        if ($MusteriId > 0) {
            $Satirlar = $Repo->musteriyeGore($MusteriId);
        } else {
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
        $FaturaId = !empty($Girdi['FaturaId']) ? (int)$Girdi['FaturaId'] : null;
        $Tutar = (float)$Girdi['Tutar'];
        $Tarih = trim((string)$Girdi['Tarih']); 
        $Aciklama = isset($Girdi['Aciklama']) ? trim((string)$Girdi['Aciklama']) : null;

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Kullanıcı bilgisi bulunamadı.', 401);
            return;
        }

        $Repo = new PaymentRepository();
        $Id = Transaction::wrap(function () use ($Repo, $MusteriId, $FaturaId, $Tarih, $Tutar, $Aciklama, $KullaniciId) {
            return $Repo->ekle([
                'MusteriId' => $MusteriId,
                'FaturaId' => $FaturaId,
                'Tarih' => $Tarih,
                'Tutar' => $Tutar,
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
        $Repo = new PaymentRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Kullanıcı bilgisi bulunamadı.', 401);
            return;
        }

        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            Response::error('Ödeme bulunamadı.', 404);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId) {
            $Guncellenecek = [];
            if (isset($Girdi['Tarih'])) $Guncellenecek['Tarih'] = $Girdi['Tarih'];
            if (isset($Girdi['Tutar'])) $Guncellenecek['Tutar'] = (float)$Girdi['Tutar'];
            if (isset($Girdi['FaturaId'])) $Guncellenecek['FaturaId'] = !empty($Girdi['FaturaId']) ? (int)$Girdi['FaturaId'] : null;
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

        $Repo = new PaymentRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Kullanıcı bilgisi bulunamadı.', 401);
            return;
        }

        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            Response::error('Ödeme bulunamadı.', 404);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $KullaniciId) {
            $Repo->yedekle($Id, 'bck_tbl_odeme', $KullaniciId);
            $Repo->softSil($Id, $KullaniciId);
            ActionLogger::delete('tbl_odeme', ['Id' => $Id]);
        });

        Response::json(['status' => 'success']);
    }
}
