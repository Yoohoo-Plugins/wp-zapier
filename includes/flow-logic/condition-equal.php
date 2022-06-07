<?php 

namespace Yoohoo\WPZapier\FlowLogic;

class ConditionEqual extends Condition{
    public function execute($a, $b){
        if($a == $b){
            return true;
        }
        return false;
    }

    public static function getLabel(){
        return "Equals";
    }

    public static function getOrder(){
        return 1;
    }
}