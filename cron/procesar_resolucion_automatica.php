<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acceso no permitido.');
}

if (!getenv('ARENA_DB_PASS')) {
    putenv('ARENA_DB_PASS=Maracaibo24158$');
}

require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../modelos/Enfrentamiento.php';
require_once __DIR__ . '/../modelos/Torneo.php';

try {
    $conexion = (new Conexion())->conectar();

    // Primero sincronizar torneos que comienzan
    $modeloTorneo = new Torneo($conexion);
    $torneosSincronizados = $modeloTorneo->sincronizarEstadosTemporales();

    // Procesar resolución automática y período de gracia
    $modeloEnfrentamiento = new Enfrentamiento($conexion);
    $reporte = $modeloEnfrentamiento->procesarResolucionAutomatica();

    $ahora = date('Y-m-d H:i:s');
    echo "[{$ahora}] Proceso de resolución automática completado:" . PHP_EOL;
    echo "  - Torneos sincronizados a en_curso: {$torneosSincronizados}" . PHP_EOL;
    echo "  - Enfrentamientos evaluados: {$reporte['evaluados']}" . PHP_EOL;
    echo "  - Entraron en período de gracia: {$reporte['entraron_en_gracia']}" . PHP_EOL;
    echo "  - Resueltos automáticamente: {$reporte['resueltos_automaticamente']}" . PHP_EOL;
    echo "  - Enviados a revisión manual: {$reporte['enviados_a_revision']}" . PHP_EOL;

    foreach ($reporte['detalles'] as $detalle) {
        $ganadorInfo = isset($detalle['ganador']) ? " -> Ganador: {$detalle['ganador']}" : "";
        echo "    * Enfrentamiento #{$detalle['id_enfrentamiento']}: {$detalle['accion']} [{$detalle['motivo']}]{$ganadorInfo}" . PHP_EOL;
    }

    exit(0);
} catch (Throwable $error) {
    fwrite(STDERR, '[' . date('Y-m-d H:i:s') . '] Error en procesamiento automático: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
