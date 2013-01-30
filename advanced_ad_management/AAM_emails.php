<?php
/**
     * Send email to users when their ad is about to expire
     *
     * @param array $item
     * @param string $r_secret
     * @param integer $expire_days
     *
     * @dynamic tags
     *
     * '{CONTACT_NAME}', '{ITEM_TITLE}',
     * '{WEB_TITLE}', '{REPUBLISH_URL}', '{EXPIRE_DAYS}'
     */

    function item_expire_email($itemId, $r_secret, $expire_days = '') {
        $mPages = new Page() ;
        $aPage = $mPages->findByInternalName('email_ad_expire') ;
        $locale = osc_current_user_locale() ;
        $content = array();
        if(isset($aPage['locale'][$locale]['s_title'])) {
            $content = $aPage['locale'][$locale];
        } else {
            $content = current($aPage['locale']);
        }

        $item = Item::newInstance()->findByPrimaryKey($itemId);

        $secret = '';

        $secret = '&secret=' . $item['s_secret'];


        if($r_secret == ''){
			$repub = ModelAAM::newInstance()->getLimitUser($item['pk_i_id']);
			$r_secret = $repub['r_secret'];
		}

        $republish_url1    = osc_base_url() . 'oc-content/plugins/advanced_ad_management/item_republish.php?id=' . $item['pk_i_id'] . '&repub=republish&rSecret=' . $r_secret . $secret ;
        $republish_url    = '<a href="' . $republish_url1 . '" >' . $republish_url1 . '</a>';

        $words   = array();
        $words[] = array('{CONTACT_NAME}', '{ITEM_TITLE}', '{WEB_TITLE}', '{REPUBLISH_URL}', '{EXPIRE_DAYS}', '{ITEM_ID}');
        $words[] = array($item['s_contact_name'], $item['s_title'], osc_page_title(), $republish_url, $expire_days, $item['pk_i_id']) ;

        $title = osc_mailBeauty($content['s_title'], $words) ;
        $body  = osc_mailBeauty($content['s_text'], $words) ;

        $emailParams =  array('subject'  => $title
                             ,'to'       => $item['s_contact_email']
                             ,'to_name'  => $item['s_contact_name']
                             ,'body'     => $body
                             ,'alt_body' => $body);

        osc_sendMail($emailParams);
    }

    /**
     * Send email to users when their ad has expired
     *
     * @param array $item
     * @param string $r_secret
     * @param integer $permDeleted
     *
     * @dynamic tags
     *
     * '{CONTACT_NAME}', '{ITEM_TITLE}',
     * '{WEB_TITLE}', '{REPUBLISH_URL}', '{PERM_DELETED}'
     */

    function item_expired_email($itemId, $r_secret, $permDeleted) {
        $mPages = new Page() ;
        $aPage = $mPages->findByInternalName('email_ad_expired') ;
        $locale = osc_current_user_locale() ;
        $content = array();
        if(isset($aPage['locale'][$locale]['s_title'])) {
            $content = $aPage['locale'][$locale];
        } else {
            $content = current($aPage['locale']);
        }

        $item = Item::newInstance()->findByPrimaryKey($itemId);

        $secret = '';

        $secret = '&secret=' . $item['s_secret'];


        $republish_url1    = osc_base_url() . 'oc-content/plugins/advanced_ad_management/item_republish.php?id=' . $item['pk_i_id'] . '&repub=republish&rSecret=' . $r_secret . $secret ;
        $republish_url    = '<a href="' . $republish_url1 . '" >' . $republish_url1 . '</a>';

        $words   = array();
        $words[] = array('{CONTACT_NAME}', '{ITEM_TITLE}', '{WEB_TITLE}', '{REPUBLISH_URL}', '{PERM_DELETED}', '{ITEM_ID}');
        $words[] = array($item['s_contact_name'], $item['s_title'], osc_page_title(), $republish_url, $permDeleted, $item['pk_i_id']) ;

        $title = osc_mailBeauty($content['s_title'], $words) ;
        $body  = osc_mailBeauty($content['s_text'], $words) ;

        $emailParams =  array('subject'  => $title
                             ,'to'       => $item['s_contact_email']
                             ,'to_name'  => $item['s_contact_name']
                             ,'body'     => $body
                             ,'alt_body' => $body);

        osc_sendMail($emailParams);
    }

    /**
     * Send email to users confirming that there ad has been re published
     *
     * @param array $item
     * @param string $r_secret
     * @param integer $expire_days
     *
     * @dynamic tags
     *
     * '{CONTACT_NAME}', '{ITEM_TITLE}',
     * '{WEB_TITLE}', '{REPUBLISH_URL}', '{EXPIRE_DAYS}'
     */

    function confirm_email($itemId, $repub_days = 0) {
        $mPages = new Page() ;
        $aPage = $mPages->findByInternalName('aam_listing_republished') ;
        $locale = osc_current_user_locale() ;
        $content = array();
        if(isset($aPage['locale'][$locale]['s_title'])) {
            $content = $aPage['locale'][$locale];
        } else {
            $content = current($aPage['locale']);
        }

        $item = Item::newInstance()->findByPrimaryKey($itemId);
        $item_url = osc_item_url_advanced($itemId);

        $words   = array();
        $words[] = array('{CONTACT_NAME}', '{ITEM_TITLE}', '{WEB_TITLE}', '{REPUB_DAYS}', '{ITEM_URL}', '{ITEM_ID}');
        $words[] = array($item['s_contact_name'], $item['s_title'], osc_page_title(), $repub_days, $item_url, $item['pk_i_id']) ;

        $title = osc_mailBeauty($content['s_title'], $words) ;
        $body  = osc_mailBeauty($content['s_text'], $words) ;

        $emailParams =  array('subject'  => $title
                             ,'to'       => $item['s_contact_email']
                             ,'to_name'  => $item['s_contact_name']
                             ,'body'     => $body
                             ,'alt_body' => $body);

        osc_sendMail($emailParams);
    }

    /**
     * Send email to the admin confirming that a user has re published their listing.
     *
     * @param array $item
     * @param string $r_secret
     * @param integer $expire_days
     *
     * @dynamic tags
     *
     * '{CONTACT_NAME}', '{ITEM_TITLE}',
     * '{WEB_TITLE}', '{REPUBLISH_URL}', '{EXPIRE_DAYS}'
     */

    function admin_confirm_email($itemId) {
        $mPages = new Page() ;
        $aPage = $mPages->findByInternalName('aam_listing_republished_admin') ;
        $locale = osc_current_user_locale() ;
        $content = array();
        if(isset($aPage['locale'][$locale]['s_title'])) {
            $content = $aPage['locale'][$locale];
        } else {
            $content = current($aPage['locale']);
        }

        $item = Item::newInstance()->findByPrimaryKey($itemId);
        $item_url = osc_item_url_advanced($itemId);

        $words   = array();
        $words[] = array('{CONTACT_NAME}', '{USER_NAME}', '{ITEM_TITLE}', '{WEB_TITLE}', '{ITEM_URL}', '{ITEM_ID}');
        $words[] = array('Admin', $item['s_contact_name'], $item['s_title'], osc_page_title(), $item_url, $item['pk_i_id']) ;

        $title = osc_mailBeauty($content['s_title'], $words) ;
        $body  = osc_mailBeauty($content['s_text'], $words) ;

        $emailParams =  array('subject'  => $title
                             ,'to'       => osc_contact_email()
                             ,'to_name'  => __('Admin mail system')
                             ,'body'     => $body
                             ,'alt_body' => $body);

        osc_sendMail($emailParams);
    }
    ?>
