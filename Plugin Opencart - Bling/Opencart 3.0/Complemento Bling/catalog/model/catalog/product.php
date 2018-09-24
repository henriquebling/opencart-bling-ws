<?php


	#################################################
	################  COMPLEMENTO BLING #############
	#################################################

	//Plugin
	public function getAllProduct(){
		$query = $this->db->query("SELECT pd.product_id, pd.name, pd.description, p.model, p.sku, p.quantity, p.price, p.weight, p.length, p.width, p.height,p.date_added, pa.text AS attribute 					
								   FROM " . DB_PREFIX . "product p LEFT JOIN  " . DB_PREFIX . "product_description pd ON pd.product_id = p.product_id LEFT JOIN  " . DB_PREFIX . "product_attribute pa ON  pa.product_id = p.product_id 							   
								   GROUP BY p.product_id"
							      );
		return $query->rows;
	}
	
	//Products by filters
	public function getAllProductFilters($filters){
		$filters = urldecode($filters);
		$filter = explode('|', $filters);
		$startDate = $filter[0];
		$finishDate = $filter[1];
		
		if($startDate == date('Y-m-d')){
			$d = date('d') +1;
			$y = date('Y');
			$m = date('m'); 
			$finishDate = $y."-".$m ."-".$d;
		}

		$query = $this->db->query("SELECT pd.product_id, pd.name, pd.description, p.model, p.sku, p.quantity, p.price, p.weight, p.length, p.width, p.height,p.date_added, pa.text AS attribute 					
								   FROM " . DB_PREFIX . "product p LEFT JOIN  " . DB_PREFIX . "product_description pd ON pd.product_id = p.product_id LEFT JOIN  " . DB_PREFIX . "product_attribute pa ON  pa.product_id = p.product_id 							   
								   WHERE p.date_added BETWEEN '".$startDate."' AND '".$finishDate."'  AND p.status = '1'   							  
								   GROUP BY p.product_id"
		);
		return $query->rows;
	}
	
	
	//Products by filters
	public function getCountProduct(){
		$query = $this->db->query("SELECT COUNT(product_id) as NrProducts FROM " . DB_PREFIX . "product ");
		return $query->rows;
	}

	//Insert products
	public function insert_oc_products($parameter){
			if(strlen($parameter->descricaoComplementar) > 64){
				$parameter->descricaoComplementar = substr($parameter->descricaoComplementar, 0, 64);
			}
						
			$idProd = (int)$parameter->id;
			if($idProd == 0){
				$sql = $this->db->query("INSERT INTO " . DB_PREFIX . "product (model, sku, upc, ean, jan, isbn, mpn, location, quantity, stock_status_id, image, manufacturer_id, shipping, price, points, tax_class_id, date_available, weight, weight_class_id, length, width, height, length_class_id, subtract, minimum, sort_order, status, viewed, date_added, date_modified ) 
					     		 VALUES ('".strip_tags($parameter->descricaoComplementar)."', '".strip_tags($parameter->codigo)."','','','','','','','".$parameter->estoqueAtual."','1','','0','0','".$parameter->preco."','0', '9','".date('Y-m-d')."', '".$parameter->peso."',	'1','".$parameter->profundidadeProduto."','".$parameter->larguraProduto."','".$parameter->alturaProduto."','1','0','1','0','1','0', NOW(), NOW())");
				
				$query = $this->db->query("SELECT model, sku, quantity, MAX(product_id) as maximo FROM `" . DB_PREFIX . "product`");
				return $query->rows;
			}else{
				$sql = $this->db->query("UPDATE " . DB_PREFIX . "product SET	model = '".strip_tags($parameter->descricaoComplementar)."', sku = '".strip_tags($parameter->codigo)."', quantity = '".$parameter->estoqueAtual."', price = '".$parameter->preco."', weight = '".$parameter->peso."', length = '".$parameter->profundidadeProduto."', width = '".$parameter->larguraProduto."', height = '".$parameter->alturaProduto."', date_modified = NOW()  WHERE product_id = '" . $idProd."'");
				return array('id' => $idProd, 'returnUp' => $sql);			
			}
	}		
	
	public function update_oc_description($parameter, $id){
		$sql = $this->db->query("UPDATE " . DB_PREFIX . "product_description SET `name` =  '".strip_tags($parameter->nome)."', `description` = '".htmlentities($parameter->descricaoComplementar)."' WHERE `product_id` = '".$id."'");
		return $sql;
	}
	
	public function insert_oc_description($parameter, $id){
		$sql = $this->db->query("INSERT INTO " . DB_PREFIX . "product_description (`product_id`, `language_id`, `name`, `description`, `tag`, `meta_title`, `meta_description`, `meta_keyword`)
			   		 VALUES('".$id."','". (int)$this->config->get('config_language_id')."','".strip_tags($parameter->nome)."','".htmlentities($parameter->descricaoComplementar)."','','','','')");
		
		$query = $this->db->query("SELECT MAX(product_id) as idMax FROM " . DB_PREFIX . "product_description");
		return $query->rows;
	}

	public function delete_oc_products($id){
		$del = $this->db->query("DELETE FROM `" . DB_PREFIX . "product` WHERE product_id = '".$id."'");
		return true;
	}

	//Get products variations
	public function getVariation($parameters){

		$query = $this->db->query("SELECT pd.name as variationName, od.name as nomeTipoVariacao,  ovd.name as tipoVariacao, pov.quantity as quantidadeVariacao, pov.price as precoVaricao, pov.price_prefix as prefixPrecoVaricao,  pov.weight as   pesoVaricao, pov.weight_prefix as prefixPesoVaricao, pov.product_option_value_id as idVariation
					   FROM " . DB_PREFIX . "option_description od 
					   LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON ( od.option_id = ovd.option_id )
					   LEFT JOIN " . DB_PREFIX . "product_option_value pov ON ( ovd.option_value_id = pov.option_value_id )
					   LEFT JOIN " . DB_PREFIX . "product_description pd ON ( pov.product_id = pd.product_id )
			   		   WHERE pd.product_id = '".$parameters['product_id']."'");
				return $query->rows;
	}
	
	public function update_stock_product($id, $qtd){
		$up = $this->db->query("UPDATE `" . DB_PREFIX . "product` SET `quantity`= '" . $qtd . "' WHERE  `product_id` = '" . $id . "'");
		if($up){
			return true;
		}else{
			return false;
		}	
	}
	
	public function update_stock_variation($id, $qtd){
		$up = $this->db->query("UPDATE `" . DB_PREFIX . "product_option_value` SET `quantity`= '" . $qtd . "' WHERE  `product_option_value_id` = '" . $id . "' ");
		if($up){
			return true;
		}else{
			return false;
		}		
	}

	public function update_price_product($id, $price){
		$up = $this->db->query("UPDATE `" . DB_PREFIX . "product` SET `price`= '" . $price . "' WHERE  `product_id` = '" . $id . "'");
		if($up){
			return true;
		}else{
			return false;
		}	
	}
	
	public function update_price_variation($id, $price){
		$up = $this->db->query("UPDATE `" . DB_PREFIX . "product_option_value` SET `price`= '" . $price . "' WHERE  `product_option_value_id` = '" . $id . "' ");
		if($up){
			return true;
		}else{
			return false;
		}		
	}

