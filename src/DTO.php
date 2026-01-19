<?php

namespace Betstore\DTO;

use Exception;
use Throwable;
use ReflectionClass;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;

abstract class DTO implements Responsable, Jsonable, Arrayable
{
    /**
     * Кэш для reflection данных по классам
     */
    private static array $reflectionCache = [];

    /**
     * Кэш для свойств класса
     */
    private static array $propertiesCache = [];

    protected function rules(): array
    {
        return [];
    }

    /**
     * @doc [key => [null, class]] - для преобразования в class
     * @doc [key => ['array', class]] - для преобразования в array[class]
     * @doc [key => ['collection', class]] - для преобразования в collect(class)
     */
    protected function casts(): array
    {
        return [];
    }

    protected function messages(): array
    {
        return [];
    }

    /**
     * Получить кэшированные данные reflection для класса
     */
    private static function getReflectionData(string $className): array
    {
        if (!isset(self::$reflectionCache[$className])) {
            $reflection = new ReflectionClass($className);
            $properties = $reflection->getProperties();

            // Создаем хэш-мапу свойств для быстрого поиска
            $propertiesMap = [];
            foreach ($properties as $property) {
                $propertiesMap[$property->getName()] = $property;
            }

            self::$reflectionCache[$className] = [
                'reflection' => $reflection,
                'properties' => $properties,
                'propertiesMap' => $propertiesMap,
            ];
        }

        return self::$reflectionCache[$className];
    }

    public function __construct(array $data = [])
    {
        $this->validate($data);

        $reflectionData = self::getReflectionData(static::class);
        $propertiesMap = $reflectionData['propertiesMap'];

        foreach ($data as $propertyName => $propertyValue) {
            if (!isset($propertiesMap[$propertyName])) {
                continue;
            }

            $property = $propertiesMap[$propertyName];

            $type = $property->getType();

            if ($type) {
                if ($type instanceof \ReflectionUnionType) {
                    $this->handleUnionType($type, $propertyName, $propertyValue);
                } else {
                    $this->typecasting($type, $propertyName, $propertyValue);
                }
            } else {
                $this->{$propertyName} = $propertyValue;
            }

            $casts = $this->casts();

            if ($casts && array_key_exists($propertyName, $casts)) {
                [$castType, $castClass] = $casts[$propertyName];
                $isCollection = $castType === 'collection';
                $isArray = $castType === 'array';

                if (is_null($propertyValue)) {
                    $this->{$propertyName} = null;

                    continue;
                }

                if (($isCollection || $isArray) && ! is_array($propertyValue)) {
                    throw new Exception("Value for {$propertyName} must be an array to cast to " . ($isCollection ? 'collection' : 'array') . " of {$castClass}");
                }

                $this->{$propertyName} = match ($castType) {
                    'collection' => count($propertyValue) > 0
                        ? collect($propertyValue)->map(fn($item) => ($item instanceof $castClass) ? $item : new $castClass($item))
                        : collect(),
                    'array' => count($propertyValue) > 0
                        ? array_map(fn($item) => ($item instanceof $castClass) ? $item : new $castClass($item), $propertyValue)
                        : [],
                    default => $this->handleEnumAndInstance($castClass, $propertyValue),
                };
            }
        }
    }


    private function handleUnionType(\ReflectionUnionType $type, string $propertyName, $propertyValue): void
    {
        $types = $type->getTypes();
        $valueType = gettype($propertyValue);

        // Быстрая проверка для null
        if ($propertyValue === null) {
            foreach ($types as $unionType) {
                if ($unionType->allowsNull()) {
                    $this->{$propertyName} = null;
                    return;
                }
            }
            throw new Exception("Cannot assign null to non-nullable union type {$propertyName}");
        }

        // Быстрая проверка соответствия типов
        foreach ($types as $unionType) {
            $unionTypeName = $unionType->getName();

            // Нормализуем типы для сравнения
            $normalizedType = match($unionTypeName) {
                'int' => 'integer',
                'bool' => 'boolean',
                'float' => 'double',
                default => $unionTypeName
            };

            if ($valueType === $normalizedType ||
                ($propertyValue instanceof $unionTypeName) ||
                (is_object($propertyValue) && is_subclass_of($propertyValue, $unionTypeName))) {
                $this->{$propertyName} = $propertyValue;
                return;
            }
        }

        // Если не подошло как есть, пытаемся конвертировать
        foreach ($types as $unionType) {
            try {
                $this->typecasting($unionType, $propertyName, $propertyValue);
                return;
            } catch (Throwable) {
                continue;
            }
        }

        $typeNames = array_map(fn($t) => $t->getName(), $types);
        throw new Exception("Cannot convert {$propertyName} to one of " . implode(', ', $typeNames));
    }

