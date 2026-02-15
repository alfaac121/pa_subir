/**
 * Sistema de Notificaciones Push - Tu Mercado SENA
 * RF05-003, RF05-004, RF05-005
 */

const NotificationManager = {
    // Estado de las notificaciones
    supported: false,
    permission: 'default',
    swRegistration: null,

    /**
     * Inicializar el sistema de notificaciones
     */
    async init() {
        // Verificar soporte
        if (!('Notification' in window)) {
            console.log('[Notif] Navegador no soporta notificaciones');
            return false;
        }

        if (!('serviceWorker' in navigator)) {
            console.log('[Notif] Navegador no soporta Service Workers');
            return false;
        }

        this.supported = true;
        this.permission = Notification.permission;

        // Registrar Service Worker
        try {
            this.swRegistration = await navigator.serviceWorker.register('/sw.js');
            console.log('[Notif] Service Worker registrado');
        } catch (error) {
            console.log('[Notif] Error registrando SW:', error);
            // Intentar con ruta relativa
            try {
                this.swRegistration = await navigator.serviceWorker.register('./sw.js');
                console.log('[Notif] Service Worker registrado (ruta relativa)');
            } catch (err) {
                console.log('[Notif] Error registrando SW (ruta relativa):', err);
            }
        }

        return true;
    },

    /**
     * Solicitar permiso para notificaciones
     */
    async requestPermission() {
        if (!this.supported) {
            return { success: false, message: 'Notificaciones no soportadas' };
        }

        try {
            const permission = await Notification.requestPermission();
            this.permission = permission;

            if (permission === 'granted') {
                // Guardar preferencia en el servidor
                await this.savePreference(true);
                return { success: true, message: 'Notificaciones activadas' };
            } else if (permission === 'denied') {
                return { success: false, message: 'Notificaciones bloqueadas por el usuario' };
            } else {
                return { success: false, message: 'Permiso pendiente' };
            }
        } catch (error) {
            return { success: false, message: 'Error al solicitar permiso' };
        }
    },

    /**
     * Desactivar notificaciones
     */
    async disable() {
        await this.savePreference(false);
        return { success: true, message: 'Notificaciones desactivadas' };
    },

    /**
     * Guardar preferencia en el servidor
     */
    async savePreference(enabled) {
        try {
            const response = await fetch('api/toggle_notificaciones.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `tipo=push&estado=${enabled ? 1 : 0}`
            });
            return await response.json();
        } catch (error) {
            console.log('[Notif] Error guardando preferencia:', error);
            return { success: false };
        }
    },

    /**
     * Mostrar notificación local
     */
    async showLocal(title, options = {}) {
        if (!this.supported || this.permission !== 'granted') {
            console.log('[Notif] No se puede mostrar notificación');
            return false;
        }

        const defaultOptions = {
            icon: 'logo_new.png',
            badge: 'logo_new.png',
            vibrate: [100, 50, 100],
            requireInteraction: false,
            silent: false
        };

        const finalOptions = { ...defaultOptions, ...options };

        try {
            if (this.swRegistration) {
                await this.swRegistration.showNotification(title, finalOptions);
            } else {
                new Notification(title, finalOptions);
            }
            return true;
        } catch (error) {
            console.log('[Notif] Error mostrando notificación:', error);
            return false;
        }
    },

    /**
     * Mostrar notificación de nuevo mensaje
     */
    async showNewMessage(senderName, message, chatUrl) {
        return this.showLocal(`💬 ${senderName}`, {
            body: message.substring(0, 100) + (message.length > 100 ? '...' : ''),
            tag: 'new-message',
            data: { url: chatUrl },
            actions: [
                { action: 'reply', title: 'Responder' },
                { action: 'view', title: 'Ver chat' }
            ]
        });
    },

    /**
     * Mostrar notificación de nueva venta
     */
    async showNewSale(productName, buyerName) {
        return this.showLocal('🎉 ¡Nueva venta!', {
            body: `${buyerName} quiere comprar "${productName}"`,
            tag: 'new-sale',
            requireInteraction: true
        });
    },

    /**
     * Mostrar notificación de devolución
     */
    async showRefundRequest(productName) {
        return this.showLocal('🔄 Solicitud de devolución', {
            body: `Han solicitado devolución para "${productName}"`,
            tag: 'refund-request',
            requireInteraction: true
        });
    },

    /**
     * Verificar si las notificaciones están habilitadas
     */
    isEnabled() {
        return this.supported && this.permission === 'granted';
    },

    /**
     * Obtener estado de las notificaciones
     */
    getStatus() {
        return {
            supported: this.supported,
            permission: this.permission,
            enabled: this.isEnabled()
        };
    }
};

// Toast notifications (in-app)
const ToastManager = {
    container: null,

    init() {
        // Crear contenedor si no existe
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.style.cssText = `
                position: fixed;
                top: 80px;
                right: 20px;
                z-index: 10000;
                display: flex;
                flex-direction: column;
                gap: 10px;
                max-width: 350px;
            `;
            document.body.appendChild(this.container);
        }
    },

    show(message, type = 'info', duration = 4000) {
        this.init();

        const toast = document.createElement('div');
        const icons = {
            success: '✓',
            error: '✗',
            warning: '⚠',
            info: 'ℹ'
        };
        const colors = {
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
        };

        toast.style.cssText = `
            background: white;
            border-left: 4px solid ${colors[type] || colors.info};
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
            font-size: 14px;
        `;

        toast.innerHTML = `
            <span style="font-size: 20px;">${icons[type] || icons.info}</span>
            <span style="flex: 1;">${message}</span>
            <button onclick="this.parentElement.remove()" style="background: none; border: none; font-size: 18px; cursor: pointer; color: #666;">&times;</button>
        `;

        this.container.appendChild(toast);

        // Agregar animación de entrada
        const style = document.createElement('style');
        if (!document.getElementById('toast-animations')) {
            style.id = 'toast-animations';
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }

        // Auto-remover después del tiempo especificado
        if (duration > 0) {
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        return toast;
    },

    success(message, duration) {
        return this.show(message, 'success', duration);
    },

    error(message, duration) {
        return this.show(message, 'error', duration);
    },

    warning(message, duration) {
        return this.show(message, 'warning', duration);
    },

    info(message, duration) {
        return this.show(message, 'info', duration);
    }
};

// Inicializar al cargar la página
document.addEventListener('DOMContentLoaded', async () => {
    await NotificationManager.init();
    ToastManager.init();
});

// Exponer globalmente
window.NotificationManager = NotificationManager;
window.ToastManager = ToastManager;
