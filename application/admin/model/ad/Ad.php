<?php

namespace app\admin\model\ad;

use think\Model;
use traits\model\SoftDelete;

class Ad extends Model
{

    use SoftDelete;


    // 表名
    protected $name = 'ad';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    protected $resultSetType = 'collection';

    // 追加属性
    protected $append = [
        'type_text',
        'status_text'
    ];


    public function getTypeList()
    {
        return ['1' => __('Type 1'), '2' => __('Type 2'), '3' => __('Type 3'), '4' => __('Type 4'), '5' => __('Type 5')];
    }

    public function getStatusList()
    {
        return ['1' => __('Status 1'), '2' => __('Status 2')];
    }

    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function category()
    {
        return $this->belongsTo('Category', 'ad_category_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function school()
    {
        return $this->belongsTo('app\admin\model\School', 'detail_id', 'id', [], 'LEFT')->setEagerlyType(0);
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
