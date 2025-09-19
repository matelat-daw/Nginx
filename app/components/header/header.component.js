// Header Component - Economía Circular Canarias
class HeaderComponent {
    constructor() {
        this.isAuthenticated = false;
        this.currentUser = null;
        this.template = null;
        this.cssLoaded = false;
        this.initializeAuthState();
    }
    // Verificar estado inicial de autenticación
    initializeAuthState() {
        // Verificar si AuthService ya está disponible y tiene usuario
        if (window.authService) {
            this.isAuthenticated = window.authService.isAuthenticated();
            this.currentUser = window.authService.getCurrentUser();
        }
    }

    // Utilidad para obtener contenido de template
    getTemplateContent(templateId) {
        const template = document.getElementById(templateId);
        if (template && template.content) {
            const clone = template.content.cloneNode(true);
            const tempDiv = document.createElement('div');
            tempDiv.appendChild(clone);
            return { element: clone, html: tempDiv.innerHTML };
        }
        return null;
    }
    // Cargar template HTML
    async loadTemplate() {
        if (this.template) return this.template;
        try {
            const response = await fetch('/app/components/header/header.component.html');
            this.template = await response.text();
            return this.template;
        } catch (error) {
            console.error('Error cargando template del header:', error);
            return this.getFallbackTemplate();
        }
    }
    // Template de respaldo si falla la carga
    getFallbackTemplate() {
        // En caso de error, usar una versión muy básica
        return `
            <header>
                <div class="header-content">
                    <a href="/" class="logo">🏝️ Economía Circular Canarias</a>
                    <div class="header-actions">
                        <button class="theme-toggle" id="themeToggle">🌙</button>
                        <div class="cart-section" id="cartSection">
                            <button class="cart-button" id="cartButton">🛒</button>
                        </div>
                        <div class="auth-section" id="authSection"></div>
                    </div>
                </div>
            </header>
        `;
    }
    // Renderizar sección de autenticación usando templates del HTML
    renderAuthSection() {
        if (this.isAuthenticated && this.currentUser) {
            // Usar template del HTML para usuario autenticado
            const templateData = this.getTemplateContent('userMenuTemplate');
            if (templateData) {
                // Actualizar el nombre del usuario
                const userName = this.currentUser.first_name || this.currentUser.firstName || 'Usuario';
                const userNameSpan = templateData.element.querySelector('.user-name');
                if (userNameSpan) {
                    userNameSpan.textContent = userName;
                }
                // Reconstruir el HTML con el nombre actualizado
                const tempDiv = document.createElement('div');
                tempDiv.appendChild(templateData.element);
                return tempDiv.innerHTML;
            }
        } else {
            // Usar template del HTML para botones de auth
            const templateData = this.getTemplateContent('authButtonsTemplate');
            if (templateData) {
                return templateData.html;
            }
        }
        
        // Fallback si no hay templates disponibles
        return this.getFallbackAuthSection();
    }

    // Fallback para auth section si no hay templates
    getFallbackAuthSection() {
        if (this.isAuthenticated && this.currentUser) {
            const userName = this.currentUser.first_name || this.currentUser.firstName || 'Usuario';
            return `<div class="user-menu">
                <button class="user-button" id="userMenuToggle">👤 ${userName}</button>
                <div class="user-dropdown" id="userDropdown" style="display: none;">
                    <button class="dropdown-item logout-btn" id="logoutBtn">🚪 Cerrar Sesión</button>
                </div>
            </div>`;
        } else {
            return `<div class="auth-buttons">
                <a href="/login" class="btn btn-outline-primary" data-navigate="/login">🔐 Login</a>
                <a href="/register" class="btn btn-primary" data-navigate="/register">👤 Registro</a>
            </div>`;
        }
    }
    async render() {
        const template = await this.loadTemplate();
        return template;
    }
    // Método para actualizar el estado de autenticación después de que los servicios estén listos
    // Método para actualizar el estado de autenticación después de que los servicios estén listos
    refreshAuthState() {
        if (window.authService) {
            this.isAuthenticated = window.authService.isAuthenticated();
            this.currentUser = window.authService.getCurrentUser();
            this.updateAuthSection();
        } else {
            this.isAuthenticated = false;
            this.currentUser = null;
            this.updateAuthSection();
        }
    }
    
    // Método para forzar actualización del estado de autenticación
    forceAuthUpdate() {
        this.refreshAuthState();
    }
    afterRender() {
        // Cargar CSS solo una vez
        if (!this.cssLoaded) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = '/app/components/header/header.component.css';
            document.head.appendChild(link);
            this.cssLoaded = true;
        }
        
