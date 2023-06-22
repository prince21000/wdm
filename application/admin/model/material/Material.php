<?php

namespace app\admin\model\material;

use think\Model;
use traits\model\SoftDelete;

class Material extends Model
{

    use SoftDelete;


    // 表名
    protected $name = 'material';

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

    /**
     * 备考类型
     */
    public function examtype()
    {
        return $this->belongsTo('app\admin\model\exam\Type', 'exam_type_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * 资料类型
     */
    public function materialtype()
    {
        return $this->belongsTo('app\admin\model\material\Type', 'material_type_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * @param $where 查询条件
     * @param $field 查询字段
     * @param $order 排序
     * @return array
     */
    public static function list($where = [], $field = '*', $order = 'weigh desc, createtime desc', $page = 1, $limit = 10): array
    {
        $data = self::field($field)->where($where)->order($order)->limit(($page - 1) * $limit, $limit)->select()->toArray();
        return $data;
    }

}
