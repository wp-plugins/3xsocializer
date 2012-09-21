<?php
    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        $email         = $_POST['licence_email'];
        $licence_key   = $_POST['licence_key'];
        $product_id    = $_POST['licence_type'];

        $args = array(
            'request'     => 'activation',
            'email'       => $email,
            'licence_key' => $licence_key,
            'product_id'  => $product_id,
        );
        $base_url = 'http://3xsocializersoftware.com/'.add_query_arg( 'wc-api', 'software-api', '');

        $target_url = $base_url . '&' . http_build_query( $args );
//        echo $target_url;
        $data = wp_remote_get( $target_url );
        $result = json_decode($data['body']);
        if (isset($result->activated)){
            if ($result->activated){
                //echo '<div style="text-align: center;" class="wrap"><h2>3XSocializer plugin activation</h2>';
                //echo "<h3>Thank you! Your plugin activated! Please <a href=''>refresh</a> page and use it!</h3>";
                $wpdb->update($this->tbl_3x_settings, array('app_key' => $licence_key, 'licence_type' => $product_id, 'app_email' => $email, 'activated' => true), array('id' => 1));
                echo "<div style=\"text-align: center;background-color: rgba(0, 255, 0, 0.3);border: 2px solid rgba(0, 255, 0, 0.5);\">Thank you — Your plugin is now activated and ready to use.</div>";
            } else {
//                echo '<div style="text-align: center;" class="wrap"><h2>3XSocializer plugin activation</h2>';
                echo "<div style=\"text-align: center;background-color: rgba(255, 0, 0, 0.3);border: 2px solid rgba(255, 0, 0, 0.5);\">Not activated.<br><strong>Error message</strong>: ".$result->error."</div>";
//                echo '</div>';
            }
        } else {
            echo '<div style="text-align: center;" class="wrap"><h2>3XSocializer plugin activation</h2>';
            echo "Not activated. Unknown error. <br><a href=''>Try one more time</a>";
        }
    }

    $settings = $wpdb->get_row("select * from ".$this->tbl_3x_settings." where id=1");
?>

<div class="wrap">
    <h2>License management</h2>
    <h3>License status</h3>
    <div>
        <?php if($settings->activated){
            $status = null;
            $args = array(
                'wc-api'	  => 'software-api',
                'request'     => 'check',
                'email'		  => $settings->app_email,
                'licence_key' => $settings->app_key,
                'product_id'  => $settings->licence_type
            );
            $base_url = 'http://3xsocializersoftware.com/'.add_query_arg( 'wc-api', 'software-api', '');

            $target_url = $base_url . '&' . http_build_query( $args );
//                 echo $target_url;
            $data = wp_remote_get( $target_url );
            $result = json_decode($data['body']);
//        var_dump($result);
            if (isset($result->success)){
                if ($result->success){
                    $status = "Valid";
                } else {
                    $status = "Invalid";
                }
            } else {
                $status = "Invalid";
            }

            echo "<strong>Licensed to: </strong>".$settings->app_email."<br>";
            echo "<strong>Product ID: </strong>".$settings->licence_type."<br>";
            echo "<strong>License key: </strong><input style='width: 200px' type='password' value='**************************************************'>";
            echo "<img title='".($status == "Valid" ? "Valid key" : "Ivalid key")."' style='position: relative; top:6px; left: 5px;' src=\"".$this->plugin_url."/images/".($status == "Valid" ? "tick" : "cross")."_icon.png\"><br>";
        } else {
            echo "Please, activate plugin using form below";
        }
        ?>
        <br><br>
        <h3>Update your license status</h3>
        <form action="" method="POST">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Your email</th>
                    <td>
                        <input style="width: 250px;" type="text" placeholder="Your email" name="licence_email"><br>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Your key</th>
                    <td>
                        <input style="width: 250px;" type="text" placeholder="Your key" name="licence_key"><br>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Product id</th>
                    <td>
                        <select style="width: 250px;" name="licence_type">
                            <option value="3xSocializer">3xSocializer</option>
                            <option value="3xSocializer_developer">3xSocializer (developer)</option>
                        </select>
                    </td>
                </tr>
            </table>
            <input type="submit" class="button-primary" value="Update!">

        </form>
    </div>
</div>