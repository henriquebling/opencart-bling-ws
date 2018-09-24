<?php
error_reporting(0);
class ControllerFeedRestApi extends Controller {

	private $debugIt = false;
	private $secretKey = 'bling123'; // Secret Key
	
	/*
	* Get products
	*/

	public function products() {
		$this->checkPlugin();
		$this->load->model('catalog/product');
		$products = $this->model_catalog_product->getAllProduct();
		
		if(count($products)){
			foreach ($products as $product) {

			$variation = $this->model_catalog_product->getVariation($product);
	
				if(empty($variation[0])){
					$variation =  null;
				}
				if(!empty($product['name'])){
					$aProducts[] = array(
									'id'			=> $product['product_id'],
									'name'			=> $product['name'],
									'description'		=> 'b64'.base64_encode($product['description']),
									'model'			=> 'b64'.base64_encode($product['model']),
									'sku'			=> $product['sku'],
									'quantity'		=> $product['quantity'],
									'price'			=> $product['price'],
									'weight'		=> $product['weight'],
									'length'		=> $product['length'],
									'width'			=> $product['width'],
									'height'		=> $product['height'],
									'attribute'		=> $product['attribute'],
									'variation'		=> $variation
									);
				}
			}
			$json['success'] 	= true;
			$json['products'] 	= $aProducts;
		}else {
			$json['success'] 	= false;
			$json['error'] 	= "Problems Getting Products.";
		}
		if ($this->debugIt) {
			echo '<pre>';
			print_r($json);
			echo '</pre>';
		} else {
			$this->response->setOutput(json_encode($json));
		}
	}
	
	
	public function count_products(){
		$this->checkPlugin();
		$this->load->model('catalog/product');
		
		$filters =  $this->getParameter();
		$products = $this->model_catalog_product->getCountProduct();

		foreach ($products as $product) {
			if($product['NrProducts'] > 0){
				$json['success'] 	= true;
				$json['products'] 	= $product['NrProducts'];
			}else {
				$json['success'] 	= false;
				$json['error'] 	= "Problems in Products Count.";
			}
		}
		if ($this->debugIt) {
			echo '<pre>';
			print_r($json);
			echo '</pre>';
		} else {
			$this->response->setOutput(json_encode($json));
		}
	}
	
	//Products by FILTERS
	public function products_filters() {
		$this->checkPlugin();
		$this->load->model('catalog/product');

		$filters =  $this->getParameter();
		$products = $this->model_catalog_product->getAllProductFilters($filters);
		
		if(count($products)){
			foreach ($products as $product) {
				
				$variation = $this->model_catalog_product->getVariation($product);

			if(empty($variation[0])){
				$variation =  null;
			}
				$aProducts[] = array(
								'id'			=> $product['product_id'],
								'name'			=> $product['name'],
								'description'		=> 'b64'.base64_encode($product['description']),
								'model'			=> 'b64'.base64_encode($product['model']),
								'sku'			=> $product['sku'],
								'quantity'		=> $product['quantity'],
								'price'			=> $product['price'],
								'weight'		=> $product['weight'],
								'length'		=> $product['length'],
								'width'			=> $product['width'],
								'height'		=> $product['height'],
								'attribute'		=> $product['attribute'],
								'variation'		=> $variation
								);
			}
			$json['success'] 	= true;
			$json['products'] 	= $aProducts;
		}else {
			$json['success'] 	= false;
			$json['error'] 	= "There are no products on this Period.";
		}
	
		if ($this->debugIt) {
			echo '<pre>';
			print_r($json);
			echo '</pre>';
		} else {
			$this->response->setOutput(json_encode($json));
		}
	}
	
	//insert Products
public function products_insert() {
		$this->checkPlugin();
		$this->load->model('catalog/product');
	
		$parameters =  urldecode($this->getParameter());
		$method = 'GET';
		$link = "https://www.bling.com.br/Integrations/Export/class-opencart-export-product.php?auth=" . base64_encode($this->config->get('rest_api_key')) . "&parameters=". $parameters;

		// create curl resource
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, $link);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
		
		// $return contains the output string
		$return = curl_exec( $ch );
		curl_close($ch);
		$result = json_decode( $return );

