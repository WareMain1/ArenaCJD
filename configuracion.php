<?php
require_once __DIR__ . '/config/protegerRol.php';
require_once __DIR__ . '/config/Conexion.php';
require_once __DIR__ . '/modelos/Usuario.php';
require_once __DIR__ . '/servicios/FotoPerfil.php';
require_once __DIR__ . '/api/_comun.php';

exigirRol('administrador');

$conexionBD = new Conexion();
$conexion = $conexionBD->conectar();
$modeloUsuario = new Usuario($conexion);

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $idUsuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
    $accion = $_POST['accion'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $_SESSION['mensaje_gestion_usuarios'] = 'La solicitud no pudo validarse. Intenta nuevamente.';
    } elseif (!$idUsuario || !in_array($accion, ['aprobar', 'rechazar'], true)) {
        $_SESSION['mensaje_gestion_usuarios'] = 'La acción solicitada no es válida.';
    } else {
        $usuarioGestionado = $modeloUsuario->buscarPorId($idUsuario);
        $nuevoEstado = $accion === 'aprobar' ? 'activo' : 'inactivo';
        $actualizado = $modeloUsuario->cambiarEstado($idUsuario, $nuevoEstado);

        if ($actualizado) {
            $aliasGestionado = $usuarioGestionado['nombre_usuario'] ?? 'usuario';
            $nombreGestionado = $usuarioGestionado['nombre_completo'] ?? 'Usuario';
            $mensajeAccion = $accion === 'aprobar'
                ? $nombreGestionado . ' (@' . $aliasGestionado . ') fue aprobado correctamente.'
                : 'La solicitud de ' . $nombreGestionado . ' (@' . $aliasGestionado . ') fue rechazada.';

            $_SESSION['mensaje_gestion_usuarios'] = $mensajeAccion;
            $_SESSION['notificacion_admin'] = [
                'id' => bin2hex(random_bytes(8)),
                'tipo' => $accion === 'aprobar' ? 'aprobado' : 'rechazado',
                'mensaje' => $mensajeAccion,
                'fecha' => date('c')
            ];

            registrarAuditoriaApi(
                $conexion,
                (int) $_SESSION['usuario_id'],
                $accion === 'aprobar' ? 'usuario_aprobado' : 'usuario_rechazado',
                'usuario',
                (int) $idUsuario,
                '@' . $aliasGestionado
            );
        } else {
            $_SESSION['mensaje_gestion_usuarios'] = 'La solicitud ya no estaba pendiente.';
        }
    }

    header('Location: /ArenaCJD/configuracion.php#usuarios-configuracion');
    exit;
}

$usuarioActual = $modeloUsuario->buscarPorId((int) $_SESSION['usuario_id']);
$solicitudesPendientes = $modeloUsuario->listarPendientes();
$cantidadSolicitudesPendientes = count($solicitudesPendientes);
$cantidadUsuariosActivos = $modeloUsuario->contarPorEstado('activo');
$cantidadRoles = (int) $conexion->query('SELECT COUNT(*) FROM roles')->fetchColumn();
$mensajeGestionUsuarios = $_SESSION['mensaje_gestion_usuarios'] ?? '';
unset($_SESSION['mensaje_gestion_usuarios']);

$nombreAdministrador = htmlspecialchars($usuarioActual['nombre_completo'] ?? 'Administrador', ENT_QUOTES, 'UTF-8');
$aliasAdministrador = htmlspecialchars($usuarioActual['nombre_usuario'] ?? 'admin', ENT_QUOTES, 'UTF-8');
$correoAdministrador = htmlspecialchars($usuarioActual['correo'] ?? '', ENT_QUOTES, 'UTF-8');
$estadoAdministrador = $usuarioActual['estado'] ?? 'activo';
$rolesAdministrador = $usuarioActual['roles'] ? explode(',', $usuarioActual['roles']) : ['administrador'];
$rolesAdministradorTexto = htmlspecialchars(
    implode(', ', array_map(function ($rol) {
        return ucfirst($rol);
    }, $rolesAdministrador)),
    ENT_QUOTES,
    'UTF-8'
);
$nombreParaInicial = $usuarioActual['nombre_completo'] ?? 'A';
preg_match('/^./u', $nombreParaInicial, $coincidenciaInicial);
$inicialAdministrador = htmlspecialchars(
    $coincidenciaInicial[0] ?? 'A',
    ENT_QUOTES,
    'UTF-8'
);

