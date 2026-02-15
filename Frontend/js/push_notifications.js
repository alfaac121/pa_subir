/**
 * Sistema de Notificaciones Push
 * RF05-004, RF05-005 - Tu Mercado SENA
 */

// Registrar Service Worker y solicitar permisos
async function initPushNotifications() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        console.log('Push messaging no está soportado');
        return false;
    }

    try {
        // Registrar Service Worker
        const registration = await navigator.serviceWorker.register('/sw.js');
        console.log('Service Worker registrado:', registration);

        // Solicitar permiso para notificaciones
        const permission = await Notification.requestPermission();
        
        if (permission === 'granted') {
            console.log('Permiso para notificaciones concedido');
            
            // Suscribirse a push notifications
            await subscribeToPush(registration);
            return true;
        } else {
            console.log('Permiso para notificaciones denegado');
            return false;
        }
    } catch (error) {
        console.error('Error al inicializar notificaciones push:', error);
        return false;
    }
}

// Suscribirse a push notifications
async function subscribeToPush(registration) {
    try {
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(getVapidPublicKey())
        });

        // Enviar suscripción al servidor
        await sendSubscriptionToServer(subscription);
        
        return subscription;
    } catch (error) {
        console.error('Error al suscribirse a push:', error);
        return null;
    }
}

// Convertir clave VAPID de base64 a Uint8Array
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

// Obtener clave pública VAPID (debe configurarse)
function getVapidPublicKey() {
    // Por ahora retornamos una clave de ejemplo
    // En producción, esto debe venir del servidor
    return 'BEl62iUYgUivxIkv69yViEuiBIa40HIe8y8zLJx8zLJx8zLJx8zLJx8zLJx8zLJx8zLJx8zLJx8zLJx8zLJx8';
}

// Enviar suscripción al servidor
async function sendSubscriptionToServer(subscription) {
    try {
        const response = await fetch('/api/save_push_subscription.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                subscription: subscription
            })
        });

        const data = await response.json();
        if (data.success) {
            console.log('Suscripción guardada en el servidor');
        }
    } catch (error) {
        console.error('Error al guardar suscripción:', error);
    }
}

// Mostrar notificación emergente (RF05-005)
function showBrowserNotification(title, options = {}) {
    if (!('Notification' in window)) {
        console.log('Este navegador no soporta notificaciones');
        return;
    }

    if (Notification.permission === 'granted') {
        const notification = new Notification(title, {
            icon: '/logo_new.png',
            badge: '/logo_new.png',
            tag: options.tag || 'general',
            body: options.body || '',
            data: options.data || {},
            requireInteraction: options.requireInteraction || false,
            ...options
        });

        notification.onclick = function() {
            window.focus();
            if (options.data && options.data.url) {
                window.location.href = options.data.url;
            }
            notification.close();
        };

        // Auto-cerrar después de 5 segundos
        setTimeout(() => {
            notification.close();
        }, 5000);
    } else if (Notification.permission !== 'denied') {
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                showBrowserNotification(title, options);
            }
        });
    }
}

// Escuchar mensajes del Service Worker
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.addEventListener('message', (event) => {
        if (event.data && event.data.type === 'SHOW_NOTIFICATION') {
            showBrowserNotification(event.data.title, event.data.options);
        }
    });
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // Solo inicializar si el usuario tiene notificaciones push habilitadas
        const userPrefs = window.currentUserPrefs || {};
        if (userPrefs.notifica_push === 1) {
            initPushNotifications();
        }
    });
} else {
    const userPrefs = window.currentUserPrefs || {};
    if (userPrefs.notifica_push === 1) {
        initPushNotifications();
    }
}

// Exportar funciones globales
window.showBrowserNotification = showBrowserNotification;
window.initPushNotifications = initPushNotifications;
