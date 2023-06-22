<?php

namespace app\api\controller;

use app\admin\model\user\AnswerPass;
use app\admin\model\user\Collect;
use app\common\controller\Api;
use app\admin\model\exam\Question;
use app\admin\model\user\Answer as UserAnswer;
use app\admin\model\user\AnswerQuestion;
use app\admin\model\exam\QuestionCategory;
use app\common\model\Config;
use app\common\model\Config as ConfigModel;
use app\common\model\User;
use app\admin\model\user\WrongQuestion;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 考试接口
 */
class Exam extends Api
{
    protected $noNeedLogin = [''];
    protected $noNeedRight = ['*'];
    protected $pagelimit = 20; //分页数量

    /**
     * 章节列表
     */
    public function categorylist()
    {
        //获取参数
        $exam_type_id = (int)$this->request->get('exam_type_id');
        $user_id = $this->auth->id;
        if (empty($exam_type_id)) {
            $this->error('参数错误');
        }
        //查询题目分类表中所有数据
        $categorys = QuestionCategory::list(['exam_type_id' => $exam_type_id, 'status' => 1, 'question_totalnum' => ['>', 0]], 'id,name,pid,level,question_totalnum');
        \think\Log::write(['categorylist_categorys' => $categorys], 'log', true);
        $category_tree = $this->tree($categorys, $user_id, 0, 'pid', 'id');
        $this->success('请求成功', $category_tree);
    }

    /**
     * 获取分类多维数组
     * @param array $array
     * @param string $first
     * @param string $pid
     * @param string $id
     * @param string $child
     * @return array
     */
    private function tree(
        array  $array,
        int    $user_id,
        string $first,
        string $pid,
        string $id,
        string $child = 'childs'
    ): array
    {
        $tree = [];
        foreach ($array as $item) {
            $cur_category_question_num = 1;
            if($item['level'] == 1){
                //查询该类别下有题目总数
                $cur_category_question_num = QuestionCategory::where(['pid' => $item['id']])->sum('question_totalnum');
            } elseif ($item['level'] == 2) {
                //查询该类别下有题目总数
                $cur_category_question_num = QuestionCategory::where(['pid' => $item['id']])->sum('question_totalnum');
                //查询该用户该版本是否已解锁
                $item['edition_unlock'] = $this->checkEditionUnlock($item['id'], $user_id); //版本是否解锁 1未解锁2已解锁
            } elseif ($item['level'] == 3) {
                //查询该用户该章节顺序刷题记录
                $user_answer = UserAnswer::field('id,question_num,answer_question_num')->where(['user_id' => $user_id, 'type' => 1, 'third_category_id' => $item['id']])->order('createtime desc')->find();
                //如果用户未对该章节进行过答题
                if (empty($user_answer)) {
                    $item['answer_question_num'] = 0; //已答题数量
                    $item['answer_status'] = 1; //未答过题
                } else {
                    $item['question_totalnum'] = $user_answer['question_num']; //题目总数
                    $item['answer_question_num'] = $user_answer['answer_question_num']; //已答题数量
                    $item['answer_status'] = 2; //已答过题
                }
            }
            if($cur_category_question_num > 0){
                $tree[$item[$id]] = $item;
            }
        }
        foreach ($tree as $item) {
            $tree[$item[$pid]][$child][] = &$tree[$item[$id]];
        }

        return $tree[$first][$child] ?? [];
    }

    /**
     * 检查版本是否已解锁
     *
     * @param $second_category_id 版本ID
     * @param $user_id 用户ID
     * @return int 版本是否解锁 1未解锁2已解锁
     */
    private function checkEditionUnlock($second_category_id, $user_id)
    {
        $wherein = [];
        if (is_array($second_category_id)) {
            $wherein = array_merge($wherein, $second_category_id);
        } else {
            $wherein = [$second_category_id];
        }
        //查询该版本下所有章节ID
        $third_category_ids = QuestionCategory::where(['level' => 3, 'status' => 1])->whereIn('pid', $wherein)->column('id');
        if (empty($third_category_ids)) {
            return false;
        }
        $has_answered = 2; //所有章节都已答题(已解锁)
        $user_answer_model = new UserAnswer();
        foreach ($third_category_ids as $third_category_id) {
            //查询当前章节是否有全部答完的答题记录
            if (!$user_answer_model->where(['third_category_id' => $third_category_id, 'user_id' => $user_id, 'type' => 1, 'status' => 3])->count()) {
                $has_answered = 1; //未解锁
            }
        }
        return $has_answered;
    }

