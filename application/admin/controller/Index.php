<?php
namespace app\admin\controller;

use app\admin\model\AdminModel;
use app\admin\model\MenuModel;
use app\admin\model\RoleAccessModel;
use think\Session;

class Index extends \think\Controller
{
    /**
     * 首页
     */
    public function index()
    {
        $admin = Session::get('admin');
        if ($admin) {
            $menu = $this->get_menus($admin['role_id']);
            return $this->fetch('index', ['admin' => $admin, 'menu' => $menu]);
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
        $menu = new MenuModel();
        $list = $menu->getList($where, true, null, 'orders');
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
        $pwd = substr(md5($param['pwd'] . $admin['salt']), 5, 27);
        if ($pwd !== $admin['pwd']) {
            return ['status' => 1, 'info' => '用户名或密码错误'];
        }
        $a->modifyField('logintime', time(), ['id' => $admin['id']]);
        session('admin', $admin);
        // return $this->fetch('login', ['admin'=>$admin, 'status'=>'success']);
        return ['status' => 0, 'info' => '登录成功'];
    }

    /**
     * 添加管理员账号
     * @param  AdminModel $a AdminModel 实例
     * @return bool          返回添加是否成功
     */
    public function add(AdminModel $a)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            $salt  = get_random_str(); // 生成密码盐
            var_dump($salt);exit;
        } else {
            return $this->fetch('add');
        }
    }

    /**
     * 删除用户
     * @param  AdminModel $a AdminModel 实例
     * @return bool          返回是否删除成功
     */
    public function del(AdminModel $a)
    {
        $ids = $this->request->post('ids');
        if (empty($ids) || !preg_match('/^0[\,\d+]+$/', $ids)) {
            return ['status' => 3, 'info' => '非法参数'];
        }
        $res = $a->modifyField('is_delete', 1, ['id' => ['in', $ids]]);
        if ($res) {
            return ['status' => 0, 'info' => '删除成功'];
        } else {
            return ['status' => 4, 'info' => '删除失败'];
        }
    }

    /**
     * 管理员列表
     * @param  AdminModel $a AdminModel 实例
     * @return page          返回列表页
     */
    public function lists(AdminModel $a)
    {
        $where = ['is_delete' => 0];
        if ($this->request->isPost()) {
            $param = $this->request->post();
            // print_r($param);exit;
            if (!empty($param['keyword'])) {
                $where['mobile|email|qq|wx'] = ['like', "%{$param['keyword']}%"];
            }
            if (!empty($param['type']) && is_numeric($param['type'])) {
                $where['type'] = $param['type'];
            }
        }
        $list = $a->getList($where);
        // print_r($list);exit;
        foreach ($list as &$item) {
            $item['status_txt'] = get_user_status($item['status']);
            $item['logintime']  = date('Y-m-d H:i:s', $item['logintime']);
            $item['addtime']    = date('Y-m-d H:i:s', $item['addtime']);
        }
        return $this->fetch('list', ['list' => $list]);
    }

}
