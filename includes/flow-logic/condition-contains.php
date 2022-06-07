<?php 

namespace Yoohoo\WPZapier\FlowLogic;

class ConditionContains extends Condition{
    public function execute($a, $b){
        $a = "{$a}";
        $b = "{$b}";
        if(strpos($a, $b) !== FALSE){
            return true;
        }
        return false;
    }

    public static function getLabel(){
        return "Contains";
    }

    public static function getOrder(){
        return 3;
    }
}