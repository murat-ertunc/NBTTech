/**
 * NbtProject - Ana JavaScript Modulu
 * ===================================
 * Bu modul, uygulama genelinde kullanilan ortak fonksiyonlari,
 * API yardimcilarini, Toast bildirimlerini ve Fullscreen islemlerini icerir.
 * Tum sayfalarda yuklenecek sekilde tasarlanmistir.
 */

// =============================================
// GLOBAL SABITLER - Uygulama genelinde kullanilan sabit degerler
// =============================================
const NBT = {
    TOKEN_KEY: 'nbt_token',
    ROLE_KEY: 'nbt_role',
    USER_KEY: 'nbt_user',
    TAB_KEY: 'nbt_tab_id',
    API_BASE: '',
    DEBUG: false
};

// =============================================
// LOGLAMA ARACI - Hata ve debug mesajlarini yonetir
// =============================================
const NbtLogger = {
    log(...args) {
        if (NBT.DEBUG) console.log('[NBT]', ...args);
    },
    warn(...args) {
        if (NBT.DEBUG) console.warn('[NBT]', ...args);
    },
    error(...args) {
        // Error'lar her zaman loglanir
        console.error('[NBT]', ...args);
    }
};

// =============================================
// UTILITY FUNCTIONS
// =============================================
const NbtUtils = {
    /**
     * Sekme ID'si al/olustur
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
     * Kullanici bilgisi al
     */
    getUser() {
        try {
            return JSON.parse(localStorage.getItem(NBT.USER_KEY));
        } catch {
            return null;
        }
    },

    /**
     * Oturum ac
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
     * XSS korumasi - HTML escape
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
        // NbtParams cache'den sembol al, yoksa default
        const symbols = NbtParams.getCurrencySymbols();
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
    },

    /**
     * Ondalik sayi formatlama - basindaki 0'i korur (0.34, 0.65 vb.)
     * Input'lara deger yuklerken kullanilir
     */
    formatDecimal(value, decimals = 2) {
        if (value === null || value === undefined || value === '') return '';
        const num = parseFloat(value);
        if (isNaN(num)) return '';
        return num.toFixed(decimals);
    },

    /**
     * Turkce karakter normalizasyonu - buyuk/kucuk harf ve Turkce karakter duyarsiz arama icin
     */
    normalizeText(str) {
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
    },

    /**
     * Tarih karsilastirma icin YYYY-MM-DD formatina cevir
     */
    formatDateForCompare(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return '';
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    },

    /**
     * Sayi formatlama (para birimi olmadan)
     */
    formatNumber(amount) {
        const num = parseFloat(amount) || 0;
        return num.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
};

// =============================================
// PARAMETRE YONETICISI
// =============================================
const NbtParams = {
    _cache: {
        currencies: null,
        statuses: {},
        settings: null,
        lastFetch: 0
    },
    
    CACHE_TTL: 5 * 60 * 1000,

    /**
     * Aktif doviz turlerini getir
     */
    async getCurrencies(forceRefresh = false) {
        if (!forceRefresh && this._cache.currencies && Date.now() - this._cache.lastFetch < this.CACHE_TTL) {
            return this._cache.currencies;
        }
        try {
            const response = await NbtApi.get('/api/parameters/currencies');
            this._cache.currencies = response.data || [];
            this._cache.lastFetch = Date.now();
            return this._cache.currencies;
        } catch (err) {
            NbtLogger.error('Doviz parametreleri alinamadi:', err);
            // Fallback - varsayilan dovizler
            return [
                { Kod: 'TRY', Etiket: 'Türk Lirası', Deger: '₺', Varsayilan: true },
                { Kod: 'USD', Etiket: 'Amerikan Doları', Deger: '$', Varsayilan: false },
                { Kod: 'EUR', Etiket: 'Euro', Deger: '€', Varsayilan: false }
            ];
        }
    },

    /**
     * Doviz sembollerini obje olarak getir (hizli erisim icin)
     */
    getCurrencySymbols() {
        // Senkron erisim icin cache'den al, yoksa default
        if (this._cache.currencies) {
            const symbols = {};
            this._cache.currencies.forEach(c => symbols[c.Kod] = c.Deger);
            return symbols;
        }
        return { TRY: '₺', USD: '$', EUR: '€', GBP: '£', TL: '₺' };
    },

    /**
     * Varsayilan doviz kodunu getir
     */
    getDefaultCurrency() {
        if (this._cache.currencies) {
            const def = this._cache.currencies.find(c => c.Varsayilan);
            return def ? def.Kod : 'TRY';
        }
        return 'TRY';
    },

    /**
     * Durum parametrelerini getir
     * @param entity proje|teklif|sozlesme|teminat
     */
    async getStatuses(entity, forceRefresh = false) {
        const cacheKey = `durum_${entity}`;
        if (!forceRefresh && this._cache.statuses[cacheKey]) {
            return this._cache.statuses[cacheKey];
        }
        try {
            const response = await NbtApi.get(`/api/parameters/statuses?entity=${entity}`);
            this._cache.statuses[cacheKey] = response.data || [];
            return this._cache.statuses[cacheKey];
        } catch (err) {
            NbtLogger.error(`Durum parametreleri alinamadi (${entity}):`, err);
            return [];
        }
    },

    /**
     * Durum badge HTML'i olustur
     * @param entity proje|teklif|sozlesme|teminat
     * @param kod Durum kodu (1, 2, 3 vb.)
     */
    getStatusBadge(entity, kod) {
        const cacheKey = `durum_${entity}`;
        const statuses = this._cache.statuses[cacheKey] || [];
        const status = statuses.find(s => s.Kod == kod);
        if (status) {
            return `<span class="badge bg-${status.Deger}">${NbtUtils.escapeHtml(status.Etiket)}</span>`;
        }
        return `<span class="badge bg-secondary">${kod}</span>`;
    },

    /**
     * Genel ayarlari getir
     */
    async getSettings(forceRefresh = false) {
        if (!forceRefresh && this._cache.settings && Date.now() - this._cache.lastFetch < this.CACHE_TTL) {
            return this._cache.settings;
        }
        try {
            const response = await NbtApi.get('/api/parameters/settings');
            // Backend response root'da paginationDefault olarak gonderiyor
            this._cache.settings = {
                pagination_default: response.paginationDefault || 25,
                default_currency: response.defaultCurrency || 'TRY',
                active_currencies: response.activeCurrencies || ['TRY', 'USD', 'EUR']
            };
            return this._cache.settings;
        } catch (err) {
            NbtLogger.error('Genel ayarlar alinamadi:', err);
            return { pagination_default: 25 };
        }
    },

    /**
     * Sayfalama varsayilan degerini getir
     */
    getPaginationDefault() {
        if (this._cache.settings && this._cache.settings.pagination_default) {
            return parseInt(this._cache.settings.pagination_default);
        }
        return window.APP_CONFIG?.PAGINATION_DEFAULT || 25;
    },

    /**
     * Select element'ine doviz seceneklerini doldur
     */
    async populateCurrencySelect(selectElement, selectedValue = null) {
        if (!selectElement) return;
        const currencies = await this.getCurrencies();
        selectElement.innerHTML = '';
        currencies.forEach(c => {
            const option = document.createElement('option');
            option.value = c.Kod;
            option.textContent = `${c.Kod} (${c.Deger})`;
            if (selectedValue ? c.Kod === selectedValue : c.Varsayilan) {
                option.selected = true;
            }
            selectElement.appendChild(option);
        });
    },

    /**
     * Select element'ine durum seceneklerini doldur
     */
    async populateStatusSelect(selectElement, entity, selectedValue = null) {
        if (!selectElement) return;
        // Her zaman cache'i yenile (forceRefresh=true)
        const statuses = await this.getStatuses(entity, true);
        selectElement.innerHTML = '';
        if (!statuses || statuses.length === 0) {
            selectElement.innerHTML = '<option value="">Durum bulunamadi</option>';
            return;
        }
        statuses.forEach(s => {
            const option = document.createElement('option');
            option.value = s.Kod;
            option.textContent = s.Etiket;
            if (selectedValue !== null ? s.Kod == selectedValue : s.Varsayilan) {
                option.selected = true;
            }
            selectElement.appendChild(option);
        });
    },

    /**
     * Tum cache'i sifirla
     */
    clearCache() {
        this._cache = { currencies: null, statuses: {}, settings: null, lastFetch: 0 };
    },

    /**
     * Baslangicta parametreleri onyukle
     */
    async preload() {
        try {
            await Promise.all([
                this.getCurrencies(),
                this.getSettings(),
                this.getStatuses('proje'),
                this.getStatuses('teklif'),
                this.getStatuses('sozlesme'),
                this.getStatuses('teminat')
            ]);
            NbtLogger.log('Parametreler onyuklendi');
        } catch (err) {
            NbtLogger.error('Parametre onyukleme hatasi:', err);
        }
    }
};

// =============================================
// API ISTEK YARDIMCISI
// =============================================
const NbtApi = {
    /**
     * API istegi yap
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
                throw new Error('Oturum suresi doldu');
            }
            if (response.status === 403) {
                throw new Error(data.error || 'Bu islem icin yetkiniz yok');
            }
            if (response.status === 404) {
                throw new Error(data.error || 'Kayit bulunamadi');
            }
            if (response.status === 422) {
                throw new Error(data.error || 'Validasyon hatasi');
            }
            if (response.status >= 500) {
                throw new Error(data.error || 'Sunucu hatasi');
            }
            throw new Error(data.error || 'Bir hata olustu');
        }

        return data;
    },

    get: (path) => NbtApi.request(path),
    post: (path, data) => NbtApi.request(path, { method: 'POST', body: JSON.stringify(data) }),
    put: (path, data) => NbtApi.request(path, { method: 'PUT', body: JSON.stringify(data) }),
    delete: (path) => NbtApi.request(path, { method: 'DELETE' })
};

// =============================================
// TOAST BILDIRIMLERI (Bootstrap Toast)
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
// LIST TOOLBAR KOMPONENTI (Bootstrap)
// =============================================
const NbtListToolbar = {
    create(options = {}) {
        const searchHtml = options.onSearch !== false ? `
                <div class="input-group input-group-sm" style="max-width:280px;">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control border-start-0" data-toolbar="search"
                           placeholder="${options.placeholder || 'Ara...'}" />
                </div>` : '';
        
        return `
            <div class="d-flex align-items-center gap-2 p-2 bg-light border-bottom">
                ${searchHtml}
                ${options.onFilter ? `
                <button type="button" class="btn btn-outline-secondary btn-sm" data-toolbar="filter" title="Filtrele">
                    <i class="bi bi-funnel"></i>
                </button>` : ''}
                ${options.onAdd ? `
                <button type="button" class="btn btn-primary btn-sm" data-toolbar="add" title="Yeni Ekle">
                    <i class="bi bi-plus-lg"></i>
                </button>` : ''}
            </div>
        `;
    },

    _boundContainers: new WeakSet(),

    bind(container, options = {}) {
        if (this._boundContainers.has(container)) return;
        this._boundContainers.add(container);

        const searchInput = container.querySelector('[data-toolbar="search"]');
        const filterBtn = container.querySelector('[data-toolbar="filter"]');
        const addBtn = container.querySelector('[data-toolbar="add"]');

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
    }
};

// =============================================
// DATA TABLE KOMPONENTI (Bootstrap)
// =============================================
const NbtDataTable = {
    create(columns, data, options = {}) {
        if (!data || data.length === 0) {
            return this.emptyState(options.emptyMessage || 'Kayıt bulunamadı');
        }

        let html = `
            <div class="table-responsive px-2 py-2">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            ${columns.map(col => `<th class="fw-semibold text-nowrap px-3">${col.label}</th>`).join('')}
                            ${options.actions ? '<th style="width:110px;" class="text-center px-3">İşlem</th>' : ''}
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
                html += `<td class="align-middle px-3">${value ?? '-'}</td>`;
            });

            if (options.actions) {
                html += `
                    <td class="text-center px-3">
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

    _handlers: new WeakMap(),

    bind(container, options = {}) {
        if (this._handlers.has(container)) {
            container.removeEventListener('click', this._handlers.get(container));
        }

        const handler = (e) => {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;

            const action = btn.dataset.action;
            const rawId = btn.dataset.id;
            const id = parseInt(rawId, 10);
            
            if (isNaN(id) || id <= 0) {
                return;
            }

            if (action === 'view' && options.onView) {
                options.onView(id);
            } else if (action === 'edit' && options.onEdit) {
                options.onEdit(id);
            } else if (action === 'delete' && options.onDelete) {
                options.onDelete(id);
            }
        };

        this._handlers.set(container, handler);
        container.addEventListener('click', handler);
    }
};

// =============================================
// MODAL KOMPONENTI
// =============================================
const NbtModal = {
    instances: {},

    /**
     * Modal ac
     */
    open(id) {
        const modal = document.getElementById(id);
        if (!modal) {
            if (typeof NbtToast !== 'undefined') {
                NbtToast.warning(`Modal bulunamadi: ${id}`);
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
     * Modal hata goster
     */
    showError(modalId, message) {
        const errorEl = document.querySelector(`#${modalId} [id$="ModalError"], #${modalId} .modal-error`);
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.remove('d-none');
        }
    },

    /**
     * Field-level hata goster (Bootstrap is-invalid class)
     */
    showFieldError(modalId, fieldId, message) {
        const field = document.querySelector(`#${modalId} #${fieldId}`);
        if (field) {
            field.classList.add('is-invalid');
            let feedback = field.parentElement.querySelector('.invalid-feedback');
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                field.parentElement.appendChild(feedback);
            }
            feedback.textContent = message;
        }
    },

    /**
     * Tum field-level hatalari temizle
     */
    clearFieldErrors(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        modal.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        modal.querySelectorAll('.invalid-feedback').forEach(el => {
            el.textContent = '';
        });
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
        this.clearFieldErrors(modalId);
    },

    /**
     * Form sifirla - hidden id alanlari haric tum inputlari temizle
     */
    resetForm(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        const preserveFields = {};
        modal.querySelectorAll('input[type="hidden"]').forEach(el => {
            const elIdLower = el.id.toLowerCase();
            const hasPreserveAttr = el.hasAttribute('data-preserve-value');
            
            if (hasPreserveAttr) {
                preserveFields[el.id] = el.getAttribute('data-preserve-value');
            } else if (elIdLower.includes('musteri') && elIdLower.includes('id')) {
                preserveFields[el.id] = el.value;
            }
        });
        
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
        } else {
            modal.querySelectorAll('input:not([type="hidden"]), textarea, select').forEach(el => {
                if (el.type === 'checkbox' || el.type === 'radio') {
                    el.checked = false;
                } else if (el.tagName === 'SELECT') {
                    el.selectedIndex = 0;
                } else {
                    el.value = '';
                }
            });
            modal.querySelectorAll('input[type="hidden"][id$="Id"]').forEach(el => {
                const elIdLower = el.id.toLowerCase();
                if (el.id === modalId.replace('Modal', 'Id') || (el.id.endsWith('Id') && !elIdLower.includes('musteri'))) {
                    el.value = '';
                }
            });
        }
        
        Object.keys(preserveFields).forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field && preserveFields[fieldId]) {
                field.value = preserveFields[fieldId];
            }
        });
        
        this.clearError(modalId);
    },
    
    /**
     * Save butonunu disable/enable et + spinner goster
     */
    setLoading(modalId, loading) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        const btn = modal.querySelector('[id^="btnSave"], .btn-primary');
        if (!btn) return;
        
        if (loading) {
            btn.disabled = true;
            btn._originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Kaydediliyor...';
        } else {
            btn.disabled = false;
            if (btn._originalHtml) {
                btn.innerHTML = btn._originalHtml;
            }
        }
    }
};

