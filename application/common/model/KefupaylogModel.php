<?php
namespace app\common\model;

use think\Db;

class KefupaylogModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 's1s_kefu_pay_log';
    }

    /**
     * 给客服打款
     * @Author 贺强
     * @date   2018-09-07
     * @param  array      $data 打款数据
     */
    public function giveMoney($data)
    {
        Db::startTrans();
        try {
            $a        = new AdminModel();
            $zg_model = $a->getModel(['id' => $data['z_id']], 'balance');
            if ($zg_model['balance'] < $data['money']) {
                throw new \Exception(1);
            }
            $res = $a->increment('balance', ['id' => $data['kf_id']], $data['money']);
            if (!$res) {
                throw new \Exception(2);
            }
            $res = $a->decrement('balance', ['id' => $data['z_id']], $data['money']);
            if (!$res) {
                throw new \Exception(3);
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return $e->getMessage();
        }
    }
}
