<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\admin\model\user\Collect;
use app\admin\model\material\Type;
use app\admin\model\material\Material as MaterialModel;
use app\common\model\User;
use app\admin\model\user\GoldcoinLog;

/**
 * 藏经阁接口
 */
class Material extends Api
{
    protected $noNeedLogin = ['list'];
    protected $noNeedRight = ['*'];

    /**
     * 藏经阁资料类型
     */
    public function type()
    {
        $data = Type::list(['status' => 1], 'id,name');
        $this->success('请求成功', $data);
    }

    /**
     * 资料列表
     *
     * @param $page 页数
     * @param $exam_type_id 备考类型ID
     * @param $material_type_id  资料类型ID
     * @param $keyword 关键词
     */
    public function list()
    {
        //获取参数
        $page = $this->request->get('page', 1);
        $exam_type_id = (int)$this->request->get('exam_type_id');
        $material_type_id = (int)$this->request->get('material_type_id');
        $keyword = $this->request->get('keyword');
        $limit = 10;
        $user_id = $this->auth->id;
        if (empty($exam_type_id) || empty($material_type_id)) {
            $this->error('参数错误');
        }
        //查询题目分类表中所有数据
        $schools = MaterialModel::list(['exam_type_id' => $exam_type_id, 'material_type_id' => $material_type_id, 'title' => ['like', '%' . $keyword . '%']], 'id,image,title,description,purchase_goldcoin', 'weigh desc, createtime desc', $page, $limit);
        $data = array_map(function ($item) use ($user_id) {
            //查询该用户是否收藏该校区
            $is_collect = Collect::where(['type' => 3, 'user_id' => $user_id, 'detail_id' => $item['id']])->count();
            $item['is_collect'] = $is_collect ? 2 : 1; //是否已收藏 1未收藏2已收藏
            $item['image'] = imgAppendUrl('string', $item['image'], ['image']);
            return $item;
        }, $schools);
        $this->success('请求成功', $data);
    }

    /**
     * 资料详情
     *
     * @param $material_id 资料ID
     */
    public function detail()
    {
        //获取参数
        $material_id = $this->request->get('material_id');
        $user_id = $this->auth->id;
        if (empty($material_id)) {
            $this->error('参数错误');
        }
        $material = MaterialModel::get(['id' => $material_id]);
        if (empty($material)) {
            $this->error('资料不存在');
        }
        $material['image'] = imgAppendUrl('string', $material['image'], ['image']);
        $material['content'] = htmlspecialchars_decode($material['content']);
        $material['createtime'] = date("Y-m-d H:i:s", $material['createtime']);
        //查询该用户是否是学员
        $user_is_student = User::where(['id' => $user_id])->value('is_student');
        //查询该用户是否已购买该资料
        $is_purchase = GoldcoinLog::where(['type' => 6, 'user_id' => $user_id, 'detail_id' => $material_id])->count();
        if ($user_is_student == 2 || $is_purchase) {
            $this->success('请求成功', $material);
        } else {
            $this->error('请支付金币', $material, 2);
        }
    }

    /**
     * 支付金币
     *
     * @param $material_id 资料ID
     */
    public function pay()
    {
        //获取参数
        $material_id = $this->request->post('material_id');
        $user_id = $this->auth->id;
        if (empty($material_id)) {
            $this->error('参数错误');
        }
        //查询该资料详情
        $material = MaterialModel::get(['id' => $material_id]);
        if (empty($material)) {
            $this->error('资料不存在');
        }
        //查询该用户是否是学员
        $user = User::field('id,is_student,goldcoin')->where(['id' => $user_id])->find();
        //查询该用户是否已购买该资料
        $is_purchase = GoldcoinLog::where(['type' => 6, 'user_id' => $user_id, 'material_id' => $material_id])->count();
        if ($user['is_student'] == 2 || $is_purchase) {
            $this->error('您不需要支付金币');
        }
        if ($user['goldcoin'] < $material['purchase_goldcoin']) {
            $this->error('金币余额不足');
        }
        //更新金币并添加金币变更记录
        $ret = User::goldcoin(-$material['purchase_goldcoin'], $user_id, 6, $material_id, '查看藏经阁资料扣除');
        if ($ret) {
            $this->success('支付成功');
        } else {
            $this->error('支付失败');
        }
    }

}
