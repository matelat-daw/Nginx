// Header Component - Economía Circular Canarias (OPTIMIZADO)
class HeaderComponent {
    constructor() {
        this.isAuthenticated = false;
        this.currentUser = null;
        this.template = null;
        this.cssLoaded = false;
        this.initializeAuthState();
    }

    initializeAuthState() {
        if (window.authService) {
            this.isAuthenticated = window.authService.isAuthenticated();
            this.currentUser = window.authService.getCurrentUser();
        }
    }

    async loadTemplate() {
        if (this.template) return this.template;
        try {
            const response = await fetch('/app/components/header/header.component.html');
            this.template = await response.text();
            return this.template;
        } catch (error) {
            console.error('Error cargando template del header:', error);
            return `<header>
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
            </header>`;
        }
    }

    async render() {
        return await this.loadTemplate();
    }

    getUserData() {
        return {
            name: this.currentUser?.firstName || this.currentUser?.first_name || 'Usuario',
            image: this.currentUser?.profileImage || this.currentUser?.profile_image
        };
    }

    updateUserAvatar(imagePath) {
        // Actualizar solo el avatar sin regenerar todo el HTML
        const userButton = document.getElementById('userMenuToggle');
        if (!userButton) return;

        // Actualizar el usuario en memoria
        if (this.currentUser) {
            this.currentUser.profileImage = imagePath;
            this.currentUser.profile_image = imagePath;
        }

        // Buscar el avatar actual (img o emoji)
        const currentAvatar = userButton.querySelector('.user-avatar');
        const { name, image } = this.getUserData();

        if (image) {
            // Añadir timestamp para evitar cache
            const imageUrl = image.includes('?') ? `${image}&t=${Date.now()}` : `${image}?t=${Date.now()}`;
            
            // Si ya existe una imagen, actualizar el src
            if (currentAvatar && currentAvatar.tagName === 'IMG') {
                currentAvatar.src = imageUrl;
                currentAvatar.style.width = '48px';
                currentAvatar.style.height = '48px';
                currentAvatar.style.minWidth = '48px';
                currentAvatar.style.minHeight = '48px';
                currentAvatar.style.maxWidth = '48px';
                currentAvatar.style.maxHeight = '48px';
                currentAvatar.style.objectFit = 'cover';
                currentAvatar.style.borderRadius = '50%';
            } else {
                // Si era emoji, reemplazar por imagen
                const avatarHTML = `<img src="${imageUrl}" alt="${name}" class="user-avatar" style="width: 48px !important; height: 48px !important; min-width: 48px; min-height: 48px; max-width: 48px; max-height: 48px; object-fit: cover; border-radius: 50%;">`;
                const firstChild = userButton.firstChild;
                if (firstChild && firstChild.nodeType === Node.TEXT_NODE && firstChild.textContent.trim() === '👤') {
                    firstChild.remove();
                }
                userButton.insertAdjacentHTML('afterbegin', avatarHTML);
            }
        }
    }

    generateAuthHTML() {
        if (!this.isAuthenticated || !this.currentUser) {
            return `<div class="auth-buttons">
                <a href="/login" class="btn btn-outline-primary" data-navigate="/login">🔐 Login</a>
                <a href="/register" class="btn btn-primary" data-navigate="/register">👤 Registro</a>
            </div>`;
        }

        const { name, image } = this.getUserData();
        const avatar = image ? `<img src="${image}" alt="${name}" class="user-avatar" style="width: 48px !important; height: 48px !important; min-width: 48px; min-height: 48px; max-width: 48px; max-height: 48px; object-fit: cover; border-radius: 50%;">` : '👤';

        return `<div class="user-menu">
            <button class="user-button" id="userMenuToggle">
                ${avatar} ${name}
                <span class="dropdown-arrow">▼</span>
            </button>
            <div class="user-dropdown" id="userDropdown">
                <a href="#/profile" class="dropdown-item" data-navigate="/profile">👤 Mi Perfil</a>
                <a href="#/orders" class="dropdown-item" data-navigate="/orders">📦 Mis Pedidos</a>
                <a href="#/settings" class="dropdown-item" data-navigate="/settings">⚙️ Configuración</a>
                <hr class="dropdown-divider">
                <button class="dropdown-item logout-btn" id="logoutBtn">🚪 Cerrar Sesión</button>
            </div>
        </div>`;
    }

    refreshAuthState() {
        if (window.authService) {
            const wasAuthenticated = this.isAuthenticated;
            const hadUser = this.currentUser !== null;

            this.isAuthenticated = window.authService.isAuthenticated();
            this.currentUser = window.authService.getCurrentUser();

            if (wasAuthenticated !== this.isAuthenticated || 
                (!hadUser && this.currentUser) || 
                (hadUser && !this.currentUser)) {
                this.updateAuthSection(true);
            }
        } else {
            this.isAuthenticated = false;
            this.currentUser = null;
            this.updateAuthSection(true);
        }
    }

    updateAuthSection(forceRefresh = false) {
        const authSection = document.getElementById('authSection');
        if (!authSection) return;

        // Si forceRefresh es true, sincronizar con authService
        // De lo contrario, usar valores locales actuales
        if (forceRefresh && window.authService) {
            this.isAuthenticated = window.authService.isAuthenticated();
            this.currentUser = window.authService.getCurrentUser();
        }

        authSection.innerHTML = this.generateAuthHTML();

        if (this.isAuthenticated && this.currentUser) {
            setTimeout(() => this.initializeUserMenu(), 50);
        }
    }

