<?php

namespace app\api\controller;

use app\admin\model\Factions;
use app\admin\model\Label;
use app\admin\model\user\ClockingRecordThumbsup;
use app\admin\model\user\GoldcoinLog;
use app\common\controller\Api;
use app\admin\model\user\ClockingRecord;
use DfaFilter\SensitiveHelper;
use app\admin\model\Sensitivewords;
use app\common\model\User;
use app\common\model\Config;
use app\admin\model\user\DoumiMarket;
use app\admin\model\Notice;
use app\admin\model\Newspaperoffice;
use think\Log;

/**
 * 江湖接口
 */
class Riverlake extends Api
{
    protected $noNeedLogin = [''];
    protected $noNeedRight = ['*'];
    protected $sensitive_words = []; //敏感词数组
    protected $sensitive_flag_arr = ['？', '！', '￥', '（', '）', '：', '‘', '’', '“', '”', '《', '》', '，', '…', '。', '、', 'nbsp', '】', '【', '～']; //敏感词字符数组
    protected $title_length = 40; //标题字数限制
    protected $content_length = 200; //内容字数限制
    protected $pagelimit = 20; //分页条数限制

    public function _initialize()
    {
        parent::_initialize();
        //获取敏感词数组
        $this->sensitive_words = Sensitivewords::column('word');
    }

    /**
     * 签到列表
     *
     * @param $type 类型 1全部打卡2我的打卡
     * @param $page 页数
     */
    public function signinlist()
    {
        //获取参数
        $type = $this->request->get('type', 1);
        $page = $this->request->get('page', 1);
        $limit = $this->pagelimit;
        $user_id = $this->auth->id;
        //查询当前登录用户信息
        $data['user'] = User::field('id,nickname,avatar,clock_days')->where(['id' => $user_id])->find();
        //查询该用户今天是否已签到
        $signin_today = ClockingRecord::where(['user_id' => $user_id])->whereTime('createtime', 'today')->count();
        $data['user']['signin_today'] = $signin_today ? 2 : 1; //是否已签到：1未签到2已签到
        $data['user']['avatar'] = imgAppendUrl('string', $data['user']['avatar'], ['avatar']);
        $where = ['clocking_record.status' => 1];
        if ($type == 2) {
            $where['user_id'] = $user_id;
        }
        //查询签到记录
//        $signin_list = ClockingRecord::list($where, 'id,user_id,content,images,label_ids,is_top,thumbsup_num', 'is_top desc,weigh desc,createtime desc', $page, $limit);
        $signin_list = ClockingRecord::field('id,user_id,content,images,label_ids,is_top,thumbsup_num,status')->with([
            'user' => function ($query) {
                $query->withField('id,nickname,avatar,school,major,factions_id');
            },
        ])->where($where)->order('is_top desc,weigh desc,clocking_record.createtime desc')->limit(($page - 1) * $limit, $limit)->select()->toArray();
        $data['signin_list'] = array_map(function ($item) use ($user_id) {
            //查询当前打卡用户信息
            $cur_user = User::field('id,nickname,avatar,school,major,factions_id')->with('factions')->find(['id' => $item['user_id']]);
            //查询当前用户派系名称
            $factions_name = Factions::where(['id' => $item['user']['factions_id']])->value('name');
            $item['user_avatar'] = $item['user']['avatar'] ? imgAppendUrl('string', $item['user']['avatar'], '') : $item['user']['avatar'];
            $item['user_nickname'] = $item['user']['nickname'];
            $item['user_description'] = $item['user']['school'] . '·' . $item['user']['major'] . '·' . $factions_name;
            //查询当前打卡标签信息
            $item['labels'] = Label::list(['id' => ['in', explode(',', $item['label_ids'])]], 'id,name');
            if ($item['images']) {
                $item['images'] = imgAppendUrl('rownokey', explode(',', $item['images']), '');
            }
            //查询当前打卡记录当前登录用户是否已点赞
            $is_thumbsup = ClockingRecordThumbsup::where(['user_clocking_record_id' => $item['id'], 'user_id' => $user_id])->count();
            $item['is_thumbsup'] = $is_thumbsup ? 2 : 1;//是否已点赞 1未点赞2已点赞
            return $item;
        }, $signin_list);
        $this->success('请求成功', $data);
    }