    /**
     * 开始考试
     *
     * @param  $type 答题类型 1顺序刷题2混合刷题
     * @param $category_ids 分类ID（顺序刷题，则是章节ID；混合刷题，则是版本ids数组）
     * @param $exam_type 考试类型 1开始答题或重新答题2继续答题
     */
    public function startexam()
    {
        //获取参数
        $type = (int)$this->request->post('type');
        $exam_type = $this->request->post('exam_type', 1);
        $user_id = $this->auth->id;
        if (empty($type) || empty($exam_type)) {
            $this->error('参数错误');
        }
        $time = time();
        //查询系统配置中联系老师微信二维码及说明
        $data['exam_teacher_wechat_qrcode'] = imgAppendUrl('string', Config::where(['name' => 'exam_teacher_wechat_qrcode'])->value('value'), '');
        $data['exam_contact_teacher_desc'] = Config::where(['name' => 'exam_contact_teacher_desc'])->value('value');
        //答题记录数据
        $user_answer_data = ['type' => $type, 'user_id' => $user_id, 'start_time' => $time, 'status' => 1];
        if ($type == 1) { //顺序刷题
            $category_ids = (int)$this->request->post('category_ids');
            //查询当前章节信息
            $third_category = QuestionCategory::where(['id' => $category_ids, 'status' => 1])->find();
            if (empty($third_category)) {
                $this->error('章节不存在');
            }
//            Db::startTrans();
//            try {
            //当前章节路径
            $third_category_patharr = explode('/', trim($third_category['path'], '/'));
            $user_answer_data_type1 = ['exam_type_id' => $third_category['exam_type_id'], 'top_category_id' => $third_category_patharr[0], 'second_category_id' => $third_category_patharr[1], 'third_category_id' => $third_category_patharr[2], 'category_path' => $third_category['path']];
            $user_answer_data = array_merge($user_answer_data, $user_answer_data_type1);
            //查询当前章节下所有题目
            $questions = Question::list(['third_category_id' => $category_ids, 'status' => 1]);
            //查询该用户是否答过当前章节题目
            $user_answer_before = UserAnswer::where(['user_id' => $user_id, 'third_category_id' => $category_ids])->count();
            $exam_type = $user_answer_before ? $exam_type : 1;
//                Db::commit();
//            } catch (ValidateException|PDOException|Exception $e) {
//                Db::rollback();
//                $this->error($e->getMessage());
//            }
        } else { //混合刷题
            //版本ids
            $category_ids = $this->request->post('category_ids');
            if(empty($category_ids)){
                $this->error('请选择版本');
            }
            $category_ids = explode(',', $category_ids);
            if ($this->checkEditionUnlock($category_ids, $user_id) == 1) {
                $this->error('您选择的版本有未解锁的');
            }
            //查询选择版本所有的章节
            //查询系统配置混合刷题随机取数
            $exam_mixed_random_access = Config::where(['name' => 'exam_mixed_random_access'])->value('value');
            //查询对应版本对应数量的题目
            $questions = Question::where(['second_category_id' => ['in', $category_ids], 'status' => 1])->orderRaw('rand()')->limit(0, $exam_mixed_random_access)->select()->toArray();
            if (empty($questions)) {
                $this->error('暂无可答题目');
            }
            $user_answer_data_type2 = ['second_category_ids' => implode(',', $category_ids)];
            $user_answer_data = array_merge($user_answer_data, $user_answer_data_type2);
        }
        $operate_ret = $this->handleQuestionAnswer($user_answer_data, $questions, $type, $exam_type, $user_id, $category_ids, $data);
        if ($operate_ret) {
            $this->success('请求成功', $operate_ret);
        } else {
            $this->error('请求失败');
        }
    }

