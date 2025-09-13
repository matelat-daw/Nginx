// Footer Component - Economía Circular Canarias
class FooterComponent {
    constructor() {
        this.cssLoaded = false;
        this.template = null;
    }
    // Cargar template HTML
    async loadTemplate() {
        if (this.template) return this.template;
        try {
            const response = await fetch('/app/components/footer/footer.component.html');
            this.template = await response.text();
            return this.template;
        } catch (error) {
            console.error('Error cargando template del footer:', error);
            return this.getFallbackTemplate();
        }
    }
    // Template de respaldo si falla la carga
    getFallbackTemplate() {
        return `
            <footer>
                <div class="footer-content">
                    <p>💛 Hecho con amor en las Islas Canarias 💙</p>
                    <p>"Si compras aquí, vuelve a Ti"</p>
                    <div class="islands-list">
                        <span class="island-badge">🏝️ Tenerife</span>
                        <span class="island-badge">🏝️ Gran Canaria</span>
                        <span class="island-badge">🏝️ Lanzarote</span>
                        <span class="island-badge">🏝️ Fuerteventura</span>
                        <span class="island-badge">🏝️ La Palma</span>
                        <span class="island-badge">🏝️ La Gomera</span>
                        <span class="island-badge">🏝️ El Hierro</span>
                    </div>
                    <p class="mt-1">
                        <small>© <span id="footer-year"></span> Economía Circular Canarias. Todos los derechos reservados.</small>
                    </p>
                </div>
            </footer>
        `;
    }
    async render() {
        const template = await this.loadTemplate();
        return template;
    }
    async afterRender() {
        // Cargar CSS solo una vez
        if (!this.cssLoaded) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = '/app/components/footer/footer.component.css';
            document.head.appendChild(link);
            this.cssLoaded = true;
        }
        // Actualizar año dinámicamente
        const yearSpan = document.querySelector('#footer-year');
        if (yearSpan) {
            yearSpan.textContent = new Date().getFullYear();
        }
        setTimeout(() => {
            this.initializeIslandBadges();
        }, 0);
    }
    getElement() {
        return document.querySelector('footer');
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