<?php
$pvars = array_filter($_ENV, fn($k) => preg_match('/^P\d+$/', $k), ARRAY_FILTER_USE_KEY);
echo 'P vars in $_ENV: ' . count($pvars) . PHP_EOL;
echo '$_ENV total count: ' . count($_ENV) . PHP_EOL;

$env = $_ENV;
foreach ($env as $key => $value) {
    $size = strlen($key) + 1 + strlen((string)$value) + 1;
    if ($size > 2048) {
        echo "OVERSIZED: $key = {$size} bytes" . PHP_EOL;
    }
}

// Simulate what getDefaultEnv does
$defaultEnv = getenv();
echo 'getenv() count: ' . count($defaultEnv) . PHP_EOL;
$pInGetenv = array_filter($defaultEnv, fn($k) => preg_match('/^P\d+$/', $k), ARRAY_FILTER_USE_KEY);
echo 'P vars in getenv(): ' . count($pInGetenv) . PHP_EOL;

$totalSize = 0;
foreach ($defaultEnv as $k => $v) {
    $totalSize += strlen($k) + 1 + strlen((string)$v) + 1;
}
echo 'getenv() total env block size: ' . $totalSize . PHP_EOL;

$totalEnvSize = 0;
foreach ($_ENV as $k => $v) {
    $totalEnvSize += strlen($k) + 1 + strlen((string)$v) + 1;
}
echo '_ENV total env block size: ' . $totalEnvSize . PHP_EOL;
