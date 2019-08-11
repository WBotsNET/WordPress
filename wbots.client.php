<?php

####################################################################################################
################################## WBots Wordpress Client ##########################################
####################################################################################################
#  /////////  ///////////  /////////--------------                                                   
#  yyyyyyyyy  yyyyyyyyyyy  yyyyyyyyy++++++++++++++++                           -++                   
#  ::syyyy::  :/yyyyyyy::  :yyyyy:::...++++.....+++++                        -++++                   
#     yyyy+    yyyyyyyy.   .yyyy-      ++++     -++++       `::::::::``    ::+++++::    `::::::::::: 
#     -yyyy   .yyyysyyyy   yyyys       ++++```::++++/     `:+++++++++++:   +++++++++   :++++++++++++ 
#      yyyyo  yyyy- yyyy. .yyyy        +++++++++++++`    :++++::..::++++:  ..+++++..   ++++....:++++ 
#      .yyyy-oyyyo  oyyyy yyyy.        ++++:::::+++++.   ++++:      `++++:   +++++     ++++:::::---` 
#       +yyyyyyyy    yyyysyyyy         ++++     `:++++   ++++        +++++   +++++     `:++++++++++/ 
#        yyyyyyy+    .yyyyyyy.         ++++      /++++   ++++/.     /++++`   +++++     //++``:::++++ 
#        +yyyyyy      yyyyyy+       ...++++....//++++-   -+++++/..//++++-    +++++..   ++++/.../++++ 
#         yyyyy`      `yyyyy        +++++++++++++++-       -++++++++++-       ++++++   ++++++++++++- 
#          ////        ////         ------------              ------           -----   ----------    
####################################################################################################
####################################################################################################


#Bağlantı güvenliği için otomatik oluşturulan anahtarlar.
$clientKey = "WBots Client Keyi";
$wbotsKey  = "WBots Master Keyi";

#WBots'un gönderdiği içerikler hangi kullanıcı id'sine aktarılacak?
$WPuserID  = 1;

# Wordpress Klasörü farklı bir dizindeyse "/dizin/" şeklinde değiştirin.
$dizin     = "/";

# Zaman Dilimi
$time      = "+3"; //"+3" İstanbul İçin


####################################################################################################
########################## WBots Wordpress Client Ayarlar Sonu #####################################
####################################################################################################

$directory = __DIR__ .$dizin;
$bot = new wbotsApi($clientKey,$wbotsKey,$directory);


if(isset($_REQUEST["ckey"])) {
    if($_REQUEST["ckey"]==$clientKey)       $bot->logOn(1);
    
    
    if(isset($_REQUEST["validation"])) {
        $step = (int) $_REQUEST["validation"];
        $bot->validation($step);
    } elseif(isset($_REQUEST["sync"])) {
        $operation = (int) $_REQUEST["sync"];
        $bot->sync($operation);
    } elseif(isset($_REQUEST["clientVersion"])) {
        $bot->clientVersion();
    } elseif(isset($_REQUEST["WPSignal"])){
        $type = (int) $_REQUEST["WPSignal"];
        
        $bot->wbotsGetContents($type);
    }
    
    
    
    
    
} else {
    $bot->errorMSG(0);
}

class wbotsApi {
    private     $clientVersion  = "0.61";
    private     $wbotsURL1      = "https://wbots.net/validation";
    private     $wbotsURL2      = "https://wbots.net/sync";
    private     $clientKey;
    private     $wbotsKey;
    private     $directory;
    public      $login          =0;
    
    
    function __construct($clientKey, $wbotsKey,$directory) {
        if(strlen($clientKey)!=64 or strlen($wbotsKey)!=64) $this->errorMSG(0);
        $this->clientKey    = $clientKey;
        $this->wbotsKey     = $wbotsKey;
        
        if(substr($directory, -1)!="/") $this->errorMSG(1);
        $this->directory    = $directory;
    }
    
    private function logOnCtrl(){
        if($this->login!=1) $this->errorMSG(0);
    }
    
    public function logOn($x) {
        if($x==1){
            include $this->directory."wp-load.php";
            $this->login = 1;
        }
    }

    public function clientVersion () {
        if($this->login!=1) $this->errorMSG(0);
        $this->sendJSON(array( "clientVersion"=>$this->clientVersion));
    }
    
