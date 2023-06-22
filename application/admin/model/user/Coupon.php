<?php

namespace app\admin\model\user;

use think\Model;
use traits\model\SoftDelete;

class Coupon extends Model
{

    use SoftDelete;


    // 表名
    protected $name = 'user_coupon';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    protected $resultSetType = 'collection';

    // 追加属性
    protected $append = [
        'discount_way_text',
        'starttime_text',
        'endtime_text',
        'status_text',
        'usetime_text'
    ];


    public function getDiscountWayList()
    {
        return ['1' => __('Discount_way 1'), '2' => __('Discount_way 2'), '3' => __('Discount_way 3')];
    }

    public function getStatusList()
    {
        return ['1' => __('Status 1'), '2' => __('Status 2')];
    }

    public function getDownloadStatusList()
    {
        return ['1' => __('Download_status 1'), '2' => __('Download_status 2')];
    }

    public function getDiscountWayTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['discount_way']) ? $data['discount_way'] : '');
        $list = $this->getDiscountWayList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getStarttimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['starttime']) ? $data['starttime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getEndtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['endtime']) ? $data['endtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getDownloadStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getDownloadStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getUsetimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['usetime']) ? $data['usetime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setStarttimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setEndtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setUsetimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    public function user()
    {
        return $this->belongsTo('app\admin\model\User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function owningcoupon()
    {
        return $this->belongsTo('app\admin\model\Coupon', 'coupon_id', 'id', [], 'LEFT')->setEagerlyType(0);
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
