<?php
namespace app\admin\controller;

use app\admin\model\AdminModel;
use app\admin\model\MenuModel;
use app\admin\model\RoleAccessModel;
use think\Session;

/**
 * 后台管理
 */
class Index extends \think\Controller
{
    /**
     * 首页
     */
    public function index()
    {
        $admin = Session::get('admin');
        if ($admin) {
            $menu     = $this->get_menus($admin['role_id']);
            $m        = new MenuModel();
            $where    = ['is_delete' => 0];
            $list     = $m->getList($where, 'identity,`name`');
            $list     = array_column($list, 'name', 'identity');
            $menu_str = json_encode($list);
            return $this->fetch('index', ['admin' => $admin, 'menu' => $menu, 'menu_str' => $menu_str]);
        }
        return $this->fetch('login', ['info' => '']);
    }

    /**
     * 根据用户角色 ID 获取该用户的权限
     * @param  integer $role_id 用户角色 ID
     * @return json             返回该用户拥有权限菜单
     */
    private function get_menus($role_id = 0)
    {
        $where = ['is_delete' => 0, 'is_hide' => 0];
        if ($role_id != 1) {
            $role      = new RoleAccessModel();
            $role_list = $role->getList(['role_id' => $role_id], 'menu_id');
            $menu_ids  = '0';
            foreach ($role_list as $ritem) {
                $menu_ids .= ",{$ritem['menu_id']}";
            }
            $where['id'] = ['in', $menu_ids];
        }
        $m    = new MenuModel();
        $list = $m->getList($where, true, null, 'orders');
        $arr  = [];
        foreach ($list as $item) {
            if ($item['pid'] === 0) {
                $arr[] = $item;
            } else {
                foreach ($arr as &$item2) {
                    if ($item['pid'] == $item2['id']) {
                        $item2['children'][] = $item;
                    }
                }
            }
        }
        return $arr;
    }

    /**
     * 后台欢迎页
     */
    public function index_v1()
    {
        $admin = $this->is_login();
        return view('index_v1');
    }

    /**
     * 登录页
     */
    public function login()
    {
        if ($this->request->get('action') === 'logout') {
            session('admin', null);
        }
        // return view('login', ['info'=>'']);
        return view('login');
    }

    /**
     * 登录操作
     * @param  AdminModel $a AdminModel 实例
     * @return bool          返回登录是否成功
     */
    public function do_login(AdminModel $a)
    {
        $param = $this->request->post();
        if (empty($param['uid']) || empty($param['pwd'])) {
            return ['status' => 3, 'info' => '用户名或密码不能为空'];
        }
        $admin = $a->getModel(['uid' => $param['uid']]);
        if (empty($admin)) {
            return ['status' => 2, 'info' => '用户名或密码错误'];
        }
        $pwd = get_password($param['pwd'], $admin['salt']);
        if ($pwd !== $admin['pwd']) {
            return ['status' => 1, 'info' => '用户名或密码错误'];
        }
        if ($admin['status'] !== 8) {
            return ['status' => 3, 'info' => '账号审核不通过或被禁用，请联系管理员'];
        }
        $a->modifyField('logintime', time(), ['id' => $admin['id']]);
        session('admin', $admin);
        // return $this->fetch('login', ['admin'=>$admin, 'status'=>'success']);
        return ['status' => 0, 'info' => '登录成功'];
    }

