<?php
namespace Moneycollect;

class Api
{
    const ENV_PRO = 'https://api.moneycollect.com/api/services/v1';
    const ENV_TEST = 'https://sandbox.moneycollect.com/api/services/v1';
    const JSSDK = 'https://static.moneycollect.com/jssdk/js/MoneyCollect.js';

    var $model;


    var $_ch;
    var $_headers = [
        "Content-type" => "application/json",
    ];
    var $_port = 80;
    var $_sslVersion = false;
    var $_timeout = 300;

    public function __construct($model)
    {
        $this->model = $model;
        $helper = new Helper($model);
        $this->_headers['Authorization'] = $helper->getPrKey();
    }

    private function getEnv()
    {
        if ($this->model->config->get('payment_moneycollect_general_mode') == '1') {
            return self::ENV_PRO;
        }
        return self::ENV_TEST;
    }

    protected function curlOption($name, $value)
    {
        curl_setopt($this->_ch, $name, $value);
    }

    public function post($url, $params = [])
    {
        return $this->request($this->getEnv().$url,json_encode($params),'POST');
    }

    public function get($url){
        return $this->request($this->getEnv().$url,'','GET');
    }

    public function request($url, $params = [], $method)
    {

        $this->_ch = curl_init();
        $this->curlOption(CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS | CURLPROTO_FTP | CURLPROTO_FTPS);
        $this->curlOption(CURLOPT_URL, $url);

        if ($method == 'POST') {
            $this->curlOption(CURLOPT_POST, 1);
            $this->curlOption(CURLOPT_POSTFIELDS, is_array($params) ? http_build_query($params) : $params);
        } elseif ($method == "GET") {
            $this->curlOption(CURLOPT_HTTPGET, 1);
        } else {
            $this->curlOption(CURLOPT_CUSTOMREQUEST, $method);
        }

        if (count($this->_headers)) {
            $heads = [];
            foreach ($this->_headers as $k => $v) {
                $heads[] = $k . ': ' . $v;
            }
            $this->curlOption(CURLOPT_HTTPHEADER, $heads);
        }


        if ($this->_timeout) {
            $this->curlOption(CURLOPT_TIMEOUT, $this->_timeout);
        }

        if ($this->_port != 80) {
            $this->curlOption(CURLOPT_PORT, $this->_port);
        }

        $this->curlOption(CURLOPT_RETURNTRANSFER, 1);

        if ($this->_sslVersion !== null) {
            $this->curlOption(CURLOPT_SSLVERSION, $this->_sslVersion);
            $this->curlOption(CURLOPT_SSL_VERIFYPEER, $this->_sslVersion);
            $this->curlOption(CURLOPT_SSL_VERIFYHOST, $this->_sslVersion);
        }

        $_responseBody = curl_exec($this->_ch);
        $_err = curl_errno($this->_ch);

        curl_close($this->_ch);

        return [
            'error' => $_err,
            'response' => $_responseBody
        ];

    }







}
