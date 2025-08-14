// Header Component - Economía Circular Canarias
class HeaderComponent {
    constructor() {
        this.isAuthenticated = false;
        this.currentUser = null;
        this.initializeAuthState();
        this.updateTemplate();
    }
    
    // Verificar estado inicial de autenticación
    initializeAuthState() {
        // Verificar si AuthService ya está disponible y tiene usuario
        if (window.authService) {
            this.isAuthenticated = window.authService.isAuthenticated();
            this.currentUser = window.authService.getCurrentUser();
        }
    }

    updateTemplate() {
        this.template = `
            <header>
                <div class="header-content">
                    <a href="/" class="logo" data-navigate="/">
                        🏝️ Economía Circular Canarias
                    </a>
                    
                    <div class="header-actions">
                        <button class="theme-toggle" id="themeToggle">
                            🌙 Modo Oscuro
                        </button>
                        
                        <div class="auth-section" id="authSection">
                            ${this.renderAuthSection()}
                        </div>
                    </div>
                </div>
            </header>
        `;
    }

    renderAuthSection() {
        if (this.isAuthenticated && this.currentUser) {
            // Obtener nombre del usuario
            const userName = this.currentUser.first_name || this.currentUser.firstName || 'Usuario';
            
            return `
                <div class="user-menu">
                    <button class="user-button" id="userMenuToggle">
                        👤 ${userName}
                        <span class="dropdown-arrow">▼</span>
                    </button>
                    <div class="user-dropdown" id="userDropdown" style="display: none;">
                        <a href="/profile" class="dropdown-item" data-navigate="/profile">
                            👤 Mi Perfil
                        </a>
                        <a href="/orders" class="dropdown-item" data-navigate="/orders">
                            📦 Mis Pedidos
                        </a>
                        <a href="/settings" class="dropdown-item" data-navigate="/settings">
                            ⚙️ Configuración
                        </a>
                        <hr class="dropdown-divider">
                        <button class="dropdown-item logout-btn" id="logoutBtn">
                            🚪 Cerrar Sesión
                        </button>
                    </div>
                </div>
            `;
        } else {
            return `
                <div class="auth-buttons">
                    <a href="/login" class="btn btn-outline-primary" data-navigate="/login">
                        🔐 Login
                    </a>
                    <a href="/register" class="btn btn-primary" data-navigate="/register">
                        👤 Registro
                    </a>
                </div>
            `;
        }
    }

    render() {
        return this.template;
    }
    
    // Método para actualizar el estado de autenticación después de que los servicios estén listos
    refreshAuthState() {
        if (window.authService) {
            this.isAuthenticated = window.authService.isAuthenticated();
            this.currentUser = window.authService.getCurrentUser();
            this.updateAuthSection();
        }
    }

    afterRender() {
        this.initializeThemeToggle();
        this.initializeNavigation();
        this.initializeAuthEvents();
        this.initializeUserMenu();
        this.checkAuthenticationStatus();
        this.addHeaderStyles();
        
        // Verificar periódicamente el estado de autenticación
        this.startAuthStatusWatcher();
    }
    
    startAuthStatusWatcher() {
        // Verificar el estado cada 2 segundos (solo durante los primeros 30 segundos)
        let checks = 0;
        const maxChecks = 15; // 30 segundos
        
        const authWatcher = setInterval(() => {
            checks++;
            
            if (window.authService) {
                const currentAuthState = window.authService.isAuthenticated();
                const currentUser = window.authService.getCurrentUser();
                
                // Si el estado cambió, actualizar
                if (currentAuthState !== this.isAuthenticated || 
                    (currentUser && !this.currentUser) ||
                    (!currentUser && this.currentUser)) {
                    
                    console.log('🔄 HeaderComponent: Estado de auth cambió, actualizando...', {
                        antes: { auth: this.isAuthenticated, user: !!this.currentUser },
                        ahora: { auth: currentAuthState, user: !!currentUser }
                    });
                    
                    this.isAuthenticated = currentAuthState;
                    this.currentUser = currentUser;
                    this.updateAuthSection();
                }
            }
            
            if (checks >= maxChecks) {
                clearInterval(authWatcher);
                console.log('🛑 HeaderComponent: Auth watcher detenido');
            }
        }, 2000);
    }

