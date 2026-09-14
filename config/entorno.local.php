<?php











$arenaValoresLocales = [
    'ARENA_DB_HOST' => 'localhost',
    'ARENA_DB_PORT' => '3306',
    'ARENA_DB_NAME' => 'arenacjd',
    'ARENA_DB_USER' => 'arenacjd_app',
    'ARENA_DB_PASS' => 'Maracaibo24158$',
    'ARENA_TIMEZONE' => 'America/Montevideo',
];

foreach ($arenaValoresLocales as $arenaNombre => $arenaValor) {
    $arenaActual = $_ENV[$arenaNombre] ?? $_SERVER[$arenaNombre] ?? getenv($arenaNombre);

    if ($arenaActual === false || $arenaActual === null || trim((string) $arenaActual) === '') {
        putenv($arenaNombre . '=' . $arenaValor);
        $_ENV[$arenaNombre] = $arenaValor;
        $_SERVER[$arenaNombre] = $arenaValor;
    }
}

unset($arenaValoresLocales, $arenaNombre, $arenaValor, $arenaActual);
