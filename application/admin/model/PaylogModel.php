<?php
namespace app\admin\model;

class PaylogModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 's1s_pay_log';
    }
}