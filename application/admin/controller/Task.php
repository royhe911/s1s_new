<?php
namespace app\admin\controller;

use app\common\model\AdminModel;
use app\common\model\CategoryModel;
use app\common\model\ChargeModel;
use app\common\model\LogModel;
use app\common\model\ProductModel;
use app\common\model\ShopModel;
use app\common\model\TaskModel;

/**
 * 任务管理
 */
class Task extends \think\Controller
{
    /**
     * 服务费设置
     * @Author 贺强
     * @Date   2018-09-27
     */
    public function charge(ChargeModel $c)
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        if ($this->request->isAjax()) {
            $param   = $this->request->post();
            $labels  = $param['labels'];
            $charges = $param['charges'];
            foreach ($labels as $key => $label) {
                $c->modifyField('charge', $charges[$key], ['label' => $label]);
            }
            return ['status' => 0, 'info' => '设置成功'];
        } else {
            $charges = $c->getList(['is_delete' => 0]);
            return $this->fetch('charge', ['charges' => $charges]);
        }
    }

    /**
     * 发布任务
     * @Author 贺强
     * @Date   2018-09-12
     * @param  TaskModel       $t  TaskModel 实例
     */
    public function add(TaskModel $t)
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        if ($this->request->isAjax()) {
            $param                = $this->request->post();
            $param['uid']         = $admin['id'];
            $param['business_id'] = $admin['s_id'];
            $param['wangwang']    = '';
            $res                  = $t->AddTask($param);
            if ($res !== true) {
                return ['status' => $res, 'info' => '发布失败'];
            }
            return ['status' => 0, 'info' => '发布成功'];
        } else {
            $s         = new ShopModel();
            $shops     = $s->getList(['is_delete' => 0, 'uid' => $admin['id']], 'id,shop_name');
            $c         = new CategoryModel();
            $categorys = $c->getList();
            $time      = time();
            return $this->fetch('add', ['shops' => $shops, 'categorys' => $categorys, 'time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time)]);
        }
    }

    /**
     * 结束任务
     * @Author 贺强
     * @Date   2018-10-04
     */
    public function overtask(TaskModel $t)
    {
        $admin = $this->is_login();
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id']) || empty($param['wangwang']) || empty($param['tb_order']) || empty($param['actual_price']) || empty($param['actual_cost'])) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            $has = $t->getModel(['tb_order' => $param['tb_order']]);
            if ($has) {
                return ['status' => 2, 'info' => '订单号已存在'];
            }
            $param['status'] = 44;
            $res             = $t->modify($param, ['id' => $param['id']]);
            if (!$res) {
                return ['status' => 4, 'info' => '操作失败'];
            }
            return ['status' => 0, 'info' => '操作成功'];
        } else {
            $id = $this->request->get('id');
            return $this->fetch('over', ['taskid' => $id]);
        }
    }

    /**
     * 结束任务
     * @Author 贺强
     * @Date   2018-09-27
     */
    public function opertask(TaskModel $t)
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        if ($this->request->isAjax()) {
            $id     = $this->request->post('id');
            $status = intval($this->request->post('status'));
            if ($id && $status) {
                $data = ['status' => $status, 'finish_time' => time()];
                if ($status === 4) {
                    $content            = "审核任务不通过";
                    $abn_reason         = $this->request->post('abn_reason');
                    $data['abn_reason'] = $abn_reason;
                } elseif ($status === 15) {
                    $content      = "领取任务";
                    $data['k_id'] = $admin['id'];
                } elseif ($status === 8) {
                    $content      = "审核任务通过或退领任务";
                    $data['k_id'] = 0;
                } elseif ($status === 44) {
                    $content      = "完成任务";
                    $actual_price = $this->request->post('actual_price');
                    if (preg_match('/^\d+[\.\d{1,2}]?$/', $actual_price)) {
                        $cost                 = $t->getCost($actual_price);
                        $data['actual_price'] = $actual_price;
                        $data['actual_cost']  = $cost;
                    }
                }
                $res = $t->modify($data, ['id' => $id]);
                if ($res) {
                    $l = new LogModel();
                    $l->addLog(['type' => LogModel::TYPE_OVERTASK, 'content' => $content]);
                    return ['status' => 0, 'info' => '操作成功'];
                } else {
                    return ['status' => 3, 'info' => '操作失败'];
                }
            } else {
                return ['status' => 2, 'info' => '非法参数'];
            }
        } else {
            return ['status' => 1, 'info' => '非法操作'];
        }
    }

    /**
     * 任务列表
     * @Author 贺强
     * @Date   2018-09-11
     * @param  TaskModel  $t TaskModel 实例
     */
    public function index(TaskModel $t)
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        $where = '1';
        // 分页参数
        $page     = intval($this->request->get('page', 1));
        $pagesize = intval($this->request->get('pagesize', config('PAGESIZE')));
        if ($admin['username'] !== 'admin') {
            if ($admin['role_id'] === 2 || $admin['role_id'] === 4) {
                $a        = new AdminModel();
                $s_id_arr = $a->getList(['s_id|z_id' => $admin['id']], 'id');
                $s_ids    = '0';
                foreach ($s_id_arr as $sia) {
                    $s_ids .= ",{$sia['id']}";
                }
                $where .= " and (k_id in ($s_ids) or `status`=8)";
            } elseif ($admin['role_id'] === 5) {
                $where .= " and (k_id={$admin['id']} or `status`=8)";
            }
        }
        $list  = $t->getList($where, true, "$page,$pagesize", 'status');
        $pages = 0;
        if ($list) {
            $pids  = array_column($list, 'pid');
            $pids  = implode(',', $pids);
            $p     = new ProductModel();
            $pics  = $p->getList(['id' => ['in', $pids]], 'id,pic,url,title,amount,num,reward');
            $pics  = array_column($pics, null, 'id');
            $count = $t->getCount($where);
            $pages = ceil($count / $pagesize);
            $sp    = new ShopModel();
            $shops = $sp->getList(['is_delete' => 0], 'id,shop_name');
            $shops = array_column($shops, 'shop_name', 'id');
            $a     = new AdminModel();
            $users = $a->getList(['is_delete' => 0, 'role_id' => 3], 'id,realname');
            $users = array_column($users, 'realname', 'id');
            foreach ($list as &$item) {
                if (!empty($pics[$item['pid']])) {
                    $pro         = $pics[$item['pid']];
                    $item['pic'] = $pro['pic'];
                    $url         = $pro['url'];
                    if (strpos($url, 'http://') === false || strpos($url, 'https://' === false)) {
                        $url = 'http://' . $url;
                    }
                    $item['url']    = $url;
                    $item['title']  = $pro['title'];
                    $item['amount'] = $pro['amount'];
                    $item['num']    = $pro['num'];
                    $item['reward'] = $pro['reward'];
                } else {
                    $item['pic']    = '';
                    $item['url']    = '';
                    $item['title']  = '';
                    $item['amount'] = 0;
                    $item['num']    = 0;
                    $item['reward'] = '';
                }
                if (!empty($shops[$item['shop_id']])) {
                    $item['shop_name'] = $shops[$item['shop_id']];
                } else {
                    $item['shop_name'] = '';
                }
                if (!empty($users[$item['uid']])) {
                    $item['username'] = $users[$item['uid']];
                } else {
                    $item['username'] = '';
                }
                if ($item['status'] === 0) {
                    $item['status_txt'] = '待审核';
                } elseif ($item['status'] === 4) {
                    $item['status_txt'] = '审核不通过';
                } elseif ($item['status'] === 8) {
                    $item['status_txt'] = '已审核';
                } elseif ($item['status'] === 15) {
                    $item['status_txt'] = '已领取';
                } elseif ($item['status'] === 44) {
                    $item['status_txt'] = '已结束';
                }
            }
        }
        return $this->fetch('index', ['list' => $list, 'pages' => $pages]);
    }

    /**
     * 完成任务统计
     * @Author 贺强
     * @Date   2018-10-01
     * @param  TaskModel  $t TaskModel 实例
     */
    public function statistics(TaskModel $t)
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        // 分页参数
        $page     = intval($this->request->get('page', 1));
        $pagesize = intval($this->request->get('pagesize', config('PAGESIZE')));
        $pages    = 0;
        $field    = ["from_unixtime(finish_time, '%Y年%d月%m日') finish_time", "from_unixtime(finish_time, '%Y%d%m') finish_time2", "sum(actual_price) actual_price", "sum(actual_cost) actual_cost", "count(*) count"];
        $list     = $t->getList(['status' => 44], $field, "$page,$pagesize", 'finish_time desc', "FROM_UNIXTIME(finish_time,'%y%d%m')");
        // print_r($list);exit;
        if ($list) {
            $count  = $t->getCount(['status' => 44], "FROM_UNIXTIME(finish_time,'%y%d%m')");
            $pages  = ceil($count / $pagesize);
            $field2 = ["from_unixtime(finish_time, '%Y%d%m') finish_time2", "count(*) count", "case when actual_price<101 then 100 when actual_price<201 then 200 when actual_price<301 then 300 when actual_price<401 then 400 when actual_price<501 then 500 when actual_price<601 then 600 end actual"];
            $list2  = $t->getList(['status' => 44], $field2, null, null, 'actual');
            foreach ($list as &$item) {
                foreach ($list2 as $item2) {
                    if ($item['finish_time2'] === $item2['finish_time2']) {
                        $item["count{$item2['actual']}"] = $item2['count'];
                    }
                }
            }
        }
        return $this->fetch('statistics', ['list' => $list, 'pages' => $pages]);
    }
}
