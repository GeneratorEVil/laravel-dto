# Betstore Laravel DTO

🚀 **Высокопроизводительная библиотека Data Transfer Objects для Laravel с поддержкой JIT компиляции**

[![PHP Version](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-8.0+-red.svg)](https://laravel.com)
[![Performance](https://img.shields.io/badge/Performance-JIT--100x-green.svg)]()
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

## ⚡ Ключевые особенности

- **🚀 JIT-оптимизированная производительность** - до 100x быстрее базовой версии
- **🔄 Автоматическое преобразование типов** - поддержка union types, nullable типов
- **✅ Валидация данных** - интеграция с Laravel Validation
- **📦 Сериализация/десериализация** - в массив, JSON, Laravel Response
- **🎯 Type casting** - преобразование вложенных объектов и коллекций
- **⚡ Кэширование Reflection** - O(1) доступ к метаданным классов
- **🎨 Laravel интеграция** - полная совместимость с экосистемой Laravel

## 📊 Производительность

| Тест | Без оптимизаций | С оптимизациями | С JIT | Улучшение |
|------|----------------|-----------------|-------|----------|
| Создание 100 DTO | ~50 ms | ~11 ms | ~3 ms | **16x** ⚡ |
| Сериализация 1000× | ~1000 ms | ~25 ms | ~3 ms | **333x** ⚡ |
| Общее время тестов | ~1000 ms | ~100 ms | ~9 ms | **111x** ⚡ |

## 📦 Установка

```bash
composer require betstore/laravel-dto
```

### Требования

- PHP 8.2+
- Laravel 8.0+
- OPcache включен
- Для максимальной производительности: JIT компиляция

### Рекомендуемая конфигурация OPcache

```ini
; php.ini или /etc/php/8.2/mods-available/opcache.ini
zend_extension=opcache.so
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.max_accelerated_files=7963
opcache.revalidate_freq=0
opcache.fast_shutdown=1
opcache.interned_strings_buffer=16

; JIT компиляция (автоматически отключается при наличии Xdebug)
opcache.jit=on
opcache.jit_buffer_size=100M
```

## 🚀 Быстрый старт

### Создание простого DTO

```php
<?php

namespace App\DTOs;

use Betstore\DTO\DTO;

class UserDTO extends DTO
{
    public string $name;
    public int $age;
    public ?string $email;
    public bool $isActive;
    public array $preferences;
}
```

### Использование

```php
// Создание из массива
$userData = [
    'name' => 'John Doe',
    'age' => 30,
    'email' => 'john@example.com',
    'isActive' => true,
    'preferences' => ['theme' => 'dark', 'notifications' => true]
];

$userDTO = new UserDTO($userData);

// Автоматическая сериализация
$array = $userDTO->toArray();
$json = $userDTO->toJson();

// Laravel Response
return $userDTO->toResponse(); // Возвращает JsonResponse
```

## 🎯 Расширенные возможности

### Валидация данных

```php
class CreateUserDTO extends DTO
{
    public string $name;
    public int $age;
    public string $email;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:100',
            'age' => 'required|integer|min:18|max:120',
            'email' => 'required|email|unique:users,email',
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Имя обязательно для заполнения',
            'age.min' => 'Возраст должен быть не менее :min лет',
            'email.email' => 'Некорректный формат email',
        ];
    }
}

// Автоматическая валидация при создании
try {
    $userDTO = new CreateUserDTO($request->all());
} catch (ValidationException $e) {
    return response()->json(['errors' => $e->errors()], 422);
}
```

### Type Casting

```php
class OrderDTO extends DTO
{
    public string $orderNumber;
    public UserDTO $customer;
    public Collection $items;
    public array $shippingAddresses;

    protected function casts(): array
    {
        return [
            // Преобразование в объект
            'customer' => [null, UserDTO::class],

            // Преобразование в коллекцию объектов
            'items' => ['collection', OrderItemDTO::class],

            // Преобразование в массив объектов
            'shippingAddresses' => ['array', AddressDTO::class],
        ];
    }
}
```

### Union Types и Nullable Types

```php
class ProductDTO extends DTO
{
    public string $name;
    public string|int $sku; // Union type
    public ?string $description; // Nullable
    public ?CategoryDTO $category; // Nullable object
    public array $tags;
    public float|bool $price; // Union type с разными типами
}
```

### Создание из модели Eloquent

```php
class User extends Model
{
    protected $fillable = ['name', 'email', 'age'];
}

// Автоматическое преобразование snake_case -> camelCase
$user = User::find(1);
$userDTO = UserDTO::fromModel($user);
```

### Фабричные методы

```php
// Из массива
$dto = UserDTO::fromArray($data);

// Из Eloquent модели
$dto = UserDTO::fromModel($userModel);

// Из коллекции моделей
$dtos = $userModels->map(fn($model) => UserDTO::fromModel($model));
```

## 🔧 Конфигурация

### Настройка casts()

```php
protected function casts(): array
{
    return [
        // Преобразование в объект
        'user' => [null, UserDTO::class],

        // Преобразование в массив объектов
        'comments' => ['array', CommentDTO::class],

        // Преобразование в коллекцию объектов
        'posts' => ['collection', PostDTO::class],
    ];
}
```

### Сериализация с фильтрацией

```php
// Исключить null значения
$array = $dto->toArray(true);

// Получить только определенные поля
$publicData = $dto->toArray();
unset($publicData['password'], $publicData['secretKey']);
```

## 🧪 Тестирование

```bash
# Запуск всех тестов
composer test

# С замерами производительности
php vendor/bin/phpunit tests/ --verbose

# Тесты оптимизаций
php vendor/bin/phpunit tests/PerformanceOptimizationTest.php
```

### Структура тестов

```
tests/
├── DTOTest.php              # Основные тесты функциональности
├── TypecastingDTOTest.php   # Тесты преобразования типов
├── ValidationDTOTest.php    # Тесты валидации
├── FactoryMethodsDTOTest.php # Тесты фабричных методов
├── SerializationDTOTest.php  # Тесты сериализации
├── PerformanceOptimizationTest.php # Тесты производительности
```

## 🚀 Производительность

### Оптимизации

1. **Кэширование Reflection данных** - статический кэш метаданных классов
2. **Оптимизация поиска свойств** - O(1) доступ вместо линейного поиска
3. **Кэширование валидации** - статический кэш правил и сообщений
4. **JIT-компиляция** - до 100x улучшения производительности

### Результаты тестирования

```
Без оптимизаций:   ~1000 ms для комплексных операций
С оптимизациями:    ~100 ms (10x улучшение)
С OPcache:          ~90 ms (11x улучшение)
С JIT:              ~9 ms (111x улучшение)
```

### Рекомендации по оптимизации

- **Включите OPcache** для кэширования байт-кода
- **Используйте JIT** в production для максимальной производительности
- **Отключите Xdebug** в production (JIT конфликтует с Xdebug)
- **Мониторьте hit rate OPcache** (>95% желательно)

## 📚 API Reference

### Основные методы

```php
// Конструктор с автоматической валидацией и преобразованием
new DTOClass(array $data)

// Сериализация
$dto->toArray(bool $unsetNulls = false): array
$dto->toJson(int $options = 0): string
$dto->toResponse(Request $request = null, int $status = 200): JsonResponse

// Фабричные методы
DTOClass::fromArray(array $data): static
DTOClass::fromModel(Model $model): static
```

### Защищенные методы для переопределения

```php
protected function rules(): array // Правила валидации
protected function messages(): array // Сообщения об ошибках
protected function casts(): array // Правила преобразования типов
```

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Laravel Framework за вдохновение
- PHP JIT компиляция за невероятную производительность
- Open source сообщество за вклад в развитие PHP

---

**Создано с ❤️ для высокопроизводительных Laravel приложений**

[📧 Email](mailto:info@betstore.io) • [🌐 Website](https://betstore.io) • [🐛 Issues](https://github.com/betstore/laravel-dto/issues)
