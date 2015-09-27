<?php

function send_to_amocrm($name = "", $email = "", $phone = "", $phone2 = "", $organization = "", $lead_title = "", $lead_comment = "",$lead_price = "", $utm_source = "", $utm_medium = "", $utm_campaign = "", $utm_content = "", $utm_term = "", $ga_utm = "") {
    global $amocrm_subdomain, $amocrm_login, $amocrm_api_hash;
    
    $errors=array(//Массив ошибок
      301=>'Moved permanently',
      400=>'Bad request',
      401=>'Unauthorized',
      403=>'Forbidden',
      404=>'Not found',
      500=>'Internal server error',
      502=>'Bad gateway',
      503=>'Service unavailable'
    );
    $flogs=fopen("logs.txt","a");//Файл логов
    $user=array(
        'USER_LOGIN' => $amocrm_login,
        'USER_HASH' => $amocrm_api_hash
    );

    #Формируем ссылки для запросов
    $subdomain = $amocrm_subdomain;
    $auth_link = 'https://'.$subdomain.'.amocrm.ru/private/api/auth.php?type=json';
    $account_link = 'https://'.$subdomain.'.amocrm.ru/private/api/v2/json/accounts/current';
    $contacts_link = 'https://'.$subdomain.'.amocrm.ru/private/api/v2/json/contacts/set';
    $leads_link = 'https://'.$subdomain.'.amocrm.ru/private/api/v2/json/leads/set';
    $company_link = 'https://'.$subdomain.'.amocrm.ru/private/api/v2/json/company/set';

    /*  Авторизация  */
    $curl=curl_init();
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
    curl_setopt($curl,CURLOPT_URL,$auth_link);
    curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
    curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($user));
    curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
    curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_COOKIEFILE,dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR,dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);

    $out = curl_exec($curl);
    $code = curl_getinfo($curl,CURLINFO_HTTP_CODE);
    curl_close($curl);

    //var_dump($out);
    //var_dump($code);

    if($code!=200 && $code!=204){
        if(isset($errors[$code])){
            fwrite($flogs,date("d-m-Y H:i:s")." Ошибка авторизации в amoCRM (".$code." ".$errors[$code].")\n");
        }else{
            fwrite($flogs,date("d-m-Y H:i:s")." Ошибка авторизации в amoCRM (".$code.")\n");
        }
    }elseif(preg_match('|"auth":true|',$out)){
        
        /*  Аккаунт  */
        $curl=curl_init();
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
        curl_setopt($curl,CURLOPT_URL,$account_link);
        curl_setopt($curl,CURLOPT_HEADER,false);
        curl_setopt($curl,CURLOPT_COOKIEFILE,dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
        curl_setopt($curl,CURLOPT_COOKIEJAR,dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
         
        $response = curl_exec($curl);
        $code = curl_getinfo($curl,CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        $response = json_decode($response,1);
        $response = $response['response'];
        $custom_fields = $response['account']['custom_fields']['contacts'];
        $custom_fields_leads = $response['account']['custom_fields']['leads'];
        $leads_statuses = $response['account']['leads_statuses'];
        $first_contact_status_id = $leads_statuses['0']['id'];
        $user_id = $response['account']['users']['0']['id'];

        if($code!=200 && $code!=204){
            if(isset($errors[$code]))
                fwrite($flogs,date("d-m-Y H:i:s")." Не удалось получить данные об аккаунте в amoCRM (".$code." ".$errors[$code].")\n");
            else 
                fwrite($flogs,date("d-m-Y H:i:s")." Не удалось получить данные об аккаунте amoCRM (".$code.")\n");
        } else {
            
            $add['request']['leads']['add'][0]['responsible_user_id'] = $user_id;
            $add['request']['leads']['add'][0]['name'] = $lead_title;
            $add['request']['leads']['add'][0]['status_id'] = $first_contact_status_id;
            $add['request']['leads']['add'][0]['price'] = $lead_price;
            
            foreach($custom_fields_leads as $field){
                if($field['name']=='Источник трафика' && $utm_source != '')
                    $add['request']['leads']['add'][0]['custom_fields'][]=array('id'=>$field['id'],'values'=>array(array('value'=>$utm_source)));
                if($field['name']=='Тип трафика' && $utm_medium != '')
                    $add['request']['leads']['add'][0]['custom_fields'][]=array('id'=>$field['id'],'values'=>array(array('value'=>$utm_medium)));
                if($field['name']=='Название рекламной кампании' && $utm_campaign != '')
                    $add['request']['leads']['add'][0]['custom_fields'][]=array('id'=>$field['id'],'values'=>array(array('value'=>$utm_campaign)));
                if($field['name']=='Ключевое слово кампании' && $utm_term != '')
                    $add['request']['leads']['add'][0]['custom_fields'][]=array('id'=>$field['id'],'values'=>array(array('value'=>$utm_term)));
            }

            /*  Сделка  */
            $curl=curl_init();
            curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
            curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
            curl_setopt($curl,CURLOPT_URL,$leads_link);
            curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
            curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($add));
            curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
            curl_setopt($curl,CURLOPT_HEADER,false);
            curl_setopt($curl,CURLOPT_COOKIEFILE,dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
            curl_setopt($curl,CURLOPT_COOKIEJAR,dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
            curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
            curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
             
            $response = curl_exec($curl);
            $code = curl_getinfo($curl,CURLINFO_HTTP_CODE);
            curl_close($curl);

            $response = json_decode($response,1);
            $response = $response['response'];
            $lead_id = $response['leads']['add']['0']['id'];
            $leads_array[] = (int)$lead_id;
            
            //var_dump($response);
            //var_dump($code);
            unset($add);
            
            if($code!=200 && $code!=204){
            if(isset($errors[$code]))
                fwrite($flogs,date("d-m-Y H:i:s")." Не удалось добавить сделку в amoCRM (".$code." ".$errors[$code].")\n");
            else 
                fwrite($flogs,date("d-m-Y H:i:s")." Не удалось добавить сделку в amoCRM (".$code.")\n");
            }else{
                
                if($name == "") {
                    $name = "Клиент";
                }
                
                if($name!="")
                    $add['request']['contacts']['add'][0]['name'] = $name;

                $add['request']['contacts']['add'][0]['linked_leads_id'] = $leads_array; 
                
                foreach($custom_fields as $field){
                    
                    if($field['code']=='PHONE') {
                        if($phone != "") {
                            $add['request']['contacts']['add'][0]['custom_fields'][]=array('id'=>$field['id'],'values'=>array(array('value'=>$phone,'enum'=>'MOB')));
                        }
                        if($phone2 != "") {
                            $add['request']['contacts']['add'][0]['custom_fields'][]=array('id'=>$field['id'],'values'=>array(array('value'=>$phone2,'enum'=>'WORK')));
                        }
                    }
                
                    if($field['code']=='EMAIL'&&$email!='') {
                        $add['request']['contacts']['add'][0]['custom_fields'][]=array('id'=>$field['id'],'values'=>array(array('value'=>$email,'enum'=>'WORK')));
                    }
                }

                /*  Контакт  */
                $curl=curl_init();
                curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
                curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
                curl_setopt($curl,CURLOPT_URL,$contacts_link);
                curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
                curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($add));
                curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
                curl_setopt($curl,CURLOPT_HEADER,false);
                curl_setopt($curl,CURLOPT_COOKIEFILE,dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
                curl_setopt($curl,CURLOPT_COOKIEJAR,dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
                curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
                curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
                 
                $response = curl_exec($curl);
                $code = curl_getinfo($curl,CURLINFO_HTTP_CODE);
                curl_close($curl);

                $response = json_decode($response,1);
                $response = $response['response'];
                //var_dump($response);
                //var_dump($code);
                unset($add);

                if($code!=200 && $code!=204){
                    if(isset($errors[$code]))
                        fwrite($flogs,date("d-m-Y H:i:s")." Не удалось добавить контакт в amoCRM (".$code." ".$errors[$code].")\n");
                    else 
                        fwrite($flogs,date("d-m-Y H:i:s")." Не удалось добавить контакт в amoCRM (".$code.")\n");
                }else{
                    
                    if($organization != "Физическое лицо" && $organization != "физ. лицо") {
                        
                        $add['request']['contacts']['add'][0]['name'] = $organization;
                        $add['request']['contacts']['add'][0]['responsible_user_id'] = $user_id;
                        $add['request']['contacts']['add'][0]['linked_leads_id'] = $leads_array;
                        
                        /*  Компания  */
                        $curl=curl_init();
                        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
                        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
                        curl_setopt($curl,CURLOPT_URL,$company_link);
                        curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
                        curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($add));
                        curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
                        curl_setopt($curl,CURLOPT_HEADER,false);
                        curl_setopt($curl,CURLOPT_COOKIEFILE,dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
                        curl_setopt($curl,CURLOPT_COOKIEJAR,dirname(__FILE__).'/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
                        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
                        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
                         
                        $response = curl_exec($curl);
                        $code = curl_getinfo($curl,CURLINFO_HTTP_CODE);
                        curl_close($curl);

                        $response = json_decode($response,1);
                        $response = $response['response'];
                        //var_dump($response);
                        //var_dump($code);
                        unset($add);
                        
                        if($code!=200 && $code!=204){
                        if(isset($errors[$code]))
                            fwrite($flogs,date("d-m-Y H:i:s")." Не удалось добавить компанию в amoCRM (".$code." ".$errors[$code].")\n");
                        else 
                            fwrite($flogs,date("d-m-Y H:i:s")." Не удалось добавить компанию в amoCRM (".$code.")\n");
                        }

                    }
                    
                }

            }
 
        }

    }
}

?>
