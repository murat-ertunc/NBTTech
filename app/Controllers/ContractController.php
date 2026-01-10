<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\ContractRepository;

class ContractController
{
    public static function index(): void
    {
        $Repo = new ContractRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }
        $MusteriId = isset($_GET['musteri_id']) ? (int)$_GET['musteri_id'] : 0;
        if ($MusteriId > 0) {
            $Satirlar = $Repo->musteriSozlesmeleri($MusteriId);
        } else {
            $Satirlar = $Repo->tumAktifler();
        }
        Response::json(['data' => $Satirlar]);
    }

    public static function store(): void
    {
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $Zorunlu = ['MusteriId', 'SozlesmeNo'];
        foreach ($Zorunlu as $Alan) {
            if (empty($Girdi[$Alan])) {
                Response::error("$Alan alanı zorunludur.", 422);
                return;
            }
        }

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }

        $Repo = new ContractRepository();
        $YuklenecekVeri = [
            'MusteriId' => (int)$Girdi['MusteriId'],
            'TeklifId' => !empty($Girdi['TeklifId']) ? (int)$Girdi['TeklifId'] : null,
            'SozlesmeNo' => trim((string)$Girdi['SozlesmeNo']),
            'BaslangicTarihi' => $Girdi['BaslangicTarihi'] ?? null,
            'BitisTarihi' => $Girdi['BitisTarihi'] ?? null,
            'Tutar' => isset($Girdi['Tutar']) ? (float)$Girdi['Tutar'] : 0.00,
            'ParaBirimi' => $Girdi['ParaBirimi'] ?? 'TRY',
            'DosyaYolu' => $Girdi['DosyaYolu'] ?? null,
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
        $Repo = new ContractRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId) {
            $Guncellenecek = [];
            if (isset($Girdi['SozlesmeNo'])) $Guncellenecek['SozlesmeNo'] = trim((string)$Girdi['SozlesmeNo']);
            if (isset($Girdi['BaslangicTarihi'])) $Guncellenecek['BaslangicTarihi'] = $Girdi['BaslangicTarihi'];
            if (isset($Girdi['BitisTarihi'])) $Guncellenecek['BitisTarihi'] = $Girdi['BitisTarihi'];
            if (isset($Girdi['Tutar'])) $Guncellenecek['Tutar'] = (float)$Girdi['Tutar'];
            if (isset($Girdi['ParaBirimi'])) $Guncellenecek['ParaBirimi'] = $Girdi['ParaBirimi'];
            if (isset($Girdi['DosyaYolu'])) $Guncellenecek['DosyaYolu'] = $Girdi['DosyaYolu'];
            if (isset($Girdi['Durum'])) $Guncellenecek['Durum'] = (int)$Girdi['Durum'];
            if (isset($Girdi['TeklifId'])) $Guncellenecek['TeklifId'] = (int)$Girdi['TeklifId'];

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

        $Repo = new ContractRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $KullaniciId) {
            $Repo->softSil($Id, $KullaniciId);
        });

        Response::json(['status' => 'success']);
    }
}
