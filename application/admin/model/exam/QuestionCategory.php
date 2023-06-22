<?php

namespace app\admin\model\exam;

use think\Model;
use traits\model\SoftDelete;
use app\admin\model\user\Answer as UserAnswer;

class QuestionCategory extends Model
{

    use SoftDelete;


    // 表名
    protected $name = 'exam_question_category';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    protected $resultSetType = 'collection';

    // 追加属性
    protected $append = [
        'level_text',
        'status_text'
    ];


    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }


    public function getLevelList()
    {
        return ['1' => __('Level 1'), '2' => __('Level 2'), '3' => __('Level 3')];
    }

    public function getStatusList()
    {
        return ['1' => __('Status 1'), '2' => __('Status 2')];
    }


    public function getLevelTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['level']) ? $data['level'] : '');
        $list = $this->getLevelList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    /**
     * 备考类型
     */
    public function type()
    {
        return $this->belongsTo('Type', 'exam_type_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * 父类
     */
    public function pcate()
    {
        return $this->belongsTo('QuestionCategory', 'pid', 'id', [], 'LEFT')->setEagerlyType(0);
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
