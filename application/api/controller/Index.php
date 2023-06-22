<?php

namespace app\api\controller;

use app\admin\model\exam\Question;
use app\admin\model\Schoolhistorymuseum;
use app\admin\model\Sensitivewords;
use app\admin\model\user\Quiz;
use app\admin\model\user\Signup;
use app\common\controller\Api;
use app\admin\model\Factions;
use app\admin\model\exam\Type;
use app\common\model\User;
use app\admin\model\user\Answer;
use app\admin\model\user\AnswerQuestion;
use app\admin\model\ad\Ad;
use app\admin\model\user\Collect;
use app\admin\model\School;
use app\admin\model\material\Material;
use app\admin\model\Label;
use app\common\model\Config;
use DfaFilter\SensitiveHelper;

/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['index', 'factions', 'examtype', 'labels'];
    protected $noNeedRight = ['*'];
    protected $sensitive_words = []; //敏感词数组
    protected $sensitive_flag_arr = ['？', '！', '￥', '（', '）', '：', '‘', '’', '“', '”', '《', '》', '，', '…', '。', '、', 'nbsp', '】', '【', '～']; //敏感词字符数组
    protected $pagelimit = 20; //分页数量
    protected $title_length = 40; //发布标题字数限制
    protected $content_length = 200; //发布内容字数限制

    public function _initialize()
    {
        parent::_initialize();
        //获取敏感词数组
        $this->sensitive_words = Sensitivewords::column('word');
    }

    /**
     * 首页
     */
    public function index()
    {
        //查询默认头像
        $default_avatar = Config::where(['name' => 'default_avatar'])->value('value');
        $data['user'] = ['nickname' => '未登录', 'avatar' => imgAppendUrl('string', $default_avatar, ''), 'description' => '']; //用户信息
        $data['examtime_remaining_day'] = '00'; //距离考试剩余天数
        $data['yesterday_answer_accuracy'] = 0; //昨日答题正确率
        $data['yesterday_answer_errorrate'] = 0; //昨日答题错误率
        $data['total_answer_accuracy'] = 0; //总答题正确率
        $data['total_answer_errorrate'] = 0; //总答题错误率
        //斩题虚拟人数配置
        $brushquestion_virtual_base = Config::where(['name' => 'brushquestion_virtual_base'])->value('value');
        $brushquestion_virtual_range = Config::where(['name' => 'brushquestion_virtual_range'])->value('value');
        $data['brush_question_usernum'] = $brushquestion_virtual_base > $brushquestion_virtual_range ? rand(bcsub($brushquestion_virtual_base, $brushquestion_virtual_range), bcadd($brushquestion_virtual_base, $brushquestion_virtual_range)) : 0; //斩题人数
        //查询首页中间轮播图
        $ads = Ad::list(['ad_category_id' => 1, 'status' => 1], 'id,image,type,path,detail_id,content');
        $data['ads'] = array_map(function ($item) {
            $item['image'] = imgAppendUrl('string', $item['image'], '');
            $item['content'] = htmlspecialchars_decode($item['content']);
            return $item;
        }, $ads);
        if ($this->auth->isLogin()) {
//            $data['user'] = $this->auth->getUser();
            $user_id = $this->auth->id;
            //查询用户信息
            $data['user'] = User::field('id,nickname,avatar,school,factions_id,major,total_answer_num,correct_answer_num,exam_time,accuracy')->with(['factions', 'examtype'])->find(['id' => $user_id]);
            $data['user']['avatar'] = imgAppendUrl('string', $data['user']['avatar'], ['avatar']);
            $data['user']['description'] = $data['user']['school'] . '·' . $data['user']['major'] . '·' . $data['user']['factions']['name'];
            unset($data['user']['factions']);
//            //查询当前参与斩题人数
//            $data['brush_question_usernum'] = Answer::group('user_id')->count();
            //查询距离考试剩余天数
            $data['examtime_remaining_day'] = $data['user']['exam_time'] > time() ? calculate_difference_days(time(), $data['user']['exam_time']) : $data['examtime_remaining_day'];
            //查询昨日答题总数
            $yesterday_answer_totalnum = AnswerQuestion::where(['user_id' => $user_id])->whereTime('answer_time', 'yesterday')->count();
            //查询昨日正确答题总数
            $yesterday_answer_correctnum = AnswerQuestion::where(['user_id' => $user_id, 'result' => 1])->whereTime('answer_time', 'yesterday')->count();
            //昨日答题正确率
            $data['yesterday_answer_accuracy'] = $yesterday_answer_totalnum > 0 ? round($yesterday_answer_correctnum / $yesterday_answer_totalnum * 100) : 0;
            //昨日答题错误率
            $data['yesterday_answer_errorrate'] = $yesterday_answer_totalnum > 0 ? 100 - $data['yesterday_answer_accuracy'] : 0;
            $data['total_answer_accuracy'] = $data['user']['accuracy']; //总答题正确率
            $data['total_answer_errorrate'] = 100 - $data['user']['accuracy']; //总答题错误率
        }
        $this->success('请求成功', $data);
    }

    /**
     * 派系列表
     *
     * @param $type 类型 1=获取基本数据（用于完善资料时显示）,2=派系排名
     */
    public function factions()
    {
        //获取参数
        $type = $this->request->request('type', 1); //类型
        $where = ['status' => 1];
        $field = 'id,name,total_goldcoin';
        $order = 'weigh desc';
        if ($type == 2) {
            $order = 'total_goldcoin desc, weigh desc';
        }
        $factions = Factions::list($where, $field, $order);
        $this->success('请求成功', $factions);
    }

    /**
     * 备考类型
     */
    public function examtype()
    {
        $factions = Type::list(['status' => 1], 'id,name');
        $this->success('请求成功', $factions);
    }

    /**
     * 标签列表
     *
     * @param $type 类型:1=打卡标签,2=集市标签,3=院校标签
     */
    public function labels()
    {
        //获取参数
        $type = $this->request->get('type');
        if (empty($type)) {
            $this->error('参数错误');
        }
        //查询标签
        $data = Label::list(['type' => $type], 'id,name');
        $this->success('请求成功', $data);
    }

    /**
     * 收藏/取消收藏
     *
     * @param $type 类型:1=院校,2=习题,3=资料
     * @param $id 参数ID
     */
    public function collect()
    {
        //获取参数
        $type = (int)$this->request->post('type', 1);
        $id = (int)$this->request->post('id');
        $user_id = $this->auth->id;
        if (empty($type) || empty($id)) {
            $this->error('参数错误');
        }
        //查询当前用户是否已收藏
        $user_collect = Collect::where(['detail_id' => $id, 'type' => $type, 'user_id' => $user_id])->find();
        if (empty($user_collect)) { //未收藏，收藏
            $data = ['detail_id' => $id, 'type' => $type, 'user_id' => $user_id];
            if ($type == 1) {
                //院校类型查询院校是否存在
                $school = School::where(['id' => $id])->count();
                if (empty($school)) {
                    $this->error('院校不存在');
                }
            } elseif ($type == 2) {
                //题目类型查询题目信息
                $question = Question::where(['id' => $id, 'status' => 1])->find();
                if (empty($question)) {
                    $this->error('题目不存在');
                }
                $data['title'] = $question['title'];
                $data['option'] = $question['option'];
                $data['right_option'] = $question['right_option'];
            } elseif ($type == 3) {
                //资料类型查询资料是否存在
                $material = Material::where(['id' => $id])->count();
                if (empty($material)) {
                    $this->error('资料不存在');
                }
            } else {
                $this->error('类型不支持');
            }
            $ret = Collect::create($data);
            $msg_type = '收藏';
        } else { //已收藏，取消收藏
            $ret = Collect::destroy($user_collect['id']);
            $msg_type = '取消收藏';
        }
        if ($ret !== false) {
            $this->success($msg_type . '成功');
        } else {
            $this->error($msg_type . '失败');
        }
    }

    /**
     * 提问列表
     *
     * @param $type 类型 1全部提问2我的提问
     * @param $page 分页
     */
    public function quizlist()
    {
        //获取参数
        $type = (int)$this->request->get('type', 1);
        $page = (int)$this->request->get('page', 1);
        $limit = $this->pagelimit;
        $user_id = $this->auth->id;
        $where = ['status' => 1];
        if ($type == 2) {
            $where['user_id'] = $user_id;
        }
        $quizs = Quiz::list($where, 'id,title,content,images,reply_content', 'createtime desc', $page, $limit);
        $data['quizs'] = array_map(function ($item) {
            $images_arr = $item['images'] ? explode(',', $item['images']) : [];
            $item['images_arr'] = imgAppendUrl('rownokey', $images_arr, '');
            return $item;
        }, $quizs);
        $this->success('请求成功', $data);
    }

    /**
     * 提问
     *
     * @param $title 提问标题
     * @param $content 提问内容
     * @param $images 提问图片
     */
    public function quiz()
    {
        //获取参数
        $param['title'] = $this->request->post('title');
        $param['content'] = $this->request->post('content');
        $param['images'] = $this->request->post('images/a');
        $param['user_id'] = $this->auth->id;
        //定义验证规则
        $validate_rule = [
            'title' => 'require|length:1,' . $this->title_length,
            'content' => 'require|length:1,' . $this->content_length,
        ];
        //定义错误信息
        $validate_message = [
            'title.require' => '标题不能为空',
            'title.length' => '标题长度需在1-' . $this->title_length . '个字符之间',
            'content.require' => '提问内容不能为空',
            'content.length' => '提问内容长度需在1-' . $this->content_length . '个字符之间',
        ];
        //验证参数
        $validate_check = $this->validate($param, $validate_rule, $validate_message);
        if ($validate_check !== true) {
            $this->error($validate_check);
        }
        //敏感词过滤
        //标题字符处理
        $title_filter = preg_replace('/\s/', '', preg_replace("/[[:punct:]]/", '', strip_tags(html_entity_decode(str_replace($this->sensitive_flag_arr, '', $param['title']), ENT_QUOTES, 'UTF-8'))));
        //内容
        $content_filter = preg_replace('/\s/', '', preg_replace("/[[:punct:]]/", '', strip_tags(html_entity_decode(str_replace($this->sensitive_flag_arr, '', $param['content']), ENT_QUOTES, 'UTF-8'))));
        $handle = SensitiveHelper::init()->setTree($this->sensitive_words);
//        $sensitiveWordGroup = $handle->getBadWord($content); //获取敏感词数组
        $title_islegal = $handle->islegal($title_filter);
        $content_islegal = $handle->islegal($content_filter);
        if ($title_islegal || $content_islegal) {
            $this->error('您发布的内容包含敏感词汇！');
        }
        $param['images'] = $param['images'] ? implode(',', $param['images']) : null;
        //发布提问
        $quiz_ret = Quiz::create($param);
        if ($quiz_ret !== false) {
            $this->success('提问成功');
        } else {
            $this->error('提问失败');
        }
    }

    /**
     * 报名
     *
     * @param $realname 姓名
     * @param $mobile 联系电话
     * @param $enrollment_school 报考院校
     * @param $account_version 考试科目与版本
     * @param $remark 备注
     */
    public function signup()
    {
        //获取参数
        $param['realname'] = $this->request->post('realname');
        $param['mobile'] = $this->request->post('mobile');
        $param['enrollment_school'] = $this->request->post('enrollment_school');
        $param['account_version'] = $this->request->post('account_version');
        $param['remark'] = $this->request->post('remark');
        $param['user_id'] = $this->auth->id;
        //定义验证规则
        $validate_rule = [
            'realname' => 'require|length:1,6',
            'mobile' => 'require|length:11',
            'enrollment_school' => 'require|length:1,20',
            'account_version' => 'require|length:1,30',
            'remark' => 'length:0,' . $this->content_length,
        ];
        //定义错误信息
        $validate_message = [
            'realname.require' => '姓名不能为空',
            'realname.length' => '姓名长度需在1-6个字符之间',
            'mobile.require' => '联系电话不能为空',
            'mobile.length' => '联系电话长度需为11位',
            'enrollment_school.require' => '报考院校不能为空',
            'enrollment_school.length' => '报考院校长度需在1-6个字符之间',
            'account_version.require' => '考试科目与版本不能为空',
            'account_version.length' => '考试科目与版本长度需在1-6个字符之间',
            'remarks.length' => '备注长度需在0-' . $this->content_length . '个字符之间',
        ];
        //验证参数
        $validate_check = $this->validate($param, $validate_rule, $validate_message);
        if ($validate_check !== true) {
            $this->error($validate_check);
        }
        //提交报名
        $signup_ret = Signup::create($param);
        if ($signup_ret !== false) {
            //查询系统配置老师微信二维码
            $qrcode = Config::where(['name' => 'exam_teacher_wechat_qrcode'])->value('value');
            $data = imgAppendUrl('string', $qrcode, '');
            $this->success('提交报名成功', $data);
        } else {
            $this->error('提交报名失败');
        }
    }

    /**
     * 修改考试时间
     *
     * @param $examdate 考试日期
     */
    public function modifyexamtime()
    {
        //获取参数
        $examdate = $this->request->post('examdate');
        $user_id = $this->auth->id;
        if (empty($examdate)) {
            $this->error('参数错误');
        }
        $examtime = strtotime($examdate);
        if ($examtime < strtotime(date('Y-m-d', time()))) {
            $this->error('考试时间不能小于今天');
        }
        //更新用户考试时间
        $ret = User::update(['exam_time' => $examtime], ['id' => $user_id]);
        if ($ret !== false) {
            $this->success('更新成功');
        } else {
            $this->error('更新失败');
        }
    }

    /**
     * 斗米校史馆 - 课程列表
     *
     * @param $page 当前页数
     */
    public function museumlist()
    {
        //获取参数
        $page = (int)$this->request->get('page', 1);
        $limit = $this->pagelimit;
        //查询斗米校史馆表中数据
        $data = Schoolhistorymuseum::list(['status' => 1], 'id,title,image,type,createtime', 'weigh desc, createtime desc', $page, $limit);
        $data = imgAppendUrl('arr', $data, ['image']);
        $this->success('请求成功', $data);
    }

    /**
     * 斗米校史馆 - 课程详情
     *
     * @param $id 课程ID
     */
    public function museumdetail()
    {
        //获取参数
        $id = $this->request->get('id');
        $data = Schoolhistorymuseum::field('id,title,type,video,content,createtime')->find($id);
        $data['content'] = htmlspecialchars_decode($data['content']);
        if ($data['type'] == 2) {
            $data['video'] = imgAppendUrl('string', $data['video'], '');
        }
        $this->success('请求成功', $data);
    }

}
