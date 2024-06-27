<?php

/*
Plugin Name: URL payload Formbuilder
Plugin URI: https://github.com/marciofao/url-payload-formbuilder
Description: Creates a form from a given url payload then send emails
Version: 0.1.0
Author: Marcio Fão
Author URI: http://marciofao.github.io

*/
require_once('acf_fields.php');

add_action( 'init', 'upf_init');

function upf_init(){
    if(isset($_GET['quote'])){
        if(empty($_GET['quote'])) return;

        $data = json_decode(base64_decode($_GET['quote']));
        
        if(is_null($data)) wp_die('Invalid URL');

        if(upf_is_expired(upf_getCurrentGMTDate(),$data[0])) wp_die('URL expired');

        $splitAfter = 12; // Split the array after x items
        $chunkedArray = array_chunk($data, $splitAfter);
        // The first chunk will contain the first $splitAfter items
        $request_details = $chunkedArray[0];
        // The second chunk will contain the remaining items
        $form_fields = array_merge(...array_slice($chunkedArray, 1));
        $post_id = upf_create_post($request_details, $form_fields);
        upf_form_build($form_fields, $post_id, $request_details);
        
        die;
    }

    //if submit...
    if(isset($_POST['upf_rfq']) && !empty($_POST['upf_rfq'])) {
        $data = array_map('sanitize_text_field', $_POST);
        upf_store_user_request($data);
    }
    
    //if quote reply ...
    if(isset($_GET['quote_reply']) && !empty($_GET['quote_reply'])) {
        if(!isset($_GET['id']) || empty($_GET['id'])) wp_die('invalid url');
        upf_store_quote_reply();
    }

    //if quote answer ...
    if(isset($_GET['quote_answer']) && !empty($_GET['quote_answer'])) {
        if(!isset($_GET['id']) || empty($_GET['id'])) wp_die('invalid url');
        upf_store_quote_answer();
    }
}   

function upf_form_build($data, $post_id, $request_details){
    require_once('styling-header.php');
    ?>
    <div class="content">
        <img src="<?php echo plugin_dir_url( __FILE__ ) ?>/main-logo.svg" alt="Company Logo" class="mb-5 mt-5">
        
        <form method="post" action="?rfq_submit" class="top-right-box">
            <?php wp_nonce_field('upf_request_quote', 'upf_nonce'); ?>
            <h1>Request a quote</h1>
            <p class="request-details">Project: <?php echo $request_details[5] ?></p>
            <?php 
                foreach($data as $key => $value){
                    if(is_string($data[$key])){
                        ufp_build_text_input($value);
                    }elseif(is_array($data[$key])){
                        ufp_build_select_input($value);
                    }    
                }
            ?>
            <input name='upf_rfq' value="<?php echo $post_id ?>" style="display: none;" maxlength="100">
            Quantity:
            <input type="number" name="quantity" class="input-field w100 mb-2">
            Price:
            <input type="number" name="price" class="input-field w100 mb-2">
            Delivery:
            <input type="text" name="spotdeliverydate" placeholder="Spot/Date" class="input-field w100 mb-2" maxlength="100">
            Aditional Details:
            <textarea name="additional_details" class="textarea w100"></textarea>
            <input type="submit" value="Request Quote" class="submit-response-btn mt-2">
        </form>
    </div>

    <?php
}

function ufp_build_text_input($label){
    $name = strtolower(str_replace(' ', '_', $label));
    ?>
    <label for="<?php echo $name ?>"> <?php echo $label ?> </label>
    <input type="text" name="<?php echo $name ?>"  maxlength="100" class="input-field w100 mb-2"/>
    <?php
}

function ufp_build_select_input($arr){
    $name = strtolower(str_replace(' ', '_', $arr[0]));
    ?>
    <label for="<?php echo $name ?>"> <?php echo $arr[0] ?> </label>
    <select name="<?php echo $name ?>" class="input-field w100 mb-2">
        <?php array_shift($arr) ?>
        <?php foreach($arr as $value):?>
            <option value="<?php echo $value ?> "> <?php echo $value ?> </option>
        <?php endforeach ?>
    </select>
    <?php
}

// Function to generate the current GMT date in the specified format
function upf_getCurrentGMTDate($format = 'Y-m-d H:i:s') {
    return gmdate($format);
}

// Function to check if the difference between two GMT dates is 3 hours
function upf_is_expired($date1, $date2) {
    // echo $date1 .'';
    // echo $date2 .'';
    $hours_limit = 3;
    $diff = abs(strtotime($date1) - strtotime($date2));
    $hours = floor($diff / (60 * 60));
    return $hours >= $hours_limit;
}

