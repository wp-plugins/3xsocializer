<?
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type');

    require_once('../../../wp-config.php');
    $postdata = file_get_contents("php://input");
//    $postdata = "iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAOxAAADsQBlSsOGwAAA5FJREFUSIndlV1MW2UYx3/v6ek57SktlGFhTVqQMkqB7ksXdTKni4ncLTEmLiYmZDFGDXgxv2KG8UJRF+PFkmGiMy4uxplo0Dk23bLF8SE4oEMGyMDBQiR8CcwUmGOl5/XCYTrcaLcYL/zfnbzP8/u/z3PO8xz4X2krqIcrK+/fW15eDIjbYRSA/p5hrNvj1IIA6tJB6ztvlAXD6w64dTUQmxxj+4aipqaGluonW9qaAZkM/LHfvzH84JaqtJzV2y9OTXd+8sWXr8FVsXRLMXG4rj/LaaxhZhI5NYGcHGVhZDjW3dG1r/LsYHUELt8I/H56ev7GtaU1nlDoseHo7MCh5saaD0fG6oEogGUpMBBb7M4U8fXp8moO0xPI30ZRpkYtnoVL95XJWNlsNHa8G+YS2JajnlW7woH8zzWnK1zf2VX7VGPjM2eic63Aws0qNQ5s3lQx9PgjA1d2bDHnt+bL6dJMOXanQ0bcam8VBAA+gECDoTee83nNhjUFExUOxxOAnqyNfysXMup8GbuHCtwzI7kOedGjyQG3VTbZlfMHFZ790apMR2ya/Maw9z8MZYmduBWJnRA8kaYe6XOpZo9hkT/pioxYhGwXyEOKiISghNv82hJl3afyUrsqzA4F2QbylGB+PWxKJVlJFrAX/KWmqCAuhWmCEJBhEcZuq6gGtGT5K/ZuP9wVFpywS/IBBgTtlxUx7NEVX45NKSy2ED0Wk62pVPIPHYTNPwhm2kC2C2SdIvq8UFgArq8d6lfDXt38xWebe1rl7luGfwQbmvkL3qEgv1fF79vgIa690GxwfJet182tdZqtefZmwEgZvge8p2G4XSDPqkJGdMV8XuEFEtYKQBCcrX5740yx06zNUJ9LCf46KMcF33YoyE5NyB7DIvfrSj3gulH8DvD25hqDvX77eDHkJDX4VNMejeiq2WVT5M9OVZ52qeNBCK+U865NfWAoz/HHZ+nWGpLNREMwcLLP55H9q3Q56NHMXZryIsmnVBx1W9/uzLaProas5YeJc2Bxh4rucZYESfNmYXNqDMbNTiCexEC+ein2phmT0VesSvlKBmh5vsW00iKcJUGsnkxzPH7d9rypzsF813ysymsR9y5nJj7EpxbNY/ZQCL0wyAVb5sAZuJCKAcDOhfjJI1fitSz7OV3X39mevpY7PB7/rxMzk2+danl5MBY/n6rBtUqmUolTARv/wpb8T/QnJUo8z0NEiDMAAAAASUVORK5CYII=";
    $img_src = substr($postdata,22);
