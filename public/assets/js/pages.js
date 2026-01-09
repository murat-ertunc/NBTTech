/**
 * NbtProject - Sayfa Modülleri
 * =============================
 * Her modül için CRUD işlemleri ve sayfa yönetimi
 */

// =============================================
// GLOBAL STATE
// =============================================
const AppState = {
    customers: [],
    currentCustomer: null,
    currentCustomerTab: 'genel',
    alarms: [],
    calendarEvents: []
};

// =============================================
// DASHBOARD MODÜLÜ
// =============================================
const DashboardModule = {
    _eventsBound: false,
    
    async init() {
        await Promise.all([
            this.loadStats(),
            this.loadCustomers(),
            this.loadAlarms(),
            this.loadCalendar()
        ]);
        this.bindEvents();
    },

    async loadStats() {
        try {
            const data = await NbtApi.get('/api/dashboard');
            document.getElementById('statCustomers').textContent = data.customerCount || 0;
            document.getElementById('statProjects').textContent = data.projectCount || 0;
            document.getElementById('statPending').textContent = NbtUtils.formatMoney(data.pendingAmount || 0);
            document.getElementById('statCollected').textContent = NbtUtils.formatMoney(data.collectedAmount || 0);
        } catch (err) {
            console.error('Dashboard stats yüklenemedi:', err);
        }
    },

    async loadCustomers() {
        const container = document.getElementById('dashCustomerList');
        try {
            const response = await NbtApi.get('/api/customers');
            AppState.customers = response.data || [];
            this.renderCustomerList(AppState.customers);
        } catch (err) {
            container.innerHTML = `<div class="text-danger small p-3">${err.message}</div>`;
        }
    },

    renderCustomerList(customers) {
        const container = document.getElementById('dashCustomerList');
        if (!customers.length) {
            container.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="bi bi-people fs-2 d-block mb-2 opacity-50"></i>
                    <p class="mb-0">Henüz müşteri eklenmemiş</p>
                </div>`;
            return;
        }

        let html = '<div class="list-group list-group-flush">';
        customers.slice(0, 10).forEach(c => {
            html += `
                <a href="#customer/${c.Id}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2" 
                   data-customer-id="${c.Id}">
                    <div>
                        <div class="fw-semibold">${NbtUtils.escapeHtml(c.Unvan)}</div>
                        ${c.Aciklama ? `<small class="text-muted">${NbtUtils.escapeHtml(c.Aciklama).substring(0, 50)}</small>` : ''}
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>`;
        });
        html += '</div>';
        
        if (customers.length > 10) {
            html += `<div class="text-center py-2">
                <a href="#customers" class="small">Tümünü Gör (${customers.length})</a>
            </div>`;
        }
        
        container.innerHTML = html;
    },

    async loadAlarms() {
        const container = document.getElementById('dashAlarmList');
        try {
            const response = await NbtApi.get('/api/alarms');
            AppState.alarms = response.data || [];
            this.renderAlarms(AppState.alarms);
            document.getElementById('alarmCount').textContent = AppState.alarms.length;
        } catch (err) {
            // API henüz yoksa mock data
            this.renderMockAlarms();
        }
    },

    renderAlarms(alarms) {
        const container = document.getElementById('dashAlarmList');
        if (!alarms.length) {
            container.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="bi bi-bell-slash fs-2 d-block mb-2 opacity-50"></i>
                    <p class="mb-0">Aktif alarm yok</p>
                </div>`;
            return;
        }

        let html = '<div class="list-group list-group-flush">';
        alarms.forEach(alarm => {
            const badgeClass = alarm.type === 'invoice' ? 'bg-danger' : 
                             alarm.type === 'calendar' ? 'bg-warning' : 'bg-info';
            const icon = alarm.type === 'invoice' ? 'bi-receipt' : 
                        alarm.type === 'calendar' ? 'bi-calendar-event' : 'bi-bell';
            
            html += `
                <div class="list-group-item d-flex align-items-start gap-2 cursor-pointer" data-alarm-type="${alarm.type}" data-alarm-id="${alarm.id}" style="cursor:pointer;">
                    <span class="badge ${badgeClass} p-2">
                        <i class="bi ${icon}"></i>
                    </span>
                    <div class="flex-grow-1">
                        <div class="fw-semibold small">${NbtUtils.escapeHtml(alarm.title)}</div>
                        <small class="text-muted">${NbtUtils.escapeHtml(alarm.description)}</small>
                    </div>
                </div>`;
        });
        html += '</div>';
        container.innerHTML = html;
    },

    renderMockAlarms() {
        // Mock alarmlar (API hazır olana kadar)
        const mockAlarms = [
            { id: 1, type: 'invoice', title: 'Ödenmemiş Fatura', description: '3 adet fatura ödeme bekliyor' },
            { id: 2, type: 'calendar', title: 'Yaklaşan İş', description: 'Bu hafta 2 görev var' }
        ];
        this.renderAlarms(mockAlarms);
        document.getElementById('alarmCount').textContent = mockAlarms.length;
    },

    async loadCalendar() {
        const container = document.getElementById('dashCalendar');
        try {
            await NbtCalendar.loadEvents();
        } catch (err) {
            // Mock events
            NbtCalendar.events = [];
        }
        
        NbtCalendar.render(container, {
            events: NbtCalendar.events,
            onDayClick: (date, events) => {
                if (events.length) {
                    NbtToast.info(`${date}: ${events.length} etkinlik`);
                }
            }
        });
    },

    bindEvents() {
        // Prevent duplicate binding
        if (this._eventsBound) return;
        this._eventsBound = true;

        // Dashboard müşteri arama
        const searchInput = document.getElementById('dashCustomerSearch');
        if (searchInput) {
            searchInput.addEventListener('input', NbtUtils.debounce((e) => {
                const query = e.target.value.toLowerCase();
                const filtered = AppState.customers.filter(c => 
                    (c.Unvan || '').toLowerCase().includes(query)
                );
                this.renderCustomerList(filtered);
            }, 300));
        }

        // Yeni müşteri butonu
        document.querySelector('[data-action="add-customer"]')?.addEventListener('click', () => {
            CustomerModule.openModal();
        });

        // Alarm tıklama
        document.getElementById('dashAlarmList')?.addEventListener('click', (e) => {
            const item = e.target.closest('.list-group-item[data-alarm-type]');
            if (item) {
                const type = item.dataset.alarmType;
                if (type === 'invoice') {
                    window.location.hash = '#invoices?filter=unpaid';
                } else if (type === 'calendar') {
                    window.location.hash = '#calendar';
                }
            }
        });

        // Müşteri tıklama (dashboard'dan)
        document.getElementById('dashCustomerList')?.addEventListener('click', (e) => {
            e.preventDefault();
            const link = e.target.closest('[data-customer-id]');
            if (link) {
                const customerId = parseInt(link.dataset.customerId);
                window.location.hash = `#customer/${customerId}`;
            }
        });
    }
};

// =============================================
// MÜŞTERİ MODÜLÜ
// =============================================
const CustomerModule = {
    searchQuery: '',
    _eventsBound: false,
    
    async init() {
        await this.loadList();
        this.initToolbar();
        this.bindEvents();
    },

    async loadList() {
        const container = document.getElementById('customersTableContainer');
        try {
            const response = await NbtApi.get('/api/customers');
            AppState.customers = response.data || [];
            this.renderTable(AppState.customers);
        } catch (err) {
            container.innerHTML = `<div class="alert alert-danger m-3">${err.message}</div>`;
        }
    },

    initToolbar() {
        const toolbarContainer = document.getElementById('customersToolbar');
        toolbarContainer.innerHTML = NbtListToolbar.create({
            placeholder: 'Müşteri ara...',
            onFilter: true,
            onAdd: true
        });

        const panel = document.getElementById('view-customers').querySelector('.card');
        NbtListToolbar.bind(toolbarContainer, {
            onSearch: (query) => {
                this.searchQuery = query.toLowerCase();
                const filtered = AppState.customers.filter(c => 
                    (c.Unvan || '').toLowerCase().includes(this.searchQuery)
                );
                this.renderTable(filtered);
            },
            onFilter: () => {
                document.getElementById('customersFilterPanel').classList.toggle('open');
            },
            onAdd: () => this.openModal(),
            panelElement: panel
        });
    },

    renderTable(data) {
        const container = document.getElementById('customersTableContainer');
        const columns = [
            { field: 'Unvan', label: 'Müşteri Adı' },
            { field: 'Aciklama', label: 'Açıklama' },
            { field: 'EklemeZamani', label: 'Kayıt Tarihi', render: (v) => NbtUtils.formatDate(v) }
        ];

        container.innerHTML = NbtDataTable.create(columns, data, {
            actions: { view: true, edit: true, delete: true },
            emptyMessage: 'Müşteri bulunamadı'
        });

        NbtDataTable.bind(container, {
            onView: (id) => {
                window.location.hash = `#customer/${id}`;
            },
            onEdit: (id) => this.openModal(id),
            onDelete: async (id) => {
                if (!confirm('Bu kaydı silmek istediğinizden emin misiniz?')) return;
                try {
                    await NbtApi.delete(`/api/customers/${id}`);
                    NbtToast.success('Müşteri silindi');
                    await this.loadList();
                } catch (err) {
                    NbtToast.error(err.message);
                }
            }
        });
    },

    openModal(id = null) {
        NbtModal.resetForm('customerModal');
        const editId = id ? parseInt(id, 10) : null;
        document.getElementById('customerModalTitle').textContent = editId ? 'Müşteri Düzenle' : 'Yeni Müşteri';
        document.getElementById('customerId').value = editId || '';

        if (editId) {
            // parseInt ile karşılaştır
            const customer = AppState.customers.find(c => parseInt(c.Id, 10) === editId);
            if (customer) {
                document.getElementById('customerUnvan').value = customer.Unvan || '';
                document.getElementById('customerAciklama').value = customer.Aciklama || '';
            } else {
                // AppState'te yoksa API'den tekrar çekelim
                NbtApi.get('/api/customers').then(response => {
                    AppState.customers = response.data || [];
                    const found = AppState.customers.find(c => parseInt(c.Id, 10) === editId);
                    if (found) {
                        document.getElementById('customerUnvan').value = found.Unvan || '';
                        document.getElementById('customerAciklama').value = found.Aciklama || '';
                    }
                }).catch(() => {});
            }
        }

        NbtModal.open('customerModal');
    },

    async save() {
        const id = document.getElementById('customerId').value;
        const data = {
            Unvan: document.getElementById('customerUnvan').value.trim(),
            Aciklama: document.getElementById('customerAciklama').value.trim() || null
        };

        if (!data.Unvan) {
            NbtModal.showError('customerModal', 'Unvan zorunludur');
            return;
        }
        if (data.Unvan.length < 2) {
            NbtModal.showError('customerModal', 'Unvan en az 2 karakter olmalıdır');
            return;
        }

        try {
            if (id) {
                await NbtApi.put(`/api/customers/${id}`, data);
                NbtToast.success('Müşteri güncellendi');
            } else {
                await NbtApi.post('/api/customers', data);
                NbtToast.success('Müşteri eklendi');
            }
            NbtModal.close('customerModal');
            await this.loadList();
            // Dashboard'u da güncelle
            if (document.getElementById('view-dashboard').classList.contains('d-none') === false) {
                DashboardModule.loadCustomers();
            }
        } catch (err) {
            NbtModal.showError('customerModal', err.message);
        }
    },

    bindEvents() {
        // Prevent duplicate binding
        if (this._eventsBound) return;
        this._eventsBound = true;
        
        document.getElementById('btnSaveCustomer')?.addEventListener('click', () => this.save());
        
        // Filter buttons
        document.getElementById('applyCustomerFilter')?.addEventListener('click', () => {
            // Filtre uygula
            NbtToast.info('Filtre uygulandı');
        });
        document.getElementById('clearCustomerFilter')?.addEventListener('click', () => {
            document.getElementById('filterCustomerDate').value = '';
            this.renderTable(AppState.customers);
        });
    }
};

