// Products Component - Economía Circular Canarias
class ProductsComponent {
    constructor() {
        this.productos = [
            {
                id: 1,
                nombre: "Queso Majorero",
                descripcion: "Auténtico queso de cabra de Fuerteventura con Denominación de Origen Protegida.",
                precio: "15.90",
                origen: "Fuerteventura",
                categoria: "Lácteos",
                sostenible: true,
                imagen: "🧀"
            },
            {
                id: 2,
                nombre: "Plátano de Canarias",
                descripcion: "Plátanos cultivados de manera sostenible con la marca de calidad IGP.",
                precio: "3.50",
                origen: "La Palma",
                categoria: "Frutas",
                sostenible: true,
                imagen: "🍌"
            },
            {
                id: 3,
                nombre: "Miel de Palma",
                descripcion: "Miel artesanal extraída de la savia de palmera canaria siguiendo métodos tradicionales.",
                precio: "25.00",
                origen: "La Palma",
                categoria: "Endulzantes",
                sostenible: true,
                imagen: "🍯"
            },
            {
                id: 4,
                nombre: "Vino Malvasía",
                descripcion: "Vino dulce tradicional de Lanzarote con Denominación de Origen.",
                precio: "18.50",
                origen: "Lanzarote",
                categoria: "Bebidas",
                sostenible: true,
                imagen: "🍷"
            },
            {
                id: 5,
                nombre: "Papas Arrugadas",
                descripcion: "Papas canarias cultivadas tradicionalmente, perfectas para papas arrugadas.",
                precio: "4.20",
                origen: "Tenerife",
                categoria: "Hortalizas",
                sostenible: true,
                imagen: "🥔"
            },
            {
                id: 6,
                nombre: "Mojo Picón",
                descripcion: "Salsa tradicional canaria elaborada con pimientos rojos y especias locales.",
                precio: "6.80",
                origen: "Gran Canaria",
                categoria: "Condimentos",
                sostenible: true,
                imagen: "🌶️"
            }
        ];
        this.template = this.generateTemplate();
    }
    generateTemplate() {
        const productsHTML = this.productos.map(product => `
            <div class="card producto-card" data-producto-id="${product.id}">
                <div class="producto-header">
                    <span class="producto-emoji">${product.imagen}</span>
                    <div class="producto-badges">
                        <span class="badge badge-origen">🏝️ ${product.origen}</span>
                        ${product.sostenible ? '<span class="badge badge-sostenible">♻️ Sostenible</span>' : ''}
                    </div>
                </div>
                <h3>${product.nombre}</h3>
                <p class="producto-descripcion">${product.descripcion}</p>
                <div class="producto-info">
                    <span class="categoria">📂 ${product.categoria}</span>
                    <span class="precio">💰 ${product.precio}€</span>
                </div>
                <div class="producto-actions mt-1">
                    <button class="btn btn-primary btn-ver-producto" data-producto-id="${product.id}">
                        👁️ Ver Producto
                    </button>
                    <button class="btn btn-success btn-comprar" data-producto-id="${product.id}">
                        🛒 Comprar
                    </button>
                </div>
            </div>
        `).join('');
        return `
            <div class="productos-component">
                <section class="productos-header text-center mb-2">
                    <h1>🛒 Productos Locales de Canarias</h1>
                    <p>Descubre la mejor selección de productos canarios sostenibles y de calidad</p>
                </section>
                <section class="filtros mb-2">
                    <div class="card">
                        <h3>🔍 Filtrar Productos</h3>
                        <div class="filtros-container">
                            <select id="filtroCategoria" class="filtro-select">
                                <option value="">Todas las categorías</option>
                                <option value="Lácteos">Lácteos</option>
                                <option value="Frutas">Frutas</option>
                                <option value="Endulzantes">Endulzantes</option>
                                <option value="Bebidas">Bebidas</option>
                                <option value="Hortalizas">Hortalizas</option>
                                <option value="Condimentos">Condimentos</option>
                            </select>
                            <select id="filtroOrigen" class="filtro-select">
                                <option value="">Todas las islas</option>
                                <option value="Tenerife">Tenerife</option>
                                <option value="Gran Canaria">Gran Canaria</option>
                                <option value="Lanzarote">Lanzarote</option>
                                <option value="Fuerteventura">Fuerteventura</option>
                                <option value="La Palma">La Palma</option>
                                <option value="La Gomera">La Gomera</option>
                                <option value="El Hierro">El Hierro</option>
                            </select>
                            <button id="limpiarFiltros" class="btn btn-secondary">
                                🧹 Limpiar Filtros
                            </button>
                        </div>
                    </div>
                </section>
                <section class="productos-grid">
                    <div class="grid grid-3" id="productosContainer">
                        ${productsHTML}
                    </div>
                </section>
                <section class="productos-stats text-center mt-2">
                    <div class="card">
                        <h3>📊 Estadísticas de Productos</h3>
                        <div class="grid grid-3 mt-1">
                            <div>
                                <h4 style="color: var(--canarias-blue);">${this.productos.length}</h4>
                                <p>Productos Disponibles</p>
                            </div>
                            <div>
                                <h4 style="color: var(--canarias-green);">${this.productos.filter(p => p.sostenible).length}</h4>
                                <p>Productos Sostenibles</p>
                            </div>
                            <div>
                                <h4 style="color: var(--canarias-yellow);">7</h4>
                                <p>Islas Representadas</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            <style>
                .producto-card {
                    position: relative;
                    overflow: hidden;
                }
                .producto-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    margin-bottom: 1rem;
                }
                .producto-emoji {
                    font-size: 3rem;
                }
                .producto-badges {
                    display: flex;
                    flex-direction: column;
                    gap: 0.5rem;
                    align-items: flex-end;
                }
                .badge {
                    padding: 0.3rem 0.8rem;
                    border-radius: 15px;
                    font-size: 0.8rem;
                    font-weight: bold;
                }
                .badge-origen {
                    background: var(--canarias-blue);
                    color: white;
                }
                .badge-sostenible {
                    background: var(--canarias-green);
                    color: white;
                }
                .producto-descripcion {
                    color: #666;
                    line-height: 1.5;
                    margin-bottom: 1rem;
                }
                .producto-info {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 1rem;
                    font-size: 0.9rem;
                }
                .precio {
                    font-weight: bold;
                    color: var(--canarias-green);
                    font-size: 1.1rem;
                }
                .producto-actions {
                    display: flex;
                    gap: 0.5rem;
                }
                .producto-actions .btn {
                    flex: 1;
                    padding: 0.5rem;
                    font-size: 0.9rem;
                }
                .filtros-container {
                    display: flex;
                    gap: 1rem;
                    flex-wrap: wrap;
                    align-items: center;
                    margin-top: 1rem;
                }
                .filtro-select {
                    padding: 0.5rem;
                    border: 2px solid var(--canarias-border);
                    border-radius: 5px;
                    background: white;
                    flex: 1;
                    min-width: 150px;
                }
                @media (max-width: 768px) {
                    .producto-actions {
                        flex-direction: column;
                    }
                    .filtros-container {
                        flex-direction: column;
                    }
                    .filtro-select {
                        width: 100%;
                    }
                }
            </style>
        `;
    }
    render() {
        return this.template;
    }
    afterRender() {
        this.initializeFiltros();
        this.initializeProductoActions();
    }
    initializeFiltros() {
        const filtroCategoria = document.getElementById('filtroCategoria');
        const filtroOrigen = document.getElementById('filtroOrigen');
        const limpiarFiltros = document.getElementById('limpiarFiltros');
        if (filtroCategoria) {
            filtroCategoria.addEventListener('change', () => this.aplicarFiltros());
        }
        if (filtroOrigen) {
            filtroOrigen.addEventListener('change', () => this.aplicarFiltros());
        }
        if (limpiarFiltros) {
            limpiarFiltros.addEventListener('click', () => {
                filtroCategoria.value = '';
                filtroOrigen.value = '';
                this.aplicarFiltros();
            });
        }
    }
    aplicarFiltros() {
        const categoriaSeleccionada = document.getElementById('filtroCategoria').value;
        const origenSeleccionado = document.getElementById('filtroOrigen').value;
        const filteredProducts = this.productos.filter(product => {
            const coincideCategoria = !categoriaSeleccionada || product.categoria === categoriaSeleccionada;
            const coincideOrigen = !origenSeleccionado || product.origen === origenSeleccionado;
            return coincideCategoria && coincideOrigen;
        });
        this.renderProductos(filteredProducts);
    }
    renderProductos(products) {
        const container = document.getElementById('productosContainer');
        if (!container) return;
        const productsHTML = products.map(product => `
            <div class="card producto-card" data-producto-id="${product.id}">
                <div class="producto-header">
                    <span class="producto-emoji">${product.imagen}</span>
                    <div class="producto-badges">
                        <span class="badge badge-origen">🏝️ ${product.origen}</span>
                        ${product.sostenible ? '<span class="badge badge-sostenible">♻️ Sostenible</span>' : ''}
                    </div>
                </div>
                <h3>${product.nombre}</h3>
                <p class="producto-descripcion">${product.descripcion}</p>
                <div class="producto-info">
                    <span class="categoria">📂 ${product.categoria}</span>
                    <span class="precio">💰 ${product.precio}€</span>
                </div>
                <div class="producto-actions mt-1">
                    <button class="btn btn-primary btn-ver-producto" data-producto-id="${product.id}">
                        👁️ Ver Producto
                    </button>
                    <button class="btn btn-success btn-comprar" data-producto-id="${product.id}">
                        🛒 Comprar
                    </button>
                </div>
            </div>
        `).join('');
        container.innerHTML = productsHTML;
        this.initializeProductoActions();
    }
    initializeProductoActions() {
        // Usar event delegation para evitar múltiples listeners
        const container = document.getElementById('productosContainer');
        
        if (container) {
            // Remover listeners previos si existen
            if (container.dataset.initialized) {
                // Clonar el elemento para remover todos los event listeners
                const newContainer = container.cloneNode(true);
                container.parentNode.replaceChild(newContainer, container);
                // Actualizar referencia
                const freshContainer = document.getElementById('productosContainer');
                freshContainer.dataset.initialized = 'true';
                this.setupContainerListeners(freshContainer);
            } else {
                container.dataset.initialized = 'true';
                this.setupContainerListeners(container);
            }
        }
    }

