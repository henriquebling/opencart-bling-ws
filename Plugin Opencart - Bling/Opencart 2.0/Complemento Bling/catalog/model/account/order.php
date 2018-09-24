<?php

	#################################################
	################  COMPLEMENTO BLING #############
	#################################################
	
	public function getAllOrders($start = 0, $limit = 20) {
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 1;
		}
	
		$query = $this->db->query("SELECT o.order_id, o.firstname, o.lastname,o.email, o.telephone, o.total, o.currency_code, o.currency_value, o.customer_id,o.date_added,o.payment_firstname,o.payment_lastname,o.payment_company,o.payment_address_1,o.payment_address_2,o.payment_city,o.payment_postcode,o.payment_country,o.payment_zone,o.payment_method,o.payment_code,o.shipping_firstname,o.shipping_lastname,o.shipping_company,o.shipping_address_1,o.shipping_address_2,o.shipping_city,o.shipping_postcode,o.shipping_country,o.shipping_zone,o.shipping_method,o.shipping_code,o.comment, os.name as status
								   FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "order_status os ON (o.order_status_id = os.order_status_id) 
								   WHERE o.order_status_id > '0'  ORDER BY o.order_id DESC LIMIT " . (int)$start . "," . (int)$limit
								  );
		return $query->rows;
		
	}
	
	//ORDERS BY FILTERS
	public function getAllOrdersFilters($start = 0, $limit = 20, $filters) {
		if ($start < 0) {
			$start = 0;
		}
		if ($limit < 1) {
			$limit = 1;
		}
		$filters = urldecode($filters);
		$filter = explode('|', $filters);
		
		$startDate = $filter[0];
		$finishDate = $filter[1];
		$status = $filter[2];
		
		if(empty($startDate)){
			$startDate = '1969-01-01';
		}
		if(empty($finishDate)){
			$finishDate = date('Y-m-d');
		}
		$sql = "SELECT o.order_id, o.firstname, o.lastname, o.email, o.telephone, o.total, o.currency_code, o.currency_value, o.customer_id,o.date_added,o.payment_firstname,o.payment_lastname,o.payment_company,o.payment_address_1,o.payment_address_2,o.payment_city,o.payment_postcode,o.payment_country,o.payment_zone,o.payment_method,o.payment_code,o.shipping_firstname,o.shipping_lastname,o.shipping_company,o.shipping_address_1,o.shipping_address_2,o.shipping_city,o.shipping_postcode,o.shipping_country,o.shipping_zone,o.shipping_method,o.shipping_code,o.comment, os.name as status
								   FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "order_status os ON (o.order_status_id = os.order_status_id)
								   WHERE ";


		/*$sql = "SELECT o.order_id, o.firstname, o.lastname, o.email, o.telephone, o.total, o.currency_code, o.currency_value, o.customer_id, o.date_added, o.payment_firstname, o.payment_lastname, 				o.payment_company, o.payment_address_1, o.payment_address_2, o.payment_city, o.payment_postcode, o.payment_country, o.payment_zone, o.payment_method, o.payment_code, 				o.shipping_firstname, o.shipping_lastname, o.shipping_company, o.shipping_address_1, o.shipping_address_2, o.shipping_city, o.shipping_postcode, o.shipping_country, 				o.shipping_zone,o.shipping_method, o.shipping_code,o.comment, os.name as status, oo.name as nomeVariacao, oo.value as valorVariacao, opov.price as precoVariacao, opov.price_prefix, 				opov.weight as weightVariacao , opov.weight_prefix

			FROM `oc_order` o LEFT JOIN oc_order_status os ON (o.order_status_id = os.order_status_id) LEFT JOIN oc_order_option oo ON (o.order_id = oo.order_id)  LEFT JOIN oc_product_option_value 				opov ON (oo.product_option_value_id = opov.product_option_value_id)

			WHERE ";*/



		if($status == 'tds'){
			$sql .= "o.order_status_id > 0";
		}else{
			$sql .= "o.order_status_id = '".$status."'";
		}
		$sql .= " AND ( o.date_added BETWEEN '".$startDate."' AND '".$finishDate."')  ORDER BY o.order_id DESC LIMIT " . (int)$start . "," . (int)$limit;
		
		$query = $this->db->query($sql);		
		return $query->rows;
	
	}

	//ORDER BY ID
	public function getOrderId($order_id){
		$query = $this->db->query("SELECT o.order_id, o.firstname, o.customer_id, o.lastname,  o.email, o.telephone, os.name as status, o.date_added, o.total, o.currency_code, o.currency_value, o.payment_firstname,o.payment_lastname,o.payment_company,o.payment_address_1,o.payment_address_2,o.payment_city,o.payment_postcode,o.payment_country,o.payment_zone,o.payment_method,o.payment_code,o.shipping_firstname,o.shipping_lastname,o.shipping_company,o.shipping_address_1,o.shipping_address_2,o.shipping_city,o.shipping_postcode,o.shipping_country,o.shipping_zone,o.shipping_method,o.shipping_code,o.comment 
								   FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "order_status os ON (o.order_status_id = os.order_status_id)  
								   WHERE o.order_id = '".$order_id."'"
								);
		return $query->rows;
	}
	
	//GETTING COUPON BY ORDER
	public function getCouponByOrder($order_id){
		$query = $this->db->query("SELECT  ot.value as valueCoupon, ot.code as codeCoupon
								   FROM " . DB_PREFIX . "order_total ot
								   WHERE ot.order_id = '".$order_id."'  AND (ot.code = 'coupon' or  ot.code = 'cupom') 
								   GROUP BY ot.value"
								  );
		return $query->rows;
	}
	
	//GETTING SHIPPING PRICE BY ORDER
	public function getShippingByOrder($order_id){
		$query = $this->db->query("SELECT  ot.value as valueShipping, ot.code as codeShipping
								   FROM " . DB_PREFIX . "order oo, " . DB_PREFIX . "order_total ot
								   WHERE ot.order_id = '".$order_id."' AND ( ot.code ='shipping' or ot.code = 'frete')
							 	   GROUP BY ot.value"
		);
		return $query->rows;
	}
	
	
	//PRODUCTS BY ID ORDER
	public function getProductOrderId($order_id){
		$query = $this->db->query("SELECT o.order_product_id, o.product_id, o.order_id, o.name, o.model, o.quantity, o.price, o.total, o.tax, o.reward, p.sku
					   FROM `" . DB_PREFIX . "order_product` o LEFT JOIN " . DB_PREFIX . "product p ON (o.product_id = p.product_id) 
					   WHERE `order_id` = '".$order_id."'"
					);
		return $query->rows;
	}

	//Get products variations in order
	public function getVariation($parameters){
		$query = $this->db->query("SELECT op.name as variationName, oo.name as nomeTipoVariacao, oo.value as tipoVariacao, opov.price as precoVaricao,  opov.price_prefix as prefixPrecoVaricao,  opov.weight as pesoVaricao, opov.weight_prefix as prefixPesoVaricao
				           FROM " . DB_PREFIX . "order_product op LEFT JOIN " . DB_PREFIX . "order_option oo ON (op.order_product_id = oo.order_product_id ) 
					   LEFT JOIN " . DB_PREFIX . "product_option_value  opov ON (oo.product_option_value_id = opov.product_option_value_id) 
					   WHERE op.order_id = '".$parameters['order_id']."' AND op.order_product_id = '".$parameters['order_product_id']."' AND oo.order_product_id is not null");
		
		return $query->rows;
	}

