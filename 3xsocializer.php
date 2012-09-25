<?php
/*
Plugin Name: 3XSocializer
Plugin URI: DonCrowther.com
Description: Social Sharing Utility
Version: 0.98.22
Author: Don Crowther
Author URI: http://doncrowther.com
License: GPL2
*/
/*  Copyright 2012  Don Crowther  (email : HelpMe@3XSocial.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
    die('You are not allowed to call this page directly.');
}
if (!class_exists('TXSocial')) {
    class TXSocial
    {
        //save internal data
        public $data = array();

        function TXSocial()
        {
            global $wpdb;

            //initialize plugin constant
            DEFINE('TXSocial', true);

            //save plugin name
            $this->plugin_name = plugin_basename(__FILE__);

            //plugin url
            $this->plugin_url = trailingslashit(WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)));

            //table for saving social posts
            $this->tbl_social_posts = $wpdb->prefix.'_3xs_social_posts';
            $this->tbl_account_sets = $wpdb->prefix.'_3xs_account_sets';
            $this->tbl_tw_accounts = $wpdb->prefix.'_3xs_tw_accounts';
            $this->tbl_fb_accounts = $wpdb->prefix.'_3xs_fb_accounts';
            $this->tbl_ln_accounts = $wpdb->prefix.'_3xs_ln_accounts';
            $this->tbl_3x_settings = $wpdb->prefix.'_3xs_settings';

            //activation function
            register_activation_hook($this->plugin_name, array(&$this, 'activate'));

            //deactivation function
            register_deactivation_hook($this->plugin_name, array(&$this, 'deactivate'));

            //deletion function
            register_uninstall_hook($this->plugin_name, 'uninstall');

            //check if we in admin interface
            if (is_admin()){

                //add styles and scripts
                add_action('wp_print_scripts', array(&$this, 'admin_load_scripts'));
                add_action('activated_plugin', array(&$this, 'save_error'));
                //add menu for plugin
                add_action( 'admin_menu', array(&$this, 'admin_generate_menu') );
                add_action( 'admin_menu', array(&$this, 'post_custom_fields'));
                add_action( 'admin_init', array(&$this, 'txs_admin_init'));
                add_filter('redirect_post_location', array(&$this, 'redirect_to_post_on_publish_or_save'));

                add_shortcode('video', array (&$this, 'show_embedded_video'));
            } else {
                add_shortcode('video', array (&$this, 'show_embedded_video'));
            }

            $this->postScheduled();

            if ((isset($_GET['page']) && $_GET['page'] == 'txsocializer') ||
                (isset($_GET['page']) && $_GET['page'] == 'social_posts' && isset($_GET['post']))) {
                add_action('admin_print_scripts', array(&$this, 'upload_admin_scripts'));
                add_action('admin_print_styles', array(&$this, 'upload_admin_styles'));
            }
        }
        function postScheduled(){
            global $wpdb;
            if($wpdb->get_var("SHOW TABLES LIKE '".$this->tbl_social_posts."'") != $this->tbl_social_posts)
                return;
            $posts = $wpdb->get_results("SELECT * FROM ".$this->tbl_social_posts." where state='scheduled' and scheduled_time < '".date("Y-m-d H:i:s",current_time( 'timestamp' ))."'");
            foreach($posts as $post){
                $this->txs_post_scheduled($post->id);
            }
        }
        function txs_post_scheduled($post_id){
            global $wpdb;
            $social_post = $wpdb->get_row("select * from ".$this->tbl_social_posts." where id = ".$post_id);

            switch($social_post->network):
                case 'fb':
                    $fb_account = $wpdb->get_row("select * from ".$this->tbl_fb_accounts." where id = ".$social_post->network_account_id);
                    $update_post = array('state' => 'published', 'status' => false);
                    $wpdb->update($this->tbl_social_posts, $update_post, array('id' => $post_id));
                    $update_post["status"] = $this->facebook_publish($fb_account,$social_post->post,$social_post->url);
                    $wpdb->update($this->tbl_social_posts, $update_post, array('id' => $post_id));
                    break;
                case 'tw':
                    $tw_account = $wpdb->get_row("select * from ".$this->tbl_tw_accounts." where id = ".$social_post->network_account_id);
                    $update_post = array('state' => 'published', 'status' => false);
                    $wpdb->update($this->tbl_social_posts, $update_post, array('id' => $post_id));
                    $update_post["status"] = $this->twitter_publish($tw_account,$social_post->post);
                    $wpdb->update($this->tbl_social_posts, $update_post, array('id' => $post_id));
                    break;
                case 'ln':
                    $ln_account = $wpdb->get_row("select * from ".$this->tbl_ln_accounts." where id = ".$social_post->network_account_id);
                    $update_post = array('state' => 'published', 'status' => false);
                    $wpdb->update($this->tbl_social_posts, $update_post, array('id' => $post_id));
                    $update_post["status"] = $this->linkedin_publish($ln_account,$social_post->post);
                    $wpdb->update($this->tbl_social_posts, $update_post, array('id' => $post_id));
                    break;
                default:
                    break;
            endswitch;
        }
        function save_error(){
            update_option('plugin_error',  ob_get_contents());
        }
        function txs_admin_init(){
            global $wpdb;

            if(!class_exists('SocializerFacebook'))
                require_once('facebook-php-sdk/src/facebook.php');
            if(isset($_POST['fb_publish']) && $_POST['fb_publish'] == "on"){
                if (trim($_POST['fb_post']) != ""){
                    $social_post = array(
                        "title"     => !empty($_POST['post_title']) ? $_POST['post_title'] : (strlen($_POST['fb_post']) > 100 ? substr($_POST['fb_post'], 0 ,100)."..." : $_POST['fb_post']),
                        "post"      => $_POST['fb_post'],
                        "origin_id" => $_POST['origin_id'],
                        "network"   => "fb",
                        "network_account_id" => $_POST['fb_account_id'],
                        "state" => $_POST['fb_publish_mode'],
                        "url" => isset($_POST['upload_image']) && trim($_POST['upload_image']) != "" ? $_POST['upload_image'] : null,
                        "scheduled_time" => "$_POST[fb_aa]-$_POST[fb_mm]-$_POST[fb_jj] $_POST[fb_hh]:$_POST[fb_mn]:00",
                    );

                    if($_POST['fb_publish_mode'] == "published"){
                        $fb_account = $wpdb->get_row("select * from ".$this->tbl_fb_accounts." where id = ".$_POST['fb_account_id']);
                        $social_post["status"] = $this->facebook_publish($fb_account,$_POST['fb_post'],$social_post['url']);
                    }
                    $wpdb->insert($this->tbl_social_posts, $social_post);
                }
            } else if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == "share"){
                if($_GET['network'] == "fb"){
                    if (trim($_POST['fb_post']) != ""){
                        $social_post = array(
                            "title"     => $_POST['post_title'],
                            "post"      => $_POST['fb_post'],
                            "origin_id" => $_POST['origin_id'],
                            "network"   => "fb",
                            "network_account_id" => $_POST['fb_account_id'],
                            "url" => isset($_POST['upload_image']) && trim($_POST['upload_image']) != "" ? $_POST['upload_image'] : null,
                            "state" => "published",
                        );

                        $fb_account = $wpdb->get_row("select * from ".$this->tbl_fb_accounts." where id = ".$_POST['fb_account_id']);

                        $social_post["status"] = $this->facebook_publish($fb_account,$_POST['fb_post'],$social_post['url']);

                        $wpdb->update($this->tbl_social_posts, $social_post, array('id' => $_POST['post_id']));

                    }
                }
            } elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['3xsocializer_facebook_check']) && $_GET['3xsocializer_facebook_check'] == 'check' && isset($_GET['page_id'])){
                $wpdb->show_errors();
                $settings = $wpdb->get_row("select * from ".$this->tbl_3x_settings." where id=1");

                $page_info = $wpdb->get_row("select * from ".$this->tbl_fb_accounts." where id=".$_GET['page_id']);
//                var_dump($page_info);
                $config = array(
                    'appId'     => $settings->fb_app_id,
                    'secret'    => $settings->fb_app_secret
                );
//                echo var_dump($settings);
                $facebook = new SocializerFacebook($config);
                $user_id = $facebook->getUser();
                if ($user_id){

                    try {
                        $ret_obj = $facebook->api('/me', 'GET');
    //                    var_dump($ret_obj);
    //                    var_dump($page_info);
                        if (isset($page_info) && ($ret_obj['username'] == $page_info->username)){
                            if (isset($_GET['facebook_callback']) && $_GET['facebook_callback']){
                                echo "Please, close this tab and refresh share status in WordPress";
                            } else {
                                echo "{\"message\": \"You can share to this account ($ret_obj[name])\", \"allow\": 1}";
                            }
                        }
                        else {
                            if (isset($_GET['facebook_callback']) && $_GET['facebook_callback']){
                                echo "You are signed under another Facebook account '$ret_obj[name]' and page belongs to '".$page_info->username."'";
                            } else {
                                echo "{\"message\": \"You are signed under another Facebook account '$ret_obj[username]' and page belongs to '".$page_info->username."'\", \"allow\": 0}";
                            }
//                            var_dump($ret_obj);
                            $facebook->destroySession();
                        }

                    } catch(FacebookApiException $e) {
                        $login_array = array();
                        $login_array['scope'] = 'publish_stream, manage_pages';
                        $login_array['redirect_uri'] = "http://".$_SERVER['SERVER_NAME']."".$_SERVER["REQUEST_URI"]."&facebook_callback=true";
                        $login_url = $facebook->getLoginUrl($login_array);
                        if (isset($_GET['facebook_callback']) && $_GET['facebook_callback']){
//                            var_dump($e);
                            echo "Sorry, you need to wait some time, while Facebook setup security policy for you.<br>
                            You can close this tab, wait some time and refresh share status in WordPress";
                        } else {
                            echo "{\"message\": \"Please, <a target=\\\"_blank\\\"href=\\\"".$login_url."\\\">login </a> to get ability to share there\", \"allow\": 0}";
                        }
                        error_log($e->getType());
                        error_log($e->getMessage());
                    }
                } else {
                    $login_array['scope'] = 'publish_stream, manage_pages';
                    $login_array['redirect_uri'] = "http://".$_SERVER['SERVER_NAME']."".$_SERVER["REQUEST_URI"]."&facebook_callback=true";
                    $login_url = $facebook->getLoginUrl($login_array);
                    echo "{\"message\": \"Please, <a target=\\\"_blank\\\"href=\\\"".$login_url."\\\">login </a> to get ability to share there\", \"allow\": 0}";
                }
            }
        }

        function redirect_to_post_on_publish_or_save($location) {
            $current_post = get_post(get_the_id());
            if (isset($_POST['txs_open']) && $_POST['txs_open'] == "on" && $current_post->post_status != "draft"){
                wp_redirect("?page=txsocializer&post_id=".get_the_id());
            } else {
                return $location;
            }
        }

        function make_bitly_url($url,$login = 'o_12t994q7k8',$appkey = 'R_cf45680b5a11f1885c5fcdf82d621572' ,$format = 'json',$version = '2.0.1'){
            $bitly = 'http://api.bit.ly/shorten?version='.$version.'&longUrl='.urlencode($url).'&login='.$login.'&apiKey='.$appkey.'&format='.$format;
            $response = file_get_contents($bitly);
            if(strtolower($format) == 'json'){
                $json = @json_decode($response,true);
                return $json['results'][$url]['shortUrl'];
            } else {
                $xml = simplexml_load_string($response);
                return 'http://bit.ly/'.$xml->results->nodeKeyVal->hash;
            }
        }

        function show_embedded_video(){
            return get_post_meta(get_the_ID(), "txs_embedded_video", TRUE);
        }

        function post_custom_fields(){
            add_meta_box( 'Screener','3XSocializer', array(&$this, 'after_post_add'), 'post', 'normal', 'high');

            add_action('save_post', array(&$this, 'save_post_options'));
        }

        function save_post_options(){

            global $post;

            if (isset($post->ID)){
                if (!wp_verify_nonce( $_POST['eventmeta_noncename'],'3Xsocial')) {
                    return $post->ID;
                }

                if (!current_user_can( 'edit_post', $post->ID ))
                    return $post->ID;

                $key = "txs_embedded_video";
                $value = $_POST['txs_embedded_video'];

                if($post->post_type == 'revision' ) return; // Don't store custom data twice

                if(get_post_meta($post->ID, $key, FALSE)) { // If the custom field already has a value
                    update_post_meta($post->ID, $key, $value);
                } else { // If the custom field doesn't have a value
                    add_post_meta($post->ID, $key, $value);
                }

                if(!$value) delete_post_meta($post->ID, $key); // Delete if blank
            }
        }

        function after_post_add(){
            include_once('after_post.php');
        }

        function upload_admin_scripts() {
            wp_enqueue_script('media-upload');
            wp_enqueue_script('thickbox');
            wp_register_script('my-upload', $this->plugin_url.'/my-script.js', array('jquery','media-upload','thickbox'));
            wp_enqueue_script('my-upload');
        }

        function upload_admin_styles() {
            wp_enqueue_style('thickbox');
        }

        function admin_load_scripts(){
            wp_register_script('TXSocialAdminJs', $this->plugin_url .'js/admin-scripts.js');
            wp_enqueue_script('TXSocialAdminJs');
            wp_register_style('TXSocialAdminCss', $this->plugin_url.'css/admin-style.css');
            wp_enqueue_style('TXSocialAdminCss');
        }

        function admin_generate_menu(){
            add_menu_page('3XSocializer Social Sharing Utility', '3XSocializer', 'manage_options', 'txsocializer', array(&$this, 'admin_sharing_utility'), $this->plugin_url."images/icon.png", 6);

            add_submenu_page( 'txsocializer', 'Social Posts Management', 'Social Posts', 'manage_options', 'social_posts', array(&$this,'admin_social_posts'));
            add_submenu_page( 'txsocializer', 'Accounts and Account Sets Management', 'Settings', 'manage_options', 'account_sets', array(&$this,'admin_settings'));
            add_submenu_page( 'txsocializer', 'License management', 'License', 'manage_options', 'license_management', array(&$this,'admin_license_management'));
            add_submenu_page( 'txsocializer', 'About 3XSocializer', 'About Plugin', 'manage_options', 'plugin_info', array(&$this,'admin_plugin_info'));
        }

        function admin_license_management(){
            global $wpdb;
            include_once('license.php');
        }

        public function admin_sharing_utility(){
            global $wpdb;
            if (isset($_POST['n_name']) && !empty($_POST['n_name']) &&
            (isset($_POST['action']) && $_POST['action']=="quick_add_set")){
                $enabled_sn = decbin($_POST['enabled_code']);
                $enabled_sn = strrev($enabled_sn);
                $newSet = array(
                    'tw_account_id' => $_POST['tw_id'] != -1 ? $_POST['tw_id'] : null,
                    'fb_account_id' => $_POST['fb_id'] != -1 ? $_POST['fb_id'] : null,
                    'ln_account_id' => $_POST['ln_id'] != -1 ? $_POST['ln_id'] : null,
                    'tw_enabled'       => isset($enabled_sn[0]) && $enabled_sn[0] == '1' ? true : false,
                    'fb_enabled'       => isset($enabled_sn[1]) && $enabled_sn[1] == '1' ? true : false,
                    'ln_enabled'       => isset($enabled_sn[2]) && $enabled_sn[2] == '1' ? true : false,
                    'name'          => $_POST['n_name'],
                );
                $override = isset($_POST['acs_override']) && $_POST['acs_override'] != -1 ? $_POST['acs_override'] : null;
                if ($override){
                    $wpdb->update($this->tbl_account_sets, $newSet, array('id' => $override));
                    if (isset($_POST['set_default']) && $_POST['set_default']){
                        $wpdb->query($wpdb->prepare("update ".$this->tbl_account_sets. " set `default`=false where id <> $override"));
                    }
                } else {
                    $wpdb->insert($this->tbl_account_sets, $newSet);
                    if (isset($_POST['set_default']) && $_POST['set_default']){
                        $inserted = $wpdb->insert_id;
                        $wpdb->query($wpdb->prepare("update ".$this->tbl_account_sets. " set `default`=false where id <> $inserted"));
                    }
                }
            }
            include_once('sharing_utility.php');
        }

        function twitter_publish($tw_account, $message) {
            require_once 'twitteroauth.php';
            define("CONSUMER_KEY", $tw_account->consumer_key);
            define("CONSUMER_SECRET", $tw_account->consumer_secret);
            define("OAUTH_TOKEN", $tw_account->access_token);
            define("OAUTH_SECRET", $tw_account->access_token_secret);

            $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, OAUTH_TOKEN, OAUTH_SECRET);

            $content = $connection->get('account/verify_credentials');

            $status = $connection->post('statuses/update', array('status' => $message));
            return isset($status->error) ? false : true;
        }

        function linkedin_publish($ln_account, $message) {
            if (!class_exists("OAuth"))
                require_once("OAuth.php");

            $domain = "https://api.linkedin.com/uas/oauth";
            $sig_method = new OAuthSignatureMethod_HMAC_SHA1();


            $test_consumer = new OAuthConsumer($ln_account->consumer_key, $ln_account->consumer_secret, NULL);


            $endpoint = "http://api.linkedin.com/v1/people/~/current-status";
            $body = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><current-status>".$message."</current-status>";
            $fp = fopen('php://temp/maxmemory:256000', 'w');
            if (!$fp) {
                die('could not open temp memory data');
            }
            fwrite($fp, $body);
            fseek($fp, 0);

            //$req_token = new OAuthConsumer($oauth['oauth_token'], $oauth['oauth_token_secret'], 1);
            $req_token = new OAuthConsumer($ln_account->access_token, $ln_account->access_token_secret, 1);
            //$profile_req = OAuthRequest::from_consumer_and_token($test_consumer, $req_token, "GET", $endpoint, array("name" => "intercom")); # but no + symbol here!
            $profile_req = OAuthRequest::from_consumer_and_token($test_consumer, $req_token, "PUT", $endpoint, array());
            //$profile_req =
            $profile_req->sign_request($sig_method, $test_consumer, $req_token);


            $ch = curl_init();

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_PUT, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($ch, CURLOPT_INFILE, $fp); // file pointer
            curl_setopt($ch, CURLOPT_INFILESIZE, strlen($body));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                $profile_req->to_header()
            ));
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            $output = curl_exec($ch);


            if (curl_errno($ch)) {
                echo 'Curl error 2: ' . curl_error($ch);
                return false;
            }
            curl_close($ch);
            if (trim($output) == ''){
               return true;
            } else {
               return false;
            }
        }

        function facebook_publish($fb_account, $message, $image = null) {
//            echo "image: ".var_dump($image);
            global $wpdb;
            $settings = $wpdb->get_row("select * from ".$this->tbl_3x_settings." where id=1");

//            $page_info = $wpdb->get_row("select * from ".$this->tbl_fb_accounts." where id=".$fb_account->page_id);

            $config = array(
                'appId'     => $settings->fb_app_id,
                'secret'    => $settings->fb_app_secret
            );
            if(!class_exists('SocializerFacebook'))
                require_once('facebook-php-sdk/src/facebook.php');
            $facebook = new SocializerFacebook($config);
            $user_profile = $facebook->api('/me','GET');
            if($user_profile){
                if($fb_account->page_id != 'me'){
                    $pages = $facebook->api($user_profile['username']."/accounts", 'GET');
                    $data = $pages['data'];
                    foreach($data as $page){
                        if($page['id'] == $fb_account->page_id){
                            try {
                                $data = array(
                                    'message' => $message,
                                    'access_token' => $page['access_token']
                                );
                                if ($image)
                                    $data['picture'] = $image;
                                $ret_obj = $facebook->api('/'.$fb_account->page_id.'/feed', 'POST', $data);
                                return true;
                            } catch(FacebookApiException $e) {
                                return false;
                            }
                        }
                    }
                    return false;
                } else {
                    try {
                        $data = array(
                            'message' => $message,
                        );
                        if ($image)
                            $data['picture'] = $image;
                        $ret_obj = $facebook->api('/'.$fb_account->page_id.'/feed', 'POST', $data);
                        return true;
                    } catch(FacebookApiException $e) {
                        return false;
                    }
                }
            }
            return false;
        }

        public function admin_social_posts(){
            global $wpdb;
            global $post;

            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == "post"){
                if(isset($_POST['tw_publish']) && $_POST['tw_publish'] == "on"){
                    if (trim($_POST['tw_post']) != ""){
                        $social_post = array(
                            "title"     => !empty($_POST['post_title']) ? $_POST['post_title'] : (strlen($_POST['tw_post']) > 100 ? substr($_POST['tw_post'], 0 ,100)."..." : $_POST['tw_post']),
                            "post"      => $_POST['tw_post'],
                            "origin_id" => $_POST['origin_id'],
                            "network"   => "tw",
                            "network_account_id" => $_POST['tw_account_id'],
                            "state" => $_POST['tw_publish_mode'],
                            "scheduled_time" => "$_POST[tw_aa]-$_POST[tw_mm]-$_POST[tw_jj] $_POST[tw_hh]:$_POST[tw_mn]:00",
                        );

                        if($_POST['tw_publish_mode'] == "published"){
                            $tw_account = $wpdb->get_row("select * from ".$this->tbl_tw_accounts." where id = ".$_POST['tw_account_id']);
                            $social_post["status"] = $this->twitter_publish($tw_account, $_POST['tw_post']);

                        }
                        $wpdb->insert($this->tbl_social_posts, $social_post);
                    }
                }
                if(isset($_POST['ln_publish']) && $_POST['ln_publish'] == "on"){
                    if (trim($_POST['ln_post']) != ""){
                        $social_post = array(
                            "title"     => !empty($_POST['post_title']) ? $_POST['post_title'] : (strlen($_POST['ln_post']) > 100 ? substr($_POST['ln_post'], 0 ,100)."..." : $_POST['ln_post']),
                            "post"      => $_POST['ln_post'],
                            "origin_id" => $_POST['origin_id'],
                            "network"   => "ln",
                            "network_account_id" => $_POST['ln_account_id'],
                            "state" => $_POST['ln_publish_mode'],
                            "scheduled_time" => "$_POST[ln_aa]-$_POST[ln_mm]-$_POST[ln_jj] $_POST[ln_hh]:$_POST[ln_mn]:00",
                        );

                        if($_POST['ln_publish_mode'] == "published"){
                            $ln_account = $wpdb->get_row("select * from ".$this->tbl_ln_accounts." where id = ".$_POST['ln_account_id']);
                            $social_post["status"] = $this->linkedin_publish($ln_account, $_POST['ln_post']);
                        }
                        $wpdb->insert($this->tbl_social_posts, $social_post);
                    }
                }
            } else if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == "share"){
                if($_GET['network'] == "tw"){
                    if (trim($_POST['tw_post']) != ""){
                        $social_post = array(
                            "title"     => $_POST['post_title'],
                            "post"      => $_POST['tw_post'],
                            "origin_id" => $_POST['origin_id'],
                            "network"   => "tw",
                            "network_account_id" => $_POST['tw_account_id'],
                            "state" => "published",
                        );

                        $tw_account = $wpdb->get_row("select * from ".$this->tbl_tw_accounts." where id = ".$_POST['tw_account_id']);
                        $social_post["status"] = $this->twitter_publish($tw_account, $_POST['tw_post']);
                        $wpdb->update($this->tbl_social_posts, $social_post, array('id' => $_POST['post_id']));
                    }
                }
                if($_GET['network'] == "ln"){
                    if (trim($_POST['ln_post']) != ""){
                        $social_post = array(
                            "title"     => $_POST['post_title'],
                            "post"      => $_POST['ln_post'],
                            "origin_id" => $_POST['origin_id'],
                            "network"   => "ln",
                            "network_account_id" => $_POST['ln_account_id'],
                            "state" => "published",
                        );

                        $ln_account = $wpdb->get_row("select * from ".$this->tbl_ln_accounts." where id = ".$_POST['ln_account_id']);
                        $social_post["status"] = $this->linkedin_publish($ln_account, $_POST['ln_post']);
                        $wpdb->update($this->tbl_social_posts, $social_post, array('id' => $_POST['post_id']));
                    }
                }
            } else if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_action']) && $_POST['bulk_action'] != -1){
                if ($_POST['bulk_action'] == 'trash'){
                    if (isset($_POST['post']) && is_array($_POST['post'])){
                        foreach($_POST['post'] as $value){
                            $wpdb->query(
                                $wpdb->prepare(
                                    "delete from ".$this->tbl_social_posts."
                                    where id=".$value
                                )
                            );
                        }
                    }
                } else if($_POST['bulk_action']=='share'){
                    if (isset($_POST['post']) && is_array($_POST['post'])){
                        require_once 'twitteroauth.php';
                        foreach($_POST['post'] as $value){
                            $social_post = $wpdb->get_row("select * from ".$this->tbl_social_posts." where id = ".$value);
                            $tw_account = $wpdb->get_row("select * from ".$this->tbl_tw_accounts." where id = ".$social_post->network_account_id);

                            define("CONSUMER_KEY", $tw_account->consumer_key);
                            define("CONSUMER_SECRET", $tw_account->consumer_secret);
                            define("OAUTH_TOKEN", $tw_account->access_token);
                            define("OAUTH_SECRET", $tw_account->access_token_secret);

                            $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, OAUTH_TOKEN, OAUTH_SECRET);

                            $content = $connection->get('account/verify_credentials');

                            $connection->post('statuses/update', array('status' => $social_post->post));
                            $wpdb->update($this->tbl_social_posts, array('state' => "published"), array('id' => $value));
                        }
                    }
                }
            }
            if (isset($_GET['action']) && $_GET['action'] == "share"){
                include_once('social_posts_share.php');

            } else {
                include_once('social_posts.php');
            }
        }

        public function admin_plugin_info(){
            include_once('plugin_info.php');
        }

        public function admin_settings(){
            global $wpdb;

            if($_SERVER['REQUEST_METHOD'] == 'POST'){
//                var_dump($_POST);
                if (isset($_POST['action']) && $_POST['action']=='update_tw'){
                    $wpdb->update($this->tbl_3x_settings, array('tw_ck' => $_POST['ck'], 'tw_cs' => $_POST['cs']), array('id' => 1));
                } elseif (isset($_POST['action']) && $_POST['action']=='update_fb'){
                    $wpdb->update($this->tbl_3x_settings, array('fb_app_id' => $_POST['ck'], 'fb_app_secret' => $_POST['cs']), array('id' => 1));
                } elseif (isset($_POST['action']) && $_POST['action']=='update_ln'){
                    $wpdb->update($this->tbl_3x_settings, array('ln_ck' => $_POST['ck'], 'ln_cs' => $_POST['cs']), array('id' => 1));
                } elseif (isset($_POST['action']) && $_POST['action']=='edit_set' && trim($_POST['n_name']) != ""){
                    $wpdb->update($this->tbl_account_sets, array(
                        'name'          => $_POST['n_name'],
                        'tw_account_id' => $_POST['tw_id'],
                        'fb_account_id' => $_POST['fb_id'],
                        'ln_account_id' => $_POST['ln_id'],
                        'tw_enabled'    => $_POST['tw_enabled'] == 'on' ? true : false,
                        'fb_enabled'    => $_POST['fb_enabled'] == 'on' ? true : false,
                        'ln_enabled'    => $_POST['ln_enabled'] == 'on' ? true : false,
                    ), array('id' => $_POST['id']));
                } elseif (isset($_POST['action']) && $_POST['action']=='add_set' && trim($_POST['n_name']) != ""){
                $wpdb->insert($this->tbl_account_sets, array(
                    'name'          => $_POST['n_name'],
                    'tw_account_id' => $_POST['tw_id'],
                    'fb_account_id' => $_POST['fb_id'],
                    'ln_account_id' => $_POST['ln_id'],
                    'tw_enabled'    => $_POST['tw_enabled'] == 'on' ? true : false,
                    'fb_enabled'    => $_POST['fb_enabled'] == 'on' ? true : false,
                    'ln_enabled'    => $_POST['ln_enabled'] == 'on' ? true : false,
                ));
            }
//                echo var_dump($_POST);
            } else if (isset($_GET['oauth_token']) && isset($_GET['oauth_verifier']) && isset($_GET['twitter_callback']) && $_GET['twitter_callback'] == true){
                require_once('twitteroauth/twitteroauth.php');

                $tokens = $wpdb->get_row("select tw_ck, tw_cs from ".$wpdb->prefix."_3xs_settings where id = 1");
                $connection = new TwitterOAuth($tokens->tw_ck, $tokens->tw_cs, get_option('tw_oauth_token'), get_option('tw_oauth_token_secret'));

                /* Request access tokens from twitter */
                $access_token = $connection->getAccessToken($_GET['oauth_verifier']);

                /* If HTTP response is 200 continue otherwise send to connect page to retry */
                if (200 == $connection->http_code) {
                    $check = $wpdb->get_results("SELECT * FROM ".$this->tbl_tw_accounts." where name='".$access_token['screen_name']."'");
                    if (count($check) == 0) {
                    $wpdb->insert($this->tbl_tw_accounts, array(
                        'name'              => $access_token['screen_name'],
                        'consumer_key'      => $tokens->tw_ck,
                        'consumer_secret'   => $tokens->tw_cs,
                        'access_token'      => $access_token['oauth_token'],
                        'access_token_secret' => $access_token['oauth_token_secret']
                    ));
                    echo "Twitter account <i>".$access_token['screen_name']."</i> added";
                    } else {
                        $wpdb->update($this->tbl_tw_accounts, array(
                            'consumer_key'      => $tokens->tw_ck,
                            'consumer_secret'   => $tokens->tw_cs,
                            'access_token'      => $access_token['oauth_token'],
                            'access_token_secret' => $access_token['oauth_token_secret']
                        ), array('name' => $access_token['screen_name']));
                        echo "This account already added, <i>".$access_token['screen_name']."</i> was updated.";
                    }
                }
            } elseif (isset($_GET['oauth_verifier']) && isset($_GET['linkedin_callback']) && $_GET['linkedin_callback'] == true){
                $settings = $wpdb->get_row("select ln_ck, ln_cs from ".$wpdb->prefix."_3xs_settings where id = 1");
                if (!class_exists("OAuth"))
                    require_once("OAuth.php");
                $domain = "https://api.linkedin.com/uas/oauth";
                $sig_method = new OAuthSignatureMethod_HMAC_SHA1();
                $test_consumer = new OAuthConsumer($settings->ln_ck, $settings->ln_cs, NULL);

                $req_token = new OAuthConsumer(get_option('ln_oauth_token'), get_option('ln_oauth_token_secret'), 1);

                $acc_req = OAuthRequest::from_consumer_and_token($test_consumer, $req_token, "POST", $domain . '/accessToken');
                $acc_req->set_parameter("oauth_verifier", $_REQUEST['oauth_verifier']); # need the verifier too!
                $acc_req->sign_request($sig_method, $test_consumer, $req_token);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_POSTFIELDS, ''); //New Line
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    $acc_req->to_header()
                ));
                curl_setopt($ch, CURLOPT_URL, $domain . "/accessToken");
                curl_setopt($ch, CURLOPT_POST, 1);
                $output = curl_exec($ch);
                if (curl_errno($ch)) {
                    echo 'Curl error 1: ' . curl_error($ch);
                }
                curl_close($ch);
                parse_str($output, $oauth);

                if (!isset($oauth['oauth_token'])){
                    throw new Exception("Failed fetching request token, response was: " . $oauth->getLastResponse());
                } else {
                    $access_token = $oauth;
                }

                $check = $wpdb->get_results("SELECT * FROM ".$this->tbl_ln_accounts." where name='LinkedIn'");
                if (count($check) == 0) {
                    $wpdb->insert($this->tbl_ln_accounts, array(
                        'name'                  => "LinkedIn",
                        'consumer_key'          => $settings->ln_ck,
                        'consumer_secret'       => $settings->ln_cs,
                        'access_token'          => $access_token['oauth_token'],
                        'access_token_secret'   => $access_token['oauth_token_secret']
                    ));
                    echo "LinkedIn account <i>"."LinkedIn"."</i> added";
                } else {
                    $wpdb->update($this->tbl_ln_accounts, array(
                        'consumer_key'          => $settings->ln_ck,
                        'consumer_secret'       => $settings->ln_cs,
                        'access_token'          => $access_token['oauth_token'],
                        'access_token_secret'   => $access_token['oauth_token_secret']
                    ), array('name' => $access_token['screen_name']));
                    echo "This account already added, <i>"."LinkedIn"."</i> was updated.";
                }
            } elseif (isset($_GET['delete_tw'])){
                $wpdb->query($wpdb->prepare("delete from ".$this->tbl_tw_accounts." where id=".$_GET['delete_tw']));
            } else if (isset($_GET['delete_fb'])){
                $wpdb->query($wpdb->prepare("delete from ".$this->tbl_fb_accounts." where id=".$_GET['delete_fb']));
            } else if (isset($_GET['delete_ln'])){
                $wpdb->query($wpdb->prepare("delete from ".$this->tbl_ln_accounts." where id=".$_GET['delete_ln']));
            } else if (isset($_GET['delete_set'])){
                $wpdb->query($wpdb->prepare("delete from ".$this->tbl_account_sets." where id=".$_GET['delete_set']));
            } else if (isset($_GET['set_default'])){
                $wpdb->query($wpdb->prepare("update ".$this->tbl_account_sets." set `default`=0"));
                $wpdb->query($wpdb->prepare("update ".$this->tbl_account_sets." set `default`=1 where id=".$_GET['set_default']));
            }


            include_once('settings.php');
        }

        function activate(){
            global $wpdb;

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            $sp_table = $this->tbl_social_posts;
            $as_table = $this->tbl_account_sets;
            $tw_table = $this->tbl_tw_accounts;
            $fb_table = $this->tbl_fb_accounts;
            $ln_table = $this->tbl_ln_accounts;
            $settings_table = $this->tbl_3x_settings;

            //check mysql version
            if (version_compare(mysql_get_server_info(), '4.1.0', '>=')) {
                if (!empty($wpdb->charset))
                    $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
                if (!empty($wpdb->collate))
                    $charset_collate .= " COLLATE $wpdb->collate";
            }

            //social_posts table structure
            $sql_table_social_posts = "
                CREATE TABLE `" . $wpdb->prefix . "_3xs_social_posts` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
                    `title` VARCHAR(255) NOT NULL ,
                    `post` TEXT NOT NULL ,
                    `network` ENUM('tw','fb','ln') NOT NULL ,
                    `network_account_id` VARCHAR(45) NOT NULL ,
                    `state` ENUM('published','scheduled') NOT NULL ,
                    `scheduled_time` DATETIME NULL DEFAULT NULL ,
                    `published_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    `origin_id` INT UNSIGNED NOT NULL,
                    `status` TINYINT(1) NOT NULL DEFAULT TRUE ,
                    `url` VARCHAR(255),
                    PRIMARY KEY (`id`)
                )" . $charset_collate . ";";

            //check if table exists
            if ($wpdb->get_var("show tables like '" . $sp_table . "'") != $sp_table) {
                dbDelta($sql_table_social_posts);
            }

            //account_sets table structure
            $sql_table_account_sets = "
                CREATE TABLE `" . $wpdb->prefix . "_3xs_account_sets` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
                    `name` VARCHAR(45) NOT NULL ,
                    `fb_account_id` INT UNSIGNED NOT NULL ,
                    `tw_account_id` INT UNSIGNED NOT NULL ,
                    `ln_account_id` INT UNSIGNED NOT NULL ,
                    `fb_enabled` TINYINT(1) NOT NULL DEFAULT TRUE ,
                    `tw_enabled` TINYINT(1) NOT NULL DEFAULT TRUE ,
                    `ln_enabled` TINYINT(1) NOT NULL DEFAULT TRUE ,
                    `default` TINYINT(1) NOT NULL DEFAULT FALSE ,
                    PRIMARY KEY (`id`)
                )" . $charset_collate . ";";

            //check if table exists
            if ($wpdb->get_var("show tables like '" . $as_table . "'") != $as_table) {
                dbDelta($sql_table_account_sets);
            }

            //twitter account table structure
            $sql_table_tw_account = "
                CREATE TABLE `" . $wpdb->prefix . "_3xs_tw_accounts` (
                    `id` INT NOT NULL AUTO_INCREMENT ,
                    `name` VARCHAR(45) NOT NULL ,
                    `consumer_key` VARCHAR(100) NOT NULL ,
                    `consumer_secret` VARCHAR(100) NOT NULL ,
                    `access_token` VARCHAR(100) NOT NULL ,
                    `access_token_secret` VARCHAR(100) NOT NULL ,
                    PRIMARY KEY (`id`)
                )" . $charset_collate . ";";

            //check if table exists
            if ($wpdb->get_var("show tables like '" . $tw_table . "'") != $tw_table) {
                dbDelta($sql_table_tw_account);
            }

            //linkedin account table structure
            $sql_table_ln_account = "
                CREATE TABLE `" . $wpdb->prefix . "_3xs_ln_accounts` (
                    `id` INT NOT NULL AUTO_INCREMENT ,
                    `name` VARCHAR(45) NOT NULL ,
                    `consumer_key` VARCHAR(100) NOT NULL ,
                    `consumer_secret` VARCHAR(100) NOT NULL ,
                    `access_token` VARCHAR(100) NOT NULL ,
                    `access_token_secret` VARCHAR(100) NOT NULL ,
                    PRIMARY KEY (`id`)
                )" . $charset_collate . ";";

            //check if table exists
            if ($wpdb->get_var("show tables like '" . $ln_table . "'") != $ln_table) {
                dbDelta($sql_table_ln_account);
            }

            //facebook account table structure
            $sql_table_fb_account = "
                CREATE TABLE `" . $wpdb->prefix . "_3xs_fb_accounts` (
                    `id` INT NOT NULL AUTO_INCREMENT ,
                    `username` VARCHAR(100) NOT NULL,
                    `name` VARCHAR(100) NOT NULL,
                    `page_id` VARCHAR(100) NOT NULL,
                    PRIMARY KEY (`id`)
                )" . $charset_collate . ";";

            //check if table exists
            if ($wpdb->get_var("show tables like '" . $fb_table . "'") != $fb_table) {
                dbDelta($sql_table_fb_account);
            }

            //facebook account table structure
            $sql_table_settings = "
                CREATE TABLE `" . $wpdb->prefix . "_3xs_settings` (
                    `id` INT NOT NULL AUTO_INCREMENT ,
                    `tw_ck` VARCHAR(100) DEFAULT NULL,
                    `tw_cs` VARCHAR(100) DEFAULT NULL,
                    `ln_ck` VARCHAR(100) DEFAULT NULL,
                    `ln_cs` VARCHAR(100) DEFAULT NULL,
                    `fb_app_id` VARCHAR(100) DEFAULT NULL ,
                    `fb_app_secret` VARCHAR(100) DEFAULT NULL ,
                    `app_key` VARCHAR(100) DEFAULT NULL ,
                    `app_email` VARCHAR(100) DEFAULT NULL ,
                    `licence_type` VARCHAR(100) DEFAULT NULL,
                    `activated` TINYINT(1) NOT NULL DEFAULT FALSE,
                    PRIMARY KEY (`id`)
                )" . $charset_collate . ";";

            //check if table exists
            if ($wpdb->get_var("show tables like '" . $settings_table . "'") != $settings_table) {
                dbDelta($sql_table_settings);
                $wpdb->insert($settings_table, array('tw_ck' => '6TPr9sv2kvnxpY8Sd0cQ'));
                $wpdb->insert($as_table, array('name' => 'Default', 'tw_enabled' => 1, 'fb_enabled' => 0, 'ln_enabled' => 1, 'default' => 1));
            } else {
                $settings = $wpdb->get_row("select * from ".$this->tbl_3x_settings." where id=1");
                if (!isset($settings->licence_type)){
                    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}_3xs_settings");
                    dbDelta($sql_table_settings);
                    $wpdb->insert($settings_table, array('tw_ck' => '6TPr9sv2kvnxpY8Sd0cQ'));
                }
            }
        }

        function deactivate(){
//            temporary
//            global $wpdb;
//            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}social_posts");
//            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}account_sets");
//            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}tw_accounts");
//            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}fb_accounts");
//            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ln_accounts");
//            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}3x_settings");

            return true;
        }

        static function uninstall(){
            global $wpdb;
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}_3xs_social_posts");
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}_3xs_account_sets");
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}_3xs_tw_accounts");
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}_3xs_fb_accounts");
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}_3xs_ln_accounts");
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}_3xs_settings");
        }
    }
}

global $txsocial;
$txsocial = new TXSocial();

