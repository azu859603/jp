<?php

namespace app\index\controller;

use think\facade\Db;
use think\facade\View;

/**
 * 新闻
 */
class News extends Base
{
    /**
     * 新闻详情
     */
    public function detail()
    {
        $id = (int)$this->request->param('id', 0);
        if ($id <= 0) {
            return redirect('/');
        }
        $news = Db::name('news')->where('id', $id)->where('status', 1)->find();
        if (!$news) {
            return redirect('/');
        }

        // 含 HTML 标签的内容原样渲染，纯文本内容按原有方式转义并保留换行
        if (preg_match('/<\w+[^>]*>/', $news['content'])) {
            $news['content_html'] = $news['content'];
        } else {
            $news['content_html'] = nl2br(htmlspecialchars($news['content']));
        }

        View::assign([
            'news'       => $news,
            'page_title' => $news['title'],
            'tab_active' => 'index',
        ]);
        return View::fetch();
    }
}
