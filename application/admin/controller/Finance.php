<?php
namespace app\admin\controller;

use app\admin\model\BalanceModel;
use app\admin\model\RechargeModel;

/**
 * 财务管理
 */
class Finance extends \think\Controller
{
    /**
     * 商家充值
     */
    public function recharge(RechargeModel $r)
    {
        $admin = $this->is_login();
        if ($this->request->isAjax()) {
            if ($admin['role_id'] !== 3) {
                return ['status' => 9, 'info' => '只有商家可以充值'];
            }
            $amount   = $this->request->post('amount'); // 充值金额
            $orders   = $this->request->post('orders'); // 充值交易单号
            $pay_mode = $this->request->post('paymode'); // 充值方式
            $img      = $this->request->post('img'); // 充值截图
            if (empty($amount) || empty($orders) || !isset($pay_mode) || empty($img)) {
                return ['status' => 1, 'info' => '非法参数'];
            }

            $data = [
                'uid'      => $admin['id'],
                'nickname' => $admin['nickname'],
                's_id'     => $admin['s_id'],
                'amount'   => $amount,
                'pay_mode' => $pay_mode,
                'orders'   => $orders,
                'img'      => $img,
                'addtime'  => time(),
            ];
            $res = $r->add($data);
            if (!$res) {
                return ['status' => 2, 'info' => '充值失败'];
            }
            return ['status' => 0, 'info' => '充值成功，等待审核'];
        } else {
            $time = time();
            return $this->fetch('recharge', ['time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time)]);
        }
    }

    /**
     * 充值记录
     */
    public function rechargelog(RechargeModel $r)
    {
        $admin   = $this->is_login();
        $where   = [];
        $keyword = $this->request->post('keyword');
        if (!empty($keyword)) {
            $where['nickname'] = ['like', "%{$keyword}%"];
        }
        $page     = intval($this->request->get('page', 1));
        $pagesize = intval($this->request->get('pagesize', config('PAGESIZE')));
        $list     = $r->getList($where, true, "$page,$pagesize", "`status`,addtime desc");
        if ($list) {
            foreach ($list as &$item) {
                if (!empty($item['addtime'])) {
                    $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                } else {
                    $item['addtime'] = '';
                }
                switch (intval($item['status'])) {
                    case 0:
                        $item['status_txt'] = '待审核';
                        break;
                    case 1:
                        $item['status_txt'] = '审核不通过';
                        break;
                    default:
                        $item['status_txt'] = '已审核';
                        break;
                }
            }
        }
        // print_r($list);exit;
        $count = $r->getCount($where);
        $pages = ceil($count / $pagesize);
        return $this->fetch('rechargelog', ['list' => $list, 'pages' => $pages, 'role_id' => $admin['role_id']]);
    }

    /**
     * 审核充值
     */
    public function auditor()
    {
        $param  = $this->request->post();
        $r      = new RechargeModel();
        $status = intval($param['status']);
        if ($status === 1) {
            // 审核不通过
            $res = $r->modify(['reason' => $param['reason'], 'status' => $status], ['id' => $param['id']]);
            if ($res !== false) {
                return ['status' => 0, 'info' => '审核成功'];
            } else {
                return ['status' => 1, 'info' => '审核失败'];
            }
        } elseif ($status === 8) {
            // 审核通过
            $data           = $r->getModel(['id' => $param['id']]);
            $data['status'] = 8;
            $b              = new BalanceModel();
            $res            = $b->balanceLog(2, $data);
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
                        $msg = '审核失败';
                        break;
                }
                return ['status' => $res, 'info' => $msg];
            }
            return ['status' => 0, 'info' => '审核成功'];
        }
    }

    /**
     * 收支明细
     */
    public function detail()
    {
        $admin = $this->is_login();
    }

    /**
     * 商家提现
     */
    public function putforward()
    {
        $admin = $this->is_login();
    }

    /**
     * 站点补款
     */
    public function supplement()
    {
        $admin = $this->is_login();
    }
}
