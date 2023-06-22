<?php

namespace app\api\controller;

use app\admin\model\user\GoldcoinLog;
use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use think\Config;
use think\Cache;
use think\Log;
use think\Validate;
use app\admin\model\Factions;
use app\admin\model\exam\Type as Examtype;
use app\common\model\User as Usermodel;
use app\common\model\Config as ConfigModel;
use app\admin\model\Coupon;
use app\admin\model\user\AnswerQuestion;
use app\admin\model\user\Collect;
use app\admin\model\user\Report;
use app\admin\model\user\Coupon as UserCoupon;
use PosterMaker\PosterMaker;

/**
 * 会员接口
 */
class User extends Api
{
    protected $noNeedLogin = ['login', 'mobilelogin', 'register', 'resetpwd', 'changeemail', 'changemobile', 'third', 'wxlogin'];
    protected $noNeedRight = '*';
    protected $wx_appid = ''; //微信APPID
    protected $wx_secret = ''; //微信secret
    protected $pagelimit = 20; //每页条数
    protected $content_length = 200; //内容字数限制

    public function _initialize()
    {
        parent::_initialize();

        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'));
        }

        $payconfig = config('wxali.wx')['xcx'];
        $this->wx_appid = $payconfig['appid'];
        $this->wx_secret = $payconfig['appsecret'];
    }

    /**
     * 会员中心
     */
    public function index()
    {
        //查询当前用户信息
        $user_id = $this->auth->id;
        //查询用户信息
        $user = Usermodel::field('id,nickname,avatar,school,factions_id,major,goldcoin,total_answer_num,correct_answer_num,wrong_answer_num,exam_time,answer_time,accuracy,is_student')->with(['factions', 'examtype'])->find(['id' => $user_id]);
        $user['avatar'] = imgAppendUrl('string', $user['avatar'], ['avatar']);
        $user['description'] = $user['school'] . '·' . $user['major'] . '·' . $user['factions']['name'];
        //查询系统配置老师微信二维码
        $qrcode = ConfigModel::where(['name' => 'exam_teacher_wechat_qrcode'])->value('value');
        $user['qrcode'] = imgAppendUrl('string', $qrcode, '');
        $this->success('', $user);
    }

    /**
     * 会员登录
     *
     * @ApiMethod (POST)
     * @param string $account 账号
     * @param string $password 密码
     */
    public function login()
    {
        $account = $this->request->post('account');
        $password = $this->request->post('password');
        if (!$account || !$password) {
            $this->error(__('Invalid parameters'));
        }
        $ret = $this->auth->login($account, $password);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 退出登录
     * @ApiMethod (POST)
     */
    public function logout()
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        $this->auth->logout();
        $this->success(__('Logout successful'));
    }

    /**
     * 修改会员个人信息
     *
     * @ApiMethod (POST)
     * @param string $avatar 头像地址
     * @param string $username 用户名
     * @param string $nickname 昵称
     * @param string $bio 个人简介
     */
    public function profile()
    {
        $user_id = $this->auth->id;
        if ($this->request->isGet()) {
            //查询当前用户资料
            $user = Usermodel::field('id,nickname,avatar,school,major,factions_id,exam_type_id,address,mobile')->with([
                'factions' => function ($query) {
                    $query->withField('id,name');
                },
                'examtype' => function ($query) {
                    $query->withField('id,name');
                }
            ])->find($user_id);
            $user['avatar_fullpath'] = imgAppendUrl('string', $user['avatar'], '');
            $this->success('请求成功', $user);
        } elseif ($this->request->isPost()) {
            $param['avatar'] = $this->request->post('avatar', '', 'trim,strip_tags,htmlspecialchars');
            $param['nickname'] = $this->request->post('nickname');
            $param['school'] = $this->request->post('school');
            $param['major'] = $this->request->post('major');
            $param['exam_type_id'] = $this->request->post('exam_type_id');
            // $param['mobile'] = $this->request->post('mobile');
            //定义验证规则
            $validate_rule = [
                'avatar' => 'require',
                'nickname' => 'require|length:1,12',
                'school' => 'require|length:1,20',
                'major' => 'require|length:1,20',
                'exam_type_id' => 'require',
            ];
            //定义错误信息
            $validate_message = [
                'avatar.require' => '头像不能为空',
                'nickname.require' => '昵称不能为空',
                'nickname.length' => '昵称长度需在1-20个字符之间',
                'school.require' => '院校不能为空',
                'school.length' => '院校长度需在1-20个字符之间',
                'major.require' => '专业不能为空',
                'major.length' => '专业长度需在1-20个字符之间',
                'exam_type_id.require' => '详细介绍不能为空',
            ];
            //验证参数
            $validate_check = $this->validate($param, $validate_rule, $validate_message);
            if ($validate_check !== true) {
                $this->error($validate_check);
            }
            //更新用户资料
            $ret = Usermodel::update($param, ['id' => $user_id]);
            if ($ret !== false) {
                $this->success('修改成功');
            } else {
                $this->error('修改失败');
            }
        }
    }

    /**
     * 微信小程序登录
     */
    public function wxlogin()
    {
        $code = $this->request->post('code');
        $data['nickName'] = $this->request->post('nickName', '');
        $data['avatarUrl'] = $this->request->post('avatarUrl', '');
        $data['gender'] = $this->request->post('gender', 0);
        $data['pid'] = (int)$this->request->post('pid');
        if (empty($code)) {
            $this->error('参数错误');
        }
        $param['appid'] = $this->wx_appid;
        $param['secret'] = $this->wx_secret;
        //小程序登录的id
        $param['js_code'] = $code;
        $param['grant_type'] = 'authorization_code';
        $http_key = httpCurl('https://api.weixin.qq.com/sns/jscode2session', $param, 'GET');
        $session_key = json_decode($http_key, true);
        Log::write(['session_key' => $session_key], 'log', true);
        if (!empty($session_key['session_key'])) {
            $data['openid'] = $session_key['openid'];
            $ret = Usermodel::wxlogin($data);
            if ($ret['code'] == 1) {
                $this->success($ret['msg'], $ret['data']);
            } else {
                $this->error($ret['msg'], null, $ret['code']);
            }
        } else {
            $this->error('获取session_key失败');
        }
    }

    /**
     * 获取微信手机号
     *
     * @param $code 微信code
     */
    public function wxmobile()
    {
        $param['code'] = $this->request->post('code');
        $user_id = $this->auth->id;
        if (empty($param['code'])) {
            $this->error('参数错误');
        }
        //获取accesstoken
        $access_token = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token=' . $access_token;
        $http_key = httpCurl($url, json_encode($param), 'POST', [], true);
        $session_key = json_decode($http_key, true);
        Log::write(['wxmobile_ret' => $session_key], 'log', true);
        if ($session_key['errcode'] == 0) {
            $mobile = $session_key['phone_info']['phoneNumber'];
            //更新用户手机号
            Usermodel::update(['mobile' => $mobile], ['id' => $user_id]);
            $this->success('获取成功', $mobile);
        } else {
            $this->error('获取失败');
        }
    }

    /**
     * 获取小程序二维码的token
     */
    private function get_access_token()
    {
        //先判断缓存里面的access_token过期了没有
        if (Cache::get('access_token')) {
            //没过期直接拿出来用
            return Cache::get('access_token');
        } else {
            //过期了就重新获取
            $param['grant_type'] = 'client_credential';
            $param['appid'] = $this->wx_appid;
            $param['secret'] = $this->wx_secret;
            $url = "https://api.weixin.qq.com/cgi-bin/token";
            //请求接口，获取accesstoken
            $user_obj = json_decode(httpCurl($url, $param, 'GET'), true);
            Log::write(['get_access_token_ret' => $user_obj], 'log', true);
            //然后将accesstoken存入缓存里面，官方过期时间7200秒，缓存里面可以过期的早一点，自己把控
            Cache::set('access_token', $user_obj['access_token'], 7100);

            return Cache::get('access_token');
        }
    }

    /**
     * 完善资料
     */
    public function perfectinfo()
    {
        if ($this->request->isPost()) {
            //获取参数
            $param['school'] = $this->request->post('school');
            $param['factions_id'] = $this->request->post('factions_id');
            $param['major'] = $this->request->post('major');
            $param['address'] = $this->request->post('address');
            $param['longitude'] = $this->request->post('longitude');
            $param['latitude'] = $this->request->post('latitude');
            $param['exam_type_id'] = $this->request->post('exam_type_id');
            $param['exam_time'] = $this->request->post('exam_time');
            if (empty($param['school']) || empty($param['factions_id']) || empty($param['major']) || empty($param['address']) || empty($param['exam_type_id']) || empty($param['exam_time'])) {
                $this->error('参数错误');
            }
            //查询派系信息
            $factions = Factions::get(['id' => $param['factions_id'], 'status' => 1]);
            if (empty($factions)) {
                $this->error('派系不存在');
            }
            //查询备考类型信息
            $exam_type = Examtype::get(['id' => $param['exam_type_id'], 'status' => 1]);
            if (empty($exam_type)) {
                $this->error('备考类型不存在');
            }
            $param['exam_time'] = strtotime($param['exam_time']);
            if ($param['exam_time'] <= time()) {
                $this->error('考试时间必须大于当前时间');
            }
            $param['is_perfect'] = 2;
            //查询当前用户信息
            $user = Usermodel::get($this->auth->id);
            //更新用户信息
            $ret = Usermodel::update($param, ['id' => $user['id']]);
            if($user['pid']){
                //查询推广奖励金币数量
                $popularize_reward_goldcoin = ConfigModel::where(['name' => 'popularize_reward_goldcoin'])->value('value');
                Usermodel::goldcoin($popularize_reward_goldcoin, $user['pid'], 3, $user['id'], '用户' . $user['nickname'] . '完善信息获得推广奖励');
            }
            if ($ret !== false) {
                //更新派系表数据
                Factions::updateStatistical($param['factions_id'], 0, 1);
                $this->success('完善成功');
            } else {
                $this->error('完善失败');
            }
        }
    }

    /**
     * 我的收藏
     *
     * @param $type 类型:1=院校,2=习题,3=资料
     * @param $pgae 当前页数
     */
    public function collects()
    {
        //获取参数
        $type = $this->request->get('type', 1);
        $page = $this->request->get('page', 1);
        $limit = $this->pagelimit;
        $user_id = $this->auth->id;
        $where = ['type' => $type, 'user_id' => $user_id];
        switch ($type) {
            case 1:
                $with = ['school' => function ($query) {
                    $query->withField('name,logo');
                    return 'third';
                }];
                break;
            case 2:
                $with = ['question' => function ($query) {
                    $query->withField('title');
                }];
                break;
            case 3:
                $with = ['material' => function ($query) {
                    $query->withField('image,title,description,purchase_goldcoin');
                }];
                break;
        }
        //查询用户收藏记录
        $collects = Collect::field('id,type,detail_id,createtime')->with($with)->where($where)->order('createtime desc')->limit(($page - 1) * $limit, $limit)->select()->toArray();
        $data['collects'] = [];
        if (!empty($collects)) {
            $data['collects'] = array_map(function ($item) {
                //处理图片
                if (isset($item['school']) && isset($item['school']['logo'])) {
                    $item['school']['logo'] = imgAppendUrl('string', $item['school']['logo'], '');
                }
                if (isset($item['material']) && isset($item['material']['image'])) {
                    $item['material']['image'] = imgAppendUrl('string', $item['material']['image'], '');
                }
                return $item;
            }, $collects);
        }
        $this->success('请求成功', $data);
    }

    /**
     * 珍藏习题详情
     *
     * @param $collect_id 收藏记录ID
     */
    public function collectquestion()
    {
        //获取参数
        $collect_id = (int)$this->request->get('collect_id');
        $user_id = $this->auth->id;
        //查询收藏信息
        $data['collect'] = Collect::where(['id' => $collect_id, 'type' => 2, 'user_id' => $user_id])->find();
        if (empty($data['collect'])) {
            $this->error('珍藏习题不存在');
        }
        //处理题目选项
        $data['collect']['option_arr'] = json_decode($data['collect']['option'], true);
        //判断该用户是否回答过该题目
        $is_answer = AnswerQuestion::where(['user_id' => $user_id, 'exam_question_id' => $data['collect']['detail_id'], 'status' => 2])->count();
        $data['collect']['is_answer'] = $is_answer ? 2 : 1; //是否回答过 1未回答2已回答
        //查询系统配置中联系老师微信二维码及说明
        $data['exam_teacher_wechat_qrcode'] = imgAppendUrl('string', ConfigModel::where(['name' => 'exam_teacher_wechat_qrcode'])->value('value'), '');
        $data['exam_contact_teacher_desc'] = ConfigModel::where(['name' => 'exam_contact_teacher_desc'])->value('value');
        $this->success('请求成功', $data);
    }

    /**
     * 举报
     *
     * @param $content 举报内容
     * @param $images 举报图片
     */
    public function report()
    {
        //获取参数
        $param['content'] = $this->request->post('content');
        $param['images'] = $this->request->post('images/a');
        $param['user_id'] = $this->auth->id;
        if (empty($param['content'])) {
            $this->error('参数错误');
        }
        if (mb_strlen($param['content']) > $this->content_length) {
            $this->error('字符长度不能超过' . $this->content_length);
        }
        $param['images'] = $param['images'] ? implode(',', $param['images']) : null;
        $ret = Report::create($param);
        if ($ret !== false) {
            $this->success('举报成功');
        } else {
            $this->error('举报失败');
        }
    }

    /**
     * 我的金币（可兑换优惠券列表）
     *
     * @param $page 当前页数
     */
    public function mygoldcoin()
    {
        //获取参数
        $page = $this->request->get('page', 1);
        $limit = $this->pagelimit;
        $user_id = $this->auth->id;
        //查询当前用户金币数量
        $data['goldcoin'] = Usermodel::where(['id' => $user_id])->value('goldcoin');
        //查询可兑换的优惠券列表
        $couponlist = Coupon::list('status=1 and surplus_num>0 and ((timelimit_type=1)  OR ( timelimit_type=2 and endtime>=' . time() . '))', '*', 'weigh desc, createtime desc', $page, $limit);
        $data['couponlist'] = array_map(function ($item) {
            return ['id' => $item['id'], 'discount_way' => $item['discount_way'], 'name' => $item['name'], 'enough' => $item['enough'], 'deduct' => $item['deduct'], 'discount' => $item['discount'], 'exchange_goldcoin' => $item['exchange_goldcoin'], 'enough_desc' => $item['enough'] == 0 ? '不限' : '满' . $item['enough'] . '可用', 'expire_date_desc' => $item['timelimit_type'] == 1 ? '兑换后' . $item['timelimit_type'] . '天内有效' : '有效期至' . date("Y-m-d H:i:s", $item['endtime'])];
        }, $couponlist);
        $this->success('请求成功', $data);
    }

    /**
     * 兑换优惠券
     *
     * @param $coupon_id 优惠券ID
     */
    public function exchangecoupon()
    {
        //获取参数
        $coupon_id = (int)$this->request->post('coupon_id');
        $user_id = $this->auth->id;
        //查询当前优惠券
        $coupon = Coupon::where(['id' => $coupon_id, 'status' => 1])->find();
        if (empty($coupon)) {
            $this->error('优惠券不存在');
        }
        if ($coupon['timelimit_type'] == 2 && $coupon['endtime'] < time()) {
            $this->error('优惠券已过期');
        }
        if ($coupon['surplus_num'] <= 0) {
            $this->error('优惠券已被兑换完');
        }
        //查询当前用户金币余额
        $user_goldcoin = Usermodel::where(['id' => $user_id])->value('goldcoin');
        if ($user_goldcoin < $coupon['exchange_goldcoin']) {
            $this->error('金币余额不足');
        }
        //添加优惠券领取记录
        $user_coupon_data = ['user_id' => $user_id, 'coupon_id' => $coupon_id, 'name' => $coupon['name'], 'timelimit_type' => $coupon['timelimit_type'], 'validday' => $coupon['validday'], 'discount_way' => $coupon['discount_way'], 'enough' => $coupon['enough'], 'deduct' => $coupon['deduct'], 'discount' => $coupon['discount'], 'exchange_goldcoin' => $coupon['exchange_goldcoin'], 'status' => 1];
        if ($coupon['timelimit_type'] == 1) {
            $user_coupon_data['starttime'] = time();
            $user_coupon_data['endtime'] = time() + $coupon['validday'] * 86400;
        } else {
            $user_coupon_data['starttime'] = $coupon['starttime'];
            $user_coupon_data['endtime'] = $coupon['endtime'];
        }
        $user_coupon_ret = UserCoupon::create($user_coupon_data);
        //更新当前优惠券剩余数量
        $coupon_ret = Coupon::update(['surplus_num' => bcsub($coupon['surplus_num'], 1), 'receive_num' => bcadd($coupon['receive_num'], 1)], ['id' => $coupon_id]);
        //更新用户金币记录
        $user_ret = Usermodel::goldcoin(-$coupon['exchange_goldcoin'], $user_id, 8, $user_coupon_ret->id, '优惠券兑换');
        if ($user_coupon_ret !== false && $coupon_ret !== false && $user_ret !== false) {
            $this->success('兑换成功');
        } else {
            $this->error('兑换失败');
        }
    }

    /**
     * 我的优惠券
     *
     * @param $page 当前页数
     */
    public function mycoupon()
    {
        //获取参数
        $page = $this->request->get('page', 1);
        $limit = $this->pagelimit;
        $user_id = $this->auth->id;
        //查询可兑换的优惠券列表
        $couponlist = UserCoupon::list('user_id=' . $user_id . ' and endtime>=' . time(), '*', 'createtime desc', $page, $limit);
        $data['couponlist'] = array_map(function ($item) {
            return ['id' => $item['id'], 'discount_way' => $item['discount_way'], 'name' => $item['name'], 'enough' => $item['enough'], 'deduct' => $item['deduct'], 'discount' => $item['discount'], 'exchange_goldcoin' => $item['exchange_goldcoin'], 'enough_desc' => $item['enough'] == 0 ? '不限' : '满' . $item['enough'] . '可用', 'expire_date_desc' => '有效期至' . date("Y-m-d H:i:s", $item['endtime'])];
        }, $couponlist);
        $this->success('请求成功', $data);
    }

    /**
     * 金币明细
     *
     * @param $type 明细类型 1收益明细2消费明细
     */
    public function goldcoinlist()
    {
        //获取参数
        $type = $this->request->get('type', 1);
        $page = $this->request->get('page', 1);
        $limit = $this->pagelimit;
        $user_id = $this->auth->id;
        $where = ['user_id' => $user_id];
        switch ($type) {
            case 1: //收益明细
                $where['type'] = ['in', [1, 2, 3, 7]];
                break;
            case 2: //消费明细
                $where['type'] = ['in', [4, 5, 6, 8]];
                break;
        }
        //查询金币明细
        $goldcoin = GoldcoinLog::list($where, 'id,goldcoin,memo,createtime', 'createtime desc', $page, $limit);
        $this->success('请求成功', $goldcoin);
    }

    /**
     * 下载优惠券
     *
     * @param $user_coupon_id 用户优惠券ID
     */
    public function downloadcoupon()
    {
        //获取参数
        $user_coupon_id = (int)$this->request->post('user_coupon_id');
        $user_id = $this->auth->id;
        //查询当前用户优惠券
        $user_coupon = UserCoupon::where(['id' => $user_coupon_id, 'user_id' => $user_id])->find();
        if (empty($user_coupon)) {
            $this->error('用户优惠券不存在');
        }
        //满多少描述
        $enough_desc = '-' . ($user_coupon['enough'] == 0 ? '无限制' : '满' . $user_coupon['enough'] . '元可用') . '-';
        //折扣描述
        $deduct_desc = $user_coupon['discount_way'] == 3 ? $user_coupon['discount'] : $user_coupon['deduct'];
        //折扣后缀
        $deduct_suffix = $user_coupon['discount_way'] == 3 ? '%' : '元';
        //有效期描述
        $expire_desc = '有效期' . date("Y-m-d", $user_coupon['starttime']) . '至' . date("Y-m-d", $user_coupon['endtime']);
        if (!$user_coupon['poster']) {
            //处理海报并保存
            $user_coupon['poster'] = $this->createposter($user_coupon['name'], $enough_desc, $deduct_desc, $deduct_suffix, $expire_desc);
            UserCoupon::update(['poster' => $user_coupon['poster'], 'download_status' => 2], ['id' => $user_coupon_id]);
        }
        $data = imgAppendUrl('string', $user_coupon['poster'], '');
        $this->success('请求成功', $data);
    }

    /**
     * 生成海报
     * @param string $coupon_name 优惠券名称
     * @param string $enough_desc 满多少描述
     * @param string $deduct_desc 折扣描述
     * @param string $deduct_suffix 折扣描述后缀
     * @param string $expire_desc 有效期描述
     */
    public function createposter($coupon_name, $enough_desc, $deduct_desc, $deduct_suffix, $expire_desc)
    {
        #### （二）实例化海报类
        // 海报大小(生成的海报宽高，可以用背景图的大小)
        $poster = new PosterMaker(750, 1206);
        // 生成的海报文件名
        $poster_name = md5(date('Y-m-d H:i:s') . rand(100000, 999999)) . '.png';
        // 生成的海报文件路径
        $poster_path = '/poster/coupon/' . $poster_name;
        if (!file_exists(ROOT_PATH . 'public/poster/coupon/')) {
            mkdir(ROOT_PATH . 'public/poster/coupon/');
        }
        $posterFilePath = ROOT_PATH . 'public' . $poster_path;
        // 背景图
        $backimag = '/assets/img/poster_bgimg.png';
        $backimag = cdnurl($backimag, true);
        #### （三）生成海报
        $poster->addImg($backimag, [0, 0], [750, 1206]) // 海报大小
        ->addText("恭喜获得", 60, [220, 240], [251, 226, 176])
            ->addText($coupon_name, 60, [mb_strlen($coupon_name) <= 5 ? 200 : 140, 340], [251, 226, 176])
            ->addText($deduct_desc, 140, [strlen($deduct_desc) == 1 ? 260 : 230, 700], [247, 41, 45])
            ->addText($deduct_suffix, 60, [strlen($deduct_desc) == 1 ? 420 : 450, 700], [247, 41, 45])
            ->addText($enough_desc, 20, [300, 780], [247, 41, 45])
            ->addText($expire_desc, 24, [120, 920], [255, 255, 255])
            ->render($posterFilePath); //生成海报
        return $poster_path;
    }

}
