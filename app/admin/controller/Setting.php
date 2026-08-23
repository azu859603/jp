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
                'site_name', 'site_logo', 'site_url', 'commission_rate',
                'seller_check', 'goods_check', 'invite_code', 'withdraw_fee', 'service_phone',
                'service_qq', 'service_link', 'auction_delay', 'user_protocol', 'privacy_policy', 'publish_protocol',
                'user_protocol_tw', 'user_protocol_en', 'privacy_policy_tw', 'privacy_policy_en', 'publish_protocol_tw', 'publish_protocol_en',
                'withdraw_min', 'withdraw_max', 'virtual_balance',
            ];
            $data = [];
            foreach ($fields as $field) {
                $value = trim($this->request->post($field, ''));
                if (in_array($field, ['commission_rate', 'withdraw_fee', 'withdraw_min', 'withdraw_max', 'virtual_balance'])) {
                    $value = (string)max(0, (float)$value);
                }
                $data[$field] = $value;
            }
            if ($data['commission_rate'] > 100) {
                return json(['code' => 0, 'msg' => '佣金比例不能超过100%']);
            }
            if ((float)$data['withdraw_max'] > 0 && (float)$data['withdraw_min'] > (float)$data['withdraw_max']) {
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
            admin_log('修改系统设置');
            return json(['code' => 1, 'msg' => '设置已保存']);
        }

        $settings = Db::name('setting')->column('value', 'name');
        // 补默认值，避免新增配置未入库时模板访问报错
        $defaults = [
            'site_name' => '', 'site_logo' => '', 'site_url' => '',
            'commission_rate' => '0', 'seller_check' => '1', 'goods_check' => '1',
            'invite_code' => '', 'withdraw_fee' => '0', 'service_phone' => '',
            'service_qq' => '', 'service_link' => '', 'auction_delay' => '0', 'user_protocol' => '', 'privacy_policy' => '', 'publish_protocol' => '',
            'user_protocol_tw' => '', 'user_protocol_en' => '', 'privacy_policy_tw' => '', 'privacy_policy_en' => '', 'publish_protocol_tw' => '', 'publish_protocol_en' => '',
            'withdraw_min' => '0', 'withdraw_max' => '0', 'virtual_balance' => '10000',
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

        $savePath = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . date('Ymd');
        if (!is_dir($savePath)) {
            mkdir($savePath, 0755, true);
        }

        $name = date('His') . '_' . mt_rand(1000, 9999) . '.' . $ext;
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
