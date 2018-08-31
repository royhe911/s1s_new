<?php
namespace app\admin\controller;

use app\admin\model\LogModel;
use app\admin\model\MenuModel;
use app\admin\model\RoleAccessModel;
use app\admin\model\RoleModel;

class Menu extends \think\Controller
{
    /**
     * 添加菜单
     * @Author 贺强
     * @date   2018-08-23
     * @param  MenuModel  $m MenuModel 实例
     */
    public function add(MenuModel $m)
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['identity']) || empty($param['name'])) {
                return ['status' => 3, 'info' => '非法操作'];
            }
            $menu = $m->getModel(['is_delete' => 0, 'identity' => $param['identity']]);
            if (!empty($menu)) {
                return ['status' => 1, 'info' => '菜单标识重复，请重新填写'];
            }
            if (empty($param['orders'])) {
                $param['orders'] = 99;
            }
            $res = $m->add($param);
            $l   = new LogModel();
            $l->addLog(['type' => LogModel::TYPE_ADD_MENU, 'content' => '添加菜单，添加的菜单标识：' . $param['identity']]);
            if ($res) {
                return ['status' => 0, 'info' => '添加成功'];
            }
            return ['status' => 2, 'info' => '添加失败'];
        } else {
            $menu = $m->getList(['pid' => 0, 'is_delete' => 0]);
            return $this->fetch('add', ['menu' => $menu]);
        }
    }

    /**
     * 删除菜单
     * @Author 贺强
     * @date   2018-08-23
     * @param  MenuModel  $m MenuModel 实例
     */
    public function del(MenuModel $m)
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        $id    = $this->request->post('id');
        $res   = $m->modifyField('is_delete', 1, ['id' => $id]);
        $l     = new LogModel();
        $l->addLog(['type' => LogModel::TYPE_DELETE_MENU, 'content' => '删除菜单']);
        if ($res) {
            return ['status' => 0, 'info' => '删除成功'];
        }
        return ['status' => 4, 'info' => '删除失败'];
    }

    /**
     * 编辑菜单
     * @Author 贺强
     * @date   2018-08-23
     * @param  MenuModel  $m MenuModel 实例
     */
    public function edit(MenuModel $m)
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id']) || empty($param['identity']) || empty($param['name'])) {
                return ['status' => 3, 'info' => '非法操作'];
            }
            $res = $m->modify($param, ['id' => $param['id']]);
            $l   = new LogModel();
            $l->addLog(['type' => LogModel::TYPE_EDIT_MENU, 'content' => '编辑菜单']);
            if ($res !== false) {
                $m->modifyField('orders', $param['orders'], ['pid' => $param['id']]);
                return ['status' => 0, 'info' => '修改成功'];
            } else {
                return ['status' => 4, 'info' => '修改失败'];
            }
        } else {
            $menu = null;
            $id   = $this->request->get('id');
            if (!empty($id) && is_numeric($id)) {
                $menu = $m->getModel(['id' => $id]);
            }
            return $this->fetch('edit', ['menu' => $menu]);
        }
    }

    /**
     * 菜单列表
     * @Author 贺强
     * @date   2018-08-23
     */
    public function lists()
    {
        // 判断是否有权限访问或操作
        $admin    = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        $r        = new RoleModel();
        $m        = new MenuModel();
        $roles    = $r->getList();
        $where    = ['is_delete' => 0];
        $page     = intval($this->request->get('page', 1));
        $pagesize = intval($this->request->get('pagesize', config('PAGESIZE')));
        $list     = $m->getList($where, true, "$page,$pagesize", 'orders');
        // var_dump($list);exit;
        $count = $m->getCount($where);
        $pages = ceil($count / $pagesize);
        return $this->fetch('list', ['list' => $list, 'roles' => $roles, 'pages' => $pages]);
    }

    /**
     * 菜单权限管理
     * @Author 贺强
     * @date   2018-08-23
     */
    public function power()
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        $ra    = new RoleAccessModel();
        if ($this->request->isAjax()) {
            $param    = $this->request->post();
            $res      = $ra->updateRolePower($param);
            $l        = new LogModel();
            $menu_ids = '';
            if (!empty($param['menu_ids'])) {
                $menu_ids = implode(',', $param['menu_ids']);
            }
            $l->addLog(['type' => LogModel::TYPE_POWER, 'content' => '分配菜单权限，分配的角色：' . $param['role_id'] . '，分配的权限：' . $menu_ids]);
            if ($res !== true) {
                switch ($res) {
                    case 1:
                        $msg = '非法参数';
                        break;
                    case 2:
                        $msg = '系统异常';
                        break;
                }
                return ['status' => $res, 'info' => $msg];
            }
            return ['status' => 0, 'info' => '修改成功'];
        } else {
            $r       = new RoleModel();
            $m       = new MenuModel();
            $roles   = $r->getList(['id' => ['<>', 1]]);
            $role_id = $this->request->get('role_id');
            $list    = $m->getList(['is_delete' => 0], true, null, 'orders');
            $has     = $ra->getList(['role_id' => $role_id], 'menu_id');
            $has     = array_column($has, 'menu_id');
            return $this->fetch('power', ['list' => $list, 'roles' => $roles, 'has' => $has, 'role_id' => $role_id]);
        }
    }
}
