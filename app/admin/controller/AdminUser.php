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
            return json(['code' => 0, 'msg' => '', 'count' => count($list), 'data' => $list]);
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
                'password'    => encrypt_password($password),
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
            $data['password'] = encrypt_password($password);
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
    }
}
