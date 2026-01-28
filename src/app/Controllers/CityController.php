<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\CityRepository;

/**
 * Şehir (İl) Controller
 */
class CityController
{
    public static function index(): void
    {
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Repo = new CityRepository();
        $Satirlar = $Repo->tumAktifler();
        Response::json(['data' => $Satirlar]);
    }

    public static function show(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int)$Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Repo = new CityRepository();
        $Sehir = $Repo->bul($Id);
        if (!$Sehir) {
            Response::error('Sehir bulunamadi.', 404);
            return;
        }

        Response::json($Sehir);
    }

    public static function store(): void
    {
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        
        if (empty($Girdi['Ad'])) {
            Response::error('Ad alani zorunludur.', 422);
            return;
        }

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Repo = new CityRepository();
        
        // Duplicate kontrolü
        $Mevcut = $Repo->adIleBul(trim($Girdi['Ad']));
        if ($Mevcut) {
            Response::error('Bu isimde bir sehir zaten mevcut.', 422);
            return;
        }

        $Id = Transaction::wrap(function () use ($Repo, $Girdi, $KullaniciId) {
            return $Repo->ekle([
                'PlakaKodu' => trim($Girdi['PlakaKodu'] ?? ''),
                'Ad' => trim($Girdi['Ad']),
                'Bolge' => isset($Girdi['Bolge']) ? trim($Girdi['Bolge']) : null
            ], $KullaniciId);
        });

        Response::json(['id' => $Id], 201);
    }

    public static function update(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int)$Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Repo = new CityRepository();
        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            Response::error('Sehir bulunamadi.', 404);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId) {
            $Guncellenecek = [];
            if (isset($Girdi['PlakaKodu'])) $Guncellenecek['PlakaKodu'] = trim($Girdi['PlakaKodu']);
            if (isset($Girdi['Ad'])) $Guncellenecek['Ad'] = trim($Girdi['Ad']);
            if (array_key_exists('Bolge', $Girdi)) $Guncellenecek['Bolge'] = $Girdi['Bolge'] ? trim($Girdi['Bolge']) : null;

            if (!empty($Guncellenecek)) {
                $Repo->guncelle($Id, $Guncellenecek, $KullaniciId);
            }
        });

        Response::json(['status' => 'success']);
    }

    public static function delete(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int)$Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Repo = new CityRepository();
        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            Response::error('Sehir bulunamadi.', 404);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $KullaniciId) {
            $Repo->softSil($Id, $KullaniciId);
        });

        Response::json(['status' => 'success']);
    }
}
