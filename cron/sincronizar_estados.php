<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acceso no permitido.');
}

require_once __DIR__ . '/../config/Conexion.php';

require_once __DIR__ . '/../modelos/Torneo.php';
require_once __DIR__ . '/../modelos/Enfrentamiento.php';

try {
    $conexion = (new Conexion())->conectar();
    $modelo = new Torneo($conexion);
    $actualizados = $modelo->sincronizarEstadosTemporales();
    $modeloEnfrentamiento = new Enfrentamiento($conexion);
    $reporte = $modeloEnfrentamiento->procesarPeriodosGracia();
    echo '[' . date('Y-m-d H:i:s') . '] Estados sincronizados: ' . $actualizados . ' torneos. Enfrentamientos en revisión por gracia vencida: ' . $reporte['enviados_a_revision'] . PHP_EOL;
    exit(0);
} catch (Throwable $error) {
    fwrite(STDERR, '[' . date('Y-m-d H:i:s') . '] No se pudieron sincronizar los estados: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
