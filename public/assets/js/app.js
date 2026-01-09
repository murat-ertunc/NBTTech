/**
 * NbtProject - Ana JavaScript Modülü
 * ===================================
 * Ortak fonksiyonlar, API yardımcıları, Toast, Fullscreen vb.
 */

// =============================================
// GLOBAL SABITLER
// =============================================
const NBT = {
    TOKEN_KEY: 'nbt_token',
    ROLE_KEY: 'nbt_role',
    USER_KEY: 'nbt_user',
    TAB_KEY: 'nbt_tab_id',
    API_BASE: ''
};

// =============================================
// UTILITY FUNCTIONS
// =============================================
const NbtUtils = {
    /**
     * Sekme ID'si al/oluştur
     */
    getTabId() {
        let id = sessionStorage.getItem(NBT.TAB_KEY);
        if (!id) {
            id = crypto.randomUUID();
            sessionStorage.setItem(NBT.TAB_KEY, id);
        }
        return id;
    },

    /**
     * Token al
     */
    getToken() {
        return localStorage.getItem(NBT.TOKEN_KEY);
    },

    /**
     * Rol al
     */
    getRole() {
        return localStorage.getItem(NBT.ROLE_KEY) || 'user';
    },

    /**
     * Kullanıcı bilgisi al
     */
    getUser() {
        try {
            return JSON.parse(localStorage.getItem(NBT.USER_KEY));
        } catch {
            return null;
        }
    },

    /**
     * Oturum aç
     */
    setSession(token, user) {
        localStorage.setItem(NBT.TOKEN_KEY, token);
        if (user) {
            localStorage.setItem(NBT.USER_KEY, JSON.stringify(user));
            if (user.role) {
                localStorage.setItem(NBT.ROLE_KEY, user.role);
            }
        }
    },

    /**
     * Oturumu kapat
     */
    clearSession() {
        localStorage.removeItem(NBT.TOKEN_KEY);
        localStorage.removeItem(NBT.ROLE_KEY);
        localStorage.removeItem(NBT.USER_KEY);
    },

    /**
     * XSS koruması - HTML escape
     */
    escapeHtml(text) {
        if (text == null) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    },

    /**
     * Tarih formatlama
     */
    formatDate(dateStr, format = 'short') {
        if (!dateStr) return '-';
        const date = new Date(dateStr);
        if (format === 'short') {
            return date.toLocaleDateString('tr-TR');
        }
        return date.toLocaleDateString('tr-TR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    },

    /**
     * Para formatlama
     */
    formatMoney(amount, currency = 'TRY') {
        const num = parseFloat(amount) || 0;
        const symbols = { TRY: '₺', USD: '$', EUR: '€', TL: '₺' };
        return num.toLocaleString('tr-TR', { minimumFractionDigits: 2 }) + ' ' + (symbols[currency] || currency);
    },

    /**
     * Debounce
     */
    debounce(fn, delay = 300) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn(...args), delay);
        };
    }
};

// =============================================
// API İSTEK YARDIMCISI
// =============================================
const NbtApi = {
    /**
     * API isteği yap
     */
    async request(path, options = {}) {
        const headers = options.headers || {};
        
        if (!headers['Content-Type'] && !(options.body instanceof FormData)) {
            headers['Content-Type'] = 'application/json';
        }
        
        const token = NbtUtils.getToken();
        if (token) {
            headers['Authorization'] = 'Bearer ' + token;
        }
        headers['X-Tab-Id'] = NbtUtils.getTabId();
        headers['X-Role'] = NbtUtils.getRole();

        const response = await fetch(NBT.API_BASE + path, { ...options, headers });
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            if (response.status === 401) {
                NbtUtils.clearSession();
                window.location.href = '/login';
                throw new Error('Oturum süresi doldu');
            }
            throw new Error(data.error || 'Bir hata oluştu');
        }

        return data;
    },

    // CRUD Shortcuts
    get: (path) => NbtApi.request(path),
    post: (path, data) => NbtApi.request(path, { method: 'POST', body: JSON.stringify(data) }),
    put: (path, data) => NbtApi.request(path, { method: 'PUT', body: JSON.stringify(data) }),
    delete: (path) => NbtApi.request(path, { method: 'DELETE' })
};

