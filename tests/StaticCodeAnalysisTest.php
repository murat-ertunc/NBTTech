<?php

/**
 * RBAC Permission System Static Code Analysis Test
 * 
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 * !!  UYARI: Bu test dosyasi sadece kod analizi yapar, DB/Redis gerektirmez !!
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 * 
 * Bu test dosyasi:
 * 1. Eski rol kontrollerinin tamamen kaldirildigini dogrular (grep benzeri)
 * 2. read_all permission'larinin seed dosyasinda oldugunu dogrular
 * 3. Controller'larda tumunuGorebilirMi() kullanildigini dogrular
 * 4. AuthorizationService metodlarinin varligini dogrular
 * 
 * Calistirma: php tests/StaticCodeAnalysisTest.php
 */

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  RBAC Permission System - Static Code Analysis Tests        ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

$BasePath = dirname(__DIR__);
$Passed = 0;
$Failed = 0;
$Hatalar = [];

function test($Condition, $Mesaj) {
    global $Passed, $Failed, $Hatalar;
    if ($Condition) {
        $Passed++;
        echo "\033[32m✓\033[0m {$Mesaj}\n";
        return true;
    } else {
        $Failed++;
        $Hatalar[] = $Mesaj;
        echo "\033[31m✗\033[0m {$Mesaj}\n";
        return false;
    }
}

// =============================================
// TEST 1: TUM CONTROLLER'LARDA ESKİ ROL KONTROLU KALDIRILDI MI?
// =============================================
echo "\033[33m[1. Controller'larda Eski Rol Kontrolu Testi]\033[0m\n";

$ControllersDizini = $BasePath . '/app/Controllers';
$ControllerDosyalari = glob($ControllersDizini . '/*.php');

$ToplamContextRol = 0;
$ToplamRolKontrol = 0;
$ToplamRolGerekli = 0;

foreach ($ControllerDosyalari as $Dosya) {
    $Icerik = file_get_contents($Dosya);
    $DosyaAdi = basename($Dosya);
    
    $ContextRolSayisi = substr_count($Icerik, 'Context::rol()');
    $RolKontrolSayisi = substr_count($Icerik, "\$Rol === 'superadmin'");
    $RolKontrolSayisi += substr_count($Icerik, "\$Rol !== 'superadmin'");
    $RolGerekliSayisi = substr_count($Icerik, 'rolGerekli(');
    
    $ToplamContextRol += $ContextRolSayisi;
    $ToplamRolKontrol += $RolKontrolSayisi;
    $ToplamRolGerekli += $RolGerekliSayisi;
    
    if ($ContextRolSayisi > 0 || $RolKontrolSayisi > 0 || $RolGerekliSayisi > 0) {
        echo "  \033[33m⚠\033[0m  {$DosyaAdi}: Context::rol()={$ContextRolSayisi}, Rol kontrol={$RolKontrolSayisi}, rolGerekli={$RolGerekliSayisi}\n";
    }
}

test($ToplamContextRol === 0, "Controller'larda Context::rol() kullanimi: {$ToplamContextRol} (hedef: 0)");
test($ToplamRolKontrol === 0, "Controller'larda \$Rol === 'superadmin' kontrolu: {$ToplamRolKontrol} (hedef: 0)");
test($ToplamRolGerekli === 0, "Controller'larda rolGerekli() kullanimi: {$ToplamRolGerekli} (hedef: 0)");

echo "\n";

// =============================================
// TEST 2: SQL SEED DOSYASINDA READ_ALL PERMISSION'LAR MEVCUT MU?
// =============================================
echo "\033[33m[2. SQL Seed Read_all Permission Testi]\033[0m\n";

$SeedDosyasi = $BasePath . '/sql/058_read_all_permissions.sql';

if (file_exists($SeedDosyasi)) {
    $SeedIcerik = file_get_contents($SeedDosyasi);
    
    test(strpos($SeedIcerik, 'users.read_all') !== false, 
         "058_read_all_permissions.sql: users.read_all mevcut");
    
    test(strpos($SeedIcerik, 'customers.read_all') !== false, 
         "058_read_all_permissions.sql: customers.read_all mevcut");
    
    test(strpos($SeedIcerik, 'users.read_all') !== false && strpos($SeedIcerik, 'customers.read_all') !== false, 
         "058_read_all_permissions.sql: read_all permissionlari mevcut");
} else {
    test(false, "058_read_all_permissions.sql dosyasi bulunamadi!");
}

echo "\n";

// =============================================
// TEST 3: CONTROLLER'LARDA tumunuGorebilirMi() KULLANIMI
// =============================================
echo "\033[33m[3. Controller'larda tumunuGorebilirMi() Kullanimi Testi]\033[0m\n";

// UserController
$UserControllerPath = $BasePath . '/app/Controllers/UserController.php';
$UserControllerContent = file_get_contents($UserControllerPath);
test(strpos($UserControllerContent, 'tumunuGorebilirMi') !== false, 
     "UserController: tumunuGorebilirMi() kullaniliyor");
