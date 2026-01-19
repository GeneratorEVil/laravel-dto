<?php

echo "=== PHP JIT Compatibility Test ===\n\n";

// Проверяем текущий статус
echo "Current PHP Configuration:\n";
echo "- PHP Version: " . PHP_VERSION . "\n";
echo "- OPcache Extension: " . (extension_loaded('opcache') ? 'Loaded' : 'Not loaded') . "\n";
echo "- Xdebug Extension: " . (extension_loaded('xdebug') ? 'Loaded' : 'Not loaded') . "\n";

// Проверяем статус OPcache
$opcacheStatus = opcache_get_status(false);
if ($opcacheStatus) {
    echo "- OPcache Enabled: " . ($opcacheStatus['opcache_enabled'] ? 'Yes' : 'No') . "\n";
    echo "- JIT Enabled: " . ($opcacheStatus['jit']['enabled'] ?? 'N/A') . "\n";
    echo "- JIT Kind: " . ($opcacheStatus['jit']['kind'] ?? 'N/A') . "\n";
    echo "- JIT Buffer Size: " . ($opcacheStatus['jit']['buffer_size'] ?? 0) . " bytes\n";
} else {
    echo "- OPcache Status: Not available\n";
}

echo "\n=== Performance Test ===\n";

// Создаем простой тест производительности
$iterations = 100000;

echo "Running $iterations iterations of simple operations...\n";

$start = microtime(true);

// Простой цикл с арифметическими операциями
$result = 0;
for ($i = 0; $i < $iterations; $i++) {
    $result += $i * 2;
    $result -= $i;
    $result *= 1.1;
    $result /= 1.1;
}

$end = microtime(true);
$time = ($end - $start) * 1000;

echo "Result: $result\n";
echo "Time: " . number_format($time, 2) . " ms\n";
echo "Operations per second: " . number_format($iterations / ($time / 1000), 0) . "\n";

echo "\n=== Recommendations ===\n";

if (extension_loaded('xdebug')) {
    echo "❌ Xdebug is loaded - JIT is disabled due to compatibility issues\n";
    echo "To enable JIT, temporarily disable Xdebug:\n";
    echo "1. sudo phpdismod -s cli xdebug\n";
    echo "2. Restart your web server if needed\n";
    echo "3. Or use: php -d 'zend_extension=xdebug.so' -d 'opcache.jit=off' script.php\n";
}

if (isset($opcacheStatus['jit']['enabled']) && $opcacheStatus['jit']['enabled']) {
    echo "✅ JIT is enabled and working!\n";
} else {
    echo "⚠️  JIT is not enabled\n";
    echo "To enable JIT, add to your php.ini or opcache.ini:\n";
    echo "opcache.jit=on\n";
    echo "opcache.jit_buffer_size=100M\n";
}

echo "\nFor development with debugging, keep Xdebug enabled.\n";
echo "For production performance, disable Xdebug and enable JIT.\n";

?>