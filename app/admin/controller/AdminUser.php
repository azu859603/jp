<?php
namespace app\admin\controller;

use think\facade\Db;
use think\facade\View;

class AdminUser extends Base
{
    /**
     * 管理员列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $list = Db::name('admin_user')->order('id', 'asc')->select()->toArray();
            foreach ($list as &$a) {
                $a['ga_bound'] = $a['google_secret'] !== '' ? 1 : 0;
                unset($a['password'], $a['google_secret']);
            }
            unset($a);
            return json(['code' => 0, 'msg' => '', 'count' => count($list), 'data' => $list, 'ga_on' => (int)get_setting('admin_google_auth', 0)]);
        }

        View::assign('menu_active', '/admin1314/admin/index');
        return View::fetch();
    }


    /**
     * 新增/编辑管理员
     */
    public function save()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id = (int)$this->request->post('id', 0);
        $username = trim($this->request->post('username', ''));
        $password = trim($this->request->post('password', ''));
        $realName = trim($this->request->post('real_name', ''));
        $role = (int)$this->request->post('role', 2);
        $status = (int)$this->request->post('status', 1);

        if ($id == 0) {
            if (empty($username) || empty($password)) {
                return json(['code' => 0, 'msg' => '请输入用户名和密码']);
            }
            if (strlen($password) < 6) {
                return json(['code' => 0, 'msg' => '密码至少6位']);
            }
            $exists = Db::name('admin_user')->where('username', $username)->find();
            if ($exists) {
                return json(['code' => 0, 'msg' => '用户名已存在']);
            }
            Db::name('admin_user')->insert([
                'username'    => $username,
                'password'    => hash_password($password),
                'real_name'   => $realName,
                'role'        => $role ?: 2,
                'status'      => $status ? 1 : 0,
                'create_time' => time(),
            ]);
            admin_log('新增管理员：' . $username);
            return json(['code' => 1, 'msg' => '创建成功']);
        }

        // 编辑
        $admin = Db::name('admin_user')->find($id);
        if (!$admin) {
            return json(['code' => 0, 'msg' => '管理员不存在']);
        }
        if ($admin['id'] == 1 && $status == 0) {
            return json(['code' => 0, 'msg' => '不能禁用超级管理员']);
        }
        if ($admin['id'] == 1 && $role != 1) {
            return json(['code' => 0, 'msg' => '不能修改超级管理员角色']);
        }

        $data = [
            'real_name' => $realName,
            'role'      => $role ?: 2,
            'status'    => $status ? 1 : 0,
        ];
        if (!empty($password)) {
            if (strlen($password) < 6) {
                return json(['code' => 0, 'msg' => '密码至少6位']);
            }
            $data['password'] = hash_password($password);
        }
        Db::name('admin_user')->where('id', $id)->update($data);
        admin_log('编辑管理员：' . $admin['username']);
        return json(['code' => 1, 'msg' => '保存成功']);
    }

    /**
     * 删除管理员
     */
    public function delete()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $id = (int)$this->request->post('id');
        if ($id == 1) {
            return json(['code' => 0, 'msg' => '不能删除超级管理员']);
        }
        if ($id == $this->admin['id']) {
            return json(['code' => 0, 'msg' => '不能删除自己']);
        }
        $admin = Db::name('admin_user')->find($id);
        if (!$admin) {
            return json(['code' => 0, 'msg' => '管理员不存在']);
        }
        Db::name('admin_user')->where('id', $id)->delete();
        admin_log('删除管理员：' . $admin['username']);
        return json(['code' => 1, 'msg' => '删除成功']);
    }    /**
     * 谷歌验证器：绑定页（本人）
     * 未绑定时生成一个临时密钥放在 session，扫码 + 输入动态码确认后才写入数据库
     */
    public function google()
    {
        $me = Db::name('admin_user')->find($this->admin['id']);
        $bound = !empty($me['google_secret']);
        $secret = '';
        $uri = '';
        if (!$bound) {
            $secret = (string)session('admin_ga_pending');
            if ($secret === '') {
                $secret = google_auth_secret();
                session('admin_ga_pending', $secret);
            }
            $issuer = (string)get_setting('site_name', '竞拍商城') ?: '竞拍商城';
            $uri = google_auth_uri($secret, $me['username'], $issuer . '后台');
        }
        View::assign([
            'bound'       => $bound,
            'secret'      => $secret,
            'uri'         => $uri,
            'ga_on'       => (int)get_setting('admin_google_auth', 0) === 1,
            'menu_active' => '/admin1314/admin_user/google',
        ]);
        return View::fetch();
    }

    /**
     * 确认绑定：用临时密钥校验动态码，通过后写入数据库
     */
    public function googleBind()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $code = trim($this->request->post('code', ''));
        $secret = (string)session('admin_ga_pending');
        if ($secret === '') {
            return json(['code' => 0, 'msg' => '绑定已过期，请刷新页面重新扫码']);
        }
        var_dump(111);exit;
        if ((string)Db::name('admin_user')->where('id', $this->admin['id'])->value('google_secret') !== '') {
            return json(['code' => 0, 'msg' => '已绑定过，无需重复绑定']);
        }
        if (google_auth_verify($secret, $code) === false) {
            return json(['code' => 0, 'msg' => '动态码不正确，请确认手机时间准确后重试']);
        }
        var_dump(123123123);exit;
        Db::name('admin_user')->where('id', $this->admin['id'])->update(['google_secret' => $secret, 'update_time' => time()]);
        var_dump(123123);exit;
        session('admin_ga_pending', null);
        // 刷新会话中的管理员信息，Base 的强制绑定检查据此放行
        $admin = Db::name('admin_user')->find($this->admin['id']);
        session('admin', $admin);
        admin_log('绑定谷歌验证器');
        return json(['code' => 1, 'msg' => '绑定成功', 'url' => '/admin1314/index/index']);
    }

    /**
     * 解除绑定（本人，需当前动态码）
     */
    public function googleUnbind()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $code = trim($this->request->post('code', ''));
        $secret = (string)Db::name('admin_user')->where('id', $this->admin['id'])->value('google_secret');
        if ($secret === '') {
            return json(['code' => 0, 'msg' => '尚未绑定']);
        }
        if (google_auth_verify($secret, $code) === false) {
            return json(['code' => 0, 'msg' => '动态码不正确']);
        }
        Db::name('admin_user')->where('id', $this->admin['id'])->update(['google_secret' => '', 'update_time' => time()]);
        $admin = Db::name('admin_user')->find($this->admin['id']);
        session('admin', $admin);
        admin_log('解除谷歌验证器绑定');
        return json(['code' => 1, 'msg' => '已解除绑定']);
    }

    /**
     * 超级管理员重置其他管理员的绑定（手机丢失等情况），对方下次登录重新绑定
     */
    public function googleReset()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        if ((int)$this->admin['role'] !== 1) {
            return json(['code' => 0, 'msg' => '只有超级管理员可以重置']);
        }
        $id = (int)$this->request->post('id');
        $target = Db::name('admin_user')->find($id);
        if (!$target) {
            return json(['code' => 0, 'msg' => '管理员不存在']);
        }
        if ($id === (int)$this->admin['id']) {
            return json(['code' => 0, 'msg' => '请在「谷歌验证」页用动态码解除自己的绑定']);
        }
        Db::name('admin_user')->where('id', $id)->update(['google_secret' => '', 'update_time' => time()]);
        admin_log('重置管理员谷歌验证器：' . $target['username']);
        return json(['code' => 1, 'msg' => '已重置，该管理员下次登录需重新绑定']);
    }
}
