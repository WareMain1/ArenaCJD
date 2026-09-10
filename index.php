<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!empty($_SESSION['usuario_id'])) {
    header('Location: panel.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ArenaCJD - Sistema de Gestión Deportiva Modular</title>
<link rel="icon" type="image/png" href="imagenes/arena-cjd-isotipo.png">

<script src="js/tema-inicial.js"></script>
<script src="js/iconos.js?v=20260909-sprint11"></script>
<link rel="stylesheet" href="css/variables.css?v=20260909-audit12">
<link rel="stylesheet" href="css/layout.css?v=20260909-sprint13">
<link rel="stylesheet" href="css/componentes.css?v=20260910-visibilidad1">
<link rel="stylesheet" href="css/experiencia.css?v=20260910-guia1">
<link rel="stylesheet" href="css/animaciones.css">
<link rel="stylesheet" href="css/paginas/index.css?v=20260909-sprint11">
<link rel="stylesheet" href="css/utilidades.css?v=20260909-sprint11">
<link rel="stylesheet" href="css/tema-claro.css?v=20260909-sprint11">
<link rel="stylesheet" href="css/tema-oscuro.css?v=20260908-toast1">
<link rel="stylesheet" href="css/contraste-claro.css?v=20260909-audit12">
<link rel="stylesheet" href="css/formularios.css?v=20260910-mobile1">
</head>
<body class="pagina-inicio-publica">
<header class="encabezado-publico">
<div class="barra-publica">
<a href="index.php" class="marca-publica" aria-label="ArenaCJD, página de inicio"><img src="imagenes/arena-cjd-isotipo.png" alt="Isotipo de ArenaCJD" class="marca-publica-icono"><div class="marca-publica-texto"><strong>ArenaCJD</strong><span>Sistema de Gestión Deportiva Modular</span></div></a>
<nav class="navegacion-principal navegacion-publica" id="navegacionPrincipal" aria-label="Navegación principal"><ul>
<li><a href="index.php" class="enlace-navegacion" aria-current="page">Inicio</a></li>
<li><a href="torneos-publicos.php" class="enlace-navegacion">Torneos</a></li>
<li><a href="enfrentamientos-publicos.php" class="enlace-navegacion">Enfrentamientos</a></li>
<li><a href="resultados-publicos.php" class="enlace-navegacion">Resultados</a></li>
<li><a href="clasificacion-publica.php" class="enlace-navegacion">Clasificación</a></li>
<li><a href="calendario-publico.php" class="enlace-navegacion">Calendario</a></li>
</ul></nav>
<button class="boton-menu-movil" type="button" aria-expanded="false" aria-controls="navegacionPrincipal"><span data-icono="menu" aria-hidden="true"></span>Menú</button>
<div class="acciones-publicas"><div class="selector-tema-publico" aria-label="Seleccionar tema"><button type="button" data-boton-tema="claro" aria-label="Usar tema claro"><span data-icono="sol" aria-hidden="true"></span></button><button type="button" data-boton-tema="oscuro" aria-label="Usar tema oscuro"><span data-icono="luna" aria-hidden="true"></span></button><button type="button" data-boton-tema="sistema" aria-label="Usar tema del sistema"><span data-icono="sistema" aria-hidden="true"></span></button></div></div>
</div>
</header>
<main id="inicio" class="inicio-publico">
<section class="portada-publica">
<div class="presentacion-publica">
<span class="etiqueta-portada"><span data-icono="medalla" aria-hidden="true"></span> Plataforma completa y modular</span>
<h1>Gestiona torneos de distintas disciplinas <span>desde un solo lugar</span></h1>
<p class="descripcion-portada">Organiza participantes, sorteos, enfrentamientos, resultados y calendarios en una plataforma adaptable a cualquier deporte o competencia.</p>
<div class="acciones-portada-publica"><a class="boton boton-principal" href="torneos-publicos.php">Explorar torneos</a><a class="boton boton-secundario" href="resultados-publicos.php">Ver resultados</a></div>
<div class="funcionalidades-destacadas">
<article class="funcionalidad-destacada"><div class="icono-funcionalidad fondo-morado"><span class="icono-imagen-ui" data-icono="torneo" aria-hidden="true"></span></div><div><h2>Gestión de torneos</h2><p>Los visitantes pueden consultar los torneos públicos en tiempo real.</p></div></article>
<article class="funcionalidad-destacada"><div class="icono-funcionalidad fondo-verde"><span class="icono-imagen-ui" data-icono="enfrentamientos" aria-hidden="true"></span></div><div><h2>Enfrentamientos</h2><p>Consulta cruces programados y rondas sin iniciar sesión.</p></div></article>
<article class="funcionalidad-destacada"><div class="icono-funcionalidad fondo-naranja"><span class="icono-imagen-ui" data-icono="resultados" aria-hidden="true"></span></div><div><h2>Resultados</h2><p>Los resultados finalizados se publican automáticamente.</p></div></article>
</div>
<div class="vista-previa-sistema" aria-label="Vista previa del módulo de torneos de ArenaCJD"><div class="laptop-ah"><div class="pantalla-laptop-ah"><div class="camara-laptop-ah" aria-hidden="true"></div><div class="contenedor-imagen-vista-previa"><img src="imagenes/vista-gestion-torneos-claro.png" alt="Pantalla de gestión de torneos de ArenaCJD" class="imagen-vista-previa imagen-vista-previa-clara"><img src="imagenes/vista-gestion-torneos-oscuro.png" alt="Pantalla de gestión de torneos de ArenaCJD" class="imagen-vista-previa imagen-vista-previa-oscura"></div></div><div class="base-laptop-ah" aria-hidden="true"><span></span></div></div></div>
</div>
<aside class="tarjeta-acceso" id="acceso">
<div class="logo-acceso"><img src="imagenes/arena-cjd-logo-horizontal.png" alt="Logo de ArenaCJD"></div><h2>Inicia sesión en tu cuenta</h2><p>Accede a tu cuenta para gestionar o participar</p>
<form class="formulario-sesion" novalidate>
<div class="grupo-formulario"><label for="usuario">Usuario</label><div class="envoltorio-campo"><span class="icono-campo" data-icono="usuario" aria-hidden="true"></span><input type="text" id="usuario" name="usuario" placeholder="Ingresa tu @usuario" autocomplete="username" required></div></div>
<div class="grupo-formulario"><label for="password">Contraseña</label><div class="envoltorio-campo"><span class="icono-campo" data-icono="candado" aria-hidden="true"></span><input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" autocomplete="current-password" required><button type="button" class="ver-contrasena" data-target="password" aria-label="Mostrar contraseña" data-icono="ojo"></button></div></div>
<div class="opciones-sesion"><label class="recordar"><input type="checkbox" name="recordar"> Recordar sesión</label><a href="recuperar-contrasena.php">¿Olvidaste tu contraseña?</a></div>
<button type="submit" class="boton boton-principal boton-bloque boton-iniciar-publico"><span data-icono="derecha" aria-hidden="true"></span> Iniciar sesión</button>
<div class="separador-acceso"><span>¿No tienes una cuenta?</span></div><a href="registro.html" class="boton boton-secundario boton-bloque"><span data-icono="usuario" aria-hidden="true"></span> Solicitar acceso</a>
<div class="aviso-aprobacion"><span><span data-icono="info" aria-hidden="true"></span></span><p>Las cuentas requieren aprobación de un administrador.</p></div>
</form></aside>
</section>

<section class="resumen-espectador-ah" aria-labelledby="tituloResumenEspectador"><div class="cabecera-resumen-espectador-ah"><div><h2 id="tituloResumenEspectador">Qué está pasando en ArenaCJD</h2><p>Accede rápido a próximos cruces, resultados recientes y torneos activos sin iniciar sesión.</p></div><a class="boton boton-claro" href="calendario-publico.php">Abrir calendario</a></div><div class="grid-resumen-espectador-ah"><article class="tarjeta-espectador-ah"><header><h3>Próximos enfrentamientos</h3><a href="enfrentamientos-publicos.php">Ver todos</a></header><div class="lista-espectador-ah" id="inicioProximosPublicos"></div></article><article class="tarjeta-espectador-ah"><header><h3>Últimos resultados</h3><a href="resultados-publicos.php">Ver todos</a></header><div class="lista-espectador-ah" id="inicioResultadosPublicos"></div></article><article class="tarjeta-espectador-ah"><header><h3>Torneos activos</h3><a href="torneos-publicos.php">Explorar</a></header><div class="lista-espectador-ah" id="inicioTorneosPublicos"></div></article></div></section>

</main>
<?php require __DIR__ . '/publico/footer.php'; ?>
<script src="js/menu.js?v=20260909-sprint11"></script><script src="js/tema.js?v=20260909-login-tema1"></script><script src="js/inicio-publico.js?v=20260908-ux3"></script>
</body></html>
