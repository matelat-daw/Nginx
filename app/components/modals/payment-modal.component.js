// Payment Modal Component
class PaymentModal {
    constructor(orderData) {
        this.orderData = orderData;
        this.selectedMethod = null;
        this.stripe = null; // Se inicializará si se usa Stripe
        this.cardElement = null;
    }

    async show() {
        console.log('💳 [PAYMENT MODAL] Iniciando show()...');
        
        try {
            // Cargar template
            console.log('💳 [PAYMENT MODAL] Cargando template HTML...');
            const response = await fetch('/app/components/modals/payment-modal.component.html');
            
            if (!response.ok) {
                throw new Error(`Error al cargar template: ${response.status} ${response.statusText}`);
            }
            
            const template = await response.text();
            console.log('💳 [PAYMENT MODAL] Template cargado, longitud:', template.length);

            // Inyectar en DOM si no existe
            if (!document.getElementById('paymentModal')) {
                console.log('💳 [PAYMENT MODAL] Inyectando template en DOM...');
                document.body.insertAdjacentHTML('beforeend', template);
            } else {
                console.log('💳 [PAYMENT MODAL] Modal ya existe en DOM');
            }

            // Cargar estilos
            console.log('💳 [PAYMENT MODAL] Cargando estilos...');
            this.loadStyles();

            // Mostrar modal
            console.log('💳 [PAYMENT MODAL] Mostrando modal...');
            const modal = document.getElementById('paymentModal');
            
            if (!modal) {
                throw new Error('No se pudo encontrar el elemento paymentModal en el DOM');
            }
            
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';

            // Inicializar contenido
            console.log('💳 [PAYMENT MODAL] Inicializando contenido...');
            this.populateSummary();
            this.populatePaymentMethods();
            this.setupEventListeners();
            
            console.log('✅ [PAYMENT MODAL] Modal mostrado correctamente');
            return Promise.resolve();
        } catch (error) {
            console.error('❌ [PAYMENT MODAL] Error en show():', error);
            console.error('❌ [PAYMENT MODAL] Stack:', error.stack);
            
            // Mostrar error al usuario
            if (window.notificationModal) {
                window.notificationModal.showError(
                    'Error al cargar el sistema de pagos',
                    error.message
                );
            }
            
            return Promise.reject(error);
        }
    }

    loadStyles() {
        if (!document.getElementById('payment-modal-styles')) {
            const link = document.createElement('link');
            link.id = 'payment-modal-styles';
            link.rel = 'stylesheet';
            link.href = '/app/components/modals/payment-modal.component.css';
            document.head.appendChild(link);
        }
    }

    populateSummary() {
        console.log('📊 [PAYMENT MODAL] Poblando resumen del pedido...');
        console.log('📊 [PAYMENT MODAL] orderData completo:', this.orderData);
        
        const { items, subtotal } = this.orderData;
        console.log('📊 [PAYMENT MODAL] Items extraídos:', items);
        console.log('📊 [PAYMENT MODAL] Subtotal:', subtotal);
        
        // Verificar que items existe y es un array
        if (!items || !Array.isArray(items)) {
            console.error('❌ [PAYMENT MODAL] Items no es un array válido:', items);
            return;
        }
        
        // Llenar items
        const itemsContainer = document.getElementById('paymentSummaryItems');
        
        if (!itemsContainer) {
            console.error('❌ [PAYMENT MODAL] No se encontró el contenedor paymentSummaryItems');
            return;
        }
        
        itemsContainer.innerHTML = items.map((item, index) => {
            console.log(`📊 [PAYMENT MODAL] Procesando item ${index}:`, item);
            const itemName = item.name || 'Producto sin nombre';
            const itemPrice = parseFloat(item.price) || 0;
            const itemQuantity = parseInt(item.quantity) || 1;
            
            return `
                <div class="summary-item">
                    <span>${itemQuantity}x ${itemName}</span>
                    <span>${itemPrice.toFixed(2)}€</span>
                </div>
            `;
        }).join('');

        console.log('✅ [PAYMENT MODAL] Items renderizados en el DOM');

        // Mostrar subtotal
        const subtotalElement = document.getElementById('paymentSubtotal');
        const totalElement = document.getElementById('paymentTotal');
        
        if (subtotalElement && totalElement) {
            const subtotalValue = parseFloat(subtotal) || 0;
            subtotalElement.textContent = `${subtotalValue.toFixed(2)}€`;
            totalElement.textContent = `${subtotalValue.toFixed(2)}€`;
            console.log('✅ [PAYMENT MODAL] Subtotal y total actualizados:', subtotalValue);
        } else {
            console.error('❌ [PAYMENT MODAL] No se encontraron elementos de subtotal/total');
        }
    }

