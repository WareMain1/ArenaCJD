<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/_comun.php';
require_once __DIR__ . '/../modelos/Inscripcion.php';

$contexto = contextoApi();
$nombreUsuario = strtolower(ltrim(trim((string) ($_GET['usuario'] ?? '')), '@'));

if (!preg_match('/^[a-z0-9._-]{4,24}$/', $nombreUsuario)) {
    responderJson(['exito' => false, 'mensaje' => 'El @usuario no tiene un formato válido.'], 400);
}

try {
    $modelo = new Inscripcion($contexto['conexion']);
    $usuario = $modelo->usuarioActivoPorNombre($nombreUsuario);
    if (!$usuario) {
        responderJson(['exito' => false, 'mensaje' => 'El usuario no existe, no está activo o no está disponible para participar.'], 404);
    }
    responderJson([
        'exito' => true,
        'usuario' => [
            'id' => (int) $usuario['id_usuario'],
            'nombre' => $usuario['nombre_completo'],
            'nombre_usuario' => $usuario['nombre_usuario']
        ]
    ]);
} catch (Throwable $error) {
    responderJson(['exito' => false, 'mensaje' => 'No se pudo verificar el usuario.'], 500);
}
