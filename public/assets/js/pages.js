/**
 * NbtProject - Sayfa Modulleri
 * =============================
 * Bu dosya, uygulamadaki tum sayfa modullerini icerir.
 * Her modul kendi CRUD islemlerini, tablo render islemlerini
 * ve event binding islemlerini bagimsiz olarak yonetir.
 * Moduller: Dashboard, Customer, Invoice, Payment, Project, Offer, Contract, Guarantee, Parameter vb.
 */

// =============================================
// GLOBAL STATE - Uygulama genelinde paylasilan veriler
// =============================================
const AppState = {
    customers: [],
    currentCustomer: null,
    currentCustomerTab: 'genel',
    alarms: [],
    calendarEvents: [],
    calendarNeedsRefresh: false, // Takvim verisi degistiginde true olur
    lastCalendarEventDate: null  // Son eklenen/guncellenen takvim kaydinin tarihi
};

// =============================================
// GLOBAL CUSTOMER SIDEBAR - Her sayfada gosterilen musteri listesi
// =============================================
const GlobalCustomerSidebar = {
    customers: [],
    displayLimit: 20,
    searchQuery: '',
    searchTimeout: null,
    _eventsBound: false,
    currentCustomerId: null,

    async init() {
        // Mevcut musteri ID'sini al (customer-detail sayfasindaysa)
        const detailEl = document.getElementById('view-customer-detail');
        if (detailEl) {
            this.currentCustomerId = parseInt(detailEl.dataset.customerId) || null;
        }
        
        await this.loadCustomers();
        this.bindEvents();
    },

    async loadCustomers(query = '') {
        const container = document.getElementById('globalCustomerList');
        if (!container) return;

        try {
            // Arama varsa backend'den ara, yoksa ilk 20'yi getir
            let url = '/api/customers?limit=' + this.displayLimit;
            if (query && query.length >= 2) {
                url += '&search=' + encodeURIComponent(query);
            }
            
            const response = await NbtApi.get(url);
            this.customers = response.data || [];
            AppState.customers = this.customers;
            this.render();
        } catch (err) {
            container.innerHTML = `<div class="text-danger small p-3">${err.message}</div>`;
        }
    },

    /**
     * Turkce ve buyuk/kucuk harf duyarsiz arama
     */
    filterCustomers(query) {
        if (!query || query.length < 2) {
            return this.customers;
        }
        
        const normalizedQuery = NbtUtils.normalizeText(query);
        
        return this.customers.filter(c => {
            const kod = NbtUtils.normalizeText(c.MusteriKodu || '');
            const unvan = NbtUtils.normalizeText(c.Unvan || '');
            return kod.includes(normalizedQuery) || unvan.includes(normalizedQuery);
        });
    },

    /**
     * Arama yap - debounce ile veya focusout ile cagrilir
     */
    doSearch() {
        const query = this.searchQuery;
        
        // 2 karakterden az ise tum listeyi goster
        if (!query || query.length < 2) {
            this.loadCustomers('');
            return;
        }
        
        // Backend'den ara
        this.loadCustomers(query);
    },

    render() {
        const container = document.getElementById('globalCustomerList');
        if (!container) return;

        if (!this.customers.length) {
            container.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="bi bi-people fs-2 d-block mb-2 opacity-50"></i>
                    <p class="mb-0">${this.searchQuery ? 'Sonuç bulunamadı' : 'Henüz müşteri eklenmemiş'}</p>
                </div>`;
            return;
        }

        let html = '<div class="list-group list-group-flush">';
        this.customers.forEach(c => {
            const musteriKodu = c.MusteriKodu || `MÜŞ-${String(c.Id).padStart(5, '0')}`;
            const isActive = parseInt(c.Id) === this.currentCustomerId;
            html += `
                <a href="/customer/${c.Id}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 ${isActive ? 'active' : ''}" 
                   data-customer-id="${c.Id}">
                    <div class="text-truncate" style="max-width: calc(100% - 20px);">
                        <div class="fw-semibold text-truncate">${NbtUtils.escapeHtml(musteriKodu)} - ${NbtUtils.escapeHtml(c.Unvan)}</div>
                        ${c.Aciklama ? `<small class="${isActive ? 'text-white-50' : 'text-muted'} d-block text-truncate">${NbtUtils.escapeHtml(c.Aciklama).substring(0, 40)}</small>` : ''}
                    </div>
                    <i class="bi bi-chevron-right flex-shrink-0"></i>
                </a>`;
        });
        html += '</div>';
        
        container.innerHTML = html;
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;

        const searchInput = document.getElementById('globalCustomerSearch');
        if (searchInput) {
            // Input olayinda: 2 saniye bekle, sonra ara
            searchInput.addEventListener('input', (e) => {
                this.searchQuery = e.target.value.trim();
                
                // Onceki timeout'u temizle
                if (this.searchTimeout) clearTimeout(this.searchTimeout);
                
                // 2 saniye bekle, sonra ara
                this.searchTimeout = setTimeout(() => {
                    this.doSearch();
                }, 2000);
            });
            
            // Focus kaybedildiginde hemen ara
            searchInput.addEventListener('blur', () => {
                // Timeout'u temizle ve hemen ara
                if (this.searchTimeout) clearTimeout(this.searchTimeout);
                this.doSearch();
            });
            
            // Enter tusuna basildiginda hemen ara
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (this.searchTimeout) clearTimeout(this.searchTimeout);
                    this.doSearch();
                }
            });
        }

        // Müşteri ekle artık external sayfa ile yapılıyor (/customer/new)
        // add-customer event listener'ı kaldırıldı

        // Musteri tiklama
        document.getElementById('globalCustomerList')?.addEventListener('click', (e) => {
            e.preventDefault();
            const link = e.target.closest('[data-customer-id]');
            if (link) {
                const customerId = parseInt(link.dataset.customerId);
                NbtRouter.navigate(`/customer/${customerId}`);
            }
        });
        
        // Yeni musteri eklendiginde listeyi guncelle
        window.addEventListener('customerAdded', () => {
            this.loadCustomers(this.searchQuery);
        });
        
        window.addEventListener('customerUpdated', () => {
            this.loadCustomers(this.searchQuery);
        });
    }
};

// =============================================
// DASHBOARD MODULU - Ana sayfa istatistikleri ve ozet bilgiler
// =============================================
const DashboardModule = {
    _eventsBound: false,
    
    async init() {
        // Takvim yenileme gerekiyorsa ve bir hedef tarih varsa
        // NbtCalendar'in currentDate'ini guncelle (baska sayfadan donerken)
        if (AppState.calendarNeedsRefresh && AppState.lastCalendarEventDate) {
            NbtCalendar.currentDate = new Date(AppState.lastCalendarEventDate);
            AppState.lastCalendarEventDate = null;
        }
        AppState.calendarNeedsRefresh = false;
        
        await Promise.all([
            this.loadStats(),
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
            NbtLogger.error('Dashboard stats yuklenemedi:', err);
        }
    },

    async loadAlarms() {
        const container = document.getElementById('dashAlarmList');
        try {
            const response = await NbtApi.get('/api/alarms');
            AppState.alarms = response.data || [];
            this.renderAlarms(AppState.alarms);
            document.getElementById('alarmCount').textContent = AppState.alarms.length;
        } catch (err) {
            AppState.alarms = [];
            this.renderAlarms([]);
            document.getElementById('alarmCount').textContent = '0';
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

        // Alarm tiplerine gore ikon ve renk
        const alarmStyles = {
            invoice: { badge: 'bg-danger', icon: 'bi-receipt' },
            calendar: { badge: 'bg-warning', icon: 'bi-calendar-event' },
            guarantee: { badge: 'bg-info', icon: 'bi-shield-check' },
            offer: { badge: 'bg-primary', icon: 'bi-file-earmark-text' },
            contract: { badge: 'bg-secondary', icon: 'bi-file-earmark-check' },
            default: { badge: 'bg-secondary', icon: 'bi-bell' }
        };

        let html = '<div class="list-group list-group-flush">';
        alarms.forEach(alarm => {
            const style = alarmStyles[alarm.type] || alarmStyles.default;
            
            html += `
                <div class="list-group-item d-flex align-items-start gap-2 cursor-pointer" data-alarm-type="${alarm.type}" data-alarm-id="${alarm.id}" style="cursor:pointer;">
                    <span class="badge ${style.badge} p-2">
                        <i class="bi ${style.icon}"></i>
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

    async loadCalendar() {
        const container = document.getElementById('dashCalendar');
        if (!container) return;
        
        try {
            // NbtCalendar'in mevcut tarihini kullan (navigasyon yapildiysa o tarihi korur)
            const month = NbtCalendar.currentDate.getMonth() + 1;
            const year = NbtCalendar.currentDate.getFullYear();
            await NbtCalendar.loadEvents(null, month, year);
        } catch (err) {
            NbtCalendar.events = [];
        }
        
        NbtCalendar.render(container, {
            events: NbtCalendar.events,
            onDayClick: (date, dayEvents) => this.openCalendarDayModal(date, dayEvents)
        });
        
        // Refresh flag'ini sifirla
        AppState.calendarNeedsRefresh = false;
    },

    openCalendarDayModal(date, events) {
        const modal = document.getElementById('calendarDayModal');
        if (!modal) return;
        
        // Tarih formatlama
        const dateObj = new Date(date);
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const formattedDate = dateObj.toLocaleDateString('tr-TR', options);
        
        document.getElementById('calendarDayModalTitle').innerHTML = `<i class="bi bi-calendar3 me-2"></i>${formattedDate}`;
        
        const eventList = document.getElementById('calendarDayEventList');
        const noEvents = document.getElementById('calendarDayNoEvents');
        
        if (!events || events.length === 0) {
            eventList.innerHTML = '';
            eventList.classList.add('d-none');
            noEvents.classList.remove('d-none');
        } else {
            noEvents.classList.add('d-none');
            eventList.classList.remove('d-none');
            
            let html = '';
            events.forEach(event => {
                const typeColors = {
                    'fatura': { bg: 'bg-danger-subtle', text: 'text-danger', icon: 'bi-receipt' },
                    'odeme': { bg: 'bg-success-subtle', text: 'text-success', icon: 'bi-cash' },
                    'teklif': { bg: 'bg-warning-subtle', text: 'text-warning', icon: 'bi-file-earmark-text' },
                    'sozlesme': { bg: 'bg-info-subtle', text: 'text-info', icon: 'bi-file-earmark-check' },
                    'teminat': { bg: 'bg-secondary-subtle', text: 'text-secondary', icon: 'bi-shield-check' },
                    'gorusme': { bg: 'bg-primary-subtle', text: 'text-primary', icon: 'bi-chat-dots' },
                    'takvim': { bg: 'bg-success-subtle', text: 'text-success', icon: 'bi-calendar-event' },
                    'default': { bg: 'bg-primary-subtle', text: 'text-primary', icon: 'bi-calendar-event' }
                };
                const typeStyle = typeColors[event.type] || typeColors.default;
                
                html += `
                    <div class="list-group-item d-flex align-items-start gap-3 py-3">
                        <span class="badge ${typeStyle.bg} ${typeStyle.text} p-2 rounded">
                            <i class="bi ${typeStyle.icon} fs-5"></i>
                        </span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">${NbtUtils.escapeHtml(event.title)}</div>
                            ${event.description ? `<div class="text-muted small">${NbtUtils.escapeHtml(event.description)}</div>` : ''}
                            ${event.customer ? `<div class="small"><i class="bi bi-building me-1"></i>${NbtUtils.escapeHtml(event.customer)}</div>` : ''}
                            ${event.time ? `<div class="small text-muted"><i class="bi bi-clock me-1"></i>${event.time}</div>` : ''}
                        </div>
                        ${event.amount ? `<span class="badge bg-dark">${NbtUtils.formatMoney(event.amount, event.currency || 'TRY')}</span>` : ''}
                    </div>
                `;
            });
            eventList.innerHTML = html;
        }
        
        NbtModal.open('calendarDayModal');
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;

        document.getElementById('dashAlarmList')?.addEventListener('click', (e) => {
            const item = e.target.closest('.list-group-item[data-alarm-type]');
            if (item) {
                const type = item.dataset.alarmType;
                // Tum alarm tiklamalarini /alarms sayfasina yonlendirme
                NbtRouter.navigate('/alarms');
            }
        });
        
        // Takvim verisi degistiginde takvimi yenile
        window.addEventListener('calendarDataChanged', async (e) => {
            const container = document.getElementById('dashCalendar');
            if (container) {
                // Eklenen/guncellenen kaydin tarihine git
                if (e.detail?.data?.TerminTarihi) {
                    NbtCalendar.currentDate = new Date(e.detail.data.TerminTarihi);
                }
                await this.loadCalendar();
            }
        });
    }
};

// =============================================
// MUSTERI MODULU
// =============================================
const CustomerModule = {
    searchQuery: '',
    _eventsBound: false,
    columnFilters: {},
    pageSize: window.APP_CONFIG?.PAGINATION_DEFAULT || 10,
    currentPage: 1,
    paginationInfo: null,
    // Filtre icin ek property'ler
    filteredData: null,
    filteredPage: 1,
    filteredPaginationInfo: null,
    // Tum musterileri cache'le (filtreleme icin)
    allCustomers: null,
    allCustomersLoading: false,
    
    async init() {
        this.pageSize = NbtParams.getPaginationDefault();
        await this.loadList();
        this.initToolbar();
        this.bindEvents();
    },

    async loadList(page = 1) {
        const container = document.getElementById('customersTableContainer');
        if (!container) return; // Standalone sayfa degilse cik
        try {
            container.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
            const response = await NbtApi.get(`/api/customers?page=${page}&limit=${this.pageSize}`);
            AppState.customers = response.data || [];
            this.paginationInfo = response.pagination || null;
            this.currentPage = page;
            // Filtre state'lerini temizle
            this.filteredData = null;
            this.filteredPaginationInfo = null;
            this.renderTable(AppState.customers);
        } catch (err) {
            container.innerHTML = `<div class="alert alert-danger m-3">${err.message}</div>`;
        }
    },

    initToolbar() {
        // Card header'daki add butonuna event listener ekleme
        const addBtn = document.querySelector('#panelCustomersList [data-action="add-customer"]');
        if (addBtn && !addBtn.hasAttribute('data-bound')) {
            addBtn.setAttribute('data-bound', 'true');
            addBtn.addEventListener('click', () => this.openModal());
        }
    },

    async applyFilters(page = 1) {
        // Eger hic filtre yoksa normal listeye don
        const hasFilters = this.searchQuery || Object.keys(this.columnFilters).length > 0;
        if (!hasFilters) {
            this.filteredData = null;
            this.filteredPaginationInfo = null;
            this.loadList(1);
            return;
        }
        
        // Tum musterileri yukle (filtreleme icin)
        if (!this.allCustomers && !this.allCustomersLoading) {
            this.allCustomersLoading = true;
            const container = document.getElementById('customersTableContainer');
            if (container) {
                container.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> <small class="text-muted ms-2">Arama yapılıyor...</small></div>';
            }
            try {
                const response = await NbtApi.get('/api/customers?page=1&limit=10000');
                this.allCustomers = response.data || [];
            } catch (err) {
                console.error('Tüm müşteriler yüklenemedi:', err);
                this.allCustomers = AppState.customers || [];
            }
            this.allCustomersLoading = false;
        }
        
        // Eger hala yukleniyorsa bekle
        if (this.allCustomersLoading) {
            setTimeout(() => this.applyFilters(page), 100);
            return;
        }
        
        let filtered = this.allCustomers || [];
        
        // Turkce karakter normalizasyonu icin yardimci fonksiyon
        const normalize = (str) => {
            return (str || '').toString()
                .toLocaleLowerCase('tr-TR')
                .replace(/ı/g, 'i')
                .replace(/İ/g, 'i')
                .replace(/ğ/g, 'g')
                .replace(/Ğ/g, 'g')
                .replace(/ü/g, 'u')
                .replace(/Ü/g, 'u')
                .replace(/ş/g, 's')
                .replace(/Ş/g, 's')
                .replace(/ö/g, 'o')
                .replace(/Ö/g, 'o')
                .replace(/ç/g, 'c')
                .replace(/Ç/g, 'c');
        };
        
        // Tarih karsilastirma icin yardimci fonksiyon (YYYY-MM-DD formatinda)
        const formatDateForCompare = (dateStr) => {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return '';
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };
        
        // Global arama islemi
        if (this.searchQuery) {
            const searchNorm = normalize(this.searchQuery);
            filtered = filtered.filter(c => 
                normalize(c.Unvan).includes(searchNorm)
            );
        }
        
        // Kolon filtreleri
        Object.keys(this.columnFilters).forEach(field => {
            const value = this.columnFilters[field];
            if (value) {
                filtered = filtered.filter(c => {
                    let cellValue = c[field];
                    
                    // Tarih alani icin ozel karsilastirma
                    if (field === 'EklemeZamani') {
                        const cellDate = formatDateForCompare(cellValue);
                        return cellDate === value; // value zaten YYYY-MM-DD formatinda
                    }
                    
                    // Diger alanlar icin normalize edilmis karsilastirma
                    return normalize(cellValue).includes(normalize(value));
                });
            }
        });
        
        // Filtrelenmis verileri sakla
        this.filteredData = filtered;
        this.filteredPage = page;
        
        // Pagination hesapla
        const total = filtered.length;
        const totalPages = Math.ceil(total / this.pageSize);
        const startIndex = (page - 1) * this.pageSize;
        const endIndex = Math.min(startIndex + this.pageSize, total);
        const pageData = filtered.slice(startIndex, endIndex);
        
        this.filteredPaginationInfo = {
            page: page,
            limit: this.pageSize,
            total: total,
            totalPages: totalPages
        };
        
        this.renderTable(pageData, true);
    },

    renderTable(data, isFiltered = false) {
        const container = document.getElementById('customersTableContainer');
        if (!container) return; // Standalone sayfa degilse cik
        const columns = [
            { field: 'MusteriKodu', label: 'Kod' },
            { field: 'Unvan', label: 'Müşteri Adı' },
            { field: 'VergiNo', label: 'Vergi No' },
            { field: 'Telefon', label: 'Telefon' },
            { field: 'EklemeZamani', label: 'Kayıt Tarihi', render: (v) => NbtUtils.formatDate(v) }
        ];

        // Header row
        const headers = columns.map(c => `<th class="bg-light px-3">${c.label}</th>`).join('') + 
            '<th class="bg-light text-center px-3" style="width:110px;">İşlem</th>';

        // Filter row - her kolon icin arama input'u
        const filterRow = columns.map(c => {
            const currentValue = this.columnFilters[c.field] || '';
            if (c.field === 'EklemeZamani') {
                // Tarih alani icin date input
                return `<th class="p-1"><input type="date" class="form-control form-control-sm" data-column-filter="${c.field}" data-table-id="customers" value="${NbtUtils.escapeHtml(currentValue)}"></th>`;
            }
            return `<th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Ara..." data-column-filter="${c.field}" data-table-id="customers" value="${NbtUtils.escapeHtml(currentValue)}"></th>`;
        }).join('') + `<th class="p-1 text-center">
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-sm btn-outline-primary" data-action="apply-filters" title="Ara"><i class="bi bi-search"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-action="clear-filters" title="Filtreleri Temizle"><i class="bi bi-x-lg"></i></button>
            </div>
        </th>`;

        // Data rows
        let rowsHtml = '';
        if (!data || data.length === 0) {
            rowsHtml = `<tr><td colspan="${columns.length + 1}" class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Müşteri bulunamadı</td></tr>`;
        } else {
            rowsHtml = data.map(row => {
                const cells = columns.map(c => {
                    let val = row[c.field];
                    if (c.render) val = c.render(val, row);
                    return `<td data-field="${c.field}" class="px-3">${val ?? '-'}</td>`;
                }).join('');
                
                return `
                    <tr data-id="${row.Id}">
                        ${cells}
                        <td class="text-center px-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary btn-sm" type="button" data-action="view" data-id="${row.Id}" title="Detay" data-can="customers.read">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-sm" type="button" data-action="delete" data-id="${row.Id}" title="Sil" data-can="customers.delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>`;
            }).join('');
        }

        container.innerHTML = `
            <div class="table-responsive p-2">
                <table class="table table-bordered table-hover table-sm mb-0" id="customersTable">
                    <thead>
                        <tr>${headers}</tr>
                        <tr class="bg-white">${filterRow}</tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            </div>`;

        // Pagination ekleme - filtrelenmis veriler icin de goster
        if (isFiltered && this.filteredPaginationInfo) {
            const { page, totalPages, total, limit } = this.filteredPaginationInfo;
            if (totalPages > 1) {
                container.innerHTML += this.renderFilteredPagination();
            } else if (total > 0) {
                // Tek sayfa ise sadece bilgi goster
                container.innerHTML += `
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light">
                        <small class="text-muted"><i class="bi bi-funnel me-1"></i>Filtrelenmiş: ${total} kayıt gösteriliyor</small>
                    </div>
                `;
            }
        } else if (!isFiltered && this.paginationInfo && this.paginationInfo.totalPages > 1) {
            container.innerHTML += this.renderPagination();
        }

        // Bind events
        this.bindTableEvents(container);
    },

    renderPagination() {
        if (!this.paginationInfo) return '';
        const { page, totalPages, total, limit } = this.paginationInfo;
        const startIndex = (page - 1) * limit;
        const endIndex = Math.min(startIndex + limit, total);

        let pageButtons = '';
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="1"><i class="bi bi-chevron-double-left"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${page - 1}"><i class="bi bi-chevron-left"></i></a></li>`;
        
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        for (let i = startPage; i <= endPage; i++) {
            pageButtons += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
        
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${page + 1}"><i class="bi bi-chevron-right"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${totalPages}"><i class="bi bi-chevron-double-right"></i></a></li>`;

        return `
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light" id="customersPagination">
                <small class="text-muted">Toplam ${total} kayıttan ${startIndex + 1}-${endIndex} arası gösteriliyor</small>
                <nav><ul class="pagination pagination-sm mb-0">${pageButtons}</ul></nav>
            </div>
        `;
    },

    renderFilteredPagination() {
        if (!this.filteredPaginationInfo) return '';
        const { page, totalPages, total, limit } = this.filteredPaginationInfo;
        const startIndex = (page - 1) * limit;
        const endIndex = Math.min(startIndex + limit, total);

        let pageButtons = '';
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="1"><i class="bi bi-chevron-double-left"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${page - 1}"><i class="bi bi-chevron-left"></i></a></li>`;
        
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        for (let i = startPage; i <= endPage; i++) {
            pageButtons += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" data-filtered-page="${i}">${i}</a></li>`;
        }
        
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${page + 1}"><i class="bi bi-chevron-right"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${totalPages}"><i class="bi bi-chevron-double-right"></i></a></li>`;

        return `
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light" id="customersFilteredPagination">
                <small class="text-muted"><i class="bi bi-funnel me-1"></i>Filtrelenmiş: Toplam ${total} kayıttan ${startIndex + 1}-${endIndex} arası gösteriliyor</small>
                <nav><ul class="pagination pagination-sm mb-0">${pageButtons}</ul></nav>
            </div>
        `;
    },

    bindTableEvents(container) {
        // View buttons
        container.querySelectorAll('[data-action="view"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                NbtRouter.navigate(`/customer/${id}`);
            });
        });

        // Pagination event binding
        container.querySelectorAll('[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const newPage = parseInt(link.dataset.page);
                if (!isNaN(newPage) && newPage !== this.currentPage) {
                    this.loadList(newPage);
                }
            });
        });

        // Filtered pagination event binding
        container.querySelectorAll('[data-filtered-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const newPage = parseInt(link.dataset.filteredPage);
                if (!isNaN(newPage) && newPage !== this.filteredPage) {
                    this.applyFilters(newPage);
                }
            });
        });

        // Apply filters button - arama butonu
        container.querySelectorAll('[data-action="apply-filters"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.columnFilters = {};
                container.querySelectorAll('[data-column-filter][data-table-id="customers"]').forEach(input => {
                    const field = input.dataset.columnFilter;
                    const value = input.value.trim();
                    if (value) {
                        this.columnFilters[field] = value;
                    }
                });
                this.applyFilters();
            });
        });

        // Enter tusu ile arama
        container.querySelectorAll('[data-column-filter][data-table-id="customers"]').forEach(input => {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    container.querySelector('[data-action="apply-filters"]')?.click();
                }
            });
        });

        // Clear filters button
        container.querySelectorAll('[data-action="clear-filters"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.columnFilters = {};
                this.searchQuery = '';
                this.allCustomers = null; // Cache'i temizle
                container.querySelectorAll('[data-column-filter][data-table-id="customers"]').forEach(input => {
                    input.value = '';
                });
                // Filtreler temizlenince normal listeye don
                this.loadList(1);
            });
        });

        // Delete buttons
        container.querySelectorAll('[data-action="delete"]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = parseInt(btn.dataset.id);
                const customer = AppState.customers.find(c => parseInt(c.Id, 10) === id);
                const customerInfo = customer ? `
                    <div class="text-start">
                        <p class="mb-2"><strong>Müşteri:</strong> ${NbtUtils.escapeHtml(customer.Unvan || '-')}</p>
                        <p class="mb-2"><strong>Kod:</strong> ${NbtUtils.escapeHtml(customer.MusteriKodu || '-')}</p>
                        <p class="mb-2"><strong>Vergi No:</strong> ${NbtUtils.escapeHtml(customer.VergiNo || '-')}</p>
                        <p class="mb-0"><strong>Telefon:</strong> ${NbtUtils.escapeHtml(customer.Telefon || '-')}</p>
                    </div>
                ` : '';
                
                const result = await Swal.fire({
                    title: 'Müşteri Silme Onayı',
                    html: `<div class="text-start">
                        <p class="mb-2">Bu müşteriyi silmek istediğinizden emin misiniz?</p>
                        ${customerInfo ? `<div class="bg-light rounded p-3 small mb-3">${customerInfo}</div>` : ''}
                        <p class="text-danger mb-0"><i class="bi bi-exclamation-triangle me-1"></i>Bu işlem geri alınamaz ve ilişkili tüm veriler silinecektir!</p>
                    </div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="bi bi-trash me-1"></i>Evet, Sil',
                    cancelButtonText: 'İptal',
                    reverseButtons: true
                });
                
                if (!result.isConfirmed) return;
                
                try {
                    await NbtApi.delete(`/api/customers/${id}`);
                    NbtToast.success('Müşteri silindi');
                    await this.loadList();
                } catch (err) {
                    NbtToast.error(err.message);
                }
            });
        });
    },

    openModal(id = null) {
        NbtModal.resetForm('customerModal');
        const editId = id ? parseInt(id, 10) : null;
        document.getElementById('customerModalTitle').textContent = editId ? 'Müşteri Düzenle' : 'Yeni Müşteri';
        document.getElementById('customerId').value = editId || '';

        document.getElementById('customerUnvan').value = '';
        document.getElementById('customerAciklama').value = '';
        document.getElementById('customerMusteriKodu').value = '';
        document.getElementById('customerVergiDairesi').value = '';
        document.getElementById('customerVergiNo').value = '';
        document.getElementById('customerMersisNo').value = '';
        document.getElementById('customerTelefon').value = '';
        document.getElementById('customerFaks').value = '';
        document.getElementById('customerWeb').value = '';
        document.getElementById('customerIl').value = '';
        document.getElementById('customerIlce').value = '';
        document.getElementById('customerAdres').value = '';

        if (editId) {
            const customer = AppState.customers.find(c => parseInt(c.Id, 10) === editId);
            if (customer) {
                document.getElementById('customerUnvan').value = customer.Unvan || '';
                document.getElementById('customerAciklama').value = customer.Aciklama || '';
                document.getElementById('customerMusteriKodu').value = customer.MusteriKodu || '';
                document.getElementById('customerVergiDairesi').value = customer.VergiDairesi || '';
                document.getElementById('customerVergiNo').value = customer.VergiNo || '';
                document.getElementById('customerMersisNo').value = customer.MersisNo || '';
                document.getElementById('customerTelefon').value = customer.Telefon || '';
                document.getElementById('customerFaks').value = customer.Faks || '';
                document.getElementById('customerWeb').value = customer.Web || '';
                document.getElementById('customerIl').value = customer.Il || '';
                document.getElementById('customerIlce').value = customer.Ilce || '';
                document.getElementById('customerAdres').value = customer.Adres || '';
            } else {
                NbtApi.get('/api/customers').then(response => {
                    AppState.customers = response.data || [];
                    const found = AppState.customers.find(c => parseInt(c.Id, 10) === editId);
                    if (found) {
                        document.getElementById('customerUnvan').value = found.Unvan || '';
                        document.getElementById('customerAciklama').value = found.Aciklama || '';
                        document.getElementById('customerMusteriKodu').value = found.MusteriKodu || '';
                        document.getElementById('customerVergiDairesi').value = found.VergiDairesi || '';
                        document.getElementById('customerVergiNo').value = found.VergiNo || '';
                        document.getElementById('customerMersisNo').value = found.MersisNo || '';
                        document.getElementById('customerTelefon').value = found.Telefon || '';
                        document.getElementById('customerFaks').value = found.Faks || '';
                        document.getElementById('customerWeb').value = found.Web || '';
                        document.getElementById('customerIl').value = found.Il || '';
                        document.getElementById('customerIlce').value = found.Ilce || '';
                        document.getElementById('customerAdres').value = found.Adres || '';
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
            Aciklama: document.getElementById('customerAciklama').value.trim() || null,
            MusteriKodu: document.getElementById('customerMusteriKodu').value.trim() || null,
            VergiDairesi: document.getElementById('customerVergiDairesi').value.trim() || null,
            VergiNo: document.getElementById('customerVergiNo').value.trim() || null,
            MersisNo: document.getElementById('customerMersisNo').value.trim() || null,
            Telefon: document.getElementById('customerTelefon').value.trim() || null,
            Faks: document.getElementById('customerFaks').value.trim() || null,
            Web: document.getElementById('customerWeb').value.trim() || null,
            Il: document.getElementById('customerIl').value.trim() || null,
            Ilce: document.getElementById('customerIlce').value.trim() || null,
            Adres: document.getElementById('customerAdres').value.trim() || null
        };

        NbtModal.clearError('customerModal');
        
        // Client-side validation with proper field mapping
        const validation = NbtModal.validateForm('customerModal', {
            customerUnvan: { required: true, min: 2, max: 150, label: 'Ünvan' },
            customerVergiDairesi: { required: true, max: 50, label: 'Vergi Dairesi' },
            customerVergiNo: { required: true, pattern: /^\d{10,11}$/, patternMessage: 'Vergi No 10-11 hane sayısal olmalı', label: 'Vergi No' }
        });
        
        if (!validation.valid) {
            NbtModal.showError('customerModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }

        NbtModal.setLoading('customerModal', true);
        try {
            let shouldReload = false;
            if (id) {
                await NbtApi.put(`/api/customers/${id}`, data);
                NbtToast.success('Müşteri güncellendi');
                NbtModal.close('customerModal');
                await this.loadList();
                shouldReload = true;
            } else {
                const result = await NbtApi.post('/api/customers', data);
                NbtToast.success('Müşteri eklendi');
                NbtModal.close('customerModal');
                // Yeni eklenen musteri sayfasina yonlendir
                if (result && result.id) {
                    window.location.href = `/customer/${result.id}`;
                    return;
                }
                await this.loadList();
                shouldReload = true;
            }
            // Dashboard sayfasindaysa musteri listesini guncelleme
            const dashboardView = document.getElementById('view-dashboard');
            if (dashboardView && !dashboardView.classList.contains('d-none')) {
                DashboardModule.loadCustomers();
            }
            if (shouldReload) {
                window.location.reload();
                return;
            }
        } catch (err) {
            // API'den gelen validation hatalarını parse et
            if (err.response && err.response.errors) {
                const fieldMapping = {
                    'Unvan': 'customerUnvan',
                    'VergiDairesi': 'customerVergiDairesi',
                    'VergiNo': 'customerVergiNo',
                    'MusteriKodu': 'customerMusteriKodu',
                    'MersisNo': 'customerMersisNo',
                    'Telefon': 'customerTelefon',
                    'Faks': 'customerFaks',
                    'Web': 'customerWeb',
                    'Il': 'customerIl',
                    'Ilce': 'customerIlce',
                    'Adres': 'customerAdres',
                    'Aciklama': 'customerAciklama'
                };
                NbtModal.showValidationErrors('customerModal', err.response.errors, fieldMapping);
                NbtModal.showError('customerModal', err.response.message || err.message);
            } else {
                NbtModal.showError('customerModal', err.message);
            }
        } finally {
            NbtModal.setLoading('customerModal', false);
        }
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        
        document.getElementById('btnSaveCustomer')?.addEventListener('click', () => this.save());
        
        document.getElementById('applyCustomerFilter')?.addEventListener('click', () => {
            const dateFilter = document.getElementById('filterCustomerDate')?.value;
            let filtered = AppState.customers;
            if (dateFilter) {
                filtered = filtered.filter(c => c.EklemeZamani && c.EklemeZamani.startsWith(dateFilter));
            }
            this.renderTable(filtered);
        });
        document.getElementById('clearCustomerFilter')?.addEventListener('click', () => {
            document.getElementById('filterCustomerDate').value = '';
            this.renderTable(AppState.customers);
        });
    }
};

// =============================================
// MUSTERI DETAY MODULU (12 TAB)
// =============================================
const CustomerDetailModule = {
    _eventsBound: false,
    customerId: null,
    activeTab: 'bilgi',
    filters: {},
    pageSize: window.APP_CONFIG?.PAGINATION_DEFAULT || 10,
    // Kolon filtreleri icin - her tablo icin ayri
    columnFilters: {},
    // Filtreleme icin tum veriler cache
    allData: {},
    allDataLoading: {},
    filteredPaginationInfo: {},

    /**
     * Ortak proje select doldurma fonksiyonu
     * Tum moduller bu fonksiyonu kullanarak proje select'lerini doldurur
     */
    async populateProjectSelect(selectId, selectedValue = null) {
        const select = document.getElementById(selectId);
        if (!select) {
            NbtLogger.warn('populateProjectSelect: select element bulunamadı:', selectId);
            return;
        }
        select.innerHTML = '<option value="">Proje Seçiniz...</option>';
        
        const musteriId = this.customerId;
        if (!musteriId) {
            NbtLogger.warn('populateProjectSelect: customerId boş!');
            return;
        }
        
        try {
            const response = await NbtApi.get(`/api/projects?musteri_id=${musteriId}`);
            let projects = response.data || [];
            
            // Pasif durumdaki projeleri filtrele (secilen proje hariç)
            // forceRefresh=true ile her zaman guncel pasif durumlarini al
            const pasifKodlar = await NbtParams.getPasifDurumKodlari('proje', true);
            
            projects = projects.filter(p => {
                // Eger proje zaten secili ise gostermeye devam et
                if (selectedValue && parseInt(p.Id) === parseInt(selectedValue)) {
                    return true;
                }
                // Pasif duruma sahip projeleri gizle
                return !pasifKodlar.includes(String(p.Durum));
            });
            
            projects.forEach(p => {
                const option = document.createElement('option');
                option.value = p.Id;
                option.textContent = NbtUtils.escapeHtml(p.ProjeAdi || '');
                if (selectedValue && parseInt(p.Id) === parseInt(selectedValue)) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        } catch (err) {
            NbtLogger.error('Projeler yüklenemedi:', err);
        }
    },
    currentPages: {}, // Her tablo icin current page: { projects: 1, invoices: 1, ... }
    paginationInfo: {},
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
        files: [],
        calendars: []
    },

    tabConfig: {
        bilgi: { title: 'Müşteri Bilgisi', icon: 'bi-info-circle', endpoint: null },
        kisiler: { title: 'Kişiler', icon: 'bi-people', endpoint: '/api/contacts', key: 'contacts' },
        gorusme: { title: 'Görüşmeler', icon: 'bi-chat-dots', endpoint: '/api/meetings', key: 'meetings' },
        projeler: { title: 'Projeler', icon: 'bi-kanban', endpoint: '/api/projects', key: 'projects' },
        teklifler: { title: 'Teklifler', icon: 'bi-file-earmark-text', endpoint: '/api/offers', key: 'offers' },
        sozlesmeler: { title: 'Sözleşmeler', icon: 'bi-file-text', endpoint: '/api/contracts', key: 'contracts' },
        takvim: { title: 'Takvim', icon: 'bi-calendar3', endpoint: '/api/takvim', key: 'calendars' },
        damgavergisi: { title: 'Damga Vergisi', icon: 'bi-percent', endpoint: '/api/stamp-taxes', key: 'stampTaxes' },
        teminatlar: { title: 'Teminatlar', icon: 'bi-shield-check', endpoint: '/api/guarantees', key: 'guarantees' },
        faturalar: { title: 'Faturalar', icon: 'bi-receipt', endpoint: '/api/invoices', key: 'invoices' },
        odemeler: { title: 'Ödemeler', icon: 'bi-cash-stack', endpoint: '/api/payments', key: 'payments' },
        dosyalar: { title: 'Dosyalar', icon: 'bi-folder', endpoint: '/api/files', key: 'files' }
    },

    tabPermissions: {
        bilgi: 'customers.read',
        kisiler: 'contacts.read',
        gorusme: 'meetings.read',
        projeler: 'projects.read',
        teklifler: 'offers.read',
        sozlesmeler: 'contracts.read',
        takvim: 'calendar.read',
        damgavergisi: 'stamp_taxes.read',
        teminatlar: 'guarantees.read',
        faturalar: 'invoices.read',
        odemeler: 'payments.read',
        dosyalar: 'files.read'
    },

    async init(customerId, initialTab = null) {
        this.pageSize = NbtParams.getPaginationDefault();
        this.customerId = parseInt(customerId, 10);
        if (isNaN(this.customerId) || this.customerId <= 0) {
            NbtToast.error('Geçersiz müşteri ID');
            NbtRouter.navigate('/dashboard');
            return;
        }
        
        // Server-side render edilen izinli tab listesini al
        const detailView = document.getElementById('view-customer-detail');
        if (detailView) {
            const allowedTabsAttr = detailView.dataset.allowedTabs;
            const defaultTabAttr = detailView.dataset.defaultTab;
            if (allowedTabsAttr) {
                try {
                    this._serverAllowedTabs = JSON.parse(allowedTabsAttr);
                } catch (e) {
                    this._serverAllowedTabs = null;
                }
            }
            if (defaultTabAttr) {
                this._serverDefaultTab = defaultTabAttr;
            }
        }
        
        // Durum parametrelerini onceden yukle (badge'ler icin)
        await Promise.all([
            NbtParams.getStatuses('proje'),
            NbtParams.getStatuses('teklif'),
            NbtParams.getStatuses('sozlesme'),
            NbtParams.getStatuses('teminat'),
            NbtParams.getCurrencies()
        ]);
        
        await this.loadCustomer();
        this.bindEvents();
        // Tab permission'lar artik server-side uygulandigi icin bu gereksiz, yine de guvenlik icin calistir
        this.applyTabPermissions();
        // URL'den gelen tab parametresi ONCELIKLI - yoksa server default, o da yoksa 'bilgi'
        const preferredTab = (initialTab && this.tabConfig[initialTab]) ? initialTab : (this._serverDefaultTab || 'bilgi');
        const tabToOpen = this.resolveInitialTab(preferredTab);
        if (!tabToOpen) {
            this.renderNoAccess();
            return;
        }
        this.switchTab(tabToOpen);
    },

    async loadCustomer() {
        try {
            let customers = AppState.customers;
            if (!customers || customers.length === 0) {
                const response = await NbtApi.get('/api/customers');
                customers = response.data || [];
                AppState.customers = customers;
            }
            
            this.data.customer = customers.find(c => parseInt(c.Id, 10) === this.customerId);
            
            if (!this.data.customer) {
                NbtToast.error('Müşteri bulunamadı');
                NbtRouter.navigate('/dashboard');
                return;
            }

            document.getElementById('customerDetailTitle').textContent = this.data.customer.Unvan;
            const musteriKodu = this.data.customer.MusteriKodu || `MÜŞ-${String(this.customerId).padStart(5, '0')}`;
            document.getElementById('customerDetailCode').textContent = musteriKodu;
            AppState.currentCustomer = this.data.customer;

            const loadTasks = [];
            if (this.isTabPermitted('projeler')) loadTasks.push(this.loadRelatedData('projects', '/api/projects'));
            if (this.isTabPermitted('faturalar')) loadTasks.push(this.loadRelatedData('invoices', '/api/invoices'));
            if (this.isTabPermitted('odemeler')) loadTasks.push(this.loadRelatedData('payments', '/api/payments'));
            if (this.isTabPermitted('teklifler')) loadTasks.push(this.loadRelatedData('offers', '/api/offers'));
            if (this.isTabPermitted('sozlesmeler')) loadTasks.push(this.loadRelatedData('contracts', '/api/contracts'));
            if (this.isTabPermitted('teminatlar')) loadTasks.push(this.loadRelatedData('guarantees', '/api/guarantees'));
            if (this.isTabPermitted('gorusme')) loadTasks.push(this.loadRelatedData('meetings', '/api/meetings'));
            if (this.isTabPermitted('kisiler')) loadTasks.push(this.loadRelatedData('contacts', '/api/contacts'));
            if (this.isTabPermitted('damgavergisi')) loadTasks.push(this.loadRelatedData('stampTaxes', '/api/stamp-taxes'));
            if (this.isTabPermitted('dosyalar')) loadTasks.push(this.loadRelatedData('files', '/api/files'));
            if (this.isTabPermitted('takvim')) loadTasks.push(this.loadRelatedData('calendars', '/api/takvim'));

            await Promise.all(loadTasks);
        } catch (err) {
            NbtToast.error(err.message);
        }
    },

    isTabPermitted(tab) {
        // Server'dan gelen izinli tab listesi varsa onu kullan (guvenilir)
        if (this._serverAllowedTabs && Array.isArray(this._serverAllowedTabs)) {
            return this._serverAllowedTabs.includes(tab);
        }
        // Fallback: client-side permission kontrolu
        const perm = this.tabPermissions[tab];
        if (!perm) return false;
        return NbtPermission.can(perm);
    },

    resolveInitialTab(preferredTab) {
        if (preferredTab && this.isTabPermitted(preferredTab)) return preferredTab;
        // Server'dan gelen izinli tab listesi varsa onu kullan
        if (this._serverAllowedTabs && Array.isArray(this._serverAllowedTabs) && this._serverAllowedTabs.length > 0) {
            return this._serverAllowedTabs[0];
        }
        // Fallback: client-side permission kontrolu
        const allowedTabs = Object.keys(this.tabConfig).filter(key => this.isTabPermitted(key));
        return allowedTabs.length > 0 ? allowedTabs[0] : null;
    },

    applyTabPermissions() {
        document.querySelectorAll('#customerTabs .nav-link').forEach(btn => {
            const tabKey = btn.dataset.tab;
            if (!this.isTabPermitted(tabKey)) {
                btn.remove();
            }
        });
    },

    renderNoAccess() {
        const container = document.getElementById('customerTabContent');
        if (container) {
            container.innerHTML = `
                <div class="alert alert-warning mb-0">
                    Bu sayfayı görüntüleme yetkiniz yok.
                </div>
            `;
        }
    },

    async loadRelatedData(key, endpoint, page = 1) {
        try {
            const limit = this.pageSize;
            const response = await NbtApi.get(`${endpoint}?musteri_id=${this.customerId}&page=${page}&limit=${limit}`);
            
            // Musteri unvanini ekle (inspect modal icin gerekli)
            const musteriUnvan = this.data.customer?.Unvan || '';
            const dataWithCustomer = (response.data || []).map(item => ({
                ...item,
                MusteriUnvan: item.MusteriUnvan || musteriUnvan
            }));
            
            if (response.pagination) {
                this.data[key] = dataWithCustomer;
                this.paginationInfo[key] = response.pagination;
            } else {
                this.data[key] = dataWithCustomer;
                this.paginationInfo[key] = {
                    page: 1,
                    limit: this.data[key].length,
                    total: this.data[key].length,
                    totalPages: 1,
                    hasNext: false,
                    hasPrev: false
                };
            }
        } catch (err) {
            this.data[key] = [];
            this.paginationInfo[key] = { page: 1, limit: 10, total: 0, totalPages: 0, hasNext: false, hasPrev: false };
        }
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        
        document.querySelectorAll('#customerTabs .nav-link').forEach(btn => {
            btn.addEventListener('click', () => this.switchTab(btn.dataset.tab));
        });

        // Müşteri düzenleme artık external sayfa ile yapılıyor (/customer/{id}/edit)
        // btnEditCustomer event listener'ı kaldırıldı
    },

    switchTab(tab) {
        if (!this.isTabPermitted(tab)) {
            const fallback = this.resolveInitialTab(tab);
            if (!fallback) {
                this.renderNoAccess();
                return;
            }
            tab = fallback;
        }

        this.activeTab = tab;
        
        document.querySelectorAll('#customerTabs .nav-link').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tab);
        });

        // Icerik render islemi
        const container = document.getElementById('customerTabContent');
        container.innerHTML = this.renderTabContent(tab);
        this.bindTabEvents(container, tab);
        NbtPermission.applyToElements(container);
        
        // Filtre select'lerini doldur (status ve currency)
        this.populateFilterSelects(container);
    },
    
    // Filtre select'lerini parametrelerden ve sabit degerlerden doldur
    async populateFilterSelects(container) {
        // Status select'lerini doldur
        for (const select of container.querySelectorAll('select[data-status-type]')) {
            const statusType = select.dataset.statusType;
            const tableId = select.dataset.tableId;
            
            // Parametreleri yukle (cache'de yoksa)
            let statuses = await NbtParams.getStatuses(statusType);
            
            // Mevcut secili degeri sakla
            const currentValue = this.columnFilters[tableId]?.[select.dataset.columnFilter] || '';
            
            // Secenekleri ekle
            let options = '<option value="">Tümü</option>';
            (statuses || []).forEach(s => {
                const selected = String(currentValue) === String(s.Kod) ? 'selected' : '';
                options += `<option value="${s.Kod}" ${selected}>${NbtUtils.escapeHtml(s.Etiket)}</option>`;
            });
            select.innerHTML = options;
        }
        
        // Currency (doviz) select'lerini parametrelerden doldur
        const currencies = await NbtParams.getCurrencies();
        container.querySelectorAll('select[data-currency-select]').forEach(select => {
            const currentValue = this.columnFilters[select.dataset.tableId]?.[select.dataset.columnFilter] || '';
            
            let options = '<option value="">Tümü</option>';
            (currencies || []).forEach(c => {
                const selected = currentValue === c.Kod ? 'selected' : '';
                options += `<option value="${c.Kod}" ${selected}>${c.Kod}</option>`;
            });
            select.innerHTML = options;
        });
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

    getModuleFromAddType(addType) {
        const map = {
            contact: 'contacts',
            meeting: 'meetings',
            project: 'projects',
            offer: 'offers',
            contract: 'contracts',
            calendar: 'calendar',
            stamptax: 'stamp_taxes',
            guarantee: 'guarantees',
            invoice: 'invoices',
            payment: 'payments',
            file: 'files'
        };
        return map[addType] || null;
    },

    getModuleFromTableId(tableId) {
        const map = {
            projeler: 'projects',
            teklifler: 'offers',
            sozlesmeler: 'contracts',
            teminatlar: 'guarantees',
            gorusme: 'meetings',
            kisiler: 'contacts',
            damgavergisi: 'stamp_taxes',
            takvim: 'calendar',
            faturalar: 'invoices',
            odemeler: 'payments',
            dosyalar: 'files'
        };
        return map[tableId] || null;
    },

    getPermissionAttr(moduleName, action) {
        if (!moduleName || !action) return '';
        return ` data-can="${moduleName}.${action}"`;
    },

    // ========== STANDART PANEL YAPISI ==========
    renderPanel(config) {
        const { id, title, icon, filterFields, columns, data, addType, emptyMsg } = config;
        const moduleName = this.getModuleFromAddType(addType);
        const addPermissionAttr = this.getPermissionAttr(moduleName, 'create');

        const tableHtml = this.renderDataTable(id, columns, data, emptyMsg);

        return `
            <div class="card shadow-sm" id="panel_${id}">
                <div class="card-header py-2 d-flex justify-content-between align-items-center bg-white">
                    <span class="fw-semibold"><i class="bi ${icon} me-2"></i>${title}</span>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-primary btn-sm" data-add="${addType}"${addPermissionAttr}>
                            <i class="bi bi-plus-lg me-1"></i>Ekle
                        </button>
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
                </div>
                <div class="card-body p-0" id="body_${id}">
                    ${tableHtml}
                </div>
            </div>`;
    },

    renderDataTable(id, columns, data, emptyMsg, isFiltered = false, config = {}) {
        const moduleName = this.getModuleFromTableId(id);
        const viewPermissionAttr = this.getPermissionAttr(moduleName, 'read');
        const editPermissionAttr = this.getPermissionAttr(moduleName, 'update');
        const deletePermissionAttr = this.getPermissionAttr(moduleName, 'delete');
        const headers = columns.map(c => `<th class="bg-light px-3">${c.label}</th>`).join('') + '<th class="bg-light text-center px-3" style="width:130px;">İşlem</th>';

        // Filter row - her kolon icin gelismis filtreler
        const tableFilters = this.columnFilters[id] || {};
        const filterRow = columns.map(c => {
            const currentValue = tableFilters[c.field] || '';
            const startValue = tableFilters[c.field + '_start'] || '';
            const endValue = tableFilters[c.field + '_end'] || '';
            
            // Tarih alanlari icin cift date input (baslangic-bitis) - yan yana
            const isDateField = c.isDate || c.field.toLowerCase().includes('tarih') || c.field === 'TeklifTarihi' || c.field === 'TerminTarihi' || c.field === 'SozlesmeTarihi' || c.field === 'OlusturmaZamani';
            if (isDateField) {
                return `<th class="p-1" style="min-width:200px;">
                    <div class="d-flex gap-1">
                        <input type="date" class="form-control form-control-sm" data-column-filter="${c.field}_start" data-table-id="${id}" value="${NbtUtils.escapeHtml(startValue)}" title="Başlangıç">
                        <input type="date" class="form-control form-control-sm" data-column-filter="${c.field}_end" data-table-id="${id}" value="${NbtUtils.escapeHtml(endValue)}" title="Bitiş">
                    </div>
                </th>`;
            }
            
            // Durum alanlari icin select (parametrelerden doldur)
            if (c.field === 'Durum' && c.statusType) {
                return `<th class="p-1">
                    <select class="form-select form-select-sm" data-column-filter="${c.field}" data-table-id="${id}" data-status-type="${c.statusType}">
                        <option value="">Tümü</option>
                    </select>
                </th>`;
            }
            
            // Doviz alanlari icin select
            if (c.field === 'ParaBirimi' || c.field === 'DovizCinsi') {
                return `<th class="p-1">
                    <select class="form-select form-select-sm" data-column-filter="${c.field}" data-table-id="${id}" data-currency-select="true">
                        <option value="">Tümü</option>
                    </select>
                </th>`;
            }
            
            // Tamamlandi alani icin select
            if (c.field === 'Tamamlandi') {
                return `<th class="p-1">
                    <select class="form-select form-select-sm" data-column-filter="${c.field}" data-table-id="${id}">
                        <option value="">Tümü</option>
                        <option value="1" ${currentValue === '1' ? 'selected' : ''}>Tamamlandı</option>
                        <option value="0" ${currentValue === '0' ? 'selected' : ''}>Tamamlanmadı</option>
                    </select>
                </th>`;
            }
            
            return `<th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Ara..." data-column-filter="${c.field}" data-table-id="${id}" value="${NbtUtils.escapeHtml(currentValue)}"></th>`;
        }).join('') + `<th class="p-1 text-center">
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-sm btn-outline-primary" data-action="apply-filters" data-table-id="${id}" title="Ara"><i class="bi bi-search"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-action="clear-filters" data-table-id="${id}" title="Filtreleri Temizle"><i class="bi bi-x-lg"></i></button>
            </div>
        </th>`;

        if (!data || !data.length) {
            return `
                <div class="table-responsive p-2">
                    <table class="table table-bordered table-hover table-sm mb-0" id="table_${id}">
                        <thead>
                            <tr>${headers}</tr>
                            <tr class="bg-white">${filterRow}</tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="${columns.length + 1}" class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>${emptyMsg || 'Kayıt bulunamadı'}</td></tr>
                        </tbody>
                    </table>
                </div>`;
        }
        
        const dataKey = this.getDataKeyFromTableId(id);
        
        // Filtrelenmis veriler icin ayri pagination
        let paginationInfo, currentPage, totalItems, totalPages, startIndex, endIndex;
        
        if (isFiltered && this.filteredPaginationInfo[id]) {
            paginationInfo = this.filteredPaginationInfo[id];
            currentPage = paginationInfo.page;
            totalItems = paginationInfo.total;
            totalPages = paginationInfo.totalPages;
            startIndex = (paginationInfo.page - 1) * paginationInfo.limit;
            endIndex = Math.min(startIndex + paginationInfo.limit, totalItems);
        } else {
            paginationInfo = this.paginationInfo[dataKey] || null;
            currentPage = paginationInfo ? paginationInfo.page : 1;
            totalItems = paginationInfo ? paginationInfo.total : data.length;
            totalPages = paginationInfo ? paginationInfo.totalPages : 1;
            startIndex = paginationInfo ? ((paginationInfo.page - 1) * paginationInfo.limit) : 0;
            endIndex = paginationInfo ? Math.min(startIndex + paginationInfo.limit, totalItems) : data.length;
        }
        
        const rows = data.map(row => {
            const cells = columns.map(c => {
                let val = row[c.field];
                if (c.render) val = c.render(val, row);
                return `<td data-field="${c.field}" class="px-3">${val ?? '-'}</td>`;
            }).join('');
            
            // Dosya indirme butonu (DosyaYolu varsa goster)
            const hasFile = row.DosyaYolu || row.DosyaId;
            const downloadBtn = hasFile ? `
                <button class="btn btn-outline-info btn-sm" type="button" data-action="download" data-id="${row.Id}" data-file="${row.DosyaYolu || ''}" title="İndir"${viewPermissionAttr}>
                    <i class="bi bi-download"></i>
                </button>` : '';
            
            return `
                <tr data-id="${row.Id}">
                    ${cells}
                    <td class="text-center px-3">
                        <div class="btn-group btn-group-sm">
                            ${downloadBtn}
                            <button class="btn btn-outline-primary btn-sm" type="button" data-action="view" data-id="${row.Id}" title="Detay"${viewPermissionAttr}>
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" type="button" data-action="edit" data-id="${row.Id}" title="Düzenle"${editPermissionAttr}>
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-sm" type="button" data-action="delete" data-id="${row.Id}" title="Sil"${deletePermissionAttr}>
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
        }).join('');

        const paginationHtml = isFiltered 
            ? this.renderFilteredPaginationDetail(id, currentPage, totalPages, totalItems, startIndex, endIndex)
            : this.renderPagination(id, currentPage, totalPages, totalItems, startIndex, endIndex);

        return `
            <div class="table-responsive p-2">
                <table class="table table-bordered table-hover table-sm mb-0" id="table_${id}">
                    <thead>
                        <tr>${headers}</tr>
                        <tr class="bg-white">${filterRow}</tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
            ${paginationHtml}`;
    },

    // Filtrelenmis veriler icin pagination
    renderFilteredPaginationDetail(tableId, currentPage, totalPages, totalItems, startIndex, endIndex) {
        if (totalItems <= this.pageSize) {
            // Tek sayfa - sadece bilgi goster
            return `
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light">
                    <small class="text-muted"><i class="bi bi-funnel me-1"></i>Filtrelenmiş: ${totalItems} kayıt gösteriliyor</small>
                </div>
            `;
        }
        
        let pageButtons = '';
        pageButtons += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="1" data-table-id="${tableId}"><i class="bi bi-chevron-double-left"></i></a></li>`;
        pageButtons += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${currentPage - 1}" data-table-id="${tableId}"><i class="bi bi-chevron-left"></i></a></li>`;
        
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        for (let i = startPage; i <= endPage; i++) {
            pageButtons += `<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="#" data-filtered-page="${i}" data-table-id="${tableId}">${i}</a></li>`;
        }
        
        pageButtons += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${currentPage + 1}" data-table-id="${tableId}"><i class="bi bi-chevron-right"></i></a></li>`;
        pageButtons += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${totalPages}" data-table-id="${tableId}"><i class="bi bi-chevron-double-right"></i></a></li>`;

        return `
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light" id="filteredPagination_${tableId}">
                <small class="text-muted"><i class="bi bi-funnel me-1"></i>Filtrelenmiş: Toplam ${totalItems} kayıttan ${startIndex + 1}-${endIndex} arası gösteriliyor</small>
                <nav><ul class="pagination pagination-sm mb-0">${pageButtons}</ul></nav>
            </div>
        `;
    },

    renderPagination(tableId, currentPage, totalPages, totalItems, startIndex, endIndex) {
        const dataKey = this.getDataKeyFromTableId(tableId);
        const paginationInfo = this.paginationInfo[dataKey] || null;
        const limit = paginationInfo ? paginationInfo.limit : this.pageSize;
        
        if (totalItems <= limit) {
            return ''; // Tek sayfa varsa pagination gosterilmez
        }
        
        let pageButtons = '';
        
        // Ilk ve onceki butonlar
        pageButtons += `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="1" data-table-id="${tableId}" title="İlk Sayfa"><i class="bi bi-chevron-double-left"></i></a>
            </li>
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage - 1}" data-table-id="${tableId}" title="Önceki"><i class="bi bi-chevron-left"></i></a>
            </li>
        `;
        
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        
        for (let i = startPage; i <= endPage; i++) {
            pageButtons += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}" data-table-id="${tableId}">${i}</a>
                </li>
            `;
        }
        
        pageButtons += `
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage + 1}" data-table-id="${tableId}" title="Sonraki"><i class="bi bi-chevron-right"></i></a>
            </li>
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${totalPages}" data-table-id="${tableId}" title="Son Sayfa"><i class="bi bi-chevron-double-right"></i></a>
            </li>
        `;
        
        return `
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light" id="pagination_${tableId}">
                <small class="text-muted">
                    Toplam ${totalItems} kayıttan ${startIndex + 1}-${endIndex} arası gösteriliyor
                </small>
                <nav aria-label="Sayfa navigasyonu">
                    <ul class="pagination pagination-sm mb-0">
                        ${pageButtons}
                    </ul>
                </nav>
            </div>
        `;
    },

    async goToPage(tableId, page) {
        const dataKey = this.getDataKeyFromTableId(tableId);
        if (!dataKey) return;
        
        const endpointMap = {
            'projects': '/api/projects',
            'invoices': '/api/invoices',
            'payments': '/api/payments',
            'offers': '/api/offers',
            'contracts': '/api/contracts',
            'guarantees': '/api/guarantees',
            'meetings': '/api/meetings',
            'contacts': '/api/contacts',
            'stampTaxes': '/api/stamp-taxes',
            'files': '/api/files'
        };
        
        const endpoint = endpointMap[dataKey];
        if (!endpoint) return;
        
        const panelBody = document.getElementById(`body_${tableId}`);
        if (panelBody) {
            panelBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Yükleniyor...</span></div></div>';
        }
        
        await this.loadRelatedData(dataKey, endpoint, page);
        this.currentPages[tableId] = page;
        
        if (panelBody) {
            const config = this.getColumnConfig(tableId);
            if (config) {
                const data = this.data[dataKey] || [];
                panelBody.innerHTML = this.renderDataTable(tableId, config.columns, data, config.emptyMsg);
                
                // Filtre select'lerini doldur
                await this.populateFilterSelects(panelBody);
                
                const container = document.getElementById('customerTabContent');
                if (container) {
                    container.querySelectorAll(`[data-table-id="${tableId}"][data-page]`).forEach(link => {
                        link.addEventListener('click', (e) => {
                            e.preventDefault();
                            const newPage = parseInt(link.dataset.page);
                            if (!isNaN(newPage)) {
                                this.goToPage(tableId, newPage);
                            }
                        });
                    });
                }
            }
        }
    },
    
    getDataKeyFromTableId(tableId) {
        const mapping = {
            'projects': 'projects',
            'projeler': 'projects',
            'invoices': 'invoices',
            'faturalar': 'invoices',
            'payments': 'payments',
            'odemeler': 'payments',
            'offers': 'offers',
            'teklifler': 'offers',
            'contracts': 'contracts',
            'sozlesmeler': 'contracts',
            'guarantees': 'guarantees',
            'teminatlar': 'guarantees',
            'meetings': 'meetings',
            'gorusme': 'meetings',
            'contacts': 'contacts',
            'kisiler': 'contacts',
            'stampTaxes': 'stampTaxes',
            'damgavergisi': 'stampTaxes',
            'files': 'files',
            'dosyalar': 'files',
            'takvim': 'calendars',
            'calendars': 'calendars'
        };
        return mapping[tableId] || null;
    },

    // ========== TAB RENDER FONKSIYONLARI ==========

    renderBilgi() {
        const c = this.data.customer;
        
        // Cari ozet verilerini async olarak yukle (DOM render sonrasi)
        setTimeout(() => this.loadCariOzet(), 50);
        
        return `
            <div class="row">
                <!-- Sol Kolon: Müşteri Bilgileri -->
                <div class="col-lg-6 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-header py-2 bg-white">
                            <span class="fw-semibold"><i class="bi bi-info-circle me-2"></i>Müşteri Bilgisi</span>
                        </div>
                        <div class="card-body">
                            <h6 class="text-muted border-bottom pb-2 mb-3"><i class="bi bi-building me-1"></i>Temel Bilgiler</h6>
                            
                            <div class="row mb-2">
                                <div class="col-5 fw-bold text-muted small">ID</div>
                                <div class="col-7">${c.Id || '-'}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold text-muted small">Müşteri Kodu</div>
                                <div class="col-7">${NbtUtils.escapeHtml(c.MusteriKodu || '-')}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold text-muted small">Unvan</div>
                                <div class="col-7">${NbtUtils.escapeHtml(c.Unvan || '-')}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-5 fw-bold text-muted small">Kayıt Tarihi</div>
                                <div class="col-7">${NbtUtils.formatDate(c.EklemeZamani) || '-'}</div>
                            </div>
                            
                            <h6 class="text-muted border-bottom pb-2 mb-3 mt-3"><i class="bi bi-percent me-1"></i>Vergi Bilgileri</h6>
                            
                            <div class="row mb-2">
                                <div class="col-5 fw-bold text-muted small">Vergi Dairesi</div>
                                <div class="col-7">${NbtUtils.escapeHtml(c.VergiDairesi || '-')}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold text-muted small">Vergi Numarası</div>
                                <div class="col-7">${NbtUtils.escapeHtml(c.VergiNo || '-')}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-5 fw-bold text-muted small">Mersis No</div>
                                <div class="col-7">${NbtUtils.escapeHtml(c.MersisNo || '-')}</div>
                            </div>
                            
                            <h6 class="text-muted border-bottom pb-2 mb-3 mt-3"><i class="bi bi-telephone me-1"></i>İletişim</h6>
                            
                            <div class="row mb-2">
                                <div class="col-5 fw-bold text-muted small">Telefon</div>
                                <div class="col-7">${NbtUtils.escapeHtml(c.Telefon || '-')}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold text-muted small">Faks</div>
                                <div class="col-7">${NbtUtils.escapeHtml(c.Faks || '-')}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-5 fw-bold text-muted small">Web Sitesi</div>
                                <div class="col-7">${c.Web ? `<a href="${NbtUtils.escapeHtml(c.Web)}" target="_blank" class="text-truncate d-block">${NbtUtils.escapeHtml(c.Web)}</a>` : '-'}</div>
                            </div>
                            
                            <h6 class="text-muted border-bottom pb-2 mb-3 mt-3"><i class="bi bi-geo-alt me-1"></i>Adres</h6>
                            
                            <div class="row mb-2">
                                <div class="col-5 fw-bold text-muted small">İl</div>
                                <div class="col-7">${NbtUtils.escapeHtml(c.Il || '-')}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold text-muted small">İlçe</div>
                                <div class="col-7">${NbtUtils.escapeHtml(c.Ilce || '-')}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold text-muted small">Adres</div>
                                <div class="col-7 small">${NbtUtils.escapeHtml(c.Adres || '-')}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold text-muted small">Açıklama</div>
                                <div class="col-7 small">${NbtUtils.escapeHtml(c.Aciklama || '-')}</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sağ Kolon: Cari Özet -->
                <div class="col-lg-6 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-header py-2 bg-white d-flex justify-content-between align-items-center">
                            <span class="fw-semibold"><i class="bi bi-calculator me-2"></i>Cari Özet</span>
                            <span class="badge bg-primary" id="cariOzetToplamFatura">0 Fatura</span>
                        </div>
                        <div class="card-body p-0" id="cariOzetContainer">
                            <div class="text-center py-5">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                <div class="small text-muted mt-2">Yükleniyor...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-3 g-2">
                <div class="col-6 col-md-2">
                    <div class="card text-center p-3 border-0 shadow-sm"><div class="fs-4 fw-bold text-primary">${this.data.projects.length}</div><small class="text-muted">Proje</small></div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="card text-center p-3 border-0 shadow-sm"><div class="fs-4 fw-bold text-success">${this.data.offers.length}</div><small class="text-muted">Teklif</small></div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="card text-center p-3 border-0 shadow-sm"><div class="fs-4 fw-bold text-info">${this.data.contracts.length}</div><small class="text-muted">Sözleşme</small></div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="card text-center p-3 border-0 shadow-sm"><div class="fs-4 fw-bold text-warning">${this.data.guarantees.length}</div><small class="text-muted">Teminat</small></div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="card text-center p-3 border-0 shadow-sm"><div class="fs-4 fw-bold text-secondary">${this.data.invoices.length}</div><small class="text-muted">Fatura</small></div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="card text-center p-3 border-0 shadow-sm"><div class="fs-4 fw-bold text-success">${this.data.payments.length}</div><small class="text-muted">Ödeme</small></div>
                </div>
            </div>`;
    },
    
    async loadCariOzet() {
        const container = document.getElementById('cariOzetContainer');
        const badge = document.getElementById('cariOzetToplamFatura');
        if (!container) return;
        
        try {
            const response = await NbtApi.get(`/api/customers/${this.customerId}/cari-ozet`);
            const data = response.data || [];
            
            if (data.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                        <p class="mb-0">Henüz fatura kaydı bulunmuyor</p>
                    </div>`;
                if (badge) badge.textContent = '0 Fatura';
                return;
            }
            
            // Yil bazli grupla
            const yilGruplari = {};
            let toplamFatura = 0;
            
            // Doviz bazli toplamlar
            const dovizToplamlar = {};
            
            data.forEach(item => {
                const yil = item.Yil;
                if (!yilGruplari[yil]) {
                    yilGruplari[yil] = [];
                }
                yilGruplari[yil].push(item);
                toplamFatura += parseInt(item.FaturaAdedi) || 0;
                
                // Doviz bazli toplam hesapla
                const doviz = item.DovizCinsi || 'TL';
                if (!dovizToplamlar[doviz]) {
                    dovizToplamlar[doviz] = { tutar: 0, odenen: 0, kalan: 0 };
                }
                dovizToplamlar[doviz].tutar += parseFloat(item.ToplamTutar) || 0;
                dovizToplamlar[doviz].odenen += parseFloat(item.ToplamOdenen) || 0;
                dovizToplamlar[doviz].kalan += parseFloat(item.ToplamKalan) || 0;
            });
            
            if (badge) badge.textContent = `${toplamFatura} Fatura`;
            
            // Yillari azalan sirada sirala
            const yillar = Object.keys(yilGruplari).sort((a, b) => b - a);
            
            let html = `
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3">Yıl</th>
                                <th class="text-end px-3">Fatura Tutarı</th>
                                <th class="text-end px-3">Ödenen</th>
                                <th class="text-end px-3">Kalan</th>
                            </tr>
                        </thead>
                        <tbody>`;
            
            yillar.forEach(yil => {
                const items = yilGruplari[yil];
                const faturaAdedi = items.reduce((sum, i) => sum + (parseInt(i.FaturaAdedi) || 0), 0);
                
                // Her doviz icin ayri satir
                items.forEach((item, idx) => {
                    const doviz = item.DovizCinsi || 'TL';
                    const dovizSimge = this.getDovizSimge(doviz);
                    const tutar = parseFloat(item.ToplamTutar) || 0;
                    const odenen = parseFloat(item.ToplamOdenen) || 0;
                    const kalan = parseFloat(item.ToplamKalan) || 0;
                    
                    html += `
                        <tr>
                            ${idx === 0 ? `<td class="px-3 fw-bold align-middle" rowspan="${items.length}">
                                <span class="badge bg-secondary">${yil}</span>
                                <small class="text-muted d-block">${faturaAdedi} fatura</small>
                            </td>` : ''}
                            <td class="text-end px-3">
                                <span class="fw-semibold">${NbtUtils.formatNumber(tutar)}</span>
                                <span class="text-muted ms-1">${dovizSimge}</span>
                            </td>
                            <td class="text-end px-3 text-success">
                                <span>${NbtUtils.formatNumber(odenen)}</span>
                                <span class="text-muted ms-1">${dovizSimge}</span>
                            </td>
                            <td class="text-end px-3 ${kalan > 0 ? 'text-danger fw-bold' : 'text-success'}">
                                <span>${NbtUtils.formatNumber(kalan)}</span>
                                <span class="text-muted ms-1">${dovizSimge}</span>
                            </td>
                        </tr>`;
                });
            });
            
            // Toplam satirlari ekle - her doviz icin ayri
            const dovizler = Object.keys(dovizToplamlar).sort();
            dovizler.forEach((doviz, idx) => {
                const t = dovizToplamlar[doviz];
                const dovizSimge = this.getDovizSimge(doviz);
                
                html += `
                    <tr class="table-secondary">
                        ${idx === 0 ? `<td class="px-3 fw-bold align-middle" rowspan="${dovizler.length}">
                            <span class="badge bg-primary">TOPLAM</span>
                        </td>` : ''}
                        <td class="text-end px-3">
                            <span class="fw-bold">${NbtUtils.formatNumber(t.tutar)}</span>
                            <span class="ms-1">${dovizSimge}</span>
                        </td>
                        <td class="text-end px-3 text-success">
                            <span class="fw-bold">${NbtUtils.formatNumber(t.odenen)}</span>
                            <span class="ms-1">${dovizSimge}</span>
                        </td>
                        <td class="text-end px-3 ${t.kalan > 0 ? 'text-danger' : 'text-success'}">
                            <span class="fw-bold">${NbtUtils.formatNumber(t.kalan)}</span>
                            <span class="ms-1">${dovizSimge}</span>
                        </td>
                    </tr>`;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>`;
            
            container.innerHTML = html;
            
        } catch (err) {
            container.innerHTML = `
                <div class="text-center py-4 text-danger">
                    <i class="bi bi-exclamation-triangle fs-3 d-block mb-2"></i>
                    <p class="mb-0 small">Veriler yüklenemedi</p>
                </div>`;
            NbtLogger.error('Cari özet yüklenemedi:', err);
        }
    },
    
    getDovizSimge(doviz) {
        const simgeler = {
            'TL': '₺',
            'TRY': '₺',
            'USD': '$',
            'EUR': '€',
            'GBP': '£',
            'CHF': '₣'
        };
        return simgeler[doviz] || doviz;
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
                { field: 'DahiliNo', label: 'Dahili' },
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
                { field: 'ProjeAdi', label: 'Proje' },
                { field: 'Tarih', label: 'Tarih', render: v => NbtUtils.formatDate(v), isDate: true },
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
                { field: 'Durum', label: 'Durum', render: v => this.getStatusBadge(v, 'project'), statusType: 'proje' }
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
                { field: 'Konu', placeholder: 'Konu', width: 3 }
            ],
            columns: [
                { field: 'ProjeAdi', label: 'Proje' },
                { field: 'TeklifTarihi', label: 'Teklif Tarihi', render: v => NbtUtils.formatDate(v), isDate: true },
                { field: 'Konu', label: 'Konu' },
                { field: 'Tutar', label: 'Tutar', render: v => NbtUtils.formatNumber(v) },
                { field: 'ParaBirimi', label: 'Döviz Cinsi', render: v => v || 'TL' },
                { field: 'Durum', label: 'Durum', render: v => this.getStatusBadge(v, 'offer'), statusType: 'teklif' }
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
            filterFields: [],
            columns: [
                { field: 'ProjeAdi', label: 'Proje' },
                { field: 'SozlesmeTarihi', label: 'Sözleşme Tarihi', render: v => NbtUtils.formatDate(v), isDate: true },
                { field: 'Tutar', label: 'Tutar', render: v => NbtUtils.formatNumber(v) },
                { field: 'ParaBirimi', label: 'Döviz', render: v => v || 'TL' },
                { field: 'Durum', label: 'Durum', render: v => this.getStatusBadge(v, 'contract'), statusType: 'sozlesme' }
            ],
            data: this.data.contracts
        });
    },

    renderTakvim() {
        // Takvim verilerine tamamlandi durumu ekle
        const calendars = (this.data.calendars || []).map(item => {
            const terminTarihi = item.TerminTarihi ? new Date(item.TerminTarihi) : null;
            const bugun = new Date();
            bugun.setHours(0, 0, 0, 0);
            const tamamlandi = terminTarihi && terminTarihi < bugun ? 1 : 0;
            return { ...item, Tamamlandi: tamamlandi };
        });
        
        return this.renderPanel({
            id: 'takvim',
            title: 'Takvim',
            icon: 'bi-calendar3',
            addType: 'calendar',
            emptyMsg: 'Henüz takvim kaydı yok',
            columns: [
                { field: 'ProjeAdi', label: 'Proje' },
                { field: 'TerminTarihi', label: 'Termin Tarihi', render: v => NbtUtils.formatDate(v), isDate: true },
                { field: 'Ozet', label: 'Özet' },
                { field: 'Tamamlandi', label: 'Durum', render: v => v === 1 
                    ? '<span class="badge bg-success">Tamamlandı</span>' 
                    : '<span class="badge bg-warning text-dark">Tamamlanmadı</span>' 
                }
            ],
            data: calendars
        });
    },

    renderDamgaVergisi() {
        return this.renderPanel({
            id: 'damgavergisi',
            title: 'Damga Vergisi',
            icon: 'bi-percent',
            addType: 'stamptax',
            emptyMsg: 'Henüz damga vergisi kaydı yok',
            filterFields: [
                { field: 'Tarih', type: 'date', placeholder: 'Tarih', width: 2 }
            ],
            columns: [
                { field: 'ProjeAdi', label: 'Proje' },
                { field: 'Tarih', label: 'Tarih', render: v => NbtUtils.formatDate(v), isDate: true },
                { field: 'Tutar', label: 'Tutar', render: v => NbtUtils.formatNumber(v) },
                { field: 'DovizCinsi', label: 'Döviz', render: v => v || 'TL' },
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
                { field: 'Tur', placeholder: 'Tür', width: 2 },
                { field: 'Durum', type: 'select', placeholder: 'Durum', width: 2, options: [
                    { value: '1', label: 'Bekliyor' },
                    { value: '2', label: 'İade Edildi' },
                    { value: '3', label: 'Tahsil Edildi' },
                    { value: '4', label: 'Yandı' }
                ]}
            ],
            columns: [
                { field: 'ProjeAdi', label: 'Proje' },
                { field: 'Tur', label: 'Tür' },
                { field: 'Tutar', label: 'Tutar', render: v => NbtUtils.formatNumber(v) },
                { field: 'ParaBirimi', label: 'Döviz', render: v => v || 'TL' },
                { field: 'BankaAdi', label: 'Banka' },
                { field: 'TerminTarihi', label: 'Termin Tarihi', render: v => NbtUtils.formatDate(v), isDate: true },
                { field: 'EklemeZamani', label: 'İşlem Tarihi', render: v => NbtUtils.formatDate(v, 'long'), isDate: true },
                { field: 'Durum', label: 'Durum', render: v => this.getStatusBadge(v, 'guarantee'), statusType: 'teminat' }
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
                { field: 'ProjeAdi', label: 'Proje' },
                { field: 'Tarih', label: 'Tarih', render: v => NbtUtils.formatDate(v), isDate: true },
                { field: 'Tutar', label: 'Tutar', render: v => NbtUtils.formatNumber(v) },
                { field: 'DovizCinsi', label: 'Döviz', render: v => v || 'TRY' },
                { field: 'OdenenTutar', label: 'Ödenen', render: v => NbtUtils.formatNumber(v || 0) },
                { field: 'Kalan', label: 'Kalan', render: (v, row) => {
                    const kalan = parseFloat(v) || 0;
                    const cls = kalan > 0 ? 'text-danger fw-bold' : 'text-success';
                    return `<span class="${cls}">${NbtUtils.formatNumber(kalan)}</span>`;
                }}
            ],
            data: data
        });
    },

    // ========== ODEME TAB - FATURA ID DROPDOWN ==========
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
                { field: 'ProjeAdi', label: 'Proje' },
                { field: 'Tarih', label: 'Tarih', render: v => NbtUtils.formatDate(v), isDate: true },
                { field: 'FaturaId', label: 'Fatura', render: (v, row) => {
                    if (!v) return '-';
                    return `FT${v}/${NbtUtils.formatDate(row.FaturaTarihi)} [${NbtUtils.formatMoney(row.FaturaTutari, row.FaturaDovizi)}]`;
                }},
                { field: 'Tutar', label: 'Tutar', render: v => NbtUtils.formatNumber(v) },
                { field: 'ParaBirimi', label: 'Döviz', render: v => v || 'TRY' },
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
                { field: 'OlusturmaZamani', type: 'date', placeholder: 'Tarih', width: 2 }
            ],
            columns: [
                { field: 'ProjeAdi', label: 'Proje' },
                { field: 'DosyaAdi', label: 'Dosya Adı' },
                { field: 'OlusturmaZamani', label: 'Yüklenme', render: v => NbtUtils.formatDate(v), isDate: true },
                { field: 'Aciklama', label: 'Açıklama' }
            ],
            data: this.data.files || []
        });
    },

    // ========== YARDIMCI FONKSIYONLAR ==========

    getStatusBadge(status, type) {
        // Entity ismini API formatina donustur
        const entityMap = { project: 'proje', offer: 'teklif', contract: 'sozlesme', guarantee: 'teminat' };
        const entity = entityMap[type] || type;
        const cacheKey = `durum_${entity}`;
        const statuses = NbtParams._cache.statuses[cacheKey] || [];
        
        // Parametrelerden durum bul
        const found = statuses.find(s => s.Kod == status);
        if (found) {
            const badge = found.Deger || 'secondary';
            const textClass = (badge === 'warning' || badge === 'light') ? ' text-dark' : '';
            return `<span class="badge bg-${badge}${textClass}">${NbtUtils.escapeHtml(found.Etiket)}</span>`;
        }
        
        // Fallback - eski sabit degerler (cache henuz yuklenmediyse)
        const fallback = {
            project: { 1: ['Aktif', 'success'], 2: ['Tamamlandı', 'info'], 3: ['İptal', 'danger'] },
            offer: { 0: ['Taslak', 'secondary'], 1: ['Gönderildi', 'warning'], 2: ['Onaylandı', 'success'], 3: ['Reddedildi', 'danger'] },
            contract: { 1: ['Aktif', 'success'], 2: ['Pasif', 'secondary'], 3: ['İptal', 'danger'] },
            guarantee: { 1: ['Bekliyor', 'warning'], 2: ['İade Edildi', 'info'], 3: ['Tahsil Edildi', 'success'], 4: ['Yandı', 'danger'] }
        };
        const config = fallback[type]?.[status] || ['Bilinmiyor', 'secondary'];
        return `<span class="badge bg-${config[1]}">${config[0]}</span>`;
    },

    bindTabEvents(container, tab) {
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
                    NbtRouter.navigate('/dashboard');
                }
            });
        });
        
        container.querySelectorAll('[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(link.dataset.page);
                const tableId = link.dataset.tableId;
                if (!isNaN(page) && tableId) {
                    this.goToPage(tableId, page);
                }
            });
        });

        container.querySelector('#customerEditForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                await NbtApi.put(`/api/customers/${this.customerId}`, {
                    Unvan: document.getElementById('editUnvan').value.trim(),
                    Aciklama: document.getElementById('editAciklama').value.trim() || null,
                    MusteriKodu: document.getElementById('editMusteriKodu').value.trim() || null,
                    VergiDairesi: document.getElementById('editVergiDairesi').value.trim() || null,
                    VergiNo: document.getElementById('editVergiNo').value.trim() || null,
                    MersisNo: document.getElementById('editMersisNo').value.trim() || null,
                    Telefon: document.getElementById('editTelefon').value.trim() || null,
                    Faks: document.getElementById('editFaks').value.trim() || null,
                    Web: document.getElementById('editWeb').value.trim() || null,
                    Adres: document.getElementById('editAdres').value.trim() || null
                });
                NbtToast.success('Müşteri bilgileri güncellendi');
                await this.loadCustomer();
            } catch (err) {
                NbtToast.error(err.message);
            }
        });

        if (container._delegationBound) return;
        container._delegationBound = true;

        container.addEventListener('click', (e) => {
            const addBtn = e.target.closest('[data-add]');
            if (addBtn) {
                e.preventDefault();
                this.openAddModal(addBtn.dataset.add);
                return;
            }

            const searchBtn = e.target.closest('[data-search]');
            if (searchBtn) {
                e.preventDefault();
                e.stopPropagation();
                this.applyFilter(searchBtn.dataset.search);
                return;
            }

            const actionEl = e.target.closest('[data-action]');
            if (actionEl) {
                e.preventDefault();
                const action = actionEl.dataset.action;
                
                if (action === 'apply-filters') {
                    const tableId = actionEl.dataset.tableId;
                    this.applyColumnFilters(tableId);
                    return;
                }
                
                if (action === 'clear-filters') {
                    const tableId = actionEl.dataset.tableId;
                    this.clearColumnFilters(tableId);
                    return;
                }
                
                const id = parseInt(actionEl.dataset.id);
                // BUG FIX: closure 'tab' yerine aktif tab kullan
                this.handleTableAction(action, id, this.activeTab);
                return;
            }
        });
        
        container.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                const input = e.target.closest('[id^="filter_"]');
                if (input) {
                    e.preventDefault();
                    e.stopPropagation();
                    const parts = input.id.split('_');
                    if (parts.length >= 2) {
                        this.applyFilter(parts[1]);
                    }
                }
                
                const columnFilter = e.target.closest('[data-column-filter]');
                if (columnFilter) {
                    e.preventDefault();
                    this.applyColumnFilters(columnFilter.dataset.tableId);
                }
            }
        });

        if (tab === 'takvim') {
            setTimeout(() => {
                const calContainer = document.getElementById('customerCalendar');
                if (calContainer) {
                    const meetings = this.data.meetings || [];
                    const calendarEvents = meetings.map(m => ({
                        id: m.Id,
                        date: m.Tarih?.split('T')[0] || m.Tarih,
                        title: m.Konu,
                        description: m.Notlar || '',
                        customerId: m.MusteriId,
                        type: 'meeting'
                    }));
                    
                    if (typeof NbtCalendar !== 'undefined' && NbtCalendar.render) {
                        NbtCalendar.render(calContainer, {
                            events: calendarEvents,
                            onDayClick: (date, events) => {
                                if (events.length) {
                                    const eventList = events.map(e => `• ${e.title}`).join('\n');
                                    NbtToast.info(`${date}: ${events.length} görüşme\n${eventList}`);
                                }
                            }
                        });
                    } else {
                        this.renderSimpleCalendar(calContainer, calendarEvents);
                    }
                }
            }, 100);
        }
    },

    renderSimpleCalendar(container, events) {
        const eventsByDate = {};
        events.forEach(e => {
            const date = e.date;
            if (!eventsByDate[date]) eventsByDate[date] = [];
            eventsByDate[date].push(e);
        });
        
        const today = new Date();
        const year = today.getFullYear();
        const month = today.getMonth();
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        
        const monthNames = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 
                           'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
        const dayNames = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
        
        let html = `
            <div class="simple-calendar">
                <div class="text-center mb-3">
                    <h5 class="mb-0">${monthNames[month]} ${year}</h5>
                </div>
                <div class="row row-cols-7 text-center fw-bold border-bottom pb-2 mb-2">
                    ${dayNames.map(d => `<div class="col small">${d}</div>`).join('')}
                </div>
                <div class="row row-cols-7 text-center g-1">
        `;
        
        const startDay = (firstDay.getDay() + 6) % 7;
        for (let i = 0; i < startDay; i++) {
            html += '<div class="col p-2"></div>';
        }
        
        for (let day = 1; day <= lastDay.getDate(); day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const dayEvents = eventsByDate[dateStr] || [];
            const isToday = day === today.getDate();
            const hasEvents = dayEvents.length > 0;
            
            html += `
                <div class="col p-1">
                    <div class="rounded ${isToday ? 'bg-primary text-white' : ''} ${hasEvents ? 'border border-success' : ''} p-2" 
                         style="min-height: 60px; cursor: ${hasEvents ? 'pointer' : 'default'}"
                         ${hasEvents ? `title="${dayEvents.map(e => e.title).join(', ')}"` : ''}>
                        <div class="small ${isToday ? '' : 'text-muted'}">${day}</div>
                        ${hasEvents ? `<div class="badge bg-success mt-1" style="font-size: 0.6rem">${dayEvents.length}</div>` : ''}
                    </div>
                </div>
            `;
        }
        
        html += `
                </div>
                <div class="mt-3">
                    <h6 class="text-muted">Bu Ayki Görüşmeler</h6>
                    <div class="list-group list-group-flush">
        `;
        
        const thisMonthEvents = events.filter(e => {
            const eventDate = new Date(e.date);
            return eventDate.getMonth() === month && eventDate.getFullYear() === year;
        }).sort((a, b) => new Date(a.date) - new Date(b.date));
        
        if (thisMonthEvents.length === 0) {
            html += '<div class="text-muted small py-2">Bu ay görüşme bulunmuyor</div>';
        } else {
            thisMonthEvents.forEach(e => {
                html += `
                    <div class="list-group-item list-group-item-action py-2 px-0 border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-primary me-2">${e.date}</span>
                                <span class="fw-semibold">${e.title}</span>
                            </div>
                        </div>
                        ${e.description ? `<small class="text-muted">${e.description}</small>` : ''}
                    </div>
                `;
            });
        }
        
        html += `
                    </div>
                </div>
            </div>
        `;
        
        container.innerHTML = html;
    },

    async applyFilter(panelId) {
        const panel = document.getElementById(`panel_${panelId}`);
        if (!panel) return;

        const filters = {};
        panel.querySelectorAll(`[id^="filter_${panelId}_"]`).forEach(input => {
            const field = input.id.replace(`filter_${panelId}_`, '');
            if (input.value) filters[field] = input.value.toLowerCase();
        });
        
        this.filters[panelId] = filters;

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

        let filtered = this.data[dataKey] || [];
        for (const [field, value] of Object.entries(filters)) {
            filtered = filtered.filter(item => {
                const itemVal = String(item[field] || '').toLowerCase();
                return itemVal.includes(value);
            });
        }
        
        const tableBody = panel.querySelector(`#body_${panelId}`);
        if (tableBody) {
            const columnConfig = this.getColumnConfig(panelId);
            tableBody.innerHTML = this.renderDataTable(panelId, columnConfig.columns, filtered, columnConfig.emptyMsg);
            // Filtre select'lerini doldur
            await this.populateFilterSelects(tableBody);
        }
    },
    
    normalizeText(text) {
        if (!text) return '';
        return text
            .toLowerCase()
            .replace(/ı/g, 'i')
            .replace(/İ/g, 'i')
            .replace(/ğ/g, 'g')
            .replace(/Ğ/g, 'g')
            .replace(/ü/g, 'u')
            .replace(/Ü/g, 'u')
            .replace(/ş/g, 's')
            .replace(/Ş/g, 's')
            .replace(/ö/g, 'o')
            .replace(/Ö/g, 'o')
            .replace(/ç/g, 'c')
            .replace(/Ç/g, 'c');
    },
    
    async applyColumnFilters(tableId, page = 1) {
        const table = document.getElementById(`table_${tableId}`);
        if (!table) return;
        
        // Input degerlerini al
        const filters = {};
        table.querySelectorAll('[data-column-filter]').forEach(input => {
            const field = input.dataset.columnFilter;
            const value = input.value.trim();
            if (value) filters[field] = value;
        });
        
        // Filtre varsa kaydet
        this.columnFilters[tableId] = filters;
        
        // Eger hic filtre yoksa normal listeye don
        if (Object.keys(filters).length === 0) {
            this.allData[tableId] = null;
            this.filteredPaginationInfo[tableId] = null;
            const dataKey = this.getDataKeyFromTableId(tableId);
            this.switchTab(this.activeTab);
            return;
        }
        
        const dataKey = this.getDataKeyFromTableId(tableId);
        const panelBody = document.getElementById(`body_${tableId}`);
        
        // Tum verileri yukle (filtreleme icin)
        if (!this.allData[tableId] && !this.allDataLoading[tableId]) {
            this.allDataLoading[tableId] = true;
            if (panelBody) {
                panelBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> <small class="text-muted ms-2">Arama yapılıyor...</small></div>';
            }
            
            const endpointMap = {
                'projects': '/api/projects',
                'invoices': '/api/invoices',
                'payments': '/api/payments',
                'offers': '/api/offers',
                'contracts': '/api/contracts',
                'guarantees': '/api/guarantees',
                'meetings': '/api/meetings',
                'contacts': '/api/contacts',
                'stampTaxes': '/api/stamp-taxes',
                'files': '/api/files',
                'calendars': '/api/takvim'
            };
            
            const endpoint = endpointMap[dataKey];
            if (endpoint) {
                try {
                    const response = await NbtApi.get(`${endpoint}?musteri_id=${this.customerId}&page=1&limit=10000`);
                    let data = response.data || [];
                    
                    // Takvim icin Tamamlandi alanini hesapla
                    if (dataKey === 'calendars') {
                        const bugun = new Date();
                        bugun.setHours(0, 0, 0, 0);
                        data = data.map(item => {
                            const terminTarihi = item.TerminTarihi ? new Date(item.TerminTarihi) : null;
                            const tamamlandi = terminTarihi && terminTarihi < bugun ? 1 : 0;
                            return { ...item, Tamamlandi: tamamlandi };
                        });
                    }
                    
                    this.allData[tableId] = data;
                } catch (err) {
                    console.error('Tüm veriler yüklenemedi:', err);
                    this.allData[tableId] = this.data[dataKey] || [];
                }
            } else {
                this.allData[tableId] = this.data[dataKey] || [];
            }
            this.allDataLoading[tableId] = false;
        }
        
        // Hala yukleniyorsa bekle
        if (this.allDataLoading[tableId]) {
            setTimeout(() => this.applyColumnFilters(tableId, page), 100);
            return;
        }
        
        let filtered = this.allData[tableId] || [];
        
        // Kolon filtreleri uygula
        Object.keys(filters).forEach(field => {
            const value = filters[field];
            if (!value) return;
            
            // Tarih araligi baslangic filtresi (_start ile bitiyor)
            if (field.endsWith('_start')) {
                const baseField = field.replace('_start', '');
                filtered = filtered.filter(item => {
                    let cellValue = item[baseField];
                    if (!cellValue) return false;
                    const cellDate = NbtUtils.formatDateForCompare(cellValue);
                    return cellDate >= value;
                });
                return;
            }
            
            // Tarih araligi bitis filtresi (_end ile bitiyor)
            if (field.endsWith('_end')) {
                const baseField = field.replace('_end', '');
                filtered = filtered.filter(item => {
                    let cellValue = item[baseField];
                    if (!cellValue) return false;
                    const cellDate = NbtUtils.formatDateForCompare(cellValue);
                    return cellDate <= value;
                });
                return;
            }
            
            // Tamamlandi filtresi
            if (field === 'Tamamlandi') {
                const bugun = new Date();
                bugun.setHours(0, 0, 0, 0);
                filtered = filtered.filter(item => {
                    const terminTarihi = item.TerminTarihi ? new Date(item.TerminTarihi) : null;
                    const tamamlandi = terminTarihi && terminTarihi < bugun ? '1' : '0';
                    return tamamlandi === value;
                });
                return;
            }
            
            // Select alanlari icin exact match (Durum, DovizCinsi, ParaBirimi vb.)
            const selectFields = ['Durum', 'DovizCinsi', 'ParaBirimi', 'Tur'];
            if (selectFields.includes(field)) {
                filtered = filtered.filter(item => {
                    const cellValue = String(item[field] ?? '');
                    return cellValue === value;
                });
                return;
            }
            
            // Normal alanlar icin filtre
            filtered = filtered.filter(item => {
                let cellValue = item[field];
                
                // Tarih alani icin ozel karsilastirma
                const isDateField = field.toLowerCase().includes('tarih') || field === 'TeklifTarihi' || field === 'TerminTarihi' || field === 'SozlesmeTarihi';
                if (isDateField) {
                    const cellDate = NbtUtils.formatDateForCompare(cellValue);
                    return cellDate === value;
                }
                
                // Diger alanlar icin normalize edilmis karsilastirma
                return NbtUtils.normalizeText(cellValue).includes(NbtUtils.normalizeText(value));
            });
        });
        
        // Pagination hesapla
        const total = filtered.length;
        const totalPages = Math.ceil(total / this.pageSize);
        const startIndex = (page - 1) * this.pageSize;
        const endIndex = Math.min(startIndex + this.pageSize, total);
        const pageData = filtered.slice(startIndex, endIndex);
        
        this.filteredPaginationInfo[tableId] = {
            page: page,
            limit: this.pageSize,
            total: total,
            totalPages: totalPages
        };
        
        // Tabloyu render et
        const config = this.getColumnConfig(tableId);
        if (config && panelBody) {
            panelBody.innerHTML = this.renderDataTable(tableId, config.columns, pageData, config.emptyMsg, true);
            this.bindFilteredPaginationEvents(panelBody, tableId);
            // Filtre select'lerini yeniden doldur
            await this.populateFilterSelects(panelBody);
        }
    },
    
    bindFilteredPaginationEvents(container, tableId) {
        container.querySelectorAll('[data-filtered-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const newPage = parseInt(link.dataset.filteredPage);
                if (!isNaN(newPage)) {
                    this.applyColumnFilters(tableId, newPage);
                }
            });
        });
    },
    
    clearColumnFilters(tableId) {
        const table = document.getElementById(`table_${tableId}`);
        if (!table) return;
        
        // Input'lari temizle
        table.querySelectorAll('[data-column-filter]').forEach(input => {
            input.value = '';
        });
        
        // Filtre state'lerini temizle
        this.columnFilters[tableId] = {};
        this.allData[tableId] = null;
        this.filteredPaginationInfo[tableId] = null;
        
        // Normal listeyi yeniden yukle
        this.switchTab(this.activeTab);
    },
    
    getColumnConfig(panelId) {
        const configs = {
            kisiler: {
                columns: [
                    { field: 'AdSoyad', label: 'Ad Soyad' },
                    { field: 'Unvan', label: 'Ünvan' },
                    { field: 'Telefon', label: 'Telefon' },
                    { field: 'DahiliNo', label: 'Dahili' },
                    { field: 'Email', label: 'E-posta' }
                ],
                emptyMsg: 'Kayıt bulunamadı'
            },
            gorusme: {
                columns: [
                    { field: 'ProjeAdi', label: 'Proje' },
                    { field: 'Tarih', label: 'Tarih', render: v => NbtUtils.formatDate(v), isDate: true },
                    { field: 'Konu', label: 'Konu' },
                    { field: 'Notlar', label: 'Notlar' },
                    { field: 'Kisi', label: 'Görüşülen Kişi' }
                ],
                emptyMsg: 'Kayıt bulunamadı'
            },
            projeler: {
                columns: [
                    { field: 'ProjeAdi', label: 'Proje Adı' },
                    { field: 'Durum', label: 'Durum', render: v => this.getStatusBadge(v, 'project'), statusType: 'proje' }
                ],
                emptyMsg: 'Kayıt bulunamadı'
            },
            teklifler: {
                columns: [
                    { field: 'ProjeAdi', label: 'Proje' },
                    { field: 'Konu', label: 'Konu' },
                    { field: 'Tutar', label: 'Tutar', render: v => NbtUtils.formatNumber(v) },
                    { field: 'ParaBirimi', label: 'Döviz', render: v => v || 'TL' },
                    { field: 'TeklifTarihi', label: 'Tarih', render: v => NbtUtils.formatDate(v), isDate: true },
                    { field: 'DosyaAdi', label: 'Dosya', render: (v, row) => {
                        if (!v || !row.DosyaYolu) return '-';
                        const ext = v.split('.').pop().toLowerCase();
                        let icon = 'bi-file-earmark';
                        if (ext === 'pdf') icon = 'bi-file-pdf text-danger';
                        else if (ext === 'doc' || ext === 'docx') icon = 'bi-file-word text-primary';
                        return `<a href="/src/${row.DosyaYolu}" target="_blank" title="${NbtUtils.escapeHtml(v)}"><i class="bi ${icon}"></i></a>`;
                    }},
                    { field: 'Durum', label: 'Durum', render: v => this.getStatusBadge(v, 'offer'), statusType: 'teklif' }
                ],
                emptyMsg: 'Kayıt bulunamadı'
            },
            sozlesmeler: {
                columns: [
                    { field: 'ProjeAdi', label: 'Proje' },
                    { field: 'SozlesmeTarihi', label: 'Sözleşme Tarihi', render: v => NbtUtils.formatDate(v), isDate: true },
                    { field: 'Tutar', label: 'Tutar', render: v => NbtUtils.formatNumber(v) },
                    { field: 'ParaBirimi', label: 'Döviz', render: v => v || 'TL' },
                    { field: 'Durum', label: 'Durum', render: v => this.getStatusBadge(v, 'contract'), statusType: 'sozlesme' }
                ],
                emptyMsg: 'Kayıt bulunamadı'
            },
            teminatlar: {
                columns: [
                    { field: 'ProjeAdi', label: 'Proje' },
                    { field: 'Tur', label: 'Tür' },
                    { field: 'Tutar', label: 'Tutar', render: v => NbtUtils.formatNumber(v) },
                    { field: 'ParaBirimi', label: 'Döviz', render: v => v || 'TL' },
                    { field: 'BankaAdi', label: 'Banka' },
                    { field: 'TerminTarihi', label: 'Termin Tarihi', render: v => NbtUtils.formatDate(v), isDate: true },
                    { field: 'EklemeZamani', label: 'İşlem Tarihi', render: v => NbtUtils.formatDate(v, 'long'), isDate: true },
                    { field: 'Durum', label: 'Durum', render: v => this.getStatusBadge(v, 'guarantee'), statusType: 'teminat' }
                ],
                emptyMsg: 'Kayıt bulunamadı'
            },
            faturalar: {
                columns: [
                    { field: 'ProjeAdi', label: 'Proje' },
                    { field: 'Tarih', label: 'Tarih', render: v => NbtUtils.formatDate(v), isDate: true },
                    { field: 'Tutar', label: 'Tutar', render: v => NbtUtils.formatNumber(v) },
                    { field: 'DovizCinsi', label: 'Döviz', render: v => v || 'TRY' },
                    { field: 'OdenenTutar', label: 'Ödenen', render: v => NbtUtils.formatNumber(v || 0) },
                    { field: 'Kalan', label: 'Kalan', render: (v, row) => {
                        const kalan = parseFloat(v) || 0;
                        const cls = kalan > 0 ? 'text-danger fw-bold' : 'text-success';
                        return `<span class="${cls}">${NbtUtils.formatNumber(kalan)}</span>`;
                    }}
                ],
                emptyMsg: 'Kayıt bulunamadı'
            },
            odemeler: {
                columns: [
                    { field: 'ProjeAdi', label: 'Proje' },
                    { field: 'Tarih', label: 'Tarih', render: v => NbtUtils.formatDate(v), isDate: true },
                    { field: 'FaturaId', label: 'Fatura', render: (v, row) => {
                        if (!v) return '-';
                        return `FT${v}/${NbtUtils.formatDate(row.FaturaTarihi)} [${NbtUtils.formatMoney(row.FaturaTutari, row.FaturaDovizi)}]`;
                    }},
                    { field: 'Tutar', label: 'Tutar', render: v => NbtUtils.formatNumber(v) },
                    { field: 'ParaBirimi', label: 'Döviz', render: v => v || 'TRY' },
                    { field: 'Aciklama', label: 'Açıklama' }
                ],
                emptyMsg: 'Kayıt bulunamadı'
            },
            damgavergisi: {
                columns: [
                    { field: 'ProjeAdi', label: 'Proje' },
                    { field: 'BelgeNo', label: 'Belge No' },
                    { field: 'Tarih', label: 'Tarih', render: v => NbtUtils.formatDate(v), isDate: true },
                    { field: 'Tutar', label: 'Tutar', render: v => NbtUtils.formatNumber(v) },
                    { field: 'DovizCinsi', label: 'Döviz', render: v => v || 'TL' },
                    { field: 'Aciklama', label: 'Açıklama' }
                ],
                emptyMsg: 'Kayıt bulunamadı'
            },
            dosyalar: {
                columns: [
                    { field: 'ProjeAdi', label: 'Proje' },
                    { field: 'DosyaAdi', label: 'Dosya Adı' },
                    { field: 'OlusturmaZamani', label: 'Yüklenme', render: v => NbtUtils.formatDate(v), isDate: true },
                    { field: 'Aciklama', label: 'Açıklama' }
                ],
                emptyMsg: 'Kayıt bulunamadı'
            },
            takvim: {
                columns: [
                    { field: 'ProjeAdi', label: 'Proje' },
                    { field: 'TerminTarihi', label: 'Termin Tarihi', render: v => NbtUtils.formatDate(v), isDate: true },
                    { field: 'Ozet', label: 'Özet' },
                    { field: 'Tamamlandi', label: 'Durum', render: v => v === 1 
                        ? '<span class="badge bg-success">Tamamlandı</span>' 
                        : '<span class="badge bg-warning text-dark">Devam Ediyor</span>' 
                    }
                ],
                emptyMsg: 'Kayıt bulunamadı'
            }
        };
        return configs[panelId] || { columns: [], emptyMsg: 'Kayıt bulunamadı' };
    },

    async handleTableAction(action, id, tab) {
        const typeMap = {
            projeler: { type: 'project', detailType: 'project', endpoint: '/api/projects', key: 'projects' },
            teklifler: { type: 'offer', detailType: 'offer', endpoint: '/api/offers', key: 'offers' },
            sozlesmeler: { type: 'contract', detailType: 'contract', endpoint: '/api/contracts', key: 'contracts' },
            teminatlar: { type: 'guarantee', detailType: 'guarantee', endpoint: '/api/guarantees', key: 'guarantees' },
            faturalar: { type: 'invoice', detailType: 'invoice', endpoint: '/api/invoices', key: 'invoices' },
            odemeler: { type: 'payment', detailType: 'payment', endpoint: '/api/payments', key: 'payments' },
            gorusme: { type: 'meeting', detailType: 'meeting', endpoint: '/api/meetings', key: 'meetings' },
            takvim: { type: 'calendar', detailType: 'calendar', endpoint: '/api/takvim', key: 'calendars' },
            kisiler: { type: 'contact', detailType: 'contact', endpoint: '/api/contacts', key: 'contacts' },
            damgavergisi: { type: 'stamptax', detailType: 'stampTax', endpoint: '/api/stamp-taxes', key: 'stampTaxes' },
            dosyalar: { type: 'file', detailType: 'file', endpoint: '/api/files', key: 'files' }
        };

        const config = typeMap[tab];
        if (!config) return;

        // Dosya indirme
        if (action === 'download') {
            try {
                const dataArray = this.data[config.key];
                const item = dataArray?.find(i => parseInt(i.Id, 10) === parseInt(id, 10));
                const dosyaAdi = item?.DosyaAdi || item?.DosyaYolu?.split('/').pop() || 'dosya';
                
                // Tab'a gore dogru endpoint belirle
                let downloadUrl;
                if (tab === 'damgavergisi') {
                    // Damga vergisi icin dogrudan kendi endpoint'ini kullan
                    downloadUrl = `/api/stamp-taxes/${id}/download`;
                } else if (tab === 'teminatlar') {
                    // Teminat icin dogrudan kendi endpoint'ini kullan
                    downloadUrl = `/api/guarantees/${id}/download`;
                } else if (tab === 'dosyalar') {
                    // Dosyalar tab'i - files endpoint kullan
                    downloadUrl = `/api/files/${id}/download`;
                } else {
                    // Diger tablar icin DosyaId varsa files, yoksa kendi endpoint'i
                    const dosyaId = item?.DosyaId;
                    if (dosyaId) {
                        downloadUrl = `/api/files/${dosyaId}/download`;
                    } else {
                        NbtToast.warning('İndirilecek dosya bulunamadı');
                        return;
                    }
                }
                
                // Auth header ile fetch kullanarak indir
                NbtToast.info('Dosya hazırlanıyor...');
                const response = await fetch(downloadUrl, {
                    method: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + NbtUtils.getToken(),
                        'X-Tab-Id': NbtUtils.getTabId()
                    }
                });
                
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.error || 'Dosya indirilemedi');
                }
                
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = dosyaAdi;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
                
                NbtToast.success('Dosya indiriliyor...');
            } catch (err) {
                NbtToast.error(err.message || 'Dosya indirilemedi');
                NbtLogger.error('Dosya indirme hatası:', err);
            }
            return;
        }

        if (action === 'view') {
            const parsedId = parseInt(id, 10);
            
            // Fatura icin ozel olarak API'den kalemlerle birlikte cek
            if (config.type === 'invoice') {
                try {
                    const invoice = await NbtApi.get(`/api/invoices/${parsedId}`);
                    if (invoice) {
                        await NbtDetailModal.show(
                            config.detailType, 
                            invoice, 
                            (editId) => this.openEditModal(config.type, editId),
                            (deleteId, deleteData) => this.confirmDelete(tab, config.endpoint, deleteId, config.key, deleteData)
                        );
                    } else {
                        NbtToast.error('Fatura bulunamadı');
                    }
                } catch (err) {
                    NbtToast.error('Fatura detayı yüklenemedi');
                }
                return;
            }
            
            const dataArray = this.data[config.key];
            
            if (!dataArray || !Array.isArray(dataArray)) {
                NbtToast.warning('Veriler yükleniyor, lütfen tekrar deneyin');
                return;
            }
            
            const item = dataArray.find(i => parseInt(i.Id, 10) === parsedId);
            if (item) {
                await NbtDetailModal.show(
                    config.detailType, 
                    item, 
                    (editId) => this.openEditModal(config.type, editId),
                    (deleteId, deleteData) => this.confirmDelete(tab, config.endpoint, deleteId, config.key, deleteData)
                );
            } else {
                NbtToast.error('Kayıt bulunamadı');
            }
        } else if (action === 'edit') {
            this.openEditModal(config.type, id);
        } else if (action === 'delete') {
            const dataArray = this.data[config.key];
            const item = dataArray?.find(i => parseInt(i.Id, 10) === parseInt(id, 10));
            this.confirmDelete(tab, config.endpoint, id, config.key, item);
        }
    },

    async openAddModal(type) {
        // Tum moduller icin sayfa bazli form kullan (Modal yerine)
        const pageRoutes = {
            offer: `/customer/${this.customerId}/offers/new`,
            contract: `/customer/${this.customerId}/contracts/new`,
            contact: `/customer/${this.customerId}/contacts/new`,
            meeting: `/customer/${this.customerId}/meetings/new`,
            project: `/customer/${this.customerId}/projects/new`,
            calendar: `/customer/${this.customerId}/calendar/new`,
            stamptax: `/customer/${this.customerId}/stamp-taxes/new`,
            guarantee: `/customer/${this.customerId}/guarantees/new`,
            invoice: `/customer/${this.customerId}/invoices/new`,
            payment: `/customer/${this.customerId}/payments/new`,
            file: `/customer/${this.customerId}/files/new`
        };

        const route = pageRoutes[type];
        if (route) {
            window.location.href = route;
            return;
        }

        NbtToast.warning(`${type} için form sayfası henüz tanımlı değil`);
    },

    openEditModal(type, id) {
        // Tum moduller icin sayfa bazli form kullan (Modal yerine)
        const pageRoutes = {
            offer: `/customer/${this.customerId}/offers/${id}/edit`,
            contract: `/customer/${this.customerId}/contracts/${id}/edit`,
            contact: `/customer/${this.customerId}/contacts/${id}/edit`,
            meeting: `/customer/${this.customerId}/meetings/${id}/edit`,
            project: `/customer/${this.customerId}/projects/${id}/edit`,
            calendar: `/customer/${this.customerId}/calendar/${id}/edit`,
            stamptax: `/customer/${this.customerId}/stamp-taxes/${id}/edit`,
            guarantee: `/customer/${this.customerId}/guarantees/${id}/edit`,
            invoice: `/customer/${this.customerId}/invoices/${id}/edit`,
            payment: `/customer/${this.customerId}/payments/${id}/edit`,
            file: `/customer/${this.customerId}/files/${id}/edit`
        };

        const route = pageRoutes[type];
        if (route) {
            window.location.href = route;
            return;
        }

        NbtToast.warning(`${type} için düzenleme sayfası henüz tanımlı değil`);
    },

    async confirmDelete(type, endpoint, id, dataKey, itemData = null) {
        const parsedId = parseInt(id, 10);
        
        let itemInfo = '';
        if (itemData) {
            itemInfo = this.buildDeleteInfo(type, itemData);
        }
        
        const typeNames = {
            kisiler: 'Kişi',
            gorusme: 'Görüşme',
            projeler: 'Proje',
            faturalar: 'Fatura',
            odemeler: 'Ödeme',
            teklifler: 'Teklif',
            sozlesmeler: 'Sözleşme',
            teminatlar: 'Teminat',
            damgavergisi: 'Damga Vergisi',
            dosyalar: 'Dosya'
        };
        
        const typeName = typeNames[type] || 'Kayıt';
        
        const result = await Swal.fire({
            title: 'Silme Onayı',
            html: `<div class="text-start">
                <p class="mb-2"><strong>${typeName}</strong> kaydını silmek istediğinizden emin misiniz?</p>
                ${itemInfo ? `<div class="bg-light rounded p-3 small">${itemInfo}</div>` : ''}
                <p class="text-danger mt-3 mb-0"><i class="bi bi-exclamation-triangle me-1"></i>Bu işlem geri alınamaz!</p>
            </div>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-trash me-1"></i>Evet, Sil',
            cancelButtonText: 'İptal',
            reverseButtons: true
        });
        
        if (!result.isConfirmed) return;

        try {
            await NbtApi.delete(`${endpoint}/${parsedId}`);
            NbtToast.success('Kayıt silindi');
            
            NbtModal.close('entityDetailModal');
            
            this.data[dataKey] = this.data[dataKey].filter(i => parseInt(i.Id, 10) !== parsedId);
            
            if (dataKey === 'payments') {
                await this.loadRelatedData('invoices', '/api/invoices');
            }
            
            this.switchTab(this.activeTab);
        } catch (err) {
            NbtToast.error(err.message);
        }
    },
    
    buildDeleteInfo(type, data) {
        const formatDate = v => NbtUtils.formatDate(v);
        const formatMoney = (v, c) => NbtUtils.formatMoney(v, c || 'TRY');
        
        const configs = {
            kisiler: () => `<div><strong>Ad Soyad:</strong> ${data.AdSoyad || '-'}</div>
                           <div><strong>Telefon:</strong> ${data.Telefon || '-'}</div>`,
            gorusme: () => `<div><strong>Tarih:</strong> ${formatDate(data.Tarih)}</div>
                           <div><strong>Konu:</strong> ${data.Konu || '-'}</div>`,
            projeler: () => `<div><strong>Proje:</strong> ${data.ProjeAdi || '-'}</div>
                            <div><strong>Durum:</strong> ${data.Durum == 1 ? 'Aktif' : data.Durum == 2 ? 'Tamamlandı' : 'İptal'}</div>`,
            faturalar: () => `<div><strong>Tarih:</strong> ${formatDate(data.FaturaTarihi)}</div>
                             <div><strong>Tutar:</strong> ${formatMoney(data.Tutar, data.DovizCinsi)}</div>`,
            odemeler: () => `<div><strong>Tarih:</strong> ${formatDate(data.OdemeTarihi)}</div>
                            <div><strong>Tutar:</strong> ${formatMoney(data.Tutar)}</div>`,
            teklifler: () => `<div><strong>Konu:</strong> ${data.Konu || '-'}</div>
                             <div><strong>Tutar:</strong> ${formatMoney(data.Tutar, data.DovizCinsi)}</div>`,
            sozlesmeler: () => `<div><strong>Tutar:</strong> ${formatMoney(data.Tutar, data.DovizCinsi)}</div>`,
            teminatlar: () => `<div><strong>Tutar:</strong> ${formatMoney(data.Tutar, data.DovizCinsi)}</div>`,
            damgavergisi: () => `<div><strong>Tarih:</strong> ${formatDate(data.Tarih)}</div>
                                <div><strong>Tutar:</strong> ${formatMoney(data.Tutar, data.DovizCinsi)}</div>`,
            dosyalar: () => `<div><strong>Dosya:</strong> ${data.DosyaAdi || '-'}</div>`
        };
        
        return configs[type] ? configs[type]() : '';
    },

    populateCustomerSelect(selectEl) {
        selectEl.innerHTML = '<option value="">Seçiniz...</option>';
        // AppState.customers bossa ve CustomerDetailModule.customerId varsa, aktif musteriyi ekle
        if (AppState.customers && AppState.customers.length > 0) {
            AppState.customers.forEach(c => {
                selectEl.innerHTML += `<option value="${c.Id}">${NbtUtils.escapeHtml(c.Unvan)}</option>`;
            });
        } else if (CustomerDetailModule.customerId && CustomerDetailModule.data?.customer) {
            const c = CustomerDetailModule.data.customer;
            selectEl.innerHTML += `<option value="${c.Id}">${NbtUtils.escapeHtml(c.Unvan)}</option>`;
        }
    }
};

// =============================================
// FATURA MODULU
// =============================================
const InvoiceModule = {
    _eventsBound: false,
    _saving: false,
    data: [],
    pageSize: window.APP_CONFIG?.PAGINATION_DEFAULT || 10,
    currentPage: 1,
    paginationInfo: null,
    searchQuery: '',
    columnFilters: {},
    // Filtre icin ek property'ler
    allData: null,
    allDataLoading: false,
    filteredPage: 1,
    filteredPaginationInfo: null,
    
    async init() {
        this.pageSize = NbtParams.getPaginationDefault();
        // Doviz parametrelerini onceden yukle (cache'e al)
        await NbtParams.getCurrencies();
        await this.loadList();
        this.initToolbar();
        this.bindEvents();
    },

    async loadList(page = 1) {
        const container = document.getElementById('invoicesTableContainer');
        if (!container) return;
        try {
            container.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
            const response = await NbtApi.get(`/api/invoices?page=${page}&limit=${this.pageSize}`);
            this.data = response.data || [];
            this.paginationInfo = response.pagination || null;
            this.currentPage = page;
            // Filtre state'lerini temizle
            this.filteredPaginationInfo = null;
            this.renderTable(this.data);
        } catch (err) {
            container.innerHTML = `<div class="alert alert-danger m-3">${err.message}</div>`;
        }
    },

    initToolbar() {
        const toolbarContainer = document.getElementById('invoicesToolbar');
        if (!toolbarContainer || toolbarContainer.children.length > 0) return;
        
        toolbarContainer.innerHTML = NbtListToolbar.create({
            onSearch: false,
            onAdd: false,
            onFilter: false
        });

        const panel = document.getElementById('panelInvoices');
        NbtListToolbar.bind(toolbarContainer, {
            panelElement: panel
        });
    },

    async applyFilters(page = 1) {
        const hasFilters = Object.keys(this.columnFilters).length > 0;
        if (!hasFilters) {
            this.allData = null;
            this.filteredPaginationInfo = null;
            this.loadList(1);
            return;
        }
        
        // Tum verileri yukle
        if (!this.allData && !this.allDataLoading) {
            this.allDataLoading = true;
            const container = document.getElementById('invoicesTableContainer');
            if (container) {
                container.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> <small class="text-muted ms-2">Arama yapılıyor...</small></div>';
            }
            try {
                const response = await NbtApi.get('/api/invoices?page=1&limit=10000');
                this.allData = response.data || [];
            } catch (err) {
                this.allData = this.data || [];
            }
            this.allDataLoading = false;
        }
        
        if (this.allDataLoading) {
            setTimeout(() => this.applyFilters(page), 100);
            return;
        }
        
        let filtered = this.allData || [];
        
        // Kolon filtreleri - CustomerDetailModule mantigiyla
        Object.keys(this.columnFilters).forEach(field => {
            const value = this.columnFilters[field];
            if (!value) return;
            
            // Tarih araligi baslangic filtresi (_start ile bitiyor)
            if (field.endsWith('_start')) {
                const baseField = field.replace('_start', '');
                filtered = filtered.filter(item => {
                    let cellValue = item[baseField];
                    if (!cellValue) return false;
                    const cellDate = NbtUtils.formatDateForCompare(cellValue);
                    return cellDate >= value;
                });
                return;
            }
            
            // Tarih araligi bitis filtresi (_end ile bitiyor)
            if (field.endsWith('_end')) {
                const baseField = field.replace('_end', '');
                filtered = filtered.filter(item => {
                    let cellValue = item[baseField];
                    if (!cellValue) return false;
                    const cellDate = NbtUtils.formatDateForCompare(cellValue);
                    return cellDate <= value;
                });
                return;
            }
            
            // Select alanlari icin exact match (DovizCinsi vb.)
            const selectFields = ['DovizCinsi', 'ParaBirimi'];
            if (selectFields.includes(field)) {
                filtered = filtered.filter(item => {
                    const cellValue = String(item[field] ?? '');
                    return cellValue === value;
                });
                return;
            }
            
            // Normal alanlar icin filtre
            filtered = filtered.filter(item => {
                let cellValue = item[field];
                return NbtUtils.normalizeText(cellValue).includes(NbtUtils.normalizeText(value));
            });
        });
        
        // Pagination hesapla
        this.filteredPage = page;
        const total = filtered.length;
        const totalPages = Math.ceil(total / this.pageSize);
        const startIndex = (page - 1) * this.pageSize;
        const endIndex = Math.min(startIndex + this.pageSize, total);
        const pageData = filtered.slice(startIndex, endIndex);
        
        this.filteredPaginationInfo = { page, limit: this.pageSize, total, totalPages };
        this.renderTable(pageData, true);
    },

    renderTable(data, isFiltered = false) {
        const container = document.getElementById('invoicesTableContainer');
        if (!container) return;
        const columns = [
            { field: 'MusteriUnvan', label: 'Müşteri' },
            { field: 'Tarih', label: 'Tarih', render: v => NbtUtils.formatDate(v), isDate: true },
            { field: 'Tutar', label: 'Tutar', render: (v, row) => NbtUtils.formatMoney(v, row.DovizCinsi) },
            { field: 'DovizCinsi', label: 'Döviz', render: v => v || 'TRY', isSelect: true },
            { field: 'Kalan', label: 'Kalan', render: (v, row) => {
                const kalan = parseFloat(v) || 0;
                const cls = kalan > 0 ? 'text-danger fw-bold' : 'text-success';
                return `<span class="${cls}">${NbtUtils.formatMoney(kalan, row.DovizCinsi)}</span>`;
            }}
        ];

        // Header row
        const headers = columns.map(c => `<th class="bg-light px-3">${c.label}</th>`).join('') + 
            '<th class="bg-light text-center px-3" style="width:140px;">İşlem</th>';

        // Filter row - CustomerDetailModule mantigiyla
        const filterRow = columns.map(c => {
            const currentValue = this.columnFilters[c.field] || '';
            const startValue = this.columnFilters[c.field + '_start'] || '';
            const endValue = this.columnFilters[c.field + '_end'] || '';
            
            // Tarih alanlari icin cift date input (baslangic-bitis) - yan yana
            if (c.isDate) {
                return `<th class="p-1" style="min-width:200px;">
                    <div class="d-flex gap-1">
                        <input type="date" class="form-control form-control-sm" data-column-filter="${c.field}_start" data-table-id="invoices" value="${NbtUtils.escapeHtml(startValue)}" title="Başlangıç">
                        <input type="date" class="form-control form-control-sm" data-column-filter="${c.field}_end" data-table-id="invoices" value="${NbtUtils.escapeHtml(endValue)}" title="Bitiş">
                    </div>
                </th>`;
            }
            
            // Doviz alanlari icin select - dinamik olarak doldurulacak
            if (c.field === 'DovizCinsi' || c.isSelect) {
                return `<th class="p-1">
                    <select class="form-select form-select-sm" data-column-filter="${c.field}" data-table-id="invoices" data-currency-select="true">
                        <option value="">Tümü</option>
                    </select>
                </th>`;
            }
            
            return `<th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Ara..." data-column-filter="${c.field}" data-table-id="invoices" value="${NbtUtils.escapeHtml(currentValue)}"></th>`;
        }).join('') + `<th class="p-1 text-center">
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-sm btn-outline-primary" data-action="apply-filters" title="Ara"><i class="bi bi-search"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-action="clear-filters" title="Filtreleri Temizle"><i class="bi bi-x-lg"></i></button>
            </div>
        </th>`;

        // Data rows
        let rowsHtml = '';
        if (!data || data.length === 0) {
            rowsHtml = `<tr><td colspan="${columns.length + 1}" class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Fatura bulunamadı</td></tr>`;
        } else {
            rowsHtml = data.map(row => {
                const cells = columns.map(c => {
                    let val = row[c.field];
                    if (c.render) val = c.render(val, row);
                    return `<td data-field="${c.field}" class="px-3">${val ?? '-'}</td>`;
                }).join('');
                
                return `
                    <tr data-id="${row.Id}">
                        ${cells}
                        <td class="text-center px-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary btn-sm" type="button" data-action="view" data-id="${row.Id}" title="Detay" data-can="invoices.read">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a class="btn btn-outline-info btn-sm" href="/customer/${row.MusteriId}?tab=faturalar" title="Müşteriye Git" data-can="customers.read">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </div>
                        </td>
                    </tr>`;
            }).join('');
        }

        container.innerHTML = `
            <div class="table-responsive p-2">
                <table class="table table-bordered table-hover table-sm mb-0" id="invoicesTable">
                    <thead>
                        <tr>${headers}</tr>
                        <tr class="bg-white">${filterRow}</tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            </div>`;

        // Pagination ekleme
        if (isFiltered && this.filteredPaginationInfo) {
            const { page, totalPages, total, limit } = this.filteredPaginationInfo;
            if (totalPages > 1) {
                container.innerHTML += this.renderFilteredPagination();
            } else if (total > 0) {
                container.innerHTML += `<div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light"><small class="text-muted"><i class="bi bi-funnel me-1"></i>Filtrelenmiş: ${total} kayıt gösteriliyor</small></div>`;
            }
        } else if (!isFiltered && this.paginationInfo && this.paginationInfo.totalPages > 1) {
            container.innerHTML += this.renderPagination();
        }

        this.bindTableEvents(container);
        this.populateFilterSelects(container);
    },

    // Filtre select'lerini dinamik parametrelerden doldur
    async populateFilterSelects(container) {
        // Currency (doviz) select'lerini parametrelerden doldur
        const currencies = await NbtParams.getCurrencies();
        container.querySelectorAll('select[data-currency-select]').forEach(select => {
            const currentValue = this.columnFilters[select.dataset.columnFilter] || '';
            
            let options = '<option value="">Tümü</option>';
            (currencies || []).forEach(c => {
                const selected = currentValue === c.Kod ? 'selected' : '';
                options += `<option value="${c.Kod}" ${selected}>${c.Kod}</option>`;
            });
            select.innerHTML = options;
        });
    },

    bindTableEvents(container) {
        // View buttons
        container.querySelectorAll('[data-action="view"]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = parseInt(btn.dataset.id);
                try {
                    // API'den fatura detayini kalemlerle birlikte al
                    const invoice = await NbtApi.get(`/api/invoices/${id}`);
                    if (invoice) {
                        await NbtDetailModal.show('invoice', invoice, null, null);
                    } else {
                        NbtToast.error('Fatura kaydı bulunamadı');
                    }
                } catch (err) {
                    // Fallback: local data
                    const invoice = (this.allData || this.data).find(i => parseInt(i.Id, 10) === id);
                    if (invoice) {
                        await NbtDetailModal.show('invoice', invoice, null, null);
                    } else {
                        NbtToast.error('Fatura kaydı bulunamadı');
                    }
                }
            });
        });

        // Pagination
        container.querySelectorAll('[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const newPage = parseInt(link.dataset.page);
                if (!isNaN(newPage) && newPage !== this.currentPage) {
                    this.loadList(newPage);
                }
            });
        });

        // Filtered pagination
        container.querySelectorAll('[data-filtered-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const newPage = parseInt(link.dataset.filteredPage);
                if (!isNaN(newPage) && newPage !== this.filteredPage) {
                    this.applyFilters(newPage);
                }
            });
        });

        // Apply filters button
        container.querySelectorAll('[data-action="apply-filters"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.columnFilters = {};
                container.querySelectorAll('[data-column-filter]').forEach(input => {
                    const field = input.dataset.columnFilter;
                    const value = input.value.trim();
                    if (value) this.columnFilters[field] = value;
                });
                this.applyFilters();
            });
        });

        // Enter key for filters
        container.querySelectorAll('[data-column-filter]').forEach(input => {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    container.querySelector('[data-action="apply-filters"]')?.click();
                }
            });
        });

        // Clear filters button
        container.querySelectorAll('[data-action="clear-filters"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.columnFilters = {};
                this.allData = null;
                container.querySelectorAll('[data-column-filter]').forEach(input => {
                    input.value = '';
                });
                this.loadList(1);
            });
        });
    },

    renderPagination() {
        if (!this.paginationInfo) return '';
        const { page, totalPages, total, limit } = this.paginationInfo;
        const startIndex = (page - 1) * limit;
        const endIndex = Math.min(startIndex + limit, total);

        let pageButtons = '';
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="1"><i class="bi bi-chevron-double-left"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${page - 1}"><i class="bi bi-chevron-left"></i></a></li>`;
        
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        for (let i = startPage; i <= endPage; i++) {
            pageButtons += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
        
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${page + 1}"><i class="bi bi-chevron-right"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${totalPages}"><i class="bi bi-chevron-double-right"></i></a></li>`;

        return `
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light" id="invoicesPagination">
                <small class="text-muted">Toplam ${total} kayıttan ${startIndex + 1}-${endIndex} arası gösteriliyor</small>
                <nav><ul class="pagination pagination-sm mb-0">${pageButtons}</ul></nav>
            </div>
        `;
    },

    renderFilteredPagination() {
        if (!this.filteredPaginationInfo) return '';
        const { page, totalPages, total, limit } = this.filteredPaginationInfo;
        const startIndex = (page - 1) * limit;
        const endIndex = Math.min(startIndex + limit, total);

        let pageButtons = '';
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="1"><i class="bi bi-chevron-double-left"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${page - 1}"><i class="bi bi-chevron-left"></i></a></li>`;
        
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        for (let i = startPage; i <= endPage; i++) {
            pageButtons += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" data-filtered-page="${i}">${i}</a></li>`;
        }
        
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${page + 1}"><i class="bi bi-chevron-right"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${totalPages}"><i class="bi bi-chevron-double-right"></i></a></li>`;

        return `
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light">
                <small class="text-muted"><i class="bi bi-funnel me-1"></i>Filtrelenmiş: Toplam ${total} kayıttan ${startIndex + 1}-${endIndex} arası gösteriliyor</small>
                <nav><ul class="pagination pagination-sm mb-0">${pageButtons}</ul></nav>
            </div>
        `;
    },

    async openModal(id = null) {
        NbtModal.resetForm('invoiceModal');
        document.getElementById('invoiceModalTitle').innerHTML = id ? '<i class="bi bi-receipt me-2"></i>Fatura Düzenle' : '<i class="bi bi-receipt me-2"></i>Yeni Fatura';
        document.getElementById('invoiceId').value = id || '';

        // Müşteri ID hidden input'a set edilecek
        const musteriIdEl = document.getElementById('invoiceMusteriId');

        const projeSelect = document.getElementById('invoiceProjeId');
        projeSelect.innerHTML = '<option value="">Proje Seçiniz...</option>';

        // Yeni alanlari sifirla
        const dovizSelect = document.getElementById('invoiceDoviz');
        const faturaNoEl = document.getElementById('invoiceFaturaNo');
        const supheliEl = document.getElementById('invoiceSupheliAlacak');
        const tevkifatAktifEl = document.getElementById('invoiceTevkifatAktif');
        const tevkifatOran1El = document.getElementById('invoiceTevkifatOran1');
        const tevkifatOran2El = document.getElementById('invoiceTevkifatOran2');
        const tevkifatAlani = document.getElementById('tevkifatAlani');

        if (faturaNoEl) faturaNoEl.value = '';
        if (supheliEl) supheliEl.checked = false;
        if (tevkifatAktifEl) tevkifatAktifEl.checked = false;
        // Tevkifat oranları default: 100,00 ve 0,00
        if (tevkifatOran1El) tevkifatOran1El.value = '100,00';
        if (tevkifatOran2El) tevkifatOran2El.value = '0,00';
        if (tevkifatAlani) tevkifatAlani.style.display = 'none';

        // Takvim hatırlatma alanlarını sıfırla
        const takvimAktifEl = document.getElementById('invoiceTakvimAktif');
        const takvimSureEl = document.getElementById('invoiceTakvimSure');
        const takvimSureTipiEl = document.getElementById('invoiceTakvimSureTipi');
        const takvimAlani = document.getElementById('takvimHatirlatmaAlani');
        if (takvimAktifEl) takvimAktifEl.checked = false;
        if (takvimSureEl) takvimSureEl.value = '7';
        if (takvimSureTipiEl) takvimSureTipiEl.value = 'gun';
        if (takvimAlani) takvimAlani.style.display = 'none';

        // Fatura kalemlerini sifirla
        this.resetInvoiceItems();

        // Projeleri yukle fonksiyonu (meeting/new ile ortak mantik)
        const loadProjects = async (musteriId) => {
            if (window.NbtProjectSelect && projeSelect) {
                await window.NbtProjectSelect.loadForCustomer(projeSelect, musteriId);
                return;
            }
            projeSelect.innerHTML = '<option value="">Proje Seçiniz...</option>';
            if (musteriId) {
                try {
                    const response = await NbtApi.get(`/api/projects?musteri_id=${musteriId}`);
                    let projects = response.data || [];
                    const pasifKodlar = await NbtParams.getPasifDurumKodlari('proje', true);
                    projects = projects.filter(p => !pasifKodlar.includes(String(p.Durum)));
                    const uniq = new Map();
                    projects.forEach(p => {
                        const key = String(p.Id);
                        if (!uniq.has(key)) uniq.set(key, p);
                    });
                    Array.from(uniq.values()).forEach(p => {
                        projeSelect.innerHTML += `<option value="${p.Id}">${NbtUtils.escapeHtml(p.ProjeAdi || '')}</option>`;
                    });
                } catch (err) {
                    NbtLogger.error('Projeler yüklenemedi:', err);
                }
            }
        };

        // Customer detail sayfasından MusteriId'yi al
        let musteriId = CustomerDetailModule.customerId || null;
        if (musteriIdEl) musteriIdEl.value = musteriId || '';
        
        // Projeleri yükle
        if (musteriId) {
            await loadProjects(musteriId);
        }

        // Döviz seçeneklerini yükle
        if (dovizSelect) {
            await NbtParams.populateCurrencySelect(dovizSelect);
        }

        if (id) {
            const parsedId = parseInt(id, 10);
            let invoice = this.data?.find(i => parseInt(i.Id, 10) === parsedId);
            if (!invoice) {
                invoice = CustomerDetailModule.data?.invoices?.find(i => parseInt(i.Id, 10) === parsedId);
            }
            if (invoice) {
                // MusteriId hidden input'a set et
                if (musteriIdEl) musteriIdEl.value = invoice.MusteriId;
                // Projeleri yukleme ve secili projeyi ayarlama
                await loadProjects(invoice.MusteriId);
                document.getElementById('invoiceProjeId').value = invoice.ProjeId || '';
                document.getElementById('invoiceTarih').value = invoice.Tarih?.split('T')[0] || '';
                if (dovizSelect) {
                    dovizSelect.value = invoice.DovizCinsi || invoice.ParaBirimi || NbtParams.getDefaultCurrency();
                }
                
                // Türkçe format helper (1234.56 -> 1.234,56)
                const formatTR = (num) => {
                    const val = parseFloat(num) || 0;
                    return val.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                };
                
                // Yeni alanlari doldur
                if (faturaNoEl && invoice.FaturaNo) faturaNoEl.value = invoice.FaturaNo;
                // Checkbox'lar icin parseInt ile sayisal karsilastirma yap (string "0" veya "1" gelebilir)
                if (supheliEl) supheliEl.checked = parseInt(invoice.SupheliAlacak, 10) === 1;
                if (parseInt(invoice.TevkifatAktif, 10) === 1) {
                    if (tevkifatAktifEl) tevkifatAktifEl.checked = true;
                    if (tevkifatAlani) tevkifatAlani.style.display = 'block';
                    if (tevkifatOran1El) tevkifatOran1El.value = formatTR(invoice.TevkifatOran1);
                    if (tevkifatOran2El) tevkifatOran2El.value = formatTR(invoice.TevkifatOran2);
                }
                
                // Takvim hatırlatma ayarlarını yükle
                if (parseInt(invoice.TakvimAktif, 10) === 1) {
                    if (takvimAktifEl) takvimAktifEl.checked = true;
                    if (takvimAlani) takvimAlani.style.display = 'block';
                    if (takvimSureEl && invoice.TakvimSure) takvimSureEl.value = invoice.TakvimSure;
                    if (takvimSureTipiEl && invoice.TakvimSureTipi) takvimSureTipiEl.value = invoice.TakvimSureTipi;
                } else {
                    if (takvimAktifEl) takvimAktifEl.checked = false;
                    if (takvimAlani) takvimAlani.style.display = 'none';
                }

                // Fatura kalemlerini API'den yukle (her zaman guncel veri icin)
                try {
                    const detailResponse = await NbtApi.get(`/api/invoices/${parsedId}`);
                    if (detailResponse && detailResponse.Kalemler && Array.isArray(detailResponse.Kalemler)) {
                        this.loadInvoiceItems(detailResponse.Kalemler);
                    }
                    // Fatura dosyalarini yukle
                    if (detailResponse && detailResponse.Dosyalar && Array.isArray(detailResponse.Dosyalar)) {
                        this.loadInvoiceFiles(detailResponse.Dosyalar);
                    } else {
                        this.resetInvoiceFiles();
                    }
                } catch (err) {
                    // API hatasi durumunda local data'dan yukle
                    if (invoice.Kalemler && Array.isArray(invoice.Kalemler)) {
                        this.loadInvoiceItems(invoice.Kalemler);
                    }
                    this.resetInvoiceFiles();
                }
            } else {
                NbtToast.error('Fatura kaydı bulunamadı');
                return;
            }
        } else {
            // Yeni fatura - dosyalari sifirla
            this.resetInvoiceFiles();
        }

        NbtModal.open('invoiceModal');
    },

    async save() {
        if (this._saving) return;
        this._saving = true;
        const id = document.getElementById('invoiceId').value;
        
        let musteriId = parseInt(document.getElementById('invoiceMusteriId').value);
        if (!musteriId || isNaN(musteriId)) {
            musteriId = CustomerDetailModule.customerId;
        }
        
        const projeIdVal = document.getElementById('invoiceProjeId').value;
        
        // Türkçe formatı parse et (1.234,56 -> 1234.56)
        const parseTR = (str) => {
            if (!str) return 0;
            return parseFloat(String(str).replace(/\./g, '').replace(',', '.')) || 0;
        };
        
        // Tutar'ı fatura kalemlerinin genel toplamından al (Türkçe formatlı)
        const genelToplamEl = document.getElementById('invoiceItemsGenelToplam');
        const tutar = genelToplamEl ? parseTR(genelToplamEl.value) : 0;
        
        // Tevkifat oranlarını Türkçe formatla parse et
        const tevkifatOran1 = parseTR(document.getElementById('invoiceTevkifatOran1')?.value);
        const tevkifatOran2 = parseTR(document.getElementById('invoiceTevkifatOran2')?.value);
        
        // Takvim hatırlatma değerlerini al
        const takvimAktif = document.getElementById('invoiceTakvimAktif')?.checked ? 1 : 0;
        const takvimSureRaw = document.getElementById('invoiceTakvimSure')?.value || '';
        const takvimSure = parseInt(takvimSureRaw, 10) || null;
        const takvimSureTipi = document.getElementById('invoiceTakvimSureTipi')?.value || '';
        const allowedSureTipleri = ['gun', 'hafta', 'ay', 'yil'];

        if (takvimAktif === 1 && (takvimSureRaw !== '' || takvimSureTipi !== '')) {
            if (!takvimSure || takvimSure <= 0) {
                NbtModal.showError('invoiceModal', 'Takvim hatırlatma süresi geçersiz');
                this._saving = false;
                NbtModal.setLoading('invoiceModal', false);
                return;
            }
            if (!allowedSureTipleri.includes(takvimSureTipi)) {
                NbtModal.showError('invoiceModal', 'Takvim hatırlatma birimi geçersiz');
                this._saving = false;
                NbtModal.setLoading('invoiceModal', false);
                return;
            }
        }
        
        const doviz = document.getElementById('invoiceDoviz')?.value || NbtParams.getDefaultCurrency();

        const data = {
            MusteriId: musteriId,
            ProjeId: projeIdVal ? parseInt(projeIdVal) : null,
            Tarih: document.getElementById('invoiceTarih').value,
            Tutar: tutar,
            DovizCinsi: doviz,
            // Yeni alanlar
            FaturaNo: document.getElementById('invoiceFaturaNo')?.value.trim() || null,
            SupheliAlacak: document.getElementById('invoiceSupheliAlacak')?.checked ? 1 : 0,
            TevkifatAktif: document.getElementById('invoiceTevkifatAktif')?.checked ? 1 : 0,
            TevkifatOran1: tevkifatOran1 || null,
            TevkifatOran2: tevkifatOran2 || null,
            TakvimAktif: takvimAktif,
            TakvimSure: takvimAktif && takvimSure > 0 ? takvimSure : null,
            TakvimSureTipi: takvimAktif && allowedSureTipleri.includes(takvimSureTipi) ? takvimSureTipi : null,
            Kalemler: this.getInvoiceItems()
        };

        NbtModal.clearError('invoiceModal');
        if (!data.MusteriId || isNaN(data.MusteriId)) {
            NbtModal.showFieldError('invoiceModal', 'invoiceMusteriId', 'Müşteri seçiniz');
            NbtModal.showError('invoiceModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        if (!data.ProjeId) {
            NbtModal.showFieldError('invoiceModal', 'invoiceProjeId', 'Proje seçiniz');
            NbtModal.showError('invoiceModal', 'Proje seçimi zorunludur');
            return;
        }
        if (!data.Tarih) {
            NbtModal.showFieldError('invoiceModal', 'invoiceTarih', 'Tarih zorunludur');
            NbtModal.showError('invoiceModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        if (!data.Kalemler || data.Kalemler.length === 0) {
            NbtModal.showError('invoiceModal', 'En az bir fatura kalemi eklemelisiniz');
            return;
        }

        NbtModal.setLoading('invoiceModal', true);
        try {
            let faturaId;
            if (id) {
                await NbtApi.put(`/api/invoices/${id}`, data);
                faturaId = id;
                NbtToast.success('Fatura güncellendi');
            } else {
                const response = await NbtApi.post('/api/invoices', data);
                // API response: {id: X} (kucuk harfle)
                faturaId = response.id || response.Id || response.data?.id || response.data?.Id;
                NbtToast.success('Fatura eklendi');
            }
            
            // Dosya islemleri
            if (faturaId) {
                await this.deleteMarkedFiles();
                await this.uploadPendingFiles(faturaId);
            }
            
            NbtModal.close('invoiceModal');
            
            // Customer detail sayfasindaysak once oraya yenile
            if (CustomerDetailModule.customerId) {
                await CustomerDetailModule.loadRelatedData('invoices', '/api/invoices');
                CustomerDetailModule.switchTab(CustomerDetailModule.activeTab);
            } else {
                // Standalone invoices sayfasindaysak liste yenile
                await this.loadList();
            }
        } catch (err) {
            NbtModal.showError('invoiceModal', err.message);
        } finally {
            NbtModal.setLoading('invoiceModal', false);
            this._saving = false;
        }
    },

    // Fatura kalemleri yardimci fonksiyonlar - Dinamik yapi
    resetInvoiceItems() {
        // Modal'daki UI reset fonksiyonunu cagir
        if (typeof window.resetInvoiceItemsUI === 'function') {
            window.resetInvoiceItemsUI();
        }
    },

    loadInvoiceItems(kalemler) {
        // Modal'daki UI load fonksiyonunu cagir
        if (typeof window.loadInvoiceItemsUI === 'function') {
            window.loadInvoiceItemsUI(kalemler);
        }
    },

    getInvoiceItems() {
        const kalemler = [];
        const rows = document.querySelectorAll('#invoiceItemsBody .invoice-item-row');
        
        // Türkçe formatı parse et (1.234,56 -> 1234.56)
        const parseTR = (str) => {
            if (!str) return 0;
            return parseFloat(String(str).replace(/\./g, '').replace(',', '.')) || 0;
        };
        
        rows.forEach((row, index) => {
            const miktar = parseFloat(row.querySelector('.item-miktar').value) || 0;
            const aciklama = row.querySelector('.item-aciklama').value.trim();
            const kdvOran = parseFloat(row.querySelector('.item-kdv').value) || 0;
            const birimFiyat = parseFloat(row.querySelector('.item-birimfiyat').value) || 0;
            const tutar = parseTR(row.querySelector('.item-tutar').value);
            
            // Sadece dolu kalemleri ekle (en az miktar veya aciklama olmali)
            if (miktar > 0 || aciklama || birimFiyat > 0) {
                kalemler.push({
                    Sira: index + 1,
                    Miktar: miktar,
                    Aciklama: aciklama,
                    KdvOran: kdvOran,
                    BirimFiyat: birimFiyat,
                    Tutar: tutar
                });
            }
        });
        return kalemler;
    },

    // Dosya yonetimi - yeni dosyalar ve mevcut dosyalar
    pendingFiles: [],
    existingFiles: [],
    filesToDelete: [],

    /**
     * Tum state'i sifirla - sayfa acildiginda veya modal kapandiginda cagrilir
     * form.php'den InvoiceModule.resetState() olarak cagriliyor
     */
    resetState() {
        this.pendingFiles = [];
        this.existingFiles = [];
        this.filesToDelete = [];
        this.resetInvoiceItems();
        this.renderInvoiceFiles();
    },

    resetInvoiceFiles() {
        this.pendingFiles = [];
        this.existingFiles = [];
        this.filesToDelete = [];
        this.renderInvoiceFiles();
    },

    loadInvoiceFiles(files) {
        this.existingFiles = files || [];
        this.pendingFiles = [];
        this.filesToDelete = [];
        this.renderInvoiceFiles();
    },

    renderInvoiceFiles() {
        const tbody = document.getElementById('invoiceFilesBody');
        const table = document.getElementById('invoiceFilesTable');
        const emptyDiv = document.getElementById('invoiceFilesEmpty');
        
        if (!tbody) return;

        const allFiles = [
            ...this.existingFiles.map(f => ({ ...f, isExisting: true })),
            ...this.pendingFiles.map((f, i) => ({ 
                Id: `pending_${i}`, 
                DosyaAdi: f.name, 
                DosyaBoyutu: f.size,
                OlusturmaZamani: new Date().toISOString(),
                isExisting: false,
                file: f
            }))
        ];

        if (allFiles.length === 0) {
            if (table) table.style.display = 'none';
            if (emptyDiv) emptyDiv.style.display = 'block';
            tbody.innerHTML = '';
            return;
        }

        if (table) table.style.display = 'table';
        if (emptyDiv) emptyDiv.style.display = 'none';

        tbody.innerHTML = allFiles.map(f => {
            const boyut = f.DosyaBoyutu ? `${(f.DosyaBoyutu / 1024).toFixed(1)} KB` : '-';
            const tarih = f.OlusturmaZamani ? NbtUtils.formatDate(f.OlusturmaZamani) : '-';
            const isExisting = f.isExisting;
            const downloadBtn = isExisting ? `<button type="button" class="btn btn-outline-info btn-sm" data-action="download-file" data-file-id="${f.Id}" title="İndir"><i class="bi bi-download"></i></button>` : '';
            
            return `
                <tr data-file-id="${f.Id}">
                    <td>${NbtUtils.escapeHtml(f.DosyaAdi)}</td>
                    <td>${boyut}</td>
                    <td>${tarih}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            ${downloadBtn}
                            <button type="button" class="btn btn-outline-danger btn-sm" data-action="remove-file" data-file-id="${f.Id}" data-is-existing="${isExisting}" title="Kaldır"><i class="bi bi-trash"></i></button>
                        </div>
                    </td>
                </tr>`;
        }).join('');

        this.bindFileEvents(tbody);
    },

    bindFileEvents(tbody) {
        tbody.querySelectorAll('[data-action="download-file"]').forEach(btn => {
            btn.onclick = async () => {
                const fileId = btn.dataset.fileId;
                if (fileId) {
                    try {
                        NbtToast.info('Dosya hazırlanıyor...');
                        const downloadUrl = `/api/files/${fileId}/download`;
                        const response = await fetch(downloadUrl, {
                            method: 'GET',
                            headers: {
                                'Authorization': 'Bearer ' + NbtUtils.getToken(),
                                'X-Tab-Id': NbtUtils.getTabId()
                            }
                        });
                        
                        if (!response.ok) {
                            const errorData = await response.json().catch(() => ({}));
                            throw new Error(errorData.error || 'Dosya indirilemedi');
                        }
                        
                        const blob = await response.blob();
                        const contentDisposition = response.headers.get('Content-Disposition');
                        let filename = 'dosya';
                        if (contentDisposition) {
                            const match = contentDisposition.match(/filename="?([^";\n]+)"?/);
                            if (match) filename = match[1];
                        }
                        
                        const url = window.URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        window.URL.revokeObjectURL(url);
                        
                        NbtToast.success('Dosya indiriliyor...');
                    } catch (err) {
                        NbtToast.error(err.message || 'Dosya indirilemedi');
                    }
                }
            };
        });

        tbody.querySelectorAll('[data-action="remove-file"]').forEach(btn => {
            btn.onclick = () => {
                const fileId = btn.dataset.fileId;
                const isExisting = btn.dataset.isExisting === 'true';

                if (isExisting) {
                    // Mevcut dosyayi silme listesine ekle
                    const file = this.existingFiles.find(f => f.Id == fileId);
                    if (file) {
                        this.filesToDelete.push(file.Id);
                        this.existingFiles = this.existingFiles.filter(f => f.Id != fileId);
                    }
                } else {
                    // Pending dosyayi kaldir
                    const idx = parseInt(fileId.replace('pending_', ''));
                    this.pendingFiles.splice(idx, 1);
                }
                this.renderInvoiceFiles();
            };
        });
    },

    handleFileSelect(input) {
        if (!input.files || input.files.length === 0) return;
        
        for (let i = 0; i < input.files.length; i++) {
            this.pendingFiles.push(input.files[i]);
        }
        this.renderInvoiceFiles();
        input.value = ''; // Input'u sifirla
    },

    async uploadPendingFiles(faturaId) {
        if (this.pendingFiles.length === 0) return;

        for (const file of this.pendingFiles) {
            const formData = new FormData();
            formData.append('dosya', file);
            formData.append('FaturaId', faturaId);
            formData.append('Aciklama', `Fatura #${faturaId} dosyası`);

            try {
                await NbtApi.request('/api/files', {
                    method: 'POST',
                    body: formData,
                    headers: {} // FormData icin Content-Type header'i otomatik
                });
            } catch (err) {
                NbtLogger.error('Dosya yükleme hatası:', err);
            }
        }
    },

    async deleteMarkedFiles() {
        for (const fileId of this.filesToDelete) {
            try {
                await NbtApi.delete(`/api/files/${fileId}`);
            } catch (err) {
                NbtLogger.error('Dosya silme hatası:', err);
            }
        }
    },

    bindEvents() {
        if (this._eventsBound) return;
        // Sayfa formu varsa modal bind'i yapma (double submit engeli)
        if (document.getElementById('invoicePageForm')) return;
        this._eventsBound = true;
        document.getElementById('btnSaveInvoice')?.addEventListener('click', () => this.save());
        
        // Dosya input event
        const fileInput = document.getElementById('invoiceFileInput');
        if (fileInput) {
            fileInput.addEventListener('change', () => this.handleFileSelect(fileInput));
        }

    }
};

// =============================================
// PROJE SELECT HELPER - Meeting/Invoice ortak mantik
// =============================================
window.NbtProjectSelect = {
    async loadForCustomer(selectEl, musteriId) {
        if (!selectEl) return;
        selectEl.innerHTML = '<option value="">Proje Seçiniz...</option>';
        if (!musteriId) return;
        try {
            const response = await NbtApi.get(`/api/projects?musteri_id=${musteriId}`);
            let projects = response.data || [];

            // Pasif projeleri filtrele
            const pasifKodlar = await NbtParams.getPasifDurumKodlari('proje', true);
            projects = projects.filter(p => !pasifKodlar.includes(String(p.Durum)));

            // Duplicate'leri temizle (Id bazli)
            const uniq = new Map();
            projects.forEach(p => {
                const key = String(p.Id);
                if (!uniq.has(key)) uniq.set(key, p);
            });

            Array.from(uniq.values()).forEach(p => {
                selectEl.innerHTML += `<option value="${p.Id}">${NbtUtils.escapeHtml(p.ProjeAdi || '')}</option>`;
            });
        } catch (err) {
            NbtLogger.error('Projeler yüklenemedi:', err);
        }
    }
};

// =============================================
// ODEME MODULU
// =============================================
const PaymentModule = {
    _eventsBound: false,
    data: [],
    pageSize: window.APP_CONFIG?.PAGINATION_DEFAULT || 10,
    currentPage: 1,
    paginationInfo: null,
    searchQuery: '',
    columnFilters: {},
    // Filtre icin ek property'ler
    allData: null,
    allDataLoading: false,
    filteredPage: 1,
    filteredPaginationInfo: null,
    
    async init() {
        this.pageSize = NbtParams.getPaginationDefault();
        // Doviz parametrelerini onceden yukle (cache'e al)
        await NbtParams.getCurrencies();
        await this.loadList();
        this.initToolbar();
        this.bindEvents();
    },

    async loadList(page = 1) {
        const container = document.getElementById('paymentsTableContainer');
        if (!container) return;
        try {
            container.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
            const response = await NbtApi.get(`/api/payments?page=${page}&limit=${this.pageSize}`);
            this.data = response.data || [];
            this.paginationInfo = response.pagination || null;
            this.currentPage = page;
            this.filteredPaginationInfo = null;
            this.renderTable(this.data);
        } catch (err) {
            container.innerHTML = `<div class="alert alert-danger m-3">${err.message}</div>`;
        }
    },

    initToolbar() {
        const toolbarContainer = document.getElementById('paymentsToolbar');
        if (!toolbarContainer || toolbarContainer.children.length > 0) return;
        
        toolbarContainer.innerHTML = NbtListToolbar.create({
            onSearch: false,
            onAdd: false,
            onFilter: false
        });

        const panel = document.getElementById('panelPayments');
        NbtListToolbar.bind(toolbarContainer, {
            panelElement: panel
        });
    },

    async applyFilters(page = 1) {
        const hasFilters = Object.keys(this.columnFilters).length > 0;
        if (!hasFilters) {
            this.allData = null;
            this.filteredPaginationInfo = null;
            this.loadList(1);
            return;
        }
        
        if (!this.allData && !this.allDataLoading) {
            this.allDataLoading = true;
            const container = document.getElementById('paymentsTableContainer');
            if (container) {
                container.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> <small class="text-muted ms-2">Arama yapılıyor...</small></div>';
            }
            try {
                const response = await NbtApi.get('/api/payments?page=1&limit=10000');
                this.allData = response.data || [];
            } catch (err) {
                this.allData = this.data || [];
            }
            this.allDataLoading = false;
        }
        
        if (this.allDataLoading) {
            setTimeout(() => this.applyFilters(page), 100);
            return;
        }
        
        let filtered = this.allData || [];
        
        // Kolon filtreleri - CustomerDetailModule mantigiyla
        Object.keys(this.columnFilters).forEach(field => {
            const value = this.columnFilters[field];
            if (!value) return;
            
            // Tarih araligi baslangic filtresi
            if (field.endsWith('_start')) {
                const baseField = field.replace('_start', '');
                filtered = filtered.filter(item => {
                    let cellValue = item[baseField];
                    if (!cellValue) return false;
                    const cellDate = NbtUtils.formatDateForCompare(cellValue);
                    return cellDate >= value;
                });
                return;
            }
            
            // Tarih araligi bitis filtresi
            if (field.endsWith('_end')) {
                const baseField = field.replace('_end', '');
                filtered = filtered.filter(item => {
                    let cellValue = item[baseField];
                    if (!cellValue) return false;
                    const cellDate = NbtUtils.formatDateForCompare(cellValue);
                    return cellDate <= value;
                });
                return;
            }
            
            // Select alanlari icin exact match
            const selectFields = ['ParaBirimi'];
            if (selectFields.includes(field)) {
                filtered = filtered.filter(item => {
                    const cellValue = String(item[field] ?? '');
                    return cellValue === value;
                });
                return;
            }
            
            // Normal alanlar icin filtre
            filtered = filtered.filter(item => {
                let cellValue = item[field];
                return NbtUtils.normalizeText(cellValue).includes(NbtUtils.normalizeText(value));
            });
        });
        
        this.filteredPage = page;
        const total = filtered.length;
        const totalPages = Math.ceil(total / this.pageSize);
        const startIndex = (page - 1) * this.pageSize;
        const endIndex = Math.min(startIndex + this.pageSize, total);
        const pageData = filtered.slice(startIndex, endIndex);
        
        this.filteredPaginationInfo = { page, limit: this.pageSize, total, totalPages };
        this.renderTable(pageData, true);
    },

    renderTable(data, isFiltered = false) {
        const container = document.getElementById('paymentsTableContainer');
        if (!container) return;
        const columns = [
            { field: 'MusteriUnvan', label: 'Müşteri' },
            { field: 'Tarih', label: 'Tarih', render: v => NbtUtils.formatDate(v), isDate: true },
            { field: 'Tutar', label: 'Tutar', render: (v, row) => NbtUtils.formatMoney(v, row.ParaBirimi) },
            { field: 'ParaBirimi', label: 'Döviz', render: v => v || 'TRY', isSelect: true },
            { field: 'Aciklama', label: 'Açıklama' }
        ];

        const headers = columns.map(c => `<th class="bg-light px-3">${c.label}</th>`).join('') + 
            '<th class="bg-light text-center px-3" style="width:140px;">İşlem</th>';

        // Filter row - CustomerDetailModule mantigiyla
        const filterRow = columns.map(c => {
            const currentValue = this.columnFilters[c.field] || '';
            const startValue = this.columnFilters[c.field + '_start'] || '';
            const endValue = this.columnFilters[c.field + '_end'] || '';
            
            // Tarih alanlari icin cift date input
            if (c.isDate) {
                return `<th class="p-1" style="min-width:200px;">
                    <div class="d-flex gap-1">
                        <input type="date" class="form-control form-control-sm" data-column-filter="${c.field}_start" data-table-id="payments" value="${NbtUtils.escapeHtml(startValue)}" title="Başlangıç">
                        <input type="date" class="form-control form-control-sm" data-column-filter="${c.field}_end" data-table-id="payments" value="${NbtUtils.escapeHtml(endValue)}" title="Bitiş">
                    </div>
                </th>`;
            }
            
            // Doviz alanlari icin select - dinamik olarak doldurulacak
            if (c.field === 'ParaBirimi' || c.isSelect) {
                return `<th class="p-1">
                    <select class="form-select form-select-sm" data-column-filter="${c.field}" data-table-id="payments" data-currency-select="true">
                        <option value="">Tümü</option>
                    </select>
                </th>`;
            }
            
            return `<th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Ara..." data-column-filter="${c.field}" data-table-id="payments" value="${NbtUtils.escapeHtml(currentValue)}"></th>`;
        }).join('') + `<th class="p-1 text-center">
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-sm btn-outline-primary" data-action="apply-filters" title="Ara"><i class="bi bi-search"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-action="clear-filters" title="Filtreleri Temizle"><i class="bi bi-x-lg"></i></button>
            </div>
        </th>`;

        let rowsHtml = '';
        if (!data || data.length === 0) {
            rowsHtml = `<tr><td colspan="${columns.length + 1}" class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Ödeme bulunamadı</td></tr>`;
        } else {
            rowsHtml = data.map(row => {
                const cells = columns.map(c => {
                    let val = row[c.field];
                    if (c.render) val = c.render(val, row);
                    return `<td data-field="${c.field}" class="px-3">${val ?? '-'}</td>`;
                }).join('');
                
                return `
                    <tr data-id="${row.Id}">
                        ${cells}
                        <td class="text-center px-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary btn-sm" type="button" data-action="view" data-id="${row.Id}" title="Detay" data-can="payments.read">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a class="btn btn-outline-info btn-sm" href="/customer/${row.MusteriId}?tab=odemeler" title="Müşteriye Git" data-can="customers.read">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </div>
                        </td>
                    </tr>`;
            }).join('');
        }

        container.innerHTML = `
            <div class="table-responsive p-2">
                <table class="table table-bordered table-hover table-sm mb-0" id="paymentsTable">
                    <thead>
                        <tr>${headers}</tr>
                        <tr class="bg-white">${filterRow}</tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            </div>`;

        if (isFiltered && this.filteredPaginationInfo) {
            const { page, totalPages, total } = this.filteredPaginationInfo;
            if (totalPages > 1) {
                container.innerHTML += this.renderFilteredPagination();
            } else if (total > 0) {
                container.innerHTML += `<div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light"><small class="text-muted"><i class="bi bi-funnel me-1"></i>Filtrelenmiş: ${total} kayıt gösteriliyor</small></div>`;
            }
        } else if (!isFiltered && this.paginationInfo && this.paginationInfo.totalPages > 1) {
            container.innerHTML += this.renderPagination();
        }

        this.bindTableEvents(container);
        this.populateFilterSelects(container);
    },

    // Filtre select'lerini dinamik parametrelerden doldur
    async populateFilterSelects(container) {
        const currencies = await NbtParams.getCurrencies();
        container.querySelectorAll('select[data-currency-select]').forEach(select => {
            const currentValue = this.columnFilters[select.dataset.columnFilter] || '';
            let options = '<option value="">Tümü</option>';
            (currencies || []).forEach(c => {
                const selected = currentValue === c.Kod ? 'selected' : '';
                options += `<option value="${c.Kod}" ${selected}>${c.Kod}</option>`;
            });
            select.innerHTML = options;
        });
    },

    bindTableEvents(container) {
        container.querySelectorAll('[data-action="view"]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = parseInt(btn.dataset.id);
                const payment = (this.allData || this.data).find(p => parseInt(p.Id, 10) === id);
                if (payment) {
                    await NbtDetailModal.show('payment', payment, null, null);
                } else {
                    NbtToast.error('Ödeme kaydı bulunamadı');
                }
            });
        });

        container.querySelectorAll('[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const newPage = parseInt(link.dataset.page);
                if (!isNaN(newPage) && newPage !== this.currentPage) {
                    this.loadList(newPage);
                }
            });
        });

        container.querySelectorAll('[data-filtered-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const newPage = parseInt(link.dataset.filteredPage);
                if (!isNaN(newPage) && newPage !== this.filteredPage) {
                    this.applyFilters(newPage);
                }
            });
        });

        container.querySelectorAll('[data-action="apply-filters"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.columnFilters = {};
                container.querySelectorAll('[data-column-filter]').forEach(input => {
                    const field = input.dataset.columnFilter;
                    const value = input.value.trim();
                    if (value) this.columnFilters[field] = value;
                });
                this.applyFilters();
            });
        });

        container.querySelectorAll('[data-column-filter]').forEach(input => {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    container.querySelector('[data-action="apply-filters"]')?.click();
                }
            });
        });

        container.querySelectorAll('[data-action="clear-filters"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.columnFilters = {};
                this.allData = null;
                container.querySelectorAll('[data-column-filter]').forEach(input => {
                    input.value = '';
                });
                this.loadList(1);
            });
        });
    },

    renderPagination() {
        if (!this.paginationInfo) return '';
        const { page, totalPages, total, limit } = this.paginationInfo;
        const startIndex = (page - 1) * limit;
        const endIndex = Math.min(startIndex + limit, total);

        let pageButtons = '';
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="1"><i class="bi bi-chevron-double-left"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${page - 1}"><i class="bi bi-chevron-left"></i></a></li>`;
        
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        for (let i = startPage; i <= endPage; i++) {
            pageButtons += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
        
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${page + 1}"><i class="bi bi-chevron-right"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${totalPages}"><i class="bi bi-chevron-double-right"></i></a></li>`;

        return `
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light" id="paymentsPagination">
                <small class="text-muted">Toplam ${total} kayıttan ${startIndex + 1}-${endIndex} arası gösteriliyor</small>
                <nav><ul class="pagination pagination-sm mb-0">${pageButtons}</ul></nav>
            </div>
        `;
    },

    renderFilteredPagination() {
        if (!this.filteredPaginationInfo) return '';
        const { page, totalPages, total, limit } = this.filteredPaginationInfo;
        const startIndex = (page - 1) * limit;
        const endIndex = Math.min(startIndex + limit, total);

        let pageButtons = '';
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="1"><i class="bi bi-chevron-double-left"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${page - 1}"><i class="bi bi-chevron-left"></i></a></li>`;
        
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        for (let i = startPage; i <= endPage; i++) {
            pageButtons += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" data-filtered-page="${i}">${i}</a></li>`;
        }
        
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${page + 1}"><i class="bi bi-chevron-right"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${totalPages}"><i class="bi bi-chevron-double-right"></i></a></li>`;

        return `
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light">
                <small class="text-muted"><i class="bi bi-funnel me-1"></i>Filtrelenmiş: Toplam ${total} kayıttan ${startIndex + 1}-${endIndex} arası gösteriliyor</small>
                <nav><ul class="pagination pagination-sm mb-0">${pageButtons}</ul></nav>
            </div>
        `;
    },

    _musteriChangeHandler: null,
    _invoicesCache: [],
    
    async loadInvoicesForCustomer(musteriId, editingPayment = null) {
        const faturaSelect = document.getElementById('paymentFaturaId');
        if (!faturaSelect) return;
        
        faturaSelect.innerHTML = '<option value="">Fatura Seçiniz...</option>';
        this._invoicesCache = [];
        if (!musteriId) return;
        
        try {
            // Backend'den sadece kalan>0 olan faturaları al (sadece_odenmemis=1)
            const response = await NbtApi.get(`/api/invoices?musteri_id=${musteriId}&sadece_odenmemis=1`);
            // API zaten musteri_id ve kalan>0 ile filtreliyor
            let faturalar = response.data || [];
            
            // Edit modda: duzenlenen odemenin faturasini listeye ekle (kalan=0 olsa bile)
            if (editingPayment && editingPayment.FaturaId) {
                const editFaturaId = parseInt(editingPayment.FaturaId);
                const existsInList = faturalar.some(f => parseInt(f.Id) === editFaturaId);
                if (!existsInList) {
                    // Düzenlenen ödemenin faturası listede yok, ayrıca çek
                    try {
                        const faturaRes = await NbtApi.get(`/api/invoices/${editFaturaId}`);
                        if (faturaRes && faturaRes.Id) {
                            faturalar.push(faturaRes);
                        }
                    } catch (e) {
                        // Fatura bulunamazsa es geç
                    }
                }
            }
            
            this._invoicesCache = faturalar;
            
            if (faturalar.length === 0) {
                faturaSelect.innerHTML = '<option value="">Ödenmemiş fatura bulunamadı</option>';
                return;
            }
            
            faturalar.forEach(f => {
                let kalan = f.Kalan !== undefined && f.Kalan !== null 
                    ? parseFloat(f.Kalan) 
                    : parseFloat(f.Tutar) || 0;
                
                // Edit modda: duzenlenen odemenin faturasiysa, mevcut odeme tutarini kalan tutara ekle
                if (editingPayment && parseInt(f.Id) === parseInt(editingPayment.FaturaId)) {
                    kalan += parseFloat(editingPayment.Tutar) || 0;
                }
                
                const tutar = parseFloat(f.Tutar) || 0;
                const label = `FT${f.Id}/${NbtUtils.formatDate(f.Tarih)} - Kalan: ${NbtUtils.formatMoney(kalan, f.DovizCinsi)} / Toplam: ${NbtUtils.formatMoney(tutar, f.DovizCinsi)}`;
                faturaSelect.innerHTML += `<option value="${f.Id}" data-kalan="${kalan}" data-doviz="${f.DovizCinsi || 'TRY'}">${label}</option>`;
            });
        } catch (err) {
            NbtLogger.error('Fatura listesi alınamadı:', err);
        }
    },
    
    async openModal(id = null) {
        NbtModal.resetForm('paymentModal');
        document.getElementById('paymentModalTitle').textContent = id ? 'Ödeme Düzenle' : 'Yeni Ödeme';
        document.getElementById('paymentId').value = id || '';

        const select = document.getElementById('paymentMusteriId');
        CustomerDetailModule.populateCustomerSelect(select);
        select.disabled = false;

        const faturaSelect = document.getElementById('paymentFaturaId');
        if (faturaSelect) {
            faturaSelect.innerHTML = '<option value="">Fatura Seçiniz...</option>';
            
            if (this._musteriChangeHandler) {
                select.removeEventListener('change', this._musteriChangeHandler);
            }
            
            this._musteriChangeHandler = async () => {
                const musteriId = parseInt(select.value);
                await this.loadInvoicesForCustomer(musteriId);
            };
            select.addEventListener('change', this._musteriChangeHandler);
        }

        // Eger customer detail sayfasindaysak musteriyi auto-select et ve faturalarini yukle
        if (CustomerDetailModule.customerId) {
            select.value = CustomerDetailModule.customerId;
            select.disabled = true;
            await this.loadInvoicesForCustomer(CustomerDetailModule.customerId);
        }

        if (id) {
            const parsedId = parseInt(id, 10);
            let payment = this.data?.find(p => parseInt(p.Id, 10) === parsedId);
            if (!payment) {
                payment = CustomerDetailModule.data?.payments?.find(p => parseInt(p.Id, 10) === parsedId);
            }
            if (payment) {
                select.value = payment.MusteriId;
                // Projeleri yukleme - edit modda secili projeyi de gonder
                await CustomerDetailModule.populateProjectSelect('paymentProjeId', payment.ProjeId);
                document.getElementById('paymentTarih').value = payment.Tarih?.split('T')[0] || '';
                document.getElementById('paymentTutar').value = NbtUtils.formatDecimal(payment.Tutar) || '';
                document.getElementById('paymentAciklama').value = payment.Aciklama || '';
                if (faturaSelect && payment.FaturaId) {
                    // Edit modda mevcut odeme bilgisini gonder - kalan tutar hesaplamasi icin
                    await this.loadInvoicesForCustomer(payment.MusteriId, payment);
                    faturaSelect.value = payment.FaturaId;
                }
            } else {
                NbtToast.error('Ödeme kaydı bulunamadı');
                return;
            }
        } else {
            // Yeni kayit icin projeleri yukleme
            await CustomerDetailModule.populateProjectSelect('paymentProjeId');
        }

        NbtModal.open('paymentModal');
    },

    async save() {
        const id = document.getElementById('paymentId').value;
        const faturaIdVal = document.getElementById('paymentFaturaId')?.value;
        
        let musteriId = parseInt(document.getElementById('paymentMusteriId').value);
        if (!musteriId || isNaN(musteriId)) {
            musteriId = CustomerDetailModule.customerId;
        }
        
        const projeIdVal = document.getElementById('paymentProjeId').value;
        const data = {
            MusteriId: musteriId,
            ProjeId: projeIdVal ? parseInt(projeIdVal) : null,
            FaturaId: faturaIdVal ? parseInt(faturaIdVal) : null,
            Tarih: document.getElementById('paymentTarih').value,
            Tutar: parseFloat(document.getElementById('paymentTutar').value) || 0,
            Aciklama: document.getElementById('paymentAciklama').value.trim() || null
        };

        NbtModal.clearError('paymentModal');
        if (!data.MusteriId || isNaN(data.MusteriId)) {
            NbtModal.showFieldError('paymentModal', 'paymentMusteriId', 'Müşteri seçiniz');
            NbtModal.showError('paymentModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        if (!data.ProjeId) {
            NbtModal.showFieldError('paymentModal', 'paymentProjeId', 'Proje seçiniz');
            NbtModal.showError('paymentModal', 'Proje seçimi zorunludur');
            return;
        }
        if (!data.FaturaId) {
            NbtModal.showFieldError('paymentModal', 'paymentFaturaId', 'Fatura seçiniz');
            NbtModal.showError('paymentModal', 'Fatura seçimi zorunludur');
            return;
        }
        if (!data.Tarih) {
            NbtModal.showFieldError('paymentModal', 'paymentTarih', 'Tarih zorunludur');
            NbtModal.showError('paymentModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        if (!data.Tutar || data.Tutar <= 0) {
            NbtModal.showFieldError('paymentModal', 'paymentTutar', 'Tutar zorunludur');
            NbtModal.showError('paymentModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        
        // Secili faturanin kalan tutarini kontrol et
        const faturaSelect = document.getElementById('paymentFaturaId');
        const selectedOption = faturaSelect?.selectedOptions[0];
        if (selectedOption && selectedOption.dataset.kalan) {
            const kalanTutar = parseFloat(selectedOption.dataset.kalan) || 0;
            if (data.Tutar > kalanTutar) {
                const doviz = selectedOption.dataset.doviz || 'TRY';
                NbtModal.showFieldError('paymentModal', 'paymentTutar', `Ödeme tutarı faturanın kalan tutarını (${NbtUtils.formatMoney(kalanTutar, doviz)}) aşamaz`);
                NbtModal.showError('paymentModal', 'Ödeme tutarı fatura kalan tutarından büyük olamaz');
                return;
            }
        }

        NbtModal.setLoading('paymentModal', true);
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
            
            if (CustomerDetailModule.customerId) {
                await CustomerDetailModule.loadRelatedData('invoices', '/api/invoices');
                await CustomerDetailModule.loadRelatedData('payments', '/api/payments');
                CustomerDetailModule.switchTab(CustomerDetailModule.activeTab);
            }
        } catch (err) {
            NbtModal.showError('paymentModal', err.message);
        } finally {
            NbtModal.setLoading('paymentModal', false);
        }
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        document.getElementById('btnSavePayment')?.addEventListener('click', () => this.save());
    }
};

// =============================================
// GORUSME MODULU (MEETING)
// =============================================
const MeetingModule = {
    _eventsBound: false,

    async openModal(id = null) {
        NbtModal.resetForm('meetingModal');
        document.getElementById('meetingModalTitle').textContent = id ? 'Görüşme Düzenle' : 'Yeni Görüşme';
        document.getElementById('meetingId').value = id || '';

        // Yeni kayit icin musteri id'sini set et
        if (CustomerDetailModule.customerId) {
            document.getElementById('meetingMusteriId').value = CustomerDetailModule.customerId;
        }

        if (id) {
            const meeting = CustomerDetailModule.data.meetings?.find(m => parseInt(m.Id, 10) === parseInt(id, 10));
            if (meeting) {
                document.getElementById('meetingMusteriId').value = meeting.MusteriId;
                // Projeleri yukleme - edit modda secili projeyi de gonder
                await CustomerDetailModule.populateProjectSelect('meetingProjeId', meeting.ProjeId);
                document.getElementById('meetingTarih').value = meeting.Tarih?.split('T')[0] || '';
                document.getElementById('meetingKonu').value = meeting.Konu || '';
                document.getElementById('meetingKisi').value = meeting.Kisi || '';
                document.getElementById('meetingEposta').value = meeting.Eposta || '';
                document.getElementById('meetingTelefon').value = meeting.Telefon || '';
                document.getElementById('meetingNotlar').value = meeting.Notlar || '';
            } else {
                NbtToast.error('Görüşme kaydı bulunamadı');
                return;
            }
        } else {
            // Yeni kayit icin projeleri yukleme
            await CustomerDetailModule.populateProjectSelect('meetingProjeId');
        }

        NbtModal.open('meetingModal');
    },

    async save() {
        const id = document.getElementById('meetingId').value;
        
        let musteriId = parseInt(document.getElementById('meetingMusteriId').value);
        if (!musteriId || isNaN(musteriId)) {
            musteriId = CustomerDetailModule.customerId;
        }
        
        const projeIdVal = document.getElementById('meetingProjeId').value;
        const data = {
            MusteriId: musteriId,
            ProjeId: projeIdVal ? parseInt(projeIdVal) : null,
            Tarih: document.getElementById('meetingTarih').value,
            Konu: document.getElementById('meetingKonu').value.trim(),
            Kisi: document.getElementById('meetingKisi').value.trim() || null,
            Eposta: document.getElementById('meetingEposta').value.trim() || null,
            Telefon: document.getElementById('meetingTelefon').value.trim() || null,
            Notlar: document.getElementById('meetingNotlar').value.trim() || null
        };

        NbtModal.clearError('meetingModal');
        
        if (!data.MusteriId || isNaN(data.MusteriId)) {
            NbtModal.showError('meetingModal', 'Müşteri seçilmedi. Lütfen müşteri detay sayfasından işlem yapın.');
            return;
        }
        if (!data.ProjeId) {
            NbtModal.showFieldError('meetingModal', 'meetingProjeId', 'Proje seçiniz');
            NbtModal.showError('meetingModal', 'Proje seçimi zorunludur');
            return;
        }
        if (!data.Tarih) {
            NbtModal.showFieldError('meetingModal', 'meetingTarih', 'Tarih zorunludur');
            NbtModal.showError('meetingModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        if (!data.Konu) {
            NbtModal.showFieldError('meetingModal', 'meetingKonu', 'Konu zorunludur');
            NbtModal.showError('meetingModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }

        NbtModal.setLoading('meetingModal', true);
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
            
            const currentTab = CustomerDetailModule.activeTab;
            if (currentTab === 'takvim') {
                CustomerDetailModule.switchTab('takvim');
            } else {
                CustomerDetailModule.switchTab('gorusme');
            }
        } catch (err) {
            NbtModal.showError('meetingModal', err.message);
        } finally {
            NbtModal.setLoading('meetingModal', false);
        }
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        document.getElementById('btnSaveMeeting')?.addEventListener('click', () => this.save());
    }
};

// =============================================
// TAKVIM TAB MODULU (CUSTOMER DETAIL TAKVIM TABI)
// =============================================
const CalendarTabModule = {
    _eventsBound: false,

    async openModal(id = null) {
        NbtModal.resetForm('calendarModal');
        document.getElementById('calendarModalTitle').textContent = id ? 'Takvim Düzenle' : 'Yeni Takvim Kaydı';
        document.getElementById('calendarId').value = id || '';

        // Yeni kayit icin musteri id'sini set et
        if (CustomerDetailModule.customerId) {
            document.getElementById('calendarMusteriId').value = CustomerDetailModule.customerId;
        }

        // Ozet karakter sayaci
        const ozetInput = document.getElementById('calendarOzet');
        const ozetCount = document.getElementById('calendarOzetCount');
        if (ozetInput && ozetCount) {
            ozetInput.oninput = () => {
                ozetCount.textContent = ozetInput.value.length;
            };
        }

        if (id) {
            const calendar = CustomerDetailModule.data.calendars?.find(c => parseInt(c.Id, 10) === parseInt(id, 10));
            if (calendar) {
                document.getElementById('calendarMusteriId').value = calendar.MusteriId;
                // Projeleri yukleme - edit modda secili projeyi de gonder
                await CustomerDetailModule.populateProjectSelect('calendarProjeId', calendar.ProjeId);
                document.getElementById('calendarTerminTarihi').value = calendar.TerminTarihi?.split('T')[0] || '';
                document.getElementById('calendarOzet').value = calendar.Ozet || '';
                if (ozetCount) ozetCount.textContent = (calendar.Ozet || '').length;
            } else {
                NbtToast.error('Takvim kaydı bulunamadı');
                return;
            }
        } else {
            // Yeni kayit icin projeleri yukleme
            await CustomerDetailModule.populateProjectSelect('calendarProjeId');
        }

        NbtModal.open('calendarModal');
    },

    async save() {
        const id = document.getElementById('calendarId').value;
        
        let musteriId = parseInt(document.getElementById('calendarMusteriId').value);
        if (!musteriId || isNaN(musteriId)) {
            musteriId = CustomerDetailModule.customerId;
        }
        
        const projeIdVal = document.getElementById('calendarProjeId').value;
        const data = {
            MusteriId: musteriId,
            ProjeId: projeIdVal ? parseInt(projeIdVal) : null,
            TerminTarihi: document.getElementById('calendarTerminTarihi').value,
            Ozet: document.getElementById('calendarOzet').value.trim()
        };

        NbtModal.clearError('calendarModal');
        
        if (!data.MusteriId || isNaN(data.MusteriId)) {
            NbtModal.showError('calendarModal', 'Müşteri seçilmedi. Lütfen müşteri detay sayfasından işlem yapın.');
            return;
        }
        if (!data.ProjeId) {
            NbtModal.showFieldError('calendarModal', 'calendarProjeId', 'Proje seçiniz');
            NbtModal.showError('calendarModal', 'Proje seçimi zorunludur');
            return;
        }
        if (!data.TerminTarihi) {
            NbtModal.showFieldError('calendarModal', 'calendarTerminTarihi', 'Termin tarihi zorunludur');
            NbtModal.showError('calendarModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        if (!data.Ozet) {
            NbtModal.showFieldError('calendarModal', 'calendarOzet', 'Özet zorunludur');
            NbtModal.showError('calendarModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        if (data.Ozet.length > 255) {
            NbtModal.showFieldError('calendarModal', 'calendarOzet', 'Özet 255 karakteri geçemez');
            NbtModal.showError('calendarModal', 'Özet 255 karakteri geçemez');
            return;
        }

        NbtModal.setLoading('calendarModal', true);
        try {
            if (id) {
                await NbtApi.put(`/api/takvim/${id}`, data);
                NbtToast.success('Takvim kaydı güncellendi');
            } else {
                await NbtApi.post('/api/takvim', data);
                NbtToast.success('Takvim kaydı eklendi');
            }
            NbtModal.close('calendarModal');
            await CustomerDetailModule.loadRelatedData('calendars', '/api/takvim');
            CustomerDetailModule.switchTab('takvim');
            
            // Takvim verisi degisti - global state'i guncelle
            AppState.calendarNeedsRefresh = true;
            AppState.lastCalendarEventDate = data.TerminTarihi; // Hedef tarihi sakla
            
            // Dashboard takvimini yenile (varsa, ayni sayfadaysa)
            const dashCalendarContainer = document.getElementById('dashCalendar');
            if (typeof NbtCalendar !== 'undefined' && dashCalendarContainer) {
                // Eklenen kaydin termin tarihine gore NbtCalendar tarihini guncelle
                if (data.TerminTarihi) {
                    NbtCalendar.currentDate = new Date(data.TerminTarihi);
                }
                await NbtCalendar.loadEvents(null, NbtCalendar.currentDate.getMonth() + 1, NbtCalendar.currentDate.getFullYear());
                NbtCalendar.render(dashCalendarContainer, { events: NbtCalendar.events });
            }
            
            // Custom event dispatch et - diger moduller dinleyebilir
            window.dispatchEvent(new CustomEvent('calendarDataChanged', { detail: { action: id ? 'update' : 'create', data: data } }));
        } catch (err) {
            NbtModal.showError('calendarModal', err.message);
        } finally {
            NbtModal.setLoading('calendarModal', false);
        }
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        document.getElementById('btnSaveCalendar')?.addEventListener('click', () => this.save());
    }
};

// =============================================
// KISI MODULU (CONTACT)
// =============================================
const ContactModule = {
    _eventsBound: false,
    
    async openModal(id = null) {
        NbtModal.resetForm('contactModal');
        document.getElementById('contactModalTitle').textContent = id ? 'Kişi Düzenle' : 'Yeni Kişi';
        document.getElementById('contactId').value = id || '';

        // Yeni kayit icin musteri id'sini set et
        if (CustomerDetailModule.customerId) {
            document.getElementById('contactMusteriId').value = CustomerDetailModule.customerId;
        }

        if (id) {
            const contact = CustomerDetailModule.data.contacts?.find(c => parseInt(c.Id, 10) === parseInt(id, 10));
            if (contact) {
                document.getElementById('contactMusteriId').value = contact.MusteriId;
                // Projeleri yukleme - edit modda secili projeyi de gonder
                await CustomerDetailModule.populateProjectSelect('contactProjeId', contact.ProjeId);
                document.getElementById('contactAdSoyad').value = contact.AdSoyad || '';
                document.getElementById('contactUnvan').value = contact.Unvan || '';
                document.getElementById('contactTelefon').value = contact.Telefon || '';
                document.getElementById('contactDahiliNo').value = contact.DahiliNo || '';
                document.getElementById('contactEmail').value = contact.Email || '';
                document.getElementById('contactNotlar').value = contact.Notlar || '';
            } else {
                NbtToast.error('Kişi kaydı bulunamadı');
                return;
            }
        } else {
            // Yeni kayit icin projeleri yukleme
            await CustomerDetailModule.populateProjectSelect('contactProjeId');
        }

        NbtModal.open('contactModal');
    },

    async save() {
        const id = document.getElementById('contactId').value;
        
        let musteriId = parseInt(document.getElementById('contactMusteriId').value);
        if (!musteriId || isNaN(musteriId)) {
            musteriId = CustomerDetailModule.customerId;
        }
        
        const projeIdVal = document.getElementById('contactProjeId').value;
        const data = {
            MusteriId: musteriId,
            ProjeId: projeIdVal ? parseInt(projeIdVal) : null,
            AdSoyad: document.getElementById('contactAdSoyad').value.trim(),
            Unvan: document.getElementById('contactUnvan').value.trim() || null,
            Telefon: document.getElementById('contactTelefon').value.trim() || null,
            DahiliNo: document.getElementById('contactDahiliNo').value.trim() || null,
            Email: document.getElementById('contactEmail').value.trim() || null,
            Notlar: document.getElementById('contactNotlar').value.trim() || null
        };

        NbtModal.clearError('contactModal');
        
        if (!data.MusteriId || isNaN(data.MusteriId)) {
            NbtModal.showError('contactModal', 'Müşteri seçilmedi. Lütfen müşteri detay sayfasından işlem yapın.');
            return;
        }
        if (!data.ProjeId) {
            NbtModal.showFieldError('contactModal', 'contactProjeId', 'Proje seçiniz');
            NbtModal.showError('contactModal', 'Proje seçimi zorunludur');
            return;
        }
        if (!data.AdSoyad) {
            NbtModal.showFieldError('contactModal', 'contactAdSoyad', 'Ad Soyad zorunludur');
            NbtModal.showError('contactModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }

        NbtModal.setLoading('contactModal', true);
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
        } finally {
            NbtModal.setLoading('contactModal', false);
        }
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        document.getElementById('btnSaveContact')?.addEventListener('click', () => this.save());
    }
};

// =============================================
// DAMGA VERGISI MODULU (STAMP TAX)
// =============================================
const StampTaxModule = {
    _eventsBound: false,
    selectedFile: null,
    removeExistingFile: false,
    
    async openModal(id = null) {
        NbtModal.resetForm('stampTaxModal');
        document.getElementById('stampTaxModalTitle').textContent = id ? 'Damga Vergisi Düzenle' : 'Yeni Damga Vergisi';
        document.getElementById('stampTaxId').value = id || '';
        
        this.selectedFile = null;
        this.removeExistingFile = false;
        document.getElementById('stampTaxDosya').value = '';
        document.getElementById('stampTaxCurrentFile')?.classList.add('d-none');

        // Yeni kayit icin musteri id'sini set et
        if (CustomerDetailModule.customerId) {
            document.getElementById('stampTaxMusteriId').value = CustomerDetailModule.customerId;
        }
        
        // Doviz seceneklerini dinamik yukle
        const dovizSelect = document.getElementById('stampTaxDovizCinsi');
        if (dovizSelect) {
            await NbtParams.populateCurrencySelect(dovizSelect);
        }

        if (id) {
            const item = CustomerDetailModule.data.stampTaxes?.find(s => parseInt(s.Id, 10) === parseInt(id, 10));
            if (item) {
                document.getElementById('stampTaxMusteriId').value = item.MusteriId;
                // Projeleri yukleme - edit modda secili projeyi de gonder
                await CustomerDetailModule.populateProjectSelect('stampTaxProjeId', item.ProjeId);
                document.getElementById('stampTaxTarih').value = item.Tarih?.split('T')[0] || '';
                document.getElementById('stampTaxTutar').value = NbtUtils.formatDecimal(item.Tutar) || '';
                document.getElementById('stampTaxDovizCinsi').value = item.DovizCinsi || NbtParams.getDefaultCurrency();
                document.getElementById('stampTaxAciklama').value = item.Aciklama || '';
                
                if (item.DosyaAdi) {
                    document.getElementById('stampTaxCurrentFileName').textContent = item.DosyaAdi;
                    document.getElementById('stampTaxCurrentFile')?.classList.remove('d-none');
                }
            } else {
                NbtToast.error('Damga vergisi kaydı bulunamadı');
                return;
            }
        } else {
            // Yeni kayit icin projeleri yukleme
            await CustomerDetailModule.populateProjectSelect('stampTaxProjeId');
        }

        NbtModal.open('stampTaxModal');
    },
    
    validatePdfFile(file) {
        const errors = [];
        
        if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
            errors.push('Sadece PDF dosyası yükleyebilirsiniz.');
        }
        
        const maxSize = 10 * 1024 * 1024;
        if (file.size > maxSize) {
            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            errors.push(`Dosya boyutu çok büyük (${sizeMB}MB). Maksimum 10MB yüklenebilir.`);
        }
        
        if (file.size === 0) {
            errors.push('Dosya boş olamaz.');
        }
        
        return errors;
    },

    async save() {
        const id = document.getElementById('stampTaxId').value;
        const musteriIdElement = document.getElementById('stampTaxMusteriId');
        
        let musteriId = parseInt(musteriIdElement?.value);
        if (!musteriId || isNaN(musteriId)) {
            musteriId = CustomerDetailModule.customerId;
        }
        
        const projeIdVal = document.getElementById('stampTaxProjeId').value;
        const data = {
            MusteriId: musteriId,
            ProjeId: projeIdVal ? parseInt(projeIdVal) : null,
            Tarih: document.getElementById('stampTaxTarih').value,
            Tutar: parseFloat(document.getElementById('stampTaxTutar').value) || 0,
            DovizCinsi: document.getElementById('stampTaxDovizCinsi').value || NbtParams.getDefaultCurrency(),
            // BelgeNo kaldırıldı
            Aciklama: document.getElementById('stampTaxAciklama').value.trim() || null
        };

        NbtModal.clearError('stampTaxModal');
        if (!data.MusteriId || isNaN(data.MusteriId)) {
            NbtModal.showFieldError('stampTaxModal', 'stampTaxMusteriId', 'Müşteri seçilmedi');
            NbtModal.showError('stampTaxModal', 'Müşteri bilgisi eksik. Lütfen sayfayı yenileyin.');
            return;
        }
        if (!data.ProjeId) {
            NbtModal.showFieldError('stampTaxModal', 'stampTaxProjeId', 'Proje seçiniz');
            NbtModal.showError('stampTaxModal', 'Proje seçimi zorunludur');
            return;
        }
        if (!data.Tarih) {
            NbtModal.showFieldError('stampTaxModal', 'stampTaxTarih', 'Tarih zorunludur');
            NbtModal.showError('stampTaxModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        if (!data.Tutar || data.Tutar <= 0) {
            NbtModal.showFieldError('stampTaxModal', 'stampTaxTutar', 'Tutar zorunludur');
            NbtModal.showError('stampTaxModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        
        const fileInput = document.getElementById('stampTaxDosya');
        const file = fileInput?.files?.[0];
        if (file) {
            const fileErrors = this.validatePdfFile(file);
            if (fileErrors.length > 0) {
                fileInput.classList.add('is-invalid');
                document.getElementById('stampTaxDosyaError').textContent = fileErrors.join(' ');
                NbtModal.showError('stampTaxModal', fileErrors.join(' '));
                return;
            }
        }

        NbtModal.setLoading('stampTaxModal', true);
        try {
            let result;
            
            if (file || this.removeExistingFile) {
                const formData = new FormData();
                formData.append('MusteriId', data.MusteriId);
                if (data.ProjeId) formData.append('ProjeId', data.ProjeId);
                formData.append('Tarih', data.Tarih);
                formData.append('Tutar', data.Tutar);
                formData.append('DovizCinsi', data.DovizCinsi);
                if (data.BelgeNo) formData.append('BelgeNo', data.BelgeNo);
                if (data.Aciklama) formData.append('Aciklama', data.Aciklama);
                if (file) formData.append('dosya', file);
                if (this.removeExistingFile) formData.append('removeFile', '1');
                
                const url = id ? `/api/stamp-taxes/${id}` : '/api/stamp-taxes';
                // PUT isteklerinde multipart/form-data PHP tarafindan parse edilmez
                // Bu yuzden formData ile gonderirken POST kullaniyoruz
                const method = 'POST';
                if (id) formData.append('_method', 'PUT');
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Authorization': 'Bearer ' + NbtUtils.getToken(),
                        'X-Tab-Id': NbtUtils.getTabId()
                    },
                    body: formData
                });
                
                const text = await response.text();
                try {
                    result = JSON.parse(text);
                } catch (parseErr) {
                    throw new Error('Sunucu hatası: Geçersiz yanıt');
                }
                
                if (!response.ok) {
                    throw new Error(result.error || 'İşlem başarısız');
                }
            } else {
                if (id) {
                    result = await NbtApi.put(`/api/stamp-taxes/${id}`, data);
                } else {
                    result = await NbtApi.post('/api/stamp-taxes', data);
                }
            }
            
            NbtToast.success(id ? 'Damga vergisi güncellendi' : 'Damga vergisi eklendi');
            NbtModal.close('stampTaxModal');
            await CustomerDetailModule.loadRelatedData('stampTaxes', '/api/stamp-taxes');
            CustomerDetailModule.switchTab('damgavergisi');
        } catch (err) {
            NbtModal.showError('stampTaxModal', err.message);
        } finally {
            NbtModal.setLoading('stampTaxModal', false);
        }
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        document.getElementById('btnSaveStampTax')?.addEventListener('click', () => this.save());
        
        document.getElementById('stampTaxDosya')?.addEventListener('change', (e) => {
            const file = e.target.files?.[0];
            const errorEl = document.getElementById('stampTaxDosyaError');
            e.target.classList.remove('is-invalid');
            if (errorEl) errorEl.textContent = '';
            
            if (file) {
                const errors = this.validatePdfFile(file);
                if (errors.length > 0) {
                    e.target.classList.add('is-invalid');
                    if (errorEl) errorEl.textContent = errors.join(' ');
                }
            }
        });
        
        document.getElementById('btnRemoveStampTaxFile')?.addEventListener('click', () => {
            this.removeExistingFile = true;
            document.getElementById('stampTaxCurrentFile')?.classList.add('d-none');
        });
    }
};

// =============================================
// DOSYA MODULU (FILE)
// =============================================
const FileModule = {
    _eventsBound: false,
    editingId: null,
    
    async openModal(id = null) {
        NbtModal.resetForm('fileModal');
        this.editingId = id;
        
        if (id) {
            // Duzenleme modu
            document.getElementById('fileModalTitle').textContent = 'Dosya Düzenle';
            document.getElementById('fileInput').closest('.row').style.display = 'none'; // Dosya degistirilmez
            
            // Mevcut dosya bilgilerini yukle
            const parsedId = parseInt(id, 10);
            let fileData = CustomerDetailModule.data?.files?.find(f => parseInt(f.Id, 10) === parsedId);
            
            if (fileData) {
                if (fileData.MusteriId) {
                    document.getElementById('fileMusteriId').value = fileData.MusteriId;
                }
                // Edit modda secili projeyi de gonder
                await CustomerDetailModule.populateProjectSelect('fileProjeId', fileData.ProjeId);
                document.getElementById('fileAciklama').value = fileData.Aciklama || '';
            }
        } else {
            // Yeni kayit modu
            document.getElementById('fileModalTitle').textContent = 'Dosya Yükle';
            document.getElementById('fileInput').value = '';
            document.getElementById('fileInput').closest('.row').style.display = '';
            
            // Yeni kayit icin musteri id'sini set et
            if (CustomerDetailModule.customerId) {
                document.getElementById('fileMusteriId').value = CustomerDetailModule.customerId;
            }

            // Projeleri yukleme
            await CustomerDetailModule.populateProjectSelect('fileProjeId');
        }
        
        NbtModal.open('fileModal');
    },

    async save() {
        let musteriId = document.getElementById('fileMusteriId').value;
        
        if (!musteriId || musteriId === '' || musteriId === '0') {
            musteriId = CustomerDetailModule.customerId;
        }
        
        const projeIdVal = document.getElementById('fileProjeId').value;
        const fileInput = document.getElementById('fileInput');
        const aciklama = document.getElementById('fileAciklama').value.trim();

        NbtModal.clearError('fileModal');
        
        if (!musteriId || isNaN(parseInt(musteriId))) {
            NbtModal.showError('fileModal', 'Müşteri seçilmedi. Lütfen müşteri detay sayfasından işlem yapın.');
            return;
        }
        
        if (!projeIdVal) {
            NbtModal.showFieldError('fileModal', 'fileProjeId', 'Proje seçiniz');
            NbtModal.showError('fileModal', 'Proje seçimi zorunludur');
            return;
        }
        
        // Duzenleme modunda dosya kontrolu yapma
        if (!this.editingId) {
            if (!fileInput.files || !fileInput.files[0]) {
                NbtModal.showFieldError('fileModal', 'fileInput', 'Dosya seçiniz');
                NbtModal.showError('fileModal', 'Lütfen bir dosya seçin');
                return;
            }

            const maxSize = 10 * 1024 * 1024; // 10MB
            const file = fileInput.files[0];
            if (file.size > maxSize) {
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                NbtModal.showFieldError('fileModal', 'fileInput', `Dosya boyutu çok büyük (${sizeMB}MB). Maksimum 10MB yüklenebilir.`);
                NbtModal.showError('fileModal', 'Dosya boyutu 10MB\'ı aşamaz');
                return;
            }

            // Izin verilen dosya turleri kontrolu
            const allowedTypes = [
                'application/pdf', 
                'application/msword', 
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'image/jpeg', 
                'image/png', 
                'image/gif'
            ];
            const allowedExtensions = ['.pdf', '.doc', '.docx', '.xls', '.xlsx', '.jpg', '.jpeg', '.png', '.gif'];
            const fileExt = '.' + file.name.split('.').pop().toLowerCase();
            
            if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExt)) {
                document.getElementById('fileInput').classList.add('is-invalid');
                document.getElementById('fileInputError').textContent = 'Bu dosya türü desteklenmiyor.';
                NbtModal.showError('fileModal', 'İzin verilen türler: PDF, Word, Excel, Resimler (JPG, PNG, GIF)');
                return;
            }
        }

        NbtModal.setLoading('fileModal', true);
        try {
            if (this.editingId) {
                // Duzenleme: JSON ile PUT
                const data = {
                    ProjeId: projeIdVal ? parseInt(projeIdVal) : null,
                    Aciklama: aciklama || null
                };
                await NbtApi.put(`/api/files/${this.editingId}`, data);
                NbtToast.success('Dosya güncellendi');
            } else {
                // Yeni kayit: FormData ile POST
                const formData = new FormData();
                formData.append('file', fileInput.files[0]);
                formData.append('MusteriId', musteriId);
                if (projeIdVal) formData.append('ProjeId', projeIdVal);
                if (aciklama) formData.append('Aciklama', aciklama);

                const response = await fetch('/api/files', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + NbtUtils.getToken(),
                        'X-Tab-Id': NbtUtils.getTabId()
                    },
                    body: formData
                });

                const text = await response.text();
                let result;
                try {
                    result = JSON.parse(text);
                } catch (parseErr) {
                    NbtLogger.error('API Response (not JSON):', text);
                    throw new Error('Sunucu hatası: Geçersiz yanıt');
                }
                
                if (!response.ok) {
                    throw new Error(result.error || 'Dosya yüklenemedi');
                }
                NbtToast.success('Dosya yüklendi');
            }
            
            NbtModal.close('fileModal');
            await CustomerDetailModule.loadRelatedData('files', '/api/files');
            CustomerDetailModule.switchTab('dosyalar');
        } catch (err) {
            NbtModal.showError('fileModal', err.message);
        } finally {
            NbtModal.setLoading('fileModal', false);
        }
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        document.getElementById('btnSaveFile')?.addEventListener('click', () => this.save());
    }
};

// =============================================
// PROJE MODULU
// =============================================
const ProjectModule = {
    _eventsBound: false,
    data: [],
    pageSize: window.APP_CONFIG?.PAGINATION_DEFAULT || 10,
    currentPage: 1,
    paginationInfo: null,
    searchQuery: '',
    columnFilters: {},
    // Filtre icin ek property'ler
    allData: null,
    allDataLoading: false,
    filteredPage: 1,
    filteredPaginationInfo: null,
    
    async init() {
        this.pageSize = NbtParams.getPaginationDefault();
        // Durum parametrelerini onceden yukle (cache'e al)
        await NbtParams.getStatuses('proje');
        await this.loadList();
        this.initToolbar();
        this.bindEvents();
    },

    async loadList(page = 1) {
        const container = document.getElementById('projectsTableContainer');
        if (!container) return;
        try {
            container.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
            const response = await NbtApi.get(`/api/projects?page=${page}&limit=${this.pageSize}`);
            this.data = response.data || [];
            this.paginationInfo = response.pagination || null;
            this.currentPage = page;
            this.filteredPaginationInfo = null;
            this.renderTable(this.data);
        } catch (err) {
            container.innerHTML = `<div class="alert alert-danger m-3">${err.message}</div>`;
        }
    },

    initToolbar() {
        const toolbarContainer = document.getElementById('projectsToolbar');
        if (!toolbarContainer || toolbarContainer.children.length > 0) return;
        
        toolbarContainer.innerHTML = NbtListToolbar.create({
            onSearch: false,
            onAdd: false,
            onFilter: false
        });

        const panel = document.getElementById('panelProjects');
        NbtListToolbar.bind(toolbarContainer, {
            panelElement: panel
        });
    },

    async applyFilters(page = 1) {
        const hasFilters = Object.keys(this.columnFilters).length > 0;
        if (!hasFilters) {
            this.allData = null;
            this.filteredPaginationInfo = null;
            this.loadList(1);
            return;
        }
        
        if (!this.allData && !this.allDataLoading) {
            this.allDataLoading = true;
            const container = document.getElementById('projectsTableContainer');
            if (container) {
                container.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> <small class="text-muted ms-2">Arama yapılıyor...</small></div>';
            }
            try {
                const response = await NbtApi.get('/api/projects?page=1&limit=10000');
                this.allData = response.data || [];
            } catch (err) {
                this.allData = this.data || [];
            }
            this.allDataLoading = false;
        }
        
        if (this.allDataLoading) {
            setTimeout(() => this.applyFilters(page), 100);
            return;
        }
        
        let filtered = this.allData || [];
        
        // Kolon filtreleri - CustomerDetailModule mantigiyla
        Object.keys(this.columnFilters).forEach(field => {
            const value = this.columnFilters[field];
            if (!value) return;
            
            // Tarih araligi baslangic filtresi
            if (field.endsWith('_start')) {
                const baseField = field.replace('_start', '');
                filtered = filtered.filter(item => {
                    let cellValue = item[baseField];
                    if (!cellValue) return false;
                    const cellDate = NbtUtils.formatDateForCompare(cellValue);
                    return cellDate >= value;
                });
                return;
            }
            
            // Tarih araligi bitis filtresi
            if (field.endsWith('_end')) {
                const baseField = field.replace('_end', '');
                filtered = filtered.filter(item => {
                    let cellValue = item[baseField];
                    if (!cellValue) return false;
                    const cellDate = NbtUtils.formatDateForCompare(cellValue);
                    return cellDate <= value;
                });
                return;
            }
            
            // Durum icin exact match
            if (field === 'Durum') {
                filtered = filtered.filter(item => {
                    const cellValue = String(item[field] ?? '');
                    return cellValue === value;
                });
                return;
            }
            
            // Normal alanlar icin filtre
            filtered = filtered.filter(item => {
                let cellValue = item[field];
                return NbtUtils.normalizeText(cellValue).includes(NbtUtils.normalizeText(value));
            });
        });
        
        this.filteredPage = page;
        const total = filtered.length;
        const totalPages = Math.ceil(total / this.pageSize);
        const startIndex = (page - 1) * this.pageSize;
        const endIndex = Math.min(startIndex + this.pageSize, total);
        const pageData = filtered.slice(startIndex, endIndex);
        
        this.filteredPaginationInfo = { page, limit: this.pageSize, total, totalPages };
        this.renderTable(pageData, true);
    },

    renderTable(data, isFiltered = false) {
        const container = document.getElementById('projectsTableContainer');
        if (!container) return;
        const columns = [
            { field: 'MusteriUnvan', label: 'Müşteri' },
            { field: 'ProjeAdi', label: 'Proje Adı' },
            { field: 'Durum', label: 'Durum', render: v => this.getStatusBadge(v, 'proje'), statusType: 'proje' }
        ];

        const headers = columns.map(c => `<th class="bg-light px-3">${c.label}</th>`).join('') + 
            '<th class="bg-light text-center px-3" style="width:140px;">İşlem</th>';

        // Filter row - CustomerDetailModule mantigiyla
        const filterRow = columns.map(c => {
            const currentValue = this.columnFilters[c.field] || '';
            const startValue = this.columnFilters[c.field + '_start'] || '';
            const endValue = this.columnFilters[c.field + '_end'] || '';
            
            // Tarih alanlari icin cift date input
            if (c.isDate) {
                return `<th class="p-1" style="min-width:200px;">
                    <div class="d-flex gap-1">
                        <input type="date" class="form-control form-control-sm" data-column-filter="${c.field}_start" data-table-id="projects" value="${NbtUtils.escapeHtml(startValue)}" title="Başlangıç">
                        <input type="date" class="form-control form-control-sm" data-column-filter="${c.field}_end" data-table-id="projects" value="${NbtUtils.escapeHtml(endValue)}" title="Bitiş">
                    </div>
                </th>`;
            }
            
            // Durum alani icin select - dinamik olarak doldurulacak
            if (c.field === 'Durum') {
                return `<th class="p-1">
                    <select class="form-select form-select-sm" data-column-filter="${c.field}" data-table-id="projects" data-status-type="proje">
                        <option value="">Tümü</option>
                    </select>
                </th>`;
            }
            
            return `<th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Ara..." data-column-filter="${c.field}" data-table-id="projects" value="${NbtUtils.escapeHtml(currentValue)}"></th>`;
        }).join('') + `<th class="p-1 text-center">
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-sm btn-outline-primary" data-action="apply-filters" title="Ara"><i class="bi bi-search"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-action="clear-filters" title="Filtreleri Temizle"><i class="bi bi-x-lg"></i></button>
            </div>
        </th>`;

        let rowsHtml = '';
        if (!data || data.length === 0) {
            rowsHtml = `<tr><td colspan="${columns.length + 1}" class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Proje bulunamadı</td></tr>`;
        } else {
            rowsHtml = data.map(row => {
                const cells = columns.map(c => {
                    let val = row[c.field];
                    if (c.render) val = c.render(val, row);
                    return `<td data-field="${c.field}" class="px-3">${val ?? '-'}</td>`;
                }).join('');
                
                return `
                    <tr data-id="${row.Id}">
                        ${cells}
                        <td class="text-center px-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary btn-sm" type="button" data-action="view" data-id="${row.Id}" title="Detay" data-can="projects.read">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a class="btn btn-outline-info btn-sm" href="/customer/${row.MusteriId}?tab=projeler" title="Müşteriye Git" data-can="customers.read">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </div>
                        </td>
                    </tr>`;
            }).join('');
        }

        container.innerHTML = `
            <div class="table-responsive p-2">
                <table class="table table-bordered table-hover table-sm mb-0" id="projectsTable">
                    <thead>
                        <tr>${headers}</tr>
                        <tr class="bg-white">${filterRow}</tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            </div>`;

        if (isFiltered && this.filteredPaginationInfo) {
            const { page, totalPages, total } = this.filteredPaginationInfo;
            if (totalPages > 1) {
                container.innerHTML += this.renderFilteredPagination();
            } else if (total > 0) {
                container.innerHTML += `<div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light"><small class="text-muted"><i class="bi bi-funnel me-1"></i>Filtrelenmiş: ${total} kayıt gösteriliyor</small></div>`;
            }
        } else if (!isFiltered && this.paginationInfo && this.paginationInfo.totalPages > 1) {
            container.innerHTML += this.renderPagination();
        }

        this.bindTableEvents(container);
        this.populateFilterSelects(container);
    },

    // Filtre select'lerini dinamik parametrelerden doldur
    async populateFilterSelects(container) {
        // Status select'lerini doldur
        for (const select of container.querySelectorAll('select[data-status-type]')) {
            const statusType = select.dataset.statusType;
            const statuses = await NbtParams.getStatuses(statusType);
            const currentValue = this.columnFilters[select.dataset.columnFilter] || '';
            
            let options = '<option value="">Tümü</option>';
            (statuses || []).forEach(s => {
                const selected = String(currentValue) === String(s.Kod) ? 'selected' : '';
                options += `<option value="${s.Kod}" ${selected}>${NbtUtils.escapeHtml(s.Etiket)}</option>`;
            });
            select.innerHTML = options;
        }
    },

    bindTableEvents(container) {
        container.querySelectorAll('[data-action="view"]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = parseInt(btn.dataset.id);
                const project = (this.allData || this.data).find(p => parseInt(p.Id, 10) === id);
                if (project) {
                    await NbtDetailModal.show('project', project, null, null);
                } else {
                    NbtToast.error('Proje kaydı bulunamadı');
                }
            });
        });

        container.querySelectorAll('[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const newPage = parseInt(link.dataset.page);
                if (!isNaN(newPage) && newPage !== this.currentPage) {
                    this.loadList(newPage);
                }
            });
        });

        container.querySelectorAll('[data-filtered-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const newPage = parseInt(link.dataset.filteredPage);
                if (!isNaN(newPage) && newPage !== this.filteredPage) {
                    this.applyFilters(newPage);
                }
            });
        });

        container.querySelectorAll('[data-action="apply-filters"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.columnFilters = {};
                container.querySelectorAll('[data-column-filter]').forEach(input => {
                    const field = input.dataset.columnFilter;
                    const value = input.value.trim();
                    if (value) this.columnFilters[field] = value;
                });
                this.applyFilters();
            });
        });

        container.querySelectorAll('[data-column-filter]').forEach(input => {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    container.querySelector('[data-action="apply-filters"]')?.click();
                }
            });
        });

        container.querySelectorAll('[data-action="clear-filters"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.columnFilters = {};
                this.allData = null;
                container.querySelectorAll('[data-column-filter]').forEach(input => {
                    input.value = '';
                });
                this.loadList(1);
            });
        });
    },

    renderPagination() {
        if (!this.paginationInfo) return '';
        const { page, totalPages, total, limit } = this.paginationInfo;
        const startIndex = (page - 1) * limit;
        const endIndex = Math.min(startIndex + limit, total);

        let pageButtons = '';
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="1"><i class="bi bi-chevron-double-left"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${page - 1}"><i class="bi bi-chevron-left"></i></a></li>`;
        
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        for (let i = startPage; i <= endPage; i++) {
            pageButtons += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
        
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${page + 1}"><i class="bi bi-chevron-right"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${totalPages}"><i class="bi bi-chevron-double-right"></i></a></li>`;

        return `
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light" id="projectsPagination">
                <small class="text-muted">Toplam ${total} kayıttan ${startIndex + 1}-${endIndex} arası gösteriliyor</small>
                <nav><ul class="pagination pagination-sm mb-0">${pageButtons}</ul></nav>
            </div>
        `;
    },

    renderFilteredPagination() {
        if (!this.filteredPaginationInfo) return '';
        const { page, totalPages, total, limit } = this.filteredPaginationInfo;
        const startIndex = (page - 1) * limit;
        const endIndex = Math.min(startIndex + limit, total);

        let pageButtons = '';
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="1"><i class="bi bi-chevron-double-left"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${page - 1}"><i class="bi bi-chevron-left"></i></a></li>`;
        
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        for (let i = startPage; i <= endPage; i++) {
            pageButtons += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" data-filtered-page="${i}">${i}</a></li>`;
        }
        
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${page + 1}"><i class="bi bi-chevron-right"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${totalPages}"><i class="bi bi-chevron-double-right"></i></a></li>`;

        return `
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light">
                <small class="text-muted"><i class="bi bi-funnel me-1"></i>Filtrelenmiş: Toplam ${total} kayıttan ${startIndex + 1}-${endIndex} arası gösteriliyor</small>
                <nav><ul class="pagination pagination-sm mb-0">${pageButtons}</ul></nav>
            </div>
        `;
    },

    async openModal(id = null) {
        NbtModal.resetForm('projectModal');
        document.getElementById('projectModalTitle').textContent = id ? 'Proje Düzenle' : 'Yeni Proje';
        document.getElementById('projectId').value = id || '';

        const select = document.getElementById('projectMusteriId');
        CustomerDetailModule.populateCustomerSelect(select);
        select.disabled = false;

        // Durum select'ini parametrelerden doldur
        const statusSelect = document.getElementById('projectStatus');
        if (statusSelect) {
            await NbtParams.populateStatusSelect(statusSelect, 'proje');
        }

        // Eger customer detail sayfasindaysak musteriyi auto-select et ve disable yap
        if (CustomerDetailModule.customerId) {
            select.value = CustomerDetailModule.customerId;
            select.disabled = true;
        }

        if (id) {
            const parsedId = parseInt(id, 10);
            let project = this.data?.find(p => parseInt(p.Id, 10) === parsedId);
            if (!project) {
                project = CustomerDetailModule.data?.projects?.find(p => parseInt(p.Id, 10) === parsedId);
            }
            if (project) {
                select.value = project.MusteriId;
                select.disabled = true; // Duzenlemede musteri kilitli
                document.getElementById('projectName').value = project.ProjeAdi || '';
                document.getElementById('projectStatus').value = project.Durum || '';
            } else {
                NbtToast.error('Proje kaydı bulunamadı');
                return;
            }
        }

        NbtModal.open('projectModal');
    },

    async save() {
        const id = document.getElementById('projectId').value;
        
        let musteriId = parseInt(document.getElementById('projectMusteriId').value);
        if (!musteriId || isNaN(musteriId)) {
            musteriId = CustomerDetailModule.customerId;
        }
        
        const data = {
            MusteriId: musteriId,
            ProjeAdi: document.getElementById('projectName').value.trim(),
            Durum: document.getElementById('projectStatus').value
        };

        NbtModal.clearError('projectModal');
        if (!data.MusteriId || isNaN(data.MusteriId)) {
            NbtModal.showFieldError('projectModal', 'projectMusteriId', 'Müşteri seçiniz');
            NbtModal.showError('projectModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        if (!data.ProjeAdi) {
            NbtModal.showFieldError('projectModal', 'projectName', 'Proje adı zorunludur');
            NbtModal.showError('projectModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }

        NbtModal.setLoading('projectModal', true);
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
            
            if (CustomerDetailModule.customerId) {
                await CustomerDetailModule.loadRelatedData('projects', '/api/projects');
                CustomerDetailModule.switchTab(CustomerDetailModule.activeTab);
            }
        } catch (err) {
            NbtModal.showError('projectModal', err.message);
        } finally {
            NbtModal.setLoading('projectModal', false);
        }
    },

    // Durum badge'ini dinamik parametrelerden olustur
    getStatusBadge(status, entity) {
        const cacheKey = `durum_${entity}`;
        const statuses = NbtParams._cache.statuses[cacheKey] || [];
        
        const found = statuses.find(s => s.Kod == status);
        if (found) {
            const badge = found.Deger || 'secondary';
            const textClass = (badge === 'warning' || badge === 'light') ? ' text-dark' : '';
            return `<span class="badge bg-${badge}${textClass}">${NbtUtils.escapeHtml(found.Etiket)}</span>`;
        }
        
        // Fallback - cache henuz yuklenmediyse
        const fallback = { 1: ['Aktif', 'success'], 2: ['Tamamlandı', 'info'], 3: ['İptal', 'danger'] };
        const config = fallback[status] || ['Bilinmiyor', 'secondary'];
        return `<span class="badge bg-${config[1]}">${config[0]}</span>`;
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        document.getElementById('btnSaveProject')?.addEventListener('click', () => this.save());
    }
};

// =============================================
// LOG MODULU
// =============================================
const LogModule = {
    _eventsBound: false,
    data: [],
    pageSize: window.APP_CONFIG?.PAGINATION_DEFAULT || 10,
    currentPage: 1,
    paginationInfo: null,
    columnFilters: {},
    // Filtre icin ek property'ler
    allData: null,
    allDataLoading: false,
    filteredPage: 1,
    filteredPaginationInfo: null,
    // Cift tiklama icin
    lastClickTime: 0,
    lastClickedRow: null,

    async init() {
        this.pageSize = NbtParams.getPaginationDefault();
        await this.loadList();
        this.initToolbar();
        this.bindEvents();
    },

    async loadList(page = 1) {
        const container = document.getElementById('logsTableContainer');
        if (!container) return;
        try {
            container.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
            const response = await NbtApi.get(`/api/logs?page=${page}&limit=${this.pageSize}`);
            this.data = response.data || [];
            this.paginationInfo = response.pagination || null;
            this.currentPage = page;
            this.filteredPaginationInfo = null;
            this.renderTable(this.data);
        } catch (err) {
            container.innerHTML = `<div class="alert alert-danger m-3">${err.message}</div>`;
        }
    },

    initToolbar() {
        const toolbarContainer = document.getElementById('logsToolbar');
        if (!toolbarContainer || toolbarContainer.children.length > 0) return;
        
        toolbarContainer.innerHTML = NbtListToolbar.create({
            onSearch: false,
            onAdd: false,
            onFilter: false
        });

        const panel = document.getElementById('panelLogs');
        NbtListToolbar.bind(toolbarContainer, {
            panelElement: panel
        });
    },

    async applyFilters(page = 1) {
        const hasFilters = Object.keys(this.columnFilters).length > 0;
        if (!hasFilters) {
            this.allData = null;
            this.filteredPaginationInfo = null;
            this.loadList(1);
            return;
        }
        
        // Tum verileri yukle
        if (!this.allData && !this.allDataLoading) {
            this.allDataLoading = true;
            const container = document.getElementById('logsTableContainer');
            if (container) {
                container.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> <small class="text-muted ms-2">Arama yapılıyor...</small></div>';
            }
            try {
                const response = await NbtApi.get('/api/logs?page=1&limit=10000');
                this.allData = response.data || [];
            } catch (err) {
                this.allData = this.data || [];
            }
            this.allDataLoading = false;
        }
        
        if (this.allDataLoading) {
            setTimeout(() => this.applyFilters(page), 100);
            return;
        }
        
        let filtered = this.allData || [];
        
        // Kolon filtreleri
        Object.keys(this.columnFilters).forEach(field => {
            const value = this.columnFilters[field];
            if (!value) return;
            
            // Tarih araligi baslangic filtresi
            if (field.endsWith('_start')) {
                const baseField = field.replace('_start', '');
                filtered = filtered.filter(item => {
                    let cellValue = item[baseField];
                    if (!cellValue) return false;
                    const cellDate = NbtUtils.formatDateForCompare(cellValue);
                    return cellDate >= value;
                });
                return;
            }
            
            // Tarih araligi bitis filtresi
            if (field.endsWith('_end')) {
                const baseField = field.replace('_end', '');
                filtered = filtered.filter(item => {
                    let cellValue = item[baseField];
                    if (!cellValue) return false;
                    const cellDate = NbtUtils.formatDateForCompare(cellValue);
                    return cellDate <= value;
                });
                return;
            }
            
            // Islem alani icin exact match
            if (field === 'Islem') {
                filtered = filtered.filter(item => {
                    const cellValue = String(item[field] ?? '');
                    return cellValue === value;
                });
                return;
            }
            
            // Normal alanlar icin filtre
            filtered = filtered.filter(item => {
                let cellValue = item[field];
                return NbtUtils.normalizeText(cellValue).includes(NbtUtils.normalizeText(value));
            });
        });
        
        this.filteredPage = page;
        const total = filtered.length;
        const totalPages = Math.ceil(total / this.pageSize);
        const startIndex = (page - 1) * this.pageSize;
        const endIndex = Math.min(startIndex + this.pageSize, total);
        const pageData = filtered.slice(startIndex, endIndex);
        
        this.filteredPaginationInfo = { page, limit: this.pageSize, total, totalPages };
        this.renderTable(pageData, true);
    },

    renderTable(data, isFiltered = false) {
        const container = document.getElementById('logsTableContainer');
        if (!container) return;
        
        const columns = [
            { field: 'EklemeZamani', label: 'Zaman', render: v => NbtUtils.formatDate(v, 'long'), isDate: true },
            { field: 'KullaniciAdi', label: 'Kullanıcı' },
            { field: 'Islem', label: 'İşlem', render: v => {
                const colors = { INSERT: 'success', UPDATE: 'warning', DELETE: 'danger', SELECT: 'info', login: 'primary' };
                return `<span class="badge bg-${colors[v] || 'secondary'}">${v}</span>`;
            }, isSelect: true },
            { field: 'Tablo', label: 'Tablo' },
            { field: 'YeniDeger', label: 'Detay', render: v => {
                if (!v) return '-';
                const text = typeof v === 'object' ? JSON.stringify(v) : v;
                const display = String(text).length > 40 ? String(text).substring(0, 40) + '...' : text;
                return `<small class="text-muted" title="Detay için çift tıklayın">${NbtUtils.escapeHtml(display)}</small>`;
            }}
        ];

        const headers = columns.map(c => `<th class="bg-light px-3">${c.label}</th>`).join('') + 
            '<th class="bg-light text-center px-3" style="width:80px;">İncele</th>';

        // Filter row
        const filterRow = columns.map(c => {
            const currentValue = this.columnFilters[c.field] || '';
            const startValue = this.columnFilters[c.field + '_start'] || '';
            const endValue = this.columnFilters[c.field + '_end'] || '';
            
            // Tarih alanlari icin cift date input
            if (c.isDate) {
                return `<th class="p-1" style="min-width:200px;">
                    <div class="d-flex gap-1">
                        <input type="date" class="form-control form-control-sm" data-column-filter="${c.field}_start" data-table-id="logs" value="${NbtUtils.escapeHtml(startValue)}" title="Başlangıç">
                        <input type="date" class="form-control form-control-sm" data-column-filter="${c.field}_end" data-table-id="logs" value="${NbtUtils.escapeHtml(endValue)}" title="Bitiş">
                    </div>
                </th>`;
            }
            
            // Islem alani icin select
            if (c.field === 'Islem') {
                return `<th class="p-1">
                    <select class="form-select form-select-sm" data-column-filter="${c.field}" data-table-id="logs">
                        <option value="">Tümü</option>
                        <option value="INSERT" ${currentValue === 'INSERT' ? 'selected' : ''}>INSERT</option>
                        <option value="UPDATE" ${currentValue === 'UPDATE' ? 'selected' : ''}>UPDATE</option>
                        <option value="DELETE" ${currentValue === 'DELETE' ? 'selected' : ''}>DELETE</option>
                        <option value="SELECT" ${currentValue === 'SELECT' ? 'selected' : ''}>SELECT</option>
                        <option value="login" ${currentValue === 'login' ? 'selected' : ''}>login</option>
                    </select>
                </th>`;
            }
            
            // Detay alani icin arama yok
            if (c.field === 'YeniDeger') {
                return `<th class="p-1"><span class="text-muted small">-</span></th>`;
            }
            
            return `<th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Ara..." data-column-filter="${c.field}" data-table-id="logs" value="${NbtUtils.escapeHtml(currentValue)}"></th>`;
        }).join('') + `<th class="p-1 text-center">
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-sm btn-outline-primary" data-action="apply-filters" title="Ara"><i class="bi bi-search"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-action="clear-filters" title="Filtreleri Temizle"><i class="bi bi-x-lg"></i></button>
            </div>
        </th>`;

        let rowsHtml = '';
        if (!data || data.length === 0) {
            rowsHtml = `<tr><td colspan="${columns.length + 1}" class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Log kaydı bulunamadı</td></tr>`;
        } else {
            rowsHtml = data.map(row => {
                const cells = columns.map(c => {
                    let val = row[c.field];
                    if (c.render) val = c.render(val, row);
                    return `<td data-field="${c.field}" class="px-3">${val ?? '-'}</td>`;
                }).join('');
                
                return `
                    <tr data-id="${row.Id}">
                        ${cells}
                        <td class="text-center px-3">
                            <button class="btn btn-sm btn-outline-info" data-action="inspect" data-log-id="${row.Id}" title="JSON Görüntüle"><i class="bi bi-code-slash"></i></button>
                        </td>
                    </tr>`;
            }).join('');
        }

        container.innerHTML = `
            <div class="table-responsive p-2">
                <table class="table table-bordered table-hover table-sm mb-0" id="logsTable">
                    <thead>
                        <tr>${headers}</tr>
                        <tr class="bg-white">${filterRow}</tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            </div>`;

        if (isFiltered && this.filteredPaginationInfo) {
            const { page, totalPages, total } = this.filteredPaginationInfo;
            if (totalPages > 1) {
                container.innerHTML += this.renderFilteredPagination();
            } else if (total > 0) {
                container.innerHTML += `<div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light"><small class="text-muted"><i class="bi bi-funnel me-1"></i>Filtrelenmiş: ${total} kayıt gösteriliyor</small></div>`;
            }
        } else if (!isFiltered && this.paginationInfo && this.paginationInfo.totalPages > 1) {
            container.innerHTML += this.renderPagination();
        }

        this.bindTableEvents(container);
    },

    bindTableEvents(container) {
        // Inspect butonlari
        container.querySelectorAll('[data-action="inspect"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const logId = btn.dataset.logId;
                const logItem = (this.allData || this.data).find(d => String(d.Id) === logId);
                if (logItem && logItem.YeniDeger) {
                    this.showInspectModal(logItem.YeniDeger);
                }
            });
        });

        // Pagination
        container.querySelectorAll('[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const newPage = parseInt(link.dataset.page);
                if (!isNaN(newPage) && newPage !== this.currentPage) {
                    this.loadList(newPage);
                }
            });
        });

        // Filtered pagination
        container.querySelectorAll('[data-filtered-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const newPage = parseInt(link.dataset.filteredPage);
                if (!isNaN(newPage) && newPage !== this.filteredPage) {
                    this.applyFilters(newPage);
                }
            });
        });

        // Apply filters button
        container.querySelectorAll('[data-action="apply-filters"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.columnFilters = {};
                container.querySelectorAll('[data-column-filter]').forEach(input => {
                    const field = input.dataset.columnFilter;
                    const value = input.value.trim();
                    if (value) this.columnFilters[field] = value;
                });
                this.applyFilters();
            });
        });

        // Enter key for filters
        container.querySelectorAll('[data-column-filter]').forEach(input => {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    container.querySelector('[data-action="apply-filters"]')?.click();
                }
            });
        });

        // Clear filters button
        container.querySelectorAll('[data-action="clear-filters"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.columnFilters = {};
                this.allData = null;
                container.querySelectorAll('[data-column-filter]').forEach(input => {
                    input.value = '';
                });
                this.loadList(1);
            });
        });
    },

    renderPagination() {
        if (!this.paginationInfo) return '';
        const { page, totalPages, total, limit } = this.paginationInfo;
        const startIndex = (page - 1) * limit;
        const endIndex = Math.min(startIndex + limit, total);

        let pageButtons = '';
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="1"><i class="bi bi-chevron-double-left"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${page - 1}"><i class="bi bi-chevron-left"></i></a></li>`;
        
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        for (let i = startPage; i <= endPage; i++) {
            pageButtons += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
        
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${page + 1}"><i class="bi bi-chevron-right"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${totalPages}"><i class="bi bi-chevron-double-right"></i></a></li>`;

        return `
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light" id="logsPagination">
                <small class="text-muted">Toplam ${total} kayıttan ${startIndex + 1}-${endIndex} arası gösteriliyor</small>
                <nav><ul class="pagination pagination-sm mb-0">${pageButtons}</ul></nav>
            </div>
        `;
    },

    renderFilteredPagination() {
        if (!this.filteredPaginationInfo) return '';
        const { page, totalPages, total, limit } = this.filteredPaginationInfo;
        const startIndex = (page - 1) * limit;
        const endIndex = Math.min(startIndex + limit, total);

        let pageButtons = '';
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="1"><i class="bi bi-chevron-double-left"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${page - 1}"><i class="bi bi-chevron-left"></i></a></li>`;
        
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        for (let i = startPage; i <= endPage; i++) {
            pageButtons += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" data-filtered-page="${i}">${i}</a></li>`;
        }
        
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${page + 1}"><i class="bi bi-chevron-right"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${totalPages}"><i class="bi bi-chevron-double-right"></i></a></li>`;

        return `
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light">
                <small class="text-muted"><i class="bi bi-funnel me-1"></i>Filtrelenmiş: Toplam ${total} kayıttan ${startIndex + 1}-${endIndex} arası gösteriliyor</small>
                <nav><ul class="pagination pagination-sm mb-0">${pageButtons}</ul></nav>
            </div>
        `;
    },

    // Satira cift tiklama event binding kodu
    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        
        const container = document.getElementById('logsTableContainer');
        if (!container) return;
        
        container.addEventListener('click', (e) => {
            const row = e.target.closest('tr[data-id]');
            if (!row) return;
            
            const now = Date.now();
            const id = row.dataset.id;
            
            // Cift tiklama algilama (500ms icinde ayni satira tiklanirsa)
            if (this.lastClickedRow === id && (now - this.lastClickTime) < 500) {
                this.openDetailInNewTab(id);
                this.lastClickTime = 0;
                this.lastClickedRow = null;
            } else {
                this.lastClickTime = now;
                this.lastClickedRow = id;
            }
        });
    },

    // Detayi yeni sekmede JSON olarak acma kodu
    openDetailInNewTab(id) {
        const log = (this.allData || this.data).find(item => String(item.Id) === String(id));
        if (!log) return;
        
        let detailData = log.YeniDeger;
        
        // JSON parse deneme
        if (typeof detailData === 'string') {
            try {
                detailData = JSON.parse(detailData);
            } catch (e) {
                // Parse edilemezse string olarak birak
            }
        }
        
        const formattedJson = JSON.stringify(detailData, null, 2);
        
        const htmlContent = `
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Log Detay - #${log.Id}</title>
    <style>
        body { font-family: 'Fira Code', 'Consolas', monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; margin: 0; }
        .header { background: #2d2d2d; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .header h2 { margin: 0 0 10px 0; color: #569cd6; }
        .meta { display: flex; gap: 20px; flex-wrap: wrap; }
        .meta-item { background: #3c3c3c; padding: 8px 12px; border-radius: 4px; }
        .meta-label { color: #808080; font-size: 12px; }
        .meta-value { color: #ce9178; font-weight: bold; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .badge-success { background: #4ec9b0; color: #1e1e1e; }
        .badge-warning { background: #dcdcaa; color: #1e1e1e; }
        .badge-danger { background: #f14c4c; color: #fff; }
        .badge-info { background: #569cd6; color: #fff; }
        .badge-primary { background: #c586c0; color: #fff; }
        pre { background: #2d2d2d; padding: 20px; border-radius: 8px; overflow: auto; line-height: 1.5; }
        .string { color: #ce9178; }
        .number { color: #b5cea8; }
        .boolean { color: #569cd6; }
        .null { color: #808080; }
        .key { color: #9cdcfe; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Log Detay #${log.Id}</h2>
        <div class="meta">
            <div class="meta-item">
                <div class="meta-label">Zaman</div>
                <div class="meta-value">${log.EklemeZamani || '-'}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Kullanıcı</div>
                <div class="meta-value">${log.KullaniciAdi || '-'}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">İşlem</div>
                <div class="meta-value"><span class="badge badge-${log.Islem === 'INSERT' ? 'success' : log.Islem === 'UPDATE' ? 'warning' : log.Islem === 'DELETE' ? 'danger' : 'info'}">${log.Islem || '-'}</span></div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Tablo</div>
                <div class="meta-value">${log.Tablo || '-'}</div>
            </div>
        </div>
    </div>
    <pre id="jsonContent">${this.syntaxHighlight(formattedJson)}</pre>
</body>
</html>`;
        
        const newTab = window.open('', '_blank');
        newTab.document.write(htmlContent);
        newTab.document.close();
    },

    // JSON syntax highlighting kodu
    syntaxHighlight(json) {
        json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
            let cls = 'number';
            if (/^"/.test(match)) {
                if (/:$/.test(match)) {
                    cls = 'key';
                } else {
                    cls = 'string';
                }
            } else if (/true|false/.test(match)) {
                cls = 'boolean';
            } else if (/null/.test(match)) {
                cls = 'null';
            }
            return '<span class="' + cls + '">' + match + '</span>';
        });
    },
    
    showInspectModal(jsonData) {
        let parsed;
        try {
            parsed = typeof jsonData === 'string' ? JSON.parse(jsonData) : jsonData;
        } catch (e) {
            parsed = jsonData;
        }
        
        const formatted = this.syntaxHighlight(JSON.stringify(parsed, null, 2));
        
        Swal.fire({
            title: '<i class="bi bi-code-slash me-2"></i>JSON Detay',
            html: `<pre class="text-start p-3 bg-light rounded" style="max-height:60vh;overflow:auto;font-size:12px;">${formatted}</pre>`,
            width: '70%',
            showConfirmButton: true,
            confirmButtonText: 'Kapat',
            customClass: {
                popup: 'text-start'
            }
        });
    }
};

// =============================================
// PARAMETRE MODULU
// =============================================
const ParameterModule = {
    _eventsBound: false,
    data: {},
    cities: [],
    districts: [],
    activeGroup: 'genel',
    
    groups: {
        'genel': { icon: 'bi-gear', label: 'Genel Ayarlar', color: 'primary', badgeId: 'paramCountGenel' },
        'doviz': { icon: 'bi-currency-exchange', label: 'Döviz Türleri', color: 'success', badgeId: 'paramCountDoviz' },
        'sehir': { icon: 'bi-geo-alt', label: 'İller', color: 'info', badgeId: 'paramCountSehir' },
        'ilce': { icon: 'bi-pin-map', label: 'İlçeler', color: 'secondary', badgeId: 'paramCountIlce' },
        'durum_proje': { icon: 'bi-kanban', label: 'Proje Durumları', color: 'info', badgeId: 'paramCountDurumProje' },
        'durum_teklif': { icon: 'bi-file-text', label: 'Teklif Durumları', color: 'warning', badgeId: 'paramCountDurumTeklif' },
        'durum_sozlesme': { icon: 'bi-file-earmark-text', label: 'Sözleşme Durumları', color: 'secondary', badgeId: 'paramCountDurumSozlesme' },
        'durum_teminat': { icon: 'bi-shield-check', label: 'Teminat Durumları', color: 'danger', badgeId: 'paramCountDurumTeminat' }
    },

    async init() {
        await this.loadData();
        this.updateTabBadges();
        this.selectTab(this.activeGroup);
        this.bindEvents();
    },

    async loadData() {
        try {
            const [paramRes, citiesRes, districtsRes] = await Promise.all([
                NbtApi.get('/api/parameters'),
                NbtApi.get('/api/cities'),
                NbtApi.get('/api/districts')
            ]);
            this.data = paramRes.data || {};
            this.cities = citiesRes.data || [];
            this.districts = districtsRes.data || [];
        } catch (err) {
            NbtToast.error('Parametreler yüklenemedi: ' + err.message);
        }
    },

    updateTabBadges() {
        Object.entries(this.groups).forEach(([key, group]) => {
            const badgeEl = document.getElementById(group.badgeId);
            if (badgeEl) {
                let count = 0;
                if (key === 'sehir') {
                    count = this.cities.length || 0;
                } else if (key === 'ilce') {
                    count = this.districts.length || 0;
                } else {
                    count = this.data[key]?.length || 0;
                }
                badgeEl.textContent = count;
            }
        });
    },

    selectTab(tab) {
        this.activeGroup = tab;
        
        // Tab butonlarini guncelle
        document.querySelectorAll('#parametersTabs .nav-link').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.paramTab === tab);
        });

        // Icerik render
        this.renderTabContent();
    },

    renderTabContent() {
        const container = document.getElementById('parametersTabContent');
        if (!container) return;

        const group = this.groups[this.activeGroup];
        
        // Sehir ve ilce icin ozel veri kaynaklari
        let items = [];
        if (this.activeGroup === 'sehir') {
            items = this.cities || [];
        } else if (this.activeGroup === 'ilce') {
            items = this.districts || [];
        } else {
            items = this.data[this.activeGroup] || [];
        }

        // Card yapisi ile panel olustur
        let html = `
            <div class="card shadow-sm">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">
                        <i class="bi ${group.icon} me-2 text-${group.color}"></i>${group.label}
                    </span>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-${group.color}">${items.length} kayıt</span>
                        ${(this.activeGroup.startsWith('durum_') || this.activeGroup === 'doviz' || this.activeGroup === 'sehir' || this.activeGroup === 'ilce') ? 
                            `<button class="btn btn-sm btn-${group.color}" id="btnAddParameter" data-can="parameters.create">
                                <i class="bi bi-plus-lg me-1"></i>Yeni Ekle
                            </button>` : ''}
                    </div>
                </div>
                <div class="card-body p-0">`;

        if (items.length === 0) {
            html += `
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                    <p class="mb-0 fw-medium">Bu grupta kayıt bulunamadı</p>
                </div>`;
        } else {
            // Gruba gore farkli render
            if (this.activeGroup === 'genel') {
                html += this.renderGeneralContent(items);
            } else if (this.activeGroup === 'doviz') {
                html += this.renderCurrencyContent(items);
            } else if (this.activeGroup === 'sehir') {
                html += this.renderCityContent(items);
            } else if (this.activeGroup === 'ilce') {
                html += this.renderDistrictContent(items);
            } else {
                html += this.renderStatusContent(items);
            }
        }

        html += '</div></div>';
        container.innerHTML = html;
        this.bindTableEvents(container);
        
        // Genel grup icin hatirlatma eventleri
        if (this.activeGroup === 'genel') {
            this.bindHatirlatmaEvents(container);
        }
        
        // Yeni ekle butonu event
        const addBtn = container.querySelector('#btnAddParameter');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.openModal());
        }
    },

    // Eski renderSidebar yerine bos birak (geriye uyumluluk)
    renderSidebar() {
        // Artik kullanilmiyor - tab yapisi ile degistirildi
    },

    // Eski renderTable yerine renderTabContent kullaniliyor
    renderTable() {
        this.renderTabContent();
    },

    // Genel ayarlar icerigi (string dondurur)
    renderGeneralContent(items) {
        // Hatirlatma parametrelerini grupla
        const hatirlatmaGunleri = {};
        const hatirlatmaAktifler = {};
        const digerItems = [];
        
        items.forEach(item => {
            if (item.Kod.endsWith('_hatirlatma_gun') || item.Kod === 'termin_hatirlatma_gun') {
                const key = item.Kod.replace('_hatirlatma_gun', '').replace('termin_hatirlatma_gun', 'termin');
                hatirlatmaGunleri[key] = item;
            } else if (item.Kod.endsWith('_hatirlatma_aktif')) {
                const key = item.Kod.replace('_hatirlatma_aktif', '');
                hatirlatmaAktifler[key] = item;
            } else {
                digerItems.push(item);
            }
        });
        
        let html = `
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Parametre</th>
                            <th style="width:200px;">Değer</th>
                            <th style="width:120px;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>`;
        
        // Diger parametreleri render et
        digerItems.forEach(item => {
            html += `
                <tr data-id="${item.Id}">
                    <td>
                        <div class="fw-semibold">${NbtUtils.escapeHtml(item.Etiket)}</div>
                        <small class="text-muted">${NbtUtils.escapeHtml(item.Kod)}</small>
                    </td>
                    <td>
                        ${item.Kod === 'pagination_default' ? `
                            <input type="number" class="form-control form-control-sm" 
                                   id="param_${item.Id}" value="${item.Deger}" 
                                   min="5" max="100" style="width:100px;" data-can="parameters.update">
                        ` : `
                            <input type="text" class="form-control form-control-sm" 
                                   id="param_${item.Id}" value="${NbtUtils.escapeHtml(item.Deger || '')}" data-can="parameters.update">
                        `}
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary" data-action="save-general" data-id="${item.Id}" data-can="parameters.update">
                            <i class="bi bi-check-lg"></i> Kaydet
                        </button>
                    </td>
                </tr>`;
        });
        
        html += '</tbody></table></div>';
        
        // Hatirlatma parametreleri icin ayri bir bolum
        if (Object.keys(hatirlatmaGunleri).length > 0) {
            html += `
                <div class="p-3 border-top">
                    <h6 class="text-muted mb-3"><i class="bi bi-bell me-2"></i>Hatırlatma Ayarları</h6>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Hatırlatma Türü</th>
                                    <th style="width:100px;">Aktif</th>
                                    <th style="width:150px;">Gün Öncesi</th>
                                    <th style="width:120px;">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>`;
            
            // Hatirlatma satirlarini render et
            const hatirlatmaLabels = {
                'gorusme': { label: 'Görüşme', icon: 'bi-chat-dots' },
                'teklif_gecerlilik': { label: 'Teklif Geçerlilik', icon: 'bi-file-text' },
                'sozlesme': { label: 'Sözleşme', icon: 'bi-file-earmark-text' },
                'damgavergisi': { label: 'Damga Vergisi', icon: 'bi-percent' },
                'teminat_termin': { label: 'Teminat Termin', icon: 'bi-shield-check' },
                'fatura': { label: 'Fatura', icon: 'bi-receipt' },
                'odeme': { label: 'Ödeme', icon: 'bi-credit-card' },
                'termin': { label: 'Genel Termin', icon: 'bi-calendar-event' }
            };
            
            Object.entries(hatirlatmaGunleri).forEach(([key, gunItem]) => {
                const aktifItem = hatirlatmaAktifler[key];
                const info = hatirlatmaLabels[key] || { label: key, icon: 'bi-bell' };
                const isAktif = aktifItem ? (aktifItem.Deger === '1' || aktifItem.Deger === 1) : true;
                
                html += `
                    <tr data-gun-id="${gunItem.Id}" data-aktif-id="${aktifItem?.Id || ''}">
                        <td>
                            <i class="bi ${info.icon} me-2 text-primary"></i>
                            <span class="fw-semibold">${info.label}</span>
                        </td>
                        <td>
                            <div class="form-check form-switch">
                                    <input class="form-check-input hatirlatma-aktif-toggle" type="checkbox" 
                                       id="aktif_${key}" data-key="${key}" 
                                       data-aktif-id="${aktifItem?.Id || ''}"
                                           ${isAktif ? 'checked' : ''} data-can="parameters.update">
                            </div>
                        </td>
                        <td>
                            <div class="input-group input-group-sm" style="width:120px;">
                                    <input type="number" class="form-control hatirlatma-gun-input" 
                                       id="gun_${key}" data-key="${key}"
                                       data-gun-id="${gunItem.Id}"
                                       value="${gunItem.Deger || 0}" 
                                           min="0" max="365" ${!isAktif ? 'disabled' : ''} data-can="parameters.update">
                                <span class="input-group-text">gün</span>
                            </div>
                        </td>
                        <td>
                                <button class="btn btn-sm btn-primary" data-action="save-hatirlatma" 
                                    data-key="${key}" data-gun-id="${gunItem.Id}" 
                                    data-aktif-id="${aktifItem?.Id || ''}" data-can="parameters.update">
                                <i class="bi bi-check-lg"></i>
                            </button>
                        </td>
                    </tr>`;
            });
            
            html += `</tbody></table></div>
                    <div class="mt-2">
                        <button class="btn btn-primary" id="btnSaveAllHatirlatma" data-can="parameters.update">
                            <i class="bi bi-check-all me-1"></i> Tümünü Kaydet
                        </button>
                    </div>
                </div>`;
        }
        
        return html;
    },

    // Doviz icerigi (string dondurur)
    renderCurrencyContent(items) {
        let html = `
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Döviz Kodu</th>
                            <th>Etiket</th>
                            <th style="width:80px;">Simge</th>
                            <th style="width:80px;">Aktif</th>
                            <th style="width:100px;">Varsayılan</th>
                            <th style="width:100px;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>`;
        
        items.forEach(item => {
            const isActive = item.Aktif === true || item.Aktif === 1 || item.Aktif === '1';
            const isDefault = item.Varsayilan === true || item.Varsayilan === 1 || item.Varsayilan === '1';
            html += `
                <tr data-id="${item.Id}">
                    <td><span class="fw-semibold">${NbtUtils.escapeHtml(item.Kod)}</span></td>
                    <td>${NbtUtils.escapeHtml(item.Etiket)}</td>
                    <td><span class="fs-5 fw-bold text-success">${NbtUtils.escapeHtml(item.Deger || '')}</span></td>
                    <td>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" data-action="toggle-active" 
                                   data-id="${item.Id}" ${isActive ? 'checked' : ''} data-can="parameters.update">
                        </div>
                    </td>
                    <td>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="defaultCurrency" 
                                data-action="set-default" data-id="${item.Id}" 
                                   ${isDefault ? 'checked' : ''} data-can="parameters.update">
                        </div>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" data-action="edit" data-id="${item.Id}" title="Düzenle" data-can="parameters.update">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-danger" data-action="delete" data-id="${item.Id}" title="Sil" data-can="parameters.delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
        });

        html += '</tbody></table></div>';
        return html;
    },

    // Durum icerigi (string dondurur)
    renderStatusContent(items) {
        // Pasif kolonu sadece durum_proje icin gosterilir
        const showPasifColumn = this.activeGroup === 'durum_proje';
        
        let html = `
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Durum Adı</th>
                            <th style="width:150px;">Badge</th>
                            <th style="width:80px;">Aktif</th>
                            <th style="width:100px;">Varsayılan</th>
                            ${showPasifColumn ? '<th style="width:100px;" title="Bu duruma sahip projeler select listelerinde görünmez">Pasifleştir</th>' : ''}
                            <th style="width:100px;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>`;
        
        items.forEach(item => {
            const badgeClass = item.Deger === 'warning' || item.Deger === 'light' ? `bg-${item.Deger} text-dark` : `bg-${item.Deger || 'secondary'}`;
            const isActive = item.Aktif === true || item.Aktif === 1 || item.Aktif === '1';
            const isDefault = item.Varsayilan === true || item.Varsayilan === 1 || item.Varsayilan === '1';
            const isPasif = item.Pasif === true || item.Pasif === 1 || item.Pasif === '1';
            html += `
                <tr data-id="${item.Id}">
                    <td><span class="fw-semibold">${NbtUtils.escapeHtml(item.Etiket)}</span></td>
                    <td><span class="badge ${badgeClass}">${NbtUtils.escapeHtml(item.Etiket)}</span></td>
                    <td>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" data-action="toggle-active" 
                                   data-id="${item.Id}" ${isActive ? 'checked' : ''} data-can="parameters.update">
                        </div>
                    </td>
                    <td>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="defaultStatus_${this.activeGroup}" 
                                data-action="set-default" data-id="${item.Id}" 
                                   ${isDefault ? 'checked' : ''} data-can="parameters.update">
                        </div>
                    </td>
                    ${showPasifColumn ? `
                    <td>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" data-action="toggle-pasif" 
                                   data-id="${item.Id}" ${isPasif ? 'checked' : ''} data-can="parameters.update">
                        </div>
                    </td>` : ''}
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" data-action="edit" data-id="${item.Id}" title="Düzenle" data-can="parameters.update">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-danger" data-action="delete" data-id="${item.Id}" title="Sil" data-can="parameters.delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
        });

        html += '</tbody></table></div>';
        return html;
    },

    // Sehir (İl) icerigi
    renderCityContent(items) {
        let html = `
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:80px;">Plaka</th>
                            <th>İl Adı</th>
                            <th>Bölge</th>
                            <th style="width:100px;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>`;
        
        items.forEach(item => {
            html += `
                <tr data-id="${item.Id}">
                    <td><span class="badge bg-primary">${NbtUtils.escapeHtml(item.PlakaKodu)}</span></td>
                    <td><span class="fw-semibold">${NbtUtils.escapeHtml(item.Ad)}</span></td>
                    <td><span class="text-muted">${NbtUtils.escapeHtml(item.Bolge || '-')}</span></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" data-action="edit-city" data-id="${item.Id}" title="Düzenle" data-can="parameters.update">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-danger" data-action="delete-city" data-id="${item.Id}" title="Sil" data-can="parameters.delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
        });

        html += '</tbody></table></div>';
        return html;
    },

    // Ilce icerigi
    renderDistrictContent(items) {
        let html = `
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:80px;">Plaka</th>
                            <th>İl</th>
                            <th>İlçe Adı</th>
                            <th style="width:100px;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>`;
        
        items.forEach(item => {
            html += `
                <tr data-id="${item.Id}">
                    <td><span class="badge bg-secondary">${NbtUtils.escapeHtml(item.PlakaKodu || '')}</span></td>
                    <td><span class="text-muted">${NbtUtils.escapeHtml(item.SehirAdi || '')}</span></td>
                    <td><span class="fw-semibold">${NbtUtils.escapeHtml(item.Ad)}</span></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" data-action="edit-district" data-id="${item.Id}" title="Düzenle" data-can="parameters.update">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-danger" data-action="delete-district" data-id="${item.Id}" title="Sil" data-can="parameters.delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
        });

        html += '</tbody></table></div>';
        return html;
    },

    // Eski render fonksiyonlari - geriye uyumluluk icin birakiliyor (artik kullanilmiyor)
    renderGeneralTable(container, items) {
        container.innerHTML = this.renderGeneralContent(items);
        this.bindTableEvents(container);
        this.bindHatirlatmaEvents(container);
    },

    renderCurrencyTable(container, items) {
        container.innerHTML = this.renderCurrencyContent(items);
        this.bindTableEvents(container);
    },

    renderStatusTable(container, items) {
        container.innerHTML = this.renderStatusContent(items);
        this.bindTableEvents(container);
    },

    // Hatirlatma toggle ve kaydetme eventleri
    bindHatirlatmaEvents(container) {
        // Aktif toggle - gun inputunu enable/disable et
        container.querySelectorAll('.hatirlatma-aktif-toggle').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const key = e.target.dataset.key;
                const gunInput = container.querySelector(`#gun_${key}`);
                if (gunInput) {
                    gunInput.disabled = !e.target.checked;
                }
            });
        });
        
        // Tek hatirlatma kaydet
        container.querySelectorAll('[data-action="save-hatirlatma"]').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const key = e.target.closest('button').dataset.key;
                await this.saveHatirlatma(key, container);
            });
        });
        
        // Tum hatirlmalari kaydet
        const btnSaveAll = container.querySelector('#btnSaveAllHatirlatma');
        if (btnSaveAll) {
            btnSaveAll.addEventListener('click', async () => {
                await this.saveAllHatirlatmalar(container);
            });
        }
    },
    
    async saveHatirlatma(key, container) {
        const aktifCheckbox = container.querySelector(`#aktif_${key}`);
        const gunInput = container.querySelector(`#gun_${key}`);
        
        if (!gunInput) return;
        
        const gunId = gunInput.dataset.gunId;
        const aktifId = aktifCheckbox?.dataset.aktifId;
        const gunDeger = gunInput.value;
        const aktifDeger = aktifCheckbox?.checked ? '1' : '0';
        
        try {
            const payload = { degerler: [] };
            
            // Gun parametresini kaydet
            if (gunId) {
                payload.degerler.push({ id: parseInt(gunId), deger: gunDeger });
            }
            
            // Aktif parametresini kaydet
            if (aktifId) {
                payload.degerler.push({ id: parseInt(aktifId), deger: aktifDeger });
            }
            
            if (payload.degerler.length > 0) {
                await NbtApi.post('/api/parameters/bulk', payload);
                NbtToast.success('Hatırlatma ayarı kaydedildi');
            }
        } catch (err) {
            NbtToast.error('Kaydetme hatası: ' + err.message);
        }
    },
    
    async saveAllHatirlatmalar(container) {
        const payload = { degerler: [] };
        
        container.querySelectorAll('.hatirlatma-gun-input').forEach(input => {
            const gunId = input.dataset.gunId;
            if (gunId) {
                payload.degerler.push({ id: parseInt(gunId), deger: input.value });
            }
        });
        
        container.querySelectorAll('.hatirlatma-aktif-toggle').forEach(checkbox => {
            const aktifId = checkbox.dataset.aktifId;
            if (aktifId) {
                payload.degerler.push({ id: parseInt(aktifId), deger: checkbox.checked ? '1' : '0' });
            }
        });
        
        if (payload.degerler.length === 0) {
            NbtToast.warning('Kaydedilecek değişiklik yok');
            return;
        }
        
        try {
            await NbtApi.post('/api/parameters/bulk', payload);
            NbtToast.success('Tüm hatırlatma ayarları kaydedildi');
        } catch (err) {
            NbtToast.error('Kaydetme hatası: ' + err.message);
        }
    },

    bindTableEvents(container) {
        // Duzenleme butonu
        container.querySelectorAll('[data-action="edit"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.id);
                this.openModal(id);
            });
        });

        // Genel parametre kaydetme
        container.querySelectorAll('[data-action="save-general"]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.dataset.id;
                const input = document.getElementById(`param_${id}`);
                if (!input) return;
                
                let value = input.value;
                
                // Pagination icin 5-100 validasyonu
                if (input.type === 'number') {
                    const numVal = parseInt(value);
                    if (isNaN(numVal) || numVal < 5 || numVal > 100) {
                        NbtToast.error('Değer 5 ile 100 arasında olmalıdır');
                        return;
                    }
                    value = String(numVal);
                }
                
                try {
                    await NbtApi.put(`/api/parameters/${id}`, { Deger: value });
                    NbtToast.success('Parametre güncellendi');
                    
                    // APP_CONFIG'i guncelle ve tum modullerin pageSize'ini guncelle
                    if (this.data.genel?.find(p => p.Id == id)?.Kod === 'pagination_default') {
                        const newSize = parseInt(value);
                        window.APP_CONFIG = window.APP_CONFIG || {};
                        window.APP_CONFIG.PAGINATION_DEFAULT = newSize;
                        // NbtParams cache'ini de guncelle
                        if (NbtParams._cache.settings) {
                            NbtParams._cache.settings.pagination_default = newSize;
                        }
                    }
                } catch (err) {
                    NbtToast.error(err.message);
                }
            });
        });

        // Aktiflik toggle
        container.querySelectorAll('[data-action="toggle-active"]').forEach(checkbox => {
            checkbox.addEventListener('change', async () => {
                const id = checkbox.dataset.id;
                try {
                    await NbtApi.put(`/api/parameters/${id}`, { Aktif: checkbox.checked });
                    NbtToast.success(checkbox.checked ? 'Aktif edildi' : 'Pasif edildi');
                    await this.loadData();
                    this.renderSidebar();
                } catch (err) {
                    checkbox.checked = !checkbox.checked;
                    NbtToast.error(err.message);
                }
            });
        });

        // Pasifleştir toggle (sadece proje durumlari icin)
        container.querySelectorAll('[data-action="toggle-pasif"]').forEach(checkbox => {
            checkbox.addEventListener('change', async () => {
                const id = checkbox.dataset.id;
                try {
                    await NbtApi.put(`/api/parameters/${id}`, { Pasif: checkbox.checked });
                    NbtToast.success(checkbox.checked ? 'Bu duruma sahip projeler select listelerinde görünmeyecek' : 'Pasifleştirme kaldırıldı');
                    await this.loadData();
                    this.renderTabContent();
                    // NbtParams cache'ini temizle ki güncel durum alınsın
                    if (NbtParams._cache.statuses) {
                        NbtParams._cache.statuses = {};
                    }
                } catch (err) {
                    checkbox.checked = !checkbox.checked;
                    NbtToast.error(err.message);
                }
            });
        });

        // Varsayilan ayarlama
        container.querySelectorAll('[data-action="set-default"]').forEach(radio => {
            radio.addEventListener('change', async () => {
                if (!radio.checked) return;
                const id = radio.dataset.id;
                try {
                    await NbtApi.put(`/api/parameters/${id}`, { Varsayilan: true });
                    NbtToast.success('Varsayılan olarak ayarlandı');
                    await this.loadData();
                } catch (err) {
                    NbtToast.error(err.message);
                    await this.loadData();
                    this.renderTable();
                }
            });
        });

        // Silme
        container.querySelectorAll('[data-action="delete"]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.dataset.id;
                const result = await Swal.fire({
                    title: 'Emin misiniz?',
                    text: 'Bu parametreyi silmek istediğinizden emin misiniz?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonText: 'İptal',
                    confirmButtonText: 'Evet, Sil'
                });
                
                if (!result.isConfirmed) return;
                
                try {
                    await NbtApi.delete(`/api/parameters/${id}`);
                    NbtToast.success('Parametre silindi');
                    NbtParams.clearCache();
                    await this.loadData();
                    this.renderSidebar();
                    this.renderTable();
                } catch (err) {
                    NbtToast.error(err.message);
                }
            });
        });

        // Sehir duzenle
        container.querySelectorAll('[data-action="edit-city"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.id);
                this.openCityModal(id);
            });
        });

        // Sehir sil
        container.querySelectorAll('[data-action="delete-city"]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.dataset.id;
                const result = await Swal.fire({
                    title: 'Emin misiniz?',
                    text: 'Bu şehri silmek istediğinizden emin misiniz? Bağlı ilçeler de etkilenecektir.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonText: 'İptal',
                    confirmButtonText: 'Evet, Sil'
                });
                
                if (!result.isConfirmed) return;
                
                try {
                    await NbtApi.post(`/api/cities/${id}/delete`);
                    NbtToast.success('Şehir silindi');
                    await this.loadData();
                    this.updateTabBadges();
                    this.renderTabContent();
                } catch (err) {
                    NbtToast.error(err.message);
                }
            });
        });

        // Ilce duzenle
        container.querySelectorAll('[data-action="edit-district"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.id);
                this.openDistrictModal(id);
            });
        });

        // Ilce sil
        container.querySelectorAll('[data-action="delete-district"]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.dataset.id;
                const result = await Swal.fire({
                    title: 'Emin misiniz?',
                    text: 'Bu ilçeyi silmek istediğinizden emin misiniz?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonText: 'İptal',
                    confirmButtonText: 'Evet, Sil'
                });
                
                if (!result.isConfirmed) return;
                
                try {
                    await NbtApi.post(`/api/districts/${id}/delete`);
                    NbtToast.success('İlçe silindi');
                    await this.loadData();
                    this.updateTabBadges();
                    this.renderTabContent();
                } catch (err) {
                    NbtToast.error(err.message);
                }
            });
        });
    },

    openModal(id = null) {
        if (this.activeGroup === 'doviz') {
            this.openCurrencyModal(id);
        } else if (this.activeGroup === 'sehir') {
            this.openCityModal(id);
        } else if (this.activeGroup === 'ilce') {
            this.openDistrictModal(id);
        } else if (this.activeGroup.startsWith('durum_')) {
            this.openStatusModal(id);
        }
    },

    // Sehir Modal
    openCityModal(id = null) {
        const modalHtml = `
            <div class="modal fade" id="cityModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="cityModalTitle">${id ? 'Şehir Düzenle' : 'Yeni Şehir'}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="cityId" value="${id || ''}">
                            <div class="mb-3">
                                <label class="form-label">Plaka Kodu <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="cityPlakaKodu" maxlength="2" placeholder="01">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Şehir Adı <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="cityAd" placeholder="Adana">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Bölge</label>
                                <select class="form-select" id="cityBolge">
                                    <option value="">Seçiniz</option>
                                    <option value="Marmara">Marmara</option>
                                    <option value="Ege">Ege</option>
                                    <option value="Akdeniz">Akdeniz</option>
                                    <option value="Iç Anadolu">İç Anadolu</option>
                                    <option value="Karadeniz">Karadeniz</option>
                                    <option value="Dogu Anadolu">Doğu Anadolu</option>
                                    <option value="Güneydogu Anadolu">Güneydoğu Anadolu</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <button type="button" class="btn btn-primary" id="btnSaveCity">Kaydet</button>
                        </div>
                    </div>
                </div>
            </div>`;
        
        // Mevcut modali kaldir
        const existingModal = document.getElementById('cityModal');
        if (existingModal) existingModal.remove();
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        if (id) {
            const item = this.cities.find(c => c.Id == id);
            if (item) {
                document.getElementById('cityPlakaKodu').value = item.PlakaKodu || '';
                document.getElementById('cityAd').value = item.Ad || '';
                document.getElementById('cityBolge').value = item.Bolge || '';
            }
        }
        
        const modal = new bootstrap.Modal(document.getElementById('cityModal'));
        modal.show();
        
        document.getElementById('btnSaveCity').addEventListener('click', () => this.saveCity());
    },

    // Sehir Kaydet
    async saveCity() {
        const id = document.getElementById('cityId').value;
        const data = {
            PlakaKodu: document.getElementById('cityPlakaKodu').value.trim(),
            Ad: document.getElementById('cityAd').value.trim(),
            Bolge: document.getElementById('cityBolge').value
        };
        
        if (!data.PlakaKodu || !data.Ad) {
            NbtToast.error('Plaka kodu ve şehir adı zorunludur');
            return;
        }
        
        try {
            if (id) {
                await NbtApi.post(`/api/cities/${id}/update`, data);
                NbtToast.success('Şehir güncellendi');
            } else {
                await NbtApi.post('/api/cities', data);
                NbtToast.success('Şehir eklendi');
            }
            
            bootstrap.Modal.getInstance(document.getElementById('cityModal')).hide();
            await this.loadData();
            this.updateTabBadges();
            this.renderTabContent();
        } catch (err) {
            NbtToast.error(err.message);
        }
    },

    // Ilce Modal
    openDistrictModal(id = null) {
        // Sehir select optionlarini olustur
        const cityOptions = this.cities.map(c => 
            `<option value="${c.Id}">${c.PlakaKodu} - ${c.Ad}</option>`
        ).join('');
        
        const modalHtml = `
            <div class="modal fade" id="districtModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="districtModalTitle">${id ? 'İlçe Düzenle' : 'Yeni İlçe'}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="districtId" value="${id || ''}">
                            <div class="mb-3">
                                <label class="form-label">İl <span class="text-danger">*</span></label>
                                <select class="form-select" id="districtSehirId">
                                    <option value="">İl Seçiniz</option>
                                    ${cityOptions}
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">İlçe Adı <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="districtAd" placeholder="Kadıköy">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <button type="button" class="btn btn-primary" id="btnSaveDistrict">Kaydet</button>
                        </div>
                    </div>
                </div>
            </div>`;
        
        // Mevcut modali kaldir
        const existingModal = document.getElementById('districtModal');
        if (existingModal) existingModal.remove();
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        if (id) {
            const item = this.districts.find(d => d.Id == id);
            if (item) {
                document.getElementById('districtSehirId').value = item.SehirId || '';
                document.getElementById('districtAd').value = item.Ad || '';
            }
        }
        
        const modal = new bootstrap.Modal(document.getElementById('districtModal'));
        modal.show();
        
        document.getElementById('btnSaveDistrict').addEventListener('click', () => this.saveDistrict());
    },

    // Ilce Kaydet
    async saveDistrict() {
        const id = document.getElementById('districtId').value;
        const data = {
            SehirId: parseInt(document.getElementById('districtSehirId').value),
            Ad: document.getElementById('districtAd').value.trim()
        };
        
        if (!data.SehirId || !data.Ad) {
            NbtToast.error('İl ve ilçe adı zorunludur');
            return;
        }
        
        try {
            if (id) {
                await NbtApi.post(`/api/districts/${id}/update`, data);
                NbtToast.success('İlçe güncellendi');
            } else {
                await NbtApi.post('/api/districts', data);
                NbtToast.success('İlçe eklendi');
            }
            
            bootstrap.Modal.getInstance(document.getElementById('districtModal')).hide();
            await this.loadData();
            this.updateTabBadges();
            this.renderTabContent();
        } catch (err) {
            NbtToast.error(err.message);
        }
    },

    // Doviz Modal
    openCurrencyModal(id = null) {
        NbtModal.resetForm('currencyModal');
        document.getElementById('currencyModalTitle').textContent = id ? 'Döviz Düzenle' : 'Yeni Döviz';
        document.getElementById('currencyId').value = id || '';

        if (id) {
            const items = this.data['doviz'] || [];
            const item = items.find(i => i.Id == id);
            if (item) {
                document.getElementById('currencyKod').value = item.Kod || '';
                document.getElementById('currencyEtiket').value = item.Etiket || '';
                document.getElementById('currencyDeger').value = item.Deger || '';
                document.getElementById('currencyAktif').checked = item.Aktif;
                document.getElementById('currencyVarsayilan').checked = item.Varsayilan;
            }
        }

        NbtModal.open('currencyModal');
    },

    // Durum Modal
    openStatusModal(id = null) {
        NbtModal.resetForm('statusModal');
        document.getElementById('statusModalTitle').textContent = id ? 'Durum Düzenle' : 'Yeni Durum';
        document.getElementById('statusId').value = id || '';
        document.getElementById('statusGrup').value = this.activeGroup;

        // Badge onizleme guncellemesi
        const updatePreview = () => {
            const selectedColor = document.querySelector('input[name="statusBadgeColor"]:checked')?.value || 'success';
            const etiket = document.getElementById('statusEtiket').value || 'Örnek Durum';
            const preview = document.getElementById('statusBadgePreview');
            preview.className = `badge bg-${selectedColor} fs-6`;
            if (selectedColor === 'warning' || selectedColor === 'light') {
                preview.classList.add('text-dark');
            }
            preview.textContent = etiket;
        };

        // Radio button events
        document.querySelectorAll('input[name="statusBadgeColor"]').forEach(radio => {
            radio.addEventListener('change', updatePreview);
        });
        document.getElementById('statusEtiket').addEventListener('input', updatePreview);

        if (id) {
            const items = this.data[this.activeGroup] || [];
            const item = items.find(i => i.Id == id);
            if (item) {
                document.getElementById('statusEtiket').value = item.Etiket || '';
                document.getElementById('statusAktif').checked = item.Aktif;
                document.getElementById('statusVarsayilan').checked = item.Varsayilan;
                // Badge rengi sec
                const colorRadio = document.querySelector(`input[name="statusBadgeColor"][value="${item.Deger}"]`);
                if (colorRadio) colorRadio.checked = true;
                updatePreview();
            }
        }

        NbtModal.open('statusModal');
    },

    // Doviz kaydetme
    async saveCurrency() {
        const id = document.getElementById('currencyId').value;
        const data = {
            Grup: 'doviz',
            Kod: document.getElementById('currencyKod').value.trim().toUpperCase(),
            Etiket: document.getElementById('currencyEtiket').value.trim(),
            Deger: document.getElementById('currencyDeger').value.trim(),
            Sira: 0,
            Aktif: document.getElementById('currencyAktif').checked,
            Varsayilan: document.getElementById('currencyVarsayilan').checked
        };

        NbtModal.clearError('currencyModal');
        if (!data.Kod) {
            NbtModal.showFieldError('currencyModal', 'currencyKod', 'Döviz kodu zorunludur');
            NbtModal.showError('currencyModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        if (!data.Etiket) {
            NbtModal.showFieldError('currencyModal', 'currencyEtiket', 'Etiket zorunludur');
            NbtModal.showError('currencyModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        if (!data.Deger) {
            NbtModal.showFieldError('currencyModal', 'currencyDeger', 'Simge zorunludur');
            NbtModal.showError('currencyModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }

        NbtModal.setLoading('currencyModal', true);
        try {
            if (id) {
                await NbtApi.put(`/api/parameters/${id}`, data);
                NbtToast.success('Döviz güncellendi');
            } else {
                await NbtApi.post('/api/parameters', data);
                NbtToast.success('Döviz eklendi');
            }
            NbtModal.close('currencyModal');
            NbtParams.clearCache();
            await this.loadData();
            this.renderSidebar();
            this.renderTable();
        } catch (err) {
            NbtModal.showError('currencyModal', err.message);
        } finally {
            NbtModal.setLoading('currencyModal', false);
        }
    },

    // Durum kaydetme
    async saveStatus() {
        const id = document.getElementById('statusId').value;
        const selectedColor = document.querySelector('input[name="statusBadgeColor"]:checked')?.value || 'success';
        const etiket = document.getElementById('statusEtiket').value.trim();
        const grup = document.getElementById('statusGrup').value;
        
        // Kod'u sayisal olarak olustur (mevcut maksimum + 1)
        let kod = '1';
        if (!id) {
            // Yeni kayit icin: mevcut kodlarin maksimumunu bul
            const existing = this.data[grup] || [];
            if (existing.length > 0) {
                const maxKod = Math.max(...existing.map(p => parseInt(p.Kod) || 0));
                kod = String(maxKod + 1);
            }
        } else {
            // Guncelleme icin mevcut kodu koru
            const existingItems = this.data[grup] || [];
            const current = existingItems.find(p => String(p.Id) === String(id));
            kod = current?.Kod || '1';
        }
        
        const data = {
            Grup: grup,
            Kod: kod,
            Etiket: etiket,
            Deger: selectedColor,
            Sira: 0,
            Aktif: document.getElementById('statusAktif').checked,
            Varsayilan: document.getElementById('statusVarsayilan').checked
        };

        NbtModal.clearError('statusModal');
        if (!data.Etiket) {
            NbtModal.showFieldError('statusModal', 'statusEtiket', 'Durum adı zorunludur');
            NbtModal.showError('statusModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }

        NbtModal.setLoading('statusModal', true);
        try {
            if (id) {
                await NbtApi.put(`/api/parameters/${id}`, data);
                NbtToast.success('Durum güncellendi');
            } else {
                await NbtApi.post('/api/parameters', data);
                NbtToast.success('Durum eklendi');
            }
            NbtModal.close('statusModal');
            NbtParams.clearCache();
            await this.loadData();
            this.renderSidebar();
            this.renderTable();
        } catch (err) {
            NbtModal.showError('statusModal', err.message);
        } finally {
            NbtModal.setLoading('statusModal', false);
        }
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        
        // Tab tiklama
        document.getElementById('parametersTabs')?.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-param-tab]');
            if (btn) {
                e.preventDefault();
                this.selectTab(btn.dataset.paramTab);
            }
        });

        // Yenile butonu
        document.getElementById('btnRefreshParameters')?.addEventListener('click', async () => {
            const btn = document.getElementById('btnRefreshParameters');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Yükleniyor...';
            
            await this.loadData();
            this.updateTabBadges();
            this.renderTabContent();
            
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i>Yenile';
            NbtToast.success('Parametreler yenilendi');
        });
        
        // Modal kaydetme butonlari
        document.getElementById('btnSaveCurrency')?.addEventListener('click', () => this.saveCurrency());
        document.getElementById('btnSaveStatus')?.addEventListener('click', () => this.saveStatus());
    }
};

// =============================================
// KULLANICI MODULU
// =============================================
const UserModule = {
    _eventsBound: false,
    data: [],
    pageSize: window.APP_CONFIG?.PAGINATION_DEFAULT || 10,
    currentPage: 1,
    paginationInfo: null,
    columnFilters: {},
    // Filtre icin ek property'ler
    allData: null,
    allDataLoading: false,
    filteredPage: 1,
    filteredPaginationInfo: null,
    // RBAC: Atanabilir roller cache
    assignableRoles: null,

    async init() {
        this.pageSize = NbtParams.getPaginationDefault();
        await this.loadList();
        this.initToolbar();
        this.bindEvents();
    },

    /**
     * Atanabilir rolleri API'den yukler
     * Subset constraint'e gore sadece kullanicinin atayabilecegi roller doner
     */
    async loadAssignableRoles() {
        if (this.assignableRoles !== null) return this.assignableRoles;
        
        try {
            const response = await NbtApi.get('/api/roles/assignable');
            this.assignableRoles = response.data || [];
            return this.assignableRoles;
        } catch (err) {
            NbtLogger.error('UserModule: Atanabilir roller yuklenemedi', err);
            this.assignableRoles = [];
            return [];
        }
    },

    /**
     * Kullanicinin mevcut rollerini API'den yukler
     */
    async loadUserRoles(userId) {
        try {
            const response = await NbtApi.get(`/api/users/${userId}/roles`);
            return response.data || [];
        } catch (err) {
            NbtLogger.error('UserModule: Kullanici rolleri yuklenemedi', err);
            return [];
        }
    },

    /**
     * Rol checkbox listesini render eder
     */
    renderRoleCheckboxes(assignableRoles, selectedRoleIds = []) {
        if (!assignableRoles || assignableRoles.length === 0) {
            return '<div class="text-muted small py-2">Atayabileceğiniz rol bulunmuyor.</div>';
        }
        
        return assignableRoles.map(rol => {
            const checked = selectedRoleIds.includes(rol.Id) ? 'checked' : '';
            return `
                <div class="form-check">
                    <input class="form-check-input user-role-checkbox" type="checkbox" 
                           value="${rol.Id}" id="userRole_${rol.Id}" ${checked}>
                    <label class="form-check-label" for="userRole_${rol.Id}">
                        ${rol.RolAdi}
                        <small class="text-muted">(${rol.RolKodu})</small>
                    </label>
                </div>
            `;
        }).join('');
    },

    async loadList(page = 1) {
        const container = document.getElementById('usersTableContainer');
        if (!container) return;
        try {
            container.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
            const response = await NbtApi.get(`/api/users?page=${page}&limit=${this.pageSize}`);
            this.data = response.data || [];
            this.paginationInfo = response.pagination || null;
            this.currentPage = page;
            this.filteredPaginationInfo = null;
            this.renderTable(this.data);
        } catch (err) {
            container.innerHTML = `<div class="alert alert-danger m-3">${err.message}</div>`;
        }
    },

    initToolbar() {
        const addBtn = document.getElementById('usersAddBtn');
        if (!addBtn) return;

        addBtn.addEventListener('click', () => this.openModal());
    },

    async applyFilters(page = 1) {
        const hasFilters = Object.keys(this.columnFilters).length > 0;
        if (!hasFilters) {
            this.allData = null;
            this.filteredPaginationInfo = null;
            this.loadList(1);
            return;
        }
        
        // Tum verileri yukle
        if (!this.allData && !this.allDataLoading) {
            this.allDataLoading = true;
            const container = document.getElementById('usersTableContainer');
            if (container) {
                container.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> <small class="text-muted ms-2">Arama yapılıyor...</small></div>';
            }
            try {
                const response = await NbtApi.get('/api/users?page=1&limit=10000');
                this.allData = response.data || [];
            } catch (err) {
                this.allData = this.data || [];
            }
            this.allDataLoading = false;
        }
        
        if (this.allDataLoading) {
            setTimeout(() => this.applyFilters(page), 100);
            return;
        }
        
        let filtered = this.allData || [];
        
        // Kolon filtreleri
        Object.keys(this.columnFilters).forEach(field => {
            const value = this.columnFilters[field];
            if (!value) return;
            
            // RollerStr alani icin text arama (multi-role destegi)
            if (field === 'RollerStr') {
                filtered = filtered.filter(item => {
                    const cellValue = String(item.RollerStr ?? '');
                    return NbtUtils.normalizeText(cellValue).includes(NbtUtils.normalizeText(value));
                });
                return;
            }
            
            // Aktif alani icin exact match (boolean olarak)
            if (field === 'Aktif') {
                filtered = filtered.filter(item => {
                    const isAktif = item[field] === true || item[field] === 1 || item[field] === '1';
                    if (value === 'true') return isAktif;
                    if (value === 'false') return !isAktif;
                    return true;
                });
                return;
            }
            
            // Normal alanlar icin filtre
            filtered = filtered.filter(item => {
                let cellValue = item[field];
                return NbtUtils.normalizeText(cellValue).includes(NbtUtils.normalizeText(value));
            });
        });
        
        this.filteredPage = page;
        const total = filtered.length;
        const totalPages = Math.ceil(total / this.pageSize);
        const startIndex = (page - 1) * this.pageSize;
        const endIndex = Math.min(startIndex + this.pageSize, total);
        const pageData = filtered.slice(startIndex, endIndex);
        
        this.filteredPaginationInfo = { page, limit: this.pageSize, total, totalPages };
        this.renderTable(pageData, true);
    },

    renderTable(data, isFiltered = false) {
        const container = document.getElementById('usersTableContainer');
        if (!container) return;
        
        const columns = [
            { field: 'AdSoyad', label: 'Ad Soyad' },
            { field: 'KullaniciAdi', label: 'Kullanıcı Adı' },
            { field: 'Roller', label: 'Roller', render: (v, row) => {
                // Yeni RBAC: Roller dizisi
                if (row.Roller && Array.isArray(row.Roller) && row.Roller.length > 0) {
                    return row.Roller.map(rol => {
                        return `<span class="badge bg-info me-1">${rol.RolAdi}</span>`;
                    }).join('');
                }
                // Eski sistem fallback: Tek Rol alani
                if (row.Rol) {
                    const roles = { superadmin: 'danger', admin: 'warning', user: 'info' };
                    return `<span class="badge bg-${roles[row.Rol] || 'secondary'}">${row.Rol}</span>`;
                }
                return '<span class="text-muted">-</span>';
            }, isSelect: false },
            { field: 'Aktif', label: 'Durum', render: v => 
                (v === true || v === 1 || v === '1') ? '<span class="badge bg-success">Aktif</span>' : 
                    '<span class="badge bg-danger">Pasif</span>',
            isSelect: true }
        ];

        const headers = columns.map(c => `<th class="bg-light px-3">${c.label}</th>`).join('') + 
            '<th class="bg-light text-center px-3" style="width:120px;">İşlemler</th>';

        // Filter row
        const filterRow = columns.map(c => {
            const currentValue = this.columnFilters[c.field] || '';
            
            // Roller alani icin text arama (multi-role destegi)
            if (c.field === 'Roller') {
                return `<th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Rol ara..." data-column-filter="RollerStr" data-table-id="users" value="${NbtUtils.escapeHtml(this.columnFilters['RollerStr'] || '')}"></th>`;
            }
            
            // Aktif alani icin select
            if (c.field === 'Aktif') {
                return `<th class="p-1">
                    <select class="form-select form-select-sm" data-column-filter="${c.field}" data-table-id="users">
                        <option value="">Tümü</option>
                        <option value="true" ${currentValue === 'true' ? 'selected' : ''}>Aktif</option>
                        <option value="false" ${currentValue === 'false' ? 'selected' : ''}>Pasif</option>
                    </select>
                </th>`;
            }
            
            return `<th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Ara..." data-column-filter="${c.field}" data-table-id="users" value="${NbtUtils.escapeHtml(currentValue)}"></th>`;
        }).join('') + `<th class="p-1 text-center">
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-sm btn-outline-primary" data-action="apply-filters" title="Ara"><i class="bi bi-search"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-action="clear-filters" title="Filtreleri Temizle"><i class="bi bi-x-lg"></i></button>
            </div>
        </th>`;

        let rowsHtml = '';
        if (!data || data.length === 0) {
            rowsHtml = `<tr><td colspan="${columns.length + 1}" class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Kullanıcı bulunamadı</td></tr>`;
        } else {
            rowsHtml = data.map(row => {
                const cells = columns.map(c => {
                    let val = row[c.field];
                    if (c.render) val = c.render(val, row);
                    return `<td data-field="${c.field}" class="px-3">${val ?? '-'}</td>`;
                }).join('');
                
                return `
                    <tr data-id="${row.Id}">
                        ${cells}
                        <td class="text-center px-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-sm btn-outline-primary" data-action="edit" data-id="${row.Id}" title="Düzenle" data-can="users.update"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${row.Id}" title="Sil" data-can="users.delete"><i class="bi bi-trash"></i></button>
                            </div>
                        </td>
                    </tr>`;
            }).join('');
        }

        container.innerHTML = `
            <div class="table-responsive p-2">
                <table class="table table-bordered table-hover table-sm mb-0" id="usersTable">
                    <thead>
                        <tr>${headers}</tr>
                        <tr class="bg-white">${filterRow}</tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            </div>`;

        if (isFiltered && this.filteredPaginationInfo) {
            const { page, totalPages, total } = this.filteredPaginationInfo;
            if (totalPages > 1) {
                container.innerHTML += this.renderFilteredPagination();
            } else if (total > 0) {
                container.innerHTML += `<div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light"><small class="text-muted"><i class="bi bi-funnel me-1"></i>Filtrelenmiş: ${total} kayıt gösteriliyor</small></div>`;
            }
        } else if (!isFiltered && this.paginationInfo && this.paginationInfo.totalPages > 1) {
            container.innerHTML += this.renderPagination();
        }

        this.bindTableEvents(container);
    },

    bindTableEvents(container) {
        // Edit butonlari
        container.querySelectorAll('[data-action="edit"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.id);
                this.openModal(id);
            });
        });

        // Delete butonlari
        container.querySelectorAll('[data-action="delete"]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.dataset.id;
                const result = await Swal.fire({
                    title: 'Emin misiniz?',
                    text: 'Bu kullanıcıyı silmek istediğinizden emin misiniz?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonText: 'İptal',
                    confirmButtonText: 'Evet, Sil'
                });
                
                if (!result.isConfirmed) return;
                
                try {
                    await NbtApi.delete(`/api/users/${id}`);
                    NbtToast.success('Kullanıcı silindi');
                    await this.loadList(this.currentPage);
                } catch (err) {
                    NbtToast.error(err.message);
                }
            });
        });

        // Pagination
        container.querySelectorAll('[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const newPage = parseInt(link.dataset.page);
                if (!isNaN(newPage) && newPage !== this.currentPage) {
                    this.loadList(newPage);
                }
            });
        });

        // Filtered pagination
        container.querySelectorAll('[data-filtered-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const newPage = parseInt(link.dataset.filteredPage);
                if (!isNaN(newPage) && newPage !== this.filteredPage) {
                    this.applyFilters(newPage);
                }
            });
        });

        // Apply filters button
        container.querySelectorAll('[data-action="apply-filters"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.columnFilters = {};
                container.querySelectorAll('[data-column-filter]').forEach(input => {
                    const field = input.dataset.columnFilter;
                    const value = input.value.trim();
                    if (value) this.columnFilters[field] = value;
                });
                this.applyFilters();
            });
        });

        // Enter key for filters
        container.querySelectorAll('[data-column-filter]').forEach(input => {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    container.querySelector('[data-action="apply-filters"]')?.click();
                }
            });
        });

        // Clear filters button
        container.querySelectorAll('[data-action="clear-filters"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.columnFilters = {};
                this.allData = null;
                container.querySelectorAll('[data-column-filter]').forEach(input => {
                    input.value = '';
                });
                this.loadList(1);
            });
        });
    },

    renderPagination() {
        if (!this.paginationInfo) return '';
        const { page, totalPages, total, limit } = this.paginationInfo;
        const startIndex = (page - 1) * limit;
        const endIndex = Math.min(startIndex + limit, total);

        let pageButtons = '';
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="1"><i class="bi bi-chevron-double-left"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${page - 1}"><i class="bi bi-chevron-left"></i></a></li>`;
        
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        for (let i = startPage; i <= endPage; i++) {
            pageButtons += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
        
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${page + 1}"><i class="bi bi-chevron-right"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${totalPages}"><i class="bi bi-chevron-double-right"></i></a></li>`;

        return `
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light" id="usersPagination">
                <small class="text-muted">Toplam ${total} kayıttan ${startIndex + 1}-${endIndex} arası gösteriliyor</small>
                <nav><ul class="pagination pagination-sm mb-0">${pageButtons}</ul></nav>
            </div>
        `;
    },

    renderFilteredPagination() {
        if (!this.filteredPaginationInfo) return '';
        const { page, totalPages, total, limit } = this.filteredPaginationInfo;
        const startIndex = (page - 1) * limit;
        const endIndex = Math.min(startIndex + limit, total);

        let pageButtons = '';
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="1"><i class="bi bi-chevron-double-left"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${page - 1}"><i class="bi bi-chevron-left"></i></a></li>`;
        
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        for (let i = startPage; i <= endPage; i++) {
            pageButtons += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" data-filtered-page="${i}">${i}</a></li>`;
        }
        
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${page + 1}"><i class="bi bi-chevron-right"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${totalPages}"><i class="bi bi-chevron-double-right"></i></a></li>`;

        return `
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light">
                <small class="text-muted"><i class="bi bi-funnel me-1"></i>Filtrelenmiş: Toplam ${total} kayıttan ${startIndex + 1}-${endIndex} arası gösteriliyor</small>
                <nav><ul class="pagination pagination-sm mb-0">${pageButtons}</ul></nav>
            </div>
        `;
    },

    openModal(id = null) {
        NbtModal.resetForm('userModal');
        document.getElementById('userModalTitle').textContent = id ? 'Kullanıcı Düzenle' : 'Yeni Kullanıcı';
        document.getElementById('userId').value = id || '';
        
        // Rol container'ini yukleniyor durumuna al
        const rolesContainer = document.getElementById('userRolesContainer');
        if (rolesContainer) {
            rolesContainer.innerHTML = '<div class="text-center text-muted py-2"><div class="spinner-border spinner-border-sm"></div> Roller yükleniyor...</div>';
        }

        if (id) {
            const user = (this.allData || this.data).find(u => parseInt(u.Id, 10) === id);
            if (user) {
                document.getElementById('userAdSoyad').value = user.AdSoyad || '';
                document.getElementById('userKullaniciAdi').value = user.KullaniciAdi || '';
            }
            
            // Kullanicinin rollerini ve atanabilir rolleri yukle
            Promise.all([
                this.loadAssignableRoles(),
                this.loadUserRoles(id)
            ]).then(([assignableRoles, userRoles]) => {
                const selectedIds = userRoles.map(r => r.Id);
                if (rolesContainer) {
                    rolesContainer.innerHTML = this.renderRoleCheckboxes(assignableRoles, selectedIds);
                }
            });
        } else {
            // Yeni kullanici: sadece atanabilir rolleri yukle
            this.loadAssignableRoles().then(assignableRoles => {
                if (rolesContainer) {
                    rolesContainer.innerHTML = this.renderRoleCheckboxes(assignableRoles, []);
                }
            });
        }

        NbtModal.open('userModal');
    },

    async save() {
        const id = document.getElementById('userId').value;
        
        // Secili rol ID'lerini topla
        const selectedRoleIds = [];
        document.querySelectorAll('.user-role-checkbox:checked').forEach(cb => {
            selectedRoleIds.push(parseInt(cb.value, 10));
        });

        // Gecersiz/tekrarlayan rol ID'lerini temizle
        const assignableIdSet = this.assignableRoles
            ? new Set(this.assignableRoles.map(r => parseInt(r.Id, 10)).filter(Number.isInteger))
            : null;
        const normalizedRoleIds = Array.from(new Set(selectedRoleIds))
            .filter(id => Number.isInteger(id) && id > 0)
            .filter(id => !assignableIdSet || assignableIdSet.has(id));
        
        const data = {
            AdSoyad: document.getElementById('userAdSoyad').value.trim(),
            RolIdler: normalizedRoleIds // Yeni RBAC: rol ID listesi
        };
        
        const sifre = document.getElementById('userSifre').value;
        if (sifre) {
            data.Sifre = sifre;
        }

        NbtModal.clearError('userModal');
        if (!data.AdSoyad) {
            NbtModal.showFieldError('userModal', 'userAdSoyad', 'Ad Soyad zorunludur');
            NbtModal.showError('userModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }

        if (!id) {
            const username = document.getElementById('userKullaniciAdi').value.trim();
            if (!username) {
                NbtModal.showFieldError('userModal', 'userKullaniciAdi', 'Kullanıcı adı zorunludur');
                NbtModal.showError('userModal', 'Lütfen zorunlu alanları doldurun');
                return;
            }
            if (!sifre) {
                NbtModal.showFieldError('userModal', 'userSifre', 'Şifre zorunludur');
                NbtModal.showError('userModal', 'Lütfen zorunlu alanları doldurun');
                return;
            }
            data.KullaniciAdi = username;
            data.Sifre = sifre;
        }

        NbtModal.setLoading('userModal', true);
        try {
            if (id) {
                // Kullaniciyi guncelle
                await NbtApi.put(`/api/users/${id}`, data);
                
                // Rolleri ayri endpoint ile guncelle
                await NbtApi.post(`/api/users/${id}/roles`, { RolIdler: normalizedRoleIds });
                
                NbtToast.success('Kullanıcı güncellendi');
            } else {
                await NbtApi.post('/api/users', data);
                NbtToast.success('Kullanıcı eklendi');
            }
            NbtModal.close('userModal');
            await this.loadList(this.currentPage);
        } catch (err) {
            NbtModal.showError('userModal', err.message);
        } finally {
            NbtModal.setLoading('userModal', false);
        }
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        document.getElementById('btnSaveUser')?.addEventListener('click', () => this.save());
    }
};

// =============================================
// HESABIM MODULU
// =============================================
const MyAccountModule = {
    _eventsBound: false,

    async init() {
        this.loadUserInfo();
        this.bindEvents();
    },

    loadUserInfo() {
        // localStorage'dan kullanici bilgilerini al
        const user = NbtUtils.getUser();
        if (!user) {
            NbtLogger.warn('MyAccountModule: Kullanıcı bilgisi bulunamadı');
            return;
        }

        const userIdEl = document.getElementById('accountUserId');
        const userCodeEl = document.getElementById('accountUserCode');
        const userNameEl = document.getElementById('accountUserName');
        const userRoleEl = document.getElementById('accountUserRole');

        if (userIdEl) userIdEl.value = user.id || '';
        if (userCodeEl) userCodeEl.value = user.username || '';
        if (userNameEl) userNameEl.value = user.name || '';
        if (userRoleEl) userRoleEl.value = this.formatRole(user.role) || '-';
    },

    formatRole(role) {
        const roleLabels = {
            'superadmin': 'Süper Admin',
            'user': 'Kullanıcı'
        };
        return roleLabels[role] || role || '-';
    },

    async changePassword() {
        const oldPassword = document.getElementById('accountOldPassword').value;
        const newPassword = document.getElementById('accountNewPassword').value;

        if (!oldPassword) {
            NbtToast.error('Eski şifre zorunludur');
            return;
        }
        if (!newPassword) {
            NbtToast.error('Yeni şifre zorunludur');
            return;
        }
        if (newPassword.length < 6) {
            NbtToast.error('Yeni şifre en az 6 karakter olmalıdır');
            return;
        }

        try {
            await NbtApi.post('/api/users/change-password', {
                CurrentPassword: oldPassword,
                NewPassword: newPassword
            });
            NbtToast.success('Şifre başarıyla değiştirildi');
            document.getElementById('accountOldPassword').value = '';
            document.getElementById('accountNewPassword').value = '';
        } catch (err) {
            NbtToast.error(err.message || 'Şifre değiştirilemedi');
        }
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;

        document.getElementById('myAccountForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.changePassword();
        });
    }
};

// =============================================
// ALARMLAR MODULU
// =============================================
const AlarmsModule = {
    _eventsBound: false,
    alarms: [],
    selectedTab: 'invoice', // invoice, doubtful, calendar, guarantee, offer

    async init() {
        await this.loadAlarms();
        this.updateTabBadges();
        this.updateSummary();
        this.selectTab(this.selectedTab);
        this.bindEvents();
    },

    async loadAlarms() {
        try {
            const response = await NbtApi.get('/api/alarms');
            this.alarms = response.data || [];
        } catch (err) {
            this.alarms = [];
        }
    },

    getGroupedAlarms() {
        const grouped = {
            invoice: { label: 'Ödenmemiş Faturalar', icon: 'bi-receipt', color: 'danger', items: [], count: 0, total: 0, totalByCurrency: {} },
            doubtful: { label: 'Şüpheli Alacaklar', icon: 'bi-exclamation-triangle', color: 'warning', items: [], count: 0, total: 0, totalByCurrency: {} },
            calendar: { label: 'Yaklaşan Takvim İşleri', icon: 'bi-calendar-event', color: 'warning', items: [], count: 0 },
            guarantee: { label: 'Termin Tarihi Geçen Teminatlar', icon: 'bi-shield-check', color: 'info', items: [], count: 0, total: 0, totalByCurrency: {} },
            offer: { label: 'Geçerliliği Dolan Teklifler', icon: 'bi-file-earmark-text', color: 'primary', items: [], count: 0 }
        };

        // API'dan gelen her alarm bir kategoriye ait ve icinde items dizisi var
        this.alarms.forEach(alarm => {
            if (grouped[alarm.type]) {
                grouped[alarm.type].items = alarm.items || [];
                grouped[alarm.type].count = alarm.count || 0;
                // Toplam bilgilerini de aktar
                if (alarm.total !== undefined) {
                    grouped[alarm.type].total = alarm.total;
                }
                if (alarm.totalByCurrency) {
                    grouped[alarm.type].totalByCurrency = alarm.totalByCurrency;
                }
            }
        });

        return grouped;
    },

    updateTabBadges() {
        const grouped = this.getGroupedAlarms();
        
        Object.keys(grouped).forEach(type => {
            const badgeEl = document.getElementById(`alarmCount${type.charAt(0).toUpperCase() + type.slice(1)}`);
            if (badgeEl) {
                badgeEl.textContent = grouped[type].count;
            }
        });
    },

    updateSummary() {
        const summaryEl = document.getElementById('alarmsSummary');
        if (!summaryEl) return;

        const grouped = this.getGroupedAlarms();
        const totalCount = Object.values(grouped).reduce((sum, g) => sum + g.count, 0);
        
        if (totalCount === 0) {
            summaryEl.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Aktif alarm bulunmuyor</span>';
        } else {
            summaryEl.textContent = `Toplam ${totalCount} alarm mevcut`;
        }
    },

    selectTab(tab) {
        this.selectedTab = tab;
        
        // Tab butonlarini guncelle
        document.querySelectorAll('#alarmsTabs .nav-link').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.alarmTab === tab);
        });

        // Icerik render
        this.renderTabContent();
    },

    renderTabContent() {
        const container = document.getElementById('alarmsTabContent');
        if (!container) return;

        const grouped = this.getGroupedAlarms();
        const group = grouped[this.selectedTab];
        const items = group ? group.items : [];

        // Card yapisi ile panel olustur
        let html = `
            <div class="card shadow-sm">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">
                        <i class="bi ${group.icon} me-2 text-${group.color}"></i>${group.label}
                    </span>
                    <span class="badge bg-${group.color}">${group.count} kayıt</span>
                </div>
                <div class="card-body p-0">`;

        if (!items.length) {
            html += `
                <div class="text-center text-muted py-5">
                    <i class="bi bi-check-circle fs-1 d-block mb-3 text-success"></i>
                    <p class="mb-0 fw-medium">Bu kategoride alarm bulunmuyor</p>
                    <small class="text-muted">Tüm kayıtlar güncel durumda</small>
                </div>`;
        } else {
            html += this.renderTable(items, group);
        }

        html += '</div></div>';
        container.innerHTML = html;
    },

    renderTable(items, group) {
        let html = '<div class="table-responsive p-2"><table class="table table-bordered table-hover table-sm mb-0 align-middle"><thead class="table-light"><tr>';
        
        // Kolon basliklari
        if (this.selectedTab === 'invoice') {
            html += `
                <th class="text-nowrap">Müşteri</th>
                <th class="text-nowrap">Proje</th>
                <th class="text-nowrap">Fatura No</th>
                <th class="text-nowrap text-center">Fatura Tarihi</th>
                <th class="text-nowrap text-center">Gecikme</th>
                <th class="text-nowrap text-end">Fatura Tutarı</th>
                <th class="text-nowrap text-end">Kalan Bakiye</th>`;
        } else if (this.selectedTab === 'doubtful') {
            html += `
                <th class="text-nowrap">Müşteri</th>
                <th class="text-nowrap">Proje</th>
                <th class="text-nowrap">Fatura No</th>
                <th class="text-nowrap text-center">Fatura Tarihi</th>
                <th class="text-nowrap text-center">Gecikme</th>
                <th class="text-nowrap text-end">Fatura Tutarı</th>
                <th class="text-nowrap text-end">Kalan Bakiye</th>`;
        } else if (this.selectedTab === 'calendar') {
            html += `
                <th class="text-nowrap">Müşteri</th>
                <th class="text-nowrap">Proje</th>
                <th class="text-nowrap">Görev</th>
                <th class="text-nowrap text-center">Termin Tarihi</th>
                <th class="text-nowrap text-center">Durum</th>`;
        } else if (this.selectedTab === 'guarantee') {
            html += `
                <th class="text-nowrap">Müşteri</th>
                <th class="text-nowrap">Proje</th>
                <th class="text-nowrap">Teminat Türü</th>
                <th class="text-nowrap text-end">Tutar</th>
                <th class="text-nowrap text-center">Termin Tarihi</th>
                <th class="text-nowrap text-center">Gecikme</th>`;
        } else if (this.selectedTab === 'offer') {
            html += `
                <th class="text-nowrap">Müşteri</th>
                <th class="text-nowrap">Proje</th>
                <th class="text-nowrap">Teklif Konusu</th>
                <th class="text-nowrap text-end">Tutar</th>
                <th class="text-nowrap text-center">Geçerlilik Tarihi</th>
                <th class="text-nowrap text-center">Durum</th>`;
        }
        
        html += '</tr></thead><tbody>';

        items.forEach(item => {
            // Supheli alacak olan faturalar icin warning arka plan
            const rowClass = (this.selectedTab === 'invoice' && item.supheliAlacak) ? 'table-warning' : '';
            html += `<tr class="${rowClass}">`;
            
            if (this.selectedTab === 'invoice') {
                const gecikmeClass = item.delayDays > 30 ? 'bg-danger text-white' : (item.delayDays > 7 ? 'bg-warning text-dark' : 'bg-secondary');
                html += `
                    <td>
                        <a href="/customer/${item.customerId}" class="text-decoration-none fw-medium">${NbtUtils.escapeHtml(item.customer || '')}</a>
                    </td>
                    <td>${NbtUtils.escapeHtml(item.project || '-')}</td>
                    <td><span class="text-muted">${NbtUtils.escapeHtml(item.invoiceNo || '-')}</span></td>
                    <td class="text-center">${NbtUtils.formatDate(item.invoiceDate)}</td>
                    <td class="text-center">
                        <span class="badge ${gecikmeClass}">${item.delayDays > 0 ? item.delayDays + ' gün' : '-'}</span>
                    </td>
                    <td class="text-end">${NbtUtils.formatMoney(item.invoiceAmount, item.currency)}</td>
                    <td class="text-end"><span class="text-danger fw-bold">${NbtUtils.formatMoney(item.balance, item.currency)}</span></td>`;
                    
            } else if (this.selectedTab === 'doubtful') {
                const gecikmeClass = item.delayDays > 30 ? 'bg-danger text-white' : (item.delayDays > 7 ? 'bg-warning text-dark' : 'bg-secondary');
                html += `
                    <td>
                        <a href="/customer/${item.customerId}" class="text-decoration-none fw-medium">${NbtUtils.escapeHtml(item.customer || '')}</a>
                    </td>
                    <td>${NbtUtils.escapeHtml(item.project || '-')}</td>
                    <td><span class="text-muted">${NbtUtils.escapeHtml(item.invoiceNo || '-')}</span></td>
                    <td class="text-center">${NbtUtils.formatDate(item.invoiceDate)}</td>
                    <td class="text-center">
                        <span class="badge ${gecikmeClass}">${item.delayDays > 0 ? item.delayDays + ' gün' : '-'}</span>
                    </td>
                    <td class="text-end">${NbtUtils.formatMoney(item.invoiceAmount, item.currency)}</td>
                    <td class="text-end"><span class="text-warning fw-bold">${NbtUtils.formatMoney(item.balance, item.currency)}</span></td>`;
                    
            } else if (this.selectedTab === 'calendar') {
                const durumClass = item.daysRemaining < 0 ? 'bg-danger' : (item.daysRemaining === 0 ? 'bg-warning text-dark' : 'bg-info');
                const durumText = item.daysRemaining < 0 ? `${Math.abs(item.daysRemaining)} gün geçti` : (item.daysRemaining === 0 ? 'Bugün' : `${item.daysRemaining} gün kaldı`);
                html += `
                    <td>
                        <a href="/customer/${item.customerId}" class="text-decoration-none fw-medium">${NbtUtils.escapeHtml(item.customer || '')}</a>
                    </td>
                    <td>${NbtUtils.escapeHtml(item.project || '-')}</td>
                    <td>${NbtUtils.escapeHtml(item.title || '')}</td>
                    <td class="text-center">${NbtUtils.formatDate(item.date)}</td>
                    <td class="text-center"><span class="badge ${durumClass}">${durumText}</span></td>`;
                    
            } else if (this.selectedTab === 'guarantee') {
                html += `
                    <td>
                        <a href="/customer/${item.customerId}" class="text-decoration-none fw-medium">${NbtUtils.escapeHtml(item.customer || '')}</a>
                    </td>
                    <td>${NbtUtils.escapeHtml(item.project || '-')}</td>
                    <td>${NbtUtils.escapeHtml(item.type || '-')}</td>
                    <td class="text-end"><span class="text-primary fw-medium">${NbtUtils.formatMoney(item.amount, item.currency)}</span></td>
                    <td class="text-center">${NbtUtils.formatDate(item.dueDate)}</td>
                    <td class="text-center"><span class="badge bg-danger">${item.daysOverdue || 0} gün geçti</span></td>`;
                    
            } else if (this.selectedTab === 'offer') {
                const durumClass = item.daysRemaining < 0 ? 'bg-danger' : (item.daysRemaining <= 3 ? 'bg-warning text-dark' : 'bg-info');
                const durumText = item.daysRemaining < 0 ? `${Math.abs(item.daysRemaining)} gün geçti` : `${item.daysRemaining} gün kaldı`;
                html += `
                    <td>
                        <a href="/customer/${item.customerId}" class="text-decoration-none fw-medium">${NbtUtils.escapeHtml(item.customer || '')}</a>
                    </td>
                    <td>${NbtUtils.escapeHtml(item.project || '-')}</td>
                    <td>${NbtUtils.escapeHtml(item.title || '-')}</td>
                    <td class="text-end"><span class="text-primary fw-medium">${NbtUtils.formatMoney(item.amount, item.currency)}</span></td>
                    <td class="text-center">${NbtUtils.formatDate(item.validUntil)}</td>
                    <td class="text-center"><span class="badge ${durumClass}">${durumText}</span></td>`;
            }
            
            html += '</tr>';
        });

        html += '</tbody>';
        
        // Toplam satiri ekle (invoice, doubtful ve guarantee icin)
        if (group && group.totalByCurrency && Object.keys(group.totalByCurrency).length > 0) {
            const toplamStr = this.formatTotalsByCurrency(group.totalByCurrency);
            
            if (this.selectedTab === 'invoice') {
                html += `<tfoot class="table-light">
                    <tr class="fw-bold">
                        <td colspan="5" class="text-end border-top-2">
                            <i class="bi bi-calculator me-1"></i>Toplam (${group.count} kayıt):
                        </td>
                        <td class="text-end border-top-2">-</td>
                        <td class="text-end border-top-2 text-danger">${toplamStr}</td>
                    </tr>
                </tfoot>`;
            } else if (this.selectedTab === 'doubtful') {
                html += `<tfoot class="table-light">
                    <tr class="fw-bold">
                        <td colspan="5" class="text-end border-top-2">
                            <i class="bi bi-calculator me-1"></i>Toplam (${group.count} kayıt):
                        </td>
                        <td class="text-end border-top-2">-</td>
                        <td class="text-end border-top-2 text-warning">${toplamStr}</td>
                    </tr>
                </tfoot>`;
            } else if (this.selectedTab === 'guarantee') {
                html += `<tfoot class="table-light">
                    <tr class="fw-bold">
                        <td colspan="3" class="text-end border-top-2">
                            <i class="bi bi-calculator me-1"></i>Toplam (${group.count} kayıt):
                        </td>
                        <td class="text-end border-top-2 text-primary">${toplamStr}</td>
                        <td colspan="2" class="border-top-2"></td>
                    </tr>
                </tfoot>`;
            }
        }
        
        html += '</table></div>';
        return html;
    },

    // Para birimi bazinda toplamlari formatla
    formatTotalsByCurrency(totalByCurrency) {
        const parts = [];
        Object.keys(totalByCurrency).forEach(currency => {
            const amount = totalByCurrency[currency];
            if (amount > 0) {
                parts.push(NbtUtils.formatMoney(amount, currency));
            }
        });
        return parts.length > 0 ? parts.join(' + ') : '-';
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;

        // Tab tiklama
        document.getElementById('alarmsTabs')?.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-alarm-tab]');
            if (btn) {
                e.preventDefault();
                this.selectTab(btn.dataset.alarmTab);
            }
        });

        // Yenile butonu
        document.getElementById('btnRefreshAlarms')?.addEventListener('click', async () => {
            const btn = document.getElementById('btnRefreshAlarms');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Yükleniyor...';
            
            await this.loadAlarms();
            this.updateTabBadges();
            this.updateSummary();
            this.renderTabContent();
            
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i>Yenile';
            NbtUtils.showToast('Alarmlar yenilendi', 'success');
        });
    }
};

// =============================================
// TEKLIF MODULU
// =============================================
const OfferModule = {
    _eventsBound: false,
    data: [],
    pageSize: window.APP_CONFIG?.PAGINATION_DEFAULT || 10,
    currentPage: 1,
    paginationInfo: null,
    searchQuery: '',
    columnFilters: {},
    // Filtre icin ek property'ler
    allData: null,
    allDataLoading: false,
    filteredPage: 1,
    filteredPaginationInfo: null,
    // Dosya islemleri icin
    removeExistingFile: false,

    async init() {
        this.pageSize = NbtParams.getPaginationDefault();
        // Durum ve doviz parametrelerini onceden yukle (cache'e al)
        await Promise.all([
            NbtParams.getStatuses('teklif'),
            NbtParams.getCurrencies()
        ]);
        await this.loadList();
        this.initToolbar();
        this.bindEvents();
    },

    async loadList(page = 1) {
        const container = document.getElementById('offersTableContainer');
        if (!container) return;
        try {
            container.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
            const response = await NbtApi.get(`/api/offers?page=${page}&limit=${this.pageSize}`);
            this.data = response.data || [];
            this.paginationInfo = response.pagination || null;
            this.currentPage = page;
            this.filteredPaginationInfo = null;
            this.renderTable(this.data);
        } catch (err) {
            container.innerHTML = `<div class="alert alert-danger m-3">${err.message}</div>`;
        }
    },

    initToolbar() {
        const toolbarContainer = document.getElementById('offersToolbar');
        if (!toolbarContainer || toolbarContainer.children.length > 0) return;
        
        toolbarContainer.innerHTML = NbtListToolbar.create({
            onSearch: false,
            onAdd: false,
            onFilter: false
        });

        const panel = document.getElementById('panelOffers');
        NbtListToolbar.bind(toolbarContainer, {
            panelElement: panel
        });
    },

    async applyFilters(page = 1) {
        const hasFilters = Object.keys(this.columnFilters).length > 0;
        if (!hasFilters) {
            this.allData = null;
            this.filteredPaginationInfo = null;
            this.loadList(1);
            return;
        }
        
        if (!this.allData && !this.allDataLoading) {
            this.allDataLoading = true;
            const container = document.getElementById('offersTableContainer');
            if (container) {
                container.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> <small class="text-muted ms-2">Arama yapılıyor...</small></div>';
            }
            try {
                const response = await NbtApi.get('/api/offers?page=1&limit=10000');
                this.allData = response.data || [];
            } catch (err) {
                this.allData = this.data || [];
            }
            this.allDataLoading = false;
        }
        
        if (this.allDataLoading) {
            setTimeout(() => this.applyFilters(page), 100);
            return;
        }
        
        let filtered = this.allData || [];
        
        // Kolon filtreleri - CustomerDetailModule mantigiyla
        Object.keys(this.columnFilters).forEach(field => {
            const value = this.columnFilters[field];
            if (!value) return;
            
            // Tarih araligi baslangic filtresi
            if (field.endsWith('_start')) {
                const baseField = field.replace('_start', '');
                filtered = filtered.filter(item => {
                    let cellValue = item[baseField];
                    if (!cellValue) return false;
                    const cellDate = NbtUtils.formatDateForCompare(cellValue);
                    return cellDate >= value;
                });
                return;
            }
            
            // Tarih araligi bitis filtresi
            if (field.endsWith('_end')) {
                const baseField = field.replace('_end', '');
                filtered = filtered.filter(item => {
                    let cellValue = item[baseField];
                    if (!cellValue) return false;
                    const cellDate = NbtUtils.formatDateForCompare(cellValue);
                    return cellDate <= value;
                });
                return;
            }
            
            // Durum ve doviz icin exact match
            if (field === 'Durum' || field === 'ParaBirimi') {
                filtered = filtered.filter(item => {
                    const cellValue = String(item[field] ?? '');
                    return cellValue === value;
                });
                return;
            }
            
            // Normal alanlar icin filtre
            filtered = filtered.filter(item => {
                let cellValue = item[field];
                return NbtUtils.normalizeText(cellValue).includes(NbtUtils.normalizeText(value));
            });
        });
        
        this.filteredPage = page;
        const total = filtered.length;
        const totalPages = Math.ceil(total / this.pageSize);
        const startIndex = (page - 1) * this.pageSize;
        const endIndex = Math.min(startIndex + this.pageSize, total);
        const pageData = filtered.slice(startIndex, endIndex);
        
        this.filteredPaginationInfo = { page, limit: this.pageSize, total, totalPages };
        this.renderTable(pageData, true);
    },

    renderTable(data, isFiltered = false) {
        const container = document.getElementById('offersTableContainer');
        if (!container) return;
        const columns = [
            { field: 'MusteriUnvan', label: 'Müşteri' },
            { field: 'Konu', label: 'Konu' },
            { field: 'Tutar', label: 'Tutar', render: (v, row) => NbtUtils.formatMoney(v, row.ParaBirimi) },
            { field: 'ParaBirimi', label: 'Döviz', render: v => v || 'TRY', isSelect: true },
            { field: 'TeklifTarihi', label: 'Tarih', render: v => NbtUtils.formatDate(v), isDate: true },
            { field: 'Durum', label: 'Durum', render: v => this.getStatusBadge(v, 'teklif'), statusType: 'teklif' }
        ];

        const headers = columns.map(c => `<th class="bg-light px-3">${c.label}</th>`).join('') + 
            '<th class="bg-light text-center px-3" style="width:140px;">İşlem</th>';

        // Filter row - CustomerDetailModule mantigiyla
        const filterRow = columns.map(c => {
            const currentValue = this.columnFilters[c.field] || '';
            const startValue = this.columnFilters[c.field + '_start'] || '';
            const endValue = this.columnFilters[c.field + '_end'] || '';
            
            // Tarih alanlari icin cift date input
            if (c.isDate) {
                return `<th class="p-1" style="min-width:200px;">
                    <div class="d-flex gap-1">
                        <input type="date" class="form-control form-control-sm" data-column-filter="${c.field}_start" data-table-id="offers" value="${NbtUtils.escapeHtml(startValue)}" title="Başlangıç">
                        <input type="date" class="form-control form-control-sm" data-column-filter="${c.field}_end" data-table-id="offers" value="${NbtUtils.escapeHtml(endValue)}" title="Bitiş">
                    </div>
                </th>`;
            }
            
            // Doviz alani icin select - dinamik olarak doldurulacak
            if (c.field === 'ParaBirimi' || c.isSelect) {
                return `<th class="p-1">
                    <select class="form-select form-select-sm" data-column-filter="${c.field}" data-table-id="offers" data-currency-select="true">
                        <option value="">Tümü</option>
                    </select>
                </th>`;
            }
            
            // Durum alani icin select - dinamik olarak doldurulacak
            if (c.field === 'Durum') {
                return `<th class="p-1">
                    <select class="form-select form-select-sm" data-column-filter="${c.field}" data-table-id="offers" data-status-type="teklif">
                        <option value="">Tümü</option>
                    </select>
                </th>`;
            }
            
            return `<th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Ara..." data-column-filter="${c.field}" data-table-id="offers" value="${NbtUtils.escapeHtml(currentValue)}"></th>`;
        }).join('') + `<th class="p-1 text-center">
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-sm btn-outline-primary" data-action="apply-filters" title="Ara"><i class="bi bi-search"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-action="clear-filters" title="Filtreleri Temizle"><i class="bi bi-x-lg"></i></button>
            </div>
        </th>`;

        let rowsHtml = '';
        if (!data || data.length === 0) {
            rowsHtml = `<tr><td colspan="${columns.length + 1}" class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Teklif bulunamadı</td></tr>`;
        } else {
            rowsHtml = data.map(row => {
                const cells = columns.map(c => {
                    let val = row[c.field];
                    if (c.render) val = c.render(val, row);
                    return `<td data-field="${c.field}" class="px-3">${val ?? '-'}</td>`;
                }).join('');
                
                return `
                    <tr data-id="${row.Id}">
                        ${cells}
                        <td class="text-center px-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary btn-sm" type="button" data-action="view" data-id="${row.Id}" title="Detay" data-can="offers.read">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a class="btn btn-outline-info btn-sm" href="/customer/${row.MusteriId}?tab=teklifler" title="Müşteriye Git" data-can="customers.read">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </div>
                        </td>
                    </tr>`;
            }).join('');
        }

        container.innerHTML = `
            <div class="table-responsive p-2">
                <table class="table table-bordered table-hover table-sm mb-0" id="offersTable">
                    <thead>
                        <tr>${headers}</tr>
                        <tr class="bg-white">${filterRow}</tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            </div>`;

        if (isFiltered && this.filteredPaginationInfo) {
            const { page, totalPages, total } = this.filteredPaginationInfo;
            if (totalPages > 1) {
                container.innerHTML += this.renderFilteredPagination();
            } else if (total > 0) {
                container.innerHTML += `<div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light"><small class="text-muted"><i class="bi bi-funnel me-1"></i>Filtrelenmiş: ${total} kayıt gösteriliyor</small></div>`;
            }
        } else if (!isFiltered && this.paginationInfo && this.paginationInfo.totalPages > 1) {
            container.innerHTML += this.renderPagination();
        }

        this.bindTableEvents(container);
        this.populateFilterSelects(container);
    },

    // Filtre select'lerini dinamik parametrelerden doldur
    async populateFilterSelects(container) {
        // Status select'lerini doldur
        for (const select of container.querySelectorAll('select[data-status-type]')) {
            const statusType = select.dataset.statusType;
            const statuses = await NbtParams.getStatuses(statusType);
            const currentValue = this.columnFilters[select.dataset.columnFilter] || '';
            
            let options = '<option value="">Tümü</option>';
            (statuses || []).forEach(s => {
                const selected = String(currentValue) === String(s.Kod) ? 'selected' : '';
                options += `<option value="${s.Kod}" ${selected}>${NbtUtils.escapeHtml(s.Etiket)}</option>`;
            });
            select.innerHTML = options;
        }
        
        // Currency select'lerini doldur
        const currencies = await NbtParams.getCurrencies();
        container.querySelectorAll('select[data-currency-select]').forEach(select => {
            const currentValue = this.columnFilters[select.dataset.columnFilter] || '';
            let options = '<option value="">Tümü</option>';
            (currencies || []).forEach(c => {
                const selected = currentValue === c.Kod ? 'selected' : '';
                options += `<option value="${c.Kod}" ${selected}>${c.Kod}</option>`;
            });
            select.innerHTML = options;
        });
    },

    bindTableEvents(container) {
        container.querySelectorAll('[data-action="view"]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = parseInt(btn.dataset.id);
                const offer = (this.allData || this.data).find(o => parseInt(o.Id, 10) === id);
                if (offer) {
                    await NbtDetailModal.show('offer', offer, null, null);
                } else {
                    NbtToast.error('Teklif kaydı bulunamadı');
                }
            });
        });

        container.querySelectorAll('[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const newPage = parseInt(link.dataset.page);
                if (!isNaN(newPage) && newPage !== this.currentPage) {
                    this.loadList(newPage);
                }
            });
        });

        container.querySelectorAll('[data-filtered-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const newPage = parseInt(link.dataset.filteredPage);
                if (!isNaN(newPage) && newPage !== this.filteredPage) {
                    this.applyFilters(newPage);
                }
            });
        });

        container.querySelectorAll('[data-action="apply-filters"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.columnFilters = {};
                container.querySelectorAll('[data-column-filter]').forEach(input => {
                    const field = input.dataset.columnFilter;
                    const value = input.value.trim();
                    if (value) this.columnFilters[field] = value;
                });
                this.applyFilters();
            });
        });

        container.querySelectorAll('[data-column-filter]').forEach(input => {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    container.querySelector('[data-action="apply-filters"]')?.click();
                }
            });
        });

        container.querySelectorAll('[data-action="clear-filters"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.columnFilters = {};
                this.allData = null;
                container.querySelectorAll('[data-column-filter]').forEach(input => {
                    input.value = '';
                });
                this.loadList(1);
            });
        });
    },

    renderPagination() {
        if (!this.paginationInfo) return '';
        const { page, totalPages, total, limit } = this.paginationInfo;
        const startIndex = (page - 1) * limit;
        const endIndex = Math.min(startIndex + limit, total);

        let pageButtons = '';
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="1"><i class="bi bi-chevron-double-left"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${page - 1}"><i class="bi bi-chevron-left"></i></a></li>`;
        
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        for (let i = startPage; i <= endPage; i++) {
            pageButtons += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
        
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${page + 1}"><i class="bi bi-chevron-right"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${totalPages}"><i class="bi bi-chevron-double-right"></i></a></li>`;

        return `
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light" id="offersPagination">
                <small class="text-muted">Toplam ${total} kayıttan ${startIndex + 1}-${endIndex} arası gösteriliyor</small>
                <nav><ul class="pagination pagination-sm mb-0">${pageButtons}</ul></nav>
            </div>
        `;
    },

    renderFilteredPagination() {
        if (!this.filteredPaginationInfo) return '';
        const { page, totalPages, total, limit } = this.filteredPaginationInfo;
        const startIndex = (page - 1) * limit;
        const endIndex = Math.min(startIndex + limit, total);

        let pageButtons = '';
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="1"><i class="bi bi-chevron-double-left"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${page - 1}"><i class="bi bi-chevron-left"></i></a></li>`;
        
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        for (let i = startPage; i <= endPage; i++) {
            pageButtons += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" data-filtered-page="${i}">${i}</a></li>`;
        }
        
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${page + 1}"><i class="bi bi-chevron-right"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${totalPages}"><i class="bi bi-chevron-double-right"></i></a></li>`;

        return `
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light">
                <small class="text-muted"><i class="bi bi-funnel me-1"></i>Filtrelenmiş: Toplam ${total} kayıttan ${startIndex + 1}-${endIndex} arası gösteriliyor</small>
                <nav><ul class="pagination pagination-sm mb-0">${pageButtons}</ul></nav>
            </div>
        `;
    },

    async openModal(id = null) {
        NbtModal.resetForm('offerModal');
        document.getElementById('offerModalTitle').textContent = id ? 'Teklif Düzenle' : 'Yeni Teklif';
        document.getElementById('offerId').value = id || '';
        
        // Dosya alanlarini sifirla
        this.removeExistingFile = false;
        document.getElementById('offerDosya').value = '';
        document.getElementById('offerDosya').classList.remove('is-invalid');
        document.getElementById('offerDosyaError').textContent = '';
        document.getElementById('offerCurrentFile').classList.add('d-none');

        const select = document.getElementById('offerMusteriId');
        CustomerDetailModule.populateCustomerSelect(select);
        select.disabled = false;

        const projeSelect = document.getElementById('offerProjeId');
        projeSelect.innerHTML = '<option value="">Proje Seçiniz...</option>';

        // Durum ve doviz select'lerini parametrelerden doldur
        await NbtParams.populateStatusSelect(document.getElementById('offerStatus'), 'teklif');
        await NbtParams.populateCurrencySelect(document.getElementById('offerCurrency'));

        // Musteri degistiginde projeleri yukleme
        select.onchange = async () => {
            projeSelect.innerHTML = '<option value="">Proje Seçiniz...</option>';
            const musteriId = select.value;
            if (musteriId) {
                try {
                    const response = await NbtApi.get(`/api/projects?musteri_id=${musteriId}`);
                    let projects = response.data || [];
                    
                    // Pasif durumdaki projeleri filtrele
                    const pasifKodlar = await NbtParams.getPasifDurumKodlari('proje', true);
                    projects = projects.filter(p => !pasifKodlar.includes(String(p.Durum)));
                    
                    projects.forEach(p => {
                        projeSelect.innerHTML += `<option value="${p.Id}">${NbtUtils.escapeHtml(p.ProjeAdi || '')}</option>`;
                    });
                } catch (err) {
                    NbtLogger.error('Projeler yüklenemedi:', err);
                }
            }
        };

        // Eger customer detail sayfasindaysak musteriyi auto-select et ve disable yap
        if (CustomerDetailModule.customerId) {
            select.value = CustomerDetailModule.customerId;
            select.disabled = true;
            await select.onchange();
        }

        if (id) {
            const parsedId = parseInt(id, 10);
            let offer = this.data?.find(o => parseInt(o.Id, 10) === parsedId);
            if (!offer) {
                offer = CustomerDetailModule.data?.offers?.find(o => parseInt(o.Id, 10) === parsedId);
            }
            if (offer) {
                select.value = offer.MusteriId;
                select.disabled = true;
                // Projeleri yukleme ve secili projeyi ayarlama
                await select.onchange();
                document.getElementById('offerProjeId').value = offer.ProjeId || '';
                document.getElementById('offerSubject').value = offer.Konu || '';
                document.getElementById('offerAmount').value = NbtUtils.formatDecimal(offer.Tutar) || '';
                document.getElementById('offerCurrency').value = offer.ParaBirimi || NbtParams.getDefaultCurrency();
                document.getElementById('offerDate').value = offer.TeklifTarihi?.split('T')[0] || '';
                document.getElementById('offerValidDate').value = offer.GecerlilikTarihi?.split('T')[0] || '';
                document.getElementById('offerStatus').value = offer.Durum ?? '';
                
                // Mevcut dosyayi goster
                if (offer.DosyaAdi && offer.DosyaYolu) {
                    document.getElementById('offerCurrentFileName').textContent = offer.DosyaAdi;
                    document.getElementById('offerCurrentFile').classList.remove('d-none');
                }
            } else {
                NbtToast.error('Teklif kaydı bulunamadı');
                return;
            }
        }

        NbtModal.open('offerModal');
    },

    // PDF ve Word dosya dogrulama
    validateOfferFile(file) {
        const errors = [];
        const maxSize = 10 * 1024 * 1024; // 10MB
        
        if (file.size > maxSize) {
            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            errors.push(`Dosya boyutu çok büyük (${sizeMB}MB). Maksimum 10MB yüklenebilir.`);
        }
        
        const allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        const allowedExtensions = ['.pdf', '.doc', '.docx'];
        const fileExt = '.' + file.name.split('.').pop().toLowerCase();
        
        if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExt)) {
            errors.push('Sadece PDF veya Word dosyası (.pdf, .doc, .docx) yüklenebilir.');
        }
        
        return errors;
    },

    async save() {
        const id = document.getElementById('offerId').value;
        
        let musteriId = parseInt(document.getElementById('offerMusteriId').value);
        if (!musteriId || isNaN(musteriId)) {
            musteriId = CustomerDetailModule.customerId;
        }
        
        const projeIdVal = document.getElementById('offerProjeId').value;
        const fileInput = document.getElementById('offerDosya');
        
        NbtModal.clearError('offerModal');
        if (!musteriId || isNaN(musteriId)) {
            NbtModal.showFieldError('offerModal', 'offerMusteriId', 'Müşteri seçiniz');
            NbtModal.showError('offerModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        if (!projeIdVal) {
            NbtModal.showFieldError('offerModal', 'offerProjeId', 'Proje seçiniz');
            NbtModal.showError('offerModal', 'Proje seçimi zorunludur');
            return;
        }
        
        const tutar = parseFloat(document.getElementById('offerAmount').value) || 0;
        if (!tutar || tutar <= 0) {
            NbtModal.showFieldError('offerModal', 'offerAmount', 'Tutar zorunludur');
            NbtModal.showError('offerModal', 'Lütfen tutar giriniz');
            return;
        }
        
        const teklifTarihi = document.getElementById('offerDate').value || null;
        if (!teklifTarihi) {
            NbtModal.showFieldError('offerModal', 'offerDate', 'Tarih zorunludur');
            NbtModal.showError('offerModal', 'Lütfen tarih seçiniz');
            return;
        }
        
        const gecerlilikTarihi = document.getElementById('offerValidDate').value || null;
        if (!gecerlilikTarihi) {
            NbtModal.showFieldError('offerModal', 'offerValidDate', 'Geçerlilik tarihi zorunludur');
            NbtModal.showError('offerModal', 'Lütfen geçerlilik tarihi seçiniz');
            return;
        }
        
        // Dosya dogrulama
        const file = fileInput?.files?.[0];
        if (file) {
            const errors = this.validateOfferFile(file);
            if (errors.length > 0) {
                fileInput.classList.add('is-invalid');
                document.getElementById('offerDosyaError').textContent = errors.join(' ');
                NbtModal.showError('offerModal', errors.join(' '));
                return;
            }
        }

        NbtModal.setLoading('offerModal', true);
        try {
            // FormData kullan - hem dosya hem diger veriler icin
            const formData = new FormData();
            formData.append('MusteriId', musteriId);
            if (projeIdVal) formData.append('ProjeId', projeIdVal);
            formData.append('Konu', document.getElementById('offerSubject').value.trim() || '');
            formData.append('Tutar', tutar);
            formData.append('ParaBirimi', document.getElementById('offerCurrency').value);
            formData.append('TeklifTarihi', teklifTarihi);
            formData.append('GecerlilikTarihi', gecerlilikTarihi);
            formData.append('Durum', document.getElementById('offerStatus').value);
            
            // Dosya ekleme veya silme
            if (file) {
                formData.append('dosya', file);
            } else if (this.removeExistingFile) {
                formData.append('removeFile', '1');
            }
            
            if (id) {
                await NbtApi.postFormData(`/api/offers/${id}`, formData, 'PUT');
                NbtToast.success('Teklif güncellendi');
            } else {
                await NbtApi.postFormData('/api/offers', formData);
                NbtToast.success('Teklif eklendi');
            }
            NbtModal.close('offerModal');
            await this.loadList();
            
            if (CustomerDetailModule.customerId) {
                await CustomerDetailModule.loadRelatedData('offers', '/api/offers');
                CustomerDetailModule.switchTab(CustomerDetailModule.activeTab);
            }
        } catch (err) {
            NbtModal.showError('offerModal', err.message);
        } finally {
            NbtModal.setLoading('offerModal', false);
        }
    },

    // Durum badge'ini dinamik parametrelerden olustur
    getStatusBadge(status, entity) {
        const cacheKey = `durum_${entity}`;
        const statuses = NbtParams._cache.statuses[cacheKey] || [];
        
        const found = statuses.find(s => s.Kod == status);
        if (found) {
            const badge = found.Deger || 'secondary';
            const textClass = (badge === 'warning' || badge === 'light') ? ' text-dark' : '';
            return `<span class="badge bg-${badge}${textClass}">${NbtUtils.escapeHtml(found.Etiket)}</span>`;
        }
        
        // Fallback - cache henuz yuklenmediyse
        const fallback = { 0: ['Taslak', 'secondary'], 1: ['Gönderildi', 'warning'], 2: ['Onaylandı', 'success'], 3: ['Reddedildi', 'danger'] };
        const config = fallback[status] || ['Bilinmiyor', 'secondary'];
        return `<span class="badge bg-${config[1]}">${config[0]}</span>`;
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        document.getElementById('btnSaveOffer')?.addEventListener('click', () => this.save());
        
        document.getElementById('offerDosya')?.addEventListener('change', (e) => {
            const file = e.target.files?.[0];
            const errorEl = document.getElementById('offerDosyaError');
            e.target.classList.remove('is-invalid');
            if (errorEl) errorEl.textContent = '';
            
            if (file) {
                const errors = this.validateOfferFile(file);
                if (errors.length > 0) {
                    e.target.classList.add('is-invalid');
                    if (errorEl) errorEl.textContent = errors.join(' ');
                }
            }
        });
        
        document.getElementById('btnRemoveOfferFile')?.addEventListener('click', () => {
            this.removeExistingFile = true;
            document.getElementById('offerCurrentFile')?.classList.add('d-none');
        });
    }
};

// =============================================
// SOZLESME MODULU
// =============================================
const ContractModule = {
    _eventsBound: false,
    data: [],
    pageSize: window.APP_CONFIG?.PAGINATION_DEFAULT || 10,
    currentPage: 1,
    paginationInfo: null,
    searchQuery: '',
    columnFilters: {},
    // Filtre icin ek property'ler
    allData: null,
    allDataLoading: false,
    filteredPage: 1,
    filteredPaginationInfo: null,
    // Dosya islemleri icin
    removeExistingFile: false,

    async init() {
        this.pageSize = NbtParams.getPaginationDefault();
        // Durum ve doviz parametrelerini onceden yukle (cache'e al)
        await Promise.all([
            NbtParams.getStatuses('sozlesme'),
            NbtParams.getCurrencies()
        ]);
        await this.loadList();
        this.initToolbar();
        this.bindEvents();
    },

    async loadList(page = 1) {
        const container = document.getElementById('contractsTableContainer');
        if (!container) return;
        try {
            container.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
            const response = await NbtApi.get(`/api/contracts?page=${page}&limit=${this.pageSize}`);
            this.data = response.data || [];
            this.paginationInfo = response.pagination || null;
            this.currentPage = page;
            this.filteredPaginationInfo = null;
            this.renderTable(this.data);
        } catch (err) {
            container.innerHTML = `<div class="alert alert-danger m-3">${err.message}</div>`;
        }
    },

    initToolbar() {
        const toolbarContainer = document.getElementById('contractsToolbar');
        if (!toolbarContainer || toolbarContainer.children.length > 0) return;
        
        toolbarContainer.innerHTML = NbtListToolbar.create({
            onSearch: false,
            onAdd: false,
            onFilter: false
        });

        const panel = document.getElementById('panelContracts');
        NbtListToolbar.bind(toolbarContainer, {
            panelElement: panel
        });
    },

    async applyFilters(page = 1) {
        const hasFilters = Object.keys(this.columnFilters).length > 0;
        if (!hasFilters) {
            this.allData = null;
            this.filteredPaginationInfo = null;
            this.loadList(1);
            return;
        }
        
        if (!this.allData && !this.allDataLoading) {
            this.allDataLoading = true;
            const container = document.getElementById('contractsTableContainer');
            if (container) {
                container.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> <small class="text-muted ms-2">Arama yapılıyor...</small></div>';
            }
            try {
                const response = await NbtApi.get('/api/contracts?page=1&limit=10000');
                this.allData = response.data || [];
            } catch (err) {
                this.allData = this.data || [];
            }
            this.allDataLoading = false;
        }
        
        if (this.allDataLoading) {
            setTimeout(() => this.applyFilters(page), 100);
            return;
        }
        
        let filtered = this.allData || [];
        
        // Kolon filtreleri - CustomerDetailModule mantigiyla
        Object.keys(this.columnFilters).forEach(field => {
            const value = this.columnFilters[field];
            if (!value) return;
            
            // Tarih araligi baslangic filtresi
            if (field.endsWith('_start')) {
                const baseField = field.replace('_start', '');
                filtered = filtered.filter(item => {
                    let cellValue = item[baseField];
                    if (!cellValue) return false;
                    const cellDate = NbtUtils.formatDateForCompare(cellValue);
                    return cellDate >= value;
                });
                return;
            }
            
            // Tarih araligi bitis filtresi
            if (field.endsWith('_end')) {
                const baseField = field.replace('_end', '');
                filtered = filtered.filter(item => {
                    let cellValue = item[baseField];
                    if (!cellValue) return false;
                    const cellDate = NbtUtils.formatDateForCompare(cellValue);
                    return cellDate <= value;
                });
                return;
            }
            
            // Durum ve doviz icin exact match
            if (field === 'Durum' || field === 'ParaBirimi') {
                filtered = filtered.filter(item => {
                    const cellValue = String(item[field] ?? '');
                    return cellValue === value;
                });
                return;
            }
            
            // Normal alanlar icin filtre
            filtered = filtered.filter(item => {
                let cellValue = item[field];
                return NbtUtils.normalizeText(cellValue).includes(NbtUtils.normalizeText(value));
            });
        });
        
        this.filteredPage = page;
        const total = filtered.length;
        const totalPages = Math.ceil(total / this.pageSize);
        const startIndex = (page - 1) * this.pageSize;
        const endIndex = Math.min(startIndex + this.pageSize, total);
        const pageData = filtered.slice(startIndex, endIndex);
        
        this.filteredPaginationInfo = { page, limit: this.pageSize, total, totalPages };
        this.renderTable(pageData, true);
    },

    renderTable(data, isFiltered = false) {
        const container = document.getElementById('contractsTableContainer');
        if (!container) return;
        const columns = [
            { field: 'MusteriUnvan', label: 'Müşteri' },
            { field: 'SozlesmeTarihi', label: 'Sözleşme Tarihi', render: v => NbtUtils.formatDate(v), isDate: true },
            { field: 'Tutar', label: 'Tutar', render: (v, row) => NbtUtils.formatMoney(v, row.ParaBirimi) },
            { field: 'ParaBirimi', label: 'Döviz', render: v => v || 'TRY', isSelect: true },
            { field: 'Durum', label: 'Durum', render: v => this.getStatusBadge(v, 'sozlesme'), statusType: 'sozlesme' }
        ];

        const headers = columns.map(c => `<th class="bg-light px-3">${c.label}</th>`).join('') + 
            '<th class="bg-light text-center px-3" style="width:140px;">İşlem</th>';

        // Filter row - CustomerDetailModule mantigiyla
        const filterRow = columns.map(c => {
            const currentValue = this.columnFilters[c.field] || '';
            const startValue = this.columnFilters[c.field + '_start'] || '';
            const endValue = this.columnFilters[c.field + '_end'] || '';
            
            // Tarih alanlari icin cift date input
            if (c.isDate) {
                return `<th class="p-1" style="min-width:200px;">
                    <div class="d-flex gap-1">
                        <input type="date" class="form-control form-control-sm" data-column-filter="${c.field}_start" data-table-id="contracts" value="${NbtUtils.escapeHtml(startValue)}" title="Başlangıç">
                        <input type="date" class="form-control form-control-sm" data-column-filter="${c.field}_end" data-table-id="contracts" value="${NbtUtils.escapeHtml(endValue)}" title="Bitiş">
                    </div>
                </th>`;
            }
            
            // Doviz alani icin select - dinamik olarak doldurulacak
            if (c.field === 'ParaBirimi' || c.isSelect) {
                return `<th class="p-1">
                    <select class="form-select form-select-sm" data-column-filter="${c.field}" data-table-id="contracts" data-currency-select="true">
                        <option value="">Tümü</option>
                    </select>
                </th>`;
            }
            
            // Durum alani icin select - dinamik olarak doldurulacak
            if (c.field === 'Durum') {
                return `<th class="p-1">
                    <select class="form-select form-select-sm" data-column-filter="${c.field}" data-table-id="contracts" data-status-type="sozlesme">
                        <option value="">Tümü</option>
                    </select>
                </th>`;
            }
            
            return `<th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Ara..." data-column-filter="${c.field}" data-table-id="contracts" value="${NbtUtils.escapeHtml(currentValue)}"></th>`;
        }).join('') + `<th class="p-1 text-center">
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-sm btn-outline-primary" data-action="apply-filters" title="Ara"><i class="bi bi-search"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-action="clear-filters" title="Filtreleri Temizle"><i class="bi bi-x-lg"></i></button>
            </div>
        </th>`;

        let rowsHtml = '';
        if (!data || data.length === 0) {
            rowsHtml = `<tr><td colspan="${columns.length + 1}" class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Sözleşme bulunamadı</td></tr>`;
        } else {
            rowsHtml = data.map(row => {
                const cells = columns.map(c => {
                    let val = row[c.field];
                    if (c.render) val = c.render(val, row);
                    return `<td data-field="${c.field}" class="px-3">${val ?? '-'}</td>`;
                }).join('');
                
                return `
                    <tr data-id="${row.Id}">
                        ${cells}
                        <td class="text-center px-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary btn-sm" type="button" data-action="view" data-id="${row.Id}" title="Detay" data-can="contracts.read">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a class="btn btn-outline-info btn-sm" href="/customer/${row.MusteriId}?tab=sozlesmeler" title="Müşteriye Git" data-can="customers.read">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </div>
                        </td>
                    </tr>`;
            }).join('');
        }

        container.innerHTML = `
            <div class="table-responsive p-2">
                <table class="table table-bordered table-hover table-sm mb-0" id="contractsTable">
                    <thead>
                        <tr>${headers}</tr>
                        <tr class="bg-white">${filterRow}</tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            </div>`;

        if (isFiltered && this.filteredPaginationInfo) {
            const { page, totalPages, total } = this.filteredPaginationInfo;
            if (totalPages > 1) {
                container.innerHTML += this.renderFilteredPagination();
            } else if (total > 0) {
                container.innerHTML += `<div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light"><small class="text-muted"><i class="bi bi-funnel me-1"></i>Filtrelenmiş: ${total} kayıt gösteriliyor</small></div>`;
            }
        } else if (!isFiltered && this.paginationInfo && this.paginationInfo.totalPages > 1) {
            container.innerHTML += this.renderPagination();
        }

        this.bindTableEvents(container);
        this.populateFilterSelects(container);
    },

    // Filtre select'lerini dinamik parametrelerden doldur
    async populateFilterSelects(container) {
        // Status select'lerini doldur
        for (const select of container.querySelectorAll('select[data-status-type]')) {
            const statusType = select.dataset.statusType;
            const statuses = await NbtParams.getStatuses(statusType);
            const currentValue = this.columnFilters[select.dataset.columnFilter] || '';
            
            let options = '<option value="">Tümü</option>';
            (statuses || []).forEach(s => {
                const selected = String(currentValue) === String(s.Kod) ? 'selected' : '';
                options += `<option value="${s.Kod}" ${selected}>${NbtUtils.escapeHtml(s.Etiket)}</option>`;
            });
            select.innerHTML = options;
        }
        
        // Currency select'lerini doldur
        const currencies = await NbtParams.getCurrencies();
        container.querySelectorAll('select[data-currency-select]').forEach(select => {
            const currentValue = this.columnFilters[select.dataset.columnFilter] || '';
            let options = '<option value="">Tümü</option>';
            (currencies || []).forEach(c => {
                const selected = currentValue === c.Kod ? 'selected' : '';
                options += `<option value="${c.Kod}" ${selected}>${c.Kod}</option>`;
            });
            select.innerHTML = options;
        });
    },

    bindTableEvents(container) {
        container.querySelectorAll('[data-action="view"]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = parseInt(btn.dataset.id);
                const contract = (this.allData || this.data).find(c => parseInt(c.Id, 10) === id);
                if (contract) {
                    await NbtDetailModal.show('contract', contract, null, null);
                } else {
                    NbtToast.error('Sözleşme kaydı bulunamadı');
                }
            });
        });

        container.querySelectorAll('[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const newPage = parseInt(link.dataset.page);
                if (!isNaN(newPage) && newPage !== this.currentPage) {
                    this.loadList(newPage);
                }
            });
        });

        container.querySelectorAll('[data-filtered-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const newPage = parseInt(link.dataset.filteredPage);
                if (!isNaN(newPage) && newPage !== this.filteredPage) {
                    this.applyFilters(newPage);
                }
            });
        });

        container.querySelectorAll('[data-action="apply-filters"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.columnFilters = {};
                container.querySelectorAll('[data-column-filter]').forEach(input => {
                    const field = input.dataset.columnFilter;
                    const value = input.value.trim();
                    if (value) this.columnFilters[field] = value;
                });
                this.applyFilters();
            });
        });

        container.querySelectorAll('[data-column-filter]').forEach(input => {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    container.querySelector('[data-action="apply-filters"]')?.click();
                }
            });
        });

        container.querySelectorAll('[data-action="clear-filters"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.columnFilters = {};
                this.allData = null;
                container.querySelectorAll('[data-column-filter]').forEach(input => {
                    input.value = '';
                });
                this.loadList(1);
            });
        });
    },

    renderPagination() {
        if (!this.paginationInfo) return '';
        const { page, totalPages, total, limit } = this.paginationInfo;
        const startIndex = (page - 1) * limit;
        const endIndex = Math.min(startIndex + limit, total);

        let pageButtons = '';
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="1"><i class="bi bi-chevron-double-left"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${page - 1}"><i class="bi bi-chevron-left"></i></a></li>`;
        
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        for (let i = startPage; i <= endPage; i++) {
            pageButtons += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
        
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${page + 1}"><i class="bi bi-chevron-right"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${totalPages}"><i class="bi bi-chevron-double-right"></i></a></li>`;

        return `
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light" id="contractsPagination">
                <small class="text-muted">Toplam ${total} kayıttan ${startIndex + 1}-${endIndex} arası gösteriliyor</small>
                <nav><ul class="pagination pagination-sm mb-0">${pageButtons}</ul></nav>
            </div>
        `;
    },

    renderFilteredPagination() {
        if (!this.filteredPaginationInfo) return '';
        const { page, totalPages, total, limit } = this.filteredPaginationInfo;
        const startIndex = (page - 1) * limit;
        const endIndex = Math.min(startIndex + limit, total);

        let pageButtons = '';
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="1"><i class="bi bi-chevron-double-left"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${page - 1}"><i class="bi bi-chevron-left"></i></a></li>`;
        
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        for (let i = startPage; i <= endPage; i++) {
            pageButtons += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" data-filtered-page="${i}">${i}</a></li>`;
        }
        
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${page + 1}"><i class="bi bi-chevron-right"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${totalPages}"><i class="bi bi-chevron-double-right"></i></a></li>`;

        return `
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light">
                <small class="text-muted"><i class="bi bi-funnel me-1"></i>Filtrelenmiş: Toplam ${total} kayıttan ${startIndex + 1}-${endIndex} arası gösteriliyor</small>
                <nav><ul class="pagination pagination-sm mb-0">${pageButtons}</ul></nav>
            </div>
        `;
    },

    async openModal(id = null) {
        NbtModal.resetForm('contractModal');
        document.getElementById('contractModalTitle').textContent = id ? 'Sözleşme Düzenle' : 'Yeni Sözleşme';
        document.getElementById('contractId').value = id || '';
        
        // Dosya alanlarini sifirla
        this.removeExistingFile = false;
        document.getElementById('contractDosya').value = '';
        document.getElementById('contractDosya').classList.remove('is-invalid');
        document.getElementById('contractDosyaError').textContent = '';
        document.getElementById('contractCurrentFile').classList.add('d-none');

        const select = document.getElementById('contractMusteriId');
        CustomerDetailModule.populateCustomerSelect(select);
        select.disabled = false;

        const projeSelect = document.getElementById('contractProjeId');
        projeSelect.innerHTML = '<option value="">Proje Seçiniz...</option>';

        // Durum ve doviz select'lerini parametrelerden doldur
        await NbtParams.populateStatusSelect(document.getElementById('contractStatus'), 'sozlesme');
        await NbtParams.populateCurrencySelect(document.getElementById('contractCurrency'));

        // Musteri degistiginde projeleri yukleme
        select.onchange = async () => {
            projeSelect.innerHTML = '<option value="">Proje Seçiniz...</option>';
            const musteriId = select.value;
            if (musteriId) {
                try {
                    const response = await NbtApi.get(`/api/projects?musteri_id=${musteriId}`);
                    let projects = response.data || [];
                    
                    // Pasif durumdaki projeleri filtrele
                    const pasifKodlar = await NbtParams.getPasifDurumKodlari('proje', true);
                    projects = projects.filter(p => !pasifKodlar.includes(String(p.Durum)));
                    
                    projects.forEach(p => {
                        projeSelect.innerHTML += `<option value="${p.Id}">${NbtUtils.escapeHtml(p.ProjeAdi || '')}</option>`;
                    });
                } catch (err) {
                    NbtLogger.error('Projeler yüklenemedi:', err);
                }
            }
        };

        // Eger customer detail sayfasindaysak musteriyi auto-select et ve disable yap
        if (CustomerDetailModule.customerId) {
            select.value = CustomerDetailModule.customerId;
            select.disabled = true;
            await select.onchange();
        }

        if (id) {
            const parsedId = parseInt(id, 10);
            let contract = this.data?.find(c => parseInt(c.Id, 10) === parsedId);
            if (!contract) {
                contract = CustomerDetailModule.data?.contracts?.find(c => parseInt(c.Id, 10) === parsedId);
            }
            if (contract) {
                select.value = contract.MusteriId;
                select.disabled = true;
                // Projeleri yukleme ve secili projeyi ayarlama
                await select.onchange();
                document.getElementById('contractProjeId').value = contract.ProjeId || '';
                document.getElementById('contractStart').value = contract.SozlesmeTarihi?.split('T')[0] || '';
                document.getElementById('contractAmount').value = NbtUtils.formatDecimal(contract.Tutar) || '';
                document.getElementById('contractCurrency').value = contract.ParaBirimi || NbtParams.getDefaultCurrency();
                document.getElementById('contractStatus').value = contract.Durum ?? '';
                
                // Mevcut dosyayi goster
                if (contract.DosyaAdi && contract.DosyaYolu) {
                    document.getElementById('contractCurrentFileName').textContent = contract.DosyaAdi;
                    document.getElementById('contractCurrentFile').classList.remove('d-none');
                }
            } else {
                NbtToast.error('Sözleşme kaydı bulunamadı');
                return;
            }
        }

        NbtModal.open('contractModal');
    },

    // PDF dosya dogrulama
    validatePdfFile(file) {
        const errors = [];
        const maxSize = 10 * 1024 * 1024; // 10MB
        
        if (file.size > maxSize) {
            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            errors.push(`Dosya boyutu çok büyük (${sizeMB}MB). Maksimum 10MB yüklenebilir.`);
        }
        
        const allowedTypes = ['application/pdf'];
        const allowedExtensions = ['.pdf'];
        const fileExt = '.' + file.name.split('.').pop().toLowerCase();
        
        if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExt)) {
            errors.push('Sadece PDF dosyası yüklenebilir.');
        }
        
        return errors;
    },

    async save() {
        const id = document.getElementById('contractId').value;
        
        let musteriId = parseInt(document.getElementById('contractMusteriId').value);
        if (!musteriId || isNaN(musteriId)) {
            musteriId = CustomerDetailModule.customerId;
        }
        
        const projeIdVal = document.getElementById('contractProjeId').value;
        const fileInput = document.getElementById('contractDosya');

        NbtModal.clearError('contractModal');
        if (!musteriId || isNaN(musteriId)) {
            NbtModal.showFieldError('contractModal', 'contractMusteriId', 'Müşteri seçiniz');
            NbtModal.showError('contractModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        if (!projeIdVal) {
            NbtModal.showFieldError('contractModal', 'contractProjeId', 'Proje seçiniz');
            NbtModal.showError('contractModal', 'Proje seçimi zorunludur');
            return;
        }
        
        const tutar = parseFloat(document.getElementById('contractAmount').value) || 0;
        if (!tutar || tutar <= 0) {
            NbtModal.showFieldError('contractModal', 'contractAmount', 'Tutar zorunludur');
            NbtModal.showError('contractModal', 'Tutar 0\'dan büyük olmalıdır');
            return;
        }
        
        // Dosya dogrulama
        const file = fileInput?.files?.[0];
        if (file) {
            const errors = this.validatePdfFile(file);
            if (errors.length > 0) {
                fileInput.classList.add('is-invalid');
                document.getElementById('contractDosyaError').textContent = errors.join(' ');
                NbtModal.showError('contractModal', errors.join(' '));
                return;
            }
        }

        NbtModal.setLoading('contractModal', true);
        try {
            // FormData kullan - hem dosya hem diger veriler icin
            const formData = new FormData();
            formData.append('MusteriId', musteriId);
            if (projeIdVal) formData.append('ProjeId', projeIdVal);
            formData.append('SozlesmeTarihi', document.getElementById('contractStart').value || '');
            formData.append('Tutar', tutar);
            formData.append('ParaBirimi', document.getElementById('contractCurrency').value);
            formData.append('Durum', document.getElementById('contractStatus').value);
            
            // Dosya ekleme veya silme
            if (file) {
                formData.append('dosya', file);
            } else if (this.removeExistingFile) {
                formData.append('removeFile', '1');
            }
            
            if (id) {
                await NbtApi.postFormData(`/api/contracts/${id}`, formData, 'PUT');
                NbtToast.success('Sözleşme güncellendi');
            } else {
                await NbtApi.postFormData('/api/contracts', formData);
                NbtToast.success('Sözleşme eklendi');
            }
            NbtModal.close('contractModal');
            await this.loadList();
            
            if (CustomerDetailModule.customerId) {
                await CustomerDetailModule.loadRelatedData('contracts', '/api/contracts');
                CustomerDetailModule.switchTab(CustomerDetailModule.activeTab);
            }
        } catch (err) {
            NbtModal.showError('contractModal', err.message);
        } finally {
            NbtModal.setLoading('contractModal', false);
        }
    },

    // Durum badge'ini dinamik parametrelerden olustur
    getStatusBadge(status, entity) {
        const cacheKey = `durum_${entity}`;
        const statuses = NbtParams._cache.statuses[cacheKey] || [];
        
        const found = statuses.find(s => s.Kod == status);
        if (found) {
            const badge = found.Deger || 'secondary';
            const textClass = (badge === 'warning' || badge === 'light') ? ' text-dark' : '';
            return `<span class="badge bg-${badge}${textClass}">${NbtUtils.escapeHtml(found.Etiket)}</span>`;
        }
        
        // Fallback - cache henuz yuklenmediyse
        const fallback = { 1: ['Aktif', 'success'], 2: ['Pasif', 'secondary'], 3: ['İptal', 'danger'] };
        const config = fallback[status] || ['Bilinmiyor', 'secondary'];
        return `<span class="badge bg-${config[1]}">${config[0]}</span>`;
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        document.getElementById('btnSaveContract')?.addEventListener('click', () => this.save());
        
        document.getElementById('contractDosya')?.addEventListener('change', (e) => {
            const file = e.target.files?.[0];
            const errorEl = document.getElementById('contractDosyaError');
            e.target.classList.remove('is-invalid');
            if (errorEl) errorEl.textContent = '';
            
            if (file) {
                const errors = this.validatePdfFile(file);
                if (errors.length > 0) {
                    e.target.classList.add('is-invalid');
                    if (errorEl) errorEl.textContent = errors.join(' ');
                }
            }
        });
        
        document.getElementById('btnRemoveContractFile')?.addEventListener('click', () => {
            this.removeExistingFile = true;
            document.getElementById('contractCurrentFile')?.classList.add('d-none');
        });
    }
};

// =============================================
// TEMINAT MODULU
// =============================================
const GuaranteeModule = {
    _eventsBound: false,
    data: [],
    pageSize: window.APP_CONFIG?.PAGINATION_DEFAULT || 10,
    currentPage: 1,
    paginationInfo: null,
    searchQuery: '',
    columnFilters: {},
    // Filtre icin ek property'ler
    allData: null,
    allDataLoading: false,
    filteredPage: 1,
    filteredPaginationInfo: null,
    // PDF dosya islemleri icin
    selectedFile: null,
    removeExistingFile: false,

    async init() {
        this.pageSize = NbtParams.getPaginationDefault();
        // Durum ve doviz parametrelerini onceden yukle (cache'e al)
        await Promise.all([
            NbtParams.getStatuses('teminat'),
            NbtParams.getCurrencies()
        ]);
        await this.loadList();
        this.initToolbar();
        this.bindEvents();
    },

    async loadList(page = 1) {
        const container = document.getElementById('guaranteesTableContainer');
        if (!container) return;
        try {
            container.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
            const response = await NbtApi.get(`/api/guarantees?page=${page}&limit=${this.pageSize}`);
            this.data = response.data || [];
            this.paginationInfo = response.pagination || null;
            this.currentPage = page;
            this.filteredPaginationInfo = null;
            this.renderTable(this.data);
        } catch (err) {
            container.innerHTML = `<div class="alert alert-danger m-3">${err.message}</div>`;
        }
    },

    initToolbar() {
        const toolbarContainer = document.getElementById('guaranteesToolbar');
        if (!toolbarContainer || toolbarContainer.children.length > 0) return;
        
        toolbarContainer.innerHTML = NbtListToolbar.create({
            onSearch: false,
            onAdd: false,
            onFilter: false
        });

        const panel = document.getElementById('panelGuarantees');
        NbtListToolbar.bind(toolbarContainer, {
            panelElement: panel
        });
    },

    async applyFilters(page = 1) {
        const hasFilters = Object.keys(this.columnFilters).length > 0;
        if (!hasFilters) {
            this.allData = null;
            this.filteredPaginationInfo = null;
            this.loadList(1);
            return;
        }
        
        if (!this.allData && !this.allDataLoading) {
            this.allDataLoading = true;
            const container = document.getElementById('guaranteesTableContainer');
            if (container) {
                container.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> <small class="text-muted ms-2">Arama yapılıyor...</small></div>';
            }
            try {
                const response = await NbtApi.get('/api/guarantees?page=1&limit=10000');
                this.allData = response.data || [];
            } catch (err) {
                this.allData = this.data || [];
            }
            this.allDataLoading = false;
        }
        
        if (this.allDataLoading) {
            setTimeout(() => this.applyFilters(page), 100);
            return;
        }
        
        let filtered = this.allData || [];
        
        // Kolon filtreleri - CustomerDetailModule mantigiyla
        Object.keys(this.columnFilters).forEach(field => {
            const value = this.columnFilters[field];
            if (!value) return;
            
            // Tarih araligi baslangic filtresi
            if (field.endsWith('_start')) {
                const baseField = field.replace('_start', '');
                filtered = filtered.filter(item => {
                    let cellValue = item[baseField];
                    if (!cellValue) return false;
                    const cellDate = NbtUtils.formatDateForCompare(cellValue);
                    return cellDate >= value;
                });
                return;
            }
            
            // Tarih araligi bitis filtresi
            if (field.endsWith('_end')) {
                const baseField = field.replace('_end', '');
                filtered = filtered.filter(item => {
                    let cellValue = item[baseField];
                    if (!cellValue) return false;
                    const cellDate = NbtUtils.formatDateForCompare(cellValue);
                    return cellDate <= value;
                });
                return;
            }
            
            // Durum ve doviz icin exact match
            if (field === 'Durum' || field === 'ParaBirimi') {
                filtered = filtered.filter(item => {
                    const cellValue = String(item[field] ?? '');
                    return cellValue === value;
                });
                return;
            }
            
            // Normal alanlar icin filtre
            filtered = filtered.filter(item => {
                let cellValue = item[field];
                return NbtUtils.normalizeText(cellValue).includes(NbtUtils.normalizeText(value));
            });
        });
        
        this.filteredPage = page;
        const total = filtered.length;
        const totalPages = Math.ceil(total / this.pageSize);
        const startIndex = (page - 1) * this.pageSize;
        const endIndex = Math.min(startIndex + this.pageSize, total);
        const pageData = filtered.slice(startIndex, endIndex);
        
        this.filteredPaginationInfo = { page, limit: this.pageSize, total, totalPages };
        this.renderTable(pageData, true);
    },

    renderTable(data, isFiltered = false) {
        const container = document.getElementById('guaranteesTableContainer');
        if (!container) return;
        
        const columns = [
            { field: 'MusteriUnvan', label: 'Müşteri' },
            { field: 'Tur', label: 'Tür' },
            { field: 'Tutar', label: 'Tutar', render: (v, row) => NbtUtils.formatMoney(v, row.ParaBirimi) },
            { field: 'ParaBirimi', label: 'Döviz', render: v => v || 'TRY', isSelect: true },
            { field: 'BankaAdi', label: 'Banka' },
            { field: 'TerminTarihi', label: 'Termin Tarihi', render: v => NbtUtils.formatDate(v), isDate: true },
            { field: 'Durum', label: 'Durum', render: v => this.getStatusBadge(v, 'teminat'), statusType: 'teminat' }
        ];

        const headers = columns.map(c => `<th class="bg-light">${c.label}</th>`).join('') + 
            '<th class="bg-light text-center" style="width:100px">İşlem</th>';

        // Filter row - CustomerDetailModule mantigiyla
        const filterRow = columns.map(c => {
            const currentValue = this.columnFilters[c.field] || '';
            const startValue = this.columnFilters[c.field + '_start'] || '';
            const endValue = this.columnFilters[c.field + '_end'] || '';
            
            // Tarih alanlari icin cift date input
            if (c.isDate) {
                return `<th class="p-1" style="min-width:200px;">
                    <div class="d-flex gap-1">
                        <input type="date" class="form-control form-control-sm" data-column-filter="${c.field}_start" data-table-id="guarantees" value="${NbtUtils.escapeHtml(startValue)}" title="Başlangıç">
                        <input type="date" class="form-control form-control-sm" data-column-filter="${c.field}_end" data-table-id="guarantees" value="${NbtUtils.escapeHtml(endValue)}" title="Bitiş">
                    </div>
                </th>`;
            }
            
            // Doviz alani icin select - dinamik parametrelerden doldurulacak
            if (c.field === 'ParaBirimi' || c.isSelect) {
                return `<th class="p-1">
                    <select class="form-select form-select-sm" data-column-filter="${c.field}" data-table-id="guarantees" data-currency-select="true">
                        <option value="">Yükleniyor...</option>
                    </select>
                </th>`;
            }
            
            // Durum alani icin select - dinamik parametrelerden doldurulacak
            if (c.statusType || c.field === 'Durum') {
                return `<th class="p-1">
                    <select class="form-select form-select-sm" data-column-filter="${c.field}" data-table-id="guarantees" data-status-type="${c.statusType || 'teminat'}">
                        <option value="">Yükleniyor...</option>
                    </select>
                </th>`;
            }
            
            return `<th class="p-1"><input type="text" class="form-control form-control-sm" placeholder="Ara..." data-column-filter="${c.field}" data-table-id="guarantees" value="${NbtUtils.escapeHtml(currentValue)}"></th>`;
        }).join('') + `<th class="p-1 text-center">
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-sm btn-outline-primary" data-action="apply-filters" title="Ara"><i class="bi bi-search"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-action="clear-filters" title="Filtreleri Temizle"><i class="bi bi-x-lg"></i></button>
            </div>
        </th>`;

        let rowsHtml = '';
        if (!data || data.length === 0) {
            rowsHtml = `<tr><td colspan="${columns.length + 1}" class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Teminat bulunamadı</td></tr>`;
        } else {
            rowsHtml = data.map(row => {
                const cells = columns.map(c => {
                    let val = row[c.field];
                    if (c.render) val = c.render(val, row);
                    return `<td data-field="${c.field}" class="px-3">${val ?? '-'}</td>`;
                }).join('');
                
                return `
                    <tr data-id="${row.Id}">
                        ${cells}
                        <td class="text-center px-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary btn-sm" type="button" data-action="view" data-id="${row.Id}" title="Detay" data-can="guarantees.read">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a class="btn btn-outline-info btn-sm" href="/customer/${row.MusteriId}?tab=teminatlar" title="Müşteriye Git" data-can="customers.read">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </div>
                        </td>
                    </tr>`;
            }).join('');
        }
        
        let html = `
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0" id="guaranteesTable">
                    <thead class="table-light">
                        <tr>${headers}</tr>
                        <tr class="bg-white">${filterRow}</tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            </div>`;
        
        container.innerHTML = html;

        if (isFiltered && this.filteredPaginationInfo) {
            const { page, totalPages, total } = this.filteredPaginationInfo;
            if (totalPages > 1) {
                container.innerHTML += this.renderFilteredPagination();
            } else if (total > 0) {
                container.innerHTML += `<div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light"><small class="text-muted"><i class="bi bi-funnel me-1"></i>Filtrelenmiş: ${total} kayıt gösteriliyor</small></div>`;
            }
        } else if (!isFiltered && this.paginationInfo && this.paginationInfo.totalPages > 1) {
            container.innerHTML += this.renderPagination();
        }

        this.bindTableEvents(container);
        this.populateFilterSelects(container);
    },

    // Filtre select'lerini dinamik parametrelerden doldur
    async populateFilterSelects(container) {
        // Status select'lerini doldur
        for (const select of container.querySelectorAll('select[data-status-type]')) {
            const statusType = select.dataset.statusType;
            const statuses = await NbtParams.getStatuses(statusType);
            const currentValue = this.columnFilters[select.dataset.columnFilter] || '';
            
            let options = '<option value="">Tümü</option>';
            (statuses || []).forEach(s => {
                const selected = String(currentValue) === String(s.Kod) ? 'selected' : '';
                options += `<option value="${s.Kod}" ${selected}>${NbtUtils.escapeHtml(s.Etiket)}</option>`;
            });
            select.innerHTML = options;
        }
        
        // Currency select'lerini doldur
        const currencies = await NbtParams.getCurrencies();
        container.querySelectorAll('select[data-currency-select]').forEach(select => {
            const currentValue = this.columnFilters[select.dataset.columnFilter] || '';
            let options = '<option value="">Tümü</option>';
            (currencies || []).forEach(c => {
                const selected = currentValue === c.Kod ? 'selected' : '';
                options += `<option value="${c.Kod}" ${selected}>${c.Kod}</option>`;
            });
            select.innerHTML = options;
        });
    },

    bindTableEvents(container) {
        container.querySelectorAll('[data-action="view"]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = parseInt(btn.dataset.id, 10);
                const guarantee = (this.allData || this.data).find(g => parseInt(g.Id, 10) === id);
                if (guarantee) {
                    await NbtDetailModal.show('guarantee', guarantee, null, null);
                } else {
                    NbtToast.error('Teminat kaydı bulunamadı');
                }
            });
        });
        
        container.querySelectorAll('[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const newPage = parseInt(link.dataset.page);
                if (!isNaN(newPage) && newPage !== this.currentPage) {
                    this.loadList(newPage);
                }
            });
        });

        container.querySelectorAll('[data-filtered-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const newPage = parseInt(link.dataset.filteredPage);
                if (!isNaN(newPage) && newPage !== this.filteredPage) {
                    this.applyFilters(newPage);
                }
            });
        });

        container.querySelectorAll('[data-action="apply-filters"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.columnFilters = {};
                container.querySelectorAll('[data-column-filter]').forEach(input => {
                    const field = input.dataset.columnFilter;
                    const value = input.value.trim();
                    if (value) this.columnFilters[field] = value;
                });
                this.applyFilters();
            });
        });

        container.querySelectorAll('[data-column-filter]').forEach(input => {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    container.querySelector('[data-action="apply-filters"]')?.click();
                }
            });
        });

        container.querySelectorAll('[data-action="clear-filters"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.columnFilters = {};
                this.allData = null;
                container.querySelectorAll('[data-column-filter]').forEach(input => {
                    input.value = '';
                });
                this.loadList(1);
            });
        });
    },

    renderPagination() {
        if (!this.paginationInfo) return '';
        const { page, totalPages, total, limit } = this.paginationInfo;
        const startIndex = (page - 1) * limit;
        const endIndex = Math.min(startIndex + limit, total);

        let pageButtons = '';
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="1"><i class="bi bi-chevron-double-left"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${page - 1}"><i class="bi bi-chevron-left"></i></a></li>`;
        
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        for (let i = startPage; i <= endPage; i++) {
            pageButtons += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
        
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${page + 1}"><i class="bi bi-chevron-right"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${totalPages}"><i class="bi bi-chevron-double-right"></i></a></li>`;

        return `
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light" id="guaranteesPagination">
                <small class="text-muted">Toplam ${total} kayıttan ${startIndex + 1}-${endIndex} arası gösteriliyor</small>
                <nav><ul class="pagination pagination-sm mb-0">${pageButtons}</ul></nav>
            </div>
        `;
    },

    renderFilteredPagination() {
        if (!this.filteredPaginationInfo) return '';
        const { page, totalPages, total, limit } = this.filteredPaginationInfo;
        const startIndex = (page - 1) * limit;
        const endIndex = Math.min(startIndex + limit, total);

        let pageButtons = '';
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="1"><i class="bi bi-chevron-double-left"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${page - 1}"><i class="bi bi-chevron-left"></i></a></li>`;
        
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        for (let i = startPage; i <= endPage; i++) {
            pageButtons += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" data-filtered-page="${i}">${i}</a></li>`;
        }
        
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${page + 1}"><i class="bi bi-chevron-right"></i></a></li>`;
        pageButtons += `<li class="page-item ${page === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-filtered-page="${totalPages}"><i class="bi bi-chevron-double-right"></i></a></li>`;

        return `
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light">
                <small class="text-muted"><i class="bi bi-funnel me-1"></i>Filtrelenmiş: Toplam ${total} kayıttan ${startIndex + 1}-${endIndex} arası gösteriliyor</small>
                <nav><ul class="pagination pagination-sm mb-0">${pageButtons}</ul></nav>
            </div>
        `;
    },

    async openModal(id = null) {
        NbtModal.resetForm('guaranteeModal');
        document.getElementById('guaranteeModalTitle').textContent = id ? 'Teminat Düzenle' : 'Yeni Teminat';
        document.getElementById('guaranteeId').value = id || '';
        
        // PDF dosya degiskenlerini sifirla
        this.selectedFile = null;
        this.removeExistingFile = false;
        document.getElementById('guaranteeDosya').value = '';
        document.getElementById('guaranteeCurrentFile')?.classList.add('d-none');

        const select = document.getElementById('guaranteeMusteriId');
        select.innerHTML = '<option value="">Seçiniz...</option>';
        select.disabled = false;
        
        // AppState.customers bossa ve CustomerDetailModule.customerId varsa, aktif musteriyi ekle
        if (AppState.customers && AppState.customers.length > 0) {
            AppState.customers.forEach(c => {
                select.innerHTML += `<option value="${c.Id}">${NbtUtils.escapeHtml(c.Unvan)}</option>`;
            });
        } else if (CustomerDetailModule.customerId && CustomerDetailModule.data?.customer) {
            const c = CustomerDetailModule.data.customer;
            select.innerHTML += `<option value="${c.Id}">${NbtUtils.escapeHtml(c.Unvan)}</option>`;
        }

        const projeSelect = document.getElementById('guaranteeProjeId');
        projeSelect.innerHTML = '<option value="">Proje Seçiniz...</option>';

        // Durum ve doviz select'lerini parametrelerden doldur
        await NbtParams.populateStatusSelect(document.getElementById('guaranteeStatus'), 'teminat');
        await NbtParams.populateCurrencySelect(document.getElementById('guaranteeCurrency'));

        // Musteri degistiginde projeleri yukleme
        select.onchange = async () => {
            projeSelect.innerHTML = '<option value="">Proje Seçiniz...</option>';
            const musteriId = select.value;
            if (musteriId) {
                try {
                    const response = await NbtApi.get(`/api/projects?musteri_id=${musteriId}`);
                    let projects = response.data || [];
                    
                    // Pasif durumdaki projeleri filtrele
                    const pasifKodlar = await NbtParams.getPasifDurumKodlari('proje', true);
                    projects = projects.filter(p => !pasifKodlar.includes(String(p.Durum)));
                    
                    projects.forEach(p => {
                        projeSelect.innerHTML += `<option value="${p.Id}">${NbtUtils.escapeHtml(p.ProjeAdi || '')}</option>`;
                    });
                } catch (err) {
                    NbtLogger.error('Projeler yüklenemedi:', err);
                }
            }
        };

        // Eger customer detail sayfasindaysak musteriyi auto-select et ve disable yap
        if (CustomerDetailModule.customerId) {
            select.value = CustomerDetailModule.customerId;
            select.disabled = true;
            await select.onchange();
        }

        if (id) {
            const parsedId = parseInt(id, 10);
            let guarantee = this.data?.find(g => parseInt(g.Id, 10) === parsedId);
            if (!guarantee) {
                guarantee = CustomerDetailModule.data?.guarantees?.find(g => parseInt(g.Id, 10) === parsedId);
            }
            if (guarantee) {
                select.value = guarantee.MusteriId;
                select.disabled = true;
                // Projeleri yukleme ve secili projeyi ayarlama
                await select.onchange();
                document.getElementById('guaranteeProjeId').value = guarantee.ProjeId || '';
                document.getElementById('guaranteeType').value = guarantee.Tur || 'Nakit';
                document.getElementById('guaranteeBank').value = guarantee.BankaAdi || '';
                document.getElementById('guaranteeAmount').value = NbtUtils.formatDecimal(guarantee.Tutar) || '';
                document.getElementById('guaranteeCurrency').value = guarantee.ParaBirimi || NbtParams.getDefaultCurrency();
                document.getElementById('guaranteeDate').value = guarantee.TerminTarihi?.split('T')[0] || '';
                document.getElementById('guaranteeStatus').value = guarantee.Durum ?? '';
                
                // Mevcut dosya varsa goster
                if (guarantee.DosyaAdi) {
                    document.getElementById('guaranteeCurrentFileName').textContent = guarantee.DosyaAdi;
                    document.getElementById('guaranteeCurrentFile')?.classList.remove('d-none');
                }
            } else {
                NbtToast.error('Teminat kaydı bulunamadı');
                return;
            }
        }

        NbtModal.open('guaranteeModal');
    },
    
    validatePdfFile(file) {
        const errors = [];
        
        if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
            errors.push('Sadece PDF dosyası yükleyebilirsiniz.');
        }
        
        const maxSize = 10 * 1024 * 1024;
        if (file.size > maxSize) {
            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            errors.push(`Dosya boyutu çok büyük (${sizeMB}MB). Maksimum 10MB yüklenebilir.`);
        }
        
        if (file.size === 0) {
            errors.push('Dosya boş olamaz.');
        }
        
        return errors;
    },

    async save() {
        const id = document.getElementById('guaranteeId').value;
        
        let musteriId = parseInt(document.getElementById('guaranteeMusteriId').value);
        if (!musteriId || isNaN(musteriId)) {
            musteriId = CustomerDetailModule.customerId;
        }
        
        const projeIdVal = document.getElementById('guaranteeProjeId').value;
        const data = {
            MusteriId: musteriId,
            ProjeId: projeIdVal ? parseInt(projeIdVal) : null,
            Tur: document.getElementById('guaranteeType').value,
            BankaAdi: document.getElementById('guaranteeBank').value.trim() || null,
            Tutar: parseFloat(document.getElementById('guaranteeAmount').value) || 0,
            ParaBirimi: document.getElementById('guaranteeCurrency').value,
            TerminTarihi: document.getElementById('guaranteeDate').value || null,
            Durum: document.getElementById('guaranteeStatus').value
        };

        NbtModal.clearError('guaranteeModal');
        if (!data.MusteriId || isNaN(data.MusteriId)) {
            NbtModal.showFieldError('guaranteeModal', 'guaranteeMusteriId', 'Müşteri seçiniz');
            NbtModal.showError('guaranteeModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        if (!data.ProjeId) {
            NbtModal.showFieldError('guaranteeModal', 'guaranteeProjeId', 'Proje seçiniz');
            NbtModal.showError('guaranteeModal', 'Proje seçimi zorunludur');
            return;
        }
        if (!data.Tur) {
            NbtModal.showFieldError('guaranteeModal', 'guaranteeType', 'Teminat türü zorunludur');
            NbtModal.showError('guaranteeModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        
        // PDF dosya kontrolu
        const fileInput = document.getElementById('guaranteeDosya');
        const file = fileInput?.files?.[0];
        if (file) {
            const fileErrors = this.validatePdfFile(file);
            if (fileErrors.length > 0) {
                fileInput.classList.add('is-invalid');
                document.getElementById('guaranteeDosyaError').textContent = fileErrors.join(' ');
                NbtModal.showError('guaranteeModal', fileErrors.join(' '));
                return;
            }
        }

        NbtModal.setLoading('guaranteeModal', true);
        try {
            let result;
            
            if (file || this.removeExistingFile) {
                const formData = new FormData();
                formData.append('MusteriId', data.MusteriId);
                if (data.ProjeId) formData.append('ProjeId', data.ProjeId);
                if (data.BelgeNo) formData.append('BelgeNo', data.BelgeNo);
                formData.append('Tur', data.Tur);
                if (data.BankaAdi) formData.append('BankaAdi', data.BankaAdi);
                formData.append('Tutar', data.Tutar);
                formData.append('ParaBirimi', data.ParaBirimi);
                if (data.TerminTarihi) formData.append('TerminTarihi', data.TerminTarihi);
                formData.append('Durum', data.Durum);
                if (file) formData.append('dosya', file);
                if (this.removeExistingFile) formData.append('removeFile', '1');
                
                const url = id ? `/api/guarantees/${id}` : '/api/guarantees';
                // PUT isteklerinde multipart/form-data PHP tarafindan parse edilmez
                // Bu yuzden formData ile gonderirken POST kullaniyoruz
                const method = 'POST';
                if (id) formData.append('_method', 'PUT');
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Authorization': 'Bearer ' + NbtUtils.getToken(),
                        'X-Tab-Id': NbtUtils.getTabId()
                    },
                    body: formData
                });
                
                const text = await response.text();
                try {
                    result = JSON.parse(text);
                } catch (parseErr) {
                    throw new Error('Sunucu hatası: Geçersiz yanıt');
                }
                
                if (!response.ok) {
                    throw new Error(result.error || 'İşlem başarısız');
                }
            } else {
                if (id) {
                    result = await NbtApi.put(`/api/guarantees/${id}`, data);
                } else {
                    result = await NbtApi.post('/api/guarantees', data);
                }
            }
            
            NbtToast.success(id ? 'Teminat güncellendi' : 'Teminat eklendi');
            NbtModal.close('guaranteeModal');
            await this.loadList();
            
            if (CustomerDetailModule.customerId) {
                await CustomerDetailModule.loadRelatedData('guarantees', '/api/guarantees');
                CustomerDetailModule.switchTab(CustomerDetailModule.activeTab);
            }
        } catch (err) {
            NbtModal.showError('guaranteeModal', err.message);
        } finally {
            NbtModal.setLoading('guaranteeModal', false);
        }
    },

    // Durum badge'ini dinamik parametrelerden olustur
    getStatusBadge(status, entity) {
        const cacheKey = `durum_${entity}`;
        const statuses = NbtParams._cache.statuses[cacheKey] || [];
        
        const found = statuses.find(s => s.Kod == status);
        if (found) {
            const badge = found.Deger || 'secondary';
            const textClass = (badge === 'warning' || badge === 'light') ? ' text-dark' : '';
            return `<span class="badge bg-${badge}${textClass}">${NbtUtils.escapeHtml(found.Etiket)}</span>`;
        }
        
        // Fallback - cache henuz yuklenmediyse
        const fallback = { 1: ['Bekliyor', 'warning'], 2: ['İade Edildi', 'info'], 3: ['Tahsil Edildi', 'success'], 4: ['Yandı', 'danger'] };
        const config = fallback[status] || ['Bilinmiyor', 'secondary'];
        return `<span class="badge bg-${config[1]}">${config[0]}</span>`;
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        document.getElementById('btnSaveGuarantee')?.addEventListener('click', () => this.save());
        
        // PDF dosya kaldirma butonu
        document.getElementById('btnRemoveGuaranteeFile')?.addEventListener('click', () => {
            this.removeExistingFile = true;
            document.getElementById('guaranteeCurrentFile')?.classList.add('d-none');
        });
        
        // Dosya secimi
        document.getElementById('guaranteeDosya')?.addEventListener('change', (e) => {
            const file = e.target.files?.[0];
            if (file) {
                const errors = this.validatePdfFile(file);
                if (errors.length > 0) {
                    e.target.classList.add('is-invalid');
                    document.getElementById('guaranteeDosyaError').textContent = errors.join(' ');
                } else {
                    e.target.classList.remove('is-invalid');
                    document.getElementById('guaranteeDosyaError').textContent = '';
                }
            }
        });
    }
};

// =============================================
// TEKLIF SAYFA FORMU - Modal yerine sayfa bazli form
// =============================================
const NbtOfferPageForm = {
    _eventsBound: false,
    musteriId: null,
    teklifId: null,
    removeExistingFile: false,

    async init(musteriId = null, teklifId = null) {
        // Sayfa formunun olup olmadigini kontrol et
        const form = document.getElementById('offerPageForm');
        if (!form) return;

        this.musteriId = musteriId || parseInt(document.getElementById('offerMusteriId')?.value) || 0;
        this.teklifId = teklifId || parseInt(document.getElementById('offerId')?.value) || 0;
        this.removeExistingFile = false;

        // Projeleri yukle
        await this.loadProjects();

        // Durum ve doviz select'lerini doldur
        await NbtParams.populateStatusSelect(document.getElementById('offerStatus'), 'teklif');
        await NbtParams.populateCurrencySelect(document.getElementById('offerCurrency'));

        // Eger edit modundaysak verileri yukle
        if (this.teklifId > 0) {
            await this.loadOfferData();
        }

        // Event binding
        this.bindEvents();
    },

    async loadProjects() {
        const projeSelect = document.getElementById('offerProjeId');
        if (!projeSelect || !this.musteriId) return;

        projeSelect.innerHTML = '<option value="">Proje Seçiniz...</option>';
        try {
            const response = await NbtApi.get(`/api/projects?musteri_id=${this.musteriId}`);
            let projects = response.data || [];
            
            // Pasif projeleri filtrele
            const pasifKodlar = await NbtParams.getPasifDurumKodlari('proje', true);
            projects = projects.filter(p => !pasifKodlar.includes(String(p.Durum)));
            
            projects.forEach(p => {
                projeSelect.innerHTML += `<option value="${p.Id}">${NbtUtils.escapeHtml(p.ProjeAdi || '')}</option>`;
            });
        } catch (err) {
            NbtLogger.error('Projeler yüklenemedi:', err);
        }
    },

    async loadOfferData() {
        try {
            const response = await NbtApi.get(`/api/offers/${this.teklifId}`);
            const offer = response.data;
            if (!offer) {
                NbtToast.error('Teklif bulunamadı');
                return;
            }

            document.getElementById('offerProjeId').value = offer.ProjeId || '';
            document.getElementById('offerSubject').value = offer.Konu || '';
            document.getElementById('offerAmount').value = NbtUtils.formatDecimal(offer.Tutar) || '0,00';
            document.getElementById('offerCurrency').value = offer.ParaBirimi || NbtParams.getDefaultCurrency();
            document.getElementById('offerDate').value = offer.TeklifTarihi?.split('T')[0] || '';
            document.getElementById('offerValidDate').value = offer.GecerlilikTarihi?.split('T')[0] || '';
            document.getElementById('offerStatus').value = offer.Durum ?? '';

            // Mevcut dosyayi goster
            if (offer.DosyaAdi && offer.DosyaYolu) {
                document.getElementById('offerCurrentFileName').textContent = offer.DosyaAdi;
                // Dosya tipine göre ikon
                const iconEl = document.getElementById('offerCurrentFileIcon');
                if (iconEl) {
                    const ext = (offer.DosyaAdi || '').split('.').pop().toLowerCase();
                    if (ext === 'pdf') {
                        iconEl.className = 'bi bi-file-pdf me-1';
                    } else if (ext === 'doc' || ext === 'docx') {
                        iconEl.className = 'bi bi-file-word me-1';
                    } else {
                        iconEl.className = 'bi bi-file-earmark me-1';
                    }
                }
                document.getElementById('offerCurrentFile').classList.remove('d-none');
            }
        } catch (err) {
            NbtToast.error('Teklif yüklenemedi: ' + err.message);
        }
    },

    validateOfferFile(file) {
        const errors = [];
        const maxSize = 10 * 1024 * 1024; // 10MB
        
        if (file.size > maxSize) {
            errors.push(`Dosya boyutu çok büyük. Maksimum 10MB.`);
        }
        
        const allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        const allowedExtensions = ['.pdf', '.doc', '.docx'];
        const fileExt = '.' + file.name.split('.').pop().toLowerCase();
        
        if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExt)) {
            errors.push('Sadece PDF veya Word dosyası (.pdf, .doc, .docx) yüklenebilir.');
        }
        
        return errors;
    },

    showError(message) {
        const errorEl = document.getElementById('offerFormError');
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.remove('d-none');
        }
    },

    clearError() {
        const errorEl = document.getElementById('offerFormError');
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.classList.add('d-none');
        }
    },

    async save() {
        this.clearError();
        
        const projeIdVal = document.getElementById('offerProjeId').value;
        const fileInput = document.getElementById('offerDosya');
        
        // Validation
        if (!projeIdVal) {
            this.showError('Proje seçimi zorunludur');
            return;
        }
        
        const tutar = parseFloat(document.getElementById('offerAmount').value) || 0;
        if (tutar <= 0) {
            this.showError('Lütfen geçerli bir tutar giriniz');
            return;
        }
        
        const teklifTarihi = document.getElementById('offerDate').value;
        if (!teklifTarihi) {
            this.showError('Teklif tarihi zorunludur');
            return;
        }
        
        const gecerlilikTarihi = document.getElementById('offerValidDate').value;
        if (!gecerlilikTarihi) {
            this.showError('Geçerlilik tarihi zorunludur');
            return;
        }
        
        // Dosya dogrulama
        const file = fileInput?.files?.[0];
        if (file) {
            const errors = this.validateOfferFile(file);
            if (errors.length > 0) {
                this.showError(errors.join(' '));
                return;
            }
        }

        const btn = document.getElementById('btnSaveOfferPage');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Kaydediliyor...';
        }

        try {
            const formData = new FormData();
            formData.append('MusteriId', this.musteriId);
            if (projeIdVal) formData.append('ProjeId', projeIdVal);
            formData.append('Konu', document.getElementById('offerSubject').value.trim() || '');
            formData.append('Tutar', tutar);
            formData.append('ParaBirimi', document.getElementById('offerCurrency').value);
            formData.append('TeklifTarihi', teklifTarihi);
            formData.append('GecerlilikTarihi', gecerlilikTarihi);
            formData.append('Durum', document.getElementById('offerStatus').value);
            
            if (file) {
                formData.append('dosya', file);
            } else if (this.removeExistingFile) {
                formData.append('removeFile', '1');
            }
            
            if (this.teklifId > 0) {
                await NbtApi.postFormData(`/api/offers/${this.teklifId}`, formData, 'PUT');
                NbtToast.success('Teklif güncellendi');
            } else {
                await NbtApi.postFormData('/api/offers', formData);
                NbtToast.success('Teklif eklendi');
            }
            
            // Basarili - listeye don
            window.location.href = `/customer/${this.musteriId}?tab=teklifler`;
        } catch (err) {
            this.showError(err.message);
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Kaydet';
            }
        }
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        
        document.getElementById('btnSaveOfferPage')?.addEventListener('click', () => this.save());
        
        document.getElementById('offerDosya')?.addEventListener('change', (e) => {
            const file = e.target.files?.[0];
            if (file) {
                const errors = this.validateOfferFile(file);
                if (errors.length > 0) {
                    this.showError(errors.join(' '));
                }
            }
        });
        
        document.getElementById('btnRemoveOfferFile')?.addEventListener('click', () => {
            this.removeExistingFile = true;
            document.getElementById('offerCurrentFile')?.classList.add('d-none');
        });
    }
};

// =============================================
// SOZLESME SAYFA FORMU - Modal yerine sayfa bazli form
// =============================================
const NbtContractPageForm = {
    _eventsBound: false,
    musteriId: null,
    sozlesmeId: null,
    removeExistingFile: false,

    async init(musteriId = null, sozlesmeId = null) {
        // Sayfa formunun olup olmadigini kontrol et
        const form = document.getElementById('contractPageForm');
        if (!form) return;

        this.musteriId = musteriId || parseInt(document.getElementById('contractMusteriId')?.value) || 0;
        this.sozlesmeId = sozlesmeId || parseInt(document.getElementById('contractId')?.value) || 0;
        this.removeExistingFile = false;

        // Projeleri yukle
        await this.loadProjects();

        // Durum ve doviz select'lerini doldur
        await NbtParams.populateStatusSelect(document.getElementById('contractStatus'), 'sozlesme');
        await NbtParams.populateCurrencySelect(document.getElementById('contractCurrency'));

        // Eger edit modundaysak verileri yukle
        if (this.sozlesmeId > 0) {
            await this.loadContractData();
        }

        // Event binding
        this.bindEvents();
    },

    async loadProjects() {
        const projeSelect = document.getElementById('contractProjeId');
        if (!projeSelect || !this.musteriId) return;

        projeSelect.innerHTML = '<option value="">Proje Seçiniz...</option>';
        try {
            const response = await NbtApi.get(`/api/projects?musteri_id=${this.musteriId}`);
            let projects = response.data || [];
            
            // Pasif projeleri filtrele
            const pasifKodlar = await NbtParams.getPasifDurumKodlari('proje', true);
            projects = projects.filter(p => !pasifKodlar.includes(String(p.Durum)));
            
            projects.forEach(p => {
                projeSelect.innerHTML += `<option value="${p.Id}">${NbtUtils.escapeHtml(p.ProjeAdi || '')}</option>`;
            });
        } catch (err) {
            NbtLogger.error('Projeler yüklenemedi:', err);
        }
    },

    async loadContractData() {
        try {
            const response = await NbtApi.get(`/api/contracts/${this.sozlesmeId}`);
            const contract = response.data;
            if (!contract) {
                NbtToast.error('Sözleşme bulunamadı');
                return;
            }

            document.getElementById('contractProjeId').value = contract.ProjeId || '';
            document.getElementById('contractStart').value = contract.SozlesmeTarihi?.split('T')[0] || '';
            document.getElementById('contractAmount').value = NbtUtils.formatDecimal(contract.Tutar) || '0,00';
            document.getElementById('contractCurrency').value = contract.ParaBirimi || NbtParams.getDefaultCurrency();
            document.getElementById('contractStatus').value = contract.Durum ?? '';

            // Mevcut dosyayi goster
            if (contract.DosyaAdi && contract.DosyaYolu) {
                document.getElementById('contractCurrentFileName').textContent = contract.DosyaAdi;
                document.getElementById('contractCurrentFile').classList.remove('d-none');
            }
        } catch (err) {
            NbtToast.error('Sözleşme yüklenemedi: ' + err.message);
        }
    },

    validatePdfFile(file) {
        const errors = [];
        const maxSize = 10 * 1024 * 1024; // 10MB
        
        if (file.size > maxSize) {
            errors.push(`Dosya boyutu çok büyük. Maksimum 10MB.`);
        }
        
        const allowedTypes = ['application/pdf'];
        const allowedExtensions = ['.pdf'];
        const fileExt = '.' + file.name.split('.').pop().toLowerCase();
        
        if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExt)) {
            errors.push('Sadece PDF dosyası yüklenebilir.');
        }
        
        return errors;
    },

    showError(message) {
        const errorEl = document.getElementById('contractFormError');
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.remove('d-none');
        }
    },

    clearError() {
        const errorEl = document.getElementById('contractFormError');
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.classList.add('d-none');
        }
    },

    async save() {
        this.clearError();
        
        const projeIdVal = document.getElementById('contractProjeId').value;
        const fileInput = document.getElementById('contractDosya');
        
        // Validation
        if (!projeIdVal) {
            this.showError('Proje seçimi zorunludur');
            return;
        }
        
        const tutar = parseFloat(document.getElementById('contractAmount').value) || 0;
        if (tutar <= 0) {
            this.showError('Lütfen geçerli bir tutar giriniz');
            return;
        }
        
        // Dosya dogrulama
        const file = fileInput?.files?.[0];
        if (file) {
            const errors = this.validatePdfFile(file);
            if (errors.length > 0) {
                this.showError(errors.join(' '));
                return;
            }
        }

        const btn = document.getElementById('btnSaveContractPage');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Kaydediliyor...';
        }

        try {
            const formData = new FormData();
            formData.append('MusteriId', this.musteriId);
            if (projeIdVal) formData.append('ProjeId', projeIdVal);
            formData.append('SozlesmeTarihi', document.getElementById('contractStart').value || '');
            formData.append('Tutar', tutar);
            formData.append('ParaBirimi', document.getElementById('contractCurrency').value);
            formData.append('Durum', document.getElementById('contractStatus').value);
            
            if (file) {
                formData.append('dosya', file);
            } else if (this.removeExistingFile) {
                formData.append('removeFile', '1');
            }
            
            if (this.sozlesmeId > 0) {
                await NbtApi.postFormData(`/api/contracts/${this.sozlesmeId}`, formData, 'PUT');
                NbtToast.success('Sözleşme güncellendi');
            } else {
                await NbtApi.postFormData('/api/contracts', formData);
                NbtToast.success('Sözleşme eklendi');
            }
            
            // Basarili - listeye don
            window.location.href = `/customer/${this.musteriId}?tab=sozlesmeler`;
        } catch (err) {
            this.showError(err.message);
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Kaydet';
            }
        }
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        
        document.getElementById('btnSaveContractPage')?.addEventListener('click', () => this.save());
        
        document.getElementById('contractDosya')?.addEventListener('change', (e) => {
            const file = e.target.files?.[0];
            if (file) {
                const errors = this.validatePdfFile(file);
                if (errors.length > 0) {
                    this.showError(errors.join(' '));
                }
            }
        });
        
        document.getElementById('btnRemoveContractFile')?.addEventListener('click', () => {
            this.removeExistingFile = true;
            document.getElementById('contractCurrentFile')?.classList.add('d-none');
        });
    }
};

// =============================================
// MUSTERI SAYFA FORMU - Modal yerine tam sayfa
// =============================================
const NbtCustomerPageForm = {
    _eventsBound: false,
    musteriId: null,

    async init(musteriId = null) {
        // Sayfa formunun olup olmadigini kontrol et
        const form = document.getElementById('customerPageForm');
        if (!form) return;

        this.musteriId = musteriId || parseInt(document.getElementById('customerId')?.value) || 0;

        // Il/Ilce select'lerini doldur
        await this.loadCities();

        // Eger edit modundaysak verileri yukle
        if (this.musteriId > 0) {
            await this.loadCustomerData();
        }

        // Event binding
        this.bindEvents();
    },

    async loadCities() {
        const sehirSelect = document.getElementById('customerSehirId');
        if (!sehirSelect) return;

        sehirSelect.innerHTML = '<option value="">İl Seçiniz...</option>';
        try {
            const response = await NbtApi.get('/api/cities');
            const cities = response.data || [];
            cities.forEach(c => {
                sehirSelect.innerHTML += `<option value="${c.Id}">${NbtUtils.escapeHtml(c.Ad)}</option>`;
            });
        } catch (err) {
            NbtLogger.error('İller yüklenemedi:', err);
        }
    },

    async loadDistricts(sehirId, selectedValue = null) {
        const ilceSelect = document.getElementById('customerIlceId');
        if (!ilceSelect) return;

        if (!sehirId) {
            ilceSelect.innerHTML = '<option value="">Önce il seçiniz...</option>';
            ilceSelect.disabled = true;
            return;
        }

        ilceSelect.innerHTML = '<option value="">İlçe Seçiniz...</option>';
        ilceSelect.disabled = false;

        try {
            const response = await NbtApi.get(`/api/districts?sehir_id=${sehirId}`);
            const districts = response.data || [];
            districts.forEach(d => {
                const selected = selectedValue && String(d.Id) === String(selectedValue) ? ' selected' : '';
                ilceSelect.innerHTML += `<option value="${d.Id}"${selected}>${NbtUtils.escapeHtml(d.Ad)}</option>`;
            });
        } catch (err) {
            NbtLogger.error('İlçeler yüklenemedi:', err);
        }
    },

    async loadCustomerData() {
        try {
            const response = await NbtApi.get(`/api/customers/${this.musteriId}`);
            const customer = response.data;
            if (!customer) {
                NbtToast.error('Müşteri bulunamadı');
                return;
            }

            document.getElementById('customerUnvan').value = customer.Unvan || '';
            document.getElementById('customerMusteriKodu').value = customer.MusteriKodu || '';
            document.getElementById('customerVergiDairesi').value = customer.VergiDairesi || '';
            document.getElementById('customerVergiNo').value = customer.VergiNo || '';
            document.getElementById('customerMersisNo').value = customer.MersisNo || '';
            document.getElementById('customerTelefon').value = customer.Telefon || '';
            document.getElementById('customerFaks').value = customer.Faks || '';
            document.getElementById('customerWeb').value = customer.Web || '';
            document.getElementById('customerAdres').value = customer.Adres || '';
            document.getElementById('customerAciklama').value = customer.Aciklama || '';

            // Il/Ilce select'lerini doldur
            if (customer.SehirId) {
                document.getElementById('customerSehirId').value = customer.SehirId;
                await this.loadDistricts(customer.SehirId, customer.IlceId);
            }
        } catch (err) {
            this.showError('Müşteri yüklenemedi: ' + err.message);
        }
    },

    showError(message) {
        const errorEl = document.getElementById('customerFormError');
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.remove('d-none');
        }
    },

    clearError() {
        const errorEl = document.getElementById('customerFormError');
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.classList.add('d-none');
        }
    },

    validate() {
        this.clearError();
        const unvan = document.getElementById('customerUnvan')?.value?.trim();
        const vergiDairesi = document.getElementById('customerVergiDairesi')?.value?.trim();
        const vergiNo = document.getElementById('customerVergiNo')?.value?.trim();

        if (!unvan || unvan.length < 2) {
            this.showError('Ünvan en az 2 karakter olmalıdır');
            return false;
        }
        if (!vergiDairesi) {
            this.showError('Vergi Dairesi zorunludur');
            return false;
        }
        if (!vergiNo || !/^\d{10,11}$/.test(vergiNo)) {
            this.showError('Vergi No 10-11 haneli sayısal olmalıdır');
            return false;
        }
        return true;
    },

    async save() {
        if (!this.validate()) return;

        const btn = document.getElementById('btnSaveCustomerPage');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Kaydediliyor...';
        }

        try {
            const data = {
                Unvan: document.getElementById('customerUnvan').value.trim(),
                MusteriKodu: document.getElementById('customerMusteriKodu').value.trim() || null,
                VergiDairesi: document.getElementById('customerVergiDairesi').value.trim(),
                VergiNo: document.getElementById('customerVergiNo').value.trim(),
                MersisNo: document.getElementById('customerMersisNo').value.trim() || null,
                Telefon: document.getElementById('customerTelefon').value.trim() || null,
                Faks: document.getElementById('customerFaks').value.trim() || null,
                Web: document.getElementById('customerWeb').value.trim() || null,
                SehirId: document.getElementById('customerSehirId').value || null,
                IlceId: document.getElementById('customerIlceId').value || null,
                Adres: document.getElementById('customerAdres').value.trim() || null,
                Aciklama: document.getElementById('customerAciklama').value.trim() || null
            };

            if (this.musteriId > 0) {
                await NbtApi.put(`/api/customers/${this.musteriId}`, data);
                NbtToast.success('Müşteri güncellendi');
                window.location.href = `/customer/${this.musteriId}`;
            } else {
                const result = await NbtApi.post('/api/customers', data);
                NbtToast.success('Müşteri eklendi');
                if (result && result.id) {
                    window.location.href = `/customer/${result.id}`;
                } else {
                    window.location.href = '/dashboard';
                }
            }
        } catch (err) {
            this.showError(err.message);
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Kaydet';
            }
        }
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;

        document.getElementById('btnSaveCustomerPage')?.addEventListener('click', () => this.save());

        // Il secildiginde ilceleri yukle
        document.getElementById('customerSehirId')?.addEventListener('change', (e) => {
            this.loadDistricts(e.target.value);
        });
    }
};

// =============================================
// GENEL SAYFA FORMU - Tum modulleri kapsayan PageForm
// =============================================
const NbtPageForm = {
    // Modul konfigurasyonlari
    configs: {
        'contact': {
            formId: 'contactPageForm',
            apiEndpoint: '/api/contacts',
            tabKey: 'kisiler',
            successMessageCreate: 'Kişi eklendi',
            successMessageUpdate: 'Kişi güncellendi',
            saveButtonId: 'btnSaveContactPage',
            errorElementId: 'contactFormError',
            idFieldId: 'contactId',
            musteriIdFieldId: 'contactMusteriId',
            needsProject: true,
            projeSelectId: 'contactProjeId',
            fields: ['contactProjeId', 'contactAdSoyad', 'contactUnvan', 'contactTelefon', 'contactDahiliNo', 'contactEmail', 'contactNotlar'],
            fieldMappings: {
                'contactProjeId': 'ProjeId',
                'contactAdSoyad': 'AdSoyad',
                'contactUnvan': 'Unvan',
                'contactTelefon': 'Telefon',
                'contactDahiliNo': 'DahiliNo',
                'contactEmail': 'Email',
                'contactNotlar': 'Notlar'
            },
            required: ['contactProjeId', 'contactAdSoyad'],
            requiredMessages: {
                'contactProjeId': 'Proje seçimi zorunludur',
                'contactAdSoyad': 'Ad Soyad zorunludur'
            }
        },
        'meeting': {
            formId: 'meetingPageForm',
            apiEndpoint: '/api/meetings',
            tabKey: 'gorusme',
            successMessageCreate: 'Görüşme eklendi',
            successMessageUpdate: 'Görüşme güncellendi',
            saveButtonId: 'btnSaveMeetingPage',
            errorElementId: 'meetingFormError',
            idFieldId: 'meetingId',
            musteriIdFieldId: 'meetingMusteriId',
            needsProject: true,
            projeSelectId: 'meetingProjeId',
            fields: ['meetingProjeId', 'meetingTarih', 'meetingKonu', 'meetingKisi', 'meetingEposta', 'meetingTelefon', 'meetingNotlar'],
            fieldMappings: {
                'meetingProjeId': 'ProjeId',
                'meetingTarih': 'Tarih',
                'meetingKonu': 'Konu',
                'meetingKisi': 'GorusulenKisi',
                'meetingEposta': 'Eposta',
                'meetingTelefon': 'Telefon',
                'meetingNotlar': 'Notlar'
            },
            required: ['meetingProjeId', 'meetingTarih', 'meetingKonu'],
            requiredMessages: {
                'meetingProjeId': 'Proje seçimi zorunludur',
                'meetingTarih': 'Tarih zorunludur',
                'meetingKonu': 'Konu zorunludur'
            }
        },
        'project': {
            formId: 'projectPageForm',
            apiEndpoint: '/api/projects',
            tabKey: 'projeler',
            successMessageCreate: 'Proje eklendi',
            successMessageUpdate: 'Proje güncellendi',
            saveButtonId: 'btnSaveProjectPage',
            errorElementId: 'projectFormError',
            idFieldId: 'projectId',
            musteriIdFieldId: 'projectMusteriId',
            needsProject: false,
            fields: ['projectName', 'projectStatus'],
            fieldMappings: {
                'projectName': 'ProjeAdi',
                'projectStatus': 'Durum'
            },
            required: ['projectName'],
            requiredMessages: {
                'projectName': 'Proje adı zorunludur'
            },
            statusField: { id: 'projectStatus', type: 'proje' }
        },
        'calendar': {
            formId: 'calendarPageForm',
            apiEndpoint: '/api/takvim',
            tabKey: 'takvim',
            successMessageCreate: 'Takvim kaydı eklendi',
            successMessageUpdate: 'Takvim kaydı güncellendi',
            saveButtonId: 'btnSaveCalendarPage',
            errorElementId: 'calendarFormError',
            idFieldId: 'calendarId',
            musteriIdFieldId: 'calendarMusteriId',
            needsProject: true,
            projeSelectId: 'calendarProjeId',
            fields: ['calendarProjeId', 'calendarTerminTarihi', 'calendarOzet'],
            fieldMappings: {
                'calendarProjeId': 'ProjeId',
                'calendarTerminTarihi': 'TerminTarihi',
                'calendarOzet': 'Ozet'
            },
            required: ['calendarProjeId', 'calendarTerminTarihi', 'calendarOzet'],
            requiredMessages: {
                'calendarProjeId': 'Proje seçimi zorunludur',
                'calendarTerminTarihi': 'Termin tarihi zorunludur',
                'calendarOzet': 'İşin özeti zorunludur'
            }
        },
        'stamp-tax': {
            formId: 'stampTaxPageForm',
            apiEndpoint: '/api/stamp-taxes',
            tabKey: 'damgavergisi',
            successMessageCreate: 'Damga vergisi eklendi',
            successMessageUpdate: 'Damga vergisi güncellendi',
            saveButtonId: 'btnSaveStampTaxPage',
            errorElementId: 'stampTaxFormError',
            idFieldId: 'stampTaxId',
            musteriIdFieldId: 'stampTaxMusteriId',
            needsProject: true,
            projeSelectId: 'stampTaxProjeId',
            sozlesmeSelectId: 'stampTaxSozlesmeId',
            fields: ['stampTaxProjeId', 'stampTaxSozlesmeId', 'stampTaxBelgeTarihi', 'stampTaxTutar', 'stampTaxOdemeDurumu', 'stampTaxNotlar'],
            fieldMappings: {
                'stampTaxProjeId': 'ProjeId',
                'stampTaxSozlesmeId': 'SozlesmeId',
                'stampTaxBelgeTarihi': 'Tarih',
                'stampTaxTutar': 'Tutar',
                'stampTaxOdemeDurumu': 'OdemeDurumu',
                'stampTaxNotlar': 'Notlar'
            },
            required: ['stampTaxProjeId', 'stampTaxBelgeTarihi', 'stampTaxTutar'],
            requiredMessages: {
                'stampTaxProjeId': 'Proje seçimi zorunludur',
                'stampTaxBelgeTarihi': 'Belge tarihi zorunludur',
                'stampTaxTutar': 'Tutar zorunludur'
            },
            amountField: 'stampTaxTutar'
        },
        'guarantee': {
            formId: 'guaranteePageForm',
            apiEndpoint: '/api/guarantees',
            tabKey: 'teminatlar',
            successMessageCreate: 'Teminat eklendi',
            successMessageUpdate: 'Teminat güncellendi',
            saveButtonId: 'btnSaveGuaranteePage',
            errorElementId: 'guaranteeFormError',
            idFieldId: 'guaranteeId',
            musteriIdFieldId: 'guaranteeMusteriId',
            needsProject: true,
            projeSelectId: 'guaranteeProjeId',
            fields: ['guaranteeProjeId', 'guaranteeTur', 'guaranteeTutar', 'guaranteeDoviz', 'guaranteeTerminTarihi', 'guaranteeBanka', 'guaranteeDurum', 'guaranteeNotlar'],
            fieldMappings: {
                'guaranteeProjeId': 'ProjeId',
                'guaranteeTur': 'Tur',
                'guaranteeTutar': 'Tutar',
                'guaranteeDoviz': 'ParaBirimi',
                'guaranteeTerminTarihi': 'TerminTarihi',
                'guaranteeBanka': 'BankaAdi',
                'guaranteeDurum': 'Durum',
                'guaranteeNotlar': 'Notlar'
            },
            required: ['guaranteeProjeId', 'guaranteeTur', 'guaranteeTutar', 'guaranteeTerminTarihi'],
            requiredMessages: {
                'guaranteeProjeId': 'Proje seçimi zorunludur',
                'guaranteeTur': 'Teminat türü seçimi zorunludur',
                'guaranteeTutar': 'Tutar zorunludur',
                'guaranteeTerminTarihi': 'Termin tarihi zorunludur'
            },
            amountField: 'guaranteeTutar',
            currencyField: 'guaranteeDoviz',
            statusField: { id: 'guaranteeTur', type: 'teminat' }
        },
        'invoice': {
            formId: 'invoicePageForm',
            apiEndpoint: '/api/invoices',
            tabKey: 'faturalar',
            successMessageCreate: 'Fatura eklendi',
            successMessageUpdate: 'Fatura güncellendi',
            saveButtonId: 'btnSaveInvoicePage',
            errorElementId: 'invoiceFormError',
            idFieldId: 'invoiceId',
            musteriIdFieldId: 'invoiceMusteriId',
            needsProject: true,
            projeSelectId: 'invoiceProjeId',
            sozlesmeSelectId: 'invoiceSozlesmeId',
            fields: ['invoiceProjeId', 'invoiceSozlesmeId', 'invoiceTur', 'invoiceFaturaNo', 'invoiceFaturaTarihi', 'invoiceToplamTutar', 'invoiceDoviz', 'invoiceKdvOrani', 'invoiceVadeTarihi', 'invoiceOdemeDurumu', 'invoiceDurum', 'invoiceNotlar'],
            fieldMappings: {
                'invoiceProjeId': 'ProjeId',
                'invoiceSozlesmeId': 'SozlesmeId',
                'invoiceTur': 'FaturaTuru',
                'invoiceFaturaNo': 'FaturaNo',
                'invoiceFaturaTarihi': 'FaturaTarihi',
                'invoiceToplamTutar': 'Tutar',
                'invoiceDoviz': 'ParaBirimi',
                'invoiceKdvOrani': 'KdvOrani',
                'invoiceVadeTarihi': 'VadeTarihi',
                'invoiceOdemeDurumu': 'OdemeDurumu',
                'invoiceDurum': 'Durum',
                'invoiceNotlar': 'Notlar'
            },
            required: ['invoiceProjeId', 'invoiceFaturaNo', 'invoiceFaturaTarihi', 'invoiceToplamTutar'],
            requiredMessages: {
                'invoiceProjeId': 'Proje seçimi zorunludur',
                'invoiceFaturaNo': 'Fatura no zorunludur',
                'invoiceFaturaTarihi': 'Fatura tarihi zorunludur',
                'invoiceToplamTutar': 'Tutar zorunludur'
            },
            amountField: 'invoiceToplamTutar',
            currencyField: 'invoiceDoviz'
        },
        'payment': {
            formId: 'paymentPageForm',
            apiEndpoint: '/api/payments',
            tabKey: 'odemeler',
            successMessageCreate: 'Ödeme eklendi',
            successMessageUpdate: 'Ödeme güncellendi',
            saveButtonId: 'btnSavePaymentPage',
            errorElementId: 'paymentFormError',
            idFieldId: 'paymentId',
            musteriIdFieldId: 'paymentMusteriId',
            needsProject: true,
            projeSelectId: 'paymentProjeId',
            faturaSelectId: 'paymentFaturaId',
            fields: ['paymentProjeId', 'paymentFaturaId', 'paymentTarih', 'paymentTutar', 'paymentDoviz', 'paymentTur', 'paymentBanka', 'paymentReferans', 'paymentNotlar'],
            fieldMappings: {
                'paymentProjeId': 'ProjeId',
                'paymentFaturaId': 'FaturaId',
                'paymentTarih': 'Tarih',
                'paymentTutar': 'Tutar',
                'paymentDoviz': 'ParaBirimi',
                'paymentTur': 'OdemeTuru',
                'paymentBanka': 'BankaHesap',
                'paymentReferans': 'ReferansNo',
                'paymentNotlar': 'Notlar'
            },
            required: ['paymentProjeId', 'paymentFaturaId', 'paymentTarih', 'paymentTutar', 'paymentTur'],
            requiredMessages: {
                'paymentProjeId': 'Proje seçimi zorunludur',
                'paymentFaturaId': 'Fatura seçimi zorunludur',
                'paymentTarih': 'Ödeme tarihi zorunludur',
                'paymentTutar': 'Tutar zorunludur',
                'paymentTur': 'Ödeme türü seçimi zorunludur'
            },
            amountField: 'paymentTutar',
            currencyField: 'paymentDoviz'
        },
        'file': {
            formId: 'filePageForm',
            apiEndpoint: '/api/files',
            tabKey: 'dosyalar',
            successMessageCreate: 'Dosya yüklendi',
            successMessageUpdate: 'Dosya güncellendi',
            saveButtonId: 'btnSaveFilePage',
            errorElementId: 'fileFormError',
            idFieldId: 'fileId',
            musteriIdFieldId: 'fileMusteriId',
            needsProject: false,
            projeSelectId: 'fileProjeId',
            hasFileUpload: true,
            fileInputId: 'fileUpload',
            fields: ['fileProjeId', 'fileAciklama'],
            fieldMappings: {
                'fileProjeId': 'ProjeId',
                'fileAciklama': 'Aciklama'
            },
            required: [],
            requiredMessages: {}
        }
    },
    
    // Aktif modul durumu
    activeModule: null,
    musteriId: null,
    recordId: null,
    tabKey: null,

    /**
     * Modul baslat
     */
    async init(moduleType, musteriId, recordId, tabKey) {
        const config = this.configs[moduleType];
        if (!config) {
            NbtLogger.error('NbtPageForm: Bilinmeyen modul tipi:', moduleType);
            return;
        }

        const form = document.getElementById(config.formId);
        if (!form) return;

        this.activeModule = moduleType;
        this.musteriId = musteriId || parseInt(document.getElementById(config.musteriIdFieldId)?.value) || 0;
        this.recordId = recordId || parseInt(document.getElementById(config.idFieldId)?.value) || 0;
        this.tabKey = tabKey || config.tabKey;

        // Projeleri yukle (gerekiyorsa)
        if (config.needsProject && config.projeSelectId) {
            await this.loadProjects(config);
        }

        // Proje gerekli olmasa bile projeSelectId varsa yukle
        if (!config.needsProject && config.projeSelectId) {
            await this.loadProjects(config);
        }

        // Sozlesme select varsa yukle
        if (config.sozlesmeSelectId) {
            await this.loadContracts(config);
        }

        // Fatura select varsa yukle
        if (config.faturaSelectId) {
            await this.loadInvoices(config);
        }

        // Durum parametrelerini doldur
        if (config.statusField) {
            await NbtParams.populateStatusSelect(
                document.getElementById(config.statusField.id), 
                config.statusField.type
            );
        }

        // Teminat turu parametrelerini doldur
        if (moduleType === 'guarantee') {
            await this.loadGuaranteeTypes();
        }

        // Doviz select'ini doldur (varsa)
        if (config.currencyField) {
            await NbtParams.populateCurrencySelect(
                document.getElementById(config.currencyField)
            );
        }

        // Edit modundaysa verileri yukle
        if (this.recordId > 0) {
            await this.loadRecordData(config);
        }

        // Event binding
        this.bindEvents(config);
    },

    async loadProjects(config) {
        const projeSelect = document.getElementById(config.projeSelectId);
        if (!projeSelect || !this.musteriId) return;

        if (window.NbtProjectSelect) {
            await window.NbtProjectSelect.loadForCustomer(projeSelect, this.musteriId);
            return;
        }

        projeSelect.innerHTML = '<option value="">Proje Seçiniz...</option>';
        try {
            const response = await NbtApi.get(`/api/projects?musteri_id=${this.musteriId}`);
            let projects = response.data || [];
            const pasifKodlar = await NbtParams.getPasifDurumKodlari('proje', true);
            projects = projects.filter(p => !pasifKodlar.includes(String(p.Durum)));
            const uniq = new Map();
            projects.forEach(p => {
                const key = String(p.Id);
                if (!uniq.has(key)) uniq.set(key, p);
            });
            Array.from(uniq.values()).forEach(p => {
                projeSelect.innerHTML += `<option value="${p.Id}">${NbtUtils.escapeHtml(p.ProjeAdi || '')}</option>`;
            });
        } catch (err) {
            NbtLogger.error('Projeler yüklenemedi:', err);
        }
    },

    async loadContracts(config) {
        const sozlesmeSelect = document.getElementById(config.sozlesmeSelectId);
        if (!sozlesmeSelect || !this.musteriId) return;

        sozlesmeSelect.innerHTML = '<option value="">Sözleşme Seçiniz (Opsiyonel)...</option>';
        try {
            const response = await NbtApi.get(`/api/contracts?musteri_id=${this.musteriId}`);
            const contracts = response.data || [];
            contracts.forEach(c => {
                const label = `#${c.Id} - ${NbtUtils.formatDate(c.SozlesmeTarihi)} - ${NbtUtils.formatMoney(c.Tutar, c.ParaBirimi)}`;
                sozlesmeSelect.innerHTML += `<option value="${c.Id}">${NbtUtils.escapeHtml(label)}</option>`;
            });
        } catch (err) {
            NbtLogger.error('Sözleşmeler yüklenemedi:', err);
        }
    },

    async loadInvoices(config) {
        const faturaSelect = document.getElementById(config.faturaSelectId);
        if (!faturaSelect || !this.musteriId) return;

        faturaSelect.innerHTML = '<option value="">Fatura Seçiniz...</option>';
        try {
            const response = await NbtApi.get(`/api/invoices?musteri_id=${this.musteriId}`);
            const invoices = response.data || [];
            
            // Sadece bakiyesi > 0 olan faturaları göster (düzenleme modunda mevcut fatura hariç)
            const editingPaymentId = this.recordId || 0;
            
            invoices.forEach(f => {
                const kalan = parseFloat(f.Kalan) || 0;
                
                // Bakiyesi 0 olan faturaları gösterme (düzenleme modunda mevcut fatura hariç)
                if (kalan <= 0 && editingPaymentId === 0) return;
                
                const kalanStr = kalan > 0 ? ` [Kalan: ${NbtUtils.formatMoney(kalan, f.ParaBirimi)}]` : ' [Tam Ödendi]';
                const label = `${f.FaturaNo} - ${NbtUtils.formatDate(f.FaturaTarihi)} - ${NbtUtils.formatMoney(f.Tutar, f.ParaBirimi)}${kalanStr}`;
                faturaSelect.innerHTML += `<option value="${f.Id}">${NbtUtils.escapeHtml(label)}</option>`;
            });
        } catch (err) {
            NbtLogger.error('Faturalar yüklenemedi:', err);
        }
    },

    async loadGuaranteeTypes() {
        const turSelect = document.getElementById('guaranteeTur');
        if (!turSelect) return;

        turSelect.innerHTML = '<option value="">Teminat Türü Seçiniz...</option>';
        try {
            const params = await NbtParams.getByGroup('teminat');
            params.forEach(p => {
                turSelect.innerHTML += `<option value="${NbtUtils.escapeHtml(p.Deger)}">${NbtUtils.escapeHtml(p.Etiket)}</option>`;
            });
        } catch (err) {
            // Fallback
            const defaultTypes = ['Nakit', 'Teminat Mektubu'];
            defaultTypes.forEach(t => {
                turSelect.innerHTML += `<option value="${t}">${t}</option>`;
            });
        }
    },

    async loadRecordData(config) {
        try {
            const response = await NbtApi.get(`${config.apiEndpoint}/${this.recordId}`);
            const data = response.data;
            if (!data) {
                NbtToast.error('Kayıt bulunamadı');
                return;
            }

            // Alanlari doldur
            for (const fieldId of config.fields) {
                const apiField = config.fieldMappings[fieldId];
                const element = document.getElementById(fieldId);
                if (!element || !apiField) continue;

                let value = data[apiField];
                
                // Tarih alanlari
                if (fieldId.includes('Tarih') || fieldId.includes('tarihi')) {
                    value = value?.split('T')[0] || '';
                }
                
                // Tutar alanlari
                if (config.amountField === fieldId) {
                    value = NbtUtils.formatDecimal(value) || '0,00';
                }

                element.value = value ?? '';
            }

            // Doviz alaninda deger yoksa varsayilan dovizi sec
            if (config.currencyField) {
                const currencyEl = document.getElementById(config.currencyField);
                if (currencyEl && !currencyEl.value) {
                    currencyEl.value = NbtParams.getDefaultCurrency();
                }
            }

            // Dosya modulu icin mevcut dosyayi goster
            if (this.activeModule === 'file' && data.DosyaAdi) {
                const currentNameEl = document.getElementById('fileCurrentName');
                if (currentNameEl) {
                    currentNameEl.textContent = data.DosyaAdi;
                }
            }
        } catch (err) {
            NbtToast.error('Kayıt yüklenemedi: ' + err.message);
        }
    },

    showError(message) {
        const config = this.configs[this.activeModule];
        const errorEl = document.getElementById(config?.errorElementId);
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.remove('d-none');
        }
    },

    clearError() {
        const config = this.configs[this.activeModule];
        const errorEl = document.getElementById(config?.errorElementId);
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.classList.add('d-none');
        }
    },

    async save() {
        const config = this.configs[this.activeModule];
        if (!config) return;

        this.clearError();

        // Validation
        for (const fieldId of config.required) {
            const element = document.getElementById(fieldId);
            const value = element?.value?.trim();
            if (!value) {
                this.showError(config.requiredMessages[fieldId] || 'Zorunlu alan eksik');
                element?.focus();
                return;
            }
        }

        // Tutar validation
        if (config.amountField) {
            const tutarEl = document.getElementById(config.amountField);
            const tutar = NbtUtils.parseDecimal(tutarEl?.value);
            if (tutar <= 0) {
                this.showError('Lütfen geçerli bir tutar giriniz');
                tutarEl?.focus();
                return;
            }
        }

        const btn = document.getElementById(config.saveButtonId);
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Kaydediliyor...';
        }

        try {
            let apiData;
            let useFormData = config.hasFileUpload;

            if (useFormData) {
                apiData = new FormData();
                apiData.append('MusteriId', this.musteriId);
                
                // File input
                const fileInput = document.getElementById(config.fileInputId);
                const file = fileInput?.files?.[0];
                if (file) {
                    apiData.append('dosya', file);
                }

                // Diger alanlar
                for (const fieldId of config.fields) {
                    const apiField = config.fieldMappings[fieldId];
                    const element = document.getElementById(fieldId);
                    if (!element || !apiField) continue;

                    let value = element.value?.trim() || '';
                    
                    if (config.amountField === fieldId) {
                        value = NbtUtils.parseDecimal(value);
                    }

                    apiData.append(apiField, value);
                }
            } else {
                apiData = { MusteriId: this.musteriId };

                for (const fieldId of config.fields) {
                    const apiField = config.fieldMappings[fieldId];
                    const element = document.getElementById(fieldId);
                    if (!element || !apiField) continue;

                    let value = element.value?.trim() || '';
                    
                    if (config.amountField === fieldId) {
                        value = NbtUtils.parseDecimal(value);
                    }

                    apiData[apiField] = value;
                }
            }

            if (this.recordId > 0) {
                if (useFormData) {
                    await NbtApi.postFormData(`${config.apiEndpoint}/${this.recordId}`, apiData, 'PUT');
                } else {
                    await NbtApi.put(`${config.apiEndpoint}/${this.recordId}`, apiData);
                }
                NbtToast.success(config.successMessageUpdate);
            } else {
                if (useFormData) {
                    await NbtApi.postFormData(config.apiEndpoint, apiData);
                } else {
                    await NbtApi.post(config.apiEndpoint, apiData);
                }
                NbtToast.success(config.successMessageCreate);
            }

            // Basarili - listeye don
            window.location.href = `/customer/${this.musteriId}?tab=${this.tabKey}`;
        } catch (err) {
            this.showError(err.message);
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Kaydet';
            }
        }
    },

    bindEvents(config) {
        // Kaydet butonu
        document.getElementById(config.saveButtonId)?.addEventListener('click', () => this.save());

        // Tutar alani icin format
        if (config.amountField) {
            const tutarEl = document.getElementById(config.amountField);
            tutarEl?.addEventListener('blur', (e) => {
                const val = NbtUtils.parseDecimal(e.target.value);
                e.target.value = NbtUtils.formatDecimal(val);
            });
        }

        // Karakter sayaci (takvim ozeti)
        if (this.activeModule === 'calendar') {
            const ozetEl = document.getElementById('calendarOzet');
            const countEl = document.getElementById('calendarOzetCount');
            ozetEl?.addEventListener('input', () => {
                if (countEl) countEl.textContent = ozetEl.value.length;
            });
        }
    }
};

// =============================================
// SIFRE DEGISTIRME
// =============================================
const PasswordModule = {
    init() {
        document.getElementById('btnChangePassword')?.addEventListener('click', () => this.save());
    },

    async save() {
        const current = document.getElementById('currentPassword').value;
        const newPass = document.getElementById('newPassword').value;
        const confirm = document.getElementById('confirmPassword').value;

        NbtModal.clearError('passwordModal');
        if (!current) {
            NbtModal.showFieldError('passwordModal', 'currentPassword', 'Mevcut şifre zorunludur');
            NbtModal.showError('passwordModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        if (!newPass) {
            NbtModal.showFieldError('passwordModal', 'newPassword', 'Yeni şifre zorunludur');
            NbtModal.showError('passwordModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        if (newPass.length < 6) {
            NbtModal.showFieldError('passwordModal', 'newPassword', 'Yeni şifre en az 6 karakter olmalıdır');
            NbtModal.showError('passwordModal', 'Yeni şifre en az 6 karakter olmalıdır');
            return;
        }
        if (!confirm) {
            NbtModal.showFieldError('passwordModal', 'confirmPassword', 'Şifre tekrarı zorunludur');
            NbtModal.showError('passwordModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        if (newPass !== confirm) {
            NbtModal.showFieldError('passwordModal', 'confirmPassword', 'Yeni şifreler eşleşmiyor');
            NbtModal.showError('passwordModal', 'Yeni şifreler eşleşmiyor');
            return;
        }

        NbtModal.setLoading('passwordModal', true);
        try {
            await NbtApi.post('/api/users/change-password', {
                CurrentPassword: current,
                NewPassword: newPass
            });
            NbtToast.success('Şifreniz değiştirildi');
            NbtModal.close('passwordModal');
        } catch (err) {
            NbtModal.showError('passwordModal', err.message);
        } finally {
            NbtModal.setLoading('passwordModal', false);
        }
    }
};

// =============================================
// ROUTER SETUP - Server-Rendered Sayfa Mimarisi
// =============================================
// NOT: Artik SPA routing yok. Her sayfa server-side render ediliyor.
// Bu fonksiyon sadece sayfa yuklendiginde ilgili modulu init etmek icin kullanilmaktadir.

function setupRoutes() {
    const initIfPermitted = (viewId, permission, initFn) => {
        const view = document.getElementById(viewId);
        if (!view) return;

        const run = () => {
            if (!permission || NbtPermission.can(permission)) {
                view.classList.remove('d-none');
                initFn();
            } else {
                view.classList.add('d-none');
            }
        };

        NbtPermission.waitForReady().then(run);
    };

    // Dashboard modulu
    NbtRouter.register('dashboard', () => {
        initIfPermitted('view-dashboard', 'dashboard.read', () => DashboardModule.init());
    });

    // Musteri detay
    NbtRouter.register('customer', (params) => {
        initIfPermitted('view-customer-detail', 'customers.read', () => {
            // ID'yi data attribute'dan veya params'dan al
            const detailEl = document.getElementById('view-customer-detail');
            const id = parseInt(params.id || detailEl?.dataset?.customerId);
            if (id) {
                // Tab parametresini URL'den al (opsiyonel deep-link destegi)
                const tabParam = params.tab || null;
                CustomerDetailModule.init(id, tabParam);
            }
        });
    });

    // Faturalar
    NbtRouter.register('invoices', () => {
        initIfPermitted('view-invoices', 'invoices.read', () => InvoiceModule.init());
    });

    // Odemeler
    NbtRouter.register('payments', () => {
        initIfPermitted('view-payments', 'payments.read', () => PaymentModule.init());
    });

    // Projeler
    NbtRouter.register('projects', () => {
        initIfPermitted('view-projects', 'projects.read', () => ProjectModule.init());
    });

    // Teklifler
    NbtRouter.register('offers', () => {
        initIfPermitted('view-offers', 'offers.read', () => OfferModule.init());
    });

    // Sozlesmeler
    NbtRouter.register('contracts', () => {
        initIfPermitted('view-contracts', 'contracts.read', () => ContractModule.init());
    });

    // Teminatlar
    NbtRouter.register('guarantees', () => {
        initIfPermitted('view-guarantees', 'guarantees.read', () => GuaranteeModule.init());
    });

    // Kullanicilar
    NbtRouter.register('users', () => {
        initIfPermitted('view-users', 'users.read', () => UserModule.init());
    });

    // Loglar
    NbtRouter.register('logs', () => {
        initIfPermitted('view-logs', 'logs.read', () => LogModule.init());
    });

    // Hesabim
    NbtRouter.register('my-account', () => {
        const view = document.getElementById('view-my-account');
        if (view) {
            view.classList.remove('d-none');
            MyAccountModule.init();
        }
    });

    // Alarmlar
    NbtRouter.register('alarms', () => {
        initIfPermitted('view-alarms', 'alarms.read', () => AlarmsModule.init());
    });

    // Parametreler
    NbtRouter.register('parameters', () => {
        initIfPermitted('view-parameters', 'parameters.read', () => ParameterModule.init());
    });
}

// =============================================
// GLOBAL EVENT BINDINGS
// =============================================
function setupGlobalEvents() {
    // Cikis
    document.getElementById('logoutNav')?.addEventListener('click', async (e) => {
        e.preventDefault();
        try {
            await NbtApi.post('/api/logout', {});
        } catch (err) {}
        NbtUtils.clearSession();
        window.location.href = '/login';
    });

    // Sifre degistir
    document.querySelector('[data-action="change-password"]')?.addEventListener('click', () => {
        NbtModal.resetForm('passwordModal');
        NbtModal.open('passwordModal');
    });

    // Navbar linkleri artik server navigation yapiyor (href kullaniyor)
    // data-route attribute'lari artik sadece aktif durumu belirlemek icin
    // Link interception KALDIRILDI - tum href'ler dogal sayfa yuklemesi yapar

    // Permission bazli menu gorunurlugu data-can ile otomatik uygulanir

    // Modul eventlerini bind et
    CustomerModule.bindEvents();
    InvoiceModule.bindEvents();
    PaymentModule.bindEvents();
    ProjectModule.bindEvents();
    OfferModule.bindEvents();
    ContractModule.bindEvents();
    GuaranteeModule.bindEvents();
    MeetingModule.bindEvents();
    CalendarTabModule.bindEvents();
    ContactModule.bindEvents();
    StampTaxModule.bindEvents();
    FileModule.bindEvents();
    ParameterModule.bindEvents();
}

// =============================================
// INIT - Server-Rendered Sayfa Mimarisi
// =============================================
document.addEventListener('DOMContentLoaded', async () => {
    // Token kontrolu
    if (!NbtUtils.getToken()) {
        window.location.href = '/login';
        return;
    }

    // Permission bilgilerini onyukle (KRITIK: Sayfa acilmadan once permission cache doldurulmali)
    await NbtPermission.load();

    // Parametreleri onyukleme (performance icin)
    await NbtParams.preload();

    // Route'lari kaydet (modul init fonksiyonlari icin)
    setupRoutes();
    
    // Global eventleri baglama
    setupGlobalEvents();
    
    // Sifre modulunu init etme
    PasswordModule.init();
    
    // Global customer sidebar'i baslat
    await GlobalCustomerSidebar.init();

    // Sayfa bazli form modulleri
    // NOT: Her form sayfasi kendi init'ini inline script ile cagirir
    // Ornek: NbtPageForm.init('contact', musteriId, kisiId, 'kisiler');
    // NbtOfferPageForm ve NbtContractPageForm geriye donuk uyumluluk icin korundu
    NbtOfferPageForm.init();
    NbtContractPageForm.init();
    NbtCustomerPageForm.init();

    // Server-rendered mimaride: Sayfa init
    // CURRENT_PAGE degerine gore ilgili modulu baslat
    NbtRouter.init();
});
