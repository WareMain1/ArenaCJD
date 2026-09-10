<?php
putenv('ARENA_DB_PASS=Maracaibo24158$');
require_once __DIR__ . '/../config/Conexion.php';
$db = (new Conexion())->conectar();

echo "USUARIOS Y ROLES:\n";
$users = $db->query('SELECT u.id_usuario, u.nombre_usuario, u.estado, GROUP_CONCAT(r.nombre) as roles FROM usuarios u LEFT JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario LEFT JOIN roles r ON r.id_rol = ur.id_rol GROUP BY u.id_usuario')->fetchAll(PDO::FETCH_ASSOC);
print_r($users);