    /**
     * 处理答题逻辑
     *
     * @param $user_answer_data 用户答题数据
     * @param $questions 问题列表
     * @param $type 答题类型 1顺序刷题2混合刷题
     * @param $exam_type考试类型 1开始答题或重新答题2继续答题
     * @param $user_id 用户ID
     * @param $category_ids 分类ID（顺序刷题，则是章节ID；混合刷题，则是版本ids数组）
     * @param $data 返回数据
     * @return false
     */
    private function handleQuestionAnswer($user_answer_data, $questions, $type, $exam_type, $user_id, $category_ids, $data)
    {
        $user_answer_ret = true; //用户答题记录添加返回值
        $user_answer_question_ret = true; //用户答题记录题目记录添加返回值
        $user_answer_latest_ret = true; //更新最新答题记录状态返回值
        if ($exam_type == 1 || $type == 2) { //开始答题或重新答题、混合刷题
            //添加答题记录
            $user_answer_data['question_num'] = count($questions); //题目数量
            $user_answer_data['answer_question_num'] = 0; //已答题目数量
            $user_answer_data['wrong_question_num'] = 0; //错题总数
            $user_answer_data['accuracy'] = 100; //答题正确率
            $user_answer_ret = UserAnswer::create($user_answer_data);
            $user_answer_insert_id = $user_answer_ret->id;
            //处理并添加答题题目
            $user_answer_question_data = array_map(function ($question_item) use ($user_answer_insert_id, $user_id) {
                return ['user_answer_id' => $user_answer_insert_id, 'user_id' => $user_id, 'exam_question_id' => $question_item['id'], 'title' => $question_item['title'], 'option' => $question_item['option'], 'right_option' => $question_item['right_option'], 'analysis' => $question_item['analysis']];
            }, $questions);
            $user_answer_question_model = new AnswerQuestion();
            $user_answer_question_ret = $user_answer_question_model->saveAll($user_answer_question_data);
            //查询当前用户最新答题记录(状态为答题中)
//            $user_answer_latest = UserAnswer::where($user_answer_where)->order('createtime desc')->find();
//            if (!empty($user_answer_latest)) {
//                //处理之前的答题记录
//                $user_answer_latest_ret = UserAnswer::update(['status' => 4], ['id' => ['<>', $user_answer_latest['id']], 'user_id' => $user_id, 'status' => ['in', [1, 2, 3]]]);
//            }
        }

        //重新查询最新的答题记录
        $user_answer_where = $type == 1 ? ['type' => $type, 'user_id' => $user_id, 'third_category_id' => $category_ids] : ['type' => $type, 'user_id' => $user_id];
        $user_answer_latest_new = UserAnswer::where($user_answer_where)->order('createtime desc')->find();
        //查询该答题记录下所有题目
        $questions_new = AnswerQuestion::list(['user_answer_id' => $user_answer_latest_new['id']], 'id,exam_question_id,title,option,right_option,user_option,status', 'createtime desc');
        $user_collect_model = new Collect();
        $data['questions'] = array_map(function ($question_new_item) use ($user_collect_model, $user_id) {
            $question_new_item['option_arr'] = json_decode($question_new_item['option'], true);
            //查询是否收藏
            $is_collect = $user_collect_model->where(['type' => 2, 'user_id' => $user_id, 'detail_id' => $question_new_item['exam_question_id']])->count();
            $question_new_item['is_collect'] = $is_collect ? 2 : 1; //是否收藏 1未收藏2已收藏
            return $question_new_item;
        }, $questions_new);
        //查询已作答题目数量
        $questions_answered_num = AnswerQuestion::where(['user_answer_id' => $user_answer_latest_new['id'], 'status' => 2])->count();
        $data['user_answer_id'] = $user_answer_latest_new['id']; //答题记录ID
        $data['questions_total_num'] = count($data['questions']); //题目总数
        $data['questions_curnum'] = $questions_answered_num == $data['questions_total_num'] ? $questions_answered_num : ($questions_answered_num + 1); //当前题目编号

        return $user_answer_ret !== false && $user_answer_question_ret !== false && $user_answer_latest_ret !== false ? $data : false;
    }

