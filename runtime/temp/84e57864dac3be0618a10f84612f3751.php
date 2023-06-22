<?php if (!defined('THINK_PATH')) exit(); /*a:4:{s:82:"/www/wwwroot/xcx.wdmnb.com/public/../application/admin/view/exam/question/add.html";i:1679568468;s:69:"/www/wwwroot/xcx.wdmnb.com/application/admin/view/layout/default.html";i:1671020444;s:66:"/www/wwwroot/xcx.wdmnb.com/application/admin/view/common/meta.html";i:1671020444;s:68:"/www/wwwroot/xcx.wdmnb.com/application/admin/view/common/script.html";i:1671020444;}*/ ?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
<title><?php echo (isset($title) && ($title !== '')?$title:''); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<meta name="renderer" content="webkit">
<meta name="referrer" content="never">
<meta name="robots" content="noindex, nofollow">

<link rel="shortcut icon" href="/assets/img/favicon.ico" />
<!-- Loading Bootstrap -->
<link href="/assets/css/backend<?php echo \think\Config::get('app_debug')?'':'.min'; ?>.css?v=<?php echo \think\Config::get('site.version'); ?>" rel="stylesheet">

<?php if(\think\Config::get('fastadmin.adminskin')): ?>
<link href="/assets/css/skins/<?php echo \think\Config::get('fastadmin.adminskin'); ?>.css?v=<?php echo \think\Config::get('site.version'); ?>" rel="stylesheet">
<?php endif; ?>

<!-- HTML5 shim, for IE6-8 support of HTML5 elements. All other JS at the end of file. -->
<!--[if lt IE 9]>
  <script src="/assets/js/html5shiv.js"></script>
  <script src="/assets/js/respond.min.js"></script>
<![endif]-->
<script type="text/javascript">
    var require = {
        config:  <?php echo json_encode($config); ?>
    };