    initializeUserMenu() {
        const button = document.getElementById('userMenuToggle');
        const dropdown = document.getElementById('userDropdown');
        const logoutBtn = document.getElementById('logoutBtn');

        if (!button || !dropdown) return;

        // Forzar dropdown oculto
        dropdown.style.display = 'none';

        // Toggle dropdown
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);
        
        newButton.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        });

        // Cerrar al hacer click fuera
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.user-menu')) {
                dropdown.style.display = 'none';
            }
        });

        // Cerrar dropdown al hacer clic en cualquier enlace del menú
        const dropdownLinks = dropdown.querySelectorAll('.dropdown-item[data-navigate]');
        dropdownLinks.forEach(link => {
            link.addEventListener('click', () => {
                dropdown.style.display = 'none';
            });
        });

        // Logout button
        if (logoutBtn) {
            const newLogoutBtn = logoutBtn.cloneNode(true);
            logoutBtn.parentNode.replaceChild(newLogoutBtn, logoutBtn);
            newLogoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                dropdown.style.display = 'none'; // Cerrar dropdown al hacer logout
                this.handleLogout();
            });
        }
    }

    async handleLogout() {
        console.log('🚪 HeaderComponent: handleLogout iniciado');
        try {
            const logoutModal = new LogoutModal();
            const confirmed = await logoutModal.show();
            console.log('🚪 Usuario confirmó logout:', confirmed);
            
            if (confirmed && window.authService) {
                console.log('🚪 Procediendo con logout...');
                
                // Limpiar estado local ANTES del logout
                this.isAuthenticated = false;
                this.currentUser = null;
                
                // Hacer logout en el servicio
                const result = await window.authService.logout();
                console.log('🚪 Resultado de logout:', result);
                
                if (result.success) {
                    console.log('🚪 Logout exitoso, actualizando UI...');
                    
                    // Forzar actualización de UI
                    this.updateAuthSection();
                    
                    // Mostrar mensaje de éxito
                    await logoutModal.showSuccess();
                    
                    console.log('🚪 Navegando a home...');
                    // Navegar a home con un pequeño delay
                    setTimeout(() => {
                        if (window.appRouter) {
                            window.appRouter.navigate('/');
                        } else {
                            window.location.hash = '/';
                            window.location.reload();
                        }
                    }, 500);
                }
            } else {
                console.log('🚪 Logout cancelado o authService no disponible');
            }
        } catch (error) {
            console.error('❌ Error durante logout:', error);
            // Limpiar estado incluso si hay error
            this.isAuthenticated = false;
            this.currentUser = null;
            this.updateAuthSection();
        }
    }

    afterRender() {
        this.loadCSS();
        this.initializeTheme();
        this.initializeNavigation();
        this.initializeAuthEvents();
        this.initializeCart();
        
        // Actualizar authSection con delays para asegurar carga
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                this.updateAuthSection(true);
                setTimeout(() => this.updateAuthSection(true), 500);
                setTimeout(() => this.updateAuthSection(true), 1000);
            });
        });
    }

    loadCSS() {
        if (!this.cssLoaded) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = '/app/components/header/header.component.css';
            document.head.appendChild(link);
            this.cssLoaded = true;
        }
    }

    initializeTheme() {
        const toggle = document.getElementById('themeToggle');
        if (!toggle) return;

        toggle.addEventListener('click', () => {
            const body = document.body;
            const theme = body.getAttribute('data-theme') || 'light';
            const newTheme = theme === 'light' ? 'dark' : 'light';
            
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            toggle.textContent = newTheme === 'light' ? '🌙 Modo Oscuro' : '☀️ Modo Claro';
        });

        // Actualizar texto inicial
        const currentTheme = document.body.getAttribute('data-theme') || 'light';
        toggle.textContent = currentTheme === 'light' ? '🌙 Modo Oscuro' : '☀️ Modo Claro';
    }

    initializeNavigation() {
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[data-navigate]');
            if (link?.closest('header')) {
                e.preventDefault();
                const route = link.getAttribute('data-navigate');
                (window.appRouter ? window.appRouter.navigate(route) : window.location.hash = route);
            }
        });
    }

    initializeAuthEvents() {
        const events = ['auth:login', 'auth:logout', 'auth:validated', 'auth:profileUpdated'];
        events.forEach(event => document.addEventListener(event, () => this.refreshAuthState()));
        
        ['userLogin', 'authStateChanged', 'auth:profileUpdated'].forEach(event => 
            window.addEventListener(event, () => this.refreshAuthState())
        );
    }

    initializeCart() {
        this.updateCartCount();
        document.addEventListener('cart-updated', () => this.updateCartCount());
        
        const cartButton = document.getElementById('cartButton');
        if (cartButton) {
            cartButton.addEventListener('click', () => {
                if (window.CartModal) new window.CartModal().show();
            });
        }
    }

    updateCartCount() {
        const cartCount = document.getElementById('cartCount');
        if (cartCount && window.cartService) {
            const itemCount = window.cartService.getItemCount();
            cartCount.textContent = itemCount;
            cartCount.style.display = itemCount > 0 ? 'inline-block' : 'none';
        }
    }
}

window.HeaderComponent = HeaderComponent;
