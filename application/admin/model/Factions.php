<?php

namespace app\admin\model;

use app\admin\model\user\GoldcoinLog;
use think\Db;
use think\Model;
use traits\model\SoftDelete;

class Factions extends Model
{

    use SoftDelete;


    // 表名
    protected $name = 'factions';

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

    /**
     * 更新统计信息
     *
     * @param $id 派系ID
     * @param $goldcoin 金币数量
     * @param $user_num 新增用户数量
     * @return bool
     */
    public static function updateStatistical($id, $goldcoin, $user_num = 0)
    {
        $factions_ret = true;
        Db::startTrans();
        try {
            $factions = self::lock(true)->find($id);
            if ($factions) {
                $accumulative_goldcoin = bcadd($factions->accumulative_goldcoin, abs($goldcoin));
                $total_goldcoin = bcadd($factions->total_goldcoin, $goldcoin);
                $total_usernum = bcadd($factions->total_usernum, $user_num);
                $average_goldcoin = $total_usernum > 0 ? bcdiv($total_goldcoin, $total_usernum) : 0;
                //更新派系信息
                $factions_ret = $factions->save(['accumulative_goldcoin' => $accumulative_goldcoin, 'total_goldcoin' => $total_goldcoin, 'total_usernum' => $total_usernum, 'average_goldcoin' => $average_goldcoin]);
            }
            Db::commit();
        } catch (\Exception $e) {
            \think\Log::write(['factions_model_updateStatistical_err' => $e], 'log', true);
            Db::rollback();
        }
        return $factions_ret !== false;
    }

}
