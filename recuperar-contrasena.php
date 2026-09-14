<?php
require_once __DIR__ . '/config/iniciarSesion.php';
iniciarSesionArenaCJD();
if (!empty($_SESSION['usuario_id'])) {
    $roles = $_SESSION['roles'] ?? [];
    header('Location: ' . ((in_array('administrador',$roles,true) || in_array('organizador',$roles,true)) ? 'panel.php' : 'torneos.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperar contraseña - ArenaCJD</title>
<link rel="icon" type="image/png" href="imagenes/arena-cjd-isotipo.png">
<script src="js/tema-inicial.js"></script>
<script src="js/iconos.js?v=20260909-sprint11"></script>
<link rel="stylesheet" href="css/variables.css?v=20260909-audit12">
<link rel="stylesheet" href="css/layout.css?v=20260911-stableui1">
<link rel="stylesheet" href="css/componentes.css?v=20260911-stableui1">
<link rel="stylesheet" href="css/animaciones.css">
<link rel="stylesheet" href="css/paginas/index.css?v=20260909-sprint11">
<link rel="stylesheet" href="css/paginas/index-publico-vivo.css">
<link rel="stylesheet" href="css/paginas/recuperar-contrasena.css?v=20260909-audit12">
<link rel="stylesheet" href="css/utilidades.css?v=20260911-stableui1">
<link rel="stylesheet" href="css/tema-claro.css?v=20260909-sprint11">
<link rel="stylesheet" href="css/tema-oscuro.css?v=20260908-toast1">
<link rel="stylesheet" href="css/contraste-claro.css?v=20260909-audit12">
<link rel="stylesheet" href="css/formularios.css?v=20260910-mobile1">
</head>
<body class="pagina-inicio-publica pagina-recuperar-contrasena">
<header class="encabezado-publico">
<div class="barra-publica">
<a href="index.php" class="marca-publica" aria-label="ArenaCJD, página de inicio"><img src="imagenes/arena-cjd-isotipo.png" alt="Isotipo de ArenaCJD" class="marca-publica-icono"><div class="marca-publica-texto"><strong>ArenaCJD</strong><span>Sistema de Gestión Deportiva Modular</span></div></a>
<div class="acciones-publicas"><div class="selector-tema-publico" aria-label="Seleccionar tema"><button type="button" data-boton-tema="claro" aria-label="Usar tema claro"><span data-icono="sol" aria-hidden="true"></span></button><button type="button" data-boton-tema="oscuro" aria-label="Usar tema oscuro"><span data-icono="luna" aria-hidden="true"></span></button><button type="button" data-boton-tema="sistema" aria-label="Usar tema del sistema"><span data-icono="sistema" aria-hidden="true"></span></button></div><a class="boton boton-claro boton-volver-recuperacion" href="index.php#acceso">Volver al inicio</a></div>
</div>
</header>
<main class="recuperacion-publica recuperacion-publica-independiente" aria-labelledby="tituloRecuperacion">
<section class="tarjeta-recuperacion-publica">
<span class="etiqueta-portada">Recuperación de cuenta</span>
<h1 id="tituloRecuperacion">¿Olvidaste tu contraseña?</h1>
<p>Identifica tu cuenta usando tu nombre completo, @usuario o correo. Después responde la pregunta de recuperación configurada en tu cuenta.</p>
<form id="formRecuperacionIdentificar"><div class="grupo-formulario"><label for="identificadorRecuperacion">Nombre, @usuario o correo</label><input class="campo-formulario" id="identificadorRecuperacion" maxlength="150" autocomplete="username" required></div><button class="boton boton-principal" type="submit">Continuar</button></form>
<form id="formRecuperacionRestaurar" hidden>
    <div class="pregunta-recuperacion-viva">
        <span>Pregunta de recuperación</span>
        <strong id="preguntaRecuperacionPublica"></strong>
    </div>

    <div class="grupo-formulario">
        <label for="respuestaRecuperacionPublica">Respuesta</label>
        <input
            class="campo-formulario"
            id="respuestaRecuperacionPublica"
            autocomplete="off"
            required
        >
    </div>

    <div class="grupo-formulario">
        <label for="nuevaRecuperacionPublica">Nueva contraseña</label>
        <input
            class="campo-formulario"
            id="nuevaRecuperacionPublica"
            type="password"
            autocomplete="new-password"
            required
        >
    </div>

    <div class="grupo-formulario">
        <label for="confirmarRecuperacionPublica">Confirmar contraseña</label>
        <input
            class="campo-formulario"
            id="confirmarRecuperacionPublica"
            type="password"
            autocomplete="new-password"
            required
        >
    </div>

    <button class="boton boton-principal" type="submit">
        Cambiar contraseña
    </button>

    <a href="index.php" class="boton boton-claro" id="reiniciarRecuperacion">
        Usar otra cuenta
    </a>
</form>
<p id="mensajeRecuperacionPublica" class="mensaje-registro" aria-live="polite"></p>
<div class="pie-recuperacion-cuenta"><span>¿Recordaste tu contraseña?</span><a href="index.php#acceso">Iniciar sesión</a></div>
</section>
</main>
<?php require __DIR__ . '/publico/footer.php'; ?>
<script src="js/tema.js?v=20260909-login-tema1"></script><script src="js/recuperacion.js?v=20260831-recuperacion2"></script>
</body>
</html>
