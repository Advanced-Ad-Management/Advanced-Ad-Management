<?php 

$p_iPageSize = 50 ;
						  
$p_iPage      = 0;
$pageSelect   = 0;
if( is_numeric(Params::getParam('iPage')) && Params::getParam('iPage') > 0 ) {
	if(osc_version < 300) {
		$p_iPage      = (intval(Params::getParam('iPage'))) *50;	
	} else {
		$p_iPage      = (intval(Params::getParam('iPage'))-1) *50;	
	}
	$pageSelect   = intval(Params::getParam('iPage')) -1;	
}
$aamLog = ModelAAM::newInstance()->getLogData($p_iPage, $p_iPageSize);
?>
<style type="text/css" >
.highlight{
	background-color:#F3F3F3;
}
</style>
<h2><?php _e('Log information','advanced_ad_management'); ?></h2>
<div class="table-contains-actions">
            <table class="table" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>                        
                        <th><?php _e('Refernce #') ; ?></th>
                        <th><?php _e('Date') ; ?></th>
                        <th><?php _e('Item Id') ; ?></th>
                        <th><?php _e('Message') ; ?></th>                      
                    </tr>
                </thead>
                <tbody>
					<?php if(count($aamLog) > 0) { ?>
					<?php foreach($aamLog as $log) { ?>
					<?php $rowClass = '';
					if($log['fk_i_item_id'] == 0) {
						$rowClass = 'highlight';
					}
					?>
					<tr class = "<?php echo $rowClass; ?>">
						<td><?php echo $log['id']; ?></td>
						<td><?php echo $log['log_date']; ?></td>
						<td>&nbsp;<?php echo $log['fk_i_item_id']; ?></td>
						<td><?php echo $log['error_action']; ?></td>
					</tr>
					<?php } ?>
					<?php } else { ?>
					<tr>
						<td colspan="4" >No log data to report.</td>
					</tr>
					<?php } ?>
                </tbody>
            </table>
        </div>
        <div class="has-pagination">
		<?php
		 $params = array('selected' => $pageSelect , 'total'    => ceil(View::newInstance()->_get('search_total_items')/$p_iPageSize ), 'url' => osc_admin_base_url(true) . '?page=plugins&amp;action=renderplugin&amp;file=advanced_ad_management/log.php&amp;iPage={PAGE}');                         
		 echo osc_pagination($params);  
		?>
		</div>