// =============================================
// TOAST BİLDİRİMLERİ (Bootstrap Toast)
// =============================================
const NbtToast = {
    container: null,

    init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container position-fixed top-0 end-0 p-3';
            this.container.style.zIndex = '1055';
            this.container.style.marginTop = '56px';
            document.body.appendChild(this.container);
        }
    },

    show(message, type = 'info', duration = 4000) {
        this.init();
        
        const icons = {
            success: 'bi-check-circle-fill text-success',
            error: 'bi-exclamation-circle-fill text-danger',
            warning: 'bi-exclamation-triangle-fill text-warning',
            info: 'bi-info-circle-fill text-info'
        };
        
        const bgColors = {
            success: 'border-start border-success border-4',
            error: 'border-start border-danger border-4',
            warning: 'border-start border-warning border-4',
            info: 'border-start border-info border-4'
        };

        const toast = document.createElement('div');
        toast.className = `toast show align-items-center bg-white shadow ${bgColors[type] || bgColors.info}`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2">
                    <i class="bi ${icons[type] || icons.info}"></i>
                    <span>${NbtUtils.escapeHtml(message)}</span>
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        this.container.appendChild(toast);
        
        toast.querySelector('.btn-close').addEventListener('click', () => toast.remove());

        if (duration > 0) {
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        return toast;
    },

    success: (msg, dur) => NbtToast.show(msg, 'success', dur),
    error: (msg, dur) => NbtToast.show(msg, 'error', dur),
    warning: (msg, dur) => NbtToast.show(msg, 'warning', dur),
    info: (msg, dur) => NbtToast.show(msg, 'info', dur)
};

// =============================================
// FULLSCREEN TOGGLE (Bootstrap classes)
// =============================================
const NbtFullscreen = {
    activeElement: null,

    toggle(element) {
        if (this.activeElement === element) {
            this.exit();
        } else {
            this.enter(element);
        }
    },

    enter(element) {
        if (this.activeElement) {
            this.exit();
        }
        element.classList.add('position-fixed', 'top-0', 'start-0', 'w-100', 'h-100', 'bg-white');
        element.style.zIndex = '1040';
        element.style.marginTop = '56px';
        element.style.height = 'calc(100vh - 96px)';
        element.style.overflow = 'auto';
        this.activeElement = element;
        document.body.style.overflow = 'hidden';
        
        document.addEventListener('keydown', this._escHandler);
    },

    exit() {
        if (this.activeElement) {
            this.activeElement.classList.remove('position-fixed', 'top-0', 'start-0', 'w-100', 'h-100', 'bg-white');
            this.activeElement.style.zIndex = '';
            this.activeElement.style.marginTop = '';
            this.activeElement.style.height = '';
            this.activeElement.style.overflow = '';
            this.activeElement = null;
            document.body.style.overflow = '';
            document.removeEventListener('keydown', this._escHandler);
        }
    },

    _escHandler(e) {
        if (e.key === 'Escape') {
            NbtFullscreen.exit();
        }
    }
};

