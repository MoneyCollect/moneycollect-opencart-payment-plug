<?php

namespace Moneycollect;

class Helper
{
    var $model;

    var $complete_status = ['processing','requires_capture','succeeded'];

    function __construct ($model) {
        $this->model = $model;
    }

    function getSetting ($key) {
        return $this->model->config->get('payment_'.$key);
    }

    function isTestMode () {
        if( $this->getSetting('moneycollect_general_mode') == '1' ){
            return false;
        }
        return true;
    }

    function getPrKey () {
        if( $this->isTestMode() ){
            return 'Bearer ' . $this->getSetting('moneycollect_general_test_private_key');
        }
        return 'Bearer ' . $this->getSetting('moneycollect_general_live_private_key');
    }

    function getPuKey () {
        if( $this->isTestMode() ){
            return $this->getSetting('moneycollect_general_test_public_key');
        }
        return $this->getSetting('moneycollect_general_live_public_key');
    }

    function goodsDetail ($currency, $products = []) {
        if (!is_array($products) || empty($currency)) return '';

        $product_data = [];

        foreach ($products as $i => $val) {
            if ($i == 10) break;
            $productName    = strlen($val['name']) > 130 ? substr($val['name'], 0, 130) : $val['name'];
            $product_data[] = [
               // 'amount'   => (int) $val['total'],
                'amount'   => 10000,
                'currency' => $currency,
                'name'     => htmlspecialchars($productName, ENT_QUOTES),
                'quantity' => $val['quantity']
            ];
        }

        return $product_data;
    }

    function changeOrderStatus ($data) {
        if ($data['order_id'] > 0) {
            $order_info = $this->model->model_checkout_order->getOrder($data['order_id']);
            $this->model->logger->write('order_status ' . $order_info['order_status_id']);

            if ($order_info['order_status_id'] != $data['status'] && $order_info['order_status_id'] != $this->controller->config->get('payment_moneycollect_general_order_status_success')) {
                $this->model->logger->write('add order history ' . $data['status']);
                $this->model->model_checkout_order->addOrderHistory($this->controller->request->post['orderNo'], $data['status'], $data['message'], FALSE);
            }
        }
    }

    function analysisUrl ($url,$key = 'filename') {
        $data = pathinfo($url);
        return str_replace('.','',$data[$key]);
    }

    function transformAmount ($amount,$currency) {
        switch ($currency){
            case strpos('CLP,ISK,VND,KRW,JPY',$currency) !== false:
                return (int)$amount;
                break;
            case strpos('IQD,KWD,TND',$currency) !== false:
                return (int)($amount*1000);
                break;
            default:
                return (int)($amount*100);
                break;
        }
    }

    function paymentComplete($status) {
        if( in_array($status,$this->complete_status) ){
            return true;
        }
        return false;
    }

    function getCardsList(){
        return array(
            'visa' => 'Visa',
            'mastercard' => 'MasterCard',
            'ae' => 'American Express',
            'jcb' => 'JCB',
            'discover' => 'Discover',
            'diners' => 'Diners Club',
            'maestro' => 'Maestro',
            'union' => 'UnionPay',
        );
    }

}
