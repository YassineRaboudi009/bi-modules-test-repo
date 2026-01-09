<?php
namespace BettIngenieure\Utilities\Struct;

use BettIngenieure\Base\BaseClass;

class DateEntry extends BaseClass {

    public int $timestamp;
    public mixed $entry;

    public function __construct(int $timestamp, mixed $entry) {

        $this->timestamp = $timestamp;
        $this->entry = $entry;
    }
}