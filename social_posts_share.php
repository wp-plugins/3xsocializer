<?php
    $account_types = array(
        'tw' => 'Twitter',
        'fb' => 'Facebook',
        'ln' => 'LinkedIn'
    );

    $social_post = $wpdb->get_row("select * from ".$this->tbl_social_posts." where id = ".$_GET['post']);
    $account = $wpdb->get_row("select * from ".$wpdb->prefix.$social_post->network."_accounts where id = ".$social_post->network_account_id);
?>
<div class="wrap">
    <h2>Publish to <?php echo $account_types[$social_post->network].": ".$social_post->title ?></h2>
    <h3>Account name: <?php echo $account->name ?></h3>
    <?php if($social_post->network=='fb'){ ?>
    <div id="fb_check" style="display: inline-block; color: darkred;">Checking ability to publish there...</div><img id="fb_refresh_img" onclick="shareOneFBCheck()" style="cursor: pointer" src="<?php echo $this->plugin_url."images/refresh_icon.png"?>" alt="Refresh"><br><br>
    <input type="hidden" id="fb_account_id" value="<?php echo $account->id ?>">
    <input type="hidden" id='fb_check_result' value="0">
    <?php } ?>
    <form method="POST" action="<?php echo get_admin_url()."admin.php?page=social_posts&network=".$_GET['network'] ?>">
        <input type="hidden" name="action" value="share">
        <input type="hidden" name="<?php echo $social_post->network ?>_account_id" value="<?php echo $social_post->network_account_id ?>">
        <input type="hidden" name="post_id" value="<?php echo $social_post->id ?>">
        <input type="hidden" name="post_title" value="<?php echo $social_post->title ?>">
        <textarea name="<?php echo $social_post->network ?>_post" id="ta" onkeydown="updateLength('post_len',this);" onkeyup="updateLength('tw_post_len',this);" rows="5" cols="100" placeholder="Post which will be published to twitter"><?php echo $social_post->post ?></textarea>
        <div id="post_len" style="margin-left: 10px;">53 characters</div>
        <script type="text/javascript">updateLength('post_len',document.getElementById('ta')) </script>
        <?php if($social_post->network=='fb'){ ?>
        <label for="upload_image">
            <input id="upload_image" type="text" size="36" name="upload_image" value="<?php echo $social_post->url ?>" />
            <input id="upload_image_button" type="button" value="Upload Image" />
        </label><br>
        <?php } ?>
        <input type="submit" value="Publish">
    </form>
</div>
<?php if($social_post->network=='fb'){ ?>
<script type="text/javascript">
    shareOneFBCheck();
</script>
<?php } ?>