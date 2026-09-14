<?php
require_once __DIR__ . '/config/proteger.php';
require_once __DIR__ . '/modelos/Torneo.php';

$rolesTorneos = $_SESSION['roles'] ?? [];
$idUsuarioTorneos = (int) ($_SESSION['usuario_id'] ?? 0);
$esAdministradorTorneos = in_array('administrador', $rolesTorneos, true);
$esOrganizadorTorneos = in_array('organizador', $rolesTorneos, true);
$filtroOrganizadorTorneos = (!$esAdministradorTorneos && $esOrganizadorTorneos) ? $idUsuarioTorneos : null;

$torneos = [];
$resumenTorneos = [
    'total' => 0,
    'activos' => 0,
    'finalizados' => 0,
    'proximos' => 0,
    'cancelados' => 0,
    'comienzan_hoy' => 0,
    'participantes' => 0
];
$proximoTorneo = false;
$proximosTorneos = [];
$torneosRecientes = [];
$errorCargaTorneos = false;

try {
    $conexionTorneos = (new Conexion())->conectar();
    $modeloTorneo = new Torneo($conexionTorneos);
    $modeloTorneo->sincronizarEstadosTemporales();
    $torneos = $modeloTorneo->obtenerTodos($filtroOrganizadorTorneos);
    $resumenTorneos = $modeloTorneo->obtenerResumen($filtroOrganizadorTorneos);
    $proximoTorneo = $modeloTorneo->obtenerProximo($filtroOrganizadorTorneos);
    $proximosTorneos = $modeloTorneo->obtenerProximos(3, $filtroOrganizadorTorneos);
    $torneosRecientes = $modeloTorneo->obtenerRecientes(5, $filtroOrganizadorTorneos);
} catch (Throwable $error) {
    $errorCargaTorneos = true;
}

$categoriasFiltroTorneos = [];
$disciplinasFiltroTorneos = [];

foreach ($torneos as $torneoFiltro) {
    $categoriaFiltro = (string) ($torneoFiltro['categoria'] ?? '');
    $disciplinaFiltro = (string) ($torneoFiltro['disciplina'] ?? '');

    if ($categoriaFiltro !== '') {
        $categoriasFiltroTorneos[$categoriaFiltro] = $categoriaFiltro;
    }

    if ($disciplinaFiltro !== '') {
        $disciplinasFiltroTorneos[$disciplinaFiltro] = $disciplinaFiltro;
    }
}

natcasesort($categoriasFiltroTorneos);
natcasesort($disciplinasFiltroTorneos);

