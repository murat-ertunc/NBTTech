<?php

namespace App\Core;

class Router
{
    private array $Rotalar = [];
    
    



    private bool $DebugMode = false;

    public function add(string $Metod, string $Desen, callable $Isleyici): void
    {
        $this->Rotalar[] = [
            'Metod' => strtoupper($Metod),
            'Desen' => $Desen,                    
            'DesenDerli' => $this->derle($Desen), 
            'Isleyici' => $Isleyici,
        ];
    }

    public function dispatch(string $Metod, string $Yol): void
    {
        
        $GercekMetod = strtoupper($Metod);
        
        if ($this->DebugMode) {
            error_log("[ROUTER-DEBUG] dispatch: Metod={$GercekMetod} Yol={$Yol}");
            error_log("[ROUTER-DEBUG] Toplam rota sayisi: " . count($this->Rotalar));
        }
        
        
        
        
        
        
        
        foreach ($this->Rotalar as $Index => $Rota) {
            if ($Rota['Metod'] !== $GercekMetod) {
                continue;
            }
            
            
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
        
        
        foreach ($this->Rotalar as $Index => $Rota) {
            if ($Rota['Metod'] !== $GercekMetod) {
                continue;
            }
            
            
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

        
        if (strpos($Yol, '/api/') === 0) {
            Response::json(['error' => 'Not Found'], 404);
        } else {
            http_response_code(404);
            require PUBLIC_PATH . '404.php';
        }
    }

    








    private function derle(string $Desen): string
    {
        $Kacisli = preg_replace('#\/#', '\\/', $Desen);
        
        
        
        
        $ParametreliDesen = preg_replace_callback(
            '#\{([a-zA-Z0-9_]+)\}#',
            function ($Match) {
                $ParamAdi = $Match[1];
                
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
    
    





    public function setDebugMode(bool $Enable): void
    {
        $this->DebugMode = $Enable;
    }
    
    




    public function getRoutes(): array
    {
        return $this->Rotalar;
    }
}