// =============================================
// MÜŞTERİ DETAY MODÜLÜ (12 TAB)
// =============================================
const CustomerDetailModule = {
    _eventsBound: false,
    customerId: null,
    activeTab: 'bilgi',
    filters: {},
    data: {
        customer: null,
        projects: [],
        invoices: [],
        payments: [],
        offers: [],
        contracts: [],
        guarantees: [],
        meetings: [],
        contacts: [],
        stampTaxes: [],
        files: []
    },

    // Tab konfigürasyonları
    tabConfig: {
        bilgi: { title: 'Müşteri Bilgisi', icon: 'bi-info-circle', endpoint: null },
        kisiler: { title: 'Kişiler', icon: 'bi-people', endpoint: '/api/contacts', key: 'contacts' },
        gorusme: { title: 'Görüşmeler', icon: 'bi-chat-dots', endpoint: '/api/meetings', key: 'meetings' },
        projeler: { title: 'Projeler', icon: 'bi-kanban', endpoint: '/api/projects', key: 'projects' },
        teklifler: { title: 'Teklifler', icon: 'bi-file-earmark-text', endpoint: '/api/offers', key: 'offers' },
        sozlesmeler: { title: 'Sözleşmeler', icon: 'bi-file-text', endpoint: '/api/contracts', key: 'contracts' },
        takvim: { title: 'Takvim', icon: 'bi-calendar3', endpoint: null },
        damgavergisi: { title: 'Damga Vergisi', icon: 'bi-percent', endpoint: '/api/stamp-taxes', key: 'stampTaxes' },
        teminatlar: { title: 'Teminatlar', icon: 'bi-shield-check', endpoint: '/api/guarantees', key: 'guarantees' },
        faturalar: { title: 'Faturalar', icon: 'bi-receipt', endpoint: '/api/invoices', key: 'invoices' },
        odemeler: { title: 'Ödemeler', icon: 'bi-cash-stack', endpoint: '/api/payments', key: 'payments' },
        dosyalar: { title: 'Dosyalar', icon: 'bi-folder', endpoint: '/api/files', key: 'files' }
    },

    async init(customerId, initialTab = null) {
        // Id'yi integer olarak normalize et
        this.customerId = parseInt(customerId, 10);
        if (isNaN(this.customerId) || this.customerId <= 0) {
            NbtToast.error('Geçersiz müşteri ID');
            window.location.hash = '#customers';
            return;
        }
        await this.loadCustomer();
        this.bindEvents();
        // URL'den gelen tab varsa onu aç, yoksa 'bilgi' aç
        const tabToOpen = initialTab && this.tabConfig[initialTab] ? initialTab : 'bilgi';
        this.switchTab(tabToOpen);
    },

    async loadCustomer() {
        try {
            // Önce AppState'ten dene, yoksa API'den çek
            let customers = AppState.customers;
            if (!customers || customers.length === 0) {
                const response = await NbtApi.get('/api/customers');
                customers = response.data || [];
                AppState.customers = customers;
            }
            
            // parseInt ile karşılaştır - API'den gelen Id int veya string olabilir
            this.data.customer = customers.find(c => parseInt(c.Id, 10) === this.customerId);
            
            if (!this.data.customer) {
                NbtToast.error('Müşteri bulunamadı');
                window.location.hash = '#customers';
                return;
            }

            document.getElementById('customerDetailTitle').textContent = this.data.customer.Unvan;
            document.getElementById('customerDetailCode').textContent = `MÜŞ-${String(this.customerId).padStart(5, '0')}`;
            AppState.currentCustomer = this.data.customer;

            // Temel verileri paralel yükle
            await Promise.all([
                this.loadRelatedData('projects', '/api/projects'),
                this.loadRelatedData('invoices', '/api/invoices'),
                this.loadRelatedData('payments', '/api/payments'),
                this.loadRelatedData('offers', '/api/offers'),
                this.loadRelatedData('contracts', '/api/contracts'),
                this.loadRelatedData('guarantees', '/api/guarantees'),
                this.loadRelatedData('meetings', '/api/meetings'),
                this.loadRelatedData('contacts', '/api/contacts'),
                this.loadRelatedData('stampTaxes', '/api/stamp-taxes'),
                this.loadRelatedData('files', '/api/files')
            ]);
        } catch (err) {
            NbtToast.error(err.message);
        }
    },

    async loadRelatedData(key, endpoint) {
        try {
            const response = await NbtApi.get(`${endpoint}?musteri_id=${this.customerId}`);
            this.data[key] = (response.data || []).filter(item => 
                parseInt(item.MusteriId, 10) === this.customerId
            );
        } catch (err) {
            this.data[key] = [];
        }
    },

    bindEvents() {
        // Prevent duplicate binding
        if (this._eventsBound) return;
        this._eventsBound = true;
        
        // Tab değiştirme - Bootstrap nav-tabs
        document.querySelectorAll('#customerTabs .nav-link').forEach(btn => {
            btn.addEventListener('click', () => this.switchTab(btn.dataset.tab));
        });

        // Müşteri düzenleme butonu
        document.getElementById('btnEditCustomer')?.addEventListener('click', () => {
            CustomerModule.openModal(this.customerId);
        });
    },

    switchTab(tab) {
        this.activeTab = tab;
        
        // Tab butonlarını güncelle
        document.querySelectorAll('#customerTabs .nav-link').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tab);
        });

        // İçeriği render et
        const container = document.getElementById('customerTabContent');
        container.innerHTML = this.renderTabContent(tab);
        this.bindTabEvents(container, tab);
    },

    renderTabContent(tab) {
        switch (tab) {
            case 'bilgi': return this.renderBilgi();
            case 'kisiler': return this.renderKisiler();
            case 'gorusme': return this.renderGorusme();
            case 'projeler': return this.renderProjeler();
            case 'teklifler': return this.renderTeklifler();
            case 'sozlesmeler': return this.renderSozlesmeler();
            case 'takvim': return this.renderTakvim();
            case 'damgavergisi': return this.renderDamgaVergisi();
            case 'teminatlar': return this.renderTeminatlar();
            case 'faturalar': return this.renderFaturalar();
            case 'odemeler': return this.renderOdemeler();
            case 'dosyalar': return this.renderDosyalar();
            default: return '<p class="text-muted">İçerik yükleniyor...</p>';
        }
    },

    // ========== STANDART PANEL YAPISI ==========
    renderPanel(config) {
        const { id, title, icon, filterFields, columns, data, addType, emptyMsg } = config;
        
        // Filtre satırı HTML
        let filterHtml = '';
        if (filterFields && filterFields.length) {
            filterHtml = `
                <div class="bg-light p-2 border-bottom">
                    <div class="row g-2 align-items-end">
                        ${filterFields.map(f => `
                            <div class="col-md-${f.width || 2}">
                                ${f.type === 'select' ? `
                                    <select class="form-select form-select-sm" id="filter_${id}_${f.field}">
                                        <option value="">${f.placeholder || f.label}</option>
                                        ${(f.options || []).map(o => `<option value="${o.value}">${o.label}</option>`).join('')}
                                    </select>
                                ` : `
                                    <input type="${f.type || 'text'}" class="form-control form-control-sm" 
                                           id="filter_${id}_${f.field}" placeholder="${f.placeholder || f.label}">
                                `}
                            </div>
                        `).join('')}
                        <div class="col-auto">
                            <button type="button" class="btn btn-primary btn-sm" data-search="${id}">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                        <div class="col-auto ms-auto">
                            <button type="button" class="btn btn-success btn-sm" data-add="${addType}">
                                <i class="bi bi-plus-lg me-1"></i>Ekle
                            </button>
                        </div>
                    </div>
                </div>`;
        }

        // Tablo HTML
        const tableHtml = this.renderDataTable(id, columns, data, emptyMsg);

        return `
            <div class="card shadow-sm" id="panel_${id}">
                <div class="card-header py-2 d-flex justify-content-between align-items-center bg-white">
                    <span class="fw-semibold"><i class="bi ${icon} me-2"></i>${title}</span>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" data-panel-action="collapse" title="Daralt">
                            <i class="bi bi-dash-lg"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-panel-action="fullscreen" title="Tam Ekran">
                            <i class="bi bi-arrows-fullscreen"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-panel-action="close" title="Kapat">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
                ${filterHtml}
                <div class="card-body p-0" id="body_${id}">
                    ${tableHtml}
                </div>
            </div>`;
    },

    renderDataTable(id, columns, data, emptyMsg) {
        if (!data || !data.length) {
            return `<div class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>${emptyMsg || 'Kayıt bulunamadı'}</div>`;
        }

        const headers = columns.map(c => `<th class="bg-light">${c.label}</th>`).join('') + '<th class="bg-light text-center" style="width:80px;">İşlem</th>';
        
        const rows = data.map(row => {
            const cells = columns.map(c => {
                let val = row[c.field];
                if (c.render) val = c.render(val, row);
                return `<td>${val ?? '-'}</td>`;
            }).join('');
            
            return `
                <tr data-id="${row.Id}">
                    ${cells}
                    <td class="text-center position-static">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                <i class="bi bi-three-dots"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li><a class="dropdown-item" href="#" data-action="view" data-id="${row.Id}"><i class="bi bi-eye me-2"></i>Detay</a></li>
                                <li><a class="dropdown-item" href="#" data-action="edit" data-id="${row.Id}"><i class="bi bi-pencil me-2"></i>Düzelt</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" data-action="delete" data-id="${row.Id}"><i class="bi bi-trash me-2"></i>Sil</a></li>
                            </ul>
                        </div>
                    </td>
                </tr>`;
        }).join('');

        return `
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead><tr>${headers}</tr></thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>`;
    },

    // ========== TAB RENDER FONKSİYONLARI ==========

    renderBilgi() {
        const c = this.data.customer;
        return `
            <div class="card shadow-sm">
                <div class="card-header py-2 bg-white">
                    <span class="fw-semibold"><i class="bi bi-info-circle me-2"></i>Müşteri Bilgisi</span>
                </div>
                <div class="card-body">
                    <form id="customerEditForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Unvan</label>
                                <input type="text" class="form-control" id="editUnvan" value="${NbtUtils.escapeHtml(c.Unvan || '')}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Kayıt Tarihi</label>
                                <input type="text" class="form-control" value="${NbtUtils.formatDate(c.EklemeZamani)}" disabled>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Açıklama</label>
                            <textarea class="form-control" id="editAciklama" rows="3">${NbtUtils.escapeHtml(c.Aciklama || '')}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Kaydet
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-2">
                    <div class="card text-center p-3"><div class="fs-4 fw-bold text-primary">${this.data.projects.length}</div><small class="text-muted">Proje</small></div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center p-3"><div class="fs-4 fw-bold text-success">${this.data.offers.length}</div><small class="text-muted">Teklif</small></div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center p-3"><div class="fs-4 fw-bold text-info">${this.data.contracts.length}</div><small class="text-muted">Sözleşme</small></div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center p-3"><div class="fs-4 fw-bold text-warning">${this.data.guarantees.length}</div><small class="text-muted">Teminat</small></div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center p-3"><div class="fs-4 fw-bold text-secondary">${this.data.invoices.length}</div><small class="text-muted">Fatura</small></div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center p-3"><div class="fs-4 fw-bold text-success">${this.data.payments.length}</div><small class="text-muted">Ödeme</small></div>
                </div>
            </div>`;
    },

    renderKisiler() {
        return this.renderPanel({
            id: 'kisiler',
            title: 'Kişiler',
            icon: 'bi-people',
            addType: 'contact',
            emptyMsg: 'Henüz kişi eklenmemiş',
            filterFields: [
                { field: 'AdSoyad', placeholder: 'Ad Soyad', width: 2 },
                { field: 'Unvan', placeholder: 'Ünvan', width: 2 },
                { field: 'Telefon', placeholder: 'Telefon', width: 2 }
            ],
            columns: [
                { field: 'AdSoyad', label: 'Ad Soyad' },
                { field: 'Unvan', label: 'Ünvan' },
                { field: 'Telefon', label: 'Telefon' },
                { field: 'Email', label: 'E-posta' }
            ],
            data: this.data.contacts || []
        });
    },

    renderGorusme() {
        return this.renderPanel({
            id: 'gorusme',
            title: 'Görüşmeler',
            icon: 'bi-chat-dots',
            addType: 'meeting',
            emptyMsg: 'Henüz görüşme kaydı yok',
            filterFields: [
                { field: 'Tarih', type: 'date', placeholder: 'Tarih', width: 2 },
                { field: 'Konu', placeholder: 'Konu', width: 3 }
            ],
            columns: [
                { field: 'Tarih', label: 'Tarih', render: v => NbtUtils.formatDate(v) },
                { field: 'Konu', label: 'Konu' },
                { field: 'Notlar', label: 'Notlar' },
                { field: 'Kisi', label: 'Görüşülen Kişi' }
            ],
            data: this.data.meetings || []
        });
    },

    renderProjeler() {
        return this.renderPanel({
            id: 'projeler',
            title: 'Projeler',
            icon: 'bi-kanban',
            addType: 'project',
            emptyMsg: 'Henüz proje eklenmemiş',
            filterFields: [
                { field: 'ProjeAdi', placeholder: 'Proje Adı', width: 3 },
                { field: 'Durum', type: 'select', placeholder: 'Durum', width: 2, options: [
                    { value: '1', label: 'Aktif' },
                    { value: '2', label: 'Tamamlandı' },
                    { value: '3', label: 'İptal' }
                ]}
            ],
            columns: [
                { field: 'ProjeAdi', label: 'Proje Adı' },
                { field: 'BaslangicTarihi', label: 'Başlangıç', render: v => NbtUtils.formatDate(v) },
                { field: 'BitisTarihi', label: 'Bitiş', render: v => NbtUtils.formatDate(v) },
                { field: 'Butce', label: 'Bütçe', render: v => NbtUtils.formatMoney(v) },
                { field: 'Durum', label: 'Durum', render: v => this.getStatusBadge(v, 'project') }
            ],
            data: this.data.projects
        });
    },

    renderTeklifler() {
        return this.renderPanel({
            id: 'teklifler',
            title: 'Teklifler',
            icon: 'bi-file-earmark-text',
            addType: 'offer',
            emptyMsg: 'Henüz teklif eklenmemiş',
            filterFields: [
                { field: 'TeklifNo', placeholder: 'Teklif No', width: 2 },
                { field: 'Konu', placeholder: 'Konu', width: 3 },
                { field: 'Durum', type: 'select', placeholder: 'Durum', width: 2, options: [
                    { value: '0', label: 'Taslak' },
                    { value: '1', label: 'Gönderildi' },
                    { value: '2', label: 'Onaylandı' },
                    { value: '3', label: 'Reddedildi' }
                ]}
            ],
            columns: [
                { field: 'TeklifNo', label: 'Teklif No' },
                { field: 'Konu', label: 'Konu' },
                { field: 'Tutar', label: 'Tutar', render: (v, row) => NbtUtils.formatMoney(v, row.ParaBirimi) },
                { field: 'TeklifTarihi', label: 'Tarih', render: v => NbtUtils.formatDate(v) },
                { field: 'Durum', label: 'Durum', render: v => this.getStatusBadge(v, 'offer') }
            ],
            data: this.data.offers
        });
    },

    renderSozlesmeler() {
        return this.renderPanel({
            id: 'sozlesmeler',
            title: 'Sözleşmeler',
            icon: 'bi-file-text',
            addType: 'contract',
            emptyMsg: 'Henüz sözleşme eklenmemiş',
            filterFields: [
                { field: 'SozlesmeNo', placeholder: 'Sözleşme No', width: 2 },
                { field: 'Durum', type: 'select', placeholder: 'Durum', width: 2, options: [
                    { value: '1', label: 'Aktif' },
                    { value: '2', label: 'Pasif' },
                    { value: '3', label: 'İptal' }
                ]}
            ],
            columns: [
                { field: 'SozlesmeNo', label: 'Sözleşme No' },
                { field: 'BaslangicTarihi', label: 'Başlangıç', render: v => NbtUtils.formatDate(v) },
                { field: 'BitisTarihi', label: 'Bitiş', render: v => NbtUtils.formatDate(v) },
                { field: 'Tutar', label: 'Tutar', render: (v, row) => NbtUtils.formatMoney(v, row.ParaBirimi) },
                { field: 'Durum', label: 'Durum', render: v => this.getStatusBadge(v, 'contract') }
            ],
            data: this.data.contracts
        });
    },

    renderTakvim() {
        return `
            <div class="card shadow-sm">
                <div class="card-header py-2 bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold"><i class="bi bi-calendar3 me-2"></i>Takvim</span>
                    <button type="button" class="btn btn-success btn-sm" data-add="event">
                        <i class="bi bi-plus-lg me-1"></i>Etkinlik Ekle
                    </button>
                </div>
                <div class="card-body" id="customerCalendar">
                    <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
                </div>
            </div>`;
    },

    renderDamgaVergisi() {
        return this.renderPanel({
            id: 'damgavergisi',
            title: 'Damga Vergisi',
            icon: 'bi-percent',
            addType: 'stamptax',
            emptyMsg: 'Henüz damga vergisi kaydı yok',
            filterFields: [
                { field: 'Tarih', type: 'date', placeholder: 'Tarih', width: 2 },
                { field: 'BelgeNo', placeholder: 'Belge No', width: 2 }
            ],
            columns: [
                { field: 'BelgeNo', label: 'Belge No' },
                { field: 'Tarih', label: 'Tarih', render: v => NbtUtils.formatDate(v) },
                { field: 'Tutar', label: 'Tutar', render: (v, row) => NbtUtils.formatMoney(v, row.DovizCinsi) },
                { field: 'Aciklama', label: 'Açıklama' }
            ],
            data: this.data.stampTaxes || []
        });
    },

    renderTeminatlar() {
        return this.renderPanel({
            id: 'teminatlar',
            title: 'Teminatlar',
            icon: 'bi-shield-check',
            addType: 'guarantee',
            emptyMsg: 'Henüz teminat eklenmemiş',
            filterFields: [
                { field: 'BelgeNo', placeholder: 'Belge No', width: 2 },
                { field: 'Tur', placeholder: 'Tür', width: 2 },
                { field: 'Durum', type: 'select', placeholder: 'Durum', width: 2, options: [
                    { value: '1', label: 'Bekliyor' },
                    { value: '2', label: 'İade Edildi' },
                    { value: '3', label: 'Tahsil Edildi' },
                    { value: '4', label: 'Yandı' }
                ]}
            ],
            columns: [
                { field: 'BelgeNo', label: 'Belge No' },
                { field: 'Tur', label: 'Tür' },
                { field: 'Tutar', label: 'Tutar', render: (v, row) => NbtUtils.formatMoney(v, row.ParaBirimi) },
                { field: 'BankaAdi', label: 'Banka' },
                { field: 'VadeTarihi', label: 'Vade', render: v => NbtUtils.formatDate(v) },
                { field: 'Durum', label: 'Durum', render: v => this.getStatusBadge(v, 'guarantee') }
            ],
            data: this.data.guarantees
        });
    },

    // ========== FATURA TAB - KALAN KIRMIZI VURGUSU ==========
    renderFaturalar() {
        const data = this.data.invoices.map(inv => ({
            ...inv,
            _kalanClass: (inv.Kalan > 0) ? 'text-danger fw-bold' : 'text-success'
        }));

        return this.renderPanel({
            id: 'faturalar',
            title: 'Faturalar',
            icon: 'bi-receipt',
            addType: 'invoice',
            emptyMsg: 'Henüz fatura eklenmemiş',
            filterFields: [
                { field: 'Tarih', type: 'date', placeholder: 'Tarih', width: 2 },
                { field: 'DovizCinsi', type: 'select', placeholder: 'Döviz', width: 2, options: [
                    { value: 'TRY', label: 'TRY' },
                    { value: 'USD', label: 'USD' },
                    { value: 'EUR', label: 'EUR' }
                ]}
            ],
            columns: [
                { field: 'Tarih', label: 'Tarih', render: v => NbtUtils.formatDate(v) },
                { field: 'Tutar', label: 'Tutar', render: (v, row) => NbtUtils.formatMoney(v, row.DovizCinsi) },
                { field: 'OdenenTutar', label: 'Ödenen', render: (v, row) => NbtUtils.formatMoney(v || 0, row.DovizCinsi) },
                { field: 'Kalan', label: 'Kalan', render: (v, row) => {
                    const kalan = parseFloat(v) || 0;
                    const cls = kalan > 0 ? 'text-danger fw-bold' : 'text-success';
                    return `<span class="${cls}">${NbtUtils.formatMoney(kalan, row.DovizCinsi)}</span>`;
                }},
                { field: 'Aciklama', label: 'Açıklama' }
            ],
            data: data
        });
    },

    // ========== ÖDEME TAB - FATURA ID DROPDOWN ==========
    renderOdemeler() {
        return this.renderPanel({
            id: 'odemeler',
            title: 'Ödemeler',
            icon: 'bi-cash-stack',
            addType: 'payment',
            emptyMsg: 'Henüz ödeme kaydı yok',
            filterFields: [
                { field: 'Tarih', type: 'date', placeholder: 'Tarih', width: 2 },
                { field: 'FaturaId', type: 'select', placeholder: 'Fatura', width: 3, options: 
                    this.data.invoices.map(f => ({
                        value: f.Id,
                        label: `FT${f.Id}/${NbtUtils.formatDate(f.Tarih)} [${NbtUtils.formatMoney(f.Tutar, f.DovizCinsi)}]`
                    }))
                }
            ],
            columns: [
                { field: 'Tarih', label: 'Tarih', render: v => NbtUtils.formatDate(v) },
                { field: 'FaturaId', label: 'Fatura', render: (v, row) => {
                    if (!v) return '-';
                    return `FT${v}/${NbtUtils.formatDate(row.FaturaTarihi)} [${NbtUtils.formatMoney(row.FaturaTutari, row.FaturaDovizi)}]`;
                }},
                { field: 'Tutar', label: 'Tutar', render: v => NbtUtils.formatMoney(v) },
                { field: 'Aciklama', label: 'Açıklama' }
            ],
            data: this.data.payments
        });
    },

    renderDosyalar() {
        return this.renderPanel({
            id: 'dosyalar',
            title: 'Dosyalar',
            icon: 'bi-folder',
            addType: 'file',
            emptyMsg: 'Henüz dosya yüklenmemiş',
            filterFields: [
                { field: 'DosyaAdi', placeholder: 'Dosya Adı', width: 3 },
                { field: 'DosyaTipi', type: 'select', placeholder: 'Tür', width: 2, options: [
                    { value: 'pdf', label: 'PDF' },
                    { value: 'doc', label: 'Word' },
                    { value: 'xls', label: 'Excel' },
                    { value: 'image', label: 'Resim' }
                ]}
            ],
            columns: [
                { field: 'DosyaAdi', label: 'Dosya Adı' },
                { field: 'DosyaTipi', label: 'Tür' },
                { field: 'DosyaBoyutu', label: 'Boyut', render: v => v ? `${(v/1024).toFixed(1)} KB` : '-' },
                { field: 'OlusturmaZamani', label: 'Yüklenme', render: v => NbtUtils.formatDate(v) },
                { field: 'Aciklama', label: 'Açıklama' }
            ],
            data: this.data.files || []
        });
    },

    // ========== YARDIMCI FONKSİYONLAR ==========

    getStatusBadge(status, type) {
        const configs = {
            project: { 1: ['Aktif', 'success'], 2: ['Tamamlandı', 'info'], 3: ['İptal', 'danger'] },
            offer: { 0: ['Taslak', 'secondary'], 1: ['Gönderildi', 'warning'], 2: ['Onaylandı', 'success'], 3: ['Reddedildi', 'danger'] },
            contract: { 1: ['Aktif', 'success'], 2: ['Pasif', 'secondary'], 3: ['İptal', 'danger'] },
            guarantee: { 1: ['Bekliyor', 'warning'], 2: ['İade Edildi', 'info'], 3: ['Tahsil Edildi', 'success'], 4: ['Yandı', 'danger'] }
        };
        const config = configs[type]?.[status] || ['Bilinmiyor', 'secondary'];
        return `<span class="badge bg-${config[1]}">${config[0]}</span>`;
    },

    bindTabEvents(container, tab) {
        // Panel header actions
        container.querySelectorAll('[data-panel-action]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const action = btn.dataset.panelAction;
                const panel = btn.closest('.card');
                const body = panel.querySelector('.card-body');
                
                if (action === 'collapse') {
                    body.classList.toggle('d-none');
                    btn.querySelector('i').classList.toggle('bi-dash-lg');
                    btn.querySelector('i').classList.toggle('bi-plus-lg');
                } else if (action === 'fullscreen') {
                    panel.classList.toggle('position-fixed');
                    panel.classList.toggle('top-0');
                    panel.classList.toggle('start-0');
                    panel.classList.toggle('w-100');
                    panel.classList.toggle('h-100');
                    panel.style.zIndex = panel.classList.contains('position-fixed') ? '1050' : '';
                } else if (action === 'close') {
                    window.location.hash = '#customers';
                }
            });
        });

        // Bilgi tab form submit
        container.querySelector('#customerEditForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                await NbtApi.put(`/api/customers/${this.customerId}`, {
                    Unvan: document.getElementById('editUnvan').value.trim(),
                    Aciklama: document.getElementById('editAciklama').value.trim() || null
                });
                NbtToast.success('Müşteri bilgileri güncellendi');
                await this.loadCustomer();
            } catch (err) {
                NbtToast.error(err.message);
            }
        });

        // Tüm buton event'leri için tek event delegation handler
        container.addEventListener('click', (e) => {
            // Yeni kayıt butonları
            const addBtn = e.target.closest('[data-add]');
            if (addBtn) {
                e.preventDefault();
                this.openAddModal(addBtn.dataset.add);
                return;
            }

            // Filtre arama butonları
            const searchBtn = e.target.closest('[data-search]');
            if (searchBtn) {
                e.preventDefault();
                this.applyFilter(searchBtn.dataset.search);
                return;
            }

            // Tablo action dropdown (view/edit/delete)
            const actionEl = e.target.closest('[data-action]');
            if (actionEl) {
                e.preventDefault();
                const action = actionEl.dataset.action;
                const id = parseInt(actionEl.dataset.id);
                this.handleTableAction(action, id, tab);
                return;
            }
        });

        // Takvim render
        if (tab === 'takvim') {
            setTimeout(() => {
                const calContainer = document.getElementById('customerCalendar');
                if (calContainer && typeof NbtCalendar !== 'undefined') {
                    NbtCalendar.render(calContainer, {
                        events: (NbtCalendar.events || []).filter(e => e.customerId === this.customerId),
                        onDayClick: (date, events) => {
                            if (events.length) {
                                NbtToast.info(`${date}: ${events.length} etkinlik`);
                            }
                        }
                    });
                } else if (calContainer) {
                    calContainer.innerHTML = '<div class="text-center text-muted py-5">Takvim yükleniyor...</div>';
                }
            }, 100);
        }
    },

    applyFilter(panelId) {
        // Panel için filtre uygula
        const panel = document.getElementById(`panel_${panelId}`);
        if (!panel) return;

        // Filtre değerlerini topla
        const filters = {};
        panel.querySelectorAll(`[id^="filter_${panelId}_"]`).forEach(input => {
            const field = input.id.replace(`filter_${panelId}_`, '');
            if (input.value) filters[field] = input.value.toLowerCase();
        });

        // Data key'i bul (tabConfig ile uyumlu)
        const keyMap = {
            kisiler: 'contacts',
            gorusme: 'meetings',
            projeler: 'projects',
            teklifler: 'offers',
            sozlesmeler: 'contracts',
            teminatlar: 'guarantees',
            faturalar: 'invoices',
            odemeler: 'payments',
            damgavergisi: 'stampTaxes',
            dosyalar: 'files'
        };

        const dataKey = keyMap[panelId];
        if (!dataKey) return;

        // Filtrele
        let filtered = this.data[dataKey] || [];
        for (const [field, value] of Object.entries(filters)) {
            filtered = filtered.filter(item => {
                const itemVal = String(item[field] || '').toLowerCase();
                return itemVal.includes(value);
            });
        }

        NbtToast.info(`${filtered.length} kayıt bulundu`);
        
        // Tabloyu yeniden render et
        this.switchTab(this.activeTab);
    },

    handleTableAction(action, id, tab) {
        // Backend'i henüz hazır olmayan modüller
        const comingSoonTabs = [];
        if (comingSoonTabs.includes(tab)) {
            NbtToast.info('Bu modül yakında aktif olacak');
            return;
        }

        const typeMap = {
            projeler: { type: 'project', endpoint: '/api/projects', key: 'projects' },
            teklifler: { type: 'offer', endpoint: '/api/offers', key: 'offers' },
            sozlesmeler: { type: 'contract', endpoint: '/api/contracts', key: 'contracts' },
            teminatlar: { type: 'guarantee', endpoint: '/api/guarantees', key: 'guarantees' },
            faturalar: { type: 'invoice', endpoint: '/api/invoices', key: 'invoices' },
            odemeler: { type: 'payment', endpoint: '/api/payments', key: 'payments' },
            gorusme: { type: 'meeting', endpoint: '/api/meetings', key: 'meetings' },
            kisiler: { type: 'contact', endpoint: '/api/contacts', key: 'contacts' },
            damgavergisi: { type: 'stamptax', endpoint: '/api/stamp-taxes', key: 'stampTaxes' },
            dosyalar: { type: 'file', endpoint: '/api/files', key: 'files' }
        };

        const config = typeMap[tab];
        if (!config) return;

        if (action === 'view') {
            NbtToast.info(`Detay: ${config.type} #${id}`);
        } else if (action === 'edit') {
            this.openEditModal(config.type, id);
        } else if (action === 'delete') {
            this.confirmDelete(config.type, config.endpoint, id, config.key);
        }
    },

    openAddModal(type) {
        // Backend'i henüz hazır olmayan modüller
        const comingSoonTypes = ['event'];
        if (comingSoonTypes.includes(type)) {
            NbtToast.info('Bu modül yakında aktif olacak');
            return;
        }

        const modalMap = {
            project: 'projectModal',
            invoice: 'invoiceModal',
            payment: 'paymentModal',
            offer: 'offerModal',
            contract: 'contractModal',
            guarantee: 'guaranteeModal',
            meeting: 'meetingModal',
            contact: 'contactModal',
            stamptax: 'stampTaxModal',
            file: 'fileModal'
        };

        const modalId = modalMap[type];
        if (!modalId) {
            NbtToast.warning(`${type} için modal henüz tanımlı değil`);
            return;
        }

        // DOM'da modal var mı kontrol et
        if (!document.getElementById(modalId)) {
            NbtToast.warning(`${type} modal'ı bulunamadı`);
            return;
        }

        NbtModal.resetForm(modalId);
        
        // Müşteri seçimini otomatik doldur (select element)
        const selectId = `${type}MusteriId`;
        const selectEl = document.getElementById(selectId);
        if (selectEl && selectEl.tagName === 'SELECT') {
            this.populateCustomerSelect(selectEl);
            selectEl.value = this.customerId;
            selectEl.disabled = true;
        }
        
        // Hidden MusteriId için (meeting, contact, stamptax, file modal'ları)
        const hiddenMusteriIdMap = {
            meeting: 'meetingMusteriId',
            contact: 'contactMusteriId',
            stamptax: 'stampTaxMusteriId',
            file: 'fileMusteriId'
        };
        const hiddenId = hiddenMusteriIdMap[type];
        if (hiddenId) {
            const hiddenEl = document.getElementById(hiddenId);
            if (hiddenEl) {
                hiddenEl.value = this.customerId;
            }
        }

        // Ödeme modal'ında fatura dropdown'ı doldur
        if (type === 'payment') {
            const faturaSelect = document.getElementById('paymentFaturaId');
            if (faturaSelect) {
                faturaSelect.innerHTML = '<option value="">Fatura Seçiniz...</option>';
                this.data.invoices.forEach(f => {
                    const label = `FT${f.Id}/${NbtUtils.formatDate(f.Tarih)} [${NbtUtils.formatMoney(f.Tutar, f.DovizCinsi)}]`;
                    faturaSelect.innerHTML += `<option value="${f.Id}">${label}</option>`;
                });
            }
        }

        NbtModal.open(modalId);
    },

    openEditModal(type, id) {
        // Backend'i henüz hazır olmayan modüller
        const comingSoonTypes = [];
        if (comingSoonTypes.includes(type)) {
            NbtToast.info('Bu modül yakında aktif olacak');
            return;
        }

        const dataMap = {
            project: 'projects',
            invoice: 'invoices',
            payment: 'payments',
            offer: 'offers',
            contract: 'contracts',
            guarantee: 'guarantees',
            meeting: 'meetings',
            contact: 'contacts',
            stamptax: 'stampTaxes',
            file: 'files'
        };

        const dataKey = dataMap[type];
        if (!dataKey) {
            NbtToast.warning(`${type} için düzenleme henüz desteklenmiyor`);
            return;
        }

        const parsedId = parseInt(id, 10);
        const item = this.data[dataKey]?.find(i => parseInt(i.Id, 10) === parsedId);
        if (!item) {
            NbtToast.error('Kayıt bulunamadı');
            return;
        }

        // Modal aç ve doldur (her module kendi edit modal'ını handle eder)
        const moduleMap = {
            project: ProjectModule,
            invoice: InvoiceModule,
            payment: PaymentModule,
            offer: OfferModule,
            contract: ContractModule,
            guarantee: GuaranteeModule,
            meeting: MeetingModule,
            contact: ContactModule,
            stamptax: StampTaxModule
            // file: Dosyalar düzenlenemiyor, sadece yüklenip silinebilir
        };

        const module = moduleMap[type];
        if (module?.openModal) {
            module.openModal(id);
        } else {
            NbtToast.warning(`${type} için düzenleme modal'ı bulunamadı`);
        }
    },

    async confirmDelete(type, endpoint, id, dataKey) {
        if (!confirm('Bu kaydı silmek istediğinizden emin misiniz?')) return;

        const parsedId = parseInt(id, 10);
        try {
            await NbtApi.delete(`${endpoint}/${parsedId}`);
            NbtToast.success('Kayıt silindi');
            
            // Data'dan kaldır ve tabloyu yenile - parseInt karşılaştırma
            this.data[dataKey] = this.data[dataKey].filter(i => parseInt(i.Id, 10) !== parsedId);
            
            // Ödeme silindiğinde Fatura datasını da yenile (Kalan backend'de hesaplanıyor)
            if (dataKey === 'payments') {
                await this.loadRelatedData('invoices', '/api/invoices');
            }
            
            this.switchTab(this.activeTab);
        } catch (err) {
            NbtToast.error(err.message);
        }
    },

    populateCustomerSelect(selectEl) {
        selectEl.innerHTML = '<option value="">Seçiniz...</option>';
        AppState.customers.forEach(c => {
            selectEl.innerHTML += `<option value="${c.Id}">${NbtUtils.escapeHtml(c.Unvan)}</option>`;
        });
    }
};

