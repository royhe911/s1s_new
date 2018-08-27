<?php
namespace app\admin\controller;

/**
 * 文件上传
 */
class Upload extends \think\Controller
{
    /**
     * 图片上传
     */
    public function upload_img()
    {
        $root_path  = ROOT_PATH . 'public/uploads/';
        $file_types = ['jpg', 'jpeg', 'gif', 'png'];
        $file       = $_FILES['Filedata'];
        if (empty($file)) {
            return json(['status' => 1, 'info' => '请选择上传文件']);
        }
        $file         = (array) $file;
        $fileinfo     = pathinfo($file['name']);
        $verify_token = md5(config('UPLOAD_SALT') . $this->request->post('timestamp'));
        if ($verify_token !== $this->request->post('token')) {
            return json(['status' => 2, 'info' => '非法操作']);
        }
        $tmp_file   = $file['tmp_name'];
        $upload_dir = date('Y') . '/' . date('m') . '/' . date('d');
        if (!is_dir($root_path . $upload_dir)) {
            @mkdir($upload_dir, 0755, true);
        }
        if (!in_array(strtolower($fileinfo['extension']), $file_types)) {
            return json(['status' => 3, 'info' => '文件类型不合法']);
        }
        $filename    = '/' . get_millisecond() . '.' . $fileinfo['extension'];
        $target_file = $root_path . $upload_dir . $filename;
        // 上传
        $res = move_uploaded_file($tmp_file, $target_file);
        if ($res) {
            return json(['status' => 0, 'info' => '上传成功', 'path' => $upload_dir . $filename]);
        }
    }
}
