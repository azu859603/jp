<?php
namespace app\index\controller;

use think\facade\Db;

class Upload extends Base
{
    /**
     * 图片上传（需登录）
     */
    public function image()
    {
        $this->checkLogin();
        $file = $this->request->file('file');
        if (!$file) {
            return json(['code' => 0, 'msg' => lang('请选择图片')]);
        }

        // 校验图片
        $size = $file->getSize();
        if ($size > upload_max_bytes()) {
            return json(['code' => 0, 'msg' => upload_max_msg(true)]);
        }
        $ext = strtolower($file->getOriginalExtension());
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return json(['code' => 0, 'msg' => lang('仅支持jpg/png/gif/webp格式')]);
        }
        // 按文件内容校验，防止把脚本改个扩展名传上来
        $imgInfo = @getimagesize($file->getPathname());
        if (!$imgInfo || !in_array($imgInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            return json(['code' => 0, 'msg' => lang('文件不是有效的图片')]);
        }

        $savePath = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . date('Ymd');
        if (!is_dir($savePath)) {
            mkdir($savePath, 0755, true);
        }

        $name = date('His') . '_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 10) . '.' . $ext;
        try {
            $file->move($savePath, $name);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'msg' => lang('上传失败：') . $e->getMessage()]);
        }

        $url = '/uploads/' . date('Ymd') . '/' . $name;
        return json(['code' => 1, 'msg' => lang('上传成功'), 'url' => $url]);
    }
}
