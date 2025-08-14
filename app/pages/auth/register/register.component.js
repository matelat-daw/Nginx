//
// Register Component - Componente de registro de usuarios
class RegisterComponent {
    constructor() {
        this.cssLoaded = false;
    }

    render() {
        // Devuelve un contenedor vacío, el HTML se inyecta en afterRender
        return '<div class="auth-component register-component"></div>';
    }

    async afterRender() {
        // Cargar CSS solo una vez
        if (!this.cssLoaded) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'app/pages/auth/register/register.component.css';
            document.head.appendChild(link);
            this.cssLoaded = true;
        }

        // Cargar HTML de forma asíncrona
        const container = document.querySelector('.auth-component.register-component');
        if (container) {
            try {
                const html = await fetch('app/pages/auth/register/register.component.html').then(r => r.text());
                container.innerHTML = html;
            } catch (e) {
                container.innerHTML = '<div>Error cargando register.component.html</div>';
            }
        }

        // Esperar a que el HTML esté en el DOM antes de inicializar lógica
        setTimeout(() => {
            this.waitForAuthServiceAndInitialize();
        }, 0);
    }

    getElement() {
        return document.querySelector('.auth-component.register-component');
    }

    async waitForAuthServiceAndInitialize() {
        console.log('🔄 RegisterComponent: Verificando AuthService...');
        // Intentar hasta 50 veces (5 segundos)
        for (let i = 0; i < 50; i++) {
            if (window.authService && typeof window.authService.register === 'function') {
                console.log('✅ RegisterComponent: AuthService disponible');
                break;
            }
            if (typeof window.AuthService === 'function' && !window.authService) {
                try {
                    console.log('🔧 RegisterComponent: Creando AuthService...');
                    window.authService = new window.AuthService();
                    console.log('✅ RegisterComponent: AuthService creado');
                    break;
                } catch (error) {
                    console.error('❌ RegisterComponent: Error creando AuthService:', error);
                }
            }
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        this.initializeForm();
        this.initializePasswordToggles();
        this.initializePasswordStrength();
        this.initializeNavigation();
        this.initializeCheckboxAnimation();
        console.log('✅ RegisterComponent: Componente inicializado');
    }

    initializeForm() {
        const form = document.getElementById('registerForm');
        if (!form) {
            console.error('❌ Formulario de registro no encontrado');
            return;
        }

        // Prevenir el envío por defecto del formulario
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleSubmit(e);
        });

        console.log('✅ Formulario de registro inicializado');
    }

    async handleSubmit(event) {
        event.preventDefault();
        
        console.log('🔄 Procesando registro...');
        
        // Obtener datos del formulario
        const formData = new FormData(event.target);
        const userData = {
            firstName: formData.get('firstName'),
            lastName: formData.get('lastName'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            island: formData.get('island'),
            city: formData.get('city'),
            userType: formData.get('userType'),
            password: formData.get('password'),
            confirmPassword: formData.get('confirmPassword')
        };

        console.log('📝 Datos del formulario:', userData);

        // Validar que las contraseñas coincidan
        if (userData.password !== userData.confirmPassword) {
            this.showError('Las contraseñas no coinciden');
            return;
        }

        // Deshabilitar el botón de envío
        const submitButton = event.target.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Registrando...';
        }

        try {
            // Llamar al servicio de autenticación
            const result = await window.authService.register(userData);
            
            console.log('📋 Resultado del registro:', result);

            if (result.success) {
                // Mostrar modal de confirmación de email
                this.showEmailConfirmationModal(userData.email);
                
                // También mostrar mensaje de éxito más prominente
                this.showSuccess('🎉 ¡Registro completado! Revisa tu email para confirmar tu cuenta.');
                
                // Ocultar el formulario y mostrar mensaje de confirmación
                this.showRegistrationComplete(userData.email);
            } else {
                this.showError(result.message || 'Error en el registro');
            }
        } catch (error) {
            console.error('❌ Error en el registro:', error);
            this.showError('Error de conexión. Inténtalo de nuevo.');
        } finally {
            // Rehabilitar el botón
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = 'Crear Cuenta';
            }
        }
    }

    showRegistrationComplete(email) {
        const form = document.getElementById('registerForm');
        if (form) {
            // Ocultar el formulario
            form.style.display = 'none';
            
            // Crear mensaje de completado
            const successContainer = document.createElement('div');
            successContainer.className = 'registration-complete';
            successContainer.innerHTML = `
                <div class="success-icon">🎉</div>
                <h2>¡Registro Completado!</h2>
                <p>Tu cuenta ha sido creada exitosamente.</p>
                <div class="email-notice">
                    <p><strong>📧 Email enviado a:</strong> ${email}</p>
                    <p>Revisa tu bandeja de entrada y confirma tu email para activar tu cuenta.</p>
                </div>
                <div class="actions">
                    <button class="btn-auth btn-primary" onclick="window.appRouter?.navigate('/login')">
                        🔐 Ir al Login
                    </button>
                </div>
            `;
            
            // Insertar después del formulario
            form.parentNode.insertBefore(successContainer, form.nextSibling);
        }
    }

    showEmailConfirmationModal(email) {
        // Usar la instancia global del modal
        if (window.emailConfirmationModal) {
            // Crear objeto usuario temporal para el modal
            const userObject = { email: email };
            window.emailConfirmationModal.show(userObject);
        } else {
            console.warn('EmailConfirmationModal no está disponible');
            // Fallback: mostrar alert simple
            alert(`¡Registro exitoso!\n\nHemos enviado un email de confirmación a: ${email}\n\nRevisa tu bandeja de entrada y confirma tu email para activar tu cuenta.`);
        }
    }

    showError(message) {
        // Mostrar mensaje de error
        const errorContainer = document.querySelector('.form-message') || this.createMessageContainer();
        errorContainer.className = 'form-message error';
        errorContainer.textContent = message;
        errorContainer.style.display = 'block';
        
        // Auto-ocultar después de 5 segundos
        setTimeout(() => {
            errorContainer.style.display = 'none';
        }, 5000);
    }

    showSuccess(message) {
        // Mostrar mensaje de éxito
        const successContainer = document.querySelector('.form-message') || this.createMessageContainer();
        successContainer.className = 'form-message success';
        successContainer.textContent = message;
        successContainer.style.display = 'block';
    }

    createMessageContainer() {
        const container = document.createElement('div');
        container.className = 'form-message';
        const form = document.getElementById('registerForm');
        if (form) {
            form.insertBefore(container, form.firstChild);
        }
        return container;
    }

    initializePasswordToggles() {
        // Implementar toggles para mostrar/ocultar contraseñas
        const passwordFields = document.querySelectorAll('input[type="password"]');
        passwordFields.forEach(field => {
            const container = field.parentElement;
            if (container && !container.querySelector('.password-toggle')) {
                const toggle = document.createElement('button');
                toggle.type = 'button';
                toggle.className = 'password-toggle';
                toggle.innerHTML = '👁️';
                toggle.addEventListener('click', () => {
                    const type = field.type === 'password' ? 'text' : 'password';
                    field.type = type;
                    toggle.innerHTML = type === 'password' ? '👁️' : '🙈';
                });
                container.appendChild(toggle);
            }
        });
    }

    initializePasswordStrength() {
        const passwordField = document.getElementById('registerPassword');
        if (passwordField) {
            passwordField.addEventListener('input', (e) => {
                // Implementar indicador de fuerza de contraseña
                const strength = this.calculatePasswordStrength(e.target.value);
                // Aquí puedes agregar lógica para mostrar la fuerza
                console.log('Fuerza de contraseña:', strength);
            });
        }
    }

    calculatePasswordStrength(password) {
        let score = 0;
        if (password.length >= 8) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;
        return score;
    }

    initializeNavigation() {
        // Agregar enlaces de navegación
        const loginLink = document.querySelector('.auth-link[href="#/login"]');
        if (loginLink) {
            loginLink.addEventListener('click', (e) => {
                e.preventDefault();
                if (window.appRouter) {
                    window.appRouter.navigate('/login');
                }
            });
        }
    }

    initializeCheckboxAnimation() {
        // Agregar animaciones a checkboxes si los hay
        const checkboxes = document.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const label = e.target.closest('label');
                if (label) {
                    label.classList.toggle('checked', e.target.checked);
                }
            });
        });
    }
}

// Exportar el componente
window.RegisterComponent = RegisterComponent;
