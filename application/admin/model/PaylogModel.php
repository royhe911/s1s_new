<?php
namespace app\admin\model;

use think\Db;

class PaylogModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 's1s_pay_log';
    }

    /**
     * 佣金审核
     * @Author 贺强
     * @date   2018-09-05
     * @param  integer    $id     条目ID
     * @param  integer    $status 状态
     */
    public function auditor($id, $status)
    {
        $pay = $this->getModel(['id' => $id]);
        if (!$pay) {
            return 1;
        }
        Db::startTrans();
        // 更新申请记录状态
        $res = $this->modify(['status' => $status, 'auditor_time' => time()], ['id' => $id]);
        if (!$res) {
            Db::rollback();
            return 2;
        }
        $a = new AdminModel();
        // 客服主管余额增加
        $res = $a->increment('balance', ['id' => $pay['uid']], $pay['money']);
        if (!$res) {
            Db::rollback();
            return 3;
        }
        // 取得财务 ID
        $model = $a->getModel(['role_id' => 6, 'is_delete' => 0], 'id');
        // 财务余额减少
        $res = $a->decrement('balance', ['id' => $model['id']], $pay['money']);
        if (!$res) {
            Db::rollback();
            return 4;
        }
        Db::commit();
        return true;
    }
}
