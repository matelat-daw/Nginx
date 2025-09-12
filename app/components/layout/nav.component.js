// Navigation Component - Economía Circular Canarias
class NavComponent {
    constructor() {
        this.template = `
            <nav>
                <div class="nav-content">
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="#/" class="nav-link" data-navigate="/">
                                🏠 Inicio
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#/productos" class="nav-link" data-navigate="/productos">
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

    render() {
        return this.template;
    }

    afterRender() {
        this.initializeNavigation();
        this.updateActiveLink();
    }

    initializeNavigation() {
        const navLinks = document.querySelectorAll('.nav-link[data-navigate]');
        
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const route = link.getAttribute('data-navigate');
                window.appRouter.navigate(route);
            });
        });
    }

    updateActiveLink() {
        const currentRoute = window.location.hash.slice(1) || '/';
        const navLinks = document.querySelectorAll('.nav-link[data-navigate]');
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            const linkRoute = link.getAttribute('data-navigate');
            
            if (linkRoute === currentRoute) {
                link.classList.add('active');
            }
        });
    }

    // Método para actualizar el enlace activo desde el router
    static updateActiveLink() {
        const currentRoute = window.location.hash.slice(1) || '/';
        const navLinks = document.querySelectorAll('.nav-link[data-navigate]');
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            const linkRoute = link.getAttribute('data-navigate');
            
            if (linkRoute === currentRoute) {
                link.classList.add('active');
            }
        });
    }
}

// Exportar el componente
window.NavComponent = NavComponent;
