<?php


namespace think\huishoubao\controller;


use think\Config;
use think\Controller;
use think\Db;
use think\exception\HttpResponseException;
use think\huishoubao\validate\PublicParams;
use think\huishoubao\validate\signature;
use think\Response;

class BaseController extends Controller
{
    protected $input;
    protected $data;
    protected $logConfig = [];
    protected $logDb = 1; // 用于判断是否入库日志，0入库，1不入库
    protected $moduleNo = 01;

    public function _initialize()
    {
        parent::_initialize();
        $this->input = urldecode($this->request->getInput());
        $this->data = json_decode($this->input, true);
        $head = [];
        if (is_array($this->data) && isset($this->data['_head']) && is_array($this->data['_head'])) {
            $head = $this->data['_head'];
            $head['_msgType'] = 'response';
        }
        // 加载服务ID配置
        if (!defined('SERVICE_ID') || !file_exists(HSB_PATH . 'config/serviceSignatureMap/' . SERVICE_ID . '.php')) {
            // 服务ID配置不存在
            $response = ['_head' => $head, '_data' => ['_ret' => "1", '_errCode' => "{$this->moduleNo}1000", '_errStr' => "serviceId不存在"]];
            $this->apiError(null, $response);
        }
        Config::load(HSB_PATH . 'config/serviceSignatureMap/' . SERVICE_ID . '.php', 'signatureKeyMap');
        //签名校验
        $signatureValidator = new signature();
        if (true !== ($signatureValidator->check($_SERVER))) {
            // 签名验证不通过
            $response = ['_head' => $head, '_data' => ['_ret' => "1", '_errCode' => "{$this->moduleNo}1001", '_errStr' => $signatureValidator->getError()]];
            $this->apiError(null, $response);
        }
        // 接口公共参数检查
        $publicValidator = new PublicParams();
        if (true !== ($publicValidator->check($this->data))) {
            // 参数验证不通过
            $response = ['_head' => $head, '_data' => ['_ret' => "1", '_errCode' => "{$this->moduleNo}1002", '_errStr' => $publicValidator->getError()]];
            $this->apiError(null, $response);
        }
    }

    /**
     * 格式化错误
     * @param $code
     * @param null|string|array $message
     * @Author lc
     */
    protected function apiError($code = null, $message = null)
    {
        if (is_array($message)) {
            $this->Log(['request' => $this->data, 'response' => $message, 'lastSql' => Db::getLastSql()]);
            $response = Response::create($message, Config::get('default_return_type'));
            throw new HttpResponseException($response);
        }
        if ($message == null) {
            $errorCode = Config::get('error_code');
            $message = isset($errorCode["E_" . $code]) ? $errorCode["E_" . $code] : "未定义错误";
        }
        $data = $this->data;
        $data['_head']['_msgType'] = 'response';
        $this->response($data['_head'], ['_ret' => "1", '_errCode' => "{$this->moduleNo}{$code}", '_errStr' => $message]);
    }

    /**
     * 记录日志
     * @param array $data
     * @Author lc
     */
    protected function Log($data)
    {
        $config = [
            // 时间戳格式
            'time_format' => 'Y-m-d H:i:s',
            // 日志记录方式，内置 file socket 支持扩展
            'type'        => 'File',
            // 日志保存目录
            'path'        => RUNTIME_PATH . 'common' . DS,
        ];
        if (!empty($this->logConfig)) {
            $config = $this->logConfig;
        }
        $this->requestLog($data, $config);
    }

    /**
     * 格式化返回数据
     * @param array $head
     * @param array $body
     * @Author lc
     */
    protected function response(array $head, array $body)
    {
        $this->Log(['request' => $this->data, 'response' => ['_head' => $head, '_data' => $body], 'lastSql' => Db::getLastSql()]);
        $responseType = Config::get('default_return_type');
        $response = Response::create(['_head' => $head, '_data' => $body], $responseType);
        throw new HttpResponseException($response);
    }

    /**
     * 记录日志
     * @param $data
     * @param $config
     * @Author lc
     */
    protected function requestLog($data, $config)
    {
        if (!is_array($data)) {
            $data = ['unexpectedType' => [$data]];
        } else {
            foreach ($data as $key => $value) {
                if (is_array($value))
                    $data[$key] = [json_encode($value, JSON_UNESCAPED_UNICODE)];
                else {
                    $value = str_replace(PHP_EOL, '', $value);
                    $value = str_replace(' ', '', $value);
                    $data[$key] = [$value];
                }
            }
        }
        $fileLog = new \think\log\driver\File($config);
        $fileLog->save($data);
    }

    /**
     * 生成签名
     * @param $data
     * @param null $callerId
     * @return bool|string
     * @Author lc
     */
    protected function getSignature($data, $callerId = null)
    {
        if (is_array($data))
            $data = json_encode($data);
        if (is_null($callerId))
            $callerId = SERVICE_ID;
        $signatureKeyMap = Config::get('signatureKeyMap');
        if (empty($signatureKeyMap) || !isset($signatureKeyMap['key'][$callerId]))
            return false;
        return md5($data . '_' . $signatureKeyMap['key'][$callerId]);
    }
}