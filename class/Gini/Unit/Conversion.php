<?php

namespace Gini\Unit;

/**
 * 单位转换类
 * e.g.
 *     $volume = \Gini\Unit\Conversion::of($object)->from('100g')->to('ml');
 *     $success = \Gini\Unit\Conversion::of('cas/123')->validate('100ml');
 *     $units = \Gini\Unit\Conversion::of('cas/123')->getUnits();.
 *
 * @author Jia Huang
 */
class Conversion
{
    protected $_object;
    protected $_unitInfo;

    protected $_value;
    protected $_fromUnit;
    protected $_toUnit;

    protected static $TIMEOUT = 86400;

    private $_RPC;
    public static function getRPC()
    {
        if (!$_RPC) {
            $conf = \Gini\Config::get('app.rpc')['unitconv'];
            $_RPC = new \Gini\RPC($conf['url'] ?: strval($conf));
        }

        return $_RPC;
    }

    public static function of($object)
    {
        return new \Gini\Unit\Conversion($object);
    }

    protected $parents = [];
    public function __construct($object)
    {
        if (is_object($object)) {
            $implements = class_implements($object);
            if (isset($implements['Gini\Unit\Conversion\ORM'])) {
                $unitInfo = $object->unitInfo();
            } elseif ($object instanceof \Gini\ORM\Object) {
                $unitInfo = [$object->name().'/'.$object->id];
            } else {
                $unitInfo = [get_class($object)];
            }
            $this->_unitInfo = $unitInfo;
        } elseif (is_array($object)) {
            $this->_unitInfo = $object;
        } else {
            $this->_unitInfo = [strval($object)];
        }
    }

    /**
     * 验证传入的表达式可以被识别: e.g. 100mg, 300pcs.
     *
     * @param string $expr
     *
     * @return bool
     *
     * @author Jia Huang
     */
    public function validate($expr)
    {
        list($value, $unit) = $this->parse($expr);
        if (!is_numeric($value) || !$unit) {
            return false;
        }

        $unit = strtolower($unit);
        $units = array_map('strtolower', $this->getUnits());
        return in_array($unit, $units);
    }

    /**
     * 验证传入的表达式可以被识别: e.g. 100mg, 300pcs.
     *
     * @param string $expr
     *
     * @author Jia Huang
     */
    public function parse($expr)
    {
        if (!preg_match('/([-0-9.]+)\s*(\S*)/', $expr, $parts)) {
            return false;
        }

        return [floatval($parts[1]), $parts[2]];
    }

    public function from($expr)
    {
        list($value, $unit) = $this->parse($expr);
        if ($unit) {
            $this->_value = $value;
            $this->_fromUnit = strtolower($unit);
        } else {
            $this->_value = null;
            $this->_fromUnit = null;
        }

        return $this;
    }

    public function to($unit)
    {
        $this->_toUnit = strtolower($unit);

        $fromDimension = $this->getDimension($this->_fromUnit);
        if (!$fromDimension) {
            return false;
        }

        $toDimension = $this->getDimension($this->_toUnit);
        if (!$toDimension) {
            return false;
        }

        $factorDimension = $this->getDimensionFactor($fromDimension, $toDimension);
        if (!$factorDimension) {
            return false;
        }

        $factorFromUnit = $this->getUnitFactor($this->_fromUnit);
        if (!$factorFromUnit) {
            return false;
        }

        $factorToUnit = $this->getUnitFactor($this->_toUnit);
        if (!$factorToUnit) {
            return false;
        }

        $factor = $factorDimension * $factorFromUnit / $factorToUnit;

        return $this->_value * $factor;
    }

    /**
     * 获取测量单位的维度
     * 比如 getDimension('g') = 'weight', getDimension('ml') = 'volume'.
     *
     * @param string $unit
     *
     * @return string
     *
     * @author Jia Huang
     */
    public function getDimension($unit)
    {
        $unit = strtolower($unit);
        $cache = \Gini\Cache::of('unitconv');
        foreach ($this->_unitInfo as $object) {
            $key = "dimension[$object-$unit]";
            $dimension = $cache->get($key);
            if (false === $dimension) {
                $rpc = self::getRPC();
                $dimension = $rpc->UnitConv->getDimension($object, $unit);
                $dimension = ($dimension===false) ? null : $dimension;
                $cache->set($key, $dimension, static::$TIMEOUT);
            }
            if ($dimension) {
                break;
            }
        }

        return $dimension;
    }

    /**
     * 获取测量单位在自己维度上的系数，
     * 比如 
     * getUnitFactor('g') = 1, 
     * getUnitFactor('kg') = 1000, 
     * getUnitFactor('mg') = 0.001.
     *
     * @param string $unit
     *
     * @return float
     *
     * @author Jia Huang
     */
    public function getUnitFactor($unit)
    {
        $unit = strtolower($unit);
        $cache = \Gini\Cache::of('unitconv');
        foreach ($this->_unitInfo as $object) {
            $key = "ufactor[$object-$unit]";
            $factor = $cache->get($key);
            if (false === $factor) {
                $rpc = self::getRPC();
                $factor = $rpc->UnitConv->getUnitFactor($object, $unit);
                $factor = ($factor===false) ? null : $factor;
                $cache->set($key, $factor, static::$TIMEOUT);
            }
            if ($factor) {
                break;
            }
        }

        return $factor;
    }

    /**
     * 获取两个维度之间的换算系数, 会和指定对象的信息有关
     * e.g. getDimensionFactor('g', 'ml').
     *
     * @param string $from
     * @param string $to
     *
     * @return float
     *
     * @author Jia Huang
     */
    public function getDimensionFactor($from, $to)
    {
        if ($from == $to) {
            return 1;
        }

        $cache = \Gini\Cache::of('unitconv');
        foreach ($this->_unitInfo as $object) {
            $key = "dfactor[$object-$from-$to]";
            $factor = $cache->get($key);
            if (false === $factor) {
                $rpc = self::getRPC();
                $factor = $rpc->UnitConv->getDimensionFactor($object, $from, $to);
                $factor = ($factor===false) ? null : $factor;
                $cache->set($key, $factor, static::$TIMEOUT);
            }
            if ($factor) {
                break;
            }
        }

        return $factor;
    }

    public function getUnits()
    {
        $cache = \Gini\Cache::of('unitconv');
        $key = 'units';
        $units = $cache->get($key);
        if (false === $units) {
            $rpc = self::getRPC();
            $units = $rpc->UnitConv->getUnits();
            $units = ($units===false) ? null : $units;
            $cache->set($key, $units, static::$TIMEOUT);
        }

        return $units;
    }
}
