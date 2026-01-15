<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Repositories\InvoiceRepository;

class DashboardController
{
    public static function index(): void
    {
        if (!Context::kullaniciId()) {
            Response::error('Yetkisiz erisim.', 401);
            return;
        }

        $InvRepo = new InvoiceRepository();
        
        // Alarms: Unpaid Invoices
        // Gercek senaryoda Musteriye ozel veya yetkiye gore filtreleme yapilabilir.
        // Simdilik tum sistemdeki odenmemis faturalari getiriyoruz.
        $Unpaid = $InvRepo->odenmemisFaturalar();
        
        $Alarms = [];
        $Calendar = [];

        foreach ($Unpaid as $Inv) {
            $Kalan = (float)$Inv['Tutar'] - (float)$Inv['OdenenTutar'];
            $Gecikmis = strtotime($Inv['Tarih']) < time();
            
            $Alarms[] = [
                'EntityId' => $Inv['Id'],
                'MusteriId' => $Inv['MusteriId'],
                'RecordId' => $Inv['Id'],
                'Type' => 'invoice',
                'Title' => 'Odenmemis Fatura',
                'Detail' => 'Ref: #' . $Inv['Id'] . ' - Kalan: ' . number_format($Kalan, 2) . ' ' . $Inv['DovizCinsi'],
                'Date' => $Inv['Tarih'],
                'IsUrgent' => $Gecikmis
            ];

            $Calendar[] = [
                'id' => $Inv['Id'],
                'MusteriId' => $Inv['MusteriId'],
                'RecordId' => $Inv['Id'],
                'title' => 'Fatura #' . $Inv['Id'] . ' (' . number_format($Kalan, 0) . ')',
                'start' => $Inv['Tarih'],
                'backgroundColor' => $Gecikmis ? '#dc3545' : '#ffc107',
                'borderColor' => $Gecikmis ? '#dc3545' : '#ffc107',
            ];
        }

        Response::json([
            'alarms' => $Alarms,
            'calendar' => $Calendar
        ]);
    }
}
