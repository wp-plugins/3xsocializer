<div class="wrap">
<h2>Accounts and Account Sets Management</h2>
<h3 style="margin-bottom: 0px">Account Sets</h3>
<?php
    $this->data['account_sets'] = $wpdb->get_results("SELECT * FROM `" . $this->tbl_account_sets . "`", ARRAY_A);

    $tw_acs = $wpdb->get_results("SELECT * FROM ".$this->tbl_tw_accounts);
    $fb_acs = $wpdb->get_results("SELECT * FROM ".$this->tbl_fb_accounts);
    $ln_acs = $wpdb->get_results("SELECT * FROM ".$this->tbl_ln_accounts);

    $action_url = substr($_SERVER['REQUEST_URI'],0,strpos($_SERVER['REQUEST_URI'],"account_sets")+12);

    $account_types = array(
        'tw' => 'Twitter',
        'fb' => 'Facebook',
        'ln' => 'LinkedIn'
    );
    foreach ($this->data['account_sets'] as $key => $record):
?>
<!--ACCOUNT_SETS--ACCOUNT_SETS--ACCOUNT_SETS--ACCOUNT_SETS--ACCOUNT_SETS-->
<div class="tx_asm_record">
    <div><?php echo $record['name']; echo $record['default'] ? " <i>(default)</i>" : ""; ?></div>
    <div><a onclick="expandEdit(<?php echo $record['id'] ?>,'set')">Edit</a></div>
    <div><a href="#" onclick="if (confirm('You are sure?'))
        location.href='<?php
        $pos = strpos($_SERVER['REQUEST_URI'],"&delete_");
        if ($pos){
            echo substr($_SERVER['REQUEST_URI'],0,$pos)."&delete_set=".$record['id'];
        } else {
            echo $_SERVER['REQUEST_URI']."&delete_set=".$record['id'];
        }
        ?>'">Delete</a></div>
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
            <input style="width: 15px;" <?php echo $record[$key."_disabled"] ? "checked=\"checked\"" : "" ?> type="checkbox" name="<?php echo $key ?>_disabled"> disabled
            <div style="clear: both"></div>
            <?php
        endforeach;
        ?>
        <p style="width: 95px">Default:</p> <input <?php echo $record['default'] ? "checked=\"checked\"" : "" ?> style="width: 15px; position: relative; left: -6px" type="checkbox" name="set_default">
        <hr>

        <input type="submit" class="editAccountBtn" value="Save">
        <a class="cancel_btn" onclick="cancelEditing(<?php echo $record['id'] ?>,'set')">Cancel</a>
    </div>
</form>
    <?php
endforeach;
?>
<button id="expandAdd" onclick="expandAddingBlock('set')">+ Add new account set</button>
<form action="<?php echo $action_url ?>" method="POST">
    <div id="add_set_block" class="add_block" style="display: none; ">
        <input type="hidden" name="action" value="add_set">
        <span class="popup_name">New Account Set</span>
        <p style="width: 95px">Name:</p><input id="n_name" name="n_name" type="text" placeholder="Name">
        <hr>
        <?php
            foreach ($account_types as $key => $value):
        ?>
            <span class="sn_name"><?php echo $value ?>:</span>
            <select onchange="expandAddingBlock('<?php echo $key ?>','set',this)" name="<?php echo $key ?>_id">
                <option value="-1">--Choose account--</option>
                <option value="-2">--Add new account--</option>
                <?php
                    $cur_array = $key."_acs";
                    foreach ($$cur_array as $account):
                        echo "<option value='".$account->id."'>".$account->name."</option>\n";
                    endforeach;
                ?>
            </select>
            <input style="width: 15px;" type="checkbox" name="<?php echo $key ?>_disabled"> disabled
            <div style="clear: both"></div>
        <?php
            endforeach;
        ?>
        <p style="width: 95px">Default:</p> <input style="width: 15px; position: relative; left: -6px" type="checkbox" name="set_default">
        <hr>
        <input type="submit" class="addAccountBtn" value="Add">
        <a class="cancel_btn" onclick="cancelAdding('set')">Cancel</a>
    </div>
</form>

