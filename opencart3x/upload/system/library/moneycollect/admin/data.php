<?php
namespace Moneycollect\Admin;

class Data
{
    private $controller;
    var $edition = 'v1.0.3';
    var $url;

    function __construct($controller){
        $this->controller = $controller;
    }

    function language($key){
        return $this->controller->language->get($key);
    }

    function getAdminSettings() {
        $order_status_option = $this->orderStatusOption();

        $settings = array(
            'general' => array(
                array(
                    'name' => 'payment_moneycollect_general_edition',
                    'title' => $this->language('entry_edition'),
                    'type' => 'hidden',
                    'description' => '',
                    'value' => $this->edition
                ),
                array(
                    'name' => 'payment_moneycollect_general_mode',
                    'title' => $this->language('entry_transaction_mode'),
                    'type' => 'select',
                    'option' => array(
                        array(
                            'text' => $this->language('entry_transaction_test'),
                            'value' => '0'
                        ),
                        array(
                            'text' => $this->language('entry_transaction_live'),
                            'value' => '1'
                        )
                    ),
                    'description' => '',
                    'value' => $this->getValue('payment_moneycollect_general_mode', 0)
                ),
                array(
                    'name' => 'payment_moneycollect_general_live_public_key',
                    'title' => $this->language('entry_live_public_key'),
                    'type' => 'text',
                    'description' => '',
                    'value' => $this->getValue('payment_moneycollect_general_live_public_key')
                ),
                array(
                    'name' => 'payment_moneycollect_general_live_private_key',
                    'title' => $this->language('entry_live_private_key'),
                    'type' => 'text',
                    'description' => '',
                    'value' => $this->getValue('payment_moneycollect_general_live_private_key')
                ),
                array(
                    'name' => 'payment_moneycollect_general_test_public_key',
                    'title' => $this->language('entry_test_public_key'),
                    'type' => 'text',
                    'description' => '',
                    'value' => $this->getValue('payment_moneycollect_general_test_public_key')
                ),
                array(
                    'name' => 'payment_moneycollect_general_test_private_key',
                    'title' => $this->language('entry_test_private_key'),
                    'type' => 'text',
                    'description' => '',
                    'value' => $this->getValue('payment_moneycollect_general_test_private_key')
                ),

                array(
                    'name' => 'payment_moneycollect_general_order_status_pending',
                    'title' => $this->language('entry_order_status_pending'),
                    'type' => 'select',
                    'option' => $order_status_option,
                    'description' => '',
                    'value' => $this->getValue('payment_moneycollect_general_order_status_created','1')
                ),
                array(
                    'name' => 'payment_moneycollect_general_order_status_success',
                    'title' => $this->language('entry_order_status_success'),
                    'type' => 'select',
                    'option' => $order_status_option,
                    'description' => '',
                    'value' => $this->getValue('payment_moneycollect_general_order_status_success','15')
                ),
                array(
                    'name' => 'payment_moneycollect_general_order_status_failed',
                    'title' => $this->language('entry_order_status_failed'),
                    'type' => 'select',
                    'option' => $order_status_option,
                    'description' => '',
                    'value' => $this->getValue('payment_moneycollect_general_order_status_failed','10')
                ),
                array(
                    'name' => 'payment_moneycollect_general_logging',
                    'title' => $this->language('entry_logging'),
                    'type' => 'select',
                    'option' => array(
                        array(
                            'text' => $this->language('text_no'),
                            'value' => '0'
                        ),
                        array(
                            'text' => $this->language('text_yes'),
                            'value' => '1'
                        )
                    ),
                    'description' => sprintf($this->language('entry_logging_description'),$this->controller->log_path),
                    'value' => $this->getValue('payment_moneycollect_general_logging')
                ),
                array(
                    'name' => 'payment_moneycollect_general_webhook',
                    'title' => $this->language('entry_webhook'),
                    'type' => 'hidden',
                    'description' => sprintf($this->language('entry_webhook_description'),$this->controller->webhook,$this->controller->webhook),
                ),
            ),
            'payment' => array(
                array(
                    'name' => 'payment_'.$this->controller->code.'_status',
                    'title' => $this->language('entry_status'),
                    'type' => 'select',
                    'option' => array(
                        array(
                            'text' => $this->language('text_disabled'),
                            'value' => '0'
                        ),
                        array(
                            'text' => $this->language('text_enabled'),
                            'value' => '1'
                        )
                    ),
                    'description' => '',
                    'value' => $this->getValue('payment_'.$this->controller->code.'_status','1')
                ),
                array(
                    'name' => 'payment_'.$this->controller->code.'_sort_order',
                    'title' => $this->language('entry_sort_order'),
                    'type' => 'text',
                    'description' => '',
                    'value' => $this->getValue('payment_'.$this->controller->code.'_sort_order','1')
                ),
                array(
                    'name' => 'payment_'.$this->controller->code.'_title',
                    'title' => $this->language('entry_payment_title'),
                    'type' => 'text',
                    'description' => '',
                    'value' => $this->getValue('payment_'.$this->controller->code.'_title',$this->controller->title)
                ),

            )
        );

        return $settings;
    }

    function orderStatusOption(){
        $this->controller->load->model('localisation/order_status');
        $order_statuses = $this->controller->model_localisation_order_status->getOrderStatuses();
        $arr = array();
        foreach ($order_statuses as $key => $val){
            $arr[] = array(
                'text' => $val['name'],
                'value' => $val['order_status_id']
            );
        }
        return $arr;
    }

    function getValue($key,$default = ''){
        $value = key_exists($key,$this->controller->request->post)?trim($this->controller->request->post[$key]):$this->controller->config->get($key);
        if( is_null($value) ) $value = $default;
        return $value;
    }
}
