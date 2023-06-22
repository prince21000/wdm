<?php

//配置文件
return [
    'exception_handle' => '\\app\\api\\library\\ExceptionHandle',
    // 默认全局过滤方法 用逗号分隔多个
    'default_filter' => 'htmlspecialchars,addslashes,strip_tags',
];
