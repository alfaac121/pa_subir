# 📋 ESTADO DE REQUERIMIENTOS - Tu Mercado SENA

**Última actualización:** 2026-02-05 23:51  
**Proyecto:** Tu Mercado SENA - Marketplace para la comunidad SENA

---

## 📊 RESUMEN GENERAL

| Estado | Cantidad | Porcentaje |
|--------|----------|------------|
| ✅ **Completos** | 17 | 77% |
| ⚠️ **Parciales** | 6 | 27% |
| ❌ **Pendientes** | 4 | 18% |

---

## ✅ REQUERIMIENTOS COMPLETADOS

| ID | Descripción | Archivo(s) | Fecha |
|----|-------------|------------|-------|
| RF03-010 | Búsqueda avanzada con filtros (precio, integridad, categoría) | `api/productos.php` | 2026-02-05 |
| RF03-011 | Ver productos vendidos y calificación | `historial.php` | 2026-02-05 |
| RF03-012 | Ver comentarios de compradores | `historial.php` | 2026-02-05 |
| RF04-009 | Adjuntar y enviar imágenes en chat | `api/send_chat_image.php`, `script.js` | 2026-02-05 |
| RF04-010 | Eliminar chats de lista personal | `api/eliminar_chat.php` | 2026-02-05 |
| RF04-011 | Registrar finalización de compraventa | `api/finalizar_venta.php` | 2026-02-05 |
| RF06-007 | Enviar PQRS desde la aplicación | `pqrs.php` | 2026-02-05 |
| RF08-009 | Información de contacto institucional | `contacto.php` | Existente |
| RF08-010 | Políticas de comportamiento y privacidad | `politicas.php` | Existente |
| RF09-001 | Bloquear usuarios | `api/toggle_bloqueo.php`, `producto.php`, `script.js` | 2026-02-05 |
| RF09-002 | Lista de usuarios bloqueados | `bloqueados.php` | 2026-02-05 |
| RF01-003 | Recuperar contraseña con código | `forgot_password.php`, `email_functions.php` | 2026-02-09 |
| RF03-017 | Gestionar devoluciones | `historial.php`, `api/solicitar_devolucion.php`, `api/responder_devolucion.php` | 2026-02-09 |
| RF05-003 | Notificaciones por correo (lógica) | `includes/notification_system.php` | 2026-02-09 |
| RF05-004 | Notificaciones push PWA | `js/push_notifications.js`, `sw.js`, `api/save_push_subscription.php` | 2026-02-09 |
| RF05-005 | Mensajes emergentes ante eventos | `includes/notification_system.php`, `script.js` | 2026-02-09 |
| RF05-006 | Correos automáticos ante eventos | `includes/notification_system.php` | 2026-02-09 |

---

## ⚠️ REQUERIMIENTOS PARCIALES

| ID | Descripción | Estado Actual | Falta |
|----|-------------|---------------|-------|
| RF02-011 | Activar/desactivar visibilidad de perfil | Campo en BD existe | Botón/switch en UI |
| RF03-007 | Establecer producto como invisible | BD lo soporta (estado_id) | Botón en página de producto |
| RF08-011 | Activar/desactivar modo uso de datos | Campo en BD | UI para activar/desactivar |
| RF09-006 | Indicador "recientemente conectado" | CSS implementado | Lógica JS para mostrar estado |
| RNF02-004 | Contraseñas fuertes | Valida 8+ caracteres | Validar mayúsculas, minúsculas, números |
| RNF05-001 | Límites de caracteres | Algunos campos tienen límite | Revisar todos los campos |

---

## ❌ REQUERIMIENTOS PENDIENTES

| ID | Descripción | Prioridad | Notas |
|----|-------------|-----------|-------|
| RNF04-004 | Cambios de perfil cada 24h | Baja | Restricción temporal en edición |
| RNF05-004 | Validar links de redes sociales | Baja | Regex para URLs válidas |
| RNF06-001 | Imágenes convertidas a JPG con límites | Media | Procesamiento de imágenes en PHP |
| RNF07-001 | Chats cargan 16 mensajes por vez | Baja | Paginación en chat |

