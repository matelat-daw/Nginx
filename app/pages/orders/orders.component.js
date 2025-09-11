/**
 * Componente de Pedidos - Economía Circular Canarias
 */

class OrdersComponent {
    constructor() {
        this.orders = [];
        this.loading = false;
    }

    async render() {
        return `
            <div class="orders-container">
                <div class="orders-header">
                    <h1>📦 Mis Pedidos</h1>
                    <p class="orders-subtitle">Historial completo de tus compras en Economía Circular Canarias</p>
                </div>
                
                <div class="orders-content">
                    <div id="ordersLoader" class="loader" style="display: none;">
                        <div class="spinner"></div>
                        <p>Cargando pedidos...</p>
                    </div>
                    
                    <div id="ordersError" class="error-message" style="display: none;"></div>
                    
                    <div id="ordersList" class="orders-list">
                        <!-- Los pedidos se cargarán aquí -->
                    </div>
                    
                    <div id="noOrders" class="no-orders" style="display: none;">
                        <div class="no-orders-icon">📭</div>
                        <h3>No tienes pedidos aún</h3>
                        <p>Cuando realices tu primera compra, aparecerá aquí.</p>
                        <a href="#/productos" class="btn-primary">Ver productos</a>
                    </div>
                </div>
            </div>
        `;
    }

    async afterRender() {
        await this.loadOrders();
    }

    async loadOrders() {
        try {
            this.showLoader(true);
            this.hideError();

            const response = await fetch('/api/orders/my-orders.php', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (data.success) {
                this.orders = data.data || data.orders || [];
                this.renderOrders();
            } else {
                throw new Error(data.message || 'Error al cargar pedidos');
            }

        } catch (error) {
            console.error('Error loading orders:', error);
            this.showError('Error al cargar los pedidos: ' + error.message);
        } finally {
            this.showLoader(false);
        }
    }

    renderOrders() {
        const ordersList = document.getElementById('ordersList');
        const noOrders = document.getElementById('noOrders');

        if (!this.orders || this.orders.length === 0) {
            if (ordersList) ordersList.style.display = 'none';
            if (noOrders) noOrders.style.display = 'block';
            return;
        }

        if (noOrders) noOrders.style.display = 'none';
        if (ordersList) {
            ordersList.style.display = 'block';
            ordersList.innerHTML = this.orders.map(order => this.renderOrderCard(order)).join('');
        }
    }

    renderOrderCard(order) {
        const statusClass = this.getStatusClass(order.status);
        const statusText = this.getStatusText(order.status);
        const orderDate = new Date(order.created_at).toLocaleDateString('es-ES');
        const total = parseFloat(order.total || 0).toFixed(2);

        return `
            <div class="order-card" data-order-id="${order.id}">
                <div class="order-header">
                    <div class="order-info">
                        <h3>Pedido #${order.id}</h3>
                        <p class="order-date">📅 ${orderDate}</p>
                    </div>
                    <div class="order-status">
                        <span class="status-badge ${statusClass}">${statusText}</span>
                        <div class="order-total">€${total}</div>
                    </div>
                </div>
                
                <div class="order-items">
                    <p>Método de pago: ${order.payment_method || 'No especificado'}</p>
                    ${order.shipping_address ? `<p>Dirección: ${order.shipping_address}</p>` : ''}
                </div>
                
                <div class="order-actions">
                    <button class="btn-secondary" onclick="alert('Función en desarrollo')">
                        👁️ Ver detalles
                    </button>
                </div>
            </div>
        `;
    }

    getStatusClass(status) {
        const statusClasses = {
            'pending': 'status-pending',
            'confirmed': 'status-confirmed',
            'processing': 'status-processing',
            'shipped': 'status-shipped',
            'delivered': 'status-delivered',
            'cancelled': 'status-cancelled'
        };
        return statusClasses[status] || 'status-unknown';
    }

    getStatusText(status) {
        const statusTexts = {
            'pending': 'Pendiente',
            'confirmed': 'Confirmado',
            'processing': 'En proceso',
            'shipped': 'Enviado',
            'delivered': 'Entregado',
            'cancelled': 'Cancelado'
        };
        return statusTexts[status] || 'Desconocido';
    }

    showLoader(show) {
        const loader = document.getElementById('ordersLoader');
        if (loader) {
            loader.style.display = show ? 'flex' : 'none';
        }
    }

    showError(message) {
        const errorDiv = document.getElementById('ordersError');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }
    }

    hideError() {
        const errorDiv = document.getElementById('ordersError');
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
    }
}
