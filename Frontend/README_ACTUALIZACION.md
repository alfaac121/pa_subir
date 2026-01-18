# 🚀 Reporte de Actualizaciones - Tu Mercado SENA
**Fecha:** 17 de Enero, 2026
**Versión de Mejora:** 1.3.0

Este documento detalla todas las modificaciones, correcciones de errores y optimizaciones realizadas recientemente en el ecosistema de **Tu Mercado SENA** (Frontend PHP y Backend Laravel).

---

## �️ 5. Avatar Visible en Todas las Páginas (NUEVO - v1.3.0)
Se implementó la visualización consistente de la foto de perfil del usuario en el header/navegación de todas las páginas de la aplicación.

### Problema Detectado:
- La foto de perfil solo aparecía en algunas páginas y usaba rutas inconsistentes.
- Algunas páginas usaban `$user['imagen']` directamente sin la función `getAvatarUrl()`.
- No existían estilos CSS unificados para el avatar en el header.

### Solución Implementada:

*   **Estandarización de Rutas:** Se actualizaron **8 archivos PHP** para usar la función `getAvatarUrl()`:
    *   `index.php` ✅ (ya funcionaba)
    *   `publicar.php` ✅ 
    *   `mis_productos.php` ✅
    *   `favoritos.php` ✅
    *   `producto.php` ✅
    *   `chat.php` ✅
    *   `editar_producto.php` ✅
    *   `perfil.php` ✅ (se agregó el avatar al header)
    *   `perfil_publico.php` ✅

*   **Clase CSS Unificada:** Se cambió la clase `user-avatar` a `avatar-header` en todas las páginas para mantener consistencia.

*   **Nuevos Estilos CSS:** Se agregaron estilos al final de `styles.css`:
    ```css
    .user-avatar-container    /* Contenedor flex para avatar + nombre */
    .avatar-header            /* Imagen circular 36x36px con borde y sombra */
    .user-name-footer         /* Nombre del usuario junto al avatar */
    .perfil-link              /* Enlace con hover effect */
    ```

*   **Diseño Responsivo:** En pantallas móviles (< 768px):
    - El nombre del usuario se oculta para ahorrar espacio
    - El avatar se reduce a 32x32px

---

## �🔐 1. Flujo de Registro y Seguridad
Se optimizó el proceso de creación de cuentas para garantizar una experiencia de usuario fluida y sin errores técnicos.

*   **Redirección Post-Verificación:** Se modificó `verificar_registro.php` para que, tras una validación exitosa del código de 6 dígitos, el usuario sea redirigido a `login.php?registered=1`.
*   **Mensajes de Feedback:** Implementación de bloques dinámicos en `login.php` para mostrar mensajes de éxito tras completar el registro.
*   **Control del Formulario:** Se eliminó el auto-envío del código de verificación al completar los 6 dígitos, permitiendo al usuario revisar el código antes de enviarlo manualmente.
*   **Backend Robustness:**
    *   Sincronización de llaves de respuesta API (`success` vs `status`) en `RegistroService.php`.
    *   Manejo de "Graceful Registration": Ahora el sistema permite reintentar la verificación incluso si el registro se interrumpe, evitando el error de "Correo ya registrado".
    *   Protección de Claims JWT: Se añadió verificación de nulidad en el modelo `Cuenta.php` para evitar errores 500 cuando el perfil aún no está vinculado.

## 💬 2. Sistema de Chat y Notificaciones
Se refinó la lógica de lectura y notificaciones para que sea precisa y en tiempo real.

*   **Burbuja de Notificaciones:** Modificación en `get_chats_notificaciones.php` para que el contador de mensajes no leídos se base en las banderas `visto_comprador` / `visto_vendedor`.
*   **Actualización Instantánea:**
    *   Se integró `loadNotifications(true)` en las funciones de apertura del modal de chat en `script.js`.
    *   El globo de notificaciones ahora desaparece o disminuye instantáneamente al abrir una conversación.
*   **Marcar como Leído:** El backend ahora actualiza automáticamente el estado de "visto" al solicitar los mensajes de un chat específico.

## 📸 3. Gestión de Imágenes de Perfil (Avatares)
Se corrigió el error que impedía visualizar las fotos de perfil en el Home e Index.

*   **Función Maestro `getAvatarUrl()`:** Creada en `config.php` para estandarizar la obtención de rutas de imágenes. Esta función maneja:
    *   Nombres de archivos simples (ej: `avatar_123.jpg`).
    *   Rutas completas (ej: `assets/images/avatars/avatar_123.jpg`).
    *   Avatares por defecto si el archivo no existe o el campo está vacío.
*   **Estandarización UI:** Actualización de las siguientes páginas para usar la nueva lógica de avatares:
    *   `index.php` (Header)
    *   `perfil.php` (Perfil de usuario)
    *   `perfil_publico.php` (Vista de vendedor)
    *   `chat.php` (Conversaciones)
    *   `favoritos.php` (Vendedores favoritos)

## 🛠️ 4. Mantenimiento de Base de Datos
*   **Script de Limpieza:** Creación de `Backend/API_Laravel/cleanup.php` para truncar tablas y permitir pruebas limpias del flujo de registro, productos y chats.
*   **Sincronización de Tiempo:** Ajuste de zonas horarias en `config.php` y la conexión MySQL para coincidir con la hora local de Bogotá (-05:00), asegurando que el "Tiempo Relativo" ("Hace 5 minutos") sea exacto.

---

## 📂 Archivos Principales Modificados

| Capa | Archivos Clave |
| :--- | :--- |
| **Frontend** | `config.php`, `index.php`, `script.js`, `perfil.php`, `verificar_registro.php`, `login.php`, `publicar.php`, `mis_productos.php`, `favoritos.php`, `producto.php`, `chat.php`, `editar_producto.php`, `perfil_publico.php`, `styles.css` |
| **Backend (API)** | `AuthController.php`, `RegistroService.php`, `AuthService.php`, `Cuenta.php` |
| **Database** | `cleanup.php`, `get_chats_notificaciones.php` |

---
**Desarrollado con 💚 por el equipo de Advanced Agentic Coding (Antigravity).**
