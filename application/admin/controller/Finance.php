<?php
namespace app\admin\controller;

use app\admin\model\AdminModel;
use app\admin\model\BalanceModel;
use app\admin\model\RechargeModel;

/**
 * 财务管理
 */
class Finance extends \think\Controller
{
    /**
     * 商家充值
     * @Author 贺强
     * @date   2018-08-27
     * @param  RechargeModel $r RechargeModel 实例
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
     * @Author 贺强
     * @date   2018-08-27
     * @param  RechargeModel $r RechargeModel 实例
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
     * 充值审核
     * @Author 贺强
     * @date   2018-08-27
     */
    public function auditor()
    {
        $admin = $this->is_login();
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
     * 收到明细
     * @Author 贺强
     * @date   2018-08-27
     * @param  BalanceModel $b BalanceModel 实例
     */
    public function detail(BalanceModel $b)
    {
        // 判断用户是否登录
        $admin = $this->is_login();
        $where = "is_delete=0";
        $a     = new AdminModel();
        if ($admin['role_id'] === 2 || $admin['role_id'] === 4 || $admin['role_id'] === 5) {
            // 若登录角色是业务员或客服主管理或客服，则只能看到他名下的商家收支明细
            $ids  = '0';
            $sarr = $a->getList(['s_id' => $admin['id']], 'id');
            foreach ($sarr as $sa) {
                $ids .= ",{$sa['id']}";
            }
            $where .= " and uid in ($ids)";
        } elseif ($admin['role_id'] === 3) {
            // 若登录角色是商家，则只能看到他自己的收支明细
            $where .= " and uid={$admin['id']}";
        }
        // 获取查询条件
        $keyword = $this->request->post('keyword', '');
        if (!empty($keyword)) {
            $k_ids = "0";
            $bns   = $a->getList(['is_delete' => 0, 'nickname' => ['like', "%{$keyword}%"]], 'id');
            foreach ($bns as $s) {
                $k_ids .= ",{$s['id']}";
            }
            $where .= " and uid in ($k_ids)";
        }
        $page     = intval($this->request->get('page', 1));
        $pagesize = intval($this->request->get('pagesize', config('PAGESIZE')));
        $list     = $b->getList($where, true, "$page,$pagesize", 'addtime desc');
        if ($list) {
            $business = $this->getBusiness();
            foreach ($list as &$item) {
                if (!empty($business[$item['uid']])) {
                    $item['business'] = $business[$item['uid']];
                }
                if (!empty($item['addtime'])) {
                    $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                }
                switch ($item['type']) {
                    case 1:
                        $item['remark'] = '提现';
                        break;
                    case 2:
                        $item['remark'] = '充值';
                        break;
                    case 3:
                        $item['remark'] = '发布任务费用扣除';
                        break;
                    case 4:
                        $item['remark'] = '退单退款';
                        break;
                    case 5:
                        $item['remark'] = '下架退款';
                        break;
                    case 6:
                        $item['remark'] = '订单异常返款';
                        break;
                    case 7:
                        $item['remark'] = '实际下单价大于发布价，扣除差价';
                        break;
                    case 8:
                        $item['remark'] = '实际下单价小于发布价，补偿差价';
                        break;
                }
            }
        }
        $count = $b->getCount($where);
        $pages = ceil($count / $pagesize);
        return $this->fetch('detail', ['list' => $list, 'pages' => $pages, 'keyword' => $keyword]);
    }

    /**
     * 商家提现
     * @Author 贺强
     * @date   2018-08-27
     */
    public function putforward()
    {
        $admin = $this->is_login();
    }

    /**
     * 站点补款
     * @Author 贺强
     * @date   2018-08-27
     */
    public function supplement()
    {
        $admin = $this->is_login();
    }
}
