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
            '患者端API' => '',

            '统一说明' => [
                '医生端域名' => 'http://d.medi-link.cn/',
                '患者端域名' => 'http://p.medi-link.cn/',
                'CMS域名' => 'http://cms.medi-link.cn/',
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
                ],

                '友盟推送说明' => [
                    '广播' => [
                        '场景' => 'CMS发送广播，将会分医生或患者端推送',
                        '传参' => [
                            'action' => 'radio',
                            'data-id' => '广播ID',
                        ],
                        '跳转' => '指定的广播页面',
                    ],
                    '约诊' => [
                        '场景' => '代约医生新建约诊，将会给相应患者推送单播，提示缴费',
                        '传参' => [
                            'action' => 'appointment',
                            'data-id' => '约诊ID',
                        ],
                        '跳转' => '指定的约诊页面',
                    ],
                ]
            ],

            '无需Token验证' => [
                'API文档' => [
                    'url' => $http . '/api',
                    'method' => 'GET'
                ],

                'Banner' => [
                    '全部链接' => [
                        'url' => $http . '/api/get-banner-url',
                        'method' => 'GET',
                        'response' => [
                            'data' => [
                                [
                                    'focus_img_url' => '轮播图URL；绝对地址',
                                    'content_url' => '跳转文章URL；绝对地址'
                                ]
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ]
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

                '订单' => [
                    '查询订单' => [
                        '说明' => '因为微信不稳定，需要手动查询接口',
                        'url' => $http . '/api/pay/order_query',
                        'method' => 'POST',
                        'form-data' => [
                            'id' => '约诊ID'
                        ],
                        'response' => [
                            'data' => [
                                'result' => 'success或fail；代表支付成功或失败，成功的话，此时会刷新订单状态'
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ]
                ],

                '静态资源' => [
                    '关于我们' => $http . '/about/contact-us',
                    '医脉简介' => $http . '/about/introduction',
                    '律师信息' => $http . '/about/lawyer',
                    '用户协议' => $http . '/agreement/patient',
                    '分享文案' => $http . '/share/index',

                    '约诊详细信息分享页面-IOS' => $http . '/YMPatientShareiOS/index.php',
                    '约诊详细信息分享页面-Android' => $http . '/YMPatientShareAndroid/index.php'
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
                                'device_token' => '友盟设备token； IOS：64位长，安卓：44位长',
                                'name' => '用户姓名',
                                'nickname' => '用户昵称',
                                'head_url' => '头像URL',
                                'sex' => '性别',
                                'birthday' => '生日',
                                'province' => [
                                    'id' => '用户所在省份ID',
                                    'name' => '用户所在省份名称'
                                ],
                                'city' => [
                                    'id' => '用户所在城市ID',
                                    'name' => '用户所在城市名称'
                                ],
                                'tags' => '标签； 格式（JSON Encode ）：{"tag_list":"1,2,3,4","illness_list":"3,4,5,6"}'
                            ],
                            'my_doctors' => [
                                'id' => '用户ID',
                                'name' => '用户姓名',
                                'head_url' => '头像URL',
                                'job_title' => '职称',
                                'province' => [
                                    'id' => '所属省份ID',
                                    'name' => '所属省份名称'
                                ],
                                'city' => [
                                    'id' => '所属城市ID',
                                    'name' => '所属城市名称'
                                ],
                                'hospital' => [
                                    'id' => '用户所在医院ID',
                                    'name' => '用户所在医院名称'
                                ],
                                'department' => [
                                    'id' => '用户所在科室ID',
                                    'name' => '用户所在科室名称'
                                ],
                                'college' => [
                                    'id' => '用户所在院校ID',
                                    'name' => '用户所在院校名称'
                                ],
                                'tags' => '医生特长列表',
                                'personal_introduction' => '个人简介',
                                'is_auth' => '是否认证,1为认证,0为未认证',
                                'fee_switch' => '1:开, 0:关',
                                'fee' => '接诊收费金额',
                                'fee_face_to_face' => '当面咨询收费金额',
                                'admission_set_fixed' => [
                                    '说明' => '接诊时间设置,固定排班; 接收json,直接存库; 需要存7组数据,week分别是:sun,mon,tue,wed,thu,fri,sat',
                                    '格式案例' => [
                                        'week' => 'sun',
                                        'am' => 'true',
                                        'pm' => 'false',
                                    ]
                                ],
                                'admission_set_flexible' => [
                                    '说明' => '接诊时间设置,灵活排班; 接收json,读取时会自动过滤过期时间; 会有多组数据,格式一致',
                                    '格式案例' => [
                                        'date' => '2016-06-23',
                                        'am' => 'true',
                                        'pm' => 'false',
                                    ]
                                ],
                                'is_my_doctor' => 'true：是； false：否； 数据类型：字符串'
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

                '数据信息' => [
                    '查询当前登录医生排班信息' => [
                        '说明' => 'data里总共会有14组数据，依次从当天开始，am和pm后面的是跟的字符串true或false',
                        'url' => $http . '/api/data/scheduling',
                        'method' => 'POST',
                        'params' => [
                            'token' => ''
                        ],
                        'form-data' => [
                            'id' => '医生ID'
                        ],
                        'response' => [
                            'data' => [
                                [
                                    'date' => '日期，数据示例：2016-06-23',
                                    'am' => '上午，数据示例：true',
                                    'pm' => '下午，数据示例：false',
                                ]
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ],
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
                                'device_token' => '友盟设备token； IOS：64位长，安卓：44位长',
                                'name' => '用户姓名',
                                'nickname' => '用户昵称',
                                'head_url' => '头像URL',
                                'sex' => '性别',
                                'birthday' => '生日',
                                'province' => [
                                    'id' => '用户所在省份ID',
                                    'name' => '用户所在省份名称'
                                ],
                                'city' => [
                                    'id' => '用户所在城市ID',
                                    'name' => '用户所在城市名称'
                                ],
                                'tags' => '标签； 格式（JSON Encode）：{"tag_list":"1,2,3,4","illness_list":"3,4,5,6"}'
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ],

                    '通过用户ID查询其他医生的信息' => [
                        '说明' => '请前台判断是否在查询自己,自己的信息在登陆时已经有全部的了,而且看自己的没有中间的两个按钮',
                        'url' => $http . '/api/user/{doctor_id}',
                        'method' => 'GET',
                        'params' => [
                            'token' => ''
                        ],
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
                                'tags' => '标签； 格式（JSON Encode）：{"tag_list":"1,2,3,4","illness_list":"3,4,5,6"}',
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

                    '通过医生手机号或医脉码查询医生的信息' => [
                        'url' => $http . '/api/user/phone-code/{doctor_id_or_code}',
                        'method' => 'GET',
                        'params' => [
                            'token' => ''
                        ],
                        'response' => [
                            'data' => [
                                'id' => '用户ID',
                                'name' => '用户姓名',
                                'head_url' => '头像URL',
                                'job_title' => '职称',
                                'province' => [
                                    'id' => '所属省份ID',
                                    'name' => '所属省份名称'
                                ],
                                'city' => [
                                    'id' => '所属城市ID',
                                    'name' => '所属城市名称'
                                ],
                                'hospital' => [
                                    'id' => '用户所在医院ID',
                                    'name' => '用户所在医院名称'
                                ],
                                'department' => [
                                    'id' => '用户所在科室ID',
                                    'name' => '用户所在科室名称'
                                ],
                                'college' => [
                                    'id' => '用户所在院校ID',
                                    'name' => '用户所在院校名称'
                                ],
                                'tags' => '医生特长列表',
                                'personal_introduction' => '个人简介',
                                'is_auth' => '是否认证,1为认证,0为未认证'
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ],

                    '修改个人信息/修改密码/修改接诊收费信息/修改隐私设置' => [
                        '说明' => 'form-data项均为可选项,修改任意一个或几个都可以,有什么数据加什么字段',
                        'url' => $http . '/api/user',
                        'method' => 'POST',
                        'params' => [
                            'token' => ''
                        ],
                        'form-data' => [
                            'password' => '用户密码',
                            'device_token' => '友盟设备token； IOS：64位长，安卓：44位长',
                            'name' => '用户姓名',
                            'nickname' => '用户昵称',
                            'head_img' => '用户头像; 直接POST文件,支持后缀:jpg/jpeg/png',
                            'sex' => '性别',
                            'birthday' => '生日; Date型',
                            'province' => '用户所在省份ID',
                            'city' => '用户所属城市ID',
                            'tags' => '标签； 格式（JSON Encode）：{"tag_list":"1,2,3,4","illness_list":"3,4,5,6"}',
                            'blacklist' => '黑名单； 用户ID list，用逗号分隔'
                        ],
                        'response' => [
                            'user' => [
                                'id' => '用户id',
                                'phone' => '用户注册手机号',
                                'device_token' => '友盟设备token； IOS：64位长，安卓：44位长',
                                'name' => '用户姓名',
                                'nickname' => '用户昵称',
                                'head_url' => '头像URL',
                                'sex' => '性别',
                                'birthday' => '生日; Date型',
                                'province' => [
                                    'id' => '用户所在省份ID',
                                    'name' => '用户所在省份名称'
                                ],
                                'city' => [
                                    'id' => '用户所在城市ID',
                                    'name' => '用户所在城市名称'
                                ],
                                'tags' => '标签； 格式（JSON Encode）：{"tag_list":"1,2,3,4","illness_list":"3,4,5,6"}',
                                'blacklist' => '黑名单； 用户ID list，用逗号分隔； 增加/删除都更新改字段即可'
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ],

                    '扫码添加医生' => [
                        '说明' => '扫码有医生ID，然后调用这个接口添加到我的医生（“约我的医生”）',
                        'url' => $http . '/api/user/add-doctor',
                        'method' => 'POST',
                        'params' => [
                            'token' => ''
                        ],
                        'form-data' => [
                            'id' => '医生ID'
                        ],
                        'response' => [
                            'data' => [
                                'result' => 'success或fail'
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ],

                    '删除我的医生' => [
                        'url' => $http . '/api/user/del-doctor',
                        'method' => 'POST',
                        'params' => [
                            'token' => ''
                        ],
                        'form-data' => [
                            'id' => '医生ID'
                        ],
                        'response' => [
                            'data' => [
                                'result' => 'success或fail'
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ]
                ],

                '搜索' => [
                    '进入搜索页面默认加载的信息' => [
                        '说明' => '本来该数据通过登录用户填写标签筛选',
                        '不过' => '现在显示约单数量最多的前十名医生',
                        'url' => $http . '/api/search/default',
                        'method' => 'GET',
                        'params' => [
                            'token' => ''
                        ],
                        'response' =>
                            [
                                'data' => [
                                    'id' => '用户ID',
                                    'name' => '用户姓名',
                                    'head_url' => '头像URL',
                                    'job_title' => '职称',
                                    'province' => [
                                        'id' => '所属省份ID',
                                        'name' => '所属省份名称'
                                    ],
                                    'city' => [
                                        'id' => '所属城市ID',
                                        'name' => '所属城市名称'
                                    ],
                                    'hospital' => [
                                        'id' => '用户所在医院ID',
                                        'name' => '用户所在医院名称'
                                    ],
                                    'department' => [
                                        'id' => '用户所在科室ID',
                                        'name' => '用户所在科室名称'
                                    ],
                                    'college' => [
                                        'id' => '用户所在院校ID',
                                        'name' => '用户所在院校名称'
                                    ],
                                    'tags' => '医生特长列表',
                                    'personal_introduction' => '个人简介',
                                    'is_auth' => '是否认证,1为认证,0为未认证',
                                    'fee_switch' => '1:开, 0:关',
                                    'fee' => '接诊收费金额',
                                    'fee_face_to_face' => '当面咨询收费金额',
                                    'admission_set_fixed' => [
                                        '说明' => '接诊时间设置,固定排班; 接收json,直接存库; 需要存7组数据,week分别是:sun,mon,tue,wed,thu,fri,sat',
                                        '格式案例' => [
                                            'week' => 'sun',
                                            'am' => 'true',
                                            'pm' => 'false',
                                        ]
                                    ],
                                    'admission_set_flexible' => [
                                        '说明' => '接诊时间设置,灵活排班; 接收json,读取时会自动过滤过期时间; 会有多组数据,格式一致',
                                        '格式案例' => [
                                            'date' => '2016-06-23',
                                            'am' => 'true',
                                            'pm' => 'false',
                                        ]
                                    ],
                                    'is_my_doctor' => 'true：是； false：否； 数据类型：字符串'
                                ],
                                'message' => '',
                                'error' => ''
                            ]
                    ],

                    '进入【约我的医生】加载的医生列表' => [
                        '说明' => '该数据通过登录用户约诊记录筛选;',
                        'url' => $http . '/api/search/my-doctor',
                        'method' => 'GET',
                        'params' => [
                            'token' => ''
                        ],
                        'response' =>
                            [
                                'data' => [
                                    'id' => '用户ID',
                                    'name' => '用户姓名',
                                    'head_url' => '头像URL',
                                    'job_title' => '职称',
                                    'province' => [
                                        'id' => '所属省份ID',
                                        'name' => '所属省份名称'
                                    ],
                                    'city' => [
                                        'id' => '所属城市ID',
                                        'name' => '所属城市名称'
                                    ],
                                    'hospital' => [
                                        'id' => '用户所在医院ID',
                                        'name' => '用户所在医院名称'
                                    ],
                                    'department' => [
                                        'id' => '用户所在科室ID',
                                        'name' => '用户所在科室名称'
                                    ],
                                    'college' => [
                                        'id' => '用户所在院校ID',
                                        'name' => '用户所在院校名称'
                                    ],
                                    'tags' => '医生特长列表',
                                    'personal_introduction' => '个人简介',
                                    'is_auth' => '是否认证,1为认证,0为未认证',
                                    'fee_switch' => '1:开, 0:关',
                                    'fee' => '接诊收费金额',
                                    'fee_face_to_face' => '当面咨询收费金额',
                                    'admission_set_fixed' => [
                                        '说明' => '接诊时间设置,固定排班; 接收json,直接存库; 需要存7组数据,week分别是:sun,mon,tue,wed,thu,fri,sat',
                                        '格式案例' => [
                                            'week' => 'sun',
                                            'am' => 'true',
                                            'pm' => 'false',
                                        ]
                                    ],
                                    'admission_set_flexible' => [
                                        '说明' => '接诊时间设置,灵活排班; 接收json,读取时会自动过滤过期时间; 会有多组数据,格式一致',
                                        '格式案例' => [
                                            'date' => '2016-06-23',
                                            'am' => 'true',
                                            'pm' => 'false',
                                        ]
                                    ],
                                    'is_my_doctor' => 'true：是； false：否； 数据类型：字符串'
                                ],
                                'message' => '',
                                'error' => ''
                            ]
                    ],

                    '搜索医生信息' => [
                        '说明' => '会一次传递所有排好序的数据,按3个分组,每个显示2条数据即可; 如果下拉框为后置条件,建议前端执行过滤; 城市按省份ID分组; 医院按省份ID和城市ID级联分组',
                        'url' => $http . '/api/search',
                        'method' => 'POST',
                        'params' => [
                            'token' => ''
                        ],
                        'form-data' => [
                            'field' => '搜索的关键字; 必填项,当type为指定内容时为可选项,不过此时将会是全局搜索,返回信息量巨大',
                            'city' => '下拉框选择的城市ID; 可选项; 参数名也可以是city_id',
                            'hospital' => '下拉框选择的医院ID; 可选项; 参数名也可以是hospital_id',
                            'department' => '下拉框选择的科室ID; 可选项; 参数名也可以是dept_id',
                            'job_title' => '下拉框选择的职称名称; 可选项;',
                            'format' => '或者什么样的格式; 可选项; 提交该项,且值为android时,hospitals会返回安卓格式',
                        ],
                        'response' =>
                            [
                                'provinces' => [
                                    'id' => '省份ID, province_id',
                                    'name' => '省份/直辖市名称'
                                ],
                                'citys' => [
                                    '{province_id}' => [
                                        'id' => '城市ID, city_id',
                                        'name' => '城市名称'
                                    ]
                                ],
                                'hospitals' => [
                                    '默认格式说明' => '例如: hospitals[1][1]可以取到1省1市下的医院列表',
                                    '{province_id}' => [
                                        '{city_id}' => [
                                            '{自增的数据下标,非key}' => [
                                                'id' => '医院ID',
                                                'name' => '城市名称',
                                                'province_id' => '该医院的省id',
                                                'city_id' => '该医院的市id'
                                            ]
                                        ]
                                    ],

                                    '安卓格式说明' => '提交format字段,且值为android时,hospitals会返回该格式 :',
                                    '{自增的数组序号}' => [
                                        'province_id' => '省份ID',
                                        'data' => [
                                            '{自增的数据下标,非key}' => [
                                                'city_id' => '城市ID',
                                                'data' => [
                                                    '{自增的数据下标,非key}' => [
                                                        'id' => '医院ID',
                                                        'name' => '城市名称',
                                                        'province_id' => '该医院的省id',
                                                        'city_id' => '该医院的市id'
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ],
                                ],
                                'departments' => [
                                    'id' => '科室ID',
                                    'name' => '科室名称'
                                ],
                                'count' => '满足条件的医生数量',
                                'users' => [
                                    'name' => [
                                        '说明' => '根据关键字,按姓名分组',
                                        'id' => '用户ID',
                                        'name' => '用户姓名',
                                        'head_url' => '头像URL',
                                        'job_title' => '职称',
                                        'province' => [
                                            'id' => '所属省份ID',
                                            'name' => '所属省份名称'
                                        ],
                                        'city' => [
                                            'id' => '所属城市ID',
                                            'name' => '所属城市名称'
                                        ],
                                        'hospital' => [
                                            'id' => '用户所在医院ID',
                                            'name' => '用户所在医院名称'
                                        ],
                                        'department' => [
                                            'id' => '用户所在科室ID',
                                            'name' => '用户所在科室名称'
                                        ],
                                        'college' => [
                                            'id' => '用户所在院校ID',
                                            'name' => '用户所在院校名称'
                                        ],
                                        'tags' => '医生特长列表',
                                        'personal_introduction' => '个人简介',
                                        'is_auth' => '是否认证,1为认证,0为未认证',
                                        'fee_switch' => '1:开, 0:关',
                                        'fee' => '接诊收费金额',
                                        'fee_face_to_face' => '当面咨询收费金额',
                                        'admission_set_fixed' => [
                                            '说明' => '接诊时间设置,固定排班; 接收json,直接存库; 需要存7组数据,week分别是:sun,mon,tue,wed,thu,fri,sat',
                                            '格式案例' => [
                                                'week' => 'sun',
                                                'am' => 'true',
                                                'pm' => 'false',
                                            ]
                                        ],
                                        'admission_set_flexible' => [
                                            '说明' => '接诊时间设置,灵活排班; 接收json,读取时会自动过滤过期时间; 会有多组数据,格式一致',
                                            '格式案例' => [
                                                'date' => '2016-06-23',
                                                'am' => 'true',
                                                'pm' => 'false',
                                            ]
                                        ],
                                        'is_my_doctor' => 'true：是； false：否； 数据类型：字符串'
                                    ],
                                    'hospital' => [
                                        '说明' => '根据关键字,按医院分组; 数据格式同上',
                                    ],
                                    'dept' => [
                                        '说明' => '根据关键字,按科室分组; 数据格式同上',
                                    ],
                                    'tag' => [
                                        '说明' => '根据关键字,按标签分组; 数据格式同上',
                                    ],
                                    'other' => [
                                        '说明' => '根据关键字,其他无法进入上面4组数据的分组; 数据格式同上',
                                    ],
                                ],
                                'message' => '',
                                'error' => ''
                            ]
                    ],

                    '通过医生ID查询其信息' => [
                        'url' => $http . '/api/search/doctor/{doctor_id}',
                        'method' => 'GET',
                        'params' => [
                            'token' => ''
                        ],
                        'response' => [
                            'data' => [
                                'id' => '用户ID',
                                'name' => '用户姓名',
                                'head_url' => '头像URL',
                                'job_title' => '职称',
                                'province' => [
                                    'id' => '所属省份ID',
                                    'name' => '所属省份名称'
                                ],
                                'city' => [
                                    'id' => '所属城市ID',
                                    'name' => '所属城市名称'
                                ],
                                'hospital' => [
                                    'id' => '用户所在医院ID',
                                    'name' => '用户所在医院名称'
                                ],
                                'department' => [
                                    'id' => '用户所在科室ID',
                                    'name' => '用户所在科室名称'
                                ],
                                'college' => [
                                    'id' => '用户所在院校ID',
                                    'name' => '用户所在院校名称'
                                ],
                                'tags' => '医生特长列表',
                                'personal_introduction' => '个人简介',
                                'is_auth' => '是否认证,1为认证,0为未认证',
                                'fee_switch' => '1:开, 0:关',
                                'fee' => '接诊收费金额',
                                'fee_face_to_face' => '当面咨询收费金额',
                                'admission_set_fixed' => [
                                    '说明' => '接诊时间设置,固定排班; 接收json,直接存库; 需要存7组数据,week分别是:sun,mon,tue,wed,thu,fri,sat',
                                    '格式案例' => [
                                        'week' => 'sun',
                                        'am' => 'true',
                                        'pm' => 'false',
                                    ]
                                ],
                                'admission_set_flexible' => [
                                    '说明' => '接诊时间设置,灵活排班; 接收json,读取时会自动过滤过期时间; 会有多组数据,格式一致',
                                    '格式案例' => [
                                        'date' => '2016-06-23',
                                        'am' => 'true',
                                        'pm' => 'false',
                                    ]
                                ],
                                'is_my_doctor' => 'true：是； false：否； 数据类型：字符串'
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ],
                ],

                '省市信息' => [
                    '省市列表' => [
                        'url' => $http . '/api/city',
                        'method' => 'GET',
                        'params' => [
                            'token' => ''
                        ],
                        'response' => [
                            'hot_citys' => [
                                'id' => '城市ID',
                                'name' => '城市名称'
                            ],
                            'provinces' => [
                                'id' => '省份ID, province_id',
                                'name' => '省份/直辖市名称'
                            ],
                            'citys' => [
                                'id' => '城市ID',
                                'province_id' => '省份ID',
                                'name' => '城市名称',
                                'hot' => '1为热门'
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

                    '获取标签和疾病,并分组' => [
                        'url' => $http . '/api/tag/group',
                        'method' => 'GET',
                        'params' => [
                            'token' => ''
                        ],
                        'response' => [
                            'data' => [
                                'id' => '标签ID',
                                'name' => '标签名称',
                                'illness' => [
                                    'id' => '疾病ID',
                                    'name' => '疾病名称',
                                ]
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ]
                ],

                '约诊' => [
                    '新建约诊' => [
                        '说明' => [
                            '【找专家】入口' => '【找专家】->如果搜索到自己的医生,选择然后【预约面诊】',
                            '【约我的医生】入口' => '【约我的医生】->选择然后【预约面诊】'
                        ],
                        'url' => $http . '/api/appointment/new',
                        'method' => 'POST',
                        'params' => [
                            'token' => ''
                        ],
                        'form-data' => [
                            'name' => '患者姓名; 必填',
                            'phone' => '患者手机号; 必填',
                            'sex' => '患者性别,1男0女',
                            'age' => '患者年龄',
                            'history' => '患者现病史',
                            'doctor' => '预约的医生的ID; 必填',
                            'date' => '预约日期,最多选择3个,用逗号分隔开即可,例:2016-05-01,2016-05-02; 如果是医生决定就是传0即可。',
                            'am_or_pm' => '预约上/下午,和上面的对应的用逗号分隔开即可,例:am,pm; 如果是医生决定随便传什么,都不会处理,取值时为空',
                        ],
                        'response' => [
                            'id' => '预约码',
                            'message' => '',
                            'error' => ''
                        ]
                    ],

                    '新建请求代约' => [
                        '说明' => [
                            '【找专家】入口1' => '【找专家】->如果搜索非自己的医生,选择然后【请平台代约】',
                            '【找专家】入口2' => '【找专家】->如果搜索到自己的医生,选择然后【请他代约】',
                            '【约我的医生】入口' => '【约我的医生】->选择我的医生【请他代约】'
                        ],
                        'url' => $http . '/api/appointment/instead',
                        'method' => 'POST',
                        'params' => [
                            'token' => ''
                        ],
                        'form-data' => [
                            'name' => '患者姓名; 必填',
                            'phone' => '患者手机号; 必填',
                            'sex' => '患者性别,1男0女',
                            'age' => '患者年龄',
                            'history' => '患者现病史',
                            'demand_doctor_name' => '患者代约请求,需求的医生姓名; 选填',
                            'demand_hospital' => '患者代约请求,需求的医院; 选填',
                            'demand_dept' => '患者代约请求,需求的科室; 选填',
                            'demand_title' => '患者代约请求,需求的医生职称; 选填',
                            'locums_doctor' => '代约的医生的ID，1为平台代约，0为没有代约医生； 必填',
                            'doctor' => '指定代约的医生的ID; 选填',
                            'date' => '预约日期,最多选择3个,用逗号分隔开即可,例:2016-05-01,2016-05-02; 如果是医生决定就是传0即可。',
                            'am_or_pm' => '预约上/下午,和上面的对应的用逗号分隔开即可,例:am,pm; 如果是医生决定随便传什么,都不会处理,取值时为空',
                        ],
                        'response' => [
                            'id' => '预约码',
                            'message' => '',
                            'error' => ''
                        ]
                    ],

                    '上传图片' => [
                        'url' => $http . '/api/appointment/upload-img',
                        'method' => 'POST',
                        'params' => [
                            'token' => ''
                        ],
                        'form-data' => [
                            'id' => '约诊预约码',
                            'img' => '病历照片,一张张传; 直接POST文件,支持后缀:jpg/jpeg/png'
                        ],
                        'response' => [
                            'url' => '压缩后的图片访问url链接,可直接用于阅览',
                            'message' => '',
                            'error' => ''
                        ]
                    ],

                    '获取约诊记录(待确认/待面诊/已结束)' => [
                        'url' => $http . '/api/appointment/list',
                        'method' => 'GET',
                        'params' => [
                            'token' => ''
                        ],
                        '状态code说明' => 'wait-0: 待医生确认
                                             wait-1: 待患者付款
                                             wait-2: 患者已付款，待医生确认
                                             wait-3: 医生确认接诊，待面诊
                                             wait-4: 医生改期，待患者确认
                                             wait-5: 患者确认改期，待面诊
                                             close:
                                             close-1: 待患者付款
                                             close-2: 医生过期未接诊,约诊关闭
                                             close-3: 医生拒绝接诊
                                             close-4: 患者过期未确认,约诊关闭
                                             cancel:
                                             cancel-1: 患者取消约诊; 未付款
                                             cancel-2: 医生取消约诊
                                             cancel-3: 患者取消约诊; 已付款后
                                             cancel-4: 医生改期之后,医生取消约诊;
                                             cancel-5: 医生改期之后,患者取消约诊;
                                             cancel-6: 医生改期之后,患者确认之后,患者取消约诊;
                                             cancel-7: 医生改期之后,患者确认之后,医生取消约诊;
                                             completed:
                                             completed-1:最简正常流程
                                             completed-2:改期后完成',
                        'response' => [
                            'data' => [
                                'wait_confirm' => [
                                    [
                                        'id' => '约诊ID',
                                        'doctor_id' => '医生ID',
                                        'doctor_name' => '医生姓名',
                                        'doctor_head_url' => '医生头像',
                                        'doctor_job_title' => '医生头衔',
                                        'doctor_is_auth' => '医生是否认证',
                                        'patient_id' => '患者ID',
                                        'patient_name' => '患者姓名',
                                        'patient_sex' => '患者性别',
                                        'patient_age' => '患者姓名',
                                        'request_mode' => '请求约诊的模式:我的医生、找专家、医生代约; 如果为null,则是由医生发起的,不用显示那个标签',
                                        'deposit' => '订金/押金',
                                        'price' => '约诊费用',
                                        'time' => '时间',
                                        'status' => '状态',
                                        'status_code' => '状态code',
                                        'is_pay' => '是否支付；0为还未，1为已经支付；有可能wait-1也有已经支付的'
                                    ]
                                ],
                                'wait_meet' => '结构同上',
                                'completed' => '结构同上'
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ],

                    '获取患者的约诊详细信息' => [
                        'url' => $http . '/api/appointment/detail',
                        'method' => 'POST',
                        'params' => [
                            'token' => ''
                        ],
                        'form-data' => [
                            'id' => '约诊ID'
                        ],
                        'response' => [
                            'doctor_info' => [
                                'id' => '接诊医生ID',
                                'name' => '接诊医生姓名',
                                'head_url' => '接诊医生头像URL',
                                'job_title' => '接诊医生职称',
                                'hospital' => '接诊医生所属医院',
                                'department' => '接诊医生所属科室'
                            ],
                            'locums_doctor_info' => [
                                'id' => '代约医生ID； 平台代约为1； 患者自己约的为空字符串',
                                'name' => '代约医生姓名； 平台代约为【医脉平台】； 患者自己约的为空字符串',
                                'head_url' => '代约医生头像URL； 平台代约为默认，',
                                'job_title' => '代约医生职称； 平台代约和患者自己约都为空字符串',
                                'hospital' => '代约医生所属医院； 平台代约和患者自己约都为空字符串',
                                'department' => '代约医生所属科室； 平台代约和患者自己约都为空字符串'
                            ],
                            'patient_info' => [
                                'name' => '患者姓名',
                                'head_url' => '患者头像URL',
                                'sex' => '患者性别',
                                'age' => '患者年龄',
                                'phone' => '患者电话',
                                'history' => '病情描述',
                                'img_url' => '病历图片url序列,url中把{_thumb}替换掉就是未压缩图片,例如:/uploads/case-history/2016/05/011605130001/1463539005_thumb.jpg,原图就是:/uploads/case-history/2016/05/011605130001/1463539005.jpg',
                            ],
                            'other_info' => [
                                'progress' => '顶部进度',
                                'time_line' => [
                                    '说明' => 'time_line数组及其内部other数组下可能有1条或多条信息,需要遍历,0和1的序号不用在意,foreach就好',
                                    '内容' => [[
                                        'time' => '时间轴左侧的时间',
                                        'info' => [
                                            'text' => '文案描述',
                                            'other' => [
                                                '内容' => [[
                                                    'name' => '其他的信息名称,例如:期望就诊时间',
                                                    'content' => '其他的信息内容,例如:2016-05-18 上午; 多条时间信息用逗号隔开,展示时则是换行展示,例如:2016-05-12 上午,2016-05-13 下午'
                                                ], []]
                                            ]
                                        ],
                                        'type' => '决定使用什么icon; begin | wait'
                                    ],
                                        [
                                            'time' => '时间轴左侧的时间, null为没有',
                                            'info' => [
                                                'text' => '文案描述',
                                                'other' => 'null为没有'
                                            ],
                                            'type' => '决定使用什么icon; begin | wait'
                                        ]]
                                ],
                                'status_code' => '状态CODE',
                                'is_pay' => '是否支付；0为还未，1为已经支付；有可能wait-1也有已经支付的'
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ],

//                    '支付/缴纳保证金/缴纳接诊费' => [
//                        'url' => $http . '/api/appointment/pay',
//                        'method' => 'POST',
//                        'params' => [
//                            'token' => ''
//                        ],
//                        'form-data' => [
//                            'id' => '约诊ID'
//                        ],
//                        'APP支付接口调用文档' => 'https://pay.weixin.qq.com/wiki/doc/api/app/app.php?chapter=9_12&index=2',
//                        'response' => [
//                            'data' => [
//                                'appid' => '',
//                                'noncestr' => '',
//                                'package' => '',
//                                'partnerid' => '',
//                                'prepayid' => '',
//                                'timestamp' => '',
//                                'sign' => '',
//                            ],
//                            'message' => 'false: 表示失败',
//                            'error' => ''
//                        ]
//                    ],

                    '确认改期' => [
                        '说明' => 'status_code为wait-4的时候，才可以调用',
                        'url' => $http . '/api/appointment/confirm-rescheduled',
                        'method' => 'POST',
                        'params' => [
                            'token' => ''
                        ],
                        'form-data' => [
                            'id' => '约诊ID'
                        ],
                        'response' => [
                            'success' => '',
                            'message' => '',
                            'error' => ''
                        ]
                    ],

                    '完成面诊' => [
                        'url' => $http . '/api/appointment/complete',
                        'method' => 'POST',
                        'params' => [
                            'token' => ''
                        ],
                        'form-data' => [
                            'id' => '约诊ID'
                        ],
                        'response' => [
                            'success' => '',
                            'message' => '',
                            'error' => ''
                        ]
                    ],

                    '取消约诊' => [
                        'url' => $http . '/api/appointment/cancel',
                        'method' => 'POST',
                        'params' => [
                            'token' => ''
                        ],
                        'form-data' => [
                            'id' => '约诊ID'
                        ],
                        'response' => [
                            'success' => '',
                            'message' => '',
                            'error' => ''
                        ]
                    ],
                ],

                '支付' => [
                    '用钱包余额支付约诊订单' => [
                        'url' => $http . '/api/pay/appointment',
                        'method' => 'POST',
                        'params' => [
                            'token' => ''
                        ],
                        'form-data' => [
                            'id' => '约诊订单ID'
                        ],
                        'response' => [
                            'data' => [
                                'info' => '【支付成功】HTTP CODE是200；【余额不足，请去充值】HTTP CODE是400',
                                'appointment_info' => [
                                    '说明' => '和/api/appointment/detail返回信息格式一样',
                                    'doctor_info' => [
                                        'id' => '接诊医生ID',
                                        'name' => '接诊医生姓名',
                                        'head_url' => '接诊医生头像URL',
                                        'job_title' => '接诊医生职称',
                                        'hospital' => '接诊医生所属医院',
                                        'department' => '接诊医生所属科室'
                                    ],
                                    'locums_doctor_info' => [
                                        'id' => '代约医生ID； 平台代约为1； 患者自己约的为空字符串',
                                        'name' => '代约医生姓名； 平台代约为【医脉平台】； 患者自己约的为空字符串',
                                        'head_url' => '代约医生头像URL； 平台代约为默认，',
                                        'job_title' => '代约医生职称； 平台代约和患者自己约都为空字符串',
                                        'hospital' => '代约医生所属医院； 平台代约和患者自己约都为空字符串',
                                        'department' => '代约医生所属科室； 平台代约和患者自己约都为空字符串'
                                    ],
                                    'patient_info' => [
                                        'name' => '患者姓名',
                                        'head_url' => '患者头像URL',
                                        'sex' => '患者性别',
                                        'age' => '患者年龄',
                                        'phone' => '患者电话',
                                        'history' => '病情描述',
                                        'img_url' => '病历图片url序列,url中把{_thumb}替换掉就是未压缩图片,例如:/uploads/case-history/2016/05/011605130001/1463539005_thumb.jpg,原图就是:/uploads/case-history/2016/05/011605130001/1463539005.jpg',
                                    ],
                                    'other_info' => [
                                        'progress' => '顶部进度',
                                        'time_line' => [
                                            '说明' => 'time_line数组及其内部other数组下可能有1条或多条信息,需要遍历,0和1的序号不用在意,foreach就好',
                                            '内容' => [[
                                                'time' => '时间轴左侧的时间',
                                                'info' => [
                                                    'text' => '文案描述',
                                                    'other' => [
                                                        '内容' => [[
                                                            'name' => '其他的信息名称,例如:期望就诊时间',
                                                            'content' => '其他的信息内容,例如:2016-05-18 上午; 多条时间信息用逗号隔开,展示时则是换行展示,例如:2016-05-12 上午,2016-05-13 下午'
                                                        ], []]
                                                    ]
                                                ],
                                                'type' => '决定使用什么icon; begin | wait'
                                            ],
                                                [
                                                    'time' => '时间轴左侧的时间, null为没有',
                                                    'info' => [
                                                        'text' => '文案描述',
                                                        'other' => 'null为没有'
                                                    ],
                                                    'type' => '决定使用什么icon; begin | wait'
                                                ]]
                                        ],
                                        'status_code' => '状态CODE',
                                        'is_pay' => '是否支付；0为还未，1为已经支付；有可能wait-1也有已经支付的'
                                    ]
                                ]
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ],

                    '用钱包余额批量支付约诊订单' => [
                        'url' => $http . '/api/pay/appointment-list',
                        'method' => 'POST',
                        'params' => [
                            'token' => ''
                        ],
                        'form-data' => [
                            'id_list' => '约诊订单ID List，用逗号拼接，例如：100000，100000'
                        ],
                        'response' => [
                            '说明' => '传来多少个订单ID，data里就会有多少组数据，每组都一定会返回appointment_id和status_code',
                            'data' => [
                                [
                                    'info' => '支付成功',
                                    'appointment_id' => '约诊ID',
                                    'appointment_info' => [
                                        '说明' => '和/api/appointment/detail返回信息格式一样',
                                        'doctor_info' => [
                                            'id' => '接诊医生ID',
                                            'name' => '接诊医生姓名',
                                            'head_url' => '接诊医生头像URL',
                                            'job_title' => '接诊医生职称',
                                            'hospital' => '接诊医生所属医院',
                                            'department' => '接诊医生所属科室'
                                        ],
                                        'locums_doctor_info' => [
                                            'id' => '代约医生ID； 平台代约为1； 患者自己约的为空字符串',
                                            'name' => '代约医生姓名； 平台代约为【医脉平台】； 患者自己约的为空字符串',
                                            'head_url' => '代约医生头像URL； 平台代约为默认，',
                                            'job_title' => '代约医生职称； 平台代约和患者自己约都为空字符串',
                                            'hospital' => '代约医生所属医院； 平台代约和患者自己约都为空字符串',
                                            'department' => '代约医生所属科室； 平台代约和患者自己约都为空字符串'
                                        ],
                                        'patient_info' => [
                                            'name' => '患者姓名',
                                            'head_url' => '患者头像URL',
                                            'sex' => '患者性别',
                                            'age' => '患者年龄',
                                            'phone' => '患者电话',
                                            'history' => '病情描述',
                                            'img_url' => '病历图片url序列,url中把{_thumb}替换掉就是未压缩图片,例如:/uploads/case-history/2016/05/011605130001/1463539005_thumb.jpg,原图就是:/uploads/case-history/2016/05/011605130001/1463539005.jpg',
                                        ],
                                        'other_info' => [
                                            'progress' => '顶部进度',
                                            'time_line' => [
                                                '说明' => 'time_line数组及其内部other数组下可能有1条或多条信息,需要遍历,0和1的序号不用在意,foreach就好',
                                                '内容' => [[
                                                    'time' => '时间轴左侧的时间',
                                                    'info' => [
                                                        'text' => '文案描述',
                                                        'other' => [
                                                            '内容' => [[
                                                                'name' => '其他的信息名称,例如:期望就诊时间',
                                                                'content' => '其他的信息内容,例如:2016-05-18 上午; 多条时间信息用逗号隔开,展示时则是换行展示,例如:2016-05-12 上午,2016-05-13 下午'
                                                            ], []]
                                                        ]
                                                    ],
                                                    'type' => '决定使用什么icon; begin | wait'
                                                ],
                                                    [
                                                        'time' => '时间轴左侧的时间, null为没有',
                                                        'info' => [
                                                            'text' => '文案描述',
                                                            'other' => 'null为没有'
                                                        ],
                                                        'type' => '决定使用什么icon; begin | wait'
                                                    ]]
                                            ],
                                            'status_code' => '状态CODE',
                                            'is_pay' => '是否支付；0为还未，1为已经支付；有可能wait-1也有已经支付的'
                                        ]
                                    ],
                                    'status_code' => '200'
                                ],
                                [
                                    'info' => '余额不足，请去充值 | 状态不对，请刷新再请求',
                                    'appointment_id' => '约诊ID',
                                    'status_code' => '400'
                                ],
                                [
                                    'info' => '不知道会是啥的错误信息',
                                    'appointment_id' => '约诊ID',
                                    'status_code' => '500'
                                ]
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ],
                ],

                '钱包' => [
                    '我的钱包' => [
                        'url' => $http . '/api/wallet/info',
                        'method' => 'GET',
                        'params' => [
                            'token' => ''
                        ],
                        'response' => [
                            'data' => [
                                'wallet' => [
                                    'total' => '总额',
                                    'freeze' => '冻结中'
                                ],
                                'appointment_list' => [
                                    'id' => '约诊ID',
                                    'doctor_id' => '医生ID',
                                    'doctor_name' => '医生姓名',
                                    'doctor_head_url' => '医生头像',
                                    'doctor_job_title' => '医生头衔',
                                    'doctor_is_auth' => '医生是否认证',
                                    'doctor_dept' => '医生所在部门/科室',
                                    'doctor_hospital' => '医生所在医院',
                                    'patient_id' => '患者ID',
                                    'patient_name' => '患者姓名',
                                    'patient_sex' => '患者性别',
                                    'patient_age' => '患者姓名',
                                    'request_mode' => '请求约诊的模式:我的医生、找专家、医生代约; 如果为null,则是由医生发起的,不用显示那个标签',
                                    'deposit' => '订金/押金',
                                    'price' => '约诊费用',
                                    'time' => '时间',
                                    'deadline' => '最后缴纳时间',
                                    'status' => '状态',
                                    'status_code' => '状态code'
                                ]
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ],

                    '收支明细列表' => [
                        'url' => $http . '/api/wallet/record',
                        'method' => 'GET',
                        'params' => [
                            'token' => ''
                        ],
                        'response' => [
                            'data' => [
                                'id' => 'ID',
                                'name' => '名目名称',
                                'transaction_id' => '交易单号/预约号',
                                'price' => '价格',
                                'type' => '类型：收入/支出',
                                'status' => '状态：还没想好怎么用，先传前台去',
                                'time' => '交易发生时间'
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ],

                    '患者会员充值' => [
                        'APP支付接口调用文档' => 'https://pay.weixin.qq.com/wiki/doc/api/app/app.php?chapter=9_12&index=2',
                        'url' => $http . '/api/wallet/recharge',
                        'method' => 'POST',
                        'params' => [
                            'token' => ''
                        ],
                        'form-data' => [
                            'fee' => '金额；单位：元'
                        ],
                        'response' => [
                            'data' => [
                                'appid' => '',
                                'noncestr' => '',
                                'package' => '',
                                'partnerid' => '',
                                'prepayid' => '',
                                'timestamp' => '',
                                'sign' => '',
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
                                'url' => '广播链接; 绝对地址',
                                'img_url' => '首页图片URL; 绝对地址',
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
                    ],

                    '全部已读' => [
                        'url' => $http . '/api/radio/all-read',
                        'method' => 'GET',
                        'params' => [
                            'token' => ''
                        ],
                        '说明' => 'HTTP状态204',
                        'response' => [
                            'success' => '',
                            'message' => '',
                            'error' => ''
                        ]
                    ]
                ],

                '患者约诊信息' => [
                    '全部信息' => [
                        'url' => $http . '/api/msg/all',
                        'method' => 'GET',
                        'params' => [
                            'token' => ''
                        ],
                        'response' => [
                            'data' => [
                                'id' => '消息ID',
                                'appointment_id' => '约诊号; 用来跳转到对应的【我的接诊】记录',
                                'text' => '显示文案',
                                'type' => '是否重要,0为不重要,1为重要; 重要的内容必须点开告知服务器变为已读; 不重要内容点开列表就全部变已读',
                                'read' => '是否已读,0为未读,1为已读; 该状态后期会将type为0的,获取时直接全部置为已读',
                                'time' => '时间'
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ],

                    '未读信息' => [
                        'url' => $http . '/api/msg/new',
                        'method' => 'GET',
                        'params' => [
                            'token' => ''
                        ],
                        'response' => [
                            'data' => [
                                'id' => '消息ID',
                                'appointment_id' => '约诊号; 用来跳转到对应的【我的接诊】记录',
                                'text' => '显示文案',
                                'type' => '是否重要,0为不重要,1为重要; 重要的内容必须点开告知服务器变为已读; 不重要内容点开列表就全部变已读',
                                'read' => '是否已读,0为未读,1为已读; 该状态后期会将type为0的,获取时直接全部置为已读',
                                'time' => '时间'
                            ],
                            'message' => '',
                            'error' => ''
                        ]
                    ],

                    '发送已读状态更新' => [
                        '说明' => 'HTTP状态204',
                        'url' => $http . '/api/msg/read',
                        'method' => 'POST',
                        'params' => [
                            'token' => ''
                        ],
                        'form-data' => [
                            'id' => '消息ID'
                        ],
                        'response' => [
                            'success' => '',
                            'message' => '',
                            'error' => ''
                        ]
                    ],
//                    '全部已读' => [
//                        '说明' => 'HTTP状态204',
//                        'url' => $http . '/api/msg/admissions/all-read',
//                        'method' => 'GET',
//                        'params' => [
//                            'token' => ''
//                        ],
//                        'response' => [
//                            'success' => '',
//                            'message' => '',
//                            'error' => ''
//                        ]
//                    ]
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
