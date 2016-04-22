<?php

namespace Gini\ORM\Unit;

// (object="cas/12312", category="weight", name="mg", value="0.001g")
class Unit extends \Gini\ORM\Object
{
    public $object = 'string:20';
    public $category = 'string:20';
    public $name = 'string:10';
    public $value = 'string:40';
}