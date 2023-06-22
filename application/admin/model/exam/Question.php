<?php

namespace app\admin\model\exam;

use think\Model;
use traits\model\SoftDelete;
use app\admin\model\exam\QuestionCategory;

class Question extends Model
{

    use SoftDelete;


    // 表名
    protected $name = 'exam_question';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    protected $resultSetType = 'collection';

    // 追加属性
    protected $append = [
        'status_text'
    ];

    protected static function init()
    {
        /**
         * 添加后操作
         */
        self::afterInsert(function ($row) {
            self::handleQuestionCategory();
        });

        /**
         * 更新后操作
         */
        self::afterUpdate(function ($row) {
            self::handleQuestionCategory();
        });

        /**
         * 删除后操作
         */
        self::afterDelete(function ($row) {
            self::handleQuestionCategory();
        });
    }

    /**
     * 更新题目分类表数据
     */
    public static function handleQuestionCategory()
    {
        $question_model = new QuestionCategory();
        //查询所有分类
        $categorys = $question_model->field('id,level,question_totalnum')->select()->toArray();
        $data = array_map(function ($item) {
            $where['status'] = 1;
            switch ($item['level']) {
                case 1:
                    $where['top_category_id'] = $item['id'];
                    break;
                case 2:
                    $where['second_category_id'] = $item['id'];
                    break;
                case 3:
                    $where['third_category_id'] = $item['id'];
                    break;
            }
            $cur_question_totalnum = self::where($where)->count();
            $cur_question_category = ['id' => $item['id'], 'question_totalnum' => $cur_question_totalnum];
            return $cur_question_category;
        }, $categorys);
        $question_model->saveAll($data);
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
     * 备考类型
     */
    public function examtype()
    {
        return $this->belongsTo('Type', 'exam_type_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * 科目（一级分类）
     */
    public function topcate()
    {
        return $this->belongsTo('QuestionCategory', 'top_category_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * 版本（二级分类）
     */
    public function secondcate()
    {
        return $this->belongsTo('QuestionCategory', 'second_category_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * 章节（三级分类）
     */
    public function thirdcate()
    {
        return $this->belongsTo('QuestionCategory', 'third_category_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * @param $where 查询条件
     * @param $field 查询字段
     * @param $order 排序
     * @return array
     */
    public static function list($where = [], $field = '*', $order = 'createtime desc'): array
    {
        $data = self::field($field)->where($where)->order($order)->select()->toArray();
        return $data;
    }

}
