<?php
namespace app\admin\controller;

use think\facade\Db;
use think\facade\View;

class Category extends Base
{
    /**
     * 分类列表
     */
    public function index()
    {
        $list = Db::name('category')->order('sort', 'asc')->order('id', 'asc')->select()->toArray();

        // 附加商品数
        $goodsCounts = Db::name('goods')->group('category_id')->column('COUNT(*)', 'category_id');
        foreach ($list as &$c) {
            $c['goods_count'] = $goodsCounts[$c['id']] ?? 0;
        }
        unset($c);

        View::assign(['list' => $list, 'menu_active' => '/admin1314/category/index']);
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
        $name = trim($this->request->post('name', ''));
        $nameTw = trim($this->request->post('name_tw', ''));
        $nameEn = trim($this->request->post('name_en', ''));
        $icon = trim($this->request->post('icon', ''));
        $image = trim($this->request->post('image', ''));
        $sort = (int)$this->request->post('sort', 0);
        $status = (int)$this->request->post('status', 1);

        if (empty($name)) {
            return json(['code' => 0, 'msg' => '请输入分类名称']);
        }

        $data = [
            'name'   => $name,
            'name_tw' => $nameTw ?: $name,
            'name_en' => $nameEn ?: $name,
            'icon'   => $icon,
            'image'  => $image,
            'sort'   => $sort,
            'status' => $status ? 1 : 0,
        ];

        if ($id > 0) {
            $data['update_time'] = time();
            Db::name('category')->where('id', $id)->update($data);
            categories_cache_clear();
            admin_log('编辑分类：' . $name);
        } else {
            $data['create_time'] = time();
            $data['update_time'] = time();
            Db::name('category')->insert($data);
            categories_cache_clear();
            admin_log('新增分类：' . $name);
        }

        return json(['code' => 1, 'msg' => '保存成功']);
    }

    /**
     * 上传分类图片
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
        if ($size > upload_max_bytes()) {
            return json(['code' => 0, 'msg' => upload_max_msg()]);
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

    /**
     * 删除分类
     */
    public function delete()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id = (int)$this->request->post('id');

        $goodsCount = Db::name('goods')->where('category_id', $id)->count();
        if ($goodsCount > 0) {
            return json(['code' => 0, 'msg' => '该分类下还有 ' . $goodsCount . ' 个商品，不能删除']);
        }

        Db::name('category')->where('id', $id)->delete();
        categories_cache_clear();
        admin_log('删除分类：ID ' . $id);
        return json(['code' => 1, 'msg' => '删除成功']);
    }
}
