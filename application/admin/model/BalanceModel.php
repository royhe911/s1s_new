<?php
namespace app\admin\model;

use think\Db;

class BalanceModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 's1s_balance';
    }

    /**
     * 金额变动记录
     * @param  integer $type 变动类型
     * @param  array   $data 变动记录数据
     */
    public function balanceLog($type = 0, $data = [])
    {
        if (empty($type) || !is_numeric($type)) {
            return 1;
        }
        if (empty($data['uid']) || !is_numeric($data['uid'])) {
            return 2;
        }
        $a = new AdminModel();
        // 查询用户是否存在
        $merchant = $a->getModel(['id' => $data['uid']]);
        if (empty($merchant)) {
            return 2;
        }
        // 判断用户状态是否可以进行操作
        switch (intval($merchant['status'])) {
            case 0:
                return 3;
                break;
            case 4:
                return 4;
                break;
            case 6:
                return 5;
                break;
        }
        $amount = $data['amount'];
        $uid    = $data['uid'];
        if ($type === 2) {
            $before_money = $merchant['balance'];
            $after_money  = $merchant['balance'] + $amount;
        } elseif ($type === 1) {
            $before_money = $merchant['balance'] + $merchant['putforward'];
            $after_money  = $merchant['balance'];
        }
        $log = [
            'uid'          => $uid,
            'before_money' => $before_money,
            'after_money'  => $after_money,
            'type'         => $type,
            'change_money' => $amount,
            'addtime'      => time(),
        ];
        // 开启事务操作用户余额
        Db::startTrans();
        if ($type === 2) {
            // 如果是充值，修改充值状态
            $r   = new RechargeModel();
            $res = $r->modify(['status' => $data['status'], 'auditor_time' => time()], ['id' => $data['id']]);
            if (!$res) {
                Db::rollback();
                return 8;
            }
        } elseif ($type === 3) {
            // 如果是发布任务，修改任务状态
            $p  = new ProductModel();
            $pr = new ProductArrModel();
        } elseif ($type === 1) {
            // 如果是提现，修改提现状态
            $p   = new PutforwardModel();
            $res = $p->modify(['status' => $data['status'], 'auditor_time' => time()], ['id' => $data['id']]);
            if (!$res) {
                Db::rollback();
                return 8;
            }
        }
        // 修改对应用户余额
        $res = $a->modifyField('balance', $after_money, ['id' => $uid]);
        if ($res === false) {
            Db::rollback();
            return 6;
        }
        // 添加金额变动流水记录
        $b   = new BalanceModel();
        $res = $b->add($log);
        if (!$res) {
            Db::rollback();
            return 7;
        }
        Db::commit();
        return true;
    }
}
