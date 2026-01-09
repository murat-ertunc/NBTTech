<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\ProjectRepository;

class ProjectController
{
    public static function index(): void
    {
        $Repo = new ProjectRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Kullanıcı bilgisi bulunamadı.', 401);
            return;
        }
        // Istege bagli filtreleme
        $MusteriId = isset($_GET['musteri_id']) ? (int)$_GET['musteri_id'] : 0;
        if ($MusteriId > 0) {
            $Satirlar = $Repo->musteriProjeleri($MusteriId);
        } else {
            $Satirlar = $Repo->tumAktifler();
        }
        Response::json(['data' => $Satirlar]);
    }

    public static function store(): void
    {
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $Zorunlu = ['MusteriId', 'ProjeAdi'];
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

        $Repo = new ProjectRepository();
        $YuklenecekVeri = [
            'MusteriId' => (int)$Girdi['MusteriId'],
            'ProjeAdi' => trim((string)$Girdi['ProjeAdi']),
            'BaslangicTarihi' => $Girdi['BaslangicTarihi'] ?? null,
            'BitisTarihi' => $Girdi['BitisTarihi'] ?? null,
            'Butce' => isset($Girdi['Butce']) ? (float)$Girdi['Butce'] : 0.00,
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
        $Repo = new ProjectRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Kullanıcı bilgisi bulunamadı.', 401);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId) {
            $Guncellenecek = [];
            if (isset($Girdi['ProjeAdi'])) $Guncellenecek['ProjeAdi'] = trim((string)$Girdi['ProjeAdi']);
            if (isset($Girdi['BaslangicTarihi'])) $Guncellenecek['BaslangicTarihi'] = $Girdi['BaslangicTarihi'];
            if (isset($Girdi['BitisTarihi'])) $Guncellenecek['BitisTarihi'] = $Girdi['BitisTarihi'];
            if (isset($Girdi['Butce'])) $Guncellenecek['Butce'] = (float)$Girdi['Butce'];
            if (isset($Girdi['Durum'])) $Guncellenecek['Durum'] = (int)$Girdi['Durum'];

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

        $Repo = new ProjectRepository();
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
