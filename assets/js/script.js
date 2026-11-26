// Rwanda Connect - Main JavaScript
document.addEventListener('DOMContentLoaded', () => {
    console.log('Rwanda Connect loaded.');

    // Back to top button
    const backToTop = document.querySelector('.back-to-top');
    if (backToTop) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                backToTop.hidden = false;
                requestAnimationFrame(() => backToTop.classList.add('visible'));
            } else {
                backToTop.classList.remove('visible');
                setTimeout(() => { if (!backToTop.classList.contains('visible')) backToTop.hidden = true; }, 300);
            }
        });
        backToTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    }

    // Mobile navigation
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileNavOverlay = document.querySelector('.mobile-nav-overlay');
    const mobileNavClose = document.querySelector('.mobile-nav-close');

    function openMobileNav() {
        mobileNavOverlay.classList.add('active');
        mobileMenuToggle.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileNav() {
        mobileNavOverlay.classList.remove('active');
        mobileMenuToggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    mobileMenuToggle?.addEventListener('click', openMobileNav);
    mobileNavClose?.addEventListener('click', closeMobileNav);
    mobileNavOverlay?.addEventListener('click', (e) => {
        if (e.target === mobileNavOverlay) closeMobileNav();
    });

    // Close mobile nav on link click
    document.querySelectorAll('.mobile-nav-list a, .mobile-nav-btn').forEach(link => {
        link.addEventListener('click', closeMobileNav);
    });

    // Dropdown menus - close on outside click
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.nav-dropdown')) {
            document.querySelectorAll('.nav-dropdown').forEach(d => {
                // Dropdowns are CSS-only hover, but we can force close on mobile
            });
        }
    });

    // Password visibility toggles
    document.querySelectorAll('.password-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.previousElementSibling;
            if (!input) return;
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.setAttribute('aria-pressed', isHidden);
            const eyeOpen = btn.querySelector('.eye-open');
            const eyeClosed = btn.querySelector('.eye-closed');
            if (eyeOpen) eyeOpen.hidden = isHidden;
            if (eyeClosed) eyeClosed.hidden = !isHidden;
        });
    });

    // Phone number formatting (Rwanda)
    document.querySelectorAll('input[type="tel"]').forEach(input => {
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value.startsWith('250')) value = value.substring(3);
            if (value.startsWith('0')) value = value.substring(1);
            if (value.length > 9) value = value.substring(0, 9);

            let formatted = '';
            if (value.length > 0) formatted = '+250 ';
            if (value.length > 2) formatted += value.substring(0, 3) + ' ';
            if (value.length > 5) formatted += value.substring(3, 6) + ' ';
            if (value.length > 6) formatted += value.substring(6);

            e.target.value = formatted;
        });
    });

    // National ID formatting (16 digits)
    document.querySelectorAll('input[name="national_id"]').forEach(input => {
        input.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 16);
        });
    });

    // Form validation helpers
    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function validatePhone(phone) {
        return /^(\+250|0)[78]\d{8}$/.test(phone.replace(/\s/g, ''));
    }

    function validatePassword(password) {
        const errors = [];
        if (password.length < 8) errors.push('At least 8 characters');
        if (!/[A-Z]/.test(password)) errors.push('One uppercase letter');
        if (!/[a-z]/.test(password)) errors.push('One lowercase letter');
        if (!/\d/.test(password)) errors.push('One number');
        return errors;
    }

    // Password strength meter
    function initPasswordStrength(passwordInputId, strengthContainerId) {
        const passwordInput = document.getElementById(passwordInputId);
        const strengthContainer = document.getElementById(strengthContainerId);
        if (!passwordInput || !strengthContainer) return;

        const strengthFill = strengthContainer.querySelector('.strength-fill');
        const strengthText = strengthContainer.querySelector('.strength-text');

        passwordInput.addEventListener('input', () => {
            const password = passwordInput.value;
            if (password.length === 0) {
                strengthContainer.hidden = true;
                return;
            }
            strengthContainer.hidden = false;

            let score = 0;
            if (password.length >= 8) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[a-z]/.test(password)) score++;
            if (/\d/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;

            const labels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
            const colors = ['#e74c3c', '#e67e22', '#f39c12', '#2ecc71', '#27ae60'];

            strengthFill.style.width = `${(score / 5) * 100}%`;
            strengthFill.style.backgroundColor = colors[score - 1] || colors[0];
            strengthText.textContent = `Strength: ${labels[score - 1] || labels[0]}`;
            strengthText.style.color = colors[score - 1] || colors[0];
        });
    }

    // Initialize password strength meters
    initPasswordStrength('password', 'password_strength');
    initPasswordStrength('new_password', 'password_strength');

    // Confirm password validation
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', (e) => {
            const password = form.querySelector('input[name="password"], input[name="new_password"]');
            const confirm = form.querySelector('input[name="confirm_password"]');
            if (password && confirm && password.value !== confirm.value) {
                e.preventDefault();
                confirm.classList.add('error');
                let errorEl = confirm.parentElement.querySelector('.error-message');
                if (!errorEl) {
                    errorEl = document.createElement('span');
                    errorEl.className = 'error-message';
                    confirm.parentElement.appendChild(errorEl);
                }
                errorEl.textContent = 'Passwords do not match';
                confirm.focus();
            }
        });
    });

    // Flash message auto-dismiss
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateX(100%)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            const target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                target.focus({ preventScroll: true });
            }
        });
    });

    // Auto-resize textareas
    document.querySelectorAll('textarea[rows]').forEach(textarea => {
        textarea.addEventListener('input', () => {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 300) + 'px';
        });
    });

    // Copy to clipboard helper
    window.copyToClipboard = async (text) => {
        try {
            await navigator.clipboard.writeText(text);
            showToast('Copied to clipboard!');
        } catch (err) {
            console.error('Failed to copy:', err);
            showToast('Failed to copy', 'error');
        }
    };

    // Toast notifications
    window.showToast = (message, type = 'success') => {
        const container = document.getElementById('flash-container') || createFlashContainer();
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;
        container.appendChild(alert);
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateX(100%)';
            setTimeout(() => alert.remove(), 300);
        }, 4000);
    };

    function createFlashContainer() {
        const container = document.createElement('div');
        container.id = 'flash-container';
        container.className = 'flash-container';
        document.body.appendChild(container);
        return container;
    }

    // CSRF token helper for AJAX
    window.getCsrfToken = () => {
        const meta = document.querySelector('meta[name="csrf-token"]');
        const input = document.querySelector('input[name="csrf_token"]');
        return meta?.getAttribute('content') || input?.value || '';
    };

    // AJAX helper with CSRF
    window.ajax = async (url, options = {}) => {
        const defaults = {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': getCsrfToken()
            },
            credentials: 'same-origin'
        };
        const config = { ...defaults, ...options, headers: { ...defaults.headers, ...options.headers } };
        if (config.body && typeof config.body === 'object') {
            config.body = JSON.stringify(config.body);
        }
        const response = await fetch(url, config);
        if (!response.ok) {
            const error = await response.json().catch(() => ({ message: 'Request failed' }));
            throw new Error(error.message || `HTTP ${response.status}`);
        }
        return response.json();
    };

    // District dropdown update for province selection
    window.initProvinceDistrict = (provinceSelectId, districtSelectId) => {
        const provinceSelect = document.getElementById(provinceSelectId);
        const districtSelect = document.getElementById(districtSelectId);
        if (!provinceSelect || !districtSelect) return;

        const districtsByProvince = {
            'Kigali City': ['Gasabo', 'Kicukiro', 'Nyarugenge'],
            'Northern Province': ['Burera', 'Gakenke', 'Gicumbi', 'Musanze', 'Rulindo'],
            'Southern Province': ['Gisagara', 'Huye', 'Kamonyi', 'Muhanga', 'Nyamagabe', 'Nyanza', 'Nyaruguru', 'Ruhango'],
            'Eastern Province': ['Bugesera', 'Gatsibo', 'Kayonza', 'Kirehe', 'Ngoma', 'Nyagatare', 'Rwamagana'],
            'Western Province': ['Karongi', 'Ngororero', 'Nyabihu', 'Nyamasheke', 'Rubavu', 'Rusizi', 'Rutsiro']
        };

        function updateDistricts(province) {
            const currentValue = districtSelect.value;
            districtSelect.innerHTML = '<option value="">Select District</option>';
            if (province && districtsByProvince[province]) {
                districtsByProvince[province].forEach(district => {
                    const option = document.createElement('option');
                    option.value = district;
                    option.textContent = district;
                    if (district === currentValue) option.selected = true;
                    districtSelect.appendChild(option);
                });
            }
        }

        provinceSelect.addEventListener('change', () => updateDistricts(provinceSelect.value));
        // Initialize on load
        if (provinceSelect.value) updateDistricts(provinceSelect.value);
    };

    // Initialize province/district on pages that need it
    initProvinceDistrict('province', 'district');

    // Debounce helper
    window.debounce = (fn, delay) => {
        let timeoutId;
        return (...args) => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => fn.apply(this, args), delay);
        };
    };

    // Format currency for display
    window.formatCurrency = (amount, currency = 'RWF') => {
        if (amount === null || amount === undefined) return 'N/A';
        return new Intl.NumberFormat('en-RW', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount);
    };

    // Time ago formatter
    window.timeAgo = (dateString) => {
        try {
            const date = new Date(dateString);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);
            if (diff < 60) return 'Just now';
            if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
            if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
            if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`;
            return date.toLocaleDateString('en-RW', { month: 'short', day: 'numeric', year: 'numeric' });
        } catch {
            return dateString;
        }
    };
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { debounce: window.debounce, formatCurrency: window.formatCurrency, timeAgo: window.timeAgo };
}