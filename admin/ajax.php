<?php
header('Access-Control-Allow-Origin: *');
include("../config.php");
include("../shopify_api.php");

$current_date = date("Y-m-d H:i:s");

$type = "";
if(isset($_REQUEST['type']) && $_REQUEST['type'] != "") {
    $type = $_REQUEST["type"];
    $type = trim($type);
}

if($type == 'b_add') {
    $bTitle= $_REQUEST['bTitle'];
	$bTitleInternal= $_REQUEST['bTitleInternal'];
    $b_pro_list_string = $_REQUEST['b_pro_list'];
    $b_target_pro =$_REQUEST['b_target_pro'];
    $b_target_pro_handle = $_REQUEST['b_target_pro_h'];
	$b_banner_visiblity = $_REQUEST['b_banner_visiblity'];
	$b_sub_title = $_REQUEST['b_sub_title'];
	$b_in_page_banner = (int)$_REQUEST['b_in_page_banner'];
	$b_template = $_REQUEST['b_Templates'];

	

    $sql_insert = "Insert into banner_mst(`b_template`,`b_in_page_banner`,`shop`,visible_on,b_title,b_sub_title,b_title_internal,created_date,updated_time,b_product_list,b_target_list, b_target_list_handle) ".
            "VALUES('$b_template',$b_in_page_banner,'".mysql_real_escape_string($shop)."','".mysql_real_escape_string($b_banner_visiblity)."','".mysql_real_escape_string($bTitle)."','".mysql_real_escape_string($b_sub_title)."','".mysql_real_escape_string($bTitleInternal)."','".$current_date."','".$current_date."','".$b_pro_list_string."','".$b_target_pro."', '".mysql_real_escape_string($b_target_pro_handle)."')";
    #echo $sql_insert;
    $result_sql = mysql_query($sql_insert);
    if (!$result_sql) {
        echo 'Invalid query: '.mysql_error();
    } else {
        $banner_id = mysql_insert_id();
		$b_pro_list_array = explode(",",$b_pro_list_string);
		foreach($b_pro_list_array as $b_pro_list){
			$product_id = str_replace("p_","",$b_pro_list);
			$coll_id = str_replace("c_","",$b_pro_list);

			/* Add Metafield for Upsell List */        
			$api_call_str = "";        
			$MetaData = array("metafield" => array("namespace" => "p_upsell_target", "key" => "p_upsell_list", "value" => $b_target_pro_handle, "value_type" => "string"));
			if(strpos($b_pro_list,'p_') !== false) { 
				$api_call_str = "/admin/products/".$product_id."/metafields.json";
			} else {
				$api_call_str = "/admin/custom_collections/".$coll_id."/metafields.json";
			}

			try{
				$add_meta = $sc->call('POST', $api_call_str, $MetaData);
			} catch (exception $e) {

			}        

			/* Add Metafield for Upsell Type */
			$api_call_str = "";
			$cu_type_value_str = $b_pro_list."###".$bTitle."###".$banner_id."###".$b_banner_visiblity."###".$b_sub_title."###".$b_template."###".$b_in_page_banner;
			$MetaData = array("metafield" => array("namespace" => "cu_upsell_type", "key" => "cu_upsell_value", "value" => $cu_type_value_str, "value_type" => "string"));
			if(strpos($b_pro_list,'p_') !== false) { 
				$api_call_str = "/admin/products/".$product_id."/metafields.json";
			} else {
				$api_call_str = "/admin/custom_collections/".$coll_id."/metafields.json";
			}

			try{
				$add_meta = $sc->call('POST', $api_call_str , $MetaData);
			} catch (exception $e) {

			}
        }
        echo "success";
    }
}

