<?php
namespace app\admin\controller;

use think\facade\Db;

/**
 * 后台图片上传（需管理员登录）
 */
class Upload extends Base
{
    public function image()
    {
        $file = $this->request->file('file');
        if (!$file) {
            return json(['code' => 0, 'msg' => '请选择图片']);
        }

        // 校验图片
        $size = $file->getSize();
        if ($size > 5 * 1024 * 1024) {
            return json(['code' => 0, 'msg' => '图片不能超过5M']);
        }
        $ext = strtolower($file->getOriginalExtension());
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return json(['code' => 0, 'msg' => '仅支持jpg/png/gif/webp格式']);
        }

        $savePath = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . date('Ymd');
        if (!is_dir($savePath)) {
            mkdir($savePath, 0755, true);
        }

        $name = date('His') . '_' . mt_rand(1000, 9999) . '.' . $ext;
        try {
            $file->move($savePath, $name);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'msg' => '上传失败：' . $e->getMessage()]);
        }

        $url = '/uploads/' . date('Ymd') . '/' . $name;
        return json(['code' => 1, 'msg' => '上传成功', 'url' => $url]);
    }
}
