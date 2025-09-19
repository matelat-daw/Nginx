/**
 * Componente de Perfil de Usuario
 * Gestiona el dashboard y perfil de usuarios compradores y vendedores
 */
class UserProfileComponent {
    constructor() {
        this.container = null;
        this.userData = null;
        this.currentView = 'dashboard';
        this.init();
    }
    init() {
        this.loadUserData();
    }
    async loadUserData() {
        try {
            const token = localStorage.getItem('authToken');
            if (!token) {
                window.location.href = '/login.html';
                return;
            }
            const response = await fetch('/api/auth/dashboard.php', {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });
            const result = await response.json();
            if (result.success) {
                this.userData = result.data;
                this.render();
            } else {
                console.error('Error al cargar datos del usuario:', result.error);
                this.showError('Error al cargar el perfil');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showError('Error de conexión');
        }
    }
    render() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'user-profile-container';
            document.body.appendChild(this.container);
        }
        this.container.innerHTML = `
            <div class="profile-header">
                <div class="profile-info">
                    <div class="profile-avatar">
                        <img src="${this.userData.user.profile_image || '/app/assets/images/default-avatar.png'}" 
                             alt="Avatar" class="avatar-img">
                    </div>
                    <div class="profile-details">
                        <h2>${this.userData.user.full_name || this.userData.user.username}</h2>
                        <p class="profile-email">${this.userData.user.email}</p>
                        <p class="profile-location">${this.userData.user.city || ''}, ${this.userData.user.island || ''}</p>
                    </div>
                </div>
                <div class="profile-stats">
                    <div class="stat-card">
                        <h3>${this.userData.buyer_stats.total_orders}</h3>
                        <p>Pedidos realizados</p>
                    </div>
                    <div class="stat-card">
                        <h3>${this.userData.seller_stats.total_products}</h3>
                        <p>Productos publicados</p>
                    </div>
                    <div class="stat-card">
                        <h3>${this.userData.seller_stats.total_sales}</h3>
                        <p>Ventas realizadas</p>
                    </div>
                </div>
            </div>
            <div class="profile-navigation">
                <button class="nav-btn ${this.currentView === 'dashboard' ? 'active' : ''}" 
                        onclick="userProfile.switchView('dashboard')">
                    Dashboard
                </button>
                <button class="nav-btn ${this.currentView === 'purchases' ? 'active' : ''}" 
                        onclick="userProfile.switchView('purchases')">
                    Mis Compras
                </button>
                <button class="nav-btn ${this.currentView === 'products' ? 'active' : ''}" 
                        onclick="userProfile.switchView('products')">
                    Mis Productos
                </button>
                <button class="nav-btn ${this.currentView === 'sales' ? 'active' : ''}" 
                        onclick="userProfile.switchView('sales')">
                    Mis Ventas
                </button>
                <button class="nav-btn ${this.currentView === 'profile' ? 'active' : ''}" 
                        onclick="userProfile.switchView('profile')">
                    Editar Perfil
                </button>
            </div>
            <div class="profile-content">
                ${this.renderCurrentView()}
            </div>
        `;
        this.attachEventListeners();
    }
    renderCurrentView() {
        switch (this.currentView) {
            case 'dashboard':
                return this.renderDashboard();
            case 'purchases':
                return this.renderPurchases();
            case 'products':
                return this.renderProducts();
            case 'sales':
                return this.renderSales();
            case 'profile':
                return this.renderProfileEdit();
            default:
                return this.renderDashboard();
        }
    }
    renderDashboard() {
        return `
            <div class="dashboard-grid">
                <div class="dashboard-section">
                    <h3>Actividad como Comprador</h3>
                    <div class="activity-stats">
                        <div class="stat-item">
                            <span class="stat-number">${this.userData.buyer_stats.total_orders}</span>
                            <span class="stat-label">Pedidos totales</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">${this.userData.buyer_stats.pending_orders}</span>
                            <span class="stat-label">Pedidos pendientes</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">€${this.userData.buyer_stats.total_spent.toFixed(2)}</span>
                            <span class="stat-label">Total gastado</span>
                        </div>
                    </div>
                    <h4>Compras Recientes</h4>
                    <div class="recent-items">
                        ${this.userData.recent_purchases.map(order => `
                            <div class="recent-item">
                                <div class="item-info">
                                    <strong>#${order.order_number}</strong>
                                    <span class="item-status status-${order.status}">${this.getStatusText(order.status)}</span>
                                </div>
                                <div class="item-details">
                                    <span>${order.item_count} artículo(s)</span>
                                    <span>€${order.total_amount}</span>
                                    <span>${new Date(order.created_at).toLocaleDateString()}</span>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                <div class="dashboard-section">
                    <h3>Actividad como Vendedor</h3>
                    <div class="activity-stats">
                        <div class="stat-item">
                            <span class="stat-number">${this.userData.seller_stats.active_products}</span>
                            <span class="stat-label">Productos activos</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">${this.userData.seller_stats.recent_sales}</span>
                            <span class="stat-label">Ventas (30 días)</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">€${this.userData.seller_stats.recent_revenue.toFixed(2)}</span>
                            <span class="stat-label">Ingresos (30 días)</span>
                        </div>
                    </div>
                    <h4>Ventas Recientes</h4>
                    <div class="recent-items">
                        ${this.userData.recent_sales.map(sale => `
                            <div class="recent-item">
                                <div class="item-info">
                                    <strong>${sale.product_name}</strong>
                                    <span class="item-status status-${sale.status}">${this.getStatusText(sale.status)}</span>
                                </div>
                                <div class="item-details">
                                    <span>Cantidad: ${sale.quantity}</span>
                                    <span>€${sale.line_total}</span>
                                    <span>${new Date(sale.created_at).toLocaleDateString()}</span>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                ${this.userData.products_attention.length > 0 ? `
                    <div class="dashboard-section attention-section">
                        <h3>⚠️ Productos que Requieren Atención</h3>
                        <div class="attention-items">
                            ${this.userData.products_attention.map(product => `
                                <div class="attention-item">
                                    <strong>${product.name}</strong>
                                    <span class="attention-reason">
                                        ${product.status === 'draft' ? 'Borrador sin publicar' : 
                                          product.stock_quantity <= product.stock_alert_level ? 'Stock bajo' : ''}
                                    </span>
                                    <button onclick="userProfile.editProduct(${product.id})" class="btn-small">
                                        Editar
                                    </button>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    }
    renderPurchases() {
        // Este método cargará las compras dinámicamente
        return `
            <div class="purchases-section">
                <div class="section-header">
                    <h3>Mis Compras</h3>
                    <div class="filters">
                        <select id="order-status-filter">
                            <option value="all">Todos los estados</option>
                            <option value="pending">Pendiente</option>
                            <option value="paid">Pagado</option>
                            <option value="processing">En preparación</option>
                            <option value="delivered">Entregado</option>
                            <option value="cancelled">Cancelado</option>
                        </select>
                    </div>
                </div>
                <div id="purchases-list" class="loading">
                    Cargando compras...
                </div>
                <div id="purchases-pagination"></div>
            </div>
        `;
    }
    renderProducts() {
        return `
            <div class="products-section">
                <div class="section-header">
                    <h3>Mis Productos</h3>
                    <div class="actions">
                        <button onclick="userProfile.showAddProductForm()" class="btn-primary">
                            + Agregar Producto
                        </button>
                        <select id="product-status-filter">
                            <option value="all">Todos</option>
                            <option value="active">Activos</option>
                            <option value="draft">Borradores</option>
                            <option value="inactive">Inactivos</option>
                        </select>
                    </div>
                </div>
                <div id="products-list" class="loading">
                    Cargando productos...
                </div>
                <div id="products-pagination"></div>
            </div>
        `;
    }
    renderSales() {
        return `
            <div class="sales-section">
                <div class="section-header">
                    <h3>Mis Ventas</h3>
                    <div class="filters">
                        <select id="sales-period-filter">
                            <option value="all">Todo el tiempo</option>
                            <option value="today">Hoy</option>
                            <option value="week">Esta semana</option>
                            <option value="month">Este mes</option>
                            <option value="year">Este año</option>
                        </select>
                        <select id="sales-status-filter">
                            <option value="all">Todos los estados</option>
                            <option value="pending">Pendiente</option>
                            <option value="paid">Pagado</option>
                            <option value="delivered">Entregado</option>
                        </select>
                    </div>
                </div>
                <div id="sales-stats" class="loading">
                    Cargando estadísticas...
                </div>
                <div id="sales-list" class="loading">
                    Cargando ventas...
                </div>
                <div id="sales-pagination"></div>
            </div>
        `;
    }
    renderProfileEdit() {
        return `
            <div class="profile-edit-section">
                <h3>Editar Perfil</h3>
                <form id="profile-edit-form" class="profile-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Nombre de usuario</label>
                            <input type="text" id="username" name="username" 
                                   value="${this.userData.user.username || ''}" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" 
                                   value="${this.userData.user.email || ''}" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Nombre completo</label>
                            <input type="text" id="full_name" name="full_name" 
                                   value="${this.userData.user.full_name || ''}">
                        </div>
                        <div class="form-group">
                            <label for="phone">Teléfono</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="${this.userData.user.phone || ''}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="island">Isla</label>
                            <select id="island" name="island">
                                <option value="">Seleccionar isla</option>
                                <option value="Gran Canaria" ${this.userData.user.island === 'Gran Canaria' ? 'selected' : ''}>Gran Canaria</option>
                                <option value="Tenerife" ${this.userData.user.island === 'Tenerife' ? 'selected' : ''}>Tenerife</option>
                                <option value="Lanzarote" ${this.userData.user.island === 'Lanzarote' ? 'selected' : ''}>Lanzarote</option>
                                <option value="Fuerteventura" ${this.userData.user.island === 'Fuerteventura' ? 'selected' : ''}>Fuerteventura</option>
                                <option value="La Palma" ${this.userData.user.island === 'La Palma' ? 'selected' : ''}>La Palma</option>
                                <option value="La Gomera" ${this.userData.user.island === 'La Gomera' ? 'selected' : ''}>La Gomera</option>
                                <option value="El Hierro" ${this.userData.user.island === 'El Hierro' ? 'selected' : ''}>El Hierro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="city">Ciudad</label>
                            <input type="text" id="city" name="city" 
                                   value="${this.userData.user.city || ''}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="address">Dirección</label>
                        <textarea id="address" name="address" rows="3">${this.userData.user.address || ''}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="postal_code">Código postal</label>
                        <input type="text" id="postal_code" name="postal_code" 
                               value="${this.userData.user.postal_code || ''}">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Guardar Cambios</button>
                        <button type="button" onclick="userProfile.deleteAccount()" class="btn-danger">
                            Eliminar Cuenta
                        </button>
                    </div>
                </form>
            </div>
        `;
    }
    // Métodos auxiliares
    getStatusText(status) {
        const statusMap = {
            'pending': 'Pendiente',
            'paid': 'Pagado',
            'processing': 'En preparación',
            'shipped': 'Enviado',
            'delivered': 'Entregado',
            'cancelled': 'Cancelado',
            'refunded': 'Reembolsado'
        };
        return statusMap[status] || status;
    }
    switchView(view) {
        this.currentView = view;
        this.render();
        // Cargar datos específicos de la vista
        if (view === 'purchases') {
            this.loadPurchases();
        } else if (view === 'products') {
            this.loadProducts();
        } else if (view === 'sales') {
            this.loadSales();
        }
    }
    attachEventListeners() {
        // Event listener para el formulario de perfil
        const profileForm = document.getElementById('profile-edit-form');
        if (profileForm) {
            profileForm.addEventListener('submit', (e) => this.updateProfile(e));
        }
        // Event listeners para filtros
        const orderStatusFilter = document.getElementById('order-status-filter');
        if (orderStatusFilter) {
            orderStatusFilter.addEventListener('change', () => this.loadPurchases());
        }
        const productStatusFilter = document.getElementById('product-status-filter');
        if (productStatusFilter) {
            productStatusFilter.addEventListener('change', () => this.loadProducts());
        }
        const salesPeriodFilter = document.getElementById('sales-period-filter');
        const salesStatusFilter = document.getElementById('sales-status-filter');
        if (salesPeriodFilter) {
            salesPeriodFilter.addEventListener('change', () => this.loadSales());
        }
        if (salesStatusFilter) {
            salesStatusFilter.addEventListener('change', () => this.loadSales());
        }
    }
    // Métodos para cargar datos específicos (implementar según necesidad)
    async loadPurchases(page = 1) {
        // Implementar carga de compras
    }
    async loadProducts(page = 1) {
        // Implementar carga de productos
    }
    async loadSales(page = 1) {
        // Implementar carga de ventas
    }
    async updateProfile(event) {
        event.preventDefault();
        // Implementar actualización de perfil
    }
    async deleteAccount() {
        if (confirm('¿Estás seguro de que quieres eliminar tu cuenta? Esta acción no se puede deshacer.')) {
            // Implementar eliminación de cuenta
        }
    }
    showAddProductForm() {
        // Mostrar formulario para agregar producto
    }
    editProduct(productId) {
        // Editar producto específico
    }
    showError(message) {
        // Mostrar mensaje de error
        console.error(message);
    }
}
// Inicializar el componente cuando se carga la página
let userProfile;
document.addEventListener('DOMContentLoaded', () => {
    userProfile = new UserProfileComponent();
});
