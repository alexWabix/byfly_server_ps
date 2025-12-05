<?php
    function sendMessageGPT($messages){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "https://api.openai.com/v1/chat/completions");
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: Bearer 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"
        ));
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(array(
            "model"=>"gpt-3.5-turbo",
            "messages"=>$messages,
            "max_tokens" => 800,
            "temperature" => 0.1, 
            "n" => 1
        )));   
    
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    
        $response = curl_exec($curl);
        if($response){
            $response = json_decode($response, true);
            if(empty($response['choices'][0]['message']['content']) == false){
                return array(
                    "succ" => true,
                    "text" => $response['choices'][0]['message']['content'],
                    "data"=>$response
                );
            }else{
                return array(
                    "succ" => false,
                    "mess" => 'Error generation responce'.json_encode($response, JSON_UNESCAPED_UNICODE)
                );
            }
        } else {
            return array(
                "succ" => false,
                "mess" => 'Error generation responce'
            );
        }
        curl_close($curl);
    }
?>