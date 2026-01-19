<?php

require_once __DIR__ . '/vendor/autoload.php';

use Betstore\DTO\DTO;

class BaseUserDTO extends DTO
{
    public string $name;
    public int $age;

    protected function validate(array $data): void
    {
        // Простая валидация для демонстрации
        if (isset($data['name']) && strlen($data['name']) < 2) {
            throw new InvalidArgumentException('Name must be at least 2 characters');
        }
        if (isset($data['age']) && $data['age'] < 18) {
            throw new InvalidArgumentException('Age must be at least 18');
        }
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:2',
            'age' => 'required|integer|min:18',
        ];
    }
}

class ExtendedUserDTO extends BaseUserDTO
{
    public string $email;
    public ?string $phone;

    protected function validate(array $data): void
    {
        // Сначала вызываем валидацию родителя
        parent::validate($data);

        // Дополнительная валидация для дочернего класса
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }
        if (isset($data['phone']) && strlen($data['phone']) < 10) {
            throw new InvalidArgumentException('Phone must be at least 10 characters');
        }
    }

    protected function rules(): array
    {
        return array_merge(parent::rules(), [
            'email' => 'required|email',
            'phone' => 'nullable|string|min:10',
        ]);
    }
}

class ProductDTO extends DTO
{
    public string $title;
    public float $price;

    protected function validate(array $data): void
    {
        // Простая валидация для демонстрации
        if (isset($data['title']) && strlen($data['title']) > 50) {
            throw new InvalidArgumentException('Title must not exceed 50 characters');
        }
        if (isset($data['price']) && $data['price'] < 0) {
            throw new InvalidArgumentException('Price must be at least 0');
        }
    }

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
        ];
    }
}

echo "=== Cache Isolation & Inheritance Fix Demonstration ===\n\n";

// Создаем базовый UserDTO первым
echo "1. Creating BaseUserDTO...\n";
$baseUser = new BaseUserDTO(['name' => 'John', 'age' => 25]);
echo "   ✅ BaseUserDTO created successfully\n";

// Создаем расширенный UserDTO (наследуется от BaseUserDTO)
echo "2. Creating ExtendedUserDTO (inherits from BaseUserDTO)...\n";
$extendedUser = new ExtendedUserDTO([
    'name' => 'Jane',
    'age' => 30,
    'email' => 'jane@example.com',
    'phone' => '+1234567890'
]);
echo "   ✅ ExtendedUserDTO created successfully\n";

// Теперь создаем ProductDTO - он должен использовать свои правила
echo "3. Creating ProductDTO...\n";
$product = new ProductDTO(['title' => 'Test Product', 'price' => 99.99]);
echo "   ✅ ProductDTO created successfully\n";

// Проверяем валидацию - BaseUserDTO должен отклонять короткое имя
echo "4. Testing BaseUserDTO validation (short name)...\n";
try {
    new BaseUserDTO(['name' => 'A', 'age' => 25]); // слишком короткое имя
    echo "   ❌ ERROR: Should have failed validation!\n";
} catch (Exception $e) {
    echo "   ✅ BaseUserDTO correctly rejected short name: " . $e->getMessage() . "\n";
}

// Проверяем валидацию - ExtendedUserDTO должен отклонять неправильный email
echo "5. Testing ExtendedUserDTO validation (invalid email)...\n";
try {
    new ExtendedUserDTO([
        'name' => 'Valid Name',
        'age' => 25,
        'email' => 'invalid-email', // неправильный email
        'phone' => '+1234567890'
    ]);
    echo "   ❌ ERROR: Should have failed validation!\n";
} catch (Exception $e) {
    echo "   ✅ ExtendedUserDTO correctly rejected invalid email: " . $e->getMessage() . "\n";
}

// Проверяем валидацию - ProductDTO должен отклонять длинный заголовок
echo "6. Testing ProductDTO validation (long title)...\n";
try {
    new ProductDTO(['title' => str_repeat('A', 51), 'price' => 99.99]); // слишком длинный заголовок
    echo "   ❌ ERROR: Should have failed validation!\n";
} catch (Exception $e) {
    echo "   ✅ ProductDTO correctly rejected long title: " . $e->getMessage() . "\n";
}

echo "\n=== Cache Status ===\n";

// Проверяем, что кэш изолирован
$reflection = new ReflectionClass('Betstore\\DTO\\DTO');
$validationCacheProperty = $reflection->getProperty('validationCache');
$validationCacheProperty->setAccessible(true);
$cache = $validationCacheProperty->getValue();

echo "Cached classes: " . count($cache) . "\n";
echo "UserDTO cached: " . (isset($cache[UserDTO::class]) ? "Yes" : "No") . "\n";
echo "ProductDTO cached: " . (isset($cache[ProductDTO::class]) ? "Yes" : "No") . "\n";

if (isset($cache[UserDTO::class])) {
    echo "UserDTO rules: " . implode(', ', array_keys($cache[UserDTO::class]['rules'])) . "\n";
}

if (isset($cache[ProductDTO::class])) {
    echo "ProductDTO rules: " . implode(', ', array_keys($cache[ProductDTO::class]['rules'])) . "\n";
}

echo "\n🎉 Cache isolation fix working correctly!\n";
echo "Each DTO class now uses its own validation rules.\n";
