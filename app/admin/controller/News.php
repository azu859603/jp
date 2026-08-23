<?php
namespace app\admin\controller;

use think\facade\Db;
use think\facade\View;

/**
 * 新闻管理
 */
class News extends Base
{
    /**
     * 新闻列表
     */
    public function index()
    {
        $status = (int)$this->request->param('status', -1);
        $keyword = trim($this->request->param('keyword', ''));

        $q = Db::name('news');
        if ($status >= 0) {
            $q->where('status', $status);
        }
        if ($keyword !== '') {
            $q->whereLike('title', "%{$keyword}%");
        }

        $list = $q->order('id', 'desc')->select()->toArray();
        View::assign([
            'list'        => $list,
            'status'      => $status,
            'keyword'     => $keyword,
            'menu_active' => '/admin1314/news/index',
        ]);
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
        $titleTw = trim($this->request->post('title_tw', ''));
        $titleEn = trim($this->request->post('title_en', ''));
        $content = trim($this->request->post('content', ''));
        $contentTw = trim($this->request->post('content_tw', ''));
        $contentEn = trim($this->request->post('content_en', ''));
        $status = (int)$this->request->post('status', 1);

        if (empty($title)) {
            return json(['code' => 0, 'msg' => '请输入新闻标题']);
        }
        if (empty($content)) {
            return json(['code' => 0, 'msg' => '请输入新闻内容']);
        }
        if (mb_strlen($title) > 100 || mb_strlen($titleTw) > 100 || mb_strlen($titleEn) > 100) {
            return json(['code' => 0, 'msg' => '标题不能超过100字']);
        }

        $data = [
            'title'       => $title,
            'title_tw'    => $titleTw,
            'title_en'    => $titleEn,
            'content'     => $content,
            'content_tw'  => $contentTw,
            'content_en'  => $contentEn,
            'status'      => $status ? 1 : 0,
        ];

        if ($id > 0) {
            $data['update_time'] = time();
            Db::name('news')->where('id', $id)->update($data);
            admin_log('编辑新闻：' . $title);
        } else {
            $data['create_time'] = time();
            $data['update_time'] = time();
            Db::name('news')->insert($data);
            admin_log('发布新闻：' . $title);
        }

        return json(['code' => 1, 'msg' => '保存成功']);
    }

    /**
     * 删除新闻
     */
    public function delete()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        $title = Db::name('news')->where('id', $id)->value('title');
        Db::name('news')->where('id', $id)->delete();
        admin_log('删除新闻：' . ($title ?: 'ID ' . $id));
        return json(['code' => 1, 'msg' => '删除成功']);
    }
}
