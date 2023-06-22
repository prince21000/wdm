<?php

namespace application\admin\controller\exam;

use application\admin\library\Auth;
use application\common\controller\Backend;
use fast\Tree;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as DateChange;

/**
 * 考试 - 题目管理
 *
 * @icon fa fa-circle-o
 */
class Question extends Backend1
{

    /**
     * Question模型对象
     * @var \app\admin\model\exam\Question
     */
    protected $model = null;
    protected $category_model = null;
    protected $examtype_model = null;
    protected $categorylist = [];
    protected $option_key = ["A", "B", "C", "D", "E", "F", "G", "H"];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\exam\Question;
        $this->category_model = new \app\admin\model\exam\QuestionCategory;
        $this->examtype_model = new \app\admin\model\exam\Type;

        $tree = Tree::instance();
        $tree->init(collection($this->category_model->order('weigh desc,id desc')->select())->toArray(), 'pid');
        $this->categorylist = $tree->getTreeList($tree->getTreeArray(0), 'name');
        $categorydata = [0 => ['pid' => 0, 'exam_type_id' => 0, 'level' => 0, 'name' => __('None')]];
        foreach ($this->categorylist as $k => $v) {
            $categorydata[$v['id']] = $v;
        }

        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("typeList", $this->examtype_model->getTypeArray(1, 1));
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
                ->with(['examtype', 'topcate', 'secondcate', 'thirdcate'])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id', 'title', 'option', 'right_option', 'status', 'createtime', 'updatetime', 'deletetime']);
                $row->visible(['examtype']);
                $row->getRelation('examtype')->visible(['name']);
                $row->visible(['topcate']);
                $row->getRelation('topcate')->visible(['name']);
                $row->visible(['secondcate']);
                $row->getRelation('secondcate')->visible(['name']);
                $row->visible(['thirdcate']);
                $row->getRelation('thirdcate')->visible(['name']);
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
            if (empty($params['title'])) {
                throw new Exception('题目不能为空');
            }
            if (empty($params['option'])) {
                throw new Exception('请添加选项');
            }
            //选项数组
            $option_arr = array_merge($params['option']);
            if (!is_array($option_arr)) {
                throw new Exception('选项内容有误');
            }
            if (count($option_arr) > count($this->option_key)) {
                throw new Exception('选项数量不能超过' . count($this->option_key) . '个');
            }
            $option_ret = 0; //是否有答案
            foreach ($option_arr as $key => $option) {
                $option_arr[$key]['option'] = $this->option_key[$key];
                if ($option['is_answer'] == 1) {
                    $option_ret += 1;
                    $params['right_option'] = $option_arr[$key]['option'];
                }
            }
            if ($option_ret !== 1) {
                throw new Exception('选项答案必须有一个');
            }
            $params['option'] = json_encode($option_arr, JSON_UNESCAPED_UNICODE);
            //查询当前章节（三级分类）
            $cur_category = $this->category_model->get($params['category_id'])->toArray();
            if (empty($cur_category)) {
                throw new Exception('该章节不存在');
            }
            if ($cur_category['level'] != 3) {
                throw new Exception('请选择正确的章节');
            }
            $params['category_path'] = $cur_category['path'];
            $path_arr = explode('/', trim($params['category_path'], '/'));
            $params['top_category_id'] = $path_arr[0];
            $params['second_category_id'] = $path_arr[1];
            $params['third_category_id'] = $path_arr[2];
            unset($params['category_id']);

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

            if (empty($params['title'])) {
                throw new Exception('题目不能为空');
            }
            if (empty($params['option'])) {
                throw new Exception('请添加选项');
            }
            //选项数组
            $option_arr = array_merge($params['option']);
            if (!is_array($option_arr)) {
                throw new Exception('选项内容有误');
            }
            if (count($option_arr) > count($this->option_key)) {
                throw new Exception('选项数量不能超过' . count($this->option_key) . '个');
            }
            $option_ret = 0; //是否有答案
            foreach ($option_arr as $key => $option) {
                $option_arr[$key]['option'] = $this->option_key[$key];
                if ($option['is_answer'] == 1) {
                    $option_ret += 1;
                    $params['right_option'] = $option_arr[$key]['option'];
                }
            }
            if ($option_ret !== 1) {
                throw new Exception('选项答案必须有一个');
            }
            $params['option'] = json_encode($option_arr, JSON_UNESCAPED_UNICODE);
            //查询当前章节（三级分类）
            $cur_category = $this->category_model->get($params['category_id'])->toArray();
            if (empty($cur_category)) {
                throw new Exception('该章节不存在');
            }
            if ($cur_category['level'] != 3) {
                throw new Exception('请选择正确的章节');
            }
            $params['category_path'] = $cur_category['path'];
            $path_arr = explode('/', trim($params['category_path'], '/'));
            $params['top_category_id'] = $path_arr[0];
            $params['second_category_id'] = $path_arr[1];
            $params['third_category_id'] = $path_arr[2];
            unset($params['category_id']);

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