        this.initializeThemeToggle();
        this.initializeNavigation();
        this.initializeAuthEvents();
        this.initializeCart();
        this.updateAuthSection();
    }
    
    updateAuthSection() {
        const authSection = document.getElementById('authSection');
        
        if (authSection) {
            const newHtml = this.renderAuthSection();
            authSection.innerHTML = newHtml;
            
            // Solo si el usuario está autenticado, inicializar el menú
            if (this.isAuthenticated) {
                // Usar requestAnimationFrame para asegurar que el DOM esté listo
                requestAnimationFrame(() => {
                    this.initializeUserMenu();
                });
            }
        }
    }
    initializeThemeToggle() {
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', this.toggleTheme.bind(this));
            this.updateThemeToggleText();
        }
    }
    toggleTheme() {
        const body = document.body;
        const currentTheme = body.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        body.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        this.updateThemeToggleText();
    }
    updateThemeToggleText() {
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            const currentTheme = document.body.getAttribute('data-theme') || 'light';
            themeToggle.textContent = currentTheme === 'light' ? '🌙 Modo Oscuro' : '☀️ Modo Claro';
        }
    }
    initializeNavigation() {
        // Manejar navegación SPA para los enlaces del header
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[data-navigate]');
            if (link && link.closest('header')) {
                e.preventDefault();
                const route = link.getAttribute('data-navigate');
                if (window.appRouter) {
                    window.appRouter.navigate(route);
                } else {
                    window.location.hash = route;
                }
            }
        });
    }
    initializeAuthEvents() {
        // Escuchar eventos de autenticación globales
        document.addEventListener('auth:login', () => {
            this.refreshAuthState();
        });
        document.addEventListener('auth:logout', () => {
            this.refreshAuthState();
        });
        document.addEventListener('auth:validated', () => {
            this.refreshAuthState();
        });
    }
    initializeUserMenu() {
        const userMenuToggle = document.getElementById('userMenuToggle');
        const userDropdown = document.getElementById('userDropdown');
        const logoutBtn = document.getElementById('logoutBtn');
        
        if (userMenuToggle && userDropdown) {
            // Limpiar event listeners previos clonando el elemento
            const newToggle = userMenuToggle.cloneNode(true);
            userMenuToggle.parentNode.replaceChild(newToggle, userMenuToggle);
            
            // Agregar event listener al nuevo elemento
            newToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                const isVisible = userDropdown.style.display === 'block';
                userDropdown.style.display = isVisible ? 'none' : 'block';
            });
            
            // Cerrar dropdown al hacer click fuera
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.user-menu')) {
                    userDropdown.style.display = 'none';
                }
            }, { once: true, capture: true });
        }
        
        if (logoutBtn) {
            // Limpiar event listeners del botón de logout
            const newLogoutBtn = logoutBtn.cloneNode(true);
            logoutBtn.parentNode.replaceChild(newLogoutBtn, logoutBtn);
            
            newLogoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (userDropdown) {
                    userDropdown.style.display = 'none';
                }
                this.handleLogout();
            });
        }
    }
    async handleLogout() {
        try {
            // Crear instancia del modal de logout
            const logoutModal = new LogoutModal();
            // Mostrar modal y esperar confirmación del usuario
            const userConfirmed = await logoutModal.show();
            if (userConfirmed) {
                if (window.authService) {
                    const result = await window.authService.logout();
                    if (result.success) {
                        // Actualizar estado local del header
                        this.isAuthenticated = false;
                        this.currentUser = null;
                        // Forzar actualización del header ANTES del modal de éxito
                        this.updateAuthSection();
                        // Mostrar modal de éxito
                        await logoutModal.showSuccess();
                        // Redirigir al home después del logout
                        if (window.appRouter) {
                            window.appRouter.navigate('/');
                        } else {
                            window.location.hash = '/';
                        }
                    }
                } else {
                    console.error('❌ AuthService no disponible');
                }
            } else {
            }
        } catch (error) {
            console.error('Error durante logout:', error);
        }
    }
    // Método para forzar actualización del estado de autenticación
    forceAuthUpdate() {
        if (window.authService) {
            this.isAuthenticated = window.authService.isAuthenticated();
            this.currentUser = window.authService.getCurrentUser();
            this.updateAuthSection();
        }
    }

    // Inicializar carrito
    initializeCart() {
        this.updateCartCount();
        this.initializeCartEvents();
        
        const cartButton = document.getElementById('cartButton');
        if (cartButton) {
            cartButton.addEventListener('click', () => {
                this.openCartModal();
            });
        }
    }

    // Inicializar eventos del carrito
    initializeCartEvents() {
        // Escuchar cambios en el carrito
        document.addEventListener('cart-updated', (e) => {
            this.updateCartCount();
        });
    }

    // Actualizar contador del carrito
    updateCartCount() {
        const cartCount = document.getElementById('cartCount');
        if (cartCount && window.cartService) {
            const itemCount = window.cartService.getItemCount();
            
            if (itemCount > 0) {
                cartCount.textContent = itemCount;
                cartCount.style.display = 'inline-block';
            } else {
                cartCount.style.display = 'none';
            }
        }
    }

    // Abrir modal del carrito
    openCartModal() {
        if (window.CartModal) {
            const cartModal = new window.CartModal();
            cartModal.show();
        } else {
            console.log('CartModal no disponible aún');
        }
    }
}
// Exportar el componente
window.HeaderComponent = HeaderComponent;