<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/_comun.php';

exigirMetodoApi('POST');
$contexto = contextoApi();
exigirCsrfApi($contexto['csrf_token']);

$datos = leerJsonApi();
$nombreUsuario = strtolower(trim((string) ($datos['nombre_usuario'] ?? '')));
$nombreUsuario = ltrim($nombreUsuario, '@');

if (!preg_match('/^[a-z0-9._-]{4,24}$/', $nombreUsuario)) {
    responderJson([
        'exito' => false,
        'mensaje' => 'Usa entre 4 y 24 caracteres: letras, números, punto, guion o guion bajo.'
    ], 400);
}

try {
    $conexion = $contexto['conexion'];
    $modeloUsuario = new Usuario($conexion);
    $idUsuario = (int) $contexto['usuario']['id_usuario'];
    $usuarioActual = $contexto['usuario'];

    if ($usuarioActual['nombre_usuario'] === $nombreUsuario) {
        responderJson([
            'exito' => true,
            'mensaje' => 'No había cambios para guardar.',
            'nombre_usuario' => $nombreUsuario
        ]);
    }

    if (!$modeloUsuario->nombreUsuarioDisponible($nombreUsuario, $idUsuario)) {
        responderJson([
            'exito' => false,
            'mensaje' => 'Ese nombre de usuario ya está en uso.'
        ], 409);
    }

    $conexion->beginTransaction();
    try {
        $actualizado = $modeloUsuario->actualizarNombreUsuario($idUsuario, $nombreUsuario);
    } catch (PDOException $error) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        if ($error->getCode() === '23000') {
            responderJson([
                'exito' => false,
                'mensaje' => 'Ese nombre de usuario acaba de ser utilizado por otra cuenta.'
            ], 409);
        }
        throw $error;
    }

    if (!$actualizado) {
        $conexion->rollBack();
        responderJson([
            'exito' => false,
            'mensaje' => 'No se pudo actualizar el nombre de usuario.'
        ], 400);
    }

    registrarAuditoriaApi($conexion, $idUsuario, 'nombre_usuario_actualizado', 'usuario', $idUsuario, '@' . $nombreUsuario);
    $conexion->commit();
    $_SESSION['nombre_usuario'] = $nombreUsuario;

    responderJson([
        'exito' => true,
        'mensaje' => 'Nombre de usuario actualizado correctamente.',
        'nombre_usuario' => $nombreUsuario
    ]);
} catch (Throwable $error) {
    if (isset($conexion) && $conexion instanceof PDO && $conexion->inTransaction()) {
        $conexion->rollBack();
    }
    responderJson([
        'exito' => false,
        'mensaje' => 'No se pudo actualizar el nombre de usuario.'
    ], 500);
}
