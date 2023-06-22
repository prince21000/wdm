<?php

namespace app\admin\model\user;

use think\Model;
use traits\model\SoftDelete;

class DoumiMarket extends Model
{

    use SoftDelete;


    // 表名
    protected $name = 'user_doumi_market';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    protected $resultSetType = 'collection';

    // 追加属性
    protected $append = [
        'status_text',
        'audittime_text',
        'offshelftime_text'
    ];


    public function getStatusList()
    {
        return ['1' => __('Status 1'), '2' => __('Status 2'), '3' => __('Status 3'), '4' => __('Status 4')];
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getAudittimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['audittime']) ? $data['audittime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getOffshelftimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['offshelftime']) ? $data['offshelftime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setAudittimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setOffshelftimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    public function user()
    {
        return $this->belongsTo('app\admin\model\User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function admin()
    {
        return $this->belongsTo('app\admin\model\Admin', 'admin_id', 'id', [], 'LEFT')->setEagerlyType(0);
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
