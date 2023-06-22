<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Sensitivewords extends Model
{

    use SoftDelete;


    // 表名
    protected $name = 'sensitive_words';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    protected $resultSetType = 'collection';

    // 追加属性
    protected $append = [

    ];

    /**
     * @param $where 查询条件
     * @param $field 查询字段
     * @param $order 排序
     * @return array
     */
    public static function list($where = [], $field = '*', $order = 'weigh desc, createtime desc'): array
    {
        $data = self::field($field)->where($where)->order($order)->select()->toArray();
        return $data;
    }

}
