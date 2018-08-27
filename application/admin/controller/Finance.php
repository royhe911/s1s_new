<?php
namespace app\admin\controller;

use app\admin\model\BalanceModel;

/**
 * 财务管理
 */
class Finance extends \think\Controller
{
    /**
     * 商家充值
     */
    public function recharge(BalanceModel $b)
    {
        if ($this->request->isAjax()) {
            $uid      = $this->request->post('uid'); // 充值商家ID
            $amount   = $this->request->post('amount'); // 充值金额
            $orders   = $this->request->post('orders'); // 充值交易单号
            $pay_mode = $this->request->post('pay_mode'); // 充值方式
            $img      = $this->request->post('img'); // 充值截图
            if (empty($uid) || empty($amount) || empty($orders) || empty($pay_mode) || empty($img)) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            $data = [
                'uid'      => $uid,
                'amount'   => $amount,
                'orders'   => $orders,
                'pay_mode' => $pay_mode,
                'img'      => $img,
            ];
            $res = $b->balanceLog(2, $uid, $amount);
            if ($res !== true) {
                switch ($res) {
                    case 2:
                        $msg = '用户不存在';
                        break;
                    case 3:
                        $msg = '用户待审核';
                        break;
                    case 4:
                        $msg = '用户审核未通过';
                        break;
                    case 5:
                        $msg = '该用户已被禁用';
                        break;
                    case 6 || 7 || 8:
                        $msg = '充值失败';
                        break;
                }
                return ['status' => $res, 'info' => "{$msg}，无法充值"];
            }
            return ['status' => 0, 'info' => '充值成功'];
        } else {
            $time = time();
            return $this->fetch('recharge', ['time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time)]);
        }
    }

    /**
     * 充值记录
     */
    public function rechargelog()
    {
        return $this->fetch('rechargelog');
    }

    /**
     * 收支明细
     */
    public function detail()
    {
        # code...
    }

    /**
     * 商家提现
     */
    public function putforward()
    {
        # code...
    }

    /**
     * 站点补款
     */
    public function supplement()
    {
        # code...
    }
}
