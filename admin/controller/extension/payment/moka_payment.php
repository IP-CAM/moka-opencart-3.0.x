<?php

error_reporting(0);


class ControllerExtensionPaymentMokaPayment extends Controller {

    private $error = array();
    private $base_url = "";
    private $order_prefix = "opencart30X_";
    private $module_version = "3.0.0.0";

    public function index() {
        $this->language->load('extension/payment/moka_payment');
        $this->load->model('extension/payment/moka_payment');
        $this->document->setTitle($this->language->get('heading_title'));
        include(DIR_SYSTEM . 'library/mokapayment/mokaconfig.php');
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_moka_payment', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
      		$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit'] = $this->language->get('heading_title');
        $data['link_title'] = $this->language->get('text_link');

        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');

        $data['entry_dealercode'] = $this->language->get('entry_dealercode');
        $data['entry_username'] = $this->language->get('entry_username');
        $data['entry_installement'] = $this->language->get('entry_installement');

        $data['entry_password'] = $this->language->get('entry_password');

        $data['entry_order_status'] = $this->language->get('entry_order_status');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_threed'] = $this->language->get('entry_threed');
        $data['entry_class_responsive'] = $this->language->get('entry_class_responsive');
        $data['entry_class_popup'] = $this->language->get('entry_class_popup');
        $data['entry_installment_options'] = $this->language->get('entry_installment_options');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['entry_moka_test_mode'] = $this->language->get('entry_moka_test_mode');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['order_status_after_payment_tooltip'] = $this->language->get('order_status_after_payment_tooltip');
        $data['order_status_after_cancel_tooltip'] = $this->language->get('order_status_after_cancel_tooltip');
        $data['entry_test_tooltip'] = $this->language->get('entry_test_tooltip');
        $data['entry_cancel_order_status'] = $this->language->get('entry_cancel_order_status');


        $data['message'] = '';
        $data['error_warning'] = '';
        $data['error_version'] = '';

        $error_data_array_key = array(
            'dealercode',
            'username',
            'password'
        );

        if ($this->config->get('payment_moka_payment_rates') == NULL) {
	
            $this->config->set('payment_moka_payment_rates', MokaConfig::setRatesDefault());
		
        }


        if (isset($this->request->get['update_error'])) {
            $data['error_version'] = $this->language->get('entry_error_version_updated');
        } else {
            $this->load->model('extension/payment/moka_payment');
            $versionCheck = $this->model_extension_payment_moka_payment->versionCheck(VERSION, $this->module_version);

            if (!empty($versionCheck['version_status']) AND $versionCheck['version_status'] == '1') {
                $data['error_version'] = $this->language->get('entry_error_version');
                $data['moka_or_text'] = $this->language->get('entry_moka_or_text');
                $data['moka_update_button'] = $this->language->get('entry_moka_update_button');
                $version_updatable = $versionCheck['new_version_id'];
                $data['version_update_link'] = $this->url->link('extension/payment/moka_payment/update', 'user_token=' . $this->session->data['user_token'] . "&version=$version_updatable", true);
            }
        }

        foreach ($error_data_array_key as $key) {
            $data["error_{$key}"] = isset($this->error[$key]) ? $this->error[$key] : '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL'),
            'separator' => false
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true),
            'separator' => ' :: '
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/moka_payment', 'user_token=' . $this->session->data['user_token'] , 'SSL'),
			
