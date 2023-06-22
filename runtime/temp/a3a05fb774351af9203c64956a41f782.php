<?php if (!defined('THINK_PATH')) exit(); /*a:4:{s:91:"/www/wwwroot/xcx.wdmnb.com/public/../application/admin/view/exam/question_category/add.html";i:1679476654;s:69:"/www/wwwroot/xcx.wdmnb.com/application/admin/view/layout/default.html";i:1671020444;s:66:"/www/wwwroot/xcx.wdmnb.com/application/admin/view/common/meta.html";i:1671020444;s:68:"/www/wwwroot/xcx.wdmnb.com/application/admin/view/common/script.html";i:1671020444;}*/ ?>
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

    <div class="form-group">
        <label for="c-exam_type_id" class="control-label col-xs-12 col-sm-2"><?php echo __('Exam_type_id'); ?>:</label>
        <div class="col-xs-12 col-sm-8">

            <select id="c-exam_type_id" data-rule="required" class="form-control selectpicker" name="row[exam_type_id]">
                <?php if(is_array($typeList) || $typeList instanceof \think\Collection || $typeList instanceof \think\Paginator): if( count($typeList)==0 ) : echo "" ;else: foreach($typeList as $key=>$vo): ?>
                <option value="<?php echo $key; ?>" <?php if(in_array(($key), explode(',',""))): ?>selected<?php endif; ?>><?php echo $vo; ?></option>
                <?php endforeach; endif; else: echo "" ;endif; ?>
            </select>

        </div>
    </div>
    <div class="form-group">
        <label for="c-pid" class="control-label col-xs-12 col-sm-2"><?php echo __('Pid'); ?>:</label>
        <div class="col-xs-12 col-sm-8">

            <select id="c-pid" data-rule="required" class="form-control selectpicker" name="row[pid]">
                <?php if(is_array($parentList) || $parentList instanceof \think\Collection || $parentList instanceof \think\Paginator): if( count($parentList)==0 ) : echo "" ;else: foreach($parentList as $key=>$vo): ?>
                <option data-level="<?php echo $vo['level']; ?>" data-type="<?php echo $vo['exam_type_id']; ?>" value="<?php echo $key; ?>" <?php if(in_array(($key), explode(',',""))): ?>selected<?php endif; if($vo['level']==3): ?>disabled<?php endif; ?>><?php echo $vo['name']; ?></option>
                <?php endforeach; endif; else: echo "" ;endif; ?>
            </select>

        </div>
    </div>
    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Name'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-name" data-rule="required" class="form-control" name="row[name]" type="text" value="">
        </div>
    </div>
    <!--    <div class="form-group">-->
    <!--        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Level'); ?>:</label>-->
    <!--        <div class="col-xs-12 col-sm-8">-->
    <!--            <select id="c-level" data-rule="required" min="0" class="form-control selectpicker" name="row[level]">-->
    <!--                <?php if(is_array($levelList) || $levelList instanceof \think\Collection || $levelList instanceof \think\Paginator): if( count($levelList)==0 ) : echo "" ;else: foreach($levelList as $key=>$vo): ?>-->
    <!--                <option value="<?php echo $key; ?>" {in name="key" value="3" }selected{-->
    <!--                /in}><?php echo $vo; ?></option>-->
    <!--                <?php endforeach; endif; else: echo "" ;endif; ?>-->
    <!--            </select>-->
    <!--        </div>-->
    <!--    </div>-->
    <!--    <div class="form-group">-->
    <!--        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Image'); ?>:</label>-->
    <!--        <div class="col-xs-12 col-sm-8">-->
    <!--            <div class="input-group">-->
    <!--                <input id="c-image" data-rule="required" class="form-control" size="50" name="row[image]" type="text"-->
    <!--                       value="">-->
    <!--                <div class="input-group-addon no-border no-padding">-->
    <!--                    <span><button type="button" id="faupload-image" class="btn btn-danger faupload"-->
    <!--                                  data-input-id="c-image"-->
    <!--                                  data-mimetype="image/gif,image/jpeg,image/png,image/jpg,image/bmp,image/webp"-->
    <!--                                  data-multiple="false" data-preview-id="p-image"><i class="fa fa-upload"></i> <?php echo __('Upload'); ?></button></span>-->
    <!--                    <span><button type="button" id="fachoose-image" class="btn btn-primary fachoose"-->
    <!--                                  data-input-id="c-image" data-mimetype="image/*" data-multiple="false"><i-->
    <!--                            class="fa fa-list"></i> <?php echo __('Choose'); ?></button></span>-->
    <!--                </div>-->
    <!--                <span class="msg-box n-right" for="c-image"></span>-->
    <!--            </div>-->
    <!--            <ul class="row list-inline faupload-preview" id="p-image"></ul>-->
    <!--        </div>-->
    <!--    </div>-->
    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Weigh'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-weigh" data-rule="required" min="0" class="form-control" name="row[weigh]" type="number"
                   value="0">
        </div>
    </div>
    <!--    <div class="form-group">-->
    <!--        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Description'); ?>:</label>-->
    <!--        <div class="col-xs-12 col-sm-8">-->
    <!--            <input id="c-description" data-rule="required" class="form-control" name="row[description]" type="text"-->
    <!--                   value="">-->
    <!--        </div>-->
    <!--    </div>-->
    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Status'); ?>:</label>
        <div class="col-xs-12 col-sm-8">

            <div class="radio">
                <?php if(is_array($statusList) || $statusList instanceof \think\Collection || $statusList instanceof \think\Paginator): if( count($statusList)==0 ) : echo "" ;else: foreach($statusList as $key=>$vo): ?>
                <label for="row[status]-<?php echo $key; ?>">
                    <input id="row[status]-<?php echo $key; ?>" name="row[status]" type="radio"
                           value="<?php echo $key; ?>" <?php if(in_array(($key), explode(',',"1"))): ?>checked<?php endif; ?> />
                    <?php echo $vo; ?>
                </label>
                <?php endforeach; endif; else: echo "" ;endif; ?>
            </div>

        </div>
    </div>
    <div class="form-group" id="goldcoin-box">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Goldcoin'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-goldcoin" min="0" class="form-control" name="row[goldcoin]" type="number">
        </div>
    </div>
    <div class="form-group" id="accuracy-box">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Accuracy'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-accuracy" min="0" class="form-control" step="0.01" name="row[accuracy]" type="number">
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
