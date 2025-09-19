// Navigation Component - Economía Circular Canarias
class NavComponent {
    constructor() {
        this.template = null;
        this.cssLoaded = false;
    }
    // Cargar template HTML
    async loadTemplate() {
        if (this.template) return this.template;
        try {
            const response = await fetch('/app/components/nav/nav.component.html');
            this.template = await response.text();
            return this.template;
        } catch (error) {
            console.error('Error cargando template del nav:', error);
            return this.getFallbackTemplate();
        }
    }
    // Template de respaldo si falla la carga
    getFallbackTemplate() {
        return `
            <nav>
                <div class="nav-content">
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="#/" class="nav-link" data-navigate="/">
                                🏠 Inicio
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#/products" class="nav-link" data-navigate="/products">
                                🛒 Productos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#/economia-circular" class="nav-link" data-navigate="/economia-circular">
                                ♻️ Economía Circular
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#/sobre-nosotros" class="nav-link" data-navigate="/sobre-nosotros">
                                ℹ️ Sobre Nosotros
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#/contacto" class="nav-link" data-navigate="/contacto">
                                📞 Contacto
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
        `;
    }
    async render() {
        const template = await this.loadTemplate();
        return template;
    }
    afterRender() {
        // Cargar CSS solo una vez
        if (!this.cssLoaded) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = '/app/components/nav/nav.component.css';
            document.head.appendChild(link);
            this.cssLoaded = true;
        }
        this.initializeNavigation();
        this.updateActiveLink();
    }
    initializeNavigation() {
        const navLinks = document.querySelectorAll('.nav-link[data-navigate]');
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const route = link.getAttribute('data-navigate');
                if (window.appRouter) {
                    window.appRouter.navigate(route);
                } else {
                    window.location.hash = route;
                }
            });
        });
    }
    updateActiveLink() {
        // Remover clase activa de todos los enlaces
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => link.classList.remove('active'));
        // Obtener ruta actual
        const currentRoute = window.location.hash.slice(1) || '/';
        // Encontrar y marcar el enlace activo
        const activeLink = document.querySelector(`.nav-link[data-navigate="${currentRoute}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }
    }
    // Método estático para actualizar enlace activo (para uso externo)
    static updateActiveLink() {
        const navComponent = document.querySelector('nav');
        if (navComponent) {
            // Remover clase activa de todos los enlaces
            const navLinks = navComponent.querySelectorAll('.nav-link');
            navLinks.forEach(link => link.classList.remove('active'));
            // Obtener ruta actual
            const currentRoute = window.location.hash.slice(1) || '/';
            // Encontrar y marcar el enlace activo
            const activeLink = navComponent.querySelector(`.nav-link[data-navigate="${currentRoute}"]`);
            if (activeLink) {
                activeLink.classList.add('active');
            }
        }
    }
}
// Exportar el componente
window.NavComponent = NavComponent;