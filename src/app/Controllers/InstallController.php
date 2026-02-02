<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;







class InstallController
{
    


    public static function run(): void
    {
        
        $AuthUser = env('MIGRATION_BASIC_USER', 'admin');
        $AuthPass = env('MIGRATION_BASIC_PASS', 'Super123!');
        
        $GivenUser = $_SERVER['PHP_AUTH_USER'] ?? '';
        $GivenPass = $_SERVER['PHP_AUTH_PW'] ?? '';
        
        if ($GivenUser !== $AuthUser || $GivenPass !== $AuthPass) {
            header('WWW-Authenticate: Basic realm="Installation"');
            Response::json(['error' => 'Yetkisiz erisim'], 401);
            return;
        }
        
        $SqlDir = SRC_PATH . 'sql';
        $Files = glob($SqlDir . '/*.sql') ?: [];
        sort($Files); 
        
        $Sonuclar = [];
        $BasariliSayisi = 0;
        $HataSayisi = 0;
        
        try {
            $Db = Database::connection();
            
            foreach ($Files as $DosyaYolu) {
                $DosyaAdi = basename($DosyaYolu);
                $Sonuc = [
                    'dosya' => $DosyaAdi,
                    'durum' => 'basarili',
                    'mesaj' => '',
                ];
                
                try {
                    $Sql = file_get_contents($DosyaYolu);
                    
                    
                    $Parcalar = preg_split('/^\s*GO\s*$/mi', $Sql);
                    
                    foreach ($Parcalar as $Parca) {
                        $Parca = trim($Parca);
                        if ($Parca !== '') {
                            $Db->exec($Parca);
                        }
                    }
                    
                    $BasariliSayisi++;
                    $Sonuc['mesaj'] = 'Migration basariyla calistirildi.';
                    
                } catch (\Throwable $e) {
                    $HataSayisi++;
                    $Sonuc['durum'] = 'hata';
                    $Sonuc['mesaj'] = $e->getMessage();
                }
                
                $Sonuclar[] = $Sonuc;
            }
            
        } catch (\Throwable $e) {
            Response::json([
                'error' => 'Veritabani baglantisi kurulamadi',
                'detay' => $e->getMessage()
            ], 500);
            return;
        }
        
        Response::json([
            'durum' => $HataSayisi === 0 ? 'basarili' : 'kismi_basarili',
            'ozet' => [
                'toplam' => count($Files),
                'basarili' => $BasariliSayisi,
                'hata' => $HataSayisi,
            ],
            'sonuclar' => $Sonuclar,
        ]);
    }
    
    



    public static function checkColumns(): void
    {
        
        $AuthUser = env('MIGRATION_BASIC_USER', 'admin');
        $AuthPass = env('MIGRATION_BASIC_PASS', 'Super123!');
        
        $GivenUser = $_SERVER['PHP_AUTH_USER'] ?? '';
        $GivenPass = $_SERVER['PHP_AUTH_PW'] ?? '';
        
        if ($GivenUser !== $AuthUser || $GivenPass !== $AuthPass) {
            header('WWW-Authenticate: Basic realm="Installation"');
            Response::json(['error' => 'Yetkisiz erisim'], 401);
            return;
        }
        
        $TabloAdi = $_GET['tablo'] ?? 'tbl_musteri';
        
        try {
            $Db = Database::connection();
            
            
            $Sql = "
                SELECT 
                    c.name AS KolonAdi,
                    t.name AS VeriTipi,
                    c.max_length AS MaxUzunluk,
                    c.is_nullable AS NullOlabilir,
                    c.is_identity AS Identity
                FROM sys.columns c
                INNER JOIN sys.types t ON c.user_type_id = t.user_type_id
                WHERE c.object_id = OBJECT_ID(:tablo)
                ORDER BY c.column_id
            ";
            
            $Stmt = $Db->prepare($Sql);
            $Stmt->execute(['tablo' => $TabloAdi]);
            $Kolonlar = $Stmt->fetchAll();
            
            Response::json([
                'tablo' => $TabloAdi,
                'kolon_sayisi' => count($Kolonlar),
                'kolonlar' => $Kolonlar,
            ]);
            
        } catch (\Throwable $e) {
            Response::json([
                'error' => 'Sorgu calistirilamadi',
                'detay' => $e->getMessage()
            ], 500);
        }
    }
}
