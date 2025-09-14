// Cart Service - Economía Circular Canarias
class CartService {
    constructor() {
        this.items = [];
        this.total = 0;
        this.loadFromStorage();
    }

    // Cargar carrito desde localStorage
    loadFromStorage() {
        try {
            const savedCart = localStorage.getItem('canarias_cart');
            if (savedCart) {
                const cartData = JSON.parse(savedCart);
                this.items = cartData.items || [];
                this.total = cartData.total || 0;
                this.calculateTotal();
            }
        } catch (error) {
            console.error('Error cargando carrito desde localStorage:', error);
            this.items = [];
            this.total = 0;
        }
    }

    // Guardar carrito en localStorage
    saveToStorage() {
        try {
            const cartData = {
                items: this.items,
                total: this.total
            };
            localStorage.setItem('canarias_cart', JSON.stringify(cartData));
        } catch (error) {
            console.error('Error guardando carrito en localStorage:', error);
        }
    }

    // Agregar producto al carrito
    addItem(product, quantity = 1) {
        // Verificar si el producto ya existe en el carrito
        const existingItem = this.items.find(item => item.id === product.id);
        
        if (existingItem) {
            // Si existe, incrementar la cantidad
            existingItem.quantity += quantity;
        } else {
            // Si no existe, agregar nuevo item
            this.items.push({
                id: product.id,
                name: product.name || product.title,
                price: parseFloat(product.price) || 0,
                image: product.image || '/assets/img/default-product.jpg',
                quantity: quantity,
                category: product.category || 'General'
            });
        }

        this.calculateTotal();
        this.saveToStorage();
        this.dispatchCartEvent('item-added', { product, quantity });
        
        return true;
    }

    // Quitar producto del carrito
    removeItem(productId) {
        const normalizedId = parseInt(productId);
        const itemIndex = this.items.findIndex(item => parseInt(item.id) === normalizedId);
        
        if (itemIndex > -1) {
            const removedItem = this.items.splice(itemIndex, 1)[0];
            this.calculateTotal();
            this.saveToStorage();
            this.dispatchCartEvent('item-removed', { item: removedItem });
            return true;
        }
        
        console.warn('CartService: Producto no encontrado para eliminar:', productId);
        return false;
    }

    // Actualizar cantidad de un producto
    updateQuantity(productId, newQuantity) {
        // Normalizar productId a número
        const normalizedId = parseInt(productId);
        const item = this.items.find(item => parseInt(item.id) === normalizedId);
        
        if (item) {
            if (newQuantity <= 0) {
                return this.removeItem(productId);
            } else {
                item.quantity = parseInt(newQuantity);
                this.calculateTotal();
                this.saveToStorage();
                this.dispatchCartEvent('quantity-updated', { productId: normalizedId, newQuantity: item.quantity });
                return true;
            }
        }
        
        console.warn('CartService: Producto no encontrado para actualizar cantidad:', productId);
        return false;
    }

    // Vaciar carrito
    clearCart() {
        this.items = [];
        this.total = 0;
        this.saveToStorage();
        this.dispatchCartEvent('cart-cleared');
    }

    // Calcular total del carrito
    calculateTotal() {
        this.total = this.items.reduce((sum, item) => {
            return sum + (item.price * item.quantity);
        }, 0);
        
        this.dispatchCartEvent('total-updated', { total: this.total });
    }

    // Obtener número total de items
    getItemCount() {
        return this.items.reduce((sum, item) => sum + item.quantity, 0);
    }

    // Obtener todos los items
    getItems() {
        return [...this.items];
    }

    // Obtener total
    getTotal() {
        return this.total;
    }

    // Verificar si un producto está en el carrito
    hasItem(productId) {
        const normalizedId = parseInt(productId);
        return this.items.some(item => parseInt(item.id) === normalizedId);
    }

    // Obtener cantidad de un producto específico
    getItemQuantity(productId) {
        const normalizedId = parseInt(productId);
        const item = this.items.find(item => parseInt(item.id) === normalizedId);
        return item ? item.quantity : 0;
    }

    // Disparar eventos del carrito
    dispatchCartEvent(eventType, detail = {}) {
        const event = new CustomEvent('cart-updated', {
            detail: {
                type: eventType,
                itemCount: this.getItemCount(),
                total: this.total,
                items: this.items,
                ...detail
            }
        });
        
        document.dispatchEvent(event);
        
        // También disparar evento específico
        const specificEvent = new CustomEvent(`cart-${eventType}`, {
            detail: {
                itemCount: this.getItemCount(),
                total: this.total,
                items: this.items,
                ...detail
            }
        });
        
        document.dispatchEvent(specificEvent);
    }

    // Obtener resumen del carrito
    getCartSummary() {
        return {
            itemCount: this.getItemCount(),
            total: this.total,
            items: this.items.length,
            isEmpty: this.items.length === 0
        };
    }

    // Formatear precio
    formatPrice(price) {
        return new Intl.NumberFormat('es-ES', {
            style: 'currency',
            currency: 'EUR'
        }).format(price);
    }
}

// Crear instancia global del servicio de carrito
window.cartService = new CartService();

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CartService;
}