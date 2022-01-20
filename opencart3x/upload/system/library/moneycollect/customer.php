<?php

namespace Moneycollect;

class customer
{
    var $model;
    var $customer_id;
    var $mc_id;

    function __construct($model,$id) {
        $this->model = $model;
        if( $id ){
            $this->customer_id = $id;
            $this->mc_id = $this->getMcId();
        }
    }


    function getMcId() {

        if( !empty($this->mc_id) ){
            return $this->mc_id;
        }

        $customer_data = $this->model->db->query("SELECT moneycollect_customer_id FROM `" . DB_PREFIX . "moneycollect_customer` WHERE customer_id = '". $this->customer_id ."' ");

        $mc_id = '';
        if (isset($customer_data->row['moneycollect_customer_id']) && !empty($customer_data->row['moneycollect_customer_id'])) {
            $mc_id = $customer_data->row['moneycollect_customer_id'];
        }

        return $mc_id;
    }

    function createMcId($mc_id) {
        $this->model->db->query("INSERT INTO `" . DB_PREFIX . "moneycollect_customer` (customer_id, moneycollect_customer_id) VALUES ('" . $this->customer_id . "','" . $mc_id . "') ");
        $this->mc_id = $mc_id;
    }

    function delMcId(){
        $this->model->db->query("DELETE FROM `" . DB_PREFIX . "moneycollect_customer`  WHERE moneycollect_customer_id ='" . $this->mc_id . "' ");
        $this->mc_id = '';
    }

    function hasMcId(){

        if( empty($this->mc_id) ){
            return false;
        }

        return true;
    }


}