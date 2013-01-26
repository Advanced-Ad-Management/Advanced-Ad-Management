<?php 
$pluginInfo = osc_plugin_get_info('advanced_ad_management/index.php'); 	

	$modPerm            = '';
    $dao_preference = new Preference();
    if(Params::getParam('modPerm') != '') {
        $modPerm = Params::getParam('modPerm');
    } else {
        $modPerm = (aam_mod_repub() != '') ? aam_mod_repub() : '' ;
    }
    
    $aamDebug            = '';
    $dao_preference = new Preference();
    if(Params::getParam('aamDebug') != '') {
        $aamDebug = Params::getParam('aamDebug');
    } else {
        $aamDebug = (osc_get_preference('advanced_ad_management_debug', 'plugin-item_advanced_ad_management') != '') ? osc_get_preference('advanced_ad_management_debug', 'plugin-item_advanced_ad_management') : '' ;
    }
    
    if( Params::getParam('option') == 'stepone' ) {
        $dao_preference->update(array("s_value" => $modPerm), array("s_section" =>"plugin-item_advanced_ad_management", "s_name" => "advanced_ad_management_mod")) ;
        $dao_preference->update(array("s_value" => $aamDebug), array("s_section" =>"plugin-item_advanced_ad_management", "s_name" => "advanced_ad_management_debug")) ;                        
        echo js_aam_message(osc_esc_js(__('Settings Saved', 'advanced_ad_management')),'ok');
    }
    unset($dao_preference) ;          
    
?>

<form action="<?php osc_admin_base_url(true); ?>" method="post">
    <input type="hidden" name="page" value="plugins" />
    <input type="hidden" name="action" value="renderplugin" />
    <input type="hidden" name="file" value="advanced_ad_management/settings.php" />
    <input type="hidden" name="option" value="stepone" />
    <div>
    <fieldset>
        <h2><?php _e('Settings', 'advanced_ad_management'); ?></h2>        
        <label for="modPerm" style="font-weight: bold;"><br /><?php _e('Allow Moderators the ability to republish ads?', 'advanced_ad_management'); ?></label>:<br /> 
		  <select name="modPerm" id="modPerm" <?php if(osc_version() < 300){echo 'disabled';} ?> > 
        	<option <?php if($modPerm == 1){echo 'selected="selected"';}?>value='1'><?php _e('Yes', 'advanced_ad_management'); ?></option>
        	<option <?php if($modPerm == 0){echo 'selected="selected"';}?>value='0'><?php _e('No', 'advanced_ad_management'); ?></option>
        </select>        
        <br />
        <label for="aamDebug" style="font-weight: bold;"><?php _e('Enable debuging. (Use with caution.)','advanced_ad_management'); ?></label>:<br />
        <select name="aamDebug" id="aamDebug">
        	<option <?php if($aamDebug ==1){echo 'selected="selected"';}?> value='1'><?php _e('Yes', 'advanced_ad_management'); ?></option>
        	<option <?php if($aamDebug ==0){echo 'selected="selected"';}?> value='0'><?php _e('No', 'advanced_ad_management'); ?></option>
        </select>
        <br />
        <br />
        <input type="submit" value="<?php _e('Save', 'advanced_ad_management'); ?>" />
        <br />
        <br />
        <?php echo __('Version','advanced_ad_management') . ' ' .$pluginInfo['version']; ?>        
     </fieldset>
    </div>
</form>
