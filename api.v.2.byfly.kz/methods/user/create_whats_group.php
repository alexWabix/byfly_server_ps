<?php
function createGroup($addPhone, $title)
{
    $url = "https://7103.api.greenapi.com/waInstance7103957708/createGroup/d0b5a00af9964a78a70d799e6bd51131467e6ae0f9d34ca295";

    $payload = [
        "groupName" => $title,
        "chatIds" => [
            $addPhone . '@c.us'
        ]
    ];

    $headers = [
        'Content-Type: application/json'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return array(
            'type' => false,
            'msg' => 'Error: ' . curl_error($ch)
        );
    } else {
        return array(
            'type' => true,
            'data' => json_decode($response, true)
        );
    }

    curl_close($ch);
}


function setGroupAdmin($groupId, $participantChatId)
{
    $url = "https://7103.api.greenapi.com/waInstance7103957708/setGroupAdmin/d0b5a00af9964a78a70d799e6bd51131467e6ae0f9d34ca295";

    $payload = [
        "groupId" => $groupId,
        "participantChatId" => $participantChatId . '@c.us',
    ];

    $headers = [
        'Content-Type: application/json'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return array(
            'type' => false,
            'msg' => 'Error: ' . curl_error($ch)
        );
    } else {
        return array(
            'type' => true,
            'data' => json_decode($response, true)
        );
    }
    curl_close($ch);

}


function setGroupAvatar($groupId, $imagePath)
{
    $url = "https://7103.api.greenapi.com/waInstance7103957708/setGroupPicture/d0b5a00af9964a78a70d799e6bd51131467e6ae0f9d34ca295";
    if (!file_exists($imagePath)) {
        return array(
            'type' => false,
            'msg' => 'Файл не найден.'
        );
    }

    $data = [
        'groupId' => $groupId,
    ];
    $files = [
        'file' => new CURLFile($imagePath, 'image/jpeg')
    ];

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, array_merge($data, $files));
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return array(
            'type' => false,
            'msg' => 'Error: ' . curl_error($ch)
        );
    }


    return array(
        'type' => true,
        'data' => json_decode($response, true)
    );

    curl_close($ch);
}


if (empty($_POST['user_id']) == false && empty($_POST['user_phone']) == false && empty($_POST['title_group']) == false && empty($_POST['city_id']) == false && empty($_POST['nakrutka']) == false) {
    $_POST['user_phone'] = preg_replace('/\D/', '', $_POST['user_phone']);

    $numberWhatsCheck = checkNumberFromWhatsapp($_POST['user_phone']);
    if ($numberWhatsCheck['type']) {
        $create = createGroup($_POST['user_phone'], $_POST['title_group']);
        if ($create['type']) {
            if ($create['data']['created']) {
                $admin = setGroupAdmin($create['data']['chatId'], $_POST['user_phone']);
                $avatar = setGroupAvatar($create['data']['chatId'], $dir . 'images/logo_group.jpg');
                if (
                    $db->query("INSERT INTO user_whatsapp_groups (`id`, `group_link`, `date_create`, `user_id`, `avatar_group`, `title_group`, `group_id`, `city_id`, `defoult_nakrutka`) VALUES (NULL, '" . $create['data']['groupInviteLink'] . "', CURRENT_TIMESTAMP, '" . $_POST['user_id'] . "', 'https://api.v.2.byfly.kz/images/logo_group.jpg', '" . $_POST['title_group'] . "', '" . $create['data']['chatId'] . "','" . $_POST['city_id'] . "', '" . $_POST['nakrutka'] . "');")
                ) {
                    echo json_encode(
                        array(
                            "type" => true,
                            "data" => array(
                                "chat_id" => $create['data']['chatId'],
                                "link" => $create['data']['groupInviteLink'],
                            ),
                        ),
                        JSON_UNESCAPED_UNICODE
                    );
                } else {
                    echo json_encode(
                        array(
                            "type" => false,
                            "msg" => $db->error,
                        ),
                        JSON_UNESCAPED_UNICODE
                    );
                }

            } else {
                echo json_encode(
                    array(
                        "type" => false,
                        "msg" => 'Error create groups... Instance no work.',
                    ),
                    JSON_UNESCAPED_UNICODE
                );
            }

        } else {
            echo json_encode(
                array(
                    "type" => false,
                    "msg" => $create['msg'],
                ),
                JSON_UNESCAPED_UNICODE
            );
        }

    } else {
        echo json_encode(
            array(
                "type" => false,
                "msg" => 'Number dont registration in whatsapp...',
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
} else {
    echo json_encode(
        array(
            "type" => false,
            "msg" => 'Empty data from group...',
        ),
        JSON_UNESCAPED_UNICODE
    );
}