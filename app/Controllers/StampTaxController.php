<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\StampTaxRepository;

class StampTaxController
{
    public static function index(): void
    {
        $Repo = new StampTaxRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }
        
        $MusteriId = isset($_GET['musteri_id']) ? (int)$_GET['musteri_id'] : 0;
        if ($MusteriId > 0) {
            $Satirlar = $Repo->musteriDamgaVergileri($MusteriId);
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
            if (empty($Girdi[$Alan]) && $Girdi[$Alan] !== 0) {
                Response::error("$Alan alanı zorunludur.", 422);
                return;
            }
        }

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }

        $Repo = new StampTaxRepository();
        $YuklenecekVeri = [
            'MusteriId' => (int)$Girdi['MusteriId'],
            'Tarih' => $Girdi['Tarih'],
            'Tutar' => (float)$Girdi['Tutar'],
            'DovizCinsi' => isset($Girdi['DovizCinsi']) ? trim((string)$Girdi['DovizCinsi']) : 'TRY',
            'Aciklama' => isset($Girdi['Aciklama']) ? trim((string)$Girdi['Aciklama']) : null,
            'BelgeNo' => isset($Girdi['BelgeNo']) ? trim((string)$Girdi['BelgeNo']) : null
        ];

        $Id = $Repo->ekle($YuklenecekVeri, $KullaniciId);

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
        $Repo = new StampTaxRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }

        $Guncellenecek = [];
        if (isset($Girdi['Tarih'])) $Guncellenecek['Tarih'] = $Girdi['Tarih'];
        if (isset($Girdi['Tutar'])) $Guncellenecek['Tutar'] = (float)$Girdi['Tutar'];
        if (isset($Girdi['DovizCinsi'])) $Guncellenecek['DovizCinsi'] = trim((string)$Girdi['DovizCinsi']);
        if (isset($Girdi['Aciklama'])) $Guncellenecek['Aciklama'] = trim((string)$Girdi['Aciklama']);
        if (isset($Girdi['BelgeNo'])) $Guncellenecek['BelgeNo'] = trim((string)$Girdi['BelgeNo']);

        if (!empty($Guncellenecek)) {
            $Repo->guncelle($Id, $Guncellenecek, $KullaniciId);
        }

        Response::json(['status' => 'success']);
    }

    public static function delete(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Geçersiz kayıt.', 422);
            return;
        }

        $Repo = new StampTaxRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }

        $Repo->softSil($Id, $KullaniciId);

        Response::json(['status' => 'success']);
    }
}
