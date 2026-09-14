<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/_comun.php';
iniciarSesionArenaCJD();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responderJson(['exito' => false, 'mensaje' => 'Método no permitido.'], 405);
$datos = json_decode(file_get_contents('php://input'), true) ?: [];
$identificador = trim((string) ($datos['identificador'] ?? ''));
if (mb_strlen($identificador) < 3 || mb_strlen($identificador) > 150) responderJson(['exito' => false, 'mensaje' => 'Ingresa tu nombre, @usuario o correo.'], 422);

function iniciarRecuperacionSimulada(string $identificador): never
{
    $preguntas = [
        '¿Cuál era el nombre de tu primera mascota?',
        '¿Cuál es tu comida favorita?',
        '¿Cuál fue tu primer videojuego?',
        '¿Cuál es el apodo de un amigo de la infancia?',
        '¿Cuál es tu película favorita?'
    ];
    $indice = hexdec(substr(hash('sha256', mb_strtolower($identificador, 'UTF-8')), 0, 2)) % count($preguntas);
    $_SESSION['recuperacion_usuario_id'] = -1;
    $_SESSION['recuperacion_simulada'] = true;
    $_SESSION['recuperacion_expira'] = time() + 600;
    $_SESSION['recuperacion_token'] = bin2hex(random_bytes(32));
    $_SESSION['recuperacion_intentos'] = 0;
    responderJson([
        'exito' => true,
        'pregunta' => $preguntas[$indice],
        'token_proceso' => $_SESSION['recuperacion_token'],
        'expira_en' => 600,
        'mensaje' => 'Si la cuenta está activa y tiene recuperación configurada, podrás continuar con la respuesta correcta.'
    ]);
}

try {
    $conexion = (new Conexion())->conectar();
    $modelo = new Usuario($conexion);
    $usuario = $modelo->buscarRecuperacion($identificador);
    if (!$usuario || $usuario['estado'] !== 'activo' || empty($usuario['pregunta_recuperacion']) || empty($usuario['respuesta_recuperacion'])) {
        iniciarRecuperacionSimulada($identificador);
    }

    if (!empty($usuario['recuperacion_bloqueada_hasta']) && strtotime((string) $usuario['recuperacion_bloqueada_hasta']) > time()) {
        registrarAuditoriaApi($conexion, (int) $usuario['id_usuario'], 'recuperacion_bloqueada', 'usuario', (int) $usuario['id_usuario'], 'Intento durante bloqueo temporal', 'denegado');
        iniciarRecuperacionSimulada($identificador);
    }

    $_SESSION['recuperacion_usuario_id'] = (int) $usuario['id_usuario'];
    $_SESSION['recuperacion_simulada'] = false;
    $_SESSION['recuperacion_expira'] = time() + 600;
    $_SESSION['recuperacion_token'] = bin2hex(random_bytes(32));
    $_SESSION['recuperacion_intentos'] = (int) ($usuario['recuperacion_intentos'] ?? 0);
    responderJson(['exito' => true, 'pregunta' => $usuario['pregunta_recuperacion'], 'token_proceso' => $_SESSION['recuperacion_token'], 'expira_en' => 600, 'mensaje' => 'Si la cuenta está activa y tiene recuperación configurada, podrás continuar con la respuesta correcta.']);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo iniciar la recuperación.'], 500);
}