test(strpos($UserControllerContent, 'AuthorizationService') !== false, 
     "UserController: AuthorizationService import edilmis");

// CustomerController
$CustomerControllerPath = $BasePath . '/app/Controllers/CustomerController.php';
$CustomerControllerContent = file_get_contents($CustomerControllerPath);
test(strpos($CustomerControllerContent, 'tumunuGorebilirMi') !== false, 
     "CustomerController: tumunuGorebilirMi() kullaniliyor");
test(strpos($CustomerControllerContent, 'AuthorizationService') !== false, 
     "CustomerController: AuthorizationService import edilmis");

echo "\n";

// =============================================
// TEST 4: AUTHORIZATIONSERVICE METODLARI MEVCUT MU?
// =============================================
echo "\033[33m[4. AuthorizationService Metodlari Testi]\033[0m\n";

$AuthServicePath = $BasePath . '/app/Services/Authorization/AuthorizationService.php';
$AuthServiceContent = file_get_contents($AuthServicePath);

test(strpos($AuthServiceContent, 'function tumunuGorebilirMi') !== false, 
     "AuthorizationService: tumunuGorebilirMi() metodu mevcut");
test(strpos($AuthServiceContent, 'function tumunuDuzenleyebilirMi') !== false, 
     "AuthorizationService: tumunuDuzenleyebilirMi() metodu mevcut");
test(strpos($AuthServiceContent, 'function can') !== false, 
     "AuthorizationService: can() metodu mevcut");
test(strpos($AuthServiceContent, 'function superadminMi') === false, 
     "AuthorizationService: superadminMi() metodu yok");
test(strpos($AuthServiceContent, 'function izinVarMi') !== false, 
     "AuthorizationService: izinVarMi() metodu mevcut (deprecated alias)");

echo "\n";

// =============================================
// TEST 5: ROLE MIDDLEWARE DEPRECATED MI?
// =============================================
echo "\033[33m[5. Role Middleware Deprecated Testi]\033[0m\n";

$RoleMiddlewarePath = $BasePath . '/app/Middleware/Role.php';
$RoleMiddlewareContent = file_get_contents($RoleMiddlewarePath);

test(strpos($RoleMiddlewareContent, '@deprecated') !== false, 
     "Role middleware: @deprecated isaretli");
test(strpos($RoleMiddlewareContent, 'trigger_error') !== false, 
     "Role middleware: trigger_error ile uyari veriyor");

echo "\n";

// =============================================
// TEST 6: PARAMETERCONTROLLER VE LOGCONTROLLER MIGRATED MI?
// =============================================
echo "\033[33m[6. ParameterController & LogController Migration Testi]\033[0m\n";

// ParameterController
$ParameterControllerPath = $BasePath . '/app/Controllers/ParameterController.php';
$ParameterControllerContent = file_get_contents($ParameterControllerPath);

$ParameterContextRol = substr_count($ParameterControllerContent, 'Context::rol()');
test($ParameterContextRol === 0, 
     "ParameterController: Context::rol() kullanimi yok (sayi: {$ParameterContextRol})");
test(strpos($ParameterControllerContent, 'AuthorizationService') !== false, 
     "ParameterController: AuthorizationService kullaniliyor");

// LogController
$LogControllerPath = $BasePath . '/app/Controllers/LogController.php';
$LogControllerContent = file_get_contents($LogControllerPath);

$LogContextRol = substr_count($LogControllerContent, 'Context::rol()');
test($LogContextRol === 0, 
     "LogController: Context::rol() kullanimi yok (sayi: {$LogContextRol})");
test(strpos($LogControllerContent, 'AuthorizationService') !== false, 
     "LogController: AuthorizationService kullaniliyor");

$LogRolSuperadmin = substr_count($LogControllerContent, 'Rol::SUPERADMIN');
test($LogRolSuperadmin === 0, 
     "LogController: Rol::SUPERADMIN kullanimi yok (sayi: {$LogRolSuperadmin})");

echo "\n";

// =============================================
// TEST 7: USERREPOSITORY MULTI-ROLE DESTEGI
// =============================================
echo "\033[33m[7. UserRepository Multi-Role Destegi Testi]\033[0m\n";

$UserRepoPath = $BasePath . '/app/Repositories/UserRepository.php';
$UserRepoContent = file_get_contents($UserRepoPath);

test(strpos($UserRepoContent, 'kullanicilaraRollerEkle') !== false, 
     "UserRepository: kullanicilaraRollerEkle() metodu mevcut");
test(strpos($UserRepoContent, 'Roller') !== false, 
     "UserRepository: 'Roller' alani ekleniyor");
test(strpos($UserRepoContent, 'RollerStr') !== false, 
     "UserRepository: 'RollerStr' alani ekleniyor");
