<?php
require_once 'config.php';

// Si el usuario ya está logueado, redirigir al index
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Forzar modo claro en welcome
forceLightTheme();
?>
<?php
require_once 'config.php';

// Si el usuario ya está logueado, redirigir al index
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .welcome-wrapper {
            min-height: 100vh;
            background: linear-gradient(
                rgba(83, 131, 146, 0.85), 
                rgba(143, 182, 191, 0.90)
            ), url('https://uploads.candelaestereo.com/1/2023/11/nuevo-contrato-para-aprendices-sena-816x496.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .welcome-wrapper::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(169, 179, 242, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(84, 94, 160, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(74, 76, 122, 0.2) 0%, transparent 50%);
            animation: pulse 8s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 0.8; }
        }

        .welcome-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            max-width: 1200px;
            width: 100%;
            background: var(--color-white);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
            min-height: 600px;
            position: relative;
            z-index: 1;
        }

        .welcome-hero {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            color: var(--color-white);
            position: relative;
            overflow: hidden;
        }

        .welcome-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: sparkle 3s infinite linear;
        }

        @keyframes sparkle {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .welcome-logo {
            width: 120px;
            height: auto;
            margin-bottom: 30px;
            display: block;
            transition: transform 0.3s ease;
        }

        .welcome-logo:hover {
            transform: scale(1.05);
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            line-height: 1.1;
            background: linear-gradient(135deg, #fff 0%, var(--color-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 30px;
            opacity: 0.9;
            line-height: 1.6;
            color: #ecf0f1;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        .hero-features {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 40px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1.1rem;
            color: #ecf0f1;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            backdrop-filter: blur(10px);
        }

        .welcome-form-section {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: var(--color-white);
        }

        .form-title {
            color: var(--color-primary);
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-align: center;
        }

        .form-subtitle {
            color: #2c3e50;
            font-size: 1.1rem;
            margin-bottom: 40px;
            text-align: center;
            font-weight: 500;
            text-shadow: 0 1px 2px rgba(255,255,255,0.8);
        }

        .welcome-buttons {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 30px;
        }

        .btn-welcome {
            padding: 18px 30px;
            font-size: 1.2rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .btn-welcome::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        .btn-welcome:hover::before {
            left: 100%;
        }

        .btn-welcome-login {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
            color: var(--color-white);
            box-shadow: 0 10px 30px rgba(83, 131, 146, 0.3);
        }

        .btn-welcome-register {
            background: var(--color-white);
            color: var(--color-primary);
            border: 2px solid var(--color-primary);
            box-shadow: 0 5px 15px rgba(83, 131, 146, 0.1);
        }

        .btn-welcome:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(83, 131, 146, 0.4);
        }

        .welcome-footer {
            margin-top: 40px;
            text-align: center;
            color: #2c3e50;
            font-size: 0.9rem;
            padding-top: 20px;
            border-top: 1px solid var(--color-bg-secondary);
            font-weight: 500;
            text-shadow: 0 1px 2px rgba(255,255,255,0.8);
        }

        .sena-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            margin-top: 10px;
        }

        /* Animaciones */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-content > * {
            animation: fadeInUp 0.8s ease-out;
        }

        .hero-content > *:nth-child(2) {
            animation-delay: 0.2s;
        }

        .hero-content > *:nth-child(3) {
            animation-delay: 0.4s;
        }

        .welcome-form-section > * {
            animation: fadeInUp 0.8s ease-out 0.6s both;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .welcome-container {
                grid-template-columns: 1fr;
                max-width: 500px;
            }
            
            .welcome-hero {
                padding: 40px 30px;
                text-align: center;
                align-items: center;
            }
            
            .hero-title {
                font-size: 2.8rem;
            }
            
            .welcome-form-section {
                padding: 40px 30px;
            }
        }

        @media (max-width: 480px) {
            .hero-title {
                font-size: 2.2rem;
            }
            
            .form-title {
                font-size: 2rem;
            }
            
            .btn-welcome {
                padding: 15px 25px;
                font-size: 1.1rem;
            }
        }

        /* Efectos de partículas */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            animation: float-particle 15s infinite linear;
        }

        @keyframes float-particle {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }

        /* Modo oscuro */
        [data-theme="dark"] .welcome-form-section {
            background: var(--color-bg);
        }

        [data-theme="dark"] .form-subtitle,
        [data-theme="dark"] .welcome-footer {
            color: var(--color-text-light);
            text-shadow: none;
        }

        [data-theme="dark"] .btn-welcome-register {
            background: var(--color-bg-secondary);
            color: var(--color-text);
        }

        [data-theme="dark"] .btn-welcome-register:hover {
            background: var(--color-primary);
            color: var(--color-white);
        }
    </style>
</head>
<body>
    <div class="welcome-wrapper">
        <!-- Partículas animadas -->
        <div class="particles" id="particles"></div>
        
        <div class="welcome-container">
            <!-- Sección Hero (izquierda) -->
            <div class="welcome-hero">
                <div class="hero-content">
                    <!-- Logo sin efectos de caja -->
                    <img src="logo_new.png" alt="Tu Mercado SENA" class="welcome-logo">
                    <h1 class="hero-title">Tu Mercado SENA</h1>
                    <p class="hero-subtitle">
                        La plataforma exclusiva para la comunidad SENA. Compra, vende y conecta de forma segura con aprendices e instructores.
                    </p>
                    
                    <div class="hero-features">
                        <div class="feature">
                            <div class="feature-icon">🛒</div>
                            <span>Compra productos de calidad</span>
                        </div>
                        <div class="feature">
                            <div class="feature-icon">💰</div>
                            <span>Vende lo que ya no uses</span>
                        </div>
                        <div class="feature">
                            <div class="feature-icon">💬</div>
                            <span>Chat seguro integrado</span>
                        </div>
                        <div class="feature">
                            <div class="feature-icon">🔒</div>
                            <span>Comunidad verificada SENA</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección Formulario (derecha) -->
            <div class="welcome-form-section">
                <h2 class="form-title">¡Bienvenido!</h2>
                <p class="form-subtitle">Únete a nuestra comunidad</p>

                <div class="welcome-buttons">
                    <a href="login.php" class="btn-welcome btn-welcome-login">
                        Iniciar Sesión
                    </a>
                    <a href="register.php" class="btn-welcome btn-welcome-register">
                        Crear Cuenta
                    </a>
                </div>

                <div class="welcome-footer">
                    <p>💡 <strong>Exclusivo para la comunidad SENA</strong></p>
                    <div class="sena-badge">
                        <span>🏫</span>
                        Requiere correo @sena.edu.co
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Forzar modo claro en welcome
        document.addEventListener('DOMContentLoaded', function() {
            localStorage.setItem('theme', 'light');
            document.documentElement.setAttribute('data-theme', 'light');
            
            // Crear partículas animadas
            createParticles();
        });

        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 15;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Tamaño aleatorio
                const size = Math.random() * 6 + 2;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                // Posición inicial aleatoria
                particle.style.left = `${Math.random() * 100}%`;
                
                // Animación con delay aleatorio
                particle.style.animationDelay = `${Math.random() * 20}s`;
                particle.style.animationDuration = `${15 + Math.random() * 10}s`;
                
                particlesContainer.appendChild(particle);
            }
        }
    </script>
</body>
</html> 