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

        View::assign([
            'news'       => $news,
            'page_title' => $news['title'],
            'tab_active' => 'index',
        ]);
        return View::fetch();
    }
}
