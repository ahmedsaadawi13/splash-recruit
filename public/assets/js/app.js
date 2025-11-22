// FILE: /public/assets/js/app.js
// SplashRecruit - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Add CSRF token to all AJAX requests
    if (csrfToken) {
        // Example: Fetch with CSRF
        window.fetchWithCSRF = function(url, options = {}) {
            options.headers = options.headers || {};
            options.headers['X-CSRF-Token'] = csrfToken;
            return fetch(url, options);
        };
    }

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm');
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // Form validation helper
    window.validateForm = function(formId, rules) {
        const form = document.getElementById(formId);
        if (!form) return false;

        form.addEventListener('submit', function(e) {
            let isValid = true;
            let firstError = null;

            Object.keys(rules).forEach(fieldName => {
                const field = form.querySelector(`[name="${fieldName}"]`);
                const rule = rules[fieldName];

                if (rule.required && !field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    if (!firstError) firstError = field;
                } else {
                    field.classList.remove('error');
                }
            });

            if (!isValid) {
                e.preventDefault();
                if (firstError) firstError.focus();
            }
        });
    };

    // Initialize tooltips (simple implementation)
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const text = this.getAttribute('data-tooltip');
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = text;
            document.body.appendChild(tooltip);

            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + 'px';
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';

            this._tooltip = tooltip;
        });

        element.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                this._tooltip = null;
            }
        });
    });
});
