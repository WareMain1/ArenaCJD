<?php
putenv('ARENA_DB_PASS=Maracaibo24158$');
require_once __DIR__ . '/../config/Conexion.php';
$db = (new Conexion())->conectar();

// Check recent matches in database
$matches = $db->query("SELECT e.id_enfrentamiento, e.id_torneo, t.nombre as torneo, tt.nombre as tipo_torneo, e.id_usuario_a, e.id_usuario_b, e.estado, e.ronda FROM enfrentamientos e JOIN torneos t ON t.id_torneo = e.id_torneo JOIN tipos_torneo tt ON tt.id_tipo_torneo = t.id_tipo_torneo ORDER BY e.id_enfrentamiento DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

print_r($matches);
