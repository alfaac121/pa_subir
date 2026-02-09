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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--color-bg-light);
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Cabecera Estilo SENA */
        .welcome-header {
            background-color: var(--color-primary);
            color: white;
            padding: 15px 0;
            width: 100%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: flex-start !important;
            padding: 0 20px;
            gap: 20px;
        }

        .header-logo {
            height: 70px; /* Logo más grande */
            width: auto;
        }

        .header-title {
            font-size: 2rem;
            font-weight: 800;
            color: white;
            letter-spacing: -0.5px;
        }

        /* Carousel Styles */
        .carousel-container {
            width: 100%;
            height: 250px; /* Un poco más alto para que el 'cover' no recorte tanto */
            overflow: hidden;
            position: relative;
            margin-top: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .carousel-wrapper {
            display: flex;
            width: 300%;
            height: 100%;
            animation: slide 15s infinite ease-in-out;
        }

        .carousel-slide {
            flex: 0 0 33.333%; /* Ocupa exactamente un tercio del wrapper (que es el 100% del contenedor) */
            width: 33.333%;
            height: 100%;
            background-size: 100% 100%; /* Forzar a que ocupe todo el recuadro sin huecos */
            background-position: center;
            background-repeat: no-repeat;
        }

        @keyframes slide {
            0% { transform: translateX(0); }
            30% { transform: translateX(0); }
            33% { transform: translateX(-33.33%); }
            63% { transform: translateX(-33.33%); }
            66% { transform: translateX(-66.66%); }
            96% { transform: translateX(-66.66%); }
            100% { transform: translateX(0); }
        }

        /* Contenedor Principal */
        .welcome-main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .welcome-card {
            background: white;
            width: 100%;
            max-width: 1150px; /* Ensanchado de 1000px a 1150px */
            display: grid;
            grid-template-columns: 48% 52%; /* Ajuste ligero de proporción */
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
            min-height: 600px;
        }

        /* Lado Izquierdo - Green */
        .card-hero {
            background-color: var(--color-primary);
            color: white;
            padding: 50px 40px; /* Restaurado padding estándar */
            display: flex;
            flex-direction: column;
            gap: 25px;
            position: relative;
        }

        .hero-brand {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            gap: 20px !important;
            margin-bottom: 30px;
        }

        .hero-logo {
            width: 100px !important; /* Más grande como pidió antes */
            height: auto !important;
            margin-bottom: 0 !important;
            flex-shrink: 0;
        }

        .hero-title {
            font-size: 2.5rem !important;
            font-weight: 800 !important;
            margin: 0 !important;
            line-height: 1.1 !important;
            white-space: nowrap;
        }

        .hero-desc {
            font-size: 0.95rem;
            line-height: 1.6;
            opacity: 0.95;
            margin-bottom: 20px;
        }

        .features-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .feature-circle {
            width: 38px;
            height: 38px;
            background: var(--color-bg-secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
            color: var(--color-secondary);
        }

        .feature-text {
            font-size: 0.95rem;
            font-weight: 500;
        }

        /* Lado Derecho - White */
        .card-auth {
            padding: 70px 60px; /* Más espacio para los botones */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .auth-title {
            color: var(--color-primary);
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 5px;
            font-family: 'Outfit', sans-serif;
        }

        .auth-subtitle {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 45px;
        }

        .auth-actions {
            width: 100%;
            max-width: 320px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .btn-welcome {
            padding: 14px 25px;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
            display: block;
        }

        .btn-primary-custom {
            background-color: var(--color-primary);
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(45, 199, 92, 0.2);
        }

        .btn-primary-custom:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(45, 199, 92, 0.3);
        }

        .btn-outline-custom {
            background-color: white;
            color: var(--color-secondary);
            border: 2px solid var(--color-accent);
        }

        .btn-outline-custom:hover {
            background-color: var(--color-bg-secondary);
            transform: translateY(-2px);
        }


        /* Footer Info */
        .auth-footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1.5px solid #eee;
            width: 100%;
            max-width: 350px;
        }

        .info-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 15px;
            color: #444;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .sena-tag {
            background: var(--color-primary);
            color: white;
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* Footer Bar */
        .welcome-footer-bar {
            background-color: var(--color-primary);
            color: white;
            text-align: center;
            padding: 15px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 850px) {
            .welcome-card {
                grid-template-columns: 1fr;
                max-width: 450px;
            }
            .card-hero {
                display: none; /* Como en muchas mobiles cards */
            }
            .card-auth {
                padding: 50px 30px;
            }
        }
    </style>
</head>
<body>
    <!-- Header superior -->
    <header class="welcome-header">
        <div class="header-content" style="display: flex; align-items: center; gap: 20px; padding: 0 20px; max-width: 1200px; margin: 0 auto;">
            <img src="logo_new.png" alt="SENA" class="header-logo" style="height: 90px; width: auto;">
            <span class="header-title" style="font-size: 2.2rem; font-weight: 800; color: white;">Tu Mercado SENA</span>
        </div>
    </header>

    <!-- Contenedor principal -->
    <main class="welcome-main">
        <div class="welcome-card">
            <!-- Lado Izquierdo - Green -->
            <div class="card-hero">
                <div class="hero-brand" style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px;">
                    <img src="logo_new.png" alt="Logo" class="hero-logo" style="width: 110px !important; height: auto;">
                    <h2 class="hero-title" style="margin: 0; font-size: 2.5rem; font-weight: 800;">Tu Mercado SENA</h2>
                </div>
                
                <p class="hero-desc">
                    La plataforma exclusiva para la comunidad SENA. Compra, vende y conecta de forma segura con aprendices.
                </p>
                
                <div class="features-list">
                    <div class="feature-item">
                        <div class="feature-circle"><i class="ri-shopping-cart-line"></i></div>
                        <span class="feature-text">Compra productos de calidad</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-circle"><i class="ri-money-dollar-circle-line"></i></div>
                        <span class="feature-text">Vende lo que ya no uses</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-circle"><i class="ri-chat-3-line"></i></div>
                        <span class="feature-text">Chat seguro integrado</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-circle"><i class="ri-shield-check-line"></i></div>
                        <span class="feature-text">Comunidad verificada SENA</span>
                    </div>
                </div>

                <!-- Carrusel de imágenes (Movido abajo) -->
                <div class="carousel-container">
                    <div class="carousel-wrapper">
                        <div class="carousel-slide" style="background-image: url('assets/carousel/1.png');"></div>
                        <div class="carousel-slide" style="background-image: url('assets/carousel/2.png');"></div>
                        <div class="carousel-slide" style="background-image: url('assets/carousel/3.png');"></div>
                    </div>
                </div>

            </div>

            <!-- Lado blanco con botones -->
            <div class="card-auth">
                <h1 class="auth-title">¡Bienvenido!</h1>
                <p class="auth-subtitle">Únete a nuestra comunidad</p>

                <div class="auth-actions">
                    <a href="login.php" class="btn-welcome btn-primary-custom">
                        Iniciar Sesión
                    </a>
                    <a href="register.php" class="btn-welcome btn-outline-custom">
                        Crear Cuenta
                    </a>
                </div>

                <div class="auth-footer">
                    <div class="info-badge">
                        💡 <span>Exclusivo para la comunidad SENA</span>
                    </div>
                    <div class="sena-tag">
                        <i class="ri-mail-line"></i> Requiere correo @sena.edu.co
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Barra inferior -->
    <footer class="welcome-footer-bar">
        © 2025 Tu Mercado SENA. Todos los derechos reservados.
    </footer>

    <script>
        // Forzar modo claro
        localStorage.setItem('theme', 'light');
        document.documentElement.setAttribute('data-theme', 'light');
    </script>


</body>
</html> 

