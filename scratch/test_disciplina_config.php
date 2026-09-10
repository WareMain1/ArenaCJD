<?php
$baseUrl = 'http://localhost/ArenaCJD';
$cookieFile = __DIR__ . '/eval_cookies/admin.txt';

// Login as admin
$loginRes = json_decode(file_get_contents("{$baseUrl}/api/sesion.php", false, stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Cookie: " . file_get_contents($cookieFile)
    ]
])), true);

echo "Admin session test: \n";
print_r($loginRes);
