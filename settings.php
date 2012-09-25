<?php

$action_url = "http://".$_SERVER['SERVER_NAME'].substr($_SERVER['REQUEST_URI'],0,strpos($_SERVER['REQUEST_URI'],"account_sets")+12);

$settings = $wpdb->get_row("select * from ".$wpdb->prefix."_3xs_settings where id = 1");
$tw_configured = $settings->tw_cs && $settings->tw_ck ? true : false;

$fb_configured = $settings->fb_app_id && $settings->fb_app_secret ? true : false;

$ln_configured = $settings->ln_cs && $settings->ln_ck ? true : false;

$tw_acs = $wpdb->get_results("SELECT * FROM ".$this->tbl_tw_accounts);
$ln_acs = $wpdb->get_results("SELECT * FROM ".$this->tbl_ln_accounts);
$fb_acs = $wpdb->get_results("SELECT * FROM ".$this->tbl_fb_accounts);

$account_sets = $wpdb->get_results("SELECT * FROM `" . $this->tbl_account_sets . "`", ARRAY_A);
$account_types = array(
    'tw' => 'Twitter',
    'fb' => 'Facebook',
    'ln' => 'LinkedIn'
);
?>
<div class="wrap">
    <h2>Accounts and Account Sets Management</h2>

    <?php if($settings->activated && $settings->licence_type=='3xSocializer_developer') {?>
    <!--ACCOUNT_SETS--ACCOUNT_SETS--ACCOUNT_SETS--ACCOUNT_SETS--ACCOUNT_SETS-->
    <h3 style="margin-bottom: 0px">Account Sets</h3>
    <?php foreach ($account_sets as $key => $record): ?>
    <div class="tx_asm_record">
        <div><?php echo $record['name']; ?></div>
        <div><a onclick="expandEdit(<?php echo $record['id'] ?>,'set')">Edit</a></div>
        <div>
            <?php if(!$record['default']) { ?>
            <a href="<?php echo $action_url."&set_default=".$record['id']; ?>">Make default</a>
            <?php } else echo "<strong>Default</strong>"; ?>
        </div>
        <div>
            <?php if(!$record['default']) { ?>
            <a href="#" onclick="if (confirm('You are sure?')) location.href='<?php echo $action_url."&delete_set=".$record['id']; ?>'">Delete</a>
            <?php } else echo "<i>Default</i>"; ?>
        </div>
    </div>

    <form action="<?php echo $action_url ?>" method="POST">
        <div class="edit_block" id="edit_set_block_<?php echo $record['id'] ?>" style="display: none; ">
            <input type="hidden" name="action" value="edit_set">
            <input type="hidden" name="id" value="<?php echo $record['id'] ?>">
            <span class="popup_name">Edit Account Set: <?php echo $record['name'] ?></span>
            <p style="width: 95px">Name:</p><input id="n_name" name="n_name" type="text" placeholder="Name" value="<?php echo $record['name'] ?>">
            <hr>
            <?php
            foreach ($account_types as $key => $value):
                if($key =='ln'){
                    echo "<input type='hidden' name='".$key."_id' value='-1'>";
                    continue;
                }
                ?>
                <span class="sn_name"><?php echo $value ?>:</span>
                <select name="<?php echo $key ?>_id">
                    <option value="-1">--Choose account--</option>
                    <?php
                    $cur_array = $key."_acs";
                    foreach ($$cur_array as $account):
                        echo "<option value='".$account->id."'";
                        echo $account->id == $record[$key."_account_id"] ? "selected" : "";
                        echo ">".$account->name."</option>\n";
                    endforeach;
                    ?>
                </select>
                <input style="width: 15px;" <?php echo $record[$key."_enabled"] ? "checked=\"checked\"" : "" ?> type="checkbox" name="<?php echo $key ?>_enabled"> enabled
                <div style="clear: both"></div>
                <?php
            endforeach;
            ?>
            <hr>

            <input type="submit" class="editAccountBtn" value="Save">
            <a class="cancel_btn" onclick="cancelEditing(<?php echo $record['id'] ?>,'set')">Cancel</a>
        </div>
    </form>
<?php endforeach; ?>
    <button id="expandAdd" onclick="expandAddingBlock('set')">+ Add new account set</button>
    <form action="<?php echo $action_url ?>" method="POST">
        <div id="add_set_block" class="add_block" style="display: none; ">
            <input type="hidden" name="action" value="add_set">
            <span class="popup_name">New Account Set</span>
            <p style="width: 95px">Name:</p><input id="n_name" name="n_name" type="text" placeholder="Name">
            <hr>
            <?php
            foreach ($account_types as $key => $value):
                if($key =='ln'){
                    echo "<input type='hidden' name='".$key."_id' value='-1'>";
                    continue;
                }
                ?>
                <span class="sn_name"><?php echo $value ?>:</span>
                <select onchange="expandAddingBlock('<?php echo $key ?>','set',this)" name="<?php echo $key ?>_id">
                    <option value="-1">--Choose account--</option>
                    <?php
                    $cur_array = $key."_acs";
                    foreach ($$cur_array as $account):
                        echo "<option value='".$account->id."'>".$account->name."</option>\n";
                    endforeach;
                    ?>
                </select>
                <input style="width: 15px;" type="checkbox" name="<?php echo $key ?>_enabled"> enabled
                <div style="clear: both"></div>
                <?php
            endforeach;
            ?>
            <hr>
            <input type="submit" class="addAccountBtn" value="Add">
            <a class="cancel_btn" onclick="cancelAdding('set')">Cancel</a>
        </div>
    </form>
    <?php } ?>



    <h3 style="margin-bottom: 0px">Applications</h3>

    <!--    TWITTER-->
    <?php
    if($tw_configured){
        ?>

        <div class="tx_asm_record">
            <div>Twitter</div>
            <div><a onclick="showPopup('tw_edit')">Update</a></div>
            <img onclick="helpPopup('<?php echo $this->plugin_url ?>help/twitter.php')" style='position: relative; top: 6px; left: 6px; cursor: pointer;' src=' <?php echo $this->plugin_url ?>images/help_icon.png'>
        </div>

        <?php
        generatePopupForm('tw', $action_url, $settings);
    } else {
        echo "Twitter - enter your application credentials <a style='cursor: pointer' onclick=\"showPopup('tw_add')\">here first.</a>";
        echo "<img onclick=\"helpPopup('".$this->plugin_url."help/twitter.php')\" style='position: relative; top: 6px; left: 6px; cursor: pointer;' src='".$this->plugin_url."images/help_icon.png'></a>";
        echo generatePopupForm('tw', $action_url);
    }
    ?>

