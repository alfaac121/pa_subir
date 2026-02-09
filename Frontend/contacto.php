<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacto Institucional - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
    <style>
        .contacto-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .contacto-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .contacto-header h1 {
            color: var(--color-primary);
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .contacto-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .contacto-card {
            background: var(--color-bg);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .contacto-card:hover {
            transform: translateY(-5px);
        }
        
        .contacto-card .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }
        
        .contacto-card h3 {
            color: var(--color-primary);
            margin-bottom: 0.75rem;
            font-size: 1.3rem;
        }
        
        .contacto-card p {
            color: var(--color-text-light);
            margin-bottom: 0.5rem;
        }
        
        .contacto-card a {
            color: var(--color-primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .contacto-card a:hover {
            text-decoration: underline;
        }
        
        .horarios-section {
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            border-radius: 16px;
            padding: 2.5rem;
            color: white;
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .horarios-section h2 {
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
        }
        
        .horarios-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }
        
        .horario-item {
            background: rgba(255,255,255,0.15);
            border-radius: 12px;
            padding: 1.5rem;
        }
        
        .horario-item h4 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        
        .responsables-section {
            margin-bottom: 3rem;
        }
        
        .responsables-section h2 {
            color: var(--color-primary);
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .responsable-card {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            background: var(--color-bg);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid var(--border-color);
        }
        
        .responsable-avatar {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            flex-shrink: 0;
        }
        
        .responsable-info h4 {
            color: var(--color-primary);
            margin-bottom: 0.25rem;
        }
        
        .responsable-info p {
            color: var(--color-text-light);
            font-size: 0.95rem;
        }
        
        .mapa-section {
            margin-bottom: 2rem;
        }
        
        .mapa-section h2 {
            color: var(--color-primary);
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .mapa-placeholder {
            background: var(--color-bg-secondary);
            border-radius: 16px;
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-text-light);
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <?php if (isLoggedIn()): ?>
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/bottom_nav.php'; ?>
    <?php endif; ?>

    <main class="main">
        <div class="container contacto-container">
            <div class="contacto-header">
                <h1>📞 Contacto Institucional</h1>
                <p>Estamos aquí para ayudarte. Contáctanos a través de cualquiera de nuestros canales.</p>
            </div>
            
            <div class="contacto-grid">
                <div class="contacto-card">
                    <span class="icon">📧</span>
                    <h3>Correo Electrónico</h3>
                    <p>Soporte técnico y consultas</p>
                    <a href="mailto:tumercado@sena.edu.co">tumercado@sena.edu.co</a>
                </div>
                
                <div class="contacto-card">
                    <span class="icon">📞</span>
                    <h3>Teléfono</h3>
                    <p>Línea de atención</p>
                    <a href="tel:+576015461500">(601) 546 1500</a>
                    <p style="font-size: 0.85rem; margin-top: 0.5rem;">Ext. 12345</p>
                </div>
                
                <div class="contacto-card">
                    <span class="icon">💬</span>
                    <h3>WhatsApp</h3>
                    <p>Atención rápida</p>
                    <a href="https://wa.me/573001234567" target="_blank">+57 300 123 4567</a>
                </div>
                
                <div class="contacto-card">
                    <span class="icon">🏢</span>
                    <h3>Oficina</h3>
                    <p>Centro de Formación</p>
                    <p><strong>Edificio A, Piso 2, Oficina 201</strong></p>
                </div>
            </div>
            
            <div class="horarios-section">
                <h2>🕐 Horarios de Atención</h2>
                <div class="horarios-grid">
                    <div class="horario-item">
                        <h4>Lunes a Viernes</h4>
                        <p>7:00 AM - 5:00 PM</p>
                    </div>
                    <div class="horario-item">
                        <h4>Sábados</h4>
                        <p>8:00 AM - 12:00 PM</p>
                    </div>
                    <div class="horario-item">
                        <h4>Domingos y Festivos</h4>
                        <p>Cerrado</p>
                    </div>
                </div>
            </div>
            
            <div class="responsables-section">
                <h2>👥 Equipo Responsable</h2>
                
                <div class="responsable-card">
                    <div class="responsable-avatar">👨‍💼</div>
                    <div class="responsable-info">
                        <h4>Coordinador de Proyecto</h4>
                        <p>Responsable general de la plataforma Tu Mercado SENA</p>
                        <p><strong>coordinador.tumercado@sena.edu.co</strong></p>
                    </div>
                </div>
                
                <div class="responsable-card">
                    <div class="responsable-avatar">👩‍💻</div>
                    <div class="responsable-info">
                        <h4>Soporte Técnico</h4>
                        <p>Asistencia técnica y resolución de problemas</p>
                        <p><strong>soporte.tumercado@sena.edu.co</strong></p>
                    </div>
                </div>
                
                <div class="responsable-card">
                    <div class="responsable-avatar">👮</div>
                    <div class="responsable-info">
                        <h4>Moderación y Seguridad</h4>
                        <p>Gestión de denuncias y comportamiento en la plataforma</p>
                        <p><strong>moderacion.tumercado@sena.edu.co</strong></p>
                    </div>
                </div>
            </div>
            
            <div class="mapa-section">
                <h2>📍 Ubicación</h2>
                <div class="mapa-placeholder">
                    <p>🗺️ SENA - Servicio Nacional de Aprendizaje<br>Bogotá, Colombia</p>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 2rem;">
                <?php if (isLoggedIn()): ?>
                    <a href="pqrs.php" class="btn-primary">📝 Enviar una PQRS</a>
                <?php else: ?>
                    <a href="login.php" class="btn-primary">Iniciar Sesión</a>
                <?php endif; ?>
                <a href="<?= isLoggedIn() ? 'index.php' : 'welcome.php' ?>" class="btn-secondary">← Volver</a>
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