function escaparTorneo(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function normalizarDatoTorneo(string $valor): string
{
    $valor = strtr(trim($valor), [
        'Á' => 'A',
        'É' => 'E',
        'Í' => 'I',
        'Ó' => 'O',
        'Ú' => 'U',
        'Ü' => 'U',
        'Ñ' => 'N',
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ü' => 'u',
        'ñ' => 'n'
    ]);

    $valor = strtolower($valor);
    $valor = preg_replace('/[^a-z0-9]+/', '-', $valor) ?? '';

    return trim($valor, '-');
}

function datosVisualesEstadoTorneo(string $estado): array
{
    return match ($estado) {
        'en_curso' => [
            'data' => 'en-curso',
            'texto' => 'En curso',
            'clase' => 'insignia-activo'
        ],
        'finalizado' => [
            'data' => 'finalizado',
            'texto' => 'Finalizado',
            'clase' => 'insignia-finalizado'
        ],
        'cancelado' => [
            'data' => 'cancelado',
            'texto' => 'Cancelado',
            'clase' => 'insignia-finalizado'
        ],
        'inscripciones' => [
            'data' => 'proximo',
            'texto' => 'Inscripciones',
            'clase' => 'insignia-proximo'
        ],
        'borrador' => [
            'data' => 'proximo',
            'texto' => 'Borrador',
            'clase' => 'insignia-proximo'
        ],
        default => [
            'data' => 'proximo',
            'texto' => 'Próximo',
            'clase' => 'insignia-proximo'
        ]
    };
}

function datosVisualesDisciplinaTorneo(string $disciplina): array
{
    $clave = normalizarDatoTorneo($disciplina);

    return match ($clave) {
        'voleibol' => [
            'data' => 'voleibol',
            'clase' => 'escudo-morado',
            'contenido' => '<span class="icono-imagen-ui" data-icono="torneo" aria-hidden="true"></span>'
        ],
        'ajedrez' => [
            'data' => 'ajedrez',
            'clase' => 'escudo-oscuro',
            'contenido' => '<span data-icono="ajedrez" aria-hidden="true"></span>'
        ],
        'esports' => [
            'data' => 'esports',
            'clase' => 'escudo-verde',
            'contenido' => '<span data-icono="juego" aria-hidden="true"></span>'
        ],
        'tenis-de-mesa' => [
            'data' => 'tenis-de-mesa',
            'clase' => 'escudo-rojo',
            'contenido' => '<span data-icono="raqueta" aria-hidden="true"></span>'
        ],
        'basquetbol' => [
            'data' => 'basquetbol',
            'clase' => 'escudo-azul',
            'contenido' => '<span data-icono="balon" aria-hidden="true"></span>'
        ],
        'juego-de-cartas' => [
            'data' => 'otro',
            'clase' => 'escudo-naranja',
            'contenido' => '<span data-icono="cartas" aria-hidden="true"></span>'
        ],
        default => [
            'data' => 'otro',
            'clase' => 'escudo-morado',
            'contenido' => '<span data-icono="torneo" aria-hidden="true"></span>'
        ]
    };
}

function formatearFechaTorneo(?string $fecha): string
{
    if (!$fecha) {
        return '-';
    }

    try {
        return (new DateTimeImmutable($fecha))->format('d/m/Y');
    } catch (Throwable) {
        return '-';
    }
}


function formatearHoraTorneo(?string $hora): string
{
    if (!$hora) {
        return '-';
    }

    return substr($hora, 0, 5);
}

function formatearFechaHoraTorneo(?string $fecha): string
{
    if (!$fecha) {
        return '-';
    }

    try {
        return (new DateTimeImmutable($fecha))->format('d/m/Y H:i');
    } catch (Throwable) {
        return '-';
    }
}

function formatearTiempoRelativoTorneo(?string $fecha): string
{
    if (!$fecha) {
        return '-';
    }

    try {
        $momento = new DateTimeImmutable($fecha);
        $ahora = new DateTimeImmutable('now', $momento->getTimezone());
        $segundos = max(0, $ahora->getTimestamp() - $momento->getTimestamp());

        if ($segundos < 60) {
            return 'ahora';
        }

        $minutos = (int) floor($segundos / 60);
        if ($minutos < 60) {
            return 'hace ' . $minutos . ' min';
        }

        $horas = (int) floor($segundos / 3600);
        if ($horas < 24) {
            return 'hace ' . $horas . ' h';
        }

        $dias = (int) floor($segundos / 86400);
        if ($dias === 1) {
            return 'ayer';
        }
        if ($dias < 30) {
            return 'hace ' . $dias . ' días';
        }

        return $momento->format('d/m/Y');
    } catch (Throwable) {
        return '-';
    }
}

require_once __DIR__ . '/servicios/OrientacionTorneo.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Torneos - ArenaCJD</title>
<link rel="icon" type="image/png" href="imagenes/arena-cjd-isotipo.png">
<script src="js/tema-inicial.js"></script>
<script src="js/iconos.js?v=20260913-realizacion1"></script>
<script src="js/modalidades-torneos.js?v=20260913-realizacion3"></script>
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
<link rel="stylesheet" href="css/paginas/torneos.css?v=20260913-mobile1">
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

<div class="contenido-app">
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

<main class="panel">

<div class="cabecera-pagina-torneos">
<div><h1 class="titulo-pagina titulo-modulo">Torneos</h1><p class="subtitulo-pagina"><?= $esAdministradorTorneos
    ? 'Administra y organiza todos los torneos del sistema.'
    : ($esOrganizadorTorneos ? 'Gestiona únicamente los torneos que tienes asignados.' : 'Consulta torneos, detalles e inscripciones disponibles.') ?></p></div>
<?php if ($esAdministradorTorneos): ?>
<button class="boton boton-principal boton-nuevo-torneo" id="crearNuevoTorneo" type="button"><span data-icono="agregar" aria-hidden="true"></span>Nuevo torneo</button>
<?php endif; ?>
</div>

<div class="grid-estadisticas">
<div class="tarjeta-estadistica">
<div class="icono-estadistica morado icono-resumen-unificado"><span class="icono-imagen-ui" data-icono="torneo" aria-hidden="true"></span></div>
<div class="texto-estadistica">
<span class="etiqueta-estadistica">Torneos activos</span>
<span class="valor-estadistica"><?= $resumenTorneos['activos'] ?></span>
<span class="detalle-estadistica">En curso actualmente</span>
</div>
</div>

<div class="tarjeta-estadistica">
<div class="icono-estadistica verde icono-resumen-unificado"><span class="icono-imagen-ui" data-icono="resultados" aria-hidden="true"></span></div>
<div class="texto-estadistica">
<span class="etiqueta-estadistica">Torneos finalizados</span>
<span class="valor-estadistica"><?= $resumenTorneos['finalizados'] ?></span>
<span class="detalle-estadistica">Completados</span>
</div>
</div>

<div class="tarjeta-estadistica">
<div class="icono-estadistica naranja icono-resumen-unificado"><span class="icono-imagen-ui" data-icono="calendario" aria-hidden="true"></span></div>
<div class="texto-estadistica">
<span class="etiqueta-estadistica">Próximo torneo</span>
<span class="valor-destacado"><?= $proximoTorneo ? escaparTorneo($proximoTorneo['nombre']) : 'Sin torneos próximos' ?></span>
<span class="detalle-estadistica"><?= $proximoTorneo ? 'Inicia el ' . escaparTorneo(formatearFechaTorneo($proximoTorneo['fecha_inicio'])) . ' a las ' . escaparTorneo(formatearHoraTorneo($proximoTorneo['hora_inicio'] ?? null)) : 'No hay fechas próximas registradas' ?></span>
</div>
</div>

<div class="tarjeta-estadistica">
<div class="icono-estadistica azul icono-resumen-unificado"><span class="icono-imagen-ui" data-icono="participantes" aria-hidden="true"></span></div>
<div class="texto-estadistica">
<span class="etiqueta-estadistica">Total participantes</span>
<span class="valor-estadistica"><?= $resumenTorneos['participantes'] ?></span>
<span class="detalle-estadistica">Usuarios con inscripción aprobada</span>
</div>
</div>
</div>

<div class="columnas-torneos">

<aside class="columna-filtros">
<form class="tarjeta tarjeta-filtros" id="formularioFiltrosTorneos">
<h2 class="titulo-seccion">Filtros de torneos</h2>

<div class="grupo-formulario">
<label for="buscarTorneo">Buscar torneo</label>
<input type="search" id="buscarTorneo" class="campo-formulario" placeholder="Nombre del torneo" autocomplete="off">
</div>

<div class="grupo-formulario">
<label for="filtroCategoria">Categoría</label>
<select id="filtroCategoria" class="selector-formulario">
<option value="">Todas las categorías</option>
<?php foreach ($categoriasFiltroTorneos as $categoriaFiltro): ?>
<option value="<?= escaparTorneo(normalizarDatoTorneo($categoriaFiltro)) ?>"><?= escaparTorneo($categoriaFiltro) ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="grupo-formulario">
<label for="filtroDisciplina">Disciplina</label>
<select id="filtroDisciplina" class="selector-formulario">
<option value="">Todas las disciplinas</option>
<?php foreach ($disciplinasFiltroTorneos as $disciplinaFiltro): ?>
<?php $visualFiltro = datosVisualesDisciplinaTorneo($disciplinaFiltro); ?>
<option value="<?= escaparTorneo($visualFiltro['data']) ?>"><?= escaparTorneo($disciplinaFiltro) ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="grupo-formulario">
<label for="filtroEstado">Estado</label>
<select id="filtroEstado" class="selector-formulario">
<option value="">Estados activos</option>
<option value="proximo">Próximo</option>
<option value="en-curso">En curso</option>
<option value="finalizado">Finalizado</option>
<option value="cancelado">Cancelado</option>
<option value="todos">Todos</option>
</select>
</div>

<fieldset class="grupo-formulario grupo-rango-fechas">
<legend>Fecha de inicio</legend>
<div class="campos-fecha">
<div>
<label for="fechaDesde">Desde</label>
<input type="date" id="fechaDesde" class="campo-formulario">
</div>
<div>
<label for="fechaHasta">Hasta</label>
<input type="date" id="fechaHasta" class="campo-formulario">
</div>
</div>
</fieldset>

<div class="acciones-filtros">
<button type="submit" class="boton boton-principal boton-bloque">Aplicar filtros</button>
<button type="reset" class="boton boton-claro boton-bloque">Limpiar</button>
</div>
</form>
</aside>

<section class="principal-torneos">
<div class="cabecera-tarjeta">
<div><h2 class="titulo-seccion">Lista de torneos</h2><p class="descripcion-lista-torneos">Prioriza el torneo que necesitas gestionar y continúa con su siguiente etapa desde el menú de acciones.</p></div>
<div class="orden">
<label>Ordenar por:</label>
<select class="selector-formulario selector-orden" aria-label="Ordenar torneos">
<option>Más recientes</option>
</select>
</div>
</div>
<div class="resumen-filtros-ah" id="resumenFiltrosTorneos" hidden></div>

<div class="lista-torneos">

<?php foreach ($torneos as $torneo): ?>
<?php
$estadoVisual = datosVisualesEstadoTorneo((string) $torneo['estado']);
$disciplinaVisual = datosVisualesDisciplinaTorneo((string) $torneo['disciplina']);
$categoriaData = normalizarDatoTorneo((string) $torneo['categoria']);
$nombreData = normalizarDatoTorneo((string) $torneo['nombre']);
$progreso = calcularProgresoTorneo($torneo);
$etiquetaProgreso = 'Resultados completados';
$cantidadInscritos = (int) $torneo['cantidad_inscritos'];
$esEquipo = $torneo['modalidad'] === 'equipo';
$textoCantidad = $cantidadInscritos . ' ' . ($esEquipo
    ? ($cantidadInscritos === 1 ? 'equipo' : 'equipos')
    : ($cantidadInscritos === 1 ? 'participante' : 'participantes'));

$torneoCerrado = in_array($torneo['estado'], ['finalizado', 'cancelado'], true);
$siguienteAccion = siguienteAccionTorneo($torneo);
$totalEnfrentamientosTarjeta = max(0, (int) ($torneo['total_enfrentamientos'] ?? 0));
$finalizadosTarjeta = max(0, (int) ($torneo['enfrentamientos_finalizados'] ?? 0));
$textoEnfrentamientosTarjeta = $totalEnfrentamientosTarjeta === 1 ? 'enfrentamiento finalizado' : 'enfrentamientos finalizados';
$puedeGestionarTorneo = $esAdministradorTorneos || (
    $esOrganizadorTorneos &&
    (int) $torneo['id_organizador'] === $idUsuarioTorneos
);
?>

<article
class="tarjeta-torneo"
data-id-torneo="<?= (int) $torneo['id_torneo'] ?>"
data-nombre="<?= escaparTorneo($nombreData) ?>"
data-estado="<?= escaparTorneo($estadoVisual['data']) ?>"
data-disciplina="<?= escaparTorneo($disciplinaVisual['data']) ?>"
data-categoria="<?= escaparTorneo($categoriaData) ?>"
data-fecha-inicio="<?= escaparTorneo($torneo['fecha_inicio']) ?>"
data-hora-inicio="<?= escaparTorneo($torneo['hora_inicio'] ?? '') ?>"
data-publicado="<?= !empty($torneo['publicado']) ? '1' : '0' ?>"
<?= $torneoCerrado ? 'hidden' : '' ?>
>
<div class="escudo-torneo <?= escaparTorneo($disciplinaVisual['clase']) ?>"><span class="media-entidad-fallback"><?= $disciplinaVisual['contenido'] ?></span><img class="imagen-torneo-tarjeta" src="api/imagen_torneo.php?id_torneo=<?= (int) $torneo['id_torneo'] ?>" alt="Imagen de <?= escaparTorneo($torneo['nombre']) ?>" loading="lazy" onerror="this.hidden=true"></div>
<div class="cuerpo-torneo">
<div class="fila-superior-torneo"><h3 class="nombre-torneo"><?= escaparTorneo($torneo['nombre']) ?></h3><div class="insignias-torneo"><span class="insignia-publicacion<?= !empty($torneo['publicado']) ? ' publicada' : '' ?>"><?= !empty($torneo['publicado']) ? 'Público' : 'Privado' ?></span><span class="insignia-torneo <?= escaparTorneo($estadoVisual['clase']) ?>"><?= escaparTorneo($estadoVisual['texto']) ?></span></div></div>
<div class="fila-meta-torneo"><span class="meta-torneo"><b>Formato</b><?= escaparTorneo($torneo['tipo_torneo']) ?></span><span class="meta-torneo"><b>Participación</b><?= escaparTorneo($textoCantidad) ?></span><span class="meta-torneo"><b>Disciplina</b><?= escaparTorneo($torneo['disciplina']) ?></span><span class="meta-torneo meta-categoria"><b>Categoría</b><?= escaparTorneo($torneo['categoria']) ?></span></div>
<div class="fila-fechas-torneo"><span><b>Inicio</b> <?= escaparTorneo(formatearFechaTorneo($torneo['fecha_inicio'])) ?> · <?= escaparTorneo(formatearHoraTorneo($torneo['hora_inicio'] ?? null)) ?></span><span class="flecha-fecha"><span data-icono="derecha" aria-hidden="true"></span></span><span><b>Fin</b> <?= escaparTorneo(formatearFechaTorneo($torneo['fecha_fin'])) ?></span></div>
<div class="progreso-torneo"><div class="cabecera-progreso-torneo"><span class="etiqueta-progreso-torneo"><?= escaparTorneo($etiquetaProgreso) ?></span><span class="texto-progreso"><?= $progreso ?>%</span></div><div class="fondo-progreso" role="progressbar" aria-label="<?= escaparTorneo($etiquetaProgreso) ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $progreso ?>"><span class="relleno-progreso<?= $progreso === 100 ? ' progreso-verde' : '' ?>" style="width:<?= $progreso ?>%;"></span></div></div>
<div class="progreso-concreto-torneo-ah"><strong><?= escaparTorneo($textoCantidad) ?></strong><span>·</span><span><?= $totalEnfrentamientosTarjeta > 0 ? $finalizadosTarjeta . '/' . $totalEnfrentamientosTarjeta . ' ' . $textoEnfrentamientosTarjeta : 'Sin enfrentamientos generados' ?></span></div>
<?php if ($puedeGestionarTorneo && $siguienteAccion): ?><div class="siguiente-accion-torneo-ah"><div><span>Siguiente paso</span><strong><?= escaparTorneo($siguienteAccion['texto']) ?></strong><small><?= escaparTorneo($siguienteAccion['detalle']) ?></small></div><a class="boton boton-principal accion-principal-torneo" href="<?= escaparTorneo($siguienteAccion['url']) ?>">Continuar</a></div><?php endif; ?>
<div class="acciones-torneo">
<?php if ($puedeGestionarTorneo && !$torneoCerrado): ?>
<button class="boton boton-claro boton-administrar" type="button" data-accion-torneo="editar" data-id-torneo="<?= (int) $torneo['id_torneo'] ?>">Administrar</button>
<?php endif; ?>
<button class="boton-accion" type="button" data-accion-torneo="detalles" data-id-torneo="<?= (int) $torneo['id_torneo'] ?>">Detalles</button>
<?php if ($puedeGestionarTorneo && !$torneoCerrado): ?>
<div class="contenedor-menu-torneo">
<button class="mas-torneo" type="button" data-accion-torneo="menu" data-id-torneo="<?= (int) $torneo['id_torneo'] ?>" aria-label="Más opciones" aria-expanded="false"><span data-icono="mas" aria-hidden="true"></span></button>
<div class="menu-contextual-torneo" data-menu-torneo="<?= (int) $torneo['id_torneo'] ?>" hidden>
<button type="button" data-accion-torneo="editar" data-id-torneo="<?= (int) $torneo['id_torneo'] ?>">Editar</button>
<span class="separador-menu-torneo">Continuar gestión</span><a href="sorteos.php?torneo=<?= (int) $torneo['id_torneo'] ?>">Sorteo</a><a href="partidos.php?torneo=<?= (int) $torneo['id_torneo'] ?>">Enfrentamientos</a><a href="resultados.php?torneo=<?= (int) $torneo['id_torneo'] ?>">Resultados</a><a href="clasificacion.php?torneo=<?= (int) $torneo['id_torneo'] ?>">Clasificación</a><button type="button" class="accion-cancelar-torneo" data-accion-torneo="cancelar" data-id-torneo="<?= (int) $torneo['id_torneo'] ?>">Cancelar torneo</button>
</div>
</div>
<?php endif; ?>
</div>
</div></article>

<?php endforeach; ?>

</div>

<div class="estado-vacio-ah estado-vacio-torneos" id="mensajeSinTorneos"<?= (!$torneos || $errorCargaTorneos) ? '' : ' hidden' ?>><strong id="tituloSinTorneos"><?= $errorCargaTorneos ? 'No se pudieron cargar los torneos' : (!$torneos ? 'Todavía no hay torneos' : 'No hay coincidencias') ?></strong><p id="detalleSinTorneos"><?= $errorCargaTorneos ? 'Intenta actualizar la vista dentro de unos instantes.' : (!$torneos ? 'Crea el primer torneo para comenzar a registrar participantes y generar enfrentamientos.' : 'Prueba con otros filtros o restablece la búsqueda.') ?></p><?php if ($esAdministradorTorneos): ?><button class="boton boton-principal" id="accionSinTorneos" type="button"><?= $torneos ? 'Quitar filtros' : 'Crear torneo' ?></button><?php else: ?><button class="boton boton-claro" id="accionSinTorneos" type="button">Quitar filtros</button><?php endif; ?></div>

<footer class="pie-resultados-torneos" id="pieResultadosTorneos"<?= !$torneos ? ' hidden' : '' ?>>
<div class="paginacion" id="paginacionTorneos">
<span class="info-paginacion" id="infoPaginacionTorneos"><?= count($torneos) === 1 ? 'Mostrando 1 de 1 torneo' : 'Mostrando ' . count($torneos) . ' de ' . count($torneos) . ' torneos' ?></span>
<div class="controles-paginacion" id="controlesPaginacionTorneos"></div>
</div>
<p class="estado-sincronizacion-torneos" id="estadoSincronizacionTorneos" aria-live="polite">Datos actualizados</p>
</footer>
</section>

<aside class="lateral-torneos">
<div class="tarjeta tarjeta-actividad tarjeta-actividad-reciente">
<div class="cabecera-actividad-torneos">
<h2 class="titulo-seccion">Actividad reciente</h2>
<a class="enlace-actividad-completa" href="mi-actividad.php">Ver toda la actividad</a>
</div>
<div class="lista-actividad">
<?php if ($torneosRecientes): ?>
<?php foreach ($torneosRecientes as $torneoReciente): ?>
<div class="item-actividad">
<div class="punto-actividad punto-morado" aria-hidden="true"><span data-icono="torneo" aria-hidden="true"></span></div>
<div class="contenido-actividad">
<span class="titulo-actividad">Torneo registrado</span>
<span class="descripcion-actividad"><?= escaparTorneo($torneoReciente['nombre']) ?></span>
<time class="tiempo-actividad" datetime="<?= escaparTorneo((string) $torneoReciente['fecha_creacion']) ?>" title="<?= escaparTorneo(formatearFechaHoraTorneo($torneoReciente['fecha_creacion'])) ?>"><?= escaparTorneo(formatearTiempoRelativoTorneo($torneoReciente['fecha_creacion'])) ?></time>
</div>
</div>
<?php endforeach; ?>
<?php else: ?>
<div class="item-actividad">
<div class="punto-actividad punto-morado" aria-hidden="true"><span data-icono="torneo" aria-hidden="true"></span></div>
<div class="contenido-actividad">
<span class="titulo-actividad">Sin actividad de torneos</span>
<span class="descripcion-actividad">Todavía no hay torneos registrados.</span>
</div>
</div>
<?php endif; ?>
</div>
<button class="boton boton-claro boton-bloque boton-actualizar-actividad" id="actualizarTorneosAhora" type="button">Actualizar ahora</button>
</div>

<div class="tarjeta tarjeta-actividad tarjeta-proximos-torneos<?= $proximosTorneos ? '' : ' estado-vacio-proximos' ?>">
<h2 class="titulo-seccion">Próximos torneos</h2>
<div class="lista-proximos">
<?php if ($proximosTorneos): ?>
<?php foreach ($proximosTorneos as $indiceProximo => $torneoProximo): ?>
<div class="item-proximo">
<div class="icono-proximo <?= $indiceProximo % 2 === 0 ? 'proximo-morado' : 'proximo-azul' ?>"><span data-icono="calendario" aria-hidden="true"></span></div>
<div class="info-proximo">
<span class="nombre-proximo"><?= escaparTorneo($torneoProximo['nombre']) ?></span>
<span class="fecha-proximo"><?= escaparTorneo(formatearFechaTorneo($torneoProximo['fecha_inicio'])) ?> · <?= escaparTorneo(formatearHoraTorneo($torneoProximo['hora_inicio'] ?? null)) ?></span>
<span class="sub-proximo"><?= escaparTorneo($torneoProximo['disciplina']) ?> · <?= escaparTorneo($torneoProximo['tipo_torneo']) ?></span>
</div>
</div>
<?php endforeach; ?>
<?php else: ?>
<div class="item-proximo">
<div class="icono-proximo proximo-morado"><span data-icono="calendario" aria-hidden="true"></span></div>
<div class="info-proximo">
<span class="nombre-proximo">No hay torneos próximos</span>
<span class="fecha-proximo">Crea un torneo para verlo aquí.</span>
</div>
</div>
<?php endif; ?>
</div>
<a class="boton boton-claro boton-bloque boton-calendario-secundario" href="calendario.php"><span data-icono="calendario" aria-hidden="true"></span> Ver calendario completo</a>
</div>
</aside>

</div>

</main>
</div>
</div>


<div class="modal-torneo" id="modalDetalleTorneo" aria-hidden="true">
<div class="modal-torneo-fondo" data-cerrar-modal-torneo="detalle"></div>
<section class="modal-torneo-contenido" role="dialog" aria-modal="true" aria-labelledby="tituloDetalleTorneo">
<header class="modal-torneo-cabecera"><div><span class="modal-torneo-etiqueta">Torneo</span><h2 id="tituloDetalleTorneo">Detalles del torneo</h2><p>Información actual del torneo.</p></div><button class="modal-torneo-cerrar" type="button" data-cerrar-modal-torneo="detalle" aria-label="Cerrar"><span data-icono="peligro" aria-hidden="true"></span></button></header>
<div class="detalle-torneo-real" id="contenidoDetalleTorneo"></div>
<footer class="acciones-modal-torneo"><button class="boton boton-claro" type="button" data-cerrar-modal-torneo="detalle">Cerrar</button></footer>
</section>
</div>

<div class="modal-torneo" id="modalEditarTorneo" aria-hidden="true">
<div class="modal-torneo-fondo" data-cerrar-modal-torneo="editar"></div>
<section class="modal-torneo-contenido" role="dialog" aria-modal="true" aria-labelledby="tituloEditarTorneo">
<header class="modal-torneo-cabecera"><div><span class="modal-torneo-etiqueta">Gestión</span><h2 id="tituloEditarTorneo">Editar torneo</h2><p id="descripcionEditarTorneo">Los cambios se guardan directamente en la tabla torneos.</p></div><button class="modal-torneo-cerrar" type="button" data-cerrar-modal-torneo="editar" aria-label="Cerrar"><span data-icono="peligro" aria-hidden="true"></span></button></header>
<form class="formulario-editar-torneo" id="formularioEditarTorneo">
<input type="hidden" id="editarTorneoId">
<div class="grupo-formulario grupo-formulario-completo"><label for="editarTorneoNombre">Nombre</label><input class="campo-formulario" id="editarTorneoNombre" type="text" maxlength="120" required></div>
<div class="grupo-formulario grupo-formulario-completo"><label for="editarTorneoImagen">Imagen del torneo <span class="texto-opcional">(opcional)</span></label><div class="selector-imagen-entidad"><div class="preview-imagen-entidad" id="previewTorneoImagen"><span aria-hidden="true"><span data-icono="torneo" aria-hidden="true"></span></span><img id="previewTorneoImagenImg" hidden alt="Vista previa de la imagen del torneo"></div><div><div class="controles-imagen-entidad"><label class="boton boton-claro" for="editarTorneoImagen">Cambiar imagen</label><button class="boton boton-claro" id="quitarTorneoImagen" type="button" hidden>Quitar imagen</button></div><input class="entrada-imagen-entidad" id="editarTorneoImagen" type="file" accept="image/jpeg,image/png,image/webp"><small class="ayuda-imagen-entidad">Se usa una copa como imagen estándar. Puedes cambiarla por JPG, PNG o WebP · máximo 4 MB · mínimo 120 × 120 px. Se mostrará en tarjetas, resultados, calendario y área pública.</small></div></div></div>
<div class="grupo-formulario"><label for="editarTorneoDisciplina">Disciplina</label><select class="selector-formulario" id="editarTorneoDisciplina" required></select></div>
<div class="grupo-formulario"><label for="editarTorneoCategoria">Categoría</label><select class="selector-formulario" id="editarTorneoCategoria" required></select></div>
<div class="grupo-formulario" id="grupoTorneoTipo"><label for="editarTorneoTipo">Tipo de torneo</label><select class="selector-formulario" id="editarTorneoTipo" required></select><small>Define el formato competitivo que utilizará el torneo.</small></div>
<div class="grupo-formulario"><label for="editarTorneoModalidad">Modalidad de participación</label><select class="selector-formulario" id="editarTorneoModalidad" required><option value="individual">Individual</option><option value="equipo">Por equipos</option></select></div>
<fieldset class="grupo-formulario grupo-formulario-completo grupo-realizacion-torneo" id="grupoTorneoRealizacion">
<legend>Modalidad de realización</legend>
<div class="opciones-realizacion-torneo" role="radiogroup" aria-label="Modalidad de realización del torneo">
<label class="opcion-realizacion-torneo" for="realizacionTorneoPresencial">
<input id="realizacionTorneoPresencial" type="radio" name="realizacionTorneo" value="presencial">
<span class="icono-realizacion-torneo" aria-hidden="true"><span data-icono="presencial"></span></span>
<span class="texto-realizacion-torneo"><strong>Presencial</strong><small>La competencia se realiza físicamente en una ubicación.</small></span>
<span class="marca-realizacion-torneo" aria-hidden="true"></span>
</label>
<label class="opcion-realizacion-torneo" for="realizacionTorneoVirtual">
<input id="realizacionTorneoVirtual" type="radio" name="realizacionTorneo" value="virtual">
<span class="icono-realizacion-torneo" aria-hidden="true"><span data-icono="virtual"></span></span>
<span class="texto-realizacion-torneo"><strong>Virtual</strong><small>La competencia se realiza de forma remota u online.</small></span>
<span class="marca-realizacion-torneo" aria-hidden="true"></span>
</label>
</div>
<small id="ayudaRealizacionTorneo">Esta preferencia se guarda únicamente en este navegador y no modifica la base de datos.</small>
<small class="aviso-realizacion-sin-definir" id="avisoRealizacionSinDefinir" hidden>Este torneo todavía no tiene una modalidad de realización definida en este navegador.</small>
</fieldset>
<div class="grupo-formulario" id="grupoTorneoOrganizador"><label for="editarTorneoOrganizador">Organizador asignado</label><select class="selector-formulario" id="editarTorneoOrganizador" required></select><small>El organizador podrá gestionar participantes, sorteos y resultados de este torneo.</small></div>
<div class="grupo-formulario"><label for="editarTorneoInicio">Fecha de inicio</label><input class="campo-formulario" id="editarTorneoInicio" type="date" required></div>
<div class="grupo-formulario"><label for="editarTorneoHoraInicio">Hora de inicio</label><input class="campo-formulario" id="editarTorneoHoraInicio" type="time" required></div>
<div class="grupo-formulario"><label for="editarTorneoFin">Fecha de finalización</label><input class="campo-formulario" id="editarTorneoFin" type="date" required></div>
<div class="grupo-formulario" id="grupoTorneoEstado"><label for="editarTorneoEstado">Estado</label><select class="selector-formulario" id="editarTorneoEstado" required><option value="borrador">Borrador</option><option value="inscripciones">Inscripciones</option><option value="en_curso">En curso</option><option value="finalizado">Finalizado</option><option value="cancelado">Cancelado</option></select></div>
<div class="grupo-formulario estado-inicial-torneo" id="estadoInicialTorneo" hidden><span class="etiqueta-estado-inicial">Estado inicial</span><strong><span data-icono="circulo" aria-hidden="true"></span> Borrador</strong><small>Los torneos nuevos siempre se crean como borrador. Podrás abrir las inscripciones después de revisar la configuración.</small></div>
<div class="grupo-formulario grupo-publicacion-torneo" id="grupoTorneoPublicado"><label class="opcion-publicacion-torneo" for="editarTorneoPublicado"><input id="editarTorneoPublicado" type="checkbox"><span><strong>Visible en el área pública</strong><small>Permite que visitantes sin cuenta consulten este torneo, sus cruces, resultados y clasificación.</small></span></label></div>
<div class="grupo-formulario"><label for="editarTorneoCupo">Cupo máximo</label><input class="campo-formulario" id="editarTorneoCupo" type="number" min="1" max="65535" placeholder="Sin límite"></div>
<div class="grupo-formulario"><label for="editarTorneoPeriodoGracia">Período de gracia (minutos)</label><input class="campo-formulario" id="editarTorneoPeriodoGracia" type="number" min="5" max="10080" step="5" value="60" placeholder="60"><small>Tiempo adicional después del encuentro para registrar o confirmar el resultado antes de enviarlo a revisión.</small></div>
<p class="mensaje-edicion-torneo grupo-formulario-completo" id="mensajeEditarTorneo" aria-live="polite"></p>
<footer class="acciones-modal-torneo grupo-formulario-completo"><button class="boton boton-claro" type="button" data-cerrar-modal-torneo="editar">Cancelar</button><button class="boton boton-principal" id="guardarTorneoModal" type="submit">Guardar cambios</button></footer>
</form>
</section>
</div>

<div class="modal-torneo" id="modalCancelarTorneo" aria-hidden="true">
<div class="modal-torneo-fondo" data-cerrar-modal-torneo="cancelar"></div>
<section class="modal-torneo-contenido modal-torneo-cancelar" role="dialog" aria-modal="true" aria-labelledby="tituloCancelarTorneo">
<header class="modal-torneo-cabecera"><div><span class="modal-torneo-etiqueta">Acción sensible</span><h2 id="tituloCancelarTorneo">Cancelar torneo</h2><p>El torneo quedará inactivo, pero se conservará todo su historial.</p></div><button class="modal-torneo-cerrar" type="button" data-cerrar-modal-torneo="cancelar" aria-label="Cerrar"><span data-icono="peligro" aria-hidden="true"></span></button></header>
<div class="aviso-cancelar-torneo"><span aria-hidden="true"><span data-icono="alerta" aria-hidden="true"></span></span><div><strong id="nombreCancelarTorneo">Torneo</strong><p>No se eliminarán participantes, inscripciones, enfrentamientos ni resultados.</p></div></div>
<div class="grupo-formulario grupo-formulario-completo"><label for="motivoCancelarTorneo">Motivo de cancelación</label><textarea class="campo-formulario" id="motivoCancelarTorneo" rows="3" minlength="5" maxlength="300" required placeholder="Ej.: No se alcanzó el mínimo de participantes"></textarea><small>El motivo quedará registrado en el historial de auditoría.</small></div>
<p class="mensaje-edicion-torneo grupo-formulario-completo" id="mensajeCancelarTorneo" aria-live="polite"></p>
<footer class="acciones-modal-torneo"><button class="boton boton-claro" type="button" data-cerrar-modal-torneo="cancelar">Volver</button><button class="boton boton-principal boton-confirmar-cancelacion" id="confirmarCancelarTorneo" type="button">Confirmar cancelación</button></footer>
</section></div>

<script src="js/sincronizacion.js?v=20260831-central1"></script>
<script src="js/componentes.js?v=20260913-sin-presencia1"></script>
<script src="js/experiencia.js?v=20260910-guia1"></script>
<script src="js/menu.js?v=20260909-sprint11"></script>
<script src="js/tema.js?v=20260909-login-tema1"></script>
<script src="js/torneos.js?v=20260913-realizacion3"></script>
</body>
</html>
