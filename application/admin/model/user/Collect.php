<?php

namespace app\admin\model\user;

use think\Model;
use traits\model\SoftDelete;

class Collect extends Model
{

    use SoftDelete;


    // 表名
    protected $name = 'user_collect';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    protected $resultSetType = 'collection';

    // 追加属性
    protected $append = [
        'type_text'
    ];


    public function getTypeList()
    {
        return ['1' => __('Type 1'), '2' => __('Type 2'), '3' => __('Type 3')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function user()
    {
        return $this->belongsTo('app\admin\model\User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function question()
    {
        return $this->belongsTo('app\admin\model\exam\Question', 'detail_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function school()
    {
        return $this->belongsTo('app\admin\model\School', 'detail_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function material()
    {
        return $this->belongsTo('app\admin\model\Material', 'detail_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * @param $where 查询条件
     * @param $field 查询字段
     * @param $order 排序
     * @return array
     */
    public static function list($where = [], $field = '*', $order = 'createtime desc', $page = 1, $limit = 10): array
    {
        $data = self::field($field)->where($where)->order($order)->limit(($page - 1) * $limit, $limit)->select()->toArray();
        return $data;
    }

}
