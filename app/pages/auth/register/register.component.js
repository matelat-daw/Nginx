//
// Register Component - Componente de registro de usuarios
class RegisterComponent {
    constructor() {
        this.cssLoaded = false;
    }
    render() {
        // Devuelve un contenedor, el HTML se inyecta en afterRender
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
        }, 100);
        // Firefox fix: Inicialización adicional más tarde
        setTimeout(() => {
            this.forceInitializeForFirefox();
        }, 500);
        // Observer para detectar cambios en el DOM (especialmente útil para Firefox)
        this.setupDOMObserver();
    }
    setupDOMObserver() {
        const isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;
        if (!isFirefox) return;
        const container = document.querySelector('.auth-component.register-component');
        if (!container) return;
        const observer = new MutationObserver((mutations) => {
            let shouldReinitialize = false;
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            if (node.id === 'toggleRegisterPassword' || 
                                node.id === 'registerPassword' ||
                                node.querySelector('#toggleRegisterPassword') ||
                                node.querySelector('#registerPassword')) {
                                shouldReinitialize = true;
                            }
                        }
                    });
                }
            });
            if (shouldReinitialize) {
                setTimeout(() => {
                    this.configurePasswordToggle('toggleRegisterPassword', 'registerPassword');
                    this.configurePasswordToggle('toggleConfirmPassword', 'confirmPassword');
                    this.forcePasswordStrengthFirefox();
                }, 100);
            }
        });
        observer.observe(container, {
            childList: true,
            subtree: true
        });
        // Desconectar después de 10 segundos
        setTimeout(() => {
            observer.disconnect();
        }, 10000);
    }
    getElement() {
        return document.querySelector('.auth-component.register-component');
    }
    async waitForAuthServiceAndInitialize() {
        // Intentar hasta 50 veces (5 segundos)
        for (let i = 0; i < 50; i++) {
            if (window.authService && typeof window.authService.register === 'function') {
                break;
            }
            if (typeof window.AuthService === 'function' && !window.authService) {
                try {
                    window.authService = new window.AuthService();
                    break;
                } catch (error) {
                    console.error('❌ RegisterComponent: Error creando AuthService:', error);
                }
            }
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        // Detectar Firefox para ajustes específicos
        const isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;
        this.initializeForm();
        this.initializePasswordToggles();
        this.initializePasswordStrength();
        this.initializeNavigation();
        this.initializeCheckboxAnimation();
    }
    forceInitializeForFirefox() {
        const isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;
        if (!isFirefox) return;
        // Verificar si los elementos existen
        const toggleRegisterPassword = document.getElementById('toggleRegisterPassword');
        const registerPassword = document.getElementById('registerPassword');
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const confirmPassword = document.getElementById('confirmPassword');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        // Si no están, forzar re-inicialización
        if (!toggleRegisterPassword || !registerPassword || !strengthFill) {
            setTimeout(() => {
                this.forcePasswordTogglesFirefox();
                this.forcePasswordStrengthFirefox();
            }, 200);
        } else {
            this.configurePasswordToggle('toggleRegisterPassword', 'registerPassword');
            this.configurePasswordToggle('toggleConfirmPassword', 'confirmPassword');
            this.forcePasswordStrengthFirefox();
        }
    }
    forcePasswordStrengthFirefox() {
        const passwordField = document.getElementById('registerPassword');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        if (passwordField && strengthFill && strengthText) {
            // Remover eventos anteriores clonando el elemento
            const newPasswordField = passwordField.cloneNode(true);
            passwordField.parentNode.replaceChild(newPasswordField, passwordField);
            // IMPORTANTE: Configurar el toggle para el campo clonado
            this.configurePasswordToggle('toggleRegisterPassword', 'registerPassword');
            newPasswordField.addEventListener('input', (e) => {
                const password = e.target.value;
                const strength = this.calculatePasswordStrength(password);
                const percentage = (strength.score / 5) * 100;
                strengthFill.style.width = percentage + '%';
                let color, text;
                switch (strength.score) {
                    case 0:
                    case 1:
                        color = '#ff4444';
                        text = 'Muy débil';
                        break;
                    case 2:
                        color = '#ff8800';
                        text = 'Débil';
                        break;
                    case 3:
                        color = '#ffdd00';
                        text = 'Regular';
                        break;
                    case 4:
                        color = '#88cc00';
                        text = 'Fuerte';
                        break;
                    case 5:
                        color = '#00cc44';
                        text = 'Muy fuerte';
                        break;
                }
                strengthFill.style.backgroundColor = color;
                strengthText.textContent = text;
                if (strength.recommendations.length > 0) {
                    strengthText.textContent += ' - ' + strength.recommendations.join(', ');
                }
            });
        } else {
            console.error('❌ Firefox: No se encontraron elementos de fortaleza');
        }
    }
    configurePasswordToggle(toggleId, inputId) {
        const toggleButton = document.getElementById(toggleId);
        const inputField = document.getElementById(inputId);
        if (!toggleButton || !inputField) {
            console.error(`❌ No se encontraron elementos: ${toggleId} o ${inputId}`);
            return;
        }
        // Detectar Firefox
        const isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;
        // Limpiar eventos anteriores clonando el botón
        const newToggleButton = toggleButton.cloneNode(true);
        toggleButton.parentNode.replaceChild(newToggleButton, toggleButton);
        // Configurar el evento click
        newToggleButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const currentType = inputField.type;
            const newType = currentType === 'password' ? 'text' : 'password';
            if (isFirefox) {
                // Firefox: usar método focus/blur para forzar re-render
                const selectionStart = inputField.selectionStart;
                const selectionEnd = inputField.selectionEnd;
                const currentValue = inputField.value;
                inputField.blur();
                inputField.type = newType;
                setTimeout(() => {
                    inputField.focus();
                    inputField.value = currentValue;
                    if (selectionStart !== undefined && selectionEnd !== undefined) {
                        inputField.setSelectionRange(selectionStart, selectionEnd);
                    }
                }, 10);
            } else {
                // Otros navegadores: método normal
                inputField.type = newType;
            }
            // Actualizar icono
            const eyeIcon = newToggleButton.querySelector('.eye-icon');
            if (eyeIcon) {
                eyeIcon.textContent = newType === 'password' ? '👁️' : '🙈';
            }
        });
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
        // Configurar preview de imagen de perfil
        this.setupImagePreview();
    }
    setupImagePreview() {
        const fileInput = document.getElementById('registerProfileImage');
        const imagePreview = document.getElementById('imagePreview');
        const profileImageError = document.getElementById('profileImageError');
        if (!fileInput || !imagePreview) {
            console.warn('⚠️ Elementos de imagen de perfil no encontrados');
            return;
        }
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            profileImageError.textContent = '';
            if (!file) {
                this.resetImagePreview();
                return;
            }
            // Validaciones del archivo
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            const maxSize = 5 * 1024 * 1024; // 5MB
            if (!allowedTypes.includes(file.type)) {
                profileImageError.textContent = 'Tipo de archivo no permitido. Solo se permiten: JPG, PNG, GIF, WebP';
                this.resetImagePreview();
                fileInput.value = '';
                return;
            }
            if (file.size > maxSize) {
                profileImageError.textContent = 'El archivo es demasiado grande. Máximo 5MB';
                this.resetImagePreview();
                fileInput.value = '';
                return;
            }
            // Mostrar preview
            const reader = new FileReader();
            reader.onload = (e) => {
                imagePreview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                imagePreview.classList.add('has-image');
            };
            reader.readAsDataURL(file);
        });
    }
    resetImagePreview() {
        const imagePreview = document.getElementById('imagePreview');
        if (imagePreview) {
            imagePreview.innerHTML = `
                <div class="preview-placeholder">
                    <span class="preview-icon">👤</span>
                    <span class="preview-text">Subir foto</span>
                </div>
            `;
            imagePreview.classList.remove('has-image');
        }
    }
    async handleSubmit(event) {
        event.preventDefault();
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
        // Validaciones detalladas
        const validationErrors = this.validateFormData(userData);
        if (validationErrors.length > 0) {
            // Mostrar errores de validación
            if (window.notificationModal) {
                window.notificationModal.showError(
                    'Por favor, corrige los siguientes errores:',
                    validationErrors
                );
            } else {
                this.showError('Error en el formulario: ' + validationErrors.join(', '));
            }
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
            if (result.success) {
                // Verificar si hay imagen de perfil para subir
                const profileImageFile = formData.get('profileImage');
                if (profileImageFile && profileImageFile.size > 0) {
                    await this.uploadProfileImage(profileImageFile);
                }
                // Ocultar cualquier modal de error previo
                if (window.notificationModal) {
                    window.notificationModal.hide();
                }
                // Mostrar modal de confirmación de email
                this.showEmailConfirmationModal(userData.email);
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
    async uploadProfileImage(file) {
        try {
            const formData = new FormData();
            formData.append('profile_image', file);
            const response = await fetch('/api/auth/upload-profile-image.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
            } else {
                console.warn('⚠️ Error subiendo imagen de perfil:', result.message);
                // No bloqueamos el registro por error en imagen, solo lo logueamos
            }
        } catch (error) {
            console.error('❌ Error subiendo imagen de perfil:', error);
            // No bloqueamos el registro por error en imagen
        }
    }
    validateFormData(userData) {
        const errors = [];
        // Validar campos requeridos
        if (!userData.firstName?.trim()) {
            errors.push('El nombre es requerido');
        }
        if (!userData.lastName?.trim()) {
            errors.push('Los apellidos son requeridos');
        }
        if (!userData.email?.trim()) {
            errors.push('El email es requerido');
        } else if (!this.isValidEmail(userData.email)) {
            errors.push('El formato del email no es válido');
        }
        if (!userData.phone?.trim()) {
            errors.push('El teléfono es requerido');
        } else if (!this.isValidPhone(userData.phone)) {
            errors.push('El formato del teléfono no es válido');
        }
        if (!userData.island?.trim()) {
            errors.push('Debes seleccionar una isla');
        }
        if (!userData.city?.trim()) {
            errors.push('La ciudad es requerida');
        }
        if (!userData.userType?.trim()) {
            errors.push('Debes seleccionar un tipo de usuario');
        }
        if (!userData.password?.trim()) {
            errors.push('La contraseña es requerida');
        } else if (userData.password.length < 8) {
            errors.push('La contraseña debe tener al menos 8 caracteres');
        }
        if (!userData.confirmPassword?.trim()) {
            errors.push('Debes confirmar la contraseña');
        } else if (userData.password !== userData.confirmPassword) {
            errors.push('Las contraseñas no coinciden');
        }
        return errors;
    }
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    isValidPhone(phone) {
        // Permitir números españoles y canarios
        const phoneRegex = /^(\+34|0034|34)?[6-9]\d{8}$/;
        return phoneRegex.test(phone.replace(/\s/g, ''));
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
        // Usar el modal de notificaciones si está disponible
        if (window.notificationModal) {
            window.notificationModal.showError(message);
        } else {
            // Fallback: crear un contenedor temporal de error
            const errorContainer = document.querySelector('.form-message') || this.createMessageContainer();
            errorContainer.className = 'form-message error';
            errorContainer.textContent = message;
            errorContainer.style.display = 'block';
            // Auto-ocultar después de 5 segundos
            setTimeout(() => {
                errorContainer.style.display = 'none';
            }, 5000);
        }
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
        // Detectar Firefox para usar método especial
        const isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;
        // Esperamos un poco más para asegurar que todo esté listo
        setTimeout(() => {
            // Toggle para contraseña principal
            const toggleRegisterPassword = document.getElementById('toggleRegisterPassword');
            const registerPassword = document.getElementById('registerPassword');
            if (toggleRegisterPassword && registerPassword) {
                // Firefox compatibility: usar múltiples event types
                ['click', 'mousedown'].forEach(eventType => {
                    toggleRegisterPassword.addEventListener(eventType, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        if (eventType === 'click') { // Solo cambiar en click, no en mousedown
                            const currentType = registerPassword.type;
                            const newType = currentType === 'password' ? 'text' : 'password';
                            if (isFirefox) {
                                // Firefox: usar método focus/blur para forzar re-render
                                const selectionStart = registerPassword.selectionStart;
                                const selectionEnd = registerPassword.selectionEnd;
                                const currentValue = registerPassword.value;
                                registerPassword.blur();
                                registerPassword.type = newType;
                                setTimeout(() => {
                                    registerPassword.focus();
                                    registerPassword.value = currentValue;
                                    if (selectionStart !== undefined && selectionEnd !== undefined) {
                                        registerPassword.setSelectionRange(selectionStart, selectionEnd);
                                    }
                                }, 10);
                            } else {
                                // Otros navegadores: método normal
                                registerPassword.type = newType;
                            }
                            const eyeIcon = toggleRegisterPassword.querySelector('.eye-icon');
                            if (eyeIcon) {
                                eyeIcon.textContent = newType === 'password' ? '👁️' : '🙈';
                            }
                        }
                    });
                });
                // Prevenir submit del form cuando se hace click en el botón
                toggleRegisterPassword.setAttribute('type', 'button');
                toggleRegisterPassword.setAttribute('tabindex', '-1');
            } else {
                console.error('❌ No se encontraron elementos para toggle de registerPassword');
            }
            // Toggle para confirmar contraseña
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const confirmPassword = document.getElementById('confirmPassword');
            if (toggleConfirmPassword && confirmPassword) {
                // Firefox compatibility: usar múltiples event types
                ['click', 'mousedown'].forEach(eventType => {
                    toggleConfirmPassword.addEventListener(eventType, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        if (eventType === 'click') { // Solo cambiar en click, no en mousedown
                            const currentType = confirmPassword.type;
                            const newType = currentType === 'password' ? 'text' : 'password';
                            if (isFirefox) {
                                // Firefox: usar método focus/blur para forzar re-render
                                const selectionStart = confirmPassword.selectionStart;
                                const selectionEnd = confirmPassword.selectionEnd;
                                const currentValue = confirmPassword.value;
                                confirmPassword.blur();
                                confirmPassword.type = newType;
                                setTimeout(() => {
                                    confirmPassword.focus();
                                    confirmPassword.value = currentValue;
                                    if (selectionStart !== undefined && selectionEnd !== undefined) {
                                        confirmPassword.setSelectionRange(selectionStart, selectionEnd);
                                    }
                                }, 10);
                            } else {
                                // Otros navegadores: método normal
                                confirmPassword.type = newType;
                            }
                            const eyeIcon = toggleConfirmPassword.querySelector('.eye-icon');
                            if (eyeIcon) {
                                eyeIcon.textContent = newType === 'password' ? '👁️' : '🙈';
                            }
                        }
                    });
                });
                // Prevenir submit del form cuando se hace click en el botón
                toggleConfirmPassword.setAttribute('type', 'button');
                toggleConfirmPassword.setAttribute('tabindex', '-1');
            } else {
                console.error('❌ No se encontraron elementos para toggle de confirmPassword');
            }
        }, 200); // Aumentamos el delay para Firefox
    }
    initializePasswordStrength() {
        const passwordField = document.getElementById('registerPassword');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        if (passwordField && strengthFill && strengthText) {
            passwordField.addEventListener('input', (e) => {
                const password = e.target.value;
                const strength = this.calculatePasswordStrength(password);
                // Actualizar barra de fortaleza
                const percentage = (strength.score / 5) * 100;
                strengthFill.style.width = percentage + '%';
                // Actualizar color y texto
                let color, text;
                switch (strength.score) {
                    case 0:
                    case 1:
                        color = '#ff4444';
                        text = 'Muy débil';
                        break;
                    case 2:
                        color = '#ff8800';
                        text = 'Débil';
                        break;
                    case 3:
                        color = '#ffdd00';
                        text = 'Regular';
                        break;
                    case 4:
                        color = '#88cc00';
                        text = 'Fuerte';
                        break;
                    case 5:
                        color = '#00cc44';
                        text = 'Muy fuerte';
                        break;
                }
                strengthFill.style.backgroundColor = color;
                strengthText.textContent = text;
                // Mostrar recomendaciones
                if (strength.recommendations.length > 0) {
                    strengthText.textContent += ' - ' + strength.recommendations.join(', ');
                }
            });
        } else {
            console.error('❌ No se encontraron elementos para password strength');
        }
    }
    calculatePasswordStrength(password) {
        let score = 0;
        const recommendations = [];
        if (password.length >= 8) {
            score++;
        } else {
            recommendations.push('mín. 8 caracteres');
        }
        if (/[a-z]/.test(password)) {
            score++;
        } else {
            recommendations.push('minúsculas');
        }
        if (/[A-Z]/.test(password)) {
            score++;
        } else {
            recommendations.push('mayúsculas');
        }
        if (/[0-9]/.test(password)) {
            score++;
        } else {
            recommendations.push('números');
        }
        if (/[^A-Za-z0-9]/.test(password)) {
            score++;
        } else {
            recommendations.push('símbolos');
        }
        return { score, recommendations };
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
