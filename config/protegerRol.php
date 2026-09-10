<?php

require_once __DIR__ . '/proteger.php';

function rutaInicioSegunRoles(array $rolesUsuario): string
{
    return '/ArenaCJD/panel.php';
}

function exigirRol(string $rolRequerido): void
{
    $rolesUsuario = $_SESSION['roles'] ?? [];

    if (!in_array($rolRequerido, $rolesUsuario, true)) {
        header('Location: ' . rutaInicioSegunRoles($rolesUsuario));
        exit;
    }
}

function exigirUnoDeLosRoles(array $rolesPermitidos): void
{
    $rolesUsuario = $_SESSION['roles'] ?? [];

    foreach ($rolesPermitidos as $rol) {
        if (in_array($rol, $rolesUsuario, true)) {
            return;
        }
    }

    header('Location: ' . rutaInicioSegunRoles($rolesUsuario));
    exit;
}
