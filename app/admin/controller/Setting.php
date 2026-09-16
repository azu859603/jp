<?php
namespace app\admin\controller;

use think\facade\Db;
use think\facade\View;

class Setting extends Base
{
    /**
     * 基础设置
     */
    public function index()
    {
        if ($this->request->isPost()) {
            $fields = [
                'site_name', 'site_logo', 'site_url', 'commission_rate', 'admin_google_auth',
                'seller_check', 'goods_check', 'invite_required', 'withdraw_fee', 'service_phone',
                'service_qq', 'service_link', 'auction_delay', 'auto_relist_seller_id', 'auto_relist_hours', 'user_protocol', 'privacy_policy', 'publish_protocol',
                'platform_auto_bid_enabled', 'platform_auto_bid_interval', 'platform_auto_bid_multiple', 'platform_auto_bid_stop_hours',
                'user_protocol_tw', 'user_protocol_en', 'privacy_policy_tw', 'privacy_policy_en', 'publish_protocol_tw', 'publish_protocol_en',
                'withdraw_min', 'withdraw_max',
                'order_pay_timeout_hours', 'order_timeout_deposit', 'order_auto_confirm_days', 'order_ship_remind_days',
                'about_us', 'about_us_tw', 'about_us_en', 'about_us_image',
                'about_dept', 'about_dept_tw', 'about_dept_en',
            ];
            $data = [];
            foreach ($fields as $field) {
                // 只处理本次实际提交的字段：否则任何只提交部分字段的请求都会把其余设置清空
                if (!$this->request->has($field, 'post')) {
                    continue;
                }
                $value = trim($this->request->post($field, ''));
                if (in_array($field, ['commission_rate', 'withdraw_fee', 'withdraw_min', 'withdraw_max', 'auto_relist_hours'])) {
                    $value = (string)max(0, (float)$value);
                }
                if ($field === 'auto_relist_seller_id') {
                    $value = (string)max(0, (int)$value);
                }
                if ($field === 'platform_auto_bid_enabled') {
                    $value = (string)$value === '1' ? '1' : '0';
                }
                if ($field === 'platform_auto_bid_interval') {
                    $value = (string)max(1, min(1440, (int)$value));
                }
                if ($field === 'platform_auto_bid_multiple') {
                    $value = (string)max(1, round((float)$value, 2));
                }
                if ($field === 'platform_auto_bid_stop_hours') {
                    $value = (string)max(0, min(720, round((float)$value, 2)));
                }
                $data[$field] = $value;
            }
            // 开启后台谷歌验证前，当前管理员自己必须已绑定，否则保存后自己也登不进来
            if (isset($data['admin_google_auth']) && (string)$data['admin_google_auth'] === '1'
                && (string)Db::name('admin_user')->where('id', $this->admin['id'])->value('google_secret') === '') {
                return json(['code' => 0, 'msg' => '请先在「系统设置 › 谷歌验证」绑定您自己的验证器，再开启后台谷歌验证']);
            }
            if (isset($data['commission_rate']) && (float)$data['commission_rate'] > 100) {
                return json(['code' => 0, 'msg' => '佣金比例不能超过100%']);
            }
            if (isset($data['withdraw_min'], $data['withdraw_max'])
                && (float)$data['withdraw_max'] > 0 && (float)$data['withdraw_min'] > (float)$data['withdraw_max']) {
                return json(['code' => 0, 'msg' => '提现最低金额不能大于最高金额']);
            }

            $now = time();
            foreach ($data as $name => $value) {
                $exists = Db::name('setting')->where('name', $name)->find();
                if ($exists) {
                    Db::name('setting')->where('name', $name)->update([
                        'value'       => $value,
                        'update_time' => $now,
                    ]);
                } else {
                    Db::name('setting')->insert([
                        'name'        => $name,
                        'value'       => $value,
                        'create_time' => $now,
                    ]);
                }
            }
            site_settings_refresh();
            // 平台自营自动出价的开关 / 参数有提交：立即同步任务（开启 → 接管会员 1 拍品上的手动任务并建任务；关闭 → 停止脚本任务）
            $syncMsg = '';
            if (isset($data['platform_auto_bid_enabled']) || isset($data['platform_auto_bid_interval']) || isset($data['platform_auto_bid_multiple']) || isset($data['platform_auto_bid_stop_hours'])) {
                $syncMsg = platform_auto_bid_sync_summary(platform_auto_bid_sync());
            }
            admin_log('修改系统设置' . ($syncMsg !== '' ? '（' . $syncMsg . '）' : ''));
            return json(['code' => 1, 'msg' => '设置已保存' . ($syncMsg !== '' ? '；' . $syncMsg : '')]);
        }

        $settings = Db::name('setting')->column('value', 'name');
        // 补默认值，避免新增配置未入库时模板访问报错
        $defaults = [
            'site_name' => '', 'site_logo' => '', 'site_url' => '', 'admin_google_auth' => '0',
            'commission_rate' => '0', 'seller_check' => '1', 'goods_check' => '1', 'invite_required' => '1',
            'withdraw_fee' => '0', 'service_phone' => '',
            'service_qq' => '', 'service_link' => '', 'auction_delay' => '0', 'auto_relist_seller_id' => '1', 'auto_relist_hours' => '0', 'user_protocol' => '',
            'platform_auto_bid_enabled' => '0', 'platform_auto_bid_interval' => '30', 'platform_auto_bid_multiple' => '2', 'platform_auto_bid_stop_hours' => '1', 'privacy_policy' => '', 'publish_protocol' => '',
            'user_protocol_tw' => '', 'user_protocol_en' => '', 'privacy_policy_tw' => '', 'privacy_policy_en' => '', 'publish_protocol_tw' => '', 'publish_protocol_en' => '',
            'withdraw_min' => '0', 'withdraw_max' => '0',
            'order_pay_timeout_hours' => '0', 'order_timeout_deposit' => 'forfeit_platform', 'order_auto_confirm_days' => '0', 'order_ship_remind_days' => '0',
            'about_us' => '', 'about_us_tw' => '', 'about_us_en' => '', 'about_us_image' => '',
            'about_dept' => '', 'about_dept_tw' => '', 'about_dept_en' => '',
        ];
        $settings = array_merge($defaults, $settings);
        View::assign(['settings' => $settings, 'menu_active' => '/admin1314/setting/index']);
        return View::fetch();
    }

    /**
     * 上传站点Logo
     */
    public function upload()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }
        $file = $this->request->file('file');
        if (!$file) {
            return json(['code' => 0, 'msg' => '请选择图片']);
        }

        $size = $file->getSize();
        if ($size > 5 * 1024 * 1024) {
            return json(['code' => 0, 'msg' => '图片不能超过5M']);
        }
        $ext = strtolower($file->getOriginalExtension());
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return json(['code' => 0, 'msg' => '仅支持jpg/png/gif/webp格式']);
        }
        // 按文件内容校验，防止把脚本改个扩展名传上来
        $imgInfo = @getimagesize($file->getPathname());
        if (!$imgInfo || !in_array($imgInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            return json(['code' => 0, 'msg' => '文件不是有效的图片']);
        }

        $savePath = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . date('Ymd');
        if (!is_dir($savePath)) {
            mkdir($savePath, 0755, true);
        }

        $name = date('His') . '_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 10) . '.' . $ext;
        try {
            $file->move($savePath, $name);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'msg' => '上传失败：' . $e->getMessage()]);
        }

        $url = '/uploads/' . date('Ymd') . '/' . $name;
        admin_log('上传站点Logo：' . $url);
        return json(['code' => 1, 'msg' => '上传成功', 'url' => $url]);
    }
}
