<?php
/**
 * Este archivo ya no es necesario porque el registro 
 * ahora es directo sin verificación por email.
 * Redirigimos al registro.
 */
require_once '../config.php';

// Limpiar cualquier dato de registro pendiente
if (isset($_SESSION['registro_cuenta_id'])) {
    unset($_SESSION['registro_cuenta_id']);
    unset($_SESSION['registro_datos_encriptados']);
    unset($_SESSION['registro_expira']);
    unset($_SESSION['registro_email']);
}

header('Location: register.php');
exit;
?>
