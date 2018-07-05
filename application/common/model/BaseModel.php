<?php
/**
 * Created by TianQi.
 * User: weblinuxgame
 * Email: 994685563@qq.com
 * Date: 2018-05-09
 * Time: 11:42
 */

namespace app\common\model;

use app\common\lib\Utils;
use think\Log;
use think\Model;
use think\Config;

class BaseModel extends Model
{

    // 查询 限定 fields 字段 配置
    protected $area = [];

    // 使用其他 数据库链接 配置
    protected $otherConfigFile = null;
    // 配置 作用域名
    protected $otherRangeName = null;
    // 其他配置链接 信息
    protected $otherConfig = null;

    /**
     * BaseModel constructor.
     * @param array $data
     * @throws \Exception
     */
    public function __construct($data = [])
    {
        parent::__construct($data);
        if (!empty($this->otherConfigFile) && !empty($this->otherRangeName)) {
            $this->otherConnect($this->otherConfigFile, $this->otherRangeName);
        }
    }

    // 设置 其他连接
    public function setOther($file, $rang)
    {
        if (empty($file) || empty($rang)) {
            return false;
        }
        if (IS_WIN) {
            $file = str_replace('/', DS, $file);
        } else {
            $file = str_replace('\\', DS, $file);
        }
        if (file_exists($file)) {
            $this->otherConfigFile = $file;
            $this->otherRangeName = $rang;
            return true;
        }
        $flag = strpos($file, CONF_PATH);
        if ($flag >= 0 && !is_bool($flag)) {
            return false;
        }
        $file = CONF_PATH . $file;
        if (file_exists($file)) {
            $this->otherConfigFile = $file;
            $this->otherRangeName = $rang;
            return true;
        }
        return false;
    }

    /**
     * @param $file
     * @param $range
     * @return BaseModel
     * @throws \Exception
     */
    protected function otherConnect($file, $range)
    {
        if (!$this->setOther($file, $range)) {
            throw  new \Exception('error config not find!');
        }
        // $_config = Config::get('database');
        $config = Config::load($this->otherConfigFile, $this->otherRangeName);

        // dump($config);
        $this->otherConfig = $config;
        Log::log('[ set other connect for model ] :: ' . json_encode($this->otherConfig));
       return $this->connect($config);
    }

    // 动态获取查询 限制 字段 并 转换 成 用户 逗号分隔
    protected function getQueryField($name)
    {
        if (empty($name)) {
            return true;
        }
        if (strpos($name, '::') >= 0) {
            list($_, $name) = explode('::', $name);
        }
        $fields = true;
        if (isset($this->area) && isset($this->area[$name])) {
            $fields = $this->area[$name];
        }
        if (empty($fields) || $fields === true) {
            return true;
        }
        if (is_array($fields)) {
            if (count($fields) === 1) {
                return $fields[0];
            }
            return implode(',', $fields);
        }

        return true;
    }

    protected function afterSelect(&$Sets = [])
    {
        if (empty($Sets)) {
            return $Sets;
        }
        $data = [];
        try {
            if (is_array($Sets)) {
                foreach ($Sets as $item) {
                    if (!empty($item) && $item instanceof Model) {
                        array_push($data, $item->toArray());
                    }
                }
                return $data;
            }
        } catch (\Exception $e) {
            Log::error(__FILE__.'  at line '.__LINE__.' '.Utils::exportError($e));
        }
        return $Sets;
    }


    protected function insertData($data,$field,$all = true)
    {
        $info = [];
        $fields = [];
        if(is_array($field)){
            $fields = $field ;
        }
        if(is_string($field) && isset($this->area[$field])){
            $fields = $this->area[$field];
        }
        foreach ($fields as $v) {
            if (empty($v)) {
                continue;
            }
            if (!isset($data[$v]) && $all) {
                return false;
            }
            if(isset($data[$v])){
                $info[$v] = $data[$v];
            }
        }
        return $info;
    }

}