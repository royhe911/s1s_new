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
    '/'                      => 'admin/Admin/index',
    '/login'                 => 'admin/Admin/login', // 后台登录页
    '/admin'                 => 'admin/Admin/index', // 后台首页
    '/upload'                => 'admin/Upload/upload_img', // 上传文件
    '/admin/doLogin'         => 'admin/Admin/do_login', // 后台登录操作
    '/admin/index_v1'        => 'admin/Admin/index_v1', // 后台首页欢迎页
    '/admin/lists'           => 'admin/Admin/lists', // 后台管理员列表
    '/admin/add'             => 'admin/Admin/add', // 后台添加管理员
    '/admin/edit'            => 'admin/Admin/edit', // 后台添加管理员
    '/admin/operation'       => 'admin/Admin/operation', // 后台操作管理员
    '/menu/lists'            => 'admin/Menu/lists', // 菜单列表
    '/menu/edit'             => 'admin/Menu/edit', // 编辑菜单
    '/menu/add'              => 'admin/Menu/add', // 添加菜单
    '/menu/del'              => 'admin/Menu/del', // 删除菜单
    '/menu/power'            => 'admin/Menu/power', // 菜单权限管理
    '/finance/recharge'      => 'admin/Finance/recharge', // 商家充值
    '/finance/rechargelog'   => 'admin/Finance/rechargelog', // 商家充值
    '/finance/auditor'       => 'admin/Finance/auditor', // 充值审核
    '/finance/detail'        => 'admin/Finance/detail', // 收支明细
    '/finance/putforwardlog' => 'admin/Finance/putforwardlog', // 提现记录
    '/finance/putforward'    => 'admin/Finance/putforward', // 提现
    '/finance/auditorf'      => 'admin/Finance/auditorf', // 提现审核
    '/finance/commision'     => 'admin/Finance/commision', // 佣金审核
    '/finance/auditorp'      => 'admin/Finance/auditorp', // 审核佣金申请
    '/finance/supplement'    => 'admin/Finance/supplement', // 平台打款
    '/finance/kfpay'         => 'admin/Finance/kfpay', // 客服充值
    '/shop/index'            => 'admin/Shop/index', // 店铺列表
    '/shop/add'              => 'admin/Shop/add', // 添加店铺
    '/shop/edit'             => 'admin/Shop/edit', // 修改店铺
    '/shop/auditor'          => 'admin/Shop/auditor', // 店铺审核
    '/shokey/index'          => 'admin/Shokey/index', // 试客列表
    '/shokey/auditors'       => 'admin/Shokey/auditors', // 试客审核
];