    public function validation($step) {
        if($this->login!=1) $this->errorMSG(0);
        switch($step) {
            case 1:
                $this->sendJSON(array("validation"=>1));
                break;
            case 2:
                if (function_exists(get_bloginfo)) {
                    $this->sendJSON(array(  "validation"=>2,
                                            "systemVer" =>get_bloginfo("version"),
                                            "clientVer" =>$this->clientVersion));
                } else {
                    $this->sendJSON(array(  "validation"=>2,
                                            "systemVer" =>0,
                                            "clientVer" =>$this->clientVersion));
                }
                break;
                
        }
        
    }
    public function sync($operation) {
        if($this->login!=1) $this->errorMSG(0);
        switch($operation) {
            case 1:
                if (!function_exists(get_categories)) $this->errorMSG(2);
                $categories = get_categories(array("hide_empty" => 0, "type" => "post", "orderby"   => "name", "order"     => "ASC" ));
                $tmp = array();
                foreach($categories as $cat) {
                  if(strlen($cat->cat_name)>0){
                    if($cat->parent == 0){
                        $tmp[] = array ("cat_ID"    =>$cat->cat_ID,
                                    "cat_name"  =>$cat->cat_name);
                    } else {
                        if (!function_exists(get_category_parents)) $this->errorMSG(2);
                        $tmp[] = array ("cat_ID"    =>$cat->cat_ID,
                                    "cat_name"  => trim(get_category_parents( $cat->cat_ID, false, " &raquo; " )," &raquo; "));
                    }
                    
                  }
                }
                $categories = $tmp;
                $this->sendJSON(array(  "sync"=>1,
                                        "values" =>$categories),2);
                
                break;
            case 2:
                if (!function_exists(wp_get_recent_posts))  $this->errorMSG(2);
                if (!function_exists(get_post_meta))        $this->errorMSG(2);
                $last_post = wp_get_recent_posts(array("numberposts" => 1));
                if(sizeof($last_post)>0) {
                    $meta       = array("id"    => $last_post[0]["ID"],
                                        "title" => $last_post[0]["post_title"],
                                        "meta"  => get_post_meta($last_post[0]["ID"]));
                    
                    $return = $this->sendJSON(array(  "sync"=>2,
                                            "values" =>$meta),2);
                    echo "<pre>"; 
                    print_r($return);
                    echo "</pre>";
                }
                break;
        }
    }
    
    public function wbotsGetContents($type){
        $json = $this->sendJSON(array( "transfer"=>$type),2);
        $array= json_decode($json,true);
        $completed = array();
        if($array["code"]==1){
            $posts = $array["data"];
            foreach($posts as $post){
                $info = $this->post_insert($post);
                if($info!==false){
                    $completed[$post["id"]] = $info;
                }
            }
        }
        if(count($completed)>0){
            $this->sendJSON(array( "transferCompleted"=>$completed),2);
        }
    }
    
