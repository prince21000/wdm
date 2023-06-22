<?php

namespace app\admin\model\exam;

use think\Model;
use traits\model\SoftDelete;

class Type extends Model
{

    use SoftDelete;


    // 表名
    protected $name = 'exam_type';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'status_text'
    ];

    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }


    public function getStatusList()
    {
        return ['1' => __('Status 1'), '2' => __('Status 2')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    /**
     * 读取备考类型列表
     * @param string $status 指定状态
     * @param string $datatype 数据类型 1=一维数组
     * @return array
     */
    public static function getTypeArray($status = null, $datatype = null)
    {
        $list = collection(self::where(function ($query) use ($status) {
            if (!is_null($status)) {
                $query->where('status', '=', $status);
            }
        })->select())->toArray();
        if (!is_null($datatype)) {
            $typearr = [];
            foreach ($list as $k => $v) {
                $typearr[$v['id']] = $v['name'];
            }
            return $typearr;
        }
        return $list;
    }

    /**
     * @param $where 查询条件
     * @param $field 查询字段
     * @param $order 排序
     * @return array
     */
    public static function list($where = [], $field = '*', $order = 'weigh desc'): array
    {
        $data = self::field($field)->where($where)->order($order)->select();
        return $data;
    }


}