---

## 📁 ARCHIVOS CREADOS EN SESIÓN 2026-02-09

### Sistema de Notificaciones:
- `Frontend/includes/notification_system.php` - Sistema centralizado de notificaciones automáticas
- `Frontend/js/push_notifications.js` - Manejo de notificaciones push PWA
- `Frontend/api/save_push_subscription.php` - API para guardar suscripciones push

### Archivos Modificados:
- `Frontend/api/send_message.php` - Integrado sistema de notificaciones
- `Frontend/api/finalizar_venta.php` - Integrado sistema de notificaciones
- `Frontend/api/solicitar_devolucion.php` - Integrado sistema de notificaciones
- `Frontend/api/responder_devolucion.php` - Integrado sistema de notificaciones
- `Frontend/script.js` - Integrado notificaciones push
- `Frontend/index.php` - Agregado script de push notifications

## 📁 ARCHIVOS CREADOS EN SESIÓN 2026-02-05

### APIs Nuevas:
- `Frontend/api/toggle_bloqueo.php` - Bloquear/desbloquear usuarios
- `Frontend/api/finalizar_venta.php` - Marcar transacción como finalizada
- `Frontend/api/eliminar_chat.php` - Eliminar chat de la lista
- `Frontend/api/send_chat_image.php` - Enviar imágenes en chat
- `Frontend/api/reportar_producto.php` - Reportar productos

### Páginas Nuevas:
- `Frontend/bloqueados.php` - Lista de usuarios bloqueados
- `Frontend/historial.php` - Historial de ventas y compras
- `Frontend/pqrs.php` - Sistema de PQRS

### Archivos Modificados:
- `Frontend/api/productos.php` - Filtros avanzados + exclusión de bloqueados y propios
- `Frontend/producto.php` - Botones Bloquear y Reportar + Modal de reporte
- `Frontend/script.js` - Funciones de bloqueo, reporte, toast notifications
- `Frontend/styles.css` - Estilos para nuevas funcionalidades

---

## 🗄️ ESTRUCTURA DE BASE DE DATOS RELEVANTE

### Tablas utilizadas:
- `bloqueados` - Relaciones de bloqueo entre usuarios
- `denuncias` - Reportes de productos/usuarios
- `pqrs` - Solicitudes PQRS
- `chats` - Conversaciones con estado de transacción
- `motivos` - Tipos de PQRS y denuncias
- `estados` - Estados para productos, chats, etc.

---

## 🔜 PRÓXIMOS PASOS SUGERIDOS

1. **Completar parciales:**
   - [ ] RF09-006 - Indicador "recientemente conectado" (agregar JS)
   - [ ] RF03-007 - Botón para ocultar producto
   - [ ] RNF02-004 - Validación de contraseña fuerte

2. **Panel de Administración:**
   - [ ] Vista de denuncias pendientes
   - [ ] Gestión de usuarios bloqueados
   - [ ] Moderación de productos

3. **Mejoras de UX:**
   - [ ] Enlace a PQRS en menú/footer
   - [ ] Confirmaciones visuales mejoradas
   - [ ] Paginación de mensajes en chat

---

## 📝 NOTAS ADICIONALES

### Filtros de productos implementados:
- Categoría
- Búsqueda por texto
- Ordenamiento (más nuevo, precio, disponibilidad)
- Condición/Integridad (nuevo, usado, con fallas)
- Precio mínimo
- Precio máximo
- **Exclusión automática:** Productos propios y de usuarios bloqueados

### Sistema de bloqueo:
- Usuario bloqueado no aparece en resultados de productos
- Se puede desbloquear desde `bloqueados.php`
- No afecta chats existentes

### Sistema de reportes:
- 5 motivos de reporte disponibles
- Se guarda en tabla `denuncias`
- Requiere panel admin para gestionar

---

*Documento generado automáticamente. Actualizar manualmente según avances.*
