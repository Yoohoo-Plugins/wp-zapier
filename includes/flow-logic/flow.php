<?php 

namespace Yoohoo\WPZapier\FlowLogic;
#[\AllowDynamicProperties]

class Flow {
    public function __construct($conditions, $data){
        $this->conditions = $conditions;
        $this->data = $data;
    }

    public function execute(){
        $success = true;
        if(!empty($this->conditions)){
            foreach($this->conditions as $condition){
                $arguments = $this->parseArguments($condition);
                if(is_object($arguments)){
                    if(Condition::validateClassFromBasename($condition['condition'])){
                        $classname = Condition::getClassFromBasename($condition['condition']);
                        $branch = new $classname();

                        if(!$branch->execute($arguments->a, $arguments->b)){
                            /* This part of the logic branch has failed - Stop early */
                            $success = false;
                            return $success;
                        }
                    } else {
                        /* The class doesn't exist */
                        $success = false;
                    }
                } else {
                    /* Arg parse failed, assume the flow failed */
                    $success = false;
                }
            }
        } else {
            /* Conditions set, but nothing here, leave it */
            $success = false;
        }
        return $success;
    }

    public function parseArguments($condition){
        if(!empty($condition)){
            $condition = (object) $condition;
            if(!empty($condition->argument_a) && !empty($condition->argument_b)){
                $arguments = (object) array(
                    "a" => false,
                    "b" => false
                );

                foreach($arguments as $key => $value){
                    $dynamicKey = "argument_{$key}";
                    $staticKey = "static_{$key}";
                    if($condition->{$dynamicKey} === 'static_value' && isset($condition->{$staticKey})){
                        // Using static value
                        $arguments->{$key} = $condition->{$staticKey};
                    } else {
                        if($condition->{$dynamicKey} === 'static_key' && !empty($condition->{$staticKey})){
                            // Custom key was passed, which can be used to access any key not added by filter (including custom sets)
                            $condition->{$dynamicKey} = $condition->{$staticKey};
                        } else if($condition->{$dynamicKey} === 'user_meta' && !empty($condition->{$staticKey})){
                            $metaKey = $condition->{$staticKey};

                            $userId = false;
                            if(!empty($this->data['user_id'])){
                                $userId = intval($this->data['user_id']);
                            } else if(!empty($this->data['user']) && !empty($this->data['user']['id'])){
                                $userId = intval($this->data['user']['id']);
                            } else if(!empty($this->data['id'])){
                                $userId = intval($this->data['id']);
                            }

                            if(!empty($userId)){
                                $metaValue = get_user_meta($userId, $metaKey, true);
                                if(!empty($metaValue)){
                                    $arguments->{$key} = $metaValue;
                                    continue;
                                }
                            }
                        } else if($condition->{$dynamicKey} === 'post_meta' && !empty($condition->{$staticKey})){
                            $metaKey = $condition->{$staticKey};

                            $postId = false;
                            if(!empty($this->data['post_id'])){
                                $postId = intval($this->data['post_id']);
                            } else if(!empty($this->data['post']) && !empty($this->data['post']['id'])){
                                $postId = intval($this->data['post']['id']);
                            } else if(!empty($this->data['id'])){
                                $postId = intval($this->data['id']);
                            }

                            if(!empty($postId)){
                                $metaValue = get_post_meta($postId, $metaKey, true);
                                if(!empty($metaValue)){
                                    $arguments->{$key} = $metaValue;
                                    continue;
                                }
                            }
                        }

                        if(isset($this->data[$condition->{$dynamicKey}])){
                            // Passed by preset key as defined in filters
                            $arguments->{$key} = $this->data[$condition->{$dynamicKey}];
                        } else {
                            if(strpos($condition->{$dynamicKey}, '.') !== FALSE){
                                // We have a single depth sub selection
                                $splitKey = explode('.', $condition->{$dynamicKey});
                                if(!empty($splitKey) && count($splitKey) >= 2){
                                    $sampler = $this->data[$splitKey[0]];
                                    if(!is_array($sampler)){
                                        $sampler = (array) $sampler;
                                    }

                                    if(is_array($sampler) && isset($sampler[$splitKey[1]])){
                                        $arguments->{$key} = $sampler[$splitKey[1]];
                                    }
                                }
                            }
                        }
                    }
                }

                return $arguments;
            }
        }
        return false;
    }
}