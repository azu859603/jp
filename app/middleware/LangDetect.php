<?php
declare (strict_types = 1);

namespace app\middleware;

use Closure;
use think\App;
use think\Lang;

/**
 * 语言检测中间件（替代框架自带的 think\middleware\LoadLangPack）
 *
 * 与框架默认实现的区别：
 * 1. 框架的探测顺序是 GET → header → cookie → HTTP_ACCEPT_LANGUAGE，
 *    导致「首次访问跟随浏览器语言」，配置的 default_lang 形同虚设；
 *    这里改为 URL参数 → cookie → (可选)浏览器语言 → 配置默认值。
 * 2. 框架每次请求都回写 cookie，会把「系统默认值」固化成「用户选择」，
 *    之后再改 .env 对老访客不生效；这里只在用户显式切换时才写 cookie。
 *
 * 对应的 .env 配置（[LANG] 段）：
 *   default_lang   = zh-cn   首次访问使用的语言
 *   detect_browser = false   首次访问是否跟随浏览器语言（true 时浏览器语言优先于默认值）
 */
class LangDetect
{
    /** @var App */
    protected $app;

    /** @var Lang */
    protected $lang;

    public function __construct(App $app, Lang $lang)
    {
        $this->app  = $app;
        $this->lang = $lang;
    }

    public function handle($request, Closure $next)
    {
        $config    = $this->lang->getConfig();
        $allow     = !empty($config['allow_lang_list']) ? $config['allow_lang_list'] : [];
        $detectVar = !empty($config['detect_var']) ? $config['detect_var'] : 'lang';
        $cookieVar = !empty($config['cookie_var']) ? $config['cookie_var'] : 'think_lang';

        $langSet  = '';
        $explicit = false;   // 是否用户主动切换（决定要不要落 cookie）

        // 1) URL 参数：用户主动切换语言
        $fromUrl = (string)$request->param($detectVar, '');
        if ($fromUrl !== '' && $this->allowed($fromUrl, $allow)) {
            $langSet  = $fromUrl;
            $explicit = true;
        }

        // 2) Cookie：用户上次选择的语言
        if ($langSet === '') {
            $fromCookie = (string)$request->cookie($cookieVar, '');
            if ($fromCookie !== '' && $this->allowed($fromCookie, $allow)) {
                $langSet = $fromCookie;
            }
        }

        // 3) 浏览器语言：仅在 detect_browser=true 时参与
        if ($langSet === '' && !empty($config['detect_browser'])) {
            $langSet = $this->fromBrowser($request, $config, $allow);
        }

        // 4) 兜底：.env 中配置的默认语言
        if ($langSet === '') {
            $langSet = $this->lang->defaultLangSet();
        }

        if ($this->lang->getLangSet() !== $langSet) {
            $this->lang->switchLangSet($langSet);
        }

        // 多应用模式下 switchLangSet() 用 getAppPath()（app/index/）去 glob 语言包，
        // 而语言包实际在 getBasePath()（app/lang/），必然落空 —— 这里显式补加载，
        // 使本中间件无论注册在全局还是应用级都能正确生效。
        $this->loadBasePack($langSet);

        // 仅在用户主动切换时持久化，避免把系统默认值固化成用户选择
        if ($explicit && !empty($config['use_cookie'])) {
            $this->app->cookie->set($cookieVar, $langSet, 365 * 86400);
        }

        return $next($request);
    }

    /**
     * 从 app/lang/ 加载语言包（框架在多应用下找不到该目录）
     */
    protected function loadBasePack(string $langSet): void
    {
        $files = glob($this->app->getBasePath() . 'lang' . DIRECTORY_SEPARATOR . $langSet . '.*');
        if (!empty($files)) {
            $this->lang->load($files, $langSet);
        }
    }

    /**
     * 是否在允许的语言列表内（列表为空视为不限制）
     */
    protected function allowed(string $lang, array $allow): bool
    {
        return empty($allow) || in_array($lang, $allow, true);
    }

    /**
     * 解析 HTTP_ACCEPT_LANGUAGE，取第一个受支持的语言
     */
    protected function fromBrowser($request, array $config, array $allow): string
    {
        $accept = (string)$request->server('HTTP_ACCEPT_LANGUAGE');
        if ($accept === '') {
            return '';
        }
        // 形如 zh-CN,zh;q=0.9,en;q=0.8 —— 按权重顺序逐个尝试
        foreach (explode(',', $accept) as $item) {
            $code = strtolower(trim(explode(';', $item)[0]));
            if ($code === '') {
                continue;
            }
            if (isset($config['accept_language'][$code])) {
                $code = $config['accept_language'][$code];
            }
            if ($this->allowed($code, $allow)) {
                return $code;
            }
        }
        return '';
    }
}
