<?php
    //set include
    define('ABS_PATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
    require_once ABS_PATH . 'oc-load.php';

	$repub = Params::getParam('repub');

	$secret = (Params::getParam('secret'))? Params::getParam('secret') : '';
	$rSecret = (Params::getParam('rSecret'))? Params::getParam('rSecret') : '';
	$id     = (Params::getParam('id'))? Params::getParam('id') : '';

	$rSecretOk  = ModelAAM::newInstance()->getLimitUser($id, true, $rSecret);
	$item   	= ModelAAM::newInstance()->getRepubItem($id, $secret);
	$cat 		= Category::newInstance()->findByPrimaryKey($item['fk_i_category_id']);
	$dt_expires = strtotime ( '+' . $cat['i_expiration_days'] . ' days') ;
	$dt_expire 	= date('Y-m-d H:i:s', $dt_expires);
	$date  = date('Y-m-d H:i:s');

if ($repub == 'republish') {   // republish item
ModelAAM::newInstance()->insertLog('User started item republish');
	if ( ($item['pk_i_id'] != 0 || $item['pk_i_id'] != null) && ($rSecretOk['r_secret'] != '' || $rSecretOk['r_secret'] != null) ) {
		if($rSecretOk['r_times'] < osc_advanced_ad_management_repubTimes() || osc_advanced_ad_management_repubTimes() == 0 ){

			$rTimes = $rSecretOk['r_times'] + 1;
			// updates the items expiration date.
			$newExp = ModelAAM::newInstance()->aam_updateExpirationDate($id, date('Y-m-d H:i:s', strtotime("+" . $cat['i_expiration_days'] . " days")) );
			if( $newExp != false && $newExp != '9999-12-31 23:59:59'){
				ModelAAM::newInstance()->insertLog('Items expire date updated exp date ' . $newExp, $id);
			} else {
				$errorMess = ModelAAM::newInstance()->dao->getErrorDesc();
				ModelAAM::newInstance()->insertLog('Item expire date not updated.' , $id);
				ModelAAM::newInstance()->insertLog($errorMess, $id);
			}
			// republishes the item.
			ModelAAM::newInstance()->updateLimitRepub(osc_genRandomPassword(), $rTimes, $id);

			ModelAAM::newInstance()->insertLog('Item has been republished', $id);

			confirm_email($id, $cat['i_expiration_days']);
			admin_confirm_email($id);
			ModelAAM::newInstance()->insertLog('Conformation email sent', $id);

			$rTimes = 0;

				 if((osc_advanced_ad_management_payperpost() == 1) ){
					if(osc_item_advanced_ad_management_freeRepubs() == 0 || $rSecretOk['r_times'] >= osc_item_advanced_ad_management_freeRepubs()) {

			          // This checks to see if there is a db table "t_paypal_publish", if so, set republished item as "unpaid"
	                  $check_if_paypal_enabled = osc_get_preference('pay_per_post', 'paypal');
	                  if ($check_if_paypal_enabled == 1) {
		                 ModelAAM::newInstance()->updatePayPalPub($id);
		                 ModelAAM::newInstance()->insertLog('Paypal publish table updated', $id);
	                  }
	                }
	             }

				osc_add_flash_ok_message( __('Item has been re published','advanced_ad_management') ) ;
				Item::newInstance()->clearStat($id, 'expired') ;
				ModelAAM::newInstance()->insertLog('Item Re published. ' . date('Y-m-d H:i:s'), $id);
				ModelAAM::newInstance()->insertLog('User Ended item republish Succesful. :)');
				// REDIRECT
				header("Location: " . osc_item_url_advanced($id));


		//else statement if the number of republish times has been reached
		} else{
		   // add a flash message [REPUB LIMIT REACHED]
			osc_add_flash_error_message( __('Sorry, this ad has reached the max number of re publishes.','advanced_ad_management')) ;
			ModelAAM::newInstance()->insertLog('Item reached max num of republishes. ' . date('Y-m-d H:i:s'), $id);

			// REDIRECT
		    header("Location: " . osc_item_url_advanced($id));
		}
	} else {
		// add a flash message [ITEM NO EXISTE]
		if ( $id == 0 || $id == null ) {
			ModelAAM::newInstance()->insertLog('Item does not exist. ' . date('Y-m-d H:i:s'), $id);
		} else if ($rSecretOk['r_secret'] == '' || $rSecretOk['r_secret'] == null) {
			ModelAAM::newInstance()->insertLog('No secret key was provided. ' . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"], $id);
		}
		$refId = ModelAAM::newInstance()->dao->insertedId();
		osc_add_flash_error_message( __('Sorry, an error occurred trying to republish your listing. <br /><br />Please contact the admin. Reference # ' . $refId,'advanced_ad_management')) ;
		ModelAAM::newInstance()->insertLog('User Ended item republish with an error. :(');

		   // REDIRECT
		   header("Location: " . osc_base_url());
	}


} elseif($repub == 'admin_repub') {

	if( osc_is_admin_user_logged_in() ) {
		ModelAAM::newInstance()->insertLog('Admin started item republish');
		// updates the items expiration date.
			$newExp = ModelAAM::newInstance()->aam_updateExpirationDate($id, date('Y-m-d H:i:s', strtotime("+" . $cat['i_expiration_days'] . " days")) );
			if( $newExp != false && $newExp != '9999-12-31 23:59:59'){
				ModelAAM::newInstance()->insertLog('Admin: Items expire date updated exp date ' . $newExp, $id);
			} else {
				ModelAAM::newInstance()->insertLog('Admin: Item expire date not updated.' , $id);
			}
		// republishes the item.
		if(ModelAAM::newInstance()->updateLimitRepub(osc_genRandomPassword(), $rSecretOk['r_times'], $id) ) {
			ModelAAM::newInstance()->insertLog('The admin has re published the listing', $id);
		}

		if((osc_advanced_ad_management_payperpost() == 1) ){
			if(osc_item_advanced_ad_management_freeRepubs() == 0 || $rSecretOk['r_times'] >= osc_item_advanced_ad_management_freeRepubs()) {

	          // This checks to see if paypal pay per post is enabled. If yes then marks the item as needing to be paid again.
			  $check_if_paypal_enabled = osc_get_preference('pay_per_post', 'paypal');
			  if ($check_if_paypal_enabled == 1) {
				 ModelAAM::newInstance()->updatePayPalPub($id);
				 ModelAAM::newInstance()->insertLog('Paypal publish table updated', $id);
			  }
			}
		 }

		osc_add_flash_ok_message( __('Item has been re published','advanced_ad_management'), 'admin' ) ;
		Item::newInstance()->clearStat($id, 'expired') ;
		ModelAAM::newInstance()->insertLog('The admin Re published the item. ' . date('Y-m-d H:i:s'), $id);
		ModelAAM::newInstance()->insertLog('Admin ended item republish successful. :)');
		header("Location: " . $_SERVER['HTTP_REFERER']);
	}

} else {
	ModelAAM::newInstance()->insertLog('Problem with url no action taken. ' . date('Y-m-d H:i:s') . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"], $id);
	// REDIRECT
	header("Location: " . osc_base_url());
}

?>
