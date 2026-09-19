<?php
namespace app\admin\controller;

use think\facade\Db;
use think\facade\View;

/**
 * 轮播管理
 */
class Banner extends Base
{
    /**
     * 轮播列表
     */
    public function index()
    {
        $list = Db::name('banner')->order('sort', 'asc')->order('id', 'asc')->select()->toArray();

        View::assign(['list' => $list, 'menu_active' => '/admin1314/banner/index']);
        return View::fetch();
    }

    /**
     * 新增/编辑
     */
    public function save()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id = (int)$this->request->post('id', 0);
        $title = trim($this->request->post('title', ''));
        $image = trim($this->request->post('image', ''));
        $url = trim($this->request->post('url', ''));
        $sort = (int)$this->request->post('sort', 0);
        $status = (int)$this->request->post('status', 1);

        if ($image === '') {
            return json(['code' => 0, 'msg' => '请上传轮播图片']);
        }

        $data = [
            'title'   => $title,
            'image'   => $image,
            'url'     => $url,
            'sort'    => $sort,
            'status'  => $status ? 1 : 0,
            'update_time' => time(),
        ];

        if ($id > 0) {
            Db::name('banner')->where('id', $id)->update($data);
            banners_cache_clear();
            admin_log('编辑轮播：' . ($title ?: 'ID ' . $id));
        } else {
            $data['create_time'] = time();
            Db::name('banner')->insert($data);
            banners_cache_clear();
            admin_log('新增轮播：' . ($title ?: '未命名'));
        }

        return json(['code' => 1, 'msg' => '保存成功']);
    }

    /**
     * 删除轮播
     */
    public function delete()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id = (int)$this->request->post('id');
        if ($id <= 0) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }

        Db::name('banner')->where('id', $id)->delete();
        banners_cache_clear();
        admin_log('删除轮播：ID ' . $id);
        return json(['code' => 1, 'msg' => '删除成功']);
    }

    /**
     * 上传轮播图片
     */
    public function upload()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $file = $this->request->file('file');
        if (!$file) {
            return json(['code' => 0, 'msg' => '请选择图片']);
        }

        $size = $file->getSize();
        if ($size > 20 * 1024 * 1024) {
            return json(['code' => 0, 'msg' => '图片不能超过20M']);
        }
        $ext = strtolower($file->getOriginalExtension());
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
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

        $url = '/uploads/' . date('Ymd') . '/' . $name;
        return json(['code' => 1, 'msg' => '上传成功', 'url' => $url]);
    }
}