            'separator' => ' :: '
        );

        $data['action'] = $this->url->link('extension/payment/moka_payment', 'user_token=' . $this->session->data['user_token'], 'SSL');

        $data['cancel'] = $this->url->link('extension/payment', 'user_token=' . $this->session->data['user_token'], 'SSL');

        $merchant_keys_name_array = array(
            'payment_moka_payment_dealercode',
            'payment_moka_payment_username',
            'payment_moka_payment_password',
            'payment_moka_payment_moka_3d_mode',
            'payment_moka_payment_status',
            'payment_moka_payment_order_status_id',
            'payment_moka_payment_sort_order',
            'payment_moka_payment_installement',
			'payment_moka_payment_test_mode',
			'payment_moka_payment_rates',
            'payment_moka_payment_cancel_order_status_id'
        );

        foreach ($merchant_keys_name_array as $key) {
           $data[$key] = isset($this->request->post[$key]) ? $this->request->post[$key] : $this->config->get($key);
        }

        $data['moka_rates_table'] = MokaConfig::createRatesUpdateForm($this->config->get('payment_moka_payment_rates'));
        $this->load->model('localisation/order_status');
        if ($data['payment_moka_payment_order_status_id'] == '') {
            $data['payment_moka_payment_order_status_id'] = $this->config->get('config_order_status_id');
        }
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/payment/moka_payment', $data));
   
    }

    public function install() {
        $this->load->model('extension/payment/moka_payment');
        $this->model_extension_payment_moka_payment->install();
			if(!isset($this->session->data['moka_update'])){
				$this->load->controller('extension/modification/refresh');
				}
    }

    public function uninstall() {
        $this->load->model('extension/payment/moka_payment');
        $this->model_extension_payment_moka_payment->uninstall();
			if(!isset($this->session->data['moka_update'])){
				$this->load->controller('extension/modification/refresh');
				}
    }

    public function update() {
		
		
        $this->load->model('extension/payment/moka_payment');
        $this->load->language('extension/payment/moka_payment');
        $version_updatable = $this->request->get['version'];
      $updated = $this->model_extension_payment_moka_payment->update($version_updatable);
        if ($updated == 1) {
			$this->load->model('setting/setting');
			$payment_moka_payment_dealercode=$this->config->get('payment_moka_payment_dealercode');
			$payment_moka_payment_username=$this->config->get('payment_moka_payment_username');
			$payment_moka_payment_password=$this->config->get('payment_moka_payment_password');	
			$payment_moka_payment_moka_3d_mode=$this->config->get('payment_moka_payment_moka_3d_mode');	
			$payment_moka_payment_status=$this->config->get('payment_moka_payment_status');	
			$payment_moka_payment_order_status_id=$this->config->get('payment_moka_payment_order_status_id');
			$payment_moka_payment_sort_order=$this->config->get('payment_moka_payment_sort_order');	
			$payment_moka_payment_installement=$this->config->get('payment_moka_payment_installement');	
			$payment_moka_payment_test_mode=$this->config->get('payment_moka_payment_test_mode');	
			$payment_moka_payment_rates=$this->config->get('payment_moka_payment_rates');	
			$payment_moka_payment_cancel_order_status_id=$this->config->get('payment_moka_payment_cancel_order_status_id');	
		
			$this->session->data['moka_update'] =1;
			$this->load->controller('extension/payment/' . 'moka_payment' . '/uninstall');
			$this->load->controller('extension/payment/' . 'moka_payment' . '/install');
			
			$this->config->set('payment_moka_payment_dealercode', $payment_moka_payment_dealercode);
			$this->config->set('payment_moka_payment_username', $payment_moka_payment_username);
			$this->config->set('payment_moka_payment_password', $payment_moka_payment_password);
			$this->config->set('payment_moka_payment_moka_3d_mode', $payment_moka_payment_moka_3d_mode);
			$this->config->set('payment_moka_payment_status', $payment_moka_payment_status);
			$this->config->set('payment_moka_payment_order_status_id', $payment_moka_payment_order_status_id);
			$this->config->set('payment_moka_payment_sort_order', $payment_moka_payment_sort_order);
			$this->config->set('payment_moka_payment_installement', $payment_moka_payment_installement);
			$this->config->set('payment_moka_payment_test_mode', $payment_moka_payment_test_mode);
			$this->config->set('payment_moka_payment_rates', $payment_moka_payment_rates);
			$this->config->set('payment_moka_payment_cancel_order_status_id', $payment_moka_payment_cancel_order_status_id);

		
			unset($this->session->data['moka_update']);
			$this->load->controller('extension/modification/refresh');
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('extension/payment', 'user_token=' . $this->session->data['user_token'], 'SSL'));
        } else {
            $this->response->redirect($this->url->link('extension/payment/moka_payment', 'user_token=' . $this->session->data['user_token'] . "&update_error=$updated", true));
			}
    }

    public function order() {
        $this->language->load('extension/payment/moka_payment');
        $language_id = (int) $this->config->get('config_language_id');
        $this->data = array();
        $order_id = (int) $this->request->get['order_id'];
        $data['user_token'] = $this->request->get['user_token'];
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_DealerPaymentId'] = $this->language->get('text_DealerPaymentId');
        $data['text_sepet_total'] = $this->language->get('text_sepet_total');
        $data['text_odenen'] = $this->language->get('text_odenen');
        $data['text_komisyon'] = $this->language->get('text_komisyon');
        $data['text_taksit_sayi'] = $this->language->get("text_taksit_sayi");
        $data['text_creditcart'] = $this->language->get('text_creditcart');
        $data['text_rescode'] = $this->language->get('text_rescode');

        $moka_order_id = $order_id;
	$payment_moka_payment_test_mode = $this->config->get('payment_moka_payment_test_mode');
	  if ($payment_moka_payment_test_mode == 'OFF') {
	  $url = 'https://service.moka.com/PaymentDealer/GetDealerPaymentTrxDetailList';
	  }else{
		    $url = 'https://service.testmoka.com/PaymentDealer/GetDealerPaymentTrxDetailList';
	  }
        $moka_username = $this->config->get('payment_moka_payment_username');
        $moka_password = $this->config->get('payment_moka_payment_password');
        $moka_dealercode = $this->config->get('payment_moka_payment_dealercode');

        $moka['PaymentDealerAuthentication'] = array(
            'DealerCode' => $moka_dealercode,
            'Username' => $moka_username,
            'Password' => $moka_password,
            'CheckKey' => hash('sha256', $moka_dealercode . 'MK' . $moka_username . 'PD' . $moka_password)
        );
        $moka['PaymentDealerRequest'] = array(
            'DealerPaymentId' => null,
            'OtherTrxCode' => $moka_order_id
        );


        $result = json_decode($this->curlPostExt(json_encode($moka), $url, true));

        $data['DealerPaymentId'] = $result->Data->PaymentDetail->DealerPaymentId;
        $data['sepet_total'] = $result->Data->PaymentDetail->DealerCommissionAmount + $result->Data->PaymentDetail->Amount;
        $data['odenen'] = $result->Data->PaymentDetail->Amount;
        $data['komisyon'] = $result->Data->PaymentDetail->DealerCommissionAmount;
        $data['taksit_sayi'] = $result->Data->PaymentDetail->InstallmentNumber;
        $data['creditcart'] = $result->Data->PaymentDetail->CardNumberFirstSix . 'XXX' . $result->Data->PaymentDetail->CardNumberLastFour . ' - ' . $result->Data->PaymentDetail->CardHolderFullName;
        $data['rescode'] = $result->Data->ResultCode." - ".$result->Data->PaymentTrxDetailList[0]->ResultMessage;


       return $this->load->view('extension/payment/moka_payment_order', $data);
    }

    private function curlPostExt($data, $url, $json = false) {
        $ch = curl_init(); // initialize curl handle
        curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
        if ($json)
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // times out after 4s
        curl_setopt($ch, CURLOPT_POST, 1); // set POST method
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // add POST fields
        if ($result = curl_exec($ch)) { // run the whole process
            curl_close($ch);

            return $result;
        }
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/payment/moka_payment')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        $validation_array = array(
            'dealercode',
            'username',
            'password'
        );

        foreach ($validation_array as $key) {
            if (empty($this->request->post["payment_moka_payment_{$key}"])) {
                $this->error[$key] = $this->language->get("error_$key");
            }
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

    private function _addhistory($order_id, $order_status_id, $comment) {

        $this->load->model('sale/order');
        $this->model_sale_order->addOrderHistory($order_id, array(
            'order_status_id' => $order_status_id,
            'notify' => 1,
            'comment' => $comment
        ));

        return true;
    }

}
