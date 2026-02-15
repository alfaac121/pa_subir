/**
 * Service Worker - Tu Mercado SENA
 * Para notificaciones push y funcionalidad offline
 * RF05-004, RF05-005
 */

const CACHE_NAME = 'tu-mercado-sena-v1';
const OFFLINE_URL = '/offline.html';

// Archivos a cachear para funcionamiento offline
const ASSETS_TO_CACHE = [
    '/',
    '/styles.css',
    '/script.js',
    '/logo_new.png',
    '/assets/images/default-avatar.jpg',
    '/assets/images/default-product.jpg'
];

// Instalación del Service Worker
self.addEventListener('install', (event) => {
    console.log('[SW] Instalando Service Worker...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[SW] Cacheando archivos estáticos');
                return cache.addAll(ASSETS_TO_CACHE);
            })
            .catch((error) => {
                console.log('[SW] Error al cachear:', error);
            })
    );
    self.skipWaiting();
});

// Activación del Service Worker
self.addEventListener('activate', (event) => {
    console.log('[SW] Service Worker activado');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[SW] Eliminando cache antiguo:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Interceptar peticiones de red
self.addEventListener('fetch', (event) => {
    // Solo cachear peticiones GET
    if (event.request.method !== 'GET') return;
    
    // No cachear peticiones a APIs
    if (event.request.url.includes('/api/')) return;
    
    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                // Devolver del cache si existe, sino ir a la red
                return response || fetch(event.request)
                    .then((fetchResponse) => {
                        // Cachear nuevas respuestas exitosas
                        if (fetchResponse && fetchResponse.status === 200) {
                            const responseClone = fetchResponse.clone();
                            caches.open(CACHE_NAME)
                                .then((cache) => {
                                    cache.put(event.request, responseClone);
                                });
                        }
                        return fetchResponse;
                    })
                    .catch(() => {
                        // Si falla la red y es una página HTML, mostrar offline
                        if (event.request.headers.get('accept').includes('text/html')) {
                            return caches.match(OFFLINE_URL);
                        }
                    });
            })
    );
});

// Manejar notificaciones push
self.addEventListener('push', (event) => {
    console.log('[SW] Push recibido:', event);
    
    let data = {
        title: 'Tu Mercado SENA',
        body: 'Tienes una nueva notificación',
        icon: '/logo_new.png',
        badge: '/logo_new.png',
        tag: 'general',
        data: { url: '/' }
    };
    
    // Intentar parsear los datos del push
    if (event.data) {
        try {
            const pushData = event.data.json();
            data = { ...data, ...pushData };
        } catch (e) {
            data.body = event.data.text();
        }
    }
    
    const options = {
        body: data.body,
        icon: data.icon || '/logo_new.png',
        badge: data.badge || '/logo_new.png',
        tag: data.tag || 'general',
        vibrate: [100, 50, 100],
        data: data.data || { url: '/' },
        actions: data.actions || [
            { action: 'view', title: 'Ver' },
            { action: 'close', title: 'Cerrar' }
        ],
        requireInteraction: data.requireInteraction || false
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Manejar clic en notificación
self.addEventListener('notificationclick', (event) => {
    console.log('[SW] Notificación clickeada:', event.notification.tag);
    
    event.notification.close();
    
    if (event.action === 'close') {
        return;
    }
    
    const urlToOpen = event.notification.data?.url || '/';
    
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Si ya hay una ventana abierta, enfocarla
                for (const client of clientList) {
                    if (client.url.includes(self.location.origin) && 'focus' in client) {
                        client.navigate(urlToOpen);
                        return client.focus();
                    }
                }
                // Si no hay ventana abierta, abrir una nueva
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});

// Manejar cierre de notificación
self.addEventListener('notificationclose', (event) => {
    console.log('[SW] Notificación cerrada:', event.notification.tag);
});

// Sincronización en background (para cuando se recupera la conexión)
self.addEventListener('sync', (event) => {
    console.log('[SW] Sync event:', event.tag);
    
    if (event.tag === 'sync-messages') {
        event.waitUntil(syncPendingMessages());
    }
});

// Función para sincronizar mensajes pendientes
async function syncPendingMessages() {
    try {
        const db = await openIndexedDB();
        const pendingMessages = await getAll(db, 'pending-messages');
        
        for (const message of pendingMessages) {
            try {
                const response = await fetch('/api/send_message.php', {
                    method: 'POST',
                    body: JSON.stringify(message),
                    headers: { 'Content-Type': 'application/json' }
                });
                
                if (response.ok) {
                    await deleteItem(db, 'pending-messages', message.id);
                }
            } catch (error) {
                console.log('[SW] Error sincronizando mensaje:', error);
            }
        }
    } catch (error) {
        console.log('[SW] Error en sync:', error);
    }
}

// Helper para IndexedDB
function openIndexedDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('TuMercadoSenaDB', 1);
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('pending-messages')) {
                db.createObjectStore('pending-messages', { keyPath: 'id', autoIncrement: true });
            }
        };
    });
}

function getAll(db, storeName) {
    return new Promise((resolve, reject) => {
        const tx = db.transaction(storeName, 'readonly');
        const store = tx.objectStore(storeName);
        const request = store.getAll();
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
    });
}

function deleteItem(db, storeName, id) {
    return new Promise((resolve, reject) => {
        const tx = db.transaction(storeName, 'readwrite');
        const store = tx.objectStore(storeName);
        const request = store.delete(id);
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve();
    });
}

console.log('[SW] Service Worker cargado');
