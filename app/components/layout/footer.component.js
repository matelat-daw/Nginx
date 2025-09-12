// Footer Component - Economía Circular Canarias

class FooterComponent {
    constructor() {
        this.cssLoaded = false;
    }

    render() {
        // Devuelve un contenedor vacío, el HTML se inyecta en afterRender
        return '<div class="footer-component"></div>';
    }

    async afterRender() {
        // Cargar CSS solo una vez
        if (!this.cssLoaded) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'app/footer/footer.component.css';
            document.head.appendChild(link);
            this.cssLoaded = true;
        }

        // Cargar HTML de forma asíncrona
        const container = document.querySelector('.footer-component');
        if (container) {
            try {
                const html = await fetch('app/footer/footer.component.html').then(r => r.text());
                container.innerHTML = html;
                // Actualizar año dinámicamente
                const yearSpan = container.querySelector('#footer-year');
                if (yearSpan) yearSpan.textContent = new Date().getFullYear();
            } catch (e) {
                container.innerHTML = '<div>Error cargando footer.component.html</div>';
            }
        }

        setTimeout(() => {
            this.initializeIslandBadges();
        }, 0);
    }

    getElement() {
        return document.querySelector('.footer-component');
    }


    initializeIslandBadges() {
        const islandBadges = document.querySelectorAll('.island-badge');
        islandBadges.forEach((badge, index) => {
            badge.style.animationDelay = `${index * 0.1}s`;
            badge.addEventListener('mouseenter', () => {
                badge.style.transform = 'scale(1.1)';
                badge.style.transition = 'transform 0.3s ease';
            });
            badge.addEventListener('mouseleave', () => {
                badge.style.transform = 'scale(1)';
            });
        });
    }
}

// Exportar el componente
window.FooterComponent = FooterComponent;
