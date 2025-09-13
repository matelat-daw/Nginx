// Main.js - Punto de entrada de la aplicación
// Economía Circular Canarias - Aplicación estilo Angular con JavaScript
// Inicializar tema desde localStorage
function initializeTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.body.setAttribute('data-theme', savedTheme);
}
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
// Función para verificar que todos los servicios estén cargados y listos
async function waitForServices() {
    const maxAttempts = 100; // 10 segundos
    let attempts = 0;
    
    while (attempts < maxAttempts) {
        // Crear AuthService si no existe pero la clase está disponible
        if (!window.authService && window.AuthService) {
            window.authService = new window.AuthService();
            // No llamar init() aquí, se llamará después si es necesario
        }
        
        // Verificar que AuthService esté disponible y completamente inicializado
        if (window.authService && 
            typeof window.authService.register === 'function' &&
            typeof window.authService.isAuthenticated === 'function') {
            
            // Esperar un poco más para asegurar que la inicialización async haya terminado
            await new Promise(resolve => setTimeout(resolve, 200));
            return true;
        }
        await new Promise(resolve => setTimeout(resolve, 100));
        attempts++;
    }
    console.error('❌ Timeout esperando servicios');
    return false;
}
// Función principal que inicia la aplicación
async function bootstrapApplication() {
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
    await app.init();
    
    // Después de inicializar la aplicación, verificar estado de autenticación una vez más
    setTimeout(() => {
        if (window.authService && window.headerComponent) {
            window.headerComponent.refreshAuthState();
        }
    }, 1000);
}
// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', () => {
    // Inicializar tema antes que nada
    initializeTheme();
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
    });
}
// ...existing code...