<!--TWITTER--TWITTER--TWITTER--TWITTER--TWITTER-->
    <h3 style="margin-bottom: 0px">Twitter Accounts</h3>
    <?php
        foreach ($tw_acs as $record):
    ?>
    <div class="tx_asm_record">
        <div><?php echo $record->name ?></div>
        <div><a onclick="expandEdit(<?php echo $record->id ?>,'tw')">Edit</a></div>
        <div><a href="#" onclick="if (confirm('You are sure?'))
            location.href='<?php
        $pos = strpos($_SERVER['REQUEST_URI'],"&delete_");
        if ($pos){
            echo substr($_SERVER['REQUEST_URI'],0,$pos)."&delete_tw=".$record->id;
        } else {
            echo $_SERVER['REQUEST_URI']."&delete_tw=".$record->id;
        }
        ?>'">Delete</a></div>
    </div>

            <form action="<?php echo $action_url ?>" method="POST">
                <div class="edit_block" id="edit_tw_block_<?php echo $record->id ?>" style="display: none; ">
                    <input type="hidden" name="action" value="edit_tw">
                    <input type="hidden" name="id" value="<?php echo $record->id ?>">
                    <span class="popup_name">Edit Twitter Account: <?php echo $record->name ?></span>

                    <p>Name:</p><input  value="<?php echo $record->name ?>" name="n_name" type="text" placeholder="Name">
                    <hr>
                    <p>Consumer key:</p>
                    <input
                        value="<?php echo $record->consumer_key ?>"
                        name="tw_ck"
                        type="text"
                        placeholder="Consumer key"><br>
                    <p>Consumer secret:</p>
                    <input
                        value="<?php echo $record->consumer_secret ?>"
                        name="tw_cs"
                        type="text"
                        placeholder="Consumer secret"><br>
                    <p>Access token:</p>
                    <input
                        value="<?php echo $record->access_token ?>"
                        name="tw_at"
                        type="text"
                        placeholder="Access token"><br>
                    <p>Access token secret:</p>
                    <input
                        value="<?php echo $record->access_token_secret ?>"
                        name="tw_ats"
                        type="text"
                        placeholder="Access token secret"><br>
                    <hr>

                    <input type="submit" class="editAccountBtn" value="Save">
                    <a class="cancel_btn" onclick="cancelEditing(<?php echo $record->id ?>,'tw')">Cancel</a>
                </div>
            </form>

    <?php
    endforeach;
    ?>
    <button id="expandAdd" onclick="expandAddingBlock('tw')">+ Add new Twitter account</button>
    <form action="<?php echo $action_url ?>" method="POST">
        <div id="add_tw_block" class="add_block" style="display: none; ">
            <input type="hidden" name="action" value="add_tw">
            <span class="popup_name">New Twitter Account</span>
            <p>Name:</p><input name="n_name" type="text" placeholder="Name">
            <hr>
            <p>Consumer key:</p>
            <input
                name="tw_ck"
                type="text"
                placeholder="Consumer key"><br>
            <p>Consumer secret:</p>
            <input
                name="tw_cs"
                type="text"
                placeholder="Consumer secret"><br>
            <p>Access token:</p>
            <input
                name="tw_at"
                type="text"
                placeholder="Access token"><br>
            <p>Access token secret:</p>
            <input
                name="tw_ats"
                type="text"
                placeholder="Access token secret"><br>
            <hr>
            <input type="submit" class="addAccountBtn" value="Add">
            <a class="cancel_btn" onclick="cancelAdding('tw')">Cancel</a>
        </div>
    </form>
<!--FACEBOOK--FACEBOOK--FACEBOOK--FACEBOOK--FACEBOOK-->
<h3 style="margin-bottom: 0px">Facebook Accounts</h3>
<?php foreach ($fb_acs as $record): ?>
<div class="tx_asm_record">
    <div><?php echo $record->name ?></div>
    <div><a onclick="expandEdit(<?php echo $record->id ?>,'fb')">Edit</a></div>
    <div><a href="#" onclick="if (confirm('You are sure?'))
            location.href='<?php
        $pos = strpos($_SERVER['REQUEST_URI'],"&delete_");
        if ($pos){
            echo substr($_SERVER['REQUEST_URI'],0,$pos)."&delete_fb=".$record->id;
        } else {
            echo $_SERVER['REQUEST_URI']."&delete_fb=".$record->id;
        }
        ?>'">Delete</a></div>
</div>

<form action="<?php echo $action_url ?>" method="POST">
    <div class="edit_block" id="edit_fb_block_<?php echo $record->id ?>" style="display: none; ">
        <input type="hidden" name="action" value="edit_fb">
        <input type="hidden" name="id" value="<?php echo $record->id ?>">
        <span class="popup_name">Edit Facebook Account: <?php echo $record->name ?></span>

        <p>Name:</p><input  value="<?php echo $record->name ?>" name="n_name" type="text" placeholder="Name">
        <hr>
        <p>App ID:</p>
        <input
            value="<?php echo $record->app_id ?>"
            name="fb_ai"
            type="text"
            placeholder="App ID"><br>
        <p>App Secret:</p>
        <input
            value="<?php echo $record->app_secret ?>"
            name="fb_as"
            type="text"
            placeholder="App Secret"><br>
        <hr>

        <input type="submit" class="editAccountBtn" value="Save">
        <a class="cancel_btn" onclick="cancelEditing(<?php echo $record->id ?>,'fb')">Cancel</a>
    </div>