    /**
     * 签到
     *
     * @param $content 打卡内容
     * @param $images 打卡图片
     * @param $label_ids 打卡标签
     */
    public function signin()
    {
        //获取参数
        $param['content'] = $this->request->post('content');
        $param['images'] = $this->request->post('images/a');
        $param['label_ids'] = $this->request->post('label_ids/a');
        $param['user_id'] = $this->auth->id;
        if (empty($param['content']) || empty($param['label_ids'])) {
            $this->error('参数错误');
        }
        if (mb_strlen($param['content']) > $this->content_length) {
            $this->error('字符长度不能超过' . $this->content_length);
        }
        //敏感词过滤
        $content_filter = preg_replace('/\s/', '', preg_replace("/[[:punct:]]/", '', strip_tags(html_entity_decode(str_replace($this->sensitive_flag_arr, '', $param['content']), ENT_QUOTES, 'UTF-8'))));
        $handle = SensitiveHelper::init()->setTree($this->sensitive_words);
//        $sensitiveWordGroup = $handle->getBadWord($content); //获取敏感词数组
        $islegal = $handle->islegal($content_filter);
        if ($islegal) {
            $this->error('您发布的内容包含敏感词汇！');
        }
        //查询标签数据
        $label_num = Label::where(['id' => ['in', $param['label_ids']]])->count();
        if ($label_num !== count($param['label_ids'])) {
            $this->error('标签数据有误');
        }
        $param['images'] = $param['images'] ? implode(',', $param['images']) : null;
        $param['label_ids'] = implode(',', $param['label_ids']);
        //查询该用户今天是否已签到
        $signin_today = ClockingRecord::where(['user_id' => $param['user_id']])->whereTime('createtime', 'today')->count();
        if ($signin_today) {
            $this->error('您今天已经打卡');
        }
        //查询当前用户是否是置顶用户
        $user_is_top = User::where(['id' => $param['user_id']])->value('is_top');
        if ($user_is_top == 2) {
            //如果当前用户是置顶用户，则把该用户之前打卡记录全部改为非置顶
            ClockingRecord::where(['user_id' => $param['user_id'], 'is_top' => 2])->update(['is_top' => 1]);
            $param['is_top'] = 2;
        }
        //执行签到
        $signin_ret = ClockingRecord::create($param);
        //查询该用户昨日是否已签到
        $signin_yesterday = ClockingRecord::where(['user_id' => $param['user_id']])->whereTime('createtime', 'yesterday')->count();
        //查询系统配置打卡赠送金币数量
        $signin_give_goldcoin = Config::where(['name' => 'signin_give_goldcoin'])->value('value');
        $goldcoin_ret = User::goldcoin($signin_give_goldcoin, $param['user_id'], 2, $signin_ret->id, '打卡奖励', $signin_yesterday ? 2 : 3);
        if ($signin_ret !== false && $goldcoin_ret !== false) {
            $this->success('签到成功', $signin_give_goldcoin);
        } else {
            $this->error('签到失败');
        }
    }

    /**
     * 点赞、取消点赞
     *
     * @param $user_clocking_record_id 打卡记录ID
     */
    public function thumbsup()
    {
        //获取参数
        $user_clocking_record_id = (int)$this->request->post('user_clocking_record_id');
        $user_id = $this->auth->id;
        if (empty($user_clocking_record_id)) {
            $this->error('参数错误');
        }
        //查询当前打卡记录
        $user_clocking_record = ClockingRecord::get(['id' => $user_clocking_record_id]);
        if (empty($user_clocking_record)) {
            $this->error('打卡记录不存在');
        }
        //查询该打卡记录点赞记录
        $data = ['user_clocking_record_id' => $user_clocking_record_id, 'user_id' => $user_id];
        $user_clocking_record_thumbsup = ClockingRecordThumbsup::get($data);
        if (empty($user_clocking_record_thumbsup)) { //未点赞，则执行点赞
            $ret = ClockingRecordThumbsup::create($data);
            $thumbsup_msg = '点赞';
            $thumbsup_num = $user_clocking_record['thumbsup_num'] + 1;
        } else { //已点赞，则执行取消点赞
            $ret = ClockingRecordThumbsup::destroy($user_clocking_record_thumbsup['id']);
            $thumbsup_msg = '取消点赞';
            $thumbsup_num = $user_clocking_record['thumbsup_num'] - 1;
        }
        //更新点赞数量
        $user_clocking_record_ret = ClockingRecord::update(['thumbsup_num' => $thumbsup_num], ['id' => $user_clocking_record_id]);
        if ($ret !== false && $user_clocking_record_ret !== false) {
            $this->success($thumbsup_msg . '成功');
        } else {
            $this->error($thumbsup_msg . '失败');
        }
    }

