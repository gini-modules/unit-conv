<?php

namespace Gini\ORM\UnitConv;

// water(from='volume', to='weight', factor=1)
// water(from='volume', to='pcs', factor=1)
class Conv extends \Gini\ORM\Object
{
    public $object = 'string:40';
    public $from = 'string:20';
    public $to = 'string:20';
    public $factor = 'double';
}