    /**
     * 删除
     *
     * @param $ids
     * @return void
     * @throws DbException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function del($ids = null)
    {
        if (false === $this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ?: $this->request->post("ids");
        if (empty($ids)) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }
        $pk = $this->model->getPk();
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $this->model->where($this->dataLimitField, 'in', $adminIds);
        }
        $list = $this->model->where($pk, 'in', $ids)->select();

        $count = 0;
        Db::startTrans();
        try {
            foreach ($list as $item) {
                $count += $item->delete();
            }
            Db::commit();
        } catch (PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success();
        }
        $this->error(__('No rows were deleted'));
    }

    /**
     * 导出EXCEL
     * 导入Excel方法
     */
    public function import()
    {
        $file = $this->request->request('file');
        if (!$file) {
            $this->error(__('Parameter %s can not be empty', 'file'));
        }
        $filePath = ROOT_PATH . DS . 'public' . DS . $file;
        if (!is_file($filePath)) {
            $this->error(__('No results were found'));
        }
        //实例化reader
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
            $this->error(__('Unknown data format'));
        }
        if ($ext === 'csv') {
            $file = fopen($filePath, 'r');
            $filePath = tempnam(sys_get_temp_dir(), 'import_csv');
            $fp = fopen($filePath, "w");
            $n = 0;
            while ($line = fgets($file)) {
                $line = rtrim($line, "\n\r\0");
                $encoding = mb_detect_encoding($line, ['utf-8', 'gbk', 'latin1', 'big5']);
                if ($encoding != 'utf-8') {
                    $line = mb_convert_encoding($line, 'utf-8', $encoding);
                }
                if ($n == 0 || preg_match('/^".*"$/', $line)) {
                    fwrite($fp, $line . "\n");
                } else {
                    fwrite($fp, '"' . str_replace(['"', ','], ['""', '","'], $line) . "\"\n");
                }
                $n++;
            }
            fclose($file) || fclose($fp);

            $reader = new Csv();
        } elseif ($ext === 'xls') {
            $reader = new Xls();
        } else {
            $reader = new Xlsx();
        }

        //导入文件首行类型,默认是注释,如果需要使用字段名称请使用name
        $importHeadType = isset($this->importHeadType) ? $this->importHeadType : 'comment';