    /**
     * 摆摊列表
     *
     * @param $type 类型 1全部打卡2我的打卡
     * @param $label_id 标签ID
     * @param $page 页数
     */
    public function marketlist()
    {
        //获取参数
        $type = $this->request->get('type', 1);
        $label_id = $this->request->get('label_id');
        $page = $this->request->get('page', 1);
        $limit = $this->pagelimit;
        $user_id = $this->auth->id;
        //查询公告列表
        $data['notice'] = Notice::field('id,title')->where(['status' => 1])->order('createtime desc')->select();
        //查询当前用户是否是学员
        $is_student = User::where(['id' => $user_id])->value('is_student');
        $where = ['status' => 2];
        if ($type == 2) {
            $where['user_id'] = $user_id;
        }
        if ($label_id) {
            $where['label_ids'] = ['like', '%' . $label_id . '%'];
        }
        //查询摆摊记录
        $market_list = DoumiMarket::list($where, 'id,user_id,title,images,label_ids,introduce,contact,consume_goldcoin', 'weigh desc,createtime desc', $page, $limit);
        $data['market_list'] = array_map(function ($item) use ($is_student, $user_id) {
            $item['image'] = '';
            if ($item['images']) {
                $images = explode(',', $item['images']);
                $item['image'] = imgAppendUrl('string', $images[0], '');
            }
            $item['contact_encrypt'] = substr($item['contact'], 0, 3) . '****' . substr($item['contact'], -4);
            //查询当前用户当前摆摊是否支付金币
            $user_goldcoin_log = GoldcoinLog::where(['type' => 5, 'user_id' => $user_id, 'detail_id' => $item['id']])->count();
            //是否有权限查看联系方式 1无权限2有权限
            $item['can_view'] = ($is_student == 2 || $item['user_id'] == $user_id || $user_goldcoin_log) ? 2 : 1;
            //查询当前打卡标签信息
            $item['labels'] = Label::list(['id' => ['in', explode(',', $item['label_ids'])]], 'id,name');
            return $item;
        }, $market_list);
        $this->success('请求成功', $data);
    }

