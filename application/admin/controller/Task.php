<?php
namespace app\admin\controller;

use app\common\model\TaskModel;

/**
 * 任务管理
 */
class Task extends \think\Controller
{
    /**
     * 任务列表
     * @Author 贺强
     * @Date   2018-09-11
     * @param  TaskModel  $t TaskModel 实例
     */
    public function index(TaskModel $t)
    {
        $where = [];
        // 分页参数
        $page     = intval($this->request->get('page', 1));
        $pagesize = intval($this->request->get('pagesize', config('PAGESIZE')));
        $list  = $t->getList($where, true, "$page,$pagesize");
        $pages = 0;
        if ($list) {
            $count = $t->getList($where);
            $pages = ceil($count / $pagesize);
        }
        return $this->fetch('index', ['list' => $list, 'pages' => $pages]);
    }
}