<!--    FACEBOOK-->
    <?php
    if($fb_configured){
        ?>

        <div class="tx_asm_record">
            <div>Facebook</div>
            <div><a onclick="showPopup('fb_edit')">Update</a></div>
            <img onclick="helpPopup('<?php echo $this->plugin_url ?>help/facebook.php')" style='position: relative; top: 6px; left: 6px; cursor: pointer;' src=' <?php echo $this->plugin_url ?>images/help_icon.png'>
        </div>

        <?php
        generatePopupForm('fb', $action_url, $settings);
    } else {
        echo "Facebook - enter your application credentials <a style='cursor: pointer' onclick=\"showPopup('fb_add')\">here first.</a>";
        echo "<img onclick=\"helpPopup('".$this->plugin_url."help/facebook.php')\" style='position: relative; top: 6px; left: 6px; cursor: pointer;' src='".$this->plugin_url."images/help_icon.png'></a>";
        echo generatePopupForm('fb', $action_url);
    }
    ?>

<!--    LINKEDIN-->
    <?php
    if (false){
//    if($ln_configured){
        ?>

        <div class="tx_asm_record">
            <div>Linkedin</div>
            <div><a onclick="showPopup('ln_edit')">Update</a></div>
            <img onclick="helpPopup('<?php echo $this->plugin_url ?>help/linkedin.php')" style='position: relative; top: 6px; left: 6px; cursor: pointer;' src=' <?php echo $this->plugin_url ?>images/help_icon.png'>
        </div>

        <?php
        generatePopupForm('ln', $action_url, $settings);
    }
//    } else {
    if (false){
        echo "LinkedIn - enter your application credentials <a style='cursor: pointer' onclick=\"showPopup('ln_add')\">here first.</a>";
        echo "<img onclick=\"helpPopup('".$this->plugin_url."help/linkedin.php')\" style='position: relative; top: 6px; left: 6px; cursor: pointer;' src='".$this->plugin_url."images/help_icon.png'></a>";
        echo generatePopupForm('ln', $action_url);
    }
    ?>
