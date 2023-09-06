<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tools;

use ReflectionClass;
use ReflectionException;
use Zumba\JsonSerializer\JsonSerializer;

use function in_array;

/**
 * Used for .out files generation
 */
class CustomJsonSerializer extends JsonSerializer
{
    public const SKIP_PROPERTIES = [
        'allowedKeywords',
        'statementEndOptions',
        'keywordParsers',
        'statementParsers',
        'keywordNameIndicators',// Not static
        'operatorNameIndicators',// Not static
        'defaultDelimiter',
        'parserMethods',
        'OPTIONS',
        'clauses',
        'databaseOptions',
        'delimiters',
        'joins',
        'statementOptions',
        'statementFieldsOptions',
        'statementLinesOptions',
        'triggerOptions',
        'functionOptions',
        'tableOptions',
        'fieldOptions',
        'dataTypeOptions',
        'referencesOptions',
        'keyOptions',
        'viewOptions',
        'eventOptions',
        'userOptions',
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
    protected function extractObjectData($value, $ref, $properties): array
    {
        $data = [];
        foreach ($properties as $property) {
            if (in_array($property, self::SKIP_PROPERTIES, true)) {
                continue;
            }

            try {
                $propRef = $ref->getProperty($property);
                $propRef->setAccessible(true);
                $data[$property] = $propRef->getValue($value);
            } catch (ReflectionException $e) {
                $data[$property] = $value->$property;
            }
        }

        return $data;
    }
}
