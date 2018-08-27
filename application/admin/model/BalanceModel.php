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
        $merchant = $a->getModel(['id' => $data['uid']], '`status`,balance');
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
        // 开启事务操作用户余额
        Db::startTrans();
        $amount      = $data['amount'];
        $uid         = $data['uid'];
        $after_money = $merchant['balance'] + $amount;
        $log         = [
            'uid'          => $uid,
            'before_money' => $merchant['balance'],
            'after_money'  => $after_money,
            'type'         => $type,
            'change_money' => $amount,
            'addtime'      => time(),
        ];
        if ($type === 2) {
            // 如果是充值，修改充值状态
            $r   = new RechargeModel();
            $res = $r->modifyField('status', $data['status'], ['id' => $data['id']]);
            if (!$res) {
                Db::rollback();
                return 8;
            }
        } elseif ($type === 3) {
            // 如果是发布任务，修改任务状态
            $p  = new ProductModel();
            $pr = new ProductArrModel();
        }
        // 修改对应用户余额
        $res = $a->modifyField('balance', $after_money, ['id' => $uid]);
        if (!$res) {
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
