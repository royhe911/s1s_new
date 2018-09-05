<?php
namespace app\admin\controller;

use app\admin\model\AdminModel;
use app\admin\model\BalanceModel;
use app\admin\model\LogModel;
use app\admin\model\PaylogModel;
use app\admin\model\PutforwardModel;
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
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
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
            $l   = new LogModel();
            $l->addLog(['type' => LogModel::TYPE_RECHARGE, 'content' => '商家充值，充值金额：' . $amount]);
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
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        $where = [];
        if ($admin['role_id'] === 3) {
            // 若登录角色是商家，则只能看到他自己的充值记录
            $where['uid'] = $admin['id'];
        } elseif ($admin['role_id'] === 2 || $admin['role_id'] === 4 || $admin['role_id'] === 5) {
            // 若登录角色是业务员或客服主管理或客服，则只能看到他名下的商家充值记录
            $ids  = '0';
            $a    = new AdminModel();
            $sarr = $a->getList(['s_id' => $admin['id']], 'id');
            foreach ($sarr as $sa) {
                $ids .= ",{$sa['id']}";
            }
            $where['uid'] = ['in', $ids];
        }
        $keyword = $this->request->post('keyword');
        if (!empty($keyword)) {
            $where['nickname'] = ['like', "%{$keyword}%"];
        }
        // 分页参数
        $page     = intval($this->request->get('page', 1));
        $pagesize = intval($this->request->get('pagesize', config('PAGESIZE')));
        $list     = $r->getList($where, true, "$page,$pagesize", "`status`,addtime desc");
        if ($list) {
            foreach ($list as &$item) {
                if (!empty($item['addtime'])) {
                    $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                }
                if (!empty($item['auditor_time'])) {
                    $item['auditor_time'] = date('Y-m-d H:i:s', $item['auditor_time']);
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
        // 判断是否有权限访问或操作
        $admin  = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        $param  = $this->request->post();
        $r      = new RechargeModel();
        $l      = new LogModel();
        $status = intval($param['status']);
        if ($status === 1) {
            // 审核不通过
            $res = $r->modify(['reason' => $param['reason'], 'auditor_time' => time(), 'status' => $status], ['id' => $param['id']]);
            $l->addLog(['type' => LogModel::TYPE_RECHARGE_AUDITOR, 'content' => '充值审核不通过，审核的充值ID：' . $param['id']]);
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
            $l->addLog(['type' => LogModel::TYPE_RECHARGE_AUDITOR, 'content' => '充值审核通过，审核的充值ID：' . $param['id']]);
            return ['status' => 0, 'info' => '审核成功'];
        }
    }

    /**
     * 提现审核
     * @Author 贺强
     * @date   2018-08-30
     */
    public function auditorf()
    {
        // 判断是否有权限访问或操作
        $admin  = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        $param  = $this->request->post();
        $p      = new PutforwardModel();
        $l      = new LogModel();
        $status = intval($param['status']);
        if ($status === 1) {
            // 审核不通过
            if ($admin['role_id'] == 2) {
                $param['status'] = 1;
            } elseif ($admin['role_id'] == 6) {
                $param['status'] = 4;
            }
            $res = $p->auditor_not_pass($param);
            if ($res !== true) {
                switch ($res) {
                    case 1:
                        $msg = '用户不存在';
                        break;
                    case 2 || 3:
                        $msg = '审核失败';
                        break;
                }
                return ['status' => $res, 'info' => $msg];
            }
            $l->addLog(['type' => LogModel::TYPE_RECHARGE_AUDITOR, 'content' => '提现审核不通过，审核的充值ID：' . $param['id']]);
            return ['status' => 0, 'info' => '审核成功'];
        } elseif ($status === 8) {
            // 审核通过
            if ($admin['role_id'] == 2) {
                $res = $p->modifyField('status', 6, ['id' => $param['id']]);
                if (!$res) {
                    return ['status' => 1, 'info' => '审核失败'];
                }
            } elseif ($admin['role_id'] == 6 || $admin['role_id'] == 1) {
                $data           = $p->getModel(['id' => $param['id']]);
                $data['status'] = 8;
                $b              = new BalanceModel();
                $res            = $b->balanceLog(1, $data);
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
            }
            $l->addLog(['type' => LogModel::TYPE_RECHARGE_AUDITOR, 'content' => '提现审核通过，审核的充值ID：' . $param['id']]);
            return ['status' => 0, 'info' => '审核成功'];
        }
    }

    /**
     * 收支明细
     * @Author 贺强
     * @date   2018-08-27
     * @param  BalanceModel $b BalanceModel 实例
     */
    public function detail(BalanceModel $b)
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
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
        // 分页参数
        $page     = intval($this->request->get('page', 1));
        $pagesize = intval($this->request->get('pagesize', config('PAGESIZE')));
        $list     = $b->getList($where, true, "$page,$pagesize", 'addtime desc');
        if ($list) {
            $business = $this->getUsers(['role_id' => 3]);
            $business = array_column($business, 'nickname', 'id');
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
    public function putforward(PutforwardModel $p)
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        if ($this->request->isAjax()) {
            $param             = $this->request->post();
            $param['uid']      = $admin['id'];
            $param['nickname'] = $admin['nickname'];
            $res               = $p->putforward($param);
            if ($res !== true) {
                switch ($res) {
                    case 1:
                        $msg = '提现金额不足';
                        break;
                    case 2:
                        $msg = '银行卡号或银行卡姓名不能为空';
                        break;
                    case 3:
                        $msg = '银行卡号或银行卡姓名有误';
                        break;
                    case 4 || 5:
                        $msg = '提现失败';
                        break;
                }
                return ['status' => $res, 'info' => $msg];
            }
            return ['status' => 0, 'info' => '提现申请成功，请等等审核'];
        } else {
            return $this->fetch('putforward');
        }
    }

    /**
     * 提现记录
     * @Author 贺强
     * @date   2018-08-30
     */
    public function putforwardlog(PutforwardModel $p)
    {
        // 判断是否有权限访问或操作
        $admin   = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        $where   = [];
        $balance = 0;
        $a       = new AdminModel();
        if ($admin['role_id'] === 3) {
            // 若登录角色是商家，则只能看到他自己的提现记录
            $where['uid']  = $admin['id'];
            $balance_model = $a->getModel(['id' => $admin['id']], 'balance');
            if (!empty($balance_model)) {
                $balance = $balance_model['balance'];
            }
        } elseif ($admin['role_id'] === 2) {
            // 若登录角色是业务员，则只能看到他名下的商家提现记录
            $ids  = '0';
            $sarr = $a->getList(['s_id' => $admin['id']], 'id');
            foreach ($sarr as $sa) {
                $ids .= ",{$sa['id']}";
            }
            $where['uid'] = ['in', $ids];
        } elseif ($admin['role_id'] === 4 || $admin['role_id'] === 5) {
            // 若登录角色是客服主管理或客服，则只能看到他名下的商家提现记录
            $ids  = '0';
            $sarr = $a->getList(['k_id' => $admin['id']], 'id');
            foreach ($sarr as $sa) {
                $ids .= ",{$sa['id']}";
            }
            $where['uid'] = ['in', $ids];
        }
        $keyword = $this->request->post('keyword');
        if (!empty($keyword)) {
            $where['nickname'] = ['like', "%{$keyword}%"];
        }
        // 分页参数
        $page     = intval($this->request->get('page', 1));
        $pagesize = intval($this->request->get('pagesize', config('PAGESIZE')));
        $list     = $p->getList($where, true, "$page,$pagesize", "`status`,addtime desc");
        if ($list) {
            foreach ($list as &$item) {
                if (!empty($item['addtime'])) {
                    $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                } else {
                    $item['addtime'] = '';
                }
                // 商家提现状态逻辑
                if ($item['status'] === 8) {
                    $item['status_txt'] = '已审核';
                } else {
                    if ($admin['role_id'] === 3) {
                        // 如果登录角色是商家或客服主管或客服则只能看到提现状态
                        if ($item['status'] === 0 || $item['status'] === 6) {
                            $item['status_txt'] = '待审核';
                        } elseif ($item['status'] === 1 || $item['status'] === 4) {
                            $item['status_txt'] = '审核不通过';
                        }
                    } elseif ($admin['role_id'] === 2) {
                        // 如果登录角色是业务员，状态为 0 时可审核
                        if ($item['status'] === 1) {
                            $item['status_txt'] = '审核不通过';
                        } elseif ($item['status'] === 4) {
                            $item['status_txt'] = '财务审核不通过';
                        } elseif ($item['status'] === 6) {
                            $item['status_txt'] = '待财务审核';
                        }
                    } elseif ($admin['role_id'] === 6) {
                        // 如果登录角色是财务，状态为 6 时可审核
                        if ($item['status'] === 0) {
                            $item['status_txt'] = '待业务审核';
                        } elseif ($item['status'] === 1) {
                            $item['status_txt'] = '业务审核不通过';
                        } elseif ($item['status'] === 4) {
                            $item['status_txt'] = '审核不通过';
                        }
                    } elseif ($admin['role_id'] === 1) {
                        // 如果登录角色是超管，状态为 0 或 6 时可审核
                        if ($item['status'] === 1) {
                            $item['status_txt'] = '业务审核不通过';
                        } elseif ($item['status'] === 4) {
                            $item['status_txt'] = '财务审核不通过';
                        }
                    }
                }
            }
        }
        // print_r($list);exit;
        $count = $p->getCount($where);
        $pages = ceil($count / $pagesize);
        return $this->fetch('putforwardlog', ['list' => $list, 'pages' => $pages, 'role_id' => $admin['role_id'], 'balance' => $balance]);
    }

    /**
     * 站点补款
     * @Author 贺强
     * @date   2018-09-05
     * @param  PaylogModel $p PaylogModel 实例
     */
    public function supplement(PaylogModel $p)
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        $param = $this->request->post();
        $where = ['type' => 1];
        // 分页参数
        $page     = intval($this->request->get('page', 1));
        $pagesize = intval($this->request->get('pagesize', config('PAGESIZE')));
        $list     = $p->getList($where, 'id,addtime,before_money,after_money,money,remark,type', "$page,$pagesize", 'addtime desc');
        $pages    = 0;
        if ($list) {
            $count = $p->getCount($where);
            $pages = ceil($count / $pages);
            foreach ($list as &$item) {
                if (!empty($item['addtime'])) {
                    $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                }
            }
        }
        return $this->fetch('supplement', ['list' => $list, 'pages' => $pages]);
    }

    /**
     * 佣金审核
     * @Author 贺强
     * @date   2018-09-05
     * @param  PaylogModel $p PaylogModel 实例
     */
    public function commision(PaylogModel $p)
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        $param = $this->request->post();
        $where = ['type' => 2];
        if (!empty($param['start'])) {
            $start            = strtotime($param['start']);
            $where['addtime'] = ['>=', $start];
            if (!empty($param['end'])) {
                $where['addtime'] = ['between', [$start, strtotime($param['end'])]];
            } else {
                $param['end'] = '';
            }
        } elseif (!empty($param['end'])) {
            $where['addtime'] = ['<=', strtotime($param['end'])];
        } else {
            $param['start'] = '';
            $param['end']   = '';
        }
        $a       = new AdminModel();
        $where_a = [];
        if (!empty($param['name'])) {
            $where_a['realname|nickname'] = ['like', "'%{$param['name']}%'"];
        } else {
            $param['name'] = '';
        }
        if (!empty($param['wangwang'])) {
            $where_a['wangwang'] = ['like', "'%{$param['wangwang']}%'"];
        } else {
            $param['wangwang'] = '';
        }
        if (!empty($where_a)) {
            $where_a['is_delete'] = 0;
            $kf_ids               = '0';
            $kf_list              = $a->getList($where_a, 'id');
            foreach ($kf_list as $kf) {
                $kf_ids .= ',' . $kf['id'];
            }
            $where['uid'] = ['in', $kf_ids];
        }
        if (isset($param['status']) && $param['status'] !== '') {
            $where['status'] = $param['status'];
        } else {
            $param['status'] = '';
        }
        // 分页参数
        $page     = intval($this->request->get('page', 1));
        $pagesize = intval($this->request->get('pagesize', config('PAGESIZE')));
        $list     = $p->getList($where, 'id,addtime,uid,money,balance,status,type', "$page,$pagesize", 'status,addtime desc');
        $pages    = 0;
        if ($list) {
            $count  = $p->getCount($where);
            $pages  = ceil($count / $pagesize);
            $a      = new AdminModel();
            $kf_arr = $a->getList(['role_id' => 4], 'id,realname');
            $kf_arr = array_column($kf_arr, 'realname', 'id');
            foreach ($list as &$item) {
                if (!empty($item['addtime'])) {
                    $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                }
                if (!empty($kf_arr[$item['uid']])) {
                    $item['kf_name'] = $kf_arr[$item['uid']];
                } else {
                    $item['kf_name'] = '';
                }
                switch ($item['status']) {
                    case 0:
                        $item['status_txt'] = '未审核';
                        break;
                    case 1:
                        $item['status_txt'] = '审核不通过';
                        break;
                    case 8:
                        $item['status_txt'] = '已审核';
                        break;
                }
            }
        }
        return $this->fetch('commision', ['list' => $list, 'pages' => $pages, 'admin' => $admin, 'param' => $param]);
    }

    /**
     * 审核佣金申请
     * @Author 贺强
     * @date   2018-09-05
     * @param  PaylogModel $p PaylogModel 实例
     */
    public function auditorp(PaylogModel $p)
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        if ($this->request->isAjax()) {
            $id     = $this->request->post('id');
            $status = $this->request->post('status');
            $reason = $this->request->post('reason', '');
            if (empty($id) || empty($status)) {
                return ['status' => 2, 'info' => '非法参数'];
            }
            if ($status == 8) {
                // 调用 model 层方法使用事务确保金额一致性
                $res = $p->auditor($id, $status);
                if ($res !== true) {
                    switch ($res) {
                        case 1:
                            $msg = '申请不存在';
                            break;
                        case 2 || 3 || 4:
                            $msg = '更新失败';
                            break;
                    }
                    return ['status' => $res, 'info' => $msg];
                }
            } else {
                $res = $p->modifyField(['status' => $status, 'reason' => $reason], ['id' => $id]);
                if (!$res) {
                    return ['status' => 5, 'info' => '审核失败'];
                }
                return ['status' => 0, 'info' => '审核成功'];
            }
            return ['status' => 0, 'info' => '审核成功'];
        } else {
            return ['status' => 6, 'info' => '非法操作'];
        }
    }
}