// =============================================
// FATURA MODÜLÜ
// =============================================
const InvoiceModule = {
    _eventsBound: false,
    
    async init() {
        await this.loadList();
        this.initToolbar();
        this.bindEvents();
    },

    async loadList() {
        const container = document.getElementById('invoicesTableContainer');
        try {
            const response = await NbtApi.get('/api/invoices');
            this.data = response.data || [];
            this.renderTable(this.data);
        } catch (err) {
            container.innerHTML = `<div class="alert alert-danger m-3">${err.message}</div>`;
        }
    },

    initToolbar() {
        const toolbarContainer = document.getElementById('invoicesToolbar');
        toolbarContainer.innerHTML = NbtListToolbar.create({
            placeholder: 'Fatura ara...',
            onAdd: true
        });

        const panel = document.getElementById('panelInvoices');
        NbtListToolbar.bind(toolbarContainer, {
            onSearch: (query) => {
                const filtered = this.data.filter(item => 
                    (item.Aciklama || '').toLowerCase().includes(query.toLowerCase())
                );
                this.renderTable(filtered);
            },
            onAdd: () => this.openModal(),
            panelElement: panel
        });
    },

    renderTable(data) {
        const container = document.getElementById('invoicesTableContainer');
        const columns = [
            { field: 'MusteriUnvan', label: 'Müşteri' },
            { field: 'Tarih', label: 'Tarih', render: v => NbtUtils.formatDate(v) },
            { field: 'Tutar', label: 'Tutar', render: (v, row) => NbtUtils.formatMoney(v, row.DovizCinsi) },
            { field: 'Kalan', label: 'Kalan', render: (v, row) => {
                const kalan = parseFloat(v) || 0;
                const cls = kalan > 0 ? 'text-danger fw-bold' : 'text-success';
                return `<span class="${cls}">${NbtUtils.formatMoney(kalan, row.DovizCinsi)}</span>`;
            }},
            { field: 'Aciklama', label: 'Açıklama' }
        ];

        container.innerHTML = NbtDataTable.create(columns, data, {
            actions: { view: true, edit: true, delete: true },
            emptyMessage: 'Fatura bulunamadı'
        });

        NbtDataTable.bind(container, {
            onView: (id) => {
                const invoice = this.data.find(i => parseInt(i.Id, 10) === id);
                if (invoice) {
                    window.location.hash = `#customer/${invoice.MusteriId}?tab=faturalar`;
                }
            },
            onEdit: (id) => this.openModal(id),
            onDelete: async (id) => {
                if (!confirm('Bu kaydı silmek istediğinizden emin misiniz?')) return;
                try {
                    await NbtApi.delete(`/api/invoices/${id}`);
                    NbtToast.success('Fatura silindi');
                    await this.loadList();
                } catch (err) {
                    NbtToast.error(err.message);
                }
            }
        });
    },

    openModal(id = null) {
        NbtModal.resetForm('invoiceModal');
        document.getElementById('invoiceModalTitle').textContent = id ? 'Fatura Düzenle' : 'Yeni Fatura';
        document.getElementById('invoiceId').value = id || '';

        // Müşteri listesini doldur
        const select = document.getElementById('invoiceMusteriId');
        select.innerHTML = '<option value="">Seçiniz...</option>';
        select.disabled = false;
        AppState.customers.forEach(c => {
            select.innerHTML += `<option value="${c.Id}">${NbtUtils.escapeHtml(c.Unvan)}</option>`;
        });

        if (id) {
            const parsedId = parseInt(id, 10);
            const invoice = this.data.find(i => parseInt(i.Id, 10) === parsedId);
            if (invoice) {
                select.value = invoice.MusteriId;
                document.getElementById('invoiceTarih').value = invoice.Tarih?.split('T')[0] || '';
                document.getElementById('invoiceTutar').value = invoice.Tutar || '';
                document.getElementById('invoiceDoviz').value = invoice.DovizCinsi || 'TRY';
                document.getElementById('invoiceAciklama').value = invoice.Aciklama || '';
            }
        }

        NbtModal.open('invoiceModal');
    },

    async save() {
        const id = document.getElementById('invoiceId').value;
        const data = {
            MusteriId: parseInt(document.getElementById('invoiceMusteriId').value),
            Tarih: document.getElementById('invoiceTarih').value,
            Tutar: parseFloat(document.getElementById('invoiceTutar').value) || 0,
            DovizCinsi: document.getElementById('invoiceDoviz').value,
            Aciklama: document.getElementById('invoiceAciklama').value.trim() || null
        };

        if (!data.MusteriId) {
            NbtModal.showError('invoiceModal', 'Müşteri seçiniz');
            return;
        }

        try {
            if (id) {
                await NbtApi.put(`/api/invoices/${id}`, data);
                NbtToast.success('Fatura güncellendi');
            } else {
                await NbtApi.post('/api/invoices', data);
                NbtToast.success('Fatura eklendi');
            }
            NbtModal.close('invoiceModal');
            await this.loadList();
            
            // Müşteri detay sayfasındaysa verileri yenile
            if (CustomerDetailModule.customerId) {
                await CustomerDetailModule.loadRelatedData('invoices', '/api/invoices');
                CustomerDetailModule.switchTab(CustomerDetailModule.activeTab);
            }
        } catch (err) {
            NbtModal.showError('invoiceModal', err.message);
        }
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        document.getElementById('btnSaveInvoice')?.addEventListener('click', () => this.save());
    }
};

