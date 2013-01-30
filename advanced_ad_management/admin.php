<?php
    $advanced_ad_management_plugin_data = Plugins::getInfo('advanced_ad_management/index.php');

    if(Params::getParam('addExistingAds') == 1){
    $allItem = ModelAAM::newInstance()->getDistinctItems();
    $dao_preference = new Preference();
    foreach($allItem as $itemB) {
        $r_secret = '';
        $r_secret = osc_genRandomPassword();
        ModelAAM::newInstance()->insertNewLimit($itemB['fk_i_item_id'], $r_secret);
    }
    $dao_preference->update(array("s_value" => '1'), array("s_section" => "plugin-item_advanced_ad_management", "s_name" => "advanced_ad_management_installed")) ;
    unset($dao_preference) ;
    Preference::newInstance()->toArray() ;
    echo '<script>location.href="' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=advanced_ad_management/admin.php"</script>';
    }// end of the addExistingAds section


    //Master setting for expired days.
    //Jesse's Ad Expiration plugin.
     $days = '';
    if(Params::getParam('days') != ''){
	$days = Params::getParam('days');
    }
    else {
    $data = ModelAAM::newInstance()->getCatExp();
    $days = $data['i_expiration_days'];
    }

    if( Params::getParam('option') == 'update' )
    {
	if($days>999)$days=999; // Set maximum days as per the sql structure of the field: i_expiration_days = int(3)
	$catInfo = Category::newInstance()->listAll();
	foreach ($catInfo as $catI) {
		//Category::newInstance()->updateByPrimaryKey(array('fields' => array('i_expiration_days' => $days)), $catI['pk_i_id']);
		ModelAAM::newInstance()->updateCatExp($days, $catI['pk_i_id']);
	}

	osc_add_flash_ok_message(__('Ad expiration successfully set for all categories', 'advanced_ad_management'),'admin');

	echo '<script>location.href="'.osc_admin_render_plugin_url('advanced_ad_management/admin.php?mUpdated=1').'"</script>';
    }
    //end Ad Expiration section

    // repub ALL ads
    $repubDays = Params::getParam('repubdays');
    if( Params::getParam('option') == 'gUpdate' && $repubDays != '' )
    {

		if(osc_validate_number ($repubDays) ) {

			ModelAAM::newInstance()->insertLog('All ads re publish started.');

		    $allItems = ModelAAM::newInstance()->getAllItemsCron();
		    foreach($allItems as $itemA) {
				$pCats = osc_is_this_category('advanced_ad_management', $itemA['fk_i_category_id'] );
				if($pCats) {
					$newExp = ModelAAM::newInstance()->aam_updateExpirationDate($itemA['pk_i_id'], date('Y-m-d H:i:s', strtotime("+" . $repubDays . " day", strtotime($itemA['dt_expiration']))) );
					if( $newExp != false && $newExp != '9999-12-31 23:59:59'){
						ModelAAM::newInstance()->insertLog('Items expire date updated exp date ' . $newExp, $itemA['pk_i_id']);
					} else {
						$errorMess = ModelAAM::newInstance()->dao->getErrorDesc();
						ModelAAM::newInstance()->insertLog('Item expire date not updated.' , $itemA['pk_i_id']);
						ModelAAM::newInstance()->insertLog($errorMess, $itemA['pk_i_id']);
					}
				}
			}

			osc_add_flash_ok_message(__('All ads successfully re published', 'advanced_ad_management'),'admin');

			ModelAAM::newInstance()->insertLog('All ads re publish finished.');
		} else {
			osc_add_flash_error_message(__('Repub days must be a number', 'advanced_ad_management'),'admin');
		}

	echo '<script>location.href="'.osc_admin_render_plugin_url('advanced_ad_management/admin.php').'"</script>';
    }
    //end repub ALL ads

    // repub all expired ads
    if( Params::getParam('option') == 'reExpUpdate')
    {
		ModelAAM::newInstance()->insertLog('All expired ads re publish started.');

		$i = 0;

	    $allItems = ModelAAM::newInstance()->getAllItemsCron();
	    foreach($allItems as $itemA) {
			$pCats = osc_is_this_category('advanced_ad_management', $itemA['fk_i_category_id'] );
			$itemCat = osc_get_category('id', $itemA['fk_i_category_id']);

			if($pCats && repub_if_repub($itemA)) {
				$newExp = ModelAAM::newInstance()->aam_updateExpirationDate($itemA['pk_i_id'], date('Y-m-d H:i:s', strtotime("+" . $itemCat['i_expiration_days'] . " day")) );
				if( $newExp != false && $newExp != '9999-12-31 23:59:59'){
					ModelAAM::newInstance()->insertLog('Items expire date updated exp date ' . $newExp, $itemA['pk_i_id']);
					$i++;
				} else {
					$errorMess = ModelAAM::newInstance()->dao->getErrorDesc();
					ModelAAM::newInstance()->insertLog('Item expire date not updated.' , $itemA['pk_i_id']);
					ModelAAM::newInstance()->insertLog($errorMess, $itemA['pk_i_id']);
				}
			}
		}
		if($i > 0) {
			osc_add_flash_ok_message(sprintf(_n('%d expired ad successfully re published', '%d expired ads successfully re published', $i, 'advanced_ad_management'), $i),'admin');
		} else {
			osc_add_flash_warning_message(__('No expired ads to re publishe', 'advanced_ad_management'),'admin');
		}

		ModelAAM::newInstance()->insertLog('All expired ads re publish finished.');

	echo '<script>location.href="'.osc_admin_render_plugin_url('advanced_ad_management/admin.php').'"</script>';
    }
    //end repub all expired ads

    // delete all expired ads
    $delDays = Params::getParam('cleanDays');
    if( Params::getParam('option') == 'adCleanup' && $delDays != '' )
    {

		if(osc_validate_number ($delDays) ) {

			ModelAAM::newInstance()->insertLog('Delete all expired ads started.');

			$i = 0;

		    $allItems = ModelAAM::newInstance()->getAllItemsCron();
		    foreach($allItems as $itemA) {
				$pCats = osc_is_this_category('advanced_ad_management', $itemA['fk_i_category_id'] );
				if($pCats && repub_if_repub($itemA)) {
					if(item_is_expired($itemA, $delDays, true) && $itemA['dt_expiration'] != '9999-12-31 23:59:59'){
					   $item   = Item::newInstance()->listWhere("i.pk_i_id = '%s' AND ((i.s_secret = '%s') OR (i.fk_i_user_id = '%d'))", $itemA['pk_i_id'], $itemA['s_secret'], $itemA['fk_i_user_id']);
		               if (count($item) == 1) {
		                  $mItems = new ItemActions(true);
		                  $success = $mItems->delete($item[0]['s_secret'], $item[0]['pk_i_id']);
		                  if($success) {
		                     ModelAAM::newInstance()->insertLog('Cleanup action item deleted. Successful.', $item[0]['pk_i_id']);
		                     $i++;
		                  } else {
							 ModelAAM::newInstance()->insertLog('Cleanup action item could not be deleted; item not found.', $itemA['pk_i_id']);
							 if(aam_debug(false) ){
								ModelAAM::newInstance()->insertLog('Debug: secret ' . $item[0]['s_secret'] . ' itemId ' . $item[0]['pk_i_id'], $itemA['pk_i_id']);
							 }
		                  } // end else not successful
		               }// end count of items that need to be deleted.
					}
				}
			}
			if($i > 0) {
				osc_add_flash_ok_message(sprintf(_n('%d expired ad was deleted successfully', '%d expired ads where deleted successfully', $i, 'advanced_ad_management'), $i),'admin');
			} else {
				osc_add_flash_warning_message(__('No expired ads to delete', 'advanced_ad_management'),'admin');
			}

			ModelAAM::newInstance()->insertLog('Delete all expired ads finished.');
		} else {
			osc_add_flash_error_message(__('Delete days must be a number', 'advanced_ad_management'),'admin');
		}

	echo '<script>location.href="'.osc_admin_render_plugin_url('advanced_ad_management/admin.php').'"</script>';
    }
    //end delete all expired ads

    // delete all non active ads
    $nonActiveDays = Params::getParam('cleanDeactiveDays');
    $runTime = Params::getParam('cleanDeactiveRunTime');
    if( Params::getParam('option') == 'adCleanupDeactive' && $nonActiveDays != '' )
    {
		osc_set_preference('aam_deactivated_run_time', $runTime, 'plugin-item_advanced_ad_management', 'STRING');
		if($runTime == 'day' || $runTime == 'week'){
			osc_set_preference('aam_deactivated_days', $nonActiveDays, 'plugin-item_advanced_ad_management', 'INTEGER');
		}
		osc_reset_preferences();

		if(osc_validate_number($nonActiveDays) ) {
			if(aam_debug(false)){
				ModelAAM::newInstance()->insertLog('Delete all non active ads started.');
			}

			$i = 0;

		    $allItems = ModelAAM::newInstance()->getAllItemsCron();

		    foreach($allItems as $itemA) {
				$pCats = osc_is_this_category('advanced_ad_management', $itemA['fk_i_category_id'] );
				if($pCats) {
					   $item = Item::newInstance()->listWhere("i.pk_i_id = '%s' AND ((i.s_secret = '%s') OR (i.fk_i_user_id = '%d')) AND b_active = 0", $itemA['pk_i_id'], $itemA['s_secret'], $itemA['fk_i_user_id']);
		               if (count($item) == 1 && strtotime("-" . $nonActiveDays ." days") >= strtotime($item[0]['dt_pub_date']) ) {
		                  $mItems = new ItemActions(true);
		                  $success = $mItems->delete($item[0]['s_secret'], $item[0]['pk_i_id']);
		                  if($success) {
		                     ModelAAM::newInstance()->insertLog('Non active listing deleted. Successful.', $item[0]['pk_i_id']);
		                     $i++;
		                  } else {
							 ModelAAM::newInstance()->insertLog('Non active listing could not be deleted; listing not found.', $itemA['pk_i_id']);
							 if(aam_debug(false) ){
								ModelAAM::newInstance()->insertLog('Debug: secret ' . $item[0]['s_secret'] . ' itemId ' . $item[0]['pk_i_id'], $itemA['pk_i_id']);
							 }
		                  } // end else not successful
		               }// end count of items that need to be deleted.
				}
			}
			if($i > 0) {
				osc_add_flash_ok_message(sprintf(_n('%d Non activated ad was deleted successfully', '%d Non activated ads where deleted successfully', $i, 'advanced_ad_management'), $i),'admin');
			} else {
				osc_add_flash_warning_message(__('No non activated ads to delete', 'advanced_ad_management'),'admin');
			}
			if(aam_debug(false)){
				ModelAAM::newInstance()->insertLog('Delete all non active ads finished.');
			}
		} else {
			osc_add_flash_error_message(__('Non active days must be a number', 'advanced_ad_management'),'admin');
		}

	echo '<script>location.href="'.osc_admin_render_plugin_url('advanced_ad_management/admin.php').'"</script>';
    }
    //end delete all non active ads

    $expire_days            = '';
    $dao_preference = new Preference();
    if(Params::getParam('expire') != '') {
        $expire_days  = Params::getParam('expire');
    } else {
        $expire_days  = (osc_advanced_ad_management_expire() != '') ? osc_advanced_ad_management_expire() : '' ;
    }

    $payPost            = '';
    $dao_preference = new Preference();
    if(Params::getParam('payPost') != '') {
        $payPost  = Params::getParam('payPost');
    } else {
        $payPost  = (osc_advanced_ad_management_payperpost() != '') ? osc_advanced_ad_management_payperpost() : '' ;
    }

    $rTimes            = '';
    $dao_preference = new Preference();
    if(Params::getParam('rTimes') != '') {
        $rTimes  = Params::getParam('rTimes');
    } else {
        $rTimes  = (osc_advanced_ad_management_repubTimes() != '') ? osc_advanced_ad_management_repubTimes() : '' ;
    }

    $freeTimes            = '';
    $dao_preference = new Preference();
    if(Params::getParam('freeTimes') != '') {
        $freeTimes  = Params::getParam('freeTimes');
    } else {
        $freeTimes  = (osc_item_advanced_ad_management_freeRepubs() != '') ? osc_item_advanced_ad_management_freeRepubs() : '' ;
    }

    $adEmailEx            = '';
    $dao_preference = new Preference();
    if(Params::getParam('adEmailEx') != '') {
        $adEmailEx  = Params::getParam('adEmailEx');
    } else {
        $adEmailEx  = (osc_item_advanced_ad_management_adEmailEx() != '') ? osc_item_advanced_ad_management_adEmailEx() : '' ;
    }

    $exReminderDays            = '';
    $dao_preference = new Preference();
    if(Params::getParam('exReminderDays') != '') {
        $exReminderDays  = Params::getParam('exReminderDays');
    } else {
        $exReminderDays  = (aam_reminder_days() != '') ? aam_reminder_days() : '' ;
    }

    $deleteDays            = '';
    $dao_preference = new Preference();
    if(Params::getParam('deleteDays') != '') {
        $deleteDays  = Params::getParam('deleteDays');
    } else {
        $deleteDays  = (osc_item_advanced_ad_management_deleteDays() != '') ? osc_item_advanced_ad_management_deleteDays() : '' ;
    }

    if( Params::getParam('option') == 'stepone' ) {
        $dao_preference->update(array("s_value" => $expire_days), array("s_section" => "plugin-item_advanced_ad_management", "s_name" => "advanced_ad_management_expire")) ;
        $dao_preference->update(array("s_value" => $payPost), array("s_section" => "plugin-item_advanced_ad_management", "s_name" => "advanced_ad_management_payperpost")) ;
        $dao_preference->update(array("s_value" => $rTimes), array("s_section" => "plugin-item_advanced_ad_management", "s_name" => "advanced_ad_management_repubTimes")) ;
        $dao_preference->update(array("s_value" => $freeTimes), array("s_section" => "plugin-item_advanced_ad_management", "s_name" => "advanced_ad_management_freeRepubs")) ;
        $dao_preference->update(array("s_value" => $adEmailEx), array("s_section" => "plugin-item_advanced_ad_management", "s_name" => "advanced_ad_management_expireEmail")) ;
        $dao_preference->update(array("s_value" => $exReminderDays), array("s_section" => "plugin-item_advanced_ad_management", "s_name" => "advanced_ad_management_reminderDays")) ;
        $dao_preference->update(array("s_value" => $deleteDays), array("s_section" => "plugin-item_advanced_ad_management", "s_name" => "advanced_ad_management_deleteDays")) ;
        echo js_aam_message(osc_esc_js(__('Settings Saved', 'advanced_ad_management')),'ok');
        osc_reset_preferences();
    }
    unset($dao_preference) ;