    private function post_insert($post) {
        global $wpdb;
        global $WPuserID;
        global $time;
        $title          = $post["title"];
        $image          = $post["photo"];
        $excerpt        = $post["excerpt"];
        $content        = $post["content"];
        $categories     = $post["category"];
        $postType       = "post";  
        $postStatus     = $post["post_status"];
        $sfields        = $post["sfields"];
        
        $cc1 = strip_tags($content);
        $findPosts = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_title ='%s' and post_type='post' and post_status!='trash' limit 1" ,$title) );
        foreach($findPosts as $findPost) { 
            $cc2 = strip_tags($findPost->post_content);     
            if($cc1==$cc2){
               return get_permalink($findPost->ID);
            }
        }

        if($post["in_upload"]==1){
            $in_images = $post["in_images"];
            for($i=0;$i<count($in_images);$i++){
                $attach_img = $this->Generate_Featured_Image( $in_images[$i], 0, $title." ".$i );
                $content    = str_replace($in_images[$i],$attach_img,$content);        
            }
        }
        
        
        kses_remove_filters();
        if($time!="+3"){
            $timeStamp = date("Y-m-d H:i:s", strtotime($time." hours"));
        } else {
            $timeStamp = current_time('mysql');
        }
        
        $new_post = array(
         "post_title"   => $title,
         "post_content" => $content,
         "post_status"  => $postStatus,
         "post_date"    => $timeStamp,
         "post_author"  => $WPuserID,
         "post_type"    => $postType,
         "post_excerpt" => $excerpt,
         "post_category"=> $categories,
         "meta_input"   => $sfields
         );
         
         $post_id = wp_insert_post($new_post);
         
         if($post_id){
            $this->Generate_Featured_Image( $image, $post_id, $title );
            wp_set_post_tags( $post_id, $post["tags"], true );
            return get_permalink($post_id);
         } else{
            return false;
         }
       
    }
    private function Generate_Featured_Image($image_url, $post_id, $title){
        $upload_dir = wp_upload_dir();
        $ch = curl_init();  
        curl_setopt($ch, CURLOPT_URL, $image_url);  
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
        curl_setopt($ch, CURLOPT_REFERER, $image_url);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);  
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);  
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);  
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  
        curl_setopt($ch, CURLOPT_ENCODING, "");  
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);  
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);  # required for https urls  
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        $image_data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if($code!=200) return false;
        
        $filename   = basename($image_url);
        $extension  = pathinfo($filename);
        $extension  = $extension["extension"];
        if($extension==""){
            $extension = "jpg";
        } else {
            $extension = explode("?",$extension)[0];
        }
        $filename   = $this->generateSeoURL($title,32)."-".$this->RandomString(7).".".$extension;
        
        if(wp_mkdir_p($upload_dir["path"])) {
            $file = $upload_dir["path"] . "/" . $filename;
            $url  = $upload_dir["url"]."/".$filename;
        } else {
            $file = $upload_dir["basedir"] . "/" . $filename;
            $url  = $upload_dir["baseurl"]."/".$filename;
        }
        
        file_put_contents($file, $image_data);
    
        $wp_filetype = wp_check_filetype($filename, null );
        $attachment = array(
            "post_mime_type" => $wp_filetype["type"],
            "post_title" => sanitize_file_name($filename),
            "post_content" => "",
            "post_status" => "inherit"
        );
        if($post_id!=0){
            $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
            require_once(ABSPATH . "wp-admin/includes/image.php");
            $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
            $res1= wp_update_attachment_metadata( $attach_id, $attach_data );
            $res2= set_post_thumbnail( $post_id, $attach_id );
        }
        
        return $url;
    }

    private function generateSeoURL($string, $wordLimit = 0){
        $separator = "-";
        if($wordLimit != 0){
            $wordArr = explode(" ", $string);
            $string = implode(" ", array_slice($wordArr, 0, $wordLimit));
        }
        $quoteSeparator = preg_quote($separator, "#");
        $trans = array(
            "&.+?;"                    => "",
            "[^\w\d _-]"            => "",
            "\s+"                    => $separator,
            "(".$quoteSeparator.")+"=> $separator
        );
        $string = strip_tags($string);
        foreach ($trans as $key => $val){
            $string = preg_replace("#".$key."#i".("UTF8_ENABLED" ? "u" : ""), $val, $string);
        }
        $string = strtolower($string);
        $string = preg_replace("/[^A-Za-z0-9]/"," ",$string); 
        $string = preg_replace("/\s+/"," ",$string);
        $string = str_replace(" ","-",$string);
        return trim(trim($string, $separator));
    }
    private function RandomString($length = 32) {
        $randstr;
        srand((double) microtime(TRUE) * 1000000);
        //our array add all letters and numbers if you wish
        $chars = array(
            "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "p",
            "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "1", "2", "3", "4", "5",
            "6", "7", "8", "9", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", 
            "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
    
        for ($rand = 0; $rand <= $length; $rand++) {
            $random = rand(0, count($chars) - 1);
            $randstr .= $chars[$random];
        }
        return $randstr;
    }
    
    public function errorMSG($x) {
        
        switch($x){
            case 0:
                http_response_code(501);
                $msg = json_encode(array(
                                    "code" => 0,
                                    "msg"  => "Hatalı Key"
                                  ),JSON_UNESCAPED_UNICODE);
                die($msg);
                break;
            case 1:
                http_response_code(502);
                $msg = json_encode(array(
                                    "code" => 1,
                                    "msg"  => "Dizin hatalı olarak yazılmış."
                                  ),JSON_UNESCAPED_UNICODE);
                die($msg);
                break;
            case 2:
                http_response_code(503);
                $msg = json_encode(array(
                                    "code" => 2,
                                    "msg"  => "WordPress fonksiyonu çalışmıyor."
                                  ),JSON_UNESCAPED_UNICODE);
                die($msg);
                break;
        }
        
    }
    
    public function sendJSON($data,$url=1) {
        switch($url) {
            case 1: $urlx = $this->wbotsURL1; break;
            case 2: $urlx = $this->wbotsURL2; break;
        }
        $ch         = curl_init( $urlx );
        $postFields = array( "wbotsKey"=> $this->wbotsKey ,"data"=> json_encode($data,JSON_UNESCAPED_UNICODE ));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $postFields );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true);  
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}





?>