function upf_create_post($details,$fields){
        
    global $wpdb;

    $rfq_id = $details[1];
    $project_identifier = $details[5];

    $post_id = $wpdb->get_var("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'rfq_id' AND meta_value = '".$rfq_id."' ORDER BY meta_id DESC LIMIT 1");
    if($post_id) return $post_id;

    $postarr = array(
        'post_title' => $rfq_id.' - '.$project_identifier,
        'post_content' => '',
        'post_status' => 'publish',
        'post_type' => 'quote-request'
    );

    if(!$post_id = wp_insert_post($postarr)) die('error on saving information');

    update_post_meta($post_id, 'rfq_id', $rfq_id);
    update_post_meta($post_id, 'project_identifier', $project_identifier);
    update_post_meta($post_id, 'requester_name', $details[2]);
    update_post_meta($post_id, 'requester_email', $details[3]);
    update_post_meta($post_id, 'request_company_name', $details[4]);
    update_post_meta($post_id, '1_broker_name', $details[6]);
    update_post_meta($post_id, '1_broker_email', $details[7]);
    update_post_meta($post_id, '2_broker_name', $details[8]);
    update_post_meta($post_id, '2_broker_email', $details[9]);
    update_post_meta($post_id, '3_broker_name', $details[10]);
    update_post_meta($post_id, '3_broker_email', $details[11]);
    update_post_meta($post_id, 'fields', json_encode($fields, JSON_PRETTY_PRINT));

    return $post_id;
}

function upf_store_user_request($data){
    //var_dump($data);die;
    //get position of rfq_id field
    //var_dump(array_keys($data));die;
    $rfq_id_position = array_search('upf_rfq', array_keys($data));
    $custom_fields = array_slice($data, 2, $rfq_id_position, true);
    $fixed_fields = array_slice($data, $rfq_id_position, null, true);
    
    $post_id = intval($fixed_fields['upf_rfq']);
    //var_dump($post_id);
    update_post_meta( $post_id, 'user_filled_fields', json_encode($custom_fields, JSON_PRETTY_PRINT));
    foreach($fixed_fields as $key => $value){
        update_post_meta( $post_id, $key, $value);
    }

    foreach($custom_fields as $key => $value){
        if(strtolower($key) == 'vintage' || strtolower($key) == 'vintages') 
        update_post_meta( $post_id, 'vintages', $value);
    }
    
    upf_send_messages($post_id);
    
}

function upf_send_messages($post_id){
    $broker_emails[] = get_post_meta($post_id, '1_broker_email', true);
    $broker_emails[] = get_post_meta($post_id, '2_broker_email', true);
    $broker_emails[] = get_post_meta($post_id, '3_broker_email', true);

    require_once('styling-header.php');
?>

<div class="content">
    <img src="<?php echo plugin_dir_url( __FILE__ ) ?>/main-logo.svg" alt="Company Logo" class="mb-5 mt-5">
    <div>
        <div class="row p-5 mt-5 top-right-box">
            <div class="col-1">
                <img src="<?php echo plugin_dir_url( __FILE__ ) ?>v-check-green.svg" alt="green-confirm" class="w100">
            </div>
            <div class="col-11 d-flex align-items-center">
                <h1 class="lh100 m-0">Quote Request sent successfully!</h1>
            </div>

        </div>
    </div>  
</div>

Messages sent to brokers:
<?php

    
    $i=1;
    foreach($broker_emails as $broker_email){

        if($broker_email){
            $url = site_url().'?quote_reply='.$i.'&id='. $post_id;
            $message = "New quote Requested.<br><br>";;
            $message.= "Click here to view: <a href='$url'>$url</a>";
            $headers[] = 'Content-type: text/html; charset=utf-8';
            wp_mail( $broker_email, "New quote Requested", $message, $headers );
        }
        $i++;
        echo '<br>'.$i.':<br>';
        echo $message.'<br>';

    }
    die;
}

function upf_store_quote_reply(){
    require_once('store-reply.php');
die;
}

function upf_store_quote_answer(){
    $post_id = intval($_GET['id']);
    $reply_id = intval($_GET['quote_answer']);
    $quote = $_POST['broker_quote'];
    update_post_meta( $post_id, $reply_id.'_broker_comments', $quote);
    upf_notify_team($post_id);
    die;
}

function upf_notify_team($post_id){
    ?>
    <?php require_once('styling-header.php') ?>
    <style>
        body,html{
            text-align: center;
        }
        h3{
            margin-top: 20px;x
        }
    </style>
    <h3>Email to be sent to team:</h3>
    <p>New quote received!</p>
    <p><a href="<?php echo site_url().'/wp-admin/post.php?post='.$post_id.'&action=edit' ?>" >Click here to view</a></p>
    
    <?php
}

