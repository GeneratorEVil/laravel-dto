<?php

namespace Betstore\DTO\Tests;

use Betstore\DTO\DTO;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Collection;

class LargeDataDTO extends DTO
{
    public string $name;
    public array $largeArray;
    public Collection $largeCollection;
    public array $nestedDTOs;

    protected function casts(): array
    {
        return [
            'largeCollection' => ['collection', SimpleDTO::class],
            'nestedDTOs' => ['array', SimpleDTO::class],
        ];
    }
}

class PerformanceDTOTest extends TestCase
{
    public function testPerformanceWithLargeDataWithMeasurement()
    {
        $startTime = microtime(true);

        // Создаем большой массив данных
        $largeArray = range(1, 1000);

        // Создаем большую коллекцию DTO
        $largeCollectionData = [];
        for ($i = 0; $i < 100; $i++) {
            $largeCollectionData[] = [
                'name' => "User {$i}",
                'age' => rand(18, 65),
                'email' => "user{$i}@example.com",
            ];
        }

        // Создаем массив вложенных DTO
        $nestedDTOsData = [];
        for ($i = 0; $i < 50; $i++) {
            $nestedDTOsData[] = [
                'name' => "Nested User {$i}",
                'age' => rand(20, 60),
                'email' => "nested{$i}@example.com",
            ];
        }

        $data = [
            'name' => 'Large Data Test',
            'largeArray' => $largeArray,
            'largeCollection' => $largeCollectionData,
            'nestedDTOs' => $nestedDTOsData,
        ];

        $dto = new LargeDataDTO($data);

        // Проверяем корректность создания
        $this->assertInstanceOf(LargeDataDTO::class, $dto);
        $this->assertEquals('Large Data Test', $dto->name);
        $this->assertCount(1000, $dto->largeArray);
        $this->assertInstanceOf(Collection::class, $dto->largeCollection);
        $this->assertCount(100, $dto->largeCollection);
        $this->assertCount(50, $dto->nestedDTOs);

        // Проверяем что все элементы в коллекции являются DTO
        foreach ($dto->largeCollection as $item) {
            $this->assertInstanceOf(SimpleDTO::class, $item);
        }

        // Проверяем что все элементы в массиве являются DTO
        foreach ($dto->nestedDTOs as $item) {
            $this->assertInstanceOf(SimpleDTO::class, $item);
        }

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->addToAssertionCount(1);
        echo "\nLarge data performance test execution time: " . number_format($executionTime, 4) . " ms\n";

        // Проверяем что время выполнения разумное (менее 500мс для 1000+ элементов)
        $this->assertLessThan(500, $executionTime, 'Performance test should complete in less than 500ms');
    }

    public function testSerializationPerformanceWithLargeDataWithMeasurement()
    {
        $startTime = microtime(true);

        // Создаем большой DTO для тестирования сериализации
        $largeCollectionData = [];
        for ($i = 0; $i < 200; $i++) {
            $largeCollectionData[] = [
                'name' => "User {$i}",
                'age' => rand(18, 65),
                'email' => "user{$i}@example.com",
            ];
        }

        $data = [
            'name' => 'Serialization Test',
            'largeArray' => range(1, 500),
            'largeCollection' => $largeCollectionData,
            'nestedDTOs' => [],
        ];

        $dto = new LargeDataDTO($data);

        // Тестируем сериализацию в массив
        $arrayStart = microtime(true);
        $arrayResult = $dto->toArray();
        $arrayEnd = microtime(true);
        $arrayTime = ($arrayEnd - $arrayStart) * 1000;

        // Тестируем сериализацию в JSON
        $jsonStart = microtime(true);
        $jsonResult = $dto->toJson();
        $jsonEnd = microtime(true);
        $jsonTime = ($jsonEnd - $jsonStart) * 1000;

        // Проверяем результаты
        $this->assertIsArray($arrayResult);
        $this->assertIsString($jsonResult);
        $this->assertJson($jsonResult);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->addToAssertionCount(1);
        echo "\nSerialization performance test execution time: " . number_format($executionTime, 4) . " ms\n";
        echo "\n  - Array serialization time: " . number_format($arrayTime, 4) . " ms\n";
        echo "\n  - JSON serialization time: " . number_format($jsonTime, 4) . " ms\n";

        // Проверяем производительность сериализации
        $this->assertLessThan(200, $arrayTime, 'Array serialization should complete in less than 200ms');
        $this->assertLessThan(300, $jsonTime, 'JSON serialization should complete in less than 300ms');
    }

    public function testMemoryUsageWithLargeData()
    {
        $startMemory = memory_get_usage(true);

        // Создаем очень большой DTO
        $hugeArray = range(1, 5000);
        $hugeCollectionData = [];
        for ($i = 0; $i < 500; $i++) {
            $hugeCollectionData[] = [
                'name' => "User {$i}",
                'age' => rand(18, 65),
                'email' => "user{$i}@example.com",
            ];
        }

        $data = [
            'name' => 'Memory Test',
            'largeArray' => $hugeArray,
            'largeCollection' => $hugeCollectionData,
            'nestedDTOs' => [],
        ];

        $dto = new LargeDataDTO($data);

        $endMemory = memory_get_usage(true);
        $memoryUsage = ($endMemory - $startMemory) / 1024 / 1024; // в МБ

        $this->assertInstanceOf(LargeDataDTO::class, $dto);

        echo "\nMemory usage for large data test: " . number_format($memoryUsage, 2) . " MB\n";

        // Проверяем что использование памяти разумное (менее 50MB)
        $this->assertLessThan(50, $memoryUsage, 'Memory usage should be less than 50MB for large data');
    }
}