    private function typecasting($type, $propertyName, $propertyValue)
    {
        $typeName = $type->getName();
        $allowNull = $type->allowsNull();

        try {
            $this->{$propertyName} = match ($typeName) {
                'string' => $allowNull && is_null($propertyValue) ? null : (string) $propertyValue,
                'int' => $allowNull && is_null($propertyValue) ? null : (int) $propertyValue,
                'bool' => $allowNull && is_null($propertyValue) ? null : (bool) $propertyValue,
                'array' => $allowNull && is_null($propertyValue) ? null : (array) $propertyValue,
                'float' => $allowNull && is_null($propertyValue) ? null : (float) $propertyValue,
                default => $this->handleEnumAndInstance($typeName, $propertyValue),
            };
        } catch (Throwable $e) {
            throw new Exception("Cannot convert {$propertyName} to {$typeName}");
        }
    }
    private function handleEnumAndInstance(string $typeName, $propertyValue)
    {
        if (is_subclass_of($typeName, \UnitEnum::class) && ! $propertyValue instanceof $typeName) {
            foreach ($typeName::cases() as $enumCase) {
                if ($enumCase->value === $propertyValue) {
                    return $enumCase;
                }
            }
            throw new Exception("Invalid enum value for type {$typeName}");
        }

        return $propertyValue instanceof $typeName ? $propertyValue : new $typeName($propertyValue);
    }

    protected function validate(array $data): void
    {
        static $cachedRules = null;
        static $cachedMessages = null;

        if ($cachedRules === null) {
            $cachedRules = $this->rules();
            $cachedMessages = $this->messages();
        }

        if ($cachedRules && count($data) > 0) {
            Validator::make($data, $cachedRules, $cachedMessages)->validate();
        }
    }

    public static function fromArray(array $data)
    {
        return new static($data);
    }

    public static function fromModel(Model $model)
    {
        $array = [];
        $dto = new static();
        foreach ($model->getAttributes() as $key => $value) {
            $camelCaseKey = Str::camel($key);
            if (property_exists($dto, $camelCaseKey)) {
                $array[$camelCaseKey] = $model->{$key};
            }
        }

        return new static($array);
    }

    public function toArray(bool $unsetNulls = false): array
    {
        $reflectionData = self::getReflectionData(static::class);
        $properties = $reflectionData['properties'];
        $data = [];

        // Собираем все свойства, включая унаследованные
        foreach ($properties as $property) {
            $property->setAccessible(true); // Делаем свойство доступным, если оно protected или private
            // Проверяем, инициализировано ли свойство
            $value = $property->isInitialized($this) ? $property->getValue($this) : null;
            $data[$property->getName()] = $value;
        }

        if ($unsetNulls) {
            $data = array_filter($data, fn($value) => ! is_null($value));
        }

        foreach ($data as $key => $value) {
            if ($value instanceof DTO) {
                $data[$key] = $value->toArray($unsetNulls);
            } elseif ($value instanceof \UnitEnum) {
                $data[$key] = $value->value;
            } elseif (is_array($value)) {
                $data[$key] = array_map(function ($item) use ($unsetNulls) {
                    return $item instanceof DTO ? $item->toArray($unsetNulls) : $item;
                }, $value);
            }
        }

        return $data;
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    public function toResponse($request, int $status = 200)
    {
        return response()->json($this->toArray(), $status);
    }
}
