/**
 * Produktivitätstool - JavaScript Utilities
 * ========================================
 * Helper-Funktionen, API-Client, DOM-Utilities und Theme-Management
 */

/**
 * Theme Management
 */
class ThemeManager {
    constructor() {
        this.currentTheme = this.getStoredTheme() || 'light';
        this.init();
    }

    init() {
        this.applyTheme(this.currentTheme);
        this.setupThemeToggle();
    }

    getStoredTheme() {
        return localStorage.getItem('theme') || document.documentElement.getAttribute('data-theme');
    }

    setStoredTheme(theme) {
        localStorage.setItem('theme', theme);
    }

    applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        this.currentTheme = theme;
        this.setStoredTheme(theme);

        // Theme-Event auslösen
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
    }

    toggleTheme() {
        const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        this.applyTheme(newTheme);
    }

    setupThemeToggle() {
        const toggle = document.querySelector('.theme-toggle');
        if (toggle) {
            toggle.addEventListener('click', () => this.toggleTheme());
            this.updateToggleButton();
        }

        // Theme-Change Event Listener
        window.addEventListener('themeChanged', () => this.updateToggleButton());
    }

    updateToggleButton() {
        const toggle = document.querySelector('.theme-toggle');
        if (toggle) {
            const icon = toggle.querySelector('i');
            if (icon) {
                // Toggle FontAwesome icon classes
                if (this.currentTheme === 'light') {
                    icon.className = 'fas fa-moon';
                    icon.textContent = '';
                    icon.setAttribute('title', 'Dark Mode');
                } else {
                    icon.className = 'fas fa-sun';
                    icon.textContent = '';
                    icon.setAttribute('title', 'Light Mode');
                }
            }
        }
    }
}

/**
 * API Client
 */
class ApiClient {
    constructor(baseUrl = null) {
        // Basis-URL automatisch ermitteln, falls nicht angegeben
        if (baseUrl === null) {
            const basePath = document.querySelector('meta[name="base-path"]')?.content || '';
            this.baseUrl = basePath;
        } else {
            this.baseUrl = baseUrl;
        }
        this.defaultHeaders = {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
    }

    async request(endpoint, options = {}) {
        // Endpoint should start with /api/ - prepend baseUrl
        const url = `${this.baseUrl}${endpoint}`;
        const config = {
            headers: { ...this.defaultHeaders, ...options.headers },
            ...options
        };

        // CSRF-Token hinzufügen
        if (config.method && config.method.toUpperCase() !== 'GET') {
            const csrfToken = this.getCsrfToken();
            if (csrfToken) {
                config.headers['X-CSRF-Token'] = csrfToken;
            }
        }

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                throw new ApiError(data.message || 'API Error', response.status, data.errors);
            }

            return data;
        } catch (error) {
            if (error instanceof ApiError) {
                throw error;
            }
            throw new ApiError('Network Error', 0, [error.message]);
        }
    }

    async get(endpoint, params = {}) {
        // Build query string if params provided
        let url = endpoint;
        if (Object.keys(params).length > 0) {
            const queryString = new URLSearchParams(params).toString();
            url += (url.includes('?') ? '&' : '?') + queryString;
        }
        return this.request(url, { method: 'GET' });
    }

    async post(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    async put(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    async delete(endpoint, data = {}) {
        return this.request(endpoint, { 
            method: 'DELETE',
            body: JSON.stringify(data)
        });
    }

    getCsrfToken() {
        const tokenInput = document.querySelector('input[name="csrf_token"]');
        return tokenInput ? tokenInput.value : null;
    }
}

class ApiError extends Error {
    constructor(message, status, errors = []) {
        super(message);
        this.status = status;
        this.errors = errors;
        this.name = 'ApiError';
    }
}

/**
 * DOM Utilities
 */
class DomUtils {
    static createElement(tag, attributes = {}, children = []) {
        const element = document.createElement(tag);

        // Attribute setzen
        Object.entries(attributes).forEach(([key, value]) => {
            if (key === 'className') {
                element.className = value;
            } else if (key === 'textContent') {
                element.textContent = value;
            } else if (key === 'innerHTML') {
                element.innerHTML = value;
            } else if (key.startsWith('on') && typeof value === 'function') {
                element.addEventListener(key.slice(2).toLowerCase(), value);
            } else {
                element.setAttribute(key, value);
            }
        });

        // Kinder hinzufügen
        children.forEach(child => {
            if (typeof child === 'string') {
                element.appendChild(document.createTextNode(child));
            } else if (child instanceof Node) {
                element.appendChild(child);
            }
        });

        return element;
    }

    static show(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        if (element) {
            element.style.display = '';
            element.classList.remove('d-none');
        }
    }

    static hide(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        if (element) {
            element.style.display = 'none';
            element.classList.add('d-none');
        }
    }

    static toggle(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        if (element) {
            if (element.classList.contains('d-none')) {
                this.show(element);
            } else {
                this.hide(element);
            }
        }
    }

    static addClass(element, className) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        if (element) {
            element.classList.add(className);
        }
    }

    static removeClass(element, className) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        if (element) {
            element.classList.remove(className);
        }
    }

    static toggleClass(element, className) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        if (element) {
            element.classList.toggle(className);
        }
    }

    static fadeIn(element, duration = 300) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        if (!element) return;

        element.style.opacity = '0';
        element.style.display = '';
        element.style.transition = `opacity ${duration}ms ease`;

        requestAnimationFrame(() => {
            element.style.opacity = '1';
        });

        setTimeout(() => {
            element.style.transition = '';
        }, duration);
    }

    static fadeOut(element, duration = 300) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        if (!element) return;

        element.style.transition = `opacity ${duration}ms ease`;
        element.style.opacity = '0';

        setTimeout(() => {
            element.style.display = 'none';
            element.style.transition = '';
            element.style.opacity = '';
        }, duration);
    }
}

