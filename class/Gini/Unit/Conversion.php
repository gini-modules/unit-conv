<?php

namespace Gini\Unit;

// \Gini\Unit\Conversion::of($object)->from('100g')->to('ml');
// \Gini\Unit\Conversion::of('cas/123')->validate('100ml');
// \Gini\Unit\Conversion::of('cas/123')->getUnits();

class Conversion {

    protected $_object;
    protected $_unitInfo;
    protected $_value;
    protected $_unit;

    public static function of($object) {
        return new \Gini\Unit\Conversion($object);
    }

    protected $parents = [];
    public function __construct($object) {
        $this->_object = $object;
        $implements = class_implements($object);
        if (isset($implements['Gini\Unit\Conversion\ORM'])) {
            $unitInfo = $object->unitInfo();
        } else {
            $unitInfo = [$object->name().'/'.$object->id];
        }
        
        $this->_unitInfo = $unitInfo;
    }

    /**
     * 验证传入的表达式可以被识别: e.g. 100mg, 300pcs
     *
     * @param string $expr 
     * @return bool
     * @author Jia Huang
     */
    public function validate($expr) {
        return !!$this->parse($expr);
    }

    /**
     * 验证传入的表达式可以被识别: e.g. 100mg, 300pcs
     *
     * @param string $expr 
     * @return void
     * @author Jia Huang
     */
    public function parse($expr) {
        $parts = preg_match('/([-0-9.]+)\s*(\S*)/', $expr);
        if (!$parts) return false;
        return [floatval($parts[1]), $parts[2]];
    }

    public function from($expr) {
        list($value, $unit) = $this->parse($expr);
        if ($unit) {
            $this->_value = $value;
            $this->_unit = an('unit/unit', [
                'object' => $this->_object, 
                'name' => $parts[2],
            ]);
        } else {
            $this->_value = null;
            $this->_unit = null;
        }
        return $this;
    }

    public function to($unit) {
        return 1;
    }

}