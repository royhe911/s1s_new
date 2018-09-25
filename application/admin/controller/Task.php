<?php
namespace app\admin\controller;

use app\common\model\AdminModel;
use app\common\model\CategoryModel;
use app\common\model\ShopModel;
use app\common\model\TaskModel;

/**
 * 任务管理
 */
class Task extends \think\Controller
{
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
            # code...
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
     * 任务列表
     * @Author 贺强
     * @Date   2018-09-11
     * @param  TaskModel  $t TaskModel 实例
     */
    public function index(TaskModel $t)
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        $where = [];
        // 分页参数
        $page     = intval($this->request->get('page', 1));
        $pagesize = intval($this->request->get('pagesize', config('PAGESIZE')));
        $list     = $t->getList($where, true, "$page,$pagesize");
        $pages    = 0;
        if ($list) {
            $count = $t->getList($where);
            $pages = ceil($count / $pagesize);
            $sp    = new ShopModel();
            $shops = $sp->getList(['is_delete' => 0], 'id,shop_name');
            $shops = array_column($shops, 'shop_name', 'id');
            $a     = new AdminModel();
            $users = $a->getList(['is_delete' => 0, 'role_id' => 3], 'id,realname');
            $users = array_column($users, 'realname', 'id');
            foreach ($list as &$item) {
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
                    $item['status_txt'] = '待付款';
                } elseif ($item['status'] === 5) {
                    $item['status_txt'] = '待审核';
                } elseif ($item['status'] === 9) {
                    $item['status_txt'] = '审核不通过';
                } elseif ($item['status'] === 15) {
                    $item['status_txt'] = '已审核';
                } elseif ($item['status'] === 18) {
                    $item['status_txt'] = '下架';
                } elseif ($item['status'] === 20) {
                    $item['status_txt'] = '进行中';
                }
            }
        }
        return $this->fetch('index', ['list' => $list, 'pages' => $pages]);
    }
}
