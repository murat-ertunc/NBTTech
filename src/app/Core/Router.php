<?php

namespace App\Core;

class Router
{
    private array $Rotalar = [];
    
    /**
     * Debug modu - gecici olarak true yapilabilir
     * @var bool
     */
    private bool $DebugMode = false;

    public function add(string $Metod, string $Desen, callable $Isleyici): void
    {
        $this->Rotalar[] = [
            'Metod' => strtoupper($Metod),
            'Desen' => $Desen,                    // Orijinal desen (debug icin)
            'DesenDerli' => $this->derle($Desen), // Derlenmiş regex
            'Isleyici' => $Isleyici,
        ];
    }

    public function dispatch(string $Metod, string $Yol): void
    {
        // Sadece GET ve POST desteklenir (IIS PUT/DELETE engelliyor)
        $GercekMetod = strtoupper($Metod);
        
        if ($this->DebugMode) {
            error_log("[ROUTER-DEBUG] dispatch: Metod={$GercekMetod} Yol={$Yol}");
            error_log("[ROUTER-DEBUG] Toplam rota sayisi: " . count($this->Rotalar));
        }
        
        // =====================================================================
        // İKİ AŞAMALI DISPATCH: Statik rotalar önce, parametreli rotalar sonra
        // Bu sayede /customer/new her zaman /customer/{id}'den önce eşleşir
        // =====================================================================
        
        // AŞAMA 1: Statik rotaları kontrol et (parametre içermeyen)
        foreach ($this->Rotalar as $Index => $Rota) {
            if ($Rota['Metod'] !== $GercekMetod) {
                continue;
            }
            
            // Sadece statik rotaları kontrol et (orijinal desende { } yoksa)
            if (strpos($Rota['Desen'], '{') !== false) {
                continue;
            }
            
            if (preg_match($Rota['DesenDerli'], $Yol, $Eslesmeler)) {
                if ($this->DebugMode) {
                    error_log("[ROUTER-DEBUG] ESLESME (STATIK): Index={$Index} Desen={$Rota['Desen']} DesenDerli={$Rota['DesenDerli']}");
                }
                $Parametreler = array_filter($Eslesmeler, '\is_string', ARRAY_FILTER_USE_KEY);
                call_user_func($Rota['Isleyici'], $Parametreler);
                return;
            }
        }
        
        // AŞAMA 2: Parametreli rotaları kontrol et
        foreach ($this->Rotalar as $Index => $Rota) {
            if ($Rota['Metod'] !== $GercekMetod) {
                continue;
            }
            
            // Sadece parametreli rotaları kontrol et
            if (strpos($Rota['Desen'], '{') === false) {
                continue;
            }
            
            if (preg_match($Rota['DesenDerli'], $Yol, $Eslesmeler)) {
                if ($this->DebugMode) {
                    error_log("[ROUTER-DEBUG] ESLESME (PARAMETRELI): Index={$Index} Desen={$Rota['Desen']} DesenDerli={$Rota['DesenDerli']}");
                    error_log("[ROUTER-DEBUG] Eslesmeler: " . json_encode($Eslesmeler));
                }
                $Parametreler = array_filter($Eslesmeler, '\is_string', ARRAY_FILTER_USE_KEY);
                call_user_func($Rota['Isleyici'], $Parametreler);
                return;
            }
        }
        
        if ($this->DebugMode) {
            error_log("[ROUTER-DEBUG] HIC ESLESME YOK: Yol={$Yol}");
        }

        // API istekleri icin JSON, web sayfalari icin HTML 404
        if (strpos($Yol, '/api/') === 0) {
            Response::json(['error' => 'Not Found'], 404);
        } else {
            http_response_code(404);
            require PUBLIC_PATH . '404.php';
        }
    }

    /**
     * Route desenini regex'e derle
     * 
     * Parametre adı "id" veya "Id"/"_id" ile bitiyorsa sadece sayısal değer kabul edilir.
     * Diğer parametreler herhangi bir değer kabul eder.
     * 
     * @param string $Desen
     * @return string
     */
    private function derle(string $Desen): string
    {
        $Kacisli = preg_replace('#\/#', '\\/', $Desen);
        
        // Parametre adına göre regex belirle:
        // - id, Id, _id ile biten parametreler: sadece sayı ([0-9]+)
        // - Diğerleri: herhangi bir karakter ([^/]+)
        $ParametreliDesen = preg_replace_callback(
            '#\{([a-zA-Z0-9_]+)\}#',
            function ($Match) {
                $ParamAdi = $Match[1];
                // id, customerId, customer_id gibi ID parametreleri sadece sayı kabul etsin
                if ($ParamAdi === 'id' || 
                    substr($ParamAdi, -2) === 'Id' || 
                    substr($ParamAdi, -3) === '_id') {
                    return '(?P<' . $ParamAdi . '>[0-9]+)';
                }
                return '(?P<' . $ParamAdi . '>[^/]+)';
            },
            $Kacisli
        );
        
        return '#^' . $ParametreliDesen . '$#';
    }
    
    /**
     * Debug modunu aç/kapat
     * 
     * @param bool $Enable
     * @return void
     */
    public function setDebugMode(bool $Enable): void
    {
        $this->DebugMode = $Enable;
    }
    
    /**
     * Kayıtlı rotaları listele (test/debug için)
     * 
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->Rotalar;
    }
}
