<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\admin\model\school\School as SchoolModel;
use app\admin\model\Label;
use app\admin\model\school\Campus;
use app\admin\model\user\Collect;

/**
 * 门派接口（院校）
 */
class School extends Api
{
    protected $noNeedLogin = ['list'];
    protected $noNeedRight = ['*'];

    /**
     * 院校列表
     *
     * @param $province_id 省份ID
     * @param $label_id 标签ID
     * @param $keyword 关键词
     */
    public function list()
    {
        //获取参数
        $province_id = (int)$this->request->get('province_id');
        $label_id = (int)$this->request->get('label_id');
        $keyword = $this->request->get('keyword');
        if (empty($province_id)) {
            $this->error('参数错误');
        }
        $where = ['province_id' => $province_id];
        if($label_id){
            $where['label_ids'] = ['like', '%' . $label_id . '%'];
        }
        if($keyword){
            $where['name'] = ['like', '%' . $keyword . '%'];
        }
        //查询题目分类表中所有数据
        $schools = SchoolModel::list($where, 'id,name,logo');
        $schools = imgAppendUrl('arr', $schools, ['logo']);
        $this->success('请求成功', $schools);
    }

    /**
     * 院校详情
     *
     * @param $school_id 院校ID
     */
    public function detail()
    {
        //获取参数
        $school_id = $this->request->get('school_id');
        $user_id = $this->auth->id;
        if (empty($school_id)) {
            $this->error('参数错误');
        }
        $school = SchoolModel::get(['id' => $school_id]);
        $school['images'] = explode(',', $school['images']);
        $school['images'] = imgAppendUrl('rownokey', $school['images'], '');
        $data = imgAppendUrl('row', $school, ['logo']);
        //查询标签内容
        $data['labels'] = Label::list(['id' => ['in', explode(',', $school['label_ids'])]], 'id,name', 'createtime desc');
        $data['content'] = htmlspecialchars_decode($data['content']);
        //查询校区内容
        $data['compus'] = Campus::list(['id' => $school_id], 'id,name,address');
        //查询该用户是否收藏该校区
        $is_collect = Collect::where(['type' => 1, 'user_id' => $user_id, 'detail_id' => $school_id])->count();
        $data['is_collect'] = $is_collect ? 2 : 1; //是否已收藏 1未收藏2已收藏
        $this->success('请求成功', $data);
    }
}
