// Products Component - Economía Circular Canarias
class ProductsComponent {
    constructor() {
        this.products = [
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
        this.template = null;
        this.productCardTemplate = null;
    }

    // Load external template and CSS
    async loadTemplate() {
        try {
            // Load HTML template
            const htmlResponse = await fetch('./app/pages/products/products.component.html');
            if (!htmlResponse.ok) throw new Error('Failed to load HTML template');
            this.template = await htmlResponse.text();

            // Load CSS if not already loaded
            if (!document.getElementById('products-component-styles')) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = './app/pages/products/products.component.css';
                link.id = 'products-component-styles';
                document.head.appendChild(link);
            }

            return true;
        } catch (error) {
            console.error('Error loading template:', error);
            return false;
        }
    }

    generateProductsHTML(products = this.products) {
        return products.map(product => `
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
    }

    async render() {
        if (!this.template) {
            const loaded = await this.loadTemplate();
            if (!loaded) {
                return '<div class="error">Error loading products component</div>';
            }
        }

        // Replace dynamic content in template
        let renderedTemplate = this.template;
        
        // Replace products container
        const productsHTML = this.generateProductsHTML();
        renderedTemplate = renderedTemplate.replace('<!-- Los productos se cargarán dinámicamente aquí -->', productsHTML);

        // Update statistics
        renderedTemplate = renderedTemplate.replace('id="totalProducts">0', `id="totalProducts">${this.products.length}`);
        renderedTemplate = renderedTemplate.replace('id="sustainableProducts">0', `id="sustainableProducts">${this.products.filter(p => p.sostenible).length}`);

        return renderedTemplate;
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
        
        const filteredProducts = this.products.filter(product => {
            const coincideCategoria = !categoriaSeleccionada || product.categoria === categoriaSeleccionada;
            const coincideOrigen = !origenSeleccionado || product.origen === origenSeleccionado;
            return coincideCategoria && coincideOrigen;
        });

        this.renderProductos(filteredProducts);
    }

    renderProductos(products) {
        const container = document.getElementById('productosContainer');
        if (!container) return;

        const productsHTML = this.generateProductsHTML(products);
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
        const product = this.products.find(p => p.id == productId);
        if (product) {
            alert(`Ver detalles de: ${product.nombre}\n\n${product.descripcion}\n\nPrecio: ${product.precio}€\nOrigen: ${product.origen}`);
        }
    }

    comprarProducto(productId) {
        const product = this.products.find(p => p.id == productId);
        
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