// =============================================
// ÖDEME MODÜLÜ
// =============================================
const PaymentModule = {
    _eventsBound: false,
    
    async init() {
        await this.loadList();
        this.initToolbar();
        this.bindEvents();
    },

    async loadList() {
        const container = document.getElementById('paymentsTableContainer');
        try {
            const response = await NbtApi.get('/api/payments');
            this.data = response.data || [];
            this.renderTable(this.data);
        } catch (err) {
            container.innerHTML = `<div class="alert alert-danger m-3">${err.message}</div>`;
        }
    },

    initToolbar() {
        const toolbarContainer = document.getElementById('paymentsToolbar');
        toolbarContainer.innerHTML = NbtListToolbar.create({
            placeholder: 'Ödeme ara...',
            onAdd: true
        });

        const panel = document.getElementById('panelPayments');
        NbtListToolbar.bind(toolbarContainer, {
            onSearch: (query) => {
                const filtered = this.data.filter(item => 
                    (item.Aciklama || '').toLowerCase().includes(query.toLowerCase())
                );
                this.renderTable(filtered);
            },
            onAdd: () => this.openModal(),
            panelElement: panel
        });
    },

    renderTable(data) {
        const container = document.getElementById('paymentsTableContainer');
        const columns = [
            { field: 'MusteriUnvan', label: 'Müşteri' },
            { field: 'Tarih', label: 'Tarih', render: v => NbtUtils.formatDate(v) },
            { field: 'Tutar', label: 'Tutar', render: v => NbtUtils.formatMoney(v) },
            { field: 'Aciklama', label: 'Açıklama' }
        ];

        container.innerHTML = NbtDataTable.create(columns, data, {
            actions: { view: true, edit: true, delete: true },
            emptyMessage: 'Ödeme bulunamadı'
        });

        NbtDataTable.bind(container, {
            onView: (id) => {
                const payment = this.data.find(p => parseInt(p.Id, 10) === id);
                if (payment) {
                    window.location.hash = `#customer/${payment.MusteriId}?tab=odemeler`;
                }
            },
            onEdit: (id) => this.openModal(id),
            onDelete: async (id) => {
                if (!confirm('Bu kaydı silmek istediğinizden emin misiniz?')) return;
                try {
                    await NbtApi.delete(`/api/payments/${id}`);
                    NbtToast.success('Ödeme silindi');
                    await this.loadList();
                } catch (err) {
                    NbtToast.error(err.message);
                }
            }
        });
    },

    openModal(id = null) {
        NbtModal.resetForm('paymentModal');
        document.getElementById('paymentModalTitle').textContent = id ? 'Ödeme Düzenle' : 'Yeni Ödeme';
        document.getElementById('paymentId').value = id || '';

        const select = document.getElementById('paymentMusteriId');
        select.innerHTML = '<option value="">Seçiniz...</option>';
        select.disabled = false;
        AppState.customers.forEach(c => {
            select.innerHTML += `<option value="${c.Id}">${NbtUtils.escapeHtml(c.Unvan)}</option>`;
        });

        // Fatura dropdown'ını doldur - müşteri seçimine göre filtrelenecek
        const faturaSelect = document.getElementById('paymentFaturaId');
        if (faturaSelect) {
            faturaSelect.innerHTML = '<option value="">Fatura Seçiniz (Opsiyonel)...</option>';
            
            // Müşteri değiştiğinde faturaları filtrele
            select.addEventListener('change', async () => {
                const musteriId = parseInt(select.value);
                if (!musteriId) {
                    faturaSelect.innerHTML = '<option value="">Fatura Seçiniz (Opsiyonel)...</option>';
                    return;
                }
                try {
                    const response = await NbtApi.get(`/api/invoices?musteri_id=${musteriId}`);
                    const faturalar = (response.data || []).filter(f => f.MusteriId === musteriId);
                    faturaSelect.innerHTML = '<option value="">Fatura Seçiniz (Opsiyonel)...</option>';
                    faturalar.forEach(f => {
                        const kalan = parseFloat(f.Kalan) || 0;
                        const label = `FT${f.Id}/${NbtUtils.formatDate(f.Tarih)} [${NbtUtils.formatMoney(f.Tutar, f.DovizCinsi)}]${kalan > 0 ? ' ⚠️' : ''}`;
                        faturaSelect.innerHTML += `<option value="${f.Id}">${label}</option>`;
                    });
                } catch (err) {
                    console.error('Fatura listesi alınamadı:', err);
                }
            });
        }

        if (id) {
            const parsedId = parseInt(id, 10);
            const payment = this.data.find(p => parseInt(p.Id, 10) === parsedId);
            if (payment) {
                select.value = payment.MusteriId;
                document.getElementById('paymentTarih').value = payment.Tarih?.split('T')[0] || '';
                document.getElementById('paymentTutar').value = payment.Tutar || '';
                document.getElementById('paymentAciklama').value = payment.Aciklama || '';
                // Fatura seçimini doldur
                if (faturaSelect && payment.FaturaId) {
                    // Müşteri seçimi değişti event'ini tetikle
                    select.dispatchEvent(new Event('change'));
                    setTimeout(() => {
                        faturaSelect.value = payment.FaturaId;
                    }, 300);
                }
            }
        }

        NbtModal.open('paymentModal');
    },

    async save() {
        const id = document.getElementById('paymentId').value;
        const faturaIdVal = document.getElementById('paymentFaturaId')?.value;
        const data = {
            MusteriId: parseInt(document.getElementById('paymentMusteriId').value),
            FaturaId: faturaIdVal ? parseInt(faturaIdVal) : null,
            Tarih: document.getElementById('paymentTarih').value,
            Tutar: parseFloat(document.getElementById('paymentTutar').value) || 0,
            Aciklama: document.getElementById('paymentAciklama').value.trim() || null
        };

        if (!data.MusteriId) {
            NbtModal.showError('paymentModal', 'Müşteri seçiniz');
            return;
        }

        try {
            if (id) {
                await NbtApi.put(`/api/payments/${id}`, data);
                NbtToast.success('Ödeme güncellendi');
            } else {
                await NbtApi.post('/api/payments', data);
                NbtToast.success('Ödeme eklendi');
            }
            NbtModal.close('paymentModal');
            await this.loadList();
            
            // Müşteri detay sayfasındaysa fatura datasını da yenile (Kalan hesabı için)
            if (CustomerDetailModule.customerId) {
                await CustomerDetailModule.loadRelatedData('invoices', '/api/invoices');
                await CustomerDetailModule.loadRelatedData('payments', '/api/payments');
                CustomerDetailModule.switchTab(CustomerDetailModule.activeTab);
            }
        } catch (err) {
            NbtModal.showError('paymentModal', err.message);
        }
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        document.getElementById('btnSavePayment')?.addEventListener('click', () => this.save());
    }
};

