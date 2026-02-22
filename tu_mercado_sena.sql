-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 11-12-2025 a las 00:29:07
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `tu_mercado_sena`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditorias`
--
-- Creación: 10-12-2025 a las 21:55:57
--

DROP TABLE IF EXISTS `auditorias`;
CREATE TABLE `auditorias` (
  `id` int(10) UNSIGNED NOT NULL,
  `administrador_id` int(10) UNSIGNED NOT NULL COMMENT 'quién hizo la acción',
  `suceso_id` int(10) UNSIGNED NOT NULL COMMENT 'cuál es la naturaleza de la acción',
  `descripcion` varchar(512) NOT NULL COMMENT 'detalla qué fué lo que sucedió, dando id de tablas implicadas',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `auditorias`:
--   `suceso_id`
--       `sucesos` -> `id`
--   `administrador_id`
--       `usuarios` -> `id`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bloqueados`
--
-- Creación: 10-12-2025 a las 21:55:57
--

DROP TABLE IF EXISTS `bloqueados`;
CREATE TABLE `bloqueados` (
  `id` int(10) UNSIGNED NOT NULL,
  `bloqueador_id` int(10) UNSIGNED NOT NULL COMMENT 'quien bloqueo al bloqueado',
  `bloqueado_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `bloqueados`:
--   `bloqueado_id`
--       `usuarios` -> `id`
--   `bloqueador_id`
--       `usuarios` -> `id`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--
-- Creación: 10-12-2025 a las 21:55:57
--

DROP TABLE IF EXISTS `categorias`;
CREATE TABLE `categorias` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(32) NOT NULL COMMENT 'por ejemplo, electrodomesticos, mobiliario, comida, ropa, etc'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `categorias`:
--

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`) VALUES
(1, 'vestimenta'),
(2, 'alimento'),
(3, 'papelería'),
(4, 'herramienta'),
(5, 'cosmético'),
(6, 'deportivo'),
(7, 'dispositivo'),
(8, 'servicio'),
(9, 'social'),
(10, 'mobiliario'),
(11, 'vehículo'),
(12, 'mascota'),
(13, 'otro'),
(14, 'adornos');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `chats`
--
-- Creación: 10-12-2025 a las 21:55:57
--

