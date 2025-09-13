// Email Confirmation Modal Component
class EmailConfirmationModal {
    constructor() {
        this.template = `
            <div class="modal-overlay" id="emailConfirmationModal" style="display: none;">
                <div class="modal-container">
                    <div class="modal-header">
                        <h2>📧 Confirma tu dirección de email</h2>
                        <button class="modal-close" id="closeEmailModal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="email-confirmation-content">
                            <div class="email-icon">
                                📬
                            </div>
                            <h3>¡Ya casi estamos listos!</h3>
                            <p>Tu cuenta está registrada, pero necesitas confirmar tu dirección de email para continuar.</p>
                            <div class="user-email">
                                <strong>Email enviado a:</strong>
                                <span id="userEmailDisplay"></span>
                            </div>
                            <div class="instructions">
                                <h4>Pasos a seguir:</h4>
                                <ol>
                                    <li>Revisa tu bandeja de entrada (y carpeta de spam)</li>
                                    <li>Busca el email de "Economía Circular Canarias"</li>
                                    <li>Haz clic en el enlace de confirmación</li>
                                    <li>Regresa aquí e intenta iniciar sesión nuevamente</li>
                                </ol>
                            </div>
                            <div class="modal-actions">
                                <button class="btn btn-primary" id="goToLoginBtn">
                                    🔐 Volver al login
                                </button>
                            </div>
                            <div class="help-text">
                                <p><small>¿No recibes el email? Revisa tu carpeta de spam o contacta con soporte.</small></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        this.currentUser = null;
        this.setupEventListeners();
    }
    show(user) {
        this.currentUser = user;
        // Inyectar modal en el DOM si no existe
        if (!document.getElementById('emailConfirmationModal')) {
            document.body.insertAdjacentHTML('beforeend', this.template);
            this.setupModalEventListeners();
        }
        // Mostrar email del usuario
        const emailDisplay = document.getElementById('userEmailDisplay');
        if (emailDisplay && user) {
            emailDisplay.textContent = user.email;
        }
        // Mostrar modal
        const modal = document.getElementById('emailConfirmationModal');
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden'; // Prevenir scroll
        }
        this.addModalStyles();
    }
    hide() {
        const modal = document.getElementById('emailConfirmationModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = ''; // Restaurar scroll
        }
    }
    setupEventListeners() {
        // Escuchar evento de email no confirmado
        window.addEventListener('auth-email-not-confirmed', (e) => {
            this.show(e.detail);
        });
    }
    setupModalEventListeners() {
        // Cerrar modal
        const closeBtn = document.getElementById('closeEmailModal');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.hide());
        }
        // Volver al login
        const goToLoginBtn = document.getElementById('goToLoginBtn');
        if (goToLoginBtn) {
            goToLoginBtn.addEventListener('click', () => {
                this.hide();
                // Navegar al login si hay router disponible
                if (window.appRouter) {
                    window.appRouter.navigate('/login');
                }
            });
        }
        // Cerrar al hacer click fuera del modal
        const modal = document.getElementById('emailConfirmationModal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.hide();
                }
            });
        }
    }
    showNotification(message, type = 'info') {
        // Crear o actualizar notificación dentro del modal
        let notification = document.getElementById('emailModalNotification');
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'emailModalNotification';
            notification.className = 'modal-notification';
            // Insertar antes de las acciones del modal
            const modalActions = document.querySelector('.modal-actions');
            if (modalActions) {
                modalActions.parentNode.insertBefore(notification, modalActions);
            }
        }
        notification.className = `modal-notification ${type}`;
        notification.innerHTML = `
            <span class="notification-text">${message}</span>
            <button class="notification-close" onclick="this.parentElement.style.display='none'">&times;</button>
        `;
        notification.style.display = 'flex';
        // Auto-ocultar después de 5 segundos
        setTimeout(() => {
            if (notification) {
                notification.style.display = 'none';
            }
        }, 5000);
    }
    addModalStyles() {
        if (!document.getElementById('email-modal-styles')) {
            const style = document.createElement('style');
            style.id = 'email-modal-styles';
            style.textContent = `
                .modal-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.7);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    z-index: 10000;
                    animation: fadeIn 0.3s ease;
                }
                .modal-container {
                    background: white;
                    border-radius: 12px;
                    max-width: 500px;
                    width: 90%;
                    max-height: 90vh;
                    overflow-y: auto;
                    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
                    animation: slideIn 0.3s ease;
                }
                .modal-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 1.5rem;
                    border-bottom: 1px solid #eee;
                    background: var(--canarias-blue, #1e3a8a);
                    color: white;
                    border-radius: 12px 12px 0 0;
                }
                .modal-header h2 {
                    margin: 0;
                    font-size: 1.25rem;
                }
                .modal-close {
                    background: none;
                    border: none;
                    font-size: 1.5rem;
                    color: white;
                    cursor: pointer;
                    padding: 0;
                    width: 30px;
                    height: 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    transition: background 0.2s;
                }
                .modal-close:hover {
                    background: rgba(255, 255, 255, 0.2);
                }
                .modal-body {
                    padding: 2rem;
                }
                .email-confirmation-content {
                    text-align: center;
                }
                .email-icon {
                    font-size: 4rem;
                    margin-bottom: 1rem;
                }
                .email-confirmation-content h3 {
                    color: var(--canarias-blue, #1e3a8a);
                    margin-bottom: 1rem;
                }
                .user-email {
                    background: #f8f9fa;
                    padding: 1rem;
                    border-radius: 8px;
                    margin: 1.5rem 0;
                    border-left: 4px solid var(--canarias-blue, #1e3a8a);
                }
                .user-email span {
                    color: var(--canarias-blue, #1e3a8a);
                    font-weight: 600;
                }
                .instructions {
                    text-align: left;
                    margin: 1.5rem 0;
                    background: #fff8dc;
                    padding: 1.5rem;
                    border-radius: 8px;
                    border-left: 4px solid #fbbf24;
                }
                .instructions h4 {
                    margin-top: 0;
                    color: #92400e;
                }
                .instructions ol {
                    margin: 0.5rem 0 0 0;
                    padding-left: 1.5rem;
                }
                .instructions li {
                    margin-bottom: 0.5rem;
                    color: #451a03;
                }
                .modal-actions {
                    display: flex;
                    flex-direction: column;
                    gap: 0.75rem;
                    margin: 1.5rem 0;
                }
                .modal-actions .btn {
                    padding: 0.75rem 1.5rem;
                    border: none;
                    border-radius: 6px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s;
                    text-decoration: none;
                    display: inline-block;
                    text-align: center;
                }
                .btn-primary {
                    background: var(--canarias-blue, #1e3a8a);
                    color: white;
                }
                .btn-primary:hover {
                    background: var(--canarias-blue-dark, #1e40af);
                    transform: translateY(-1px);
                }
                .btn-secondary {
                    background: #6b7280;
                    color: white;
                }
                .btn-secondary:hover {
                    background: #4b5563;
                }
                .help-text {
                    margin-top: 1.5rem;
                    color: #6b7280;
                }
                .modal-notification {
                    display: none;
                    align-items: center;
                    justify-content: space-between;
                    padding: 1rem;
                    margin: 1rem 0;
                    border-radius: 6px;
                    font-weight: 500;
                    animation: slideDown 0.3s ease;
                }
                .modal-notification.success {
                    background: #d1fae5;
                    color: #065f46;
                    border-left: 4px solid #10b981;
                }
                .modal-notification.error {
                    background: #fee2e2;
                    color: #991b1b;
                    border-left: 4px solid #ef4444;
                }
                .modal-notification.info {
                    background: #dbeafe;
                    color: #1e40af;
                    border-left: 4px solid #3b82f6;
                }
                .notification-text {
                    flex: 1;
                }
                .notification-close {
                    background: none;
                    border: none;
                    font-size: 1.2rem;
                    cursor: pointer;
                    padding: 0;
                    margin-left: 1rem;
                    opacity: 0.7;
                    transition: opacity 0.2s;
                }
                .notification-close:hover {
                    opacity: 1;
                }
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes slideIn {
                    from { 
                        opacity: 0;
                        transform: translateY(-20px) scale(0.95);
                    }
                    to { 
                        opacity: 1;
                        transform: translateY(0) scale(1);
                    }
                }
                @keyframes slideDown {
                    from {
                        opacity: 0;
                        transform: translateY(-10px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                @media (max-width: 768px) {
                    .modal-container {
                        width: 95%;
                        margin: 1rem;
                    }
                    .modal-body {
                        padding: 1.5rem;
                    }
                    .modal-actions {
                        flex-direction: column;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }
}
// Crear instancia global del modal
window.emailConfirmationModal = new EmailConfirmationModal();
// Exportar la clase
window.EmailConfirmationModal = EmailConfirmationModal;
