<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\GuaranteeRepository;

class GuaranteeController
{
    public static function index(): void
    {
        $Repo = new GuaranteeRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Kullanıcı bilgisi bulunamadı.', 401);
            return;
        }
        $MusteriId = isset($_GET['musteri_id']) ? (int)$_GET['musteri_id'] : 0;
        if ($MusteriId > 0) {
            $Satirlar = $Repo->musteriTeminatlari($MusteriId);
        } else {
            $Satirlar = $Repo->tumAktifler();
        }
        Response::json(['data' => $Satirlar]);
    }

    public static function store(): void
    {
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $Zorunlu = ['MusteriId', 'Tur', 'Tutar'];
        foreach ($Zorunlu as $Alan) {
            if (empty($Girdi[$Alan])) {
                Response::error("$Alan alanı zorunludur.", 422);
                return;
            }
        }

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Kullanıcı bilgisi bulunamadı.', 401);
            return;
        }

        $Repo = new GuaranteeRepository();
        $YuklenecekVeri = [
            'MusteriId' => (int)$Girdi['MusteriId'],
            'ProjeId' => !empty($Girdi['ProjeId']) ? (int)$Girdi['ProjeId'] : null,
            'Tur' => trim((string)$Girdi['Tur']),
            'Tutar' => (float)$Girdi['Tutar'],
            'ParaBirimi' => $Girdi['ParaBirimi'] ?? 'TRY',
            'BankaAdi' => $Girdi['BankaAdi'] ?? null,
            'VadeTarihi' => $Girdi['VadeTarihi'] ?? null,
            'BelgeNo' => $Girdi['BelgeNo'] ?? null,
            'Durum' => isset($Girdi['Durum']) ? (int)$Girdi['Durum'] : 1
        ];

        $Id = Transaction::wrap(function () use ($Repo, $YuklenecekVeri, $KullaniciId) {
            return $Repo->ekle($YuklenecekVeri, $KullaniciId);
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
        $Repo = new GuaranteeRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Kullanıcı bilgisi bulunamadı.', 401);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId) {
            $Guncellenecek = [];
            if (isset($Girdi['Tur'])) $Guncellenecek['Tur'] = trim((string)$Girdi['Tur']);
            if (isset($Girdi['Tutar'])) $Guncellenecek['Tutar'] = (float)$Girdi['Tutar'];
            if (isset($Girdi['ParaBirimi'])) $Guncellenecek['ParaBirimi'] = $Girdi['ParaBirimi'];
            if (isset($Girdi['BankaAdi'])) $Guncellenecek['BankaAdi'] = $Girdi['BankaAdi'];
            if (isset($Girdi['VadeTarihi'])) $Guncellenecek['VadeTarihi'] = $Girdi['VadeTarihi'];
            if (isset($Girdi['BelgeNo'])) $Guncellenecek['BelgeNo'] = $Girdi['BelgeNo'];
            if (isset($Girdi['Durum'])) $Guncellenecek['Durum'] = (int)$Girdi['Durum'];
            if (isset($Girdi['ProjeId'])) $Guncellenecek['ProjeId'] = !empty($Girdi['ProjeId']) ? (int)$Girdi['ProjeId'] : null;

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

        $Repo = new GuaranteeRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Kullanıcı bilgisi bulunamadı.', 401);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $KullaniciId) {
            $Repo->softSil($Id, $KullaniciId);
        });

        Response::json(['status' => 'success']);
    }
}