		//Obter o erro de url
		$code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		if ( empty( $return ) ) {
			$json['success'] 	= false;
			$json['error'] 	= "cURL HTTP error " . $code;
			$json['url'] 	= $link;
		}else{	

			$products = $this->model_catalog_product->insert_oc_products($result);
			
			if(isset($products['id'])){
				if($products['returnUp']){
					$description  = $this->model_catalog_product->update_oc_description($result, $products['id']);
					$json['desc'] = $description;
				
					if($description){
						$json['idProduto'] = $products['id'];
						$json['success'] = true;
					}else{
						$json['success'] = false;
						$json['error'] 	 = "Problems Saving Descripton Product. ";				
					}
				}else{
					$json['success'] = false;
					$json['error'] 	 = "Problems Updating Product";
				}
			}else{
				foreach ($products as $prod){
					$description  = $this->model_catalog_product->insert_oc_description($result, $prod['maximo']);
					foreach ($description as $desc){
						if($desc['idMax'] != $prod['maximo']){
							$this->model_catalog_product->delete_oc_products($prod['maximo']);
							$json['success'] = false;
							$json['error'] 	 = "Problems Saving Products.";
						}else{
							$json['idProduto'] = $prod['maximo'];
							$json['success'] = true;
						}	
					}

				}
			}
		}
		if ($this->debugIt) {
			echo '<pre>';
			print_r($json);
			echo '</pre>';
		} else {
			$this->response->setOutput(json_encode($json));
		}
	}

	//products_stock
	public function products_stock() {
		$this->checkPlugin();
		$this->load->model('catalog/product');
	
		$parameters =  urldecode($this->getParameter());
		$exp = explode("|", $parameters);
		
		$tipo = $exp[0];
		$id = $exp[1];
		$qtd = $exp[2];

		if($tipo == 'P' ){
			$products = $this->model_catalog_product->update_stock_product($id, $qtd);
		}else{	
			$products = $this->model_catalog_product->update_stock_variation($id, $qtd);
		}
		
		$json['success'] = $products;

		if ($this->debugIt) {
			echo '<pre>';
			print_r($json);
			echo '</pre>';
		} else {
			$this->response->setOutput(json_encode($json));
		}
	}
	
	//products_price
	public function products_price() {
		$this->checkPlugin();
		$this->load->model('catalog/product');
	
		$parameters =  urldecode($this->getParameter());
		$exp = explode("|", $parameters);
		
		$tipo = $exp[0];
		$id = $exp[1];
		$price = $exp[2];

		if($tipo == 'P' ){
			$products = $this->model_catalog_product->update_price_product($id, $price);
		}else{	
			$products = $this->model_catalog_product->update_price_variation($id, $price);
		}
		
		$json['success'] = $products;

		if ($this->debugIt) {
			echo '<pre>';
			print_r($json);
			echo '</pre>';
		} else {
			$this->response->setOutput(json_encode($json));
		}
	}


	//All orders
	public function orders() {	
		$this->checkPlugin();
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
			$orders   = $this->ParametersReturnOrder($results); 
			$json['success'] 	= true;
			$json['orders'] 	= $orders;			
		}else {
			$json['success'] 	= false;
			$json['error'] 	= "Problems Getting Orders. There are no Orders on this Period.";
		}
		
		if ($this->debugIt) {
			echo '<pre>';
			print_r($json);
			echo '</pre>';

		} else {
			$this->response->setOutput(json_encode($json));
		}
	}
	
	//Orders by FILTERS
	public function orders_filters() {
		$this->checkPlugin();
		$filters =  $this->getParameter();
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
		$results = $this->model_account_order->getAllOrdersFilters($offset, $limit, $filters);

		$orders = array();
		if(count($results)){
			$orders   = $this->ParametersReturnOrder($results); 
			$json['success'] 	= true;
			$json['orders'] 	= $orders;			
		}else {
			$json['success'] 	= false;
			$json['error'] 	= "Problems Getting Products.";
		}
	
		if ($this->debugIt) {
			echo '<pre>';
			print_r($json);
			echo '</pre>';
	
		} else {
			$this->response->setOutput(json_encode($json));
		}
	}

	
	//Order by ID
	public function order_id() {
		$this->checkPlugin();
		$parametro =  $this->getParameter();
		$this->load->model('account/order');	
		//get values of the database
		$results = $this->model_account_order->getOrderId($parametro);
		
		$orders = array();
		if(count($results)){
			$orders   = $this->ParametersReturnOrder($results); 
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
	
	
	
	
	/**
	 * 
	 * Parameters of the functions
	 * 
	 */
	
	public function ParametersReturnProduct(array $params = null){
		$products = $params;
		$produtos = array();
		foreach ($products as $product){
			
			$variation = $this->model_account_order->getVariation($product);


			if(empty($variation[0])){
				$variation = null;	
			}

			$produtos[] = array(
					'order_product_id'  	=>  $product['order_product_id'],
					'product_id'  	    	=>  $product['product_id'],
					'name'		 	=>  $product['name'],
					'model' 	  	=>  $product['model'],
					'sku' 	  		=>  $product['sku'],
					'quantity'    		=>  $product['quantity'],
					'price'  	 	=>  $product['price'],
					'total'  	 	=>  $product['total'],
					'tax'  		 	=>  $product['tax'],
					'rewar'  	 	=>  $product['reward'],
					'variation'		=>  $variation
			);
		}
		return $produtos;		
	}
	
	public function ParametersReturnPayment(array $params = null){	
		$result = $params;
		$payment = array(
				'payment_name'		=> $result['payment_firstname'] ." ". $result['payment_lastname'],
				'payment_company'	=> $result['payment_company'],
				'payment_address_1'	=> $result['payment_address_1'],
				'payment_address_2'	=> $result['payment_address_2'],
				'payment_city'		=> $result['payment_city'],
				'payment_postcode'	=> $result['payment_postcode'],
				'payment_country'	=> $result['payment_country'],
				'payment_zone'		=> $result['payment_zone'],
				'payment_method'	=> $result['payment_method'],
				'payment_code'		=> $result['payment_code']
		);
		return $payment;
	}	
		
	public function ParametersReturnShipping(array $params = null){
		$result = $params;
		
		$shippingValue = $this->model_account_order->getShippingByOrder($result['order_id']);
		if(empty($shippingValue[0]['valueShipping']) || $shippingValue[0]['valueShipping'] == null ){
			$shippingValue[0]['valueShipping'] = 0;
		}
		
		$shipping = array (
				'shipping_name'		=> $result['shipping_firstname'] . " ".$result['shipping_lastname'],
				'shipping_company'	=> $result['shipping_company'],
				'shipping_address_1'	=> $result['shipping_address_1'],
				'shipping_address_2'	=> $result['shipping_address_2'],
				'shipping_city'		=> $result['shipping_city'],
				'shipping_postcode'	=> $result['shipping_postcode'],
				'shipping_country'	=> $result['shipping_country'],
				'shipping_zone'		=> $result['shipping_zone'],
				'shipping_method'	=> $result['shipping_method'],
				'shipping_code'		=> $result['shipping_code'],
				'shipping_price'	=> $shippingValue[0]['valueShipping']
		);
		return $shipping;
	}	
		
	public function ParametersReturnOrder(array $params = null){
		$results = $params;
		foreach ($results as $result) {
			$product_total = $this->model_account_order->getTotalOrderProductsByOrderId($result['order_id']);
			$voucher_total = $this->model_account_order->getTotalOrderVouchersByOrderId($result['order_id']);
			$products = $this->model_account_order->getProductOrderId($result['order_id']);
			$couponDiscount =  $this->model_account_order->getCouponByOrder($result['order_id']);

			//shipping price and/or discount coupon
			if(empty($couponDiscount[0]['valueCoupon']) || $couponDiscount[0]['valueCoupon'] == null ){
				$couponDiscount[0]['valueCoupon'] = 0;
			}

			$productsOrder = $this->ParametersReturnProduct($products);
			$payment  = $this->ParametersReturnPayment($result);
			$shipping = $this->ParametersReturnShipping($result);
			
			$orders[] = array(
					'order_id'		=> $result['order_id'],
					'name'			=> $result['firstname'] . ' ' . $result['lastname'],
					'customer_id'		=> $result['customer_id'],
					'email'			=> $result['email'],
					'telephone'		=> $result['telephone'],
					'status'		=> $result['status'],
					'date_added'		=> $result['date_added'],
					'products_totals'	=> ($product_total + $voucher_total),
					'products'		=> $productsOrder,
					'payment'		=> $payment,
					'shipping'		=> $shipping,
					'discount'		=> $couponDiscount[0]['valueCoupon'],
					'total'			=> $result['total'],
					'comment'		=> $result['comment'],
					'currency_code'		=> $result['currency_code'],
					'currency_value'	=> $result['currency_value'],
			);
		}	
		return $orders;
	}

	
	/**
	 * 
	 * get GET parameters
	 * 
	 */
	private function getParameter(){
		
		if(isset($this->request->get['parametro']) && !empty($this->request->get['parametro'])){
			$parametro = $this->request->get['parametro'];
		}else{
			$parametro = null;			
		}		
	
		return $parametro;
	}
	
	private function checkPlugin() {

		$json = array("success"=>false);

		/*validate api security key*/
		if($this->request->get['key'] != $this->secretKey){
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

