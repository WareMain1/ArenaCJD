
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT;
SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS;
SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION;
SET NAMES utf8mb4;

CREATE TABLE `auditoria` (
  `id_auditoria` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED DEFAULT NULL,
  `accion` varchar(80) NOT NULL,
  `entidad` varchar(60) NOT NULL,
  `id_entidad` varchar(80) DEFAULT NULL,
  `detalle` varchar(500) DEFAULT NULL,
  `resultado` enum('exito','error','denegado') NOT NULL DEFAULT 'exito',
  `fecha_evento` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `auditoria` (`id_auditoria`, `id_usuario`, `accion`, `entidad`, `id_entidad`, `detalle`, `resultado`, `fecha_evento`) VALUES
(1, 1, 'torneo_eliminado', 'torneo', '4', '155455', 'exito', '2026-08-31 23:32:11'),
(2, 1, 'torneo_eliminado', 'torneo', '3', 'hola', 'exito', '2026-08-31 23:32:16'),
(3, 1, 'torneo_creado', 'torneo', '5', 'Prueba', 'exito', '2026-08-31 23:35:35'),
(4, 1, 'equipo_creado', 'equipo', '1', 'LeonesFC', 'exito', '2026-08-31 23:36:33'),
(5, 2, 'login_exitoso', 'sesion', NULL, 'Inicio de sesión correcto', 'exito', '2026-08-31 23:48:08'),
(6, 1, 'login_exitoso', 'sesion', NULL, 'Inicio de sesión correcto', 'exito', '2026-08-31 23:49:40'),
(7, 1, 'torneo_actualizado', 'torneo', '5', 'Prueba', 'exito', '2026-08-31 23:50:17'),
(8, 1, 'foto_perfil_actualizada', 'usuario', '1', NULL, 'exito', '2026-08-31 23:52:14'),
(9, 1, 'torneo_creado', 'torneo', '6', 'hola', 'exito', '2026-08-31 23:54:31'),
(10, 1, 'torneo_actualizado', 'torneo', '6', 'hola', 'exito', '2026-08-31 23:54:39'),
(11, 1, 'login_exitoso', 'sesion', NULL, 'Inicio de sesión correcto', 'exito', '2026-08-31 23:57:11'),
(12, 1, 'equipo_actualizado', 'equipo', '1', 'LeonesFC · activo', 'exito', '2026-08-31 23:58:21'),
(13, 2, 'login_exitoso', 'sesion', NULL, 'Inicio de sesión correcto', 'exito', '2026-08-31 23:58:26'),
(14, 1, 'login_exitoso', 'sesion', NULL, 'Inicio de sesión correcto', 'exito', '2026-08-31 23:58:57'),
(15, 1, 'equipo_eliminado', 'equipo', '1', 'LeonesFC', 'exito', '2026-09-01 00:00:47'),
(16, 4, 'cuenta_registrada', 'usuario', '4', '@root', 'exito', '2026-09-01 00:01:52'),
(17, 1, 'login_exitoso', 'sesion', NULL, 'Inicio de sesión correcto', 'exito', '2026-09-01 00:01:58'),
(18, 1, 'usuario_aprobado', 'usuario', '4', '@root', 'exito', '2026-09-01 00:02:06'),
(19, 1, 'usuario_actualizado', 'usuario', '4', 'activo · administrador, organizador, participante', 'exito', '2026-09-01 00:02:19'),
(20, 4, 'login_exitoso', 'sesion', NULL, 'Inicio de sesión correcto', 'exito', '2026-09-01 00:02:33'),
(21, 4, 'torneo_creado', 'torneo', '7', 'gtyg', 'exito', '2026-09-01 00:03:42'),
(22, NULL, 'cuenta_registrada', 'usuario', '5', '@perol', 'exito', '2026-09-01 00:04:25'),
(23, 4, 'login_exitoso', 'sesion', NULL, 'Inicio de sesión correcto', 'exito', '2026-09-01 00:04:29'),
(24, 4, 'usuario_rechazado', 'usuario', '5', '@perol', 'exito', '2026-09-01 00:04:35'),
(25, 4, 'usuario_eliminado', 'usuario', '5', '@perol', 'exito', '2026-09-01 00:04:44'),
(26, 4, 'equipo_creado', 'equipo', '2', 'fdsfds', 'exito', '2026-09-01 00:08:45'),
(27, 2, 'login_exitoso', 'sesion', NULL, 'Inicio de sesión correcto', 'exito', '2026-09-01 00:08:50'),
(28, 4, 'login_exitoso', 'sesion', NULL, 'Inicio de sesión correcto', 'exito', '2026-09-01 00:09:12'),
(29, 4, 'equipo_eliminado', 'equipo', '2', 'fdsfds', 'exito', '2026-09-01 00:13:00'),
(30, 4, 'invitacion_enviada', 'invitacion', '1', '@membrillo · gtyg', 'exito', '2026-09-01 00:35:41'),
(31, 2, 'login_exitoso', 'sesion', NULL, 'Inicio de sesión correcto', 'exito', '2026-09-01 00:35:46'),
(32, 2, 'invitacion_aceptada', 'invitacion', '1', NULL, 'exito', '2026-09-01 00:36:05'),
(33, 2, 'login_exitoso', 'sesion', NULL, 'Inicio de sesión correcto', 'exito', '2026-09-01 00:36:15'),
(34, 4, 'login_exitoso', 'sesion', NULL, 'Inicio de sesión correcto', 'exito', '2026-09-01 00:36:24'),
(35, 1, 'login_exitoso', 'sesion', NULL, 'Inicio de sesión correcto', 'exito', '2026-09-01 02:50:08'),
(36, NULL, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @wqwe', 'denegado', '2026-09-01 03:18:13'),
(37, NULL, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @wqwe', 'denegado', '2026-09-01 03:18:14'),
(38, NULL, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @wqwe', 'denegado', '2026-09-01 03:18:15'),
(39, NULL, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @wqwe', 'denegado', '2026-09-01 03:18:16'),
(40, NULL, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @wqwe', 'denegado', '2026-09-01 03:18:16'),
(41, NULL, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @wqwe', 'denegado', '2026-09-01 03:18:17'),
(42, NULL, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @wqwe', 'denegado', '2026-09-01 03:18:18'),
(43, NULL, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @wqwe', 'denegado', '2026-09-01 03:18:22'),
(44, NULL, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @wqwe', 'denegado', '2026-09-01 03:18:23'),
(45, NULL, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @wqwe', 'denegado', '2026-09-01 03:18:23'),
(46, NULL, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @wqwe', 'denegado', '2026-09-01 03:18:24'),
(47, NULL, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @wqwe', 'denegado', '2026-09-01 03:18:24'),
(48, NULL, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @wqwe', 'denegado', '2026-09-01 03:18:25'),
(49, NULL, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @wqwe', 'denegado', '2026-09-01 03:18:25'),
(50, NULL, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @wqwe', 'denegado', '2026-09-01 03:18:25'),
(51, NULL, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @wqwe', 'denegado', '2026-09-01 03:18:26'),
(52, NULL, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @wqwe', 'denegado', '2026-09-01 03:18:27'),
(53, NULL, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @wqwe', 'denegado', '2026-09-01 03:18:27'),
(54, NULL, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @wqwe', 'denegado', '2026-09-01 03:18:28'),
(55, NULL, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @wqwe', 'denegado', '2026-09-01 03:18:28'),
(56, 4, 'login_exitoso', 'sesion', NULL, 'Inicio de sesión correcto', 'exito', '2026-09-01 03:19:06'),
(57, 4, 'foto_perfil_actualizada', 'usuario', '4', NULL, 'exito', '2026-09-01 03:40:26'),
(58, 4, 'foto_perfil_actualizada', 'usuario', '4', NULL, 'exito', '2026-09-01 03:40:51'),
(59, 4, 'login_exitoso', 'sesion', NULL, 'Inicio de sesión correcto', 'exito', '2026-09-03 22:07:15'),
(60, 4, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @root', 'denegado', '2026-09-03 22:07:29'),
(61, 4, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @root', 'denegado', '2026-09-03 22:07:30'),
(62, 4, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @root', 'denegado', '2026-09-03 22:07:31'),
(63, 4, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @root', 'denegado', '2026-09-03 22:07:31'),
(64, 4, 'login_fallido', 'sesion', NULL, 'Intento de acceso para @root', 'denegado', '2026-09-03 22:07:32'),
(65, 4, 'login_bloqueado_temporal', 'sesion', NULL, 'Bloqueo automático por 5 intentos fallidos', 'denegado', '2026-09-03 22:07:32'),
(66, 4, 'login_bloqueado_temporal', 'sesion', NULL, 'Intento durante bloqueo temporal', 'denegado', '2026-09-03 22:09:34'),
(67, 4, 'login_exitoso', 'sesion', NULL, 'Inicio de sesión correcto', 'exito', '2026-09-03 22:40:26'),
(68, 4, 'login_exitoso', 'sesion', NULL, 'Inicio de sesión correcto', 'exito', '2026-09-04 20:42:39');

CREATE TABLE `categorias` (
  `id_categoria` tinyint(3) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categorias` (`id_categoria`, `nombre`) VALUES
(1, 'Libre'),
(4, 'Mayores'),
(5, 'Mixto'),
(3, 'Sub-16'),
(2, 'Sub-18');

CREATE TABLE `disciplinas` (
  `id_disciplina` tinyint(3) UNSIGNED NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `estado` enum('activa','inactiva') NOT NULL DEFAULT 'activa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `disciplinas` (`id_disciplina`, `nombre`, `estado`) VALUES
(1, 'Vóleibol', 'activa'),
(2, 'carro', 'activa'),
(3, 'eSports', 'activa'),
(4, 'Tenis de mesa', 'activa'),
(5, 'carrs', 'activa'),
(6, 'Juego de cartas', 'activa'),
(7, 'asdasdasd', 'activa');

CREATE TABLE `disciplina_categoria` (
  `id_disciplina` tinyint(3) UNSIGNED NOT NULL,
  `id_categoria` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `disciplina_tipo_torneo` (
  `id_disciplina` tinyint(3) UNSIGNED NOT NULL,
  `id_tipo_torneo` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `enfrentamientos` (
  `id_enfrentamiento` int(10) UNSIGNED NOT NULL,
  `id_torneo` int(10) UNSIGNED NOT NULL,
  `numero_ronda` int(11) NOT NULL DEFAULT 1,
  `orden_ronda` int(11) NOT NULL DEFAULT 1,
  `ronda` varchar(60) NOT NULL,
  `tipo_participante` enum('individual','equipo') NOT NULL,
  `id_usuario_a` int(10) UNSIGNED DEFAULT NULL,
  `id_usuario_b` int(10) UNSIGNED DEFAULT NULL,
  `id_equipo_a` int(10) UNSIGNED DEFAULT NULL,
  `id_equipo_b` int(10) UNSIGNED DEFAULT NULL,
  `puntaje_a` int(11) DEFAULT NULL,
  `puntaje_b` int(11) DEFAULT NULL,
  `estado` enum('pendiente','programado','en_curso','en_periodo_gracia','pendiente_revision','finalizado','cancelado') NOT NULL DEFAULT 'pendiente',
  `incomparecencia` enum('ninguna','a','b','ambos') NOT NULL DEFAULT 'ninguna',
  `resolucion_motivo` varchar(100) DEFAULT NULL,
  `resolucion_origen` enum('manual','sistema_automatico') NOT NULL DEFAULT 'manual',
  `fecha_resolucion` datetime DEFAULT NULL,
  `fecha_hora` datetime DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `equipos` (
  `id_equipo` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `id_creador` int(10) UNSIGNED NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `inscripciones_equipos` (
  `id_inscripcion` int(10) UNSIGNED NOT NULL,
  `id_torneo` int(10) UNSIGNED NOT NULL,
  `id_equipo` int(10) UNSIGNED NOT NULL,
  `fecha_inscripcion` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` enum('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `inscripciones_individuales` (
  `id_inscripcion` int(10) UNSIGNED NOT NULL,
  `id_torneo` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `fecha_inscripcion` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` enum('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `inscripciones_individuales` (`id_inscripcion`, `id_torneo`, `id_usuario`, `fecha_inscripcion`, `estado`) VALUES
(1, 7, 2, '2026-08-31 21:36:05', 'aprobada');

CREATE TABLE `integrantes_equipo` (
  `id_equipo` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `fecha_alta` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `integrantes_inscripcion_equipo` (
  `id_inscripcion` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `invitaciones_equipo` (
  `id_invitacion_equipo` int(10) UNSIGNED NOT NULL,
  `id_equipo` int(10) UNSIGNED NOT NULL,
  `id_invitador` int(10) UNSIGNED NOT NULL,
  `id_invitado` int(10) UNSIGNED NOT NULL,
  `estado` enum('pendiente','aceptada','rechazada','cancelada') NOT NULL DEFAULT 'pendiente',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_respuesta` datetime DEFAULT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `invitaciones_torneo` (
  `id_invitacion` int(10) UNSIGNED NOT NULL,
  `id_torneo` int(10) UNSIGNED NOT NULL,
  `id_invitador` int(10) UNSIGNED NOT NULL,
  `id_invitado` int(10) UNSIGNED NOT NULL,
  `tipo` enum('individual','equipo') NOT NULL DEFAULT 'individual',
  `id_equipo` int(10) UNSIGNED DEFAULT NULL,
  `estado` enum('pendiente','aceptada','rechazada','cancelada') NOT NULL DEFAULT 'pendiente',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_respuesta` datetime DEFAULT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `invitaciones_torneo` (`id_invitacion`, `id_torneo`, `id_invitador`, `id_invitado`, `tipo`, `id_equipo`, `estado`, `fecha_creacion`, `fecha_respuesta`, `fecha_actualizacion`) VALUES
(1, 7, 4, 2, 'individual', NULL, 'aceptada', '2026-09-01 00:35:41', '2026-08-31 21:36:05', '2026-09-01 00:36:05');

CREATE TABLE `preferencias_usuario` (
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `configuracion_json` longtext NOT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles` (
  `id_rol` tinyint(3) UNSIGNED NOT NULL,
  `nombre` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id_rol`, `nombre`) VALUES
(1, 'administrador'),
(2, 'organizador'),
(3, 'participante');

CREATE TABLE `tipos_torneo` (
  `id_tipo_torneo` tinyint(3) UNSIGNED NOT NULL,
  `nombre` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tipos_torneo` (`id_tipo_torneo`, `nombre`) VALUES
(2, 'Eliminación directa'),
(3, 'Liga'),
(1, 'Sistema suizo');

CREATE TABLE `torneos` (
  `id_torneo` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `id_disciplina` tinyint(3) UNSIGNED NOT NULL,
  `id_categoria` tinyint(3) UNSIGNED NOT NULL,
  `id_tipo_torneo` tinyint(3) UNSIGNED NOT NULL,
  `id_organizador` int(10) UNSIGNED NOT NULL,
  `modalidad` enum('individual','equipo') NOT NULL,
  `fecha_inicio` date NOT NULL,
  `hora_inicio` time NOT NULL DEFAULT '09:00:00',
  `fecha_fin` date NOT NULL,
  `estado` enum('borrador','inscripciones','en_curso','finalizado','cancelado') NOT NULL DEFAULT 'borrador',
  `publicado` tinyint(1) NOT NULL DEFAULT 0,
  `cupo_maximo` smallint(5) UNSIGNED DEFAULT NULL,
  `periodo_gracia_resultado` int(10) UNSIGNED NOT NULL DEFAULT 60,
  `regla_desempate` enum('ninguna','diferencia_puntos','penales_prorroga') NOT NULL DEFAULT 'diferencia_puntos',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `torneos` (`id_torneo`, `nombre`, `id_disciplina`, `id_categoria`, `id_tipo_torneo`, `id_organizador`, `modalidad`, `fecha_inicio`, `hora_inicio`, `fecha_fin`, `estado`, `publicado`, `cupo_maximo`, `fecha_creacion`) VALUES
(5, 'Prueba', 2, 1, 2, 1, 'individual', '2026-08-31', '21:00:00', '2026-08-31', 'en_curso', 0, 1, '2026-08-31 20:35:35'),
(6, 'hola', 2, 1, 2, 1, 'individual', '2026-08-31', '21:00:00', '2026-08-31', 'en_curso', 0, NULL, '2026-08-31 20:54:31'),
(7, 'gtyg', 7, 1, 2, 4, 'individual', '2026-08-31', '22:00:00', '2026-08-31', 'en_curso', 0, 4, '2026-08-31 21:03:42');

CREATE TABLE `usuarios` (
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `nombre_completo` varchar(120) NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `correo` varchar(120) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `pregunta_recuperacion` varchar(150) DEFAULT NULL,
  `respuesta_recuperacion` varchar(255) DEFAULT NULL,
  `version_sesion` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `recuperacion_intentos` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `recuperacion_bloqueada_hasta` datetime DEFAULT NULL,
  `login_intentos` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `login_bloqueado_hasta` datetime DEFAULT NULL,
  `estado` enum('pendiente','activo','inactivo','bloqueado') NOT NULL DEFAULT 'pendiente',
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `usuarios` (`id_usuario`, `nombre_completo`, `nombre_usuario`, `correo`, `contrasena`, `pregunta_recuperacion`, `respuesta_recuperacion`, `version_sesion`, `recuperacion_intentos`, `recuperacion_bloqueada_hasta`, `login_intentos`, `login_bloqueado_hasta`, `estado`, `fecha_registro`) VALUES
(1, 'Juan perez', 'juansito', 'psycho.music19@gmail.com', '$2y$10$wzW/GbFeOpbma1MmqQJ3BeeoBUfHeTjKcNEeIz2BeaEYWZUd1w.wG', NULL, NULL, 1, 0, NULL, 0, NULL, 'activo', '2026-08-30 21:25:46'),
(2, 'Membrillo', 'membrillo', 'membrillo@gmail.com', '$2y$10$.qggZsGx6HShev.89PlSQOzjy3FRo7Y6XqirXz9TSb2m0NCg61MMO', NULL, NULL, 1, 0, NULL, 0, NULL, 'activo', '2026-08-31 00:08:19'),
(3, 'Prueba', 'prueba123', 'psadas@gmail.com', '$2y$10$etG7V0ju08rBvcXb3ACSQeqSUTV7h6cOYYH0uMjthT.cHUsnuHw..', NULL, NULL, 1, 0, NULL, 0, NULL, 'activo', '2026-08-31 00:55:10'),
(4, 'Ronny', 'root', 'root@gmail.com', '$2y$10$/HVYxnCDFvMWXeBKnfRPnennrxOnGShk1aqdOzCwJvVAN.dZV363y', '¿Cuál era el nombre de tu primera mascota?', '$2y$10$AeuHRYkEhsax6ZD3IJKcnOvac4VIU63iGVK8DuENY1d8qCrELiPlW', 1, 0, NULL, 0, NULL, 'activo', '2026-08-31 21:01:52');

CREATE TABLE `usuario_rol` (
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `id_rol` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `usuario_rol` (`id_usuario`, `id_rol`) VALUES
(1, 1),
(1, 3),
(2, 3),
(3, 3),
(4, 1),
(4, 2),
(4, 3);

ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id_auditoria`),
  ADD KEY `idx_auditoria_fecha` (`fecha_evento`),
  ADD KEY `idx_auditoria_usuario` (`id_usuario`),
  ADD KEY `idx_auditoria_entidad` (`entidad`,`id_entidad`);

ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categoria`),
  ADD UNIQUE KEY `nombre` (`nombre`);

ALTER TABLE `disciplinas`
  ADD PRIMARY KEY (`id_disciplina`),
  ADD UNIQUE KEY `nombre` (`nombre`);

ALTER TABLE `disciplina_categoria`
  ADD PRIMARY KEY (`id_disciplina`,`id_categoria`),
  ADD KEY `id_categoria` (`id_categoria`);

ALTER TABLE `disciplina_tipo_torneo`
  ADD PRIMARY KEY (`id_disciplina`,`id_tipo_torneo`),
  ADD KEY `id_tipo_torneo` (`id_tipo_torneo`);

ALTER TABLE `enfrentamientos`
  ADD PRIMARY KEY (`id_enfrentamiento`),
  ADD UNIQUE KEY `uq_enfrentamiento_orden` (`id_torneo`,`numero_ronda`,`orden_ronda`),
  ADD KEY `fk_enfrentamiento_usuario_a` (`id_usuario_a`),
  ADD KEY `fk_enfrentamiento_usuario_b` (`id_usuario_b`),
  ADD KEY `fk_enfrentamiento_equipo_a` (`id_equipo_a`),
  ADD KEY `fk_enfrentamiento_equipo_b` (`id_equipo_b`),
  ADD KEY `idx_enfrentamientos_torneo_estado` (`id_torneo`,`estado`),
  ADD KEY `idx_enfrentamientos_fecha` (`fecha_hora`);

ALTER TABLE `equipos`
  ADD PRIMARY KEY (`id_equipo`),
  ADD UNIQUE KEY `nombre` (`nombre`),
  ADD KEY `id_creador` (`id_creador`);

ALTER TABLE `inscripciones_equipos`
  ADD PRIMARY KEY (`id_inscripcion`),
  ADD UNIQUE KEY `id_torneo` (`id_torneo`,`id_equipo`),
  ADD KEY `id_equipo` (`id_equipo`);

ALTER TABLE `inscripciones_individuales`
  ADD PRIMARY KEY (`id_inscripcion`),
  ADD UNIQUE KEY `id_torneo` (`id_torneo`,`id_usuario`),
  ADD KEY `id_usuario` (`id_usuario`);

ALTER TABLE `integrantes_equipo`
  ADD PRIMARY KEY (`id_equipo`,`id_usuario`),
  ADD KEY `idx_integrantes_equipo_usuario` (`id_usuario`);

ALTER TABLE `integrantes_inscripcion_equipo`
  ADD PRIMARY KEY (`id_inscripcion`,`id_usuario`),
  ADD KEY `id_usuario` (`id_usuario`);

ALTER TABLE `invitaciones_equipo`
  ADD PRIMARY KEY (`id_invitacion_equipo`),
  ADD UNIQUE KEY `uq_invitacion_equipo_usuario` (`id_equipo`,`id_invitado`),
  ADD KEY `fk_invitacion_equipo_invitador` (`id_invitador`),
  ADD KEY `idx_invitaciones_equipo_invitado_estado` (`id_invitado`,`estado`),
  ADD KEY `idx_invitaciones_equipo_fecha` (`fecha_creacion`);

ALTER TABLE `invitaciones_torneo`
  ADD PRIMARY KEY (`id_invitacion`),
  ADD KEY `idx_invitaciones_invitado_estado` (`id_invitado`,`estado`),
  ADD KEY `idx_invitaciones_invitador` (`id_invitador`),
  ADD KEY `idx_invitaciones_equipo` (`id_equipo`),
  ADD KEY `idx_invitaciones_fecha` (`fecha_creacion`),
  ADD KEY `idx_invitaciones_objetivo_usuario` (`id_torneo`,`tipo`,`id_invitado`),
  ADD KEY `idx_invitaciones_objetivo_equipo` (`id_torneo`,`tipo`,`id_equipo`);

ALTER TABLE `preferencias_usuario`
  ADD PRIMARY KEY (`id_usuario`);

ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `nombre` (`nombre`);

ALTER TABLE `tipos_torneo`
  ADD PRIMARY KEY (`id_tipo_torneo`),
  ADD UNIQUE KEY `nombre` (`nombre`);

ALTER TABLE `torneos`
  ADD PRIMARY KEY (`id_torneo`),
  ADD KEY `id_disciplina` (`id_disciplina`),
  ADD KEY `id_categoria` (`id_categoria`),
  ADD KEY `id_tipo_torneo` (`id_tipo_torneo`),
  ADD KEY `id_organizador` (`id_organizador`);

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  ADD UNIQUE KEY `correo` (`correo`);

ALTER TABLE `usuario_rol`
  ADD PRIMARY KEY (`id_usuario`,`id_rol`),
  ADD KEY `id_rol` (`id_rol`);

ALTER TABLE `auditoria`
  MODIFY `id_auditoria` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

ALTER TABLE `categorias`
  MODIFY `id_categoria` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `disciplinas`
  MODIFY `id_disciplina` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

ALTER TABLE `enfrentamientos`
  MODIFY `id_enfrentamiento` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `equipos`
  MODIFY `id_equipo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `inscripciones_equipos`
  MODIFY `id_inscripcion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `inscripciones_individuales`
  MODIFY `id_inscripcion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `invitaciones_equipo`
  MODIFY `id_invitacion_equipo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `invitaciones_torneo`
  MODIFY `id_invitacion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `roles`
  MODIFY `id_rol` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `tipos_torneo`
  MODIFY `id_tipo_torneo` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `torneos`
  MODIFY `id_torneo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `auditoria`
  ADD CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL;

ALTER TABLE `disciplina_categoria`
  ADD CONSTRAINT `disciplina_categoria_ibfk_1` FOREIGN KEY (`id_disciplina`) REFERENCES `disciplinas` (`id_disciplina`) ON DELETE CASCADE,
  ADD CONSTRAINT `disciplina_categoria_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON DELETE CASCADE;

ALTER TABLE `disciplina_tipo_torneo`
  ADD CONSTRAINT `disciplina_tipo_torneo_ibfk_1` FOREIGN KEY (`id_disciplina`) REFERENCES `disciplinas` (`id_disciplina`) ON DELETE CASCADE,
  ADD CONSTRAINT `disciplina_tipo_torneo_ibfk_2` FOREIGN KEY (`id_tipo_torneo`) REFERENCES `tipos_torneo` (`id_tipo_torneo`) ON DELETE CASCADE;

ALTER TABLE `enfrentamientos`
  ADD CONSTRAINT `fk_enfrentamiento_equipo_a` FOREIGN KEY (`id_equipo_a`) REFERENCES `equipos` (`id_equipo`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_enfrentamiento_equipo_b` FOREIGN KEY (`id_equipo_b`) REFERENCES `equipos` (`id_equipo`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_enfrentamiento_torneo` FOREIGN KEY (`id_torneo`) REFERENCES `torneos` (`id_torneo`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_enfrentamiento_usuario_a` FOREIGN KEY (`id_usuario_a`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_enfrentamiento_usuario_b` FOREIGN KEY (`id_usuario_b`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL;

ALTER TABLE `equipos`
  ADD CONSTRAINT `equipos_ibfk_1` FOREIGN KEY (`id_creador`) REFERENCES `usuarios` (`id_usuario`);

ALTER TABLE `inscripciones_equipos`
  ADD CONSTRAINT `inscripciones_equipos_ibfk_1` FOREIGN KEY (`id_torneo`) REFERENCES `torneos` (`id_torneo`) ON DELETE CASCADE,
  ADD CONSTRAINT `inscripciones_equipos_ibfk_2` FOREIGN KEY (`id_equipo`) REFERENCES `equipos` (`id_equipo`);

ALTER TABLE `inscripciones_individuales`
  ADD CONSTRAINT `inscripciones_individuales_ibfk_1` FOREIGN KEY (`id_torneo`) REFERENCES `torneos` (`id_torneo`) ON DELETE CASCADE,
  ADD CONSTRAINT `inscripciones_individuales_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

ALTER TABLE `integrantes_equipo`
  ADD CONSTRAINT `fk_integrante_equipo_equipo` FOREIGN KEY (`id_equipo`) REFERENCES `equipos` (`id_equipo`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_integrante_equipo_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

ALTER TABLE `integrantes_inscripcion_equipo`
  ADD CONSTRAINT `integrantes_inscripcion_equipo_ibfk_1` FOREIGN KEY (`id_inscripcion`) REFERENCES `inscripciones_equipos` (`id_inscripcion`) ON DELETE CASCADE,
  ADD CONSTRAINT `integrantes_inscripcion_equipo_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

ALTER TABLE `invitaciones_equipo`
  ADD CONSTRAINT `fk_invitacion_equipo_equipo` FOREIGN KEY (`id_equipo`) REFERENCES `equipos` (`id_equipo`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_invitacion_equipo_invitado` FOREIGN KEY (`id_invitado`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_invitacion_equipo_invitador` FOREIGN KEY (`id_invitador`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

ALTER TABLE `invitaciones_torneo`
  ADD CONSTRAINT `fk_invitacion_equipo` FOREIGN KEY (`id_equipo`) REFERENCES `equipos` (`id_equipo`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_invitacion_invitado` FOREIGN KEY (`id_invitado`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_invitacion_invitador` FOREIGN KEY (`id_invitador`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_invitacion_torneo` FOREIGN KEY (`id_torneo`) REFERENCES `torneos` (`id_torneo`) ON DELETE CASCADE;

ALTER TABLE `preferencias_usuario`
  ADD CONSTRAINT `fk_preferencias_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

ALTER TABLE `torneos`
  ADD CONSTRAINT `torneos_ibfk_1` FOREIGN KEY (`id_disciplina`) REFERENCES `disciplinas` (`id_disciplina`),
  ADD CONSTRAINT `torneos_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`),
  ADD CONSTRAINT `torneos_ibfk_3` FOREIGN KEY (`id_tipo_torneo`) REFERENCES `tipos_torneo` (`id_tipo_torneo`),
  ADD CONSTRAINT `torneos_ibfk_4` FOREIGN KEY (`id_organizador`) REFERENCES `usuarios` (`id_usuario`);

ALTER TABLE `usuario_rol`
  ADD CONSTRAINT `usuario_rol_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `usuario_rol_ibfk_2` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE;
COMMIT;

SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT;
SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS;
SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION;
