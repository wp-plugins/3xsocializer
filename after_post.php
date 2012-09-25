<?php
    global $post;
?>
<div class="wrap">
Sharing screen after publish:&nbsp;&nbsp;&nbsp;
    <input type="radio" name="txs_open" value="on" checked>On&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <input type="radio" name="txs_open" value="off">Off<hr>

    <?php echo '<input type="hidden" name="eventmeta_noncename" id="eventmeta_noncename" value="'.wp_create_nonce('3Xsocial').'" />';?>
    <div class="screenr_container">
        <div style="width: 200px;">Embedded video code:</a><img onclick="helpPopup('<?php echo $this->plugin_url ?>help/youtube.php')" style='float: right; position: relative; top: -3px; left: 0px; cursor: pointer;' src="<?php echo $this->plugin_url ?>images/help_icon.png"></div>
        <div style="clear: both;"></div>
        <textarea rows="3" cols="40" name="txs_embedded_video" placeholder="Embedded video code"><?php echo get_post_meta($post->ID, 'txs_embedded_video', true); ?></textarea>
        <a href="http://screenr.com" style="float: right;" target="_blank"><img src="<?php echo $this->plugin_url ?>images/screenr_logo_small.gif" alt="Screenr"></a>
        <img onclick="helpPopup('<?php echo $this->plugin_url ?>help/screenr.php')" style='float: right; position: relative; top: 14px; left: -6px; cursor: pointer;' src="<?php echo $this->plugin_url ?>images/help_icon.png">
    </div>
</div>
