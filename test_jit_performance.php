<?php

// Тест производительности DTO с включенным JIT
// Этот скрипт должен запускаться без Xdebug

require_once __DIR__ . '/vendor/autoload.php';

use Betstore\DTO\DTO;

class TestDTO extends DTO
{
    public string $name;
    public int $age;
    public ?string $email;
    public string|int $unionField;
    public array $items;

    protected function validate(array $data): void
    {
        // Простая валидация без фасадов
        if (isset($data['name']) && strlen($data['name']) < 2) {
            throw new InvalidArgumentException('Name too short');
        }
        if (isset($data['age']) && ($data['age'] < 18 || $data['age'] > 120)) {
            throw new InvalidArgumentException('Invalid age');
        }
    }
}

echo "=== DTO Performance Test with JIT ===\n\n";

echo "JIT Status: " . (opcache_get_status()['jit']['enabled'] ?? 'Unknown') . "\n";
echo "Xdebug: " . (extension_loaded('xdebug') ? 'Enabled' : 'Disabled') . "\n\n";

// Тест 1: Создание 100 DTO
echo "Test 1: Creating 100 DTOs\n";
$data = [
    'name' => 'Test User',
    'age' => 25,
    'email' => 'test@example.com',
    'unionField' => 'test_value',
    'items' => [1, 2, 3],
];

$start = microtime(true);
$dtos = [];
for ($i = 0; $i < 100; $i++) {
    $dtos[] = new TestDTO($data);
}
$end = microtime(true);
$time1 = ($end - $start) * 1000;

echo "Time: " . number_format($time1, 4) . " ms\n";
echo "Average: " . number_format($time1 / 100, 4) . " ms per DTO\n\n";

// Тест 2: Сериализация 1000 раз
echo "Test 2: Serializing 1000 times\n";
$dto = new TestDTO($data);

$start = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    $array = $dto->toArray();
    $json = $dto->toJson();
}
$end = microtime(true);
$time2 = ($end - $start) * 1000;

echo "Time: " . number_format($time2, 4) . " ms\n";
echo "Average: " . number_format($time2 / 1000, 4) . " ms per serialization\n\n";

// Тест 3: Память
echo "Test 3: Memory usage\n";
$startMemory = memory_get_usage(true);

$bigDtos = [];
for ($i = 0; $i < 50; $i++) {
    $bigDtos[] = new TestDTO(array_merge($data, ['items' => range(1, 1000)]));
}

$endMemory = memory_get_usage(true);
$memoryUsage = ($endMemory - $startMemory) / 1024 / 1024;

echo "Memory used: " . number_format($memoryUsage, 2) . " MB\n\n";

echo "=== Results Summary ===\n";
echo "DTO Creation (100): " . number_format($time1, 2) . " ms (" . number_format($time1/100, 4) . " ms avg)\n";
echo "Serialization (1000): " . number_format($time2, 2) . " ms (" . number_format($time2/1000, 4) . " ms avg)\n";
echo "Memory Usage (50 DTOs): " . number_format($memoryUsage, 2) . " MB\n";

?>