$servicioFotoPerfil = new FotoPerfil();
$idAdministrador = (int) $_SESSION['usuario_id'];
$tieneFotoPerfilAdministrador = $servicioFotoPerfil->existe($idAdministrador);
$versionFotoPerfilAdministrador = $tieneFotoPerfilAdministrador
    ? $servicioFotoPerfil->obtenerVersion($idAdministrador)
    : 0;
$urlFotoPerfilAdministrador = $tieneFotoPerfilAdministrador
    ? 'api/foto_perfil.php?v=' . $versionFotoPerfilAdministrador
    : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Configuración - ArenaCJD</title>
<link rel="icon" type="image/png" href="imagenes/arena-cjd-isotipo.png">
<script src="js/tema-inicial.js"></script>
<script src="js/iconos.js?v=20260909-sprint11"></script>
<script>
  if (window.matchMedia('(min-width: 992px)').matches &&
      localStorage.getItem('menuColapsado') !== 'true') {
    document.documentElement.classList.add('menu-pre-abierto');
  }
</script>

<link rel="stylesheet" href="css/variables.css?v=20260909-audit12">
<link rel="stylesheet" href="css/layout.css?v=20260911-stableui1">
<link rel="stylesheet" href="css/componentes.css?v=20260911-stableui1">
<link rel="stylesheet" href="css/experiencia.css?v=20260910-guia1">
<link rel="stylesheet" href="css/animaciones.css">
<link rel="stylesheet" href="css/paginas/configuracion.css?v=20260913-alineacion1">
<link rel="stylesheet" href="css/utilidades.css?v=20260911-stableui1">
<link rel="stylesheet" href="css/tema-claro.css?v=20260909-sprint11">
<link rel="stylesheet" href="css/tema-oscuro.css?v=20260908-toast1">
<link rel="stylesheet" href="css/contraste-claro.css?v=20260909-audit12">
<link rel="stylesheet" href="css/formularios.css?v=20260910-mobile1">
</head>
<body class="cuerpo-app" <?= atributosSesionArenaCJD() ?>>
<div class="contenedor-app">
<aside class="menu-lateral" id="menuLateral" data-componente="menu-lateral"></aside>

<div class="fondo-oscuro" id="fondoOscuro"></div>

<div class="contenido-app" id="inicioConfiguracion">
<header class="encabezado-app">
<button class="boton-menu" id="botonMenu" aria-label="Abrir menú" aria-expanded="false">
<span data-icono="menu" class="icono-menu-abrir" aria-hidden="true"></span><span data-icono="peligro" class="icono-menu-cerrar" aria-hidden="true"></span>
</button>
<div class="logo-app">
<img src="imagenes/arena-cjd-logo-horizontal.png" alt="Logo de ArenaCJD">
</div>
<div class="acciones-app">
<button class="boton-notificaciones" id="botonNotificaciones" type="button" aria-label="Abrir notificaciones" aria-expanded="false" aria-controls="panelNotificaciones">
<span data-icono="notificaciones" aria-hidden="true"></span>
<span class="contador-notificaciones" id="contadorNotificaciones" hidden>0</span>
</button>
</div>
</header>

<main class="panel configuracion-administrador">
<section class="encabezado-configuracion">
<div>
<h1 class="titulo-pagina titulo-modulo">Configuración</h1>
<p class="subtitulo-pagina">Preferencias generales y opciones administrativas de ArenaCJD</p>
</div>
<span class="etiqueta-acceso-administrador">Acceso exclusivo del administrador</span>
</section>

<section class="aviso-configuracion">
<div class="icono-aviso-configuracion"><span data-icono="info" aria-hidden="true"></span></div>
<div>
<strong>Configuración conectada</strong>
<p>Administra preferencias, usuarios, roles y actividad desde un solo lugar. El diagnóstico técnico se muestra al final de la página.</p>
</div>
</section>

