<?php

namespace app\common\model;

use think\Model;


class Base extends Model
{

    const FIELDS_CACHE_TIME = 1880;
    const APP_AREA_KEY = 'app';
    const ADMIN_AREA_KEY = 'admin';
    const EXCLUDED_KEY = '!';


    // 数据自动完成
    protected $auto = [
         'create_at', 'update_at',
    ];

    /**
     * @var array 作用域 只返回 对应 字段
     */
    protected $area = [];

    protected function setCreateAt()
    {
        return time();
    }

    protected function setUpdateAt()
    {
        return time();
    }

    /**
     * 返回作用域
     * @return array
     */
    public function _getArea()
    {
        if (empty($this->area)) {
            $this->area = [self::ADMIN_AREA_KEY => [], self::APP_AREA_KEY => []];
        }
        return $this->area;
    }

    /**
     * 设置作用 域 | 添加 作用域
     * @param $attr
     * @param string $range
     * @return bool
     */
    public function _setArea($attr, $range = self::APP_AREA_KEY)
    {
        if (empty($this->area[$range])) {
            $this->area[$range] = $attr;
            return true;
        }
        if (is_array($attr)) {
            $this->area[$range] = array_merge($this->area[$range], $attr);
            return true;
        }
        array_push($this->area[$range], $attr);
    }

    /**
     * 查询 获取字段 过滤
     * @param array $attr
     * @return $this
     */
    public function filter($attr = [])
    {
        $_attr = $this->cache(true, self::FIELDS_CACHE_TIME)->getTableInfo($this->table, 'fields');
        $_area = array_keys($this->_getArea());
        if (empty($attr)) {
            return $this->field(true);
        }
        if (is_string($attr)) {
            $key = str_replace(self::EXCLUDED_KEY, '', $attr);
            $flag = false;
            if (in_array($key, $_attr)) {
                $attr = [$attr];
                $flag = true;
            }
            if (in_array($key, $_area)) {
                $attr = $this->area[$key];
                $flag = true;
            }
            if (!$flag) {
                $attr = [];
            }
        }
        $field = [];
        $except = [];
        foreach ($attr as $value) {
            if (preg_match('/^' . self::EXCLUDED_KEY . '/', $value) && in_array(str_replace(self::EXCLUDED_KEY, '', $value), $_attr)) {
                array_push($except, $value);
                continue;
            }
            if (in_array($value, $_attr)) {
                array_push($field, $value);
                continue;
            }
        }
        if (empty($field)) {
            $field = true;
        } else {
            $field = implode(',', $field);
        }
        if (empty($except)) {
            $except = false;
        } else {
            $except = implode(',', $except);
        }
        return $this->field($field, $except);
    }
}