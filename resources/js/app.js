import './bootstrap';

import Alpine from 'alpinejs';

/* ── Import Components ───────────────────────── */
import clockComponent from './components/clock';
import counterComponent from './components/counter';
import geolocationComponent from './components/geolocation';

/* ── Register Alpine Data Components ─────────── */
Alpine.data('clock', clockComponent);
Alpine.data('counter', (target = 0, duration = 800) => counterComponent(target, duration));
Alpine.data('geolocation', (lat, lng, radius) => geolocationComponent(lat, lng, radius));

/* ── Toast Notification System ───────────────── */
Alpine.data('toastManager', () => ({
    toasts: [],
    id: 0,

    add(message, type = 'success', duration = 4000) {
        const toast = {
            id: ++this.id,
            message,
            type, // success | error | warning | info
            visible: true,
        };
        this.toasts.push(toast);

        setTimeout(() => {
            this.remove(toast.id);
        }, duration);
    },

    remove(id) {
        const idx = this.toasts.findIndex((t) => t.id === id);
        if (idx !== -1) {
            this.toasts[idx].visible = false;
            setTimeout(() => {
                this.toasts = this.toasts.filter((t) => t.id !== id);
            }, 300);
        }
    },
}));

/* ── Sidebar State ───────────────────────────── */
Alpine.store('sidebar', {
    open: false,
    collapsed: false,

    toggle() {
        this.open = !this.open;
    },

    close() {
        this.open = false;
    },

    toggleCollapse() {
        this.collapsed = !this.collapsed;
    },
});

/* ── Start Alpine ────────────────────────────── */
window.Alpine = Alpine;
Alpine.start();

/* ── IntersectionObserver for Scroll Animations ─ */
document.addEventListener('DOMContentLoaded', () => {
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.1, rootMargin: '0px 0px -40px 0px' }
    );

    document.querySelectorAll('.appear-on-scroll').forEach((el) => {
        observer.observe(el);
    });

    // Re-observe after Livewire updates
    if (window.Livewire) {
        document.addEventListener('livewire:navigated', () => {
            document.querySelectorAll('.appear-on-scroll:not(.is-visible)').forEach((el) => {
                observer.observe(el);
            });
        });
    }
});

/* ── Global Toast from Session Flash ─────────── */
document.addEventListener('alpine:init', () => {
    // Listen for Livewire events
    if (window.Livewire) {
        window.Livewire.on('toast', (data) => {
            const manager = document.querySelector('[x-data*="toastManager"]');
            if (manager && manager.__x) {
                manager.__x.$data.add(data.message, data.type || 'success');
            }
        });
    }
});

/* ── Dynamic Input Restrictions ── */
document.addEventListener('input', (event) => {
    const target = event.target;
    if (!target) return;

    // 1. Name input restriction (letters only + spaces & symbols: . , ' -)
    const isSettingsPage = window.location.pathname.includes('/admin/settings');
    const isNameInput = (target.id === 'name' && !isSettingsPage) || target.id === 'editName';
    
    if (isNameInput) {
        restrictInput(target, /[^a-zA-Z\s.,'\-]/g);
    }

    // 2. Numeric inputs (NISN, NIP, Phone/WA numbers)
    const isNumericInput = [
        'nis', 'editNis', 'nip', 'editNip',
        'whatsapp_number', 'editWhatsapp', 'parent_phone_wa', 'editParentPhone'
    ].includes(target.id);

    if (isNumericInput) {
        restrictInput(target, /[^0-9]/g);
    }
});

function restrictInput(element, regex) {
    const oldValue = element.value;
    const newValue = oldValue.replace(regex, '');
    if (oldValue !== newValue) {
        const selectionStart = element.selectionStart;
        const selectionEnd = element.selectionEnd;
        element.value = newValue;
        const diff = oldValue.length - newValue.length;
        element.setSelectionRange(selectionStart - diff, selectionEnd - diff);
    }
}

