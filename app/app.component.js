// App Component Principal - Economía Circular Canarias
class AppComponent {
    constructor() {
        this.headerComponent = new HeaderComponent();
        this.navComponent = new NavComponent();
        this.footerComponent = new FooterComponent();
        this.template = null;
    }
    async loadTemplate() {
        if (this.template) return this.template;
        const headerHtml = await this.headerComponent.render();
        const navHtml = await this.navComponent.render();
        const footerHtml = await this.footerComponent.render();
        this.template = `
            <div class="app-container">
                ${headerHtml}
                ${navHtml}
                <main>
                    <div id="router-outlet" class="router-outlet">
                        <!-- El contenido de las rutas se cargará aquí -->
                    </div>
                </main>
                ${footerHtml}
            </div>
        `;
        return this.template;
    }
    async render() {
        return await this.loadTemplate();
    }
    async init() {
        // Renderizar la aplicación en el DOM
        const appRoot = document.getElementById('app-root');
        if (appRoot) {
            const template = await this.render();
            appRoot.innerHTML = template;
            
            // Guardar referencia global del header
            window.headerComponent = this.headerComponent;
            
            // Ejecutar afterRender de componentes
            this.headerComponent.afterRender();
            this.navComponent.afterRender();
            this.footerComponent.afterRender();
            
            // PRIMERO: Inicializar y verificar sesión
            if (window.authService) {
                // Si ya existe AuthService, verificar si hay sesión activa
                const isAuthenticated = window.authService.isAuthenticated();
                
                if (!isAuthenticated) {
                    // Intentar inicializar para verificar cookies
                    await window.authService.init();
                }
                
                // Refrescar el estado del header
                this.headerComponent.refreshAuthState();
                
            } else {
                // Crear AuthService si no existe
                if (window.AuthService) {
                    window.authService = new window.AuthService();
                    await window.authService.init();
                    this.headerComponent.refreshAuthState();
                }
            }
            
            // DESPUÉS: Inicializar el router (ahora que el authService ya está listo)
            window.appRouter = new AppRouter();
        }
    }
}
// Exportar el componente principal
window.AppComponent = AppComponent;
