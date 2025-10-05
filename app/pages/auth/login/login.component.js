class LoginComponent {
    constructor() {
        this.cssLoaded = false;
        this.params = {};
    }
    setParams(params) {
        this.params = params;
        // Si hay un mensaje de confirmación, mostrarlo
        if (params['email-confirmed'] && params.message) {
            setTimeout(() => {
                if (window.notificationModal) {
                    window.notificationModal.show('success', params.message);
                }
            }, 1000); // Esperar a que el componente esté completamente cargado
        }
    }
    render() {
        // Devuelve un contenedor, el HTML se inyecta en afterRender
        return '<div class="auth-component login-component"></div>';
    }
    async afterRender() {
        // Cargar CSS solo una vez
        if (!this.cssLoaded) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'app/pages/auth/login/login.component.css';
            document.head.appendChild(link);
            this.cssLoaded = true;
        }
        // Cargar HTML de forma asíncrona
        const container = document.querySelector('.auth-component.login-component');
        if (container) {
            try {
                const html = await fetch('app/pages/auth/login/login.component.html').then(r => r.text());
                container.innerHTML = html;
            } catch (e) {
                container.innerHTML = '<div>Error cargando login.component.html</div>';
            }
        }
        // Esperar a que el HTML esté en el DOM antes de inicializar lógica
        setTimeout(() => {
            this.waitForAuthServiceAndInitialize();
        }, 0);
    }
    getElement() {
        return document.querySelector('.auth-component.login-component');
    }
    waitForAuthServiceAndInitialize() {
        const container = this.getElement();
        if (!container) return;
        // Inicializar eventos del formulario
        const form = container.querySelector('#loginForm');
        if (form) {
            form.addEventListener('submit', (e) => this.handleSubmit(e));
        }
        this.initializePasswordToggle();
        this.initializeNavigation();
        this.initializeForgotPassword();
        this.checkURLParameters();
    }
    checkURLParameters() {
        const urlParams = new URLSearchParams(window.location.search);
        const message = urlParams.get('message');
        const emailConfirmed = urlParams.get('email-confirmed');
        if (emailConfirmed === '1') {
            // Mostrar modal de confirmación exitosa
            setTimeout(() => {
                if (window.notificationModal) {
                    window.notificationModal.showSuccess(
                        '¡Email Confirmado!', 
                        ['Tu cuenta ha sido verificada exitosamente', 'Ya puedes iniciar sesión con tus credenciales']
                    );
                } else {
                    this.showSuccess('¡Tu email ha sido confirmado exitosamente! Ya puedes iniciar sesión.');
                }
            }, 500); // Pequeño delay para asegurar que el modal esté disponible
        } else if (message) {
            // Determinar el tipo de mensaje basado en el contenido
            const messageText = decodeURIComponent(message);
            setTimeout(() => {
                if (window.notificationModal) {
                    if (messageText.includes('exitoso') || messageText.includes('confirmado')) {
                        window.notificationModal.showSuccess('Confirmación Exitosa', [messageText]);
                    } else {
                        window.notificationModal.showError('Información', [messageText]);
                    }
                } else {
                    if (messageText.includes('exitoso') || messageText.includes('confirmado')) {
                        this.showSuccess(messageText);
                    } else {
                        this.showError(messageText);
                    }
                }
            }, 500);
        }
        // Limpiar la URL después de mostrar el mensaje
        if (message || emailConfirmed) {
            const newUrl = window.location.pathname + window.location.hash.split('?')[0];
            window.history.replaceState({}, '', newUrl);
        }
    }
    async handleSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const credentials = {
            email: form.email.value.trim(),
            password: form.password.value
        };
        // Validar campos
        if (!this.validateForm(credentials)) {
            return;
        }
        // Mostrar estado de carga
        this.setLoadingState(true);
        try {
            const result = await window.authService.login(credentials);
            if (result.success) {
                this.showNotification(result.message, 'success');
                // Redirigir después del login exitoso
                setTimeout(() => {
                    window.appRouter.navigate('/');
                }, 1500);
            } else {
                if (!result.requiresEmailConfirmation) {
                    this.showNotification(result.message, 'error');
                }
            }
        } catch (error) {
            console.error('Error en login:', error);
            this.showError('Error de conexión. Intenta nuevamente.');
        } finally {
            this.setLoadingState(false);
        }
    }
    validateForm(credentials) {
        this.clearErrors();
        let isValid = true;
        // Validar email
        if (!credentials.email) {
            this.showFieldError('emailError', 'El email es requerido');
            isValid = false;
        } else if (!this.isValidEmail(credentials.email)) {
            this.showFieldError('emailError', 'El email no es válido');
            isValid = false;
        }
        // Validar contraseña
        if (!credentials.password) {
            this.showFieldError('passwordError', 'La contraseña es requerida');
            isValid = false;
        } else if (credentials.password.length < 6) {
            this.showFieldError('passwordError', 'La contraseña debe tener al menos 6 caracteres');
            isValid = false;
        }
        return isValid;
    }
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    showFieldError(fieldId, message) {
        const errorElement = this.getElement().querySelector(`#${fieldId}`);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.add('show');
        }
    }
    clearErrors() {
        const errorElements = this.getElement().querySelectorAll('.form-error');
        errorElements.forEach(element => {
            element.classList.remove('show');
            element.textContent = '';
        });
    }
    setLoadingState(isLoading) {
        const btn = this.getElement().querySelector('#loginBtn');
        if (!btn) return;
        const btnText = btn.querySelector('.btn-text');
        const btnLoader = btn.querySelector('.btn-loader');
        if (isLoading) {
            btn.disabled = true;
            if (btnText) btnText.style.display = 'none';
            if (btnLoader) btnLoader.style.display = 'inline';
        } else {
            btn.disabled = false;
            if (btnText) btnText.style.display = 'inline';
            if (btnLoader) btnLoader.style.display = 'none';
        }
    }
    showError(message) {
        this.showNotification(message, 'error');
    }
    showSuccess(message) {
        this.showNotification(message, 'success');
    }
    showNotification(message, type = 'info') {
        // Remover notificación anterior si existe
        const existingNotification = this.getElement().querySelector('.login-notification');
        if (existingNotification) {
            existingNotification.remove();
        }
        // Crear notificación
        const notification = document.createElement('div');
        notification.className = `login-notification ${type}`;
        notification.innerHTML = `
            <span class="notification-icon">${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</span>
            <span class="notification-text">${message}</span>
            <button class="notification-close" onclick="this.parentElement.remove()">&times;</button>
        `;
        // Insertar en el formulario
        const authCard = this.getElement().querySelector('.auth-card');
        if (authCard) {
            authCard.insertBefore(notification, authCard.firstChild);
        }
        // Auto-remover después de 5 segundos para éxito, 8 segundos para error
        const timeout = type === 'success' ? 5000 : 8000;
        setTimeout(() => {
            if (notification && notification.parentNode) {
                notification.remove();
            }
        }, timeout);
        // Agregar estilos si no existen
        this.addNotificationStyles();
    }
    addNotificationStyles() {
        if (!document.getElementById('login-notification-styles')) {
            const style = document.createElement('style');
            style.id = 'login-notification-styles';
            style.textContent = `
                .login-notification {
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    padding: 1rem;
                    margin-bottom: 1.5rem;
                    border-radius: 8px;
                    font-weight: 500;
                    animation: slideInFromTop 0.3s ease;
                }
                .login-notification.success {
                    background: #d1fae5;
                    color: #065f46;
                    border-left: 4px solid #10b981;
                }
                .login-notification.error {
                    background: #fee2e2;
                    color: #991b1b;
                    border-left: 4px solid #ef4444;
                }
                .login-notification.info {
                    background: #dbeafe;
                    color: #1e40af;
                    border-left: 4px solid #3b82f6;
                }
                .notification-icon {
                    font-size: 1.1rem;
                    flex-shrink: 0;
                }
                .notification-text {
                    flex: 1;
                    font-size: 0.9rem;
                }
                .notification-close {
                    background: none;
                    border: none;
                    font-size: 1.2rem;
                    cursor: pointer;
                    padding: 0;
                    opacity: 0.7;
                    transition: opacity 0.2s;
                    flex-shrink: 0;
                }
                .notification-close:hover {
                    opacity: 1;
                }
                @keyframes slideInFromTop {
                    from {
                        opacity: 0;
                        transform: translateY(-20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }
    initializePasswordToggle() {
        const toggleBtn = this.getElement().querySelector('#toggleLoginPassword');
        const passwordInput = this.getElement().querySelector('#loginPassword');
        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', () => {
                const isPassword = passwordInput.type === 'password';
                if (isPassword) {
                    // Mostrar contraseña
                    passwordInput.type = 'text';
                    toggleBtn.textContent = '🙈';
                    toggleBtn.setAttribute('aria-label', 'Ocultar contraseña');
                } else {
                    // Ocultar contraseña
                    passwordInput.type = 'password';
                    toggleBtn.textContent = '👁️';
                    toggleBtn.setAttribute('aria-label', 'Mostrar contraseña');
                }
                // Forzar el foco para mantener la posición del cursor
                passwordInput.focus();
                // Mover el cursor al final
                setTimeout(() => {
                    passwordInput.setSelectionRange(passwordInput.value.length, passwordInput.value.length);
                }, 10);
            });
        }
    }
    initializeNavigation() {
        const navLinks = this.getElement().querySelectorAll('[data-navigate]');
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const route = link.getAttribute('data-navigate');
                window.appRouter.navigate(route);
            });
        });
    }
    initializeForgotPassword() {
        const forgotLink = this.getElement().querySelector('#forgotPasswordLink');
        if (forgotLink) {
            forgotLink.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleForgotPassword();
            });
        }
    }
    async handleForgotPassword() {
        const email = prompt('Ingresa tu email para restablecer la contraseña:');
        if (email && email.trim()) {
            try {
                this.showNotification('Enviando solicitud de restablecimiento...', 'info');
                const result = await window.authService.requestPasswordReset(email.trim());
                if (result.success) {
                    this.showNotification(result.message || 'Se ha enviado un email con instrucciones para restablecer tu contraseña.', 'success');
                } else {
                    this.showNotification(result.message || 'Error al procesar la solicitud.', 'error');
                }
            } catch (error) {
                this.showNotification('Error de conexión al procesar la solicitud.', 'error');
            }
        } else if (email === '') {
            this.showNotification('Email requerido para restablecer contraseña.', 'error');
        }
    }
}
// Exportar el componente
window.LoginComponent = LoginComponent;
