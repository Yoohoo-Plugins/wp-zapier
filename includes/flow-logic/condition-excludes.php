<?php 

namespace Yoohoo\WPZapier\FlowLogic;

class ConditionExcludes extends Condition{
    public function execute($a, $b){
        $a = "{$a}";
        $b = "{$b}";
        if(strpos($a, $b) === FALSE){
            return true;
        }
        return false;
    }

    public static function getLabel(){
        return "Excludes";
    }

    public static function getOrder(){
        return 4;
    }
}