/**
 * Form Utilities
 */
class FormUtils {
    static serialize(form) {
        if (typeof form === 'string') {
            form = document.querySelector(form);
        }
        if (!form) return {};

        const data = new FormData(form);
        const result = {};

        for (let [key, value] of data.entries()) {
            if (result[key]) {
                if (Array.isArray(result[key])) {
                    result[key].push(value);
                } else {
                    result[key] = [result[key], value];
                }
            } else {
                result[key] = value;
            }
        }

        return result;
    }

    static validate(form, rules = {}) {
        if (typeof form === 'string') {
            form = document.querySelector(form);
        }
        if (!form) return { valid: true, errors: {} };

        const errors = {};
        let isValid = true;

        Object.entries(rules).forEach(([fieldName, fieldRules]) => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (!field) return;

            const value = field.value.trim();
            const fieldErrors = [];

            fieldRules.forEach(rule => {
                if (typeof rule === 'function') {
                    const result = rule(value, field);
                    if (result !== true) {
                        fieldErrors.push(result);
                    }
                } else if (typeof rule === 'object') {
                    const { type, param, message } = rule;

                    switch (type) {
                        case 'required':
                            if (!value) {
                                fieldErrors.push(message || 'Dieses Feld ist erforderlich.');
                            }
                            break;
                        case 'minLength':
                            if (value.length < param) {
                                fieldErrors.push(message || `Mindestens ${param} Zeichen erforderlich.`);
                            }
                            break;
                        case 'maxLength':
                            if (value.length > param) {
                                fieldErrors.push(message || `Maximal ${param} Zeichen erlaubt.`);
                            }
                            break;
                        case 'email':
                            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                            if (value && !emailRegex.test(value)) {
                                fieldErrors.push(message || 'Bitte eine gültige E-Mail-Adresse eingeben.');
                            }
                            break;
                        case 'match':
                            const matchField = form.querySelector(`[name="${param}"]`);
                            if (matchField && value !== matchField.value) {
                                fieldErrors.push(message || 'Felder stimmen nicht überein.');
                            }
                            break;
                    }
                }
            });

            if (fieldErrors.length > 0) {
                errors[fieldName] = fieldErrors;
                isValid = false;
            }
        });

        return { valid: isValid, errors };
    }

    static showErrors(form, errors) {
        if (typeof form === 'string') {
            form = document.querySelector(form);
        }
        if (!form) return;

        // Bestehende Fehler entfernen
        form.querySelectorAll('.field-error').forEach(el => el.remove());
        form.querySelectorAll('.field-has-error').forEach(el => el.classList.remove('field-has-error'));

        Object.entries(errors).forEach(([fieldName, fieldErrors]) => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (!field) return;

            // Feld als fehlerhaft markieren
            field.classList.add('field-has-error');

            // Fehler-Container finden oder erstellen
            let errorContainer = field.parentNode.querySelector('.field-error');
            if (!errorContainer) {
                errorContainer = DomUtils.createElement('div', {
                    className: 'field-error text-danger mt-1'
                });
                field.parentNode.insertBefore(errorContainer, field.nextSibling);
            }

            errorContainer.innerHTML = fieldErrors.join('<br>');
        });
    }

    static clearErrors(form) {
        if (typeof form === 'string') {
            form = document.querySelector(form);
        }
        if (!form) return;

        form.querySelectorAll('.field-error').forEach(el => el.remove());
        form.querySelectorAll('.field-has-error').forEach(el => el.classList.remove('field-has-error'));
    }
}

/**
 * Notification System
 */
class NotificationManager {
    constructor() {
        this.container = null;
        this.init();
    }

    init() {
        this.container = document.querySelector('.notification-container');
        if (!this.container) {
            this.container = DomUtils.createElement('div', {
                className: 'notification-container position-fixed',
                style: 'top: 20px; right: 20px; z-index: 9999;'
            });
            document.body.appendChild(this.container);
        }
    }

