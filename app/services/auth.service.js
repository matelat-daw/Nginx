// Auth Service Optimizado - Economía Circular Canarias
class AuthService {
    constructor() {
        // Configuración de endpoints
        this.endpoints = {
            register: '/api/auth/register.php',
            login: '/api/auth/login.php',
            logout: '/api/auth/logout.php',
            validate: '/api/auth/validate.php'
        };
        
        this.currentUser = null;
        this.token = null;
        
        console.log('🔧 AuthService optimizado creado (pendiente de inicialización)');
    }

    // Función helper para construir URLs del API
    getApiUrl(endpoint) {
        return this.endpoints[endpoint] || '';
    }

    // Obtener token de la cookie
    getTokenFromCookie() {
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === 'ecc_auth_token') {
                console.log('🍪 Token encontrado en cookie');
                return value;
            }
        }
        console.log('🍪 No se encontró token en cookies');
        return null;
    }

    // Inicialización mejorada con verificación automática
    async init() {
        try {
            console.log('🔧 Inicializando AuthService...');
            this.token = this.getTokenFromCookie();
            
            if (this.token) {
                console.log('🍪 Token encontrado, validando automáticamente...');
                const isValid = await this.validateToken();
                
                if (isValid) {
                    console.log('✅ Token válido, usuario autenticado automáticamente');
                    // Forzar actualización inmediata del header si existe
                    this.updateHeaderAuthState();
                } else {
                    console.log('❌ Token inválido, limpiando estado');
                    this.clearAuthState();
                }
            } else {
                console.log('🔍 No hay token, usuario no autenticado');
                this.clearAuthState();
            }
        } catch (error) {
            console.error('❌ Error en init():', error);
            this.clearAuthState();
        }
    }

    // Limpiar estado de autenticación
    clearAuthState() {
        this.token = null;
        this.currentUser = null;
        this.updateHeaderAuthState();
    }

    // Actualizar estado del header
    updateHeaderAuthState() {
        if (window.headerComponent) {
            if (typeof window.headerComponent.forceAuthUpdate === 'function') {
                window.headerComponent.forceAuthUpdate();
            } else if (typeof window.headerComponent.refreshAuthState === 'function') {
                window.headerComponent.refreshAuthState();
            }
        }
    }

    // Manejar redirección después del login
    handlePostLoginRedirect() {
        try {
            const redirectTo = sessionStorage.getItem('redirectAfterLogin');
            
            if (redirectTo && redirectTo !== '/login' && redirectTo !== '/register') {
                console.log(`🔀 Redirigiendo a ${redirectTo} después del login`);
                sessionStorage.removeItem('redirectAfterLogin');
                
                setTimeout(() => {
                    if (window.appRouter) {
                        window.appRouter.navigate(redirectTo);
                    } else {
                        window.location.hash = redirectTo;
                    }
                }, 100);
            } else {
                // Redirigir al home si no hay redirección específica
                setTimeout(() => {
                    if (window.appRouter) {
                        window.appRouter.navigate('/');
                    } else {
                        window.location.hash = '/';
                    }
                }, 100);
            }
        } catch (error) {
            console.error('Error en redirección post-login:', error);
        }
    }

    // Registro de usuario
    async register(userData) {
        try {
            // Validaciones básicas del lado cliente
            if (!userData.email || !userData.firstName || !userData.lastName || !userData.password) {
                return {
                    success: false,
                    message: 'Todos los campos requeridos deben estar completos'
                };
            }

            if (userData.password !== userData.confirmPassword) {
                return {
                    success: false,
                    message: 'Las contraseñas no coinciden'
                };
            }

            console.log('🔄 Enviando registro al servidor...');

            const response = await fetch(this.getApiUrl('register'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({
                    firstName: userData.firstName,
                    lastName: userData.lastName,
                    email: userData.email,
                    phone: userData.phone || '',
                    island: userData.island || '',
                    city: userData.city || '',
                    userType: userData.userType || 'user',
                    password: userData.password
                })
            });

            const data = await response.json();
            console.log('📩 Respuesta de registro:', data);

            if (response.ok && data.success) {
                // Auto-login si se incluye token
                if (data.data?.token && data.data?.user) {
                    this.token = data.data.token;
                    this.currentUser = data.data.user;
                    this.dispatchAuthEvent('login', this.currentUser);
                    console.log('✅ Registro exitoso con auto-login');
                }
                
                return {
                    success: true,
                    message: data.message,
                    autoLogin: !!(data.data?.token)
                };
            } else {
                return {
                    success: false,
                    message: data.message || 'Error en el registro'
                };
            }
        } catch (error) {
            console.error('Error en registro:', error);
            return {
                success: false,
                message: 'Error de conexión. Intenta nuevamente.'
            };
        }
    }

    // Login de usuario
    async login(credentials) {
        try {
            if (!credentials.email || !credentials.password) {
                return {
                    success: false,
                    message: 'Email y contraseña son requeridos'
                };
            }

            console.log('🔄 Enviando login al servidor...');

            const response = await fetch(this.getApiUrl('login'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({
                    email: credentials.email,
                    password: credentials.password,
                    rememberMe: credentials.rememberMe || false
                })
            });

            const data = await response.json();
            console.log('📩 Respuesta de login:', data);

            if (response.ok && data.success) {
                // Verificar si requiere confirmación de email
                if (data.data.requiresEmailConfirmation) {
                    console.log('⚠️ Email no confirmado');
                    this.dispatchAuthEvent('email-not-confirmed', data.data.user);
                    
                    return {
                        success: false,
                        requiresEmailConfirmation: true,
                        message: data.message,
                        user: data.data.user
                    };
                }
                
                // Login exitoso normal
                this.token = data.data.token;
                this.currentUser = data.data.user;
                
                console.log('✅ Login exitoso, usuario:', this.currentUser);
                this.dispatchAuthEvent('login', this.currentUser);
                
                // Forzar actualización del header si está disponible
                if (window.headerComponent && typeof window.headerComponent.forceAuthUpdate === 'function') {
                    setTimeout(() => {
                        window.headerComponent.forceAuthUpdate();
                    }, 100);
                }
                
                // Redirigir a la página que quería visitar antes del login
                this.handlePostLoginRedirect();
                
                return {
                    success: true,
                    message: data.message,
                    user: this.currentUser
                };
            } else {
                return {
                    success: false,
                    message: data.message || 'Credenciales incorrectas'
                };
            }
        } catch (error) {
            console.error('Error en login:', error);
            return {
                success: false,
                message: 'Error de conexión. Intenta nuevamente.'
            };
        }
    }

    // Logout
    async logout() {
        try {
            console.log('🔄 Cerrando sesión...');
            
            const response = await fetch(this.getApiUrl('logout'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include'
            });

            const data = await response.json();
            console.log('📩 Respuesta de logout:', data);

            // Limpiar datos locales
            this.token = null;
            this.currentUser = null;
            
            // Limpiar cookie manualmente también (por si acaso)
            document.cookie = 'ecc_auth_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
            
            console.log('🧹 Datos locales limpiados');
            this.dispatchAuthEvent('logout');
            
            return {
                success: true,
                message: data.message || 'Sesión cerrada exitosamente'
            };
        } catch (error) {
            console.error('Error en logout:', error);
            // Limpiar datos locales aunque falle la petición
            this.token = null;
            this.currentUser = null;
            
            // Limpiar cookie manualmente también
            document.cookie = 'ecc_auth_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
            
            console.log('🧹 Datos locales limpiados (fallback)');
            this.dispatchAuthEvent('logout');
            
            return {
                success: true,
                message: 'Sesión cerrada'
            };
        }
    }

    // Validar token actual
    async validateToken() {
        if (!this.token) {
            console.log('🔍 No hay token para validar');
            return false;
        }

        try {
            console.log('🔍 Validando token...');
            
            const response = await fetch(this.getApiUrl('validate'), {
                method: 'GET',
                headers: { 'Authorization': `Bearer ${this.token}` },
                credentials: 'include'
            });

            const data = await response.json();
            console.log('📩 Respuesta de validación:', data);

            if (response.ok && data.success && data.data?.valid) {
                this.currentUser = data.data.user;
                console.log('✅ Token válido, usuario:', this.currentUser);
                this.dispatchAuthEvent('validated', this.currentUser);
                
                // Forzar actualización del header si está disponible
                if (window.headerComponent && typeof window.headerComponent.forceAuthUpdate === 'function') {
                    setTimeout(() => {
                        window.headerComponent.forceAuthUpdate();
                    }, 100);
                }
                
                return true;
            } else {
                // Token inválido
                this.token = null;
                this.currentUser = null;
                this.dispatchAuthEvent('logout');
                return false;
            }
        } catch (error) {
            console.error('Error validando token:', error);
            this.token = null;
            this.currentUser = null;
            this.dispatchAuthEvent('logout');
            return false;
        }
    }

    // Métodos de estado
    isAuthenticated() {
        return this.token !== null && this.currentUser !== null;
    }

    getCurrentUser() {
        return this.currentUser;
    }

    getToken() {
        return this.token;
    }

    // Disparar eventos de autenticación
    dispatchAuthEvent(type, data = null) {
        const event = new CustomEvent(`auth-${type}`, { detail: data });
        window.dispatchEvent(event);
        console.log(`🔔 Evento disparado: auth-${type}`, data ? data.firstName || data.email : '');
    }
}

// Crear instancia global optimizada
console.log('🔧 Creando AuthService optimizado...');

try {
    window.authService = new AuthService();
    console.log('✅ AuthService optimizado creado exitosamente');
    
    // Verificar métodos disponibles
    ['register', 'login', 'logout', 'validateToken', 'isAuthenticated'].forEach(method => {
        console.log(`📋 ${method}:`, typeof window.authService[method]);
    });
    
} catch (error) {
    console.error('❌ Error al crear AuthService:', error);
}

// Exportar la clase
window.AuthService = AuthService;

// Función de emergencia
window.ensureAuthService = function() {
    if (!window.authService && typeof window.AuthService === 'function') {
        try {
            window.authService = new window.AuthService();
            return true;
        } catch (error) {
            console.error('❌ ensureAuthService error:', error);
            return false;
        }
    }
    return !!window.authService;
};

// Verificación final
setTimeout(() => {
    if (!window.authService) {
        console.log('⚠️ Ejecutando función de emergencia...');
        window.ensureAuthService();
    }
}, 50);