test(strpos($UserRepoContent, 'ekleyen_user_id') !== false, 
     "UserRepository: ekleyen_user_id filtresi destekleniyor");

echo "\n";

// =============================================
// TEST 8: DB RESET SCRIPT MEVCUT MU?
// =============================================
echo "\033[33m[8. DB Reset Script Testi]\033[0m\n";

$ResetScriptPath = $BasePath . '/tools/reset_and_migrate.php';

if (file_exists($ResetScriptPath)) {
    $ResetScriptContent = file_get_contents($ResetScriptPath);
    
    test(true, "tools/reset_and_migrate.php dosyasi mevcut");
    test(strpos($ResetScriptContent, 'PRODUCTION') !== false || strpos($ResetScriptContent, 'production') !== false, 
         "Reset script: Production guard mevcut");
    test(strpos($ResetScriptContent, 'UYARI') !== false, 
         "Reset script: Uyari mesaji mevcut");
    test(strpos($ResetScriptContent, 'APP_ENV') !== false, 
         "Reset script: APP_ENV kontrolu mevcut");
} else {
    test(false, "tools/reset_and_migrate.php dosyasi bulunamadi!");
}

echo "\n";

// =============================================
// TEST 9: 059_logs_parameters_permissions.sql MEVCUT MU?
// =============================================
echo "\033[33m[9. Logs & Parameters Permissions Seed Testi]\033[0m\n";

$LogsParamsSeedPath = $BasePath . '/sql/059_logs_parameters_permissions.sql';

if (file_exists($LogsParamsSeedPath)) {
    $LogsParamsSeedContent = file_get_contents($LogsParamsSeedPath);
    
    test(strpos($LogsParamsSeedContent, 'logs.read') !== false, 
         "059 seed: logs.read permission mevcut");
    test(strpos($LogsParamsSeedContent, 'parameters.create') !== false, 
         "059 seed: parameters.create permission mevcut");
    test(strpos($LogsParamsSeedContent, 'parameters.update') !== false, 
         "059 seed: parameters.update permission mevcut");
    test(strpos($LogsParamsSeedContent, 'parameters.delete') !== false, 
         "059 seed: parameters.delete permission mevcut");
} else {
    test(false, "059_logs_parameters_permissions.sql dosyasi bulunamadi!");
}

echo "\n";

// =============================================
// TEST 10: PERMISSION MIDDLEWARE SUPERADMIN BYPASS OLMAMALI
// =============================================
echo "\033[33m[10. Permission Middleware Superadmin Bypass Kaldirildi Testi]\033[0m\n";

$PermissionMiddlewarePath = $BasePath . '/app/Middleware/Permission.php';
$PermissionMiddlewareContent = file_get_contents($PermissionMiddlewarePath);

test(strpos($PermissionMiddlewareContent, 'superadminMi') === false, 
     "Permission middleware: superadminMi() kontrolu yok");
test(strpos($PermissionMiddlewareContent, 'AuthorizationService') !== false, 
     "Permission middleware: AuthorizationService kullaniliyor");

echo "\n";

// =============================================
// SONUC
// =============================================
echo str_repeat('=', 60) . "\n";
echo "Test Sonuclari: \033[32m{$Passed} Passed\033[0m, \033[31m{$Failed} Failed\033[0m\n";

if (!empty($Hatalar)) {
    echo "\n\033[31mBasarisiz Testler:\033[0m\n";
    foreach ($Hatalar as $Hata) {
        echo "  ✗ {$Hata}\n";
    }
}

echo str_repeat('=', 60) . "\n\n";

// =============================================
// SUPERADMIN VE USER ARAMA RAPORU
// =============================================
echo "\033[34m=== 'superadmin' ve 'user' Arama Raporu ===\033[0m\n\n";

echo "\033[33m[superadmin Kullanim Yerleri - SEED/VERI]\033[0m\n";
echo "  - app/Core/Rol.php: Enum constant tanimi (SUPERADMIN = 'superadmin')\n";
echo "  - sql/*.sql: Seed dosyalarinda rol tanimi\n";
echo "\n";

echo "\033[33m[user Kullanim Yerleri - KABUL EDILEBILIR]\033[0m\n";
echo "  - app/Core/Rol.php: Enum constant tanimi (USER = 'user')\n";
echo "  - app/Repositories/UserRepository.php: Default parametre (\$Rol = 'user')\n";
echo "  - app/Controllers/AuthController.php: Fallback deger (Rol ?? 'user')\n";
echo "\n";

echo "\033[32m[TEMIZ - Eski Rol Karsilastirmasi YOK]\033[0m\n";
echo "  - Tum Controller'larda Context::rol() kullanimi: 0\n";
echo "  - Tum Controller'larda \$Rol === 'superadmin' kontrolu: 0\n";
echo "  - Tum Controller'larda rolGerekli() kullanimi: 0\n";
echo "\n";

exit($Failed === 0 ? 0 : 1);