    show(message, type = 'info', duration = 5000) {
        const notification = DomUtils.createElement('div', {
            className: `alert alert-${type} shadow`,
            style: 'margin-bottom: 10px; min-width: 300px;'
        }, [
            message,
            DomUtils.createElement('button', {
                type: 'button',
                className: 'btn-close float-end',
                onclick: () => this.remove(notification)
            })
        ]);

        this.container.appendChild(notification);

        // Auto-remove nach duration
        if (duration > 0) {
            setTimeout(() => this.remove(notification), duration);
        }

        return notification;
    }

    success(message, duration = 5000) {
        return this.show(message, 'success', duration);
    }

    error(message, duration = 7000) {
        return this.show(message, 'danger', duration);
    }

    warning(message, duration = 6000) {
        return this.show(message, 'warning', duration);
    }

    info(message, duration = 5000) {
        return this.show(message, 'info', duration);
    }

    remove(notification) {
        if (notification.parentNode) {
            DomUtils.fadeOut(notification, 300);
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    }
}

/**
 * Loading States
 */
class LoadingManager {
    static show(element, text = 'Lädt...') {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        if (!element) return;

        element.style.position = 'relative';

        const overlay = DomUtils.createElement('div', {
            className: 'loading-overlay position-absolute d-flex align-center justify-center',
            style: `
                top: 0; left: 0; right: 0; bottom: 0;
                background-color: rgba(255, 255, 255, 0.8);
                z-index: 10;
                backdrop-filter: blur(2px);
            `
        }, [
            DomUtils.createElement('div', { className: 'text-center' }, [
                DomUtils.createElement('div', { className: 'spinner mb-2' }),
                DomUtils.createElement('div', { textContent: text })
            ])
        ]);

        element.appendChild(overlay);
        element.classList.add('loading');
    }

    static hide(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        if (!element) return;

        const overlay = element.querySelector('.loading-overlay');
        if (overlay) {
            overlay.remove();
        }
        element.classList.remove('loading');
    }
}

/**
 * Date/Time Utilities
 */
class DateUtils {
    static format(date, format = 'd.m.Y H:i') {
        if (!(date instanceof Date)) {
            date = new Date(date);
        }

        const tokens = {
            'Y': date.getFullYear(),
            'm': String(date.getMonth() + 1).padStart(2, '0'),
            'd': String(date.getDate()).padStart(2, '0'),
            'H': String(date.getHours()).padStart(2, '0'),
            'i': String(date.getMinutes()).padStart(2, '0'),
            's': String(date.getSeconds()).padStart(2, '0')
        };

        return format.replace(/Y|m|d|H|i|s/g, match => tokens[match]);
    }

    static timeAgo(date) {
        if (!(date instanceof Date)) {
            date = new Date(date);
        }

        const now = new Date();
        const diff = Math.floor((now - date) / 1000);

        if (diff < 60) return 'gerade eben';
        if (diff < 3600) return `vor ${Math.floor(diff / 60)} Minute${Math.floor(diff / 60) > 1 ? 'n' : ''}`;
        if (diff < 86400) return `vor ${Math.floor(diff / 3600)} Stunde${Math.floor(diff / 3600) > 1 ? 'n' : ''}`;
        if (diff < 604800) return `vor ${Math.floor(diff / 86400)} Tag${Math.floor(diff / 86400) > 1 ? 'en' : ''}`;

        return this.format(date, 'd.m.Y');
    }

    static addDays(date, days) {
        const result = new Date(date);
        result.setDate(result.getDate() + days);
        return result;
    }

    static isToday(date) {
        const today = new Date();
        return date.toDateString() === today.toDateString();
    }

    static isTomorrow(date) {
        const tomorrow = this.addDays(new Date(), 1);
        return date.toDateString() === tomorrow.toDateString();
    }
}

/**
 * Storage Utilities
 */
class Storage {
    static get(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (e) {
            return defaultValue;
        }
    }

    static set(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (e) {
            return false;
        }
    }

    static remove(key) {
        localStorage.removeItem(key);
    }

    static clear() {
        localStorage.clear();
    }
}

/**
 * Debounce Utility
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Throttle Utility
 */
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Global Instances
const themeManager = new ThemeManager();
const api = new ApiClient();
const notifications = new NotificationManager();

// Expose to window for other scripts
window.ApiClient = api;
window.NotificationManager = notifications;
window.ThemeManager = themeManager;
window.DomUtils = DomUtils;
window.FormUtils = FormUtils;
window.DateUtils = DateUtils;
window.Storage = Storage;

// Export für Module-Systeme
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        ThemeManager,
        ApiClient,
        ApiError,
        DomUtils,
        FormUtils,
        NotificationManager,
        LoadingManager,
        DateUtils,
        Storage,
        debounce,
        throttle,
        themeManager,
        api,
        notifications
    };
}