// =============================================
// LIST TOOLBAR KOMPONENTİ (Bootstrap)
// =============================================
const NbtListToolbar = {
    create(options = {}) {
        return `
            <div class="d-flex align-items-center gap-2 p-2 bg-light border-bottom">
                <div class="input-group input-group-sm" style="max-width:280px;">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control border-start-0" data-toolbar="search"
                           placeholder="${options.placeholder || 'Ara...'}" />
                </div>
                ${options.onFilter ? `
                <button type="button" class="btn btn-outline-secondary btn-sm" data-toolbar="filter" title="Filtrele">
                    <i class="bi bi-funnel"></i>
                </button>` : ''}
                ${options.onAdd ? `
                <button type="button" class="btn btn-primary btn-sm" data-toolbar="add" title="Yeni Ekle">
                    <i class="bi bi-plus-lg"></i>
                </button>` : ''}
                <button type="button" class="btn btn-outline-secondary btn-sm ms-auto" data-toolbar="fullscreen" title="Tam Ekran">
                    <i class="bi bi-arrows-fullscreen"></i>
                </button>
            </div>
        `;
    },

    bind(container, options = {}) {
        const searchInput = container.querySelector('[data-toolbar="search"]');
        const filterBtn = container.querySelector('[data-toolbar="filter"]');
        const addBtn = container.querySelector('[data-toolbar="add"]');
        const fullscreenBtn = container.querySelector('[data-toolbar="fullscreen"]');

        if (searchInput && options.onSearch) {
            searchInput.addEventListener('input', NbtUtils.debounce((e) => {
                options.onSearch(e.target.value);
            }, 300));
        }

        if (filterBtn && options.onFilter) {
            filterBtn.addEventListener('click', () => options.onFilter());
        }

        if (addBtn && options.onAdd) {
            addBtn.addEventListener('click', () => options.onAdd());
        }

        if (fullscreenBtn && options.panelElement) {
            fullscreenBtn.addEventListener('click', () => {
                NbtFullscreen.toggle(options.panelElement);
                const icon = fullscreenBtn.querySelector('i');
                if (icon) {
                    icon.className = NbtFullscreen.activeElement ? 'bi bi-fullscreen-exit' : 'bi bi-arrows-fullscreen';
                }
            });
        }
    }
};

