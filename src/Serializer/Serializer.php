<?php

declare(strict_types=1);

namespace Paramon\ExampleClient\Serializer;

use Paramon\ExampleClient\Exception\DeserializeException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;

class Serializer
{
    /**
     * @param array<string, mixed> $data
     *
     * @throws DeserializeException
     */
    public static function getDeserializedObject(array $data, string|object $neededObject): object
    {
        $reflection = new ReflectionClass($neededObject);
        $constructorParams = [];
        foreach ($reflection->getConstructor()?->getParameters() ?? [] as $param) {
            /** @var ReflectionNamedType $type */
            $type = $param->getType();
            $value = $data[$param->getName()] ?? null;
            unset($data[$param->getName()]);
            if (is_null($data[$param->getName()] ?? null)) {
                if ($param->isDefaultValueAvailable()) {
                    $constructorParams[] = $param->getDefaultValue();
                    continue;
                }
                if ($type->allowsNull()) {
                    $constructorParams[] = null;
                    continue;
                }
            }
            if (!$type->isBuiltin() && class_exists($type->getName())) {
                if (!is_array($value)) {
                    throw new DeserializeException();
                }
                $constructorParams[] = self::getDeserializedObject($value, $type->getName());
                continue;
            }
            if (!self::valideType($type, $value)) {
                throw new DeserializeException();
            }
            $constructorParams[] = $value;
        }
        $obj = $reflection->newInstance(...$constructorParams);
        foreach ($data as $name => $value) {
            try {
                $property = $reflection->getProperty($name);
            } catch (ReflectionException $e) {
                continue;
            }
            $type = $property->getType();
            if (!$type->isBuiltin() && class_exists($type->getName())) {
                if (!is_array($value)) {
                    throw new DeserializeException();
                }
                $property->setValue($obj, self::getDeserializedObject($value, $type->getName()));
                continue;
            }
            if (!self::valideType($type, $value)) {
                throw new DeserializeException();
            }
            $property->setValue($obj, $value);
        }
        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isInitialized($obj)) {
                if ($property->getType()->allowsNull()) {
                    $property->setValue($obj, null);
                    continue;
                }
                throw new DeserializeException();
            }
        }

        return $obj;
    }

    public static function serialize(object $object, ?array $groups = null): array
    {
        $data = [];
        $getter = function (ReflectionProperty $prop) use ($object, $groups) {
            $propertyName = $prop->getName();
            $value = $object->$propertyName;

            return is_object($value) ? self::serialize($value, $groups) : $value;
        };
        $reflection = new ReflectionClass($object);
        if (!$groups) {
            foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
                $data[$prop->getName()] = $getter($prop);
            }
        } else {
            foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
                if (!$attribute = current($prop->getAttributes(
                    SerializeGroup::class,
                    ReflectionAttribute::IS_INSTANCEOF
                ))) {
                    continue;
                }
                if (array_intersect($groups, current($attribute->getArguments() ?: []))) {
                    $data[$prop->getName()] = $getter($prop);
                }
            }
        }

        return $data;
    }

    private static function valideType(ReflectionNamedType $type, mixed $value): bool
    {
        $validators = [
            "string" => is_string(...),
            "bool" => is_bool(...),
            "array" => is_array(...),
            "int" => is_int(...),
            "float" => fn(mixed $value) => is_int($value) || is_float($value),
            "mixed" => fn(mixed $value) => true,
        ];
        if (!$type->isBuiltin() || (!$validator = $validators[$type->getName()] ?? null)) {
            throw new DeserializeException();
        }

        return $validator($value);
    }
}
