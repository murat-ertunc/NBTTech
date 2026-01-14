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
            NbtLogger.error('Dashboard stats yüklenemedi:', err);
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
            const musteriKodu = c.MusteriKodu || `MÜŞ-${String(c.Id).padStart(5, '0')}`;
            html += `
                <a href="/customer/${c.Id}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2" 
                   data-customer-id="${c.Id}">
                    <div>
                        <div class="fw-semibold">${NbtUtils.escapeHtml(musteriKodu)} - ${NbtUtils.escapeHtml(c.Unvan)}</div>
                        ${c.Aciklama ? `<small class="text-muted">${NbtUtils.escapeHtml(c.Aciklama).substring(0, 50)}</small>` : ''}
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>`;
        });
        html += '</div>';
        
        if (customers.length > 10) {
            html += `<div class="text-center py-2">
                <a href="/customers" class="small">Tümünü Gör (${customers.length})</a>
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

    async loadCalendar() {
        const container = document.getElementById('dashCalendar');
        try {
            await NbtCalendar.loadEvents();
        } catch (err) {
            NbtCalendar.events = [];
        }
        
        NbtCalendar.render(container, {
            events: NbtCalendar.events,
            onDayClick: (date, dayEvents) => this.openCalendarDayModal(date, dayEvents)
        });
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

        document.querySelector('[data-action="add-customer"]')?.addEventListener('click', () => {
            CustomerModule.openModal();
        });

        document.getElementById('dashAlarmList')?.addEventListener('click', (e) => {
            const item = e.target.closest('.list-group-item[data-alarm-type]');
            if (item) {
                const type = item.dataset.alarmType;
                // Tüm alarm tıklamalarını /alarms sayfasına yönlendirme
                NbtRouter.navigate('/alarms');
            }
        });

        document.getElementById('dashCustomerList')?.addEventListener('click', (e) => {
            e.preventDefault();
            const link = e.target.closest('[data-customer-id]');
            if (link) {
                const customerId = parseInt(link.dataset.customerId);
                NbtRouter.navigate(`/customer/${customerId}`);
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
    columnFilters: {},
    pageSize: window.APP_CONFIG?.PAGINATION_DEFAULT || 10,
    currentPage: 1,
    paginationInfo: null,
    // Filtre için ek property'ler
    filteredData: null,
    filteredPage: 1,
    filteredPaginationInfo: null,
    // Tüm müşterileri cache'le (filtreleme için)
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
        if (!container) return; // Standalone sayfa değilse çık
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
        // Eğer hiç filtre yoksa normal listeye dön
        const hasFilters = this.searchQuery || Object.keys(this.columnFilters).length > 0;
        if (!hasFilters) {
            this.filteredData = null;
            this.filteredPaginationInfo = null;
            this.loadList(1);
            return;
        }
        
        // Tüm müşterileri yükle (filtreleme için)
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
        
        // Eğer hala yükleniyorsa bekle
        if (this.allCustomersLoading) {
            setTimeout(() => this.applyFilters(page), 100);
            return;
        }
        
        let filtered = this.allCustomers || [];
        
        // Türkçe karakter normalizasyonu için yardımcı fonksiyon
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
        
        // Tarih karşılaştırma için yardımcı fonksiyon (YYYY-MM-DD formatında)
        const formatDateForCompare = (dateStr) => {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return '';
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };
        
        // Global arama işlemi
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
                    
                    // Tarih alanı için özel karşılaştırma
                    if (field === 'EklemeZamani') {
                        const cellDate = formatDateForCompare(cellValue);
                        return cellDate === value; // value zaten YYYY-MM-DD formatında
                    }
                    
                    // Diğer alanlar için normalize edilmiş karşılaştırma
                    return normalize(cellValue).includes(normalize(value));
                });
            }
        });
        
        // Filtrelenmiş verileri sakla
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
        if (!container) return; // Standalone sayfa değilse çık
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

        // Filter row - her kolon için arama input'u
        const filterRow = columns.map(c => {
            const currentValue = this.columnFilters[c.field] || '';
            if (c.field === 'EklemeZamani') {
                // Tarih alanı için date input
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
                                <button class="btn btn-outline-primary btn-sm" type="button" data-action="view" data-id="${row.Id}" title="Detay">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-sm" type="button" data-action="delete" data-id="${row.Id}" title="Sil">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>`;
            }).join('');
        }

        container.innerHTML = `
            <div class="table-responsive px-2 py-2">
                <table class="table table-bordered table-hover table-sm mb-0" id="customersTable">
                    <thead>
                        <tr>${headers}</tr>
                        <tr class="bg-white">${filterRow}</tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            </div>`;

        // Pagination ekleme - filtrelenmiş veriler için de göster
        if (isFiltered && this.filteredPaginationInfo) {
            const { page, totalPages, total, limit } = this.filteredPaginationInfo;
            if (totalPages > 1) {
                container.innerHTML += this.renderFilteredPagination();
            } else if (total > 0) {
                // Tek sayfa ise sadece bilgi göster
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

        // Enter tuşu ile arama
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
                // Filtreler temizlenince normal listeye dön
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
            Adres: document.getElementById('customerAdres').value.trim() || null
        };

        NbtModal.clearError('customerModal');
        if (!data.Unvan) {
            NbtModal.showFieldError('customerModal', 'customerUnvan', 'Unvan zorunludur');
            NbtModal.showError('customerModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        if (data.Unvan.length < 2) {
            NbtModal.showFieldError('customerModal', 'customerUnvan', 'Unvan en az 2 karakter olmalıdır');
            NbtModal.showError('customerModal', 'Unvan en az 2 karakter olmalıdır');
            return;
        }

        NbtModal.setLoading('customerModal', true);
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
            // Dashboard sayfasındaysa müşteri listesini güncelleme
            const dashboardView = document.getElementById('view-dashboard');
            if (dashboardView && !dashboardView.classList.contains('d-none')) {
                DashboardModule.loadCustomers();
            }
        } catch (err) {
            NbtModal.showError('customerModal', err.message);
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
// MÜŞTERİ DETAY MODÜLÜ (12 TAB)
// =============================================
const CustomerDetailModule = {
    _eventsBound: false,
    customerId: null,
    activeTab: 'bilgi',
    filters: {},
    sidebarCustomers: [],
    pageSize: window.APP_CONFIG?.PAGINATION_DEFAULT || 10,
    // Kolon filtreleri için - her tablo için ayrı
    columnFilters: {},
    // Filtreleme için tüm veriler cache
    allData: {},
    allDataLoading: {},
    filteredPaginationInfo: {},

    /**
     * Ortak proje select doldurma fonksiyonu
     * Tüm modüller bu fonksiyonu kullanarak proje select'lerini doldurur
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
            const projects = response.data || [];
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
    currentPages: {}, // Her tablo için current page: { projects: 1, invoices: 1, ... }
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

    async init(customerId, initialTab = null) {
        this.pageSize = NbtParams.getPaginationDefault();
        this.customerId = parseInt(customerId, 10);
        if (isNaN(this.customerId) || this.customerId <= 0) {
            NbtToast.error('Geçersiz müşteri ID');
            NbtRouter.navigate('/customers');
            return;
        }
        
        // Durum parametrelerini önceden yükle (badge'ler için)
        await Promise.all([
            NbtParams.getStatuses('proje'),
            NbtParams.getStatuses('teklif'),
            NbtParams.getStatuses('sozlesme'),
            NbtParams.getStatuses('teminat'),
            NbtParams.getCurrencies()
        ]);
        
        await this.loadCustomer();
        await this.loadSidebarCustomers();
        this.bindEvents();
        const tabToOpen = initialTab && this.tabConfig[initialTab] ? initialTab : 'bilgi';
        this.switchTab(tabToOpen);
    },

    async loadSidebarCustomers() {
        const container = document.getElementById('sidebarCustomerList');
        if (!container) return;
        
        try {
            let customers = AppState.customers;
            if (!customers || customers.length === 0) {
                const response = await NbtApi.get('/api/customers');
                customers = response.data || [];
                AppState.customers = customers;
            }
            
            this.sidebarCustomers = customers;
            this.renderSidebarCustomers();
        } catch (err) {
            container.innerHTML = `<div class="text-danger text-center py-2">Hata: ${err.message}</div>`;
        }
    },

    renderSidebarCustomers(filter = '') {
        const container = document.getElementById('sidebarCustomerList');
        if (!container) return;
        
        let filtered = this.sidebarCustomers;
        if (filter) {
            const lowerFilter = filter.toLowerCase();
            filtered = this.sidebarCustomers.filter(c => 
                (c.Unvan || '').toLowerCase().includes(lowerFilter) ||
                (c.MusteriKodu || '').toLowerCase().includes(lowerFilter) ||
                (c.VergiNo || '').toLowerCase().includes(lowerFilter)
            );
        }
        
        if (filtered.length === 0) {
            container.innerHTML = `<div class="text-muted text-center py-3"><i class="bi bi-inbox me-1"></i>Müşteri bulunamadı</div>`;
            return;
        }
        
        container.innerHTML = filtered.map(c => {
            const isActive = parseInt(c.Id, 10) === this.customerId;
            const musteriKodu = c.MusteriKodu || `MÜŞ-${String(c.Id).padStart(5, '0')}`;
            return `
            <a href="/customer/${c.Id}" 
               class="list-group-item list-group-item-action py-2 px-3 ${isActive ? 'active' : ''}"
               style="cursor:pointer;">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-truncate" style="max-width: calc(100% - 20px);">
                        <div class="fw-semibold text-truncate">${NbtUtils.escapeHtml(musteriKodu)} - ${NbtUtils.escapeHtml(c.Unvan || '—')}</div>
                        ${c.Aciklama ? `<small class="${isActive ? 'text-white-50' : 'text-muted'} d-block text-truncate">${NbtUtils.escapeHtml(c.Aciklama).substring(0, 40)}</small>` : ''}
                    </div>
                    <i class="bi bi-chevron-right flex-shrink-0"></i>
                </div>
            </a>`;
        }).join('');
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
                NbtRouter.navigate('/customers');
                return;
            }

            document.getElementById('customerDetailTitle').textContent = this.data.customer.Unvan;
            const musteriKodu = this.data.customer.MusteriKodu || `MÜŞ-${String(this.customerId).padStart(5, '0')}`;
            document.getElementById('customerDetailCode').textContent = musteriKodu;
            AppState.currentCustomer = this.data.customer;

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
                this.loadRelatedData('files', '/api/files'),
                this.loadRelatedData('calendars', '/api/takvim')
            ]);
        } catch (err) {
            NbtToast.error(err.message);
        }
    },

    async loadRelatedData(key, endpoint, page = 1) {
        try {
            const limit = this.pageSize;
            const response = await NbtApi.get(`${endpoint}?musteri_id=${this.customerId}&page=${page}&limit=${limit}`);
            
            if (response.pagination) {
                this.data[key] = response.data || [];
                this.paginationInfo[key] = response.pagination;
            } else {
                this.data[key] = response.data || [];
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

        document.getElementById('btnEditCustomer')?.addEventListener('click', () => {
            CustomerModule.openModal(this.customerId);
        });

        const sidebarSearch = document.getElementById('sidebarCustomerSearch');
        if (sidebarSearch) {
            sidebarSearch.addEventListener('input', (e) => {
                this.renderSidebarCustomers(e.target.value);
            });
        }
        
        document.querySelector('#view-customer-detail [data-action="add-customer"]')?.addEventListener('click', () => {
            CustomerModule.openModal(null);
        });
    },

    switchTab(tab) {
        this.activeTab = tab;
        
        document.querySelectorAll('#customerTabs .nav-link').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tab);
        });

        // İçerik render işlemi
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

        const tableHtml = this.renderDataTable(id, columns, data, emptyMsg);

        return `
            <div class="card shadow-sm" id="panel_${id}">
                <div class="card-header py-2 d-flex justify-content-between align-items-center bg-white">
                    <span class="fw-semibold"><i class="bi ${icon} me-2"></i>${title}</span>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-success btn-sm" data-add="${addType}">
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

    renderDataTable(id, columns, data, emptyMsg, isFiltered = false) {
        const headers = columns.map(c => `<th class="bg-light px-3">${c.label}</th>`).join('') + '<th class="bg-light text-center px-3" style="width:110px;">İşlem</th>';

        // Filter row - her kolon için arama input'u
        const tableFilters = this.columnFilters[id] || {};
        const filterRow = columns.map(c => {
            const currentValue = tableFilters[c.field] || '';
            // Tarih alanları için date input
            const isDateField = c.field.toLowerCase().includes('tarih') || c.field === 'BaslangicTarihi' || c.field === 'BitisTarihi' || c.field === 'TeklifTarihi' || c.field === 'VadeTarihi';
            if (isDateField) {
                return `<th class="p-1"><input type="date" class="form-control form-control-sm" data-column-filter="${c.field}" data-table-id="${id}" value="${NbtUtils.escapeHtml(currentValue)}"></th>`;
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
                <div class="table-responsive px-2 py-2">
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
        
        // Filtrelenmiş veriler için ayrı pagination
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
            
            return `
                <tr data-id="${row.Id}">
                    ${cells}
                    <td class="text-center px-3">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary btn-sm" type="button" data-action="view" data-id="${row.Id}" title="Detay">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" type="button" data-action="edit" data-id="${row.Id}" title="Düzenle">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-sm" type="button" data-action="delete" data-id="${row.Id}" title="Sil">
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
            <div class="table-responsive px-2 py-2">
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

    // Filtrelenmiş veriler için pagination
    renderFilteredPaginationDetail(tableId, currentPage, totalPages, totalItems, startIndex, endIndex) {
        if (totalItems <= this.pageSize) {
            // Tek sayfa - sadece bilgi göster
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
            return ''; // Tek sayfa varsa pagination gösterilmez
        }
        
        let pageButtons = '';
        
        // İlk ve önceki butonlar
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
            'takvim': 'meetings'
        };
        return mapping[tableId] || null;
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
                    <h6 class="text-muted border-bottom pb-2 mb-3"><i class="bi bi-building me-1"></i>Temel Bilgiler</h6>
                    
                    <div class="row mb-2">
                        <div class="col-12 col-md-4 fw-bold text-muted">ID</div>
                        <div class="col-12 col-md-8">${c.Id || '-'}</div>
                    </div>
                    
                    <div class="row mb-2">
                        <div class="col-12 col-md-4 fw-bold text-muted">Müşteri Kodu</div>
                        <div class="col-12 col-md-8">${NbtUtils.escapeHtml(c.MusteriKodu || '-')}</div>
                    </div>
                    
                    <div class="row mb-2">
                        <div class="col-12 col-md-4 fw-bold text-muted">Unvan</div>
                        <div class="col-12 col-md-8">${NbtUtils.escapeHtml(c.Unvan || '-')}</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-12 col-md-4 fw-bold text-muted">Kayıt Tarihi</div>
                        <div class="col-12 col-md-8">${NbtUtils.formatDate(c.EklemeZamani) || '-'}</div>
                    </div>
                    
                    <h6 class="text-muted border-bottom pb-2 mb-3 mt-4"><i class="bi bi-percent me-1"></i>Vergi Bilgileri</h6>
                    
                    <div class="row mb-2">
                        <div class="col-12 col-md-4 fw-bold text-muted">Vergi Dairesi</div>
                        <div class="col-12 col-md-8">${NbtUtils.escapeHtml(c.VergiDairesi || '-')}</div>
                    </div>
                    
                    <div class="row mb-2">
                        <div class="col-12 col-md-4 fw-bold text-muted">Vergi Numarası</div>
                        <div class="col-12 col-md-8">${NbtUtils.escapeHtml(c.VergiNo || '-')}</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-12 col-md-4 fw-bold text-muted">Mersis No</div>
                        <div class="col-12 col-md-8">${NbtUtils.escapeHtml(c.MersisNo || '-')}</div>
                    </div>
                    
                    <h6 class="text-muted border-bottom pb-2 mb-3 mt-4"><i class="bi bi-telephone me-1"></i>İletişim Bilgileri</h6>
                    
                    <div class="row mb-2">
                        <div class="col-12 col-md-4 fw-bold text-muted">Telefon</div>
                        <div class="col-12 col-md-8">${NbtUtils.escapeHtml(c.Telefon || '-')}</div>
                    </div>
                    
                    <div class="row mb-2">
                        <div class="col-12 col-md-4 fw-bold text-muted">Faks</div>
                        <div class="col-12 col-md-8">${NbtUtils.escapeHtml(c.Faks || '-')}</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-12 col-md-4 fw-bold text-muted">Web Sitesi</div>
                        <div class="col-12 col-md-8">${c.Web ? `<a href="${NbtUtils.escapeHtml(c.Web)}" target="_blank">${NbtUtils.escapeHtml(c.Web)}</a>` : '-'}</div>
                    </div>
                    
                    <h6 class="text-muted border-bottom pb-2 mb-3 mt-4"><i class="bi bi-geo-alt me-1"></i>Adres ve Açıklama</h6>
                    
                    <div class="row mb-2">
                        <div class="col-12 col-md-4 fw-bold text-muted">Adres</div>
                        <div class="col-12 col-md-8">${NbtUtils.escapeHtml(c.Adres || '-')}</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-12 col-md-4 fw-bold text-muted">Açıklama</div>
                        <div class="col-12 col-md-8">${NbtUtils.escapeHtml(c.Aciklama || '-')}</div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4 g-2">
                <div class="col-6 col-md-2">
                    <div class="card text-center p-3"><div class="fs-4 fw-bold text-primary">${this.data.projects.length}</div><small class="text-muted">Proje</small></div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="card text-center p-3"><div class="fs-4 fw-bold text-success">${this.data.offers.length}</div><small class="text-muted">Teklif</small></div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="card text-center p-3"><div class="fs-4 fw-bold text-info">${this.data.contracts.length}</div><small class="text-muted">Sözleşme</small></div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="card text-center p-3"><div class="fs-4 fw-bold text-warning">${this.data.guarantees.length}</div><small class="text-muted">Teminat</small></div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="card text-center p-3"><div class="fs-4 fw-bold text-secondary">${this.data.invoices.length}</div><small class="text-muted">Fatura</small></div>
                </div>
                <div class="col-6 col-md-2">
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
        const calendars = this.data.calendars || [];
        
        return this.renderPanel({
            id: 'takvim',
            title: 'Takvim',
            icon: 'bi-calendar3',
            addType: 'calendar',
            emptyMsg: 'Henüz takvim kaydı yok',
            columns: [
                { field: 'ProjeAdi', label: 'Proje' },
                { field: 'BaslangicTarihi', label: 'Başlangıç', render: v => NbtUtils.formatDate(v) },
                { field: 'BitisTarihi', label: 'Bitiş', render: v => NbtUtils.formatDate(v) },
                { field: 'Ozet', label: 'Özet' }
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
        // Entity ismini API formatına dönüştür
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
        
        // Fallback - eski sabit değerler (cache henüz yüklenmediyse)
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
                    NbtRouter.navigate('/customers');
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

    applyFilter(panelId) {
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
        
        // Input değerlerini al
        const filters = {};
        table.querySelectorAll('[data-column-filter]').forEach(input => {
            const field = input.dataset.columnFilter;
            const value = input.value.trim();
            if (value) filters[field] = value;
        });
        
        // Filtre varsa kaydet
        this.columnFilters[tableId] = filters;
        
        // Eğer hiç filtre yoksa normal listeye dön
        if (Object.keys(filters).length === 0) {
            this.allData[tableId] = null;
            this.filteredPaginationInfo[tableId] = null;
            const dataKey = this.getDataKeyFromTableId(tableId);
            this.switchTab(this.activeTab);
            return;
        }
        
        const dataKey = this.getDataKeyFromTableId(tableId);
        const panelBody = document.getElementById(`body_${tableId}`);
        
        // Tüm verileri yükle (filtreleme için)
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
                'files': '/api/files'
            };
            
            const endpoint = endpointMap[dataKey];
            if (endpoint) {
                try {
                    const response = await NbtApi.get(`${endpoint}?musteri_id=${this.customerId}&page=1&limit=10000`);
                    this.allData[tableId] = response.data || [];
                } catch (err) {
                    console.error('Tüm veriler yüklenemedi:', err);
                    this.allData[tableId] = this.data[dataKey] || [];
                }
            } else {
                this.allData[tableId] = this.data[dataKey] || [];
            }
            this.allDataLoading[tableId] = false;
        }
        
        // Hala yükleniyorsa bekle
        if (this.allDataLoading[tableId]) {
            setTimeout(() => this.applyColumnFilters(tableId, page), 100);
            return;
        }
        
        let filtered = this.allData[tableId] || [];
        
        // Kolon filtreleri uygula
        Object.keys(filters).forEach(field => {
            const value = filters[field];
            if (value) {
                filtered = filtered.filter(item => {
                    let cellValue = item[field];
                    
                    // Tarih alanı için özel karşılaştırma
                    const isDateField = field.toLowerCase().includes('tarih') || field === 'BaslangicTarihi' || field === 'BitisTarihi' || field === 'TeklifTarihi' || field === 'VadeTarihi';
                    if (isDateField) {
                        const cellDate = NbtUtils.formatDateForCompare(cellValue);
                        return cellDate === value;
                    }
                    
                    // Diğer alanlar için normalize edilmiş karşılaştırma
                    return NbtUtils.normalizeText(cellValue).includes(NbtUtils.normalizeText(value));
                });
            }
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
        
        // Input'ları temizle
        table.querySelectorAll('[data-column-filter]').forEach(input => {
            input.value = '';
        });
        
        // Filtre state'lerini temizle
        this.columnFilters[tableId] = {};
        this.allData[tableId] = null;
        this.filteredPaginationInfo[tableId] = null;
        
        // Normal listeyi yeniden yükle
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
                    { field: 'Tarih', label: 'Tarih', render: v => NbtUtils.formatDate(v) },
                    { field: 'Konu', label: 'Konu' },
                    { field: 'Notlar', label: 'Notlar' },
                    { field: 'Kisi', label: 'Görüşülen Kişi' }
                ],
                emptyMsg: 'Kayıt bulunamadı'
            },
            projeler: {
                columns: [
                    { field: 'ProjeAdi', label: 'Proje Adı' },
                    { field: 'BaslangicTarihi', label: 'Başlangıç', render: v => NbtUtils.formatDate(v) },
                    { field: 'BitisTarihi', label: 'Bitiş', render: v => NbtUtils.formatDate(v) },
                    { field: 'Durum', label: 'Durum', render: v => this.getStatusBadge(v, 'project') }
                ],
                emptyMsg: 'Kayıt bulunamadı'
            },
            teklifler: {
                columns: [
                    { field: 'TeklifNo', label: 'Teklif No' },
                    { field: 'Tarih', label: 'Tarih', render: v => NbtUtils.formatDate(v) },
                    { field: 'Konu', label: 'Konu' },
                    { field: 'Tutar', label: 'Tutar', render: v => NbtUtils.formatMoney(v) },
                    { field: 'Durum', label: 'Durum', render: v => this.getStatusBadge(v, 'offer') }
                ],
                emptyMsg: 'Kayıt bulunamadı'
            },
            sozlesmeler: {
                columns: [
                    { field: 'SozlesmeNo', label: 'Sözleşme No' },
                    { field: 'Tarih', label: 'Tarih', render: v => NbtUtils.formatDate(v) },
                    { field: 'Tutar', label: 'Tutar', render: v => NbtUtils.formatMoney(v) },
                    { field: 'Durum', label: 'Durum', render: v => this.getStatusBadge(v, 'contract') }
                ],
                emptyMsg: 'Kayıt bulunamadı'
            },
            teminatlar: {
                columns: [
                    { field: 'BelgeNo', label: 'Belge No' },
                    { field: 'Tur', label: 'Tür' },
                    { field: 'BankaAdi', label: 'Banka' },
                    { field: 'Tutar', label: 'Tutar', render: (v, row) => NbtUtils.formatMoney(v, row.ParaBirimi) },
                    { field: 'BitisTarihi', label: 'Bitiş', render: v => NbtUtils.formatDate(v) },
                    { field: 'Durum', label: 'Durum', render: v => this.getStatusBadge(v, 'guarantee') }
                ],
                emptyMsg: 'Kayıt bulunamadı'
            },
            faturalar: {
                columns: [
                    { field: 'Tarih', label: 'Tarih', render: v => NbtUtils.formatDate(v) },
                    { field: 'Tutar', label: 'Tutar', render: (v, row) => NbtUtils.formatMoney(v, row.DovizCinsi) },
                    { field: 'Kalan', label: 'Kalan', render: (v, row) => {
                        const kalan = parseFloat(v) || 0;
                        const cls = kalan > 0 ? 'text-danger fw-bold' : 'text-success';
                        return `<span class="${cls}">${NbtUtils.formatMoney(kalan, row.DovizCinsi)}</span>`;
                    }},
                    { field: 'Aciklama', label: 'Açıklama' }
                ],
                emptyMsg: 'Kayıt bulunamadı'
            },
            odemeler: {
                columns: [
                    { field: 'Tarih', label: 'Tarih', render: v => NbtUtils.formatDate(v) },
                    { field: 'Tutar', label: 'Tutar', render: (v, row) => NbtUtils.formatMoney(v, row.DovizCinsi) },
                    { field: 'OdemeTuru', label: 'Ödeme Türü' },
                    { field: 'Aciklama', label: 'Açıklama' }
                ],
                emptyMsg: 'Kayıt bulunamadı'
            },
            damgavergisi: {
                columns: [
                    { field: 'Tarih', label: 'Tarih', render: v => NbtUtils.formatDate(v) },
                    { field: 'Tutar', label: 'Tutar', render: (v, row) => NbtUtils.formatMoney(v, row.DovizCinsi) },
                    { field: 'Aciklama', label: 'Açıklama' }
                ],
                emptyMsg: 'Kayıt bulunamadı'
            },
            dosyalar: {
                columns: [
                    { field: 'DosyaAdi', label: 'Dosya Adı' },
                    { field: 'DosyaTipi', label: 'Tür' },
                    { field: 'Boyut', label: 'Boyut', render: v => NbtUtils.formatFileSize(v) },
                    { field: 'EklemeZamani', label: 'Yüklenme', render: v => NbtUtils.formatDate(v) }
                ],
                emptyMsg: 'Kayıt bulunamadı'
            },
            takvim: {
                columns: [
                    { field: 'Tarih', label: 'Tarih', render: v => NbtUtils.formatDate(v) },
                    { field: 'Tarih', label: 'Saat', render: v => v ? new Date(v).toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' }) : '-' },
                    { field: 'Konu', label: 'Konu' },
                    { field: 'Kisi', label: 'Görüşülen Kişi' },
                    { field: 'Notlar', label: 'Notlar' }
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
            takvim: { type: 'meeting', detailType: 'meeting', endpoint: '/api/meetings', key: 'meetings' },
            kisiler: { type: 'contact', detailType: 'contact', endpoint: '/api/contacts', key: 'contacts' },
            damgavergisi: { type: 'stamptax', detailType: 'stampTax', endpoint: '/api/stamp-taxes', key: 'stampTaxes' },
            dosyalar: { type: 'file', detailType: 'file', endpoint: '/api/files', key: 'files' }
        };

        const config = typeMap[tab];
        if (!config) return;

        if (action === 'view') {
            const parsedId = parseInt(id, 10);
            
            // Fatura için özel olarak API'den kalemlerle birlikte çek
            if (config.type === 'invoice') {
                try {
                    const invoice = await NbtApi.get(`/api/invoices/${parsedId}`);
                    if (invoice) {
                        NbtDetailModal.show(
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
                NbtDetailModal.show(
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
        // Modül bazlı openModal fonksiyonlarının kullanımı - bu sayede proje select'leri de doldurulur
        const moduleMap = {
            project: ProjectModule,
            invoice: InvoiceModule,
            payment: PaymentModule,
            offer: OfferModule,
            contract: ContractModule,
            guarantee: GuaranteeModule,
            meeting: MeetingModule,
            contact: ContactModule,
            stamptax: StampTaxModule,
            file: FileModule,
            calendar: CalendarTabModule
        };

        const module = moduleMap[type];
        if (module?.openModal) {
            // Yeni kayıt için null id ile çağır - async fonksiyonları await ile çağır
            await module.openModal(null);
        } else {
            NbtToast.warning(`${type} için modal henüz tanımlı değil`);
        }
    },

    openEditModal(type, id) {
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
            file: 'files',
            calendar: 'calendars'
        };

        const dataKey = dataMap[type];
        if (!dataKey) {
            NbtToast.warning(`${type} için düzenleme henüz desteklenmiyor`);
            return;
        }

        const moduleMap = {
            project: ProjectModule,
            invoice: InvoiceModule,
            payment: PaymentModule,
            offer: OfferModule,
            contract: ContractModule,
            guarantee: GuaranteeModule,
            meeting: MeetingModule,
            contact: ContactModule,
            stamptax: StampTaxModule,
            file: FileModule,
            calendar: CalendarTabModule
        };

        const module = moduleMap[type];
        if (module?.openModal) {
            module.openModal(id);
        } else {
            NbtToast.warning(`${type} için düzenleme modal'ı bulunamadı`);
        }
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
            teklifler: () => `<div><strong>Teklif No:</strong> ${data.TeklifNo || '-'}</div>
                             <div><strong>Tutar:</strong> ${formatMoney(data.Tutar, data.DovizCinsi)}</div>`,
            sozlesmeler: () => `<div><strong>Sözleşme No:</strong> ${data.SozlesmeNo || '-'}</div>
                               <div><strong>Tutar:</strong> ${formatMoney(data.Tutar, data.DovizCinsi)}</div>`,
            teminatlar: () => `<div><strong>Belge No:</strong> ${data.BelgeNo || '-'}</div>
                              <div><strong>Tutar:</strong> ${formatMoney(data.Tutar, data.DovizCinsi)}</div>`,
            damgavergisi: () => `<div><strong>Tarih:</strong> ${formatDate(data.Tarih)}</div>
                                <div><strong>Tutar:</strong> ${formatMoney(data.Tutar, data.DovizCinsi)}</div>`,
            dosyalar: () => `<div><strong>Dosya:</strong> ${data.DosyaAdi || '-'}</div>`
        };
        
        return configs[type] ? configs[type]() : '';
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
    data: [],
    pageSize: window.APP_CONFIG?.PAGINATION_DEFAULT || 10,
    currentPage: 1,
    paginationInfo: null,
    searchQuery: '',
    columnFilters: {},
    // Filtre için ek property'ler
    allData: null,
    allDataLoading: false,
    filteredPage: 1,
    filteredPaginationInfo: null,
    
    async init() {
        this.pageSize = NbtParams.getPaginationDefault();
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
        
        // Tüm verileri yükle
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
        
        // Kolon filtreleri
        Object.keys(this.columnFilters).forEach(field => {
            const value = this.columnFilters[field];
            if (value) {
                filtered = filtered.filter(item => {
                    let cellValue = item[field];
                    const isDateField = field === 'Tarih';
                    if (isDateField) {
                        return NbtUtils.formatDateForCompare(cellValue) === value;
                    }
                    return NbtUtils.normalizeText(cellValue).includes(NbtUtils.normalizeText(value));
                });
            }
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
            { field: 'Kalan', label: 'Kalan', render: (v, row) => {
                const kalan = parseFloat(v) || 0;
                const cls = kalan > 0 ? 'text-danger fw-bold' : 'text-success';
                return `<span class="${cls}">${NbtUtils.formatMoney(kalan, row.DovizCinsi)}</span>`;
            }},
            { field: 'Aciklama', label: 'Açıklama' }
        ];

        // Header row
        const headers = columns.map(c => `<th class="bg-light px-3">${c.label}</th>`).join('') + 
            '<th class="bg-light text-center px-3" style="width:140px;">İşlem</th>';

        // Filter row
        const filterRow = columns.map(c => {
            const currentValue = this.columnFilters[c.field] || '';
            if (c.isDate) {
                return `<th class="p-1"><input type="date" class="form-control form-control-sm" data-column-filter="${c.field}" data-table-id="invoices" value="${NbtUtils.escapeHtml(currentValue)}"></th>`;
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
                                <button class="btn btn-outline-primary btn-sm" type="button" data-action="view" data-id="${row.Id}" title="Detay">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a class="btn btn-outline-info btn-sm" href="/customer/${row.MusteriId}?tab=faturalar" title="Müşteriye Git">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </div>
                        </td>
                    </tr>`;
            }).join('');
        }

        container.innerHTML = `
            <div class="table-responsive px-2 py-2">
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
    },

    bindTableEvents(container) {
        // View buttons
        container.querySelectorAll('[data-action="view"]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = parseInt(btn.dataset.id);
                try {
                    // API'den fatura detayını kalemlerle birlikte al
                    const invoice = await NbtApi.get(`/api/invoices/${id}`);
                    if (invoice) {
                        NbtDetailModal.show('invoice', invoice, null, null);
                    } else {
                        NbtToast.error('Fatura kaydı bulunamadı');
                    }
                } catch (err) {
                    // Fallback: local data
                    const invoice = (this.allData || this.data).find(i => parseInt(i.Id, 10) === id);
                    if (invoice) {
                        NbtDetailModal.show('invoice', invoice, null, null);
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

        const select = document.getElementById('invoiceMusteriId');
        select.innerHTML = '<option value="">Seçiniz...</option>';
        select.disabled = false;
        AppState.customers.forEach(c => {
            select.innerHTML += `<option value="${c.Id}">${NbtUtils.escapeHtml(c.Unvan)}</option>`;
        });

        const projeSelect = document.getElementById('invoiceProjeId');
        projeSelect.innerHTML = '<option value="">Proje Seçiniz...</option>';
        
        // Döviz seçeneklerini dinamik yükle
        const dovizSelect = document.getElementById('invoiceDoviz');
        if (dovizSelect) {
            await NbtParams.populateCurrencySelect(dovizSelect);
        }

        // Yeni alanları sıfırla
        const faturaNoEl = document.getElementById('invoiceFaturaNo');
        const supheliEl = document.getElementById('invoiceSupheliAlacak');
        const tevkifatAktifEl = document.getElementById('invoiceTevkifatAktif');
        const tevkifatOran1El = document.getElementById('invoiceTevkifatOran1');
        const tevkifatOran2El = document.getElementById('invoiceTevkifatOran2');
        const takvimAktifEl = document.getElementById('invoiceTakvimAktif');
        const takvimSureEl = document.getElementById('invoiceTakvimSure');
        const takvimSureTipiEl = document.getElementById('invoiceTakvimSureTipi');
        const tevkifatAlani = document.getElementById('tevkifatAlani');
        const takvimAlani = document.getElementById('takvimAlani');

        if (faturaNoEl) faturaNoEl.value = '';
        if (supheliEl) supheliEl.checked = false;
        if (tevkifatAktifEl) tevkifatAktifEl.checked = false;
        if (tevkifatOran1El) tevkifatOran1El.value = '';
        if (tevkifatOran2El) tevkifatOran2El.value = '';
        if (takvimAktifEl) takvimAktifEl.checked = false;
        if (takvimSureEl) takvimSureEl.value = '';
        if (takvimSureTipiEl) takvimSureTipiEl.value = 'gun';
        if (tevkifatAlani) tevkifatAlani.style.display = 'none';
        if (takvimAlani) takvimAlani.style.display = 'none';

        // Fatura kalemlerini sıfırla
        this.resetInvoiceItems();

        // Müşteri değiştiğinde projeleri yükleme
        select.onchange = async () => {
            projeSelect.innerHTML = '<option value="">Proje Seçiniz...</option>';
            const musteriId = select.value;
            if (musteriId) {
                try {
                    const response = await NbtApi.get(`/api/projects?musteri_id=${musteriId}`);
                    const projects = response.data || [];
                    projects.forEach(p => {
                        projeSelect.innerHTML += `<option value="${p.Id}">${NbtUtils.escapeHtml(p.ProjeAdi || '')}</option>`;
                    });
                } catch (err) {
                    NbtLogger.error('Projeler yüklenemedi:', err);
                }
            }
        };

        // Eğer customer detail sayfasındaysak müşteriyi auto-select et ve disable yap
        if (CustomerDetailModule.customerId) {
            select.value = CustomerDetailModule.customerId;
            select.disabled = true;
            await select.onchange();
        }

        if (id) {
            const parsedId = parseInt(id, 10);
            let invoice = this.data?.find(i => parseInt(i.Id, 10) === parsedId);
            if (!invoice) {
                invoice = CustomerDetailModule.data?.invoices?.find(i => parseInt(i.Id, 10) === parsedId);
            }
            if (invoice) {
                select.value = invoice.MusteriId;
                select.disabled = true;
                // Projeleri yükleme ve seçili projeyi ayarlama
                await select.onchange();
                document.getElementById('invoiceProjeId').value = invoice.ProjeId || '';
                document.getElementById('invoiceTarih').value = invoice.Tarih?.split('T')[0] || '';
                document.getElementById('invoiceTutar').value = NbtUtils.formatDecimal(invoice.Tutar) || '';
                document.getElementById('invoiceDoviz').value = invoice.DovizCinsi || 'TRY';
                document.getElementById('invoiceAciklama').value = invoice.Aciklama || '';
                
                // Yeni alanları doldur
                if (faturaNoEl && invoice.FaturaNo) faturaNoEl.value = invoice.FaturaNo;
                if (supheliEl) supheliEl.checked = !!invoice.SupheliAlacak;
                if (invoice.TevkifatAktif) {
                    if (tevkifatAktifEl) tevkifatAktifEl.checked = true;
                    if (tevkifatAlani) tevkifatAlani.style.display = 'block';
                    if (tevkifatOran1El) tevkifatOran1El.value = NbtUtils.formatDecimal(invoice.TevkifatOran1) || '';
                    if (tevkifatOran2El) tevkifatOran2El.value = NbtUtils.formatDecimal(invoice.TevkifatOran2) || '';
                }
                if (invoice.TakvimAktif) {
                    if (takvimAktifEl) takvimAktifEl.checked = true;
                    if (takvimAlani) takvimAlani.style.display = 'block';
                    if (takvimSureEl) takvimSureEl.value = invoice.TakvimSure || '';
                    if (takvimSureTipiEl) takvimSureTipiEl.value = invoice.TakvimSureTipi || 'gun';
                }

                // Fatura kalemlerini API'den yükle (her zaman güncel veri için)
                try {
                    const detailResponse = await NbtApi.get(`/api/invoices/${parsedId}`);
                    if (detailResponse && detailResponse.Kalemler && Array.isArray(detailResponse.Kalemler)) {
                        this.loadInvoiceItems(detailResponse.Kalemler);
                    }
                } catch (err) {
                    // API hatası durumunda local data'dan yükle
                    if (invoice.Kalemler && Array.isArray(invoice.Kalemler)) {
                        this.loadInvoiceItems(invoice.Kalemler);
                    }
                }
            } else {
                NbtToast.error('Fatura kaydı bulunamadı');
                return;
            }
        }

        NbtModal.open('invoiceModal');
    },

    async save() {
        const id = document.getElementById('invoiceId').value;
        
        let musteriId = parseInt(document.getElementById('invoiceMusteriId').value);
        if (!musteriId || isNaN(musteriId)) {
            musteriId = CustomerDetailModule.customerId;
        }
        
        const projeIdVal = document.getElementById('invoiceProjeId').value;
        const data = {
            MusteriId: musteriId,
            ProjeId: projeIdVal ? parseInt(projeIdVal) : null,
            Tarih: document.getElementById('invoiceTarih').value,
            Tutar: parseFloat(document.getElementById('invoiceTutar').value) || 0,
            DovizCinsi: document.getElementById('invoiceDoviz').value,
            Aciklama: document.getElementById('invoiceAciklama').value.trim() || null,
            // Yeni alanlar
            FaturaNo: document.getElementById('invoiceFaturaNo')?.value.trim() || null,
            SupheliAlacak: document.getElementById('invoiceSupheliAlacak')?.checked ? 1 : 0,
            TevkifatAktif: document.getElementById('invoiceTevkifatAktif')?.checked ? 1 : 0,
            TevkifatOran1: parseFloat(document.getElementById('invoiceTevkifatOran1')?.value) || null,
            TevkifatOran2: parseFloat(document.getElementById('invoiceTevkifatOran2')?.value) || null,
            TakvimAktif: document.getElementById('invoiceTakvimAktif')?.checked ? 1 : 0,
            TakvimSure: parseInt(document.getElementById('invoiceTakvimSure')?.value) || null,
            TakvimSureTipi: document.getElementById('invoiceTakvimSureTipi')?.value || null,
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
        if (!data.Tutar || data.Tutar <= 0) {
            NbtModal.showFieldError('invoiceModal', 'invoiceTutar', 'Tutar zorunludur');
            NbtModal.showError('invoiceModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        // Takvim aktifse süre zorunlu
        if (data.TakvimAktif && (!data.TakvimSure || data.TakvimSure <= 0)) {
            NbtModal.showFieldError('invoiceModal', 'invoiceTakvimSure', 'Takvim süresi zorunludur');
            NbtModal.showError('invoiceModal', 'Takvim hatırlatması için süre giriniz');
            return;
        }

        NbtModal.setLoading('invoiceModal', true);
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
            
            if (CustomerDetailModule.customerId) {
                await CustomerDetailModule.loadRelatedData('invoices', '/api/invoices');
                CustomerDetailModule.switchTab(CustomerDetailModule.activeTab);
            }
        } catch (err) {
            NbtModal.showError('invoiceModal', err.message);
        } finally {
            NbtModal.setLoading('invoiceModal', false);
        }
    },

    // Fatura kalemleri yardımcı fonksiyonlar - Dinamik yapı
    resetInvoiceItems() {
        // Modal'daki UI reset fonksiyonunu çağır
        if (typeof window.resetInvoiceItemsUI === 'function') {
            window.resetInvoiceItemsUI();
        }
    },

    loadInvoiceItems(kalemler) {
        // Modal'daki UI load fonksiyonunu çağır
        if (typeof window.loadInvoiceItemsUI === 'function') {
            window.loadInvoiceItemsUI(kalemler);
        }
    },

    getInvoiceItems() {
        const kalemler = [];
        const rows = document.querySelectorAll('#invoiceItemsBody .invoice-item-row');
        rows.forEach((row, index) => {
            const miktar = parseFloat(row.querySelector('.item-miktar').value) || 0;
            const aciklama = row.querySelector('.item-aciklama').value.trim();
            const kdvOran = parseFloat(row.querySelector('.item-kdv').value) || 0;
            const birimFiyat = parseFloat(row.querySelector('.item-birimfiyat').value) || 0;
            const tutar = parseFloat(row.querySelector('.item-tutar').value) || 0;
            
            // Sadece dolu kalemleri ekle (en az miktar veya açıklama olmalı)
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
    data: [],
    pageSize: window.APP_CONFIG?.PAGINATION_DEFAULT || 10,
    currentPage: 1,
    paginationInfo: null,
    searchQuery: '',
    columnFilters: {},
    // Filtre için ek property'ler
    allData: null,
    allDataLoading: false,
    filteredPage: 1,
    filteredPaginationInfo: null,
    
    async init() {
        this.pageSize = NbtParams.getPaginationDefault();
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
        
        Object.keys(this.columnFilters).forEach(field => {
            const value = this.columnFilters[field];
            if (value) {
                filtered = filtered.filter(item => {
                    let cellValue = item[field];
                    if (field === 'Tarih') {
                        return NbtUtils.formatDateForCompare(cellValue) === value;
                    }
                    return NbtUtils.normalizeText(cellValue).includes(NbtUtils.normalizeText(value));
                });
            }
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
            { field: 'Tutar', label: 'Tutar', render: v => NbtUtils.formatMoney(v) },
            { field: 'Aciklama', label: 'Açıklama' }
        ];

        const headers = columns.map(c => `<th class="bg-light px-3">${c.label}</th>`).join('') + 
            '<th class="bg-light text-center px-3" style="width:140px;">İşlem</th>';

        const filterRow = columns.map(c => {
            const currentValue = this.columnFilters[c.field] || '';
            if (c.isDate) {
                return `<th class="p-1"><input type="date" class="form-control form-control-sm" data-column-filter="${c.field}" data-table-id="payments" value="${NbtUtils.escapeHtml(currentValue)}"></th>`;
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
                                <button class="btn btn-outline-primary btn-sm" type="button" data-action="view" data-id="${row.Id}" title="Detay">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a class="btn btn-outline-info btn-sm" href="/customer/${row.MusteriId}?tab=odemeler" title="Müşteriye Git">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </div>
                        </td>
                    </tr>`;
            }).join('');
        }

        container.innerHTML = `
            <div class="table-responsive px-2 py-2">
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
    },

    bindTableEvents(container) {
        container.querySelectorAll('[data-action="view"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.id);
                const payment = (this.allData || this.data).find(p => parseInt(p.Id, 10) === id);
                if (payment) {
                    NbtDetailModal.show('payment', payment, null, null);
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
            const response = await NbtApi.get(`/api/invoices?musteri_id=${musteriId}`);
            // API zaten musteri_id ile filtreliyor, ek filtreye gerek yok
            let faturalar = response.data || [];
            
            // Ödenmemiş faturaları filtrele (Kalan > 0)
            // Kalan yoksa Tutar'ı kullan (hiç ödeme yapılmamış faturalar için)
            // Edit modda: düzenlenen ödemenin faturasını her durumda göster
            faturalar = faturalar.filter(f => {
                // Edit modda düzenlenen ödemenin faturası her zaman gösterilmeli
                if (editingPayment && parseInt(f.Id) === parseInt(editingPayment.FaturaId)) {
                    return true;
                }
                const kalan = f.Kalan !== undefined && f.Kalan !== null 
                    ? parseFloat(f.Kalan) 
                    : parseFloat(f.Tutar) || 0;
                return kalan > 0;
            });
            
            this._invoicesCache = faturalar;
            
            if (faturalar.length === 0) {
                faturaSelect.innerHTML = '<option value="">Ödenmemiş fatura bulunamadı</option>';
                return;
            }
            
            faturalar.forEach(f => {
                let kalan = f.Kalan !== undefined && f.Kalan !== null 
                    ? parseFloat(f.Kalan) 
                    : parseFloat(f.Tutar) || 0;
                
                // Edit modda: düzenlenen ödemenin faturasıysa, mevcut ödeme tutarını kalan tutara ekle
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
        select.innerHTML = '<option value="">Seçiniz...</option>';
        select.disabled = false;
        AppState.customers.forEach(c => {
            select.innerHTML += `<option value="${c.Id}">${NbtUtils.escapeHtml(c.Unvan)}</option>`;
        });

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

        // Eğer customer detail sayfasındaysak müşteriyi auto-select et ve faturalarını yükle
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
                // Projeleri yükleme - edit modda seçili projeyi de gönder
                await CustomerDetailModule.populateProjectSelect('paymentProjeId', payment.ProjeId);
                document.getElementById('paymentTarih').value = payment.Tarih?.split('T')[0] || '';
                document.getElementById('paymentTutar').value = NbtUtils.formatDecimal(payment.Tutar) || '';
                document.getElementById('paymentAciklama').value = payment.Aciklama || '';
                if (faturaSelect && payment.FaturaId) {
                    // Edit modda mevcut ödeme bilgisini gönder - kalan tutar hesaplaması için
                    await this.loadInvoicesForCustomer(payment.MusteriId, payment);
                    faturaSelect.value = payment.FaturaId;
                }
            } else {
                NbtToast.error('Ödeme kaydı bulunamadı');
                return;
            }
        } else {
            // Yeni kayıt için projeleri yükleme
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
        
        // Seçili faturanın kalan tutarını kontrol et
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
// GÖRÜŞME MODÜLÜ (MEETING)
// =============================================
const MeetingModule = {
    _eventsBound: false,

    async openModal(id = null) {
        NbtModal.resetForm('meetingModal');
        document.getElementById('meetingModalTitle').textContent = id ? 'Görüşme Düzenle' : 'Yeni Görüşme';
        document.getElementById('meetingId').value = id || '';

        // Yeni kayıt için müşteri id'sini set et
        if (CustomerDetailModule.customerId) {
            document.getElementById('meetingMusteriId').value = CustomerDetailModule.customerId;
        }

        if (id) {
            const meeting = CustomerDetailModule.data.meetings?.find(m => parseInt(m.Id, 10) === parseInt(id, 10));
            if (meeting) {
                document.getElementById('meetingMusteriId').value = meeting.MusteriId;
                // Projeleri yükleme - edit modda seçili projeyi de gönder
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
            // Yeni kayıt için projeleri yükleme
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
// TAKVİM TAB MODÜLÜ (CUSTOMER DETAIL TAKVİM TABI)
// =============================================
const CalendarTabModule = {
    _eventsBound: false,

    async openModal(id = null) {
        NbtModal.resetForm('calendarModal');
        document.getElementById('calendarModalTitle').textContent = id ? 'Takvim Düzenle' : 'Yeni Takvim Kaydı';
        document.getElementById('calendarId').value = id || '';

        // Yeni kayıt için müşteri id'sini set et
        if (CustomerDetailModule.customerId) {
            document.getElementById('calendarMusteriId').value = CustomerDetailModule.customerId;
        }

        // Özet karakter sayacı
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
                // Projeleri yükleme - edit modda seçili projeyi de gönder
                await CustomerDetailModule.populateProjectSelect('calendarProjeId', calendar.ProjeId);
                document.getElementById('calendarBaslangicTarihi').value = calendar.BaslangicTarihi?.split('T')[0] || '';
                document.getElementById('calendarBitisTarihi').value = calendar.BitisTarihi?.split('T')[0] || '';
                document.getElementById('calendarOzet').value = calendar.Ozet || '';
                if (ozetCount) ozetCount.textContent = (calendar.Ozet || '').length;
            } else {
                NbtToast.error('Takvim kaydı bulunamadı');
                return;
            }
        } else {
            // Yeni kayıt için projeleri yükleme
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
            BaslangicTarihi: document.getElementById('calendarBaslangicTarihi').value,
            BitisTarihi: document.getElementById('calendarBitisTarihi').value,
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
        if (!data.BaslangicTarihi) {
            NbtModal.showFieldError('calendarModal', 'calendarBaslangicTarihi', 'Başlangıç tarihi zorunludur');
            NbtModal.showError('calendarModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        if (!data.BitisTarihi) {
            NbtModal.showFieldError('calendarModal', 'calendarBitisTarihi', 'Bitiş tarihi zorunludur');
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
// KİŞİ MODÜLÜ (CONTACT)
// =============================================
const ContactModule = {
    _eventsBound: false,
    
    async openModal(id = null) {
        NbtModal.resetForm('contactModal');
        document.getElementById('contactModalTitle').textContent = id ? 'Kişi Düzenle' : 'Yeni Kişi';
        document.getElementById('contactId').value = id || '';

        // Yeni kayıt için müşteri id'sini set et
        if (CustomerDetailModule.customerId) {
            document.getElementById('contactMusteriId').value = CustomerDetailModule.customerId;
        }

        if (id) {
            const contact = CustomerDetailModule.data.contacts?.find(c => parseInt(c.Id, 10) === parseInt(id, 10));
            if (contact) {
                document.getElementById('contactMusteriId').value = contact.MusteriId;
                // Projeleri yükleme - edit modda seçili projeyi de gönder
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
            // Yeni kayıt için projeleri yükleme
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
// DAMGA VERGİSİ MODÜLÜ (STAMP TAX)
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

        // Yeni kayıt için müşteri id'sini set et
        if (CustomerDetailModule.customerId) {
            document.getElementById('stampTaxMusteriId').value = CustomerDetailModule.customerId;
        }
        
        // Döviz seçeneklerini dinamik yükle
        const dovizSelect = document.getElementById('stampTaxDovizCinsi');
        if (dovizSelect) {
            await NbtParams.populateCurrencySelect(dovizSelect);
        }

        if (id) {
            const item = CustomerDetailModule.data.stampTaxes?.find(s => parseInt(s.Id, 10) === parseInt(id, 10));
            if (item) {
                document.getElementById('stampTaxMusteriId').value = item.MusteriId;
                // Projeleri yükleme - edit modda seçili projeyi de gönder
                await CustomerDetailModule.populateProjectSelect('stampTaxProjeId', item.ProjeId);
                document.getElementById('stampTaxTarih').value = item.Tarih?.split('T')[0] || '';
                document.getElementById('stampTaxTutar').value = NbtUtils.formatDecimal(item.Tutar) || '';
                document.getElementById('stampTaxDovizCinsi').value = item.DovizCinsi || 'TRY';
                document.getElementById('stampTaxBelgeNo').value = item.BelgeNo || '';
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
            // Yeni kayıt için projeleri yükleme
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
            DovizCinsi: document.getElementById('stampTaxDovizCinsi').value || 'TRY',
            BelgeNo: document.getElementById('stampTaxBelgeNo').value.trim() || null,
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
                if (file) formData.append('file', file);
                if (this.removeExistingFile) formData.append('removeFile', '1');
                
                const url = id ? `/api/stamp-taxes/${id}` : '/api/stamp-taxes';
                const method = id ? 'PUT' : 'POST';
                
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
// DOSYA MODÜLÜ (FILE)
// =============================================
const FileModule = {
    _eventsBound: false,
    editingId: null,
    
    async openModal(id = null) {
        NbtModal.resetForm('fileModal');
        this.editingId = id;
        
        if (id) {
            // Düzenleme modu
            document.getElementById('fileModalTitle').textContent = 'Dosya Düzenle';
            document.getElementById('fileInput').closest('.row').style.display = 'none'; // Dosya değiştirilmez
            
            // Mevcut dosya bilgilerini yükle
            const parsedId = parseInt(id, 10);
            let fileData = CustomerDetailModule.data?.files?.find(f => parseInt(f.Id, 10) === parsedId);
            
            if (fileData) {
                if (fileData.MusteriId) {
                    document.getElementById('fileMusteriId').value = fileData.MusteriId;
                }
                // Edit modda seçili projeyi de gönder
                await CustomerDetailModule.populateProjectSelect('fileProjeId', fileData.ProjeId);
                document.getElementById('fileAciklama').value = fileData.Aciklama || '';
            }
        } else {
            // Yeni kayıt modu
            document.getElementById('fileModalTitle').textContent = 'Dosya Yükle';
            document.getElementById('fileInput').value = '';
            document.getElementById('fileInput').closest('.row').style.display = '';
            
            // Yeni kayıt için müşteri id'sini set et
            if (CustomerDetailModule.customerId) {
                document.getElementById('fileMusteriId').value = CustomerDetailModule.customerId;
            }

            // Projeleri yükleme
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
        
        // Düzenleme modunda dosya kontrolü yapma
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

            // İzin verilen dosya türleri kontrolü
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
                // Düzenleme: JSON ile PUT
                const data = {
                    ProjeId: projeIdVal ? parseInt(projeIdVal) : null,
                    Aciklama: aciklama || null
                };
                await NbtApi.put(`/api/files/${this.editingId}`, data);
                NbtToast.success('Dosya güncellendi');
            } else {
                // Yeni kayıt: FormData ile POST
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
// PROJE MODÜLÜ
// =============================================
const ProjectModule = {
    _eventsBound: false,
    data: [],
    pageSize: window.APP_CONFIG?.PAGINATION_DEFAULT || 10,
    currentPage: 1,
    paginationInfo: null,
    searchQuery: '',
    columnFilters: {},
    // Filtre için ek property'ler
    allData: null,
    allDataLoading: false,
    filteredPage: 1,
    filteredPaginationInfo: null,
    
    async init() {
        this.pageSize = NbtParams.getPaginationDefault();
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
        
        Object.keys(this.columnFilters).forEach(field => {
            const value = this.columnFilters[field];
            if (value) {
                filtered = filtered.filter(item => {
                    let cellValue = item[field];
                    if (field === 'BaslangicTarihi' || field === 'BitisTarihi') {
                        return NbtUtils.formatDateForCompare(cellValue) === value;
                    }
                    if (field === 'Durum') {
                        const statuses = { 1: 'Aktif', 2: 'Tamamlandı', 3: 'İptal' };
                        cellValue = statuses[cellValue] || '';
                    }
                    return NbtUtils.normalizeText(cellValue).includes(NbtUtils.normalizeText(value));
                });
            }
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
            { field: 'BaslangicTarihi', label: 'Başlangıç', render: v => NbtUtils.formatDate(v), isDate: true },
            { field: 'BitisTarihi', label: 'Bitiş', render: v => NbtUtils.formatDate(v), isDate: true },
            { field: 'Butce', label: 'Bütçe', render: v => NbtUtils.formatMoney(v) },
            { field: 'Durum', label: 'Durum', render: v => {
                const statuses = { 1: ['Aktif', 'success'], 2: ['Tamamlandı', 'info'], 3: ['İptal', 'danger'] };
                const s = statuses[v] || ['Bilinmiyor', 'secondary'];
                return `<span class="badge bg-${s[1]}">${s[0]}</span>`;
            }}
        ];

        const headers = columns.map(c => `<th class="bg-light px-3">${c.label}</th>`).join('') + 
            '<th class="bg-light text-center px-3" style="width:140px;">İşlem</th>';

        const filterRow = columns.map(c => {
            const currentValue = this.columnFilters[c.field] || '';
            if (c.isDate) {
                return `<th class="p-1"><input type="date" class="form-control form-control-sm" data-column-filter="${c.field}" data-table-id="projects" value="${NbtUtils.escapeHtml(currentValue)}"></th>`;
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
                                <button class="btn btn-outline-primary btn-sm" type="button" data-action="view" data-id="${row.Id}" title="Detay">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a class="btn btn-outline-info btn-sm" href="/customer/${row.MusteriId}?tab=projeler" title="Müşteriye Git">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </div>
                        </td>
                    </tr>`;
            }).join('');
        }

        container.innerHTML = `
            <div class="table-responsive px-2 py-2">
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
    },

    bindTableEvents(container) {
        container.querySelectorAll('[data-action="view"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.id);
                const project = (this.allData || this.data).find(p => parseInt(p.Id, 10) === id);
                if (project) {
                    NbtDetailModal.show('project', project, null, null);
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
        select.innerHTML = '<option value="">Seçiniz...</option>';
        select.disabled = false;
        AppState.customers.forEach(c => {
            select.innerHTML += `<option value="${c.Id}">${NbtUtils.escapeHtml(c.Unvan)}</option>`;
        });

        // Durum select'ini parametrelerden doldur
        const statusSelect = document.getElementById('projectStatus');
        if (statusSelect) {
            await NbtParams.populateStatusSelect(statusSelect, 'proje');
        }

        // Eğer customer detail sayfasındaysak müşteriyi auto-select et ve disable yap
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
                select.disabled = true; // Düzenlemede müşteri kilitli
                document.getElementById('projectName').value = project.ProjeAdi || '';
                document.getElementById('projectStart').value = project.BaslangicTarihi?.split('T')[0] || '';
                document.getElementById('projectEnd').value = project.BitisTarihi?.split('T')[0] || '';
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
            BaslangicTarihi: document.getElementById('projectStart').value || null,
            BitisTarihi: document.getElementById('projectEnd').value || null,
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
    lastClickTime: 0,
    lastClickedRow: null,

    async init() {
        await this.loadList();
        this.initToolbar();
        this.bindEvents();
    },

    async loadList() {
        const container = document.getElementById('logsTableContainer');
        if (!container) return; // Standalone sayfa değilse çık
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
        if (!toolbarContainer || toolbarContainer.children.length > 0) return;
        if (toolbarContainer.children.length > 0) return;
        
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

    // Satıra çift tıklama event binding kodu
    bindEvents() {
        const container = document.getElementById('logsTableContainer');
        container.addEventListener('click', (e) => {
            const row = e.target.closest('tr[data-id]');
            if (!row) return;
            
            const now = Date.now();
            const id = row.dataset.id;
            
            // Çift tıklama algılama (500ms içinde aynı satıra tıklanırsa)
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

    // Detayı yeni sekmede JSON olarak açma kodu
    openDetailInNewTab(id) {
        const log = this.data.find(item => String(item.Id) === String(id));
        if (!log) return;
        
        let detailData = log.YeniDeger;
        
        // JSON parse deneme
        if (typeof detailData === 'string') {
            try {
                detailData = JSON.parse(detailData);
            } catch (e) {
                // Parse edilemezse string olarak bırak
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
                return `<small class="text-muted" title="Detay için çift tıklayın">${NbtUtils.escapeHtml(display)}</small>`;
            }},
            { field: 'YeniDeger', label: 'İncele', render: (v, row) => {
                if (!v) return '-';
                return `<button class="btn btn-sm btn-outline-info" data-action="inspect" data-log-id="${row.Id}" title="JSON Görüntüle"><i class="bi bi-code-slash"></i></button>`;
            }}
        ];

        container.innerHTML = NbtDataTable.create(columns, data, {
            actions: false,
            emptyMessage: 'Log kaydı bulunamadı'
        });
        
        // Inspect butonları için event listener
        container.querySelectorAll('[data-action="inspect"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const logId = btn.dataset.logId;
                const logItem = data.find(d => String(d.Id) === logId);
                if (logItem && logItem.YeniDeger) {
                    this.showInspectModal(logItem.YeniDeger);
                }
            });
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
// PARAMETRE MODÜLÜ
// =============================================
const ParameterModule = {
    _eventsBound: false,
    data: {},
    activeGroup: 'genel',
    
    groups: {
        'genel': { icon: 'bi-gear', label: 'Genel Ayarlar', color: 'primary' },
        'doviz': { icon: 'bi-currency-exchange', label: 'Döviz Türleri', color: 'success' },
        'durum_proje': { icon: 'bi-kanban', label: 'Proje Durumları', color: 'info' },
        'durum_teklif': { icon: 'bi-file-text', label: 'Teklif Durumları', color: 'warning' },
        'durum_sozlesme': { icon: 'bi-file-earmark-text', label: 'Sözleşme Durumları', color: 'secondary' },
        'durum_teminat': { icon: 'bi-shield-check', label: 'Teminat Durumları', color: 'danger' }
    },

    async init() {
        await this.loadData();
        this.renderSidebar();
        this.renderTable();
        this.bindEvents();
    },

    async loadData() {
        try {
            const response = await NbtApi.get('/api/parameters');
            this.data = response.data || {};
        } catch (err) {
            NbtToast.error('Parametreler yüklenemedi: ' + err.message);
        }
    },

    renderSidebar() {
        const sidebar = document.getElementById('parametersSidebar');
        if (!sidebar) return;

        let html = '<div class="list-group list-group-flush">';
        
        Object.entries(this.groups).forEach(([key, group]) => {
            const count = this.data[key]?.length || 0;
            const isActive = key === this.activeGroup;
            html += `
                <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center ${isActive ? 'active' : ''}" 
                   data-group="${key}">
                    <span><i class="bi ${group.icon} me-2"></i>${group.label}</span>
                    <span class="badge bg-${group.color} rounded-pill">${count}</span>
                </a>
            `;
        });
        
        html += '</div>';
        sidebar.innerHTML = html;

        // Sidebar click events
        sidebar.querySelectorAll('[data-group]').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                sidebar.querySelectorAll('.list-group-item').forEach(i => i.classList.remove('active'));
                item.classList.add('active');
                this.activeGroup = item.dataset.group;
                this.renderTable();
            });
        });
    },

    renderTable() {
        const container = document.getElementById('parametersTableContainer');
        const titleEl = document.getElementById('parametersTableTitle');
        const addBtn = document.getElementById('btnAddParameter');
        if (!container) return;

        const group = this.groups[this.activeGroup];
        const items = this.data[this.activeGroup] || [];
        
        titleEl.innerHTML = `<i class="bi ${group.icon} me-2"></i>${group.label}`;
        
        // Yeni ekleme butonu durum ve döviz gruplarında görünür (genel hariç)
        if (this.activeGroup.startsWith('durum_') || this.activeGroup === 'doviz') {
            addBtn.style.display = 'inline-block';
        } else {
            addBtn.style.display = 'none';
        }

        if (items.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    Bu grupta parametre bulunamadı
                </div>`;
            return;
        }

        // Gruba göre farklı render
        if (this.activeGroup === 'genel') {
            this.renderGeneralTable(container, items);
        } else if (this.activeGroup === 'doviz') {
            this.renderCurrencyTable(container, items);
        } else {
            this.renderStatusTable(container, items);
        }
    },

    // Genel ayarlar tablosu (pagination vb.)
    renderGeneralTable(container, items) {
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
        
        items.forEach(item => {
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
                                   min="5" max="100" style="width:100px;">
                        ` : `
                            <input type="text" class="form-control form-control-sm" 
                                   id="param_${item.Id}" value="${NbtUtils.escapeHtml(item.Deger || '')}">
                        `}
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary" data-action="save-general" data-id="${item.Id}">
                            <i class="bi bi-check-lg"></i> Kaydet
                        </button>
                    </td>
                </tr>`;
        });

        html += '</tbody></table></div>';
        container.innerHTML = html;
        this.bindTableEvents(container);
    },

    // Döviz tablosu (sadeleştirilmiş - düzenleme modaldan yapılır)
    renderCurrencyTable(container, items) {
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
                                   data-id="${item.Id}" ${isActive ? 'checked' : ''}>
                        </div>
                    </td>
                    <td>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="defaultCurrency" 
                                   data-action="set-default" data-id="${item.Id}" 
                                   ${isDefault ? 'checked' : ''}>
                        </div>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" data-action="edit" data-id="${item.Id}" title="Düzenle">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-danger" data-action="delete" data-id="${item.Id}" title="Sil">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
        });

        html += '</tbody></table></div>';
        container.innerHTML = html;
        this.bindTableEvents(container);
    },

    // Durum badge tablosu (sadeleştirilmiş - düzenleme modaldan yapılır)
    renderStatusTable(container, items) {
        let html = `
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Durum Adı</th>
                            <th style="width:150px;">Badge</th>
                            <th style="width:80px;">Aktif</th>
                            <th style="width:100px;">Varsayılan</th>
                            <th style="width:100px;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>`;
        
        items.forEach(item => {
            const badgeClass = item.Deger === 'warning' || item.Deger === 'light' ? `bg-${item.Deger} text-dark` : `bg-${item.Deger || 'secondary'}`;
            const isActive = item.Aktif === true || item.Aktif === 1 || item.Aktif === '1';
            const isDefault = item.Varsayilan === true || item.Varsayilan === 1 || item.Varsayilan === '1';
            html += `
                <tr data-id="${item.Id}">
                    <td><span class="fw-semibold">${NbtUtils.escapeHtml(item.Etiket)}</span></td>
                    <td><span class="badge ${badgeClass}">${NbtUtils.escapeHtml(item.Etiket)}</span></td>
                    <td>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" data-action="toggle-active" 
                                   data-id="${item.Id}" ${isActive ? 'checked' : ''}>
                        </div>
                    </td>
                    <td>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="defaultStatus_${this.activeGroup}" 
                                   data-action="set-default" data-id="${item.Id}" 
                                   ${isDefault ? 'checked' : ''}>
                        </div>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" data-action="edit" data-id="${item.Id}" title="Düzenle">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-danger" data-action="delete" data-id="${item.Id}" title="Sil">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
        });

        html += '</tbody></table></div>';
        container.innerHTML = html;
        this.bindTableEvents(container);
    },

    bindTableEvents(container) {
        // Düzenleme butonu
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
                
                // Pagination için 5-100 validasyonu
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
                    
                    // APP_CONFIG'i güncelle ve tüm modüllerin pageSize'ını güncelle
                    if (this.data.genel?.find(p => p.Id == id)?.Kod === 'pagination_default') {
                        const newSize = parseInt(value);
                        window.APP_CONFIG = window.APP_CONFIG || {};
                        window.APP_CONFIG.PAGINATION_DEFAULT = newSize;
                        // NbtParams cache'ini de güncelle
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

        // Varsayılan ayarlama
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
    },

    openModal(id = null) {
        if (this.activeGroup === 'doviz') {
            this.openCurrencyModal(id);
        } else if (this.activeGroup.startsWith('durum_')) {
            this.openStatusModal(id);
        }
    },

    // Döviz Modal
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

        // Badge önizleme güncellemesi
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
                // Badge rengi seç
                const colorRadio = document.querySelector(`input[name="statusBadgeColor"][value="${item.Deger}"]`);
                if (colorRadio) colorRadio.checked = true;
                updatePreview();
            }
        }

        NbtModal.open('statusModal');
    },

    // Döviz kaydetme
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
        
        // Kod'u sayısal olarak oluştur (mevcut maksimum + 1)
        let kod = '1';
        if (!id) {
            // Yeni kayıt için: mevcut kodların maksimumunu bul
            const existing = this.data[grup] || [];
            if (existing.length > 0) {
                const maxKod = Math.max(...existing.map(p => parseInt(p.Kod) || 0));
                kod = String(maxKod + 1);
            }
        } else {
            // Güncelleme için mevcut kodu koru
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
        
        document.getElementById('btnAddParameter')?.addEventListener('click', () => this.openModal());
        document.getElementById('btnSaveCurrency')?.addEventListener('click', () => this.saveCurrency());
        document.getElementById('btnSaveStatus')?.addEventListener('click', () => this.saveStatus());
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
        if (!container) return; // Standalone sayfa değilse çık
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
        if (!toolbarContainer || toolbarContainer.children.length > 0) return;
        
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
// HESABIM MODÜLÜ
// =============================================
const MyAccountModule = {
    _eventsBound: false,

    async init() {
        this.loadUserInfo();
        this.bindEvents();
    },

    loadUserInfo() {
        // localStorage'dan kullanıcı bilgilerini al
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
// ALARMLAR MODÜLÜ
// =============================================
const AlarmsModule = {
    _eventsBound: false,
    alarms: [],
    selectedType: 'invoice', // invoice, calendar, guarantee, contract

    async init() {
        await this.loadAlarms();
        this.renderSidebar();
        this.selectType(this.selectedType);
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
            invoice: { label: 'Ödenmemiş Faturalar', icon: 'bi-receipt', color: 'danger', items: [], count: 0 },
            calendar: { label: 'Takvim Kayıtları', icon: 'bi-calendar-event', color: 'warning', items: [], count: 0 },
            guarantee: { label: 'Vadesi Geçen Teminatlar', icon: 'bi-shield-check', color: 'info', items: [], count: 0 },
            contract: { label: 'Sözleşme Bitişleri', icon: 'bi-file-earmark-text', color: 'primary', items: [], count: 0 }
        };

        // API'dan gelen her alarm bir kategoriye ait ve içinde items dizisi var
        this.alarms.forEach(alarm => {
            if (grouped[alarm.type]) {
                grouped[alarm.type].items = alarm.items || [];
                grouped[alarm.type].count = alarm.count || 0;
            }
        });

        return grouped;
    },

    renderSidebar() {
        const container = document.getElementById('alarmsSidebar');
        if (!container) return;

        const grouped = this.getGroupedAlarms();
        let html = '<div class="list-group list-group-flush">';

        Object.keys(grouped).forEach(type => {
            const group = grouped[type];
            const isActive = type === this.selectedType ? 'active' : '';
            html += `
                <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center ${isActive}" data-alarm-type="${type}">
                    <span><i class="bi ${group.icon} me-2"></i>${group.label}</span>
                    <span class="badge bg-${group.color}">${group.count}</span>
                </a>`;
        });

        html += '</div>';
        container.innerHTML = html;
    },

    selectType(type) {
        this.selectedType = type;
        
        // Sidebar aktif durumu güncelleme
        document.querySelectorAll('#alarmsSidebar .list-group-item').forEach(item => {
            item.classList.toggle('active', item.dataset.alarmType === type);
        });

        // Tablo render işlemi
        this.renderTable();
    },

    renderTable() {
        const container = document.getElementById('alarmsTableContainer');
        if (!container) return;

        const grouped = this.getGroupedAlarms();
        const group = grouped[this.selectedType];
        const items = group ? group.items : [];

        // Başlık güncelleme
        const titleEl = document.getElementById('alarmsTableTitle');
        if (titleEl && group) {
            titleEl.innerHTML = `<i class="bi ${group.icon} me-2"></i>${group.label}`;
        }

        if (!items.length) {
            container.innerHTML = `
                <div class="text-center text-muted py-5">
                    <i class="bi bi-check-circle fs-1 d-block mb-3 text-success"></i>
                    <p class="mb-0">Bu kategoride alarm bulunmuyor</p>
                </div>`;
            return;
        }

        // Tip bazlı farklı kolonlar ve tablo
        let html = '<div class="table-responsive"><table class="table table-bordered table-hover table-sm mb-0"><thead class="table-light"><tr>';
        
        if (this.selectedType === 'invoice') {
            html += '<th>Müşteri</th><th>Tutar</th><th>Para Birimi</th>';
        } else if (this.selectedType === 'calendar') {
            html += '<th>Başlık</th><th>Açıklama</th><th>Tarih</th>';
        } else if (this.selectedType === 'guarantee') {
            html += '<th>Müşteri</th><th>Tutar</th><th>Vade Tarihi</th>';
        } else if (this.selectedType === 'contract') {
            html += '<th>Müşteri</th><th>Sözleşme No</th><th>Bitiş Tarihi</th>';
        }
        
        html += '</tr></thead><tbody>';

        items.forEach(item => {
            html += '<tr>';
            if (this.selectedType === 'invoice') {
                html += `
                    <td>${NbtUtils.escapeHtml(item.customer || '')}</td>
                    <td class="text-danger fw-bold">${NbtUtils.formatMoney(item.amount, item.currency)}</td>
                    <td>${item.currency || 'TRY'}</td>`;
            } else if (this.selectedType === 'calendar') {
                html += `
                    <td>${NbtUtils.escapeHtml(item.title || '')}</td>
                    <td>${NbtUtils.escapeHtml(item.description || '')}</td>
                    <td>${NbtUtils.formatDate(item.date)}</td>`;
            } else if (this.selectedType === 'guarantee') {
                html += `
                    <td>${NbtUtils.escapeHtml(item.customer || '')}</td>
                    <td class="text-danger">${NbtUtils.formatMoney(item.amount, item.currency)}</td>
                    <td>${NbtUtils.formatDate(item.dueDate)}</td>`;
            } else if (this.selectedType === 'contract') {
                html += `
                    <td>${NbtUtils.escapeHtml(item.customer || '')}</td>
                    <td>${NbtUtils.escapeHtml(item.contractNo || '')}</td>
                    <td>${NbtUtils.formatDate(item.endDate)}</td>`;
            }
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        container.innerHTML = html;
    },

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;

        // Sidebar tıklama
        document.getElementById('alarmsSidebar')?.addEventListener('click', (e) => {
            e.preventDefault();
            const item = e.target.closest('[data-alarm-type]');
            if (item) {
                this.selectType(item.dataset.alarmType);
            }
        });
    }
};

// =============================================
// TEKLİF MODÜLÜ
// =============================================
const OfferModule = {
    _eventsBound: false,
    data: [],
    pageSize: window.APP_CONFIG?.PAGINATION_DEFAULT || 10,
    currentPage: 1,
    paginationInfo: null,
    searchQuery: '',
    columnFilters: {},
    // Filtre için ek property'ler
    allData: null,
    allDataLoading: false,
    filteredPage: 1,
    filteredPaginationInfo: null,

    async init() {
        this.pageSize = NbtParams.getPaginationDefault();
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
        
        Object.keys(this.columnFilters).forEach(field => {
            const value = this.columnFilters[field];
            if (value) {
                filtered = filtered.filter(item => {
                    let cellValue = item[field];
                    if (field === 'TeklifTarihi') {
                        return NbtUtils.formatDateForCompare(cellValue) === value;
                    }
                    if (field === 'Durum') {
                        const statuses = { 0: 'Taslak', 1: 'Gönderildi', 2: 'Onaylandı', 3: 'Reddedildi' };
                        cellValue = statuses[cellValue] || '';
                    }
                    return NbtUtils.normalizeText(cellValue).includes(NbtUtils.normalizeText(value));
                });
            }
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
            { field: 'TeklifNo', label: 'Teklif No' },
            { field: 'Konu', label: 'Konu' },
            { field: 'Tutar', label: 'Tutar', render: (v, row) => NbtUtils.formatMoney(v, row.ParaBirimi) },
            { field: 'TeklifTarihi', label: 'Tarih', render: v => NbtUtils.formatDate(v), isDate: true },
            { field: 'Durum', label: 'Durum', render: v => {
                const statuses = { 0: ['Taslak', 'secondary'], 1: ['Gönderildi', 'warning'], 2: ['Onaylandı', 'success'], 3: ['Reddedildi', 'danger'] };
                const s = statuses[v] || ['Bilinmiyor', 'secondary'];
                return `<span class="badge bg-${s[1]}">${s[0]}</span>`;
            }}
        ];

        const headers = columns.map(c => `<th class="bg-light px-3">${c.label}</th>`).join('') + 
            '<th class="bg-light text-center px-3" style="width:140px;">İşlem</th>';

        const filterRow = columns.map(c => {
            const currentValue = this.columnFilters[c.field] || '';
            if (c.isDate) {
                return `<th class="p-1"><input type="date" class="form-control form-control-sm" data-column-filter="${c.field}" data-table-id="offers" value="${NbtUtils.escapeHtml(currentValue)}"></th>`;
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
                                <button class="btn btn-outline-primary btn-sm" type="button" data-action="view" data-id="${row.Id}" title="Detay">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a class="btn btn-outline-info btn-sm" href="/customer/${row.MusteriId}?tab=teklifler" title="Müşteriye Git">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </div>
                        </td>
                    </tr>`;
            }).join('');
        }

        container.innerHTML = `
            <div class="table-responsive px-2 py-2">
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
    },

    bindTableEvents(container) {
        container.querySelectorAll('[data-action="view"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.id);
                const offer = (this.allData || this.data).find(o => parseInt(o.Id, 10) === id);
                if (offer) {
                    NbtDetailModal.show('offer', offer, null, null);
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

        const select = document.getElementById('offerMusteriId');
        select.innerHTML = '<option value="">Seçiniz...</option>';
        select.disabled = false;
        AppState.customers.forEach(c => {
            select.innerHTML += `<option value="${c.Id}">${NbtUtils.escapeHtml(c.Unvan)}</option>`;
        });

        const projeSelect = document.getElementById('offerProjeId');
        projeSelect.innerHTML = '<option value="">Proje Seçiniz...</option>';

        // Durum ve döviz select'lerini parametrelerden doldur
        await NbtParams.populateStatusSelect(document.getElementById('offerStatus'), 'teklif');
        await NbtParams.populateCurrencySelect(document.getElementById('offerCurrency'));

        // Müşteri değiştiğinde projeleri yükleme
        select.onchange = async () => {
            projeSelect.innerHTML = '<option value="">Proje Seçiniz...</option>';
            const musteriId = select.value;
            if (musteriId) {
                try {
                    const response = await NbtApi.get(`/api/projects?musteri_id=${musteriId}`);
                    const projects = response.data || [];
                    projects.forEach(p => {
                        projeSelect.innerHTML += `<option value="${p.Id}">${NbtUtils.escapeHtml(p.ProjeAdi || '')}</option>`;
                    });
                } catch (err) {
                    NbtLogger.error('Projeler yüklenemedi:', err);
                }
            }
        };

        // Eğer customer detail sayfasındaysak müşteriyi auto-select et ve disable yap
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
                // Projeleri yükleme ve seçili projeyi ayarlama
                await select.onchange();
                document.getElementById('offerProjeId').value = offer.ProjeId || '';
                document.getElementById('offerNo').value = offer.TeklifNo || '';
                document.getElementById('offerSubject').value = offer.Konu || '';
                document.getElementById('offerAmount').value = NbtUtils.formatDecimal(offer.Tutar) || '';
                document.getElementById('offerCurrency').value = offer.ParaBirimi || NbtParams.getDefaultCurrency();
                document.getElementById('offerDate').value = offer.TeklifTarihi?.split('T')[0] || '';
                document.getElementById('offerValidDate').value = offer.GecerlilikTarihi?.split('T')[0] || '';
                document.getElementById('offerStatus').value = offer.Durum ?? '';
            } else {
                NbtToast.error('Teklif kaydı bulunamadı');
                return;
            }
        }

        NbtModal.open('offerModal');
    },

    async save() {
        const id = document.getElementById('offerId').value;
        
        let musteriId = parseInt(document.getElementById('offerMusteriId').value);
        if (!musteriId || isNaN(musteriId)) {
            musteriId = CustomerDetailModule.customerId;
        }
        
        const projeIdVal = document.getElementById('offerProjeId').value;
        const data = {
            MusteriId: musteriId,
            ProjeId: projeIdVal ? parseInt(projeIdVal) : null,
            TeklifNo: document.getElementById('offerNo').value.trim(),
            Konu: document.getElementById('offerSubject').value.trim() || null,
            Tutar: parseFloat(document.getElementById('offerAmount').value) || 0,
            ParaBirimi: document.getElementById('offerCurrency').value,
            TeklifTarihi: document.getElementById('offerDate').value || null,
            GecerlilikTarihi: document.getElementById('offerValidDate').value || null,
            Durum: document.getElementById('offerStatus').value
        };

        NbtModal.clearError('offerModal');
        if (!data.MusteriId || isNaN(data.MusteriId)) {
            NbtModal.showFieldError('offerModal', 'offerMusteriId', 'Müşteri seçiniz');
            NbtModal.showError('offerModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        if (!data.ProjeId) {
            NbtModal.showFieldError('offerModal', 'offerProjeId', 'Proje seçiniz');
            NbtModal.showError('offerModal', 'Proje seçimi zorunludur');
            return;
        }
        if (!data.TeklifNo) {
            NbtModal.showFieldError('offerModal', 'offerNo', 'Teklif No zorunludur');
            NbtModal.showError('offerModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        if (!data.Tutar || data.Tutar <= 0) {
            NbtModal.showFieldError('offerModal', 'offerAmount', 'Tutar zorunludur');
            NbtModal.showError('offerModal', 'Lütfen tutar giriniz');
            return;
        }
        if (!data.TeklifTarihi) {
            NbtModal.showFieldError('offerModal', 'offerDate', 'Tarih zorunludur');
            NbtModal.showError('offerModal', 'Lütfen tarih seçiniz');
            return;
        }
        if (!data.GecerlilikTarihi) {
            NbtModal.showFieldError('offerModal', 'offerValidDate', 'Geçerlilik tarihi zorunludur');
            NbtModal.showError('offerModal', 'Lütfen geçerlilik tarihi seçiniz');
            return;
        }

        NbtModal.setLoading('offerModal', true);
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
    pageSize: window.APP_CONFIG?.PAGINATION_DEFAULT || 10,
    currentPage: 1,
    paginationInfo: null,
    searchQuery: '',
    columnFilters: {},
    // Filtre için ek property'ler
    allData: null,
    allDataLoading: false,
    filteredPage: 1,
    filteredPaginationInfo: null,

    async init() {
        this.pageSize = NbtParams.getPaginationDefault();
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
        
        Object.keys(this.columnFilters).forEach(field => {
            const value = this.columnFilters[field];
            if (value) {
                filtered = filtered.filter(item => {
                    let cellValue = item[field];
                    if (field === 'BaslangicTarihi' || field === 'BitisTarihi') {
                        return NbtUtils.formatDateForCompare(cellValue) === value;
                    }
                    if (field === 'Durum') {
                        const statuses = { 1: 'Aktif', 2: 'Pasif', 3: 'İptal' };
                        cellValue = statuses[cellValue] || '';
                    }
                    return NbtUtils.normalizeText(cellValue).includes(NbtUtils.normalizeText(value));
                });
            }
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
            { field: 'SozlesmeNo', label: 'Sözleşme No' },
            { field: 'BaslangicTarihi', label: 'Başlangıç', render: v => NbtUtils.formatDate(v), isDate: true },
            { field: 'BitisTarihi', label: 'Bitiş', render: v => NbtUtils.formatDate(v), isDate: true },
            { field: 'Tutar', label: 'Tutar', render: (v, row) => NbtUtils.formatMoney(v, row.ParaBirimi) },
            { field: 'Durum', label: 'Durum', render: v => {
                const statuses = { 1: ['Aktif', 'success'], 2: ['Pasif', 'secondary'], 3: ['İptal', 'danger'] };
                const s = statuses[v] || ['Bilinmiyor', 'secondary'];
                return `<span class="badge bg-${s[1]}">${s[0]}</span>`;
            }}
        ];

        const headers = columns.map(c => `<th class="bg-light px-3">${c.label}</th>`).join('') + 
            '<th class="bg-light text-center px-3" style="width:140px;">İşlem</th>';

        const filterRow = columns.map(c => {
            const currentValue = this.columnFilters[c.field] || '';
            if (c.isDate) {
                return `<th class="p-1"><input type="date" class="form-control form-control-sm" data-column-filter="${c.field}" data-table-id="contracts" value="${NbtUtils.escapeHtml(currentValue)}"></th>`;
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
                                <button class="btn btn-outline-primary btn-sm" type="button" data-action="view" data-id="${row.Id}" title="Detay">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a class="btn btn-outline-info btn-sm" href="/customer/${row.MusteriId}?tab=sozlesmeler" title="Müşteriye Git">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </div>
                        </td>
                    </tr>`;
            }).join('');
        }

        container.innerHTML = `
            <div class="table-responsive px-2 py-2">
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
    },

    bindTableEvents(container) {
        container.querySelectorAll('[data-action="view"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.id);
                const contract = (this.allData || this.data).find(c => parseInt(c.Id, 10) === id);
                if (contract) {
                    NbtDetailModal.show('contract', contract, null, null);
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

        const select = document.getElementById('contractMusteriId');
        select.innerHTML = '<option value="">Seçiniz...</option>';
        select.disabled = false;
        AppState.customers.forEach(c => {
            select.innerHTML += `<option value="${c.Id}">${NbtUtils.escapeHtml(c.Unvan)}</option>`;
        });

        const projeSelect = document.getElementById('contractProjeId');
        projeSelect.innerHTML = '<option value="">Proje Seçiniz...</option>';

        // Durum ve döviz select'lerini parametrelerden doldur
        await NbtParams.populateStatusSelect(document.getElementById('contractStatus'), 'sozlesme');
        await NbtParams.populateCurrencySelect(document.getElementById('contractCurrency'));

        // Müşteri değiştiğinde projeleri yükleme
        select.onchange = async () => {
            projeSelect.innerHTML = '<option value="">Proje Seçiniz...</option>';
            const musteriId = select.value;
            if (musteriId) {
                try {
                    const response = await NbtApi.get(`/api/projects?musteri_id=${musteriId}`);
                    const projects = response.data || [];
                    projects.forEach(p => {
                        projeSelect.innerHTML += `<option value="${p.Id}">${NbtUtils.escapeHtml(p.ProjeAdi || '')}</option>`;
                    });
                } catch (err) {
                    NbtLogger.error('Projeler yüklenemedi:', err);
                }
            }
        };

        // Eğer customer detail sayfasındaysak müşteriyi auto-select et ve disable yap
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
                // Projeleri yükleme ve seçili projeyi ayarlama
                await select.onchange();
                document.getElementById('contractProjeId').value = contract.ProjeId || '';
                document.getElementById('contractStart').value = contract.BaslangicTarihi?.split('T')[0] || '';
                document.getElementById('contractEnd').value = contract.BitisTarihi?.split('T')[0] || '';
                document.getElementById('contractAmount').value = NbtUtils.formatDecimal(contract.Tutar) || '';
                document.getElementById('contractCurrency').value = contract.ParaBirimi || NbtParams.getDefaultCurrency();
                document.getElementById('contractStatus').value = contract.Durum ?? '';
            } else {
                NbtToast.error('Sözleşme kaydı bulunamadı');
                return;
            }
        }

        NbtModal.open('contractModal');
    },

    async save() {
        const id = document.getElementById('contractId').value;
        
        let musteriId = parseInt(document.getElementById('contractMusteriId').value);
        if (!musteriId || isNaN(musteriId)) {
            musteriId = CustomerDetailModule.customerId;
        }
        
        const projeIdVal = document.getElementById('contractProjeId').value;
        const data = {
            MusteriId: musteriId,
            ProjeId: projeIdVal ? parseInt(projeIdVal) : null,
            BaslangicTarihi: document.getElementById('contractStart').value || null,
            BitisTarihi: document.getElementById('contractEnd').value || null,
            Tutar: parseFloat(document.getElementById('contractAmount').value) || 0,
            ParaBirimi: document.getElementById('contractCurrency').value,
            Durum: document.getElementById('contractStatus').value
        };

        NbtModal.clearError('contractModal');
        if (!data.MusteriId || isNaN(data.MusteriId)) {
            NbtModal.showFieldError('contractModal', 'contractMusteriId', 'Müşteri seçiniz');
            NbtModal.showError('contractModal', 'Lütfen zorunlu alanları doldurun');
            return;
        }
        if (!data.ProjeId) {
            NbtModal.showFieldError('contractModal', 'contractProjeId', 'Proje seçiniz');
            NbtModal.showError('contractModal', 'Proje seçimi zorunludur');
            return;
        }
        if (!data.Tutar || data.Tutar <= 0) {
            NbtModal.showFieldError('contractModal', 'contractAmount', 'Tutar zorunludur');
            NbtModal.showError('contractModal', 'Tutar 0\'dan büyük olmalıdır');
            return;
        }

        NbtModal.setLoading('contractModal', true);
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
    pageSize: window.APP_CONFIG?.PAGINATION_DEFAULT || 10,
    currentPage: 1,
    paginationInfo: null,
    searchQuery: '',
    columnFilters: {},
    // Filtre için ek property'ler
    allData: null,
    allDataLoading: false,
    filteredPage: 1,
    filteredPaginationInfo: null,
    // PDF dosya işlemleri için
    selectedFile: null,
    removeExistingFile: false,

    async init() {
        this.pageSize = NbtParams.getPaginationDefault();
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
        
        Object.keys(this.columnFilters).forEach(field => {
            const value = this.columnFilters[field];
            if (value) {
                filtered = filtered.filter(item => {
                    let cellValue = item[field];
                    if (field === 'VadeTarihi') {
                        return NbtUtils.formatDateForCompare(cellValue) === value;
                    }
                    if (field === 'Durum') {
                        const statuses = { 1: 'Bekliyor', 2: 'İade Edildi', 3: 'Tahsil Edildi', 4: 'Yandı' };
                        cellValue = statuses[cellValue] || '';
                    }
                    return NbtUtils.normalizeText(cellValue).includes(NbtUtils.normalizeText(value));
                });
            }
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
        const statuses = { 1: ['Bekliyor', 'warning'], 2: ['İade Edildi', 'info'], 3: ['Tahsil Edildi', 'success'], 4: ['Yandı', 'danger'] };
        
        const columns = [
            { field: 'MusteriUnvan', label: 'Müşteri' },
            { field: 'BelgeNo', label: 'Belge No' },
            { field: 'Tur', label: 'Tür' },
            { field: 'Tutar', label: 'Tutar', render: (v, row) => NbtUtils.formatMoney(v, row.ParaBirimi) },
            { field: 'BankaAdi', label: 'Banka' },
            { field: 'VadeTarihi', label: 'Vade', render: v => NbtUtils.formatDate(v), isDate: true },
            { field: 'Durum', label: 'Durum', render: v => {
                const s = statuses[v] || ['Bilinmiyor', 'secondary'];
                return `<span class="badge bg-${s[1]}">${s[0]}</span>`;
            }}
        ];

        const headers = columns.map(c => `<th class="bg-light">${c.label}</th>`).join('') + 
            '<th class="bg-light text-center" style="width:100px">İşlem</th>';

        const filterRow = columns.map(c => {
            const currentValue = this.columnFilters[c.field] || '';
            if (c.isDate) {
                return `<th class="p-1"><input type="date" class="form-control form-control-sm" data-column-filter="${c.field}" data-table-id="guarantees" value="${NbtUtils.escapeHtml(currentValue)}"></th>`;
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
            rowsHtml = '<tr><td colspan="8" class="text-center text-muted py-4">Teminat bulunamadı</td></tr>';
        } else {
            rowsHtml = data.map(row => {
                const s = statuses[row.Durum] || ['Bilinmiyor', 'secondary'];
                return `
                    <tr>
                        <td>${NbtUtils.escapeHtml(row.MusteriUnvan || '')}</td>
                        <td>${NbtUtils.escapeHtml(row.BelgeNo || '')}</td>
                        <td>${NbtUtils.escapeHtml(row.Tur || '')}</td>
                        <td>${NbtUtils.formatMoney(row.Tutar, row.ParaBirimi)}</td>
                        <td>${NbtUtils.escapeHtml(row.BankaAdi || '')}</td>
                        <td>${NbtUtils.formatDate(row.VadeTarihi)}</td>
                        <td><span class="badge bg-${s[1]}">${s[0]}</span></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary btn-view" data-id="${row.Id}" title="Görüntüle"><i class="bi bi-eye"></i></button>
                                <a class="btn btn-outline-info" href="/customer/${row.MusteriId}?tab=teminatlar" title="Müşteriye Git"><i class="bi bi-person"></i></a>
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
    },

    bindTableEvents(container) {
        container.querySelectorAll('.btn-view').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.id, 10);
                const guarantee = (this.allData || this.data).find(g => parseInt(g.Id, 10) === id);
                if (guarantee) {
                    NbtDetailModal.show('guarantee', guarantee, null, null);
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
        
        // PDF dosya değişkenlerini sıfırla
        this.selectedFile = null;
        this.removeExistingFile = false;
        document.getElementById('guaranteeDosya').value = '';
        document.getElementById('guaranteeCurrentFile')?.classList.add('d-none');

        const select = document.getElementById('guaranteeMusteriId');
        select.innerHTML = '<option value="">Seçiniz...</option>';
        select.disabled = false;
        AppState.customers.forEach(c => {
            select.innerHTML += `<option value="${c.Id}">${NbtUtils.escapeHtml(c.Unvan)}</option>`;
        });

        const projeSelect = document.getElementById('guaranteeProjeId');
        projeSelect.innerHTML = '<option value="">Proje Seçiniz...</option>';

        // Durum ve döviz select'lerini parametrelerden doldur
        await NbtParams.populateStatusSelect(document.getElementById('guaranteeStatus'), 'teminat');
        await NbtParams.populateCurrencySelect(document.getElementById('guaranteeCurrency'));

        // Müşteri değiştiğinde projeleri yükleme
        select.onchange = async () => {
            projeSelect.innerHTML = '<option value="">Proje Seçiniz...</option>';
            const musteriId = select.value;
            if (musteriId) {
                try {
                    const response = await NbtApi.get(`/api/projects?musteri_id=${musteriId}`);
                    const projects = response.data || [];
                    projects.forEach(p => {
                        projeSelect.innerHTML += `<option value="${p.Id}">${NbtUtils.escapeHtml(p.ProjeAdi || '')}</option>`;
                    });
                } catch (err) {
                    NbtLogger.error('Projeler yüklenemedi:', err);
                }
            }
        };

        // Eğer customer detail sayfasındaysak müşteriyi auto-select et ve disable yap
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
                // Projeleri yükleme ve seçili projeyi ayarlama
                await select.onchange();
                document.getElementById('guaranteeProjeId').value = guarantee.ProjeId || '';
                document.getElementById('guaranteeNo').value = guarantee.BelgeNo || '';
                document.getElementById('guaranteeType').value = guarantee.Tur || 'Nakit';
                document.getElementById('guaranteeBank').value = guarantee.BankaAdi || '';
                document.getElementById('guaranteeAmount').value = NbtUtils.formatDecimal(guarantee.Tutar) || '';
                document.getElementById('guaranteeCurrency').value = guarantee.ParaBirimi || NbtParams.getDefaultCurrency();
                document.getElementById('guaranteeDate').value = guarantee.VadeTarihi?.split('T')[0] || '';
                document.getElementById('guaranteeStatus').value = guarantee.Durum ?? '';
                
                // Mevcut dosya varsa göster
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
            BelgeNo: document.getElementById('guaranteeNo').value.trim() || null,
            Tur: document.getElementById('guaranteeType').value,
            BankaAdi: document.getElementById('guaranteeBank').value.trim() || null,
            Tutar: parseFloat(document.getElementById('guaranteeAmount').value) || 0,
            ParaBirimi: document.getElementById('guaranteeCurrency').value,
            VadeTarihi: document.getElementById('guaranteeDate').value || null,
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
        
        // PDF dosya kontrolü
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
                if (data.VadeTarihi) formData.append('VadeTarihi', data.VadeTarihi);
                formData.append('Durum', data.Durum);
                if (file) formData.append('file', file);
                if (this.removeExistingFile) formData.append('removeFile', '1');
                
                const url = id ? `/api/guarantees/${id}` : '/api/guarantees';
                const method = id ? 'PUT' : 'POST';
                
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

    bindEvents() {
        if (this._eventsBound) return;
        this._eventsBound = true;
        document.getElementById('btnSaveGuarantee')?.addEventListener('click', () => this.save());
        
        // PDF dosya kaldırma butonu
        document.getElementById('btnRemoveGuaranteeFile')?.addEventListener('click', () => {
            this.removeExistingFile = true;
            document.getElementById('guaranteeCurrentFile')?.classList.add('d-none');
        });
        
        // Dosya seçimi
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
// NOT: Artık SPA routing yok. Her sayfa server-side render ediliyor.
// Bu fonksiyon sadece sayfa yüklendiğinde ilgili modülü init etmek için kullanılmaktadır.

function setupRoutes() {
    // Dashboard modülü
    NbtRouter.register('dashboard', () => {
        const view = document.getElementById('view-dashboard');
        if (view) {
            view.classList.remove('d-none');
            DashboardModule.init();
        }
    });

    // Müşteriler listesi
    NbtRouter.register('customers', () => {
        const view = document.getElementById('view-customers');
        if (view) {
            view.classList.remove('d-none');
            CustomerModule.init();
        }
    });

    // Müşteri detay
    NbtRouter.register('customer', (params) => {
        const view = document.getElementById('view-customer-detail');
        if (view) {
            view.classList.remove('d-none');
            // ID'yi data attribute'dan veya params'dan al
            const detailEl = document.getElementById('view-customer-detail');
            const id = parseInt(params.id || detailEl?.dataset?.customerId);
            if (id) {
                // Tab parametresini URL'den al (opsiyonel deep-link desteği)
                const tabParam = params.tab || null;
                CustomerDetailModule.init(id, tabParam);
            }
        }
    });

    // Faturalar
    NbtRouter.register('invoices', () => {
        const view = document.getElementById('view-invoices');
        if (view) {
            view.classList.remove('d-none');
            InvoiceModule.init();
        }
    });

    // Ödemeler
    NbtRouter.register('payments', () => {
        const view = document.getElementById('view-payments');
        if (view) {
            view.classList.remove('d-none');
            PaymentModule.init();
        }
    });

    // Projeler
    NbtRouter.register('projects', () => {
        const view = document.getElementById('view-projects');
        if (view) {
            view.classList.remove('d-none');
            ProjectModule.init();
        }
    });

    // Teklifler
    NbtRouter.register('offers', () => {
        const view = document.getElementById('view-offers');
        if (view) {
            view.classList.remove('d-none');
            OfferModule.init();
        }
    });

    // Sözleşmeler
    NbtRouter.register('contracts', () => {
        const view = document.getElementById('view-contracts');
        if (view) {
            view.classList.remove('d-none');
            ContractModule.init();
        }
    });

    // Teminatlar
    NbtRouter.register('guarantees', () => {
        const view = document.getElementById('view-guarantees');
        if (view) {
            view.classList.remove('d-none');
            GuaranteeModule.init();
        }
    });

    // Kullanıcılar
    NbtRouter.register('users', () => {
        const view = document.getElementById('view-users');
        if (view) {
            view.classList.remove('d-none');
            UserModule.init();
        }
    });

    // Loglar
    NbtRouter.register('logs', () => {
        const view = document.getElementById('view-logs');
        if (view) {
            view.classList.remove('d-none');
            LogModule.init();
        }
    });

    // Hesabım
    NbtRouter.register('my-account', () => {
        const view = document.getElementById('view-my-account');
        if (view) {
            view.classList.remove('d-none');
            MyAccountModule.init();
        }
    });

    // Alarmlar
    NbtRouter.register('alarms', () => {
        const view = document.getElementById('view-alarms');
        if (view) {
            view.classList.remove('d-none');
            AlarmsModule.init();
        }
    });

    // Parametreler
    NbtRouter.register('parameters', () => {
        const view = document.getElementById('view-parameters');
        if (view) {
            view.classList.remove('d-none');
            ParameterModule.init();
        }
    });
}

// =============================================
// GLOBAL EVENT BINDINGS
// =============================================
function setupGlobalEvents() {
    // Çıkış
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

    // Navbar linkleri artık server navigation yapıyor (href kullanıyor)
    // data-route attribute'ları artık sadece aktif durumu belirlemek için
    // Link interception KALDIRILDI - tüm href'ler doğal sayfa yüklemesi yapar

    // Rol bazlı menü görünürlüğü
    const role = NbtUtils.getRole();
    if (role !== 'superadmin') {
        document.getElementById('systemMenu')?.classList.add('d-none');
    }

    // Modül eventlerini bind et
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
    // Token kontrolü
    if (!NbtUtils.getToken()) {
        window.location.href = '/login';
        return;
    }

    // Parametreleri ve müşteri listesini önyükleme (performance için)
    await Promise.all([
        NbtParams.preload(),
        NbtApi.get('/api/customers').then(response => {
            AppState.customers = response.data || [];
        }).catch(() => {})
    ]);

    // Route'ları kaydet (modül init fonksiyonları için)
    setupRoutes();
    
    // Global eventleri bağlama
    setupGlobalEvents();
    
    // Şifre modülünü init etme
    PasswordModule.init();

    // Server-rendered mimaride: Sayfa init
    // CURRENT_PAGE değerine göre ilgili modülü başlat
    NbtRouter.init();
});