    /**
     * 结束答题
     *
     * @param $user_answer_id 答题记录ID
     * @param $answer_time 答题时长（秒数）
     */
    public function endanswer()
    {
        //获取参数
        $user_answer_id = (int)$this->request->post('user_answer_id');
        $answer_time = (int)$this->request->post('answer_time'); //答题时长（秒数）
        $user_id = $this->auth->id;
        if (empty($user_answer_id) || empty($answer_time)) {
            $this->error('参数错误');
        }
        //答题时长小时数
        $answer_time_hour = bcdiv($answer_time, 3600, 2);
        $user_answer_model = new UserAnswer();
        //查询当前答题记录
        $user_answer = $user_answer_model->where(['id' => $user_answer_id, 'user_id' => $user_id])->find();
        if (empty($user_answer)) {
            $this->error('答题记录不存在');
        }
        if ($user_answer['status'] == 3) {
            $this->error('已全部答完');
        }
        //查询当前答题记录题目总数
        $user_answer_question_totalnum = AnswerQuestion::where(['user_answer_id' => $user_answer_id, 'user_id' => $user_id])->count();
        //查询当前答题记录已答题目总数
        $user_answer_question_answerednum = AnswerQuestion::where(['user_answer_id' => $user_answer_id, 'user_id' => $user_id, 'status' => 2])->count();
        //结束当前答题
        $user_answer_ret = $user_answer_model->update(['status' => $user_answer_question_totalnum == $user_answer_question_answerednum ? 3 : 2, 'answer_time' => bcadd($user_answer['answer_time'], $answer_time_hour, 2)], ['id' => $user_answer_id]);
        //查询通关记录
        $answerpass_count = AnswerPass::where(['user_id' => $user_answer_id, 'exam_type_id' => $user_answer['exam_type_id'], 'top_category_id' => $user_answer['top_category_id']])->count();
        $user_goldcoin_ret = true; //用户金币变动记录返回值
        $return_data = []; //返回数据
        $return_code = 1; //返回状态
        $return_msg = '结束答题成功'; //返回消息
        if (!$answerpass_count && $user_answer['type'] == 1) { //顺序刷题
            //查询当前答题记录对应科目
            $exam_question_category = QuestionCategory::where(['id' => $user_answer['top_category_id'], 'status' => 1])->find();
            if (empty($exam_question_category)) {
                $this->error('答题记录对应科目不存在');
            }
            //查询当前答题记录对应科目下所有版本ids
            $second_category_ids = QuestionCategory::where(['pid' => $user_answer['top_category_id'], 'level' => 2, 'status' => 1])->column('id');
            //查询当前答题记录对应科目下所有版本下的所有章节ids
            $third_category_ids = QuestionCategory::where(['pid' => ['in', $second_category_ids], 'level' => 3, 'status' => 1, 'question_totalnum' => ['>', 0]])->column('id');
            $has_answered = true; //是否有未全部答完的章节，需要处理通关逻辑
            $user_answer_max_accuracy = 0; //最高答题率
            foreach ($third_category_ids as $third_category_id) {
                //查询当前章节是否有全部答完的答题记录，没有则不需要处理通关逻辑
                if (!$user_answer_model->where(['type' => 1, 'user_id' => $user_id, 'third_category_id' => $third_category_id, 'status' => 3])->count()) {
                    $has_answered = false;
                    break;
                }
                //查询当前章节答题率最高的答题记录
                $user_answer_max_accuracy += $user_answer_model->where(['type' => 1, 'user_id' => $user_id, 'third_category_id' => $third_category_id, 'status' => 3])->order('accuracy desc')->limit(1)->value('accuracy');
            }
            if ($has_answered) { //处理通关逻辑
                $return_code = 3; //通关失败
                $return_msg = '通关失败'; //通关失败
                //该用户答题记录平均值
                $user_answer_average_accuracy = bcdiv($user_answer_max_accuracy, count($third_category_ids));
                //判断当前科目下平均答题率是否满足通关条件
                if ($user_answer_average_accuracy >= $exam_question_category['accuracy']) {
                    //添加通关记录
                    $user_answer_pass = AnswerPass::create(['user_id' => $user_id, 'exam_type_id' => $exam_question_category['exam_type_id'], 'top_category_id' => $exam_question_category['id']]);
                    //添加金币变动记录
                    $user_goldcoin_ret = User::goldcoin($exam_question_category['goldcoin'], $user_id, 1, $user_answer_pass->id, '顺序刷题通关奖励');
                    $return_data = ['question_totalnum' => $exam_question_category['question_totalnum'], 'average_accuracy' => $user_answer_average_accuracy, 'goldcoin' => $exam_question_category['goldcoin']];
                    $return_code = 2; //通关成功
                    $return_msg = '通关成功'; //通关成功
                }
            }
        }
        if ($user_answer_ret !== false && $user_goldcoin_ret !== false) {
            //更新用户答题时长
            User::where(['id' => $user_id])->setInc('answer_time', $answer_time_hour);
            $this->success($return_msg, $return_data, $return_code);
        } else {
            $this->error('结束答题失败');
        }
    }

