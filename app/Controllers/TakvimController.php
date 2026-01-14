<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Repositories\CalendarRepository;

/**
 * TakvimController
 * 
 * Müşteri detay sayfasındaki takvim kayıtları için endpoint'ler
 */
class TakvimController
{
    public static function index(): void
    {
        $Repo = new CalendarRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }
        
        $MusteriId = isset($_GET['musteri_id']) ? (int)$_GET['musteri_id'] : 0;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : (int)env('PAGINATION_DEFAULT', 10);

        if ($MusteriId > 0) {
            if (isset($_GET['page']) || isset($_GET['limit'])) {
                $result = $Repo->musteriTakvimleriPaginated($MusteriId, $page, $limit);
                Response::json($result);
            } else {
                $Satirlar = $Repo->musteriTakvimleri($MusteriId);
                Response::json(['data' => $Satirlar]);
            }
        } else {
            $Satirlar = $Repo->tumAktifler();
            Response::json(['data' => $Satirlar]);
        }
    }

    public static function store(): void
    {
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $Zorunlu = ['MusteriId', 'ProjeId', 'BaslangicTarihi', 'BitisTarihi', 'Ozet'];
        foreach ($Zorunlu as $Alan) {
            if (empty($Girdi[$Alan])) {
                Response::error("$Alan alanı zorunludur.", 422);
                return;
            }
        }

        // Özet 255 karakteri geçemez
        if (strlen($Girdi['Ozet']) > 255) {
            Response::error('Özet 255 karakteri geçemez.', 422);
            return;
        }

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }

        $Repo = new CalendarRepository();
        $YuklenecekVeri = [
            'MusteriId' => (int)$Girdi['MusteriId'],
            'ProjeId' => (int)$Girdi['ProjeId'],
            'BaslangicTarihi' => $Girdi['BaslangicTarihi'],
            'BitisTarihi' => $Girdi['BitisTarihi'],
            'Ozet' => trim((string)$Girdi['Ozet'])
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
        $Repo = new CalendarRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }

        $Guncellenecek = [];
        if (isset($Girdi['BaslangicTarihi'])) $Guncellenecek['BaslangicTarihi'] = $Girdi['BaslangicTarihi'];
        if (isset($Girdi['BitisTarihi'])) $Guncellenecek['BitisTarihi'] = $Girdi['BitisTarihi'];
        if (isset($Girdi['Ozet'])) {
            if (strlen($Girdi['Ozet']) > 255) {
                Response::error('Özet 255 karakteri geçemez.', 422);
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
            Response::error('Geçersiz kayıt.', 422);
            return;
        }

        $Repo = new CalendarRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }

        $Repo->softSil($Id, $KullaniciId);

        Response::json(['status' => 'success']);
    }
}
