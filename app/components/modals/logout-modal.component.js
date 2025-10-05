// Logout Modal Component
class LogoutModal {
    constructor() {
        this.template = `
            <div class="modal-overlay" id="logoutModal" style="display: none;">
                <div class="modal-container logout-modal">
                    <div class="modal-header">
                        <h2>🚪 Cerrar Sesión</h2>
                    </div>
                    <div class="modal-body">
                        <div class="logout-content">
                            <div class="logout-icon">
                                🤔
                            </div>
                            <h3>¿Estás seguro?</h3>
                            <p>¿Realmente quieres cerrar tu sesión?</p>
                            <p class="logout-note">Tendrás que volver a iniciar sesión para acceder a tu cuenta.</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" id="cancelLogoutBtn">
                            ❌ Cancelar
                        </button>
                        <button class="btn btn-danger" id="confirmLogoutBtn">
                            🚪 Sí, cerrar sesión
                        </button>
                    </div>
                </div>
            </div>
        `;
        this.successTemplate = `
            <div class="modal-overlay" id="logoutSuccessModal" style="display: none;">
                <div class="modal-container logout-success-modal">
                    <div class="modal-header success">
                        <h2>✅ Sesión Cerrada</h2>
                    </div>
                    <div class="modal-body">
                        <div class="logout-success-content">
                            <div class="success-icon">
                                👋
                            </div>
                            <h3>¡Hasta luego!</h3>
                            <p>Tu sesión se ha cerrado correctamente.</p>
                            <p class="success-note">Gracias por usar Economía Circular Canarias.</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" id="closeSuccessBtn">
                            🏠 Volver al inicio
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    show() {
        console.log('🔍 LogoutModal: Mostrando modal de confirmación');
        return new Promise((resolve) => {
            // Inyectar modal en el DOM si no existe
            if (!document.getElementById('logoutModal')) {
                console.log('🔍 LogoutModal: Inyectando modal en DOM');
                document.body.insertAdjacentHTML('beforeend', this.template);
                this.setupModalEventListeners(resolve);
            } else {
                console.log('🔍 LogoutModal: Modal ya existe en DOM, reusando');
                // Si el modal ya existe, necesitamos recrear los event listeners
                this.setupModalEventListeners(resolve);
            }
            
            // Mostrar modal
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden'; // Prevenir scroll
                console.log('🔍 LogoutModal: Modal mostrado');
            } else {
                console.error('❌ LogoutModal: No se pudo encontrar el modal en DOM');
            }
            this.addModalStyles();
        });
    }
    hide() {
        const modal = document.getElementById('logoutModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = ''; // Restaurar scroll
        }
    }
    showSuccess() {
        return new Promise((resolve) => {
            // Primero ocultar el modal de confirmación
            this.hide();
            // Inyectar modal de éxito si no existe
            if (!document.getElementById('logoutSuccessModal')) {
                document.body.insertAdjacentHTML('beforeend', this.successTemplate);
                this.setupSuccessModalEventListeners(resolve);
            }
            // Mostrar modal de éxito
            const successModal = document.getElementById('logoutSuccessModal');
            if (successModal) {
                successModal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
            this.addModalStyles();
            // Auto-cerrar después de 3 segundos
            setTimeout(() => {
                this.hideSuccess();
                resolve();
            }, 3000);
        });
    }
    hideSuccess() {
        const modal = document.getElementById('logoutSuccessModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }
    setupModalEventListeners(resolve) {
        const cancelBtn = document.getElementById('cancelLogoutBtn');
        const confirmBtn = document.getElementById('confirmLogoutBtn');
        const modal = document.getElementById('logoutModal');
        
        console.log('🔍 LogoutModal: Configurando event listeners', { cancelBtn: !!cancelBtn, confirmBtn: !!confirmBtn, modal: !!modal });
        
        if (cancelBtn) {
            // Remover listeners previos clonando el botón
            const newCancelBtn = cancelBtn.cloneNode(true);
            cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
            
            newCancelBtn.addEventListener('click', () => {
                console.log('🔍 LogoutModal: Usuario canceló');
                this.hide();
                resolve(false); // Usuario canceló
            });
        }
        
        if (confirmBtn) {
            // Remover listeners previos clonando el botón
            const newConfirmBtn = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
            
            newConfirmBtn.addEventListener('click', () => {
                console.log('🔍 LogoutModal: Usuario confirmó');
                this.hide();
                resolve(true); // Usuario confirmó
            });
        }
        
        // Cerrar al hacer clic fuera del modal
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    console.log('🔍 LogoutModal: Click fuera del modal, cancelando');
                    this.hide();
                    resolve(false);
                }
            });
        }
        
        // Cerrar con Escape
        const escapeHandler = (e) => {
            if (e.key === 'Escape') {
                console.log('🔍 LogoutModal: Escape presionado, cancelando');
                this.hide();
                resolve(false);
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);
    }
    setupSuccessModalEventListeners(resolve) {
        const closeBtn = document.getElementById('closeSuccessBtn');
        const modal = document.getElementById('logoutSuccessModal');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                this.hideSuccess();
                resolve();
            });
        }
        // Cerrar al hacer clic fuera del modal
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.hideSuccess();
                    resolve();
                }
            });
        }
    }
    addModalStyles() {
        if (!document.getElementById('logout-modal-styles')) {
            const style = document.createElement('style');
            style.id = 'logout-modal-styles';
            style.textContent = `
                .modal-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.6);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    z-index: 10000;
                    backdrop-filter: blur(4px);
                }
                .modal-container {
                    background: white;
                    border-radius: 15px;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                    max-width: 450px;
                    width: 90%;
                    max-height: 90vh;
                    overflow-y: auto;
                    animation: modalSlideIn 0.3s ease-out;
                }
                .logout-modal .modal-header {
                    background: linear-gradient(135deg, #ef4444, #dc2626);
                    color: white;
                    padding: 1.5rem;
                    border-radius: 15px 15px 0 0;
                    text-align: center;
                }
                .logout-success-modal .modal-header {
                    background: linear-gradient(135deg, #10b981, #059669);
                    color: white;
                    padding: 1.5rem;
                    border-radius: 15px 15px 0 0;
                    text-align: center;
                }
                .modal-header h2 {
                    margin: 0;
                    font-size: 1.5rem;
                    font-weight: 600;
                }
                .modal-body {
                    padding: 2rem;
                }
                .logout-content,
                .logout-success-content {
                    text-align: center;
                }
                .logout-icon,
                .success-icon {
                    font-size: 4rem;
                    margin-bottom: 1rem;
                }
                .logout-content h3,
                .logout-success-content h3 {
                    color: #374151;
                    margin-bottom: 1rem;
                    font-size: 1.5rem;
                }
                .logout-content p,
                .logout-success-content p {
                    color: #6b7280;
                    margin-bottom: 0.5rem;
                    line-height: 1.6;
                }
                .logout-note,
                .success-note {
                    font-size: 0.9rem !important;
                    color: #9ca3af !important;
                    font-style: italic;
                }
                .modal-footer {
                    padding: 1.5rem 2rem;
                    border-top: 1px solid #e5e7eb;
                    display: flex;
                    gap: 1rem;
                    justify-content: flex-end;
                }
                .btn {
                    padding: 0.75rem 1.5rem;
                    border: none;
                    border-radius: 8px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    font-size: 1rem;
                }
                .btn-secondary {
                    background: #6b7280;
                    color: white;
                }
                .btn-secondary:hover {
                    background: #4b5563;
                    transform: translateY(-1px);
                }
                .btn-danger {
                    background: #ef4444;
                    color: white;
                }
                .btn-danger:hover {
                    background: #dc2626;
                    transform: translateY(-1px);
                }
                .btn-primary {
                    background: #3b82f6;
                    color: white;
                }
                .btn-primary:hover {
                    background: #2563eb;
                    transform: translateY(-1px);
                }
                @keyframes modalSlideIn {
                    from {
                        opacity: 0;
                        transform: translateY(-50px) scale(0.9);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0) scale(1);
                    }
                }
                /* Tema oscuro */
                [data-theme="dark"] .modal-container {
                    background: #1f2937;
                    color: #f9fafb;
                }
                [data-theme="dark"] .logout-content h3,
                [data-theme="dark"] .logout-success-content h3 {
                    color: #f9fafb;
                }
                [data-theme="dark"] .logout-content p,
                [data-theme="dark"] .logout-success-content p {
                    color: #d1d5db;
                }
                [data-theme="dark"] .modal-footer {
                    border-top-color: #374151;
                }
                /* Responsive */
                @media (max-width: 640px) {
                    .modal-container {
                        width: 95%;
                        margin: 1rem;
                    }
                    .modal-footer {
                        flex-direction: column;
                    }
                    .btn {
                        width: 100%;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }
}
// Crear instancia global del modal
window.logoutModal = new LogoutModal();
// Exportar la clase
window.LogoutModal = LogoutModal;
