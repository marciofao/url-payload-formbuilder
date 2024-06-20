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

        if(upf_is_expired(upf_getCurrentGMTDate(),$data[0])) die('URL expired');

        $splitAfter = 12; // Split the array after x items
        $chunkedArray = array_chunk($data, $splitAfter);
        // The first chunk will contain the first $splitAfter items
        $request_details = $chunkedArray[0];
        // The second chunk will contain the remaining items
        $form_fields = array_merge(...array_slice($chunkedArray, 1));
        $post_id = upf_create_post($request_details, $form_fields);
        upf_form_build($form_fields, $post_id);
        
        die;
    }
    //if quote reply ...
    //if submit...
    
}   

function upf_form_build($data, $post_id){
    ?>
    <style>
        body,html{
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            background: #f5f5f5;
            font-size: 15px;
            font-family: Arial, Helvetica, sans-serif;
        }
        label{
            display: block;
            margin-bottom: 5px;
        }
        form{
            margin: 0 auto;
            max-width: 400px;
        }
        input,textarea,select{
            width: 100%;
        }
        textarea{
            height: 100px;
        }
    </style>
        <form method="post" action="/?rfq_submit=<?php echo $post_id ?>" >
            <?php 
                foreach($data as $key => $value){
                    if(is_string($data[$key])){
                        ufp_build_text_input($value);
                    }elseif(is_array($data[$key])){
                        ufp_build_select_input($value);
                    }    
                }
            ?>
        Quantity:
        <input type="number" name="quantity">
        Price:
        <input type="number" name="price">
        Delivery:
        <input type="text" name="delivery" placeholder="Spot/Date">
        Aditional Details:
        <textarea name="aditional_details"></textarea>
        <input type="submit" value="Request Quote">
        </form>
        <?php
    
}

function ufp_build_text_input($label){
    $name = strtolower(str_replace(' ', '_', $label));
    ?>
    <label for="<?php echo $name ?>"> <?php echo $label ?> </label>
    <input type="text" name="<?php echo $name ?>" />
    <?php
}

function ufp_build_select_input($arr){
    $name = strtolower(str_replace(' ', '_', $arr[0]));
    ?>
    <label for="<?php echo $name ?>"> <?php echo $arr[0] ?> </label>
    <select name=""<?php echo $name ?>"">
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

    $post_id = $wpdb->get_var("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'rfq_id' AND meta_value = '".$rfq_id."'");
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
