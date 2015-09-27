<?php

require_once "settings.php";
require_once "amocrm.php";

// md5($_REQUEST['id'].$_REQUEST['email'].$_REQUEST['phone'].$eautopay_secret_key)

if($_REQUEST['hash']) {
    
    $ea_id              = $_REQUEST['id'];
    $ea_first_name      = $_REQUEST['first_name'];
    $ea_last_name       = $_REQUEST['last_name'];
    $ea_middle_name     = $_REQUEST['middle_name'];
    $ea_email           = $_REQUEST['email'];
    $ea_phone           = $_REQUEST['phone'];
    $ea_phone2          = $_REQUEST['phone2'];
    $ea_payed           = $_REQUEST['payed'];
    $ea_product_id      = $_REQUEST['product_id'];
    $ea_product_name    = $_REQUEST['product_name'];
    $ea_product_price   = $_REQUEST['product_price'];
    $ea_comments_client = $_REQUEST['comments_client'];
    $ea_organization    = $_REQUEST['additional_field_1'];
    
    $name           = $ea_first_name." ".$ea_last_name." ".$ea_middle_name;
    $email          = $ea_email;
    $phone          = $ea_phone;
    $phone2         = $ea_phone2;
    $organization   = $ea_organization;
    $lead_title     = "Новый лид по: ".$ea_product_name;
    $lead_price     = $ea_product_price;
    $lead_comment   = $ea_comments_client;
    $utm_source     = isset($_REQUEST['utm'])?$_REQUEST['utm']['utm_source']:"";
    $utm_medium     = isset($_REQUEST['utm'])?$_REQUEST['utm']['utm_medium']:"";
    $utm_campaign   = "";
    $utm_content    = "";
    $utm_term       = "";
    
    send_to_amocrm($name, $email, $phone, $phone2, $organization, $lead_title, $lead_comment, $lead_price, $utm_source, $utm_medium, $utm_campaign, $utm_content, $utm_term, $ga_utm);
    
}

$req_dump = print_r($_REQUEST, TRUE);
$fp = fopen('request.txt', 'a');
fwrite($fp, $req_dump);
fclose($fp);


?>
