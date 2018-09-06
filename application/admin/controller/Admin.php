<?php
namespace app\admin\controller;

use app\common\model\AdminModel;
use app\common\model\LogModel;
use app\common\model\MenuModel;
use app\common\model\RoleAccessModel;
use think\Session;

/**
 * 后台管理
 */
class Admin extends \think\Controller
{
    /**
     * 首页
     * @Author 贺强
     * @date   2018-08-22
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
     * @Author 贺强
     * @date   2018-08-22
     * @param  integer $role_id 用户角色 ID
     * @return json    返回该用户拥有权限菜单
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
     * @Author 贺强
     * @date   2018-08-22
     */
    public function index_v1()
    {
        $admin = $this->is_login();
        return view('index_v1');
    }

    /**
     * 登录页
     * @Author 贺强
     * @date   2018-08-22
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
     * @Author 贺强
     * @date   2018-08-22
     * @param  AdminModel $a AdminModel 实例
     * @return bool          返回登录是否成功
     */
    public function do_login(AdminModel $a)
    {
        $param = $this->request->post();
        if (empty($param['username']) || empty($param['pwd'])) {
            return ['status' => 3, 'info' => '用户名或密码不能为空'];
        }
        $admin = $a->getModel(['username|mobile' => $param['username']]);
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
        $r        = new RoleAccessModel();
        $role_arr = $r->getList(['role_id' => $admin['role_id']], 'menu_id');
        $roles    = [];
        foreach ($role_arr as $role) {
            $roles[] = $role['menu_id'];
        }
        $admin['roles'] = $roles;
        session('admin', $admin);
        // return $this->fetch('login', ['admin'=>$admin, 'status'=>'success']);
        return ['status' => 0, 'info' => '登录成功'];
    }

    /**
     * 添加用户账号
     * @Author 贺强
     * @date   2018-08-22
     * @param  AdminModel $a AdminModel 实例
     */
    public function add(AdminModel $a)
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['uid']) || empty($param['pwd']) || empty($param['role_id'])) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            $has = $a->getCount(['is_delete' => 0, 'uid' => $param['uid']]);
            if (!empty($has)) {
                return ['status' => 2, 'info' => '该账号已存在'];
            }
            // 如果添加的用户不是商家，则不需要业务员
            if ($param['role_id'] != 3) {
                unset($param['s_id']);
            }
            // 如果添加的用户不是客服，则不需要客服主管
            if ($param['role_id'] != 5) {
                unset($param['k_id']);
            }
            $salt             = get_random_str(); // 生成密码盐
            $param['salt']    = $salt;
            $param['pwd']     = get_password($param['pwd'], $salt);
            $param['addtime'] = time();
            $param['status']  = 8;
            $res              = $a->add($param);
            $l                = new LogModel();
            $l->addLog(['type' => LogModel::TYPE_ADD_USER, 'content' => '添加用户，添加的用户：' . $param['uid']]);
            if (!$res) {
                return ['status' => 4, 'info' => '添加失败'];
            }
            return ['status' => 0, 'info' => '添加成功'];
        } else {
            $where_role = ['id' => ['<>', 1]];
            // 根据登录用户角色判断该用户能添加哪些角色的用户
            if ($admin['role_id'] !== 1) {
                $where_role = ['id' => $admin['role_id']];
            }
            $roles     = $this->getRoles($where_role);
            $salesman  = $this->getUsers(['role_id' => 2]);
            $executive = $this->getUsers(['role_id' => 4]);
            $time      = time();
            return $this->fetch('add', ['roles' => $roles, 'salesman' => $salesman, 'executive' => $executive, 'time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time)]);
        }
    }

    /**
     * 设置用户
     * @Author 贺强
     * @date   2018-08-22
     * @param  AdminModel $a AdminModel 实例
     */
    public function operation(AdminModel $a)
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
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
            $data  = ['type' => LogModel::TYPE_DELETE_USER, 'content' => '删除用户，被删除的用户ID：' . $ids];
        } elseif ($type === 'disable' || $type === 'disableAll') {
            $field = 'status';
            $value = 6;
            $msg   = '禁用';
            $data  = ['type' => LogModel::TYPE_DISABLE_USER, 'content' => '禁用用户，被禁用的用户ID：' . $ids];
        } elseif ($type == 'enable' || $type == 'enableAll') {
            $field = 'status';
            $value = 8;
            $msg   = '启用';
            $data  = ['type' => LogModel::TYPE_ENABLE_USER, 'content' => '启用用户，被启用的用户ID：' . $ids];
        } else {
            return ['status' => 2, 'info' => '非法操作'];
        }
        $res = $a->modifyField($field, $value, ['id' => ['in', $ids]]);
        $l   = new LogModel();
        $l->addLog($data);
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
     * @Author 贺强
     * @date   2018-08-22
     * @param  AdminModel $a AdminModel 实例
     */
    public function edit(AdminModel $a)
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        $role_id = $admin['role_id'];
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            $id    = $admin['id'];
            if ($role_id === 1) {
                $id = $param['id'];
            } elseif ($admin['uid'] !== $param['uid']) {
                return ['status' => 1, 'info' => '非法操作，只能修改自己的账号'];
            }
            unset($param['uid']);
            if ($role_id != 1) {
                unset($param['role_id']);
            }
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
            $l = new LogModel();
            $l->addLog(['type' => LogModel::TYPE_EDIT_USER, 'content' => '修改用户，被修改的用户ID：' . $id]);
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
            $role_w = null;
            if ($role_id != 1) {
                $role_w = ['id' => ['<>', 1]];
            }
            $roles    = $this->getRoles($role_w);
            $salesman = $this->getUsers(['role_id' => 2]);
            $executive = $this->getUsers(['role_id' => 4]);
            $time     = time();
            return $this->fetch('edit', ['admin' => $user, 'role_id' => $admin['role_id'], 'roles' => $roles, 'executive' => $executive, 'salesman' => $salesman, 'time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time)]);
        }
    }

    /**
     * 用户列表
     * @Author 贺强
     * @date   2018-08-22
     * @param  AdminModel $a AdminModel 实例
     */
    public function lists(AdminModel $a)
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
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
        // 分页参数
        $page     = intval($this->request->get('page', 1));
        $pagesize = intval($this->request->get('pagesize', config('PAGESIZE')));
        $list     = $a->getList($where, true, "$page,$pagesize", 'logintime desc');
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
        $count = $a->getCount($where);
        $pages = ceil($count / $pagesize);
        $roles = $this->getRoles();
        return $this->fetch('list', ['list' => $list, 'roles' => $roles, 'keyword' => $keyword, 'type' => $type, 'pages' => $pages]);
    }

}
