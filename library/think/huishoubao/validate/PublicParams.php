<?php

namespace think\huishoubao\validate;


use think\Validate;

/**
 * 公共验证器
 * Class PublicParams
 * @package think\huishoubao\validate
 * @Author lc
 */
class PublicParams extends Validate
{

    private $expire = 5; // 签名有效期（单位：秒）

    protected $rule = [
        '_head'                  => 'require|array|checkParam',
        '_head._interface'       => 'require',
        '_head._version'         => 'require',
        '_head._msgType'         => 'require',
        '_head._invokeId'        => 'require',
        '_head._callerServiceId' => 'require',
        '_head._groupNo'         => 'require',
        '_head._timestamps'      => 'require|length:10|expired'

    ];

    protected $message = [
        '_head.require'                  => '请求头不能为空',
        '_head.array'                    => '请求头必须是数组',
        '_head._interface.require'       => '请求接口不能为空',
        '_head._version.require'         => '接口版本号不能为空',
        '_head._msgType.require'         => '消息类型不能为空',
        '_head._invokeId.require'        => '请求流水号不能为空',
        '_head._callerServiceId.require' => '请求服务ID不能为空',
        '_head._groupNo.require'         => 'GROUP不能为空',
        '_head._timestamps.require'      => '请求时间不能为空',
        '_head._timestamps.length'       => '请求时间长度为10位',
        '_head._timestamps.expired'      => '签名时间戳过期',
    ];

    protected $scene = [
    ];

    /**
     * 验证时间戳是否过期
     * @param mixed $value 验证数据
     * @param mixed $rule 验证规则
     * @param array $data 全部数据（数组）
     * @return string|boolean
     * @Author lc
     */
    protected function expired($value, $rule, $data)
    {
        $time = time();
        // 时间戳过期
        if ($time - $data['_head']['_timestamps'] > $this->expire) {
            return false;
        }
        return true;
    }

    /**
     * 验证_param
     * @param mixed $value 验证数据
     * @param mixed $rule 验证规则
     * @param array $data 全部数据（数组）
     * @return string|boolean
     * @Author lc
     */
    protected function checkParam($value, $rule, $data)
    {
        if (!isset($data['_param']))
            return "请求参数不能为空";
        if (!is_array($data['_param']))
            return "请求参数必须是数组";
        return true;
    }
}