<?php

class ControllerFeedRestApi extends Controller {

	private $debugIt = false;
	
	/*
	* Get products
	*/
	public function products() {

		$this->checkPlugin();

		$this->load->model('catalog/product');
	
		$json = array('success' => true, 'products' => array());

		/*check category id parameter*/
		if (isset($this->request->get['category'])) {
			$category_id = $this->request->get['category'];
		} else {
			$category_id = 0;
		}

		$products = $this->model_catalog_product->getProducts(array(
			'filter_category_id'        => $category_id
		));

		foreach ($products as $product) {

			if ($product['image']) {
				$image = $product['image'];
			} else {
				$image = false;
			}

			if ((float)$product['special']) {
				$special = $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')));
			} else {
				$special = false;
			}

			$json['products'][] = array(
					'id'			=> $product['product_id'],
					'name'			=> $product['name'],
					'description'	=> $product['description'],
					'pirce'			=> $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'))),
					'href'			=> $this->url->link('product/product', 'product_id=' . $product['product_id']),
					'thumb'			=> $image,
					'special'		=> $special,
					'rating'		=> $product['rating']
			);
		}

		if ($this->debugIt) {
			echo '<pre>';
			print_r($json);
			echo '</pre>';
		} else {
			$this->response->setOutput(json_encode($json));
		}
	}
		

	/*
	* Get orders
	*/
	public function orders() {

		$this->checkPlugin();
	
		$orderData['orders'] = array();

		$this->load->model('account/order');

		/*check offset parameter*/
		if (isset($this->request->get['offset']) && $this->request->get['offset'] != "" && ctype_digit($this->request->get['offset'])) {
			$offset = $this->request->get['offset'];
		} else {
			$offset 	= 0;
		}

		/*check limit parameter*/
		if (isset($this->request->get['limit']) && $this->request->get['limit'] != "" && ctype_digit($this->request->get['limit'])) {
			$limit = $this->request->get['limit'];
		} else {
			$limit 	= 10000;
		}
		
		/*get all orders of user*/
		$results = $this->model_account_order->getAllOrders($offset, $limit);
		
		$orders = array();

		if(count($results)){
			foreach ($results as $result) {

				$product_total = $this->model_account_order->getTotalOrderProductsByOrderId($result['order_id']);
				$voucher_total = $this->model_account_order->getTotalOrderVouchersByOrderId($result['order_id']);

				$orders[] = array(
						'order_id'		=> $result['order_id'],
						'name'			=> $result['firstname'] . ' ' . $result['lastname'],
						'status'		=> $result['status'],
						'date_added'	=> $result['date_added'],
						'products'		=> ($product_total + $voucher_total),
						'total'			=> $result['total'],
						'currency_code'	=> $result['currency_code'],
						'currency_value'=> $result['currency_value'],
				);
			}

			$json['success'] 	= true;
			$json['orders'] 	= $orders;
		}else {
			$json['success'] 	= false;
		}
		
		if ($this->debugIt) {
			echo '<pre>';
			print_r($json);
			echo '</pre>';

		} else {
			$this->response->setOutput(json_encode($json));
		}
	}	
	
	
	private function checkPlugin() {

		$json = array("success"=>false);

		/*check rest api is enabled*/
		if (!$this->config->get('rest_api_status')) {
			$json["error"] = 'API is disabled. Enable it!';
		}
		
		/*validate api security key*/
		if ($this->config->get('rest_api_key') && (!isset($this->request->get['key']) || $this->request->get['key'] != $this->config->get('rest_api_key'))) {
			$json["error"] = 'Invalid secret key';
		}
		
		if(isset($json["error"])){
			$this->response->addHeader('Content-Type: application/json');
			echo(json_encode($json));
			exit;
		}else {
			$this->response->setOutput(json_encode($json));			
		}	
	}	

}
