<?php 

namespace Yoohoo\WPZapier\FlowLogic;

class ConditionNotEqual extends Condition{
    public function execute($a, $b){
        if($a != $b){
            return true;
        }
        return false;
    }

    public static function getLabel(){
        return "Not Equals";
    }

    public static function getOrder(){
        return 2;
    }
}