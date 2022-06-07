<?php 

namespace Yoohoo\WPZapier\FlowLogic;

class ConditionLess extends Condition{
    public function execute($a, $b){
        $a = floatval($a);
        $b = floatval($b);
        if($a < $b){
            return true;
        }
        return false;
    }

    public static function getLabel(){
        return "Less Than";
    }

    public static function getOrder(){
        return 6;
    }
}