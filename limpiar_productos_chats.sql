-- Limpiar todos los productos y chats
-- ADVERTENCIA: Esto eliminará TODOS los datos de productos, chats y mensajes

-- Eliminar mensajes primero (tienen FK a chats)
DELETE FROM mensajes;

-- Eliminar chats (tienen FK a productos)
DELETE FROM chats;

-- Eliminar favoritos (tienen FK a productos)
DELETE FROM favoritos;

-- Eliminar reportes de productos
DELETE FROM reportes WHERE producto_id IS NOT NULL;

-- Eliminar productos
DELETE FROM productos;

-- Reiniciar auto_increment
ALTER TABLE mensajes AUTO_INCREMENT = 1;
ALTER TABLE chats AUTO_INCREMENT = 1;
ALTER TABLE productos AUTO_INCREMENT = 1;

-- Mensaje de confirmación
SELECT 'Todos los productos, chats y mensajes han sido eliminados' AS resultado;
