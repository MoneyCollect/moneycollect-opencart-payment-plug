<?php

class ControllerExtensionPaymentMoneycollectCreditCard extends Controller {

    var $code = 'moneycollect_creditcard';
    var $mc_model;

	function __construct($registry)
    {
        parent::__construct($registry);

        $this->load->model('extension/module/moneycollect');
        $this->mc_model = $this->model_extension_module_moneycollect;


        // language
        $this->load->language('extension/payment/moneycollect');
    }

    // 付款信息
    public function index()
    {

        $data['text_loading'] = $this->language->get('text_loading');

        $logo = [];
        foreach ($this->mc_model->helper()->getCardsList() as $key => $value){

            if( $this->mc_model->helper()->getSetting($this->code.'_'.$key) == 'on' ){
                $logo[] = [
                    'title' => $value,
                    'url' => 'catalog/view/theme/default/image/moneycollect/card/'.$key.'.png'
                ];
            }

        }
        $data['logos'] = $logo;

        $data['checkout_mode'] = $this->mc_model->helper()->getSetting($this->code.'_checkout_mode');

        if ( $data['checkout_mode'] == 1 ) {
            $data['text_redirect'] = $this->language->get('text_redirect');
        } else {


            $data['is_save_card'] = false;
            $payment_methods = [];

            if( $this->customer->isLogged() ){

                if( $this->mc_model->helper()->getSetting($this->code.'_save_cards') == 'on' ){
                    $data['is_save_card'] = true;
                }

                if( $data['is_save_card'] && $this->mc_model->customer()->hasMcId() ){

                    $result = $this->mc_model->api()->get('/payment_methods/list/'.$this->mc_model->customer()->getMcId());

                    if( !$result['error'] ){

                        $response = json_decode($result['response'],true);

                        if( $response['code'] == 'success' ){


                            foreach ($response['data'] as $item){
                                $payment_methods[] = [
                                    'id' => $item['id'],
                                    'card' => [
                                        'brand' => $item['card']['brand'],
                                        'expire' => '(expires '. $item['card']['expMonth'] .' / '.substr($item['card']['expYear'],'2','2').' )',
                                        'last4' => $item['card']['last4'],
                                    ]
                                ];
                            }

                        }


                    }else{
                        $this->mc_model->logger()->addBug($result['error']);
                    }


                }

            }

            $from_style = $this->mc_model->helper()->getSetting($this->code.'_form_style');

            $data['payment_methods'] = $payment_methods;
            $data['api_key'] = $this->mc_model->helper()->getPuKey();
            $data['sdk_mode'] = 'test';
            $data['layout'] = json_encode([
                'pageMode' => $from_style,// 页面风格模式  inner | block
                'style' => [
                    'frameMaxHeight' => $from_style == 'inner' ? '44' : '100', //  iframe最大高度
                    'input' => [
                        'FontSize' => '14', // 收集页面字体大小
                        'FontFamily' => '',  // 收集页面字体名称
                        'FontWeight' => '', // 收集页面字体粗细
                        'Color' => '', // 收集页面字体颜色
                        'ContainerBorder' => '1px solid #ddd;', // 收集页面字体边框
                        'ContainerBg' => '', // 收集页面字体粗细
                        'ContainerSh' => '' // 收集页面字体颜色
                    ]
                ],
            ]);

            $data['billing'] = json_encode($this->load->controller('extension/moneycollect/checkout/_orderData')['billingDetails']);

        }

        return $this->load->view('/extension/payment/moneycollect', $data);

    }


}
