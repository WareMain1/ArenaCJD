<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function escaparPublico(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function abrirPaginaPublica(string $titulo, string $activa, string $pagina): void
{
    $sesionActiva = !empty($_SESSION['usuario_id']);
    $enlaces = [
        'inicio' => ['Inicio', 'index.php'],
        'torneos' => ['Torneos', 'torneos-publicos.php'],
        'enfrentamientos' => ['Enfrentamientos', 'enfrentamientos-publicos.php'],
        'resultados' => ['Resultados', 'resultados-publicos.php'],
        'clasificacion' => ['Clasificación', 'clasificacion-publica.php'],
        'calendario' => ['Calendario', 'calendario-publico.php']
    ];
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= escaparPublico($titulo) ?> - ArenaCJD</title>
<link rel="icon" type="image/png" href="imagenes/arena-cjd-isotipo.png">
<script src="js/tema-inicial.js"></script>
<script src="js/iconos.js?v=20260909-sprint11"></script>
<link rel="stylesheet" href="css/variables.css?v=20260909-audit12">
<link rel="stylesheet" href="css/layout.css?v=20260909-sprint13">
<link rel="stylesheet" href="css/componentes.css?v=20260910-visibilidad1">
<link rel="stylesheet" href="css/animaciones.css">
<link rel="stylesheet" href="css/paginas/index.css?v=20260909-sprint11">
<link rel="stylesheet" href="css/paginas/area-publica.css?v=20260910-mobile1">
<link rel="stylesheet" href="css/utilidades.css?v=20260909-sprint11">
<link rel="stylesheet" href="css/tema-claro.css?v=20260909-sprint11">
<link rel="stylesheet" href="css/tema-oscuro.css?v=20260908-toast1">
<link rel="stylesheet" href="css/contraste-claro.css?v=20260909-audit12">
<link rel="stylesheet" href="css/formularios.css?v=20260910-mobile1">
</head>
<body class="pagina-publica-consulta" data-pagina-publica="<?= escaparPublico($pagina) ?>" data-tema-autenticado="<?= $sesionActiva ? '1' : '0' ?>">
<header class="encabezado-publico encabezado-consulta-publica">
<div class="barra-publica barra-consulta-publica">
<a href="index.php" class="marca-publica" aria-label="ArenaCJD, inicio"><img src="imagenes/arena-cjd-isotipo.png" alt="Isotipo de ArenaCJD" class="marca-publica-icono"><div class="marca-publica-texto"><strong>ArenaCJD</strong><span>Consulta pública</span></div></a>
<button class="boton-menu-publico" id="botonMenuPublico" type="button" aria-expanded="false" aria-controls="navegacionPublica"><span data-icono="menu" aria-hidden="true"></span><b>Menú</b></button><nav class="navegacion-publica navegacion-consulta-publica" id="navegacionPublica" aria-label="Navegación pública"><ul>
<?php foreach ($enlaces as $clave => [$texto, $url]): ?>
<li><a href="<?= escaparPublico($url) ?>" class="enlace-navegacion"<?= $activa === $clave ? ' aria-current="page"' : '' ?>><?= escaparPublico($texto) ?></a></li>
<?php endforeach; ?>
</ul></nav>
<div class="acciones-publicas acciones-consulta-publica"><div class="selector-tema-publico" aria-label="Seleccionar tema"><button type="button" data-boton-tema="claro" aria-label="Usar tema claro"><span data-icono="sol" aria-hidden="true"></span></button><button type="button" data-boton-tema="oscuro" aria-label="Usar tema oscuro"><span data-icono="luna" aria-hidden="true"></span></button><button type="button" data-boton-tema="sistema" aria-label="Usar tema del sistema"><span data-icono="sistema" aria-hidden="true"></span></button></div><a class="boton boton-principal boton-acceso-publico" href="<?= $sesionActiva ? 'panel.php' : 'index.php#acceso' ?>"><?= $sesionActiva ? 'Ir al panel' : 'Iniciar sesión' ?></a></div>
</div>
</header>
<main class="contenedor-consulta-publica">
<?php
}

function cerrarPaginaPublica(): void
{
    ?>
</main>
<?php require __DIR__ . '/footer.php'; ?>
<script src="js/tema.js?v=20260909-login-tema1"></script>
<script src="js/area-publica.js?v=20260909-iconos4"></script>
</body>
</html>
<?php
}
