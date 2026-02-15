<?php
/**
 * Funciones de Email - Tu Mercado SENA
 * 
 * Este archivo contiene funciones para enviar correos electrónicos
 * usando PHPMailer o la función mail() de PHP como fallback.
 */

require_once __DIR__ . '/email_config.php';

// Intentar cargar PHPMailer si está disponible
$phpmailerPath = __DIR__ . '/PHPMailer/src/PHPMailer.php';
$usePhpMailer = file_exists($phpmailerPath);

if ($usePhpMailer) {
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
}

// Las declaraciones use deben estar en el nivel superior del archivo
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

/**
 * Envía un correo electrónico
 * 
 * @param string $to Email del destinatario
 * @param string $subject Asunto del correo
 * @param string $htmlBody Cuerpo HTML del correo
 * @param string $textBody Cuerpo de texto plano (opcional)
 * @return array ['success' => bool, 'message' => string]
 */
function sendEmail($to, $subject, $htmlBody, $textBody = '') {
    global $usePhpMailer;
    
    // Si no hay texto plano, crear uno simple desde el HTML
    if (empty($textBody)) {
        $textBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
    }
    
    if ($usePhpMailer) {
        return sendWithPhpMailer($to, $subject, $htmlBody, $textBody);
    } else {
        return sendWithMailFunction($to, $subject, $htmlBody, $textBody);
    }
}

/**
 * Envía correo usando PHPMailer
 */
function sendWithPhpMailer($to, $subject, $htmlBody, $textBody) {
    try {
        $mail = new PHPMailer(true);
        
        // Configuración del servidor
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_EMAIL;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // Remitente y destinatario
        $mail->setFrom(SMTP_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody;
        
        $mail->send();
        return ['success' => true, 'message' => 'Correo enviado correctamente'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al enviar: ' . $mail->ErrorInfo];
    }
}

/**
 * Envía correo usando la función mail() de PHP (fallback)
 */
function sendWithMailFunction($to, $subject, $htmlBody, $textBody) {
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . SMTP_FROM_NAME . ' <' . SMTP_EMAIL . '>',
        'Reply-To: ' . SMTP_EMAIL,
        'X-Mailer: PHP/' . phpversion()
    ];
    
    $success = @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    
    if ($success) {
        return ['success' => true, 'message' => 'Correo enviado correctamente'];
    } else {
        return ['success' => false, 'message' => 'Error al enviar el correo. Verifica la configuración del servidor.'];
    }
}

/**
 * Genera un código de recuperación de 6 dígitos
 */
function generateRecoveryCode() {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Envía el correo de recuperación de contraseña
 */
function sendPasswordRecoveryEmail($to, $code, $userName = 'Usuario') {
    $subject = '🔐 Código de recuperación - Tu Mercado SENA';
    
    $htmlBody = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
            .container { max-width: 500px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #1a5f2a, #2d7a3e); color: white; padding: 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; }
            .content { padding: 30px; }
            .code-box { background: #f8f9fa; border: 2px dashed #1a5f2a; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
            .code { font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #1a5f2a; }
            .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin-top: 20px; font-size: 13px; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🛒 Tu Mercado SENA</h1>
            </div>
            <div class="content">
                <p>Hola <strong>' . htmlspecialchars($userName) . '</strong>,</p>
                <p>Recibimos una solicitud para restablecer tu contraseña. Usa el siguiente código:</p>
                
                <div class="code-box">
                    <div class="code">' . $code . '</div>
                </div>
                
                <p>Este código expira en <strong>' . RECOVERY_CODE_EXPIRY . ' minutos</strong>.</p>
                
                <div class="warning">
                    ⚠️ Si no solicitaste este cambio, ignora este correo. Tu cuenta está segura.
                </div>
            </div>
            <div class="footer">
                <p>© ' . date('Y') . ' Tu Mercado SENA - Marketplace de la comunidad SENA</p>
                <p>Este es un correo automático, no respondas a este mensaje.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return sendEmail($to, $subject, $htmlBody);
}

/**
 * Envía notificación de nuevo mensaje en chat
 */
function sendNewMessageNotification($to, $senderName, $productName, $chatUrl) {
    $subject = '💬 Nuevo mensaje de ' . $senderName . ' - Tu Mercado SENA';
    
    $htmlBody = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
            .container { max-width: 500px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #1a5f2a, #2d7a3e); color: white; padding: 20px; text-align: center; }
            .content { padding: 30px; }
            .btn { display: inline-block; background: #1a5f2a; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin-top: 15px; }
            .footer { background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>💬 Nuevo Mensaje</h1>
            </div>
            <div class="content">
                <p><strong>' . htmlspecialchars($senderName) . '</strong> te ha enviado un mensaje sobre:</p>
                <p style="font-size: 18px; color: #1a5f2a;"><strong>' . htmlspecialchars($productName) . '</strong></p>
                <a href="' . $chatUrl . '" class="btn">Ver Mensaje</a>
            </div>
            <div class="footer">
                <p>© ' . date('Y') . ' Tu Mercado SENA</p>
            </div>
        </div>
    </body>
    </html>';
    
    return sendEmail($to, $subject, $htmlBody);
}

/**
 * Envía notificación de solicitud de devolución
 */
function sendRefundRequestNotification($to, $buyerName, $productName, $reason) {
    $subject = '🔄 Solicitud de devolución - Tu Mercado SENA';
    
    $htmlBody = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
            .container { max-width: 500px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; }
            .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
            .content { padding: 30px; }
            .reason-box { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0; }
            .footer { background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🔄 Solicitud de Devolución</h1>
            </div>
            <div class="content">
                <p><strong>' . htmlspecialchars($buyerName) . '</strong> ha solicitado una devolución para:</p>
                <p style="font-size: 18px; color: #1a5f2a;"><strong>' . htmlspecialchars($productName) . '</strong></p>
                
                <div class="reason-box">
                    <strong>Motivo:</strong><br>
                    ' . htmlspecialchars($reason) . '
                </div>
                
                <p>Por favor, revisa la solicitud y responde lo antes posible.</p>
            </div>
            <div class="footer">
                <p>© ' . date('Y') . ' Tu Mercado SENA</p>
            </div>
        </div>
    </body>
    </html>';
    
    return sendEmail($to, $subject, $htmlBody);
}
?>
