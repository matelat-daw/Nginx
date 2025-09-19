// Configuración de la aplicación Economía Circular Canarias
window.AppConfig = {
    // Configuración del API
    api: {
        // Cambiar a false cuando el backend esté listo
        useMockMode: false, // Activado para probar el backend PHP
        // URLs del API - usando rutas relativas para el mismo servidor
        baseUrl: '', // Vacío para usar rutas relativas desde la misma raíz
        // Rutas del API desde la raíz del servidor (http://localhost:8080)
        endpoints: {
            // Autenticación
            register: '/api/auth/register.php',
            login: '/api/auth/login.php',
            logout: '/api/auth/logout.php',
            validate: '/api/auth/validate.php',
            confirmEmail: '/api/auth/confirm-email.php',
            requestPasswordReset: '/api/auth/request-password-reset.php',
            resetPassword: '/api/auth/reset-password.php',
            // Productos
            createProduct: '/api/products/create.php',
            listProducts: '/api/products/list.php',
            getProduct: '/api/products/get.php',
            updateProduct: '/api/products/update.php',
            deleteProduct: '/api/products/delete.php',
            getCategories: '/api/products/categories.php',
            // Pedidos
            createOrder: '/api/orders/create.php',
            getOrder: '/api/orders/get.php',
            myOrders: '/api/orders/my-orders.php',
            updateOrderStatus: '/api/orders/update-status.php',
            // Pruebas
            testProducts: '/api/test-products.php',
            testOrders: '/api/test-orders.php'
        }
    },
    // Configuración de la aplicación
    app: {
        name: 'Economía Circular Canarias',
        version: '1.0.0',
        environment: 'development'
    },
    // Configuración de debugging
    debug: {
        enableLogs: true,
        enableAuthServiceLogs: true
    }
};
