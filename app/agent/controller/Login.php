<?php
namespace app\agent\controller;

use app\BaseController;
use think\facade\Cache;
use think\facade\Db;
use think\facade\View;

/**
 * 代理后台登录
 *
 * 身份说明：
 * - 校验的是前台会员表 user（手机号 + 密码），不是 admin_user；
 * - 登录成功写入的仍是 session('user')，与前台共用同一身份，
 *   因此前台已登录的代理进 /agent 无需二次登录，反之亦然；
 * - 准入条件 is_agent=1 在登录时就校验，非代理账号不写 session。
 *
 * 注意：本控制器继承 BaseController 而非本应用的 Base，
 * 因为 Base::initialize() 会强制要求已登录，登录页必须绕开。
 */
class Login extends BaseController
{
    /** 连续失败上限 */
    const MAX_FAIL = 5;

    /** 锁定时长（秒） */
    const LOCK_TTL = 600;

    /**
     * 登录页
     */
    public function index()
    {
        $sessUser = session('user');
        $notice   = '';

        if (!empty($sessUser['id'])) {
            $cur = Db::name('user')->where('id', $sessUser['id'])->find();
            if ($cur && $cur['status'] == 1 && (int)$cur['is_agent'] === 1) {
                // 已是代理且在线，直接进后台
                return redirect('/agent/index/index');
            }
            if ($cur && $cur['status'] == 1) {
                // 前台已登录但不是代理：提示换号，不清除其前台登录态
                $notice = '当前登录的账号（' . $this->maskMobile($cur['mobile']) . '）不是代理账号，请使用代理账号登录。';
            }
        }

        View::assign([
            'notice'    => $notice,
            'site_name' => $this->siteName(),
        ]);
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

        $lockKey = 'agent_login_fail_' . md5($this->request->ip());
        $fails   = (int)Cache::get($lockKey, 0);
        if ($fails >= self::MAX_FAIL) {
            return json(['code' => 0, 'msg' => '失败次数过多，请 ' . ceil(self::LOCK_TTL / 60) . ' 分钟后再试']);
        }

        $mobile   = trim($this->request->post('mobile', ''));
        $password = trim($this->request->post('password', ''));
        $captcha  = trim($this->request->post('captcha', ''));

        if ($mobile === '' || $password === '') {
            return json(['code' => 0, 'msg' => '请输入手机号和密码']);
        }
        if ($captcha === '' || strtolower($captcha) !== strtolower((string)session('agent_captcha'))) {
            // 验证码一次性：无论对错都作废，防止重复使用
            session('agent_captcha', null);
            return json(['code' => 0, 'msg' => '验证码错误']);
        }
        session('agent_captcha', null);

        $user = Db::name('user')->where('mobile', $mobile)->find();
        if (!$user || !verify_password($password, $user['password'])) {
            $this->markFail($lockKey, $fails);
            return json(['code' => 0, 'msg' => '手机号或密码错误']);
        }
        if ($user['status'] != 1) {
            $this->markFail($lockKey, $fails);
            return json(['code' => 0, 'msg' => '账号已被禁用']);
        }
        if ((int)$user['is_agent'] !== 1) {
            // 非代理：不写 session，避免代理登录页被当作前台登录入口
            $this->markFail($lockKey, $fails);
            return json(['code' => 0, 'msg' => '该账号不是代理账号，无法登录代理中心']);
        }

        Cache::delete($lockKey);

        // 旧 md5 哈希在本次成功登录后静默升级为 bcrypt
        if (password_is_legacy($user['password'])) {
            Db::name('user')->where('id', $user['id'])->update(['password' => hash_password($password)]);
        }

        Db::name('user')->where('id', $user['id'])->update([
            'last_login_time' => time(),
            'update_time'     => time(),
        ]);

        unset($user['password']);
        session('user', $user);

        return json(['code' => 1, 'msg' => '登录成功', 'url' => '/agent/index/index']);
    }

    /**
     * 记一次失败
     */
    protected function markFail($lockKey, $fails)
    {
        Cache::set($lockKey, $fails + 1, self::LOCK_TTL);
    }

    /**
     * 图形验证码
     */
    public function captcha()
    {
        $code  = '';
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        for ($i = 0; $i < 4; $i++) {
            $code .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        session('agent_captcha', $code);

        $width  = 120;
        $height = 40;
        $img = imagecreatetruecolor($width, $height);
        $bg  = imagecolorallocate($img, 245, 247, 250);
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
            imagechar($img, 5, 12 + $i * 26, mt_rand(8, 20), $code[$i], $textColor);
        }

        // 走 TP 响应管线输出，避免直接 header() 造成的 headers already sent
        ob_start();
        imagepng($img);
        $content = ob_get_clean();
        imagedestroy($img);

        return response($content, 200, [
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma'        => 'no-cache',
        ]);
    }

    /**
     * 退出登录
     * 与前台共用 session('user')，退出即同时退出前台
     */
    public function logout()
    {
        session('user', null);
        session('agent_captcha', null);
        return redirect('/agent/login/index');
    }

    /**
     * 手机号脱敏（登录页提示用）
     */
    protected function maskMobile($mobile)
    {
        $mobile = (string)$mobile;
        if (strlen($mobile) < 7) {
            return $mobile;
        }
        return substr($mobile, 0, 3) . '****' . substr($mobile, -4);
    }

    /**
     * 站点名称
     */
    protected function siteName()
    {
        $name = (string)Db::name('setting')->where('name', 'site_name')->value('value');
        return $name !== '' ? $name : '竞拍商城';
    }
}
