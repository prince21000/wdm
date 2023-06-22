<?php

namespace app\admin\model\user;

use think\Model;
use traits\model\SoftDelete;

class AnswerQuestion extends Model
{

    use SoftDelete;


    // 表名
    protected $name = 'user_answer_question';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    protected $resultSetType = 'collection';

    // 追加属性
    protected $append = [
        'result_text',
        'answer_time_text',
        'status_text'
    ];


    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }

    public function getResultList()
    {
        return ['1' => __('Result 1'), '2' => __('Result 2')];
    }

    public function getStatusList()
    {
        return ['1' => __('Status 1'), '2' => __('Status 2')];
    }

    public function getResultTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['result']) ? $data['result'] : '');
        $list = $this->getResultList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getAnswerTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['answer_time']) ? $data['answer_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setAnswerTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    public function user()
    {
        return $this->belongsTo('app\admin\model\User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * @param $where 查询条件
     * @param $field 查询字段
     * @param $order 排序
     * @return array
     */
    public static function list($where = [], $field = '*', $order = 'weigh desc,createtime desc'): array
    {
        $data = self::field($field)->where($where)->order($order)->select()->toArray();
        return $data;
    }

}
