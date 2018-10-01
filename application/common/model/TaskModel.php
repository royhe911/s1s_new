<?php
namespace app\common\model;

use think\Db;

class TaskModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 's1s_task';
    }

    /**
     * 发布任务
     * @Author 贺强
     * @Date   2018-09-27
     * @param  array      $param 要发布的任务数据
     */
    public function AddTask($param = [])
    {
        $pro_no   = date('YmdHis') . rand(100000, 999999);
        $uid      = $param['uid'];
        $shop_id  = $param['shop_id'];
        $pro_data = [
            'pro_no'      => $pro_no,
            'uid'         => $uid,
            'shop_id'     => $shop_id,
            'category_id' => $param['category_id'],
            'title'       => $param['title'],
            'url'         => $param['url'],
            'pic'         => $param['pic'],
            'reward'      => $param['reward'],
            'addtime'     => time(),
            'business_id' => $param['business_id'],
            'remark'      => '',
        ];
        // 开启事务操作发布任务
        Db::startTrans();
        $p   = new ProductModel();
        $res = $p->add($pro_data);
        if (!$res) {
            Db::rollback();
            return 1;
        }
        $pa            = new ProductAttrModel();
        $pro_attr_data = [];
        $task_data     = [];
        $keyword_arr   = $param['keyword_arr'];
        $price_arr     = $param['price_arr'];
        $num_arr       = $param['num_arr'];
        $chat_arr      = $param['chat_arr'];
        $hbsj_arr      = $param['hbsj_arr'];
        $jgwc_arr      = $param['jgwc_arr'];
        $scbb_arr      = $param['scbb_arr'];
        $gzdp_arr      = $param['gzdp_arr'];
        $llt_arr       = $param['llt_arr'];
        $llf_arr       = $param['llf_arr'];
        $remark_arr    = $param['remark_arr'];
        $index         = 0;
        foreach ($keyword_arr as $key => $keyword) {
            $pro_attr['pid']     = $res;
            $pro_attr['keyword'] = $keyword;
            $price               = $price_arr[$key];
            $pro_attr['price']   = $price;
            $num                 = intval($num_arr[$key]);
            $pro_attr['num']     = $num;
            $pro_attr['remark']  = $remark_arr[$key];
            $s_services          = '';
            if (!empty($chat_arr[$key])) {
                $s_services .= ',' . $chat_arr[$key];
            }
            if (!empty($hbsj_arr[$key])) {
                $s_services .= ',' . $hbsj_arr[$key];
            }
            if (!empty($jgwc_arr[$key])) {
                $s_services .= ',' . $jgwc_arr[$key];
            }
            if (!empty($scbb_arr[$key])) {
                $s_services .= ',' . $scbb_arr[$key];
            }
            if (!empty($gzdp_arr[$key])) {
                $s_services .= ',' . $gzdp_arr[$key];
            }
            if (!empty($llt_arr[$key])) {
                $s_services .= ',' . $llt_arr[$key];
            }
            if (!empty($llf_arr[$key])) {
                $s_services .= ',' . $llf_arr[$key];
            }
            if (!empty($s_services)) {
                $s_services = substr($s_services, 1);
            }
            $pro_attr['s_services'] = $s_services;
            $res_pa                 = $pa->add($pro_attr);
            if (!$res_pa) {
                Db::rollback();
                return 2;
            }
            for ($i = 0; $i < $num; $i++) {
                $task_data[$index]['uid']      = $uid;
                $task_data[$index]['shop_id']  = $shop_id;
                $task_data[$index]['pid']      = $res;
                $task_data[$index]['pa_id']    = $res_pa;
                $task_data[$index]['wangwang'] = $param['wangwang'];
                $task_data[$index]['price']    = $price;
                $task_data[$index]['cost']     = $this->getCost($price);
                $index++;
            }
        }
        $res_t = $this->addArr($task_data);
        if (!$res_t) {
            DB::rollback();
            return 3;
        }
        DB::commit();
        return true;
    }

    /**
     * 根据任务价格获取服务费
     * @Author 贺强
     * @Date   2018-09-27
     * @param  decimal    $price 任务价格
     * @return decimal           返回服务费
     */
    public function getCost($price)
    {
        $charges = session('charges');
        if (empty($charges)) {
            $c       = new ChargeModel();
            $charges = $c->getList(['is_delete' => 0]);
            $charges = array_column($charges, 'charge', 'label');
            session('charges', $charges);
        }
        if ($price > 0) {
            if ($price <= 100) {
                return $charges['0-100'];
            } elseif ($price <= 200) {
                return $charges['101-200'];
            } elseif ($price <= 300) {
                return $charges['201-300'];
            } elseif ($price <= 400) {
                return $charges['301-400'];
            } elseif ($price <= 500) {
                return $charges['401-500'];
            } elseif ($price <= 600) {
                return $charges['501-600'];
            }
        }
        return 0;
    }
}