    setupContainerListeners(container) {
        container.addEventListener('click', (e) => {
            const target = e.target.closest('button');
            if (!target) return;
            
            const productId = target.getAttribute('data-producto-id');
            if (!productId) return;
            
            if (target.classList.contains('btn-ver-producto')) {
                e.preventDefault();
                this.verProducto(productId);
            } else if (target.classList.contains('btn-comprar')) {
                e.preventDefault();
                this.comprarProducto(productId);
            }
        });
    }
    verProducto(productId) {
        const product = this.productos.find(p => p.id == productId);
        if (product) {
            alert(`Ver detalles de: ${product.nombre}\n\n${product.descripcion}\n\nPrecio: ${product.precio}€\nOrigen: ${product.origen}`);
        }
    }
    comprarProducto(productId) {
        const product = this.productos.find(p => p.id == productId);
        
        if (product && window.cartService) {
            // Crear objeto de producto compatible con el carrito
            const cartProduct = {
                id: product.id,
                name: product.nombre,
                title: product.nombre,
                price: parseFloat(product.precio),
                image: '/assets/img/default-product.jpg', // Usar imagen por defecto
                category: product.categoria
            };
            
            // Agregar al carrito
            const success = window.cartService.addItem(cartProduct, 1);
            
            if (success) {
                // Mostrar notificación de éxito
                this.showAddToCartNotification(product);
            } else {
                alert('Error al agregar el producto al carrito');
            }
        } else {
            // Fallback si no hay cartService
            alert(`¡Producto agregado al carrito!\n\n${product.nombre} - ${product.precio}€\n\n¡Gracias por apoyar la economía local canaria!`);
        }
    }