    /**
     * 答题
     *
     * @param $type 答题类型 1普通答题2错题答题
     */
    public function answer()
    {
        //获取参数
        $type = (int)$this->request->post('type', 1); //类型 1普通答题2错题答题
        switch ($type) {
            case 1:
                $this->handleCommonAnswer();
                break;
            case 2:
                $this->handleWrongQuestionAnswer();
                break;
        }
    }

    /**
     * 普通刷题
     *
     * @param $user_answer_id 答题记录ID
     * @param $user_answer_question_id 答题题目ID
     * @param $user_option 用户选项
     */
    public function handleCommonAnswer()
    {
        $user_answer_id = (int)$this->request->post('user_answer_id');
        $user_answer_question_id = (int)$this->request->post('user_answer_question_id');
        $user_option = $this->request->post('user_option');
        $user_id = $this->auth->id;
        if (empty($user_answer_id) || empty($user_answer_question_id) || empty($user_option)) {
            $this->error('参数错误');
        }
        //查询当前答题记录
        $user_answer = UserAnswer::where(['id' => $user_answer_id, 'user_id' => $user_id, 'status' => ['in', [1, 2]]])->find();
        if (empty($user_answer)) {
            $this->error('答题记录不存在');
        }
        //查询该答题题目
        $user_answer_question = AnswerQuestion::where(['id' => $user_answer_question_id, 'user_id' => $user_id])->find();
        if (empty($user_answer_question)) {
            $this->error('答题题目不存在');
        }
        if($user_answer_question['status'] == 2){
            $this->error('已回答请勿重复作答');
        }
        //查询当前用户数据
        $user = User::field('id,total_answer_num,correct_answer_num,wrong_answer_num,accuracy')->where(['id' => $user_id])->find();
        //用户答题题目更新数据
        $user_answer_question_update = ['user_option' => $user_option, 'status' => 2, 'answer_time' => time()];
        //用户答题记录更新数据
        $user_answer_update = ['answer_question_num' => $user_answer['answer_question_num'] + 1];
        $user_wrong_question_ret = true;
        //查询错题记录
        $user_wrong_question = WrongQuestion::where(['user_id' => $user_id, 'exam_question_id' => $user_answer_question['exam_question_id']])->find();
        if ($user_option == $user_answer_question['right_option']) { //选项正确
            $user_answer_question_update['result'] = 1;
            $user_answer_update['wrong_question_num'] = $user_answer['wrong_question_num'];
            $msg = '回答正确';
            //移除错题库
            if (!empty($user_wrong_question)) {
                $user_wrong_question_ret = $user_wrong_question->delete();
            }
            $user->correct_answer_num += 1;
        } else { //选项错误
            $user_answer_question_update['result'] = 2;
            $user_answer_update['wrong_question_num'] = $user_answer['wrong_question_num'] + 1;
            $msg = '回答错误';
            //加入错题库
            if (empty($user_wrong_question)) {
                $user_wrong_question_ret = WrongQuestion::create(['user_id' => $user_id, 'exam_question_id' => $user_answer_question['exam_question_id'], 'user_answer_id' => $user_answer_id, 'user_answer_question_id' => $user_answer_question_id, 'title' => $user_answer_question['title'], 'option' => $user_answer_question['option'], 'right_option' => $user_answer_question['right_option'], 'user_option' => $user_option]);
            }
            $user->wrong_answer_num += 1;
        }
        //计算正确率
        $user_answer_update['accuracy'] = $user_answer_update['answer_question_num'] > 0 ? (bcdiv(bcsub($user_answer_update['answer_question_num'], $user_answer_update['wrong_question_num']), $user_answer_update['answer_question_num'], 2)) * 100 : 0;
        //更新用户答题
        $user_answer_question_ret = AnswerQuestion::update($user_answer_question_update, ['id' => $user_answer_question_id]);
        //处理用户答题记录
        $user_answer_ret = UserAnswer::update($user_answer_update, ['id' => $user_answer_id]);
        //更新用户数据
        $user->total_answer_num += 1; //总答题数加一
        $user->accuracy = bcdiv($user->correct_answer_num, $user->total_answer_num, 2) * 100;
        $user_ret = $user->save();
        if ($user_answer_question_ret !== false && $user_answer_ret !== false && $user_wrong_question_ret !== false && $user_ret !== false) {
            $this->success($msg, $user_answer_question_ret);
        } else {
            $this->error('答题失败', $user_answer_question_ret);
        }
    }

