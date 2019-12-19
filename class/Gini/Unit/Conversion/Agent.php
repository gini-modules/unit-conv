<?php

namespace Gini\Unit\Conversion;

class Agent extends \Gini\Unit\Conversion
{
    private static function getDB()
    {
        return \Gini\Database::db('unit-conv-client-local-db');
    }

    /**
     * 获取测量单位的维度
     * 比如 getDimension('g') = 'weight', getDimension('ml') = 'volume'.
     *
     * @param string $unit
     *
     * @return string
     */
    public function getDimension($unit)
    {
        $unit = trim(strtolower($unit));
        $cache = \Gini\Cache::of('unitconv');
        foreach ($this->_unitInfo as $object) {
            $key = "dimension[$object-$unit]";
            $dimension = $cache->get($key);
            if (false === $dimension) {
                $db = self::getDB();
                $row = $db->query('select id,dimension from unitconv_unit where name=:name', null, [
                    ':name'=> $unit
                ])->row();
                if ($row->id) $dimension = $row->dimension;
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
     */
    public function getUnitFactor($unit)
    {
        $unit = trim(strtolower($unit));
        $cache = \Gini\Cache::of('unitconv');
        foreach ($this->_unitInfo as $object) {
            $key = "ufactor[$object-$unit]";
            $factor = $cache->get($key);
            if (false === $factor) {
                $db = self::getDB();
                $row = $db->query('select id,factor from unitconv_unit where name=:name', null, [
                    ':name'=> $unit
                ])->row();
                if ($row->id) $factor = $row->factor;
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
     */
    public function getDimensionFactor($from, $to)
    {
        if ($from == $to) {
            return 1;
        }
        $cache = \Gini\Cache::of('unitconv');
        $objects = $this->_unitInfo;
        $key = "dfactor[".md5(J($objects))."-$from-$to]";
        $factor = $cache->get($key);
        if (false === $factor) {
            $db = self::getDB();
            foreach ($objects as $object) {
                $convs = $db->query("select * from unitconv_conv where object = :object", null, [':object'=>$object])->rows();
                foreach ($convs as $conv) {
                    $current_from = $conv->from;
                    $current_to = $conv->to;
                    $current_factor = $conv->factor;
                    if ($current_from==$from && $current_to==$to) {
                        $factor = $current_factor;
                        break 2;
                    } else if ($current_from==$to && $current_to==$from) {
                        $factor = 1 / $current_factor;
                        break 2;
                    }
                }
                foreach ($convs as $conv) {
                    $current_from = $conv->from;
                    $current_to = $conv->to;
                    $current_factor = $conv->factor;
                    if ($to==$current_to) {
                        $from_factor = self::getDefaultFactor($from, $current_from);
                        if ($from_factor) {
                            $tmp_factor = $current_factor * $from_factor;
                            break 2;
                        }
                    }
                }
            }
            $cache->set($key, $factor, static::$TIMEOUT);
        }
        return $factor;
    }

    private static function getDefaultFactor($dimensionA, $dimensionB)
    {
        if ($dimensionA == $dimensionB) return 1;
        $db = self::getDB();
        $conv = $db->query('select id,factor from unitconv_conv where `object`=:obj and `from`=:from and `to`=:to', null, [
            ':obj'=> 'default',
            ':from'=> $dimensionA,
            ':to'=> $dimensionB
        ])->row();
        if ($conv->id) {
            return $conv->factor;
        }
        $conv = $db->query('select id,factor from unitconv_conv where `object`=:obj and `from`=:from and `to`=:to', null, [
            ':obj'=> 'default',
            ':from'=> $dimensionB,
            ':to'=> $dimensionA
        ])->row();
        if ($conv->id) {
            return 1 / $conv->factor;
        }
        return false;
    }

    public function getUnits()
    {
        $cache = \Gini\Cache::of('unitconv');
        $key = 'units';
        $units = $cache->get($key);
        if (false === $units) {
            $db = self::getDB();
            $rows = $db->query('select id,name from unitconv_unit')->rows();
            $units = [];
            foreach ($rows as $row) {
                $units[$row->id] = $row->name;
            }
            $cache->set($key, $units, static::$TIMEOUT);
        }

        return $units;
    }

    public function validate($expr)
    {
        return parent::validate($expr);
    }

    public function parse($expr)
    {
        return parent::parse($expr);
    }

    public function from($expr)
    {
        return parent::from($expr);
    }

    public function to($expr)
    {
        return parent::to($expr);
    }

}
