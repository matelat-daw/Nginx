//
// Notification Modal Component - Modal genérico para notificaciones
class NotificationModal {
    constructor() {
        this.modal = null;
        this.isVisible = false;
        this.init();
    }
    init() {
        // Crear el modal si no existe
        if (!document.getElementById('notificationModal')) {
            this.createModal();
        }
        this.modal = document.getElementById('notificationModal');
        this.setupEventListeners();
    }
    createModal() {
        const modalHTML = `
            <div id="notificationModal" class="modal-overlay" style="display: none;">
                <div class="modal-container notification-modal">
                    <div class="modal-header">
                        <div id="notificationIcon" class="notification-icon">⚠️</div>
                        <h3 id="notificationTitle" class="notification-title">Notificación</h3>
                        <button id="closeNotificationModal" class="modal-close" aria-label="Cerrar">×</button>
                    </div>
                    <div class="modal-body">
                        <p id="notificationMessage" class="notification-message"></p>
                        <div id="notificationDetails" class="notification-details" style="display: none;"></div>
                    </div>
                    <div class="modal-footer">
                        <button id="notificationAcceptBtn" class="btn-modal btn-primary">Entendido</button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.addModalStyles();
    }
    addModalStyles() {
        if (!document.getElementById('notificationModalStyles')) {
            const styles = `
                <style id="notificationModalStyles">
                    .notification-modal {
                        max-width: 480px;
                        width: 90%;
                    }
                    .notification-modal .modal-header {
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        padding: 20px 24px 16px;
                        border-bottom: 1px solid var(--border-color, #e2e8f0);
                    }
                    .notification-icon {
                        font-size: 24px;
                        line-height: 1;
                    }
                    .notification-title {
                        margin: 0;
                        font-size: 18px;
                        font-weight: 600;
                        color: var(--text-primary, #1e293b);
                        flex: 1;
                    }
                    .notification-message {
                        margin: 0;
                        padding: 20px 24px;
                        font-size: 16px;
                        line-height: 1.5;
                        color: var(--text-secondary, #475569);
                    }
                    .notification-details {
                        padding: 0 24px 20px;
                        font-size: 14px;
                        color: var(--text-muted, #64748b);
                        background: var(--bg-muted, #f8fafc);
                        border-radius: 8px;
                        margin: 0 24px;
                    }
                    .notification-details ul {
                        margin: 8px 0;
                        padding-left: 20px;
                    }
                    .notification-details li {
                        margin-bottom: 4px;
                    }
                    /* Tipos de notificación */
                    .notification-modal.error .notification-icon {
                        color: #ef4444;
                    }
                    .notification-modal.error .notification-title {
                        color: #dc2626;
                    }
                    .notification-modal.success .notification-icon {
                        color: #10b981;
                    }
                    .notification-modal.success .notification-title {
                        color: #059669;
                    }
                    .notification-modal.warning .notification-icon {
                        color: #f59e0b;
                    }
                    .notification-modal.warning .notification-title {
                        color: #d97706;
                    }
                    .notification-modal.info .notification-icon {
                        color: #3b82f6;
                    }
                    .notification-modal.info .notification-title {
                        color: #2563eb;
                    }
                    /* Animaciones */
                    .notification-modal {
                        animation: notificationSlideIn 0.3s ease-out;
                    }
                    @keyframes notificationSlideIn {
                        from {
                            opacity: 0;
                            transform: translateY(-20px) scale(0.95);
                        }
                        to {
                            opacity: 1;
                            transform: translateY(0) scale(1);
                        }
                    }
                </style>
            `;
            document.head.insertAdjacentHTML('beforeend', styles);
        }
    }
    setupEventListeners() {
        const closeBtn = document.getElementById('closeNotificationModal');
        const acceptBtn = document.getElementById('notificationAcceptBtn');
        const overlay = document.getElementById('notificationModal');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.hide());
        }
        if (acceptBtn) {
            acceptBtn.addEventListener('click', () => this.hide());
        }
        if (overlay) {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    this.hide();
                }
            });
        }
        // Cerrar con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isVisible) {
                this.hide();
            }
        });
    }
    show(options = {}) {
        const {
            type = 'info',
            title = 'Notificación',
            message = '',
            details = null,
            icon = null
        } = options;
        if (!this.modal) {
            this.init();
        }
        // Configurar contenido
        const iconElement = document.getElementById('notificationIcon');
        const titleElement = document.getElementById('notificationTitle');
        const messageElement = document.getElementById('notificationMessage');
        const detailsElement = document.getElementById('notificationDetails');
        // Configurar icono según tipo
        const icons = {
            error: '❌',
            success: '✅',
            warning: '⚠️',
            info: 'ℹ️'
        };
        if (iconElement) {
            iconElement.textContent = icon || icons[type] || icons.info;
        }
        if (titleElement) {
            titleElement.textContent = title;
        }
        if (messageElement) {
            messageElement.textContent = message;
        }
        // Configurar detalles si se proporcionan
        if (detailsElement) {
            if (details) {
                if (Array.isArray(details)) {
                    detailsElement.innerHTML = '<ul>' + details.map(detail => `<li>${detail}</li>`).join('') + '</ul>';
                } else {
                    detailsElement.textContent = details;
                }
                detailsElement.style.display = 'block';
            } else {
                detailsElement.style.display = 'none';
            }
        }
        // Aplicar clase de tipo
        const modalContainer = this.modal.querySelector('.modal-container');
        if (modalContainer) {
            modalContainer.className = `modal-container notification-modal ${type}`;
        }
        // Mostrar modal
        this.modal.style.display = 'flex';
        this.isVisible = true;
        // Focus en el botón de cerrar para accesibilidad
        setTimeout(() => {
            const acceptBtn = document.getElementById('notificationAcceptBtn');
            if (acceptBtn) {
                acceptBtn.focus();
            }
        }, 100);
    }
    hide() {
        if (this.modal) {
            this.modal.style.display = 'none';
            this.isVisible = false;
        }
    }
    // Métodos de conveniencia
    showError(message, details = null) {
        this.show({
            type: 'error',
            title: 'Error',
            message,
            details
        });
    }
    showSuccess(message, details = null) {
        this.show({
            type: 'success',
            title: 'Éxito',
            message,
            details
        });
    }
    showWarning(message, details = null) {
        this.show({
            type: 'warning',
            title: 'Advertencia',
            message,
            details
        });
    }
    showInfo(message, details = null) {
        this.show({
            type: 'info',
            title: 'Información',
            message,
            details
        });
    }
}
// Instancia global del modal de notificaciones
window.notificationModal = new NotificationModal();
