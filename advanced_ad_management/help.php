<?php
/*
 *      OSCLass – software for creating and publishing online classified
 *                           advertising platforms
 *
 *                        Copyright (C) 2010 OSCLASS
 *
 *       This program is free software: you can redistribute it and/or
 *     modify it under the terms of the GNU Affero General Public License
 *     as published by the Free Software Foundation, either version 3 of
 *            the License, or (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful, but
 *         WITHOUT ANY WARRANTY; without even the implied warranty of
 *        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *             GNU Affero General Public License for more details.
 *
 *      You should have received a copy of the GNU Affero General Public
 * License along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
?>

<div id="settings_form" style="border: 1px solid #ccc; background: #eee; ">
    <div style="padding: 0 20px 20px;">
        <div>
            <fieldset>
                <legend>
                    <h1><?php _e('Advanced Ad Management Help', 'adManage'); ?></h1>
                </legend>
                <h2>
                    <?php _e('What is the Advanced Ad Management Plugin?', 'adManage'); ?>
                </h2>
                <p>
                    <?php _e('The Advanced Ad Management Plugin gives the user the option to republish there ad after it expires. More info coming soon.', 'adManage'); ?>
                </p>
                <h2>
                    <?php _e('Do I need to edit any files for Advanced Ad Management plugin to work?', 'adManage'); ?>
                </h2>
                <p>
                    <?php _e('Yes, You need to edit the user-items.php file and add the following line.', 'adManage'); ?>
                </p>
                <pre>
                    &lt;?php if (function_exists('republish_url')) {echo republish_url();} ?&gt;
                </pre>
                <p>
                    <?php _e('Find the following code and add the above code between the tags but before the &lt;/p&gt; tag.','adManage'); ?>
                </p>
                <pre>
                    &lt;p class="options"&gt;
                </pre>
                <h2>
                    <?php _e('How do I edit the email templates?', 'adManage'); ?>
                </h2>
                <p>
                    <?php _e('To edit the email templates you have to go under the Email & Alerts menu. Then you will see towards the end of the list email_ad_expire and email_ad_expired.', 'adManage'); ?>
                </p>
                <h2>
                    <?php _e('What are the dynamic tags that can be used in the "email_ad_expire" template?', 'adManage'); ?>
                </h2>
                <p>
                    <?php echo'{CONTACT_NAME}, {ITEM_TITLE}, {WEB_TITLE}, {REPUBLISH_URL}, {EXPIRE_DAYS}'; ?>
                </p>
                <h2>
                    <?php _e('What are the dynamic tags that can be used in the "email_ad_expired" template?', 'adManage'); ?>
                </h2>
                <p>
                    <?php echo '{CONTACT_NAME}, {ITEM_TITLE}, {WEB_TITLE}, {REPUBLISH_URL}, {PERM_DELETED}.'; ?>
                </p>
            </fieldset>
        </div>
    </div>
</div>