// =============================================
// GÖRÜŞME MODÜLÜ (MEETING)
// =============================================
const MeetingModule = {
    openModal(id = null) {
        NbtModal.resetForm('meetingModal');
        document.getElementById('meetingModalTitle').textContent = id ? 'Görüşme Düzenle' : 'Yeni Görüşme';
        document.getElementById('meetingId').value = id || '';

        if (id) {
            const meeting = CustomerDetailModule.data.meetings.find(m => parseInt(m.Id, 10) === parseInt(id, 10));
            if (meeting) {
                document.getElementById('meetingMusteriId').value = meeting.MusteriId;
                document.getElementById('meetingTarih').value = meeting.Tarih?.split('T')[0] || '';
                document.getElementById('meetingKonu').value = meeting.Konu || '';
                document.getElementById('meetingKisi').value = meeting.Kisi || '';
                document.getElementById('meetingNotlar').value = meeting.Notlar || '';
            }
        }

        NbtModal.open('meetingModal');
    },

    async save() {
        const id = document.getElementById('meetingId').value;
        const data = {
            MusteriId: parseInt(document.getElementById('meetingMusteriId').value),
            Tarih: document.getElementById('meetingTarih').value,
            Konu: document.getElementById('meetingKonu').value.trim(),
            Kisi: document.getElementById('meetingKisi').value.trim() || null,
            Notlar: document.getElementById('meetingNotlar').value.trim() || null
        };

        if (!data.Tarih) {
            NbtModal.showError('meetingModal', 'Tarih zorunludur');
            return;
        }
        if (!data.Konu) {
            NbtModal.showError('meetingModal', 'Konu zorunludur');
            return;
        }

        try {
            if (id) {
                await NbtApi.put(`/api/meetings/${id}`, data);
                NbtToast.success('Görüşme güncellendi');
            } else {
                await NbtApi.post('/api/meetings', data);
                NbtToast.success('Görüşme eklendi');
            }
            NbtModal.close('meetingModal');
            await CustomerDetailModule.loadRelatedData('meetings', '/api/meetings');
            CustomerDetailModule.switchTab('gorusme');
        } catch (err) {
            NbtModal.showError('meetingModal', err.message);
        }
    },

    bindEvents() {
        document.getElementById('btnSaveMeeting')?.addEventListener('click', () => this.save());
    }
};

// =============================================
// KİŞİ MODÜLÜ (CONTACT)
// =============================================
const ContactModule = {
    openModal(id = null) {
        NbtModal.resetForm('contactModal');
        document.getElementById('contactModalTitle').textContent = id ? 'Kişi Düzenle' : 'Yeni Kişi';
        document.getElementById('contactId').value = id || '';

        if (id) {
            const contact = CustomerDetailModule.data.contacts.find(c => parseInt(c.Id, 10) === parseInt(id, 10));
            if (contact) {
                document.getElementById('contactMusteriId').value = contact.MusteriId;
                document.getElementById('contactAdSoyad').value = contact.AdSoyad || '';
                document.getElementById('contactUnvan').value = contact.Unvan || '';
                document.getElementById('contactTelefon').value = contact.Telefon || '';
                document.getElementById('contactEmail').value = contact.Email || '';
                document.getElementById('contactNotlar').value = contact.Notlar || '';
            }
        }

        NbtModal.open('contactModal');
    },

    async save() {
        const id = document.getElementById('contactId').value;
        const data = {
            MusteriId: parseInt(document.getElementById('contactMusteriId').value),
            AdSoyad: document.getElementById('contactAdSoyad').value.trim(),
            Unvan: document.getElementById('contactUnvan').value.trim() || null,
            Telefon: document.getElementById('contactTelefon').value.trim() || null,
            Email: document.getElementById('contactEmail').value.trim() || null,
            Notlar: document.getElementById('contactNotlar').value.trim() || null
        };

        if (!data.AdSoyad) {
            NbtModal.showError('contactModal', 'Ad Soyad zorunludur');
            return;
        }

        try {
            if (id) {
                await NbtApi.put(`/api/contacts/${id}`, data);
                NbtToast.success('Kişi güncellendi');
            } else {
                await NbtApi.post('/api/contacts', data);
                NbtToast.success('Kişi eklendi');
            }
            NbtModal.close('contactModal');
            await CustomerDetailModule.loadRelatedData('contacts', '/api/contacts');
            CustomerDetailModule.switchTab('kisiler');
        } catch (err) {
            NbtModal.showError('contactModal', err.message);
        }
    },

    bindEvents() {
        document.getElementById('btnSaveContact')?.addEventListener('click', () => this.save());
    }
};

