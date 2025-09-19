// Cart Modal Component - Economía Circular Canarias
class CartModal {
    constructor() {
        this.isVisible = false;
        this.cartService = window.cartService;
        this.template = null;
        this.cssLoaded = false;
        
        // Cargar CSS inmediatamente
        this.loadCSS();
    }

    // Cargar template HTML
    async loadTemplate() {
        if (this.template) return this.template;
        
        try {
            const response = await fetch('/app/components/cart/cart-modal.component.html');
            this.template = await response.text();
            return this.template;
        } catch (error) {
            console.error('Error cargando template del carrito:', error);
            return this.getFallbackTemplate();
        }
    }

    // Template de respaldo mínimo
    getFallbackTemplate() {
        return `
            <div id="cartModal" class="cart-modal-overlay">
                <div class="cart-modal">
                    <div class="cart-modal-header">
                        <h2>🛒 Carrito</h2>
                        <button class="cart-modal-close" id="cartModalClose">✕</button>
                    </div>
                    <div class="cart-modal-content" id="cartModalContent"></div>
                    <div class="cart-modal-footer" id="cartModalFooter" style="display: none;"></div>
                </div>
            </div>
        `;
    }

    // Obtener contenido de template por ID
    getTemplateContent(templateId) {
        const template = document.getElementById(templateId);
        if (template && template.content) {
            const clone = template.content.cloneNode(true);
            return clone;
        }
        return null;
    }

    // Mostrar modal del carrito
    async show() {
        await this.render();
        this.isVisible = true;
    }

    // Ocultar modal del carrito
    hide() {
        const modal = document.getElementById('cartModal');
        if (modal) {
            modal.remove();
        }
        this.isVisible = false;
    }

    // Renderizar modal
    async render() {
        // Cargar CSS si no está cargado
        if (!this.cssLoaded) {
            this.loadCSS();
        }

        // Remover modal existente si existe
        this.hide();

        const cartItems = this.cartService.getItems();
        const itemCount = this.cartService.getItemCount();
        const total = this.cartService.getTotal();

        // Usar siempre el método directo para mejor control del tema
        const modalHTML = this.buildModalHTML(cartItems, itemCount, total);
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // Aplicar tema inmediatamente
        this.applyTheme();
        this.initializeEvents();
    }

    // Construir HTML del modal directamente
    buildModalHTML(cartItems, itemCount, total) {
        const isDarkMode = document.body.classList.contains('dark-mode');
        const modalClass = isDarkMode ? 'cart-modal-overlay dark-mode' : 'cart-modal-overlay';
        const contentHTML = cartItems.length === 0 ? this.renderEmptyCart() : this.renderCartItems(cartItems);
        const footerHTML = cartItems.length > 0 ? this.renderCartFooter(total, itemCount) : '';

        return `
            <div id="cartModal" class="${modalClass}">
                <div class="cart-modal ${isDarkMode ? 'dark-mode' : ''}">
                    <div class="cart-modal-header">
                        <h2>🛒 Carrito de Compras</h2>
                        <button class="cart-modal-close" id="cartModalClose">✕</button>
                    </div>
                    
                    <div class="cart-modal-content">
                        ${contentHTML}
                    </div>
                    
                    ${footerHTML ? `<div class="cart-modal-footer">${footerHTML}</div>` : ''}
                </div>
            </div>
        `;
    }

    // Aplicar tema actual al modal (simplificado)
    applyTheme() {
        const modal = document.getElementById('cartModal');
        const isDarkMode = document.body.classList.contains('dark-mode');
        
        if (modal && isDarkMode) {
            modal.classList.add('dark-mode');
            const cartModalDiv = modal.querySelector('.cart-modal');
            if (cartModalDiv) {
                cartModalDiv.classList.add('dark-mode');
            }
        }
    }