    /**
     * 摆摊
     *
     * @param $title 摆摊标题
     * @param $introduce 摆摊详细介绍
     * @param $image 摆摊图片
     * @param $label_ids 摆摊标签
     * @param $contact 摆摊联系方式
     * @param $remarks 摆摊备注
     */
    public function market()
    {
        //获取参数
        $param['title'] = $this->request->post('title');
        $param['introduce'] = $this->request->post('introduce');
        $param['images'] = $this->request->post('images/a');
        $param['label_ids'] = $this->request->post('label_ids/a');
        $param['contact'] = $this->request->post('contact');
        $param['remarks'] = $this->request->post('remarks');
        $param['user_id'] = $this->auth->id;
        //定义验证规则
        $validate_rule = [
            'title' => 'require|length:1,' . $this->title_length,
            'introduce' => 'require|length:1,' . $this->content_length,
            'images' => 'require',
            'label_ids' => 'require',
            'contact' => 'require|length:11',
            'remarks' => 'length:0,' . $this->content_length,
        ];
        //定义错误信息
        $validate_message = [
            'title.require' => '标题不能为空',
            'title.length' => '标题长度需在1-' . $this->title_length . '个字符之间',
            'introduce.require' => '详细介绍不能为空',
            'introduce.length' => '详细介绍需在1-' . $this->content_length . '个字符之间',
            'images.require' => '上传图片不能为空',
            'label_ids.require' => '标签不能为空',
            'contact.require' => '联系方式不能为空',
            'contact.length' => '联系方式长度需为11位',
//            'contact.mobile' => '联系方式必须为手机号格式',
//            'remarks.require' => '备注不能为空',
            'remarks.length' => '备注长度需在0-' . $this->content_length . '个字符之间',
        ];
        //验证参数
        $validate_check = $this->validate($param, $validate_rule, $validate_message);
        if ($validate_check !== true) {
            $this->error($validate_check);
        }
        //敏感词过滤
        //标题字符处理
        $title_filter = preg_replace('/\s/', '', preg_replace("/[[:punct:]]/", '', strip_tags(html_entity_decode(str_replace($this->sensitive_flag_arr, '', $param['title']), ENT_QUOTES, 'UTF-8'))));
        //详细介绍字符处理
        $introduce_filter = preg_replace('/\s/', '', preg_replace("/[[:punct:]]/", '', strip_tags(html_entity_decode(str_replace($this->sensitive_flag_arr, '', $param['introduce']), ENT_QUOTES, 'UTF-8'))));
        //备注字符处理
        $remarks_filter = preg_replace('/\s/', '', preg_replace("/[[:punct:]]/", '', strip_tags(html_entity_decode(str_replace($this->sensitive_flag_arr, '', $param['remarks']), ENT_QUOTES, 'UTF-8'))));
        $handle = SensitiveHelper::init()->setTree($this->sensitive_words);
//        $sensitiveWordGroup = $handle->getBadWord($content); //获取敏感词数组
        $title_islegal = $handle->islegal($title_filter);
        $introduce_islegal = $handle->islegal($title_filter);
        $remarks_islegal = $handle->islegal($title_filter);
        if ($title_islegal || $introduce_islegal || $remarks_islegal) {
            $this->error('您发布的内容包含敏感词汇！');
        }
        //处理并验证图片
        $param['images'] = array_filter($param['images']);
        if (count($param['images']) == 0) {
            $this->error('请至少上传一张图片');
        }
        $param['images'] = $param['images'] ? implode(',', $param['images']) : null;
        //查询标签数据
        $label_num = Label::where(['id' => ['in', $param['label_ids']]])->count();
        if (count($param['label_ids']) > 0 && $label_num !== count($param['label_ids'])) {
            $this->error('标签数据有误');
        }
        $param['label_ids'] = implode(',', $param['label_ids']);
        //查询该用户今天已发布摆摊数量
        $market_today_num = DoumiMarket::where(['user_id' => $param['user_id'], 'status' => ['in', [1, 2]]])->whereTime('createtime', 'today')->count();
        //查询系统配置摆摊每日每人最大发布数量
        $market_everyday_everybody_num = Config::where(['name' => 'market_everyday_everybody_num'])->value('value');
        //验证每人每日发布数量
        if ($market_today_num >= $market_everyday_everybody_num) {
            $this->error('您今日发布数量已达上限');
        }
        //查询所有用户今天已发布摆摊数量
        $market_today_allnum = DoumiMarket::where(['status' => ['in', [1, 2]]])->whereTime('createtime', 'today')->count();
        //查询系统配置摆摊每日最大发布数量
        $market_everyday_num = Config::where(['name' => 'market_everyday_num'])->value('value');
        //验证每日发布数量
        if ($market_today_allnum >= $market_everyday_num) {
            $this->error('今日发布数量已达上限');
        }
        //查询系统配置发布摆摊消耗金币数量
        $market_consume_goldcoin = Config::where(['name' => 'market_consume_goldcoin'])->value('value');
        //查询当前用户
        $user = User::field('id,goldcoin')->where(['id' => $param['user_id']])->find();
        if ($user['goldcoin'] < $market_consume_goldcoin) {
            $this->error('金币余额不足');
        }
        $param['consume_goldcoin'] = $market_consume_goldcoin;
        //发布摆摊
        $market_ret = DoumiMarket::create($param);
        $goldcoin_ret = User::goldcoin(-$market_consume_goldcoin, $param['user_id'], 4, $market_ret->id, '斗米集市摆摊扣除');
        if ($market_ret !== false && $goldcoin_ret !== false) {
            $this->success('发布摆摊成功');
        } else {
            $this->error('发布摆摊失败');
        }
    }

