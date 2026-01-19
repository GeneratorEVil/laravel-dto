<?php

namespace Betstore\DTO\Tests;

use Betstore\DTO\DTO;
use PHPUnit\Framework\TestCase;
use Illuminate\Validation\ValidationException;

class ValidationDTO extends DTO
{
    public string $name;
    public int $age;
    public string $email;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:50',
            'age' => 'required|integer|min:18|max:120',
            'email' => 'required|email',
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Имя обязательно для заполнения',
            'name.min' => 'Имя должно содержать минимум :min символов',
            'age.min' => 'Возраст должен быть не менее :min лет',
            'email.email' => 'Некорректный формат email',
        ];
    }

    protected function validate(array $data): void
    {
        // Для тестирования без Laravel фасадов используем простую валидацию
        $rules = $this->rules();

        if ($rules && count($data) > 0) {
            // Простая валидация без Laravel Validator
            foreach ($rules as $field => $ruleString) {
                if (isset($data[$field])) {
                    $value = $data[$field];
                    $fieldRules = explode('|', $ruleString);

                    foreach ($fieldRules as $rule) {
                        if ($rule === 'required' && empty($value)) {
                            throw new \InvalidArgumentException("Field {$field} is required");
                        }
                        if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            throw new \InvalidArgumentException("Field {$field} must be a valid email");
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
                        }
                    }
                }
            }
        }
    }
}

class ValidationDTOTest extends TestCase
{
    public function testValidationWithPerformanceMeasurement()
    {
        $startTime = microtime(true);

        // Тест успешной валидации
        $validData = [
            'name' => 'John Doe',
            'age' => 25,
            'email' => 'john@example.com',
        ];

        $dto = new ValidationDTO($validData);
        $this->assertInstanceOf(ValidationDTO::class, $dto);
        $this->assertEquals('John Doe', $dto->name);
        $this->assertEquals(25, $dto->age);
        $this->assertEquals('john@example.com', $dto->email);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->addToAssertionCount(1);
        echo "\nValidation success test execution time: " . number_format($executionTime, 4) . " ms\n";
    }

    public function testValidationFailureWithPerformanceMeasurement()
    {
        $startTime = microtime(true);

        // Тест неуспешной валидации - короткое имя
        $invalidData1 = [
            'name' => 'J', // слишком короткое имя
            'age' => 25,
            'email' => 'john@example.com',
        ];

        $this->expectException(\InvalidArgumentException::class);
        new ValidationDTO($invalidData1);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        echo "\nValidation failure test (short name) execution time: " . number_format($executionTime, 4) . " ms\n";
    }

    public function testValidationFailureAgeWithPerformanceMeasurement()
    {
        $startTime = microtime(true);

        // Тест неуспешной валидации - возраст слишком маленький
        $invalidData2 = [
            'name' => 'John Doe',
            'age' => 16, // слишком молодой
            'email' => 'john@example.com',
        ];

        $this->expectException(\InvalidArgumentException::class);
        new ValidationDTO($invalidData2);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        echo "\nValidation failure test (age too young) execution time: " . number_format($executionTime, 4) . " ms\n";
    }

    public function testValidationFailureEmailWithPerformanceMeasurement()
    {
        $startTime = microtime(true);

        // Тест неуспешной валидации - некорректный email
        $invalidData3 = [
            'name' => 'John Doe',
            'age' => 25,
            'email' => 'invalid-email', // некорректный email
        ];

        $this->expectException(\InvalidArgumentException::class);
        new ValidationDTO($invalidData3);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        echo "\nValidation failure test (invalid email) execution time: " . number_format($executionTime, 4) . " ms\n";
    }
}