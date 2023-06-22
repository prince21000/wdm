<?php

namespace app\admin\controller\ad;

use app\common\controller\Backend;
use fast\Tree;
use think\Db;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 广告管理
 *
 * @icon fa fa-circle-o
 */
class Ad extends Backend
{

    /**
     * Ad模型对象
     * @var \app\admin\model\ad\Ad
     */
    protected $model = null;
    protected $category_model = null;
    protected $examtype_model = null;
    protected $categorylist = [];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\ad\Ad;
        $this->category_model = new \app\admin\model\exam\QuestionCategory;
        $this->examtype_model = new \app\admin\model\exam\Type;

        $tree = Tree::instance();
        $tree->init(collection($this->category_model->where(['status' => 1, 'question_totalnum' => ['>', 0]])->order('weigh desc,id desc')->select())->toArray(), 'pid');
        $this->categorylist = $tree->getTreeList($tree->getTreeArray(0), 'name');
        $categorydata = [0 => ['pid' => 0, 'exam_type_id' => 0, 'level' => 0, 'name' => __('None')]];
        foreach ($this->categorylist as $k => $v) {
            $categorydata[$v['id']] = $v;
        }

        $this->view->assign("typeList", $this->model->getTypeList());
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("examtypeList", $this->examtype_model->getTypeArray(1, 1));
        $this->view->assign("categoryList", $categorydata);
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                ->with(['category', 'school'])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id', 'title', 'image', 'type', 'path', 'status', 'createtime', 'updatetime']);
                $row->visible(['category']);
                $row->getRelation('category')->visible(['name']);
                $row->visible(['school']);
                $row->getRelation('school')->visible(['name']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     *
     * @return string
     * @throws \think\Exception
     */
    public function add()
    {
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);

        if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
            $params[$this->dataLimitField] = $this->auth->id;
        }
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }
            $category_id = $this->request->post('category_id');
            if ($params['type'] == 4) {
                if (empty($category_id)) {
                    throw new Exception('请选择章节');
                }
                $params['detail_id'] = $category_id;
            }
            if ($params['type'] == 2 && empty($params['content'])) {
                throw new Exception('请输入详情内容');
            }
            if ($params['type'] == 3 && empty($params['path'])) {
                throw new Exception('请输入公众号文章链接');
            }
            if (($params['type'] == 4 || $params['type'] == 5) && empty($params['detail_id'])) {
                throw new Exception('请选择关联数据');
            }
            $result = $this->model->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($result === false) {
            $this->error(__('No rows were inserted'));
        }
        $this->success();
    }

    /**
     * 编辑
     *
     * @param $ids
     * @return string
     * @throws DbException
     * @throws \think\Exception
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $row['exam_type_id'] = null;
        //查询当前广告对应的数据
        if ($row['type'] == 4) {
            //查询该章节对应的备考类型ID
            $row['exam_type_id'] = $this->category_model->where(['id' => $row['detail_id']])->value('exam_type_id');
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }
        if (false === $this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            $category_id = $this->request->post('category_id');
            if ($params['type'] == 4) {
                if (empty($category_id)) {
                    throw new Exception('请选择章节');
                }
                $params['detail_id'] = $category_id;
            }
            if ($params['type'] == 2 && empty($params['content'])) {
                throw new Exception('请输入详情内容');
            }
            if ($params['type'] == 3 && empty($params['path'])) {
                throw new Exception('请输入公众号文章链接');
            }
            if (($params['type'] == 4 || $params['type'] == 5) && empty($params['detail_id'])) {
                throw new Exception('请选择关联数据');
            }
            $result = $row->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if (false === $result) {
            $this->error(__('No rows were updated'));
        }
        $this->success();
    }

}
