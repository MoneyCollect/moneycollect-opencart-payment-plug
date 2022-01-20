<?php

class ControllerExtensionMoneycollectCheckout extends Controller {

    var $code;
    var $mc_model;
    var $order;
    var $payment_method = [
        'moneycollect_creditcard' => 'card',
        'moneycollect_alipayhk' => 'alipay_hk'
    ];

    public function __construct($registry)
    {
        parent::__construct($registry);

        // 不存在订单号 || 不存在支付方式
        if (empty($this->session->data['order_id']) ) {
            $this->response->redirect($this->url->link('checkout/cart'));
        }

        $this->response->addHeader('Content-Type: application/json');

        $this->code = $this->session->data['payment_method']['code'];

        $this->load->model('checkout/order');
        $order_id = $this->session->data['order_id'];
        $this->order = $this->model_checkout_order->getOrder($order_id);


        $this->load->model('extension/module/moneycollect');
        $this->mc_model = $this->model_extension_module_moneycollect;

    }

    public function create()
    {

        $base_data = $this->_baseData();
        $order_data = $this->_orderData();

        $data = array_merge($base_data,$order_data);

        $this->mc_model->logger()->addLog('session create',$data);

        $result = $this->mc_model->api()->post('/checkout/session/create',$data);



        if( $result['error'] ){

            $this->mc_model->logger()->addBug('payment create error: '.$result['error']);

            $this->_err('session create error: '. $result['error']);

            return;
        }

        $this->mc_model->logger()->addLog('session create result',$data);

        $this->response->setOutput($result['response']);

    }

    public function payment()
    {

        if( $this->request->server['REQUEST_METHOD'] == 'POST' ){
            $post = $this->request->post;

            $base_data = $this->_baseData();
            $order_data = $this->_orderData();

            $pm_id = $post['id'];

            if( empty($pm_id) ){
                $this->_err('Payment method  is null.');
                return;
            }

            try{

                if( $this->customer->isLogged() && !$this->mc_model->customer()->hasMcId() ){

                    $result = $this->mc_model->api()->post('/customers/create',$order_data['billingDetails']);

                    if( $result['error'] ){
                        throw new Exception($result['msg']);
                    }

                    $result_arr = json_decode( $result['response'], true);

                    $this->mc_model->logger()->addLog('customers create', $result_arr);

                    if( $result_arr['code'] === 'success' ){
                        $this->mc_model->customer()->createMcId($result_arr['data']['id']);
                    }else{
                        throw new Exception('customers create error: '.$result_arr['msg']);
                    }

                }

                if( $post['type'] == 'id' ){

                    $result = $this->mc_model->api()->post('/payment_methods/'.$pm_id.'/update',['billingDetails' => $order_data['billingDetails']]);

                    $this->mc_model->logger()->addLog('payment method ['.$pm_id.'] update', $order_data['billingDetails']);

                    if( $result['error'] ){
                        throw new Exception($result['error']);
                    }

                    $result_arr = json_decode( $result['response'], true);

                    $this->mc_model->logger()->addLog('payment methods update', $result_arr);

                    if( $result_arr['code'] !== 'success' ){
                        throw new Exception('payment methods update error: '.$result_arr['msg']);
                    }

                }

            }catch (\Exception $e){
                $this->mc_model->logger()->addBug($e->getMessage());
            }

            $data = [
                'orderNo' => $order_data['orderNo'],
                'amount' => $order_data['amountTotal'],
                'currency' => $order_data['currency'],
                'confirm' => 'true',
                'confirmationMethod' => 'automatic',
                'lineItems' => $order_data['lineItems'],
                'paymentMethod' => $pm_id,
                'customerId' => $this->mc_model->customer()->getMcId(),
                'ip' => $this->order['ip'],
                'notifyUrl' => $base_data['notifyUrl'],
                'returnUrl' => $base_data['returnUrl'],
                'preAuth' => $base_data['preAuth'],
                'setupFutureUsage' => $post['save_card'] == 'true'?'on':'off' ,
                'statementDescriptor' => $base_data['statementDescriptor'],
                'website' => $base_data['website']
            ];


            if( isset($order_data['shipping']) ){
                $data['shipping'] = $order_data['shipping'];
            }

            try{

                $this->mc_model->logger()->addLog('payment create',$data);

                $result = $this->mc_model->api()->post('/payment/create',$data);

                if( $result['error'] ){
                    throw new Exception($result['msg']);
                }

                $this->mc_model->logger()->addLog('payment create result',$result['response']);

                $result_arr = json_decode( $result['response'], true);

                if( $result_arr['code'] === 'success' ){

                    $data = $result_arr['data'];

                    if( isset($data['nextAction']) && $data['nextAction']['type'] == 'redirect' ){
                        $redirect = $data['nextAction']['redirectToUrl'];
                    }else if ( $this->mc_model->helper()->paymentComplete($data['status']) ) {
                        $redirect = '/index.php?route=checkout/success';
                    }else{
                        throw new Exception($data['displayStatus'].': '.$data['errorMessage']);
                    }

                    $json = json_encode(array(
                        'code' => 'success',
                        'redirect' => $redirect
                    ));

                    $this->mc_model->logger()->addLog('payment create handle',$json);

                    $this->response->setOutput($json);

                }else{
                    throw new Exception($result_arr['msg']);
                }


            }catch (\Exception $e){

                $message = $e->getMessage();

                $this->mc_model->logger()->addBug('payment create error: '.$message);

                $this->_err($message);

            }

        }

    }

