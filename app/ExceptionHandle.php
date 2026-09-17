<?php
namespace app;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\Response;
use Throwable;

/**
 * 应用异常处理类
 */
class ExceptionHandle extends Handle
{
    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     * @access public
     * @param Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        // 线上 APP_DEBUG=false 时框架默认只记录一句错误信息，这里补上请求地址 / 方式 / IP / 当前登录身份和精简调用栈，
        // 写入 runtime/log/YYYYMM/DD_error.log，排查问题时不用再猜是哪个页面触发的
        if (!$this->isIgnoreReport($exception)) {
            try {
                $req  = $this->app->request;
                $who  = '';
                $u = session('user'); $a = session('admin');
                if (!empty($a['id'])) $who = 'admin#' . $a['id'];
                elseif (!empty($u['id'])) $who = 'user#' . $u['id'];
                $line = sprintf('[%s %s] ip=%s %s %s: %s in %s:%d', $req->method(), $req->url(true), $req->ip(), $who ?: 'guest', get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine());
                $trace = array_slice(explode("\n", $exception->getTraceAsString()), 0, 8);
                $this->app->log->record($line . "\n" . implode("\n", $trace), 'error');
                return;
            } catch (Throwable $e) {
                // 记录本身出错时退回框架默认方式
            }
        }
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     * @access public
     * @param \think\Request $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        // 添加自定义异常处理机制

        // 其他错误交给系统处理
        return parent::render($request, $e);
    }
}
