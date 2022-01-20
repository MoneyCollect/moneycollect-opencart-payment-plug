<?php 
class ModelExtensionPaymentMoneycollectCreditCard extends Model {

    var $code = 'moneycollect_creditcard';
    var $mc_model;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('extension/module/moneycollect');
        $this->mc_model = $this->model_extension_module_moneycollect;
    }

    public function getMethod($address) {

		$method_data = [];

		if ( $this->mc_model->helper()->getSetting($this->code.'_status') == '1' ) {
            $method_data = array(
                'code'       => $this->code,
                'title'      => $this->mc_model->helper()->getSetting($this->code.'_title'),
                'sort_order' => $this->mc_model->helper()->getSetting($this->code.'_sort_order')
            );

		    if( $this->mc_model->helper()->getSetting($this->code.'_checkout_mode') == '0' ){
                $this->loadResources();
            }

		}

    	return $method_data;
  	}

	private function loadResources() {
  	    $api = $this->mc_model->api();
  		echo '<script src="'.$api::JSSDK.'"></script>';
        //$this->document->addScript($api::JSSDK);
	}
}
