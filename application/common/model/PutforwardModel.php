<?php
namespace app\common\model;

use think\Db;

class PutforwardModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 's1s_putforward';
    }

    /**
     * 商家提现
     * @Author 贺强
     * @date   2018-08-30
     * @param  array      $data 提现数据
     */
    public function putforward($data)
    {
        $a             = new AdminModel();
        $balance_model = $a->getModel(['id' => $data['uid']]);
        if (empty($data['amount']) || $balance_model['balance'] < $data['amount']) {
            return 1;
        }
        if (empty($data['bank_card']) || empty($data['bank_name'])) {
            return 2;
        }
        if ($data['bank_card'] !== $balance_model['bank_card'] || $data['bank_name'] !== $balance_model['bank_name']) {
            return 3;
        }
        $data['addtime'] = time();
        Db::startTrans();
        $res = $a->modify(['balance' => $balance_model['balance'] - $data['amount'], 'putforward' => $data['amount']], ['id' => $data['uid']]);
        if (!$res) {
            Db::rollback();
            return 4;
        }
        $res = $this->add($data);
        if (!$res) {
            Db::rollback();
            return 5;
        }
        Db::commit();
        return true;
    }

    /**
     * 提现审核不通过
     * @Author 贺强
     * @date   2018-08-30
     * @param  array      $data 提现数据
     */
    public function auditor_not_pass($data)
    {
        $putforward_model = $this->getModel(['id' => $data['id']]);
        $a                = new AdminModel();
        $user_model       = $a->getModel(['id' => $putforward_model['uid']]);
        if (empty($user_model)) {
            return 1;
        }
        Db::startTrans();
        $user_model['balance'] += $putforward_model['amount'];
        $user_model['putforward'] -= $putforward_model['amount'];
        $res = $a->modify($user_model, ['id' => $user_model['id']]);
        if (!$res) {
            Db::rollback();
            return 2;
        }
        $data['auditor_time'] = time();
        $res                  = $this->modify($data, ['id' => $data['id']]);
        if (!$res) {
            Db::rollback();
            return 3;
        }
        Db::commit();
        return true;
    }
}
