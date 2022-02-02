<?php

use JetBrains\PhpStorm\NoReturn;

if(!function_exists('object_to_array')) {
    function object_to_array($obj)
    {
        //  only process if it's an object or array being passed to the function
        if(is_object($obj) || is_array($obj)) {
            $ret = (array)$obj;

            foreach($ret as &$item) {
                //  recursively process EACH element regardless of type
                $item = object_to_array($item);
            }

            return $ret;
        } else {
            //  otherwise, (i.e. for scalar values) return without modification
            return $obj;
        }
    }
}

if(!function_exists('dump_json')) {
    #[NoReturn]
    function dump_json(...$vars)
    {
        echo "<pre>";
        print_r($vars);
        die;
    }
}

if(!function_exists('base_64_url_encode')) {
    function base_64_url_encode($text): array|string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($text));
    }
}
