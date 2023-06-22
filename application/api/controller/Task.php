<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Config;
use app\admin\model\user\DoumiMarket;

/**
 * 定时任务
 */
class Task extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 下架斗米集市摆摊 ------------- 每分钟执行一次
     */
    public function offshelfmarket(){
        //查询斗米集市自动下架时间配置
        $market_auto_offshelf_hour = Config::where(['name' => 'market_auto_offshelf_hour'])->value('value');
        //下架符合下架时间的摆摊数据
        $time = time();
        $market = DoumiMarket::update(['offshelftime' => $time, 'status' => 4], ['status' => 2, 'createtime' => ['<', bcsub($time, $market_auto_offshelf_hour * 60 * 60)]]);
        $this->success('请求成功', $market);
    }

}