<!--      ------------------------------------------------->
    <h3 style="margin-bottom: 0px">Twitter Accounts</h3>

    <?php foreach ($tw_acs as $record): ?>
    <div class="tx_asm_record">
        <div><?php echo $record->name ?></div>
        <div><a href="#" onclick="if (confirm('You are sure?'))
            location.href='<?php
            echo $action_url."&delete_tw=".$record->id;
            ?>'">Delete</a></div>
    </div>
    <?php endforeach; ?>

    <?php
    if($tw_configured){
        require_once('twitteroauth/twitteroauth.php');

        $connection = new TwitterOAuth($settings->tw_ck, $settings->tw_cs);

        /* Get temporary credentials. */
        $request_token = $connection->getRequestToken($action_url."&twitter_callback=true");

        /* Save temporary credentials to session. */
//            $_SESSION['tw_oauth_token'] =
        $token = isset($request_token['oauth_token']) ? $request_token['oauth_token'] : "nothing";
        update_option('tw_oauth_token',  $token);

        update_option('tw_oauth_token_secret', isset($request_token['oauth_token_secret']) ? $request_token['oauth_token_secret'] : "nothing");
//            echo var_dump($_SESSION);
        /* If last connection failed don't display authorization link. */
//            var_dump($connection);
        switch ($connection->http_code) {
            case 200:
                /* Build authorize URL and redirect user to Twitter. */
                $url = $connection->getAuthorizeURL($token);
                echo "<a href='$url'>Add your twitter account</a>";//  header('Location: ' . $url);
                break;
            default:
                /* Show notification if something went wrong. */
                echo 'Could not connect to Twitter. Please check your Twitter consumer key / consumer secret<br>and also check if there is read and write access in your Twitter application settings (not just read access).<br>Please review the <a style="cursor: pointer;" onclick=helpPopup("'.$this->plugin_url.'help/twitter.php")>instructions</a> for further assistance.';
        }
    } else {
        echo "<i>Please, enter your Twitter application credentials first</i>";
    }
    ?>
<!--                -------------------------------------------------------->
    <h3 style="margin-bottom: 0px">Facebook Pages</h3>
    <?php

    if($fb_configured){
        if(!class_exists('SocializerFacebook'))
            require_once('facebook-php-sdk/src/facebook.php');

        $config = array(
            'appId' => $settings->fb_app_id,
            'secret' => $settings->fb_app_secret,
        );

        $facebook = new SocializerFacebook($config);
        //echo strpos(var_dump($facebook),"[\"state\":protected]=> NULL");
        //echo strpos(print_r($facebook), "[state:protected] => [accessToken:protected]");
        $user_id = $facebook->getUser();
        if($user_id) {
            try {

                $user_profile = $facebook->api('/me','GET');
                if (isset($_GET['facebook_callback']) && $_GET['facebook_callback'] == true){

                    $wpdb->query($wpdb->prepare('delete from '.$this->tbl_fb_accounts));

                    $pages = $facebook->api($user_profile['username']."/accounts", 'GET');

                    $data = $pages['data'];

                    $wpdb->insert($this->tbl_fb_accounts, array('username' => $user_profile['username'], 'name' => 'My feed', 'page_id' => 'me'));

                    foreach($data as $page){
                        if(isset($page['perms'])){
                            foreach($page['perms'] as $permission){
                                if ($permission == "ADMINISTER"){
                                    $wpdb->show_errors();
//                                    echo "Page: ".$page['name']."<br>";
                                    $wpdb->insert($this->tbl_fb_accounts, array('username' => $user_profile['username'], 'name' => $page['name'], 'page_id' => $page['id']));
                                }
                            }
                        }
                    }
                } else {
                    $login_array = array();
                    $login_array['scope'] = 'publish_stream, manage_pages';
                    $login_array['redirect_uri'] = $action_url."&facebook_callback=true";
                    $login_url = $facebook->getLoginUrl($login_array);
                }
            } catch(FacebookApiException $e) {
                  echo "Please check your Facebook App credentials and try once more";
//                var_dump($e);
            }
        }
    }
    $fb_acs = $wpdb->get_results("SELECT * FROM ".$this->tbl_fb_accounts);
    foreach ($fb_acs as $record): ?>
        <div class="tx_asm_record">
            <div><?php echo $record->name ?></div>
            <div><a href="#" onclick="if (confirm('You are sure?'))
                location.href='<?php echo $action_url."&delete_fb=".$record->id; ?>'">Delete</a></div>
        </div>
    <?php endforeach;

    if($fb_configured){
        // No user, print a link for the user to login
 //       if ($user_id){
            $login_array = array();
            $login_array['scope'] = 'publish_stream, manage_pages';
            $login_array['redirect_uri'] = $action_url."&facebook_callback=true";
            $login_url = $facebook->getLoginUrl($login_array);
            echo '<a href="' . $login_url . '">Refresh your facebook pages.</a>';
 //       } else {
 //           echo "<i>You provided invalid Facebook App credentials</i>";
 //       }
    } else {
        echo "<i>Please, enter your Facebook application credentials first</i>";
    }
    ?>
