<?php
namespace app\admin\controller;

/**
 * 接口类
 */
class Api extends \think\Controller
{
    /**
     * 商家注册
     * @Author 贺强
     * @date   2018-09-06
     */
    public function register()
    {
        if ($this->request->isPost()) {
            $param = $this->request->post();
        } else {
            return ['status' => 300, 'info' => '非法操作'];
        }
    }
}