        $table = $this->model->getQuery()->getTable();
        $database = \think\Config::get('database.database');
        $fieldArr = [];
        $list = db()->query("SELECT COLUMN_NAME,COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ?", [$table, $database]);
        foreach ($list as $k => $v) {
            if ($importHeadType == 'comment') {
                $fieldArr[$v['COLUMN_COMMENT']] = $v['COLUMN_NAME'];
            } else {
                $fieldArr[$v['COLUMN_NAME']] = $v['COLUMN_NAME'];
            }
        }
        //加载文件
        $insert = [];
        try {
            if (!$PHPExcel = $reader->load($filePath)) {
                $this->error(__('Unknown data format'));
            }
            $currentSheet = $PHPExcel->getSheet(0);  //读取文件中的第一个工作表
            $allColumn = $currentSheet->getHighestDataColumn(); //取得最大的列号
            $allRow = $currentSheet->getHighestRow(); //取得一共有多少行
            $maxColumnNumber = Coordinate::columnIndexFromString($allColumn);
            $fields = [];
            for ($currentRow = 1; $currentRow <= 1; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $fields[] = $val;
                }
            }

            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                $values = [];
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $cell = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow);
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    if ($cell->getDataType() == DataType::TYPE_NUMERIC) {
                        $cellstyleformat = $cell->getStyle($cell->getCoordinate())->getNumberFormat();

                        $formatcode = $cellstyleformat->getFormatCode();

                        if (preg_match('/^(\[\$[A-Z]*-[0-9A-F]*\])*[hmsdy]/i', $formatcode)) {
                            $val = DateChange::excelToTimestamp($val);
                            $num = 8 * 60 * 60;
                            $val = $val - $num;
                        }

                    }
                    $values[] = is_null($val) ? '' : $val;
                }
                $row = [];
                $temp = array_combine($fields, $values);
                foreach ($temp as $k => $v) {
                    if (isset($fieldArr[$k]) && $k !== '') {
                        $row[$fieldArr[$k]] = $v;
                    }
                    if ($k == '备考类型（必填）') {
                        $row['exam_type_name'] = $v;
                    }
                    if ($k == '所属章节（必填）') {
                        $row['third_category_name'] = $v;
                    }
                    if ($k == '题目（必填）') {
                        $row['title'] = $v;
                    }
                    if ($k == '正确答案（必填）') {
                        $row['right_option'] = $v;
                    }
                    if (strstr($k, '选项') !== false && $v !== '') {
                        $row['option'][]['content'] = $v;
                    }
                }
                foreach ($row['option'] as $row_option_k => $row_option_v) {
                    $row['option'][$row_option_k]['option'] = $this->option_key[$row_option_k];
                    $row['option'][$row_option_k]['is_answer'] = $this->option_key[$row_option_k] == $row['right_option'] ? 1 : 0;
                }
                if ($row) {
                    $insert[] = $row;
                }
            }
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
        if (!$insert) {
            $this->error(__('No rows were updated'));
        }

        try {
            //是否包含admin_id字段
            $has_admin_id = false;
            foreach ($fieldArr as $name => $key) {
                if ($key == 'admin_id') {
                    $has_admin_id = true;
                    break;
                }
            }
//            if ($has_admin_id) {
            $auth = Auth::instance();
            foreach ($insert as $key => $val) {
                if ($has_admin_id && (!isset($val['admin_id']) || empty($val['admin_id']))) {
                    $val['admin_id'] = $auth->isLogin() ? $auth->id : 0;
                }
                //查询到当前备考类型
                $cur_examtype = $this->examtype_model->get(['name' => $val['exam_type_name'], 'status' => 1]);
                if (empty($cur_examtype)) {
                    unset($insert[$key]);
                    continue;
                }
                //查询到当前分类
                $cur_category = $this->category_model->get(['exam_type_id' => $cur_examtype['id'], 'name' => $val['third_category_name'], 'level' => 3]);
                if (empty($cur_category)) {
                    unset($insert[$key]);
                    continue;
                }
                $val['exam_type_id'] = $cur_examtype['id'];
                $val['third_category_id'] = $cur_category['id'];
                $val['category_path'] = $cur_category['path'];
                $path_arr = explode('/', trim($val['category_path'], '/'));
                $val['top_category_id'] = $path_arr[0];
                $val['second_category_id'] = $path_arr[1];
                unset($val['exam_type_name']);
                unset($val['third_category_name']);
                //验证答案数量以及正确答案数量
                $right_option_num = 0;
                foreach ($val['option'] as $option_v) {
                    if ($option_v['is_answer'] == 1) {
                        $right_option_num += 1;
                    }
                }
                if (count($val['option']) < 2 || count($val['option']) > 8 || $right_option_num !== 1) {
                    unset($insert[$key]);
                    unset($right_option_num);
                    continue;
                }
                $val['option'] = json_encode($val['option'], JSON_UNESCAPED_UNICODE);
                $insert[$key] = $val;
            }
//            }
            \think\Log::write(['question_import_data' => $insert], 'log', true);
            $this->model->saveAll($insert);
        } catch (PDOException $exception) {
            $msg = $exception->getMessage();
            if (preg_match("/.+Integrity constraint violation: 1062 Duplicate entry '(.+)' for key '(.+)'/is", $msg, $matches)) {
                $msg = "导入失败，包含【{$matches[1]}】的记录已存在";
            };
            $this->error($msg);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success();
    }

    /**
     * 获取并组装答案选项
     */
    public function getOptionKey()
    {
        if ($this->request->isPost()) {
            $option_centent = $this->request->post('option_content');
            if (empty($option_centent)) {
                $this->error('参数错误');
            }
            $option_centent_arr = json_decode($option_centent, true);
            if (!is_array($option_centent_arr)) {
                $this->error('选项内容有误');
            }
            foreach ($option_centent_arr as $key => $val) {
                $option_centent_arr[$key]['option'] = $this->option_key[$key];
            }

            $this->success('ok', null, json_encode($option_centent_arr));
        }
    }

}
