<?php
namespace app\admin\controller;

use app\admin\model\ShopModel;

/**
 * 店铺管理
 */
class Shop extends \think\Controller
{

    /**
     * 新增店铺
     * @Author 贺强
     * @date   2018-09-04
     * @param  ShopModel  $s ShopModel 实例
     */
    public function add(ShopModel $s)
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        if ($this->request->isAjax()) {
            if ($admin['role_id'] !== 3) {
                return ['status' => 1, 'info' => '没有权限进行此操作'];
            }
            $param = $this->request->post();
            if (empty($param['shop_name']) || empty($param['wangwang']) || empty($param['url']) || empty($param['mobile']) || empty($param['wx']) || empty($param['province']) || empty($param['city'])) {
                return ['status' => 2, 'info' => '非法参数'];
            }
            // $has = $s->getModel(['mobile' => $param['mobile']]);
            // if (!empty($has)) {
            //     return ['status' => 3, 'info' => '手机号已存在'];
            // }
            // $has = $s->getModel(['wangwang' => $param['wangwang']]);
            // if (!empty($has)) {
            //     return ['status' => 5, 'info' => '旺旺号已存在'];
            // }
            $has = $s->getModel(['url' => $param['url']]);
            if (!empty($has)) {
                return ['status' => 3, 'info' => '店铺网址已存在'];
            }
            $param['uid']     = $admin['id'];
            $param['addtime'] = time();
            $res              = $s->add($param);
            if (!$res) {
                return ['status' => 4, 'info' => '添加失败'];
            }
            return ['status' => 0, 'info' => '添加成功'];
        } else {
            return $this->fetch('add');
        }
    }

    /**
     * 修改店铺
     * @Author 贺强
     * @date   2018-09-04
     * @param  ShopModel  $s ShopModel 实例
     */
    public function edit(ShopModel $s)
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        if ($this->request->isAjax()) {
            if ($admin['role_id'] !== 3) {
                return ['status' => 1, 'info' => '没有权限进行此操作'];
            }
            $param = $this->request->post();
            if (empty($param['id']) || empty($param['shop_name']) || empty($param['wangwang']) || empty($param['url']) || empty($param['mobile']) || empty($param['wx']) || empty($param['province']) || empty($param['city'])) {
                return ['status' => 2, 'info' => '非法参数'];
            }
            $has = $s->getModel(['url' => $param['url'], 'id' => ['<>', $param['id']]]);
            if (!empty($has)) {
                return ['status' => 3, 'info' => '店铺网址非法'];
            }
            $res = $s->modify($param, ['id' => $param['id']]);
            if ($res === false) {
                return ['status' => 4, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        } else {
            $id = $this->request->get('id');
            if (empty($id) || !is_numeric($id)) {
                $this->error('非法操作');
            }
            $shop = $s->getModel(['id' => $id]);
            if (empty($shop)) {
                $this->error('店铺不存在');
            }
            return $this->fetch('edit', ['shop' => $shop]);
        }
    }

    /**
     * 审核店铺
     * @Author 贺强
     * @date   2018-09-04
     * @param  ShopModel  $s ShopModel 实例
     */
    public function auditor(ShopModel $s)
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        if ($this->request->isAjax()) {
            $id     = $this->request->post('id');
            $status = $this->request->post('status');
            if (empty($id) || empty($status)) {
                return ['status' => 2, 'info' => '非法参数'];
            }
            $shop = $s->getModel(['id' => $id]);
            if (empty($shop)) {
                return ['status' => 3, 'info' => '店铺不存在'];
            }
            $res = $s->modify(['status' => $status, 'reviewtime' => time()], ['id' => $id]);
            if ($res === false) {
                return ['status' => 4, 'info' => '审核失败'];
            }
            return ['status' => 0, 'info' => '审核成功'];
        } else {
            return ['status' => 1, 'info' => '非法操作'];
        }
    }
    /**
     * 店铺列表
     * @Author 贺强
     * @date   2018-09-03
     * @param  ShopModel  $s ShopModel 实例
     */
    public function index(ShopModel $s)
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        $where = ['is_delete' => 0];
        if ($admin['role_id'] === 3) {
            // 如果登录角色是商家，则只能看到自己的店铺
            $where['uid'] = $admin['id'];
        } elseif ($admin['role_id'] === 2) {
            // 如果登录角色是业务员，则能他名下所有商家的店铺
            $a       = new AdminModel();
            $list_sj = $a->getList(['s_id' => $admin['id']], 'id');
            $ids     = '0';
            foreach ($list_sj as $sj) {
                $ids .= "," . $sj['id'];
            }
            $where['uid'] = ['in', $ids];
        }
        // 分页参数
        $page     = intval($this->request->get('page', 1));
        $pagesize = intval($this->request->get('pagesize', config('PAGESIZE')));
        $list     = $s->getList($where, true, "$page,$pagesize");
        $pages    = 0;
        if ($list) {
            $a      = new AdminModel();
            $sj_arr = $a->getList(['role_id' => 3], 'id,realname');
            $sj_arr = array_column($sj_arr, 'realname', 'id');
            $count  = $s->getCount($where);
            foreach ($list as &$item) {
                if (!empty($sj_arr[$item['uid']])) {
                    $item['sj_name'] = $sj_arr[$item['uid']];
                } else {
                    $item['sj_name'] = '';
                }
                if (!empty($item['addtime'])) {
                    $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                }
                if (!empty($item['reviewtime'])) {
                    $item['reviewtime'] = date('Y-m-d H:i:s', $item['reviewtime']);
                }
                if ($item['status'] == 0) {
                    $item['status_txt'] = '待审核';
                } elseif ($item['status'] == 8) {
                    $item['status_txt'] = '已审核';
                } elseif ($item['status'] == 4) {
                    $item['status_txt'] = '审核不通过';
                }
                if (strpos($item['url'], 'http://') === false && strpos($item['url'], 'https://') === false) {
                    $item['url'] = 'http://' . $item['url'];
                }
            }
            $pages = ceil($count / $pagesize);
        }
        return $this->fetch('index', ['admin' => $admin, 'list' => $list, 'pages' => $pages]);
    }
}
