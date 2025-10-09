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
        console.log('👋 [CART MODAL] Ocultando modal del carrito...');
        const modal = document.getElementById('cartModal');
        if (modal) {
            modal.remove();
        }
        this.isVisible = false;
        
        // Solo restaurar overflow si no hay otros modales activos
        // El payment modal manejará su propio overflow
        console.log('👋 [CART MODAL] Modal ocultado');
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
    async proceedToCheckout() {
        console.log('🚀 [CHECKOUT] Iniciando proceso de checkout...');
        
        try {
            // Verificar que hay items en el carrito
            const cartItems = this.cartService.getItems();
            console.log('🛒 [CHECKOUT] Items en carrito:', cartItems.length);
            
            if (cartItems.length === 0) {
                console.warn('⚠️ [CHECKOUT] Carrito vacío');
                if (window.notificationModal) {
                    window.notificationModal.showWarning(
                        'El carrito está vacío. Agrega productos antes de proceder al pago.',
                        'Navega a la sección de Productos para agregar artículos a tu carrito.'
                    );
                } else {
                    alert('El carrito está vacío');
                }
                return;
            }

            // Verificar que el usuario está autenticado
            console.log('🔐 [CHECKOUT] Verificando autenticación...');
            console.log('🔐 [CHECKOUT] authService disponible:', !!window.authService);
            console.log('🔐 [CHECKOUT] Usuario autenticado:', window.authService?.isAuthenticated());
            
            if (!window.authService || !window.authService.isAuthenticated()) {
                console.log('❌ [CHECKOUT] Usuario NO autenticado, mostrando modal de login');
                // Cerrar el carrito primero
                this.hide();
                
                // Mostrar modal de confirmación para ir al login
                this.showLoginRequiredModal();
                return;
            }

            console.log('✅ [CHECKOUT] Usuario autenticado, continuando...');

            // Obtener información del usuario
            const currentUser = window.authService.getCurrentUser();
            console.log('👤 [CHECKOUT] Datos del usuario:', currentUser);
            
            if (!currentUser) {
                console.error('❌ [CHECKOUT] No se pudo obtener información del usuario');
                if (window.notificationModal) {
                    window.notificationModal.showError(
                        'Error al obtener información del usuario. Por favor, recarga la página e inténtalo de nuevo.'
                    );
                } else {
                    alert('Error al obtener información del usuario');
                }
                return;
            }

            // Preparar datos del pedido
            const orderData = {
                orderId: 'PEDIDO-' + Date.now(),
                items: cartItems.map(item => ({
                    product_id: item.id || item.product_id || null,  // ID del producto (puede ser null)
                    id: item.id || item.product_id || null,  // Mantener retrocompatibilidad
                    name: item.name || item.nombre || 'Producto sin nombre',
                    quantity: item.quantity || 1,
                    price: parseFloat(item.price || item.precio || 0)
                })),
                subtotal: this.cartService.getTotal(),
                customerInfo: {
                    name: `${currentUser.first_name || ''} ${currentUser.last_name || ''}`.trim(),
                    email: currentUser.email,
                    phone: currentUser.phone || '',
                    userId: currentUser.id
                }
            };

            console.log('📦 [CHECKOUT] Datos del pedido preparados:', orderData);
            console.log('📦 [CHECKOUT] Items originales del carrito:', cartItems);

            // Cerrar modal del carrito
            console.log('👋 [CHECKOUT] Cerrando modal del carrito...');
            this.hide();

            // Abrir modal de pago
            console.log('💳 [CHECKOUT] Verificando PaymentModal...');
            console.log('💳 [CHECKOUT] PaymentModal disponible:', !!window.PaymentModal);
            
            if (window.PaymentModal) {
                console.log('💳 [CHECKOUT] Creando instancia de PaymentModal...');
                const paymentModal = new PaymentModal(orderData);
                console.log('💳 [CHECKOUT] Instancia creada:', paymentModal);
                
                console.log('💳 [CHECKOUT] Mostrando modal de pago...');
                await paymentModal.show();
                console.log('✅ [CHECKOUT] Modal de pago mostrado correctamente');

                // Escuchar evento de pago completado
                window.addEventListener('paymentCompleted', (event) => {
                    console.log('✅ Pago completado:', event.detail);
                    
                    // Vaciar carrito después del pago exitoso
                    this.cartService.clearCart();
                    
                    // Mostrar notificación de éxito
                    if (window.notificationModal) {
                        window.notificationModal.show({
                            type: 'success',
                            title: '🎉 ¡Pedido Realizado!',
                            message: '¡Pedido realizado con éxito! Recibirá un email de confirmación en breve.',
                            details: `Número de pedido: ${orderData.orderId}`
                        });
                    } else {
                        this.showSuccessNotification('¡Pedido realizado con éxito! Recibirá un email de confirmación.');
                    }
                    
                    // Redirigir a página de pedidos
                    setTimeout(() => {
                        if (window.appRouter) {
                            window.appRouter.navigate('/orders');
                        }
                    }, 3000);
                }, { once: true }); // Solo escuchar una vez
            } else {
                console.error('❌ [CHECKOUT] PaymentModal NO está disponible');
                if (window.notificationModal) {
                    window.notificationModal.showError(
                        'Sistema de pagos no disponible',
                        'Por favor, recargue la página e inténtelo de nuevo.'
                    );
                } else {
                    alert('Error: Sistema de pagos no disponible. Por favor, recargue la página.');
                }
            }
        } catch (error) {
            console.error('💥 [CHECKOUT] Error en proceedToCheckout:', error);
            console.error('💥 [CHECKOUT] Stack trace:', error.stack);
            if (window.notificationModal) {
                window.notificationModal.showError(
                    'Error al procesar el pedido',
                    'Ha ocurrido un error inesperado. Por favor, inténtelo de nuevo.'
                );
            } else {
                alert('Error al procesar el pedido. Por favor, inténtelo de nuevo.');
            }
        }
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

    // Mostrar notificación de éxito
    showSuccessNotification(message) {
        this.showNotification(message, 'success');
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

    // Mostrar modal de confirmación para ir al login
    showLoginRequiredModal() {
        // Remover modal existente si existe
        const existingModal = document.querySelector('.login-required-modal');
        if (existingModal) {
            document.body.removeChild(existingModal);
        }

        // Crear modal personalizado
        const modal = document.createElement('div');
        modal.className = 'login-required-modal';
        modal.innerHTML = `
            <div class="login-required-content">
                <div class="login-required-header">
                    <span class="login-required-icon">🔐</span>
                    <h3>Autenticación Requerida</h3>
                </div>
                <div class="login-required-body">
                    <p class="login-required-message">Para proceder con el pago, necesitas iniciar sesión en tu cuenta.</p>
                    <p class="login-required-info">💡 Tus productos quedarán guardados en el carrito</p>
                </div>
                <div class="login-required-actions">
                    <button class="btn btn-secondary login-required-cancel">Cancelar</button>
                    <button class="btn btn-success login-required-register">✨ Registrarte</button>
                    <button class="btn btn-primary login-required-ok">🔐 Iniciar Sesión</button>
                </div>
            </div>
        `;

        // Agregar estilos si no existen
        if (!document.getElementById('loginRequiredModalStyles')) {
            const styles = document.createElement('style');
            styles.id = 'loginRequiredModalStyles';
            styles.textContent = `
                .login-required-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.65);
                    backdrop-filter: blur(2px);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                    animation: modalFadeIn 0.2s ease-out;
                }
                .login-required-content {
                    background: #ffffff;
                    border-radius: 12px;
                    max-width: 500px;
                    width: 90%;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                    animation: modalSlideIn 0.3s ease-out;
                }
                .login-required-header {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 24px 24px 16px;
                    border-bottom: 2px solid #e2e8f0;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    border-radius: 12px 12px 0 0;
                }
                .login-required-icon {
                    font-size: 32px;
                    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
                }
                .login-required-header h3 {
                    margin: 0;
                    font-size: 22px;
                    color: #ffffff;
                    font-weight: 700;
                    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
                }
                .login-required-body {
                    padding: 24px;
                    background: #f8fafc;
                }
                .login-required-message {
                    margin: 0 0 16px 0;
                    color: #1e293b;
                    line-height: 1.6;
                    font-size: 15px;
                    font-weight: 500;
                }
                .login-required-body p {
                    margin: 0 0 12px 0;
                    color: #475569;
                    line-height: 1.5;
                }
                .login-required-info {
                    background: linear-gradient(135deg, #dbeafe 0%, #e0f2fe 100%);
                    padding: 12px 16px;
                    border-radius: 8px;
                    border-left: 4px solid #3b82f6;
                    color: #1e40af;
                    font-size: 14px;
                    font-weight: 500;
                    margin: 0;
                }
                .login-required-actions {
                    display: flex;
                    gap: 10px;
                    padding: 20px 24px;
                    justify-content: flex-end;
                    background: #ffffff;
                    border-radius: 0 0 12px 12px;
                }
                .login-required-actions .btn {
                    padding: 12px 24px;
                    border: none;
                    border-radius: 8px;
                    font-size: 14px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                .login-required-cancel {
                    background: #e2e8f0;
                    color: #475569;
                }
                .login-required-cancel:hover {
                    background: #cbd5e1;
                    transform: translateY(-1px);
                    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
                }
                .login-required-register {
                    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                    color: #ffffff;
                }
                .login-required-register:hover {
                    background: linear-gradient(135deg, #059669 0%, #047857 100%);
                    transform: translateY(-1px);
                    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
                }
                .login-required-ok {
                    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
                    color: #ffffff;
                }
                .login-required-ok:hover {
                    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
                    transform: translateY(-1px);
                    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
                }
                @keyframes modalFadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes modalSlideIn {
                    from {
                        opacity: 0;
                        transform: translateY(-20px) scale(0.95);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0) scale(1);
                    }
                }
                @keyframes modalFadeOut {
                    from { opacity: 1; }
                    to { opacity: 0; }
                }
            `;
            document.head.appendChild(styles);
        }

        document.body.appendChild(modal);

        // Event listeners
        const cancelBtn = modal.querySelector('.login-required-cancel');
        const registerBtn = modal.querySelector('.login-required-register');
        const okBtn = modal.querySelector('.login-required-ok');

        const closeModal = () => {
            modal.style.animation = 'modalFadeOut 0.2s ease-in forwards';
            setTimeout(() => {
                if (document.body.contains(modal)) {
                    document.body.removeChild(modal);
                }
            }, 200);
        };

        cancelBtn.addEventListener('click', closeModal);

        registerBtn.addEventListener('click', () => {
            console.log('🎯 Usuario va a registrarse - Carrito se mantendrá');
            closeModal();
            setTimeout(() => {
                if (window.appRouter) {
                    // El carrito ya está guardado en localStorage por CartService
                    window.appRouter.navigate('/register');
                }
            }, 250);
        });

        okBtn.addEventListener('click', () => {
            console.log('🔐 Usuario va a iniciar sesión - Carrito se mantendrá');
            closeModal();
            setTimeout(() => {
                if (window.appRouter) {
                    // El carrito ya está guardado en localStorage por CartService
                    window.appRouter.navigate('/login');
                }
            }, 250);
        });

        // Cerrar con click fuera del modal
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
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
}

// Exportar componente
window.CartModal = CartModal;