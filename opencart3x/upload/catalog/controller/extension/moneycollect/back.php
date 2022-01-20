<?php

class ControllerExtensionMoneycollectBack extends Controller {

    var $code;
    var $mc_model;
    var $order;

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->load->model('checkout/order');

        $this->load->model('extension/module/moneycollect');
        $this->mc_model = $this->model_extension_module_moneycollect;

    }

    public function index(){

        @$payment_id = $this->request->get['payment_id'];

        if( !$payment_id ){
            $this->response->redirect($this->url->link('common/home'));
        }

        try{

            $this->mc_model->logger()->addLog('back get payment',$payment_id);

            $result = $this->mc_model->api()->get('/payment/'.$payment_id);

            if( $result['error'] ){
                $this->mc_model->logger()->addBug('back get payment error: '.$result['msg']);
                throw new Exception($result['msg']);
            }

            $this->mc_model->logger()->addLog('back get payment result',$result['response']);

            $result_arr = json_decode( $result['response'], true);

            if( $result_arr['code'] !== 'success' ){
                throw new Exception($result_arr['msg']);
            }

            $data = $result_arr['data'];

            $order_id = $data['orderNo'];
            $this->order = $this->model_checkout_order->getOrder($order_id);

            if( !$this->order ){
                $this->mc_model->logger()->addBug('the order '. $order_id .' is not found ');
                throw new Exception('the order is not found');
            }

            $this->code = $this->order['payment_code'];

            if( $this->customer->isLogged() && !$this->mc_model->customer()->hasMcId() && !empty($data['customerId']) ){

                $this->mc_model->logger()->addLog('back customers create',$data['customerId']);

                $this->mc_model->customer()->createMcId($data['customerId']);
            }

            if( $this->mc_model->helper()->paymentComplete($data['status']) ){
                $this->response->redirect($this->url->link('checkout/success'));
            }else{
                if( empty($data['errorMessage']) ){
                    throw new Exception($data['displayStatus']);
                }else{
                    throw new Exception($data['displayStatus'].': '.$data['errorMessage']);
                }

            }

        }catch (\Exception $e){
            $message = $e->getMessage();
            $this->session->data['error'] = $message;
            $this->response->redirect($this->url->link('checkout/checkout'));
        }

    }

}