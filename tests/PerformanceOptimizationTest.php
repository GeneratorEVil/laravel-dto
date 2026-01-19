<?php

namespace Betstore\DTO\Tests;

use Betstore\DTO\DTO;
use PHPUnit\Framework\TestCase;

class OptimizedTestDTO extends DTO
{
    public string $name;
    public int $age;
    public ?string $email;
    public string|int $unionField;
    public array $items;

    protected function validate(array $data): void
    {
        // Для тестирования производительности отключаем валидацию
        // или используем простую валидацию без фасадов
        static $cachedRules = null;

        if ($cachedRules === null) {
            $cachedRules = $this->rules();
        }

        if ($cachedRules && count($data) > 0) {
            // Простая валидация без Laravel Validator
            foreach ($cachedRules as $field => $ruleString) {
                if (isset($data[$field])) {
                    $value = $data[$field];
                    $fieldRules = explode('|', $ruleString);

                    foreach ($fieldRules as $rule) {
                        if ($rule === 'required' && empty($value)) {
                            throw new \InvalidArgumentException("Field {$field} is required");
                        }
                        if (strpos($rule, 'min:') === 0) {
                            $min = (int) str_replace('min:', '', $rule);
                            if (is_string($value) && strlen($value) < $min) {
                                throw new \InvalidArgumentException("Field {$field} must be at least {$min} characters");
                            }
                        }
                    }
                }
            }
        }
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:2',
            'age' => 'required|integer|min:18',
            'email' => 'nullable|email',
        ];
    }
}

class PerformanceOptimizationTest extends TestCase
{
    public function testReflectionCachingPerformance()
    {
        $startTime = microtime(true);

        // Создаем много DTO для тестирования кэширования reflection
        $data = [
            'name' => 'Test User',
            'age' => 25,
            'email' => 'test@example.com',
            'unionField' => 'test_value',
            'items' => [1, 2, 3],
        ];

        $dtos = [];
        for ($i = 0; $i < 100; $i++) {
            $dtos[] = new OptimizedTestDTO($data);
        }

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->assertCount(100, $dtos);
        $this->assertInstanceOf(OptimizedTestDTO::class, $dtos[0]);

        echo "\nReflection caching performance test (100 DTOs): " . number_format($executionTime, 4) . " ms\n";
        echo "Average time per DTO: " . number_format($executionTime / 100, 4) . " ms\n";

        // Проверяем что время разумное (менее 50мс для 100 DTO)
        $this->assertLessThan(50, $executionTime);
    }

    public function testSerializationPerformanceComparison()
    {
        $data = [
            'name' => 'Test User',
            'age' => 25,
            'email' => 'test@example.com',
            'unionField' => 'test_value',
            'items' => range(1, 100), // большой массив
        ];

        $dto = new OptimizedTestDTO($data);

        // Тестируем сериализацию
        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $array = $dto->toArray();
            $json = $dto->toJson();
        }
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->assertIsArray($array);
        $this->assertIsString($json);
        $this->assertJson($json);

        echo "\nSerialization performance test (1000 iterations): " . number_format($executionTime, 4) . " ms\n";
        echo "Average time per serialization: " . number_format($executionTime / 1000, 4) . " ms\n";

        // Проверяем что время разумное (менее 1000мс для 1000 сериализаций с большими данными)
        $this->assertLessThan(1000, $executionTime);
    }

    public function testMemoryEfficiency()
    {
        $startMemory = memory_get_usage(true);

        $data = [
            'name' => 'Test User',
            'age' => 25,
            'email' => 'test@example.com',
            'unionField' => 'test_value',
            'items' => range(1, 1000), // большой массив
        ];

        // Создаем много DTO
        $dtos = [];
        for ($i = 0; $i < 50; $i++) {
            $dtos[] = new OptimizedTestDTO($data);
        }

        $endMemory = memory_get_usage(true);
        $memoryUsage = ($endMemory - $startMemory) / 1024 / 1024; // в МБ

        echo "\nMemory usage for 50 DTOs with large arrays: " . number_format($memoryUsage, 2) . " MB\n";

        // Проверяем что использование памяти разумное (менее 10MB для 50 DTO с большими массивами)
        $this->assertLessThan(10, $memoryUsage);
    }
}