    // Mostrar notificación de producto agregado
    showAddToCartNotification(product) {
        // Crear notificación temporal
        const notification = document.createElement('div');
        notification.className = 'cart-notification';
        notification.innerHTML = `
            <div class="cart-notification-content">
                <span class="cart-notification-icon">✅</span>
                <div class="cart-notification-text">
                    <strong>${product.nombre}</strong><br>
                    ¡Agregado al carrito!
                </div>
                <button class="cart-notification-close">✕</button>
            </div>
        `;

        // Agregar estilos dinámicamente si no existen
        if (!document.getElementById('cart-notification-styles')) {
            const styles = document.createElement('style');
            styles.id = 'cart-notification-styles';
            styles.textContent = `
                .cart-notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #28a745;
                    color: white;
                    padding: 1rem;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                    z-index: 9999;
                    animation: slideInRight 0.3s ease;
                }

                .cart-notification-content {
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                }

                .cart-notification-icon {
                    font-size: 1.5rem;
                }

                .cart-notification-text {
                    flex: 1;
                }

                .cart-notification-close {
                    background: none;
                    border: none;
                    color: white;
                    cursor: pointer;
                    font-size: 1.2rem;
                    padding: 0;
                    margin-left: 0.5rem;
                }

                @keyframes slideInRight {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }

                @keyframes slideOutRight {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(styles);
        }

        // Agregar al DOM
        document.body.appendChild(notification);

        // Configurar auto-close
        const closeNotification = () => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        };

        // Cerrar automáticamente después de 3 segundos
        setTimeout(closeNotification, 3000);

        // Cerrar al hacer clic en el botón X
        const closeBtn = notification.querySelector('.cart-notification-close');
        closeBtn.addEventListener('click', closeNotification);
    }
}
// Exportar el componente
window.ProductsComponent = ProductsComponent;
