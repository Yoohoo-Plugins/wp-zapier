<?php 

namespace Yoohoo\WPZapier\FlowLogic;

abstract class Condition{
    abstract public function execute($a, $b);
    abstract static public function getLabel();

    public static function getOrder(){
        return 99;
    }

    public static function getNamespace(){
        return "Yoohoo\\WPZapier\\FlowLogic\\";
    }

    public static function getClassFromBasename($class){
        return Condition::getNamespace() . $class;
    }

    public static function getTypes(){
        $result = array();

        $namespace = Condition::getNamespace();
        foreach (get_declared_classes() as $class) {
            if (is_subclass_of($class, "{$namespace}Condition")){
                $result[] = str_replace($namespace, "", $class);
            }
        }
        return $result;
    }

    public static function getTypesMapped(){
		$conditons = array();
        $conditionTypes = Condition::getTypes();
		foreach($conditionTypes as $type){
			$class = Condition::getClassFromBasename($type);
			$label = $class::getLabel();

			$conditons[$type] = $label;
		}

        /* Sort */
        uksort($conditons, array(self::class, 'compareTypeOrder'));

        return $conditons;
    }

    public static function compareTypeOrder($a, $b){
        $classA = Condition::getClassFromBasename($a);
        $classB = Condition::getClassFromBasename($b);
		return $classA::getOrder() < $classB::getOrder() ? -1 : 1;
    }

    public static function validateClassFromBasename($class){
        if(class_exists(Condition::getClassFromBasename($class))){
            return true;
        }
        return false;
    }
}