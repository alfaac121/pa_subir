<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Políticas - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
</head>
<body>
    <?php if (isLoggedIn()): ?>
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/bottom_nav.php'; ?>
    <?php endif; ?>

    <main class="main">
        <div class="container" style="max-width: 900px;">
            <div class="page-header" style="text-align: center; margin-bottom: 2rem;">
                <h1>📜 Políticas y Privacidad</h1>
                <p>Conoce las reglas de nuestra comunidad</p>
            </div>
            
            <div class="form-container" style="margin-bottom: 2rem;">
                <h2>🤝 Código de Comportamiento</h2>
                <p>Tu Mercado SENA es una comunidad exclusiva para aprendices, instructores y personal del SENA.</p>
                <h3>Se espera de los usuarios:</h3>
                <ul>
                    <li><strong>Respeto mutuo:</strong> Tratar a todos con cortesía.</li>
                    <li><strong>Honestidad:</strong> Describir productos de manera precisa.</li>
                    <li><strong>Responsabilidad:</strong> Cumplir con los compromisos.</li>
                </ul>
                <h3>⚠️ Conductas Prohibidas</h3>
                <ul>
                    <li>Acoso, discriminación o bullying</li>
                    <li>Contenido ofensivo, obsceno o violento</li>
                    <li>Suplantación de identidad</li>
                    <li>Spam o publicidad no relacionada</li>
                    <li>Intentos de fraude o estafa</li>
                    <li>Venta de productos ilegales</li>
                </ul>
            </div>
            
            <div class="form-container" style="margin-bottom: 2rem;">
                <h2>🔐 Política de Privacidad</h2>
                <h3>Datos que Recopilamos</h3>
                <ul>
                    <li><strong>Cuenta:</strong> Correo institucional, contraseña encriptada</li>
                    <li><strong>Perfil:</strong> Foto, descripción, enlaces (opcional)</li>
                    <li><strong>Actividad:</strong> Productos publicados, chats, transacciones</li>
                </ul>
                <h3>Seguridad</h3>
                <p>Las contraseñas se almacenan usando encriptación bcrypt. Nunca compartimos información personal sin consentimiento.</p>
            </div>
            
            <div class="form-container" style="margin-bottom: 2rem;">
                <h2>💰 Transacciones</h2>
                <p>Tu Mercado SENA facilita el contacto entre usuarios, pero las transacciones son responsabilidad directa de los involucrados.</p>
                <h3>Recomendaciones</h3>
                <ul>
                    <li>Realizar intercambios en lugares públicos del SENA</li>
                    <li>Verificar el producto antes de pagar</li>
                    <li>Conservar evidencia de comunicaciones</li>
                    <li>Reportar comportamiento sospechoso</li>
                </ul>
            </div>
            
            <div class="form-container" style="margin-bottom: 2rem;">
                <h2>🚫 Productos Prohibidos</h2>
                <ul>
                    <li>Alcohol, tabaco y sustancias controladas</li>
                    <li>Armas o elementos peligrosos</li>
                    <li>Material pirateado</li>
                    <li>Productos robados</li>
                    <li>Contenido para adultos</li>
                </ul>
            </div>
            
            <div class="form-container">
                <h2>⚖️ Sanciones</h2>
                <ul>
                    <li><strong>Advertencia:</strong> Infracciones menores</li>
                    <li><strong>Suspensión temporal:</strong> 1 a 30 días</li>
                    <li><strong>Suspensión permanente:</strong> Infracciones graves</li>
                </ul>
            </div>
            
            <p style="text-align: center; color: var(--color-text-light); margin-top: 2rem;">
                Última actualización: Febrero 2026
            </p>
            
            <div style="text-align: center; margin-top: 2rem;">
                <a href="<?= isLoggedIn() ? 'index.php' : 'welcome.php' ?>" class="btn-primary">← Volver</a>
                <a href="contacto.php" class="btn-secondary">Contacto</a>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Tu Mercado SENA. Todos los derechos reservados.</p>
        </div>
    </footer>
    <script src="script.js"></script>
</body>
</html>