    populatePaymentMethods() {
        const methods = window.paymentService.getAvailablePaymentMethods();
        const container = document.getElementById('paymentMethodList');

        container.innerHTML = methods.map(method => `
            <div class="payment-method-item" data-method="${method.id}">
                <div class="method-icon">${method.icon}</div>
                <div class="method-info">
                    <div class="method-name">${method.name}</div>
                    <div class="method-fee" data-method="${method.id}"></div>
                </div>
                <div class="method-radio">
                    <input type="radio" name="paymentMethod" value="${method.id}" id="method-${method.id}">
                </div>
            </div>
        `).join('');

        // Event listeners para selección
        container.querySelectorAll('.payment-method-item').forEach(item => {
            item.addEventListener('click', () => {
                const methodId = item.dataset.method;
                this.selectPaymentMethod(methodId);
            });
        });
    }

    selectPaymentMethod(methodId) {
        this.selectedMethod = methodId;

        // Actualizar UI
        document.querySelectorAll('.payment-method-item').forEach(item => {
            item.classList.remove('selected');
        });
        document.querySelector(`[data-method="${methodId}"]`)?.classList.add('selected');
        document.getElementById(`method-${methodId}`).checked = true;

        // Calcular y mostrar comisiones
        this.updateTotals();

        // Mostrar formulario correspondiente
        this.showPaymentForm(methodId);

        // Habilitar botón de pagar
        document.getElementById('confirmPaymentBtn').disabled = false;
    }

    updateTotals() {
        const { subtotal } = this.orderData;
        const totals = window.paymentService.getTotalWithFees(subtotal, this.selectedMethod);

        document.getElementById('paymentSubtotal').textContent = `${totals.subtotal.toFixed(2)}€`;
        
        const feeRow = document.getElementById('paymentFeeRow');
        if (totals.fee > 0) {
            feeRow.style.display = 'flex';
            document.getElementById('paymentFee').textContent = `${totals.fee.toFixed(2)}€`;
        } else {
            feeRow.style.display = 'none';
        }

        document.getElementById('paymentTotal').textContent = `${totals.total.toFixed(2)}€`;
        document.getElementById('confirmPaymentAmount').textContent = `${totals.total.toFixed(2)}€`;

        // Actualizar descripción de comisiones
        document.querySelectorAll('.method-fee').forEach(el => {
            const method = el.dataset.method;
            const fee = window.paymentService.calculateFee(subtotal, method);
            el.textContent = fee > 0 ? `+${fee.toFixed(2)}€ gastos de gestión` : 'Sin gastos adicionales';
        });
    }

    showPaymentForm(methodId) {
        // Ocultar todos los formularios
        document.querySelectorAll('.payment-form').forEach(form => {
            form.style.display = 'none';
        });

        // Mostrar el formulario correspondiente
        const formId = `${methodId}PaymentForm`;
        const form = document.getElementById(formId);
        if (form) {
            form.style.display = 'block';

            // Inicializar según el método
            switch (methodId) {
                case 'card':
                    this.initializeStripe();
                    break;
                case 'paypal':
                    this.initializePayPal();
                    break;
            }
        }
    }

    async initializeStripe() {
        // Cargar Stripe.js si no está cargado
        if (!window.Stripe) {
            const script = document.createElement('script');
            script.src = 'https://js.stripe.com/v3/';
            script.onload = () => this.setupStripeElements();
            document.head.appendChild(script);
        } else {
            this.setupStripeElements();
        }
    }

