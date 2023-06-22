<?php

namespace app\admin\controller;

use app\admin\model\user\Answer;
use app\common\controller\Backend;
use app\common\model\Attachment;
use fast\Date;
use think\Db;
use app\admin\model\Admin;
use app\admin\model\User;
use app\admin\model\exam\QuestionCategory;

/**
 * 控制台
 *
 * @icon   fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{

    /**
     * 查看
     */
    public function index()
    {
        try {
            \think\Db::execute("SET @@sql_mode='';");
        } catch (\Exception $e) {

        }
        $this->view->assign([
            'totaluser'         => User::count(), //用户总数
            'totalsubject'        => QuestionCategory::where(['level' => 1])->count(), //科目总数
            'totaledition'        => QuestionCategory::where(['level' => 2])->count(), //版本总数
            'totalchapter'        => QuestionCategory::where(['level' => 3])->count(), //章节总数
            'totalbrushquestion'        => Answer::group('user_id')->count(), //总刷题人数
            'totaladmin'        => Admin::count(),
//            'totalcategory'     => \app\common\model\Category::count(),
//            'todayusersignup'   => User::whereTime('jointime', 'today')->count(),
//            'todayuserlogin'    => User::whereTime('logintime', 'today')->count(),
//            'sevendau'          => User::whereTime('jointime|logintime|prevtime', '-7 days')->count(),
//            'thirtydau'         => User::whereTime('jointime|logintime|prevtime', '-30 days')->count(),
//            'threednu'          => User::whereTime('jointime', '-3 days')->count(),
//            'sevendnu'          => User::whereTime('jointime', '-7 days')->count(),
//            'dbtablenums'       => count($dbTableList),
//            'dbsize'            => array_sum(array_map(function ($item) {
//                return $item['Data_length'] + $item['Index_length'];
//            }, $dbTableList)),
//            'totalworkingaddon' => $totalworkingaddon,
//            'attachmentnums'    => Attachment::count(),
//            'attachmentsize'    => Attachment::sum('filesize'),
//            'picturenums'       => Attachment::where('mimetype', 'like', 'image/%')->count(),
//            'picturesize'       => Attachment::where('mimetype', 'like', 'image/%')->sum('filesize'),
        ]);

        return $this->view->fetch();
    }

}
