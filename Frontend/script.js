// JavaScript para funcionalidades del marketplace

// ==================== TEMA OSCURO/CLARO ====================
function initTheme() {
    const pagesForceLight = ['welcome.php', 'login.php', 'register.php'];
    const currentPage = window.location.pathname.split("/").pop();

    if (pagesForceLight.includes(currentPage)) {
        localStorage.setItem('theme', 'light');
        document.documentElement.setAttribute('data-theme', 'light');
        return;
    }

    const savedTheme = localStorage.getItem("theme") || "light";
    document.documentElement.setAttribute("data-theme", savedTheme);
}

document.addEventListener('DOMContentLoaded', initTheme);

// ==================== MENÚ HAMBURGUESA ====================
function initHamburgerMenu() {
    const menuToggle = document.getElementById('menuToggle');
    const menuClose = document.getElementById('menuClose');
    const mainNav = document.getElementById('mainNav');
    const menuOverlay = document.getElementById('menuOverlay');

    if (!menuToggle || !mainNav) return;

    // Abrir menú
    menuToggle.addEventListener('click', function (e) {
        e.stopPropagation();
        menuToggle.classList.toggle('active');
        mainNav.classList.toggle('active');
        if (menuOverlay) menuOverlay.classList.toggle('active');
        document.body.style.overflow = mainNav.classList.contains('active') ? 'hidden' : '';
    });

    // Cerrar menú con botón X
    if (menuClose) {
        menuClose.addEventListener('click', function () {
            closeHamburgerMenu();
        });
    }

    // Cerrar menú con overlay
    if (menuOverlay) {
        menuOverlay.addEventListener('click', function () {
            closeHamburgerMenu();
        });
    }

    // Cerrar menú al hacer clic en un enlace
    const navLinks = mainNav.querySelectorAll('a');
    navLinks.forEach(link => {
        link.addEventListener('click', function () {
            // Pequeño delay para ver el efecto antes de navegar
            setTimeout(closeHamburgerMenu, 100);
        });
    });
}

function closeHamburgerMenu() {
    const menuToggle = document.getElementById('menuToggle');
    const mainNav = document.getElementById('mainNav');
    const menuOverlay = document.getElementById('menuOverlay');

    if (menuToggle) menuToggle.classList.remove('active');
    if (mainNav) mainNav.classList.remove('active');
    if (menuOverlay) menuOverlay.classList.remove('active');
    document.body.style.overflow = '';
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeIcon(newTheme);
}

function updateThemeIcon(theme) {
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.textContent = theme === 'dark' ? '☀️' : '🌙';
    }
}

// ==================== CHAT EN TIEMPO REAL ====================
let chatPollingInterval = null;
let lastMessageId = 0;

function initChatRealTime(chatId) {
    if (!chatId) return;

    // Cargar últimos mensajes al iniciar
    loadNewMessages(chatId);

    // Polling cada 2 segundos para nuevos mensajes
    chatPollingInterval = setInterval(() => {
        loadNewMessages(chatId);
    }, 2000);

    // Limpiar intervalo al salir de la página
    window.addEventListener('beforeunload', () => {
        if (chatPollingInterval) {
            clearInterval(chatPollingInterval);
        }
    });
}