    // Aplicar estilos de modo oscuro directamente
    applyDarkModeStyles(modal) {
        // Aplicar estilos al modal principal
        const cartModal = modal.querySelector('.cart-modal');
        if (cartModal) {
            cartModal.style.background = '#1e2832';
            cartModal.style.color = '#ecf0f1';
            cartModal.style.boxShadow = '0 10px 40px rgba(0, 0, 0, 0.6)';
        }

        // Aplicar estilos al contenido
        const content = modal.querySelector('.cart-modal-content');
        if (content) {
            content.style.background = '#1e2832';
            content.style.color = '#ecf0f1';
        }

        // Aplicar estilos a los items
        const items = modal.querySelectorAll('.cart-item');
        items.forEach(item => {
            item.style.background = '#2c3e50';
            item.style.border = '1px solid #4a5f7a';
            item.style.color = '#ecf0f1';
        });

        // Aplicar estilos al footer
        const footer = modal.querySelector('.cart-modal-footer');
        if (footer) {
            footer.style.background = '#2c3e50';
            footer.style.borderTop = '1px solid #4a5f7a';
            footer.style.color = '#ecf0f1';
        }

        // Aplicar estilos a botones
        const buttons = modal.querySelectorAll('.btn-outline-secondary');
        buttons.forEach(btn => {
            btn.style.background = 'transparent';
            btn.style.border = '2px solid #5d6d7e';
            btn.style.color = '#bdc3c7';
        });
    }

    // Asegurar que los templates estén cargados
    async ensureTemplatesLoaded() {
        if (!document.getElementById('cartModalTemplate')) {
            const templateHTML = await this.loadTemplate();
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = templateHTML;
            document.body.appendChild(tempDiv);
        }
    }

    // Renderizar contenido del modal
    renderContent(cartItems, itemCount, total) {
        const contentContainer = document.getElementById('cartModalContent');
        const footerContainer = document.getElementById('cartModalFooter');

        if (!contentContainer) return;

        if (cartItems.length === 0) {
            contentContainer.innerHTML = this.renderEmptyCart();
            if (footerContainer) {
                footerContainer.style.display = 'none';
            }
        } else {
            contentContainer.innerHTML = this.renderCartItems(cartItems);
            if (footerContainer) {
                footerContainer.innerHTML = this.renderCartFooter(total, itemCount);
                footerContainer.style.display = 'block';
            }
        }

        // Aplicar tema después de renderizar el contenido
        this.applyTheme();
    }

    // Renderizar carrito vacío
    renderEmptyCart() {
        return `
            <div class="cart-empty">
                <div class="cart-empty-icon">🛒</div>
                <h3>Tu carrito está vacío</h3>
                <p>¡Explora nuestros productos y encuentra algo increíble!</p>
                <button class="btn btn-primary" id="continueShopping">
                    🏪 Continuar Comprando
                </button>
            </div>
        `;
    }

    // Renderizar items del carrito
    renderCartItems(items) {
        return `
            <div class="cart-items">
                ${items.map(item => this.renderCartItem(item)).join('')}
            </div>
        `;
    }

    // Renderizar item individual del carrito  
    renderCartItem(item) {
        return `
            <div class="cart-item" data-product-id="${item.id}">
                <div class="cart-item-image">
                    <img src="${item.image || '/assets/img/default-product.svg'}" 
                         alt="${item.name}" 
                         class="cart-product-image"
                         loading="lazy"
                         onload="this.style.opacity=1;"
                         onerror="this.src='/assets/img/default-product.svg'; this.style.opacity=1;">
                </div>
                <div class="cart-item-details">
                    <h4 class="cart-item-name">${item.name}</h4>
                    <p class="cart-item-category">${item.category}</p>
                    <p class="cart-item-price">${this.cartService.formatPrice(item.price)}</p>
                </div>
                <div class="cart-item-quantity">
                    <button class="quantity-btn quantity-decrease" data-product-id="${item.id}">-</button>
                    <span class="quantity-display">${item.quantity}</span>
                    <button class="quantity-btn quantity-increase" data-product-id="${item.id}">+</button>
                </div>
                <div class="cart-item-total">
                    ${this.cartService.formatPrice(item.price * item.quantity)}
                </div>
                <button class="cart-item-remove" data-product-id="${item.id}" title="Eliminar producto">
                    🗑️
                </button>
            </div>
        `;
    }