DROP TABLE IF EXISTS `chats`;
CREATE TABLE `chats` (
  `id` int(10) UNSIGNED NOT NULL,
  `comprador_id` int(10) UNSIGNED NOT NULL COMMENT 'el usuario que inicia la conversación',
  `producto_id` int(10) UNSIGNED NOT NULL COMMENT 'los chats se inician mediante un producto en venta, aqui estaria ese producto',
  `estado_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'el estado dice si el chat esta eliminado, finalizado, activo, etc',
  `visto_comprador` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'true indica que el último mensaje del vendedor ya fué visto por el comprador',
  `visto_vendedor` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'true indica que el último mensaje del comprador ya fué visto por el vendedor',
  `precio` float DEFAULT NULL COMMENT 'el precio total final acordado, puede diferir del precio del producto, esto lo pone el vendedor',
  `cantidad` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'la cantidad de ítems transaccionados, esto lo pone el vendedor',
  `calificacion` tinyint(3) UNSIGNED DEFAULT NULL COMMENT '1 a 5 puesta por el comprador y se puede modificar luego',
  `comentario` varchar(255) DEFAULT NULL COMMENT 'escrito por el comprador y se puede modificar luego',
  `fecha_venta` timestamp NULL DEFAULT NULL COMMENT 'cuándo se hizo la transacción'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `chats`:
--   `estado_id`
--       `estados` -> `id`
--   `producto_id`
--       `productos` -> `id`
--   `comprador_id`
--       `usuarios` -> `id`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuentas`
--
-- Creación: 10-12-2025 a las 21:55:57
--

DROP TABLE IF EXISTS `cuentas`;
CREATE TABLE `cuentas` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(64) NOT NULL COMMENT 'correo institucional, debe ser unico',
  `password` varchar(127) NOT NULL DEFAULT '' COMMENT 'debe guardarse como un hash',
  `clave` varchar(32) NOT NULL DEFAULT '' COMMENT 'una combinación aleatoria que será enviada al correo, con un solo uso limitado por tiempo',
  `notifica_correo` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'en true significa que desea recibir correos cuando alguien se pone en contacto',
  `notifica_push` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'en true significa que quiere recibir notificaciones emergentes en celular o computadora cuando algo sucede',
  `uso_datos` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'en false reduce el consumo de datos de la aplicacion evitando cargar imagenes',
  `pin` varchar(4) NOT NULL DEFAULT '' COMMENT 'para bloqueo de interfaces sin logout',
  `token_web` varchar(32) NOT NULL DEFAULT '' COMMENT 'para un acceso a web',
  `token_movil` varchar(32) NOT NULL DEFAULT '' COMMENT 'para un acceso a móvil',
  `token_admin` varchar(32) NOT NULL DEFAULT '' COMMENT 'para un acceso a desktop',
  `fecha_clave` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'guarda el momento en que se envio una solicitud al mail, para poder esperar y no enviarlas muy seguido'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `cuentas`:
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `denuncias`
--
-- Creación: 10-12-2025 a las 21:55:57
--

DROP TABLE IF EXISTS `denuncias`;
CREATE TABLE `denuncias` (
  `id` int(10) UNSIGNED NOT NULL,
  `denunciante_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'quién fué el usuario que creó la denuncia',
  `producto_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'si fué creada a partir de un producto',
  `usuario_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'si fué creada a partir de un usuario',
  `chat_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'si fué creada a partir de un chat / mensaje de comprador',
  `motivo_id` int(10) UNSIGNED NOT NULL COMMENT 'indica qué naturaleza tiene la denuncia',
  `estado_id` int(10) UNSIGNED NOT NULL COMMENT 'indica si ya se ha procesado la denuncia',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `denuncias`:
--   `chat_id`
--       `chats` -> `id`
--   `denunciante_id`
--       `usuarios` -> `id`
--   `estado_id`
--       `estados` -> `id`
--   `motivo_id`
--       `motivos` -> `id`
--   `producto_id`
--       `productos` -> `id`
--   `usuario_id`
--       `usuarios` -> `id`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estados`
--
-- Creación: 10-12-2025 a las 21:55:57
--

DROP TABLE IF EXISTS `estados`;
CREATE TABLE `estados` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(32) NOT NULL,
  `descripcion` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `estados`:
--

--
-- Volcado de datos para la tabla `estados`
--

INSERT INTO `estados` (`id`, `nombre`, `descripcion`) VALUES
(1, 'activo', 'cuando funciona con completa normalidad'),
(2, 'invisible', 'cuando un producto es sacado temporalmente del mercado o un usuario quiere ocultarse temporalmente'),
(3, 'eliminado', 'ya no puede ser alcanzado por los usuarios nunca más'),
(4, 'bloqueado', 'se ha aplicado una censura a usuario o producto por parte del sistema'),
(5, 'vendido', 'aplicado a un chat cuando se hizo la transacción'),
(6, 'esperando', 'la transacción del chat espera el visto bueno del comprador'),
(7, 'devolviendo', 'el historial abre una solicitud de devolución, a espera de respuesta del vendedor'),
(8, 'devuelto', 'el chat finalizó con una transacción que fué cancelada'),
(9, 'censurado', 'el estado del chat era vendido, pero la administración baneó la calificación y comentario'),
(10, 'denunciado', 'cuando un usuario o producto ha sido denunciado repetidas veces, mientras se revisa el caso, no será listado públicamente, pero '),
(11, 'resuelto', 'para decir que una PQRS o denuncia ya fué tratada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `favoritos`
--
-- Creación: 10-12-2025 a las 21:55:57
--

DROP TABLE IF EXISTS `favoritos`;
CREATE TABLE `favoritos` (
  `id` int(10) UNSIGNED NOT NULL,
  `votante_id` int(10) UNSIGNED NOT NULL COMMENT 'el votante dijo que el votado era su favorito',
  `votado_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `favoritos`:
--   `votado_id`
--       `usuarios` -> `id`
--   `votante_id`
--       `usuarios` -> `id`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fotos`
--
-- Creación: 10-12-2025 a las 23:28:28
--

DROP TABLE IF EXISTS `fotos`;
CREATE TABLE `fotos` (
  `id` int(10) UNSIGNED NOT NULL,
  `producto_id` int(10) UNSIGNED NOT NULL COMMENT 'a que producto pertenecene las fotos',
  `imagen` varchar(80) NOT NULL COMMENT 'nombre del archivo con extension, para buscarlo en almacenamiento',
  `actualiza` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'cuando se cambio la foto'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `fotos`:
--   `producto_id`
--       `productos` -> `id`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `integridad`
--
-- Creación: 10-12-2025 a las 21:55:57
--

DROP TABLE IF EXISTS `integridad`;
CREATE TABLE `integridad` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(32) NOT NULL COMMENT 'ejemplo, nuevo, de segunda pero bueno, en mal estado (digamos que vende un PC malo para sacarle componentes)',
  `descripcion` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `integridad`:
--

--
-- Volcado de datos para la tabla `integridad`
--

INSERT INTO `integridad` (`id`, `nombre`, `descripcion`) VALUES
(1, 'nuevo', 'alta calidad, recién hecho o sin desempacar, sin uso'),
(2, 'usado', 'el producto está en buena calidad pero ya ha sido usado o tiene algún tipo de desgaste'),
(3, 'reparado', 'el producto puede tener fallas pero aún funciona'),
(4, 'reciclable', 'el producto está inutilizable, pero puede ser reutilizado, reparado o desarmado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `login_ip`
--
-- Creación: 10-12-2025 a las 21:55:57
--

DROP TABLE IF EXISTS `login_ip`;
CREATE TABLE `login_ip` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL COMMENT 'qué admin ingrsó al sistema',
  `ip_direccion` varchar(45) NOT NULL COMMENT 'para almacenar direcciónes IP incluso IPv6',
  `informacion` varchar(128) NOT NULL DEFAULT '' COMMENT 'por ejemplo: para datos de localización IP',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'cuándo sucedió el ingreso'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `login_ip`:
--   `usuario_id`
--       `usuarios` -> `id`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes`
--
-- Creación: 10-12-2025 a las 22:05:15
-- Última actualización: 10-12-2025 a las 23:27:18
--

DROP TABLE IF EXISTS `mensajes`;
CREATE TABLE `mensajes` (
  `id` int(10) UNSIGNED NOT NULL,
  `es_comprador` tinyint(3) UNSIGNED NOT NULL COMMENT 'el chat tiene usuario A y B, acá es 1 si lo escribió A o 0 si B',
  `chat_id` int(10) UNSIGNED NOT NULL COMMENT 'a que chat va, ahi estara el receptor',
  `mensaje` varchar(512) NOT NULL DEFAULT '' COMMENT 'el texto como tal',
  `imagen` varchar(80) NOT NULL DEFAULT '''''' COMMENT 'nombre del archivo de imagen con extension, en el almacenamiento',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'automaticamente se establece cuando se creo el mensaje'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `mensajes`:
--   `chat_id`
--       `chats` -> `id`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `motivos`
--
-- Creación: 10-12-2025 a las 21:55:57
--

DROP TABLE IF EXISTS `motivos`;
CREATE TABLE `motivos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(32) NOT NULL,
  `descripcion` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `motivos`:
--

--
-- Volcado de datos para la tabla `motivos`
--

INSERT INTO `motivos` (`id`, `nombre`, `descripcion`) VALUES
(1, 'pqrs_pregunta', 'mensaje de pregunta'),
(2, 'pqrs_queja', 'mensaje de queja'),
(3, 'pqrs_reclamo', 'mensaje de reclamo'),
(4, 'pqrs_sugerencia', 'mensaje de sugerencia'),
(5, 'pqrs_agradecimiento', 'mensaje de agradecimiento'),
(6, 'notifica_denuncia', 'se ha respondido algo ante una denuncia'),
(7, 'notifica_pqrs', 'se ha respondido algo a una PQRS'),
(8, 'notifica_comprador', 'un comprador potencial se ha puesto en contacto'),
(9, 'notifica_comunidad', 'ha llegado un mensaje enviado a todos los usuarios'),
(10, 'notifica_administrativa', 'un mensaje interno de la administración, por ejemplo, puedes haber sido baneado o eliminado'),
(11, 'notifica_bienvenida', 'mensaje de bienvenida al sistema'),
(12, 'notifica_oferta', 'un favorito ha publicado un nuevo producto'),
(13, 'notifica_venta', 'un vendedor ha enviado una solicitud de consolidar venta'),
(14, 'notifica_devolver', 'un comprador ha enviado una solicitud de cancelar una transacción'),
(15, 'notifica_exito', 'se ha llevado a cabo una compraventa exitosa'),
(16, 'notifica_cancela', 'se ha llevado a cabo una devolución exitosa, se cancelará la compraventa del historial'),
(17, 'notifica_califica', 'un comprador ha calificado o escrito un comentario, o lo ha modificado'),
(18, 'denuncia_acoso', 'comportamiento de acoso sexual en un chat o imágenes o descripciónes'),
(19, 'denuncia_bulling', 'comportamiento de burlas o insultos en un chat o imágenes o descripciónes'),
(20, 'denuncia_violencia', 'comportamiento que incita al odio o amenzada directamente'),
(21, 'denuncia_ilegal', 'comportamiento asociado a drogas, armas, prostitución y demás'),
(22, 'denuncia_troll', 'comportamiento enfocado en molestar y hacer perder el tiempo, por ejemplo, con negociaciónes por mamar gallo'),
(23, 'denuncia_fraude', 'se trata de vender algo malo o mediante trampas, tratan de tumbar al otro con fraudes'),
(24, 'denuncia_fake', 'un producto o perfil es meme o chisto o simplemente hace perder el tiempo al no ser una propuesta real'),
(25, 'denuncia_spam', 'un producto o perfil aparece muchas veces como si lo pusieran en demasia para llamar la atención'),
(26, 'denuncia_sexual', 'un perfil o producto exhibe temáticas sexuales o pornográficas que incomodan a la comunidad');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--
-- Creación: 10-12-2025 a las 21:55:57
--

DROP TABLE IF EXISTS `notificaciones`;
CREATE TABLE `notificaciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL COMMENT 'quién recibirá la notificación',
  `motivo_id` int(10) UNSIGNED NOT NULL COMMENT 'naturaleza de la notificación',
  `mensaje` varchar(255) NOT NULL COMMENT 'cuerpo de la notificación',
  `visto` tinyint(3) UNSIGNED NOT NULL COMMENT 'true si ya fué abierta por el usuario',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `notificaciones`:
--   `motivo_id`
--       `motivos` -> `id`
--   `usuario_id`
--       `usuarios` -> `id`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `papelera`
--
-- Creación: 10-12-2025 a las 22:03:53
--

DROP TABLE IF EXISTS `papelera`;
CREATE TABLE `papelera` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL COMMENT 'quién es responsable de esta edición',
  `mensaje` varchar(512) NOT NULL DEFAULT '' COMMENT 'texto que fué editado sea en perfil o producto o calificación de producto, cualquier parte donde se pueda editar',
  `imagen` varchar(80) NOT NULL DEFAULT '' COMMENT 'nombre del archivo de imagen con extension, en el almacenamiento',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'cuándo se hizo el registro, la edición'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `papelera`:
--   `usuario_id`
--       `usuarios` -> `id`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pqrs`
--
-- Creación: 10-12-2025 a las 21:55:57
--

DROP TABLE IF EXISTS `pqrs`;
CREATE TABLE `pqrs` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL COMMENT 'quién hizo la PQRS',
  `mensaje` varchar(512) NOT NULL COMMENT 'mensaje del cuerpo',
  `motivo_id` int(10) UNSIGNED NOT NULL COMMENT 'para saber si es P, Q, R, S',
  `estado_id` int(10) UNSIGNED NOT NULL COMMENT 'para saber si ya se proceso la PQRS',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `pqrs`:
--   `estado_id`
--       `estados` -> `id`
--   `motivo_id`
--       `motivos` -> `id`
--   `usuario_id`
--       `usuarios` -> `id`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--
-- Creación: 10-12-2025 a las 23:24:46
-- Última actualización: 10-12-2025 a las 23:24:46
--

DROP TABLE IF EXISTS `productos`;
CREATE TABLE `productos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(64) NOT NULL,
  `subcategoria_id` int(10) UNSIGNED NOT NULL COMMENT 'la subcategoria incluye a la categoria, por ejemplo, electrodomesticos, mobiliario, alimento, etc',
  `integridad_id` int(10) UNSIGNED NOT NULL COMMENT 'para saber si el producto es nuevo, de segunda pero en buen estado o si es un producto con fallas',
  `vendedor_id` int(10) UNSIGNED NOT NULL COMMENT 'apunta al id de usuario que es su creador',
  `estado_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'l estado define si esta visible, eliminado, bloqueado',
  `descripcion` varchar(512) NOT NULL COMMENT 'texto amplio describiendo las caracteristicas del producto',
  `precio` float NOT NULL COMMENT 'cuanto cuesta en COP',
  `disponibles` smallint(5) UNSIGNED NOT NULL COMMENT 'cuandos articulos hay disponibles',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'esto solo se coloca automaticamente al crear el registro, y se deja asi',
  `fecha_actualiza` timestamp NOT NULL DEFAULT '2000-01-01 05:00:00' COMMENT 'esto se actualiza cuando hay edicion por parte del propietario, es para evitar actualizaciones muy seguidas, opcionalmente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `productos`:
--   `estado_id`
--       `estados` -> `id`
--   `subcategoria_id`
--       `subcategorias` -> `id`
--   `integridad_id`
--       `integridad` -> `id`
--   `vendedor_id`
--       `usuarios` -> `id`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--
-- Creación: 10-12-2025 a las 21:55:57
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `roles`:
--

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`) VALUES
(1, 'master'),
(2, 'administrador'),
(3, 'prosumer');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `subcategorias`
--
-- Creación: 10-12-2025 a las 21:55:57
--

DROP TABLE IF EXISTS `subcategorias`;
CREATE TABLE `subcategorias` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(32) NOT NULL COMMENT 'por ejemplo, para la categoria ropa tenemos: calzado, pantalon, camisa, sombrero, etc',
  `categoria_id` int(10) UNSIGNED NOT NULL COMMENT 'a que categoria pertenece la subcategoria'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `subcategorias`:
--   `categoria_id`
--       `categorias` -> `id`
--

--
-- Volcado de datos para la tabla `subcategorias`
--

INSERT INTO `subcategorias` (`id`, `nombre`, `categoria_id`) VALUES
(1, 'otro', 2),
(2, 'postre o helado', 2),
(3, 'fruta o verdura fresca', 2),
(4, 'carne o huevos', 2),
(5, 'especias o aditivos', 2),
(6, 'almuerzo o desayuno', 2),
(7, 'chatarra preparada', 2),
(8, 'chatarra industrial', 2),
(9, 'pan o pastel', 2),
(10, 'bebidas', 2),
(21, 'otro', 5),
(22, 'cuidado de la piel', 5),
(23, 'cuidado del pelo', 5),
(24, 'labial', 5),
(25, 'sombra', 5),
(26, 'delineador', 5),
(27, 'piercing', 5),
(28, 'tatuaje', 5),
(29, 'maniquiur', 5),
(30, 'peluqueria', 5),
(31, 'otro', 6),
(32, 'balón', 6),
(33, 'pesas', 6),
(34, 'suplemento alimenticio', 6),
(35, 'patineta o patines', 6),
(36, 'implementos acuaticos', 6),
(37, 'implementos terrestres', 6),
(38, 'implementos extremos', 6),
(39, 'arte marcial o lucha', 6),
(40, 'aseo deportivo', 6),
(41, 'otro', 7),
(42, 'computador de escritorio', 7),
(43, 'computador portátil', 7),
(44, 'periféricos para computador', 7),
(45, 'celular', 7),
(46, 'cámara fotográfica', 7),
(47, 'calculadora o mediciónes', 7),
(48, 'tableta para arte', 7),
(49, 'audifónos, reloj o corporales', 7),
(50, 'sistema de seguridad', 7),
(51, 'otro', 4),
(52, 'taladro, pulidora o similar', 4),
(53, 'martillo, alicate o similar', 4),
(54, 'licuadora, microondas o similar', 4),
(55, 'seguridad para ajustar bicicleta', 4),
(56, 'escuadra o regla para dibujo', 4),
(57, 'metro o medidores', 4),
(58, 'tijera, visturí o similar', 4),
(59, 'ventilador o aire acondicionado', 4),
(60, 'kit de mecánico', 4),
(61, 'otro', 4),
(62, 'taladro, pulidora o similar', 4),
(63, 'martillo, alicate o similar', 4),
(64, 'licuadora, microondas o similar', 4),
(65, 'seguridad para ajustar bicicleta', 4),
(66, 'escuadra o regla para dibujo', 4),
(67, 'metro o medidores', 4),
(68, 'tijera, visturí o similar', 4),
(69, 'ventilador o aire acondicionado', 4),
(70, 'kit de mecánico', 4),
(71, 'otro', 12),
(72, 'perro', 12),
(73, 'gato', 12),
(74, 'pez', 12),
(75, 'alimento', 12),
(76, 'correa', 12),
(77, 'juguete', 12),
(78, 'roedor', 12),
(79, 'tapete', 12),
(80, 'ropa', 12),
(81, 'otro', 10),
(82, 'silla regulable', 10),
(83, 'silla estática', 10),
(84, 'sillón grande', 10),
(85, 'mesa', 10),
(86, 'cama o colchón', 10),
(87, 'matera', 10),
(88, 'armario o nochero', 10),
(89, 'escritorio', 10),
(90, 'tapete', 10),
(91, 'otro', 13),
(92, 'otro', 3),
(93, 'cartónes o cajas', 3),
(94, 'telas y costura', 3),
(95, 'pegamentos', 3),
(96, 'cuadernos, carpetas', 3),
(97, 'colores, pinturas, pinceles', 3),
(98, 'libros', 3),
(99, 'lápices, marcadores, lapiceros', 3),
(100, 'borradores, sacapuntas', 3),
(101, 'papel, fomi, cartulina', 3),
(102, 'otro', 8),
(103, 'entrenamiento deportivo', 8),
(104, 'eseñanza artística', 8),
(105, 'enseñanza tecnológica', 8),
(106, 'mantenimiento computadora', 8),
(107, 'reparación dispositivos', 8),
(108, 'preparación de comidas', 8),
(109, 'documentación', 8),
(110, 'creación de arte o manualidades', 8),
(111, 'cuidado y aseo', 8),
(112, 'otro', 9),
(113, 'juego deportivo', 9),
(114, 'juego videojuegos', 9),
(115, 'practicar idiomas', 9),
(116, 'fiesta y baile', 9),
(117, 'charlar', 9),
(118, 'emprendimiento', 9),
(119, 'paseos y viajes', 9),
(120, 'seguir en redes', 9),
(121, 'religión e ideologías', 9),
(122, 'otro', 11),
(123, 'bicicleta', 11),
(124, 'moto de gasolina', 11),
(125, 'casco de moto', 11),
(126, 'moto eléctrica', 11),
(127, 'bici o patín eléctricos', 11),
(128, 'carro', 11),
(129, 'chaleco, guantes o vestimenta', 11),
(130, 'repuesto', 11),
(131, 'parrillas o implementos', 11),
(132, 'otro', 1),
(133, 'calzado', 1),
(134, 'sombrero', 1),
(135, 'pantalón', 1),
(136, 'camisa', 1),
(137, 'vestido', 1),
(138, 'falda', 1),
(139, 'medias o guantes', 1),
(140, 'chaleco o buzo', 1),
(141, 'colgandijas', 1),
(142, 'colgantes', 14),
(143, 'figurillas', 14),
(144, 'materas o jardín', 14),
(145, 'de metal', 14),
(146, 'de plástico', 14),
(147, 'de madera', 14),
(148, 'de porcelana', 14),
(149, 'afiches o pinturas', 14),
(150, 'peluches', 14),
(151, 'otro', 14);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sucesos`
--
-- Creación: 10-12-2025 a las 21:55:57
--

DROP TABLE IF EXISTS `sucesos`;
CREATE TABLE `sucesos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(32) NOT NULL,
  `descripcion` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `sucesos`:
--

--
-- Volcado de datos para la tabla `sucesos`
--

INSERT INTO `sucesos` (`id`, `nombre`, `descripcion`) VALUES
(1, 'estado_usuario', 'ha cambiado el estado de un usuario, por ejemplo a activo, eliminado, baneado'),
(2, 'rol_cambiado', 'se ha modificado que un usuario sea o deje de ser administrador'),
(3, 'ver_chat', 'buscando ilegalidades ha entrado a revisar una conversación'),
(4, 'enviar_mail', 'ha enviado un mail a un usuario, lo que también disparará una notificación'),
(5, 'constante_modificada', 'creó, destruyó o editó una constante de la DB por ejemplo, categorías'),
(6, 'cambio_password', 'obtuvo una clave de acceso para recuperar una contraseña o crear una cuenta sin correo institucional'),
(7, 'noticia_masiva', 'envió una notificación y email a todos los usuarios'),
(8, 'estado_producto', 'cambio un producto poniéndolo como eliminado o activo por ejemplo'),
(9, 'respuesta_pqrs', 'marcó una PQRS como resuelta ya que hizo alguna acción para atenderla'),
(10, 'respuesta_denuncia', 'marcó una denuncia como resuelta pues confirma que hizo algo para atenderla'),
(11, 'estado_chat', 'modificó la visibilidad de un historial de compraventa, posiblemente deshabilitando calificación y comentario');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--
-- Creación: 10-12-2025 a las 23:26:22
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `cuenta_id` int(10) UNSIGNED NOT NULL,
  `nickname` varchar(32) NOT NULL COMMENT 'el nickname del usuario, pueden repetirse',
  `imagen` varchar(80) NOT NULL COMMENT 'nombre del archivo de imagen con extension, en el almacenamiento',
  `descripcion` varchar(512) NOT NULL DEFAULT '''''' COMMENT 'para que el usuario diga algo sobre si mismo en su perfil',
  `link` varchar(128) NOT NULL DEFAULT '''''' COMMENT 'si el usuario quiere compartir redes sociales o algo asi',
  `rol_id` int(10) UNSIGNED NOT NULL DEFAULT 3 COMMENT 'administra los permisos de acceso al sistema',
  `estado_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'esto dice si el usuario esta pendiente de aprobacion, bloqueado del sistema, eliminado, etc',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'esto no se cambia, solo se pone automaticamente cuando el usuario se registra',
  `fecha_actualiza` timestamp NOT NULL DEFAULT '2000-01-01 05:00:00' COMMENT 'se actualizara cada que el usuario edita su perfil, para dar una ventana de tiempo entre ediciones',
  `fecha_reciente` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'utilizado para saber si el usuario ha estado activo recientemente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `usuarios`:
--   `cuenta_id`
--       `cuentas` -> `id`
--   `estado_id`
--       `estados` -> `id`
--   `rol_id`
--       `roles` -> `id`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vistos`
--
-- Creación: 10-12-2025 a las 21:55:57
--

DROP TABLE IF EXISTS `vistos`;
CREATE TABLE `vistos` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL COMMENT 'quien observo el producto',
  `producto_id` int(10) UNSIGNED NOT NULL COMMENT 'que producto fue observado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `vistos`:
--   `producto_id`
--       `productos` -> `id`
--   `usuario_id`
--       `usuarios` -> `id`
--

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `auditorias`
--
ALTER TABLE `auditorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `auditoria_usuario` (`administrador_id`),
  ADD KEY `auditoria_suceso` (`suceso_id`);

--
-- Indices de la tabla `bloqueados`
--
ALTER TABLE `bloqueados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_bloqueado` (`bloqueado_id`),
  ADD KEY `usuario_bloqueador` (`bloqueador_id`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `chats`
--
ALTER TABLE `chats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chat_usuario_a` (`comprador_id`),
  ADD KEY `chat_producto` (`producto_id`),
  ADD KEY `chat_estado` (`estado_id`);

--
-- Indices de la tabla `cuentas`
--
ALTER TABLE `cuentas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `denuncias`
--
ALTER TABLE `denuncias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `denuncia_chat` (`chat_id`),
  ADD KEY `denuncia_usuario` (`usuario_id`),
  ADD KEY `denuncia_producto` (`producto_id`),
  ADD KEY `denuncia_estado` (`estado_id`),
  ADD KEY `denuncia_motivo` (`motivo_id`),
  ADD KEY `denuncia_denunciante` (`denunciante_id`);

--
-- Indices de la tabla `estados`
--
ALTER TABLE `estados`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_votante` (`votante_id`),
  ADD KEY `usuario_votado` (`votado_id`) USING BTREE;

--
-- Indices de la tabla `fotos`
--
ALTER TABLE `fotos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `filename` (`imagen`),
  ADD KEY `foto_producto` (`producto_id`);

--
-- Indices de la tabla `integridad`
--
ALTER TABLE `integridad`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `login_ip`
--
ALTER TABLE `login_ip`
  ADD PRIMARY KEY (`id`),
  ADD KEY `login_usuario` (`usuario_id`);

--
-- Indices de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mensaje_chat` (`chat_id`);

--
-- Indices de la tabla `motivos`
--
ALTER TABLE `motivos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifi_motivo` (`motivo_id`),
  ADD KEY `notifi_usuario` (`usuario_id`);

--
-- Indices de la tabla `papelera`
--
ALTER TABLE `papelera`
  ADD PRIMARY KEY (`id`),
  ADD KEY `papelera_usuario` (`usuario_id`);

--
-- Indices de la tabla `pqrs`
--
ALTER TABLE `pqrs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pqrs_estado` (`estado_id`),
  ADD KEY `pqrs_motivo` (`motivo_id`),
  ADD KEY `pqrs_usuario` (`usuario_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_usuario` (`vendedor_id`),
  ADD KEY `producto_subcategoria` (`subcategoria_id`),
  ADD KEY `producto_uso` (`integridad_id`),
  ADD KEY `producto_estado` (`estado_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `subcategorias`
--
ALTER TABLE `subcategorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subcategoria_categoria` (`categoria_id`);

--
-- Indices de la tabla `sucesos`
--
ALTER TABLE `sucesos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nickname` (`nickname`),
  ADD KEY `usuario_estado` (`estado_id`),
  ADD KEY `usuario_rol` (`rol_id`),
  ADD KEY `usuario_cuenta` (`cuenta_id`);

--
-- Indices de la tabla `vistos`
--
ALTER TABLE `vistos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `visto_producto` (`producto_id`),
  ADD KEY `visto_usuario` (`usuario_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `auditorias`
--
ALTER TABLE `auditorias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `bloqueados`
--
ALTER TABLE `bloqueados`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `chats`
--
ALTER TABLE `chats`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cuentas`
--
ALTER TABLE `cuentas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `denuncias`
--
ALTER TABLE `denuncias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estados`
--
ALTER TABLE `estados`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `fotos`
--
ALTER TABLE `fotos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `integridad`
--
ALTER TABLE `integridad`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `login_ip`
--
ALTER TABLE `login_ip`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `motivos`
--
ALTER TABLE `motivos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `papelera`
--
ALTER TABLE `papelera`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pqrs`
--
ALTER TABLE `pqrs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `subcategorias`
--
ALTER TABLE `subcategorias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=152;

--
-- AUTO_INCREMENT de la tabla `sucesos`
--
ALTER TABLE `sucesos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vistos`
--
ALTER TABLE `vistos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `auditorias`
--
ALTER TABLE `auditorias`
  ADD CONSTRAINT `auditoria_suceso` FOREIGN KEY (`suceso_id`) REFERENCES `sucesos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `auditoria_usuario` FOREIGN KEY (`administrador_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `bloqueados`
--
ALTER TABLE `bloqueados`
  ADD CONSTRAINT `usuario_bloqueado` FOREIGN KEY (`bloqueado_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `usuario_bloqueador` FOREIGN KEY (`bloqueador_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `chats`
--
ALTER TABLE `chats`
  ADD CONSTRAINT `chat_estado` FOREIGN KEY (`estado_id`) REFERENCES `estados` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `chat_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `chat_usuario_a` FOREIGN KEY (`comprador_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `denuncias`
--
ALTER TABLE `denuncias`
  ADD CONSTRAINT `denuncia_chat` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `denuncia_denunciante` FOREIGN KEY (`denunciante_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `denuncia_estado` FOREIGN KEY (`estado_id`) REFERENCES `estados` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `denuncia_motivo` FOREIGN KEY (`motivo_id`) REFERENCES `motivos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `denuncia_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `denuncia_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `usuario_votado` FOREIGN KEY (`votado_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `usuario_votante` FOREIGN KEY (`votante_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `fotos`
--
ALTER TABLE `fotos`
  ADD CONSTRAINT `foto_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `login_ip`
--
ALTER TABLE `login_ip`
  ADD CONSTRAINT `login_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD CONSTRAINT `mensaje_chat` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notifi_motivo` FOREIGN KEY (`motivo_id`) REFERENCES `motivos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `notifi_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `papelera`
--
ALTER TABLE `papelera`
  ADD CONSTRAINT `papelera_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pqrs`
--
ALTER TABLE `pqrs`
  ADD CONSTRAINT `pqrs_estado` FOREIGN KEY (`estado_id`) REFERENCES `estados` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `pqrs_motivo` FOREIGN KEY (`motivo_id`) REFERENCES `motivos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `pqrs_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `producto_estado` FOREIGN KEY (`estado_id`) REFERENCES `estados` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `producto_subcategoria` FOREIGN KEY (`subcategoria_id`) REFERENCES `subcategorias` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `producto_uso` FOREIGN KEY (`integridad_id`) REFERENCES `integridad` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `producto_usuario` FOREIGN KEY (`vendedor_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `subcategorias`
--
ALTER TABLE `subcategorias`
  ADD CONSTRAINT `subcategoria_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuario_cuenta` FOREIGN KEY (`cuenta_id`) REFERENCES `cuentas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `usuario_estado` FOREIGN KEY (`estado_id`) REFERENCES `estados` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `usuario_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `vistos`
--
ALTER TABLE `vistos`
  ADD CONSTRAINT `visto_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `visto_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