<nav class="indice-configuracion" aria-label="Secciones de configuración">
<a href="#perfil-configuracion">Perfil</a>
<a href="#apariencia-configuracion">Apariencia</a>
<a href="#preferencias-configuracion">Preferencias</a>
<a href="#torneos-configuracion">Torneos</a>
<a href="#usuarios-configuracion">Usuarios</a>
<a href="#segunda-entrega-configuracion">Opciones técnicas</a>
<a href="#informacion-configuracion">Sistema</a>
</nav>

<section class="seccion-configuracion" id="perfil-configuracion">
<div class="cabecera-seccion-configuracion">
<div><span class="numero-seccion-configuracion">01</span><h2>Perfil del administrador</h2><p>Información de la cuenta que administra el sistema.</p></div>
</div>
<div class="perfil-configuracion">
<div class="avatar-configuracion" id="avatarConfiguracion">
<img
class="foto-avatar-configuracion"
id="fotoPerfilConfiguracion"
src="<?= htmlspecialchars(
    $tieneFotoPerfilAdministrador
        ? $urlFotoPerfilAdministrador
        : 'imagenes/avatar-usuario-predeterminado.png',
    ENT_QUOTES,
    'UTF-8'
) ?>"
alt="Foto de perfil de <?= $nombreAdministrador ?>"
onerror="if(!this.dataset.fallback){this.dataset.fallback='1';this.src='imagenes/avatar-usuario-predeterminado.png';}"
>
</div>
<div class="datos-principales-configuracion"><strong><?= $nombreAdministrador ?></strong><span id="aliasAdministradorConfiguracion">@<?= $aliasAdministrador ?></span><small><?= $rolesAdministradorTexto ?></small></div>
<div class="datos-contacto-configuracion"><span><?= $correoAdministrador ?></span><span><?= $estadoAdministrador === 'activo' ? 'Cuenta activa' : ucfirst($estadoAdministrador) ?></span></div>
<button class="boton boton-principal" id="editarPerfilConfiguracion" type="button">Editar perfil</button>
</div>
</section>

<section class="seccion-configuracion" id="apariencia-configuracion">
<div class="cabecera-seccion-configuracion">
<div><span class="numero-seccion-configuracion">02</span><h2>Apariencia</h2><p>El tema elegido se aplica en todas las páginas y se conserva al volver a ingresar.</p></div>
</div>
<div class="opciones-tema" role="group" aria-label="Tema de ArenaCJD">
<button class="opcion-tema" type="button" data-boton-tema="claro"><span class="muestra-tema muestra-tema-claro"></span><strong>Claro</strong><small>Fondo claro y tarjetas blancas</small></button>
<button class="opcion-tema" type="button" data-boton-tema="oscuro"><span class="muestra-tema muestra-tema-oscuro"></span><strong>Oscuro</strong><small>Fondo oscuro y contraste suave</small></button>
<button class="opcion-tema" type="button" data-boton-tema="sistema"><span class="muestra-tema muestra-tema-sistema"></span><strong>Sistema</strong><small>Usa la preferencia del dispositivo</small></button>
</div>
</section>

<section class="seccion-configuracion" id="preferencias-configuracion">
<div class="cabecera-seccion-configuracion">
<div><span class="numero-seccion-configuracion">03</span><h2>Preferencias generales</h2><p>Valores del sistema que se mantienen fijos en esta entrega.</p></div>
</div>
<div class="informacion-sistema-configuracion">
<div><span>Idioma</span><strong>Español</strong></div>
<div><span>Zona horaria</span><strong>America/Montevideo (UTC-03:00)</strong></div>
<div><span>Seguridad</span><strong>Confirmación obligatoria en acciones sensibles</strong></div>
<div><span>Notificaciones</span><strong>Invitaciones y eventos que requieren atención</strong></div>
</div>
</section>

<section class="seccion-configuracion" id="torneos-configuracion">
<div class="cabecera-seccion-configuracion">
<div><span class="numero-seccion-configuracion">04</span><h2>Preferencias de torneos</h2><p>Valores generales para las vistas administrativas.</p></div>
</div>
<div class="grid-preferencias-configuracion">
<div class="grupo-formulario"><label for="elementosPagina">Elementos por página</label><select id="elementosPagina" class="selector-formulario"><option>10</option><option>20</option><option>50</option></select></div>
<div class="grupo-formulario"><label for="torneoPredeterminado">Torneo predeterminado</label><select id="torneoPredeterminado" class="selector-formulario" data-torneos-oficiales><option value="">Seleccionar torneo</option></select></div>
</div>
<div class="lista-interruptores-configuracion">

