<?php
declare (strict_types = 1);

namespace app\middleware;

use think\Request;
use think\Response;

/**
 * 跨站请求防护：改状态的请求（POST/PUT/DELETE/PATCH）若携带 Origin / Referer，
 * 其来源域名必须与当前站点一致，否则拒绝。
 * 浏览器发起的跨站表单提交与脚本请求都会带 Origin；同站请求、命令行 / 服务端调用（无 Origin、无 Referer）不受影响。
 * Origin 为 "null"（sandbox iframe / data: 页面 / 跨站重定向）无法证明来源，同样按跨站拒绝。
 */
class CsrfOrigin
{
    public function handle(Request $request, \Closure $next)
    {
        if (in_array(strtoupper($request->method()), ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            $origin  = trim((string)$request->header('origin', ''));
            $referer = trim((string)$request->header('referer', ''));
            $source  = $origin !== '' ? $origin : $referer;
            if ($source !== '') {
                $host = strtolower((string)parse_url($source, PHP_URL_HOST));
                $self = strtolower((string)$request->host(true));
                // 来源域名不一致，或 Origin: null（解析不出域名）→ 拒绝
                if ($host !== $self) {
                    if ($request->isAjax() || $request->isJson()) {
                        return json(['code' => 0, 'msg' => lang('请求来源不合法')], 403);
                    }
                    return Response::create(lang('请求来源不合法'), 'html', 403);
                }
            }
        }
        return $next($request);
    }
}
