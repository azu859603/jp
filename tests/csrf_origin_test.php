<?php
/**
 * CsrfOrigin 中间件：改状态请求的来源校验
 *  - 同站 Origin / Referer：放行
 *  - 跨站 Origin：403
 *  - Origin: null（sandbox iframe / data: 页面）：403
 *  - 无 Origin 且无 Referer（命令行 / 服务端调用）：放行
 *  - GET 不受影响
 */
function ok($n, $c, $d = '') { echo ($c ? '  PASS ' : '  FAIL ') . $n . ($c ? '' : '  <- ' . mb_substr((string)$d, 0, 300)) . "\n"; }
function post($headers) {
    $ch = curl_init('http://localhost/user/doLogin');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => 'mobile=&password=', CURLOPT_HTTPHEADER => array_merge(['X-Requested-With: XMLHttpRequest'], $headers)]);
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b];
}
[$c, $b] = post(['Origin: http://localhost']);                     ok('同站 Origin 放行（进入控制器校验）', $c == 200 && strpos($b, '请输入手机号和密码') !== false, "HTTP $c $b");
[$c, $b] = post(['Referer: http://localhost/user/login']);          ok('同站 Referer 放行', $c == 200, "HTTP $c $b");
[$c, $b] = post(['Origin: http://evil.example']);                   ok('跨站 Origin → 403', $c == 403 && strpos($b, '请求来源不合法') !== false, "HTTP $c $b");
[$c, $b] = post(['Referer: http://evil.example/x']);                ok('跨站 Referer → 403', $c == 403, "HTTP $c $b");
[$c, $b] = post(['Origin: null']);                                  ok('Origin: null → 403', $c == 403 && strpos($b, '请求来源不合法') !== false, "HTTP $c $b");
[$c, $b] = post(['Origin: null', 'Referer: http://localhost/x']);   ok('Origin: null 即便 Referer 同站也拒绝', $c == 403, "HTTP $c $b");
[$c, $b] = post([]);                                                ok('无 Origin 无 Referer（非浏览器调用）放行', $c == 200, "HTTP $c $b");
$ch = curl_init('http://localhost/user/login'); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HTTPHEADER => ['Origin: http://evil.example']]); curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
ok('GET 不校验来源', $c == 200, "HTTP $c");
