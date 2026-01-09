<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\FileRepository;

class FileController
{
    private static string $uploadDir = __DIR__ . '/../../storage/uploads/';

    public static function index(): void
    {
        $Repo = new FileRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Kullanıcı bilgisi bulunamadı.', 401);
            return;
        }
        
        $MusteriId = isset($_GET['musteri_id']) ? (int)$_GET['musteri_id'] : 0;
        if ($MusteriId > 0) {
            $Satirlar = $Repo->musteriDosyalari($MusteriId);
        } else {
            $Satirlar = $Repo->tumAktifler();
        }
        Response::json(['data' => $Satirlar]);
    }

    public static function store(): void
    {
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Kullanıcı bilgisi bulunamadı.', 401);
            return;
        }

        // Multipart form-data kontrolü
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            Response::error('Dosya yüklenemedi.', 422);
            return;
        }

        $MusteriId = isset($_POST['MusteriId']) ? (int)$_POST['MusteriId'] : 0;
        if ($MusteriId <= 0) {
            Response::error('MusteriId alanı zorunludur.', 422);
            return;
        }

        $File = $_FILES['file'];
        $OriginalName = $File['name'];
        $FileSize = $File['size'];
        $FileType = $File['type'];
        $TempPath = $File['tmp_name'];

        // Güvenli dosya adı oluştur
        $Extension = pathinfo($OriginalName, PATHINFO_EXTENSION);
        $SafeName = uniqid() . '_' . time() . '.' . $Extension;
        
        // Upload dizinini oluştur
        if (!is_dir(self::$uploadDir)) {
            mkdir(self::$uploadDir, 0755, true);
        }

        $DestPath = self::$uploadDir . $SafeName;
        
        if (!move_uploaded_file($TempPath, $DestPath)) {
            Response::error('Dosya kaydedilemedi.', 500);
            return;
        }

        $Repo = new FileRepository();
        $YuklenecekVeri = [
            'MusteriId' => $MusteriId,
            'DosyaAdi' => $OriginalName,
            'DosyaYolu' => 'storage/uploads/' . $SafeName,
            'DosyaTipi' => $FileType,
            'DosyaBoyutu' => $FileSize,
            'Aciklama' => isset($_POST['Aciklama']) ? trim((string)$_POST['Aciklama']) : null
        ];

        $Id = Transaction::wrap(function () use ($Repo, $YuklenecekVeri, $KullaniciId) {
            return $Repo->ekle($YuklenecekVeri, $KullaniciId);
        });

        Response::json(['id' => $Id, 'path' => $YuklenecekVeri['DosyaYolu']], 201);
    }

    public static function update(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Geçersiz kayıt.', 422);
            return;
        }

        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $Repo = new FileRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Kullanıcı bilgisi bulunamadı.', 401);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId) {
            $Guncellenecek = [];
            if (isset($Girdi['Aciklama'])) $Guncellenecek['Aciklama'] = trim((string)$Girdi['Aciklama']);

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

        $Repo = new FileRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Kullanıcı bilgisi bulunamadı.', 401);
            return;
        }

        // Dosyayı fiziksel olarak silmiyoruz, sadece soft delete
        Transaction::wrap(function () use ($Repo, $Id, $KullaniciId) {
            $Repo->softSil($Id, $KullaniciId);
        });

        Response::json(['status' => 'success']);
    }

    public static function download(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Geçersiz kayıt.', 422);
            return;
        }

        $Repo = new FileRepository();
        $Dosya = $Repo->bul($Id);
        
        if (!$Dosya) {
            Response::error('Dosya bulunamadı.', 404);
            return;
        }

        $FilePath = __DIR__ . '/../../' . $Dosya['DosyaYolu'];
        
        if (!file_exists($FilePath)) {
            Response::error('Dosya bulunamadı.', 404);
            return;
        }

        header('Content-Type: ' . ($Dosya['DosyaTipi'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $Dosya['DosyaAdi'] . '"');
        header('Content-Length: ' . filesize($FilePath));
        readfile($FilePath);
        exit;
    }
}
