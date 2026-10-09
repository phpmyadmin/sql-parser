<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tools;

use ReflectionClass;
use ReflectionException;
use SplDoublyLinkedList;
use UnitEnum;
use Zumba\JsonSerializer\Exception\JsonSerializerException;
use Zumba\JsonSerializer\JsonSerializer;

use function array_key_exists;
use function class_exists;
use function constant;
use function in_array;
use function is_subclass_of;
use function method_exists;
use function substr;

use const PHP_VERSION_ID;

/**
 * Used for .out files generation
 */
class CustomJsonSerializer extends JsonSerializer
{
    public const SKIP_PROPERTIES = [
        'ALLOWED_KEYWORDS',
        'GROUP_OPTIONS',
        'END_OPTIONS',
        'KEYWORD_PARSERS',
        'STATEMENT_PARSERS',
        'KEYWORD_NAME_INDICATORS',
        'OPERATOR_NAME_INDICATORS',
        'DEFAULT_DELIMITER',
        'PARSER_METHODS',
        'OPTIONS',
        'CLAUSES',
        'DB_OPTIONS',
        'DELIMITERS',
        'JOINS',
        'FIELDS_OPTIONS',
        'LINES_OPTIONS',
        'TRIGGER_OPTIONS',
        'FUNC_OPTIONS',
        'TABLE_OPTIONS',
        'FIELD_OPTIONS',
        'DATA_TYPE_OPTIONS',
        'REFERENCES_OPTIONS',
        'KEY_OPTIONS',
        'VIEW_OPTIONS',
        'EVENT_OPTIONS',
        'USER_OPTIONS',
        'asciiMap',
        'trace',
    ];

    /**
     * Extract the object data
     *
     * @param  object          $value
     * @param  ReflectionClass $ref
     * @param  string[]        $properties
     *
     * @return array<string,mixed>
     */
    protected function extractObjectData($value, $ref, $properties)
    {
        $data = [];
        foreach ($properties as $property) {
            if (in_array($property, self::SKIP_PROPERTIES, true)) {
                continue;
            }

            try {
                $propRef = $this->getReflectionProperty($ref, $property);
                if (PHP_VERSION_ID < 80100) {
                    $propRef->setAccessible(true);
                }

                if (method_exists($propRef, 'isInitialized') && ! $propRef->isInitialized($value)) {
                    continue;
                }

                $data[$property] = $propRef->getValue($value);
            } catch (ReflectionException $e) {
                $data[$property] = $value->$property;
            }
        }

        return $data;
    }

    /**
     * Convert the serialized array into an object
     *
     * @param array<mixed> $value
     *
     * @return object
     *
     * @throws JsonSerializerException
     */
    protected function unserializeObject($value)
    {
        $className = $value[self::CLASS_IDENTIFIER_KEY];
        unset($value[self::CLASS_IDENTIFIER_KEY]);

        if ($className[0] === '@') {
            $index = substr($className, 1);

            return $this->objectMapping[$index];
        }

        if (array_key_exists($className, $this->customObjectSerializerMap)) {
            $obj = $this->customObjectSerializerMap[$className]->unserialize($value);
            $this->objectMapping[$this->objectMappingIndex++] = $obj;

            return $obj;
        }

        if (! class_exists($className)) {
            throw new JsonSerializerException('Unable to find class ' . $className);
        }

        if ($this->allowedClasses !== null && ! in_array($className, $this->allowedClasses, true)) {
            throw new JsonSerializerException(
                'Class ' . $className . ' is not allowed for deserialization. ' .
                'Use setAllowedClasses() to configure the list of allowed classes.'
            );
        }

        if ($className === 'DateTime' || $className === 'DateTimeImmutable') {
            $obj = $this->restoreUsingUnserialize($className, $value);
            $this->objectMapping[$this->objectMappingIndex++] = $obj;

            return $obj;
        }

        if (is_subclass_of($className, UnitEnum::class)) {
            $obj = constant($className . '::' . $value['name']);
            $this->objectMapping[$this->objectMappingIndex++] = $obj;

            return $obj;
        }

        if (! $this->isSplList($className)) {
            $ref = new ReflectionClass($className);
            $obj = $ref->newInstanceWithoutConstructor();
        } else {
            $obj = new $className();
        }

        if ($obj instanceof SplDoublyLinkedList) {
            $obj->unserialize($value['value']);
            $this->objectMapping[$this->objectMappingIndex++] = $obj;

            return $obj;
        }

        $this->objectMapping[$this->objectMappingIndex++] = $obj;
        foreach ($value as $property => $propertyValue) {
            try {
                $propRef = $this->getReflectionProperty($ref, $property);
                if (PHP_VERSION_ID < 80100) {
                    $propRef->setAccessible(true);
                }

                $propRef->setValue($obj, $this->unserializeData($propertyValue));
            } catch (ReflectionException $e) {
                switch ($this->undefinedAttributeMode) {
                    case self::UNDECLARED_PROPERTY_MODE_SET:
                        $obj->$property = $this->unserializeData($propertyValue);
                        break;
                    case self::UNDECLARED_PROPERTY_MODE_IGNORE:
                        break;
                    case self::UNDECLARED_PROPERTY_MODE_EXCEPTION:
                        throw new JsonSerializerException('Undefined attribute detected during unserialization');
                }
            }
        }

        if (method_exists($obj, '__wakeup')) {
            $obj->__wakeup();
        }

        return $obj;
    }
}
