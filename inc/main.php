<?php
class importcsv{
    
    public $_list_urls_name = 'ydimportcsv_listurls';
    public $_service;
    
    public function __construct() {
        //$this->requirement_for_google();
        /**FRONT **/
        //init cron, possibility
        add_filter('cron_schedules', array($this,'x_min_interval'));
        add_action( 'ydcsv_reader_cron', array( $this, 'ydcsv_cron_reader' ) );
        
        /**ADMIN**/
        if(!is_admin()){
            return;
        }
        add_action('admin_menu', array($this, 'importcsv_menu'));
        add_action ('init',array($this,'process_post'));
    }
    
    public function x_min_interval($schedules){
        // add a 'weekly' schedule to the existing set
        $schedules['every_x_minutes_csvimport'] = array(
            'interval' => 2*60,
            'display' => __('every x minutes')
        );
        return $schedules;
    }
    
    public function process_post(){
        // ADD ENW URL **/
        if(isset($_POST['importcsv_add_url'])){
            /* get urls */
            $list_urls = get_option($this->_list_urls_name,false);
            $list_decoded = array();
            if($list_urls){
                $list_decoded = json_decode($list_urls,true);
            }
            /* add the new url */
            $keyurl = time();
            $list_decoded[$keyurl] = 
                array(
                  "name"=>$_POST['importcsv_url_name'],
                  "url"=>$_POST['importcsv_add_url'],
                  "ligne"=>1
                );
            
            /*reencode */
            $tosave = json_encode($list_decoded);
            /*save*/
            update_option($this->_list_urls_name,$tosave);
            /*redirect*/
            wp_redirect('/wp-admin/admin.php?page=ydimportcsvoptions&keyurl='.$keyurl); 
       }
        
        /**DELETE URL **/
        if(isset($_GET['keyurldel']) && $_GET['keyurldel'] != ""){
            $list_urls = get_option($this->_list_urls_name,false);
            $list_decoded = array();
            if($list_urls){
                $list_decoded = json_decode($list_urls,true);
                if(isset($list_decoded[$_GET['keyurldel']])){
                    unset($list_decoded[$_GET['keyurldel']]);
                }
                    /*reencode */
                $tosave = json_encode($list_decoded);
                /*save*/
                update_option($this->_list_urls_name,$tosave);
            }
        }
        
        /** ADD line limit to reading **/
        if( isset($_GET['keyurl']) && $_GET['keyurl']!='' 
            && isset($_POST['linesave']) && $_POST['linesave']!=""){
            
            /* get urls */
            $list_urls = get_option($this->_list_urls_name,false);
            $list_decoded = array();
            if($list_urls){
                $list_decoded = json_decode($list_urls,true);
            }
            /* add the new url */
            $list_decoded[$_GET['keyurl']]['ligne'] = $_POST['linesave'];
            
            /*reencode */
            $tosave = json_encode($list_decoded);
            /*save*/
            update_option($this->_list_urls_name,$tosave);
        }
        
                /** ADD line limit to reading **/
        if( isset($_GET['keyurl']) && $_GET['keyurl']!='' 
            && isset($_POST['sheetsave']) && $_POST['sheetsave']!=""){
            
            /* get urls */
            $list_urls = get_option($this->_list_urls_name,false);
            $list_decoded = array();
            if($list_urls){
                $list_decoded = json_decode($list_urls,true);
            }
            /* add the new url */
            $list_decoded[$_GET['keyurl']]['sheetid'] = $_POST['sheetsave'];
            
            /*reencode */
            $tosave = json_encode($list_decoded);
            /*save*/
            update_option($this->_list_urls_name,$tosave);
        }
        
        if(isset($_GET['keyurl']) && $_GET['keyurl']!='' 
            && isset($_POST['cptsave']) && $_POST['cptsave']!=""){
            /* get urls */
            $list_urls = get_option($this->_list_urls_name,false);
            $list_decoded = array();
            if($list_urls){
                $list_decoded = json_decode($list_urls,true);
            }
            /* add the new url */
            $list_decoded[$_GET['keyurl']]['cpt'] = $_POST['cptsave'];
            
            /*reencode */
            $tosave = json_encode($list_decoded);
            /*save*/
            update_option($this->_list_urls_name,$tosave);
        }
        
        if(isset($_GET['keyurl']) && $_GET['keyurl']!='' 
            && isset($_POST['authorsave']) && $_POST['authorsave']!=""){
            /* get urls */
            $list_urls = get_option($this->_list_urls_name,false);
            $list_decoded = array();
            if($list_urls){
                $list_decoded = json_decode($list_urls,true);
            }
            /* add the new url */
            $list_decoded[$_GET['keyurl']]['author'] = $_POST['authorsave'];
            
            /*reencode */
            $tosave = json_encode($list_decoded);
            /*save*/
            update_option($this->_list_urls_name,$tosave);
        }
        
        
        if(isset($_GET['keyurl']) && $_GET['keyurl']!='' 
            && isset($_POST['associatecptcolumn']) && $_POST['associatecptcolumn']!=""){
            
            $alldata = $_POST;
            unset($alldata['associatecptcolumn']);
            
            /* get urls */
            $list_urls = get_option($this->_list_urls_name,false);
            $list_decoded = array();
            if($list_urls){
                $list_decoded = json_decode($list_urls,true);
            }
            $list_decoded[$_GET['keyurl']]['association'] = $alldata;
            /*reencode */
            $tosave = json_encode($list_decoded);
            /*save*/
            update_option($this->_list_urls_name,$tosave);
        }
        
        /* TEST IMPORT */
        if(isset($_GET['keyurl']) && $_GET['keyurl']!='' 
            && isset($_POST['test_import']) && $_POST['test_import']!=""){
            $this->import_data_from_csv($_GET['keyurl']);
        }
        
        /*START CRON */
        if(isset($_POST['startcrontask_ydcsv'])){
          $t = wp_schedule_event(time(), 'every_x_minutes_csvimport', 'ydcsv_reader_cron'); //every_x_minutes_csvimport //hourly//daily
          echo '<div> Cron démaré !</div>';
        }

        /*END CRON */
        if(isset($_POST['endcrontask_ydcsv'])){
          wp_clear_scheduled_hook( 'ydcsv_reader_cron' );
          echo '<div> Cron stoppé !</div>';
        }
        
        if(isset($_POST['ydcsv_cron_reader'])){
            $this->ydcsv_cron_reader();
        }
        
    }
    
