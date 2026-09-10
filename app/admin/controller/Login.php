<?php
namespace app\admin\controller;

use app\BaseController;
use think\facade\Cache;
use think\facade\Db;
use think\facade\View;

class Login extends BaseController
{
    /** 连续失败上限 */
    const MAX_FAIL = 5;

    /** 锁定时长（秒） */
    const LOCK_TTL = 600;
    /**
     * 登录页面
     */
    public function index()
    {
        if (session('admin')) {
            return redirect('/admin1314/index/index');
        }
        // 是否开启谷歌验证码（登录页据此显示动态码输入框）
        View::assign('ga_on', (int)get_setting('admin_google_auth', 0) === 1);
        return View::fetch();
    }

    /**
     * 记一次登录失败
     */
    protected function markFail($lockKey, $fails)
    {
        Cache::set($lockKey, $fails + 1, self::LOCK_TTL);
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
        $gaCode   = trim($this->request->post('google_code', ''));

        // 同一 IP 连续失败达上限则临时锁定，防止口令爆破
        $lockKey = 'admin_login_fail_' . md5($this->request->ip());
        $fails   = (int)Cache::get($lockKey, 0);
        if ($fails >= self::MAX_FAIL) {
            return json(['code' => 0, 'msg' => '失败次数过多，请 ' . ceil(self::LOCK_TTL / 60) . ' 分钟后再试']);
        }

        if (empty($username) || empty($password)) {
            return json(['code' => 0, 'msg' => '请输入用户名和密码']);
        }
        if (empty($captcha) || strtolower($captcha) !== strtolower((string)session('admin_captcha'))) {
            $this->markFail($lockKey, $fails);
            session('admin_captcha', null);
            return json(['code' => 0, 'msg' => '验证码错误']);
        }

        $admin = Db::name('admin_user')->where('username', $username)->find();
        if (!$admin || !verify_password($password, $admin['password'])) {
            $this->markFail($lockKey, $fails);
            return json(['code' => 0, 'msg' => '用户名或密码错误']);
        }
        if ($admin['status'] != 1) {
            $this->markFail($lockKey, $fails);
            return json(['code' => 0, 'msg' => '账号已被禁用']);
        }

        // 谷歌验证码（后台开关开启时）：已绑定的管理员必须提供正确的 6 位动态码；
        // 未绑定的先放行登录，随后由 Base 强制跳转到绑定页完成绑定
        $gaOn = (int)get_setting('admin_google_auth', 0) === 1;
        if ($gaOn && !empty($admin['google_secret'])) {
            if ($gaCode === '') {
                return json(['code' => 0, 'msg' => '请输入谷歌验证码']);
            }
            $slice = google_auth_verify($admin['google_secret'], $gaCode);
            if ($slice === false) {
                $this->markFail($lockKey, $fails);
                return json(['code' => 0, 'msg' => '谷歌验证码错误或已过期']);
            }
            // 同一动态码只能用一次，防止被截获后重放
            $usedKey = 'admin_ga_used_' . $admin['id'];
            if ((int)Cache::get($usedKey, 0) === $slice) {
                return json(['code' => 0, 'msg' => '该谷歌验证码已使用，请等待下一个']);
            }
            Cache::set($usedKey, $slice, 120);
        }

        // 登录成功，清空失败计数
        Cache::delete($lockKey);

        // 写入登录信息
        session('admin', $admin);
        session('admin_captcha', null);
        $loginUpdate = [
            'last_login_ip'   => $this->request->ip(),
            'last_login_time' => time(),
        ];
        // 旧 md5 哈希在本次成功登录后静默升级为 bcrypt
        if (password_is_legacy($admin['password'])) {
            $loginUpdate['password'] = hash_password($password);
        }
        Db::name('admin_user')->where('id', $admin['id'])->update($loginUpdate);
        admin_log('登录后台', $admin['id']);

        // 开启了谷歌验证但本人还没绑定：登录后直接进绑定页
        if ($gaOn && empty($admin['google_secret'])) {
            return json(['code' => 1, 'msg' => '登录成功，请先绑定谷歌验证器', 'url' => '/admin1314/admin_user/google']);
        }
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
