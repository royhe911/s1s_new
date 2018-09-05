<?php
namespace app\admin\controller;

use app\admin\model\AdminModel;
use app\admin\model\ShokeyModel;

/**
 * 试客管理
 */
class Shokey extends \think\Controller
{
    /**
     * 试客列表
     * @Author 贺强
     * @date   2018-09-05
     * @param  ShokeyModel $s ShokeyModel 实例
     */
    public function index(ShokeyModel $s)
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        $where = ['is_delete' => 0];
        // 分页参数
        $page     = intval($this->request->get('page', 1));
        $pagesize = intval($this->request->get('pagesize', config('PAGESIZE')));
        $list     = $s->getList($where, true, "$page,$pagesize", 'addtime desc');
        $pages    = 0;
        if ($list) {
            $count  = $s->getCount($where);
            $pages  = ceil($count / $pagesize);
            $a      = new AdminModel();
            $kf_arr = $a->getList(['role_id' => ['in', '5,6']], 'id,realname');
            $kf_arr = array_column($kf_arr, 'realname', 'id');
            foreach ($list as &$item) {
                if (!empty($kf_arr[$item['c_id']])) {
                    $item['kf_name'] = $kf_arr[$item['c_id']];
                } else {
                    $item['kf_name'] = '暂无';
                }
            }
        }
        return $this->fetch('index', ['list' => $list, 'pages' => $pages]);
    }
}
