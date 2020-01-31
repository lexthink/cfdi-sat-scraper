<?php

declare(strict_types=1);

namespace PhpCfdi\CfdiSatScraper;

/**
 * Class ParserFormatSAT.
 */
class ParserFormatSAT
{
    public $source;

    public $values;

    public $items;

    public $valids;

    public $sorted;

    /**
     * ParserFormatSAT constructor.
     *
     * @param $source
     */
    public function __construct($source)
    {
        $this->source = $source;
        $this->values = [];
        $this->items = [];
        $this->valids = ['__EVENTTARGET', '__EVENTARGUMENT', '__LASTFOCUS', '__VIEWSTATE'];
    }

    /**
     * @return array
     */
    public function getFormValues()
    {
        $this->values = explode('|', $this->source);

        $this->sorted = [];
        foreach (range(0, count($this->values) - 1) as $index) {
            $item = $this->values[$index];
            if (in_array($item, $this->valids)) {
                $name = $item;
                $index += 1;
                $item = $this->values[$index];
                $this->items[$name] = $item;
            }
        }

        return $this->items;
    }
}
