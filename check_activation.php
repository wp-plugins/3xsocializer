<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type');

require_once('../../../wp-config.php');

    $con = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD);
    if (!$con) {
        //error notify
        echo '{"stat": false, "url" : null, "message" : "Can not connect to DB"}';
        return;
    } else {
        $charset="set names utf8";
        mysql_query($charset,$con);
        mysql_select_db(DB_NAME,$con);

        $settings = "select * from ".$table_prefix."_3xs_settings where id=1";
        if($result = mysql_query($settings)){
            $row = mysql_fetch_assoc($result);
            if($row['activated']){
                $args = array(
                    'wc-api'	  => 'software-api',
                    'request'     => 'check',
                    'email'		  => $row['app_email'],
                    'licence_key' => $row['app_key'],
                    'product_id'  => $row['licence_type']
                );
                $base_url = 'http://3xsocializersoftware.com/?wc-api=software-api';
                $target_url = $base_url . '&' . http_build_query( $args );
//                echo $target_url;
                $data = file_get_contents($target_url);
                $result = json_decode($data);
//                var_dump($data);
                if (isset($result->success)){
                    if ($result->success){
                        echo '{"stat": true, "message" : "Activated!"}';
                        return;
                    }
                }
            }
        }
        echo '{"stat": false, "message" : "Unknown error"}';
    }