    /**
     * 添加用户账号
     * @param  AdminModel $a AdminModel 实例
     * @return bool          返回添加是否成功
     */
    public function add(AdminModel $a)
    {
        $admin = $this->is_login();
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['uid']) || empty($param['pwd']) || empty($param['role_id'])) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            $has = $a->getCount(['is_delete' => 0, 'uid' => $param['uid']]);
            if (!empty($has)) {
                return ['status' => 2, 'info' => '该账号已存在'];
            }
            $salt             = get_random_str(); // 生成密码盐
            $param['salt']    = $salt;
            $param['pwd']     = get_password($param['pwd'], $salt);
            $param['addtime'] = time();
            $param['status']  = 8;
            $res              = $a->add($param);
            if (!$res) {
                return ['status' => 4, 'info' => '添加失败'];
            }
            return ['status' => 0, 'info' => '添加成功'];
        } else {
            $roles    = $this->getRoles();
            $salesman = $this->getSalesman();
            $time     = time();
            return $this->fetch('add', ['roles' => $roles, 'salesman' => $salesman, 'time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time)]);
        }
    }

    /**
     * 删除用户
     * @param  AdminModel $a AdminModel 实例
     */
    public function operation(AdminModel $a)
    {
        $admin = $this->is_login();
        $ids   = $this->request->post('ids');
        if (empty($ids) || !preg_match('/^0[\,\d+]+$/', $ids)) {
            return ['status' => 3, 'info' => '非法参数'];
        }
        $type = $this->request->post('type');
        if (empty($type)) {
            return ['status' => 1, 'info' => '非法操作'];
        }
        if ($type === 'del' || $type === 'delAll') {
            $field = 'is_delete';
            $value = 1;
            $msg   = '删除';
        } elseif ($type === 'disable' || $type === 'disableAll') {
            $field = 'status';
            $value = 6;
            $msg   = '禁用';
        } elseif ($type == 'enable' || $type == 'enableAll') {
            $field = 'status';
            $value = 8;
            $msg   = '启用';
        } else {
            return ['status' => 2, 'info' => '非法操作'];
        }
        $res = $a->modifyField($field, $value, ['id' => ['in', $ids]]);
        if ($res) {
            return ['status' => 0, 'info' => $msg . '成功'];
        } elseif ($res === false) {
            return ['status' => 4, 'info' => $msg . '失败'];
        } else {
            return ['status' => 5, 'info' => '该账号已' . $msg];
        }
    }

    /**
     * 修改用户
     * @param  AdminModel $a AdminModel 实例
     */
    public function edit(AdminModel $a)
    {
        $admin = $this->is_login();
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            $id    = $admin['id'];
            if ($admin['role_id'] === 1) {
                $id = $param['id'];
            } elseif ($admin['uid'] !== $param['uid']) {
                return ['status' => 1, 'info' => '非法操作，只能修改自己的账号'];
            }
            unset($param['uid']);
            if (!empty($param['pwd'])) {
                $salt          = get_random_str(); // 生成密码盐
                $param['salt'] = $salt;
                $param['pwd']  = get_password($param['pwd'], $salt);
            } else {
                unset($param['pwd']);
            }
            if (empty($param['avatar'])) {
                unset($param['avatar']);
            }
            $param['updatetime'] = time();
            $res                 = $a->modify($param, ['id' => $id]);
            if (!$res) {
                return ['status' => 4, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        } else {
            $id = $this->request->get('id');
            if (empty($id) || !is_numeric($id)) {
                $id = $admin['id'];
            }
            $user = $a->getModel(['id' => $id]);
            if (empty($user)) {
                $this->error('用户不存在');
            }
            $roles    = $this->getRoles();
            $salesman = $this->getSalesman();
            $time     = time();
            return $this->fetch('edit', ['admin' => $user, 'role_id' => $admin['role_id'], 'roles' => $roles, 'salesman' => $salesman, 'time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time)]);
        }
    }

    /**
     * 管理员列表
     * @param  AdminModel $a AdminModel 实例
     * @return page          返回列表页
     */
    public function lists(AdminModel $a)
    {
        $admin   = $this->is_login();
        $where   = ['is_delete' => 0];
        $keyword = '';
        $type    = 0;
        if ($this->request->isPost()) {
            $param = $this->request->post();
            // print_r($param);exit;
            if (!empty($param['keyword'])) {
                $where['mobile|email|qq|wx'] = ['like', "%{$param['keyword']}%"];
                $keyword                     = $param['keyword'];
            }
            if (!empty($param['type']) && is_numeric($param['type'])) {
                $where['role_id'] = $type = $param['type'];
            }
        }
        $list = $a->getList($where);
        // print_r($list);exit;
        foreach ($list as &$item) {
            $item['status_txt'] = get_user_status($item['status']);
            if (!empty($item['logintime'])) {
                $item['logintime'] = date('Y-m-d H:i:s', $item['logintime']);
            }
            if (!empty($item['addtime'])) {
                $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
            }
        }
        $roles = $this->getRoles();
        return $this->fetch('list', ['list' => $list, 'roles' => $roles, 'keyword' => $keyword, 'type' => $type]);
    }

}
