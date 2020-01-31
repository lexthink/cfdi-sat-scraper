<?php

declare(strict_types=1);

namespace PhpCfdi\CfdiSatScraper;

/**
 * This class is a helper to parse that incredibly weird responses from SAT that contains internal
 * information in a pipe delimited list.
 *
 * format: |value-length|field-type|field-name|value
 * example: |3|hiddenField|__FOO|foo|0|hiddenField|__EMPTY||16|hiddenField|__BAR|0123456789abcdef|
 * contents: __FOO: foo, __EMPTY: , __BAR: 0123456789abcdef
 */
class ParserFormatSAT
{
    /** @var string[] array of field names to filter */
    private const FILTER_KEYS = ['__EVENTTARGET', '__EVENTARGUMENT', '__LASTFOCUS', '__VIEWSTATE'];

    /**
     * Parse source and retrieve only the preconfigured valid keys
     *
     * @param string $source
     * @return array<string, string>
     */
    public function getFormValues(string $source): array
    {
        // format is: |value-length|field-type|field-name|value

        $values = explode('|', ltrim($source, '|'));
        $length = count($values);

        $items = [];
        for ($index = 0; $index < $length; $index = $index + 4) {
            $fieldName = $values[$index + 2] ?? '';
            if (in_array($fieldName, self::FILTER_KEYS, true)) {
                $items[$fieldName] = $values[$index + 3] ?? '';
            }
        }

        return $items;
    }
}
