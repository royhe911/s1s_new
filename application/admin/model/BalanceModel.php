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
        $merchant = $a->getModel(['id' => $data['uid']], '`status`,balance,nickname,s_id');
        if (empty($merchant)) {
            return 2;
        }
        // 判断用户状态是否可以充值
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
        // 开启事务给用户充值
        Db::startTrans();
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
            $data['s_id']     = $merchant['s_id'];
            $data['nickname'] = $merchant['nickname'];
            $data['addtime']  = time();
            $r                = new RechargeModel();
            $res              = $r->add($data);
            if (!$res) {
                Db::rollback();
                return 8;
            }
        }
        $res = $m->modifyField('balance', $after_money, ['id' => $uid]);
        if (!$res) {
            Db::rollback();
            return 6;
        }
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
