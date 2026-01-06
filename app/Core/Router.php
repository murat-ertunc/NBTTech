<?php

namespace App\Core;

class Router
{
    private array $Rotalar = [];

    public function add(string $Metod, string $Desen, callable $Isleyici): void
    {
        $this->Rotalar[] = [
            'Metod' => strtoupper($Metod),
            'Desen' => $this->derle($Desen),
            'Isleyici' => $Isleyici,
        ];
    }

    public function dispatch(string $Metod, string $Yol): void
    {
        foreach ($this->Rotalar as $Rota) {
            if ($Rota['Metod'] !== strtoupper($Metod)) {
                continue;
            }
            if (preg_match($Rota['Desen'], $Yol, $Eslesmeler)) {
                $Parametreler = array_filter($Eslesmeler, '\is_string', ARRAY_FILTER_USE_KEY);
                call_user_func($Rota['Isleyici'], $Parametreler);
                return;
            }
        }

        Response::json(['error' => 'Not Found'], 404);
    }

    private function derle(string $Desen): string
    {
        $Kacisli = preg_replace('#\/#', '\\/', $Desen);
        $ParametreliDesen = preg_replace('#\{([a-zA-Z0-9_]+)\}#', '(?P<$1>[^/]+)', $Kacisli);
        return '#^' . $ParametreliDesen . '$#';
    }
}
