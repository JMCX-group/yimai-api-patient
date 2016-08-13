<?php
/**
 * Created by PhpStorm.
 * User: lyx
 * Date: 16/4/18
 * Time: 下午3:09
 */

namespace App\Api\Controllers;

/**
 * Class HospitalsController
 * @package App\Api\Controllers
 */
class ApiController extends BaseController
{
    /**
     * @return \Dingo\Api\Http\Response
     */
    public function index()
    {
        $http = env('MY_API_HTTP_HEAD', 'http://localhost');

        $api = [
            '统一说明' => [
                '数据格式' => 'JSON',
                'url字段' => 'HTTP请求地址; {}表示在链接后直接跟该数据的ID值即可,例:http://api/hospital/77?token=xx,能获取id为77的医院信息',
                'method字段' => 'GET / POST',
                'form-data字段' => '表单数据',
                'response字段' => [
                    'error字段' => 'Token验证信息错误,表示需要重新登录获取Token; 在HTTP状态码非200时,才会有该字段',
                    'message字段' => '业务信息错误; 在HTTP状态码非200时,才会有该字段',
                    'debug字段' => '只有内测时有,用于传递一些非公开数据或调试信息',
                ],
                '特别说明' => '由于框架原因,Token相关和业务相关的错误返回字段无法保持自定义统一',
                'HTTP状态码速记' => [
                    '释义' => 'HTTP状态码有五个不同的类别:',
                    '1xx' => '临时/信息响应',
                    '2xx' => '成功; 200表示成功获取正确的数据; 204表示执行/通讯成功,但是无返回数据',
                    '3xx' => '重定向',
                    '4xx' => '客户端/请求错误; 需检查url拼接和参数; 在我们这会出现可以提示的[message]或需要重新登录获取token的[error]',
                    '5xx' => '服务器错误; 可以提示服务器崩溃/很忙啦~',
                ]
            ],

            '无需Token验证' => [
                'API文档' => [
                    'url' => $http . '/api',
                    'method' => 'GET'
                ],

                '用户' => [
                    '注册' => [
                        'url' => $http . '/api/user/register',
                        'method' => 'POST',
                        'form-data' => [
                            'phone' => '11位长的纯数字手机号码',
                            'password' => '6-60位密码',
                            'verify_code' => '4位数字验证码'
                        ],
                        'response' => [
                            'token' => '成功后会返回登录之后的token值',
                            'message' => ''
                        ]
                    ],
                    '发送验证码' => [
                        'url' => $http . '/api/user/verify-code',
                        'method' => 'POST',
                        'form-data' => [
                            'phone' => '11位长的纯数字手机号码'
                        ],
                        'response' => [
                            'debug' => '为了测试方便,成功后会返回随机的4位手机验证码,正式版上线时没有该项',
                            'message' => ''
                        ]
                    ],
                    '登录' => [
                        'url' => $http . '/api/user/login',
                        'method' => 'POST',
                        'form-data' => [
                            'phone' => '11位长的纯数字手机号码',
                            'password' => '6-60位密码'
                        ],
                        'response' => [
                            'token' => '成功后会返回登录之后的token值',
                            'message' => ''
                        ]
                    ],
                    '重置密码' => [
                        'url' => $http . '/api/user/reset-pwd',
                        'method' => 'POST',
                        'form-data' => [
                            'phone' => '11位长的纯数字手机号码',
                            'password' => '6-60位密码',
                            'verify_code' => '4位数字验证码'
                        ],
                        'response' => [
                            'token' => '成功后会返回登录之后的token值',
                            'message' => ''
                        ]
                    ]
                ],
                '静态资源' => [
                    '说明' => '和图片一样是相对链接，前面拼域名或IP即可访问； 例如：http://101.201.40.220/about/contact-us ，可以访问关于我们',
                    '关于我们' => '/about/contact-us',
                    '医脉简介' => '/about/introduction',
                    '律师信息' => '/about/lawyer'
                ]
            ],

            '需要Token验证' => [

                '初始化信息' => [
                    '启动软件初始化' => [
                        'url' => $http . '/api/init',
                        'method' => 'GET',
                        'params' => [
                            'token' => ''
                        ],
                        'response' => [
                            'user' => [
                                'id' => '用户id',
                                'phone' => '用户注册手机号',
                                'name' => '用户姓名',
                                'head_url' => '头像URL',
                                'sex' => '性别',
                                'province' => [
                                    'id' => '用户所在省份ID',
                                    'name' => '用户所在省份名称'
                                ],
                                'city' => [
                                    'id' => '用户所在城市ID',
                                    'name' => '用户所在城市名称'
                                ],
                                'tags' => '标签； 格式（JSON）：[{"tag_list":"1,2,3,4","illness_list":"3,4,5,6"}]'
                            ],
                            'sys_info' => [
                                'radio_unread_count' => '未读的广播数量',
                                'admissions_unread_count' => '未读的接诊信息数量',
                                'appointment_unread_count' => '未读的约诊信息数量'
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ]
                ],

                '用户信息' => [
                    '查询登陆用户自己的信息' => [
                        'url' => $http . '/api/user/me',
                        'method' => 'GET',
                        'params' => [
                            'token' => ''
                        ],
                        'response' => [
                            'user' => [
                                'id' => '用户id',
                                'phone' => '用户注册手机号',
                                'name' => '用户姓名',
                                'head_url' => '头像URL',
                                'sex' => '性别',
                                'province' => [
                                    'id' => '用户所在省份ID',
                                    'name' => '用户所在省份名称'
                                ],
                                'city' => [
                                    'id' => '用户所在城市ID',
                                    'name' => '用户所在城市名称'
                                ],
                                'tags' => '标签； 格式（JSON）：[{"tag_list":"1,2,3,4","illness_list":"3,4,5,6"}]'
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ],
                    '通过用户ID查询其他医生的信息' => [
                        'url' => $http . '/api/user/{doctor_id}',
                        'method' => 'GET',
                        'params' => [
                            'token' => ''
                        ],
                        '说明' => '请前台判断是否在查询自己,自己的信息在登陆时已经有全部的了,而且看自己的没有中间的两个按钮',
                        'response' => [
                            'user' => [
                                'is_friend' => '决定按钮的布局; true | false',
                                'id' => '用户id',
                                'name' => '用户姓名',
                                'head_url' => '头像URL',
                                'job_title' => '用户职称',
                                'province' => '用户所在省份名称',
                                'city' => '用户所在城市名称',
                                'hospital' => '用户所在医院名称',
                                'department' => '用户所在科室名称',
                                'college' => '用户所在院校名称',
                                'tags' => '标签； 格式（JSON）：[{"tag_list":"1,2,3,4","illness_list":"3,4,5,6"}]',
                                'personal_introduction' => '个人简介',
                                'is_auth' => '是否认证,1为认证,0为未认证',
                                'common_friend_list' => [
                                    'id' => '用户id',
                                    'head_url' => '头像URL',
                                    'is_auth' => '是否认证,1为认证,0为未认证'
                                ]
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ],
                    '修改个人信息/修改密码/修改接诊收费信息/修改隐私设置' => [
                        'url' => $http . '/api/user',
                        'method' => 'POST',
                        'params' => [
                            'token' => ''
                        ],
                        '说明' => '以下form-data项均为可选项,修改任意一个或几个都可以,有什么数据加什么字段',
                        'form-data' => [
                            'password' => '用户密码',
                            'name' => '用户姓名',
                            'head_img' => '用户头像; 直接POST文件,支持后缀:jpg/jpeg/png',
                            'sex' => '性别',
                            'province' => '用户所在省份ID',
                            'city' => '用户所属城市ID',
                            'tags' => '标签； 格式（JSON）：[{"tag_list":"1,2,3,4","illness_list":"3,4,5,6"}]'
                        ],
                        'response' => [
                            'user' => [
                                'id' => '用户id',
                                'phone' => '用户注册手机号',
                                'name' => '用户姓名',
                                'head_url' => '头像URL',
                                'sex' => '性别',
                                'province' => [
                                    'id' => '用户所在省份ID',
                                    'name' => '用户所在省份名称'
                                ],
                                'city' => [
                                    'id' => '用户所在城市ID',
                                    'name' => '用户所在城市名称'
                                ],
                                'tags' => '标签； 格式（JSON）：[{"tag_list":"1,2,3,4","illness_list":"3,4,5,6"}]'
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ]
                ],

                '省市信息' => [
                    '省市列表' => [
                        'url' => $http . '/api/city',
                        'method' => 'GET',
                        'params' => [
                            'token' => ''
                        ],
                        'response' => [
                            'provinces' => [
                                'id' => '省份ID, province_id',
                                'name' => '省份/直辖市名称'
                            ],
                            'citys' => [
                                'id' => '城市ID',
                                'province_id' => '省份ID',
                                'name' => '城市名称'
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ],
                    '省市列表-按省分组' => [
                        'url' => $http . '/api/city/group',
                        'method' => 'GET',
                        'params' => [
                            'token' => ''
                        ],
                        'response' => [
                            'provinces' => [
                                'id' => '省份ID, province_id',
                                'name' => '省份/直辖市名称'
                            ],
                            'citys' => [
                                '{province_id}' => [
                                    'id' => '城市ID',
                                    'name' => '城市名称'
                                ]
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ]
                ],

                '标签信息' => [
                    '所有标签（二级科室）' => [
                        'url' => $http . '/api/tag/all',
                        'method' => 'GET',
                        'params' => [
                            'token' => ''
                        ],
                        'response' => [
                            'data' => [
                                'id' => '标签ID',
                                'name' => '标签名称'
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ],
                    '相应标签对应的疾病' => [
                        'url' => $http . '/api/tag/illness',
                        'method' => 'POST',
                        'params' => [
                            'token' => ''
                        ],
                        'form-data' => [
                            'id' => '标签ID'
                        ],
                        'response' => [
                            'data' => [
                                'id' => '疾病ID',
                                'name' => '疾病名称'
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ],
                ],

                '广播信息' => [
                    '所有广播' => [
                        'url' => $http . '/api/radio',
                        'method' => 'GET',
                        'params' => [
                            'token' => '',
                            'page' => '页码,一页4个; 没有填页码默认是第一页'
                        ],
                        'response' => [
                            'data' => [
                                'id' => '广播ID',
                                'name' => '广播标题',
                                'url' => '广播链接; 相对地址; 例如:/article/1, 前台请拼成: http://101.201.40.220/article/1 , 即可直接访问,不需要其他get参数',
                                'img_url' => '首页图片URL; 相对地址; 例如:/uploads/article/1.png, 前台请拼成: http://101.201.40.220/uploads/article/1.png ,即可直接访问',
                                'author' => '发表人',
                                'time' => '发表时间',
                                'unread' => '是否未读,1为未读,null为已读'
                            ],
                            'meta' => [
                                'pagination' => [
                                    'total' => '广播总共的数量',
                                    'count' => '该次请求获取的数量',
                                    'per_page' => '每页将请求数据量',
                                    'current_page' => '当前页码(page)',
                                    'total_pages' => '总共页码(page)',
                                    'links' => [
                                        'next' => '会自动生成下一页链接,类似于:http://localhost/api/radio?page=2'
                                    ]
                                ]
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ],
                    '广播已读' => [
                        'url' => $http . '/api/radio/read',
                        'method' => 'POST',
                        'params' => [
                            'token' => ''
                        ],
                        'form-data' => [
                            'id' => '广播ID'
                        ],
                        'response' => [
                            'message' => '',
                            'error' => ''
                        ]
                    ]
                ],
            ]
        ];

        return $api;
    }

    public function item()
    {
        $http = env('MY_API_HTTP_HEAD', 'http://localhost');

        $api = [
            'API文档目录' => [
                'API文档' => [
                    'url' => $http . '/api',
                    'method' => 'GET'
                ],
                '用户' => [
                    '地址' => [],
                    '包含' => '注册/发送验证码/获取邀请人/登录/重置密码'
                ]
            ],
            '需要Token验证' => [
                '用户' => [
                    '地址' => [],
                    '包含' => '注册/发送验证码/获取邀请人/登录/重置密码'
                ],
                '用户信息' => [
                    '查询个人信息' => [],
                    '修改个人信息' => []
                ],
                '省市信息' => [
                    '省市列表' => [],
                    '省市列表-按省分组' => []
                ],
                '医院信息' => [
                    '单个医院' => [],
                    '属于某个城市下的医院' => [],
                    '模糊查询某个医院名称' => []
                ],
                '科室信息' => [
                    '所有科室' => []
                ],
                '医脉资源' => [
                    '一度医脉(四部分数据,多用于首次/当天首次打开)' => [],
                    '一度医脉(两部分数据,多用于打开后第一次之后的刷新数据用)' => [],
                    '二度医脉(两部分数据)' => [],
                    '新朋友' => []
                ],
                '广播信息' => [
                    '所有广播' => [],
                    '广播已读' => []
                ]

            ]
        ];

        return $api;
    }
}
