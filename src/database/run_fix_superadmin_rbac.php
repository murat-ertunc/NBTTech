#!/usr/bin/env php
<?php

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

use App\Core\Database;

try {
    $Db = Database::connection();

    echo "🔧 Superadmin RBAC Düzeltme Script'i çalıştırılıyor...\n\n";

    $SqlFile = SRC_PATH . 'sql/062_fix_superadmin_rbac.sql';
    $SqlContent = file_get_contents($SqlFile);

    $Parts = preg_split('/^\s*GO\s*$/mi', $SqlContent);

    foreach ($Parts as $Part) {
        $Part = trim($Part);
        if (!empty($Part)) {
            $Db->exec($Part);
        }
    }

    echo "\n✅ Script başarıyla tamamlandı!\n\n";

} catch (\Exception $e) {
    echo "\n❌ HATA: " . $e->getMessage() . "\n\n";
    exit(1);
}
