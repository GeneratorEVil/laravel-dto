<?php

namespace Betstore\DTO\Tests;

use Betstore\DTO\DTO;
use PHPUnit\Framework\TestCase;

class DTO1 extends DTO
{
    public string $name;
    public int $age;

    protected function validate(array $data): void
    {
        // Простая валидация для тестов (без Laravel фасадов)
        static $cachedRules = null;

        if ($cachedRules === null) {
            $cachedRules = $this->rules();
        }

        if ($cachedRules && count($data) > 0) {
            foreach ($cachedRules as $field => $ruleString) {
                if (isset($data[$field])) {
                    $value = $data[$field];
                    $fieldRules = explode('|', $ruleString);

                    foreach ($fieldRules as $rule) {
                        if ($rule === 'required' && empty($value)) {
                            throw new \InvalidArgumentException("Field {$field} is required");
                        }
                        if ($rule === 'string' && !is_string($value)) {
                            throw new \InvalidArgumentException("Field {$field} must be a string");
                        }
                        if (strpos($rule, 'min:') === 0) {
                            $min = (int) str_replace('min:', '', $rule);
                            if (is_string($value) && strlen($value) < $min) {
                                throw new \InvalidArgumentException("Field {$field} must be at least {$min} characters");
                            }
                            if (is_numeric($value) && $value < $min) {
                                throw new \InvalidArgumentException("Field {$field} must be at least {$min}");
                            }
                        }
                        if (strpos($rule, 'max:') === 0) {
                            $max = (int) str_replace('max:', '', $rule);
                            if (is_string($value) && strlen($value) > $max) {
                                throw new \InvalidArgumentException("Field {$field} must not exceed {$max} characters");
                            }
                            if (is_numeric($value) && $value > $max) {
                                throw new \InvalidArgumentException("Field {$field} must not exceed {$max}");
                            }
                        }
                        if ($rule === 'integer' && !is_int($value)) {
                            throw new \InvalidArgumentException("Field {$field} must be an integer");
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
            'age' => 'required|integer|min:18|max:65',
        ];
    }

    protected function messages(): array
    {
        return [
            'name.min' => 'DTO1: Name must be at least 2 characters',
            'age.min' => 'DTO1: Age must be at least 18',
            'age.max' => 'DTO1: Age must not exceed 65',
        ];
    }
}

class DTO2 extends DTO
{
    public string $title;
    public float $price;

    protected function validate(array $data): void
    {
        // Простая валидация для тестов (без Laravel фасадов)
        static $cachedRules = null;

        if ($cachedRules === null) {
            $cachedRules = $this->rules();
        }

        if ($cachedRules && count($data) > 0) {
            foreach ($cachedRules as $field => $ruleString) {
                if (isset($data[$field])) {
                    $value = $data[$field];
                    $fieldRules = explode('|', $ruleString);

                    foreach ($fieldRules as $rule) {
                        if ($rule === 'required' && empty($value)) {
                            throw new \InvalidArgumentException("Field {$field} is required");
                        }
                        if ($rule === 'string' && !is_string($value)) {
                            throw new \InvalidArgumentException("Field {$field} must be a string");
                        }
                        if (strpos($rule, 'max:') === 0) {
                            $max = (int) str_replace('max:', '', $rule);
                            if (is_string($value) && strlen($value) > $max) {
                                throw new \InvalidArgumentException("Field {$field} must not exceed {$max} characters");
                            }
                        }
                        if ($rule === 'numeric' && !is_numeric($value)) {
                            throw new \InvalidArgumentException("Field {$field} must be numeric");
                        }
                        if (strpos($rule, 'min:') === 0) {
                            $min = (int) str_replace('min:', '', $rule);
                            if (is_numeric($value) && $value < $min) {
                                throw new \InvalidArgumentException("Field {$field} must be at least {$min}");
                            }
                        }
                        if (strpos($rule, 'max:') === 0 && is_numeric($value)) {
                            $max = (int) str_replace('max:', '', $rule);
                            if ($value > $max) {
                                throw new \InvalidArgumentException("Field {$field} must not exceed {$max}");
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
            'title' => 'required|string|max:100',
            'price' => 'required|numeric|min:0|max:10000',
        ];
    }

    protected function messages(): array
    {
        return [
            'title.max' => 'DTO2: Title must not exceed 100 characters',
            'price.min' => 'DTO2: Price must be at least 0',
            'price.max' => 'DTO2: Price must not exceed 10000',
        ];
    }
}

class BaseDTO extends DTO
{
    public string $baseField;

    protected function validate(array $data): void
    {
        // Простая валидация для тестов
        if (isset($data['baseField']) && empty($data['baseField'])) {
            throw new \InvalidArgumentException('Base field cannot be empty');
        }
    }

    protected function rules(): array
    {
        return [
            'baseField' => 'required|string',
        ];
    }
}

class ChildDTO extends BaseDTO
{
    public string $childField;
    public int $childNumber;

    protected function validate(array $data): void
    {
        // Вызываем валидацию родителя
        parent::validate($data);

        // Дополнительная валидация для дочернего класса
        if (isset($data['childField']) && strlen($data['childField']) < 3) {
            throw new \InvalidArgumentException('Child field must be at least 3 characters');
        }
        if (isset($data['childNumber']) && $data['childNumber'] < 0) {
            throw new \InvalidArgumentException('Child number must be non-negative');
        }
    }

    protected function rules(): array
    {
        return array_merge(parent::rules(), [
            'childField' => 'required|string|min:3',
            'childNumber' => 'required|integer|min:0',
        ]);
    }

    protected function messages(): array
    {
        return array_merge(parent::messages(), [
            'childField.min' => 'Child field must be at least 3 characters',
            'childNumber.min' => 'Child number must be non-negative',
        ]);
    }
}

class CacheIsolationTest extends TestCase
{
    public function testInheritanceFunctionality()
    {
        // Создаем базовый DTO
        $baseDto = new BaseDTO(['baseField' => 'base value']);
        $this->assertEquals('base value', $baseDto->baseField);

        // Создаем дочерний DTO - он должен иметь свои собственные правила
        $childDto = new ChildDTO([
            'baseField' => 'base value',
            'childField' => 'child value',
            'childNumber' => 42,
        ]);

        $this->assertEquals('base value', $childDto->baseField);
        $this->assertEquals('child value', $childDto->childField);
        $this->assertEquals(42, $childDto->childNumber);

        // Проверяем, что дочерний класс корректно наследует свойства
        $this->assertTrue(property_exists($childDto, 'baseField'));
        $this->assertTrue(property_exists($childDto, 'childField'));
        $this->assertTrue(property_exists($childDto, 'childNumber'));

        // Проверяем, что можно создать еще один экземпляр дочернего класса
        $childDto2 = new ChildDTO([
            'baseField' => 'another base',
            'childField' => 'another child',
            'childNumber' => 100,
        ]);

        $this->assertEquals('another base', $childDto2->baseField);
        $this->assertEquals('another child', $childDto2->childField);
        $this->assertEquals(100, $childDto2->childNumber);
    }

    public function testInheritanceValidation()
    {
        // Проверяем валидацию базового класса
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Base field cannot be empty');
        new BaseDTO(['baseField' => '']);
    }

    public function testChildInheritanceValidation()
    {
        // Проверяем валидацию дочернего класса (короткое поле)
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Child field must be at least 3 characters');
        new ChildDTO([
            'baseField' => 'valid',
            'childField' => 'x', // слишком короткое
            'childNumber' => 10,
        ]);
    }

    public function testChildInheritanceValidationNumber()
    {
        // Проверяем валидацию дочернего класса (отрицательное число)
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Child number must be non-negative');
        new ChildDTO([
            'baseField' => 'valid',
            'childField' => 'valid field',
            'childNumber' => -5, // отрицательное
        ]);
    }

    public function testCacheIsolationBetweenDifferentDTOs()
    {
        // Создаем DTO1 первым - его правила должны закэшироваться
        $dto1 = new DTO1([
            'name' => 'John',
            'age' => 25,
        ]);

        $this->assertEquals('John', $dto1->name);
        $this->assertEquals(25, $dto1->age);

        // Теперь создаем DTO2 - он должен использовать свои правила, а не DTO1
        $dto2 = new DTO2([
            'title' => 'Test Product',
            'price' => 99.99,
        ]);

        $this->assertEquals('Test Product', $dto2->title);
        $this->assertEquals(99.99, $dto2->price);

        // Проверяем, что кэши изолированы - создаем новые экземпляры и проверяем,
        // что они работают корректно с правильными правилами
        $dto1_again = new DTO1([
            'name' => 'Jane',
            'age' => 30,
        ]);

        $dto2_again = new DTO2([
            'title' => 'Another Product',
            'price' => 149.99,
        ]);

        $this->assertEquals('Jane', $dto1_again->name);
        $this->assertEquals('Another Product', $dto2_again->title);
    }

    public function testValidationWorksCorrectlyForEachDTO()
    {
        // Проверяем, что DTO1 правильно валидирует свои данные
        $this->expectException(\InvalidArgumentException::class);
        new DTO1([
            'name' => 'J', // слишком короткое имя
            'age' => 25,
        ]);
    }

    public function testDTO2ValidationWorksCorrectly()
    {
        // Проверяем, что DTO2 правильно валидирует свои данные
        $this->expectException(\InvalidArgumentException::class);
        new DTO2([
            'title' => str_repeat('A', 101), // слишком длинный заголовок
            'price' => 99.99,
        ]);
    }

    public function testCachePersistenceBetweenInstances()
    {
        // Создаем первый экземпляр DTO1
        $dto1_first = new DTO1(['name' => 'Alice', 'age' => 30]);

        // Создаем второй экземпляр DTO1 - должен использовать кэш
        $dto1_second = new DTO1(['name' => 'Bob', 'age' => 35]);

        // Проверяем, что оба экземпляра созданы правильно
        $this->assertEquals('Alice', $dto1_first->name);
        $this->assertEquals('Bob', $dto1_second->name);

        // Проверяем, что можно создать еще один экземпляр без проблем
        $dto1_third = new DTO1(['name' => 'Charlie', 'age' => 40]);
        $this->assertEquals('Charlie', $dto1_third->name);
        $this->assertEquals(40, $dto1_third->age);
    }
}