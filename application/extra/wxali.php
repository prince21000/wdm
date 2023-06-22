<?php

/**
 *   $payconfig = config('wxali.wx')['app'];
 */
return [
    'wx' => [//微信相关
        'app' => [//开放平台信息
            'appid' => '',
            'appsecret' => '',
        ],
        'xcx' => [//小程序信息
            // 'appid' => 'wx22517c13b92c0044',
            // 'appsecret' => '78814b8b8ed0e22907059872a78b259a',
            'appid' => 'wx5f00883a148e2eec	',
            'appsecret' => '3ef8380089fb39c67b15b35b4a687902', 
        ],
        'merchant' => [//微信商户信息
            'collect' => [//收款商户
                'mch_id' => '',//商户号
                'key' => '',//api秘钥
                'cert' => [
                    'sslcert_path' => ROOT_PATH . "public/cert/wx/collect/apiclient_cert.pem", //证书路径
                    'sslkey_path' => ROOT_PATH . "public/cert/wx/collect/apiclient_key.pem", //证书路径
                ],
            ],
            'out' => [//分润商户
                'mch_id' => '',//商户号
                'key' => '',//api秘钥
                'cert' => [
                    'sslcert_path' => ROOT_PATH . "public/cert/wx/out/apiclient_cert.pem", //证书路径
                    'sslkey_path' => ROOT_PATH . "public/cert/wx/out/apiclient_key.pem", //证书路径
                ],
            ],
        ],
    ],
    'ali' => [//支付宝相关
        'appid' => '',
        'public_key' => '',
    ],
    'aliyun' => [//阿里云相关
        'ram' => [//访问用户
            'AccessKeyID' => '',
            'AccessKeySecret' => '',
        ],
        'sms' => [//短信配置
            'SignName' => '',//短信签名
            'TemplateCode' => '',//模板id
        ],
    ],
];