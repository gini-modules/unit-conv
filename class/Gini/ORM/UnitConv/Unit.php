<?php

namespace Gini\ORM\UnitConv;

// (dimension="weight", name="g", factor=1)
// (dimension="weight", name="mg", factor=0.001)
// (dimension="weight", name="kg", factor=1000)
class Unit extends \Gini\ORM\Object
{
    public $dimension = 'string:20';
    public $name = 'string:10';
    public $factor = 'double';
    
    public function getUnits() {
        return array_values(those('unitconv/unit')->get('id', 'name'));
    }
}