// =============================================
// DATA TABLE KOMPONENTİ (Bootstrap)
// =============================================
const NbtDataTable = {
    create(columns, data, options = {}) {
        if (!data || data.length === 0) {
            return this.emptyState(options.emptyMessage || 'Kayıt bulunamadı');
        }

        let html = `
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            ${columns.map(col => `<th class="fw-semibold text-nowrap">${col.label}</th>`).join('')}
                            ${options.actions ? '<th style="width:80px;" class="text-center">İşlem</th>' : ''}
                        </tr>
                    </thead>
                    <tbody>
        `;

        data.forEach(row => {
            html += '<tr>';
            columns.forEach(col => {
                let value = row[col.field];
                if (col.render) {
                    value = col.render(value, row);
                } else {
                    value = NbtUtils.escapeHtml(value);
                }
                html += `<td class="align-middle">${value ?? '-'}</td>`;
            });

            if (options.actions) {
                html += `
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            ${options.actions.view !== false ? `
                            <button type="button" class="btn btn-outline-primary btn-sm" data-action="view" data-id="${row.Id}" title="Detay">
                                <i class="bi bi-eye"></i>
                            </button>` : ''}
                            ${options.actions.edit !== false ? `
                            <button type="button" class="btn btn-outline-warning btn-sm" data-action="edit" data-id="${row.Id}" title="Düzenle">
                                <i class="bi bi-pencil"></i>
                            </button>` : ''}
                            ${options.actions.delete !== false ? `
                            <button type="button" class="btn btn-outline-danger btn-sm" data-action="delete" data-id="${row.Id}" title="Sil">
                                <i class="bi bi-trash"></i>
                            </button>` : ''}
                        </div>
                    </td>
                `;
            }
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        return html;
    },

    emptyState(message) {
        return `
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                <p class="mb-0">${NbtUtils.escapeHtml(message)}</p>
            </div>
        `;
    },

    // Store bound handlers to prevent duplicate binding
    _handlers: new WeakMap(),

    bind(container, options = {}) {
        // Remove existing handler if any (prevent duplicate binding)
        if (this._handlers.has(container)) {
            container.removeEventListener('click', this._handlers.get(container));
        }

        // Create new handler
        const handler = (e) => {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;

            const action = btn.dataset.action;
            const id = parseInt(btn.dataset.id);

            if (action === 'view' && options.onView) {
                options.onView(id);
            } else if (action === 'edit' && options.onEdit) {
                options.onEdit(id);
            } else if (action === 'delete' && options.onDelete) {
                options.onDelete(id);
            }
        };

        // Store and bind handler
        this._handlers.set(container, handler);
        container.addEventListener('click', handler);
    }
};

// =============================================
// MODAL KOMPONENTİ
// =============================================
const NbtModal = {
    instances: {},

    /**
     * Modal aç
     */
    open(id) {
        const modal = document.getElementById(id);
        if (!modal) {
            // Modal bulunamadı - graceful fallback
            if (typeof NbtToast !== 'undefined') {
                NbtToast.warning(`Modal bulunamadı: ${id}`);
            }
            return false;
        }
        if (window.bootstrap) {
            if (!this.instances[id]) {
                this.instances[id] = new bootstrap.Modal(modal);
            }
            this.instances[id].show();
            return true;
        }
        return false;
    },

    /**
     * Modal kapat
     */
    close(id) {
        if (this.instances[id]) {
            this.instances[id].hide();
        }
    },

    /**
     * Modal hata göster
     */
    showError(modalId, message) {
        const errorEl = document.querySelector(`#${modalId} [id$="ModalError"], #${modalId} .modal-error`);
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.remove('d-none');
        }
    },

    /**
     * Modal hata temizle
     */
    clearError(modalId) {
        const errorEl = document.querySelector(`#${modalId} [id$="ModalError"], #${modalId} .modal-error`);
        if (errorEl) {
            errorEl.classList.add('d-none');
            errorEl.textContent = '';
        }
    },

    /**
     * Form sıfırla
     */
    resetForm(modalId) {
        const form = document.querySelector(`#${modalId} form`);
        if (form) {
            form.reset();
        }
        this.clearError(modalId);
    }
};

// =============================================
// ROUTER - SPA Navigation
// =============================================
const NbtRouter = {
    routes: {},
    currentView: null,
    defaultRoute: 'dashboard',

    /**
     * Route tanımla
     */
    register(path, handler) {
        this.routes[path] = handler;
    },

    /**
     * Sayfaya git
     */
    navigate(path, params = {}) {
        // View container'ları gizle
        document.querySelectorAll('[id^="view-"]').forEach(el => {
            el.classList.add('d-none');
        });

        const handler = this.routes[path];
        if (handler) {
            handler(params);
            this.currentView = path;
            
            // Navbar aktif durumu güncelle
            this.updateNavbar(path);
        } else {
            console.warn('Route bulunamadı:', path);
            if (this.routes[this.defaultRoute]) {
                this.navigate(this.defaultRoute);
            }
        }
    },

    /**
     * Navbar aktif durumunu güncelle - tek kaynak üzerinden
     */
    updateNavbar(path) {
        const navbar = document.getElementById('mainNav');
        if (!navbar) return;

        // Tüm active class'ları temizle
        navbar.querySelectorAll('.nav-link.active, .dropdown-item.active').forEach(el => {
            el.classList.remove('active');
        });

        // Route'a karşılık gelen linki bul
        let activeLink = navbar.querySelector(`[data-route="${path}"]`);
        
        // customer/123 gibi nested route'lar için customers grubunu active yap
        if (!activeLink && path === 'customer') {
            activeLink = navbar.querySelector('[data-route="customers"]');
        }

        if (activeLink) {
            // Dropdown item ise hem kendini hem parent dropdown toggle'ı active yap
            if (activeLink.classList.contains('dropdown-item')) {
                activeLink.classList.add('active');
                const parentDropdown = activeLink.closest('.nav-item.dropdown');
                if (parentDropdown) {
                    parentDropdown.querySelector('.nav-link.dropdown-toggle')?.classList.add('active');
                }
            } else {
                activeLink.classList.add('active');
            }
        }
    },

    /**
     * URL hash'ten route başlat
     */
    init() {
        const hash = window.location.hash.slice(1) || this.defaultRoute;
        this.navigate(hash);

        window.addEventListener('hashchange', () => {
            const hash = window.location.hash.slice(1) || this.defaultRoute;
            this.navigate(hash);
        });
    }
};

// =============================================
// TAKVİM KOMPONENTİ (Profesyonel Haftalık/Aylık)
// =============================================
const NbtCalendar = {
    currentDate: new Date(),
    viewMode: 'month', // 'month' veya 'week'
    events: [],
    onEventClick: null,
    onDayClick: null,
    container: null,

    render(container, options = {}) {
        this.container = container;
        if (options.events) this.events = options.events;
        if (options.onEventClick) this.onEventClick = options.onEventClick;
        if (options.onDayClick) this.onDayClick = options.onDayClick;
        if (options.viewMode) this.viewMode = options.viewMode;

        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth();
        const today = new Date();
        
        const monthNames = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran',
                           'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
        const dayNames = ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
        const dayNamesShort = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];

        let headerTitle = '';
        if (this.viewMode === 'week') {
            const weekStart = this._getWeekStart(this.currentDate);
            const weekEnd = new Date(weekStart);
            weekEnd.setDate(weekEnd.getDate() + 6);
            headerTitle = `${weekStart.getDate()} ${monthNames[weekStart.getMonth()]} - ${weekEnd.getDate()} ${monthNames[weekEnd.getMonth()]} ${year}`;
        } else {
            headerTitle = `${monthNames[month]} ${year}`;
        }

        let html = `
            <div class="border rounded bg-white">
                <div class="d-flex justify-content-between align-items-center p-2 bg-light border-bottom">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" data-calendar="prev">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-calendar="today">Bugün</button>
                        <button type="button" class="btn btn-outline-secondary" data-calendar="next">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                    <strong class="text-primary">${headerTitle}</strong>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn ${this.viewMode === 'month' ? 'btn-primary' : 'btn-outline-primary'}" data-calendar="view-month">Ay</button>
                        <button type="button" class="btn ${this.viewMode === 'week' ? 'btn-primary' : 'btn-outline-primary'}" data-calendar="view-week">Hafta</button>
                    </div>
                </div>
        `;

        if (this.viewMode === 'week') {
            html += this._renderWeekView(dayNames, today);
        } else {
            html += this._renderMonthView(dayNamesShort, today, year, month);
        }

        html += '</div>';
        container.innerHTML = html;
        this._bindEvents(container);
    },

    _renderMonthView(dayNames, today, year, month) {
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const startDay = firstDay.getDay() || 7;

        let html = `<div class="d-grid" style="grid-template-columns: repeat(7, 1fr);">`;
        
        // Gün başlıkları
        dayNames.forEach(d => {
            html += `<div class="text-center small text-muted py-1 border-bottom fw-semibold">${d}</div>`;
        });

        // Önceki ayın günleri
        const prevMonthDays = new Date(year, month, 0).getDate();
        for (let i = startDay - 2; i >= 0; i--) {
            html += `<div class="text-center text-muted p-1 border-bottom border-end opacity-50" style="min-height:60px;"><small>${prevMonthDays - i}</small></div>`;
        }

        // Bu ayın günleri
        for (let day = 1; day <= lastDay.getDate(); day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const isToday = today.getDate() === day && today.getMonth() === month && today.getFullYear() === year;
            const dayEvents = this.events.filter(e => e.date === dateStr && !e.completed);
            
            let bgClass = isToday ? 'bg-primary-subtle' : 'bg-white';
            
            html += `
                <div class="p-1 border-bottom border-end ${bgClass} position-relative" style="min-height:60px;cursor:pointer;" data-date="${dateStr}">
                    <small class="${isToday ? 'badge bg-primary rounded-circle' : ''}">${day}</small>
                    <div class="position-absolute start-0 end-0 px-1" style="top:22px;">
            `;
            
            dayEvents.slice(0, 2).forEach(event => {
                const typeColor = this._getEventColor(event.type);
                html += `<div class="text-truncate small px-1 mb-1 rounded ${typeColor}" style="font-size:10px;" title="${NbtUtils.escapeHtml(event.title)}" data-event-id="${event.id}">${NbtUtils.escapeHtml(event.title)}</div>`;
            });
            
            if (dayEvents.length > 2) {
                html += `<div class="text-muted small" style="font-size:10px;">+${dayEvents.length - 2} daha</div>`;
            }
            
            html += `</div></div>`;
        }

        // Sonraki ayın günleri
        const remainingDays = 42 - (startDay - 1 + lastDay.getDate());
        for (let i = 1; i <= remainingDays && remainingDays < 14; i++) {
            html += `<div class="text-center text-muted p-1 border-bottom border-end opacity-50" style="min-height:60px;"><small>${i}</small></div>`;
        }

        html += '</div>';
        return html;
    },

    _renderWeekView(dayNames, today) {
        const weekStart = this._getWeekStart(this.currentDate);
        const hours = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'];

        let html = `<div class="d-grid" style="grid-template-columns: 60px repeat(7, 1fr);">`;
        
        // Gün başlıkları
        html += `<div class="border-end border-bottom"></div>`;
        for (let i = 0; i < 7; i++) {
            const d = new Date(weekStart);
            d.setDate(d.getDate() + i);
            const isToday = d.toDateString() === today.toDateString();
            const dateStr = this._formatDate(d);
            html += `
                <div class="text-center py-2 border-bottom ${isToday ? 'bg-primary-subtle' : ''}" data-date="${dateStr}">
                    <div class="small text-muted">${dayNames[i]}</div>
                    <div class="${isToday ? 'badge bg-primary' : 'fw-semibold'}">${d.getDate()}</div>
                </div>
            `;
        }

        // Saat satırları
        hours.forEach(hour => {
            html += `<div class="text-end pe-2 small text-muted border-end py-2">${hour}</div>`;
            for (let i = 0; i < 7; i++) {
                const d = new Date(weekStart);
                d.setDate(d.getDate() + i);
                const dateStr = this._formatDate(d);
                const hourEvents = this.events.filter(e => e.date === dateStr && e.time && e.time.startsWith(hour.split(':')[0]));
                
                html += `<div class="border-end border-bottom p-1" style="min-height:40px;" data-date="${dateStr}" data-hour="${hour}">`;
                hourEvents.forEach(event => {
                    const typeColor = this._getEventColor(event.type);
                    html += `<div class="text-truncate small px-1 rounded ${typeColor}" style="font-size:10px;" title="${NbtUtils.escapeHtml(event.title)}" data-event-id="${event.id}">${NbtUtils.escapeHtml(event.title)}</div>`;
                });
                html += `</div>`;
            }
        });

        html += '</div>';
        return html;
    },

    _getWeekStart(date) {
        const d = new Date(date);
        const day = d.getDay() || 7;
        d.setDate(d.getDate() - day + 1);
        return d;
    },

    _formatDate(date) {
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    },

    _getEventColor(type) {
        const colors = {
            'fatura': 'bg-danger-subtle text-danger',
            'odeme': 'bg-success-subtle text-success',
            'teklif': 'bg-warning-subtle text-warning',
            'sozlesme': 'bg-info-subtle text-info',
            'teminat': 'bg-secondary-subtle text-secondary',
            'default': 'bg-primary-subtle text-primary'
        };
        return colors[type] || colors.default;
    },

    _bindEvents(container) {
        container.addEventListener('click', (e) => {
            const prevBtn = e.target.closest('[data-calendar="prev"]');
            const nextBtn = e.target.closest('[data-calendar="next"]');
            const todayBtn = e.target.closest('[data-calendar="today"]');
            const monthBtn = e.target.closest('[data-calendar="view-month"]');
            const weekBtn = e.target.closest('[data-calendar="view-week"]');
            const dayEl = e.target.closest('[data-date]');
            const eventEl = e.target.closest('[data-event-id]');

            if (prevBtn) {
                if (this.viewMode === 'week') {
                    this.currentDate.setDate(this.currentDate.getDate() - 7);
                } else {
                    this.currentDate.setMonth(this.currentDate.getMonth() - 1);
                }
                this.render(container, { events: this.events });
            } else if (nextBtn) {
                if (this.viewMode === 'week') {
                    this.currentDate.setDate(this.currentDate.getDate() + 7);
                } else {
                    this.currentDate.setMonth(this.currentDate.getMonth() + 1);
                }
                this.render(container, { events: this.events });
            } else if (todayBtn) {
                this.currentDate = new Date();
                this.render(container, { events: this.events });
            } else if (monthBtn) {
                this.viewMode = 'month';
                this.render(container, { events: this.events });
            } else if (weekBtn) {
                this.viewMode = 'week';
                this.render(container, { events: this.events });
            } else if (eventEl && this.onEventClick) {
                const eventId = parseInt(eventEl.dataset.eventId);
                const event = this.events.find(e => e.id === eventId);
                if (event) this.onEventClick(event);
            } else if (dayEl && this.onDayClick) {
                const date = dayEl.dataset.date;
                const dayEvents = this.events.filter(e => e.date === date && !e.completed);
                this.onDayClick(date, dayEvents);
            }
        });
    },

    async loadEvents(customerId = null) {
        try {
            let url = '/api/calendar';
            if (customerId) {
                url += `?customerId=${customerId}`;
            }
            const response = await NbtApi.get(url);
            this.events = (response.data || []).filter(e => !e.completed);
            return this.events;
        } catch (err) {
            console.error('Takvim eventi yüklenemedi:', err);
            return [];
        }
    }
};

