<?php

namespace Gini\Controller\CLI;

class UnitConv extends \Gini\Controller\CLI {

    public function actionConvert($args) {
        $params = [];
        foreach ($args as $arg) {
            list($k, $v) = explode('=', $arg, 2);
            $params[trim($k)] = trim($v);
        }

        if (!isset($params['object']) 
        || !isset($params['from']) || !isset($params['to'])) {
            die("Usage: gini unit-conv convert object=liquid from=100mg to=l\n");
        }

        $conv = \Gini\Unit\Conversion::of($params['object']);
        list($fromValue, $fromUnit) = $conv->parse($params['from']);
        $toUnit = $params['to'];
    
        $factorFromUnit = $conv->getUnitFactor($fromUnit);
        $factorToUnit = $conv->getUnitFactor($toUnit);

        $fromDimension = $conv->getDimension($fromUnit);
        $toDimension = $conv->getDimension($toUnit);

        $factorDimension = $conv->getDimensionFactor($fromDimension, $toDimension);

        $toValue = $conv->from($params['from'])->to($params['to']);
        printf("\e[33m%s\e[0m = \e[33m%f\e[0m * \e[1m%f\e[0m %s/%s *  \e[1m%f\e[0m %s/%s * \e[1m%f\e[0m %s/%s = \e[32m%f%s\e[0m\n", $params['from'], $fromValue, 
            $factorFromUnit, $fromDimension, $fromUnit,
            $factorDimension, $toDimension, $fromDimension,
            $factorToUnit, $toUnit, $toDimension,
            $toValue, $toUnit);
    }

    public function actionListUnits($args) {
        $conv = \Gini\Unit\Conversion::of('nothing');
        $units = $conv->getUnits();
        echo implode(", ", $units)."\n";
    }
}