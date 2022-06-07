<?php 

namespace Yoohoo\WPZapier\FlowLogic;

class ConditionGreater extends Condition{
    public function execute($a, $b){
        $a = floatval($a);
        $b = floatval($b);
        if($a > $b){
            return true;
        }
        return false;
    }

    public static function getLabel(){
        return "Greater Than";
    }

    public static function getOrder(){
        return 5;
    }
}