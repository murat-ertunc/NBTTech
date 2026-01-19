<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Repositories\CalendarRepository;

/**
 * TakvimController
 * 
 * Musteri detay sayfasindaki takvim kayitlari icin endpoint'ler
 */
class TakvimController
{
    public static function index(): void
    {
        $Repo = new CalendarRepository();
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
                $Sonuc = $Repo->musteriTakvimleriPaginated($MusteriId, $Sayfa, $Limit);
                Response::json($Sonuc);
            } else {
                $Satirlar = $Repo->musteriTakvimleri($MusteriId);
                Response::json(['data' => $Satirlar]);
            }
        } else {
            $Satirlar = $Repo->tumAktifler();
            Response::json(['data' => $Satirlar]);
        }
    }

    /**
     * Tek Takvim Kaydi Detayi Getir
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

        $Repo = new CalendarRepository();
        $Takvim = $Repo->bul($Id);

        if (!$Takvim) {
            Response::error('Takvim kaydi bulunamadi.', 404);
            return;
        }

        Response::json(['data' => $Takvim]);
    }

    public static function store(): void
    {
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $Zorunlu = ['MusteriId', 'ProjeId', 'TerminTarihi', 'Ozet'];
        foreach ($Zorunlu as $Alan) {
            if (empty($Girdi[$Alan])) {
                Response::error("$Alan alani zorunludur.", 422);
                return;
            }
        }

        // Ozet 255 karakteri gecemez
        if (strlen($Girdi['Ozet']) > 255) {
            Response::error('Ozet 255 karakteri gecemez.', 422);
            return;
        }

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Repo = new CalendarRepository();
        $YuklenecekVeri = [
            'MusteriId' => (int)$Girdi['MusteriId'],
            'ProjeId' => (int)$Girdi['ProjeId'],
            'TerminTarihi' => $Girdi['TerminTarihi'],
            'Ozet' => trim((string)$Girdi['Ozet'])
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
        $Repo = new CalendarRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Guncellenecek = [];
        if (isset($Girdi['TerminTarihi'])) $Guncellenecek['TerminTarihi'] = $Girdi['TerminTarihi'];
        if (isset($Girdi['Ozet'])) {
            if (strlen($Girdi['Ozet']) > 255) {
                Response::error('Ozet 255 karakteri gecemez.', 422);
                return;
            }
            $Guncellenecek['Ozet'] = trim((string)$Girdi['Ozet']);
        }
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

        $Repo = new CalendarRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Repo->softSil($Id, $KullaniciId);

        Response::json(['status' => 'success']);
    }
}