function loadNewMessages(chatId) {
    if (!chatId) return;

    fetch(`api/get_messages.php?chat_id=${chatId}&last_id=${lastMessageId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.messages && data.messages.length > 0) {
                const chatMessages = document.getElementById('chatMessages');
                if (chatMessages) {
                    data.messages.forEach(message => {
                        addMessageToChat(message, chatMessages);
                        lastMessageId = Math.max(lastMessageId, message.id);
                    });
                    scrollChatToBottom();
                }
            }
        })
        .catch(error => {
            console.error('Error al cargar mensajes:', error);
        });
}

function addMessageToChat(message, container) {
    // Verificar si el mensaje ya existe
    if (document.getElementById(`message-${message.id}`)) {
        return;
    }

    const messageDiv = document.createElement('div');
    messageDiv.id = `message-${message.id}`;
    messageDiv.className = `message ${message.es_mio == 1 ? 'message-sent' : 'message-received'}`;
    const messageText = document.createElement('p');
    // Convertir saltos de línea a <br>
    messageText.innerHTML = message.mensaje.replace(/\n/g, '<br>');

    const messageTime = document.createElement('span');
    messageTime.className = 'message-time';
    messageTime.textContent = formatMessageTime(message.fecha_registro);

    messageDiv.appendChild(messageText);
    messageDiv.appendChild(messageTime);
    container.appendChild(messageDiv);
}

function formatMessageTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);

    if (minutes < 1) return 'Ahora';
    if (minutes < 60) return `Hace ${minutes} min`;
    if (minutes < 1440) return `Hace ${Math.floor(minutes / 60)} h`;

    return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

// Auto-scroll en chat
function scrollChatToBottom() {
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
}

// Enviar mensaje con AJAX
function sendMessage(chatId, messageText, callback) {
    if (!messageText.trim() || !chatId) return;

    const formData = new FormData();
    formData.append('chat_id', chatId);
    formData.append('mensaje', messageText);

    fetch('api/send_message.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Si estamos en el modal, agregar el mensaje ahí
                if (window.currentModalChatId === chatId) {
                    const messagesContainer = document.getElementById('chatModalMessages');
                    if (messagesContainer && data.message) {
                        addMessageToModal(data.message, messagesContainer);
                        window.currentModalLastMessageId = Math.max(window.currentModalLastMessageId || 0, data.message.id);
                        scrollModalToBottom();
                    } else {
                        loadModalMessages(chatId);
                    }
                } else {
                    // Si estamos en la página de chat normal
                    const textarea = document.getElementById('messageInput');
                    if (textarea) textarea.value = '';

                    if (data.message) {
                        const chatMessages = document.getElementById('chatMessages');
                        if (chatMessages) {
                            addMessageToChat(data.message, chatMessages);
                            lastMessageId = Math.max(lastMessageId, data.message.id);
                            scrollChatToBottom();
                        }
                    } else {
                        loadNewMessages(chatId);
                    }
                }

                // Recargar notificaciones
                loadNotifications();

                // Ejecutar callback si existe
                if (callback) callback();
            } else {
                alert('Error al enviar mensaje: ' + (data.error || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error al enviar mensaje:', error);
            alert('Error al enviar mensaje. Por favor intenta de nuevo.');
        });
}

// ==================== SISTEMA DE NOTIFICACIONES ====================
let notificationsPollingInterval = null;
let currentChatModal = null;
let lastNotificationCheck = Date.now();

function initNotifications() {
    // Cargar notificaciones al iniciar
    loadNotifications();

    // Polling cada 3 segundos para nuevas notificaciones
    notificationsPollingInterval = setInterval(() => {
        loadNotifications(true); // true = silencioso (no mostrar notificaciones)
    }, 3000);

    // Limpiar intervalo al salir
    window.addEventListener('beforeunload', () => {
        if (notificationsPollingInterval) {
            clearInterval(notificationsPollingInterval);
        }
    });
}

function loadNotifications(silent = false) {
    fetch('api/get_chats_notificaciones.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationCount(data.total_no_leidos);

                // Actualizar lista de chats solo si está visible
                const chatsList = document.getElementById('chatsList');
                if (chatsList && (chatsList.classList.contains('active') || !silent)) {
                    updateChatsList(data.chats);
                }

                // Mostrar notificaciones de nuevos mensajes (solo si no es silencioso y no estamos en un modal de chat)
                if (!silent && data.chats && !window.currentModalChatId) {
                    checkNewMessages(data.chats);
                }
            }
        })
        .catch(error => {
            console.error('Error al cargar notificaciones:', error);
        });
}

function updateNotificationCount(count) {
    const notificationCount = document.getElementById('notificationCount');
    if (notificationCount) {
        if (count > 0) {
            notificationCount.textContent = count > 99 ? '99+' : count;
            notificationCount.classList.remove('hidden');
        } else {
            notificationCount.classList.add('hidden');
        }
    }
}

function updateChatsList(chats) {
    const chatsList = document.getElementById('chatsList');
    if (!chatsList) return;

    if (!chats || chats.length === 0) {
        chatsList.innerHTML = '<div class="chat-item"><p style="padding: 1rem; text-align: center; color: var(--color-text-light);">No tienes chats activos</p></div>';
        return;
    }

    chatsList.innerHTML = chats.map(chat => {
        const unread = parseInt(chat.mensajes_no_leidos) || 0;
        const lastMsg = chat.ultimo_mensaje ? chat.ultimo_mensaje.mensaje : 'Sin mensajes';
        const lastMsgPreview = lastMsg.length > 50 ? lastMsg.substring(0, 50) + '...' : lastMsg;

        return `
            <div class="chat-item" data-chat-id="${chat.chat_id}" onclick="openChatModal(${chat.chat_id}, '${escapeHtml(chat.producto_nombre)}', '${escapeHtml(chat.otro_usuario)}')">
                <div class="chat-item-info">
                    <div class="chat-item-title">${escapeHtml(chat.producto_nombre)}</div>
                    <div class="chat-item-message">${escapeHtml(chat.otro_usuario)}: ${escapeHtml(lastMsgPreview)}</div>
                </div>
                ${unread > 0 ? `<span class="chat-item-badge">${unread > 99 ? '99+' : unread}</span>` : '<span class="chat-item-badge hidden">0</span>'}
            </div>
        `;
    }).join('');
}


function checkNewMessages(chats) {
    const storedLastCheck = localStorage.getItem('lastNotificationCheck');
    const lastCheckTime = storedLastCheck ? parseInt(storedLastCheck) : Date.now() - 60000; // 1 minuto atrás si es la primera vez

    let newMessagesFound = false;

    chats.forEach(chat => {
        if (chat.ultimo_mensaje && chat.mensajes_no_leidos > 0) {
            try {
                // Convertir fecha de MySQL a timestamp
                const fechaStr = chat.ultimo_mensaje.fecha_registro;
                const fecha = new Date(fechaStr.replace(' ', 'T'));
                const lastMsgTime = fecha.getTime();

                // Si el mensaje es nuevo (después de la última verificación)
                if (lastMsgTime > lastCheckTime) {
                    newMessagesFound = true;
                    const messagePreview = chat.ultimo_mensaje.mensaje.length > 50
                        ? chat.ultimo_mensaje.mensaje.substring(0, 50) + '...'
                        : chat.ultimo_mensaje.mensaje;

                    showBrowserNotification(
                        chat.producto_nombre,
                        `${chat.otro_usuario}: ${messagePreview}`,
                        chat.chat_id
                    );

                    // Intentar mostrar notificación del navegador
                    requestBrowserNotification(chat.producto_nombre, `${chat.otro_usuario} te escribió`);
                }
            } catch (e) {
                console.error('Error al procesar mensaje:', e);
            }
        }
    });

    // Actualizar último check solo si encontramos mensajes nuevos o es la primera vez
    if (newMessagesFound || !storedLastCheck) {
        localStorage.setItem('lastNotificationCheck', Date.now().toString());
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showBrowserNotification(title, message, chatId) {
    // Eliminar notificaciones anteriores
    const existing = document.querySelectorAll('.browser-notification');
    existing.forEach(n => n.remove());

    const notification = document.createElement('div');
    notification.className = 'browser-notification';
    notification.onclick = () => {
        openChatModal(chatId, title, '');
        notification.remove();
    };

    notification.innerHTML = `
        <div class="browser-notification-header">
            <div class="browser-notification-title">${escapeHtml(title)}</div>
            <button class="browser-notification-close" onclick="event.stopPropagation(); this.parentElement.parentElement.remove()">×</button>
        </div>
        <div class="browser-notification-message">${escapeHtml(message)}</div>
        <div class="browser-notification-time">Hace un momento</div>
    `;

    document.body.appendChild(notification);

    // Auto-eliminar después de 5 segundos
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

function requestBrowserNotification(title, body) {
    if (!('Notification' in window)) {
        return;
    }

    if (Notification.permission === 'granted') {
        new Notification(title, {
            body: body,
            icon: '/favicon.ico',
            tag: 'chat-notification'
        });
    } else if (Notification.permission !== 'denied') {
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                new Notification(title, {
                    body: body,
                    icon: '/favicon.ico',
                    tag: 'chat-notification'
                });
            }
        });
    }
}


// ==================== MODAL DE CHAT ====================
function openChatModal(chatId, productoNombre, otroUsuario) {
    // Cerrar lista de chats
    const chatsList = document.getElementById('chatsList');
    if (chatsList) {
        chatsList.classList.remove('active');
    }

    // Si ya hay un modal abierto, cerrarlo
    if (currentChatModal) {
        closeChatModal();
    }

    // Crear modal
    const modal = document.createElement('div');
    modal.className = 'chat-modal active';
    modal.id = 'chatModal';

    modal.innerHTML = `
        <div class="chat-modal-content">
            <div class="chat-modal-header">
                <h3>${escapeHtml(productoNombre)}</h3>
                <button class="chat-modal-close" onclick="closeChatModal()">×</button>
            </div>
            <div class="chat-modal-body">
                <div class="chat-modal-messages" id="chatModalMessages"></div>
                <div class="chat-modal-input">
                    <form id="chatModalForm" onsubmit="event.preventDefault(); sendModalMessage(${chatId})">
                        <textarea id="chatModalInput" placeholder="Escribe un mensaje..." required rows="2"></textarea>
                        <button type="submit" class="btn-primary">Enviar</button>
                    </form>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    currentChatModal = modal;

    // Inicializar variables antes de cargar mensajes
    window.currentModalChatId = chatId;
    window.currentModalLastMessageId = 0;

    // Cargar todos los mensajes al abrir el modal
    loadAllModalMessages(chatId);

    // Inicializar polling para este chat
    if (chatPollingInterval) {
        clearInterval(chatPollingInterval);
    }

    // Polling para mensajes del modal (solo nuevos mensajes)
    chatPollingInterval = setInterval(() => {
        if (window.currentModalChatId === chatId) {
            loadModalMessages(chatId, true);
            // Actualizar notificaciones mientras el modal está abierto (silencioso)
            loadNotifications(true);
        }
    }, 2000);

    // Enviar con Enter
    const textarea = document.getElementById('chatModalInput');
    if (textarea) {
        textarea.focus();
        textarea.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendModalMessage(chatId);
            }
        });
    }

    // Cerrar al hacer clic fuera
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeChatModal();
        }
    });
}

