<?php
namespace app\admin\model;

class LogModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 's1s_log';
    }

    const TYPE_TASK_DOWN_ALL            = 1; // 日志类型 - 批量下架
    const TYPE_TASK_APPLY_DOWN_ALL      = 2; // 日志类型 - 申请批量下架
    const TYPE_PRODUCT_ADD              = 3; // 日志类型 - 发布任务
    const TYPE_PRODUCT_UPDATE           = 4; // 日志类型 - 修改任务
    const TYPE_PRODUCT_PAY              = 5; // 日志类型 - 任务付款
    const TYPE_CONFIRM_PRODUCT_PASS     = 6; // 日志类型 - 商品审核通过
    const TYPE_CONFIRM_PRODUCT_FAIL     = 7; // 日志类型 - 商品审核不通过
    const TYPE_TASK_APPLY_DOWN          = 8; // 日志类型 - 申请下架
    const TYPE_TASK_APPLY_DOWN_PASS     = 9; // 日志类型 - 申请下架通过
    const TYPE_TASK_APPLY_DOWN_FAIL     = 10; // 日志类型 - 申请下架拒绝
    const TYPE_TASK_APPLY_DOWN_ALL_PASS = 11; // 日志类型 - 申请批量下架通过
    const TYPE_TASK_APPLY_DOWN_ALL_FAIL = 12; // 日志类型 - 申请批量下架拒绝
    const TYPE_APPLY_CASH               = 13; // 日志类型 - 申请提现
    const TYPE_APPLY_CASH_PASS          = 14; // 日志类型 - 申请提现审核通过
    const TYPE_APPLY_CASH_FAIL          = 15; // 日志类型 - 申请提现审核不通过

    /**
     * 写操作日志
     * @Author  贺强
     * @time    2018-08-29
     * @param   array                    $data 要写入的数据
     */
    public function addLog($data)
    {
        $admin           = Session::get('admin');
        $data['addtime'] = time();
        $data['uid']     = $admin['id'];
        $data['uname']   = $admin['nickname'];
        $this->add($data);
    }
}
