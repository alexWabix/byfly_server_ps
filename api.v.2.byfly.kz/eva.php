<?php
function sendMessageEvaGPT($messages)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, "https://api.openai.com/v1/chat/completions");
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Authorization: Bearer sk-R27HMlE7O0pqJjzHeYEsNn5SsR20beQf9aX1azR42sT3BlbkFJQBB7GqJEH9XVFaR-EbltOfwEGyjBuaSWF77ByBrboA"
    ));
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(array(
        "model" => "gpt-4o",
        "messages" => $messages,
        "max_tokens" => 4096,
        "temperature" => 0.7,
        "n" => 1
    )));

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($curl);
    if ($response) {
        $response = json_decode($response, true);
        if (empty($response['choices'][0]['message']['content']) == false) {
            return array(
                "succ" => true,
                "text" => $response['choices'][0]['message']['content'],
            );
        } else {
            return array(
                "succ" => false,
                "mess" => 'Error generation responce' . json_encode($response, JSON_UNESCAPED_UNICODE)
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