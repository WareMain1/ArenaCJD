<?php
$htmlFiles = glob(__DIR__ . '/../*.php');
$responsiveReport = [];

foreach ($htmlFiles as $f) {
    $content = file_get_contents($f);
    $hasViewport = (strpos($content, 'viewport') !== false);
    $basename = basename($f);
    $responsiveReport[$basename] = [
        'has_viewport' => $hasViewport
    ];
}

$cssFiles = glob(__DIR__ . '/../css/**/*.css');
$cssFiles = array_merge($cssFiles, glob(__DIR__ . '/../css/*.css'));
$mediaQueriesCount = 0;
$cssBreakpoints = [];

foreach ($cssFiles as $cf) {
    $content = file_get_contents($cf);
    if (preg_match_all('/@media[^{]+{/', $content, $matches)) {
        $mediaQueriesCount += count($matches[0]);
        foreach ($matches[0] as $m) {
            $cssBreakpoints[] = trim($m);
        }
    }
}

echo "REPORTE RESPONSIVE:\n";
echo "Archivos PHP analizados: " . count($responsiveReport) . "\n";
$sinViewport = array_filter($responsiveReport, fn($r) => !$r['has_viewport']);
echo "Archivos PHP sin viewport tag: " . count($sinViewport) . " (" . implode(', ', array_keys($sinViewport)) . ")\n";
echo "Total de @media queries en CSS: " . $mediaQueriesCount . "\n";
echo "Muestra de Breakpoints encontrados:\n";
print_r(array_unique(array_slice($cssBreakpoints, 0, 10)));
