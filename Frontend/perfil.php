<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$conn = getDBConnection();

$usuario_id = $_SESSION['usuario_id'] ?? 0;
if ($usuario_id <= 0) {
    header('Location: login.php');
    exit;
}

/* ===============================
   OBTENER USUARIO + CUENTA
================================ */
$stmt = $conn->prepare("
    SELECT 
        u.id AS usuario_id,
        u.cuenta_id,
        u.nickname,
        u.descripcion,
        u.link,
        u.imagen,
        c.email,
        c.notifica_correo,
        c.notifica_push,
        c.uso_datos,
        c.password
    FROM usuarios u
    JOIN cuentas c ON u.cuenta_id = c.id
    WHERE u.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: logout.php');
    exit;
}

$cuenta_id = $user['cuenta_id'];

$error = '';
$success = '';
$active_section = $_GET['section'] ?? 'perfil';

/* ===============================
   POST
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $section = $_POST['section'] ?? 'perfil';

    /* ===== PERFIL ===== */
    if ($section === 'perfil') {

        $nickname = sanitize($_POST['nickname'] ?? '');
        $descripcion = sanitize($_POST['descripcion'] ?? '');
        $link = sanitize($_POST['link'] ?? '');

        if ($nickname === '') {
            $error = 'El nombre es obligatorio';
        } else {
            $stmt = $conn->prepare("
                UPDATE usuarios 
                SET nickname = ?, descripcion = ?, link = ?
                WHERE id = ?
            ");
            $stmt->bind_param("sssi", $nickname, $descripcion, $link, $usuario_id);

            if ($stmt->execute()) {
                $success = 'Perfil actualizado correctamente';
            } else {
                $error = 'Error al actualizar el perfil';
            }
            $stmt->close();
        }
    }

    /* ===== AVATAR ===== */
    elseif ($section === 'avatar' && isset($_FILES['avatar_file'])) {

        $file = $_FILES['avatar_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'Error al subir el archivo';
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $mime = mime_content_type($file['tmp_name']);

            $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
            $allowedMime = [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/avif',
                'application/octet-stream'
            ];

            if (
                in_array($ext, $allowedExt, true) &&
                in_array($mime, $allowedMime, true) &&
                $file['size'] <= 2097152
            ) {
                $name = uniqid('avatar_') . '.' . $ext;
                $path = "assets/images/avatars/$name";

                if (move_uploaded_file($file['tmp_name'], __DIR__ . "/$path")) {
                    $stmt = $conn->prepare("UPDATE usuarios SET imagen = ? WHERE id = ?");
                    $stmt->bind_param("si", $name, $usuario_id);
                    $stmt->execute();
                    $stmt->close();

                    header("Location: perfil.php?section=perfil&status=avatar_success");
                    exit;
                } else {
                    $error = 'No se pudo guardar la imagen';
                }
            } else {
                $error = 'Formato o tamaño inválido';
            }
        }
    }

    /* ===== CONFIGURACIÓN ===== */
    elseif ($section === 'configuracion') {

        $correo = isset($_POST['notifica_correo']) ? 1 : 0;
        $push   = isset($_POST['notifica_push']) ? 1 : 0;
        $datos  = isset($_POST['uso_datos']) ? 1 : 0;

        $stmt = $conn->prepare("
            UPDATE cuentas 
            SET notifica_correo = ?, notifica_push = ?, uso_datos = ?
            WHERE id = ?
        ");
        $stmt->bind_param("iiii", $correo, $push, $datos, $cuenta_id);

        if ($stmt->execute()) {
            header("Location: perfil.php?section=configuracion&status=ok");
            exit;
        } else {
            $error = 'Error al actualizar configuración';
        }
        $stmt->close();
    }

    /* ===== SEGURIDAD ===== */
    elseif ($section === 'seguridad') {

        $actual  = $_POST['password_actual'] ?? '';
        $nueva   = $_POST['password_nueva'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';

        if (!password_verify($actual, $user['password'])) {
            $error = 'Contraseña actual incorrecta';
        } elseif ($nueva !== $confirm || strlen($nueva) < 6) {
            $error = 'Contraseñas inválidas';
        } else {
            $hash = password_hash($nueva, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE cuentas SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hash, $cuenta_id);
            $stmt->execute();
            $stmt->close();

            header("Location: perfil.php?section=seguridad&status=password_ok");
            exit;
        }
    }

    $active_section = $section;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
</head>
<body>
    <?php
    $current_page = basename($_SERVER['PHP_SELF']);
    ?>
    <header class="header">
        <div class="container">
            <div class="header-content">
               <h1 class="logo">
                <a href="index.php">
                    <img src="logo_new.png" class="logo-img">
                    Tu Mercado SENA
                </a>
                </h1>
                <nav class="nav nav-desktop">

                    <a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">Menu Principal</a>
                    
                    <a href="mis_productos.php" class="<?= $current_page == 'mis_productos.php' ? 'active' : '' ?>">
                        Mis Productos
                    </a>
                    <a href="favoritos.php">Favoritos</a>
                    <a href="publicar.php" class="<?= $current_page == 'publicar.php' ? 'active' : '' ?>">
                        Publicar Producto
                    </a>
                    <div class="notification-badge">
                        <span class="notification-icon" id="notificationIcon" title="Chats y notificaciones">💬</span>
                        <span class="notification-count hidden" id="notificationCount">0</span>
                        <div class="chats-list" id="chatsList"></div>
                    </div>
                    <a href="perfil.php" class="perfil-link">
                        <div class="user-avatar-container">
                            <img src="<?php echo getAvatarUrl($user['imagen']); ?>"
                                 alt="Avatar de <?php echo htmlspecialchars($user['nickname']); ?>"
                                 class="avatar-header">
                            <span class="user-name-footer"><?php echo htmlspecialchars($user['nickname']); ?></span>
                        </div>
                    </a>
                </nav>
            </div>
        </div>
    </header>
    
    <?php include 'includes/bottom_nav.php'; ?>

    <main class="main">
        <div class="container">
            <div class="settings-container">
                <div class="settings-sidebar">
                    <ul>
                        <li><a href="#" data-section="perfil" class="<?php echo $active_section === 'perfil' ? 'active' : ''; ?>">Información Personal</a></li>
                        <li><a href="#" data-section="configuracion" class="<?php echo $active_section === 'configuracion' ? 'active' : ''; ?>">Configuración</a></li>
                        <li><a href="#" data-section="seguridad" class="<?php echo $active_section === 'seguridad' ? 'active' : ''; ?>">Seguridad</a></li>
                        
                        <a href="logout.php" onclick="return confirmarLogout();">Cerrar sesión</a>
                        <script>
                            function confirmarLogout() {
                                return confirm("¿Estás seguro de que deseas cerrar sesión?");
                            }
                        </script></li>  
                    </ul>
                </div>
                
                <div class="settings-content">
                    <?php if ($error): ?>
                        <div class="error-message"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="success-message"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <div id="perfil" class="settings-section <?php echo $active_section === 'perfil' ? 'active' : ''; ?>">
    <h2>Información Personal</h2>

    <!-- ===========================
         FORMULARIO DE AVATAR
    ============================ -->
    <div class="profile-avatar-wrapper">
<img id="avatarPhoto" 
     src="<?php echo getAvatarUrl($user['imagen']); ?>"
     class="avatar-photo"
     alt="Avatar">


        <form method="POST" action="perfil.php" id="avatarUploadForm" enctype="multipart/form-data">
            <input type="hidden" name="section" value="avatar">
            <input type="file" id="avatarInputHidden" name="avatar_file" accept="image/*" style="display:none;">

            <button type="button" id="avatarEditButton" class="avatar-edit-btn" title="Cambiar foto de perfil">
                <img src="assets/icons/icono-lapiz.png" alt="Editar">
            </button>
        </form>
    </div>

    <!-- Script de avatar -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const avatarEditBtn = document.getElementById('avatarEditButton');
        const avatarInput = document.getElementById('avatarInputHidden');
        const avatarForm = document.getElementById('avatarUploadForm');
        const avatarPhoto = document.getElementById('avatarPhoto');

        avatarEditBtn.addEventListener('click', () => {
            avatarInput.click();
        });

        avatarInput.addEventListener('change', () => {
            if (avatarInput.files && avatarInput.files[0]) {
                const reader = new FileReader();
                reader.onload = e => {
                    avatarPhoto.src = e.target.result;
                };
                reader.readAsDataURL(avatarInput.files[0]);
                avatarForm.submit();
            }
        });
    });
    </script>

    <!-- ===========================
         FORMULARIO DE DATOS PERSONALES
    ============================ -->
    <form method="POST" action="perfil.php">
        <input type="hidden" name="section" value="perfil">

        <div class="settings-group">
            <h3>Datos Básicos</h3>

            <div class="form-group">
                <label for="nombre">Nombre de Usuario *</label>
                <input type="text" id="nickname" name="nickname"
                    value="<?php echo htmlspecialchars($user['nickname']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                <small>El correo no se puede cambiar</small>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="5" maxlength="512"><?php echo htmlspecialchars($user['descripcion']); ?></textarea>
            </div>

          <div class="form-group">
            <label for="link">Enlace (Redes sociales, sitio web, etc.)</label>
            <input type="url" id="link" name="link" value="<?php echo htmlspecialchars($user['link']); ?>" maxlength="128" placeholder="https://...">
            <small>Comparte tus redes sociales o sitio web</small>
            </div>
        </div>

        <button type="submit" class="btn-primary">Guardar Cambios</button>
    </form>

</div>
                    
<div id="configuracion" class="settings-section <?php echo $active_section === 'configuracion' ? 'active' : ''; ?>">
    <h2>Configuración del Marketplace</h2>
    <form method="POST" action="perfil.php" class="profile-form">
        <input type="hidden" name="section" value="configuracion">
        
        <div class="settings-group">
            <h3>Apariencia</h3>
            <div class="toggle-switch">
                <label for="apariencia">Modo oscuro</label>
                <label class="switch">
                    <button class="theme-toggle" id="themeToggle" title="Cambiar tema">🌓</button>
                </label>
            </div>
            <small>Activar modo oscuro en toda la aplicación</small>
        </div>


        <div class="settings-group">
            <h3>Notificaciones</h3>
            <div class="toggle-switch">
                <label for="notifica_correo">Notificaciones por Correo</label>
                <label class="switch">
                    <input type="checkbox" id="notifica_correo" name="notifica_correo" 
                           <?php echo ($user['notifica_correo'] ?? 0) ? 'checked' : ''; ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <small>Recibir notificaciones importantes por correo electrónico</small>
            
            <div class="toggle-switch">
                <label for="notifica_push">Notificaciones Push</label>
                <label class="switch">
                    <input type="checkbox" id="notifica_push" name="notifica_push" 
                           <?php echo ($user['notifica_push'] ?? 0) ? 'checked' : ''; ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <small>Recibir notificaciones emergentes en tu dispositivo</small>
        </div>
        
        <div class="settings-group">
            <h3>Ahorro de Datos</h3>
            
            <div class="toggle-switch">
                <label for="uso_datos">Modo Ahorro de Datos</label>
                <label class="switch">
                    <input type="checkbox" id="uso_datos" name="uso_datos" 
                           <?php echo ($user['uso_datos'] ?? 0) ? 'checked' : ''; ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <small>Reduce el consumo de datos evitando cargar imágenes automáticamente</small>
        </div>
        
        <button type="submit" class="btn-primary">Guardar Configuración</button>
    </form>
</div>
 <!-- Sección: Seguridad -->
                    <div id="seguridad" class="settings-section <?php echo $active_section === 'seguridad' ? 'active' : ''; ?>">
                        <h2>Seguridad</h2>
                        <form method="POST" action="perfil.php" class="profile-form">
                            <input type="hidden" name="section" value="seguridad">
                            
                            <div class="settings-group">
                                <h3>Cambiar Contraseña</h3>
                                
                                <div class="form-group">
                                    <label for="password_actual">Contraseña Actual *</label>
                                    <input type="password" id="password_actual" name="password_actual" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="password_nueva">Nueva Contraseña *</label>
                                    <input type="password" id="password_nueva" name="password_nueva" required minlength="6">
                                    <small>Mínimo 6 caracteres</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="password_confirm">Confirmar Nueva Contraseña *</label>
                                    <input type="password" id="password_confirm" name="password_confirm" required minlength="6">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-primary">Cambiar Contraseña</button>
                        </form>
                    </div>
                </div>
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


