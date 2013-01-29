<?php
/*
Plugin Name: Advanced Ad Management
Plugin URI: http://forums.osclass.org/development/repost-an-expired-ad/
Description: Ad management options for ad republishing, expiration notices, automatic ad deletion and more!
Version: 2.5
Author: Jesse & JChapman
Author URI: http://www.osclass.org/
Short Name: advanced_ad_management

The plans of the diligent lead to profit as
surely as haste leads to poverty. Proverbs 21:5
*/

require_once('ModelAAM.php');
require_once('logging.php');


// checks to make sure the file permissions are set right that way the plugin will work right. :)
if(substr(decoct(fileperms(ABS_PATH . 'oc-content/plugins/')),2) != 755 ){
	osc_add_flash_error_message( __('File permissions for oc-content/plugins are wrong. Please change them to 755.','advanced_ad_management'), 'admin') ;
} else if (substr(decoct(fileperms(ABS_PATH . 'oc-content/plugins/advanced_ad_management')),2) != 755){
	osc_add_flash_error_message( __('File permissions for oc-content/plugins/advanced_ad_management are wrong. Please change them to 755.','advanced_ad_management'), 'admin') ;
} else if (substr(decoct(fileperms(ABS_PATH . 'oc-content/plugins/advanced_ad_management/item_republish.php')),2) != 644) {
	osc_add_flash_error_message( __('File permissions for oc-content/plugins/advanced_ad_management/item_republish.php are wrong. Please change them to 644.','advanced_ad_management'), 'admin') ;
}
if(osc_item_advanced_ad_management_installed() != 1) {
	osc_add_flash_info_message( '<a href="' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=advanced_ad_management/admin.php&addExistingAds=1">' . __('Click here to complete the install of Advanced Ad Management.','advanced_ad_management'), 'admin') . '</a>';
}


   function advanced_ad_management_install () {
      ModelAAM::newInstance()->import('advanced_ad_management/struct.sql');

      osc_set_preference('advanced_ad_management_expire', '4', 'plugin-item_advanced_ad_management', 'INTEGER');
      osc_set_preference('advanced_ad_management_payperpost', '1', 'plugin-item_advanced_ad_management', 'INTEGER');
      osc_set_preference('advanced_ad_management_repubTimes', '5', 'plugin-item_advanced_ad_management', 'INTEGER');
      osc_set_preference('advanced_ad_management_installed', '0', 'plugin-item_advanced_ad_management', 'INTEGER');
      osc_set_preference('advanced_ad_management_freeRepubs', '0', 'plugin-item_advanced_ad_management', 'INTEGER');
      osc_set_preference('advanced_ad_management_expireEmail', '1', 'plugin-item_advanced_ad_management', 'INTEGER');
      osc_set_preference('advanced_ad_management_deleteDays', '0', 'plugin-item_advanced_ad_management', 'INTEGER');
      //added in v2.0 8-28-12
      osc_set_preference('advanced_ad_management_debug', '0', 'plugin-item_advanced_ad_management', 'INTEGER');
      osc_set_preference('advanced_ad_management_mod',   '0', 'plugin-item_advanced_ad_management', 'INTEGER');
      osc_set_preference('advanced_ad_management_update','0', 'plugin-item_advanced_ad_management', 'INTEGER');
      //added in v2.1 12-20-12
      osc_set_preference('advanced_ad_management_reminderDays', '5', 'plugin-item_advanced_ad_management', 'INTEGER');
      osc_set_preference('aam_cron_last_run', '', 'plugin-item_advanced_ad_management', 'INTEGER');

      @fopen(osc_content_path() . 'uploads/AAM.log', 'a');

      // insert email templates
      foreach(osc_listLanguageCodes() as $locales) {
		   //aam_listing_republished_admin
		   $description[$locales]['s_title'] = '{WEB_TITLE} - User republished listing';
		   $description[$locales]['s_text'] = '<p>Hi {CONTACT_NAME}!</p><p>User {USER_NAME} has just republished their listing {ITEM_TITLE}.</p><P>{ITEM_URL}</p><p>Thanks</p>';
		   Page::newInstance()->insert( array('s_internal_name' => 'aam_listing_republished_admin', 'b_indelible' => '1'), $description );

		   //aam_listing_replublished
	       $descriptionRePub[$locales]['s_title'] = '{WEB_TITLE} - Listing Re Published.';
	       $descriptionRePub[$locales]['s_text'] = '<p>Hi {CONTACT_NAME},</p> <p>Your ad "{ITEM_TITLE}" has been re publied for {REPUB_DAYS} days.</p> <p>{ITEM_URL}</p> <p>This is an automatic email, Please do not respond to this email.</p> <p>Thanks</p> <p>{WEB_TITLE}</p> <p> </p>';
	       Page::newInstance()->insert( array('s_internal_name' => 'aam_listing_republished', 'b_indelible' => '1'), $descriptionRePub );

	       // email_ad_expire
	       $descriptionExpire[$locales]['s_title'] = '{WEB_TITLE} - Your ad {ITEM_TITLE} is about to expire.';
		   $descriptionExpire[$locales]['s_text'] = '<p>&lt;p&gt;Hi {CONTACT_NAME}!&lt;/p&gt; &lt;p&gt;Your ad is about to expire, click on the link if you would like to re publish your ad {REPUBLISH_URL}&lt;/p&gt; &lt;p&gt;This is an automatic email, Please do not respond to this email.&lt;/p&gt; &lt;p&gt;Thanks&lt;/p&gt; &lt;p&gt;{WEB_TITLE}&lt;/p&gt;</p>';
		   Page::newInstance()->insert( array('s_internal_name' => 'email_ad_expire', 'b_indelible' => '1'), $descriptionExpire );

		   // email_ad_expired
		   $descriptionExpired[$locales]['s_title'] = '{WEB_TITLE} - Your ad {ITEM_TITLE} has expired.';
		   $descriptionExpired[$locales]['s_text'] = '<p>&lt;p&gt;Hi {CONTACT_NAME}!&lt;/p&gt; &lt;p&gt;Your ad has expired. You may renew your ad by clicking on the link {REPUBLISH_URL}. Otherwise your ad will be permanently deleted in {PERM_DELETED} days&lt;/p&gt; &lt;p&gt;This is an automatic email, Please do not respond to this email.&lt;/p&gt; &lt;p&gt;Thanks&lt;/p&gt; &lt;p&gt;{WEB_TITLE}&lt;/p&gt;</p>';
		   Page::newInstance()->insert( array('s_internal_name' => 'email_ad_expired', 'b_indelible' => '1'), $descriptionExpired );
	   }
   }

   function advanced_ad_management_uninstall () {
      $conn = getConnection() ;
      // Remove the table we added for our plugin
	  ModelAAM::newInstance()->uninstall();
      // Delete the preference rows we added
      osc_delete_preference('advanced_ad_management_expire', 'plugin-item_advanced_ad_management');
      osc_delete_preference('advanced_ad_management_payperpost', 'plugin-item_advanced_ad_management');
      osc_delete_preference('advanced_ad_management_repubTimes', 'plugin-item_advanced_ad_management');
      osc_delete_preference('advanced_ad_management_istalled', 'plugin-item_advanced_ad_management');
      osc_delete_preference('advanced_ad_management_freeRepubs', 'plugin-item_advanced_ad_management');
      osc_delete_preference('advanced_ad_management_expireEmail', 'plugin-item_advanced_ad_management');
      osc_delete_preference('advanced_ad_management_deleteDays', 'plugin-item_advanced_ad_management');
      // added in v2.0 8-28-12
      osc_delete_preference('advanced_ad_management_debug', 'plugin-item_advanced_ad_management');
      osc_delete_preference('advanced_ad_management_mod', 'plugin-item_advanced_ad_management');
      osc_delete_preference('advanced_ad_management_update', 'plugin-item_advanced_ad_management');
      //added in v2.1 12-20-12
      osc_delete_preference('advanced_ad_management_reminderDays', 'plugin-item_advanced_ad_management');
      //added in v2.2 01-10-13
      osc_delete_preference('aam_cron_last_run', 'plugin-item_advanced_ad_management');

      //remove email templates
      Page::newInstance()->deleteByInternalName('aam_listing_republished_admin');
      Page::newInstance()->deleteByInternalName('aam_listing_republished');
      Page::newInstance()->deleteByInternalName('email_ad_expired');
      Page::newInstance()->deleteByInternalName('email_ad_expire');
      $path = osc_content_path() . 'uploads/AAM.log';
      @unlink($path);
   }

    function item_advanced_ad_management_posted($catId= '', $itemId) {
      $r_secret = '';
      $r_secret = osc_genRandomPassword();
      ModelAAM::newInstance()->insertNewLimit($itemId, $r_secret, 0, osc_item_advanced_ad_management_deleteDays() );
      if(aam_debug(false) ) {
		ModelAAM::newInstance()->insertLog('New item added. Item id ' . $itemId, $itemId);
	  }

    }

   // HELPER
    function osc_advanced_ad_management_expire() {
        return(osc_get_preference('advanced_ad_management_expire', 'plugin-item_advanced_ad_management')) ;
    }
    function osc_advanced_ad_management_payperpost() {
        return(osc_get_preference('advanced_ad_management_payperpost', 'plugin-item_advanced_ad_management')) ;
    }
    function osc_advanced_ad_management_paypalPaypost() {
		$ppp = osc_get_preference('pay_per_post', 'paypal');
		if($ppp == '') {
			$ppp = osc_get_preference('pay_per_post', 'payment');
		}
        return($ppp) ;
    }
    function osc_advanced_ad_management_repubTimes() {
        return(osc_get_preference('advanced_ad_management_repubTimes', 'plugin-item_advanced_ad_management')) ;
    }
    function osc_item_advanced_ad_management_installed() {
        return(osc_get_preference('advanced_ad_management_installed', 'plugin-item_advanced_ad_management')) ;
    }
    function osc_item_advanced_ad_management_freeRepubs() {
        return(osc_get_preference('advanced_ad_management_freeRepubs', 'plugin-item_advanced_ad_management')) ;
    }
    function osc_item_advanced_ad_management_adEmailEx() {
        return(osc_get_preference('advanced_ad_management_expireEmail', 'plugin-item_advanced_ad_management')) ;
    }
    function osc_item_advanced_ad_management_deleteDays() {
        return(osc_get_preference('advanced_ad_management_deleteDays', 'plugin-item_advanced_ad_management')) ;
    }
    function aam_reminder_days() {
        return(osc_get_preference('advanced_ad_management_reminderDays', 'plugin-item_advanced_ad_management')) ;
    }
    function aam_debug($adminLogged = TRUE) {
		if($adminLogged) {
			if(osc_get_preference('advanced_ad_management_debug', 'plugin-item_advanced_ad_management') == 1 && osc_is_admin_user_logged_in() ) {
				return true ;
			}
		} elseif(osc_get_preference('advanced_ad_management_debug', 'plugin-item_advanced_ad_management') == 1) {
			return true ;
		}
		return false;
    }
    function aam_mod_repub() {
        return(osc_get_preference('advanced_ad_management_mod', 'plugin-item_advanced_ad_management')) ;
    }

    //Helper to display the re-publish [date]
    function aam_pub_repub_date() {
       $rePubTime = ModelAAM::newInstance()->getLimitUser(osc_item_id());
       if(@$rePubTime['repub_date'] != '' && @$rePubTime['repub_date'] !== date('Y-m-d H:i:s',strtotime(osc_item_pub_date())) ) {
			return (string) '<em class="publish">' .  __('Republication Date: ','advanced_ad_management') . osc_format_date($rePubTime['repub_date']) . '</em>';
		}
    }

    function check_if_item_expired() {
		$expDate = strtotime( "-" . osc_advanced_ad_management_expire() . " days", strtotime(osc_item_dt_expiration() ) );
		$expDate = date('Y-m-d H:i:s', $expDate);
		if( osc_item_is_premium() ) {
            return false;
        } else {
            return osc_isExpired($expDate);
        }
	}

   // function for displaying the republish link in the users area
   function republish_url() {
      $pCats = osc_is_this_category('advanced_ad_management', osc_item_category_id() );
      $advanced_ad_management_url = '';
      $rSecret   = ModelAAM::newInstance()->getLimitUser(osc_item_id() );
      $itemB = Item::newInstance()->findByPrimaryKey($rSecret['fk_i_item_id']);

      if(($rSecret['r_times'] < osc_advanced_ad_management_repubTimes() || osc_advanced_ad_management_repubTimes() == 0) && ($pCats || aam_debug() ) ){
         if(osc_expire_date($itemB['pk_i_id'], true) || aam_debug() ) {
			 if(aam_debug() ) {
				$advanced_ad_management_url =  '<span>|</span> <a href="'. osc_base_url() . 'oc-content/plugins/advanced_ad_management/item_republish.php?repub=republish&id=' . osc_item_id() . '&rSecret=' . $rSecret['r_secret'] . '">'. osc_base_url() . 'oc-content/plugins/advanced_ad_management/item_republish.php?repub=republish&id=' . osc_item_id() . '&rSecret=' . $rSecret['r_secret'] . '</a>';
			} else {
				$advanced_ad_management_url =  '<span>|</span> <a href="'. osc_base_url() . 'oc-content/plugins/advanced_ad_management/item_republish.php?repub=republish&id=' . osc_item_id() . '&rSecret=' . $rSecret['r_secret'] . '">' . __('Republish Ad','advanced_ad_management') .  '</a>';
			}
            if(aam_debug()) {
				//ModelAAM::newInstance()->insertLog('User Item list page Item will expire on ' . $itemB['dt_expiration'], osc_item_id());
				echo '<pre>';
				print_r($rSecret);
				echo '</pre>';
				echo 'listing expired? ';
				if(check_if_item_expired()){
					echo 'true';
				} else{
					echo 'false';
				}
				echo ModelAAM::newInstance()->dao->getErrorDesc();
				echo '<br />';
			}
         }
      }
      return $advanced_ad_management_url;
   }

   //depreciated menu hook remove in next upgrade.
   function item_advanced_ad_management_admin_menu(){
      if( OSCLASS_VERSION < '2.4.0') {
         echo '<h3><a href="#">Advanced Ad Management</a></h3><ul>';

           echo '<li class="" ><a href="' . osc_admin_render_plugin_url('advanced_ad_management/admin.php') . '" > &raquo; '. __('Configure', 'advanced_ad_management') . '</a></li>' .
           '<li class="" ><a href="' . osc_admin_render_plugin_url('advanced_ad_management/help.php') . '" >&raquo; ' . __('F.A.Q. / Help', 'advanced_ad_management') . '</a></li>';
           echo '<li class="" ><a href="' . osc_admin_render_plugin_url('advanced_ad_management/settings.php') . '" >&raquo; ' . __('Settings', 'advanced_ad_management') . '</a></li>';
           echo '</ul>';
     } else {
        echo '<li id="AAM"><h3><a href="#">Advanced Ad Management</a></h3><ul>';

           echo '<li class="" ><a href="' . osc_admin_render_plugin_url('advanced_ad_management/admin.php') . '" > &raquo; '. __('Configure', 'advanced_ad_management') . '</a></li>' .
           '<li class="" ><a href="' . osc_admin_render_plugin_url('advanced_ad_management/help.php') . '" >&raquo; ' . __('F.A.Q. / Help', 'advanced_ad_management') . '</a></li>';
           echo '<li class="" ><a href="' . osc_admin_render_plugin_url('advanced_ad_management/settings.php') . '" >&raquo; ' . __('Settings', 'advanced_ad_management') . '</a></li>';
           echo '<li class="" ><a href="' . osc_admin_render_plugin_url('advanced_ad_management/log.php') . '" >&raquo; ' . __('Log', 'advanced_ad_management') . '</a></li>';
           echo '</ul></li>';
     }
   }

	/*
     * Return the date the item expires
     *
     * @return string
     */
   function osc_expire_date($itemId, $greater = false) {
		$item = Item::newInstance()->findByPrimaryKey($itemId);
		$category = Category::newInstance()->findByPrimaryKey( $item['fk_i_category_id'] ) ;
		$expiration = $category['i_expiration_days'];
		if($expiration <= 9){ return FALSE; }
		else{
			$expireAlert = osc_advanced_ad_management_expire();
			$date_expiration = strtotime( "-" . $expireAlert . " days", strtotime(date('Y-m-d',strtotime($item['dt_expiration']))) );
			if(aam_debug(false)) {
				ModelAAM::newInstance()->insertLog('About to Expire: Date when about to expire email will be sent. ' . date('Y-m-d', $date_expiration), $item['pk_i_id'] );
			}
			//if date_expiration is equal to the current date send the about to expire email to user.
			if($greater == false) {
				if($date_expiration == strtotime(date("Y-m-d"))) {
				   return TRUE;
				} else {
				   return FALSE;
				}
			} else {
				if($date_expiration <= strtotime(date("Y-m-d")) ) {
				   return TRUE;
				} else {
				   return FALSE;
				}
			}
		}
   }

   /** This function is modelled after the one found in hItems.php
     *
     * Return true if item is expired, else return false
     *
     * @return boolean
     */
    function item_is_expired($item,$delExpire=0, $user=false) {
        if( $item['b_premium'] ) {
            return false;
        } else {
            $category = Category::newInstance()->findByPrimaryKey( $item['fk_i_category_id'] ) ;
            $expiration = $category['i_expiration_days'];

            if($expiration == 0){ return false; }
            else if ($delExpire > 0){
                $date_expiration = strtotime(date("Y-m-d", strtotime("+" . $delExpire . " days", strtotime($item['dt_expiration']))));
                $now             = strtotime(date('Y-m-d'));
				if(aam_debug(false)) {
					ModelAAM::newInstance()->insertLog('Expired: Date Item will be deleted ' . date('Y-m-d', $date_expiration), $item['pk_i_id'] );
					ModelAAM::newInstance()->insertLog('Expired: Current Date ' . date('Y-m-d', $now), $item['pk_i_id'] );
				}
				if($user) {
					if( $date_expiration <= $now ) { return true; }
	                else { return false; }
				} else {
					if( $date_expiration == $now ) { return true; }
	                else { return false; }
				}

            } else {
				$date_expiration = strtotime(date('Y-m-d', strtotime($item['dt_expiration'])));
				$now             = strtotime(date('Y-m-d'));
				if(aam_debug(false)) {
					ModelAAM::newInstance()->insertLog('Expired: Date Item expires ' . date('Y-m-d', $date_expiration), $item['pk_i_id'] );
					ModelAAM::newInstance()->insertLog('Expired: Current Date ' . date('Y-m-d', $now), $item['pk_i_id'] );
				}

				if( $date_expiration == $now ) { return true; }
                else { return false; }
			}

        }
    }

    function repub_if_repub($item) {
		if( $item['b_premium'] ) {
            return false;
        } else {
            $category = Category::newInstance()->findByPrimaryKey( $item['fk_i_category_id'] ) ;
            $expiration = $category['i_expiration_days'];

            if($expiration == 0){ return false; }
            else {
				$date_expiration = strtotime(date('Y-m-d', strtotime($item['dt_expiration'])));
				$now             = strtotime(date('Y-m-d'));
				if(aam_debug(false)) {
					ModelAAM::newInstance()->insertLog('Expired: Date Item expires ' . date('Y-m-d', $date_expiration), $item['pk_i_id'] );
					ModelAAM::newInstance()->insertLog('Expired: Current Date ' . date('Y-m-d', $now), $item['pk_i_id'] );
				}

				if( $date_expiration <= $now ) { return true; }
				else { return false; }
			}
		}
	}

	//short: to cron AAM
   function item_advanced_ad_management_cron() {
	    // check to make sure the cron only runs once per day!
		if(osc_get_preference('aam_cron_last_run', 'plugin-item_advanced_ad_management') == '')	{
			osc_set_preference('aam_cron_last_run', '', 'plugin-item_advanced_ad_management', 'INTEGER');
			osc_reset_preferences();
		}

		$sDate = osc_get_preference('aam_cron_last_run', 'plugin-item_advanced_ad_management');
		if($sDate != strtotime('today')) {

		   	ModelAAM::newInstance()->insertLog('Cron job started.');

		      $allItems = ModelAAM::newInstance()->getAllItemsCron();
		      foreach($allItems as $itemA) {

		         $pCats = osc_is_this_category('advanced_ad_management', $itemA['fk_i_category_id'] );


		         $repub = ModelAAM::newInstance()->getLimitUser($itemA['pk_i_id']);

		         //send almost expired email

		         // checks if item is about to expire and it the item is in a category that allows republishing.
		         if(osc_expire_date($itemA['pk_i_id']) && $pCats ) {
					// sends data to the email template to send the email.
		            item_expire_email($itemA['pk_i_id'], $repub['r_secret'], osc_advanced_ad_management_expire() );
		            ModelAAM::newInstance()->insertLog('Ad is about to expire email sent. Successful', $itemA['pk_i_id']);
		         } else{
		         	ModelAAM::newInstance()->insertLog('Ad not about to expire.', $itemA['pk_i_id']);
		         }// end almost expired email check.

		         //send expired email
		         if(osc_item_advanced_ad_management_adEmailEx() == 1) {
					if($pCats){
			            if(item_is_expired($itemA)) {
			               $exEmailed = ModelAAM::newInstance()->getLimitUser($itemA['pk_i_id']);
			               if($exEmailed['ex_email'] != 1) {
			                  item_expired_email($itemA['pk_i_id'], $repub['r_secret'], $exEmailed['delete_days'] );
			                  // update the user limit to state that the expired email has been sent.
			                  ModelAAM::newInstance()->updateLimitUser('ex_email', 1, $itemA['pk_i_id']);
			                  // adds a log entry stating that the expired emial was sent.
			                  ModelAAM::newInstance()->insertLog('Expired email sent. Successful', $itemA['pk_i_id']);
			                  $mItems = new ItemActions(true);
			                  $success = $mItems->mark($itemA['pk_i_id'], 'expired');
			               }// end check of expired email has been sent.
			            }// end of is item expired check.
			            else {
			            	ModelAAM::newInstance()->insertLog('Ad is not expired so no email sent.', $itemA['pk_i_id']);
			            }
				    }
		         }// end of if expired email enabled

		         //send reminder expired email
		         if(osc_item_advanced_ad_management_adEmailEx() == 1 && aam_reminder_days() != 0 && osc_item_advanced_ad_management_deleteDays() != 0) {
					 if($pCats){
						if(osc_item_advanced_ad_management_deleteDays() > aam_reminder_days()) {
							$reminderDays = osc_item_advanced_ad_management_deleteDays() - aam_reminder_days();
				            if(item_is_expired($itemA, $reminderDays)) {
				               $exEmailed = ModelAAM::newInstance()->getLimitUser($itemA['pk_i_id']);
				               if($exEmailed['ex_email'] == 1 && $exEmailed['ex_email_remind'] != 1 ) {
				                  item_expired_email($itemA['pk_i_id'], $repub['r_secret'], aam_reminder_days() );
				                  // update the user limit to state that the expired email has been sent.
				                  ModelAAM::newInstance()->updateLimitUser('ex_email_remind', 1, $itemA['pk_i_id']);
				                  // adds a log entry stating that the expired emial was sent.
				                  ModelAAM::newInstance()->insertLog('Expired reminder email sent. Successful', $itemA['pk_i_id']);
				                  $mItems = new ItemActions(true);
				                  $success = $mItems->mark($itemA['pk_i_id'], 'expired');
				               }// end check of expired email has been sent.
				            }// end of is item expired check.
				            else {
				            	ModelAAM::newInstance()->insertLog('Ad is not expired so no reminder email sent.', $itemA['pk_i_id']);
				            }
						}// end of is expired
						else {
							ModelAAM::newInstance()->insertLog('Reminder days is greater then the deletion day!', $itemA['pk_i_id']);
						}
					}
				 }// end of reminder expired email

		         //delete expired ads
		         if(osc_item_advanced_ad_management_deleteDays() != 0){
		           if($pCats){
		            if(item_is_expired($itemA, osc_item_advanced_ad_management_deleteDays()) && $itemA['dt_expiration'] != '9999-12-31 23:59:59'){

		               $item   = Item::newInstance()->listWhere("i.pk_i_id = '%s' AND ((i.s_secret = '%s') OR (i.fk_i_user_id = '%d'))", $itemA['pk_i_id'], $itemA['s_secret'], $itemA['fk_i_user_id']);
		               if (count($item) == 1) {
		                  $mItems = new ItemActions(true);
		                  $success = $mItems->delete($item[0]['s_secret'], $item[0]['pk_i_id']);
		                  if($success) {
		                     ModelAAM::newInstance()->insertLog('Cron item deleted. Successful.', $item[0]['pk_i_id']);
		                  } else {
							 ModelAAM::newInstance()->insertLog('Cron item could not be deleted; item not found.', $itemA['pk_i_id']);
							 if(aam_debug(false) ){
								ModelAAM::newInstance()->insertLog('Debug: secret ' . $item[0]['s_secret'] . ' itemId ' . $item[0]['pk_i_id'], $itemA['pk_i_id']);
							 }
		                  } // end else not successful
		               }// end count of items that need to be deleted.
		            }// end of if item is expired past set expired date
		            else {
		            	ModelAAM::newInstance()->insertLog('Ad not expired. So ad is not deleted.', $itemA['pk_i_id']);
		            }
		           }// end check if item is in pCat
		         } else {// end check if deleteDays is not equal to zero.
					ModelAAM::newInstance()->insertLog('Delete expired ads disabled.', $itemA['pk_i_id']);
				}

		      }//end of foreach
		      ModelAAM::newInstance()->insertLog('Cron complete.');
		      // to store more or less rows in the database either increase the number to store more in database
		      // or decrease the number to store less in the database.
		      $logCount = ModelAAM::newInstance()->countLog(250);
		      if( is_array($logCount) && file_exists(osc_content_path() . 'uploads/AAM.log') ) {
				    // Logging class initialization
					$log = new Logging();

					// set path and name of log file (optional)
					$log->lfile(osc_content_path() . 'uploads/AAM.log');


						// write message to the log file
						$log->lwrite('');
						$log->lwrite('--------------------------------------------------------------------------------');
						$log->lwrite('');

						foreach($logCount as $txtLog) {
							$log->lwrite('Refernce # ' . $txtLog['id'] . ' Log date ' . $txtLog['log_date'] . ' Item id ' . $txtLog['fk_i_item_id'] . ' Msg ' . $txtLog['error_action']);
							ModelAAM::newInstance()->deleteLogById($txtLog['id']);
						}

						// close log file
						$log->lclose();
			  }
			  osc_set_preference('aam_cron_last_run', strtotime('today'), 'plugin-item_advanced_ad_management', 'INTEGER');
			  osc_reset_preferences();
		} else{
			ModelAAM::newInstance()->insertLog('Cron has already been exicuted for the day.');
		}
    }  //end item_advanced_ad_management_cron() function.


   require_once('AAM_emails.php');

    /**
     * Create automatically the url of the item details page
     * Same function from hDefines just with the option to create the url for a certian item.
     *
     * @param string $locale
     * @return string
     */
    function osc_item_url_advanced($id = '', $locale = '') {
		if ($id != ''){
			$item = Item::newInstance()->findByPrimaryKey($id);
			$itemId = $id;
			$itemCat = $item['fk_i_category_id'];
		} else{
			$itemId = osc_item_id();
			$itemCat = osc_item_category_id();
		}
        if ( osc_rewrite_enabled() ) {
            $url = osc_get_preference('rewrite_item_url');
            if( preg_match('|{CATEGORIES}|', $url) ) {
                $sanitized_categories = array();
                $cat = Category::newInstance()->hierarchy($itemCat) ;
                for ($i = (count($cat)); $i > 0; $i--) {
                    $sanitized_categories[] = $cat[$i - 1]['s_slug'];
                }
                $url = str_replace('{CATEGORIES}', implode("/", $sanitized_categories), $url);
            }
            $url = str_replace('{ITEM_ID}', osc_sanitizeString($itemId), $url);
            $url = str_replace('{ITEM_CITY}', osc_sanitizeString(osc_item_city()), $url);
            $url = str_replace('{ITEM_TITLE}', osc_sanitizeString(osc_item_title()), $url);
            $url = str_replace('?', '', $url);
            if($locale!='') {
                $path = osc_base_url().$locale."/".$url;
            } else {
                $path = osc_base_url().$url;
            }
        } else {
            $path = osc_item_url_ns($itemId, $locale);
        }
        return $path ;
    }

    function item_advanced_ad_management_delete($itemId){
       ModelAAM::newInstance()->deleteLimitUser($itemId);
    }

    function advanced_ad_management_config() {
       osc_admin_render_plugin(osc_plugin_path(dirname(__FILE__)) . '/admin.php') ;
    }

    function advanced_ad_management_footer() {
       ?>
       <script type="text/javascript">
       $(document).ready(function(){

		  var payPost = $("#payPost").val();
		  if(payPost =='1') {
		  	$("#freeTimes").removeAttr('disabled');
		  }else{
		  	$("#freeTimes").attr('disabled','disabled');
		  }

        $("#payPost").change(function(){
            var payPost_id = $(this).val();
            if(payPost_id == '1') {
            	$("#freeTimes").removeAttr('disabled');
            }
            else {
            	$("#freeTimes").attr('disabled','disabled');
            }
        });

        $("#deleteDays").change(function(){
			if($(this).val() != 0){
				alert('<?php _e("This change will only effect new listings published after you save!","advanced_ad_management");?>');
			}
        });
       });
       </script>

		<script type="text/javascript" >
			$(document).ready(function () {
			  $('legend').click(function(){
					$(this).parent().find('.content').slideToggle("slow");
					$(this).parent().find('.showContent').slideToggle("slow");
			  });
			  $('.showContent').click(function(){
					$(this).parent().find('.content').slideToggle("slow");
					$(this).parent().find('.showContent').slideToggle("slow");
			  });
			});

		</script>

       <?php
    }

    function aam_admin_header() {
	?>
    <style>
    .ico-aam{
        background-image: url('<?php echo osc_base_url();?>oc-content/plugins/<?php echo osc_plugin_folder(__FILE__);?>images/Gears48.png') !important;
    }
    body.compact .ico-aam{
        background-image: url('<?php echo osc_base_url();?>oc-content/plugins/<?php echo osc_plugin_folder(__FILE__);?>images/Gears32.png') !important;
    }
	</style>
    <?php
	}

	// 3.0+ admin menu structure
	if(osc_version() >= 300){
		osc_add_admin_menu_page(
		   __('Advanced Ad Management', 'advanced_ad_management'),  // menu title
		   '#',                                         			// menu url
		   'aam'	                                    			// menu id
		) ;

		osc_add_admin_submenu_page(
		   'aam',	                                    					// menu id
		   __('Configure', 'advanced_ad_management'),           			// submenu title
		   osc_admin_render_plugin_url('advanced_ad_management/admin.php'), // submenu url
		   'aam_configure' 	                           			    		// submenu id
		) ;

		osc_add_admin_submenu_page(
		   'aam',	                                    					// menu id
		   __('Help / FAQ', 'advanced_ad_management'),           			// submenu title
		   osc_admin_render_plugin_url('advanced_ad_management/help.php'), 	// submenu url
		   'aam_help' 	                           			    			// submenu id
		) ;

		osc_add_admin_submenu_page(
		   'aam',	                                    				    // menu id
		   __('Log', 'advanced_ad_management'),           					// submenu title
		   osc_admin_render_plugin_url('advanced_ad_management/log.php'), 	// submenu url
		   'aam_log' 	                           			    			// submenu id
		) ;

	}

	function aamTitle($title){
	        $title = 'Plugin - Advanced Ad Management <a href="'.osc_admin_render_plugin_url(osc_plugin_folder(__FILE__) . 'settings.php') . '" class="btn ico ico-32 ico-engine float-right"></a>';
	        /*if($file[1] == 'admin_list.php'){
	        <a class="btn ico ico-32 ico-help float-right" href="#"></a>
	        <script type="text/javascript" >
					$(document).ready(function(){
        				$("#help-box").append("<h3>Manage Promo Codes</h3>");
        				$("#help-box").append("<p>Some really great text will go here soon. How much text can I put here I wonder if it has a limit or if it expands more I will have to ponder that for a while. pondering!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! !!!!!!!!!!!!!!!!. It looks as though it can handle a lot of text well I guess we better keep filling it up with text wow my fingers are tierd of this. Just starting the 3 rd line lets see if I can read all of this text lets hope so.<br /><br /><br /> Just starting the 3 rd line lets see if I can read all of this text lets hope so.<br /><br /><br /> Just starting the 3 rd line lets see if I can read all of this text lets hope so.");
        			});
			  </script>
			  }
	        */

			return $title;
	 }

	 if( osc_version() >= 300){
		 $file = explode('/', Params::getParam('file'));
	     if($file[0] == 'advanced_ad_management'){
			osc_add_filter('custom_plugin_title','aamTitle');
		 }
	 }

	 function admin_repub($more_options, $item) {
		$pCats = osc_is_this_category('advanced_ad_management', osc_item_category_id() );
		$rSecret   = ModelAAM::newInstance()->getLimitUser(osc_item_id() );

		if( osc_is_moderator() && aam_mod_repub() ==1 && $pCats) {
			if(check_if_item_expired() || aam_debug() ) {
				$more_options[] = '<span class="adminRepub"><a href="'. osc_base_url() . 'oc-content/plugins/advanced_ad_management/item_republish.php?repub=admin_repub&id=' . osc_item_id() . '&rSecret=' . $rSecret['r_secret'] . '">' . __('Republish Ad','advanced_ad_management') .  '</a></span>';
			}
		} elseif ( !osc_is_moderator() && ($pCats || aam_debug() ) ) {
			if(check_if_item_expired() || aam_debug() ) {
				$more_options[] = '<span class="adminRepub"><a href="'. osc_base_url() . 'oc-content/plugins/advanced_ad_management/item_republish.php?repub=admin_repub&id=' . osc_item_id() . '&rSecret=' . $rSecret['r_secret'] . '">' . __('Republish Ad (Debug mode enabled)','advanced_ad_management') .  '</a></span>';
			}
		}
		return $more_options;
	 }
	  if( osc_version() >= 300){
		osc_add_filter('actions_manage_items', 'admin_repub');
	  }

	function js_aam_message($message, $type = 'error', $class = 'flashmessage') {
	    $class = 'flashmessage';
	    //$type = 'error'; // 'error', 'ok', 'info', 'warning'
	    //$message = 'My message';
	    ?>
	    <script type="text/javascript">
	        $("#flash_js").attr("class", "<?php echo strtolower($class) . ' ' . strtolower($class) . '-' .$type; ?>");
	        var msg = '<a class="btn ico btn-mini ico-close">x</a><?php echo osc_apply_filter('flash_message_text', $message); ?>' ;
	        $("#flash_js").html(msg);
	        $("#flash_js").show();

	    </script>
	    <?php
	};

	function aam_before_html() {
		if(osc_is_home_page()) {
			View::newInstance()->_exportVariableToView('latestItems', ModelAAM::newInstance()->getLatestAAMitems(osc_max_latest_items_at_home()));
		}
	}

	function aam_search_conditions($params = null) {
		if(@$params['sOrder'] == '' || (@$params['sOrder'] == 'dt_pub_date' && @$params['iOrderType'] == 'desc')) {
			Search::newInstance()->addTable(DB_TABLE_PREFIX. 't_item_adManage_limit');
			Search::newInstance()->addConditions(DB_TABLE_PREFIX. 't_item.pk_i_id =' . DB_TABLE_PREFIX. 't_item_adManage_limit.fk_i_item_id');
			Search::newInstance()->order('repub_date', 'DESC', '%st_item_adManage_limit');
		}
	}

    // This is needed in order to be able to activate the plugin
    osc_register_plugin(osc_plugin_path(__FILE__), 'advanced_ad_management_install') ;
    osc_add_hook(__FILE__ . "_configure", 'advanced_ad_management_config');
    // This is a hack to show a Uninstall link at plugins table (you could also use some other hook to show a custom option panel)
    osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'advanced_ad_management_uninstall') ;

    // Add the help to the menu
    if(osc_version() < 300){
		osc_add_hook('admin_menu', 'item_advanced_ad_management_admin_menu');
	}
    // Add cron
    osc_add_hook('cron_daily', 'item_advanced_ad_management_cron');
    // run after item is posted
    osc_add_hook('item_form_post','item_advanced_ad_management_posted');
    // run hook when item is deleted
    osc_add_hook('delete_item','item_advanced_ad_management_delete');
    // add javascript to the footer
    osc_add_hook('admin_footer','advanced_ad_management_footer');
    osc_add_hook('admin_header', 'aam_admin_header');

    osc_add_hook('before_html', 'aam_before_html', 10);
    // Hook for adding new search conditions
	osc_add_hook('search_conditions', 'aam_search_conditions');
?>
