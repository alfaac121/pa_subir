<?php
require_once 'config.php';
forceLightTheme();

$error = '';
$success = '';

// Si ya tiene sesión, redirigir
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $nombre = sanitize($_POST['nombre'] ?? '');
    $descripcion = sanitize($_POST['descripcion'] ?? '');
    $link = sanitize($_POST['link'] ?? '');
    $imagenNombre = '';

    // Procesar imagen de perfil si se subió
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        $fileType = $_FILES['imagen']['type'];
        $fileSize = $_FILES['imagen']['size'];
        
        if (!in_array($fileType, $allowedTypes)) {
            $error = "Formato de imagen no válido. Use JPG, PNG, GIF o WEBP.";
        } elseif ($fileSize > $maxSize) {
            $error = "La imagen es muy grande. Máximo 5MB.";
        } else {
            // Generar nombre único para la imagen
            $extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            $imagenNombre = 'avatar_' . uniqid() . '.' . $extension;
            
            // Crear directorio si no existe
            $uploadDir = 'uploads/usuarios/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Mover archivo
            $destino = $uploadDir . $imagenNombre;
            if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $destino)) {
                $error = "Error al subir la imagen. Intenta de nuevo.";
                $imagenNombre = '';
            }
        }
    }

    if (empty($error)) {
        if (empty($email) || empty($password) || empty($password_confirm) || empty($nombre)) {
            $error = "Todos los campos obligatorios deben completarse";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Formato de correo inválido";
        } elseif (!str_ends_with(strtolower($email), "@soy.sena.edu.co")) {
            $error = "Debe usar un correo @soy.sena.edu.co";
        } elseif ($password !== $password_confirm) {
            $error = "Las contraseñas no coinciden";
        } elseif (strlen($password) < 8) {
            $error = "La contraseña debe tener al menos 8 caracteres";
        } else {
            $conn = getDBConnection();
            
            // Verificar si el correo ya existe
            $stmt = $conn->prepare("SELECT id FROM cuentas WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Este correo ya está registrado";
                $stmt->close();
                
                // Eliminar imagen subida si el registro falla
                if (!empty($imagenNombre) && file_exists('uploads/usuarios/' . $imagenNombre)) {
                    unlink('uploads/usuarios/' . $imagenNombre);
                }
            } else {
                $stmt->close();
                
                // Hash de la contraseña
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                // Iniciar transacción
                $conn->begin_transaction();
                
                try {
                    // Insertar cuenta
                    $stmt = $conn->prepare("
                        INSERT INTO cuentas (email, password, notifica_correo, notifica_push, uso_datos)
                        VALUES (?, ?, 0, 0, 1)
                    ");
                    $stmt->bind_param("ss", $email, $passwordHash);
                    $stmt->execute();
                    $cuentaId = $conn->insert_id;
                    $stmt->close();
                    
                    // Insertar usuario
                    $stmt = $conn->prepare("
                        INSERT INTO usuarios (cuenta_id, nickname, imagen, descripcion, link, rol_id, estado_id, visible)
                        VALUES (?, ?, ?, ?, ?, 3, 1, 1)
                    ");
                    $stmt->bind_param("issss", $cuentaId, $nombre, $imagenNombre, $descripcion, $link);

                    $stmt->execute();
                    $stmt->close();
                    
                    // Confirmar transacción
                    $conn->commit();
                    
                    $conn->close();
                    
                    // Redirigir al login con mensaje de éxito
                    header("Location: login.php?registered=1");
                    exit();
                    
                } catch (Exception $e) {
                    // Revertir transacción
                    $conn->rollback();
                    $error = "Error al crear la cuenta. Intenta de nuevo.";
                    
                    // Eliminar imagen subida si el registro falla
                    if (!empty($imagenNombre) && file_exists('uploads/usuarios/' . $imagenNombre)) {
                        unlink('uploads/usuarios/' . $imagenNombre);
                    }
                }
            }
            
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
    <style>
        .avatar-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }
        .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #ffffff; /* Fondo blanco */
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 3px dashed var(--color-primary); /* Borde discontinuo verde */
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: relative;
        }
        .avatar-preview .avatar-icon {
            font-size: 48px;
            color: var(--color-primary); /* Icono verde */
        }
        .avatar-preview:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .avatar-preview .overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.5);
            padding: 8px;
            text-align: center;
            color: white;
            font-size: 11px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .avatar-preview:hover .overlay {
            opacity: 1;
        }
        .avatar-input {
            display: none !important;
            visibility: hidden;
            position: absolute;
            left: -9999px;
        }
        /* Ocultar cualquier imagen fuera del preview */
        .avatar-upload > img,
        .avatar-upload input[type="file"] + img {
            display: none !important;
        }
        .avatar-label {
            margin-top: 10px;
            color: #666;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <!-- Header superior -->
    <header class="header">
        <div class="header-content" style="max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: flex-start; gap: 20px; padding: 0 20px;">
            <img src="logo_new.png" alt="SENA" style="height: 70px; width: auto;">
            <span style="font-size: 1.5rem; font-weight: 800; color: white;">Tu Mercado SENA</span>
        </div>
    </header>

    <div class="auth-container" style="margin-top: 20px;">
        <div class="auth-box" style="width: 500px; margin: 40px 0;"> <!-- Un poco más ancho para el registro -->
            <h1 class="auth-title">
            Registro
            </h1>
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form method="POST" action="register.php" enctype="multipart/form-data">
                
                <!-- Campo de imagen de perfil -->
                <div class="avatar-upload">
                    <label for="imagen" class="avatar-preview" id="avatarPreview">
                        <span class="avatar-icon">👤</span>
                        <div class="overlay">Cambiar foto</div>
                    </label>
                    <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/gif,image/webp" class="avatar-input">
                    <span class="avatar-label">Foto de perfil (opcional)</span>
                </div>
                
                <div class="form-group">
                    <label for="nombre">Nombre de Usuario *</label>
                    <input type="text" id="nombre" name="nombre" maxlength="24" required>
                    <small>Máximo 24 caracteres</small>
                </div>
                <div class="form-group">
                    <label for="email">Correo Electrónico (@soy.sena.edu.co) *</label>
                    <input type="email" id="email" name="email" placeholder="usuario@soy.sena.edu.co" required>
                    <small>Solo se aceptan correos del dominio @soy.sena.edu.co</small>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña *</label>
                    <input type="password" id="password" name="password" required minlength="8">
                    <small>Mínimo 8 caracteres</small>
                </div>
                <div class="form-group">
                    <label for="password_confirm">Confirmar Contraseña *</label>
                    <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción (opcional)</label>
                    <textarea id="descripcion" name="descripcion" maxlength="300" rows="3" placeholder="Cuéntanos sobre ti..."></textarea>
                    <small>Máximo 300 caracteres</small>
                </div>
                <button type="submit" class="btn-primary">Registrarse</button>
            </form>
            <p class="auth-link">¿Ya tienes cuenta? <a href="login.php" style="color: var(--color-primary);">Inicia sesión aquí</a></p>
            <p class="auth-link"><small>Debes tener un correo @sena.edu.co para registrarte</small></p>
        </div>
    </div>

    <!-- Barra inferior -->
    <footer style="background-color: var(--color-primary); color: white; text-align: center; padding: 15px; font-size: 0.9rem; font-weight: 500;">
        © 2025 Tu Mercado SENA. Todos los derechos reservados.
    </footer>
</body>

    <script src="script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Preview de imagen de perfil
            const imagenInput = document.getElementById('imagen');
            const avatarPreview = document.getElementById('avatarPreview');
            
            imagenInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validar tamaño (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('La imagen es muy grande. Máximo 5MB.');
                        this.value = '';
                        return;
                    }
                    
                    // Validar tipo
                    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!validTypes.includes(file.type)) {
                        alert('Formato no válido. Use JPG, PNG, GIF o WEBP.');
                        this.value = '';
                        return;
                    }
                    
                    // Mostrar preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        avatarPreview.innerHTML = `
                            <img src="${e.target.result}" alt="Preview">
                            <div class="overlay">Cambiar foto</div>
                        `;
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Validación del dominio @soy.sena.edu.co en tiempo real
            const emailInput = document.getElementById('email');
            if (emailInput) {
                emailInput.addEventListener('blur', function() {
                    const email = this.value.trim().toLowerCase();
                    if (email && !email.endsWith('@soy.sena.edu.co')) {
                        this.setCustomValidity('El correo debe ser del dominio @soy.sena.edu.co');
                        this.style.borderColor = '#e74c3c';
                    } else {
                        this.setCustomValidity('');
                        this.style.borderColor = '#ddd';
                    }
                });
                
                emailInput.addEventListener('input', function() {
                    if (this.style.borderColor === 'rgb(231, 76, 60)') {
                        const email = this.value.trim().toLowerCase();
                        if (email.endsWith('@soy.sena.edu.co')) {
                            this.setCustomValidity('');
                            this.style.borderColor = '#ddd';
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>