    addHeaderStyles() {
        if (!document.getElementById('header-styles')) {
            const style = document.createElement('style');
            style.id = 'header-styles';
            style.textContent = `
                .header-actions {
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                }

                .auth-buttons {
                    display: flex;
                    gap: 0.5rem;
                    align-items: center;
                }

                .auth-buttons .btn {
                    padding: 0.5rem 1rem;
                    font-size: 0.9rem;
                    text-decoration: none;
                    border-radius: 5px;
                    transition: all 0.3s ease;
                }

                .btn-outline-primary {
                    border: 2px solid var(--canarias-white);
                    color: var(--canarias-white);
                    background: transparent;
                }

                .btn-outline-primary:hover {
                    background: var(--canarias-white);
                    color: var(--canarias-blue);
                }

                .user-menu {
                    position: relative;
                }

                .user-button {
                    background: rgba(255, 255, 255, 0.2);
                    border: 2px solid var(--canarias-white);
                    color: var(--canarias-white);
                    padding: 0.5rem 1rem;
                    border-radius: 25px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    font-size: 0.9rem;
                }

                .user-button:hover {
                    background: var(--canarias-white);
                    color: var(--canarias-blue);
                }

                .dropdown-arrow {
                    font-size: 0.7rem;
                    transition: transform 0.3s ease;
                }

                .user-menu.open .dropdown-arrow {
                    transform: rotate(180deg);
                }

                .user-dropdown {
                    position: absolute;
                    top: 100%;
                    right: 0;
                    margin-top: 0.5rem;
                    background: var(--canarias-white);
                    border-radius: 10px;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
                    min-width: 200px;
                    z-index: 1000;
                    border: 1px solid var(--canarias-border);
                }

                .dropdown-item {
                    display: block;
                    padding: 0.75rem 1rem;
                    color: var(--canarias-dark);
                    text-decoration: none;
                    transition: background-color 0.3s ease;
                    border: none;
                    background: none;
                    width: 100%;
                    text-align: left;
                    font-size: 0.9rem;
                    cursor: pointer;
                }

                .dropdown-item:hover {
                    background: var(--canarias-light-gray);
                }

                .dropdown-item:first-child {
                    border-radius: 10px 10px 0 0;
                }

                .dropdown-item:last-child {
                    border-radius: 0 0 10px 10px;
                }

                .dropdown-divider {
                    border: none;
                    border-top: 1px solid var(--canarias-border);
                    margin: 0.5rem 0;
                }

                .logout-btn {
                    color: #dc3545;
                }

                .logout-btn:hover {
                    background: #f8d7da;
                }

                @media (max-width: 768px) {
                    .header-actions {
                        flex-direction: column;
                        gap: 0.5rem;
                    }
                    
                    .auth-buttons {
                        flex-direction: column;
                        width: 100%;
                    }
                    
                    .auth-buttons .btn {
                        width: 100%;
                        text-align: center;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }

    initializeAuthEvents() {
        console.log('🔧 HeaderComponent: Inicializando eventos de autenticación...');
        
        // Escuchar eventos de autenticación
        window.addEventListener('auth-login', (e) => {
            console.log('🔔 HeaderComponent: Evento auth-login recibido:', e.detail);
            this.isAuthenticated = true;
            this.currentUser = e.detail;
            this.updateAuthSection();
        });

        window.addEventListener('auth-logout', () => {
            console.log('🔔 HeaderComponent: Evento auth-logout recibido');
            this.isAuthenticated = false;
            this.currentUser = null;
            this.updateAuthSection();
        });

        window.addEventListener('auth-validated', (e) => {
            console.log('🔔 HeaderComponent: Evento auth-validated recibido:', e.detail);
            this.isAuthenticated = true;
            this.currentUser = e.detail;
            this.updateAuthSection();
        });
    }

    initializeUserMenu() {
        const userMenuToggle = document.getElementById('userMenuToggle');
        const userDropdown = document.getElementById('userDropdown');
        const logoutBtn = document.getElementById('logoutBtn');

        if (userMenuToggle && userDropdown) {
            userMenuToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                const userMenu = userMenuToggle.closest('.user-menu');
                const isOpen = userMenu.classList.contains('open');
                
                if (isOpen) {
                    userMenu.classList.remove('open');
                    userDropdown.style.display = 'none';
                } else {
                    userMenu.classList.add('open');
                    userDropdown.style.display = 'block';
                }
            });

            // Cerrar dropdown al hacer clic fuera
            document.addEventListener('click', () => {
                const userMenu = userMenuToggle.closest('.user-menu');
                userMenu.classList.remove('open');
                userDropdown.style.display = 'none';
            });
        }

        if (logoutBtn) {
            logoutBtn.addEventListener('click', async () => {
                // Usar el modal personalizado en lugar de confirm()
                if (window.logoutModal) {
                    const shouldLogout = await window.logoutModal.show();
                    
                    if (shouldLogout) {
                        console.log('🔄 Cerrando sesión...');
                        const result = await window.authService.logout();
                        
                        if (result.success) {
                            // Mostrar modal de éxito en lugar de alert()
                            await window.logoutModal.showSuccess();
                            
                            // Navegar al inicio después del modal
                            if (window.appRouter) {
                                window.appRouter.navigate('/');
                            }
                        } else {
                            // En caso de error, usar un modal de error (fallback con alert por ahora)
                            alert('❌ Error al cerrar sesión. Inténtalo de nuevo.');
                        }
                    }
                } else {
                    // Fallback si el modal no está disponible
                    if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
                        const result = await window.authService.logout();
                        if (result.success) {
                            alert('✅ Sesión cerrada exitosamente');
                            window.appRouter.navigate('/');
                        }
                    }
                }
            });
        }
    }

    updateAuthSection() {
        console.log('🔄 HeaderComponent: Actualizando sección de autenticación...', {
            isAuthenticated: this.isAuthenticated,
            currentUser: this.currentUser
        });
        
        this.updateTemplate();
        const authSection = document.getElementById('authSection');
        if (authSection) {
            authSection.innerHTML = this.renderAuthSection();
            this.initializeNavigation();
            this.initializeUserMenu();
            console.log('✅ HeaderComponent: Sección de autenticación actualizada');
        } else {
            console.warn('⚠️ HeaderComponent: No se encontró el elemento authSection');
        }
    }

    checkAuthenticationStatus() {
        console.log('🔍 HeaderComponent: Verificando estado de autenticación...');
        
        // Verificar si el usuario está autenticado al cargar
        if (window.authService) {
            const isAuth = window.authService.isAuthenticated();
            const user = window.authService.getCurrentUser();
            
            console.log('📊 HeaderComponent: Estado actual:', {
                authService: !!window.authService,
                isAuthenticated: isAuth,
                currentUser: user
            });
            
            if (isAuth && user) {
                this.isAuthenticated = true;
                this.currentUser = user;
                this.updateAuthSection();
                console.log('✅ HeaderComponent: Usuario autenticado encontrado');
            } else {
                console.log('ℹ️ HeaderComponent: Usuario no autenticado');
            }
        } else {
            console.warn('⚠️ HeaderComponent: AuthService no disponible aún');
            // Reintentar después de un momento
            setTimeout(() => {
                this.checkAuthenticationStatus();
            }, 1000);
        }
    }

    forceAuthUpdate() {
        console.log('🔄 HeaderComponent: Forzando actualización de autenticación...');
        if (window.authService) {
            this.isAuthenticated = window.authService.isAuthenticated();
            this.currentUser = window.authService.getCurrentUser();
            this.updateAuthSection();
        }
    }

    initializeThemeToggle() {
        const themeToggle = document.getElementById('themeToggle');
        if (!themeToggle) return;

        // Cargar tema guardado
        const savedTheme = localStorage.getItem('canarias-theme') || 'light';
        this.applyTheme(savedTheme);
        this.updateThemeToggleText(savedTheme);

        themeToggle.addEventListener('click', () => {
            const currentTheme = document.body.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            this.applyTheme(newTheme);
            localStorage.setItem('canarias-theme', newTheme);
            this.updateThemeToggleText(newTheme);
        });
    }

    applyTheme(theme) {
        document.body.setAttribute('data-theme', theme);
        
        if (theme === 'dark') {
            document.documentElement.style.setProperty('--canarias-light-gray', '#1a1a1a');
            document.documentElement.style.setProperty('--canarias-white', '#2d2d2d');
            document.documentElement.style.setProperty('--canarias-dark', '#ffffff');
            document.documentElement.style.setProperty('--canarias-border', '#404040');
            // No cambiar el color del footer, siempre fondo oscuro
        } else {
            document.documentElement.style.setProperty('--canarias-light-gray', '#F8F9FA');
            document.documentElement.style.setProperty('--canarias-white', '#FFFFFF');
            document.documentElement.style.setProperty('--canarias-dark', '#2C3E50');
            document.documentElement.style.setProperty('--canarias-border', '#E9ECEF');
            // No cambiar el color del footer, siempre fondo oscuro
        }
    }

    updateThemeToggleText(theme) {
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.textContent = theme === 'light' ? '🌙 Modo Oscuro' : '☀️ Modo Claro';
        }
    }

    initializeNavigation() {
        const logoLink = document.querySelector('.logo[data-navigate]');
        const navLinks = document.querySelectorAll('[data-navigate]');
        
        if (logoLink) {
            logoLink.addEventListener('click', (e) => {
                e.preventDefault();
                const route = logoLink.getAttribute('data-navigate');
                window.appRouter.navigate(route);
            });
        }

        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const route = link.getAttribute('data-navigate');
                window.appRouter.navigate(route);
            });
        });
    }
}

// Exportar el componente
window.HeaderComponent = HeaderComponent;

// Crear instancia global para fácil acceso
window.headerComponent = null;
