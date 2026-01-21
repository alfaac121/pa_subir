<?php
require_once 'config.php';
require_once 'api/api_client.php';
forceLightTheme();

$error = '';
$success = '';

// Verificar que hay datos de registro pendiente
if (!isset($_SESSION['registro_cuenta_id'])) {
    header("Location: register.php");
    exit();
}

$cuentaId = $_SESSION['registro_cuenta_id'];
$datosEncriptados = $_SESSION['registro_datos_encriptados'] ?? '';
$expiraEn = $_SESSION['registro_expira'] ?? '';
$email = $_SESSION['registro_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clave = strtoupper(trim($_POST['clave'] ?? ''));
    
    if (empty($clave)) {
        $error = "Por favor ingresa el código de verificación";
    } elseif (strlen($clave) !== 6) {
        $error = "El código debe tener 6 caracteres";
    } else {
        // Llamar a la API para completar el registro
        $response = apiCompletarRegistro($cuentaId, $clave, $datosEncriptados);
        
        if ($response['success'] && isset($response['data']['token'])) {
            $data = $response['data'];
            $user = $data['user'];
            $token = $data['token'];
            $expiresIn = $data['expires_in'] ?? 86400;
            
            // Guardar token JWT
            saveToken($token, $expiresIn);
            
            // Guardar datos del usuario en sesión
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nombre'] = $user['nickname'];
            $_SESSION['usuario_rol'] = $user['rol_id'];
            $_SESSION['usuario_imagen'] = $user['imagen'] ?? '';
            $_SESSION['cuenta_id'] = $user['cuenta_id'];
            
            // Limpiar datos de registro temporal
            unset($_SESSION['registro_cuenta_id']);
            unset($_SESSION['registro_datos_encriptados']);
            unset($_SESSION['registro_expira']);
            unset($_SESSION['registro_email']);
            
            // Redirigir al login
            header("Location: login.php?registered=1");
            exit();
        } else {
            // Manejar errores de la API
            if (isset($response['data']['message'])) {
                $error = $response['data']['message'];
            } elseif (isset($response['data']['error'])) {
                $error = $response['data']['error'];
            } else {
                $error = "Código inválido o expirado. Intenta de nuevo.";
            }
        }
    }
}

// Cancelar registro
if (isset($_GET['cancelar'])) {
    unset($_SESSION['registro_cuenta_id']);
    unset($_SESSION['registro_datos_encriptados']);
    unset($_SESSION['registro_expira']);
    unset($_SESSION['registro_email']);
    header("Location: register.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Código - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
    <style>
        .verification-code {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }
        .verification-code input {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border: 2px solid #ddd;
            border-radius: 8px;
            text-transform: uppercase;
        }
        .verification-code input:focus {
            border-color: var(--primary-color, #007bff);
            outline: none;
        }
        .email-display {
            background: rgba(0, 123, 255, 0.1);
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .email-display strong {
            color: var(--primary-color, #007bff);
        }
        .timer {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-top: 10px;
        }
        .resend-link {
            text-align: center;
            margin-top: 15px;
        }
        .resend-link a {
            color: var(--primary-color, #007bff);
            text-decoration: none;
        }
        .resend-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1 class="auth-title">
                Verificar Código
            </h1>
            
            <div class="email-display">
                Enviamos un código de verificación a:<br>
                <strong><?php echo htmlspecialchars($email); ?></strong>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="verificar_registro.php" id="verifyForm">
                <p style="text-align: center; margin-bottom: 15px;">Ingresa el código de 6 caracteres:</p>
                
                <div class="verification-code">
                    <input type="text" maxlength="1" class="code-input" data-index="0" autofocus>
                    <input type="text" maxlength="1" class="code-input" data-index="1">
                    <input type="text" maxlength="1" class="code-input" data-index="2">
                    <input type="text" maxlength="1" class="code-input" data-index="3">
                    <input type="text" maxlength="1" class="code-input" data-index="4">
                    <input type="text" maxlength="1" class="code-input" data-index="5">
                </div>
                
                <input type="hidden" name="clave" id="claveInput">
                
                <button type="submit" class="btn-primary">Verificar Código</button>
            </form>
            
            <?php if ($expiraEn): ?>
                <p class="timer">El código expira: <?php echo htmlspecialchars($expiraEn); ?></p>
            <?php endif; ?>
            
            <div class="resend-link">
                <a href="register.php">← Volver al registro</a> | 
                <a href="verificar_registro.php?cancelar=1">Cancelar</a>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.code-input');
            const claveInput = document.getElementById('claveInput');
            const form = document.getElementById('verifyForm');
            
            // Función para actualizar el campo oculto
            function updateHiddenInput() {
                let code = '';
                inputs.forEach(input => {
                    code += input.value.toUpperCase();
                });
                claveInput.value = code;
            }
            
            // Manejar input en cada campo
            inputs.forEach((input, index) => {
                input.addEventListener('input', function(e) {
                    this.value = this.value.toUpperCase();
                    updateHiddenInput();
                    
                    // Avanzar al siguiente campo
                    if (this.value && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                });
                
                input.addEventListener('keydown', function(e) {
                    // Retroceder con backspace
                    if (e.key === 'Backspace' && !this.value && index > 0) {
                        inputs[index - 1].focus();
                    }
                });
                
                // Permitir pegar código completo
                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedData = e.clipboardData.getData('text').toUpperCase().slice(0, 6);
                    
                    for (let i = 0; i < pastedData.length && i < inputs.length; i++) {
                        inputs[i].value = pastedData[i];
                    }
                    
                    updateHiddenInput();
                    
                    // Enfocar el siguiente campo vacío o el último
                    const nextEmpty = Array.from(inputs).findIndex(inp => !inp.value);
                    if (nextEmpty !== -1) {
                        inputs[nextEmpty].focus();
                    } else {
                        inputs[inputs.length - 1].focus();
                    }
                });
            });
            
            // Validar antes de enviar
            form.addEventListener('submit', function(e) {
                updateHiddenInput();
                if (claveInput.value.length !== 6) {
                    e.preventDefault();
                    alert('Por favor ingresa el código completo de 6 caracteres');
                }
            });
        });
    </script>
</body>
</html>