?>
	<style type="text/css">
		.dDbold {
			font-weight:bold;
			font-size:12pt;
		}
	</style>

    <fieldset style="border: 1px solid #ccc; padding-left:10px; padding-bottom: 10px; background: #eee; -moz-border-radius:20px; -webkit-border-radius:20px; border-radius: 20px;">
    <h2><?php _e('Configuration', 'advanced_ad_management'); ?></h2>
        <fieldset style="border: 1px solid #ccc; padding:10px; background: #ddd; -moz-border-radius:10px; -webkit-border-radius:10px; border-radius: 10px;">
        <legend><?php _e('Step 1','advanced_ad_management'); ?></legend>
        <?php if (!osc_item_advanced_ad_management_installed() ==1) {
               echo '<a href="' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=advanced_ad_management/admin.php&addExistingAds=1">' . __('Click here to finish the install','advanced_ad_management') . '</a>';
          } else {_e('Done','advanced_ad_management');} ?>
        </fieldset>

        <fieldset style="border: 1px solid #ccc; padding:10px; background: #ddd; -moz-border-radius:10px; -webkit-border-radius:10px; border-radius: 10px;">
        <legend><?php _e('Step 2','advanced_ad_management'); ?></legend>
        <?php
        $catSet = ModelAAM::newInstance()->getPluginCatCount('advanced_ad_management');
        if (($catSet < 1) && (osc_item_advanced_ad_management_installed() ==1) ) {?>
               <a href="<?php echo osc_admin_base_url(true) . '?page=plugins&action=configure&plugin=advanced_ad_management/index.php'; ?>" ><?php _e('Configure which categories you want your users to be able to republish their ads in.','advanced_ad_management'); ?></a>
        <?php } else { ?>
               <a href="<?php echo osc_admin_base_url(true) . '?page=plugins&action=configure&plugin=advanced_ad_management/index.php'; ?>" ><?php _e('Manage which categories you want your users to be able to republish their ads in.','advanced_ad_management'); ?></a> <br />
               <?php echo $catSet . ' ' . __('categories allow users to republish ads','advanced_ad_management');?>
              <?php } ?>
        </fieldset>

   <?php // Jessie's category changer section.   ?>
   <?php $mUpdated = ''; $mUdated = Params::getParam('mUpdated'); ?>
   <form name="adexpiration" action="<?php echo osc_admin_base_url(true); ?>" method="POST" enctype="multipart/form-data" >
	<input type="hidden" name="page" value="plugins" />
	<input type="hidden" name="action" value="renderplugin" />
	<input type="hidden" name="file" value="advanced_ad_management/admin.php" />
	<input type="hidden" name="option" value="update" />
        <fieldset style="border: 1px solid #ccc; padding:10px; background: #ddd; -moz-border-radius:10px; -webkit-border-radius:10px; border-radius: 10px;">
        <legend><?php _e('Master Expire Settings','advanced_ad_management'); ?></legend>
        <div class="content" style="display:none;">
        <?php echo __('This powerful feature will change the ad expiration settings for','advanced_ad_management') . ' <b>' . __('ALL','advanced_ad_management') . '</b> ' . __('existing categories and subcategories.','advanced_ad_management'); ?> <br /><br />
        <label for="days" style="font-weight: bold;"><?php _e('Enter ad expiration in days (0 = no expiration, max = 999 days)', 'advanced_ad_management'); ?></label>:<br />
        <input type="text" name="days" id="days" value="<?php echo $days; ?>" /><?php echo __('(currently set at ','advanced_ad_management') . $days . __(' days)','advanced_ad_management');?><?php if($mUpdated == 1) {echo ' <b>' . __('Updated', 'advanced_ad_management') . '</b>' ;} ?>
        <br />
        <br />
        <input type="submit" value="<?php _e('Save Master Expire Settings', 'advanced_ad_management'); ?>" onclick="javascript:return confirm('<?php _e('This action can not be undone. Are you sure you want to continue?', 'advanced_ad_management'); ?>')" />
        </div>
        <div class="showContent">
			<?php _e('Click here to show the settings.', 'advanced_ad_management'); ?>
        </div>
        </fieldset>
    </form>
   <?php /*
   <form name="adRepub" action="<?php echo osc_admin_base_url(true); ?>" method="POST" enctype="multipart/form-data" >
	<input type="hidden" name="page" value="plugins" />
	<input type="hidden" name="action" value="renderplugin" />
	<input type="hidden" name="file" value="advanced_ad_management/admin.php" />
	<input type="hidden" name="option" value="gUpdate" />
        <fieldset style="border: 1px solid #ccc; padding:10px; background: #ddd; -moz-border-radius:10px; -webkit-border-radius:10px; border-radius: 10px;">
        <legend><?php _e('Republish ALL ads.','advanced_ad_management'); ?></legend>
        <div class="content" style="display:none;">
        <?php echo __('This feature will republish all ads for the entered amount.','advanced_ad_management'); ?> <br />
        <label for="repubdays" style="font-weight: bold;"><?php _e('Enter the number of days you want to extend the ads. (max = 999 days)', 'advanced_ad_management'); ?></label>:<br />
        <input type="text" name="repubdays" id="repubdays" value="" /> <br />
        <?php _e('The way this works is it takes the current expire date of the item and ads the set days above to make the new expiration date.', 'advanced_ad_management'); ?>
        <br />
        <br />
        <input type="submit" value="<?php _e('Extend Ads', 'advanced_ad_management'); ?>" onclick="javascript:return confirm('<?php _e('This action can not be undone. Are you sure you want to continue?', 'advanced_ad_management'); ?>')" />
        </div>
        <div class="showContent">
			<?php _e('Click here to show the settings.', 'advanced_ad_management'); ?>
        </div>
        </fieldset>
    </form>
    */ ?>

    <form name="adRepubEXp" action="<?php echo osc_admin_base_url(true); ?>" method="POST" enctype="multipart/form-data" >
	<input type="hidden" name="page" value="plugins" />
	<input type="hidden" name="action" value="renderplugin" />
	<input type="hidden" name="file" value="advanced_ad_management/admin.php" />
	<input type="hidden" name="option" value="reExpUpdate" />
        <fieldset style="border: 1px solid #ccc; padding:10px; background: #ddd; -moz-border-radius:10px; -webkit-border-radius:10px; border-radius: 10px;">
        <legend><?php _e('Republish all Expired ads.','advanced_ad_management'); ?></legend>
        <div class="content" style="display:none;">
        <?php echo __('This feature will republish ALL expired ads.','advanced_ad_management'); ?> <br />
        <?php _e('The way this works is it takes all expired ads and republishes them for as many days as the expiration days set in the category settings from todays date.', 'advanced_ad_management'); ?>
        <br />
        <br />
        <input type="submit" value="<?php _e('Extend Expired Ads', 'advanced_ad_management'); ?>" onclick="javascript:return confirm('<?php _e('This action can not be undone. Are you sure you want to continue?', 'advanced_ad_management'); ?>')" />
        </div>
        <div class="showContent">
			<?php _e('Click here to show the settings.', 'advanced_ad_management'); ?>
        </div>
        </fieldset>
    </form>

    <form action="<?php osc_admin_base_url(true); ?>" method="post">
    <input type="hidden" name="page" value="plugins" />
    <input type="hidden" name="action" value="renderplugin" />
    <input type="hidden" name="file" value="advanced_ad_management/admin.php" />
    <input type="hidden" name="option" value="stepone" />
        <fieldset style="border: 1px solid #ccc; padding:10px; background: #ddd; -moz-border-radius:10px; -webkit-border-radius:10px; border-radius: 10px;">
        <legend><?php echo _e('Item Republish Settings','advanced_ad_management'); ?></legend>
        <?php if(osc_item_advanced_ad_management_installed() ==1 && $catSet > 1) { ?>
        <label for="expire" style="font-weight: bold;"><?php _e('Number of days before item expires to send email?', 'advanced_ad_management'); ?></label><br />
        <span style="font-size:small;color:gray;"><?php _e('Note: emails will only be sent on categories with <br />expiration days greater then 10', 'advanced_ad_management'); ?>.</span><br />
        <input type="text" name="expire" value="<?php echo $expire_days; ?>" /> <?php _e('(default: 4)','advanced_ad_management'); ?>
        <br />
        <br />
        <label for="rTimes" style="font-weight: bold;"><?php _e('Number of times an ad can be republished? (0 = unlimited)', 'advanced_ad_management'); ?></label><br />
        <input type="text" name="rTimes" id="rTimes" value="<?php echo $rTimes; ?>" /><?php _e('(default: 5)','advanced_ad_management');?>
        <br />
        <br />
        <fieldset style="border: 1px solid #ccc; padding:10px; background: #ddd; -moz-border-radius:10px; -webkit-border-radius:10px; border-radius: 10px;">
        <legend><?php _e('Paypal republish settings','advanced_ad_management'); ?></legend>
        <?php if(osc_advanced_ad_management_paypalPaypost() == 1) { ?>
        <label for="payPost" style="font-weight: bold;"><?php _e('Require users to pay the same fee they paid to publish the ad?', 'advanced_ad_management'); ?></label>:<br />
        <select name="payPost" id="payPost">
        	<option <?php if($payPost == 1){echo 'selected="selected"';}?>value='1'><?php _e('Yes','advanced_ad_management');?></option>
        	<option <?php if($payPost == 0){echo 'selected="selected"';}?>value='0'><?php _e('No','advanced_ad_management');?></option>
        </select>
        <br />
        <br />
        <label for="freeTimes" style="font-weight: bold;"><?php _e('Number of free republishes before requiring fee to be paid?', 'advanced_ad_management'); ?></label>:<br />
        <span style="font-size:small;color:gray;"><?php _e('Note: This number should be smaller then the "Number of times an <br />ad can be republished" unless it is set to zero.', 'advanced_ad_management'); ?>.</span><br />
        <input type="text" name="freeTimes" id="freeTimes" value="<?php echo $freeTimes; ?>" /><?php _e('(0 = no free republishes)','advanced_ad_management');?>
        <?php } else{ _e('Enable Paypal Pay Per Post option to see these settings','advanced_ad_management');} ?>
        </fieldset>
        <?php } ?>
        </fieldset>

        <fieldset style="border: 1px solid #ccc; padding:10px; background: #ddd; -moz-border-radius:10px; -webkit-border-radius:10px; border-radius: 10px;">
        <legend><?php _e('Ad Expiration Settings','advanced_ad_management'); ?></legend>
        <label for="adEmailEx" style="font-weight: bold;"><?php _e('Send an email once the ad has expired?', 'advanced_ad_management'); ?></label>:<br />
        <select name="adEmailEx" id="adEmailEx">
        	<option <?php if($adEmailEx == 1){echo 'selected="selected"';}?>value='1'><?php _e('Yes','advanced_ad_management');?></option>
        	<option <?php if($adEmailEx == 0){echo 'selected="selected"';}?>value='0'><?php _e('No','advanced_ad_management');?></option>
        </select>
        <br />
        <br />
        <label for="exReminderDays" style="font-weight: bold;"><?php _e('Send a reminder email XX days before the item is deleted? (0 = do not send reminder email)', 'advanced_ad_management'); ?></label>:<br />
        <input type="text" name="exReminderDays" id="exReminderDays" value="<?php echo $exReminderDays; ?>" /><?php _e('(default: 5)','advanced_ad_management'); ?>
        <br />
        <br />
        <label for="deleteDays" style="font-weight: bold;"><?php _e('Number of days until expired ads are deleted permanently?', 'advanced_ad_management'); ?></label>:<br />
        <span style="font-size:small;color:gray;"><?php _e('Note: If you have the number of days set ot zero you should edit the <br />email template "email_ad_expired" accordingly', 'advanced_ad_management'); ?>.</span><br />
        <input type="text" name="deleteDays" id="deleteDays" value="<?php echo $deleteDays; ?>" /><?php _e('(0 = never deleted)','advanced_ad_management');?>
        <br />
        <br />
        <div id="flashmessage" class="flashmessage flashmessage-inline flashmessage-warning" style="color: #505050; display: block; ">

			<p><?php echo __('When changing the deletion days it only applys to newly posted listings and republished listings that are posted/republished', 'advanced_ad_management') . ' <span class="dDbold">' .  __('after you save.','advanced_ad_management') . '</span>'; ?></p>
		</div>
        <br />
        <br />
        </fieldset>
        <?php if(osc_item_advanced_ad_management_installed() ==1 && $catSet > 1) { ?>
        <input type="submit" value="<?php _e('Save', 'advanced_ad_management'); ?>" />
        <?php } ?>
    </fieldset>
