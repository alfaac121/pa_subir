<?php
require_once 'config.php';
require_once 'api/api_client.php';

// Cerrar sesión en la API
if (hasValidToken()) {
    apiLogout();
}

// Limpiar token
clearToken();

// Limpiar todas las variables de sesión
session_unset();
session_destroy();

// Redirigir al login
header("Location: login.php");
exit();
?>

