<?php
namespace app\admin\controller;

use app\admin\model\MenuModel;
use app\admin\model\RoleAccessModel;
use app\admin\model\RoleModel;
use think\Config;

class Menu extends \think\Controller
{
    /**
     * 添加菜单
     * @param MenuModel $m MenuModel 实例
     */
    public function add(MenuModel $m)
    {
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
            if ($res) {
                return ['status' => 0, 'info' => '添加成功'];
            }
            return ['status' => 2, 'info' => '添加失败'];
        } else {
            $menu = $m->getList(['pid' => 0]);
            return $this->fetch('add', ['menu' => $menu]);
        }
    }

    /**
     * 删除菜单
     * @param  MenuModel $m MenuModel 实例
     */
    public function del(MenuModel $m)
    {
        $id  = $this->request->post('id');
        $res = $m->modifyField('is_delete', 1, ['id' => $id]);
        if ($res) {
            return ['status' => 0, 'info' => '删除成功'];
        }
        return ['status' => 4, 'info' => '删除失败'];
    }

    /**
     * 编辑菜单
     * @param  MenuModel $m MenuModel 实例
     */
    public function edit(MenuModel $m)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id']) || empty($param['identity']) || empty($param['name'])) {
                return ['status' => 3, 'info' => '非法操作'];
            }
            $res = $m->modify($param, ['id' => $param['id']]);
            if ($res !== false) {
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
     * @return array        返回菜单数组
     */
    public function lists()
    {
        $r        = new RoleModel();
        $m        = new MenuModel();
        $roles    = $r->getList();
        $where    = ['is_delete' => 0];
        $page     = intval($this->request->get('page', 1));
        $pagesize = config('PAGESIZE');
        $start    = $page - 1;
        $list     = $m->getList($where, true, "$start,$pagesize", 'orders');
        // var_dump($list);exit;
        $count = $m->getCount($where);
        $pages = ceil($count / $pagesize);
        return $this->fetch('list', ['list' => $list, 'roles' => $roles, 'pages' => $pages]);
    }

    /**
     * 菜单权限管理
     */
    public function power()
    {
        $ra = new RoleAccessModel();
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            $res   = $ra->updateRolePower($param);
            if ($res === 1) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            if ($res === 2) {
                return ['status' => 2, 'info' => '系统异常'];
            }
            if ($res === 10) {
                return ['status' => 0, 'info' => '修改成功'];
            }
        } else {
            $r       = new RoleModel();
            $m       = new MenuModel();
            $roles   = $r->getList(['id' => ['<>', 1]]);
            $role_id = $this->request->get('role_id');
            $list    = $m->getList(['is_delete' => 0]);
            $has     = $ra->getList(['role_id' => $role_id], 'menu_id');
            $has     = array_column($has, 'menu_id');
            return $this->fetch('power', ['list' => $list, 'roles' => $roles, 'has' => $has, 'role_id' => $role_id]);
        }
    }
}