</script>

    </head>

    <body class="inside-header inside-aside <?php echo defined('IS_DIALOG') && IS_DIALOG ? 'is-dialog' : ''; ?>">
        <div id="main" role="main">
            <div class="tab-content tab-addtabs">
                <div id="content">
                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                            <section class="content-header hide">
                                <h1>
                                    <?php echo __('Dashboard'); ?>
                                    <small><?php echo __('Control panel'); ?></small>
                                </h1>
                            </section>
                            <?php if(!IS_DIALOG && !\think\Config::get('fastadmin.multiplenav') && \think\Config::get('fastadmin.breadcrumb')): ?>
                            <!-- RIBBON -->
                            <div id="ribbon">
                                <ol class="breadcrumb pull-left">
                                    <?php if($auth->check('dashboard')): ?>
                                    <li><a href="dashboard" class="addtabsit"><i class="fa fa-dashboard"></i> <?php echo __('Dashboard'); ?></a></li>
                                    <?php endif; ?>
                                </ol>
                                <ol class="breadcrumb pull-right">
                                    <?php foreach($breadcrumb as $vo): ?>
                                    <li><a href="javascript:;" data-url="<?php echo $vo['url']; ?>"><?php echo $vo['title']; ?></a></li>
                                    <?php endforeach; ?>
                                </ol>
                            </div>
                            <!-- END RIBBON -->
                            <?php endif; ?>
                            <div class="content">
                                <form id="add-form" class="form-horizontal" role="form" data-toggle="validator" method="POST" action="">

    <!--    <div class="form-group">-->
    <!--        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Top_category_id'); ?>:</label>-->
    <!--        <div class="col-xs-12 col-sm-8">-->
    <!--            <input id="c-top_category_id" data-rule="required" min="0" data-source="top/category/index"-->
    <!--                   class="form-control selectpage" name="row[top_category_id]" type="text" value="">-->
    <!--        </div>-->
    <!--    </div>-->
    <!--    <div class="form-group">-->
    <!--        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Second_category_id'); ?>:</label>-->
    <!--        <div class="col-xs-12 col-sm-8">-->
    <!--            <input id="c-second_category_id" data-rule="required" min="0" data-source="second/category/index"-->
    <!--                   class="form-control selectpage" name="row[second_category_id]" type="text" value="">-->
    <!--        </div>-->
    <!--    </div>-->
    <!--    <div class="form-group">-->
    <!--        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Third_category_id'); ?>:</label>-->
    <!--        <div class="col-xs-12 col-sm-8">-->
    <!--            <input id="c-third_category_id" data-rule="required" min="0" data-source="third/category/index"-->
    <!--                   class="form-control selectpage" name="row[third_category_id]" type="text" value="">-->
    <!--        </div>-->
    <!--    </div>-->

    <div class="form-group">
        <label for="c-exam_type_id" class="control-label col-xs-12 col-sm-2"><?php echo __('Exam_type_id'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <select id="c-exam_type_id" data-rule="required" class="form-control selectpicker" name="row[exam_type_id]">
                <?php if(is_array($typeList) || $typeList instanceof \think\Collection || $typeList instanceof \think\Paginator): if( count($typeList)==0 ) : echo "" ;else: foreach($typeList as $key=>$vo): ?>
                <option value="<?php echo $key; ?>" {in name="key" value="" }selected{
                /in}><?php echo $vo; ?></option>
                <?php endforeach; endif; else: echo "" ;endif; ?>
            </select>
        </div>
    </div>
    <div class="form-group">
        <label for="c-category_id" class="control-label col-xs-12 col-sm-2"><?php echo __('Category_id'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <select id="c-category_id" data-rule="required" class="form-control selectpicker" name="row[category_id]">
                <?php if(is_array($categoryList) || $categoryList instanceof \think\Collection || $categoryList instanceof \think\Paginator): if( count($categoryList)==0 ) : echo "" ;else: foreach($categoryList as $key=>$vo): ?>
                <option data-type="<?php echo $vo['exam_type_id']; ?>" value="<?php echo $key; ?>" {in name="key" value="" }selected{
                /in} <?php if($vo['level']!=3): ?>disabled<?php endif; ?>><?php echo $vo['name']; ?></option>
                <?php endforeach; endif; else: echo "" ;endif; ?>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Title'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-title" data-rule="required" class="form-control" name="row[title]" type="text">
        </div>
    </div>
    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Option'); ?>:</label>
        <div class="col-xs-12 col-sm-8" id="fieldlist-box">
            <table class="table fieldlist" data-template="basictpl" data-name="row[option]" id="option-table" data-tag="tr">
                <tr>
<!--                    <td><?php echo __('Option'); ?></td>-->
                    <td><?php echo __('Option_content'); ?></td>
                    <td><?php echo __('Is_answer'); ?></td>
                    <td><?php echo __('Operate'); ?></td>
                </tr>
                <tr>
                    <td colspan="5">
                        <a href="javascript:;" class="btn btn-sm btn-success btn-append">
                            <i class="fa fa-plus"></i> <?php echo __('Append'); ?></a>
                    </td>
                </tr>
                <!--请注意实际开发中textarea应该添加个hidden进行隐藏-->
                <textarea name="row[option]" id="option-content" class="form-control hidden" cols="30" rows="5"></textarea>
            </table>

            <script id="basictpl" type="text/html">
                <tr class="form-inline">
<!--                    <td>-->
<!--                        <input type="text" name="<%=name%>[<%=index%>][option]" class="form-control" size="2" value="<%=row.option%>" placeholder="<?php echo __('Option'); ?>"  />-->
<!--                    </td>-->
                    <td>
                        <input type="text" name="<%=name%>[<%=index%>][content]" class="form-control" size="36" value="<%=row.content%>" placeholder="<?php echo __('Option_content'); ?>"/>
                    </td>
                    <td>
                        <input type="hidden" name="<%=name%>[<%=index%>][is_answer]" id="c-is_answer-<%=index%>" class="form-control option-state" style="width:50px" value="0" placeholder="是否答案"/>
                        <a href="javascript:;" data-toggle="switcher" class="btn-switcher" data-input-id="c-is_answer-<%=index%>" data-yes="1" data-no="0" >
                            <i class="fa fa-toggle-on text-success fa-flip-horizontal text-gray fa-2x"></i>
                        </a>
                    </td>
                    <td>
                        <!--下面的两个按钮务必保留-->
                        <span class="btn btn-sm btn-danger btn-remove"><i class="fa fa-times"></i></span>
                        <span class="btn btn-sm btn-primary btn-dragsort"><i class="fa fa-arrows"></i></span>
                    </td>
                </tr>
            </script>
        </div>
    </div>
<!--    <div class="form-group">-->
<!--        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Right_option'); ?>:</label>-->
<!--        <div class="col-xs-12 col-sm-8">-->
<!--            <input id="c-right_option" class="form-control" name="row[right_option]" type="text">-->
<!--        </div>-->
<!--    </div>-->
<!--    <div class="form-group">-->
<!--        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Analysis'); ?>:</label>-->
<!--        <div class="col-xs-12 col-sm-8">-->
<!--            <textarea id="c-analysis" class="form-control " rows="5" name="row[analysis]" cols="50"></textarea>-->
<!--        </div>-->
<!--    </div>-->
    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Status'); ?>:</label>
        <div class="col-xs-12 col-sm-8">

            <div class="radio">
                <?php if(is_array($statusList) || $statusList instanceof \think\Collection || $statusList instanceof \think\Paginator): if( count($statusList)==0 ) : echo "" ;else: foreach($statusList as $key=>$vo): ?>
                <label for="row[status]-<?php echo $key; ?>"><input id="row[status]-<?php echo $key; ?>" name="row[status]" type="radio"
                                                       value="<?php echo $key; ?>" <?php if(in_array(($key), explode(',',"1"))): ?>checked<?php endif; ?> />
                    <?php echo $vo; ?></label>
                <?php endforeach; endif; else: echo "" ;endif; ?>
            </div>

        </div>
    </div>
    <div class="form-group layer-footer">
        <label class="control-label col-xs-12 col-sm-2"></label>
        <div class="col-xs-12 col-sm-8">
            <button type="submit" class="btn btn-primary btn-embossed disabled"><?php echo __('OK'); ?></button>
            <button type="reset" class="btn btn-default btn-embossed"><?php echo __('Reset'); ?></button>
        </div>
    </div>
</form>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="/assets/js/require<?php echo \think\Config::get('app_debug')?'':'.min'; ?>.js" data-main="/assets/js/require-backend<?php echo \think\Config::get('app_debug')?'':'.min'; ?>.js?v=<?php echo htmlentities($site['version']); ?>"></script>
    </body>
</html>
