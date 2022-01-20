<?php 
class ModelExtensionModuleMoneycollect extends Model {

    var $api;
    var $mc_customer;
    var $helper;
    var $logger;


    public function __construct($registry){
        parent::__construct($registry);
        $this->api = new Moneycollect\Api($this);
        $this->mc_customer = new Moneycollect\customer($this,$this->customer->getId());

        if( $this->mc_customer->hasMcId() ){
            $result = $this->api->get('/customers/'.$this->mc_customer->getMcId());
            $result_arr = json_decode( $result['response'], true);
            if( $result_arr['code'] !== 'success' ){
                $this->mc_customer->delMcId();
            }
        }

        $this->helper = new Moneycollect\Helper($this);
        $is_logger = $this->helper->getSetting('moneycollect_general_logging');
        $this->logger = new Moneycollect\Logger($is_logger);
    }

    function api(){
        return $this->api;
    }

    function customer(){
        return $this->mc_customer;
    }

    function helper(){
        return $this->helper;
    }

    function logger(){
        return $this->logger;
    }

    function canChangeOrderStatus($order_status){

        if( in_array($order_status,[$this->helper->getSetting('moneycollect_general_order_status_success')]) ){
            return false;
        }

        if( in_array($order_status,[$this->helper->getSetting('moneycollect_general_order_status_failed'),$this->helper->getSetting('moneycollect_general_order_status_pending')]) ){
            return true;
        }

        if( in_array($order_status,['Complete','Processed','Processing','Refunded','Shipped']) ){
            return false;
        }

        return true;
    }

    function getChangeOrderStatus($result_status){
        switch ( $result_status ){
            case 'succeeded':
                $new_status = $this->helper->getSetting('moneycollect_general_order_status_success');
                break;
            case 'failed':
                $new_status = $this->helper->getSetting('moneycollect_general_order_status_failed');
                break;
            case 'requires_payment_method':
            case 'requires_confirmation':
            case 'requires_action':
            case 'processing':
                $new_status = $this->helper->getSetting('moneycollect_general_order_status_pending');
                break;
            default:
                $new_status = '';
        }
        return $new_status;
    }

    function changeOrderStatus($order,$result){

        $data = $result['data'];

        $order_status = $this->getChangeOrderStatus($data['status']);

        if( empty($order_status) ){
            $this->logger->addBug('get new status is null :'.$data['status']);
            return false;
        }

        $comment = '<b>Payment</b> : Moneycollect' . "\r\n";
        $comment .= '<b>Type</b> : ' . $data['paymentMethodDetails']['type'];
        if( $data['paymentMethodDetails']['type'] === 'card' ){
            $comment .= '('. $data['paymentMethodDetails']['card']['brand'] .')';
        }
        $comment .= "\r\n";
        $comment .= '<b>Transaction</b> : '.$data['id'] ."\r\n";
        $comment .= '<b>Status</b> : '.$data['status'] ."\r\n";

        if( $data['errorMessage'] ){
            $comment .= '<b>Message</b> : '.$data['errorMessage'] ."\r\n";
        }

        $order->addOrderHistory($data['orderNo'], $order_status, $comment, false);

        return true;

    }


}
