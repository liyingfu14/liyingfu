<?php
/**
 * Created by TianQi.
 * User: weblinuxgame
 * Email: 994685563@qq.com
 * Date: 2018-05-02
 * Time: 19:24
 */

$map =  [
    'Aliyun' => __DIR__,
    'app\\libs' => __DIR__
];

return spl_autoload_register(function($class = '' )use($map){
    if(empty($class)){
        return $class;
    }
    if(empty($map) && !is_array($class)){
        return $class;
    }
    $parse = function($baseDir = __DIR__ ,$class = null,$namespace){
        if(empty($baseDir) || empty($class) ){
            return null ;
        }
        $_class = str_replace('\\',DS ,$class);
        $file = $baseDir . DS . $_class .'.php';

        if(!file_exists($file)){
            $file = $baseDir . DS .str_replace($namespace,'',$class).'.php';
            $file = str_replace(DS.'\\',DS,str_replace(DS.DS,DS,$file));
            if(file_exists($file)){
               return $file ;
            }
            return null;
        }
        return $file ;
    };
    foreach ( $map as $namespace => $path ){
        $_namespace = str_replace('\\','\\\\',$namespace);
        $pattern = "/^$_namespace/" ;
        if(preg_match($pattern,$class)){
            $file = $parse($path,$class,$namespace);
            if(!empty($file)){
                require $file ;
            }
            break ;
        }
    }
    return $class;
});