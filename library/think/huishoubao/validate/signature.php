<?php


namespace think\huishoubao\validate;


use think\Config;
use think\Validate;

class signature extends Validate
{
    protected $rule = [
        'HTTP_HSB_OPENAPI_SIGNATURE'       => 'require',
        'HTTP_HSB_OPENAPI_CALLERSERVICEID' => 'require|signature',

    ];
    protected $message = [
        'HTTP_HSB_OPENAPI_SIGNATURE.require'         => '签名不能为空',
        'HTTP_HSB_OPENAPI_CALLERSERVICEID.require'   => '服务ID不能为空',
        'HTTP_HSB_OPENAPI_CALLERSERVICEID.signature' => '签名验证失败',
    ];

    /**
     * 校验签名
     * @param mixed $value 验证数据
     * @param mixed $rule 验证规则
     * @param array $data 全部数据（数组）
     * @return string|boolean
     * @Author lc
     */
    protected function signature($value, $rule, $data)
    {
        $signature = $data['HTTP_HSB_OPENAPI_SIGNATURE'];
        $signatureKeyMap = Config::get('signatureKeyMap');
        if (!isset($signatureKeyMap['key'][$data['HTTP_HSB_OPENAPI_CALLERSERVICEID']]))
            return 'callerServiceId不存在';
        $signatureReal = md5(request()->getInput() . '_' . $signatureKeyMap['key'][$data['HTTP_HSB_OPENAPI_CALLERSERVICEID']]);
        if ($signature != $signatureReal)
            return false;
        return true;
    }
}