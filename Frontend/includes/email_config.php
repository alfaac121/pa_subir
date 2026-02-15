<?php
/**
 * Configuración de Email - Tu Mercado SENA
 * 
 * IMPORTANTE: Configura estos valores con tus credenciales SMTP
 * Puedes usar Gmail, Outlook, o cualquier servicio SMTP
 */

// =====================================
// CONFIGURACIÓN SMTP
// =====================================

// Servidor SMTP (ejemplos: smtp.gmail.com, smtp.office365.com, mail.tudominio.com)
define('SMTP_HOST', 'smtp.gmail.com');

// Puerto SMTP (587 para TLS, 465 para SSL, 25 sin encriptación)
define('SMTP_PORT', 587);

// Tipo de encriptación: 'tls', 'ssl', o '' (ninguna)
define('SMTP_SECURE', 'tls');

// Email desde donde se enviarán los correos
define('SMTP_EMAIL', 'tu_correo@gmail.com'); // ← CAMBIAR POR TU CORREO

// Contraseña o App Password (para Gmail necesitas App Password)
define('SMTP_PASSWORD', 'tu_app_password'); // ← CAMBIAR POR TU CONTRASEÑA

// Nombre que aparecerá como remitente
define('SMTP_FROM_NAME', 'Tu Mercado SENA');

// =====================================
// CONFIGURACIÓN DE LA APLICACIÓN
// =====================================

// URL base de la aplicación (sin / al final)
define('APP_URL', 'http://localhost/Nueva_carpeta/tu_mercado_sena/Frontend');

// Tiempo de expiración del código de recuperación (en minutos)
define('RECOVERY_CODE_EXPIRY', 15);

// =====================================
// INSTRUCCIONES PARA GMAIL
// =====================================
/*
Para usar Gmail como SMTP:

1. Ve a tu cuenta de Google: https://myaccount.google.com/
2. Seguridad → Verificación en 2 pasos (actívala si no está)
3. Busca "Contraseñas de aplicaciones"
4. Crea una nueva contraseña para "Correo" en "Otro (nombre personalizado)"
5. Usa esa contraseña de 16 caracteres como SMTP_PASSWORD

Ejemplo:
define('SMTP_EMAIL', 'tumercadosena@gmail.com');
define('SMTP_PASSWORD', 'abcd efgh ijkl mnop'); // Sin espacios
*/

// =====================================
// INSTRUCCIONES PARA OUTLOOK/HOTMAIL
// =====================================
/*
define('SMTP_HOST', 'smtp.office365.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_EMAIL', 'tu_correo@outlook.com');
define('SMTP_PASSWORD', 'tu_contraseña');
*/
?>