</div>
</section>

<section class="seccion-configuracion" id="usuarios-configuracion">
<div class="cabecera-seccion-configuracion">
<div><span class="numero-seccion-configuracion">05</span><h2>Usuarios</h2><p>Administración visual de cuentas, solicitudes, roles e historial.</p></div>
</div>
<nav class="navegacion-usuarios-configuracion" aria-label="Administración de usuarios">
<button class="pestana-usuarios" type="button" data-pestana-usuarios="activos">Usuarios registrados</button>
<button class="pestana-usuarios activa" type="button" data-pestana-usuarios="solicitudes">Solicitudes de acceso <span><?= $cantidadSolicitudesPendientes ?></span></button>
<button class="pestana-usuarios" type="button" data-pestana-usuarios="roles">Roles</button>
<button class="pestana-usuarios" type="button" data-pestana-usuarios="historial">Historial</button>
</nav>
<div class="panel-usuarios-configuracion" data-panel-usuarios="activos" hidden>
<div class="resumen-usuarios-activos"><article><strong id="cantidadUsuariosActivosConfig"><?= $cantidadUsuariosActivos ?></strong><span>Usuarios activos</span></article><article><strong><?= $cantidadRoles ?></strong><span>Roles disponibles</span></article></div>
<div class="barra-gestion-usuarios">
<div><strong>Gestión de cuentas</strong><span id="estadoUsuariosActivos">Actualizando información...</span></div>
<button class="boton boton-claro" id="actualizarUsuariosActivos" type="button"><span data-icono="actualizar" aria-hidden="true"></span> Actualizar</button>
</div>
<div class="tabla-solicitudes-contenedor">
<table class="tabla-ah tabla-solicitudes tabla-usuarios-activos">
<thead><tr><th>Usuario</th><th>Correo</th><th>Roles</th><th>Estado</th><th>Registro</th><th>Acciones</th></tr></thead>
<tbody id="cuerpoUsuariosActivos"><tr><td colspan="6"><p class="sin-solicitudes">Cargando usuarios...</p></td></tr></tbody>
</table>
</div>
</div>
<div class="panel-usuarios-configuracion" data-panel-usuarios="solicitudes">
<?php if ($mensajeGestionUsuarios !== ''): ?>
<div class="aviso-configuracion"><div class="icono-aviso-configuracion"><span data-icono="exito" aria-hidden="true"></span></div><div><strong>Gestión de usuarios</strong><p><?= htmlspecialchars($mensajeGestionUsuarios, ENT_QUOTES, 'UTF-8') ?></p></div></div>
<?php endif; ?>
<div class="tabla-solicitudes-contenedor">
<table class="tabla-ah tabla-solicitudes">
<thead><tr><th>Nombre completo</th><th>@usuario</th><th>Correo</th><th>Fecha de solicitud</th><th>Estado</th><th>Acciones</th></tr></thead>
<tbody id="cuerpoSolicitudesAcceso" data-csrf-token="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
<?php if ($solicitudesPendientes): ?>
<?php foreach ($solicitudesPendientes as $solicitud): ?>
<tr>
<td><strong><?= htmlspecialchars($solicitud['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></strong></td>
<td>@<?= htmlspecialchars($solicitud['nombre_usuario'], ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars($solicitud['correo'], ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($solicitud['fecha_registro'])), ENT_QUOTES, 'UTF-8') ?></td>
<td><span class="estado-solicitud estado-pendiente">Pendiente</span></td>
<td>
<form method="post" class="acciones-tabla-solicitudes">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="id_usuario" value="<?= (int) $solicitud['id_usuario'] ?>">
<button type="submit" name="accion" value="aprobar">Aprobar</button>
<button type="submit" name="accion" value="rechazar">Rechazar</button>
</form>
</td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="6"><p class="sin-solicitudes">No hay solicitudes pendientes.</p></td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
<div class="panel-usuarios-configuracion" data-panel-usuarios="roles" hidden>
<div class="grid-roles-configuracion">
<article class="rol-configuracion"><span>Acceso total</span><h3>Administrador</h3><p>Configuración completa, solicitudes, usuarios y asignación de roles.</p></article>
<article class="rol-configuracion"><span>Gestión deportiva</span><h3>Organizador</h3><p>Administra sus torneos, participantes, sorteos, calendarios y resultados.</p></article>
<article class="rol-configuracion"><span>Consulta</span><h3>Participante</h3><p>Consulta torneos, calendario, enfrentamientos y clasificación.</p></article>
</div>
</div>
<div class="panel-usuarios-configuracion" data-panel-usuarios="historial" hidden>
<div class="barra-gestion-usuarios barra-historial-configuracion">
<div><strong>Actividad y seguridad</strong><span id="estadoHistorialUsuarios">Actualizando información...</span></div>
<button class="boton boton-claro" id="actualizarHistorialUsuarios" type="button"><span data-icono="actualizar" aria-hidden="true"></span> Actualizar</button>
</div>
<p class="descripcion-historial-configuracion">Los eventos se agrupan para distinguir inicios de sesión, alertas de seguridad, cambios en torneos y otras acciones importantes del sistema.</p>
<nav class="filtros-historial-configuracion" id="filtrosHistorialConfiguracion" aria-label="Categorías del historial">
<button class="filtro-historial-configuracion activo" type="button" data-categoria-historial="todas">Todo <span data-contador-historial="todas">0</span></button>
<button class="filtro-historial-configuracion" type="button" data-categoria-historial="sesiones">Inicios de sesión <span data-contador-historial="sesiones">0</span></button>
<button class="filtro-historial-configuracion filtro-historial-alerta" type="button" data-categoria-historial="amenazas">Alertas de seguridad <span data-contador-historial="amenazas">0</span></button>
<button class="filtro-historial-configuracion" type="button" data-categoria-historial="torneos">Torneos <span data-contador-historial="torneos">0</span></button>
<button class="filtro-historial-configuracion" type="button" data-categoria-historial="usuarios">Usuarios y accesos <span data-contador-historial="usuarios">0</span></button>
<button class="filtro-historial-configuracion" type="button" data-categoria-historial="participacion">Participación <span data-contador-historial="participacion">0</span></button>
<button class="filtro-historial-configuracion" type="button" data-categoria-historial="competencia">Competencia <span data-contador-historial="competencia">0</span></button>
<button class="filtro-historial-configuracion" type="button" data-categoria-historial="catalogos">Catálogos <span data-contador-historial="catalogos">0</span></button>
</nav>
<div class="historial-usuarios-configuracion" id="historialUsuariosConfiguracion">
<p class="sin-solicitudes">Cargando actividad...</p>
</div>
</div>
</section>


<section class="seccion-configuracion" id="segunda-entrega-configuracion">
<div class="cabecera-seccion-configuracion">
<div><span class="numero-seccion-configuracion">06</span><h2>Opciones técnicas</h2><p>Estado técnico real del servidor, sesión, seguridad y base de datos.</p></div>
</div>
<div class="grid-opciones-bloqueadas" id="estadoTecnicoTiempoReal">
<article class="opcion-bloqueada opcion-tecnica-viva"><span class="candado-opcion"><span data-icono="escudo" aria-hidden="true"></span></span><div><h3>Seguridad</h3><p>Sesión: <strong id="tecnicoSesion">Consultando...</strong> · CSRF: <strong id="tecnicoCsrf">...</strong></p><span id="tecnicoHash">Hash de contraseñas</span></div></article>
<article class="opcion-bloqueada opcion-tecnica-viva"><span class="candado-opcion"><span data-icono="base" aria-hidden="true"></span></span><div><h3>Base de datos</h3><p>MySQL: <strong id="tecnicoMysql">Consultando...</strong></p><span id="tecnicoBaseDatos">Usuario y tablas</span></div></article>
<article class="opcion-bloqueada opcion-tecnica-viva"><span class="candado-opcion"><span data-icono="enfrentamientos" aria-hidden="true"></span></span><div><h3>Datos competitivos</h3><p>Enfrentamientos persistidos: <strong id="tecnicoEnfrentamientos">0</strong></p><span>Actualización automática</span></div></article>
<article class="opcion-bloqueada opcion-tecnica-viva"><span class="candado-opcion"><span data-icono="configuracion" aria-hidden="true"></span></span><div><h3>Servidor</h3><p>PHP: <strong id="tecnicoPhp">...</strong></p><span id="tecnicoServidor">Apache/PHP</span></div></article>
<article class="opcion-bloqueada opcion-tecnica-viva"><span class="candado-opcion"><span data-icono="circulo" aria-hidden="true"></span></span><div><h3>Estado en tiempo real</h3><p id="tecnicoHora">Esperando actualización...</p><span class="estado-tecnico-vivo" id="tecnicoEstado">Conectando</span></div></article>
</div>
</section>

<section class="seccion-configuracion" id="informacion-configuracion">
<div class="cabecera-seccion-configuracion">
<div><span class="numero-seccion-configuracion">07</span><h2>Información del sistema</h2><p>Identidad y versión actual de la aplicación.</p></div>
</div>
<div class="informacion-sistema-configuracion">
<div><span>Producto</span><strong>ArenaCJD</strong></div>
<div><span>Empresa desarrolladora</span><strong>WareMain</strong></div>
<div><span>Versión</span><strong>2.0 - Segunda entrega</strong></div>
<div><span>Licencia</span><strong>Proyecto académico</strong></div>
<div><span>Estado</span><strong class="estado-sistema-operativo">Operativo</strong></div>
</div>
<div class="acciones-configuracion-final">
<button class="boton boton-principal" id="guardarConfiguracion" type="button">Guardar configuración</button>
<a class="boton boton-claro boton-cerrar-sesion-configuracion" href="api/logout.php" data-cerrar-sesion>Cerrar sesión</a></div>
</section>
</main>
</div>
</div>

<div class="fondo-modal-gestion-usuario" id="modalGestionUsuario" hidden>
<section class="modal-gestion-usuario" role="dialog" aria-modal="true" aria-labelledby="tituloGestionUsuario">
<header class="cabecera-modal-gestion-usuario">
<div><span>Administración</span><h2 id="tituloGestionUsuario">Editar usuario</h2><p id="subtituloGestionUsuario">Actualiza el estado y los roles de la cuenta.</p></div>
<button type="button" class="cerrar-modal-gestion-usuario" data-cerrar-gestion-usuario aria-label="Cerrar"><span data-icono="peligro" aria-hidden="true"></span></button>
</header>
<form id="formularioGestionUsuario" class="formulario-gestion-usuario">
<input type="hidden" id="gestionUsuarioId">
<div class="grupo-formulario"><label>Cuenta</label><div class="resumen-cuenta-gestion"><strong id="gestionUsuarioNombre">Usuario</strong><span id="gestionUsuarioAlias">@usuario</span></div></div>
<div class="grupo-formulario"><label for="gestionUsuarioEstado">Estado</label><select id="gestionUsuarioEstado" class="selector-formulario" required><option value="activo">Activo</option><option value="inactivo">Inactivo</option><option value="bloqueado">Bloqueado</option></select></div>
<fieldset class="grupo-formulario grupo-formulario-completo"><legend>Roles</legend><div class="lista-roles-gestion" id="rolesGestionUsuario"></div></fieldset>
<p class="mensaje-gestion-usuario" id="mensajeGestionUsuario" aria-live="polite"></p>
<footer class="acciones-modal-gestion-usuario grupo-formulario-completo"><button class="boton boton-claro" type="button" data-cerrar-gestion-usuario>Cancelar</button><button class="boton boton-principal" type="submit">Guardar cambios</button></footer>
</form>
</section>
</div>

<script src="js/sincronizacion.js?v=20260831-central1"></script>
<script src="js/datos.js"></script>
<script src="js/componentes.js?v=20260913-sin-presencia1"></script>
<script src="js/experiencia.js?v=20260910-guia1"></script>
<script src="js/menu.js?v=20260909-sprint11"></script>
<script src="js/configuracion.js?v=20260909-iconos4"></script>
<script src="js/tema.js?v=20260909-login-tema1"></script>
</body>
</html>
