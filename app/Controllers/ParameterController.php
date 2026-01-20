<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\ParameterRepository;
use App\Services\Authorization\AuthorizationService;
use App\Services\CalendarService;
use App\Services\Logger\ActionLogger;

class ParameterController
{
    /**
     * Tum parametreleri listele
     */
    public static function index(): void
    {
        $Repo = new ParameterRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        // Grup parametresi varsa o grubu getir
        $Grup = isset($_GET['grup']) ? trim($_GET['grup']) : null;
        
        if ($Grup) {
            $Satirlar = $Repo->grubaGore($Grup);
            Response::json(['data' => $Satirlar]);
        } else {
            $Gruplu = $Repo->grupluGetir();
            Response::json(['data' => $Gruplu]);
        }
    }

    /**
     * Aktif dovizleri getir
     */
    public static function currencies(): void
    {
        $Repo = new ParameterRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Dovizler = $Repo->aktifDovizler();
        $Varsayilan = $Repo->varsayilanDoviz();
        
        Response::json([
            'data' => $Dovizler,
            'default' => $Varsayilan ? $Varsayilan['Kod'] : 'TRY'
        ]);
    }

    /**
     * Durum parametrelerini getir (belirli bir entity icin)
     */
    public static function statuses(): void
    {
        $Repo = new ParameterRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Entity = isset($_GET['entity']) ? trim($_GET['entity']) : null;
        if (!$Entity) {
            Response::error('Entity parametresi zorunludur.', 422);
            return;
        }

        $Grup = 'durum_' . $Entity;
        $Durumlar = $Repo->grubaGore($Grup);
        
        // Frontend icin array formatinda don
        $Sonuc = [];
        foreach ($Durumlar as $Durum) {
            $Sonuc[] = [
                'Kod' => $Durum['Kod'],
                'Etiket' => $Durum['Etiket'],
                'Deger' => $Durum['Deger'],
                'Varsayilan' => (bool)$Durum['Varsayilan']
            ];
        }
        
        Response::json(['data' => $Sonuc]);
    }

    /**
     * Genel ayarlari getir (pagination vb)
     */
    public static function settings(): void
    {
        $Repo = new ParameterRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $PaginationDefault = $Repo->paginationDefault();
        $VarsayilanDoviz = $Repo->varsayilanDoviz();
        $AktifDovizler = $Repo->aktifDovizler();

        Response::json([
            'paginationDefault' => $PaginationDefault,
            'defaultCurrency' => $VarsayilanDoviz ? $VarsayilanDoviz['Kod'] : 'TRY',
            'activeCurrencies' => array_column($AktifDovizler, 'Kod')
        ]);
    }

    /**
     * Yeni parametre ekle
     */
    public static function store(): void
    {
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $Zorunlu = ['Grup', 'Kod', 'Etiket'];
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

        // Permission kontrolu
        $AuthService = AuthorizationService::getInstance();
        if (!$AuthService->can($KullaniciId, 'parameters.create')) {
            Response::error('Bu islem icin yetkiniz yok.', 403);
            return;
        }

        $Repo = new ParameterRepository();
        $YuklenecekVeri = [
            'Grup' => trim((string)$Girdi['Grup']),
            'Kod' => trim((string)$Girdi['Kod']),
            'Deger' => isset($Girdi['Deger']) ? trim((string)$Girdi['Deger']) : null,
            'Etiket' => trim((string)$Girdi['Etiket']),
            'Sira' => isset($Girdi['Sira']) ? (int)$Girdi['Sira'] : 0,
            'Aktif' => isset($Girdi['Aktif']) ? (bool)$Girdi['Aktif'] : true,
            'Varsayilan' => isset($Girdi['Varsayilan']) ? (bool)$Girdi['Varsayilan'] : false
        ];

        $Id = $Repo->ekle($YuklenecekVeri, $KullaniciId);

        Response::json(['id' => $Id], 201);
    }

    /**
     * Parametre guncelle
     */
    public static function update(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int)$Parametreler['id'] : 0;
        if ($Id <= 0) {
            ActionLogger::error('ParameterController::update', 'Gecersiz parametre ID: ' . ($Parametreler['id'] ?? 'null'));
            Response::error('Parametre ID gecersiz veya eksik.', 422);
            return;
        }

        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        // Permission kontrolu
        $AuthService = AuthorizationService::getInstance();
        if (!$AuthService->can($KullaniciId, 'parameters.update')) {
            ActionLogger::error('ParameterController::update', 'Yetkisiz erisim denemesi. UserId: ' . $KullaniciId);
            Response::error('Bu islem icin yetkiniz yok.', 403);
            return;
        }

        $Repo = new ParameterRepository();
        
