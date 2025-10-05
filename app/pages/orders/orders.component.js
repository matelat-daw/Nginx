/**
 * Componente de Pedidos - Economía Circular Canarias
 */
class OrdersComponent {
    constructor() {
        this.orders = [];
        this.loading = false;
        this.template = null; // Para cachear el template
    }
    async render(container) {
        // Cargar estilos CSS del componente
        this.loadStyles();
        // Si se proporciona un container, renderizar directamente
        if (container) {
            // Cargar template si no está cacheado
            if (!this.template) {
                await this.loadTemplate();
            }
            container.innerHTML = this.template;
            // Ejecutar afterRender después de un pequeño delay
            setTimeout(() => this.afterRender(), 10);
            return;
        }
        // Si no hay container, cargar y retornar el template
        if (!this.template) {
            await this.loadTemplate();
        }
        return this.template;
    }
    async loadTemplate() {
        // Cargar template de manera asíncrona usando fetch
        try {
            const response = await fetch('/app/pages/orders/orders.component.html');
            if (response.ok) {
                this.template = await response.text();
            } else {
                throw new Error(`Error cargando template: ${response.status}`);
            }
        } catch (error) {
            console.error('Error cargando template HTML:', error);
            this.template = this.getFallbackTemplate();
        }
    }
    getFallbackTemplate() {
        return `
            <div class="orders-container">
                <div class="orders-header">
                    <h1>📦 Mis Pedidos</h1>
                    <p class="orders-subtitle">Historial completo de tus compras en Economía Circular Canarias</p>
                </div>
                <div class="orders-content">
                    <div class="error-message">
                        <h2>Error al cargar la página</h2>
                        <p>No se pudo cargar el contenido de pedidos.</p>
                    </div>
                </div>
            </div>
        `;
    }
    loadStyles() {
        // Verificar si ya se cargaron los estilos
        if (!document.querySelector('link[href="/app/pages/orders/orders.component.css"]')) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = '/app/pages/orders/orders.component.css';
            document.head.appendChild(link);
        }
    }
    async afterRender() {
        // Por ahora solo mostramos el mensaje de no pedidos
        // Más adelante se puede agregar la carga real de pedidos
        this.showNoOrders();
    }
    showNoOrders() {
        const noOrders = document.getElementById('noOrders');
        const ordersList = document.getElementById('ordersList');
        if (noOrders) {
            noOrders.style.display = 'block';
        }
        if (ordersList) {
            ordersList.style.display = 'none';
        }
    }
}
// Exportar el componente
window.OrdersComponent = OrdersComponent;