// =============================================
// DETAY MODAL KOMPONENTI (Read-Only Goruntuleme)
// =============================================
const NbtDetailModal = {
    _currentEntity: null,
    _currentId: null,
    _currentData: null,
    _onEdit: null,
    _onDelete: null,

    /**
     * Entity detaylarini modal'da goster
     * @param {string} entityType - Entity tipi (invoice, payment, project, vb.)
     * @param {object} data - Gosterilecek veri
     * @param {function} onEdit - Duzenle butonuna basilinca cagrilacak fonksiyon
     * @param {function} onDelete - Sil butonuna basilinca cagrilacak fonksiyon
     */
    async show(entityType, data, onEdit = null, onDelete = null) {
        // Durum parametrelerini onceden yukle (proje, teklif, sozlesme, teminat icin)
        const statusEntityMap = {
            project: 'proje',
            offer: 'teklif',
            contract: 'sozlesme',
            guarantee: 'teminat'
        };
        if (statusEntityMap[entityType]) {
            await NbtParams.getStatuses(statusEntityMap[entityType]);
        }
        
        this._currentEntity = entityType;
        this._currentId = data.Id;
        this._currentData = data;
        this._onEdit = onEdit;
        this._onDelete = onDelete;

        const titles = {
            customer: 'Müşteri Detayı',
            invoice: 'Fatura Detayı',
            payment: 'Ödeme Detayı',
            project: 'Proje Detayı',
            offer: 'Teklif Detayı',
            contract: 'Sözleşme Detayı',
            guarantee: 'Teminat Detayı',
            meeting: 'Görüşme Detayı',
            contact: 'Kişi Detayı',
            stampTax: 'Damga Vergisi Detayı',
            file: 'Dosya Detayı',
            calendar: 'Takvim Detayı'
        };

        const icons = {
            customer: 'bi-building',
            invoice: 'bi-receipt',
            payment: 'bi-cash-stack',
            project: 'bi-kanban',
            offer: 'bi-file-earmark-text',
            contract: 'bi-file-text',
            guarantee: 'bi-shield-check',
            meeting: 'bi-chat-dots',
            contact: 'bi-person',
            stampTax: 'bi-percent',
            file: 'bi-folder',
            calendar: 'bi-calendar3'
        };

        const titleEl = document.getElementById('entityDetailModalTitle');
        if (titleEl) {
            titleEl.innerHTML = `<i class="bi ${icons[entityType] || 'bi-eye'} me-2"></i>${titles[entityType] || 'Detay'}`;
        }

        // Icerik olusturma
        const bodyEl = document.getElementById('entityDetailModalBody');
        if (bodyEl) {
            bodyEl.innerHTML = this._buildContent(entityType, data);
        }

        const editBtn = document.getElementById('btnEntityDetailEdit');
        if (editBtn) {
            if (onEdit) {
                editBtn.classList.remove('d-none');
                editBtn.onclick = () => {
                    NbtModal.close('entityDetailModal');
                    onEdit(this._currentId);
                };
            } else {
                editBtn.classList.add('d-none');
            }
        }

        const pageBtn = document.getElementById('btnEntityDetailPage');
        if (pageBtn) {
            if (entityType === 'customer') {
                pageBtn.classList.remove('d-none');
                pageBtn.onclick = () => {
                    NbtModal.close('entityDetailModal');
                    // Server-rendered navigation: gercek sayfa yuklemesi
                    window.location.href = `/customer/${this._currentId}`;
                };
            } else {
                pageBtn.classList.add('d-none');
            }
        }

        const deleteBtn = document.getElementById('btnEntityDetailDelete');
        if (deleteBtn) {
            if (onDelete) {
                deleteBtn.classList.remove('d-none');
                deleteBtn.onclick = () => {
                    onDelete(this._currentId, this._currentData);
                };
            } else {
                deleteBtn.classList.add('d-none');
            }
        }

        NbtModal.open('entityDetailModal');
    },

    /**
     * Entity tipine gore icerik olustur
     */
    _buildContent(entityType, data) {
        const formatters = {
            date: (v) => NbtUtils.formatDate(v),
            money: (v, currency) => NbtUtils.formatMoney(v, currency || 'TRY')
        };

        const configs = {
            customer: [
                { label: 'ID', field: 'Id' },
                { label: 'Müşteri Kodu', field: 'MusteriKodu', render: (v, d) => v || `MÜŞ-${String(d.Id).padStart(5, '0')}` },
                { label: 'Ünvan', field: 'Unvan' },
                { label: 'Vergi Dairesi', field: 'VergiDairesi' },
                { label: 'Vergi No', field: 'VergiNo' },
                { label: 'Mersis No', field: 'MersisNo' },
                { label: 'Telefon', field: 'Telefon' },
                { label: 'Faks', field: 'Faks' },
                { label: 'Web', field: 'Web', render: (v) => v ? `<a href="${v}" target="_blank">${v}</a>` : '-' },
                { label: 'Adres', field: 'Adres' },
                { label: 'Açıklama', field: 'Aciklama' },
                { label: 'Kayıt Tarihi', field: 'EklemeZamani', format: 'date' }
            ],
            invoice: [
                { label: 'Fatura No', field: 'FaturaNo' },
                { label: 'Müşteri', field: 'MusteriUnvan' },
                { label: 'Tarih', field: 'Tarih', format: 'date' },
                { label: 'Tutar', field: 'Tutar', format: 'money', currencyField: 'DovizCinsi' },
                { label: 'Döviz', field: 'DovizCinsi' },
                { label: 'Şüpheli Alacak', field: 'SupheliAlacak', render: (v) => v ? '<span class="badge bg-warning">Evet</span>' : '<span class="badge bg-secondary">Hayır</span>' },
                { label: 'Tevkifat', field: 'TevkifatAktif', render: (v, d) => {
                    if (!v) return '<span class="badge bg-secondary">Yok</span>';
                    return `<span class="badge bg-info">Oran1: %${d.TevkifatOran1 || 0}, Oran2: %${d.TevkifatOran2 || 0}</span>`;
                }},
                { label: 'Açıklama', field: 'Aciklama' }
            ],
            payment: [
                { label: 'Müşteri', field: 'MusteriUnvan' },
                { label: 'Tarih', field: 'Tarih', format: 'date' },
                { label: 'Tutar', field: 'Tutar', format: 'money' },
                { label: 'Fatura', field: 'FaturaId', render: (v) => v ? `FT${v}` : 'Bağımsız' },
                { label: 'Açıklama', field: 'Aciklama' }
            ],
            project: [
                { label: 'Müşteri', field: 'MusteriUnvan' },
                { label: 'Proje Adı', field: 'ProjeAdi' },
                { label: 'Başlangıç', field: 'BaslangicTarihi', format: 'date' },
                { label: 'Bitiş', field: 'BitisTarihi', format: 'date' },
                { label: 'Durum', field: 'Durum', format: 'status', statusEntity: 'proje' }
            ],
            offer: [
                { label: 'Müşteri', field: 'MusteriUnvan' },
                { label: 'Teklif No', field: 'TeklifNo' },
                { label: 'Konu', field: 'Konu' },
                { label: 'Tutar', field: 'Tutar', format: 'money', currencyField: 'ParaBirimi' },
                { label: 'Teklif Tarihi', field: 'TeklifTarihi', format: 'date' },
                { label: 'Geçerlilik Tarihi', field: 'GecerlilikTarihi', format: 'date' },
                { label: 'Durum', field: 'Durum', format: 'status', statusEntity: 'teklif' }
            ],
            contract: [
                { label: 'Müşteri', field: 'MusteriUnvan' },
                { label: 'Sözleşme No', field: 'SozlesmeNo' },
                { label: 'Başlangıç', field: 'BaslangicTarihi', format: 'date' },
                { label: 'Bitiş', field: 'BitisTarihi', format: 'date' },
                { label: 'Tutar', field: 'Tutar', format: 'money', currencyField: 'ParaBirimi' },
                { label: 'Durum', field: 'Durum', format: 'status', statusEntity: 'sozlesme' }
            ],
            guarantee: [
                { label: 'Müşteri', field: 'MusteriUnvan' },
                { label: 'Belge No', field: 'BelgeNo' },
                { label: 'Tür', field: 'Tur' },
                { label: 'Banka', field: 'BankaAdi' },
                { label: 'Tutar', field: 'Tutar', format: 'money', currencyField: 'ParaBirimi' },
                { label: 'Vade Tarihi', field: 'VadeTarihi', format: 'date' },
                { label: 'Durum', field: 'Durum', format: 'status', statusEntity: 'teminat' }
            ],
            meeting: [
                { label: 'Müşteri', field: 'MusteriUnvan' },
                { label: 'Tarih', field: 'Tarih', format: 'date' },
                { label: 'Konu', field: 'Konu' },
                { label: 'Kişi', field: 'Kisi' },
                { label: 'Notlar', field: 'Notlar' }
            ],
            contact: [
                { label: 'Müşteri', field: 'MusteriUnvan' },
                { label: 'Ad Soyad', field: 'AdSoyad' },
                { label: 'Unvan', field: 'Unvan' },
                { label: 'Telefon', field: 'Telefon' },
                { label: 'E-posta', field: 'Email' },
                { label: 'Notlar', field: 'Notlar' }
            ],
            stampTax: [
                { label: 'Müşteri', field: 'MusteriUnvan' },
                { label: 'Tarih', field: 'Tarih', format: 'date' },
                { label: 'Tutar', field: 'Tutar', format: 'money', currencyField: 'DovizCinsi' },
                { label: 'Belge No', field: 'BelgeNo' },
                { label: 'Açıklama', field: 'Aciklama' }
            ],
            file: [
                { label: 'Müşteri', field: 'MusteriUnvan' },
                { label: 'Dosya Adı', field: 'DosyaAdi' },
                { label: 'Açıklama', field: 'Aciklama' },
                { label: 'Yüklenme Tarihi', field: 'OlusturmaTarihi', format: 'date' }
            ],
            calendar: [
                { label: 'Müşteri', field: 'MusteriUnvan' },
                { label: 'Proje', field: 'ProjeAdi' },
                { label: 'Özet', field: 'Ozet' },
                { label: 'Başlangıç', field: 'BaslangicTarihi', format: 'date' },
                { label: 'Bitiş', field: 'BitisTarihi', format: 'date' },
                { label: 'Açıklama', field: 'Aciklama' }
            ]
        };

        const config = configs[entityType] || [];
        
        let html = '<div class="row g-3">';
        config.forEach(item => {
            let value = data[item.field];
            
            if (item.render) {
                value = item.render(value, data);
            } else if (item.format === 'date') {
                value = formatters.date(value);
            } else if (item.format === 'money') {
                const currency = item.currencyField ? data[item.currencyField] : 'TRY';
                value = formatters.money(value, currency);
            } else if (item.format === 'status' && item.statusEntity) {
                // Dinamik durum parametrelerinden badge al
                value = NbtParams.getStatusBadge(item.statusEntity, value);
            }
            
            value = value || '<span class="text-muted">-</span>';
            
            html += `
                <div class="col-md-6">
                    <div class="border rounded p-2 h-100">
                        <small class="text-muted d-block">${item.label}</small>
                        <div class="fw-medium">${value}</div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        // Fatura kalemleri tablosu (invoice icin)
        if (entityType === 'invoice' && data.Kalemler && Array.isArray(data.Kalemler) && data.Kalemler.length > 0) {
            html += `
                <div class="mt-3">
                    <h6 class="border-bottom pb-2"><i class="bi bi-list-ul me-1"></i>Fatura Kalemleri</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Miktar</th>
                                    <th>Açıklama</th>
                                    <th>KDV %</th>
                                    <th>Birim Fiyat</th>
                                    <th>Tutar</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.Kalemler.map((k, i) => `
                                    <tr>
                                        <td>${i + 1}</td>
                                        <td>${k.Miktar || 0}</td>
                                        <td>${NbtUtils.escapeHtml(k.Aciklama || '-')}</td>
                                        <td>%${k.KdvOran || 0}</td>
                                        <td>${NbtUtils.formatMoney(k.BirimFiyat || 0)}</td>
                                        <td>${NbtUtils.formatMoney(k.Tutar || 0)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }
        
        // Fatura dosyalari tablosu (invoice icin)
        if (entityType === 'invoice' && data.Dosyalar && Array.isArray(data.Dosyalar) && data.Dosyalar.length > 0) {
            html += `
                <div class="mt-3">
                    <h6 class="border-bottom pb-2"><i class="bi bi-folder me-1"></i>Fatura Dosyaları</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Dosya Adı</th>
                                    <th>Boyut</th>
                                    <th>Yüklenme</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.Dosyalar.map((d) => `
                                    <tr>
                                        <td>${NbtUtils.escapeHtml(d.DosyaAdi || '-')}</td>
                                        <td>${d.DosyaBoyutu ? (d.DosyaBoyutu / 1024).toFixed(1) + ' KB' : '-'}</td>
                                        <td>${NbtUtils.formatDate(d.OlusturmaZamani)}</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-info btn-download-file" data-file-id="${d.Id}" data-file-name="${NbtUtils.escapeHtml(d.DosyaAdi || 'dosya')}" title="İndir">
                                                <i class="bi bi-download"></i>
                                            </button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            
            // Dosya indirme event'lerini bind et
            setTimeout(() => {
                document.querySelectorAll('.btn-download-file').forEach(btn => {
                    btn.onclick = async () => {
                        const fileId = btn.dataset.fileId;
                        const fileName = btn.dataset.fileName || 'dosya';
                        if (fileId) {
                            try {
                                NbtToast.info('Dosya hazırlanıyor...');
                                const response = await fetch(`/api/files/${fileId}/download`, {
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
                                link.download = fileName;
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
            }, 100);
        }
        
        return html;
    }
};

// =============================================
// NAVIGATION HELPER - Server-Rendered Sayfa Mimarisi
// =============================================
// Sayfa baslatma ve navigasyon yardimcilari.
// Tum navigasyonlar gercek sayfa yuklemesi yapar (SPA degil).

const NbtRouter = {
    routes: {},
    defaultRoute: 'dashboard',

    /**
     * Sayfa modulu kaydet - Sayfa yuklendiginde init icin kullanilir
     */
    register(path, handler) {
        this.routes[path] = handler;
    },

    /**
     * Sayfaya git - Server-Rendered Navigation
     * NOT: Artik pushState kullanilmiyor, gercek sayfa yuklemesi yapilir.
     */
    navigate(path, params = {}) {
        // /customer/9 gibi full path'leri parse et
        let targetUrl = path;
        
        if (!path.startsWith('/')) {
            targetUrl = '/' + path;
        }
        
        // customer/123 formatini handle et
        if (path.includes('customer') && params.id) {
            targetUrl = `/customer/${params.id}`;
        } else if (params.id) {
            targetUrl = `/${path}/${params.id}`;
        }
        
        // Tab parametresi KALDIRILDI - tab state artik URL'de degil
        // (params.tab artik URL'ye eklenmez)
        
        // Gercek sayfa yuklemesi yapilmasi (SPA degil)
        window.location.href = targetUrl;
    },

    /**
     * Sayfa baslatma - CURRENT_PAGE'e gore ilgili modulu init eder
     */
    init() {
        const currentPage = window.APP_CONFIG?.CURRENT_PAGE || 'dashboard';
        
        if (this.routes[currentPage]) {
            const params = this._getUrlParams();
            this.routes[currentPage](params);
        }
    },

    /**
     * URL parametrelerini al
     */
    _getUrlParams() {
        const params = {};
        const searchParams = new URLSearchParams(window.location.search);
        searchParams.forEach((value, key) => {
            params[key] = value;
        });
        
        // /customer/{id} formatindan ID al
        const pathParts = window.location.pathname.split('/').filter(Boolean);
        if (pathParts[0] === 'customer' && pathParts[1]) {
            params.id = pathParts[1];
        }
        
        return params;
    }
};

// =============================================
// TAKVIM KOMPONENTI (Profesyonel Haftalik/Aylik)
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
        if (options.events !== undefined) this.events = options.events;
        if (options.onEventClick !== undefined) this.onEventClick = options.onEventClick;
        if (options.onDayClick !== undefined) this.onDayClick = options.onDayClick;
        if (options.viewMode !== undefined) this.viewMode = options.viewMode;

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
        
        dayNames.forEach(d => {
            html += `<div class="text-center small text-muted py-1 border-bottom fw-semibold">${d}</div>`;
        });

        // Onceki ayin gunleri
        const prevMonthDays = new Date(year, month, 0).getDate();
        for (let i = startDay - 2; i >= 0; i--) {
            html += `<div class="text-center text-muted p-1 border-bottom border-end opacity-50" style="min-height:60px;"><small>${prevMonthDays - i}</small></div>`;
        }

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
                    // Ay navigasyonunda gun sifirla - ay sonlarinda atlama sorununu onle
                    const newDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() - 1, 1);
                    this.currentDate = newDate;
                }
                this.render(container, { events: this.events });
            } else if (nextBtn) {
                if (this.viewMode === 'week') {
                    this.currentDate.setDate(this.currentDate.getDate() + 7);
                } else {
                    // Ay navigasyonunda gun sifirla - ay sonlarinda atlama sorununu onle
                    const newDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 1);
                    this.currentDate = newDate;
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
            NbtLogger.error('Takvim eventi yüklenemedi:', err);
            return [];
        }
    }
};

// =============================================
// GLOBAL INIT
// =============================================
document.addEventListener('DOMContentLoaded', () => {
    if (!NbtUtils.getToken() && !window.location.pathname.includes('login') && !window.location.pathname.includes('register')) {
        window.location.href = '/login';
        return;
    }

    const footerDateTime = document.getElementById('footerDateTime');
    if (footerDateTime) {
        const updateTime = () => {
            const now = new Date();
            footerDateTime.textContent = now.toLocaleDateString('tr-TR') + ' ' + now.toLocaleTimeString('tr-TR');
        };
        updateTime();
        setInterval(updateTime, 1000);
    }

    const footerIp = document.getElementById('footerIp');
    if (footerIp) {
        footerIp.textContent = 'IP: ' + (window.location.hostname || 'localhost');
    }

    const user = NbtUtils.getUser();
    const userNameEl = document.getElementById('userNameDisplay');
    if (userNameEl && user?.name) {
        userNameEl.textContent = user.name;
    }
    const footerUser = document.getElementById('footerUser');
    if (footerUser && user?.name) {
        footerUser.textContent = 'Kullanıcı: ' + user.name;
    }

    // Global fullscreen button handler
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-panel-fullscreen]');
        if (!btn) return;
        
        const panelId = btn.dataset.panelFullscreen;
        const panel = document.getElementById(panelId);
        if (!panel) return;
        
        NbtFullscreen.toggle(panel);
        const icon = btn.querySelector('i');
        if (icon) {
            icon.className = NbtFullscreen.activeElement ? 'bi bi-fullscreen-exit' : 'bi bi-arrows-fullscreen';
        }
    });

    // Ondalik sayi formatini koru (0.34 → .34 sorununu onle)
    // Number input'larda blur oldugunda basindaki 0'i koru
    document.addEventListener('blur', (e) => {
        if (e.target.type === 'number' && e.target.step && parseFloat(e.target.step) < 1) {
            const val = parseFloat(e.target.value);
            if (!isNaN(val) && val > 0 && val < 1) {
                // Deger 0 ile 1 arasindaysa, formatla (0.34 gibi goster)
                e.target.value = val.toFixed(2);
            }
        }
    }, true);
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
window.NbtDetailModal = NbtDetailModal;
window.NbtRouter = NbtRouter;
window.NbtCalendar = NbtCalendar;