</form>

    <?php endforeach; ?>
<button id="expandAdd" onclick="expandAddingBlock('fb')">+ Add new Facebook account</button>
<form action="<?php echo $action_url ?>" method="POST">
    <div id="add_fb_block" class="add_block" style="display: none; ">
        <input type="hidden" name="action" value="add_fb">
        <span class="popup_name">New Facebook Account</span>
        <p>Name:</p><input name="n_name" type="text" placeholder="Name">
        <hr>
        <p>App ID:</p>
        <input
            name="fb_ai"
            type="text"
            placeholder="App ID"><br>
        <p>App Secret:</p>
        <input
            name="fb_as"
            type="text"
            placeholder="App Secret"><br>
        <hr>
        <input type="submit" class="addAccountBtn" value="Add">
        <a class="cancel_btn" onclick="cancelAdding('fb')">Cancel</a>
    </div>
</form>
<!--LINKEDIN--LINKEDIN--LINKEDIN--LINKEDIN--LINKEDIN-->
    <h3 style="margin-bottom: 0px">LinkedIn Accounts</h3>
    <?php foreach ($ln_acs as $record): ?>
    <div class="tx_asm_record">
        <div><?php echo $record->name ?></div>
        <div><a onclick="expandEdit(<?php echo $record->id ?>,'ln')">Edit</a></div>
        <div><a href="#" onclick="if (confirm('You are sure?'))
            location.href='<?php
            $pos = strpos($_SERVER['REQUEST_URI'],"&delete_");
            if ($pos){
                echo substr($_SERVER['REQUEST_URI'],0,$pos)."&delete_ln=".$record->id;
            } else {
                echo $_SERVER['REQUEST_URI']."&delete_ln=".$record->id;
            }
            ?>'">Delete</a></div>
    </div>

    <form action="<?php echo $action_url ?>" method="POST">
        <div class="edit_block" id="edit_ln_block_<?php echo $record->id ?>" style="display: none; ">
            <input type="hidden" name="action" value="edit_ln">
            <input type="hidden" name="id" value="<?php echo $record->id ?>">
            <span class="popup_name">Edit LinkedIn Account: <?php echo $record->name ?></span>

            <p>Name:</p><input  value="<?php echo $record->name ?>" name="n_name" type="text" placeholder="Name">
            <hr>
            <p>API Key:</p>
            <input
                value="<?php echo $record->consumer_key ?>"
                name="ln_ck"
                type="text"
                placeholder="API Key"><br>
            <p>Secret Key:</p>
            <input
                value="<?php echo $record->consumer_secret ?>"
                name="ln_cs"
                type="text"
                placeholder="Secret Key"><br>
            <p>OAuth User Token:</p>
            <input
                value="<?php echo $record->access_token ?>"
                name="ln_at"
                type="text"
                placeholder="OAuth User Token"><br>
            <p>AOAuth User Secret:</p>
            <input
                value="<?php echo $record->access_token_secret ?>"
                name="ln_ats"
                type="text"
                placeholder="OAuth User Secret"><br>
            <hr>

            <input type="submit" class="editAccountBtn" value="Save">
            <a class="cancel_btn" onclick="cancelEditing(<?php echo $record->id ?>,'ln')">Cancel</a>
        </div>
    </form>

    <?php endforeach; ?>
    <button id="expandAdd" onclick="expandAddingBlock('ln')">+ Add new LinkedIn account</button>
    <form action="<?php echo $action_url ?>" method="POST">
        <div id="add_ln_block" class="add_block" style="display: none; ">
            <input type="hidden" name="action" value="add_ln">
            <span class="popup_name">New LinkedIn Account</span>
            <p>Name:</p><input name="n_name" type="text" placeholder="Name">
            <hr>
            <p>API Key:</p>
            <input
                name="ln_ck"
                type="text"
                placeholder="API Key"><br>
            <p>Secret Key:</p>
            <input
                name="ln_cs"
                type="text"
                placeholder="Secret Key"><br>
            <p>OAuth User Token:</p>
            <input
                name="ln_at"
                type="text"
                placeholder="OAuth User Token"><br>
            <p>AOAuth User Secret:</p>
            <input
                name="ln_ats"
                type="text"
                placeholder="OAuth User Secret"><br>
            <hr>
            <input type="submit" class="addAccountBtn" value="Add">
            <a class="cancel_btn" onclick="cancelAdding('ln')">Cancel</a>
        </div>
        <script type="text/javascript">onloadEditAccount()</script>
    </form>
</div>