if($type == 'b_update') {
    $bTitle= $_REQUEST['bTitle'];
	$bTitleInternal= $_REQUEST['bTitleInternal'];
    $b_pro_list_string = $_REQUEST['b_pro_list'];
    $b_target_pro =$_REQUEST['b_target_pro'];
    $b_target_pro_handle = $_REQUEST['b_target_pro_h'];
	$b_banner_visiblity = $_REQUEST['b_banner_visiblity'];
	$b_sub_title = $_REQUEST['b_sub_title'];
	$b_in_page_banner = (int)$_REQUEST['b_in_page_banner'];
	$b_template = $_REQUEST['b_Templates'];
	
    $bid = base64_decode($_REQUEST['id']);
    $old_upsell_string = trim($_REQUEST['b_pro_list_old']);
    
    $sql_update = "Update banner_mst set b_in_page_banner=$b_in_page_banner,b_template='$b_template',b_sub_title = '".$b_sub_title."',b_title_internal = '".$bTitleInternal."',visible_on='".$b_banner_visiblity."', b_title='".$bTitle."',b_product_list='".$b_pro_list_string."',b_target_list='".$b_target_pro."', b_target_list_handle='".mysql_real_escape_string($b_target_pro_handle)."',updated_time='".$current_date."' where bid='".$bid."' and shop='".mysql_real_escape_string($shop)."'";
    #echo $sql_update;            
    $result_sql = mysql_query($sql_update);
    if (!$result_sql) {
        echo 'Invalid query: '.mysql_error();
    } else {
        $banner_id = $bid;

		$old_upsell_array = explode(",",$old_upsell_string);
		foreach($old_upsell_array as $old_upsell){
			$old_upsell_pro = str_replace("p_","",$old_upsell);
			$old_upsell_coll = str_replace("c_","",$old_upsell);
		
            /* For Find Metafield */
            $api_call_str = "";
            if(strpos($old_upsell,'p_') !== false) { 
                $api_call_str = "/admin/products/".$old_upsell_pro."/metafields.json";
            } else {
                $api_call_str = "/admin/custom_collections/".$old_upsell_coll."/metafields.json";
            }

            try{
                $product_meta_list = $sc->call('GET', $api_call_str);

                /* Delete meta for upsell list */
                $upsell_meta_id = "";                
                $upsell_meta = loopAndFind($product_meta_list, 'namespace', 'p_upsell_target');
                foreach ($upsell_meta as $k => $v) {
                    if ($v['key'] == 'p_upsell_list') {
                        $upsell_meta_id = $v['id'];
                    }
                }
                
                if($upsell_meta_id != ""){
                    $d_api_call_str = "";
                    if(strpos($old_upsell,'p_') !== false) { 
                        $d_api_call_str = "/admin/products/".$old_upsell_pro."/metafields/".$upsell_meta_id.".json";
                    } else {
                        $d_api_call_str = "/admin/custom_collections/".$old_upsell_pro."/metafields/".$upsell_meta_id.".json";
                    }
                    
                    if($d_api_call_str != ""){
                        try{
                            $delete_meta = $sc->call('DELETE', $d_api_call_str);                        
                        } catch (exception $e) {

                        }
                    }
                }

                /* Delete meta for upsell type */
                $upsell_meta_id_for_type = "";
                $upsell_meta = loopAndFind($product_meta_list, 'namespace', 'cu_upsell_type');
                foreach ($upsell_meta as $k => $v) {
                    if ($v['key'] == 'cu_upsell_value') {
                        $upsell_meta_id_for_type = $v['id'];
                    }
                }
                
                if($upsell_meta_id_for_type != ""){
                    $d_api_call_str = "";
                    if(strpos($old_upsell,'p_') !== false) { 
                        $d_api_call_str = "/admin/products/".$old_upsell_pro."/metafields/".$upsell_meta_id_for_type.".json";
                    } else {
                        $d_api_call_str = "/admin/custom_collections/".$old_upsell_pro."/metafields/".$upsell_meta_id_for_type.".json";
                    }
                    
                    if($d_api_call_str != ""){
                        try{
                            $delete_meta = $sc->call('DELETE', $d_api_call_str);                        
                        } catch (exception $e) {

                        }
                    }
                }
            } catch (exception $e) {

            }
        }

        /* Add/Update Metafield for Upsell List */
		
		$b_pro_list_array = explode(",",$b_pro_list_string);
		foreach($b_pro_list_array as $b_pro_list){
			$product_id = str_replace("p_","",$b_pro_list);
			$coll_id = str_replace("c_","",$b_pro_list);
		
			$api_call_str = "";
			$MetaData = array("metafield" => array("namespace" => "p_upsell_target", "key" => "p_upsell_list", "value" => $b_target_pro_handle, "value_type" => "string"));
			if(strpos($b_pro_list,'p_') !== false) { 
				$api_call_str = "/admin/products/".$product_id."/metafields.json";
			} else {
				$api_call_str = "/admin/custom_collections/".$coll_id."/metafields.json";
			}

			try{
				$add_meta = $sc->call('POST', $api_call_str, $MetaData);
			} catch (exception $e) {

			}

			/* Add/Update Metafield for Upsell Type */
			$api_call_str = "";
			$cu_type_value_str = $b_pro_list."###".$bTitle."###".$banner_id."###".$b_banner_visiblity."###".$b_sub_title."###".$b_template."###".$b_in_page_banner;;
			$MetaData = array("metafield" => array("namespace" => "cu_upsell_type", "key" => "cu_upsell_value", "value" => $cu_type_value_str, "value_type" => "string"));
			if(strpos($b_pro_list,'p_') !== false) { 
				$api_call_str = "/admin/products/".$product_id."/metafields.json";
			} else {
				$api_call_str = "/admin/custom_collections/".$coll_id."/metafields.json";
			}

			try{
				$add_meta = $sc->call('POST', $api_call_str , $MetaData);
			} catch (exception $e) {

			}
		}
		
        echo "success";
    }
}

