<?php 

namespace Yoohoo\WPZapier\FlowLogic;

/* Simple helper for building flow related input fields, this allows for a small amount of caching and more modular code overall. Consider this a helper*/
class FlowFieldBuilder{
    public function __construct(){

    }

    public function argumentSelect($params = false, $nameSuffix = "a", $allowStatic = true){
        $params = array_merge(
            array(
                "name" => "flow_argument_{$nameSuffix}[]",
                "value" => false,
                "static_value" => ""
            ),
            !empty($params) && is_array($params) ? $params : array()
        );

        $params = (object) $params;

        $options = array(
            array("label" => "Select Field", "value" => "", "trigger" => "shared")
        );

        $arguments = $this->getArguments();
        if(!empty($arguments)){
            foreach($arguments as $trigger => $fields){
                if(!empty($fields) && is_array($fields)){
                    foreach($fields as $field => $label){
                        $options[] = array("label" => $label, "value" => "{$field}", "trigger" => $trigger);
                    }
                }
            }
        }


        if($allowStatic){
            $options[] = array("label" => "Enter Value", "value" => "static_value", "trigger" => "shared");
            $options[] = array("label" => "Enter Key", "value" => "static_key", "trigger" => "shared");
            $options[] = array("label" => "User Meta", "value" => "user_meta", "trigger" => "shared");
            $options[] = array("label" => "Post Meta", "value" => "post_meta", "trigger" => "shared");
        }

        $html = "<select name='{$params->name}' class='field-builder-argument-select'>";
        
        $activeOptGroup = false;
        $lastTrigger = false;
        if(!empty($options)){
            foreach($options as $option){
                $option = (object) $option;

                if($lastTrigger !== $option->trigger){
                    if(!empty($activeOptGroup)){
                        // Force it now, we are changing to a new trigger
                        // Close last optgroup 
                        $html .= "</optgroup>";
                        $activeOptGroup = false;
                    }
                }

                if(strpos($option->value, '.') !== FALSE){
                    $optGroup = explode('.', $option->value);
                    if(!empty($optGroup)){
                        $optGroup = $optGroup[0];

                        if($optGroup !== $activeOptGroup){
                            if(!empty($activeOptGroup)){
                                // Close last optgroup 
                                $html .= "</optgroup>";
                            }

                            $html .= "<optgroup label='" . ucwords(str_replace(array("_", "-"), " ", $optGroup)) . "' data-trigger='{$option->trigger}'>";

                            $activeOptGroup = $optGroup;
                        }
                    } else {
                        if(!empty($activeOptGroup)){
                            // Close last optgroup 
                            $html .= "</optgroup>";
                            $activeOptGroup = false;
                        }
                    }
                } else {
                    if(!empty($activeOptGroup)){
                        // Close last optgroup 
                        $html .= "</optgroup>";
                        $activeOptGroup = false;
                    }
                }

                if($option->value === 'static_value'){
                    $html .= "<optgroup label='Shared' data-trigger='shared'>";
                } 

                $lastTrigger = $option->trigger;
                $selected = $params->value === $option->value ? "selected" : "";
                $html .= "<option value='{$option->value}' data-trigger='{$option->trigger}' {$selected}>{$option->label}</option>";

                if ($option->value === 'post_meta'){
                    $html .= "</optgroup>";
                }
            }
        }

        $html .= "<select>";

        if($allowStatic){
            $html .= "<input type='text' name='flow_argument_static_{$nameSuffix}[]' class='field-builder-argument-static-input' value='{$params->static_value}' style='display:none;' placeholder='Enter value'/>";
        }

        return $html;
    }

    public function conditionSelect($params = false){
        $params = array_merge(
            array(
                "name" => "flow_condition[]",
                "value" => false,
            ),
            !empty($params) && is_array($params) ? $params : array()
        );

        $params = (object) $params;

        $options = array(
            array("label" => "Select Condition", "value" => "")
        );

        $conditions = $this->getConditions();
        if(!empty($conditions)){
            foreach($conditions as $type => $label){
                $options[] = array("label" => $label, "value" => $type);
            }
        }

        $html = "<select name='{$params->name}' class='field-builder-condition-select'>";
        
        if(!empty($options)){
            foreach($options as $option){
                $option = (object) $option;
                $selected = $params->value === $option->value ? "selected" : "";
                $html .= "<option value='{$option->value}' {$selected}>{$option->label}</option>";
            }
        }

        $html .= "<select>";

        return $html;
    }

    private function getConditions(){
        if(empty($this->_conditions)){
            $this->_conditions = Condition::getTypesMapped();
        }
        return $this->_conditions;
    }

    private function getArguments(){
        $arguments = apply_filters("wp_zapier_flow_logic_argument_filter", array());

        foreach($arguments as $key => $value){
            if(!is_array($value)){
                unset($arguments[$key]);
            }
        }
        return $arguments;
    }
}