    /**
     * 摆摊详情
     *
     * @param $market_id 摆摊ID
     */
    public function marketdetail()
    {
        //获取参数
        $market_id = $this->request->get('market_id');
        $user_id = $this->auth->id;
        if (empty($market_id)) {
            $this->error('参数错误');
        }
        $market = DoumiMarket::field('id,title,images,introduce,label_ids,remarks,contact')->where(['id' => $market_id])->find();
        if (empty($market)) {
            $this->error('摆摊不存在');
        }
        //查询当前打卡标签信息
        $market['labels'] = Label::list(['id' => ['in', explode(',', $market['label_ids'])]], 'id,name');
        if ($market['images']) {
            $market['images_arr'] = imgAppendUrl('rownokey', explode(',', $market['images']), '');
        }
        //查询当前用户是否是学员
        $is_student = User::where(['id' => $user_id])->value('is_student');
        $market['contact_encrypt'] = substr($market['contact'], 0, 3) . '****' . substr($market['contact'], -4);
        //查询当前用户当前摆摊是否支付金币
        $user_goldcoin_log = GoldcoinLog::where(['type' => 5, 'user_id' => $user_id, 'detail_id' => $market['id']])->count();
        //是否有权限查看联系方式 1无权限2有权限
        $market['can_view'] = ($is_student == 2 || $user_goldcoin_log) ? 2 : 1;
        $this->success('请求成功', $market);
    }

    /**
     * 查看摆摊联系方式
     *
     * @param $market_id 摆摊ID
     */
    public function viewcontact()
    {
        //获取参数
        $market_id = (int)$this->request->post('market_id');
        $user_id = $this->auth->id;
        if (empty($market_id)) {
            $this->error('参数错误');
        }
        //查询当前摆摊信息
        $market = DoumiMarket::get($market_id);
        if (empty($market)) {
            $this->error('摆摊不存在');
        }
        //查询当前用户信息
        $user = User::field('id,is_student,goldcoin')->where(['id' => $user_id])->find();
        //查询当前用户当前摆摊是否支付金币
        $user_goldcoin_log = GoldcoinLog::where(['type' => 5, 'user_id' => $user_id, 'detail_id' => $market_id])->count();
        if ($user['is_student'] == 2 || $user_goldcoin_log) {
            $this->success('请求成功', $market['contact']);
        } else {
            //查询查看摆摊集市查看联系方式消耗金币数量
            $market_look_consume_goldcoin = Config::where(['name' => 'market_look_consume_goldcoin'])->value('value');
            if ($user['goldcoin'] < $market_look_consume_goldcoin) {
                $this->error('金币余额不足');
            }
            //支付金币查看摆摊联系方式
            $ret = User::goldcoin(-$market_look_consume_goldcoin, $user_id, 5, $market_id, '斗米集市查看联系方式扣除');
            if ($ret) {
                $this->success('请求成功', $market['contact']);
            } else {
                $this->error('请求失败');
            }
        }
    }

    /**
     * 删除摆摊
     *
     * @param $market_id 摆摊ID
     */
    public function delmarket()
    {
        //获取参数
        $market_id = (int)$this->request->post('market_id');
        $user_id = $this->auth->id;
        //查询当前摆摊
        $user_market = DoumiMarket::where(['id' => $market_id, 'user_id' => $user_id])->find();
        if (empty($user_market)) {
            $this->error('摆摊信息不存在');
        }
        //执行删除
        $ret = DoumiMarket::destroy($market_id);
        if ($ret) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 斗米报社列表（议事堂）
     *
     * @param $page  页数
     * @param $keyword 关键词
     */
    public function officelist()
    {
        //获取参数
        $page = $this->request->get('page', 1);
        $keyword = $this->request->get('keyword', '');
        $user_id = $this->auth->id;
        $limit = $this->pagelimit;
        //查询斗米报社表中数据
        $data = Newspaperoffice::list(['title' => ['like', '%' . $keyword . '%']], 'id,title,description', 'weigh desc, createtime desc', $page, $limit);
        $this->success('请求成功', $data);
    }

    /**
     * 斗米报社详情
     *
     * @param $newspaper_office_id 斗米报社ID
     */
    public function officedetail()
    {
        //获取参数
        $newspaper_office_id = $this->request->get('newspaper_office_id');
        $user_id = $this->auth->id;
        if (empty($newspaper_office_id)) {
            $this->error('参数错误');
        }
        $newspaper_office = Newspaperoffice::get(['id' => $newspaper_office_id]);
        if (empty($newspaper_office)) {
            $this->error('资料不存在');
        }
        $newspaper_office['content'] = htmlspecialchars_decode($newspaper_office['content']);
        $newspaper_office['createtime'] = date("Y-m-d H:i:s", $newspaper_office['createtime']);
        $this->success('请求成功', $newspaper_office);
    }

}
