<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acceso no permitido.');
}

require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../modelos/Enfrentamiento.php';
require_once __DIR__ . '/../modelos/Torneo.php';

try {
    $conexion = (new Conexion())->conectar();
    $modeloTorneo = new Torneo($conexion);
    $torneosSincronizados = $modeloTorneo->sincronizarEstadosTemporales();
    $modeloEnfrentamiento = new Enfrentamiento($conexion);
    $reporte = $modeloEnfrentamiento->procesarPeriodosGracia();

    $ahora = date('Y-m-d H:i:s');
    echo "[{$ahora}] Procesamiento de períodos de gracia completado:" . PHP_EOL;
    echo "  - Torneos sincronizados a en_curso: {$torneosSincronizados}" . PHP_EOL;
    echo "  - Enfrentamientos evaluados: {$reporte['evaluados']}" . PHP_EOL;
    echo "  - Entraron en período de gracia: {$reporte['entraron_en_gracia']}" . PHP_EOL;
    echo "  - Enviados a revisión manual: {$reporte['enviados_a_revision']}" . PHP_EOL;

    foreach ($reporte['detalles'] as $detalle) {
        echo "    * Enfrentamiento #{$detalle['id_enfrentamiento']}: {$detalle['accion']} [{$detalle['motivo']}]" . PHP_EOL;
    }

    exit(0);
} catch (Throwable $error) {
    fwrite(STDERR, '[' . date('Y-m-d H:i:s') . '] Error en procesamiento de períodos de gracia: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
