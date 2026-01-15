<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\OfferRepository;

class OfferController
{
    public static function index(): void
    {
        $Repo = new OfferRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }
        
        $MusteriId = isset($_GET['musteri_id']) ? (int)$_GET['musteri_id'] : 0;
        $ProjeId = isset($_GET['proje_id']) ? (int)$_GET['proje_id'] : 0;
        $Sayfa = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $Limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : (int)env('PAGINATION_DEFAULT', 10);

        if ($MusteriId > 0) {
            if (isset($_GET['page']) || isset($_GET['limit'])) {
                $Sonuc = $Repo->musteriTeklifleriPaginated($MusteriId, $Sayfa, $Limit);
                Response::json($Sonuc);
            } else {
                $Satirlar = $Repo->musteriTeklifleri($MusteriId);
                Response::json(['data' => $Satirlar]);
            }
        } elseif ($ProjeId > 0) {
            $Satirlar = $Repo->projeTeklifleri($ProjeId);
            Response::json(['data' => $Satirlar]);
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
     * Tek Teklif Detayi Getir
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

        $Repo = new OfferRepository();
        $Teklif = $Repo->bul($Id);

        if (!$Teklif) {
            Response::error('Teklif bulunamadi.', 404);
            return;
        }

        Response::json(['data' => $Teklif]);
    }

    public static function store(): void
    {
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $Zorunlu = ['MusteriId', 'TeklifNo'];
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

        $Repo = new OfferRepository();
        $YuklenecekVeri = [
            'MusteriId' => (int)$Girdi['MusteriId'],
            'ProjeId' => !empty($Girdi['ProjeId']) ? (int)$Girdi['ProjeId'] : null,
            'TeklifNo' => trim((string)$Girdi['TeklifNo']),
            'Konu' => $Girdi['Konu'] ?? null,
            'Tutar' => isset($Girdi['Tutar']) ? (float)$Girdi['Tutar'] : 0.00,
            'ParaBirimi' => $Girdi['ParaBirimi'] ?? 'TRY',
            'TeklifTarihi' => $Girdi['TeklifTarihi'] ?? null,
            'GecerlilikTarihi' => $Girdi['GecerlilikTarihi'] ?? null,
            'Durum' => isset($Girdi['Durum']) ? (int)$Girdi['Durum'] : 0
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
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $Repo = new OfferRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId) {
            $Guncellenecek = [];
            if (isset($Girdi['TeklifNo'])) $Guncellenecek['TeklifNo'] = trim((string)$Girdi['TeklifNo']);
            if (isset($Girdi['Konu'])) $Guncellenecek['Konu'] = $Girdi['Konu'];
            if (isset($Girdi['Tutar'])) $Guncellenecek['Tutar'] = (float)$Girdi['Tutar'];
            if (isset($Girdi['ParaBirimi'])) $Guncellenecek['ParaBirimi'] = $Girdi['ParaBirimi'];
            if (isset($Girdi['TeklifTarihi'])) $Guncellenecek['TeklifTarihi'] = $Girdi['TeklifTarihi'];
            if (isset($Girdi['GecerlilikTarihi'])) $Guncellenecek['GecerlilikTarihi'] = $Girdi['GecerlilikTarihi'];
            if (isset($Girdi['Durum'])) $Guncellenecek['Durum'] = (int)$Girdi['Durum'];
            if (isset($Girdi['ProjeId'])) $Guncellenecek['ProjeId'] = (int)$Girdi['ProjeId'];

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
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $Repo = new OfferRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $KullaniciId) {
            $Repo->softSil($Id, $KullaniciId);
        });

        Response::json(['status' => 'success']);
    }
}