<!--    ---------------------------------------->
<!--    <h3 style="margin-bottom: 0px">LinkedIn Accounts</h3>-->

    <?php if (false) foreach ($ln_acs as $record): ?>
    <div class="tx_asm_record">
        <div><?php echo $record->name ?></div>
        <div><a href="#" onclick="if (confirm('You are sure?'))
            location.href='<?php
            echo $action_url."&delete_ln=".$record->id;
            ?>'">Delete</a></div>
    </div>
        <?php endforeach; ?>

    <?php
//    if($ln_configured){
    if (false){
        if (!class_exists("OAuth"))
            require_once("OAuth.php");
        $domain = "https://api.linkedin.com/uas/oauth";
        $sig_method = new OAuthSignatureMethod_HMAC_SHA1();

        $test_consumer = new OAuthConsumer($settings->ln_ck, $settings->ln_cs, NULL);
        $callback = $action_url."&linkedin_callback=true";

        $req_req = OAuthRequest::from_consumer_and_token($test_consumer, NULL, "POST", $domain . "/requestToken");
        $req_req->set_parameter("oauth_callback", $callback); # part of OAuth 1.0a - callback now in requestToken
        $req_req->sign_request($sig_method, $test_consumer, NULL);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_POSTFIELDS, '');

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            $req_req->to_header()
        ));
        curl_setopt($ch, CURLOPT_URL, $domain . "/requestToken");
        curl_setopt($ch, CURLOPT_POST, 1);
        $output = curl_exec($ch);
        curl_close($ch);

        parse_str($output, $oauth);

        update_option('ln_oauth_token', $oauth['oauth_token']);
        update_option('ln_oauth_token_secret', $oauth['oauth_token_secret']);

        if(!isset($oauth['oauth_token'])) {
           echo "Something wrong, please recheck your LinkedIn App Credentials". $output;
        } else {
           $url = "https://api.linkedin.com/uas/oauth/authorize?oauth_token=".$oauth['oauth_token'];
           echo "<a href='$url'>Add your LinkedIn account</a>";
        }
    }
//    } else {
    if (false){
        echo "<i>Please, enter your LinkedIn application credentials first</i>";
    }
    ?>
</div>
<?php
function generatePopupForm($type, $action_url, $values = null){
    $account_types = array(
        'tw' => 'Twitter',
        'fb' => 'Facebook',
        'ln' => 'LinkedIn'
    );

    $ck_value = null;
    $cs_value = null;
    $ck_name = null;
    $ck_value = null;
    switch($type):
        case 'fb':
            $ck_value = $values ? $values->fb_app_id : null;
            $cs_value = $values ? $values->fb_app_secret : null;
            $ck_name = 'App ID';
            $cs_name = 'App Secret';
            break;
        case 'tw':
            $ck_value = $values ? $values->tw_ck : null;
            $cs_value = $values ? $values->tw_cs : null;
            $ck_name = 'Consumer key';
            $cs_name = 'Consumer secret';
            break;
        case 'ln':
            $ck_value = $values ? $values->ln_ck : null;
            $cs_value = $values ? $values->ln_cs : null;
            $ck_name = 'API Key';
            $cs_name = 'Secret Key';
            break;
        default:
            break;
    endswitch;
    ?>
<form action="<?php echo  $action_url; ?>" method="POST">
    <div id="<?php echo $values ? $type."_edit" : $type."_add"; ?>" class="add_block" style="display: none; ">
        <input type="hidden" name="action" value="update_<?php echo $type; ?>">
        <span class="popup_name"><?php echo $account_types[$type].' Application Credentials'?></span>
        <hr>
        <span class="sn_name"><?php echo "$ck_name:" ?></span>
        <input type="text" name="ck" value="<?php echo $ck_value ? $ck_value : "" ?>" placeholder="<?php echo $ck_name ?>">
        <br>
        <span class="sn_name"><?php echo "$cs_name:" ?></span>
        <input type="text" name="cs" value="<?php echo $cs_value ? $cs_value : "" ?>" placeholder="<?php echo $cs_name ?>">

        <div style="clear: both"></div>
        <hr>
        <input type="submit" class="addAccountBtn" value="<?php echo $values ? "Update" : "Add" ?>">
        <a class="cancel_btn" onclick="closePopup('<?php echo $values ? $type."_edit" : $type."_add"; ?>')">Cancel</a>
    </div>
</form>

<?
}
?>
<script type="text/javascript">onloadEditAccount()</script>
