<?php

$config = [
    'host' => getenv('DB_HOST') ?: '',
    'username' => getenv('DB_USERNAME') ?: getenv('DB_USER') ?: '',
    'password' => getenv('DB_PASSWORD') ?: '',
    'database' => getenv('DB_DATABASE') ?: getenv('DB_NAME') ?: '',
    'port' => getenv('DB_PORT') ?: '3306',
];

$localConfigPath = __DIR__ . '/config.local.php';
if (is_readable($localConfigPath)) {
    $localConfig = require $localConfigPath;

    if (is_array($localConfig)) {
        foreach ($localConfig as $key => $value) {
            if (array_key_exists($key, $config) && $value !== null && $value !== '') {
                $config[$key] = $value;
            }
        }
    }
}

$missing = [];
foreach (['host', 'username', 'password', 'database'] as $requiredKey) {
    if ($config[$requiredKey] === '') {
        $missing[] = $requiredKey;
    }
}

if ($missing) {
    throw new RuntimeException(
        'Missing database configuration values: ' . implode(', ', $missing)
    );
}

$conn = @new mysqli(
    $config['host'],
    $config['username'],
    $config['password'],
    $config['database'],
    (int) $config['port']
);

if ($conn->connect_error) {
    throw new RuntimeException('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
?>
