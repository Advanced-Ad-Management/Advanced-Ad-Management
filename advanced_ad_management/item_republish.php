<?php 
    //set include
    define('ABS_PATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
    require_once ABS_PATH . 'oc-load.php'; 
         
               $repub = Params::getParam('repub');    
                 if ($repub == 'republish') {   // repost item
                                    $secret = (Params::getParam('secret'))? Params::getParam('secret') : '';
                                    $rSecret = (Params::getParam('rSecret'))? Params::getParam('rSecret') : '';
                                    $id     = (Params::getParam('id'))? Params::getParam('id') : '';
                                    $conn   = getConnection();
                                    $rSecretOk   = $conn->osc_dbFetchResult("SELECT * FROM %st_item_adManage_limit WHERE fk_i_item_id = '%d' AND r_secret = '%s'", DB_TABLE_PREFIX, $id, $rSecret);
                                    $item   = $conn->osc_dbFetchResult("SELECT * FROM %st_item WHERE pk_i_id = '%d' AND ((s_secret = '%s' OR (fk_i_user_id = '%d'))", DB_TABLE_PREFIX, $id, $secret, osc_logged_user_id());
           if ( $item['pk_i_id'] != 0 && $rSecretOk['r_secret'] != '') {
              if($rSecretOk['r_times'] < osc_adManage_repubTimes() || osc_adManage_repubTimes() == 0 ){
					$date  = date('Y-m-d H:i:s');
					$rTimes = $rSecretOk['r_times'] + 1;
					$conn->osc_dbExec("UPDATE %st_item SET dt_pub_date = '%s' WHERE pk_i_id = '%d' ", DB_TABLE_PREFIX,$date,$id);
					$conn->osc_dbExec("UPDATE %st_item_adManage_limit SET r_secret = '%s', r_times = '%d' WHERE fk_i_item_id = '%d' ", DB_TABLE_PREFIX, osc_genRandomPassword(), $rTimes, $id);
					$rTimes = 0;

                         if((osc_adManage_payperpost() == 1) ){
                            if(osc_item_adManage_freeRepubs() == 0 || $rSecretOk['r_times'] >= osc_item_adManage_freeRepubs()) {
					             // This checks to see if there is a db table "t_paypal_publish", if so, set republished item as "unpaid"
			                  $check_if_paypal_enabled = $conn->osc_dbFetchResult("SELECT b_paid FROM %st_paypal_publish WHERE fk_i_item_id = %d", DB_TABLE_PREFIX, $id);
			                  if ($check_if_paypal_enabled) {
				                 $conn->osc_dbExec("UPDATE %st_paypal_publish SET dt_date = '%s', b_paid =  '0' WHERE fk_i_item_id = %d", DB_TABLE_PREFIX, date('Y-m-d H:i:s'), $id);
			                  }
			                  }
			                }

                                        $conn->osc_dbExec("UPDATE %st_item_adManage_limit SET ex_email = '%d' WHERE fk_i_item_id = '%d'", DB_TABLE_PREFIX, 0, $id);
                                        osc_add_flash_ok_message( __('Item has been republished','adManage') ) ;
                                        $conn->osc_dbExec("INSERT %st_item_adManage_log (fk_i_item_id, log_date, error_action) VALUES ('%d', '%s', '%s')", DB_TABLE_PREFIX, $id, date('Y-m-d H:i:s'), 'Item Republished. ' . date('Y-m-d H:i:s'));                                  
                                        header("Location: " . osc_base_url(true) . '?page=item&id=' . $id);
                                        exit;
    	                                    
                                    //else statement if the number of republish times has been reached
                                    } else{
                                       // add a flash message [ITEM NO EXISTE]
                                        osc_add_flash_error_message( __('Sorry, this ad has reached the max number of republishes.','adManage')) ;
                                        $conn->osc_dbExec("INSERT %st_item_adManage_log (fk_i_item_id, log_date, error_action) VALUES ('%d', '%s', '%s')", DB_TABLE_PREFIX, $id, date('Y-m-d H:i:s'), 'Item reached max num of republishes. ' . date('Y-m-d H:i:s'));
                                        if(osc_is_web_user_logged_in()) {
                                           // REDIRECT
                                           header("Location: " . osc_user_list_items_url());
    	                                    
                                        } else {
                                           // REDIRECT
                                           header("Location: " . osc_base_url(true));
                                        }                                    
                                    }
                                    } else {
                                        // add a flash message [ITEM NO EXISTE]
                                        osc_add_flash_error_message( __('Sorry, we don\'t have any items with that ID or the secret key is incorrect.','adManage')) ;
                                        $conn->osc_dbExec("INSERT %st_item_adManage_log (fk_i_item_id, log_date, error_action) VALUES ('%d', '%s', '%s')", DB_TABLE_PREFIX, $id, date('Y-m-d H:i:s'), 'Item does not exist or incorrect secret key. ' . date('Y-m-d H:i:s'));
                                        if(osc_is_web_user_logged_in()) {
                                           // REDIRECT
                                           header("Location: " . osc_user_list_items_url());    	                                  
                                        } else {
                                           // REDIRECT
    	                                     header("Location: " . osc_base_url(true));
                                        }
                                    }

         
         } else {
            $conn->osc_dbExec("INSERT %st_item_adManage_log (fk_i_item_id, log_date, error_action) VALUES ('%d', '%s', '%s')", DB_TABLE_PREFIX, $id, date('Y-m-d H:i:s'), 'Problem with url no action taken. ' . date('Y-m-d H:i:s'));
         }
        
?>