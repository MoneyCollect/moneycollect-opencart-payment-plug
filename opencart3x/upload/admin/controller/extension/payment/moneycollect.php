<?php

class ControllerExtensionPaymentMoneycollect extends Controller {


    var $code;
    var $title;
    var $data = [];
    var $webhook;
    var $logger;
    var $log_path;
    var $mc_data;


    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->load->language('extension/payment/moneycollect');

        $this->mc_data = new Moneycollect\Admin\Data($this);
        $this->webhook = $this->url->link('moneycollect/webhook');
        $this->logger = new Moneycollect\Logger();
        $this->log_path = $this->logger->logPath();

    }

    public function install() {
        if ($this->user->hasPermission('modify', 'extension/extension/payment')) {
            $this->load->model('extension/payment/moneycollect');
            $this->model_extension_payment_moneycollect->install();
        }
    }

    public function index(){

        $post_data = $this->checkParameter();

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $post_data) {
            $this->load->model('setting/setting');

            if ($this->request->post['payment_moneycollect_general_logging'] == '1') {
                $this->logger->mkDir();
            }

            foreach ($post_data as $code => $item){
                $this->model_setting_setting->editSetting($code, $item);
            }

            $this->data['success'] = $this->language->get('text_success');
        }

        $this->indexData();

        $this->settingOption();

        $this->response->setOutput($this->load->view('extension/payment/moneycollect_payment', $this->data), $this->config->get('config_compression'));
    }

    protected function settingOption($payment_settings = array()){
        $settings       = $this->mc_data->getAdminSettings();

        if( $payment_settings ){
            $settings['payment'] = array_merge($settings['payment'],$payment_settings);
        }

        $this->data['settings_general']      = $this->load->view('extension/payment/moneycollect_settings', ['settings' => $settings['general']]);
        $this->data['settings_payment']      = $this->load->view('extension/payment/moneycollect_settings', ['settings' => $settings['payment']]);

    }

    protected function checkParameter(){
        if ($this->request->server['REQUEST_METHOD'] != 'POST') {
            return false;
        }
        $post_data = [];

        foreach ($this->request->post as $key => $val) {
            $key_arr                = explode('_', $key);
            $code                   = $key_arr[0] . '_' . $key_arr[1] . '_' . $key_arr[2];
            $post_data[$code][$key] = trim(addslashes($val));
        }
        return $post_data;
    }



    protected function indexData(){
        $this->data['header']      = $this->load->controller('common/header');
        $this->data['column_left'] = $this->load->controller('common/column_left');
        $this->data['footer']      = $this->load->controller('common/footer');

        $this->data['breadcrumbs'] = array(
            array(
                'href' => HTTPS_SERVER . 'index.php?route=common/home&token=' . $this->session->data['user_token'],
                'text' => $this->language->get('text_home'),
            ),
            array(
                'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true),
                'text' => $this->language->get('text_extension'),
            ),
            array(
                'href' => $this->url->link('extension/payment/moneycollectpay', 'user_token=' . $this->session->data['user_token'], true),
                'text' => $this->language->get('heading_title'),
            )
        );

        $this->data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);
        $this->data['action'] = $this->url->link('extension/payment/'.$this->code, 'user_token=' . $this->session->data['user_token'], true);

    }


}
