// Main.js - Punto de entrada de la aplicación
// Economía Circular Canarias - Aplicación estilo Angular con JavaScript

// Función para manejar la pantalla de carga
function handleLoadingScreen() {
    // Remover pantalla de carga cuando la aplicación esté lista
    window.addEventListener('load', () => {
        setTimeout(() => {
            document.body.classList.add('app-loaded');
            setTimeout(() => {
                const loadingScreen = document.querySelector('.loading-screen');
                if (loadingScreen) {
                    loadingScreen.remove();
                }
            }, 500);
        }, 1000);
    });
}

// Función para verificar que todos los servicios estén cargados
async function waitForServices() {
    console.log('⏳ Esperando a que todos los servicios estén cargados...');
    
    const maxAttempts = 100; // 10 segundos
    let attempts = 0;
    
    while (attempts < maxAttempts) {
        // Verificar que AuthService esté disponible
        if (window.authService && typeof window.authService.register === 'function') {
            console.log('✅ Todos los servicios están listos');
            return true;
        }
        
        console.log(`⏳ Esperando servicios... intento ${attempts + 1}/${maxAttempts}`);
        await new Promise(resolve => setTimeout(resolve, 100));
        attempts++;
    }
    
    console.error('❌ Timeout esperando servicios');
    return false;
}

// Función principal que inicia la aplicación
async function bootstrapApplication() {
    console.log('🏝️ Iniciando Economía Circular Canarias...');
    
    // Esperar a que todos los servicios estén disponibles
    const servicesReady = await waitForServices();
    
    if (!servicesReady) {
        console.error('❌ No se pudieron cargar todos los servicios');
        // Mostrar mensaje de error al usuario
        const loadingText = document.querySelector('.loading-text');
        if (loadingText) {
            loadingText.textContent = 'Error cargando la aplicación. Recarga la página.';
            loadingText.style.color = '#dc3545';
        }
        return;
    }
    
    // Crear y inicializar la aplicación principal
    const app = new AppComponent();
    app.init();
    
    console.log('✅ Aplicación cargada correctamente');
}

// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', () => {
    bootstrapApplication();
    handleLoadingScreen();
});

// Manejo de errores globales
window.addEventListener('error', (e) => {
    console.error('❌ Error en la aplicación:', e.error);
});

// Registrar Service Worker (si está disponible)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        // En una aplicación real, aquí registrarías el service worker
        console.log('🔧 Service Worker disponible');
    });
}
// ...existing code...
