// Componente de Perfil
class ProfileComponent {
    constructor() {
        this.userData = null;
        this.isEditing = false;
        this.originalData = {};
    }
    async render(container) {
        // Usar arguments[0] como fallback si container está undefined
        const actualContainer = container || arguments[0];
        // Verificar que el container existe
        if (!actualContainer) {
            console.error('Container is undefined or null in ProfileComponent.render()');
            throw new Error('Container element is required for rendering');
        }
        try {
            // Verificar autenticación
            const isAuthenticated = await window.authService.isAuthenticated();
            if (!isAuthenticated) {
                if (window.appRouter) {
                    window.appRouter.navigate('/login');
                }
                return;
            }
            // Cargar HTML del componente
            const response = await fetch('/app/pages/profile/profile.component.html');
            if (!response.ok) {
                throw new Error(`Error cargando HTML: ${response.status} ${response.statusText}`);
            }
            const html = await response.text();
            actualContainer.innerHTML = html;
            // Cargar CSS del componente
            if (!document.querySelector('link[href*="profile.component.css"]')) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = '/app/pages/profile/profile.component.css';
                document.head.appendChild(link);
            }
            // Cargar datos del usuario
            await this.loadUserData();
            // Configurar event listeners
            this.setupEventListeners();
        } catch (error) {
            console.error('Error cargando perfil:', error);
            if (actualContainer) {
                actualContainer.innerHTML = `
                    <div class="error-container" style="padding: 40px; text-align: center; color: #dc3545;">
                        <h2>❌ Error cargando el perfil</h2>
                        <p><strong>Detalles:</strong> ${error.message}</p>
                        <button onclick="window.location.reload()" style="margin-top: 20px; padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                            🔄 Recargar página
                        </button>
                    </div>
                `;
            }
            throw error;
        }
    }
    async loadUserData() {
        try {
            // Obtener el token del authService
            const token = window.authService.getToken();
            const headers = {
                'Content-Type': 'application/json'
            };
            // Agregar el token JWT si existe
            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }
            const response = await fetch('/api/auth/get-profile.php', {
                method: 'GET',
                headers: headers,
                credentials: 'include'
            });
            if (response.ok) {
                const responseText = await response.text();
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (jsonError) {
                    console.error('JSON Parse Error:', jsonError);
                    console.error('Response text that failed to parse:', responseText);
                    throw new Error(`Invalid JSON response from server: ${jsonError.message}`);
                }
                if (result.success !== false && result.user) {
                    this.userData = result.user;
                    this.originalData = { ...result.user };
                    this.populateUserData();
                } else {
                    throw new Error(result.message || 'No se pudieron obtener los datos del usuario');
                }
            } else if (response.status === 401) {
                // Token expirado o inválido - redirigir al login
                window.authService.logout();
                if (window.appRouter) {
                    window.appRouter.navigate('/login');
                }
                return;
            } else {
                const errorText = await response.text();
                console.error('API Error:', errorText);
                throw new Error(`Error del servidor: ${response.status} ${response.statusText}`);
            }
        } catch (error) {
            console.error('Error loadUserData:', error);
            const profileContainer = document.querySelector('.profile-container');
            if (profileContainer) {
                profileContainer.innerHTML = `
                    <div class="error-container" style="padding: 40px; text-align: center; color: #dc3545;">
                        <h2>❌ Error cargando datos del usuario</h2>
                        <p>${error.message}</p>
                        <button onclick="window.location.reload()" style="margin-top: 20px; padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                            🔄 Recargar página
                        </button>
                        <button onclick="window.appRouter.navigate('/')" style="margin-top: 10px; margin-left: 10px; padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">
                            🏠 Ir al inicio
                        </button>
                    </div>
                `;
            }
            throw error;
        }
    }
    populateUserData() {
        if (!this.userData) return;
        // Actualizar header
        const userName = document.getElementById('profileUserName');
        if (userName) {
            userName.textContent = `${this.userData.first_name} ${this.userData.last_name}`;
        }
        // Actualizar imagen de perfil
        this.updateProfileImage();
        // Estado de verificación de email
        const emailStatus = document.getElementById('emailVerificationStatus');
        if (emailStatus) {
            if (this.userData.email_verified) {
                emailStatus.textContent = 'Email verificado';
                emailStatus.className = 'email-status verified';
            } else {
                emailStatus.textContent = 'Email pendiente de verificación';
                emailStatus.className = 'email-status unverified';
            }
        }
        // Llenar formulario
        const fields = [
            { id: 'profileFirstName', value: this.userData.first_name },
            { id: 'profileLastName', value: this.userData.last_name },
            { id: 'profileEmail', value: this.userData.email },
            { id: 'profilePhone', value: this.userData.phone },
            { id: 'profileIsland', value: this.userData.island },
            { id: 'profileCity', value: this.userData.city },
            { id: 'profileUserType', value: this.userData.user_type }
        ];
        fields.forEach(field => {
            const element = document.getElementById(field.id);
            if (element) {
                element.value = field.value || '';
            }
        });
    }
    updateProfileImage() {
        const avatarIcon = document.getElementById('avatarIcon');
        const avatarImage = document.getElementById('avatarImage');
        
        if (this.userData.profile_image) {
            // Mostrar imagen con cache-busting
            if (avatarImage) {
                const imageUrl = `/${this.userData.profile_image}?t=${Date.now()}`;
                
                // Aplicar estilos ANTES de cargar la imagen
                avatarImage.style.width = '80px';
                avatarImage.style.height = '80px';
                avatarImage.style.objectFit = 'cover';
                avatarImage.style.borderRadius = '50%';
                avatarImage.style.display = 'block';
                
                // Forzar dimensiones cuando la imagen carga
                avatarImage.onload = () => {
                    avatarImage.style.width = '80px';
                    avatarImage.style.height = '80px';
                    avatarImage.style.objectFit = 'cover';
                };
                
                // Cargar la imagen DESPUÉS de aplicar estilos
                avatarImage.src = imageUrl;
            }
            if (avatarIcon) {
                avatarIcon.style.display = 'none';
            }
        } else {
            // Mostrar icono por defecto
            if (avatarImage) {
                avatarImage.style.display = 'none';
            }
            if (avatarIcon) {
                avatarIcon.style.display = 'flex';
            }
        }
    }
    setupEventListeners() {
        // Botón editar
        const editBtn = document.getElementById('editProfileBtn');
        if (editBtn) {
            editBtn.addEventListener('click', () => this.startEdit());
        }
        // Botón guardar
        const saveBtn = document.getElementById('saveProfileBtn');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.saveProfile());
        }
        // Botón cancelar
        const cancelBtn = document.getElementById('cancelEditBtn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => this.cancelEdit());
        }
        // Botón cambiar contraseña
        const changePasswordBtn = document.getElementById('changePasswordBtn');
        if (changePasswordBtn) {
            changePasswordBtn.addEventListener('click', () => this.showChangePasswordModal());
        }
        // Botón eliminar cuenta
        const deleteAccountBtn = document.getElementById('deleteAccountBtn');
        if (deleteAccountBtn) {
            deleteAccountBtn.addEventListener('click', () => this.showDeleteAccountModal());
        }
        // Configurar toggles de contraseña para modales
        this.setupPasswordToggles();
        // Configurar subida de imagen de perfil
        this.setupProfileImageUpload();
    }
    setupProfileImageUpload() {
        const profileImageUpload = document.getElementById('profileImageUpload');
        if (profileImageUpload) {
            profileImageUpload.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    this.uploadProfileImageFile(file);
                }
            });
        }
    }
    async uploadProfileImageFile(file) {
        // Validaciones del archivo
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        const maxSize = 5 * 1024 * 1024; // 5MB
        if (!allowedTypes.includes(file.type)) {
            this.showUploadError('Formato no permitido. Solo se permiten imágenes en formato: JPG, PNG, WebP o GIF');
            return;
        }
        if (file.size > maxSize) {
            this.showUploadError('El archivo es demasiado grande. Máximo 5MB');
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('profile_image', file);
            const response = await fetch('/api/auth/upload-profile-image.php', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Authorization': `Bearer ${window.authService.getToken()}`
                },
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                // Actualizar imagen en la interfaz del perfil
                this.userData.profile_image = result.profile_image;
                this.updateProfileImage();
                
                // Actualizar el usuario en authService
                const currentUser = window.authService.currentUser;
                if (currentUser) {
                    currentUser.profileImage = result.profile_image;
                    currentUser.profile_image = result.profile_image;
                }
                
                // Actualizar solo el avatar del header sin regenerar todo
                if (window.headerComponent && typeof window.headerComponent.updateUserAvatar === 'function') {
                    setTimeout(() => {
                        window.headerComponent.updateUserAvatar(result.profile_image);
                    }, 100);
                }
                
                this.showUploadSuccess();
            } else {
                this.showUploadError('Error: ' + result.message);
            }
        } catch (error) {
            console.error('Error subiendo imagen:', error);
            this.showUploadError('Error subiendo la imagen. Inténtalo de nuevo.');
        }
    }
    
    showUploadSuccess() {
        const modal = document.getElementById('imageUploadSuccessModal');
        if (modal) {
            modal.style.display = 'flex';
            
            const closeBtn = document.getElementById('closeImageUploadSuccess');
            if (closeBtn) {
                closeBtn.onclick = () => {
                    modal.style.display = 'none';
                };
            }
            
            // Cerrar también al hacer click fuera del modal
            modal.onclick = (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            };
        }
    }
    
    showUploadError(message) {
        // Usar el sistema de notificaciones existente para errores
        this.showNotification(message, 'error');
    }

    setupPasswordToggles() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('password-toggle')) {
                const targetId = e.target.getAttribute('data-target');
                if (targetId) {
                    const input = document.getElementById(targetId);
                    if (input) {
                        if (input.type === 'password') {
                            input.type = 'text';
                            e.target.textContent = '🙈';
                        } else {
                            input.type = 'password';
                            e.target.textContent = '👁️';
                        }
                    }
                }
            }
        });
    }
    startEdit() {
        this.isEditing = true;
        this.toggleEditMode();
    }
    toggleEditMode() {
        // Habilitar/deshabilitar campos
        const fields = ['profileFirstName', 'profileLastName', 'profilePhone', 'profileIsland', 'profileCity', 'profileUserType'];
        fields.forEach(fieldId => {
            const element = document.getElementById(fieldId);
            if (element) {
                element.disabled = !this.isEditing;
            }
        });
        // Mostrar/ocultar botones
        const editBtn = document.getElementById('editProfileBtn');
        const saveBtn = document.getElementById('saveProfileBtn');
        const cancelBtn = document.getElementById('cancelEditBtn');
        const avatarUpload = document.getElementById('avatarUpload');
        if (this.isEditing) {
            if (editBtn) editBtn.style.display = 'none';
            if (saveBtn) saveBtn.style.display = 'inline-flex';
            if (cancelBtn) cancelBtn.style.display = 'inline-flex';
            if (avatarUpload) avatarUpload.style.display = 'block';
            // Añadir clase al contenedor
            const section = document.querySelector('.profile-section');
            if (section) section.classList.add('editing');
        } else {
            if (editBtn) editBtn.style.display = 'inline-flex';
            if (saveBtn) saveBtn.style.display = 'none';
            if (cancelBtn) cancelBtn.style.display = 'none';
            if (avatarUpload) avatarUpload.style.display = 'none';
            // Remover clase del contenedor
            const section = document.querySelector('.profile-section');
            if (section) section.classList.remove('editing');
        }
    }
    cancelEdit() {
        this.isEditing = false;
        this.toggleEditMode();
        this.populateUserData(); // Restaurar datos originales
    }
    async saveProfile() {
        if (!this.isEditing) return;
        
        // Habilitar temporalmente todos los campos para que FormData los capture
        const userTypeField = document.getElementById('profileUserType');
        const wasDisabled = userTypeField?.disabled;
        if (userTypeField && wasDisabled) {
            userTypeField.disabled = false;
        }
        
        const formData = new FormData(document.getElementById('profileForm'));
        
        // Restaurar el estado disabled si era necesario
        if (userTypeField && wasDisabled) {
            userTypeField.disabled = true;
        }
        
        const userData = {
            firstName: formData.get('firstName'),
            lastName: formData.get('lastName'),
            phone: formData.get('phone'),
            island: formData.get('island'),
            city: formData.get('city'),
            userType: formData.get('userType') || userTypeField?.value
        };
        
        // Debug: Mostrar datos que se enviarán
        console.log('🔍 Datos del perfil a enviar:', userData);
        console.log('🔍 userType específicamente:', userData.userType);
        
        // Validar datos
        const errors = this.validateProfileData(userData);
        if (errors.length > 0) {
            this.showNotification('Errores en el formulario:', 'error', errors);
            return;
        }
        // Mostrar loading
        const saveBtn = document.getElementById('saveProfileBtn');
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="btn-icon">⏳</span> Guardando...';
        }
        try {
            const response = await fetch('/api/auth/update-profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify(userData)
            });
            
            const result = await response.json();
            
            console.log('📥 Respuesta del servidor:', result);
            console.log('📥 Usuario devuelto:', result.user);
            if (result.user) {
                console.log('📥 user_type en respuesta:', result.user.user_type);
            }
            
            if (response.ok) {
                this.userData = result.user;
                this.originalData = { ...result.user };
                this.isEditing = false;
                this.toggleEditMode();
                this.showNotification('✅ Perfil actualizado correctamente', 'success');
            } else {
                this.showNotification('error', result.message || 'Error al actualizar el perfil');
            }
        } catch (error) {
            console.error('Error actualizando perfil:', error);
            this.showNotification('Error de conexión. Inténtalo de nuevo.', 'error');
        } finally {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<span class="btn-icon">💾</span> Guardar Cambios';
            }
        }
    }
    validateProfileData(data) {
        const errors = [];
        if (!data.firstName?.trim()) {
            errors.push('El nombre es requerido');
        }
        if (!data.lastName?.trim()) {
            errors.push('Los apellidos son requeridos');
        }
        if (!data.phone?.trim()) {
            errors.push('El teléfono es requerido');
        } else if (!/^[\+]?[\d\s\-\(\)]+$/.test(data.phone)) {
            errors.push('Formato de teléfono inválido');
        }
        if (!data.island) {
            errors.push('Debes seleccionar una isla');
        }
        if (!data.city?.trim()) {
            errors.push('La ciudad es requerida');
        }
        if (!data.userType) {
            errors.push('Debes seleccionar un tipo de usuario');
        }
        return errors;
    }
    showChangePasswordModal() {
        const modal = document.getElementById('changePasswordModal');
        if (modal) {
            modal.style.display = 'flex';
            // Limpiar campos
            const inputs = modal.querySelectorAll('input[type="password"]');
            inputs.forEach(input => input.value = '');
            // Configurar event listeners
            const closeBtn = modal.querySelector('.modal-close');
            const cancelBtn = modal.querySelector('#cancelPasswordChange');
            const submitBtn = modal.querySelector('#submitPasswordChange');
            const closeModal = () => {
                modal.style.display = 'none';
                inputs.forEach(input => input.value = '');
                this.clearPasswordValidation(modal);
            };
            if (closeBtn) closeBtn.onclick = closeModal;
            if (cancelBtn) cancelBtn.onclick = closeModal;
            if (submitBtn) {
                submitBtn.onclick = (e) => {
                    e.preventDefault();
                    this.changePassword();
                };
            }
            // Configurar validación en tiempo real
            this.setupPasswordValidation(modal);
            // Focus en el primer campo
            const firstInput = modal.querySelector('#currentPasswordInput');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }
    }
    setupPasswordValidation(modal) {
        const newPasswordInput = modal.querySelector('#newPasswordInput');
        const confirmPasswordInput = modal.querySelector('#confirmPasswordInput');
        if (newPasswordInput) {
            newPasswordInput.addEventListener('input', () => {
                this.validatePasswordRequirements(newPasswordInput.value, modal);
            });
        }
        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', () => {
                this.validatePasswordMatch(newPasswordInput.value, confirmPasswordInput.value, modal);
            });
        }
    }
    validatePasswordRequirements(password, modal) {
        const requirements = [
            { id: 'lengthReq', test: password.length >= 8 },
            { id: 'uppercaseReq', test: /[A-Z]/.test(password) },
            { id: 'lowercaseReq', test: /[a-z]/.test(password) },
            { id: 'numberReq', test: /\d/.test(password) }
        ];
        requirements.forEach(req => {
            const element = modal.querySelector(`#${req.id}`);
            if (element) {
                if (req.test) {
                    element.classList.add('valid');
                } else {
                    element.classList.remove('valid');
                }
            }
        });
    }
    validatePasswordMatch(newPassword, confirmPassword, modal) {
        const matchElement = modal.querySelector('#passwordMatch');
        if (matchElement && confirmPassword) {
            if (newPassword === confirmPassword) {
                matchElement.textContent = '✓ Las contraseñas coinciden';
                matchElement.className = 'validation-message success';
                matchElement.style.display = 'block';
            } else {
                matchElement.textContent = '✗ Las contraseñas no coinciden';
                matchElement.className = 'validation-message error';
                matchElement.style.display = 'block';
            }
        }
    }
    clearPasswordValidation(modal) {
        const requirements = modal.querySelectorAll('.requirement');
        requirements.forEach(req => req.classList.remove('valid'));
        const matchElement = modal.querySelector('#passwordMatch');
        if (matchElement) {
            matchElement.style.display = 'none';
        }
    }
    async changePassword() {
        const modal = document.getElementById('changePasswordModal');
        const currentPassword = modal.querySelector('#currentPasswordInput').value;
        const newPassword = modal.querySelector('#newPasswordInput').value;
        const confirmPassword = modal.querySelector('#confirmPasswordInput').value;
        // Validaciones
        if (!currentPassword || !newPassword || !confirmPassword) {
            this.showNotification('Todos los campos son requeridos', 'error');
            return;
        }
        if (newPassword !== confirmPassword) {
            this.showNotification('Las contraseñas nuevas no coinciden', 'error');
            return;
        }
        if (newPassword.length < 8) {
            this.showNotification('La nueva contraseña debe tener al menos 8 caracteres', 'error');
            return;
        }
        const submitBtn = modal.querySelector('#submitPasswordChange');
        try {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="btn-icon">⏳</span> Cambiando...';
            }
            const response = await fetch('/api/auth/change-password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify({
                    currentPassword,
                    newPassword
                })
            });
            const result = await response.json();
            if (response.ok) {
                modal.style.display = 'none';
                this.showNotification('✅ Contraseña cambiada correctamente', 'success');
            } else {
                this.showNotification('error', result.message || 'Error al cambiar la contraseña');
            }
        } catch (error) {
            console.error('Error cambiando contraseña:', error);
            this.showNotification('Error de conexión. Inténtalo de nuevo.', 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span class="btn-icon">💾</span> Cambiar Contraseña';
            }
        }
    }
    showDeleteAccountModal() {
        const modal = document.getElementById('deleteAccountModal');
        if (modal) {
            modal.style.display = 'flex';
            // Configurar event listeners
            const closeBtn = modal.querySelector('.modal-close');
            const cancelBtn = modal.querySelector('#cancelAccountDelete');
            const confirmBtn = modal.querySelector('#confirmAccountDelete');
            const passwordInput = modal.querySelector('#deletePasswordInput');
            // Limpiar campo de contraseña
            if (passwordInput) {
                passwordInput.value = '';
            }
            const closeModal = () => {
                modal.style.display = 'none';
                if (passwordInput) passwordInput.value = '';
            };
            if (closeBtn) closeBtn.onclick = closeModal;
            if (cancelBtn) cancelBtn.onclick = closeModal;
            if (confirmBtn) {
                confirmBtn.onclick = (e) => {
                    e.preventDefault();
                    this.deleteAccount();
                };
            }
            // Focus en el campo de contraseña
            if (passwordInput) {
                setTimeout(() => passwordInput.focus(), 100);
            }
        }
    }
    async deleteAccount() {
        const passwordInput = document.getElementById('deletePasswordInput');
        const password = passwordInput?.value;
        if (!password) {
            this.showNotification('Debes ingresar tu contraseña para confirmar', 'error');
            return;
        }
        const deleteBtn = document.querySelector('#confirmAccountDelete');
        try {
            if (deleteBtn) {
                deleteBtn.disabled = true;
                deleteBtn.innerHTML = '<span class="btn-icon">⏳</span> Eliminando...';
            }
            const response = await fetch('/api/auth/delete-account.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify({ password })
            });
            const result = await response.json();
            if (response.ok) {
                // Cerrar modal
                const modal = document.getElementById('deleteAccountModal');
                if (modal) {
                    modal.style.display = 'none';
                }
                // Cerrar sesión y redirigir
                await window.authService.logout();
                this.showNotification('✅ Cuenta eliminada exitosamente', 'success');
                setTimeout(() => {
                    if (window.appRouter) {
                        window.appRouter.navigate('/');
                    }
                }, 2000);
            } else {
                this.showNotification(result.message || 'Error al eliminar la cuenta', 'error');
            }
        } catch (error) {
            console.error('Error eliminando cuenta:', error);
            this.showNotification('Error de conexión. Inténtalo de nuevo.', 'error');
        } finally {
            if (deleteBtn) {
                deleteBtn.disabled = false;
                deleteBtn.innerHTML = '<span class="btn-icon">🗑️</span> Eliminar Cuenta Permanentemente';
            }
        }
    }
    showNotification(message, type = 'info', details = null) {
        if (window.notificationModal) {
            if (type === 'error') {
                window.notificationModal.showError(message, details);
            } else if (type === 'success') {
                window.notificationModal.showSuccess(message, details);
            } else {
                window.notificationModal.showInfo(message, details);
            }
        } else {
            // Fallback
            alert(message);
        }
    }
}
// Exportar el componente
window.ProfileComponent = ProfileComponent;
