<?php

namespace app\admin\model\school;

use think\Model;
use traits\model\SoftDelete;

class Campus extends Model
{

    use SoftDelete;


    // 表名
    protected $name = 'school_campus';

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


    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }

    public function school()
    {
        return $this->belongsTo('app\admin\model\School', 'school_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

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
