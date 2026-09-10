<?php

require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../modelos/Usuario.php';

function responderJson(array $datos, int $estado = 200): never
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function contextoApi(array $rolesPermitidos = []): array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['usuario_id'])) {
        responderJson(['exito' => false, 'mensaje' => 'No hay una sesión activa.'], 401);
    }

    try {
        $conexion = (new Conexion())->conectar();
        $modeloUsuario = new Usuario($conexion);
        $usuario = $modeloUsuario->buscarPorId((int) $_SESSION['usuario_id']);

        if (!$usuario || $usuario['estado'] !== 'activo') {
            $_SESSION = [];
            session_destroy();
            responderJson(['exito' => false, 'mensaje' => 'La sesión ya no es válida.'], 401);
        }

        $versionSesion = (int) ($usuario['version_sesion'] ?? 0);
        if (!isset($_SESSION['version_sesion']) || (int) $_SESSION['version_sesion'] !== $versionSesion) {
            $_SESSION = [];
            session_destroy();
            responderJson(['exito' => false, 'mensaje' => 'La sesión fue invalidada por un cambio de seguridad. Inicia sesión nuevamente.'], 401);
        }

        $roles = $usuario['roles'] ? explode(',', $usuario['roles']) : [];
        $_SESSION['roles'] = $roles;

        if ($rolesPermitidos) {
            $permitido = false;
            foreach ($rolesPermitidos as $rol) {
                if (in_array($rol, $roles, true)) {
                    $permitido = true;
                    break;
                }
            }
            if (!$permitido) {
                responderJson(['exito' => false, 'mensaje' => 'No tienes permisos para realizar esta acción.'], 403);
            }
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return [
            'conexion' => $conexion,
            'usuario' => $usuario,
            'roles' => $roles,
            'csrf_token' => $_SESSION['csrf_token']
        ];
    } catch (Throwable $error) {
        responderJson(['exito' => false, 'mensaje' => 'No se pudo validar la sesión.'], 500);
    }
}

function exigirMetodoApi(string $metodo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== $metodo) {
        responderJson(['exito' => false, 'mensaje' => 'Método no permitido.'], 405);
    }
}

function exigirCsrfApi(string $tokenSesion): void
{
    $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if ($tokenRecibido === '' || !hash_equals($tokenSesion, $tokenRecibido)) {
        responderJson(['exito' => false, 'mensaje' => 'Token de seguridad no válido.'], 403);
    }
}

function leerJsonApi(): array
{
    $datos = json_decode(file_get_contents('php://input'), true);
    if (!is_array($datos)) {
        responderJson(['exito' => false, 'mensaje' => 'Los datos enviados no son válidos.'], 400);
    }
    return $datos;
}


function fechaYmdValidaApi(string $fecha): ?DateTimeImmutable
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return null;
    }

    $fechaObj = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
    $errores = DateTimeImmutable::getLastErrors();
    if (!$fechaObj) {
        return null;
    }
    if (is_array($errores) && ($errores['warning_count'] > 0 || $errores['error_count'] > 0)) {
        return null;
    }
    if ($fechaObj->format('Y-m-d') !== $fecha) {
        return null;
    }

    return $fechaObj;
}


function horaHmValidaApi(string $hora): ?string
{
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hora)) {
        return null;
    }

    return $hora . ':00';
}

function registrarAuditoriaApi(PDO $conexion, ?int $idUsuario, string $accion, string $entidad, string|int|null $idEntidad = null, ?string $detalle = null, string $resultado = 'exito'): void
{
    if (!in_array($resultado, ['exito', 'error', 'denegado'], true)) {
        $resultado = 'error';
    }
    try {
        $consulta = $conexion->prepare(
            "INSERT INTO auditoria (id_usuario, accion, entidad, id_entidad, detalle, resultado)
             VALUES (:id_usuario, :accion, :entidad, :id_entidad, :detalle, :resultado)"
        );
        $consulta->execute([
            ':id_usuario' => $idUsuario,
            ':accion' => mb_substr($accion, 0, 80),
            ':entidad' => mb_substr($entidad, 0, 60),
            ':id_entidad' => $idEntidad === null ? null : mb_substr((string) $idEntidad, 0, 80),
            ':detalle' => $detalle === null ? null : mb_substr($detalle, 0, 500),
            ':resultado' => $resultado
        ]);
    } catch (Throwable $error) {
    }
}

function obtenerIpClienteApi(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
    $ip = preg_replace('/[^0-9a-fA-F:.]/', '', $ip) ?: 'desconocida';
    return substr($ip, 0, 45);
}

function registrarEventoSeguridadApi(string $evento, string $usuario = ''): void
{
    $evento = preg_replace('/[^A-Z0-9_]/', '_', strtoupper($evento)) ?: 'EVENTO_DESCONOCIDO';
    $usuario = preg_replace('/[\r\n\t]+/', ' ', trim($usuario)) ?? '';
    $usuario = substr($usuario, 0, 80);
    $ip = obtenerIpClienteApi();

    $rutaLog = getenv('ARENA_SECURITY_LOG') ?: dirname(__DIR__) . '/storage/logs/security.log';
    $directorio = dirname($rutaLog);

    if (!is_dir($directorio)) {
        @mkdir($directorio, 0750, true);
    }

    $linea = sprintf(
        "%s event=%s ip=%s user=%s%s",
        date(DATE_ATOM),
        $evento,
        $ip,
        $usuario !== '' ? $usuario : '-',
        PHP_EOL
    );

    @file_put_contents($rutaLog, $linea, FILE_APPEND | LOCK_EX);
}