    // Renderizar footer del carrito
    renderCartFooter(total, itemCount) {
        return `
            <div class="cart-summary">
                <div class="cart-summary-line">
                    <span>Total de productos:</span>
                    <span>${itemCount} ${itemCount === 1 ? 'artículo' : 'artículos'}</span>
                </div>
                <div class="cart-summary-line cart-total">
                    <span>Total a pagar:</span>
                    <span class="total-amount">${this.cartService.formatPrice(total)}</span>
                </div>
            </div>
            <div class="cart-actions">
                <button class="btn btn-outline-secondary" id="clearCart">
                    🗑️ Vaciar Carrito
                </button>
                <button class="btn btn-primary" id="proceedCheckout">
                    💳 Proceder al Pago
                </button>
            </div>
        `;
    }

    // Cargar CSS del componente
    loadCSS() {
        if (!document.getElementById('cart-modal-styles')) {
            const link = document.createElement('link');
            link.id = 'cart-modal-styles';
            link.rel = 'stylesheet';
            link.href = '/app/components/cart/cart-modal.component.css';
            document.head.appendChild(link);
            this.cssLoaded = true;
        }
    }

    // Inicializar eventos del modal
    initializeEvents() {
        // Cerrar modal
        const closeBtn = document.getElementById('cartModalClose');
        const modal = document.getElementById('cartModal');
        
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.hide());
        }

        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.hide();
                }
            });
        }

        // Continuar comprando
        const continueBtn = document.getElementById('continueShopping');
        if (continueBtn) {
            continueBtn.addEventListener('click', () => {
                this.hide();
                if (window.router) {
                    window.router.navigate('/products');
                }
            });
        }

        // Botones de cantidad
        const quantityBtns = document.querySelectorAll('.quantity-btn');
        quantityBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                try {
                    const productId = parseInt(e.target.getAttribute('data-product-id'));
                    const currentItem = this.cartService.getItems().find(item => item.id === productId);
                    
                    if (!currentItem) return;
                    
                    if (btn.classList.contains('quantity-increase')) {
                        this.cartService.updateQuantity(productId, currentItem.quantity + 1);
                    } else if (btn.classList.contains('quantity-decrease')) {
                        if (currentItem.quantity > 1) {
                            this.cartService.updateQuantity(productId, currentItem.quantity - 1);
                        }
                    }
                    
                    this.render();
                } catch (error) {
                    console.error('Error actualizando cantidad:', error);
                    this.showErrorNotification('Error al actualizar el carrito. Inténtalo de nuevo.');
                }
            });
        });

        // Botones de eliminar
        const removeBtns = document.querySelectorAll('.cart-item-remove');
        removeBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const productId = parseInt(e.target.getAttribute('data-product-id'));
                if (productId) {
                    this.confirmRemoveItem(productId);
                }
            });
        });

        // Vaciar carrito
        const clearBtn = document.getElementById('clearCart');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                this.confirmClearCart();
            });
        }

        // Proceder al checkout
        const checkoutBtn = document.getElementById('proceedCheckout');
        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', () => {
                this.proceedToCheckout();
            });
        }
    }

    // Proceder al checkout
    proceedToCheckout() {
        alert('Funcionalidad de checkout en desarrollo. ¡Pronto estará disponible!');
    }

    // Confirmar eliminación de item con modal personalizado
    confirmRemoveItem(productId) {
        // Remover modal existente si existe
        const existingModal = document.querySelector('.cart-confirm-modal');
        if (existingModal) {
            document.body.removeChild(existingModal);
        }

        const template = this.getTemplateContent('confirmRemoveTemplate');
        let confirmModal;
        
        if (template) {
            confirmModal = template.querySelector('.cart-confirm-modal');
        } else {
            // Fallback
            confirmModal = document.createElement('div');
            confirmModal.className = 'cart-confirm-modal';
            confirmModal.innerHTML = `
                <div class="cart-confirm-content">
                    <h3>🗑️ Eliminar Producto</h3>
                    <p>¿Estás seguro de que quieres eliminar este producto del carrito?</p>
                    <div class="cart-confirm-actions">
                        <button class="btn btn-secondary cart-confirm-cancel">Cancelar</button>
                        <button class="btn btn-danger cart-confirm-ok">Eliminar</button>
                    </div>
                </div>
            `;
        }

        document.body.appendChild(confirmModal);

        // Event listeners
        const cancelBtn = confirmModal.querySelector('.cart-confirm-cancel');
        const okBtn = confirmModal.querySelector('.cart-confirm-ok');

        const closeModal = () => {
            if (document.body.contains(confirmModal)) {
                document.body.removeChild(confirmModal);
            }
        };

        cancelBtn.addEventListener('click', closeModal);
        
        okBtn.addEventListener('click', () => {
            this.cartService.removeItem(productId);
            closeModal();
            this.render();
        });

        // Cerrar con click fuera
        confirmModal.addEventListener('click', (e) => {
            if (e.target === confirmModal) {
                closeModal();
            }
        });
    }

    // Confirmar vaciado del carrito con modal profesional
    confirmClearCart() {
        // Remover modal existente si existe
        const existingModal = document.querySelector('.cart-clear-modal');
        if (existingModal) {
            document.body.removeChild(existingModal);
        }

        const template = this.getTemplateContent('confirmClearTemplate');
        let confirmModal;
        
        if (template) {
            confirmModal = template.querySelector('.cart-clear-modal');
        } else {
            // Fallback
            confirmModal = document.createElement('div');
            confirmModal.className = 'cart-clear-modal';
            confirmModal.innerHTML = `
                <div class="cart-clear-content">
                    <h3>🗑️ Vaciar Carrito</h3>
                    <p>¿Estás seguro de que quieres eliminar <strong>todos los productos</strong> del carrito?</p>
                    <p class="warning-text">Esta acción no se puede deshacer.</p>
                    <div class="cart-clear-actions">
                        <button class="btn btn-secondary cart-clear-cancel">Cancelar</button>
                        <button class="btn btn-danger cart-clear-ok">Vaciar Carrito</button>
                    </div>
                </div>
            `;
        }

        document.body.appendChild(confirmModal);

        // Event listeners
        const cancelBtn = confirmModal.querySelector('.cart-clear-cancel');
        const okBtn = confirmModal.querySelector('.cart-clear-ok');

        const closeModal = () => {
            confirmModal.style.animation = 'modalFadeOut 0.2s ease-in forwards';
            setTimeout(() => {
                if (document.body.contains(confirmModal)) {
                    document.body.removeChild(confirmModal);
                }
            }, 200);
        };

        cancelBtn.addEventListener('click', closeModal);
        
        okBtn.addEventListener('click', () => {
            this.cartService.clearCart();
            closeModal();
            this.render();
            this.showNotification('🗑️ Carrito vaciado correctamente', 'success');
        });

        // Cerrar con click fuera del modal
        confirmModal.addEventListener('click', (e) => {
            if (e.target === confirmModal) {
                closeModal();
            }
        });

        // Cerrar con tecla Escape
        const escapeHandler = (e) => {
            if (e.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);
    }

    // Mostrar notificación de error
    showErrorNotification(message) {
        this.showNotification(message, 'error');
    }

    // Mostrar notificación genérica (éxito, error, info)
    showNotification(message, type = 'info') {
        const template = this.getTemplateContent('notificationTemplate');
        let notification;
        
        if (template) {
            notification = template.querySelector('.cart-notification');
            notification.className = `cart-notification cart-notification-${type}`;
            
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };
            
            notification.querySelector('.cart-notification-icon').textContent = icons[type] || icons.info;
            notification.querySelector('.cart-notification-text').textContent = message;
        } else {
            // Fallback
            notification = document.createElement('div');
            notification.className = `cart-notification cart-notification-${type}`;
            
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };
            
            notification.innerHTML = `
                <div class="cart-notification-content">
                    <span class="cart-notification-icon">${icons[type] || icons.info}</span>
                    <div class="cart-notification-text">${message}</div>
                    <button class="cart-notification-close">✕</button>
                </div>
            `;
        }

        document.body.appendChild(notification);

        const closeNotification = () => {
            if (notification.parentNode) {
                notification.style.animation = 'slideOutRight 0.3s ease forwards';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }
        };

        // Auto-cerrar después de 4 segundos
        setTimeout(closeNotification, 4000);

        const closeBtn = notification.querySelector('.cart-notification-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', closeNotification);
        }
    }
}

// Exportar componente
window.CartModal = CartModal;