<?php

namespace Gini\Unit\Conversion;

/**
 * 能够支持单位转换的ORM接口 
 *
 * @package unit-conversion
 * @author Jia Huang
 */
interface ORM {
    public function unitInfo();
}