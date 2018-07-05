<?php
/**
 * Created by TianQi.
 * User: weblinuxgame
 * Email: 994685563@qq.com
 * Date: 2018-04-24
 * Time: 20:16
 */

namespace app\common\lib;

use think\Request;

class Generator
{
     protected $wares = [];
     public $error = null ;
    /**
     * @var Request
     */
     protected  $request ;
     public function __construct($class = [], $request)
     {
            $this->wares = $class ;
            $this->request = $request;
     }

     public function next()
     {
          foreach ( $this->wares as $ware ) {

                try{
                    $class = $ware['class'];
                    echo $class . PHP_EOL;
                    $Ware = new $class();
                    $action = $ware['action'];

                    if(is_array($action)){
                        foreach ( $action as $v ){
                              if( method_exists($Ware,$v) && ($result = $Ware->$v($this->request)) ){
                                    yield  $result;
                              }else{
                                    $this->error = 'class :: '.$class.' method '.$v.' not exits';
                                    yield $this;
                              }
                        }
                        continue ;
                    }
                    if( is_string($action) && method_exists($Ware,$action) && ($result = $Ware->$action($this->request))){
                        yield $result ;
                        continue ;
                    }
                    $this->error = 'class :: '.$class.' method '.$v.' not exits';
                    yield $this;

                }catch (\Exception $e){
                    $this->error = $e ;
                    break;
                }
          }
     }

}
