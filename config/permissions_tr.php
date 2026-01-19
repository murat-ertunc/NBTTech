<?php
/**
 * Permission Turkce Cevirileri
 * 
 * Format: 'permission.code' => 'Turkce Aciklama'
 * Modul bazinda gruplandirma mevcuttur.
 * 
 * @package Config
 */
return [
    // =========================================================================
    // MODUL ISIMLERI
    // =========================================================================
    'moduller' => [
        'users' => 'Kullanıcılar',
        'roles' => 'Roller',
        'customers' => 'Müşteriler',
        'invoices' => 'Faturalar',
        'payments' => 'Ödemeler',
        'projects' => 'Projeler',
        'offers' => 'Teklifler',
        'contracts' => 'Sözleşmeler',
        'guarantees' => 'Teminatlar',
        'meetings' => 'Görüşmeler',
        'contacts' => 'Kişiler',
        'calendar' => 'Takvim',
        'files' => 'Dosyalar',
        'stamp_taxes' => 'Damga Vergisi',
        'parameters' => 'Tanımlamalar',
        'logs' => 'İşlem Kayıtları',
        'dashboard' => 'Ana Sayfa',
        'alarms' => 'Hatırlatmalar',
    ],
    
    // =========================================================================
    // AKSIYON ISIMLERI
    // =========================================================================
    'aksiyonlar' => [
        'create' => 'Oluşturma',
        'read' => 'Görüntüleme',
        'update' => 'Düzenleme',
        'delete' => 'Silme',
        'export' => 'Dışa Aktarma',
        'import' => 'İçe Aktarma',
        'assign' => 'Atama',
    ],
    
    // =========================================================================
    // PERMISSION CEVIRILERI (module.action => Turkce)
    // =========================================================================
    'permissionlar' => [
        // Kullanicilar
        'users.create' => 'Kullanıcı Oluşturma',
        'users.read' => 'Kullanıcı Görüntüleme',
        'users.update' => 'Kullanıcı Düzenleme',
        'users.delete' => 'Kullanıcı Silme',
        
        // Roller
        'roles.create' => 'Rol Oluşturma',
        'roles.read' => 'Rol Görüntüleme',
        'roles.update' => 'Rol Düzenleme',
        'roles.delete' => 'Rol Silme',
        
        // Musteriler
        'customers.create' => 'Müşteri Oluşturma',
        'customers.read' => 'Müşteri Görüntüleme',
        'customers.update' => 'Müşteri Düzenleme',
        'customers.delete' => 'Müşteri Silme',
        
        // Faturalar
        'invoices.create' => 'Fatura Oluşturma',
        'invoices.read' => 'Fatura Görüntüleme',
        'invoices.update' => 'Fatura Düzenleme',
        'invoices.delete' => 'Fatura Silme',
        
        // Odemeler
        'payments.create' => 'Ödeme Oluşturma',
        'payments.read' => 'Ödeme Görüntüleme',
        'payments.update' => 'Ödeme Düzenleme',
        'payments.delete' => 'Ödeme Silme',
        
        // Projeler
        'projects.create' => 'Proje Oluşturma',
        'projects.read' => 'Proje Görüntüleme',
        'projects.update' => 'Proje Düzenleme',
        'projects.delete' => 'Proje Silme',
        
        // Teklifler
        'offers.create' => 'Teklif Oluşturma',
        'offers.read' => 'Teklif Görüntüleme',
        'offers.update' => 'Teklif Düzenleme',
        'offers.delete' => 'Teklif Silme',
        
        // Sozlesmeler
        'contracts.create' => 'Sözleşme Oluşturma',
        'contracts.read' => 'Sözleşme Görüntüleme',
        'contracts.update' => 'Sözleşme Düzenleme',
        'contracts.delete' => 'Sözleşme Silme',
        
        // Teminatlar
        'guarantees.create' => 'Teminat Oluşturma',
        'guarantees.read' => 'Teminat Görüntüleme',
        'guarantees.update' => 'Teminat Düzenleme',
        'guarantees.delete' => 'Teminat Silme',
        
        // Gorusmeler
        'meetings.create' => 'Görüşme Oluşturma',
        'meetings.read' => 'Görüşme Görüntüleme',
        'meetings.update' => 'Görüşme Düzenleme',
        'meetings.delete' => 'Görüşme Silme',
        
        // Kisiler
        'contacts.create' => 'Kişi Oluşturma',
        'contacts.read' => 'Kişi Görüntüleme',
        'contacts.update' => 'Kişi Düzenleme',
        'contacts.delete' => 'Kişi Silme',
        
        // Takvim
        'calendar.create' => 'Etkinlik Oluşturma',
        'calendar.read' => 'Takvim Görüntüleme',
        'calendar.update' => 'Etkinlik Düzenleme',
        'calendar.delete' => 'Etkinlik Silme',
        
        // Dosyalar
        'files.create' => 'Dosya Yükleme',
        'files.read' => 'Dosya Görüntüleme',
        'files.update' => 'Dosya Düzenleme',
        'files.delete' => 'Dosya Silme',
        
        // Damga Vergisi
        'stamp_taxes.create' => 'Damga Vergisi Oluşturma',
        'stamp_taxes.read' => 'Damga Vergisi Görüntüleme',
        'stamp_taxes.update' => 'Damga Vergisi Düzenleme',
        'stamp_taxes.delete' => 'Damga Vergisi Silme',
        
        // Tanimlamalar
        'parameters.create' => 'Parametre Oluşturma',
        'parameters.read' => 'Parametre Görüntüleme',
        'parameters.update' => 'Parametre Düzenleme',
        'parameters.delete' => 'Parametre Silme',
        
        // Loglar
        'logs.read' => 'Log Görüntüleme',
        
        // Dashboard
        'dashboard.read' => 'Ana Sayfa Görüntüleme',
        
        // Hatirlatmalar
        'alarms.read' => 'Hatırlatma Görüntüleme',
    ],
];
