<?php 

namespace Yoohoo\WPZapier\FlowLogic;

class ConditionDateBefore extends Condition{
    public function execute($a, $b){
        $a = strtotime("{$a}");
        $b = strtotime("{$b}");
        if($a < $b){
            return true;
        }
        return false;
    }

    public static function getLabel(){
        return "Before Date";
    }

    public static function getOrder(){
        return 7;
    }
}