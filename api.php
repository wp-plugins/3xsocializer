<?php
    if(!class_exists('SocializerFacebook'))
        require_once('facebook-php-sdk/src/facebook.php');

    if (isset($_GET['appId']) && isset($_GET['secret'])){
        echo checkFBAccount($_GET['appId'], $_GET['secret']);
    } else { echo "{\"message\": \"Bad usage\"}"; }

    function checkFBAccount($app_id, $secret){
        $config = array(
            'appId'     => $app_id,
            'secret'    => $secret,
        );

        $facebook = new SocializerFacebook($config);
        $user_id = $facebook->getUser();

        try {
            $ret_obj = $facebook->api('/me', 'GET');
            if (isset($_GET['facebook_callback']) && $_GET['facebook_callback']){
                return "Please, close this tab and refresh share status in WordPress";
            }
            return "{\"message\": \"You can share to this account ($ret_obj[name])\", \"allow\": 1}";

        } catch(FacebookApiException $e) {
            $login_array = array();
            $login_array['scope'] = 'publish_stream';

            $login_array['redirect_uri'] = "http://".$_SERVER['SERVER_NAME']."".$_SERVER["REQUEST_URI"]."&facebook_callback=true";
            $login_url = $facebook->getLoginUrl($login_array);
            if (isset($_GET['facebook_callback']) && $_GET['facebook_callback']){
                return "Sorry, you need to wait some time, while Facebook setup security policy for you.<br>
                You can close this tab, wait some time and refresh share status in WordPress";
            }
            return "{\"message\": \"Please, <a target=\\\"_blank\\\"href=\\\"".$login_url."\\\">login </a> to get ability to share there\", \"allow\": 0}";
            error_log($e->getType());
            error_log($e->getMessage());
        }
    }
?>
