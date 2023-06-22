<?php

namespace app\common\model;

use app\admin\model\Factions;
use app\common\library\Auth;
use fast\Random;
use think\Db;
use think\Log;
use think\Model;
use app\admin\model\user\GoldcoinLog;

/**
 * 会员模型
 */
class User extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
        'url',
    ];

    /**
     * 获取个人URL
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getUrlAttr($value, $data)
    {
        return "/u/" . $data['id'];
    }

    /**
     * 获取头像
     * @param string $value
     * @param array $data
     * @return string
     */
    public function getAvatarAttr($value, $data)
    {
        if (!$value) {
            //如果不需要启用首字母头像，请使用
            //$value = '/assets/img/avatar.png';
            $value = letter_avatar($data['nickname']);
        }
        return $value;
    }

    /**
     * 获取会员的组别
     */
    public function getGroupAttr($value, $data)
    {
        return UserGroup::get($data['group_id']);
    }

    /**
     * 获取验证字段数组值
     * @param string $value
     * @param array $data
     * @return  object
     */
    public function getVerificationAttr($value, $data)
    {
        $value = array_filter((array)json_decode($value, true));
        $value = array_merge(['email' => 0, 'mobile' => 0], $value);
        return (object)$value;
    }

    /**
     * 设置验证字段
     * @param mixed $value
     * @return string
     */
    public function setVerificationAttr($value)
    {
        $value = is_object($value) || is_array($value) ? json_encode($value) : $value;
        return $value;
    }

    /**
     * 变更会员余额
     * @param int $money 余额
     * @param int $user_id 会员ID
     * @param string $memo 备注
     */
    public static function money($money, $user_id, $memo)
    {
        Db::startTrans();
        try {
            $user = self::lock(true)->find($user_id);
            if ($user && $money != 0) {
                $before = $user->money;
                //$after = $user->money + $money;
                $after = function_exists('bcadd') ? bcadd($user->money, $money, 2) : $user->money + $money;
                //更新会员信息
                $user->save(['money' => $after]);
                //写入日志
                MoneyLog::create(['user_id' => $user_id, 'money' => $money, 'before' => $before, 'after' => $after, 'memo' => $memo]);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
        }
    }

    /**
     * 变更会员积分
     * @param int $score 积分
     * @param int $user_id 会员ID
     * @param string $memo 备注
     */
    public static function score($score, $user_id, $memo)
    {
        Db::startTrans();
        try {
            $user = self::lock(true)->find($user_id);
            if ($user && $score != 0) {
                $before = $user->score;
                $after = $user->score + $score;
                $level = self::nextlevel($after);
                //更新会员信息
                $user->save(['score' => $after, 'level' => $level]);
                //写入日志
                ScoreLog::create(['user_id' => $user_id, 'score' => $score, 'before' => $before, 'after' => $after, 'memo' => $memo]);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
        }
    }

    /**
     * 变更会员金币
     * @param int $goldcoin 金币
     * @param int $user_id 会员ID
     * @param int $type 类型:1=顺序刷题通关奖励,2=打卡奖励,3=推广奖励,4=斗米集市摆摊扣除,5=斗米集市查看联系方式扣除,6=查看藏经阁资料扣除,7=斗米集市摆摊审核驳回,8=优惠券兑换
     * @param int $detail_id 参数ID
     * @param string $memo 备注
     * @param string $signin 签到：1未变化2连续签到3重新开始
     */
    public static function goldcoin($goldcoin, $user_id, $type, $detail_id, $memo = '', $signin = 1)
    {
        $user_ret = true;
        $goldcoin_ret = true;
        Db::startTrans();
        try {
            $user = self::lock(true)->find($user_id);
            if ($user && $goldcoin != 0) {
//                $goldcoin_column_name = [1 => 'user_answer_id', 2 => 'user_clocking_record_id', 3 => 'user_share_id', 4 => 'user_doumi_market_id', 5 => 'user_doumi_market_id', 6 => 'material_id'];
                $before = $user->goldcoin;
                $after = $user->goldcoin + $goldcoin;
                $clock_days = $signin == 2 ? $user->clock_days + 1 : ($signin == 3 ? 1 : $user->clock_days);
                //更新会员信息
                $user_ret = $user->save(['goldcoin' => $after, 'clock_days' => $clock_days]);
                //写入日志
                $goldcoin_ret = GoldcoinLog::create(['type' => $type, 'user_id' => $user_id, 'goldcoin' => $goldcoin, 'before' => $before, 'after' => $after, 'detail_id' => $detail_id, 'memo' => $memo]);
                //更新派系表数据
                Factions::updateStatistical($user['factions_id'], $goldcoin);
            }
            Db::commit();
        } catch (\Exception $e) {
            \think\Log::write(['user_model_goldcoin_err' => $e], 'log', true);
            Db::rollback();
        }
        return $user_ret !== false && $goldcoin_ret !== false;
    }

    /**
     * 根据积分获取等级
     * @param int $score 积分
     * @return int
     *
     */
    public static function nextlevel($score = 0)
    {
        $lv = array(1 => 0, 2 => 30, 3 => 100, 4 => 500, 5 => 1000, 6 => 2000, 7 => 3000, 8 => 5000, 9 => 8000, 10 => 10000);
        $level = 1;
        foreach ($lv as $key => $value) {
            if ($score >= $value) {
                $level = $key;
            }
        }
        return $level;
    }

    /**
     * 所属派系
     */
    public function factions()
    {
        return $this->belongsTo('app\admin\model\Factions', 'factions_id', 'id')->field('name')->setEagerlyType(0);
    }

    /**
     * 所属派系
     */
    public function examtype()
    {
        return $this->belongsTo('app\admin\model\exam\Type', 'exam_type_id', 'id')->field('name')->setEagerlyType(0);
    }

    /**
     * 微信授权登录
     */
    public static function wxlogin($data)
    {
        //查询当前会员信息
        $userinfo = self::get(['wx_openid' => $data['openid']]);
        $time = time();
        $auth = new Auth();
        if (!$userinfo) {
            if (empty($data['nickName']) || empty($data['avatarUrl'])) {
                return ['code' => 0, 'msg' => '参数错误'];
            }
            if ($data['nickName'] == '微信用户') {
                return ['code' => 2, 'msg' => '请修改用户昵称'];
            }
            //用户不存在
            //新增会员
            $username = self::getUsername();
            $user_data = [
                'group_id' => 1,
//                'username' => $username,
                'nickname' => $data['nickName'],
//                'password' => $password,
//                'salt' => $salt,
                'avatar' => $data['avatarUrl'],
                'gender' => $data['gender'],
                'joinip' => request()->ip(),
                'jointime' => $time,
                'createtime' => $time,
                'status' => 'normal',
                'wx_openid' => $data['openid'],
            ];
            //如果存在父级ID
            if (isset($data['pid']) && !empty($data['pid'])) {
                //查询当前父级ID是否存在
                $puser = self::get($data['pid']);
                Log::write(['wxlogin_puser' => $puser], 'log', true);
                if ($puser) {
                    $user_data['pid'] = $data['pid'];
                }
            }
            $auth_ret = $auth->register($username, '123456', '', '', $user_data);
            $ret_msg = '注册';
        } else {
            //用户已经存在
            $auth_ret = $auth->direct($userinfo['id']);
            $ret_msg = '登录';
        }
        return $auth_ret ? ['code' => 1, 'msg' => $ret_msg . '成功', 'data' => $auth->getUserinfo()] : ['code' => 0, 'msg' => $ret_msg . '失败'];
    }

    /**
     * 获取用户名
     */
    public static function getUsername()
    {
        $username = 'st_' . rand(100000, 999999);
        if (self::where(['username' => $username])->count()) {
            $username = self::getUsername();
        }
        return $username;
    }

}
