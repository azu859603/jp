<?php
namespace app\agent\controller;

/**
 * 代理后台图片上传（继承 Base，自动要求代理登录）
 * 逻辑与主后台一致：扩展名白名单 + 20M 限制 + 服务端重命名
 */
class Upload extends Base
{
    public function image()
    {
        $file = $this->request->file('file');
        if (!$file) {
            return json(['code' => 0, 'msg' => '请选择图片']);
        }

        if ($file->getSize() > 20 * 1024 * 1024) {
            return json(['code' => 0, 'msg' => '图片不能超过20M']);
        }
        $ext = strtolower($file->getOriginalExtension());
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return json(['code' => 0, 'msg' => '仅支持jpg/png/gif/webp格式']);
        }
        // 按文件内容校验，防止把脚本改个扩展名传上来
        $imgInfo = @getimagesize($file->getPathname());
        if (!$imgInfo || !in_array($imgInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            return json(['code' => 0, 'msg' => '文件不是有效的图片']);
        }

        $savePath = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . date('Ymd');
        if (!is_dir($savePath)) {
            mkdir($savePath, 0755, true);
        }

        $name = date('His') . '_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 10) . '.' . $ext;
        try {
            $file->move($savePath, $name);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'msg' => '上传失败：' . $e->getMessage()]);
        }

        return json(['code' => 1, 'msg' => '上传成功', 'url' => '/uploads/' . date('Ymd') . '/' . $name]);
    }
}