        // Kayit mevcut mu kontrol et
        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            ActionLogger::error('ParameterController::update', 'Parametre bulunamadi. ID: ' . $Id);
            Response::error('Parametre bulunamadi.', 404);
            return;
        }
        
        $Guncellenecek = [];
        if (isset($Girdi['Kod'])) $Guncellenecek['Kod'] = trim((string)$Girdi['Kod']);
        if (isset($Girdi['Deger'])) $Guncellenecek['Deger'] = trim((string)$Girdi['Deger']);
        if (isset($Girdi['Etiket'])) $Guncellenecek['Etiket'] = trim((string)$Girdi['Etiket']);
        if (isset($Girdi['Sira'])) $Guncellenecek['Sira'] = (int)$Girdi['Sira'];
        if (isset($Girdi['Aktif'])) {
            $Repo->aktiflikDegistir($Id, (bool)$Girdi['Aktif'], $KullaniciId);
        }
        if (isset($Girdi['Varsayilan']) && $Girdi['Varsayilan']) {
            $Repo->varsayilanYap($Id, $KullaniciId);
        }

        if (!empty($Guncellenecek)) {
            $Repo->guncelle($Id, $Guncellenecek, $KullaniciId);
        }

        Response::json(['status' => 'success']);
    }

    /**
     * Parametre sil
     */
    public static function delete(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int)$Parametreler['id'] : 0;
        if ($Id <= 0) {
            ActionLogger::error('ParameterController::delete', 'Gecersiz parametre ID: ' . ($Parametreler['id'] ?? 'null'));
            Response::error('Parametre ID gecersiz veya eksik.', 422);
            return;
        }

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        // Permission kontrolu
        $AuthService = AuthorizationService::getInstance();
        if (!$AuthService->can($KullaniciId, 'parameters.delete')) {
            ActionLogger::error('ParameterController::delete', 'Yetkisiz erisim denemesi. UserId: ' . $KullaniciId);
            Response::error('Bu islem icin yetkiniz yok.', 403);
            return;
        }

        $Repo = new ParameterRepository();
        
        // Kayit mevcut mu kontrol et
        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            ActionLogger::error('ParameterController::delete', 'Parametre bulunamadi. ID: ' . $Id);
            Response::error('Parametre bulunamadi.', 404);
            return;
        }
        
        $Repo->yedekle($Id, 'bck_tbl_parametre', $KullaniciId);
        $Repo->softSil($Id, $KullaniciId);

        Response::json(['status' => 'success']);
    }

    /**
     * Toplu guncelleme (aktiflik ve varsayilan ayarlari)
     */
    public static function bulkUpdate(): void
    {
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        // Permission kontrolu
        $AuthService = AuthorizationService::getInstance();
        if (!$AuthService->can($KullaniciId, 'parameters.update')) {
            Response::error('Bu islem icin yetkiniz yok.', 403);
            return;
        }

        $Repo = new ParameterRepository();

        Transaction::wrap(function () use ($Girdi, $Repo, $KullaniciId) {
            // Aktiflik guncellemeleri
            if (isset($Girdi['aktiflik']) && is_array($Girdi['aktiflik'])) {
                foreach ($Girdi['aktiflik'] as $item) {
                    $Repo->aktiflikDegistir((int)$item['id'], (bool)$item['aktif'], $KullaniciId);
                }
            }

            // Varsayilan guncellemeleri
            if (isset($Girdi['varsayilan']) && is_array($Girdi['varsayilan'])) {
                foreach ($Girdi['varsayilan'] as $item) {
                    if ($item['varsayilan']) {
                        $Repo->varsayilanYap((int)$item['id'], $KullaniciId);
                    }
                }
            }

            // Deger guncellemeleri
            if (isset($Girdi['degerler']) && is_array($Girdi['degerler'])) {
                foreach ($Girdi['degerler'] as $item) {
                    $Repo->guncelle((int)$item['id'], ['Deger' => $item['deger']], $KullaniciId);
                }
            }
        });

        Response::json(['status' => 'success']);
    }

    /**
     * Hatirlatma ayarlarini getir
     */
    public static function reminderSettings(): void
    {
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Settings = CalendarService::getAllReminderSettings();
        Response::json(['data' => $Settings]);
    }

    /**
     * Hatirlatma ayarlarini guncelle
     */
    public static function updateReminderSettings(): void
    {
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        // Permission kontrolu
        $AuthService = AuthorizationService::getInstance();
        if (!$AuthService->can($KullaniciId, 'parameters.update')) {
            Response::error('Bu islem icin yetkiniz yok.', 403);
            return;
        }

        $Repo = new ParameterRepository();
        
        Transaction::wrap(function () use ($Repo, $Girdi, $KullaniciId) {
            foreach ($Girdi as $type => $settings) {
                // Gun parametresini guncelle
                if (isset($settings['gun'])) {
                    $gunParamAd = $type . '_hatirlatma_gun';
                    if ($type === 'teklif') $gunParamAd = 'teklif_gecerlilik_hatirlatma_gun';
                    if ($type === 'teminat') $gunParamAd = 'teminat_termin_hatirlatma_gun';
                    
                    $Repo->degerGuncelle($gunParamAd, (string)$settings['gun'], $KullaniciId);
                }
                
                // Aktif parametresini guncelle
                if (isset($settings['aktif'])) {
                    $aktifParamAd = $type . '_hatirlatma_aktif';
                    if ($type === 'teklif') $aktifParamAd = 'teklif_gecerlilik_hatirlatma_aktif';
                    if ($type === 'teminat') $aktifParamAd = 'teminat_termin_hatirlatma_aktif';
                    
                    $Repo->degerGuncelle($aktifParamAd, $settings['aktif'] ? '1' : '0', $KullaniciId);
                }
            }
        });

        Response::json(['status' => 'success']);
    }
}
