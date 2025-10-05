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
    }
    // Función helper para construir URLs del API
    getApiUrl(endpoint) {
        const url = this.endpoints[endpoint] || '';
        return url;
    }
    // Obtener token de la cookie
    getTokenFromCookie() {
        const cookieName = 'ecc_auth_token';
        
        // Método principal: buscar en cookies parseadas
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === cookieName) {
                return value;
            }
        }
        
        // Método alternativo usando regex
        const match = document.cookie.match(new RegExp('(^| )' + cookieName + '=([^;]+)'));
        if (match) {
            return match[2];
        }
        
        return null;
    }
    
    // Verificar si tenemos datos de sesión válidos
    hasValidSession() {
        const token = this.getTokenFromCookie();
        return token !== null && token !== undefined && token !== '';
    }
    // Inicialización mejorada con verificación automática
    async init() {
        try {
            // Verificar si tenemos una sesión válida
            const hasSession = this.hasValidSession();
            
            if (hasSession) {
                this.token = this.getTokenFromCookie();
                
                // Intentar validar el token
                const isValid = await this.validateToken();
                
                if (isValid) {
                    // Token válido - usuario autenticado
                    this.dispatchAuthEvent('login', this.currentUser);
                    
                    // Forzar actualización inmediata del header si existe
                    this.updateHeaderAuthState();
                    
                    // También forzar actualización después de un pequeño delay para componentes que se cargan tarde
                    setTimeout(() => {
                        this.updateHeaderAuthState();
                        this.dispatchAuthEvent('authRestored', this.currentUser);
                    }, 500);
                    
                    return true; // Sesión restaurada exitosamente
                } else {
                    // Token inválido - limpiar estado
                    this.clearAuthState();
                    return false; // Sesión expirada
                }
            } else {
                // No hay token - usuario no autenticado
                this.clearAuthState();
                return false; // No hay sesión
            }
        } catch (error) {
            console.error('❌ Error en init():', error);
            this.clearAuthState();
            return false; // Error en inicialización
        }
    }
    // Limpiar estado de autenticación
    clearAuthState() {
        this.token = null;
        this.currentUser = null;
        
        // Limpiar cookie de autenticación
        document.cookie = 'ecc_auth_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
        
        // Disparar evento de logout solo si había usuario autenticado previamente
        this.dispatchAuthEvent('logout');
        
        // Actualizar header
        this.updateHeaderAuthState();
    }
    // Actualizar estado del header
    updateHeaderAuthState() {
        // Intentar múltiples maneras de actualizar el header
        setTimeout(() => {
            // Método 1: Componente header específico
            if (window.headerComponent) {
                if (typeof window.headerComponent.forceAuthUpdate === 'function') {
                    window.headerComponent.forceAuthUpdate();
                } else if (typeof window.headerComponent.refreshAuthState === 'function') {
                    window.headerComponent.refreshAuthState();
                }
            }
            
            // Método 2: Buscar componente header en el DOM y forzar actualización
            const headerElement = document.querySelector('header');
            if (headerElement && headerElement._component) {
                const component = headerElement._component;
                if (typeof component.forceAuthUpdate === 'function') {
                    component.forceAuthUpdate();
                } else if (typeof component.refreshAuthState === 'function') {
                    component.refreshAuthState();
                }
            }
            
            // Método 3: Evento global para que todos los componentes se actualicen
            const authEvent = new CustomEvent('globalAuthUpdate', { 
                detail: { 
                    isAuthenticated: this.isAuthenticated(),
                    user: this.getCurrentUser()
                } 
            });
            document.dispatchEvent(authEvent);
        }, 100);
    }
    // Manejar redirección después del login
    handlePostLoginRedirect() {
        try {
            const redirectTo = sessionStorage.getItem('redirectAfterLogin');
            if (redirectTo && redirectTo !== '/login' && redirectTo !== '/register') {
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
            if (response.ok && data.success) {
                // Auto-login si se incluye token
                if (data.data?.token && data.data?.user) {
                    this.token = data.data.token;
                    this.currentUser = data.data.user;
                    this.dispatchAuthEvent('login', this.currentUser);
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
            if (response.ok && data.success) {
                // Verificar si requiere confirmación de email
                if (data.data.requiresEmailConfirmation) {
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
        console.log('🔐 AuthService: Iniciando logout...');
        try {
            const response = await fetch(this.getApiUrl('logout'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include'
            });
            
            console.log('🔐 AuthService: Respuesta del servidor:', response.status);
            const data = await response.json();
            console.log('🔐 AuthService: Datos de respuesta:', data);
            
            // Limpiar datos locales
            this.token = null;
            this.currentUser = null;
            
            // Limpiar cookie manualmente también (por si acaso)
            document.cookie = 'ecc_auth_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
            console.log('🔐 AuthService: Datos locales limpiados');
            
            this.dispatchAuthEvent('logout');
            console.log('🔐 AuthService: Evento de logout disparado');
            
            return {
                success: true,
                message: data.message || 'Sesión cerrada exitosamente'
            };
        } catch (error) {
            console.error('❌ AuthService: Error en logout:', error);
            // Limpiar datos locales aunque falle la petición
            this.token = null;
            this.currentUser = null;
            // Limpiar cookie manualmente también
            document.cookie = 'ecc_auth_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
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
            return false;
        }
        
        try {
            const validateUrl = this.getApiUrl('validate');
            
            const response = await fetch(validateUrl, {
                method: 'GET',
                headers: { 'Authorization': `Bearer ${this.token}` },
                credentials: 'include'
            });
            
            const data = await response.json();
            
            if (response.ok && data.success) {
                // La estructura real es: data.data.valid y data.data.user (debido a jsonResponse wrapper)
                const validationData = data.data || data; // Fallback por si cambia la estructura
                
                if (validationData.valid) {
                    this.currentUser = validationData.user;
                    this.dispatchAuthEvent('validated', this.currentUser);
                    
                    // Forzar actualización del header si está disponible
                    if (window.headerComponent && typeof window.headerComponent.forceAuthUpdate === 'function') {
                        setTimeout(() => {
                            window.headerComponent.forceAuthUpdate();
                        }, 100);
                    }
                    return true;
                } else {
                    this.token = null;
                    this.currentUser = null;
                    this.dispatchAuthEvent('logout');
                    return false;
                }
            } else {
                // Token inválido
                this.token = null;
                this.currentUser = null;
                this.dispatchAuthEvent('logout');
                return false;
            }
        } catch (error) {
            console.error('❌ ValidateToken: Error de red:', error);
            this.token = null;
            this.currentUser = null;
            this.dispatchAuthEvent('logout');
            return false;
        }
    }
    // Métodos de estado
    isAuthenticated() {
        const hasToken = this.token !== null && this.token !== undefined && this.token !== '';
        const hasUser = this.currentUser !== null && this.currentUser !== undefined;
        const result = hasToken && hasUser;
        return result;
    }
    
    getCurrentUser() {
        return this.currentUser;
    }
    
    getToken() {
        return this.token;
    }
    // Disparar eventos de autenticación
    dispatchAuthEvent(type, data = null) {
        // Evento principal con formato auth-* en document
        const authEvent = new CustomEvent(`auth-${type}`, { detail: data });
        document.dispatchEvent(authEvent);
        
        // También en window para compatibilidad
        window.dispatchEvent(authEvent);
        
        // Eventos específicos para mejor compatibilidad
        if (type === 'login') {
            const loginEvent = new CustomEvent('userLogin', { detail: data });
            window.dispatchEvent(loginEvent);
            document.dispatchEvent(loginEvent);
        } else if (type === 'logout') {
            const logoutEvent = new CustomEvent('userLogout', { detail: data });
            window.dispatchEvent(logoutEvent);
            document.dispatchEvent(logoutEvent);
        }
        
        // Evento general de cambio de estado
        const stateEvent = new CustomEvent('authStateChanged', { 
            detail: { 
                type, 
                isAuthenticated: this.isAuthenticated(),
                user: this.getCurrentUser(),
                data 
            } 
        });
        window.dispatchEvent(stateEvent);
        document.dispatchEvent(stateEvent);
    }
}
// Crear instancia global optimizada
try {
    window.authService = new AuthService();
    // Verificar métodos disponibles
    ['register', 'login', 'logout', 'validateToken', 'isAuthenticated'].forEach(method => {
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
        window.ensureAuthService();
    }
}, 50);