    /**
     * 错题回答
     *
     * @param $wrong_question_id 错题记录ID
     * @param $user_option 用户选项
     */
    private function handleWrongQuestionAnswer()
    {
        //获取参数
        $wrong_question_id = (int)$this->request->post('wrong_question_id');
        $user_option = $this->request->post('user_option');
        $user_id = $this->auth->id;
        if (empty($wrong_question_id) || empty($user_option)) {
            $this->error('参数错误');
        }
        //查询当前错题信息
        $wrong_question = WrongQuestion::where(['id' => $wrong_question_id, 'user_id' => $user_id])->find();
        if (empty($wrong_question)) {
            $this->error('该错题不存在');
        }
        $wrong_question_delete = true;
        $msg = '回答错误';
        //更新错题回答选项
        $wrong_question_update = WrongQuestion::update(['user_option' => $user_option], ['id' => $wrong_question_id]);
        if ($user_option == $wrong_question['right_option']) { //回答正确
            //移除错题库
            $wrong_question_delete = WrongQuestion::destroy($wrong_question_id);
            $msg = '回答正确';
        }
        //移除错题库
        if ($wrong_question_delete !== false && $wrong_question_update !== false) {
            $data = ['user_option' => $user_option];
            $this->success($msg, $data);
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 错题列表
     */
    public function wrongquestions()
    {
        //获取参数
        $page = $this->request->get('page', 1);
        $limit = $this->pagelimit;
        $user_id = $this->auth->id;
        //查询当前用户错题列表
        $wrong_questions = WrongQuestion::list(['user_id' => $user_id], 'id,title,createtime', 'createtime desc', $page, $limit);
        $this->success('请求成功', $wrong_questions);
    }

    /**
     * 错题详情
     *
     * @param $wrong_question_id 错题ID
     */
    public function wrongquestion()
    {
        //获取参数
        $wrong_question_id = (int)$this->request->get('wrong_question_id');
        $user_id = $this->auth->id;
        //查询当前错题信息
        $wrong_question = WrongQuestion::where(['id' => $wrong_question_id, 'user_id' => $user_id])->find();
        if (empty($wrong_question)) {
            $this->error('该错题不存在');
        }
        $wrong_question['user_option'] = '';
        $wrong_question['option_arr'] = json_decode($wrong_question['option'], true);
        //查询系统配置中联系老师微信二维码及说明
        $qrcode = ConfigModel::where(['name' => 'exam_teacher_wechat_qrcode'])->value('value');
        $wrong_question['exam_teacher_wechat_qrcode'] = imgAppendUrl('string', $qrcode, '');
        $wrong_question['exam_contact_teacher_desc'] = ConfigModel::where(['name' => 'exam_contact_teacher_desc'])->value('value');
        //查询是否收藏
        $is_collect = Collect::where(['type' => 2, 'user_id' => $user_id, 'detail_id' => $wrong_question['exam_question_id']])->count();
        $wrong_question['is_collect'] = $is_collect ? 2 : 1; //是否收藏 1未收藏2已收藏
        $this->success('请求成功', $wrong_question);
    }

}