// =============================================
// DAMGA VERGİSİ MODÜLÜ (STAMP TAX)
// =============================================
const StampTaxModule = {
    openModal(id = null) {
        NbtModal.resetForm('stampTaxModal');
        document.getElementById('stampTaxModalTitle').textContent = id ? 'Damga Vergisi Düzenle' : 'Yeni Damga Vergisi';
        document.getElementById('stampTaxId').value = id || '';

        if (id) {
            const item = CustomerDetailModule.data.stampTaxes.find(s => parseInt(s.Id, 10) === parseInt(id, 10));
            if (item) {
                document.getElementById('stampTaxMusteriId').value = item.MusteriId;
                document.getElementById('stampTaxTarih').value = item.Tarih?.split('T')[0] || '';
                document.getElementById('stampTaxTutar').value = item.Tutar || '';
                document.getElementById('stampTaxDovizCinsi').value = item.DovizCinsi || 'TRY';
                document.getElementById('stampTaxBelgeNo').value = item.BelgeNo || '';
                document.getElementById('stampTaxAciklama').value = item.Aciklama || '';
            }
        }

        NbtModal.open('stampTaxModal');
    },

    async save() {
        const id = document.getElementById('stampTaxId').value;
        const data = {
            MusteriId: parseInt(document.getElementById('stampTaxMusteriId').value),
            Tarih: document.getElementById('stampTaxTarih').value,
            Tutar: parseFloat(document.getElementById('stampTaxTutar').value) || 0,
            DovizCinsi: document.getElementById('stampTaxDovizCinsi').value || 'TRY',
            BelgeNo: document.getElementById('stampTaxBelgeNo').value.trim() || null,
            Aciklama: document.getElementById('stampTaxAciklama').value.trim() || null
        };

        if (!data.Tarih) {
            NbtModal.showError('stampTaxModal', 'Tarih zorunludur');
            return;
        }
        if (!data.Tutar || data.Tutar <= 0) {
            NbtModal.showError('stampTaxModal', 'Tutar zorunludur');
            return;
        }

        try {
            if (id) {
                await NbtApi.put(`/api/stamp-taxes/${id}`, data);
                NbtToast.success('Damga vergisi güncellendi');
            } else {
                await NbtApi.post('/api/stamp-taxes', data);
                NbtToast.success('Damga vergisi eklendi');
            }
            NbtModal.close('stampTaxModal');
            await CustomerDetailModule.loadRelatedData('stampTaxes', '/api/stamp-taxes');
            CustomerDetailModule.switchTab('damgavergisi');
        } catch (err) {
            NbtModal.showError('stampTaxModal', err.message);
        }
    },

    bindEvents() {
        document.getElementById('btnSaveStampTax')?.addEventListener('click', () => this.save());
    }
};

// =============================================
// DOSYA MODÜLÜ (FILE)
// =============================================
const FileModule = {
    openModal() {
        NbtModal.resetForm('fileModal');
        document.getElementById('fileModalTitle').textContent = 'Dosya Yükle';
        document.getElementById('fileInput').value = '';
        NbtModal.open('fileModal');
    },

    async save() {
        const musteriId = document.getElementById('fileMusteriId').value;
        const fileInput = document.getElementById('fileInput');
        const aciklama = document.getElementById('fileAciklama').value.trim();

        if (!fileInput.files || !fileInput.files[0]) {
            NbtModal.showError('fileModal', 'Dosya seçiniz');
            return;
        }

        const formData = new FormData();
        formData.append('file', fileInput.files[0]);
        formData.append('MusteriId', musteriId);
        if (aciklama) formData.append('Aciklama', aciklama);

        try {
            const response = await fetch('/api/files', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + NbtUtils.getToken()
                },
                body: formData
            });

            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.error || 'Dosya yüklenemedi');
            }

            NbtToast.success('Dosya yüklendi');
            NbtModal.close('fileModal');
            await CustomerDetailModule.loadRelatedData('files', '/api/files');
            CustomerDetailModule.switchTab('dosyalar');
        } catch (err) {
            NbtModal.showError('fileModal', err.message);
        }
    },

    bindEvents() {
        document.getElementById('btnSaveFile')?.addEventListener('click', () => this.save());
    }
};

// =============================================
// PROJE MODÜLÜ
// =============================================
const ProjectModule = {
    _eventsBound: false,
    
    async init() {
        await this.loadList();
        this.initToolbar();
        this.bindEvents();
    },

    async loadList() {
        const container = document.getElementById('projectsTableContainer');
        try {
            const response = await NbtApi.get('/api/projects');
            this.data = response.data || [];
            this.renderTable(this.data);
        } catch (err) {
            container.innerHTML = `<div class="alert alert-danger m-3">${err.message}</div>`;
        }
    },

    initToolbar() {
        const toolbarContainer = document.getElementById('projectsToolbar');
        toolbarContainer.innerHTML = NbtListToolbar.create({
            placeholder: 'Proje ara...',
            onAdd: true
        });

        const panel = document.getElementById('panelProjects');
        NbtListToolbar.bind(toolbarContainer, {
            onSearch: (query) => {
                const filtered = this.data.filter(item => 
                    (item.ProjeAdi || '').toLowerCase().includes(query.toLowerCase())
                );
                this.renderTable(filtered);
            },
            onAdd: () => this.openModal(),
            panelElement: panel
        });
    },

    renderTable(data) {
        const container = document.getElementById('projectsTableContainer');
        const columns = [
            { field: 'MusteriUnvan', label: 'Müşteri' },
            { field: 'ProjeAdi', label: 'Proje Adı' },
            { field: 'BaslangicTarihi', label: 'Başlangıç', render: v => NbtUtils.formatDate(v) },
            { field: 'BitisTarihi', label: 'Bitiş', render: v => NbtUtils.formatDate(v) },
            { field: 'Butce', label: 'Bütçe', render: v => NbtUtils.formatMoney(v) },
            { field: 'Durum', label: 'Durum', render: v => {
                const statuses = { 1: ['Aktif', 'success'], 2: ['Tamamlandı', 'info'], 3: ['İptal', 'danger'] };
                const s = statuses[v] || ['Bilinmiyor', 'secondary'];
                return `<span class="badge bg-${s[1]}">${s[0]}</span>`;
            }}
        ];

        container.innerHTML = NbtDataTable.create(columns, data, {
            actions: { view: true, edit: true, delete: true },
            emptyMessage: 'Proje bulunamadı'
        });

        NbtDataTable.bind(container, {
            onView: (id) => {
                const project = this.data.find(p => parseInt(p.Id, 10) === id);
                if (project) {
                    window.location.hash = `#customer/${project.MusteriId}?tab=projeler`;
                }
            },
            onEdit: (id) => this.openModal(id),
            onDelete: async (id) => {
                if (!confirm('Bu kaydı silmek istediğinizden emin misiniz?')) return;
                try {
                    await NbtApi.delete(`/api/projects/${id}`);
                    NbtToast.success('Proje silindi');
                    await this.loadList();
                } catch (err) {
                    NbtToast.error(err.message);
                }
            }
        });
    },

    openModal(id = null) {
        NbtModal.resetForm('projectModal');
        document.getElementById('projectModalTitle').textContent = id ? 'Proje Düzenle' : 'Yeni Proje';
        document.getElementById('projectId').value = id || '';

        const select = document.getElementById('projectMusteriId');
        select.innerHTML = '<option value="">Seçiniz...</option>';
        select.disabled = false;
        AppState.customers.forEach(c => {
            select.innerHTML += `<option value="${c.Id}">${NbtUtils.escapeHtml(c.Unvan)}</option>`;
        });

        if (id) {
            const parsedId = parseInt(id, 10);
            const project = this.data.find(p => parseInt(p.Id, 10) === parsedId);
            if (project) {
                select.value = project.MusteriId;
                document.getElementById('projectName').value = project.ProjeAdi || '';
                document.getElementById('projectStart').value = project.BaslangicTarihi?.split('T')[0] || '';
                document.getElementById('projectEnd').value = project.BitisTarihi?.split('T')[0] || '';
                document.getElementById('projectBudget').value = project.Butce || '';
                document.getElementById('projectStatus').value = project.Durum || 1;
            }
        }

        NbtModal.open('projectModal');
    },

    async save() {
        const id = document.getElementById('projectId').value;
        const data = {
            MusteriId: parseInt(document.getElementById('projectMusteriId').value),
            ProjeAdi: document.getElementById('projectName').value.trim(),
            BaslangicTarihi: document.getElementById('projectStart').value || null,
            BitisTarihi: document.getElementById('projectEnd').value || null,
            Butce: parseFloat(document.getElementById('projectBudget').value) || 0,
            Durum: parseInt(document.getElementById('projectStatus').value)
        };

        if (!data.MusteriId) {
            NbtModal.showError('projectModal', 'Müşteri seçiniz');
            return;
        }
        if (!data.ProjeAdi) {
            NbtModal.showError('projectModal', 'Proje adı zorunludur');
            return;
        }

        try {
            if (id) {
                await NbtApi.put(`/api/projects/${id}`, data);
                NbtToast.success('Proje güncellendi');
            } else {
                await NbtApi.post('/api/projects', data);
                NbtToast.success('Proje eklendi');
            }
            NbtModal.close('projectModal');
            await this.loadList();
        } catch (err) {
            NbtModal.showError('projectModal', err.message);
        }
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        document.getElementById('btnSaveProject')?.addEventListener('click', () => this.save());
    }
};

// =============================================
// LOG MODÜLÜ
// =============================================
const LogModule = {
    data: [],

    async init() {
        await this.loadList();
        this.initToolbar();
    },

    async loadList() {
        const container = document.getElementById('logsTableContainer');
        try {
            const response = await NbtApi.get('/api/logs');
            this.data = response.data || [];
            this.renderTable(this.data);
        } catch (err) {
            container.innerHTML = `<div class="alert alert-danger m-3">${err.message}</div>`;
        }
    },

    initToolbar() {
        const toolbarContainer = document.getElementById('logsToolbar');
        toolbarContainer.innerHTML = NbtListToolbar.create({
            placeholder: 'Log ara...',
            onAdd: false
        });

        const panel = document.getElementById('panelLogs');
        NbtListToolbar.bind(toolbarContainer, {
            onSearch: (query) => {
                const filtered = this.data.filter(item => 
                    (item.Islem || '').toLowerCase().includes(query.toLowerCase()) ||
                    (item.Tablo || '').toLowerCase().includes(query.toLowerCase()) ||
                    (item.KullaniciAdi || '').toLowerCase().includes(query.toLowerCase())
                );
                this.renderTable(filtered);
            },
            panelElement: panel
        });
    },

    renderTable(data) {
        const container = document.getElementById('logsTableContainer');
        const columns = [
            { field: 'EklemeZamani', label: 'Zaman', render: v => NbtUtils.formatDate(v, 'long') },
            { field: 'KullaniciAdi', label: 'Kullanıcı' },
            { field: 'Islem', label: 'İşlem', render: v => {
                const colors = { INSERT: 'success', UPDATE: 'warning', DELETE: 'danger', SELECT: 'info', login: 'primary' };
                return `<span class="badge bg-${colors[v] || 'secondary'}">${v}</span>`;
            }},
            { field: 'Tablo', label: 'Tablo' },
            { field: 'YeniDeger', label: 'Detay', render: v => {
                if (!v) return '-';
                const text = typeof v === 'object' ? JSON.stringify(v) : v;
                const display = String(text).length > 50 ? String(text).substring(0, 50) + '...' : text;
                return `<small class="text-muted">${NbtUtils.escapeHtml(display)}</small>`;
            }}
        ];

        container.innerHTML = NbtDataTable.create(columns, data, {
            actions: { view: false, edit: false, delete: false },
            emptyMessage: 'Log kaydı bulunamadı'
        });
    }
};

// =============================================
// KULLANICI MODÜLÜ
// =============================================
const UserModule = {
    _eventsBound: false,
    
    async init() {
        await this.loadList();
        this.initToolbar();
        this.bindEvents();
    },

    async loadList() {
        const container = document.getElementById('usersTableContainer');
        try {
            const response = await NbtApi.get('/api/users');
            this.data = response.data || [];
            this.renderTable(this.data);
        } catch (err) {
            container.innerHTML = `<div class="alert alert-danger m-3">${err.message}</div>`;
        }
    },

    initToolbar() {
        const toolbarContainer = document.getElementById('usersToolbar');
        toolbarContainer.innerHTML = NbtListToolbar.create({
            placeholder: 'Kullanıcı ara...',
            onAdd: true
        });

        const panel = document.getElementById('panelUsers');
        NbtListToolbar.bind(toolbarContainer, {
            onSearch: (query) => {
                const filtered = this.data.filter(u => 
                    (u.AdSoyad || '').toLowerCase().includes(query.toLowerCase()) ||
                    (u.KullaniciAdi || '').toLowerCase().includes(query.toLowerCase())
                );
                this.renderTable(filtered);
            },
            onAdd: () => this.openModal(),
            panelElement: panel
        });
    },

    renderTable(data) {
        const container = document.getElementById('usersTableContainer');
        const columns = [
            { field: 'AdSoyad', label: 'Ad Soyad' },
            { field: 'KullaniciAdi', label: 'Kullanıcı Adı' },
            { field: 'Rol', label: 'Rol', render: v => {
                const roles = { superadmin: 'danger', admin: 'warning', user: 'info' };
                return `<span class="badge bg-${roles[v] || 'secondary'}">${v}</span>`;
            }},
            { field: 'Aktif', label: 'Durum', render: v => 
                v ? '<span class="badge bg-success">Aktif</span>' : 
                    '<span class="badge bg-danger">Pasif</span>'
            }
        ];

        container.innerHTML = NbtDataTable.create(columns, data, {
            actions: { view: false, edit: true, delete: true },
            emptyMessage: 'Kullanıcı bulunamadı'
        });

        NbtDataTable.bind(container, {
            onEdit: (id) => this.openModal(id),
            onDelete: async (id) => {
                if (!confirm('Bu kaydı silmek istediğinizden emin misiniz?')) return;
                try {
                    await NbtApi.delete(`/api/users/${id}`);
                    NbtToast.success('Kullanıcı silindi');
                    await this.loadList();
                } catch (err) {
                    NbtToast.error(err.message);
                }
            }
        });
    },

    openModal(id = null) {
        NbtModal.resetForm('userModal');
        document.getElementById('userModalTitle').textContent = id ? 'Kullanıcı Düzenle' : 'Yeni Kullanıcı';
        document.getElementById('userId').value = id || '';

        if (id) {
            const user = this.data.find(u => parseInt(u.Id, 10) === id);
            if (user) {
                document.getElementById('userAdSoyad').value = user.AdSoyad || '';
                document.getElementById('userKullaniciAdi').value = user.KullaniciAdi || '';
                document.getElementById('userRol').value = user.Rol || 'user';
            }
        }

        NbtModal.open('userModal');
    },

    async save() {
        const id = document.getElementById('userId').value;
        const data = {
            AdSoyad: document.getElementById('userAdSoyad').value.trim(),
            Rol: document.getElementById('userRol').value
        };
        
        // Şifre sadece girilmişse gönder
        const sifre = document.getElementById('userSifre').value;
        if (sifre) {
            data.Sifre = sifre;
        }

        if (!data.AdSoyad) {
            NbtModal.showError('userModal', 'Ad Soyad zorunludur');
            return;
        }

        // Yeni kullanıcı için kullanıcı adı ve şifre zorunlu
        if (!id) {
            const username = document.getElementById('userKullaniciAdi').value.trim();
            if (!username) {
                NbtModal.showError('userModal', 'Kullanıcı adı zorunludur');
                return;
            }
            if (!sifre) {
                NbtModal.showError('userModal', 'Şifre zorunludur');
                return;
            }
            data.KullaniciAdi = username;
            data.Sifre = sifre;
        }

        try {
            if (id) {
                await NbtApi.put(`/api/users/${id}`, data);
                NbtToast.success('Kullanıcı güncellendi');
            } else {
                await NbtApi.post('/api/users', data);
                NbtToast.success('Kullanıcı eklendi');
            }
            NbtModal.close('userModal');
            await this.loadList();
        } catch (err) {
            NbtModal.showError('userModal', err.message);
        }
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        document.getElementById('btnSaveUser')?.addEventListener('click', () => this.save());
    }
};