//    $img_src = $postdata;
    error_log("PICTURE----:".$img_src);






    $con = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD);
    if (!$con) {
        //error notify
        echo '{"stat": false, "url" : null, "message" : "Can not connect to DB"}';
        return;
    } else {
        $charset="set names utf8";
        mysql_query($charset,$con);
        mysql_select_db(DB_NAME,$con);

        $media_url = null;
        $upload_path = 'wp-content/uploads';
        $use_dates = false;

        if($result = mysql_query("select * from `".DB_NAME."`.`".$table_prefix."options` where option_name in ('uploads_use_yearmonth_folders','upload_path','upload_url_path');")){
            while($row = mysql_fetch_assoc($result)){
                if ($row['option_name'] == 'uploads_use_yearmonth_folders'){
                    if($row['option_value'] == '1'){
                        $use_dates = true;
                    }
                }
                if ($row['option_name'] == 'upload_path'){
                    if($row['option_value'] != ''){
                        $upload_path = $row['option_value'];
                    }
                }
                if ($row['option_name'] == 'upload_url_path'){
                    if($row['option_value'] != ''){
                        $media_url = $row['option_value'];
                    }
                }
            }
        }

        if ($use_dates){
            $upload_path .= "/".date('Y').'/'.date('m');
        }
//        echo $upload_path;


        $filename = "".uniqid();

        if (!is_dir(getcwd().'/../../../'.$upload_path)) {
            mkdir(getcwd().'/../../../'.$upload_path,0777,true);
        }
        $full_image_url = null;
        if ($media_url){
            $full_image_url = $media_url."/".$filename.".png";
        } else {
            $full_image_url = "http://".$_SERVER['SERVER_NAME'].substr($_SERVER['REQUEST_URI'],0,strpos($_SERVER['REQUEST_URI'],'wp-content')).$upload_path."/".$filename.".png";
        }

        $img = imagecreatefromstring(base64_decode($img_src));
        if ($img){
            if(imagepng($img, getcwd().'/../../../'.$upload_path.'/'.$filename.'.png')){
                $insert_post = "INSERT INTO  `".DB_NAME."`.`".$table_prefix."posts` (
                    `post_author` ,
                    `post_date` ,
                    `post_date_gmt` ,
                    `post_content` ,
                    `post_title` ,
                    `post_excerpt` ,
                    `post_status` ,
                    `comment_status` ,
                    `ping_status` ,
                    `post_password` ,
                    `post_name` ,
                    `to_ping` ,
                    `pinged` ,
                    `post_modified` ,
                    `post_modified_gmt` ,
                    `post_content_filtered` ,
                    `post_parent` ,
                    `guid` ,
                    `menu_order` ,
                    `post_type` ,
                    `post_mime_type` ,
                    `comment_count`
                    )
                    VALUES ('1',  now(),  now(),  '',  '".$filename."',  '',  'inherit',  'open',  'open',  '',  '3',  '',  '',  now(),  now(),  '',  '109', '".$full_image_url."',  '0',  'attachment',  'image/png',  '0'
                )";
                $inserted_post_id = 0;

                $rel_path = null;
                if($use_dates){
                    $rel_path = date('Y')."/".date('m')."/".$filename.".png";
                } else {
                    $rel_path = $filename.".png";
                }
                if (mysql_query($insert_post, $con)){
                    $inserted_post_id = mysql_insert_id();
                    $insert_postmeta = "INSERT INTO  `".DB_NAME."`.`".$table_prefix."postmeta` (
                        `post_id` ,
                        `meta_key` ,
                        `meta_value`
                        )
                        VALUES ('".$inserted_post_id."',  '_wp_attached_file',  '".$rel_path."'
                    )";
                } else {
                    echo '{"stat": false, "url" : null}';
                    return;
                }
                if (mysql_query($insert_postmeta, $con)){
                    list($width, $height) = getimagesize(getcwd().'/../../../'.$upload_path.'/'.$filename.'.png');
                    $insert_metadata = "INSERT INTO  `".DB_NAME."`.`".$table_prefix."postmeta` (
                        `post_id` ,
                        `meta_key` ,
                        `meta_value`
                        )
                        VALUES (
                            '".$inserted_post_id."',  '_wp_attachment_metadata', 'a:5:{s:5:\"width\";s:".strlen($width).":\"".$width."\";s:6:\"height\";s:".strlen($height).":\"".$height."\";s:14:\"hwstring_small\";s:23:\"height=\\'90\\' width=\\'128\\'\";s:4:\"file\";s:".strlen($rel_path).":\"".$rel_path."\";s:10:\"image_meta\";a:10:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";}}'
                        );";


                    if (mysql_query($insert_metadata, $con)){
                        echo '{"stat": true, "url" : "'.$full_image_url.'"}';

                        return;
                    } else {
                        echo '{"stat": false, "url" : null}';
                        return;
                    }
                } else {
                    echo '{"stat": false, "url" : null}';
                    return;
                }
            } else {
                echo '{"stat": false, "url" : null}';
                return;
            }
        } else {
            echo '{"stat": false, "url" : null}';
            return;
        }
    }
