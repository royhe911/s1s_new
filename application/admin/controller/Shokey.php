<?php
namespace app\admin\controller;

use app\common\model\AdminModel;
use app\common\model\ShokeyModel;

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
                if ($item['sex'] == 1) {
                    $item['sex_txt'] = '男';
                } elseif ($item['sex'] == 2) {
                    $item['sex_txt'] = '女';
                } else {
                    $item['sex_txt'] = '保密';
                }
                if ($item['status'] == 8) {
                    $item['status_txt'] = '已审核';
                } elseif ($item['status'] == 4) {
                    $item['status_txt'] = '审核不通过';
                } elseif ($item['status'] == 0) {
                    $item['status_txt'] = '未审核';
                }
            }
        }
        return $this->fetch('index', ['list' => $list, 'pages' => $pages, 'admin' => $admin]);
    }

    /**
     * 试客审核
     * @Author 贺强
     * @date   2018-09-06
     * @param  ShokeyModel $s ShokeyModel 实例
     */
    public function auditors(ShokeyModel $s)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id']) || empty($param['status'])) {
                return ['status' => 2, 'info' => '非法参数'];
            }
            $reason = '';
            if (!empty($param['reason'])) {
                $reason = $param['reason'];
            }
            $res = $s->modify(['status' => $param['status'], 'reason' => $reason], ['id' => $param['id']]);
            if (!$res) {
                return ['status' => 4, 'info' => '审核失败'];
            }
            return ['status' => 0, 'info' => '审核成功'];
        } else {
            return ['status' => 1, 'info' => '非法操作'];
        }
    }
}
