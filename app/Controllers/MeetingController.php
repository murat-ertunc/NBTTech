<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\MeetingRepository;

class MeetingController
{
    public static function index(): void
    {
        $Repo = new MeetingRepository();
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
                $Sonuc = $Repo->musteriGorusmeleriPaginated($MusteriId, $Sayfa, $Limit);
                Response::json($Sonuc);
            } else {
                $Satirlar = $Repo->musteriGorusmeleri($MusteriId);
                Response::json(['data' => $Satirlar]);
            }
        } else {
            $Satirlar = $Repo->tumAktifler();
            Response::json(['data' => $Satirlar]);
        }
    }

    /**
     * Tek Gorusme Detayi Getir
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

        $Repo = new MeetingRepository();
        $Gorusme = $Repo->bul($Id);

        if (!$Gorusme) {
            Response::error('Gorusme bulunamadi.', 404);
            return;
        }

        Response::json(['data' => $Gorusme]);
    }

    public static function store(): void
    {
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $Zorunlu = ['MusteriId', 'Tarih', 'Konu'];
        foreach ($Zorunlu as $Alan) {
            if (empty($Girdi[$Alan])) {
                Response::error("$Alan alani zorunludur.", 422);
                return;
            }
        }

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Repo = new MeetingRepository();
        $YuklenecekVeri = [
            'MusteriId' => (int)$Girdi['MusteriId'],
            'ProjeId' => isset($Girdi['ProjeId']) && $Girdi['ProjeId'] ? (int)$Girdi['ProjeId'] : null,
            'Tarih' => $Girdi['Tarih'],
            'Konu' => trim((string)$Girdi['Konu']),
            'Notlar' => isset($Girdi['Notlar']) ? trim((string)$Girdi['Notlar']) : null,
            'Kisi' => isset($Girdi['Kisi']) ? trim((string)$Girdi['Kisi']) : null
        ];

        $Id = $Repo->ekle($YuklenecekVeri, $KullaniciId);

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
        $Repo = new MeetingRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Guncellenecek = [];
        if (isset($Girdi['Tarih'])) $Guncellenecek['Tarih'] = $Girdi['Tarih'];
        if (isset($Girdi['Konu'])) $Guncellenecek['Konu'] = trim((string)$Girdi['Konu']);
        if (isset($Girdi['Notlar'])) $Guncellenecek['Notlar'] = trim((string)$Girdi['Notlar']);
        if (isset($Girdi['Kisi'])) $Guncellenecek['Kisi'] = trim((string)$Girdi['Kisi']);
        if (array_key_exists('ProjeId', $Girdi)) $Guncellenecek['ProjeId'] = $Girdi['ProjeId'] ? (int)$Girdi['ProjeId'] : null;

        if (!empty($Guncellenecek)) {
            $Repo->guncelle($Id, $Guncellenecek, $KullaniciId);
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

        $Repo = new MeetingRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Repo->softSil($Id, $KullaniciId);

        Response::json(['status' => 'success']);
    }
}