// =============================================
// TEKLİF MODÜLÜ
// =============================================
const OfferModule = {
    _eventsBound: false,
    data: [],

    async init() {
        await this.loadList();
        this.initToolbar();
        this.bindEvents();
    },

    async loadList() {
        const container = document.getElementById('offersTableContainer');
        try {
            const response = await NbtApi.get('/api/offers');
            this.data = response.data || [];
            this.renderTable(this.data);
        } catch (err) {
            container.innerHTML = `<div class="alert alert-danger m-3">${err.message}</div>`;
        }
    },

    initToolbar() {
        const toolbarContainer = document.getElementById('offersToolbar');
        toolbarContainer.innerHTML = NbtListToolbar.create({
            placeholder: 'Teklif ara...',
            onAdd: true
        });

        const panel = document.getElementById('panelOffers');
        NbtListToolbar.bind(toolbarContainer, {
            onSearch: (query) => {
                const filtered = this.data.filter(item => 
                    (item.TeklifNo || '').toLowerCase().includes(query.toLowerCase()) ||
                    (item.Konu || '').toLowerCase().includes(query.toLowerCase()) ||
                    (item.MusteriUnvan || '').toLowerCase().includes(query.toLowerCase())
                );
                this.renderTable(filtered);
            },
            onAdd: () => this.openModal(),
            panelElement: panel
        });
    },

    renderTable(data) {
        const container = document.getElementById('offersTableContainer');
        const columns = [
            { field: 'MusteriUnvan', label: 'Müşteri' },
            { field: 'TeklifNo', label: 'Teklif No' },
            { field: 'Konu', label: 'Konu' },
            { field: 'Tutar', label: 'Tutar', render: (v, row) => NbtUtils.formatMoney(v, row.ParaBirimi) },
            { field: 'TeklifTarihi', label: 'Tarih', render: v => NbtUtils.formatDate(v) },
            { field: 'Durum', label: 'Durum', render: v => {
                const statuses = { 0: ['Taslak', 'secondary'], 1: ['Gönderildi', 'warning'], 2: ['Onaylandı', 'success'], 3: ['Reddedildi', 'danger'] };
                const s = statuses[v] || ['Bilinmiyor', 'secondary'];
                return `<span class="badge bg-${s[1]}">${s[0]}</span>`;
            }}
        ];

        container.innerHTML = NbtDataTable.create(columns, data, {
            actions: { view: true, edit: true, delete: true },
            emptyMessage: 'Teklif bulunamadı'
        });

        NbtDataTable.bind(container, {
            onView: (id) => {
                const offer = this.data.find(o => parseInt(o.Id, 10) === id);
                if (offer) {
                    window.location.hash = `#customer/${offer.MusteriId}?tab=teklifler`;
                }
            },
            onEdit: (id) => this.openModal(id),
            onDelete: async (id) => {
                if (!confirm('Bu kaydı silmek istediğinizden emin misiniz?')) return;
                try {
                    await NbtApi.delete(`/api/offers/${id}`);
                    NbtToast.success('Teklif silindi');
                    await this.loadList();
                } catch (err) {
                    NbtToast.error(err.message);
                }
            }
        });
    },

    openModal(id = null) {
        NbtModal.resetForm('offerModal');
        document.getElementById('offerModalTitle').textContent = id ? 'Teklif Düzenle' : 'Yeni Teklif';
        document.getElementById('offerId').value = id || '';

        const select = document.getElementById('offerMusteriId');
        select.innerHTML = '<option value="">Seçiniz...</option>';
        select.disabled = false;
        AppState.customers.forEach(c => {
            select.innerHTML += `<option value="${c.Id}">${NbtUtils.escapeHtml(c.Unvan)}</option>`;
        });

        if (id) {
            const parsedId = parseInt(id, 10);
            const offer = this.data.find(o => parseInt(o.Id, 10) === parsedId);
            if (offer) {
                select.value = offer.MusteriId;
                document.getElementById('offerNo').value = offer.TeklifNo || '';
                document.getElementById('offerSubject').value = offer.Konu || '';
                document.getElementById('offerAmount').value = offer.Tutar || '';
                document.getElementById('offerCurrency').value = offer.ParaBirimi || 'TRY';
                document.getElementById('offerDate').value = offer.TeklifTarihi?.split('T')[0] || '';
                document.getElementById('offerValidDate').value = offer.GecerlilikTarihi?.split('T')[0] || '';
                document.getElementById('offerStatus').value = offer.Durum ?? 0;
            }
        }

        NbtModal.open('offerModal');
    },

    async save() {
        const id = document.getElementById('offerId').value;
        const data = {
            MusteriId: parseInt(document.getElementById('offerMusteriId').value),
            TeklifNo: document.getElementById('offerNo').value.trim(),
            Konu: document.getElementById('offerSubject').value.trim() || null,
            Tutar: parseFloat(document.getElementById('offerAmount').value) || 0,
            ParaBirimi: document.getElementById('offerCurrency').value,
            TeklifTarihi: document.getElementById('offerDate').value || null,
            GecerlilikTarihi: document.getElementById('offerValidDate').value || null,
            Durum: parseInt(document.getElementById('offerStatus').value)
        };

        if (!data.MusteriId) {
            NbtModal.showError('offerModal', 'Müşteri seçiniz');
            return;
        }
        if (!data.TeklifNo) {
            NbtModal.showError('offerModal', 'Teklif No zorunludur');
            return;
        }

        try {
            if (id) {
                await NbtApi.put(`/api/offers/${id}`, data);
                NbtToast.success('Teklif güncellendi');
            } else {
                await NbtApi.post('/api/offers', data);
                NbtToast.success('Teklif eklendi');
            }
            NbtModal.close('offerModal');
            await this.loadList();
        } catch (err) {
            NbtModal.showError('offerModal', err.message);
        }
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        document.getElementById('btnSaveOffer')?.addEventListener('click', () => this.save());
    }
};

// =============================================
// SÖZLEŞME MODÜLÜ
// =============================================
const ContractModule = {
    _eventsBound: false,
    data: [],

    async init() {
        await this.loadList();
        this.initToolbar();
        this.bindEvents();
    },

    async loadList() {
        const container = document.getElementById('contractsTableContainer');
        try {
            const response = await NbtApi.get('/api/contracts');
            this.data = response.data || [];
            this.renderTable(this.data);
        } catch (err) {
            container.innerHTML = `<div class="alert alert-danger m-3">${err.message}</div>`;
        }
    },

    initToolbar() {
        const toolbarContainer = document.getElementById('contractsToolbar');
        toolbarContainer.innerHTML = NbtListToolbar.create({
            placeholder: 'Sözleşme ara...',
            onAdd: true
        });

        const panel = document.getElementById('panelContracts');
        NbtListToolbar.bind(toolbarContainer, {
            onSearch: (query) => {
                const filtered = this.data.filter(item => 
                    (item.SozlesmeNo || '').toLowerCase().includes(query.toLowerCase()) ||
                    (item.MusteriUnvan || '').toLowerCase().includes(query.toLowerCase())
                );
                this.renderTable(filtered);
            },
            onAdd: () => this.openModal(),
            panelElement: panel
        });
    },

    renderTable(data) {
        const container = document.getElementById('contractsTableContainer');
        const columns = [
            { field: 'MusteriUnvan', label: 'Müşteri' },
            { field: 'SozlesmeNo', label: 'Sözleşme No' },
            { field: 'BaslangicTarihi', label: 'Başlangıç', render: v => NbtUtils.formatDate(v) },
            { field: 'BitisTarihi', label: 'Bitiş', render: v => NbtUtils.formatDate(v) },
            { field: 'Tutar', label: 'Tutar', render: (v, row) => NbtUtils.formatMoney(v, row.ParaBirimi) },
            { field: 'Durum', label: 'Durum', render: v => {
                const statuses = { 1: ['Aktif', 'success'], 2: ['Pasif', 'secondary'], 3: ['İptal', 'danger'] };
                const s = statuses[v] || ['Bilinmiyor', 'secondary'];
                return `<span class="badge bg-${s[1]}">${s[0]}</span>`;
            }}
        ];

        container.innerHTML = NbtDataTable.create(columns, data, {
            actions: { view: true, edit: true, delete: true },
            emptyMessage: 'Sözleşme bulunamadı'
        });

        NbtDataTable.bind(container, {
            onView: (id) => {
                const contract = this.data.find(c => parseInt(c.Id, 10) === id);
                if (contract) {
                    window.location.hash = `#customer/${contract.MusteriId}?tab=sozlesmeler`;
                }
            },
            onEdit: (id) => this.openModal(id),
            onDelete: async (id) => {
                if (!confirm('Bu kaydı silmek istediğinizden emin misiniz?')) return;
                try {
                    await NbtApi.delete(`/api/contracts/${id}`);
                    NbtToast.success('Sözleşme silindi');
                    await this.loadList();
                } catch (err) {
                    NbtToast.error(err.message);
                }
            }
        });
    },

    openModal(id = null) {
        NbtModal.resetForm('contractModal');
        document.getElementById('contractModalTitle').textContent = id ? 'Sözleşme Düzenle' : 'Yeni Sözleşme';
        document.getElementById('contractId').value = id || '';

        const select = document.getElementById('contractMusteriId');
        select.innerHTML = '<option value="">Seçiniz...</option>';
        select.disabled = false;
        AppState.customers.forEach(c => {
            select.innerHTML += `<option value="${c.Id}">${NbtUtils.escapeHtml(c.Unvan)}</option>`;
        });

        if (id) {
            const parsedId = parseInt(id, 10);
            const contract = this.data.find(c => parseInt(c.Id, 10) === parsedId);
            if (contract) {
                select.value = contract.MusteriId;
                document.getElementById('contractNo').value = contract.SozlesmeNo || '';
                document.getElementById('contractStart').value = contract.BaslangicTarihi?.split('T')[0] || '';
                document.getElementById('contractEnd').value = contract.BitisTarihi?.split('T')[0] || '';
                document.getElementById('contractAmount').value = contract.Tutar || '';
                document.getElementById('contractCurrency').value = contract.ParaBirimi || 'TRY';
                document.getElementById('contractStatus').value = contract.Durum ?? 1;
            }
        }

        NbtModal.open('contractModal');
    },

    async save() {
        const id = document.getElementById('contractId').value;
        const data = {
            MusteriId: parseInt(document.getElementById('contractMusteriId').value),
            SozlesmeNo: document.getElementById('contractNo').value.trim(),
            BaslangicTarihi: document.getElementById('contractStart').value || null,
            BitisTarihi: document.getElementById('contractEnd').value || null,
            Tutar: parseFloat(document.getElementById('contractAmount').value) || 0,
            ParaBirimi: document.getElementById('contractCurrency').value,
            Durum: parseInt(document.getElementById('contractStatus').value)
        };

        if (!data.MusteriId) {
            NbtModal.showError('contractModal', 'Müşteri seçiniz');
            return;
        }
        if (!data.SozlesmeNo) {
            NbtModal.showError('contractModal', 'Sözleşme No zorunludur');
            return;
        }

        try {
            if (id) {
                await NbtApi.put(`/api/contracts/${id}`, data);
                NbtToast.success('Sözleşme güncellendi');
            } else {
                await NbtApi.post('/api/contracts', data);
                NbtToast.success('Sözleşme eklendi');
            }
            NbtModal.close('contractModal');
            await this.loadList();
        } catch (err) {
            NbtModal.showError('contractModal', err.message);
        }
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        document.getElementById('btnSaveContract')?.addEventListener('click', () => this.save());
    }
};

