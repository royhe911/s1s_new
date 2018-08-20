<?php
namespace app\admin\controller;

use think\Session;

use app\admin\model\AdminModel;

class Index extends \think\Controller
{
    /**
     * 首页
     */
    public function index()
    {
        $admin = Session::get('admin');
        if($admin){
            return $this->fetch('index', ['admin'=>$admin]);
        }
        return $this->fetch('login', ['info'=>'']);
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
        if($this->request->get('action') === 'out'){
            session('admin', null);
        }
        // return view('login', ['info'=>'']);
        return view('login');
    }

    /**
     * 登录操作
     * @param AdminModel $a AdminModel 实例
     * @return 返回登录是否成功
     */
    public function do_login(AdminModel $a)
    {
        $param = $this->request->post();
        if(!empty($param['uid']) && !empty($param['pwd'])){
            $pwd = substr(md5($param['pwd'].config('MD5_ADMIN')), 5, 27);
            $admin = $a->getModel(['uid'=>$param['uid'], 'pwd'=>$pwd]);
            if($admin){
                $a->modifyField('login_time', date('Y-m-d H:i:s'), ['id'=>$admin['id']]);
                session('admin', $admin);
                // return $this->fetch('login', ['admin'=>$admin, 'status'=>'success']);
                return ['status'=>0, 'info'=>'登录成功'];
            }else{
                // return $this->fetch('login', ['info'=>'用户名或密码错误']);
                return ['status'=>2, 'info'=>'用户名或密码错误'];
            }
        }else{
            // return $this->fetch('login', ['info'=>'非法操作']);
            return ['status'=>3, 'info'=>'非法操作'];
        }
    }

}
