<?php

namespace app\admin\model\user;

use think\Model;
use traits\model\SoftDelete;

class AnswerPass extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'user_answer_pass';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [

    ];


    public function user()
    {
        return $this->belongsTo('app\admin\model\User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function type()
    {
        return $this->belongsTo('app\admin\model\exam\Type', 'exam_type_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function category()
    {
        return $this->belongsTo('app\admin\model\exam\QuestionCategory', 'top_category_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

}