if($type == 'b_delete') {
    $bid = base64_decode($_REQUEST['id']);
    $old_upsell_string = trim($_REQUEST['b_pro_list_old']);
	
    

    $sql_sel = "select bid from banner_mst where bid='".$bid."' and shop='".mysql_real_escape_string($shop)."' limit 1";
    #echo $sql_sel;
    $sel_result_sql = mysql_query($sql_sel);
    if(mysql_num_rows($sel_result_sql) > 0){
        /* For Find Metafield & Delete */
		
		$old_upsell_array = explode(",",$old_upsell_string);
		foreach($old_upsell_array as $old_upsell){
			$old_upsell_pro = str_replace("p_","",$old_upsell);
			$old_upsell_coll = str_replace("c_","",$old_upsell);
	
			$api_call_str = "";
			if(strpos($old_upsell,'p_') !== false) { 
				$api_call_str = "/admin/products/".$old_upsell_pro."/metafields.json";
			} else {
				$api_call_str = "/admin/custom_collections/".$old_upsell_coll."/metafields.json";
			}

			try{
				$product_meta_list = $sc->call('GET', $api_call_str);

				/* Delete meta for upsell list */
				$upsell_meta_id = "";                
				$upsell_meta = loopAndFind($product_meta_list, 'namespace', 'p_upsell_target');
				foreach ($upsell_meta as $k => $v) {
					if ($v['key'] == 'p_upsell_list') {
						$upsell_meta_id = $v['id'];
					}
				}
				
				if($upsell_meta_id != ""){
					$d_api_call_str = "";
					if(strpos($old_upsell,'p_') !== false) { 
						$d_api_call_str = "/admin/products/".$old_upsell_pro."/metafields/".$upsell_meta_id.".json";
					} else {
						$d_api_call_str = "/admin/custom_collections/".$old_upsell_pro."/metafields/".$upsell_meta_id.".json";
					}
					
					if($d_api_call_str != ""){
						try{
							$delete_meta = $sc->call('DELETE', $d_api_call_str);                        
						} catch (exception $e) {

						}
					}
				}

				/* Delete meta for upsell type */
				$upsell_meta_id_for_type = "";
				$upsell_meta = loopAndFind($product_meta_list, 'namespace', 'cu_upsell_type');
				foreach ($upsell_meta as $k => $v) {
					if ($v['key'] == 'cu_upsell_value') {
						$upsell_meta_id_for_type = $v['id'];
					}
				}
				
				if($upsell_meta_id_for_type != ""){
					$d_api_call_str = "";
					if(strpos($old_upsell,'p_') !== false) { 
						$d_api_call_str = "/admin/products/".$old_upsell_pro."/metafields/".$upsell_meta_id_for_type.".json";
					} else {
						$d_api_call_str = "/admin/custom_collections/".$old_upsell_pro."/metafields/".$upsell_meta_id_for_type.".json";
					}
					
					if($d_api_call_str != ""){
						try{
							$delete_meta = $sc->call('DELETE', $d_api_call_str);                        
						} catch (exception $e) {

						}
					}
				}
			} catch (exception $e) {

			}
		}
		
        $sql_delete = "delete from banner_mst where bid='".$bid."' and shop='".mysql_real_escape_string($shop)."'";
        #echo $sql_update;            
        $result_sql = mysql_query($sql_delete);
        if (!$result_sql) {
            echo 'Invalid query: '.mysql_error();
        } else {
            echo "success";
        }        
    } else {
        echo 'Invalid query: '.mysql_error();
    }
}

if($type == 'update_status') {
    $status_update_val = $_REQUEST['val'];
    $bid = base64_decode($_REQUEST['id']);

    $sql_update = "Update banner_mst set b_status='".$status_update_val."',updated_time='".$current_date."' where bid='".$bid."' and shop='".mysql_real_escape_string($shop)."'";
    #echo $sql_update;            
    $result_sql = mysql_query($sql_update);
    if (!$result_sql) {
        echo 'Invalid query: '.mysql_error();
    } else {
        echo "success";
    }
}

