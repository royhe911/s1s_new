<?php
namespace app\admin\model;

class AdminModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'admin';
    }
}