function closeChatModal() {
    if (currentChatModal) {
        currentChatModal.remove();
        currentChatModal = null;
        const closedChatId = window.currentModalChatId;
        window.currentModalChatId = null;
        window.currentModalLastMessageId = null;

        if (chatPollingInterval) {
            clearInterval(chatPollingInterval);
            chatPollingInterval = null;
        }

        // Recargar notificaciones al cerrar el modal
        setTimeout(() => {
            loadNotifications();
        }, 500);
    }
}

function loadModalMessages(chatId, silent = false) {
    const lastId = window.currentModalLastMessageId || 0;

    fetch(`api/get_messages.php?chat_id=${chatId}&last_id=${lastId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.messages && data.messages.length > 0) {
                const messagesContainer = document.getElementById('chatModalMessages');
                if (messagesContainer) {
                    data.messages.forEach(message => {
                        addMessageToModal(message, messagesContainer);
                        window.currentModalLastMessageId = Math.max(window.currentModalLastMessageId || 0, message.id);
                    });
                    scrollModalToBottom();
                    // Forzar actualización de burbuja de notificaciones
                    loadNotifications(true);
                }
            } else if (!silent) {
                // Cargar todos los mensajes la primera vez
                loadAllModalMessages(chatId);
            }
        })
        .catch(error => {
            console.error('Error al cargar mensajes del modal:', error);
        });
}

function loadAllModalMessages(chatId) {
    fetch(`api/get_messages.php?chat_id=${chatId}&last_id=0`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.messages) {
                const messagesContainer = document.getElementById('chatModalMessages');
                if (messagesContainer) {
                    messagesContainer.innerHTML = '';
                    data.messages.forEach(message => {
                        addMessageToModal(message, messagesContainer);
                        window.currentModalLastMessageId = Math.max(window.currentModalLastMessageId || 0, message.id);
                    });
                    scrollModalToBottom();
                    // Forzar actualización de burbuja de notificaciones
                    loadNotifications(true);
                }
            }
        })
        .catch(error => {
            console.error('Error al cargar todos los mensajes:', error);
        });
}

function addMessageToModal(message, container) {
    if (document.getElementById(`modal-message-${message.id}`)) {
        return;
    }

    const messageDiv = document.createElement('div');
    messageDiv.id = `modal-message-${message.id}`;
    messageDiv.className = `message ${message.es_mio == 1 ? 'message-sent' : 'message-received'}`;

    const messageText = document.createElement('p');
    messageText.innerHTML = message.mensaje.replace(/\n/g, '<br>');

    const messageTime = document.createElement('span');
    messageTime.className = 'message-time';
    messageTime.textContent = formatMessageTime(message.fecha_registro);

    messageDiv.appendChild(messageText);
    messageDiv.appendChild(messageTime);
    container.appendChild(messageDiv);
}

function sendModalMessage(chatId) {
    const textarea = document.getElementById('chatModalInput');
    if (!textarea || !textarea.value.trim()) return;

    const messageText = textarea.value;
    textarea.value = '';
    textarea.disabled = true;

    sendMessage(chatId, messageText, () => {
        // Callback después de enviar
        textarea.disabled = false;
        textarea.focus();
        // Recargar mensajes
        loadModalMessages(chatId);
        // Recargar notificaciones
        loadNotifications();
    });
}

function scrollModalToBottom() {
    const messagesContainer = document.getElementById('chatModalMessages');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
}

// ==================== INICIALIZACIÓN ====================
document.addEventListener('DOMContentLoaded', function () {
    // Inicializar tema
    initTheme();

    // Inicializar menú hamburguesa
    initHamburgerMenu();

    // Toggle de tema
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }

    // Sistema de notificaciones
    initNotifications();

    // Toggle lista de chats
    const notificationIcon = document.getElementById('notificationIcon');
    if (notificationIcon) {
        notificationIcon.addEventListener('click', function (e) {
            e.stopPropagation();
            const chatsList = document.getElementById('chatsList');
            if (chatsList) {
                chatsList.classList.toggle('active');
                if (chatsList.classList.contains('active')) {
                    loadNotifications();
                }
            }
        });

        // Cerrar lista al hacer clic fuera
        document.addEventListener('click', function (e) {
            const chatsList = document.getElementById('chatsList');
            if (chatsList && !chatsList.contains(e.target) && !notificationIcon.contains(e.target)) {
                chatsList.classList.remove('active');
            }
        });
    }

    // Solicitar permiso para notificaciones del navegador
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }

    // Chat en tiempo real
    const chatId = window.chatId || getUrlParameter('id');
    if (chatId && document.getElementById('chatMessages')) {
        // Obtener el último ID de mensaje
        lastMessageId = window.lastMessageId || 0;
        if (lastMessageId === 0) {
            const messages = document.querySelectorAll('.message');
            if (messages.length > 0) {
                const lastMessage = messages[messages.length - 1];
                const lastId = lastMessage.id.replace('message-', '');
                lastMessageId = parseInt(lastId) || 0;
            }
        }
        initChatRealTime(chatId);
    }

    // Auto-scroll en chat
    scrollChatToBottom();

    // Formulario de mensaje mejorado con AJAX
    const messageForm = document.getElementById('messageForm');
    if (messageForm) {
        messageForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const textarea = document.getElementById('messageInput');
            const chatId = window.chatId || getUrlParameter('id');
            if (textarea && chatId && textarea.value.trim()) {
                sendMessage(chatId, textarea.value);
            }
        });

        // Enviar con Enter (Shift+Enter para nueva línea)
        const textarea = document.getElementById('messageInput');
        if (textarea) {
            textarea.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    messageForm.dispatchEvent(new Event('submit'));
                }
            });
        }
    }
    // Solo validar formularios que no sean chat
    const forms = document.querySelectorAll('form:not(#messageForm)');
    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#e74c3c';
                } else {
                    field.style.borderColor = '';
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Por favor completa todos los campos requeridos');
            }
        });
    });

    // Preview de imagen antes de subir (excluyendo el avatar que tiene su propio preview)
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]:not(#imagen):not(.avatar-input)');
    imageInputs.forEach(input => {
        input.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    let preview = input.parentElement.querySelector('.image-preview');
                    if (!preview) {
                        preview = document.createElement('img');
                        preview.className = 'image-preview';
                        preview.style.maxWidth = '200px';
                        preview.style.height = 'auto';
                        preview.style.marginTop = '0.5rem';
                        preview.style.borderRadius = '4px';
                        input.parentElement.appendChild(preview);
                    }
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    });

    // Formateo automático de precios
    const priceInputs = document.querySelectorAll('input[type="number"][name="precio"]');
    priceInputs.forEach(input => {
        input.addEventListener('input', function (e) {
            let value = e.target.value;
            if (value < 0) {
                e.target.value = 0;
            }
        });
    });

    // Navegación de settings
    const settingsLinks = document.querySelectorAll('.settings-sidebar a');
    settingsLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            if (!this.hasAttribute('data-section')) {
                return;
            }
            e.preventDefault();
            const target = this.getAttribute('data-section');
            if (target) {
                // Ocultar todas las secciones
                document.querySelectorAll('.settings-section').forEach(section => {
                    section.classList.remove('active');
                });
                // Remover active de todos los links
                settingsLinks.forEach(l => l.classList.remove('active'));
                // Mostrar sección seleccionada
                document.getElementById(target).classList.add('active');
                this.classList.add('active');
            }
        });
    });
});

// Función auxiliar para obtener parámetros de URL
function getUrlParameter(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

document.getElementById("avatarInput").addEventListener("change", function () {
    const file = this.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function (e) {
        // Actualizar foto visible
        document.getElementById("avatarPhoto").src = e.target.result;

        // Actualizar foto del header
        const headerAvatar = document.getElementById("headerAvatar");
        if (headerAvatar) headerAvatar.src = e.target.result;
    };

    reader.readAsDataURL(file);
});

document.addEventListener('DOMContentLoaded', () => {
    // --- LÓGICA DE AVATAR PRINCIPAL ---


    if (avatarEditButton && avatarInputHidden && avatarUploadForm) {
        document.addEventListener('DOMContentLoaded', () => {

            const avatarEditButton = document.getElementById('avatarEditButton'); // Botón lápiz
            const avatarInputHidden = document.getElementById('avatarInputHidden'); // Input oculto
            const avatarUploadForm = document.getElementById('avatarUploadForm'); // Formulario
            const avatarPhoto = document.getElementById('avatarPhoto'); // Imagen en perfil
            const headerAvatar = document.getElementById('headerAvatar'); // Imagen en header (opcional)
            const deleteAvatarBtn = document.getElementById('deleteAvatarBtn'); // Botón de eliminar (opcional)

            if (avatarEditButton && avatarInputHidden && avatarUploadForm) {
                // Abrir selector al hacer clic en lápiz
                avatarEditButton.addEventListener('click', () => {
                    avatarInputHidden.click();
                });

                // Previsualizar y enviar automáticamente
                avatarInputHidden.addEventListener('change', function () {
                    if (this.files.length > 0) {
                        const file = this.files[0];
                        const reader = new FileReader();

                        reader.onload = (e) => {
                            // Actualizar avatar en perfil
                            avatarPhoto.src = e.target.result;

                            // Actualizar avatar en header si existe
                            if (headerAvatar) headerAvatar.src = e.target.result;
                        };

                        reader.readAsDataURL(file);

                        // Enviar formulario al servidor
                        avatarUploadForm.submit();
                    }
                });
            }

            // Lógica para eliminar avatar
            if (deleteAvatarBtn) {
                deleteAvatarBtn.addEventListener('click', () => {
                    if (confirm("¿Deseas eliminar tu foto de perfil?")) {
                        window.location.href = 'perfil.php?section=avatar&action=delete';
                    }
                });
            }

        });
    }
});
document.addEventListener('DOMContentLoaded', () => {
    const avatarEditBtn = document.getElementById('avatarEditButton');
    const avatarInput = document.getElementById('avatarInputHidden');
    const avatarForm = document.getElementById('avatarUploadForm');
    const avatarPhoto = document.getElementById('avatarPhoto');

    // Clic en lápiz abre selector de archivos
    avatarEditBtn.addEventListener('click', () => {
        avatarInput.click();
    });

    // Cuando se selecciona un archivo, previsualiza y envía
    avatarInput.addEventListener('change', () => {
        if (avatarInput.files && avatarInput.files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => {
                avatarPhoto.src = e.target.result; // Previsualización
            };
            reader.readAsDataURL(avatarInput.files[0]);

            // Enviar formulario automáticamente
            avatarForm.submit();
        }
    });
});
document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('toggleFavoriteBtn');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', async () => {
            const productId = toggleBtn.dataset.productId;
            const isCurrentlyFavorite = toggleBtn.dataset.isFavorite === 'true';

            const data = {
                product_id: productId,
                is_favorite: isCurrentlyFavorite ? 'true' : 'false'
            };

            try {
                // Envía la solicitud al nuevo archivo toggle_favorito.php
                const response = await fetch('toggle_favorito.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    const newStatus = result.is_favorite;

                    // 1. Actualiza el atributo data-is-favorite
                    toggleBtn.dataset.isFavorite = newStatus ? 'true' : 'false';

                    // 2. Actualiza la apariencia y el texto
                    if (newStatus) {
                        toggleBtn.classList.add('active');
                        toggleBtn.innerHTML = '❤️ Favorito';
                        toggleBtn.title = 'Quitar de Favoritos';
                    } else {
                        toggleBtn.classList.remove('active');
                        toggleBtn.innerHTML = '🤍 Añadir a Favoritos';
                        toggleBtn.title = 'Añadir a Favoritos';
                    }
                } else {
                    console.error('Error al actualizar favoritos:', result.message);
                    alert('Error al actualizar favoritos.');
                }

            } catch (error) {
                console.error('Error de conexión o servidor:', error);
                alert('Hubo un problema al conectar con el servidor.');
            }
        });
    }
});

// ==================== MÚLTIPLES IMÁGENES PREVIEW ====================
function initMultipleImagesUpload() {
    const input = document.getElementById('imagenes');
    const previewContainer = document.getElementById('previsualizaciones');
    const dropArea = document.getElementById('dropArea');

    if (!input || !previewContainer) return;

    let selectedFiles = [];

    input.addEventListener('change', function (e) {
        const files = Array.from(e.target.files);

        // Limitar a los primeros 5 archivos si seleccionan más
        if (files.length > 5) {
            alert('Solo puedes subir un máximo de 5 imágenes. Se tomarán las primeras 5.');
            selectedFiles = files.slice(0, 5);
        } else {
            selectedFiles = files;
        }

        renderPreviews();
    });

    function renderPreviews() {
        previewContainer.innerHTML = '';

        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function (e) {
                const div = document.createElement('div');
                div.className = 'prev-container';
                div.innerHTML = `
                    <img src="${e.target.result}">
                    <button type="button" class="btn-remove-prev" data-index="${index}">×</button>
                `;
                previewContainer.appendChild(div);

                div.querySelector('.btn-remove-prev').addEventListener('click', () => {
                    removeFile(index);
                });
            };
            reader.readAsDataURL(file);
        });
    }

    function removeFile(index) {
        selectedFiles.splice(index, 1);

        // Actualizar el input real (Esto es complejo porque FileList es readonly)
        // Por simplificación en este paso, si borran, el input mantendrá los originales 
        // pero el visual ayudará al usuario. Lo ideal es usar DataTransfer.
        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        input.files = dt.files;

        renderPreviews();
    }

    // Drag and Drop básico
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.add('drag-over'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.add('drag-over'), false);
    });

    dropArea.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = Array.from(dt.files);

        if (files.length > 5) {
            alert('Solo puedes subir un máximo de 5 imágenes.');
            input.files = dt.files; // El navegador filtrará o podemos usar DataTransfer
        }

        input.files = dt.files;
        input.dispatchEvent(new Event('change'));
    }, false);
}

// ==================== GALERÍA DE PRODUCTO ====================
function changeMainImage(src, thumbnail) {
    const mainImg = document.getElementById('mainProductImage');
    if (mainImg) {
        mainImg.src = src;

        // Actualizar clase activa en miniaturas
        document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
        thumbnail.classList.add('active');
    }
}

// Inicializar en DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    initMultipleImagesUpload();
});