// =============================================
// TEMİNAT MODÜLÜ
// =============================================
const GuaranteeModule = {
    _eventsBound: false,
    data: [],

    async init() {
        await this.loadList();
        this.initToolbar();
        this.bindEvents();
    },

    async loadList() {
        const container = document.getElementById('guaranteesTableContainer');
        try {
            const response = await NbtApi.get('/api/guarantees');
            this.data = response.data || [];
            this.renderTable(this.data);
        } catch (err) {
            container.innerHTML = `<div class="alert alert-danger m-3">${err.message}</div>`;
        }
    },

    initToolbar() {
        const toolbarContainer = document.getElementById('guaranteesToolbar');
        toolbarContainer.innerHTML = NbtListToolbar.create({
            placeholder: 'Teminat ara...',
            onAdd: true
        });

        const panel = document.getElementById('panelGuarantees');
        NbtListToolbar.bind(toolbarContainer, {
            onSearch: (query) => {
                const filtered = this.data.filter(item => 
                    (item.BelgeNo || '').toLowerCase().includes(query.toLowerCase()) ||
                    (item.Tur || '').toLowerCase().includes(query.toLowerCase()) ||
                    (item.MusteriUnvan || '').toLowerCase().includes(query.toLowerCase()) ||
                    (item.BankaAdi || '').toLowerCase().includes(query.toLowerCase())
                );
                this.renderTable(filtered);
            },
            onAdd: () => this.openModal(),
            panelElement: panel
        });
    },

    renderTable(data) {
        const container = document.getElementById('guaranteesTableContainer');
        const columns = [
            { field: 'MusteriUnvan', label: 'Müşteri' },
            { field: 'BelgeNo', label: 'Belge No' },
            { field: 'Tur', label: 'Tür' },
            { field: 'Tutar', label: 'Tutar', render: (v, row) => NbtUtils.formatMoney(v, row.ParaBirimi) },
            { field: 'BankaAdi', label: 'Banka' },
            { field: 'VadeTarihi', label: 'Vade', render: v => NbtUtils.formatDate(v) },
            { field: 'Durum', label: 'Durum', render: v => {
                const statuses = { 1: ['Bekliyor', 'warning'], 2: ['İade Edildi', 'info'], 3: ['Tahsil Edildi', 'success'], 4: ['Yandı', 'danger'] };
                const s = statuses[v] || ['Bilinmiyor', 'secondary'];
                return `<span class="badge bg-${s[1]}">${s[0]}</span>`;
            }}
        ];

        container.innerHTML = NbtDataTable.create(columns, data, {
            actions: { view: true, edit: true, delete: true },
            emptyMessage: 'Teminat bulunamadı'
        });

        NbtDataTable.bind(container, {
            onView: (id) => {
                const guarantee = this.data.find(g => parseInt(g.Id, 10) === id);
                if (guarantee) {
                    window.location.hash = `#customer/${guarantee.MusteriId}?tab=teminatlar`;
                }
            },
            onEdit: (id) => this.openModal(id),
            onDelete: async (id) => {
                if (!confirm('Bu kaydı silmek istediğinizden emin misiniz?')) return;
                try {
                    await NbtApi.delete(`/api/guarantees/${id}`);
                    NbtToast.success('Teminat silindi');
                    await this.loadList();
                } catch (err) {
                    NbtToast.error(err.message);
                }
            }
        });
    },

    openModal(id = null) {
        NbtModal.resetForm('guaranteeModal');
        document.getElementById('guaranteeModalTitle').textContent = id ? 'Teminat Düzenle' : 'Yeni Teminat';
        document.getElementById('guaranteeId').value = id || '';

        const select = document.getElementById('guaranteeMusteriId');
        select.innerHTML = '<option value="">Seçiniz...</option>';
        select.disabled = false;
        AppState.customers.forEach(c => {
            select.innerHTML += `<option value="${c.Id}">${NbtUtils.escapeHtml(c.Unvan)}</option>`;
        });

        if (id) {
            const parsedId = parseInt(id, 10);
            const guarantee = this.data.find(g => parseInt(g.Id, 10) === parsedId);
            if (guarantee) {
                select.value = guarantee.MusteriId;
                document.getElementById('guaranteeNo').value = guarantee.BelgeNo || '';
                document.getElementById('guaranteeType').value = guarantee.Tur || 'Nakit';
                document.getElementById('guaranteeBank').value = guarantee.BankaAdi || '';
                document.getElementById('guaranteeAmount').value = guarantee.Tutar || '';
                document.getElementById('guaranteeCurrency').value = guarantee.ParaBirimi || 'TRY';
                document.getElementById('guaranteeDate').value = guarantee.VadeTarihi?.split('T')[0] || '';
                document.getElementById('guaranteeStatus').value = guarantee.Durum ?? 1;
            }
        }

        NbtModal.open('guaranteeModal');
    },

    async save() {
        const id = document.getElementById('guaranteeId').value;
        const data = {
            MusteriId: parseInt(document.getElementById('guaranteeMusteriId').value),
            BelgeNo: document.getElementById('guaranteeNo').value.trim() || null,
            Tur: document.getElementById('guaranteeType').value,
            BankaAdi: document.getElementById('guaranteeBank').value.trim() || null,
            Tutar: parseFloat(document.getElementById('guaranteeAmount').value) || 0,
            ParaBirimi: document.getElementById('guaranteeCurrency').value,
            VadeTarihi: document.getElementById('guaranteeDate').value || null,
            Durum: parseInt(document.getElementById('guaranteeStatus').value)
        };

        if (!data.MusteriId) {
            NbtModal.showError('guaranteeModal', 'Müşteri seçiniz');
            return;
        }
        if (!data.Tur) {
            NbtModal.showError('guaranteeModal', 'Teminat türü zorunludur');
            return;
        }

        try {
            if (id) {
                await NbtApi.put(`/api/guarantees/${id}`, data);
                NbtToast.success('Teminat güncellendi');
            } else {
                await NbtApi.post('/api/guarantees', data);
                NbtToast.success('Teminat eklendi');
            }
            NbtModal.close('guaranteeModal');
            await this.loadList();
        } catch (err) {
            NbtModal.showError('guaranteeModal', err.message);
        }
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        document.getElementById('btnSaveGuarantee')?.addEventListener('click', () => this.save());
    }
};

// =============================================
// ŞİFRE DEĞİŞTİRME
// =============================================
const PasswordModule = {
    init() {
        document.getElementById('btnChangePassword')?.addEventListener('click', () => this.save());
    },

    async save() {
        const current = document.getElementById('currentPassword').value;
        const newPass = document.getElementById('newPassword').value;
        const confirm = document.getElementById('confirmPassword').value;

        if (!current || !newPass || !confirm) {
            NbtModal.showError('passwordModal', 'Tüm alanları doldurunuz');
            return;
        }
        if (newPass.length < 6) {
            NbtModal.showError('passwordModal', 'Yeni şifre en az 6 karakter olmalıdır');
            return;
        }
        if (newPass !== confirm) {
            NbtModal.showError('passwordModal', 'Yeni şifreler eşleşmiyor');
            return;
        }

        try {
            await NbtApi.post('/api/users/change-password', {
                CurrentPassword: current,
                NewPassword: newPass
            });
            NbtToast.success('Şifreniz değiştirildi');
            NbtModal.close('passwordModal');
        } catch (err) {
            NbtModal.showError('passwordModal', err.message);
        }
    }
};

// =============================================
// ROUTER SETUP
// =============================================
function setupRoutes() {
    // Dashboard
    NbtRouter.register('dashboard', () => {
        document.getElementById('view-dashboard').classList.remove('d-none');
        DashboardModule.init();
    });

    // Müşteriler listesi
    NbtRouter.register('customers', () => {
        document.getElementById('view-customers').classList.remove('d-none');
        CustomerModule.init();
    });

    // Müşteri detay
    NbtRouter.register('customer', (params) => {
        document.getElementById('view-customer-detail').classList.remove('d-none');
        const id = parseInt(window.location.hash.split('/')[1]);
        if (id) {
            CustomerDetailModule.init(id);
        }
    });

    // Faturalar
    NbtRouter.register('invoices', () => {
        document.getElementById('view-invoices').classList.remove('d-none');
        InvoiceModule.init();
    });

    // Ödemeler
    NbtRouter.register('payments', () => {
        document.getElementById('view-payments').classList.remove('d-none');
        PaymentModule.init();
    });

    // Projeler
    NbtRouter.register('projects', () => {
        document.getElementById('view-projects').classList.remove('d-none');
        ProjectModule.init();
    });

    // Teklifler
    NbtRouter.register('offers', () => {
        document.getElementById('view-offers').classList.remove('d-none');
        OfferModule.init();
    });

    // Sözleşmeler
    NbtRouter.register('contracts', () => {
        document.getElementById('view-contracts').classList.remove('d-none');
        ContractModule.init();
    });

    // Teminatlar
    NbtRouter.register('guarantees', () => {
        document.getElementById('view-guarantees').classList.remove('d-none');
        GuaranteeModule.init();
    });

    // Kullanıcılar
    NbtRouter.register('users', () => {
        document.getElementById('view-users').classList.remove('d-none');
        UserModule.init();
    });

    // Loglar
    NbtRouter.register('logs', () => {
        document.getElementById('view-logs').classList.remove('d-none');
        LogModule.init();
    });

}

// =============================================
// GLOBAL EVENT BINDINGS
// =============================================
function setupGlobalEvents() {
    // Logout
    document.getElementById('logoutNav')?.addEventListener('click', async (e) => {
        e.preventDefault();
        try {
            await NbtApi.post('/api/logout', {});
        } catch (err) {}
        NbtUtils.clearSession();
        window.location.href = '/login';
    });

    // Şifre değiştir
    document.querySelector('[data-action="change-password"]')?.addEventListener('click', () => {
        NbtModal.resetForm('passwordModal');
        NbtModal.open('passwordModal');
    });

    // Navbar link'leri
    document.querySelectorAll('[data-route]').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const route = link.dataset.route;
            window.location.hash = '#' + route;
        });
    });

    // Rol bazlı menü göster/gizle
    const role = NbtUtils.getRole();
    if (role !== 'superadmin' && role !== 'admin') {
        document.getElementById('systemMenu')?.classList.add('d-none');
    }

    // Yeni modül save butonları
    CustomerModule.bindEvents();
    MeetingModule.bindEvents();
    ContactModule.bindEvents();
    StampTaxModule.bindEvents();
    FileModule.bindEvents();
}

// =============================================
// INIT
// =============================================
document.addEventListener('DOMContentLoaded', () => {
    // Auth check
    if (!NbtUtils.getToken()) {
        window.location.href = '/login';
        return;
    }

    // Müşterileri yükle (global state için)
    NbtApi.get('/api/customers').then(response => {
        AppState.customers = response.data || [];
    }).catch(() => {});

    // Setup
    setupRoutes();
    setupGlobalEvents();
    PasswordModule.init();

    // URL'den route parse et
    let hash = window.location.hash.slice(1) || 'dashboard';
    
    // customer/123?tab=xxx formatını handle et
    if (hash.startsWith('customer/')) {
        // Tüm view'ları gizle ve customer detail'i göster
        document.querySelectorAll('[id^="view-"]').forEach(el => el.classList.add('d-none'));
        document.getElementById('view-customer-detail').classList.remove('d-none');
        // Parse id and tab from hash
        const [path, queryString] = hash.split('?');
        const id = parseInt(path.split('/')[1], 10);
        const params = new URLSearchParams(queryString || '');
        const tab = params.get('tab');
        if (id) CustomerDetailModule.init(id, tab);
        // Navbar'ı güncelle
        NbtRouter.updateNavbar('customer');
    } else {
        NbtRouter.navigate(hash);
    }

    // Hash change listener
    window.addEventListener('hashchange', () => {
        let hash = window.location.hash.slice(1) || 'dashboard';
        if (hash.startsWith('customer/')) {
            // Tüm view'ları gizle
            document.querySelectorAll('[id^="view-"]').forEach(el => el.classList.add('d-none'));
            document.getElementById('view-customer-detail').classList.remove('d-none');
            // Parse id and tab from hash
            const [path, queryString] = hash.split('?');
            const id = parseInt(path.split('/')[1], 10);
            const params = new URLSearchParams(queryString || '');
            const tab = params.get('tab');
            if (id) CustomerDetailModule.init(id, tab);
            // Navbar'ı güncelle - customer route için customers menüsünü active yap
            NbtRouter.updateNavbar('customer');
        } else {
            NbtRouter.navigate(hash);
        }
    });
});
