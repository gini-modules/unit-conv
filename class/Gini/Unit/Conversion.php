<?php

namespace Gini\Unit;

/**
 * 单位转换类
 * e.g.
 *     $volume = \Gini\Unit\Conversion::of($object)->from('100g')->to('ml');
 *     $success = \Gini\Unit\Conversion::of('cas/123')->validate('100ml');
 *     $units = \Gini\Unit\Conversion::of('cas/123')->getUnits();
 *
 * @package default
 * @author Jia Huang
 */
class Conversion {

    protected $_object;
    protected $_unitInfo;

    protected $_value;
    protected $_fromUnit;
    protected $_toUnit;

    public static function of($object) {
        return new \Gini\Unit\Conversion($object);
    }

    protected $parents = [];
    public function __construct($object) {
        if (is_object($object)) {
            $implements = class_implements($object);
            if (isset($implements['Gini\Unit\Conversion\ORM'])) {
                $unitInfo = $object->unitInfo();
            } elseif ($object instanceof \Gini\ORM\Object) {
                $unitInfo = [ $object->name().'/'.$object->id ];
            } else {
                $unitInfo = [ get_class($object) ];
            }
            $this->_unitInfo = $unitInfo;
        } elseif (is_array($object)) {
            $this->_unitInfo = $object;
        } else {
            $this->_unitInfo = [ strval($object)];
        }
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
        if (!preg_match('/([-0-9.]+)\s*(\S*)/', $expr, $parts)) return false;
        return [ floatval($parts[1]), $parts[2] ];
    }

    public function from($expr) {
        list($value, $unit) = $this->parse($expr);
        if ($unit) {
            $this->_value = $value;
            $this->_fromUnit = $unit;
        } else {
            $this->_value = null;
            $this->_fromUnit = null;
        }
        return $this;
    }

    public function to($unit) {
        $this->_toUnit = $unit;

        $fromDimension = $this->getDimension($this->_fromUnit);
        if (!$fromDimension) return false;

        $toDimension = $this->getDimension($this->_toUnit);
        if (!$toDimension) return false;

        $factorDimension = $this->getDimensionFactor($fromDimension, $toDimension);
        if (!$factorDimension) return false;

        $factorFromUnit = $this->getUnitFactor($this->_fromUnit);
        if (!$factorFromUnit) return false;

        $factorToUnit = $this->getUnitFactor($this->_toUnit);
        if (!$factorToUnit) return false;
 
        $factor = $factorDimension * $factorFromUnit * $factorToUnit;
        return $this->_value * $factor;
    }

    /**
     * 获取测量单位的维度
     * 比如 getDimension('g') = 'weight', getDimension('ml') = 'volume'
     *
     * @param string $unit 
     * @return string
     * @author Jia Huang
     */
    public function getDimension($unit) {
        $u = a('unitconv/unit', ['name'=>$unit]);
        return $u->id ? $u->dimension : false;
    }

    /**
     * 获取测量单位在自己维度上的系数，
     * 比如 getUnitFactor('g') = 1, getUnitFactor('kg') = 1000, getUnitFactor('mg') = 0.001
     *
     * @param string $unit 
     * @return double
     * @author Jia Huang
     */
    public function getUnitFactor($unit) {
        $u = a('unitconv/unit', ['name'=>$unit]);
        return $u->id ? $u->factor : false;
    }

    /**
     * 获取两个维度之间的换算系数, 会和指定对象的信息有关
     * e.g. getDimensionFactor('g', ')
     *
     * @param string $fromDimension 
     * @param string $toDimension 
     * @return double
     * @author Jia Huang
     */
    public function getDimensionFactor($fromDimension, $toDimension) {
        if ($fromDimension == $toDimension) {
            return 1.00;
        }

        foreach ($this->_unitInfo as $object) {
            $c = a('unitconv/conv', ['object'=>$object, 'from'=>$fromDimension, 'to'=>$toDimension]);
            if ($c->id) {
                $factor = doubleval($c->factor);
                break;
            }
            $c = a('unitconv/conv', ['object'=>$object, 'from'=>$toDimension, 'to'=>$fromDimension]);
            if ($c->id) {
                $factor = 1 / doubleval($c->factor);
                break;
            }
        }
        return $factor ?: false;
    }

}