    setupStripeElements() {
        // Inicializar Stripe (usar tu clave pública)
        this.stripe = Stripe('pk_test_YOUR_STRIPE_PUBLIC_KEY');
        const elements = this.stripe.elements();

        // Crear elemento de tarjeta
        this.cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#32325d',
                    '::placeholder': {
                        color: '#aab7c4'
                    }
                }
            }
        });

        // Montar en el DOM
        const container = document.getElementById('card-element');
        if (container && !container.hasChildNodes()) {
            this.cardElement.mount('#card-element');

            // Manejar errores
            this.cardElement.on('change', (event) => {
                const displayError = document.getElementById('card-errors');
                displayError.textContent = event.error ? event.error.message : '';
            });
        }
    }

    async initializePayPal() {
        // TEMPORALMENTE DESHABILITADO - PayPal requiere configuración del client-id real
        console.warn('⚠️ [PAYMENT MODAL] PayPal no configurado - usando modo DEMO');
        
        const container = document.getElementById('paypal-button-container');
        if (container) {
            container.innerHTML = `
                <div style="text-align: center; padding: 20px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px;">
                    <p style="color: #856404; margin: 0;">
                        ⚠️ PayPal no está configurado.<br>
                        Usa otro método de pago o configura el Client ID de PayPal.
                    </p>
                </div>
            `;
        }
        
        /* DESCOMENTAR CUANDO SE CONFIGURE PAYPAL
        if (!window.paypal) {
            const script = document.createElement('script');
            script.src = 'https://www.paypal.com/sdk/js?client-id=YOUR_PAYPAL_CLIENT_ID&currency=EUR';
            script.onload = () => this.setupPayPalButtons();
            document.head.appendChild(script);
        } else {
            this.setupPayPalButtons();
        }
        */
    }

    setupPayPalButtons() {
        const container = document.getElementById('paypal-button-container');
        if (!container.hasChildNodes()) {
            paypal.Buttons({
                createOrder: async () => {
                    // Crear orden en tu servidor
                    const result = await window.paymentService.processPayPalPayment({
                        amount: this.orderData.subtotal,
                        orderId: this.orderData.orderId,
                        customerInfo: this.orderData.customerInfo
                    });
                    return result.orderId;
                },
                onApprove: async (data) => {
                    // Capturar el pago
                    await this.handlePaymentSuccess(data);
                }
            }).render('#paypal-button-container');
        }
    }

    setupEventListeners() {
        // Cerrar modal
        document.getElementById('closePaymentModal')?.addEventListener('click', () => this.hide());
        document.getElementById('cancelPaymentBtn')?.addEventListener('click', () => this.hide());

        // Confirmar pago
        document.getElementById('confirmPaymentBtn')?.addEventListener('click', () => this.processPayment());
    }

    async processPayment() {
        if (!this.selectedMethod) {
            if (window.notificationModal) {
                window.notificationModal.showWarning(
                    'Por favor, seleccione un método de pago',
                    'Debe elegir cómo desea realizar el pago antes de continuar.'
                );
            } else {
                alert('Por favor, seleccione un método de pago');
            }
            return;
        }

        // Mostrar loading
        document.getElementById('paymentLoading').style.display = 'flex';
        document.getElementById('confirmPaymentBtn').disabled = true;

        try {
            const totals = window.paymentService.getTotalWithFees(this.orderData.subtotal, this.selectedMethod);
            
            const paymentData = {
                method: this.selectedMethod,
                amount: totals.total,
                orderId: this.orderData.orderId,
                customerInfo: this.orderData.customerInfo
            };

            const result = await window.paymentService.initiatePayment(paymentData);

            if (result.success) {
                await this.handlePaymentSuccess(result);
            } else {
                this.handlePaymentError(result.error);
            }
        } catch (error) {
            this.handlePaymentError(error.message);
        } finally {
            document.getElementById('paymentLoading').style.display = 'none';
            document.getElementById('confirmPaymentBtn').disabled = false;
        }
    }

    async handlePaymentSuccess(result) {
        console.log('✅ [PAYMENT MODAL] Pago exitoso, creando pedido en backend...');
        
        try {
            // Preparar datos completos del pedido para el backend
            const orderDataForBackend = {
                orderId: this.orderData.orderId,
                items: this.orderData.items,
                subtotal: this.orderData.subtotal,
                customerInfo: this.orderData.customerInfo,
                paymentMethod: this.selectedMethod,
                paymentResult: result
            };
            
            console.log('📦 [PAYMENT MODAL] Enviando pedido al backend:', orderDataForBackend);
            
            // Crear pedido en el backend y enviar email
            const response = await fetch('/api/orders/create-order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify(orderDataForBackend)
            });
            
            const backendResult = await response.json();
            console.log('📧 [PAYMENT MODAL] Respuesta del backend:', backendResult);
            
            if (!backendResult.success) {
                throw new Error(backendResult.message || 'Error al crear el pedido');
            }
            
            // Verificar si el email fue enviado
            const emailSent = backendResult.emailSent;
            const emailMessage = emailSent ? 
                '📧 Recibirás un email de confirmación en breve.' : 
                '⚠️ El email de confirmación no pudo ser enviado.';
            
            console.log(emailSent ? '✅ Email enviado correctamente' : '⚠️ Email no enviado');
            
            // Mostrar mensaje de éxito SIN cerrar el modal automáticamente
            const paymentContent = document.getElementById('paymentContent');
            if (paymentContent) {
                paymentContent.innerHTML = `
                    <div style="text-align: center; padding: 40px 20px;">
                        <div style="font-size: 64px; margin-bottom: 20px;">🎉</div>
                        <h2 style="color: #10b981; margin-bottom: 15px;">¡Pedido Realizado con Éxito!</h2>
                        <p style="color: #6b7280; margin-bottom: 10px;">Tu pedido <strong>#${this.orderData.orderId}</strong> ha sido procesado correctamente.</p>
                        <p style="color: #6b7280; margin-bottom: 25px;">${emailMessage}</p>
                        <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 15px; margin-bottom: 25px;">
                            <p style="color: #166534; margin: 0; font-size: 14px;">
                                💳 Pago: <strong>${this.getPaymentMethodLabel(this.selectedMethod)}</strong><br>
                                💰 Total: <strong>${this.orderData.subtotal.toFixed(2)}€</strong>
                            </p>
                        </div>
                        <button 
                            onclick="window.paymentModal.handleConfirmSuccess()"
                            style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); 
                                   color: white; 
                                   border: none; 
                                   padding: 15px 40px; 
                                   border-radius: 25px; 
                                   font-size: 16px; 
                                   font-weight: bold; 
                                   cursor: pointer; 
                                   box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                                   transition: transform 0.2s;">
                            ✅ Aceptar
                        </button>
                    </div>
                `;
            }
            
            // Guardar resultado para usarlo cuando se confirme
            this.lastOrderResult = {
                method: this.selectedMethod,
                paymentResult: result,
                orderResult: backendResult
            };
            
        } catch (error) {
            console.error('❌ [PAYMENT MODAL] Error creando pedido:', error);
            
            // Cerrar modal
            this.hide();
            
            // Mostrar error
            if (window.notificationModal) {
                window.notificationModal.showError(
                    'Error al procesar el pedido',
                    'El pago fue exitoso pero hubo un error al crear el pedido. Por favor, contacta con soporte. ' + error.message
                );
            } else {
                alert(`❌ Error: ${error.message}`);
            }
        }
    }

    handleConfirmSuccess() {
        console.log('✅ [PAYMENT MODAL] Usuario confirmó el pedido exitoso');
        
        // 1. Limpiar el carrito
        if (window.cartService) {
            window.cartService.clear();
            console.log('🛒 [PAYMENT MODAL] Carrito limpiado');
        }
        
        // 2. Emitir evento de pago completado
        if (this.lastOrderResult) {
            const event = new CustomEvent('paymentCompleted', {
                detail: this.lastOrderResult
            });
            window.dispatchEvent(event);
        }
        
        // 3. Cerrar modal
        this.hide();
        
        // 4. Mostrar notificación de éxito
        if (window.notificationModal) {
            window.notificationModal.show({
                type: 'success',
                title: '✅ Pedido Confirmado',
                message: 'Tu pedido ha sido procesado exitosamente. Revisa tu email para más detalles.'
            });
        }
        
        // 5. Redirigir a la página de inicio después de 2 segundos
        setTimeout(() => {
            console.log('🏠 [PAYMENT MODAL] Redirigiendo a inicio...');
            if (window.router) {
                window.router.navigate('/');
            } else {
                window.location.href = '/';
            }
        }, 2000);
    }

    getPaymentMethodLabel(method) {
        const labels = {
            'card': 'Tarjeta de Crédito/Débito 💳',
            'bizum': 'Bizum 📱',
            'transfer': 'Transferencia Bancaria 🏦',
            'paypal': 'PayPal 🅿️',
            'cash_on_delivery': 'Contrarreembolso 💵'
        };
        return labels[method] || method;
    }

    handlePaymentError(errorMessage) {
        if (window.notificationModal) {
            window.notificationModal.showError(
                'Error al procesar el pago',
                errorMessage || 'Ha ocurrido un error inesperado. Por favor, inténtelo de nuevo.'
            );
        } else {
            alert(`❌ Error al procesar el pago: ${errorMessage}`);
        }
    }

    hide() {
        console.log('👋 [PAYMENT MODAL] Ocultando modal de pago...');
        const modal = document.getElementById('paymentModal');
        if (modal) {
            // Animar salida
            modal.style.animation = 'fadeOut 0.2s ease-in forwards';
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = '';
                console.log('👋 [PAYMENT MODAL] Modal ocultado y overflow restaurado');
            }, 200);
        }
    }
}

window.PaymentModal = PaymentModal;
