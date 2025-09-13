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
        const productosHTML = this.productos.map(producto => `
            <div class="card producto-card" data-producto-id="${producto.id}">
                <div class="producto-header">
                    <span class="producto-emoji">${producto.imagen}</span>
                    <div class="producto-badges">
                        <span class="badge badge-origen">🏝️ ${producto.origen}</span>
                        ${producto.sostenible ? '<span class="badge badge-sostenible">♻️ Sostenible</span>' : ''}
                    </div>
                </div>
                <h3>${producto.nombre}</h3>
                <p class="producto-descripcion">${producto.descripcion}</p>
                <div class="producto-info">
                    <span class="categoria">📂 ${producto.categoria}</span>
                    <span class="precio">💰 ${producto.precio}€</span>
                </div>
                <div class="producto-actions mt-1">
                    <button class="btn btn-primary btn-ver-producto" data-producto-id="${producto.id}">
                        👁️ Ver Producto
                    </button>
                    <button class="btn btn-success btn-comprar" data-producto-id="${producto.id}">
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
                        ${productosHTML}
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
        const productosFiltrados = this.productos.filter(producto => {
            const coincideCategoria = !categoriaSeleccionada || producto.categoria === categoriaSeleccionada;
            const coincideOrigen = !origenSeleccionado || producto.origen === origenSeleccionado;
            return coincideCategoria && coincideOrigen;
        });
        this.renderProductos(productosFiltrados);
    }
    renderProductos(productos) {
        const container = document.getElementById('productosContainer');
        if (!container) return;
        const productosHTML = productos.map(producto => `
            <div class="card producto-card" data-producto-id="${producto.id}">
                <div class="producto-header">
                    <span class="producto-emoji">${producto.imagen}</span>
                    <div class="producto-badges">
                        <span class="badge badge-origen">🏝️ ${producto.origen}</span>
                        ${producto.sostenible ? '<span class="badge badge-sostenible">♻️ Sostenible</span>' : ''}
                    </div>
                </div>
                <h3>${producto.nombre}</h3>
                <p class="producto-descripcion">${producto.descripcion}</p>
                <div class="producto-info">
                    <span class="categoria">📂 ${producto.categoria}</span>
                    <span class="precio">💰 ${producto.precio}€</span>
                </div>
                <div class="producto-actions mt-1">
                    <button class="btn btn-primary btn-ver-producto" data-producto-id="${producto.id}">
                        👁️ Ver Producto
                    </button>
                    <button class="btn btn-success btn-comprar" data-producto-id="${producto.id}">
                        🛒 Comprar
                    </button>
                </div>
            </div>
        `).join('');
        container.innerHTML = productosHTML;
        this.initializeProductoActions();
    }
    initializeProductoActions() {
        const botonesVer = document.querySelectorAll('.btn-ver-producto');
        const botonesComprar = document.querySelectorAll('.btn-comprar');
        botonesVer.forEach(boton => {
            boton.addEventListener('click', (e) => {
                const productoId = e.target.getAttribute('data-producto-id');
                this.verProducto(productoId);
            });
        });
        botonesComprar.forEach(boton => {
            boton.addEventListener('click', (e) => {
                const productoId = e.target.getAttribute('data-producto-id');
                this.comprarProducto(productoId);
            });
        });
    }
    verProducto(productoId) {
        const producto = this.productos.find(p => p.id == productoId);
        if (producto) {
            alert(`Ver detalles de: ${producto.nombre}\n\n${producto.descripcion}\n\nPrecio: ${producto.precio}€\nOrigen: ${producto.origen}`);
        }
    }
    comprarProducto(productoId) {
        const producto = this.productos.find(p => p.id == productoId);
        if (producto) {
            alert(`¡Producto agregado al carrito!\n\n${producto.nombre} - ${producto.precio}€\n\n¡Gracias por apoyar la economía local canaria!`);
        }
    }
}
// Exportar el componente
window.ProductsComponent = ProductsComponent;