    public function importcsv_menu(){
        $page_title = 'Import CSV Options';
        $menu_title = 'Import CSV Options';
        $capability = 'manage_options';
        $menu_slug = 'ydimportcsvoptions';
        $function = array($this, 'ydimportcsvoptions_main_menu_options');
        $icon_url = 'dashicons-media-code';

        add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url);
    }
    
    public function ydcsv_cron_reader(){
        /*FIND THE NEXT ONE TO READ*/
        $list_urls = get_option($this->_list_urls_name,false);
        if($list_urls){
            $list_decoded = json_decode($list_urls,true);

            $first_key = false;
            $key_to_use = false;
            $next_key = false;
            if(isset($list_decoded['last_used_key']) && $list_decoded['last_used_key']!=""){
                //find the key in the array and take next one
                foreach($list_decoded as $key_line=>$line){
                    if(!$first_key){
                        $first_key = $key_line; //keep the first key in case we arrive at the end
                    }
                    
                    //if next key true, we found previously the key, take new key and break
                    if($next_key){
                        if($key_line == "last_used_key"){
                            $key_to_use = $first_key;
                        }else{
                            $key_to_use = $key_line;
                        }
                        break;
                    }
                    
                    //last key used
                    if($list_decoded['last_used_key'] == $key_line){
                        $next_key = true; //initialise to take next key
                    }
                    
                    //we arrived at the end so we take the first key
                    if($key_line == "last_used_key"){
                        $key_to_use = $first_key;
                    }                    
                }
                
            }else{
                //take first key
                foreach($list_decoded as $key_line=>$line){
                    $key_to_use = $key_line;
                    break;
                }
            }
                   
        } 

        $list_decoded['last_used_key'] = $key_to_use;
        
        /*reencode */
        $tosave = json_encode($list_decoded);
        /*save*/
        update_option($this->_list_urls_name,$tosave);
        
        $this->import_data_from_csv($key_to_use);        
    }
    
    public function import_data_from_csv($key){
        if(!$key){
            return;
        }
        $this->requirement_for_google();
        //get data from key
        $list_urls = get_option($this->_list_urls_name,false);
        $list_decoded = json_decode($list_urls,true);

        $url = $list_decoded[$key]['url'];
        $sheet_id = "";
        if(isset($list_decoded[$key]['sheetid']) && $list_decoded[$key]['sheetid']!==""){
            $sheet_id = $list_decoded[$key]['sheetid'];
        }  
        
        $spreadsheetId = $this->get_google_service($url); 
        
        $prefix_sheet = '';
        if($sheet_id!=""){
            $prefix_sheet = $sheet_id.'!';
        }
        $range = $prefix_sheet.'A:Z';//$prefix_sheet.'A'.($line+1).':Z'.($line+1)

        $response = $this->service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();
        
        //get all the keys to check witch one exist already
        $query_postmeta = "SELECT meta_value FROM `wp_postmeta` WHERE `meta_key` = 'yd_csv_import_line_id'";
        global $wpdb;
        $result_postmeta = $wpdb->get_results($query_postmeta,ARRAY_A);
        $postmeta_list = array();
        foreach($result_postmeta as $value_postmeta){
            $postmeta_list[$value_postmeta['meta_value']] = $value_postmeta['meta_value'];
        }
        //update post meta "yd_csv_import_line_id"
        $association_list = $list_decoded[$key]['association'];
        $id_postmeta = $association_list['id_unique'];
        $startline = 1;
        if(isset($list_decoded[$key]['ligne']) && $list_decoded[$key]['ligne']!==""){
            $startline = intval($list_decoded[$key]['ligne']);
        }    
        //parse csvlines if no yd_csv_import_line_id exist, create post
        $count = 0;
        foreach($values as $line){
            
            if($count<intval($startline)){
                $count++;
                continue;
            }
            $count++;            
                    
            $unique_id_value = md5($line[$id_postmeta]);
            if(isset($postmeta_list[$unique_id_value]) && $postmeta_list[$unique_id_value]!=""){
                continue;
            }else{              
                $this->create_post($line,$list_decoded,$key,$unique_id_value);
            }
break;//ONLY FOR TEST
        }
    }
    
    public function create_post($line,$list_decoded,$key,$unique_id_value){   
                
        $association_list = $list_decoded[$key]['association'];
        $data = array();
        //create a post
        $list_acf = array();
        foreach($association_list as $key_al => $value_al){
            if(isset($value_al) && ($value_al == "notselected" || $value_al=="" ) ){
                continue;
            }
            //$association_list[$key_al] gives the key to seek
            $data[$key_al] = $line[$value_al];
            
            if(preg_match('#field_#', $key_al)){
                $key_al = str_replace('_text', '', $key_al);
                $list_acf[$key_al] = $line[$value_al];
            }
            
        }
        
        //add post status
        $data['post_status'] = $list_decoded[$key]['association']['post_status'];
        $data['post_type'] = $list_decoded[$key]['cpt'];
        $data['author'] = $list_decoded[$key]['author'];
        
        //if we want to send to the user "author" linked t the file
        //$user_author_data = get_userdata( $data['author'] );
        
        /* INSERT POST */
        $new_post_id = $this->insert_post($data);
        if($new_post_id){
            
            $category_slug = $line[$list_decoded[$key]['association']['post_category']];
            if($category_slug && $category_slug != ""){
                $the_cat_id = get_category_by_slug( $category_slug );
                if($the_cat_id && $the_cat_id!=""){
                    wp_set_post_categories( $new_post_id, $the_cat_id->term_id);
                }
            }            
            /* add the postmeta of unique csv id */
            update_post_meta( $new_post_id, 'yd_csv_import_line_id', $unique_id_value);

            //update acfs
            foreach($list_acf as $acf_key=>$acf_value){
                update_field($acf_key, $acf_value, $new_post_id);
            }

            //Send mail
            $edit_url = admin_url('post.php?post=' . $new_post_id . '&action=edit');
            //$to = 'contact@citoyens.com';
//            if(isset($user_author_data->user_email) && $user_author_data->user_email!=""){
//                $to = $user_author_data->user_email;
//            }else{
//                $to = 'silver.celyan@gmail.com';
//            }
            
            $to = "redaction@citoyens.com";
            $subject = '[94 Cron] ' . $data['post_title'];
            $body = '<h1>Nouveau post ajouté' . "</h1>\n" .
                '<p><strong>Editer&nbsp;:</strong> [<a href="' . $edit_url . '">' . $edit_url . "</a>]</p>\n" .
                '<h2>' . $data['post_title'] . "</h2>\n";
            // send email
            wp_mail($to, $subject, $body);
        }
    }
    
    public function insert_post($data){
        $new_post = array();
        $new_post['post_title'] = $data['post_title'];
        $new_post['post_author'] = $data['author'];
        $new_post['post_status'] = $data['post_status'];
        $new_post['post_type'] = $data['post_type'];
        $new_post['post_content'] = $data['post_content'];

        $post_id = wp_insert_post( $new_post, true );
        $error_html = '';
        if (is_wp_error($post_id)) {
            $errors = $post_id->get_error_messages();
            foreach ($errors as $error) {
                $error_html.=$error;
            }
            
            $to = 'silver.celyan@gmail.com';
            $subject = '[94 Cron] ERROR';
            // send email
            wp_mail($to, $subject, $error_html);
            
            return false; //$error_html;
        }
        return $post_id;
    }
   
    /**
     * Returns an authorized API client.
     * @return Google_Client the authorized client object
     */
    public function getClient() {
        
      $client = new Google_Client();
      $client->setApplicationName(APPLICATION_NAME);
      $client->setScopes(SCOPES);
      $client->setAuthConfig(CLIENT_SECRET_PATH);
      $client->setAccessType('offline');
      
      // Load previously authorized credentials from a file.
      $accessToken = false;
      $credentialsPath = $this->expandHomeDirectory(CREDENTIALS_PATH);
      if (file_exists($credentialsPath)) {
        $accessToken = json_decode(file_get_contents($credentialsPath), true);
      }
      
      if($accessToken){
        $client->setAccessToken($accessToken);
        return $client;
        
      } else {
          
        if(isset($_POST['authcodegoogle']) && $_POST['authcodegoogle']!=""){
            echo "<div>COPY THAT CODE INTO A FILE HERE : ".$credentialsPath."</div>";
            $accessToken = $client->fetchAccessTokenWithAuthCode($_POST['authcodegoogle']);            
            echo "<pre>", print_r($accessToken, 1), "</pre>";
        }
        
//        $array_data = array(
//          'access_token' => 'ya29.GlvGBCcPZ08dF13ryrL-vTxsQP0v91ErzlU2nFH-xVI0RUJ06BM1P2ssAyx7DOMipO_KwdSTST4y5E73_vfap6AYnIEzj0dkghq-Skb8H4YV5EaAObEdo_S1fUoe',
//          'token_type' => 'Bearer',
//          'expires_in' => 3600,
//          'refresh_token' => '1/CKPBHRcqGvg-T3w8HioT-EDPEJlBVPb38bVDDx3sDjA',
//          'created' => 1505394862
//          );
        
        
        /* Array(
    [access_token] => ya29.GlvGBCcPZ08dF13ryrL-vTxsQP0v91ErzlU2nFH-xVI0RUJ06BM1P2ssAyx7DOMipO_KwdSTST4y5E73_vfap6AYnIEzj0dkghq-Skb8H4YV5EaAObEdo_S1fUoe
    [token_type] => Bearer
    [expires_in] => 3600
    [refresh_token] => 1/CKPBHRcqGvg-T3w8HioT-EDPEJlBVPb38bVDDx3sDjA
    [created] => 1505394862) */
        
//$json_data = json_encode($array_data);
//var_dump($json_data);
        
        // Request authorization from the user.
        $authUrl = $client->createAuthUrl();
        printf("Open the following link in your browser:\n%s\n", $authUrl);
        echo 'Enter verification code: ';
        echo '<form action="" method="POST">';
        echo '<br><input type="text" name="authcodegoogle">';
        echo '<br><input type="submit" value="Get the token data">';
        echo '</form>';
                
        //$authCode = trim(fgets(STDIN));
        //enter here the code given by the browser, normally just once
        //$authCode = '4/QMGGre4azer0WJ7zN1iPonwx3bvBGpc1Y9jJKSpvth8';

        // Exchange authorization code for an access token.
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
        
        // Store the credentials to disk.
//        if(!file_exists(dirname($credentialsPath))) {
//          mkdir(dirname($credentialsPath), 0700, true);
//        }
        
        file_put_contents($credentialsPath, json_encode($accessToken));
        printf("Credentials saved to %s\n", $credentialsPath);
        return false;
      }
      
    }

    /**
     * Expands the home directory alias '~' to the full path.
     * @param string $path the path to expand.
     * @return string the expanded path.
     */
    public function expandHomeDirectory($path) {
        $homeDirectory = getenv('HOME');
        if (empty($homeDirectory)) {
            $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
        }
        return str_replace('~', realpath($homeDirectory), $path);
    }
    
    public function requirement_for_google(){
//        require_once __DIR__ . '/vendor/autoload.php'; 
//        echo "<pre>", print_r(__DIR__ . '/vendor/autoload.php', 1), "</pre>";
//        die();
        
//        require_once __DIR__ . '/src/Google/autoload.php'; 
//        echo "<pre>", print_r(__DIR__ . '/vendor/google/apiclient-services/src/autoload.php', 1), "</pre>";
//        die();
        define('APPLICATION_NAME', 'Other client 1');
        define('CREDENTIALS_PATH', __DIR__.'/quickstart.json');
        define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
        define('SCOPES', implode(' ', array(
            Google_Service_Sheets::SPREADSHEETS_READONLY)
          ));
    }
    
    public function ydimportcsvoptions_main_menu_options() {
        
        $this->requirement_for_google();
        
        echo '<div class="wrap">';
        echo '<h2>'.__('YD IMPORT','yd_import_csv').'</h2>';
          
        echo '<div><h3>Cron informations</h3></div>';
        $test = wp_next_scheduled('ydcsv_reader_cron');
        echo "<div>Prochain passage du cron (heure serveur) : ".date('d-m-Y H:i',$test)."</div>";

        $infos_cron = _get_cron_array();
        foreach($infos_cron as $task):

          foreach($task as $task_name=>$task_infos):
            if($task_name == 'ydcsv_reader_cron'):
                
                //echo "<pre>", print_r($task_infos, 1), "</pre>";
            
              echo "<div>Nom de la tache : ".$task_name."</div>";
              foreach($task_infos as $ti):
                echo "<div>Type de programmation : ".$ti['schedule']."</div>";
              endforeach;

            endif;
          endforeach;

        endforeach;
        
        echo '<form action="" method="POST">';
        echo '<input type="hidden" name="startcrontask_ydcsv" value="startcrontask">';
            echo '<input type="submit" value="Démarer tache cron">';
        echo '</form>';

        echo '<form action="" method="POST">';
            echo '<input type="hidden" name="endcrontask_ydcsv" value="endcrontask">';
            echo '<input type="submit" value="Stop tache cron">';
        echo '</form>';    
                           
        /** LIST URLS **/
        echo '<hr>';
        echo '<h2>'.__('URL LIST','yd_import_csv').'</h2>';
        $list_urls = get_option($this->_list_urls_name,false);
        if($list_urls){
            $list_decoded = json_decode($list_urls,true);
            echo '<ul>';
            foreach($list_decoded as $keyurl=>$urlroparse){
                if($keyurl == 'last_used_key'){
                    continue;
                }                
                echo '<li>';
                echo '<span style="width:100px;">';
                    echo '<a href="/wp-admin/admin.php?page=ydimportcsvoptions&keyurl='.$keyurl.'">';
                        echo 'EDIT -- ';
                    echo '</a>';
                echo '</span>';
                if(isset($urlroparse['name']) && $urlroparse['name']!=''){
                    echo $urlroparse['name'];
                }else{
                    echo $urlroparse['url'];
                }
                echo '<span style="width:100px;">';
                    echo '<a href="/wp-admin/admin.php?page=ydimportcsvoptions&keyurldel='.$keyurl.'">';
                        echo ' -- Delete -- ';
                    echo '</a>';
                echo '</span>';
                echo '</li>';
            }
            echo '</ul>';
        }
         
        /** form to ad url **/
        echo '<form action="" method="POST">';        
            echo "<div>";
            echo "<span>".__('Name for url','yd_import_csv')."</span>";
            echo '<input type="text" name="importcsv_url_name" value="" style="margin-left: 60px;width: 300px;">';
            echo "<br>";
            echo "<span>".__('Add an url to import','yd_import_csv')."</span>";
            echo '<input type="text" name="importcsv_add_url" value="" style="margin-left: 16px;width: 300px;">';
            echo "<br>";
            echo '<input type="submit" value="'.__('Add url','yd_import_csv').'">';
            echo '</div>';
        echo '</form>';
        
        /* edit a link */        
        if(isset($_GET['keyurl'])){
            echo '<hr>';
            echo '<h2>EDIT LINK</h2>';
            $this->read_doc($list_decoded);
        }
        
          
        echo '</div>';
    }
    
    public function get_google_service($url) {
        // Get the API client and construct the service object.
        $client = $this->getClient();
        if(!$client){
            return false;
        }
		if(!class_exists('Google_Service_Sheets')){
			return false;
		}        
        $this->service = new Google_Service_Sheets($client);
        preg_match('#d\/(.*)\/#', $url, $urlgoogleid);
        if(isset($urlgoogleid[1]) && $urlgoogleid[1] != ""){
            return $urlgoogleid[1];//'1CpUq2yxMsZmVEfxXMOAHqMncEjy94jF9dp5OKXhjKVI';
        }
        return false;
    }
        
    public function read_doc($list_decoded){
        
        $line = 1;
        if(isset($list_decoded[$_GET['keyurl']]['ligne']) && $list_decoded[$_GET['keyurl']]['ligne']!==""){
            $line  = $list_decoded[$_GET['keyurl']]['ligne'];
        }
        $url = $list_decoded[$_GET['keyurl']]['url'];
        $cptlinked = $list_decoded[$_GET['keyurl']]['cpt'];
        $association_list = $list_decoded[$_GET['keyurl']]['association'];
        $author_selected = $list_decoded[$_GET['keyurl']]['author'];
        $sheet_id = "";
        if(isset($list_decoded[$_GET['keyurl']]['sheetid']) && $list_decoded[$_GET['keyurl']]['sheetid']!==""){
            $sheet_id = $list_decoded[$_GET['keyurl']]['sheetid'];
        }        
        
        $spreadsheetId = $this->get_google_service($url);
        if(!$spreadsheetId){
            echo "<div>Can not read the Google doc. Permission missing.</div>";
            return;
        }
                
        /** LIST LINES SKIP **/
        echo '<h3>Associer les colonnes aux champs</h3>';
        
        /** SELECT THE LINE **/
        echo '<form action="" method="POST">';
        echo '<div>';
            echo '<div>'.__('Start at the line : ',',importcsv').'</div>';
            echo '<select name="linesave" style="width:150px;">';
            for($alpha=1;$alpha<=10;$alpha++){
                $selected = '';
                if($line == $alpha){
                    $selected = 'selected';
                }
                echo '<option value="'.$alpha.'" '.$selected.'>'.$alpha.'</option>';
            }
            echo '</select>';
            echo '<input type="submit" value="'.__('Change the line',',importcsv').'">';
        echo '</div>';
        echo '</form>';
        
        /** SELECT THE SHEET **/
        echo '<form action="" method="POST">';
        echo '<div>';
            echo '<div>'.__('Sheet : ',',importcsv').'</div>';
            echo '<select name="sheetsave" style="width:150px;">';
            $response = $this->service->spreadsheets->get($spreadsheetId);
            foreach($response->getSheets() as $sheet) {
                $selected = '';
                if($sheet_id == $sheet['modelData']['properties']['title']){
                    $selected = 'selected';
                }
                echo '<option value="'.$sheet['modelData']['properties']['title'].'" '.$selected.'>';//['sheetId']
                echo $sheet['modelData']['properties']['title'];
                echo '</option>';
            }
            echo '</select>';
            echo '<input type="submit" value="'.__('Change the sheet',',importcsv').'">';
        echo '</div>';
        echo '</form>';        

        /** CPT **/
        //Get all the cpt
        $args_cpt = array('public'   => true);
        $list_cpt = get_post_types($args_cpt);
        echo '<form action="" method="POST">';
        echo '<div>';
            echo '<div>'.__('Select CPT : ',',importcsv').'</div>';
            echo '<select name="cptsave" style="width:150px;">';
            echo '<option value="">'.__('Select a CPT ',',importcsv').'</option>';
            foreach($list_cpt as $cpt_code=>$cpt_name){
                $selected = '';
                if($cpt_code == $cptlinked){
                    $selected = 'selected';
                }
                echo '<option value="'.$cpt_code.'" '.$selected.'>'.$cpt_name.'</option>';
            }
            echo '</select>';
            echo '<input type="submit" value="'.__('Link doc to a cpt ',',importcsv').'">';
        echo '</div>';
        echo '</form>';
        
        /** SELECT A AUTHOR **/
        $args_users = array(
            'role__in'     => array('administrator','editor','author')
         ); 
        $all_users = get_users( $args_users );
        echo '<form action="" method="POST">';
        echo '<div>';
            echo '<div>'.__('Select Author : ',',importcsv').'</div>';
            echo '<select name="authorsave" style="width:150px;">';
            echo '<option value="">'.__('Select Author ',',importcsv').'</option>';
            foreach($all_users as $user){
                $selected = '';
                if($user->ID == $author_selected){
                    $selected = 'selected';
                }
                echo '<option value="'.$user->ID.'" '.$selected.'>'.$user->data->display_name.'</option>';
            }
            echo '</select>';
            echo '<input type="submit" value="'.__('Select the author ',',importcsv').'">';
        echo '</div>';
        echo '</form>';
        
        //NEXT STEP GET ALL ACF FIELDS FOR THAT CPT
        //CREATE A SELECT TO ASSOCIATE A COLUMN TO A FIELD TO SAVE 
        
 
        //VA CHERCHER LE PREMIER POST CREE QUI DOIS AVOIR été SAUVé AVEC TOUT LES ACF QU4ON CHERCHE
        if(isset($cptlinked) && $cptlinked != ""){
            $args = array(
              'posts_per_page'   => 1,
              'post_type'        => $cptlinked,
              'post_status'      => "any",
              'order'   => 'ASC',
              'orderby' =>  'ID'
              );
            $cptposts = get_posts( $args );            
            if(isset($cptposts[0]->ID) && $cptposts[0]->ID != ""){
                //will be used later to creafte select fields
                $fields_acf = get_field_objects($cptposts[0]->ID);
            }
        }
        
        /** GOOGLE API **/
        echo '<div>';
        echo '<h3>Titles</h3>';
        if($spreadsheetId){
            $prefix_sheet = '';
            if($sheet_id!=""){
                $prefix_sheet = $sheet_id.'!';
            }
            $range = $prefix_sheet.'A'.$line.':Z'.$line;
            
            $response = $this->service->spreadsheets_values->get($spreadsheetId, $range);
            $values = $response->getValues();
            $titles = $values[0];
            
            echo '<div>'.__('Titles of column for : ',',importcsv').$url.'</div>';
            echo '<form action="" method="POST">';
            echo '<ul>';
            
            $color_background = '#dcdcdc';
            $color_set = 1;
            
            //////////////////
            //Add basic fields
            //////////////////
            $array_list_fields_wp = array(
                'id_unique'=>'COLONE IDENTIFIANTE',
                'post_title'=>'Post titre',
                'post_content'=>'Post content',
                'post_category'=>'Post catégorie'
            );
            foreach($array_list_fields_wp as $fieldkey=>$fieldname){
                if($color_set){
                    $color_set = 0;
                    $color = 'background-color: '.$color_background.';';
                }else{
                    $color_set = 1;
                    $color = '';
                }
                echo '<li style="'.$color.'">';
                    echo '<span style="width: 250px;display: inline-block;">';
                        echo $fieldname;
                    echo '</span>';
                    
                    $default_value = "notselected";
                    if($association_list[$fieldkey] !== null){
                       $default_value = $association_list[$fieldkey]; 
                    }
                    echo $this->create_select_form($titles, $fieldkey,$default_value);
                    
                    $default_value = "";
                    if($association_list[$fieldkey.'_text'] !== null && $fieldkey!='id_unique'){
                       $default_value = $association_list[$fieldkey.'_text']; 
                    }
                    
                    echo '<input type="text" name="'.$fieldkey.'_text" value="'.$default_value.'">';
                echo '</li>';             
            }
            
            /////////////////
            //Add Post status
            /////////////////
            if($color_set){
                $color_set = 0;
                $color = 'background-color: '.$color_background.';';
            }else{
                $color_set = 1;
                $color = '';
            }
            echo '<li style="'.$color.'">';
                echo '<span style="width: 250px;display: inline-block;">';
                    echo "Post status";
                echo '</span>';
                $list_status = array(
                  'publish'=>'Publié',
                  'draft'=>'Brouillon'
                );
                $default_value = "notselected";
                if($association_list['post_status'] !== null){
                   $default_value = $association_list['post_status']; 
                }
                echo $this->create_select_form($list_status, 'post_status',$association_list['post_status']);
            echo '</li>';
            
            ////////////////
            //Add acf fields
            ////////////////
            foreach( $fields_acf as $field_slug => $field_data ){
                if($color_set){
                    $color_set = 0;
                    $color = 'background-color: '.$color_background.';';
                }else{
                    $color_set = 1;
                    $color = '';
                }
                echo '<li style="'.$color.'">';
                    echo '<span style="width: 250px;display: inline-block;">';
                        echo $field_data['label'].' ('.$field_data['name'].')';
                    echo '</span>';
                    
                    $default_value = "notselected";
                    if($association_list[$field_data['key']] !== null){
                       $default_value = $association_list[$field_data['key']]; 
                    }
                    
                    echo $this->create_select_form($titles, $field_data['key'],$default_value);
                    
                    $default_value = "";
                    if($association_list[$field_data['key'].'_text'] !== null){
                       $default_value = $association_list[$field_data['key'].'_text']; 
                    }
                    
                    echo '<input type="text" name="'.$field_data['key'].'_text" value="'.$default_value.'">';
                echo '</li>';
            }
            
            echo '</ul>';
                        
            echo '<input value="1" name="associatecptcolumn" type="hidden">';
            echo '<input type="submit" value="'.__('Associate',',importcsv').'">';
            echo '</form>';
            
        echo '<hr>';
        /** TEST IMPORT **/
        echo '<form action="" method="POST">';        
            echo "<div>";
            //echo "<span>".__('Test import','yd_import_csv')."</span>";
            echo '<input type="hidden" name="test_import" value="1">';
            echo '<input type="submit" value="'.__('Test Import','yd_import_csv').'">';
            echo '</div>';
        echo '</form>';
        
        /** test next file **/
        echo '<form action="" method="POST">';        
            echo "<div>";
            echo '<input type="hidden" name="ydcsv_cron_reader" value="1">';
            echo '<input type="submit" value="test next file">';
            echo '</div>';
        echo '</form>';        
            
        }else{
            echo '<div>'.__('The id is not found for : ',',importcsv').$url.'</div>';
        }
        echo '</div>';
    }
    
    public function create_select_form($fields,$select_name,$default_value = null){
        $html = '';
        if(isset($fields) && $fields != ""){
            $html.= '<select name="'.$select_name.'">';
            
                if($default_value === 'notselected'){
                    $select = "selected";
                }
                $html.= '<option value="notselected" '.$select.'>Select a field</option>';
                
                foreach( $fields as $field_value => $field_name ){
                    
                    $select = "";                    
                    if((string)$default_value === (string)$field_value){                        
                        $select = "selected";
                    }                    
                    $html.= '<option value="'.$field_value.'" '.$select.'>';
                    $html.= $field_name;
                    $html.= '</option>';
                }                
                
            $html.='</select>';
        }
        return $html;                    
    }    
}