    public function relieve(){

        @$id = $this->request->post['id'];

        try{

            if( !$id ){
                throw new Exception('payment id is null');
            }

            $result = $this->mc_model->api()->post('/payment_methods/'.$id.'/detach','');

            if( $result['error'] ){
                throw new Exception($result['msg']);
            }

            $this->response->setOutput($result['response']);

        }catch (\Exception $e){
            $this->_err($e->getMessage());
        }


    }

    public function _baseData()
    {

        $order_info = $this->order;

        if( $this->mc_model->customer()->hasMcId() ){
            $customer = $this->mc_model->customer()->getMcId();
            $customer_email = '';
        }else{
            $customer = '';
            $customer_email = $order_info['email'];
        }

        $payment_method = key_exists($this->session->data['payment_method']['code'], $this->payment_method)?$this->payment_method[ $this->session->data['payment_method']['code'] ]:null;

        $data = [
            'customer' => $customer,
            'customerEmail' => $customer_email,
            'cancelUrl' => $this->url->link('checkout/checkout'),
            'returnUrl' => $this->url->link('extension/moneycollect/back'),
            'notifyUrl' => $this->url->link('extension/moneycollect/webhook'),
            'preAuth' => $this->mc_model->helper()->getSetting($this->code.'_pre_auth') == 'on' ? 'y': 'n',
            'website' => $this->config->get('config_secure') ? HTTPS_SERVER : HTTP_SERVER,
            'statementDescriptor' => $this->mc_model->helper()->getSetting($this->code.'_statement_descriptor'),
            'paymentMethodTypes' => [$payment_method]
        ];

        if( empty($data['statementDescriptor']) ){
            unset($data['statementDescriptor']);
        }

        return $data;
    }

    public function _orderData()
    {

        $this->load->model('catalog/product');
        $this->load->model('tool/image');

        $order_info = $this->order;
        $order_products = $this->model_checkout_order->getOrderProducts($order_info['order_id']);
        $currency = $order_info['currency_code'];

        $line_items = [];


        foreach( $order_products as $item ){

            $product_info = $this->model_catalog_product->getProduct($item['product_id']);

            if ($product_info['image']) {
                $image = $this->model_tool_image->resize($product_info['image'],200,200);
            } else {
                $image = $this->model_tool_image->resize('placeholder.png',200,200);
            }

            $line_items[] = [
                'amount'   => $this->mc_model->helper()->transformAmount($item['price'],$currency),
                'currency' => $currency,
                'name'     => $item['name'],
                'quantity' => $item['quantity'],
                'description' => utf8_substr(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
                'images' => [$image]
            ];

        }

        $data = [
            'orderNo' => $order_info['order_id'],
            'currency' => $currency,
            'amountTotal' => $this->mc_model->helper()->transformAmount($order_info['total'],$currency),
            'billingDetails' => [
                'firstName' => $order_info['payment_firstname'],
                'lastName' => $order_info['payment_lastname'],
                'email' => $order_info['email'],
                'phone' => $order_info['telephone'],
                'address' => [
                    'country' => $order_info['payment_iso_code_2'],
                    'state'=> $order_info['payment_zone'],
                    'city' => $order_info['payment_city'],
                    'line1' => $order_info['payment_address_1'],
                    'line2' => $order_info['payment_address_2'],
                    'postalCode' => $order_info['payment_postcode']
                ]
            ],
            'lineItems' => $line_items,
        ];

        if( ( isset($order_info['shipping_firstname']) && !empty($order_info['shipping_firstname']) ) ||
            ( isset($order_info['shipping_lastname']) && !empty($order_info['shipping_lastname']) ) )
        {
            $data['shipping'] = [
                'firstName' => $order_info['shipping_firstname'],
                'lastName' => $order_info['shipping_lastname'],
                'phone' => $order_info['telephone'],
                'address' => [
                    'country' => $order_info['shipping_iso_code_2'],
                    'state'=> $order_info['shipping_zone'],
                    'city' => $order_info['shipping_city'],
                    'line1' => $order_info['shipping_address_1'],
                    'line2' => $order_info['shipping_address_2'],
                    'postalCode' => $order_info['shipping_postcode']
                ]
            ];
        }

        return $data;

    }

    private function _err($msg = ''){
        $json = json_encode(array(
            'code' => 'fail',
            'msg' => $msg,
            'data' => null
        ));
        $this->response->setOutput($json);
    }

}