if($type == 'get_banner_list') {
    $table = 'banner_mst';    
    $primaryKey = 'bid';
    
    $columns = array(        
        array('db' => 'b_title_internal', 'dt' => 'b_title_internal'),
        array('db' => 'b_status', 'dt' => 'b_status'),
        array('db' => 'b_product_list', 'dt' => 'b_product_list'),
        array('db' => 'b_target_list', 'dt' => 'b_target_list'),        
        array('db' => 'bid', 'dt' => 'bid')
    );

    $extraWhere = "shop='".mysql_real_escape_string($shop)."' ";
    require('ssp.class.php');

    $arr = SSP::complex($_POST, $sql_details, $table, $primaryKey, $columns, null, $extraWhere);
    $json = $arr;
    
    print_r(json_encode($json));
}

if($type == 'search_p') {
    $search_text= trim($_REQUEST['ss']);  
    $search_collection_id = trim($_REQUEST['sc']);  
    $search_type= trim($_REQUEST['st']);  
    $page = trim($_REQUEST['page']);
    $limit_str = 10;

    if($search_type == "all_coll"){
        $search_peram = "?limit=".$limit_str."&page=".$page;
        $search_products_data = $sc->call('GET', '/admin/products.json?collection_id='.$search_collection_id.$search_peram);  

        $total_prod_count = $sc->call('GET', '/admin/products.json?collection_id='.$search_collection_id); 
        $total_prod_count = intval($total_prod_count); 
    } else {
        if($search_text == ""){
            $search_peram = "?limit=".$limit_str."&page=".$page;
        } else {
            $search_peram = "?page=".$page;
        }
        if($search_text != ""){
            $search_peram .= "&title=".$search_text;
        }
        $search_products_data = $sc->call('GET', '/admin/products.json'.$search_peram);

        if($search_text != ""){
            $total_prod_count = count($search_products_data);
        } else {
            $total_prod_count = $sc->call('GET', '/admin/products/count.json');
        }
        $total_prod_count = intval($total_prod_count);
    }

    #echo "<pre>"; print_r($search_products_data);
    #echo $total_prod_count;

    if($total_prod_count > 0){        
        $p_str = "";
        for($i=0;$i<$limit_str;$i++){
            if($search_products_data[$i]["id"] != ""){
                $p_str_temp = '<div class="p_box" id="'.$search_products_data[$i]["id"].'"><span class="p_name">'.$search_products_data[$i]["title"].'</span><input type="button" class="btn primary p_add" value="Add" p_id="'.$search_products_data[$i]["id"].'" handle="'.$search_products_data[$i]["handle"].'" /></div>';
                $p_str .= $p_str_temp;
            }
        }

        $pagignation_str = "";        
        $total_page = $total_prod_count / $limit_str;
        if($total_page > 1){
            $pagignation_str = "Page: ";
            for($i=0;$i<$total_page;$i++){            
                $active_class="";
                if(($i+1) == $page){
                    $active_class=" active_page";
                }
                $pagignation_str .= "<a class='page_no".$active_class."' st='".$search_type."' sc='".$search_collection_id."' ss='".$search_text."'>".($i+1)."</a>";
            }
        }

        if($p_str != ""){
            echo $pagignation_str."|||".$p_str;
        } else {
            echo "no_products";
        }
    }
}
if ($type == 'get_preview') {
    $bid = base64_decode($_REQUEST['id']);

    $products_data = array();    
    $sql_sel = "select * from banner_mst where bid='".$bid."' limit 1";
    #echo $sql_sel;
    $result_sql = mysql_query($sql_sel);
    if (!$result_sql) {
        echo 'Invalid query: '.mysql_error();
    } else {
        $preview = "";
        $result_data= mysql_fetch_assoc($result_sql);
        $target_list_temp = $result_data['b_target_list']; 
        $preview .= "<div><h1>".$result_data['b_title']."</h1>";
        $preview .= "<ul class='pr_images'>";
        $target_data_temp = array();
        $target_data_temp = $sc->call('GET', "/admin/products.json?fields=image&ids=".$target_list_temp);

        if(count($target_data_temp) > 0){
            for($i =0;$i<count($target_data_temp);$i++){
                $image_str = $target_data_temp[$i]['image']['src'];
                if($image_str != ""){
                    $image_str = "<img src='".$image_str."' alt='' />";
                }                
                $preview .= "<li class='up_pr_img'>
                                <div class='table' style='width:100%;display: table;'>
                                    <div class='table-cell' style='width:100%;vertical-align: middle;;height:140px;display: table-cell;text-align:center;border: 1px solid #bbb;'>".$image_str."</div>
                                </div>
                            </li>";
            }
        } 
        $preview .="</ul></div>";
        echo $preview;
    }
}
?>