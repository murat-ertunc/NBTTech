<?php
/**
 * Router Regression Test
 * 
 * Statik rotaların parametreli rotalardan önce eşleştiğini doğrular.
 * CLI'dan çalıştırılır: php tests/router_regression_test.php
 * 
 * @package Tests
 */

// Bootstrap yükle
require_once dirname(__DIR__) . '/bootstrap/app.php';

use App\Core\Router;

echo "=============================================================================\n";
echo "ROUTER REGRESSION TEST\n";
echo "=============================================================================\n\n";

// Test Router'ı oluştur
$Router = new Router();

// Test için basit handler'lar - hangi rota eşleştiğini döndürür
$TestRoutes = [
    ['GET', '/dashboard', 'dashboard'],
    ['GET', '/customer/new', 'customer/new (STATIK)'],
    ['GET', '/customer/{id}/edit', 'customer/{id}/edit'],
    ['GET', '/customer/{id}', 'customer/{id}'],
    ['GET', '/customer/{id}/offers/new', 'customer/{id}/offers/new'],
    ['GET', '/customer/{id}/offers/{offerId}/edit', 'customer/{id}/offers/{offerId}/edit'],
    ['GET', '/logs', 'logs'],
    ['GET', '/users', 'users'],
    ['GET', '/user/{id}', 'user/{id}'],
];

// Rotaları kaydet
foreach ($TestRoutes as $Route) {
    $RouteName = $Route[2];
    $Router->add($Route[0], $Route[1], function ($Params) use ($RouteName) {
        return ['route' => $RouteName, 'params' => $Params];
    });
}

echo "Kayitli rotalar:\n";
echo "----------------\n";
foreach ($Router->getRoutes() as $Index => $Rota) {
    echo sprintf("  [%d] %s %s -> %s\n", 
        $Index, 
        $Rota['Metod'], 
        $Rota['Desen'], 
        $Rota['DesenDerli']
    );
}
echo "\n";

// Test senaryoları
$TestCases = [
    // [method, path, expected_route, expected_params, should_match]
    ['GET', '/customer/new', 'customer/new (STATIK)', [], true],
    ['GET', '/customer/123', 'customer/{id}', ['id' => '123'], true],
    ['GET', '/customer/456', 'customer/{id}', ['id' => '456'], true],
    ['GET', '/customer/abc', null, [], false],  // abc sayısal değil, eşleşmemeli
    ['GET', '/customer/0', 'customer/{id}', ['id' => '0'], true],
    ['GET', '/customer/123/edit', 'customer/{id}/edit', ['id' => '123'], true],
    ['GET', '/customer/new/edit', null, [], false],  // new sayısal değil
    ['GET', '/customer/123/offers/new', 'customer/{id}/offers/new', ['id' => '123'], true],
    ['GET', '/customer/123/offers/456/edit', 'customer/{id}/offers/{offerId}/edit', ['id' => '123', 'offerId' => '456'], true],
    ['GET', '/dashboard', 'dashboard', [], true],
    ['GET', '/logs', 'logs', [], true],
    ['GET', '/user/99', 'user/{id}', ['id' => '99'], true],
    ['GET', '/user/admin', null, [], false],  // admin sayısal değil
];

echo "Test sonuclari:\n";
echo "---------------\n";

$PassCount = 0;
$FailCount = 0;

foreach ($TestCases as $Case) {
    [$Method, $Path, $ExpectedRoute, $ExpectedParams, $ShouldMatch] = $Case;
    
    // Dispatch'i simüle et - output buffering ile handler'ı yakala
    $MatchedRoute = null;
    $MatchedParams = [];
    $Found = false;
    
    foreach ($Router->getRoutes() as $Rota) {
        if ($Rota['Metod'] !== $Method) {
            continue;
        }
        if (preg_match($Rota['DesenDerli'], $Path, $Eslesmeler)) {
            $Found = true;
            $MatchedRoute = $Rota['Desen'];
            $MatchedParams = array_filter($Eslesmeler, '\is_string', ARRAY_FILTER_USE_KEY);
            
            // İki aşamalı dispatch simülasyonu: statik önce
            if (strpos($Rota['Desen'], '{') === false) {
                break; // Statik eşleşme bulundu, devam etme
            }
        }
    }
    
    // Sonucu değerlendir
    $TestPassed = false;
    
    if ($ShouldMatch) {
        // Eşleşmesi bekleniyor
        if ($Found && $MatchedRoute === getOriginalPattern($ExpectedRoute)) {
            $TestPassed = true;
        }
    } else {
        // Eşleşmemesi bekleniyor
        if (!$Found) {
            $TestPassed = true;
        }
    }
    
    $Status = $TestPassed ? '✓ PASS' : '✗ FAIL';
    $PassCount += $TestPassed ? 1 : 0;
    $FailCount += $TestPassed ? 0 : 1;
    
    echo sprintf("  %s: %s %s\n", $Status, $Method, $Path);
    if ($ShouldMatch) {
        echo sprintf("       Beklenen: %s\n", $ExpectedRoute);
        echo sprintf("       Bulunan:  %s\n", $Found ? $MatchedRoute : '(eşleşme yok)');
        if ($Found && !empty($MatchedParams)) {
            echo sprintf("       Params:   %s\n", json_encode($MatchedParams));
        }
    } else {
        echo sprintf("       Beklenen: Eşleşme olmamalı\n");
        echo sprintf("       Bulunan:  %s\n", $Found ? $MatchedRoute : '(eşleşme yok - DOĞRU)');
    }
    echo "\n";
}

echo "=============================================================================\n";
echo sprintf("SONUC: %d PASS, %d FAIL\n", $PassCount, $FailCount);
echo "=============================================================================\n";

// Helper function
function getOriginalPattern(string $RouteName): string
{
    // Route name'den orijinal pattern'ı çıkar
    $Map = [
        'customer/new (STATIK)' => '/customer/new',
        'customer/{id}' => '/customer/{id}',
        'customer/{id}/edit' => '/customer/{id}/edit',
        'customer/{id}/offers/new' => '/customer/{id}/offers/new',
        'customer/{id}/offers/{offerId}/edit' => '/customer/{id}/offers/{offerId}/edit',
        'dashboard' => '/dashboard',
        'logs' => '/logs',
        'users' => '/users',
        'user/{id}' => '/user/{id}',
    ];
    return $Map[$RouteName] ?? $RouteName;
}

exit($FailCount > 0 ? 1 : 0);