</form>
<br />
<fieldset style="border: 1px solid #ccc; padding-left:10px; padding-bottom: 10px; background: #eee; -moz-border-radius:20px; -webkit-border-radius:20px; border-radius: 20px;">
	<h2><?php _e('Ad Cleanup', 'advanced_ad_management'); ?></h2>

	<form name="adCleanup" action="<?php echo osc_admin_base_url(true); ?>" method="POST" enctype="multipart/form-data" >
		<input type="hidden" name="page" value="plugins" />
		<input type="hidden" name="action" value="renderplugin" />
		<input type="hidden" name="file" value="advanced_ad_management/admin.php" />
		<input type="hidden" name="option" value="adCleanup" />

		<fieldset style="border: 1px solid #ccc; padding:10px; background: #ddd; -moz-border-radius:10px; -webkit-border-radius:10px; border-radius: 10px;">
	        <legend><?php _e('Remove Expired ads.','advanced_ad_management'); ?></legend>

	        <?php echo __('This feature will delete ALL expired ads for the entered amount.','advanced_ad_management'); ?> <br />
	        <label for="cleanDays" style="font-weight: bold;"><?php _e('Delete ads that have been expired for more than XX days (XX is the below entered days)', 'advanced_ad_management'); ?></label>:<br />
	        <input type="text" name="cleanDays" id="cleanDays" value="" />
	        <br />
	        <br />
	        <input type="submit" value="<?php _e('Delete Expired Ads', 'advanced_ad_management'); ?>" onclick="javascript:return confirm('<?php _e('This action can not be undone. Are you sure you want to continue?', 'advanced_ad_management'); ?>')" />
        </fieldset>
	</form>
	<br />
	<form name="adCleanupDeactiv" action="<?php echo osc_admin_base_url(true); ?>" method="POST" enctype="multipart/form-data" >
		<input type="hidden" name="page" value="plugins" />
		<input type="hidden" name="action" value="renderplugin" />
		<input type="hidden" name="file" value="advanced_ad_management/admin.php" />
		<input type="hidden" name="option" value="adCleanupDeactive" />

		<fieldset style="border: 1px solid #ccc; padding:10px; background: #ddd; -moz-border-radius:10px; -webkit-border-radius:10px; border-radius: 10px;">
	        <legend><?php _e('Remove Ads that have not been activated.','advanced_ad_management'); ?></legend>

	        <label for="cleanDeactiveDays" style="font-weight: bold;"><?php _e('Delete non activated ads that where posted XX days ago and older. (XX is the below entered days)', 'advanced_ad_management'); ?></label>:<br />
	        <input type="text" name="cleanDeactiveDays" id="cleanDeactiveDays" value="<?php if(aam_non_active_run_time() != 'never') { echo aam_non_active_days(); } ?>" />
	        <br />
	        <br />
	        <label for="cleanDeactiveRunTime" style="font-weight: bold;"><?php _e('Do you want this to a reoccurring event?', 'advanced_ad_management'); ?></label>:<br />
	        <select name="cleanDeactiveRunTime">
				<option value="never"><?php _e('No, run just this once','advanced_ad_management'); ?></option>
				<option <?php if(aam_non_active_run_time() == 'day') { echo 'selected';} ?> value="day"><?php _e('Yes, Daily','advanced_ad_management'); ?></option>
				<option <?php if(aam_non_active_run_time() == 'week') { echo 'selected';} ?> value="week"><?php _e('Yes, Weekly','advanced_ad_management'); ?></option>
	        </select>
	        <br />
	        <br />
			<?php if(aam_non_active_run_time() != 'never' && aam_non_active_run_time() != '') { ?>
				<div id="flashmessage" class="flashmessage flashmessage-inline flashmessage-ok" style="color: #505050; display: block; ">
					<p><?php echo __('Non activated ads that where published', 'advanced_ad_management') . ' ' . aam_non_active_days() . ' ' . __('days ago and older will be automatically deleted every', 'advanced_ad_management') . ' ' . aam_non_active_run_time(); ?></p>
				</div>
				<br />
			<?php } ?>
	        <input type="submit" value="<?php _e('Delete Non Active Ads', 'advanced_ad_management'); ?>" onclick="javascript:return confirm('<?php _e('This action can not be undone. Are you sure you want to continue?', 'advanced_ad_management'); ?>')" />
        </fieldset>
	</form>
	<br />
    <?php echo __('Authors','advanced_ad_management') . ': ' . '<!-- <a target="_blank" href="' . $advanced_ad_management_plugin_data['plugin_uri'] . '<!-- "> -->' . $advanced_ad_management_plugin_data['author'] . '<!-- </a> -->' . ' ' . __('Version','advanced_ad_management') . ': ' . $advanced_ad_management_plugin_data['version']; ?>
</fieldset>
