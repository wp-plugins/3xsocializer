<?php
global $post;
global $wpdb;

$settings = $wpdb->get_row("select * from ".$wpdb->prefix."_3xs_settings where id = 1");

$activated = false;
$activation_error = null;
if (isset($settings->app_key)){
$email         = $_POST['licence_email'];
$licence_key   = $_POST['licence_key'];
$product_id    = $_POST['licence_type'];

$args = array(
    'wc-api'	  => 'software-api',
    'request'     => 'check',
    'email'		  => $settings->app_email,
    'licence_key' => $settings->app_key,
    'product_id'  => $settings->licence_type
);
$target_url = create_url( $args );

//    echo $target_url;
$data = wp_remote_get( $target_url );
$result = json_decode($data['body']);
if (isset($result->success))
    if ($result->success){
        $activated = true;
    }
    else {
//        var_dump($result);
        $activation_error = $result->error;
    }
}

if ($activated){
$myposts = get_posts(array('post_status' => 'publish, future', 'numberposts' => -1));
$myaccouts = $wpdb->get_results("SELECT * FROM `" . $this->tbl_account_sets . "`", ARRAY_A);
//echo var_dump($myaccouts);
$current_post = null;
if (isset($_GET['post_id'])){
    $current_post = get_post(intval($_GET['post_id']));
}
$fb_ac = $wpdb->get_results("SELECT * FROM `" . $this->tbl_fb_accounts . "`", ARRAY_A);

$selected = isset($_GET['account_set']) ? $wpdb->get_row("SELECT * FROM `" . $this->tbl_account_sets . "` where id=".$_GET['account_set']) : null;
if (!$selected)
    $selected = $wpdb->get_row("SELECT * FROM `" . $this->tbl_account_sets . "` where `default`=true");

$post_date = null;
if ($current_post && $current_post->post_status == 'future'){
    $post_date = new DateTime($current_post->post_date);
    $timestamp = mktime($post_date->format('H'),
        $post_date->format('i'),
        $post_date->format('s'),
        $post_date->format('m'),
        $post_date->format('d'),
        $post_date->format('Y'));
    $timestamp += 5*60;
    $post_date = new DateTime(date('Y-m-d H:i:s',$timestamp));
}

function scheduleInputs($type,$post_date){
    $month = $post_date ? $post_date->format('n') : date('n',current_time('timestamp',0));
    $day = $post_date ? $post_date->format('d') : date('d',current_time('timestamp',0));
    $year = $post_date ? $post_date->format('Y') : date('Y',current_time('timestamp',0));
    $hour = $post_date ? $post_date->format('G') : date('G',current_time('timestamp',0));
    $minute = $post_date ? $post_date->format('i') : date('i',current_time('timestamp',0));

    echo "<select name='".$type."_mm' tabindex='4'>";
    for ($i=1; $i <= 12; $i++){
        $month_name = date( 'F', mktime(0, 0, 0, $i) );
        echo "<option value='";
        echo $i < 10 ? "0$i'" : "$i'";
        echo $month == $i ? " selected" : "";
        echo ">$month_name</option>";
    }?>
    </select>
    <input type="text" name="<?php echo $type ?>_jj" value="<?php echo $day ?>" size="2" maxlength="2" tabindex="4" autocomplete="off">,
    <input type="text" name="<?php echo $type ?>_aa" value="<?php echo $year ?>" size="4" maxlength="4" tabindex="4" autocomplete="off"> @
    <input type="text" name="<?php echo $type ?>_hh" value="<?php echo $hour ?>" size="2" maxlength="2" tabindex="4" autocomplete="off"> :
    <input type="text" name="<?php echo $type ?>_mn" value="<?php echo $minute ?>" size="2" maxlength="2" tabindex="4" autocomplete="off">
<?php
}
?>


<script type="text/javascript">
    var fb_ac = new Array();
    <?php
    foreach ($fb_ac as $a_key => $a_value):
        echo "fb_ac[$a_value[id]] = {};";
        echo "fb_ac[$a_value[id]].app_id='$a_value[app_id]';";
        echo "fb_ac[$a_value[id]].app_secret='$a_value[app_secret]';";
    endforeach;
    ?>
</script>

<div class="wrap">
<h2>Sharing Utility</h2>

<form action="" method="POST">
    <div id="add_quick_set_block" class="add_block" style="display: none; ">
        <input type="hidden" name="action" value="quick_add_set">

        <input type="hidden" id="fb_id" name="fb_id" value="-1">
        <input type="hidden" id="tw_id" name="tw_id" value="-1">
        <input type="hidden" id="ln_id" name="ln_id" value="-1">
        <input type="hidden" id="enabled_code" name="enabled_code" value="0">

        <span class="popup_name">Save Custom Account Set</span>
        <p style="width: 95px">Name:</p><input id="n_name" name="n_name" type="text" placeholder="Name">
        <div style="clear: both"></div>
        <p style="width: 95px">Or overwrite:</p>
        <select name="acs_override" onchange="quickSaveOverride(this)">
            <option value="-1">--Don't overwrite--</option>
            <?php foreach ($myaccouts as $set): ?>
            <option value="<?php echo $set['id'] ?>"><?php echo $set['name'] ?></option>
            <?php endforeach; ?>
        </select>
        <hr>
        <input type="submit" class="addAccountBtn" id='quick_add_btn' value="Add">
        <a class="cancel_btn" onclick="cancelAdding('quick_set')">Cancel</a>
    </div>
</form>

<div class="sh_utlt_header">
    <div class="post">
        <p>Blog post:</p>
        <select id="post_id" onchange="postChanged('post')">
            <option>-- Select a blog post --</option>
            <option disabled="disabled">--------------------------</option>
            <?php foreach( $myposts as $post ) :	setup_postdata($post); ?>
            <option<?php echo isset($_GET['post_id']) && $_GET['post_id'] == get_the_id() ? " selected" : "" ?> value="<?php the_id(); ?>"><?php the_title(); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="account_sets">
        <?php if($settings->licence_type == "3xSocializer_developer"){ ?>
        <p>Account sets:</p>
        <select id="account_set_id" onchange="postChanged('set')">
            <option value="0">-- Select account set --</option>
            <option value="-1">-- Custom account set --</option>
            <option disabled="disabled">--------------------------</option>
            <?php
            foreach ($myaccouts as $set):
                ?>
                <option<?php echo (isset($_GET['account_set']) && $_GET['account_set'] == $set['id']) || (((isset($_GET['account_set']) && $_GET['account_set'] == 0) || !isset($_GET['account_set'])) && $set['default']) ? " selected" : "" ?> value="<?php echo $set['id'] ?>"><?php echo $set['name'] ?></option>
                <?php endforeach; ?>
        </select>
        <img title="Edit" onclick="quickEdit(document.getElementById('account_set_id').value)" style="cursor: pointer;left:0" src="<?php echo $this->plugin_url."images/edit_icon.png" ?>" alt="Quick save">
        <img title="Save" onclick="quickSave()" style="cursor: pointer; float: right;" src="<?php echo $this->plugin_url."images/save_icon.png" ?>" alt="Quick edit">
        <!--            <a href="--><?php //echo get_admin_url()."admin.php?page=account_sets" ?><!--"><img src="--><?php //echo $this->plugin_url."images/edit_icon.png" ?><!--" alt="Edit"></a>-->
        <?php } ?>
    </div>
</div>

<form id="social_share_form" method="POST" action="<?php echo get_admin_url()."admin.php?page=social_posts&state=3ecff3166d41627f729110a9116c469d&code=AQBtEgkmm70GbsgouDQoX34wnXU55UJH-adXVcs4RpHo11rZZGtir3dXluv0ioDJhFTgMWJPcFBe_bwJ_19Ohbhxmslq27ca5DXM1LNCw3kQADPlD9lkjb9z5UlEMF--zAwcJRX3ZFXJ6w0mMUu_Q60cc3RFf-5Y-Bs1YcUiSKazsv8aSEKMOXnAPHCIFDZQmqg#_=_" ?>">
    <input type="hidden" name="action" value="post">
    <input type="hidden" name="origin_id" value="<?php echo $_GET['post_id']?>">
    <!--    <form method="POST">-->
    <?php echo $current_post ? "<input name=\"post_title\" type='hidden' value=\"".$current_post->post_title."\">" : "";?>
    <div style="clear: both"></div>

    <!--TWITTER--TWITTER--TWITTER--TWITTER--TWITTER--TWITTER--TWITTER--TWITTER--TWITTER--TWITTER--TWITTER--TWITTER--TWITTER-->
    <div class="social_share">
        <div class="post_header">
            <div>
                <img class="tw" src="<?php echo $this->plugin_url."images/twitter_logo.png" ?>" alt="">
            </div>
            <div class="options">
                <select onchange="setCustomSet()" id="tw_account_id" name="tw_account_id">
                    <option value="-1">--Select account--</option>
                    <?php
                    $tw_accounts = $wpdb->get_results("SELECT id, name from ".$this->tbl_tw_accounts, ARRAY_A);
                    foreach ($tw_accounts as $record):
                        ?>
                        <option<?php echo isset($selected) && $selected->tw_account_id == $record['id'] ? " selected" : "" ?> value="<?php echo $record['id'] ?>"><?php echo $record['name'] ?></option>
                        <?php endforeach; ?>
                </select>
                <br><br>
                <input id='tw_publish' <?php echo isset($selected) && $selected->tw_enabled ? "checked=\"checked\"" : ""; ?> class="social_checkbox" type="checkbox" name="tw_publish" > <div class="checkbox_title">Post To Twitter?</div>
            </div>
        </div>
        <div class="share_post" id="tw_post">
            <textarea name="tw_post" id='tw-ta' onkeydown="updateLength('tw_post_len',this);" onkeyup="updateLength('tw_post_len',this);" rows="5" placeholder="Post which will be published to twitter"><?php echo $current_post ? $current_post->post_title.": ".$this->make_bitly_url(get_permalink($current_post->ID)) : "";?></textarea>
            <div class="publish_mode">
                <input onchange="scheduledChanged(this,'tw')" type="radio" <?php echo $current_post && $current_post->post_status == 'future' ? "" : "checked='checked'" ?> name="tw_publish_mode" value="published"> Publish immediately<br>
                <input id="tw_schedule" onchange="scheduledChanged(this,'tw')" type="radio" <?php echo $current_post && $current_post->post_status == 'future' ? "checked='checked'" : "" ?>  name="tw_publish_mode" value="scheduled"> Schedule
                <div id="timestampdiv_tw" class="hide-if-js" style="display: none; ">
                    <div class="timestamp-wrap">
                        <?php echo scheduleInputs('tw',$post_date); ?>
                    </div>
                </div>
            </div><div style="clear: both;"></div>
            <div id='tw_post_len' style="margin-left: 10px;">0 characters</div>
            <script type="text/javascript">updateLength('tw_post_len',document.getElementById('tw-ta')) </script>
        </div>
    </div>
    <!--FACEBOOK--FACEBOOK--FACEBOOK--FACEBOOK--FACEBOOK--FACEBOOK--FACEBOOK--FACEBOOK--FACEBOOK--FACEBOOK--FACEBOOK--FACEBOOK-->
    <div class="social_share">
        <div class="post_header">
            <div>
                <img class="tw" src="<?php echo $this->plugin_url."images/facebook_logo.png" ?>" alt="">
            </div>
            <div class="options">
                <select onchange="setCustomSet(); fbAccountChanged('<?php echo dirname(plugin_basename(__FILE__)); ?>');" name="fb_account_id" id="fb_account_id">
                    <option value="-1">--Select account--</option>
                    <?php
                    $fb_accounts = $wpdb->get_results("SELECT id, name FROM ".$this->tbl_fb_accounts, ARRAY_A);
                    foreach ($fb_accounts as $record):
                        ?>
                        <option<?php echo isset($selected) && $selected->fb_account_id == $record['id'] ? " selected" : "" ?> value="<?php echo $record['id'] ?>"><?php echo $record['name'] ?></option>
                        <?php endforeach; ?>
                </select>

                <div id="fb_check" style="display: inline-block; color: darkred;">Checking ability to publish there...</div><img id="fb_refresh_img" onclick="fbAccountChanged('<?php echo dirname(plugin_basename(__FILE__)); ?>')" style="cursor: pointer" src="<?php echo $this->plugin_url."images/refresh_icon.png"?>" alt="Refresh"><br><br>
                <input type="hidden" id='fb_check_result' value="0">
                <input type="checkbox" id='fb_publish' <?php echo isset($selected) && $selected->fb_enabled ? "checked=\"checked\"" : ""; ?> name="fb_publish" class="social_checkbox"  > <div class="checkbox_title">Post To Facebook?</div>
            </div>
        </div>
        <div class="share_post" style="height: 140px" id="fb_post">
            <textarea name="fb_post" id='fb-ta' onkeydown="updateLength('fb_post_len',this);" onkeyup="updateLength('fb_post_len',this);" rows="5" placeholder="Post which will be published to facebook"><?php

                if (isset($current_post)){
                    echo $current_post->post_title."\n";
                    echo strip_tags(substr($current_post->post_content,0,strpos($current_post->post_content, "\n")))."\n";
                    echo "Read more: ".$this->make_bitly_url(get_permalink($current_post->ID));
                }
                ?></textarea>

            <div class="publish_mode">
                <input onchange="scheduledChanged(this, 'fb')" type="radio" <?php echo $current_post && $current_post->post_status == 'future' ? "" : "checked='checked'" ?> name="fb_publish_mode" value="published"> Publish immediately<br>
                <input id="fb_schedule" onchange="scheduledChanged(this, 'fb')" type="radio" <?php echo $current_post && $current_post->post_status == 'future' ? "checked='checked'" : "" ?> name="fb_publish_mode" value="scheduled"> Schedule
                <div id="timestampdiv_fb" class="hide-if-js" style="display: none; ">
                    <div class="timestamp-wrap">
                        <?php echo scheduleInputs('fb',$post_date); ?>
                    </div>
                </div>
            </div><div style="clear: both;"></div>
            <div id='fb_post_len' style="margin-left: 10px;">0 characters</div><tr valign="top">
            <label for="upload_image">
                <input id="upload_image" type="text" size="36" name="upload_image" value="" />
                <input id="upload_image_button" type="button" value="Upload Image" />
            </label>
            <script type="text/javascript">updateLength('tw_post_len',document.getElementById('tw-ta')); updateLength('fb_post_len',document.getElementById('fb-ta')) </script>
        </div>
    </div>

    <!--LINKEDIN--LINKEDIN--LINKEDIN--LINKEDIN--LINKEDIN--LINKEDIN--LINKEDIN--LINKEDIN--LINKEDIN--LINKEDIN--LINKEDIN--LINKEDIN-->
    <?php
       //if(class_exists('OAuth')){
        if (false){
    ?>
    <div class="social_share">
        <div class="post_header">
            <div>
                <img class="tw" src="<?php echo $this->plugin_url."images/linkedin_logo.png" ?>" alt="">
            </div>
            <div class="options">
                <select onchange="setCustomSet()" name="ln_account_id" id="ln_account_id">
                    <option value="-1">--Select account--</option>
                    <?php
                    $ln_accounts = $wpdb->get_results("SELECT id, name FROM ".$this->tbl_ln_accounts, ARRAY_A);
                    foreach ($ln_accounts as $record):
                        ?>
                        <option<?php echo isset($selected) && $selected->ln_account_id == $record['id'] ? " selected" : "" ?> value="<?php echo $record['id'] ?>"><?php echo $record['name'] ?></option>
                        <?php endforeach; ?>
                </select>
                <br><br>
                <input id='ln_publish' <?php echo isset($selected) && $selected->ln_enabled ? "checked=\"checked\" " : " "; ?>type="checkbox" name="ln_publish" class="social_checkbox">
                <div class="checkbox_title">Post To LinkedIn?</div>
            </div>
        </div>
        <div class="share_post" id="ln_post">
            <textarea name="ln_post" id='ln-ta' onkeydown="updateLength('ln_post_len',this);" onkeyup="updateLength('ln_post_len',this);" rows="5" placeholder="Post which will be published to linkedin"><?php echo $current_post ? $current_post->post_title.": ".$this->make_bitly_url(get_permalink($current_post->ID)) : "";?></textarea>
            <div class="publish_mode">
                <input onchange="scheduledChanged(this,'ln')" type="radio" <?php echo $current_post && $current_post->post_status == 'future' ? "" : "checked='checked'" ?> name="ln_publish_mode" value="published"> Publish immediately<br>
                <input id="ln_schedule" onchange="scheduledChanged(this,'ln')" type="radio" <?php echo $current_post && $current_post->post_status == 'future' ? "checked='checked'" : "" ?> name="ln_publish_mode" value="scheduled"> Schedule
                <div id="timestampdiv_ln" class="hide-if-js" style="display: none; ">
                    <div class="timestamp-wrap">
                        <?php echo scheduleInputs('ln',$post_date); ?>
                    </div>
                </div>
            </div><div style="clear: both;"></div>
            <div id='ln_post_len' style="margin-left: 10px;">0 characters</div>
            <script type="text/javascript">updateLength('ln_post_len',document.getElementById('ln-ta')) </script>
        </div>
    </div>
    <?php } else { ?>
            <!--
    <div class="social_share">
        <div class="post_header">
            <div>
                <img class="tw" src="http://blog.vakoms.com/wp-content/plugins/3XSocializer/images/linkedin_logo.png" alt="">
            </div>
            <div class="options">
                Your hosting server hasn't installed PHP OAuth extension, so there is no ability to post on LinkedIn.
                Please <a href="<?php echo get_admin_url(); ?>admin.php?page=plugin_help#oauth">read</a> how you can fix this.
            </div>
        </div>
    </div>
        -->
    <?php } ?>
    <div style="text-align: center">
        <input class="delete_all social_button" type="button" value="" onclick="deleteAll()">
        <input class="publish_all social_button" type="button" value="" onclick="submitSharingForm()">
    </div>
</form>
</div>
<script type="text/javascript">
    if($('fb_account_id').value == -1){
        $('fb_check').style.display='none';
        $('fb_refresh_img').style.display='none';
    }

    if ($('tw_schedule').checked)
        $('timestampdiv_tw').style.display = 'block';

    if ($('fb_schedule').checked)
        $('timestampdiv_fb').style.display = 'block';

    if ($('ln_schedule'))
        if ($('ln_schedule').checked)
            $('timestampdiv_ln').style.display = 'block';

        socialCheckboxSet();
    fbAccountChanged('<?php echo dirname(plugin_basename(__FILE__)); ?>');
</script>
    <?php } elseif($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email         = $_POST['licence_email'];
    $licence_key   = $_POST['licence_key'];
    $product_id    = $_POST['licence_type'];

    $args = array(
        'request'     => 'activation',
        'email'       => $email,
        'licence_key' => $licence_key,
        'product_id'  => $product_id,
    );
    $target_url = create_url( $args );
    $data = wp_remote_get( $target_url );
    $result = json_decode($data['body']);
    if (isset($result->activated)){
        if ($result->activated){
            echo '<div style="text-align: center;" class="wrap"><h2>3XSocializer plugin activation</h2>';
            echo "<h3>Thank you! Your plugin activated! Please <a href=''>refresh</a> page and use it!</h3>";
            $wpdb->update($this->tbl_3x_settings, array('app_key' => $licence_key, 'app_email' => $email, 'licence_type' => $product_id, 'activated' => true), array('id' => 1));
        } else {
            echo '<div style="text-align: center;" class="wrap"><h2>3XSocializer plugin activation</h2>';
            echo "Not activated.<br><strong>Error message</strong>: ".$result->error." <br><a href=''>Try one more</a>";
            echo '</div>';
        }
    } else {
        echo '<div style="text-align: center;" class="wrap"><h2>3XSocializer plugin activation</h2>';
        echo "Not activated. Unknown error. <br><a href=''>Try one more time</a>";
    }
} elseif(!$settings->activated) {
        ?>
    <div style="text-align: center;" class="wrap">
        <h2>Please activate 3XSocializer plugin first</h2>
        <div>You can do it <a href="<?php echo "http://".$_SERVER['SERVER_NAME']."".substr($_SERVER['REQUEST_URI'],0,strpos($_SERVER['REQUEST_URI'], 'wp-admin'))."wp-admin/admin.php?page=license_management"; ?>"> here</div>
    </div>
    <?php } else {
    ?>
<div style="text-align: center;" class="wrap">
    <h2>Please activate 3XSocializer plugin first</h2>
    <div>You can do it <a href="<?php echo "http://".$_SERVER['SERVER_NAME']."".substr($_SERVER['REQUEST_URI'],0,strpos($_SERVER['REQUEST_URI'], 'wp-admin'))."wp-admin/admin.php?page=license_management"; ?>"> here</div>
</div>
<?php
    }


// Create an url based on
function create_url( $args ) {

    $base_url = 'http://3xsocializersoftware.com/'.add_query_arg( 'wc-api', 'software-api', '');

    return $base_url . '&' . http_build_query( $args );
}?>
