/**
 * =====================================================
 * COMPONENTE DEL CARRITO DE COMPRAS CON GESTIÓN DE RESERVAS
 * Maneja productos, cantidades, reservas temporales y checkout
 * =====================================================
 */
class ShoppingCart {
    constructor() {
        this.items = this.loadCart();
        this.apiBase = 'api';
        this.reservations = new Map(); // Cache de reservas activas
        this.reservationTimer = null; // Timer para auto-extender reservas
        this.init();
    }
    init() {
        this.updateCartBadge();
        this.bindEvents();
        this.startReservationMonitor();
        this.loadActiveReservations();
    }
    // ===== GESTIÓN DE LOCALSTORAGE =====
    loadCart() {
        var savedCart = localStorage.getItem('ecc_shopping_cart');
        return savedCart ? JSON.parse(savedCart) : [];
    }
    saveCart() {
        localStorage.setItem('ecc_shopping_cart', JSON.stringify(this.items));
        this.updateCartBadge();
    }
    // ===== GESTIÓN DE RESERVAS DE STOCK =====
    async reserveStock(productId, quantity) {
        try {
            var token = localStorage.getItem('ecc_token');
            if (!token) {
                throw new Error('Usuario no autenticado');
            }
            var response = await fetch(this.apiBase + '/stock-reservations.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify({
                    action: 'reserve',
                    product_id: productId,
                    quantity: quantity
                })
            });
            var result = await response.json();
            return result;
        } catch (error) {
            console.error('Error al reservar stock:', error);
            return { success: false, message: 'Error de conexión' };
        }
    }
    async checkAvailableStock(productId) {
        try {
            var token = localStorage.getItem('ecc_token');
            if (!token) {
                throw new Error('Usuario no autenticado');
            }
            var response = await fetch(this.apiBase + '/stock-reservations.php?action=check_stock&product_id=' + productId, {
                headers: {
                    'Authorization': 'Bearer ' + token
                }
            });
            var result = await response.json();
            return result;
        } catch (error) {
            console.error('Error al verificar stock:', error);
            return { success: false, message: 'Error de conexión' };
        }
    }
    async releaseReservations() {
        try {
            var token = localStorage.getItem('ecc_token');
            if (!token) return;
            var response = await fetch(this.apiBase + '/stock-reservations.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify({
                    action: 'release_all'
                })
            });
            var result = await response.json();
            return result;
        } catch (error) {
            console.error('Error al liberar reservas:', error);
            return { success: false };
        }
    }
    async loadActiveReservations() {
        try {
            var token = localStorage.getItem('ecc_token');
            if (!token) return;
            var response = await fetch(this.apiBase + '/stock-reservations.php?action=get_reservations', {
                headers: {
                    'Authorization': 'Bearer ' + token
                }
            });
            var result = await response.json();
            if (result.success) {
                this.reservations.clear();
                var self = this;
                result.reservations.forEach(function(reservation) {
                    self.reservations.set(reservation.product_id, reservation);
                });
                this.updateReservationDisplay();
            }
        } catch (error) {
            console.error('Error al cargar reservas:', error);
        }
    }
    async extendReservations() {
        try {
            var token = localStorage.getItem('ecc_token');
            if (!token) return;
            var response = await fetch(this.apiBase + '/stock-reservations.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify({
                    action: 'extend_all'
                })
            });
            var result = await response.json();
            if (result.success) {
                await this.loadActiveReservations();
                this.showNotification('Reservas extendidas por 30 minutos más', 'success');
            }
        } catch (error) {
            console.error('Error al extender reservas:', error);
        }
    }
    startReservationMonitor() {
        var self = this;
        this.reservationTimer = setInterval(function() {
            self.reservations.forEach(function(reservation, productId) {
                if (reservation.minutes_remaining <= 5 && reservation.minutes_remaining > 0) {
                    self.extendReservations();
                    return;
                }
            });
            self.updateReservationDisplay();
        }, 60000); // Cada minuto
    }
    updateReservationDisplay() {
        var cartItems = document.querySelectorAll('.cart-item');
        var self = this;
        cartItems.forEach(function(item) {
            var productId = parseInt(item.dataset.productId);
            var reservation = self.reservations.get(productId);
            var reservationInfo = item.querySelector('.reservation-info');
            if (reservation) {
                if (!reservationInfo) {
                    reservationInfo = document.createElement('div');
                    reservationInfo.className = 'reservation-info';
                    item.querySelector('.item-details').appendChild(reservationInfo);
                }
                var minutes = reservation.minutes_remaining;
                var timeClass = minutes <= 5 ? 'warning' : 'info';
                reservationInfo.innerHTML = 
                    '<small class="reservation-time ' + timeClass + '">' +
                    '<i class="fas fa-clock"></i>' +
                    'Reservado por ' + minutes + ' min' +
                    '</small>';
            } else if (reservationInfo) {
                reservationInfo.remove();
            }
        });
    }
    // ===== GESTIÓN DEL CARRITO =====
    async addItem(product, quantity) {
        quantity = quantity || 1;
        try {
            // Verificar stock disponible
            var stockCheck = await this.checkAvailableStock(product.id);
            if (!stockCheck.success) {
                this.showNotification('Error al verificar stock disponible', 'error');
                return false;
            }
            var availableStock = stockCheck.stock_data.stock_available;
            var existingItem = this.items.find(function(item) { return item.id === product.id; });
            var currentQuantity = existingItem ? existingItem.quantity : 0;
            var newTotalQuantity = currentQuantity + quantity;
            // Verificar disponibilidad
            if (newTotalQuantity > availableStock) {
                var maxCanAdd = Math.max(0, availableStock - currentQuantity);
                if (maxCanAdd === 0) {
                    this.showNotification('No hay más stock disponible para este producto', 'warning');
                    return false;
                } else {
                    this.showNotification('Solo se pueden agregar ' + maxCanAdd + ' unidades más', 'warning');
                    quantity = maxCanAdd;
                }
            }
            // Reservar stock
            var reserveResult = await this.reserveStock(product.id, quantity);
            if (!reserveResult.success) {
                this.showNotification(reserveResult.message || 'Error al reservar stock', 'error');
                return false;
            }
            // Agregar al carrito local
            if (existingItem) {
                existingItem.quantity += quantity;
            } else {
                this.items.push({
                    id: product.id,
                    name: product.name,
                    price: parseFloat(product.price),
                    image: product.main_image || '/api/uploads/products/default.jpg',
                    seller_id: product.seller_id,
                    seller_name: product.seller_name,
                    stock_quantity: product.stock_quantity,
                    stock_available: availableStock - quantity,
                    quantity: quantity,
                    condition: product.condition,
                    location_island: product.location_island
                });
            }
            this.saveCart();
            this.showNotification(product.name + ' agregado al carrito', 'success');
            return true;
        } catch (error) {
            console.error('Error al agregar producto:', error);
            this.showNotification('Error al agregar producto al carrito', 'error');
            return false;
        }
    }
    async removeItem(productId) {
        try {
            // Liberar reserva en el servidor
            var token = localStorage.getItem('ecc_token');
            if (token) {
                await fetch(this.apiBase + '/stock-reservations.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + token
                    },
                    body: JSON.stringify({
                        action: 'release_product',
                        product_id: productId
                    })
                });
            }
            // Remover del carrito local
            this.items = this.items.filter(function(item) { return item.id !== productId; });
            this.reservations.delete(productId);
            this.saveCart();
            this.renderCart();
        } catch (error) {
            console.error('Error al remover producto:', error);
            // Continuar con remoción local
            this.items = this.items.filter(function(item) { return item.id !== productId; });
            this.saveCart();
            this.renderCart();
        }
    }
    updateQuantity(productId, quantity) {
        if (quantity <= 0) {
            this.removeItem(productId);
            return;
        }
        var item = this.items.find(function(i) { return i.id === productId; });
        if (item) {
            if (quantity > item.stock_quantity) {
                quantity = item.stock_quantity;
                this.showNotification('Stock máximo disponible: ' + item.stock_quantity, 'warning');
            }
            item.quantity = quantity;
            this.saveCart();
            this.renderCart();
        }
    }
    getTotal() {
        return this.items.reduce(function(total, item) {
            return total + (item.price * item.quantity);
        }, 0);
    }
    getItemCount() {
        return this.items.reduce(function(count, item) {
            return count + item.quantity;
        }, 0);
    }
    updateCartBadge() {
        var badge = document.querySelector('.cart-badge');
        var count = this.getItemCount();
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'inline';
            } else {
                badge.style.display = 'none';
            }
        }
    }
    // ===== RENDERIZADO =====
    renderCart() {
        var cartContainer = document.getElementById('cartItems');
        var cartTotal = document.getElementById('cartTotal');
        if (!cartContainer) return;
        if (this.items.length === 0) {
            cartContainer.innerHTML = 
                '<div class="empty-cart">' +
                '<h3>Tu carrito está vacío</h3>' +
                '<p>¡Explora nuestros productos y encuentra algo increíble!</p>' +
                '<a href="productos.html" class="btn-primary">Ver Productos</a>' +
                '</div>';
            if (cartTotal) {
                cartTotal.textContent = '€0.00';
            }
            return;
        }
        var self = this;
        cartContainer.innerHTML = this.items.map(function(item) {
            return (
                '<div class="cart-item" data-product-id="' + item.id + '">' +
                '<div class="item-image">' +
                '<img src="' + item.image + '" alt="' + item.name + '">' +
                '</div>' +
                '<div class="item-details">' +
                '<h4>' + item.name + '</h4>' +
                '<p class="seller">Vendedor: ' + item.seller_name + '</p>' +
                '<p class="condition">' + self.getConditionLabel(item.condition) + '</p>' +
                '<p class="location">📍 ' + item.location_island + '</p>' +
                '<p class="price">€' + item.price.toFixed(2) + ' / unidad</p>' +
                '</div>' +
                '<div class="item-controls">' +
                '<div class="quantity-controls">' +
                '<button onclick="cart.updateQuantity(' + item.id + ', ' + (item.quantity - 1) + ')" class="qty-btn">-</button>' +
                '<input type="number" value="' + item.quantity + '" min="1" max="' + item.stock_quantity + '" ' +
                'onchange="cart.updateQuantity(' + item.id + ', parseInt(this.value))" class="qty-input">' +
                '<button onclick="cart.updateQuantity(' + item.id + ', ' + (item.quantity + 1) + ')" class="qty-btn">+</button>' +
                '</div>' +
                '<p class="item-total">€' + (item.price * item.quantity).toFixed(2) + '</p>' +
                '<button onclick="cart.removeItem(' + item.id + ')" class="remove-btn">🗑️</button>' +
                '</div>' +
                '</div>'
            );
        }).join('');
        if (cartTotal) {
            cartTotal.textContent = '€' + this.getTotal().toFixed(2);
        }
        this.updateCartBadge();
        this.updateReservationDisplay();
    }
    getConditionLabel(condition) {
        var labels = {
            'new': 'Nuevo',
            'like_new': 'Como nuevo',
            'good': 'Buen estado',
            'fair': 'Estado regular',
            'poor': 'Necesita reparación'
        };
        return labels[condition] || condition;
    }
    // ===== CHECKOUT =====
    async proceedToCheckout() {
        if (this.items.length === 0) {
            this.showNotification('Tu carrito está vacío', 'error');
            return;
        }
        var token = localStorage.getItem('ecc_auth_token');
        if (!token) {
            this.showNotification('Debes iniciar sesión para comprar', 'error');
            window.location.href = 'login.html';
            return;
        }
        this.showCheckoutModal();
    }
    showCheckoutModal() {
        var modal = document.createElement('div');
        modal.className = 'checkout-modal';
        var self = this;
        modal.innerHTML = 
            '<div class="modal-overlay" onclick="this.parentElement.remove()"></div>' +
            '<div class="modal-content">' +
            '<div class="modal-header">' +
            '<h2>🛒 Finalizar Compra</h2>' +
            '<button onclick="this.closest(\'.checkout-modal\').remove()" class="close-btn">×</button>' +
            '</div>' +
            '<div class="modal-body">' +
            '<!-- Resumen del pedido -->' +
            '<div class="order-summary">' +
            '<h3>📦 Resumen del Pedido</h3>' +
            '<div class="summary-items">' +
            this.items.map(function(item) {
                return (
                    '<div class="summary-item">' +
                    '<span>' + item.name + ' x' + item.quantity + '</span>' +
                    '<span>€' + (item.price * item.quantity).toFixed(2) + '</span>' +
                    '</div>'
                );
            }).join('') +
            '</div>' +
            '<div class="summary-total">' +
            '<strong>Total: €' + this.getTotal().toFixed(2) + '</strong>' +
            '</div>' +
            '</div>' +
            '<!-- Información de entrega -->' +
            '<div class="delivery-info">' +
            '<h3>🚚 Información de Entrega</h3>' +
            '<div class="form-group">' +
            '<label>Método de entrega:</label>' +
            '<select id="deliveryMethod" onchange="this.onchange && this.onchange()">' +
            '<option value="pickup">Recogida en persona (Gratis)</option>' +
            '<option value="shipping">Envío a domicilio (+€3.50)</option>' +
            '</select>' +
            '</div>' +
            '<div id="shippingAddress" class="shipping-section" style="display: none;">' +
            '<div class="form-group">' +
            '<label>Dirección de envío:</label>' +
            '<textarea id="shippingAddressText" placeholder="Calle, número, piso..."></textarea>' +
            '</div>' +
            '<div class="form-row">' +
            '<div class="form-group">' +
            '<label>Isla:</label>' +
            '<select id="shippingIsland">' +
            '<option value="Gran Canaria">Gran Canaria</option>' +
            '<option value="Tenerife">Tenerife</option>' +
            '<option value="Lanzarote">Lanzarote</option>' +
            '<option value="Fuerteventura">Fuerteventura</option>' +
            '<option value="La Palma">La Palma</option>' +
            '<option value="La Gomera">La Gomera</option>' +
            '<option value="El Hierro">El Hierro</option>' +
            '</select>' +
            '</div>' +
            '<div class="form-group">' +
            '<label>Ciudad:</label>' +
            '<input type="text" id="shippingCity" placeholder="Ciudad">' +
            '</div>' +
            '</div>' +
            '<div class="form-row">' +
            '<div class="form-group">' +
            '<label>Código postal:</label>' +
            '<input type="text" id="shippingPostal" placeholder="35000">' +
            '</div>' +
            '<div class="form-group">' +
            '<label>Teléfono:</label>' +
            '<input type="tel" id="shippingPhone" placeholder="600 000 000">' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '<!-- Método de pago -->' +
            '<div class="payment-info">' +
            '<h3>💳 Método de Pago</h3>' +
            '<div class="payment-methods">' +
            '<label class="payment-option">' +
            '<input type="radio" name="paymentMethod" value="bizum" checked>' +
            '<div class="payment-card">' +
            '<div class="payment-icon">📱</div>' +
            '<div class="payment-details">' +
            '<h4>Bizum</h4>' +
            '<p>Pago instantáneo y seguro</p>' +
            '</div>' +
            '</div>' +
            '</label>' +
            '<label class="payment-option">' +
            '<input type="radio" name="paymentMethod" value="card">' +
            '<div class="payment-card">' +
            '<div class="payment-icon">💳</div>' +
            '<div class="payment-details">' +
            '<h4>Tarjeta</h4>' +
            '<p>Débito o crédito</p>' +
            '</div>' +
            '</div>' +
            '</label>' +
            '<label class="payment-option">' +
            '<input type="radio" name="paymentMethod" value="transfer">' +
            '<div class="payment-card">' +
            '<div class="payment-icon">🏦</div>' +
            '<div class="payment-details">' +
            '<h4>Transferencia</h4>' +
            '<p>Transferencia bancaria</p>' +
            '</div>' +
            '</div>' +
            '</label>' +
            '<label class="payment-option">' +
            '<input type="radio" name="paymentMethod" value="cash">' +
            '<div class="payment-card">' +
            '<div class="payment-icon">💵</div>' +
            '<div class="payment-details">' +
            '<h4>Contrareembolso</h4>' +
            '<p>Pago en efectivo al recibir</p>' +
            '</div>' +
            '</div>' +
            '</label>' +
            '</div>' +
            '</div>' +
            '<!-- Notas adicionales -->' +
            '<div class="order-notes">' +
            '<h3>📝 Notas del Pedido (Opcional)</h3>' +
            '<textarea id="orderNotes" placeholder="Instrucciones especiales, horarios de entrega, etc."></textarea>' +
            '</div>' +
            '</div>' +
            '<div class="modal-footer">' +
            '<button onclick="this.closest(\'.checkout-modal\').remove()" class="btn-secondary">Cancelar</button>' +
            '<button onclick="cart.processOrder()" class="btn-primary">🛒 Confirmar Pedido (€' + this.getTotal().toFixed(2) + ')</button>' +
            '</div>' +
            '</div>';
        document.body.appendChild(modal);
        // Configurar eventos del modal
        var deliveryMethod = document.getElementById('deliveryMethod');
        var shippingSection = document.getElementById('shippingAddress');
        deliveryMethod.onchange = function() {
            if (this.value === 'shipping') {
                shippingSection.style.display = 'block';
                var newTotal = self.getTotal() + 3.50;
                document.querySelector('.btn-primary').textContent = '🛒 Confirmar Pedido (€' + newTotal.toFixed(2) + ')';
            } else {
                shippingSection.style.display = 'none';
                document.querySelector('.btn-primary').textContent = '🛒 Confirmar Pedido (€' + self.getTotal().toFixed(2) + ')';
            }
        };
    }
    async processOrder() {
        var modal = document.querySelector('.checkout-modal');
        var deliveryMethod = document.getElementById('deliveryMethod').value;
        var paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
        var orderNotes = document.getElementById('orderNotes').value;
        // Validar datos de envío
        if (deliveryMethod === 'shipping') {
            var address = document.getElementById('shippingAddressText').value;
            var city = document.getElementById('shippingCity').value;
            var phone = document.getElementById('shippingPhone').value;
            if (!address || !city || !phone) {
                this.showNotification('Por favor, completa la información de envío', 'error');
                return;
            }
        }
        try {
            var submitBtn = document.querySelector('.modal-footer .btn-primary');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Procesando...';
            var token = localStorage.getItem('ecc_auth_token');
            // PASO 1: Confirmar reservas de stock
            var cartItems = this.items.map(function(item) {
                return {
                    product_id: item.id,
                    quantity: item.quantity
                };
            });
            var confirmReservationsResponse = await fetch(this.apiBase + '/stock-reservations.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify({
                    action: 'confirm_cart',
                    cart_items: cartItems
                })
            });
            var reservationResult = await confirmReservationsResponse.json();
            if (!reservationResult.success) {
                throw new Error(reservationResult.message || 'Error al confirmar reservas de stock');
            }
            // PASO 2: Crear la orden
            var orderData = {
                items: this.items,
                delivery_method: deliveryMethod,
                payment_method: paymentMethod,
                buyer_notes: orderNotes,
                shipping_cost: deliveryMethod === 'shipping' ? 3.50 : 0,
                stock_confirmed: true
            };
            if (deliveryMethod === 'shipping') {
                orderData.shipping_address = document.getElementById('shippingAddressText').value;
                orderData.shipping_island = document.getElementById('shippingIsland').value;
                orderData.shipping_city = document.getElementById('shippingCity').value;
                orderData.shipping_postal_code = document.getElementById('shippingPostal').value;
                orderData.shipping_phone = document.getElementById('shippingPhone').value;
            }
            var response = await fetch(this.apiBase + '/orders/create-simple.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify(orderData)
            });
            var result = await response.json();
            if (result.success) {
                // Limpiar carrito y reservas
                this.items = [];
                this.reservations.clear();
                this.saveCart();
                this.renderCart();
                if (this.reservationTimer) {
                    clearInterval(this.reservationTimer);
                    this.reservationTimer = null;
                }
                modal.remove();
                this.showOrderConfirmation(result.order);
                this.showNotification('¡Pedido confirmado! El stock ha sido asignado definitivamente.', 'success');
            } else {
                throw new Error(result.message || 'Error al procesar el pedido');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Error al procesar el pedido: ' + error.message, 'error');
            var submitBtn = document.querySelector('.modal-footer .btn-primary');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = '🛒 Confirmar Pedido (€' + this.getTotal().toFixed(2) + ')';
            }
        }
    }
    showOrderConfirmation(order) {
        var modal = document.createElement('div');
        modal.className = 'confirmation-modal';
        modal.innerHTML = 
            '<div class="modal-overlay" onclick="this.parentElement.remove()"></div>' +
            '<div class="modal-content">' +
            '<div class="confirmation-content">' +
            '<div class="success-icon">✅</div>' +
            '<h2>¡Pedido Confirmado!</h2>' +
            '<p>Tu pedido <strong>#' + order.order_number + '</strong> ha sido creado exitosamente.</p>' +
            '<div class="order-details">' +
            '<h3>Detalles del Pedido:</h3>' +
            '<p><strong>Número:</strong> ' + order.order_number + '</p>' +
            '<p><strong>Total:</strong> €' + order.total_amount + '</p>' +
            '<p><strong>Método de pago:</strong> ' + this.getPaymentMethodLabel(order.payment_method) + '</p>' +
            '<p><strong>Estado:</strong> ' + order.status + '</p>' +
            '</div>' +
            '<div class="next-steps">' +
            '<h3>Próximos pasos:</h3>' +
            this.getPaymentInstructions(order.payment_method) +
            '</div>' +
            '</div>' +
            '<div class="modal-footer">' +
            '<button onclick="this.closest(\'.confirmation-modal\').remove()" class="btn-secondary">Cerrar</button>' +
            '<button onclick="window.location.href=\'#/orders\'" class="btn-primary">Ver Mis Pedidos</button>' +
            '</div>' +
            '</div>';
        document.body.appendChild(modal);
    }
    getPaymentMethodLabel(method) {
        var labels = {
            'bizum': '📱 Bizum',
            'card': '💳 Tarjeta',
            'transfer': '🏦 Transferencia',
            'cash': '💵 Contrareembolso'
        };
        return labels[method] || method;
    }
    getPaymentInstructions(method) {
        var instructions = {
            'bizum': 
                '<p>1. Te contactaremos por WhatsApp para coordinar el pago por Bizum</p>' +
                '<p>2. Una vez confirmado el pago, procederemos con la entrega</p>',
            'card': 
                '<p>1. Te enviaremos un enlace de pago seguro</p>' +
                '<p>2. Completa el pago con tu tarjeta</p>' +
                '<p>3. Recibirás confirmación por email</p>',
            'transfer': 
                '<p>1. Te enviaremos los datos bancarios por email</p>' +
                '<p>2. Realiza la transferencia bancaria</p>' +
                '<p>3. Envíanos el comprobante por WhatsApp</p>',
            'cash': 
                '<p>1. Te contactaremos para coordinar la entrega</p>' +
                '<p>2. Prepara el importe exacto en efectivo</p>' +
                '<p>3. El pago se realizará al momento de la entrega</p>'
        };
        return instructions[method] || '<p>Te contactaremos pronto con los detalles.</p>';
    }
    // ===== UTILIDADES =====
    clearCart() {
        this.items = [];
        this.reservations.clear();
        this.saveCart();
        this.renderCart();
        this.releaseReservations();
        if (this.reservationTimer) {
            clearInterval(this.reservationTimer);
            this.reservationTimer = null;
        }
    }
    showNotification(message, type) {
        type = type || 'info';
        var notification = document.createElement('div');
        notification.className = 'notification notification-' + type;
        notification.innerHTML = 
            '<span>' + message + '</span>' +
            '<button onclick="this.parentElement.remove()">×</button>';
        document.body.appendChild(notification);
        setTimeout(function() {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }
    bindEvents() {
        var self = this;
        // Liberar reservas al cerrar la ventana
        window.addEventListener('beforeunload', function() {
            self.releaseReservations();
        });
        // Actualizar reservaciones periódicamente
        setInterval(function() {
            self.loadActiveReservations();
        }, 300000); // Cada 5 minutos
    }
}
// Inicializar carrito globalmente
var cart = new ShoppingCart();
