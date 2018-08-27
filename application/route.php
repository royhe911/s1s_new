<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    '/'                    => 'Index/index',
    '/login'               => 'admin/Index/login', // 后台登录页
    '/admin'               => 'admin/Index/index', // 后台首页
    '/admin/doLogin'       => 'admin/Index/do_login', // 后台登录操作
    '/admin/index_v1'      => 'admin/Index/index_v1', // 后台首页欢迎页
    '/admin/list'          => 'admin/Index/lists', // 后台管理员列表
    '/admin/add'           => 'admin/Index/add', // 后台添加管理员
    '/admin/edit'          => 'admin/Index/edit', // 后台添加管理员
    '/admin/del'           => 'admin/Index/del', // 后台添加管理员
    '/menu/list'           => 'admin/Menu/lists', // 菜单列表
    '/menu/edit'           => 'admin/Menu/edit', // 编辑菜单
    '/menu/add'            => 'admin/Menu/add', // 添加菜单
    '/menu/del'            => 'admin/Menu/del', // 删除菜单
    '/menu/power'          => 'admin/Menu/power', // 菜单权限管理
    '/finance/recharge'    => 'admin/Finance/recharge', //商家充值
    '/finance/rechargelog' => 'admin/Finance/rechargelog', //商家充值
    '/upload'              => 'admin/Upload/upload_img', // 上传文件
];
