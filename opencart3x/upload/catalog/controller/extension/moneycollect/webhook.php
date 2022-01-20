<?php

class ControllerExtensionMoneycollectWebhook extends Controller {

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

        try{

            $request_body = file_get_contents( 'php://input' );

            $this->mc_model->logger()->addLog('webhook',$request_body);

            $result = json_decode($request_body,true);

            if( isset($result['type']) && strpos($result['type'],'payment') !== false ){

                $data = $result['data'];

                $order_id = $data['orderNo'];

                $this->order = $this->model_checkout_order->getOrder($order_id);

                if( !$this->order ){
                    throw new Exception('the order['. $order_id .'] is not found');
                }

                $this->code = $this->order['payment_code'];

                if( $this->mc_model->canChangeOrderStatus($this->order['order_status']) ){

                    $result = $this->mc_model->api()->get('/payment/'.$data['id']);

                    $this->mc_model->logger()->addLog('webhook get payment',$data['id']);

                    if( $result['error'] ){
                        throw new Exception('webhook get payment error: '.$result['msg']);
                    }

                    $this->mc_model->logger()->addLog('webhook get payment result',$result['response']);

                    $result_arr = json_decode( $result['response'], true);
                    $rs = $this->mc_model->changeOrderStatus($this->model_checkout_order,$result_arr);

                    if( !$rs ){
                        die ('fail');
                    }
                }
            }

        }catch (\Exception $e){
            $this->mc_model->logger()->addBug($e->getMessage());
        }

        echo 'success';
    }

}