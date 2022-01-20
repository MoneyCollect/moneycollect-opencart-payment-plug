<?php

include 'moneycollect.php';

class ControllerExtensionPaymentMoneycollectCreditcard extends ControllerExtensionPaymentMoneycollect {

    var $code = 'moneycollect_creditcard';
    var $title = 'Credit Card';

    public function index()
    {
        $this->load->language('extension/payment/'.$this->code);
        parent::index();
    }

    protected function settingOption($payment_settings = array()) {

        $settings[] =  array(
            'name' => 'payment_'.$this->code.'_checkout_mode',
            'title' => $this->language->get('entry_checkout_mode'),
            'type' => 'select',
            'option' => array(
                array(
                    'text' => $this->language->get('entry_checkout_in_page'),
                    'value' => '0'
                ),
                array(
                    'text' => $this->language->get('entry_checkout_hosted'),
                    'value' => '1'
                )
            ),
            'description' => '',
            'value' => $this->mc_data->getValue('payment_'.$this->code.'_checkout_mode', 0)
        );
        $settings[] =  array(
            'name' => 'payment_'.$this->code.'_form_style',
            'title' => $this->language->get('entry_form_style'),
            'type' => 'select',
            'option' => array(
                array(
                    'text' => $this->language->get('entry_style_line'),
                    'value' => 'inner'
                ),
                array(
                    'text' => $this->language->get('entry_style_block'),
                    'value' => 'block'
                )
            ),
            'description' => '',
            'value' => $this->mc_data->getValue('payment_'.$this->code.'_form_style', 'inner')
        );
        $settings[] =  array(
            'name' => 'payment_'.$this->code.'_save_cards',
            'title' => $this->language->get('entry_saved_cards'),
            'type' => 'select',
            'option' => array(
                array(
                    'text' => $this->language->get('text_disabled'),
                    'value' => 'off'
                ),
                array(
                    'text' => $this->language->get('text_enabled'),
                    'value' => 'on'
                )
            ),
            'description' => $this->language->get('text_saved_cards'),
            'value' => $this->mc_data->getValue('payment_'.$this->code.'_save_cards','off')
        );

        $settings[] =  array(
            'name' => 'payment_'.$this->code.'_pre_auth',
            'title' => $this->language->get('entry_pre_auth'),
            'type' => 'select',
            'option' => array(
                array(
                    'text' => $this->language->get('text_disabled'),
                    'value' => 'off'
                ),
                array(
                    'text' => $this->language->get('text_enabled'),
                    'value' => 'on'
                )
            ),
            'description' => '',
            'value' => $this->mc_data->getValue('payment_'.$this->code.'_pre_auth','off')
        );

        $settings[] = array(
            'name' => 'payment_'.$this->code.'_statement_descriptor',
            'title' => $this->language->get('entry_statement_descriptor'),
            'type' => 'text',
            'description' => $this->language->get('text_statement_descriptor'),
            'value' => $this->mc_data->getValue('payment_'.$this->code.'_statement_descriptor')
        );

        $card_arr = [
            'visa' => 'Visa',
            'mastercard' => 'MasterCard',
            'ae' => 'American Express',
            'jcb' => 'JCB',
            'discover' => 'Discover',
            'diners' => 'Diners Club',
            'maestro' => 'Maestro',
            'union' => 'UnionPay',
        ];

        foreach ($card_arr as $key => $value){
            $settings[] = array(
                'name'  => 'payment_' . $this->code . '_' . $key,
                'title' => $value,
                'type'  => 'select',
                'option' => array(
                    array(
                        'text' => $this->language->get('text_disabled'),
                        'value' => 'off'
                    ),
                    array(
                        'text' => $this->language->get('text_enabled'),
                        'value' => 'on'
                    )
                ),
                'value' => $this->mc_data->getValue('payment_' . $this->code . '_' . $key,'off')
            );
        }

        parent::settingOption($settings);

    }


}
