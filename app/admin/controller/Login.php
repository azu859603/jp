<?php
namespace app\admin\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\View;

class Login extends BaseController
{
    /**
     * 登录页面
     */
    public function index()
    {
        if (session('admin')) {
            return redirect('/admin1314/index/index');
        }
        return View::fetch();
    }

    /**
     * 登录处理
     */
    public function doLogin()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }

        $username = trim($this->request->post('username', ''));
        $password = trim($this->request->post('password', ''));
        $captcha  = trim($this->request->post('captcha', ''));

        if (empty($username) || empty($password)) {
            return json(['code' => 0, 'msg' => '请输入用户名和密码']);
        }
        if (empty($captcha) || strtolower($captcha) !== strtolower((string)session('admin_captcha'))) {
            return json(['code' => 0, 'msg' => '验证码错误']);
        }

        $admin = Db::name('admin_user')->where('username', $username)->find();
        if (!$admin || $admin['password'] !== encrypt_password($password)) {
            return json(['code' => 0, 'msg' => '用户名或密码错误']);
        }
        if ($admin['status'] != 1) {
            return json(['code' => 0, 'msg' => '账号已被禁用']);
        }

        // 写入登录信息
        session('admin', $admin);
        session('admin_captcha', null);
        Db::name('admin_user')->where('id', $admin['id'])->update([
            'last_login_ip'   => $this->request->ip(),
            'last_login_time' => time(),
        ]);
        admin_log('登录后台', $admin['id']);

        return json(['code' => 1, 'msg' => '登录成功', 'url' => '/admin1314/index/index']);
    }

    /**
     * 图形验证码
     */
    public function captcha()
    {
        $code = '';
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        for ($i = 0; $i < 4; $i++) {
            $code .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        session('admin_captcha', $code);

        $width = 120;
        $height = 40;
        $img = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($img, 245, 247, 250);
        imagefill($img, 0, 0, $bg);

        // 干扰线
        for ($i = 0; $i < 5; $i++) {
            $lineColor = imagecolorallocate($img, mt_rand(150, 220), mt_rand(150, 220), mt_rand(150, 220));
            imageline($img, mt_rand(0, $width), mt_rand(0, $height), mt_rand(0, $width), mt_rand(0, $height), $lineColor);
        }
        // 噪点
        for ($i = 0; $i < 80; $i++) {
            $pixColor = imagecolorallocate($img, mt_rand(120, 200), mt_rand(120, 200), mt_rand(120, 200));
            imagesetpixel($img, mt_rand(0, $width - 1), mt_rand(0, $height - 1), $pixColor);
        }

        // 字符
        for ($i = 0; $i < 4; $i++) {
            $textColor = imagecolorallocate($img, mt_rand(30, 120), mt_rand(30, 120), mt_rand(30, 120));
            $font = 5;
            $x = 12 + $i * 26;
            $y = mt_rand(8, 20);
            imagechar($img, $font, $x, $y, $code[$i], $textColor);
        }

        header('Content-Type: image/png');
        imagepng($img);
        imagedestroy($img);
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        session('admin', null);
        session('admin_captcha', null);
        return redirect('/admin1314/login/index');
    }
}