// =============================================
// GLOBAL INIT
// =============================================
document.addEventListener('DOMContentLoaded', () => {
    // Auth kontrolü
    if (!NbtUtils.getToken() && !window.location.pathname.includes('login') && !window.location.pathname.includes('register')) {
        window.location.href = '/login';
        return;
    }

    // Footer tarih güncelleme
    const footerDateTime = document.getElementById('footerDateTime');
    if (footerDateTime) {
        const updateTime = () => {
            const now = new Date();
            footerDateTime.textContent = now.toLocaleDateString('tr-TR') + ' ' + now.toLocaleTimeString('tr-TR');
        };
        updateTime();
        setInterval(updateTime, 1000);
    }

    // Footer IP
    const footerIp = document.getElementById('footerIp');
    if (footerIp) {
        footerIp.textContent = 'IP: ' + (window.location.hostname || 'localhost');
    }

    // Kullanıcı adı
    const user = NbtUtils.getUser();
    const userNameEl = document.getElementById('userNameDisplay');
    if (userNameEl && user?.name) {
        userNameEl.textContent = user.name;
    }
    const footerUser = document.getElementById('footerUser');
    if (footerUser && user?.name) {
        footerUser.textContent = 'Kullanıcı: ' + user.name;
    }
});

// Export for global access
window.NBT = NBT;
window.NbtUtils = NbtUtils;
window.NbtApi = NbtApi;
window.NbtToast = NbtToast;
window.NbtFullscreen = NbtFullscreen;
window.NbtListToolbar = NbtListToolbar;
window.NbtDataTable = NbtDataTable;
window.NbtModal = NbtModal;
window.NbtRouter = NbtRouter;
window.NbtCalendar = NbtCalendar;
