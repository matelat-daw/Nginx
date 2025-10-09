// Payment Service - Sistema de Pagos para Economía Circular Canarias
class PaymentService {
    constructor() {
        this.apiEndpoint = '/api/payments';
        this.supportedMethods = {
            card: { name: 'Tarjeta de Crédito/Débito', icon: '💳', enabled: true },
            bizum: { name: 'Bizum', icon: '📱', enabled: true },
            transfer: { name: 'Transferencia Bancaria', icon: '🏦', enabled: true },
            paypal: { name: 'PayPal', icon: '🅿️', enabled: true },
            cash_on_delivery: { name: 'Contrarreembolso', icon: '💵', enabled: true }
        };
    }

    /**
     * Obtener métodos de pago disponibles
     */
    getAvailablePaymentMethods() {
        return Object.entries(this.supportedMethods)
            .filter(([_, method]) => method.enabled)
            .map(([key, method]) => ({
                id: key,
                name: method.name,
                icon: method.icon
            }));
    }

    /**
     * Iniciar proceso de pago
     * @param {Object} paymentData - Datos del pago
     * @returns {Promise<Object>} Resultado del pago
     */
    async initiatePayment(paymentData) {
        const { method, amount, orderId, customerInfo } = paymentData;

        console.log('💰 PaymentService: Iniciando pago', { method, amount, orderId });

        try {
            switch (method) {
                case 'card':
                    return await this.processCardPayment(paymentData);
                case 'bizum':
                    return await this.processBizumPayment(paymentData);
                case 'transfer':
                    return await this.processBankTransferPayment(paymentData);
                case 'paypal':
                    return await this.processPayPalPayment(paymentData);
                case 'cash_on_delivery':
                    return await this.processCashOnDelivery(paymentData);
                default:
                    throw new Error('Método de pago no soportado');
            }
        } catch (error) {
            console.error('❌ Error procesando pago:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    /**
     * Procesar pago con tarjeta (Stripe)
     */
    async processCardPayment(paymentData) {
        console.log('💳 Procesando pago con tarjeta...');
        
        // TODO: Implementar integración real con backend
        // Por ahora, simular el pago exitoso
        
        try {
            // Simular delay de red
            await new Promise(resolve => setTimeout(resolve, 1500));
            
            // Por ahora retornar éxito simulado
            // Cuando el backend esté listo, descomentar el código de fetch
            /*
            const response = await fetch(`${this.apiEndpoint}/card/create-intent`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify(paymentData)
            });

            if (!response.ok) {
                throw new Error(`Error del servidor: ${response.status}`);
            }

            const result = await response.json();
            
            if (result.success) {
                return {
                    success: true,
                    requiresAction: true,
                    clientSecret: result.clientSecret,
                    message: 'Proceda con el pago con tarjeta'
                };
            }

            return result;
            */
            
            // SIMULACIÓN (remover cuando backend esté listo)
            return {
                success: true,
                transactionId: 'CARD-' + Date.now(),
                message: 'Pago con tarjeta procesado correctamente (DEMO)',
                paymentMethod: 'card'
            };
        } catch (error) {
            console.error('❌ Error en processCardPayment:', error);
            throw new Error('Error al procesar pago con tarjeta: ' + error.message);
        }
    }

    /**
     * Procesar pago con Bizum
     */
    async processBizumPayment(paymentData) {
        console.log('📱 Procesando pago con Bizum...');
        
        try {
            // Simular delay de red
            await new Promise(resolve => setTimeout(resolve, 1200));
            
            // SIMULACIÓN (remover cuando backend esté listo)
            /*
            const response = await fetch(`${this.apiEndpoint}/bizum/initiate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify(paymentData)
            });

            if (!response.ok) {
                throw new Error(`Error del servidor: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                return {
                    success: true,
                    requiresAction: true,
                    bizumPhone: result.bizumPhone,
                    bizumCode: result.bizumCode,
                    message: `Envíe ${paymentData.amount}€ al número ${result.bizumPhone} con el código ${result.bizumCode}`
                };
            }

            return result;
            */
            
            return {
                success: true,
                transactionId: 'BIZUM-' + Date.now(),
                bizumPhone: '600123456',
                bizumCode: 'EC' + Math.random().toString(36).substr(2, 6).toUpperCase(),
                message: 'Pago con Bizum procesado correctamente (DEMO)',
                paymentMethod: 'bizum'
            };
        } catch (error) {
            console.error('❌ Error en processBizumPayment:', error);
            throw new Error('Error al procesar pago con Bizum: ' + error.message);
        }
    }

    /**
     * Procesar transferencia bancaria
     */
    async processBankTransferPayment(paymentData) {
        console.log('🏦 Procesando transferencia bancaria...');
        
        try {
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            // SIMULACIÓN
            /*
            const response = await fetch(`${this.apiEndpoint}/transfer/initiate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify(paymentData)
            });

            if (!response.ok) {
                throw new Error(`Error del servidor: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                return {
                    success: true,
                    requiresAction: true,
                    bankDetails: result.bankDetails,
                    reference: result.reference,
                    message: 'Realice la transferencia con los datos proporcionados'
                };
            }

            return result;
            */
            
            return {
                success: true,
                transactionId: 'TRANSFER-' + Date.now(),
                bankDetails: {
                    iban: 'ES00 0000 0000 0000 0000 0000',
                    bankName: 'Banco de Canarias',
                    accountHolder: 'Economía Circular Canarias'
                },
                reference: 'EC-' + paymentData.orderId,
                message: 'Transferencia bancaria registrada correctamente (DEMO)',
                paymentMethod: 'transfer'
            };
        } catch (error) {
            console.error('❌ Error en processBankTransferPayment:', error);
            throw new Error('Error al procesar transferencia: ' + error.message);
        }
    }

    /**
     * Procesar pago con PayPal
     */
    async processPayPalPayment(paymentData) {
        console.log('🅿️ Procesando pago con PayPal...');
        
        try {
            await new Promise(resolve => setTimeout(resolve, 1300));
            
            // SIMULACIÓN
            /*
            const response = await fetch(`${this.apiEndpoint}/paypal/create-order`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify(paymentData)
            });

            if (!response.ok) {
                throw new Error(`Error del servidor: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                return {
                    success: true,
                    requiresAction: true,
                    redirectUrl: result.approvalUrl,
                    orderId: result.paypalOrderId,
                    message: 'Será redirigido a PayPal para completar el pago'
                };
            }

            return result;
            */
            
            return {
                success: true,
                transactionId: 'PAYPAL-' + Date.now(),
                message: 'Pago con PayPal procesado correctamente (DEMO)',
                paymentMethod: 'paypal'
            };
        } catch (error) {
            console.error('❌ Error en processPayPalPayment:', error);
            throw new Error('Error al procesar pago con PayPal: ' + error.message);
        }
    }

    /**
     * Procesar contrarreembolso
     */
    async processCashOnDelivery(paymentData) {
        console.log('💵 Procesando contrarreembolso...');
        
        try {
            await new Promise(resolve => setTimeout(resolve, 800));
            
            // SIMULACIÓN
            /*
            const response = await fetch(`${this.apiEndpoint}/cash-on-delivery/create`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify(paymentData)
            });

            if (!response.ok) {
                throw new Error(`Error del servidor: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                return {
                    success: true,
                    requiresAction: false,
                    message: 'Pedido confirmado. Pagará en efectivo al recibir el pedido.',
                    additionalFee: result.additionalFee || 0
                };
            }

            return result;
            */
            
            return {
                success: true,
                transactionId: 'COD-' + Date.now(),
                requiresAction: false,
                message: 'Pedido confirmado. Pagará en efectivo al recibir el pedido (DEMO)',
                additionalFee: 2.50,
                paymentMethod: 'cash_on_delivery'
            };
        } catch (error) {
            console.error('❌ Error en processCashOnDelivery:', error);
            throw new Error('Error al procesar contrarreembolso: ' + error.message);
        }
    }

    /**
     * Verificar estado del pago
     */
    async checkPaymentStatus(paymentId) {
        const response = await fetch(`${this.apiEndpoint}/status/${paymentId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include'
        });

        return await response.json();
    }

    /**
     * Confirmar pago (webhook o confirmación manual)
     */
    async confirmPayment(paymentId, confirmationData) {
        const response = await fetch(`${this.apiEndpoint}/confirm/${paymentId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(confirmationData)
        });

        return await response.json();
    }

    /**
     * Cancelar pago
     */
    async cancelPayment(paymentId) {
        const response = await fetch(`${this.apiEndpoint}/cancel/${paymentId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include'
        });

        return await response.json();
    }

    /**
     * Calcular comisión según método de pago
     */
    calculateFee(amount, method) {
        const fees = {
            card: 0.014 * amount + 0.25, // 1.4% + 0.25€ (Stripe)
            bizum: 0, // Gratis para particulares
            transfer: 0, // Gratis
            paypal: 0.034 * amount + 0.35, // 3.4% + 0.35€
            cash_on_delivery: 3.00 // Tarifa fija de 3€
        };

        return fees[method] || 0;
    }

    /**
     * Obtener total con comisiones
     */
    getTotalWithFees(subtotal, method) {
        const fee = this.calculateFee(subtotal, method);
        return {
            subtotal: subtotal,
            fee: fee,
            total: subtotal + fee
        };
    }
}

// Hacer disponible globalmente
window.PaymentService = PaymentService;
window.paymentService = new PaymentService();

console.log('✅ Payment Service cargado correctamente');
