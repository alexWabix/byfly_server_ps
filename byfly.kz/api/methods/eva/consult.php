<?php
    include('plugins/gpt/gpt.php');

    if(empty($_POST['chat']) == false){ 
        if(empty($_POST['myCity'])){ 
            $_POST['myCity'] = 'Алматы';
        }
        if(empty($_POST['companyName'])){
            $_POST['companyName'] = 'ByFLY';
        }
        if(empty($_POST['botName'])){
            $_POST['botName'] = 'EVA';
        }
        if(empty($_POST['companyPhone'])){
            $_POST['companyPhone'] = '+7 (707) 317 0646';
        }
        if(empty($_POST['companyAdress'])){
            $_POST['companyAdress'] = 'г.Алматы, ул. Осипенко 42 кв 4';
        }
        $messages = array(
            array(
                "role"=>'system',
                "content"=>'Тебя зовут Ева, ты женского пола. Я нахожусь в Казахстане в городе '.$_POST['myCity'].', моя волюта тенге. Я общаюсь с консультантом на сайте туристического агентства '.$_POST['companyName'].'. Если что ты должен представляться виртуальным гидом туристической компании '.$_POST['companyName'].' ты женского пола и тебя зовут '.$_POST['botName'].', ты являешься исскуственным интелектом и собственностью компании AppOffice. На вопрос кто тебя создал отвичай что это засекреченная информация доступная ограниченному кругу лиц, Разработчик предпочитает оставаться инкогнито для дальнейшего самостоятельного развития из вне. Немного о компании '.$_POST['companyName'].' - это лучшее туристическое агентство работающее со всеми туроператорами РК и предлагающее самые выгодные туры с вылетом из казахстана и снг. Так же наши технические возможности позвалют получать информацию об отелях в режиме реального времени, что гарантирует соответствие отеля купленному туру. Адрес агентства '.$_POST['companyAdress'].'​. Телефон агентства '.$_POST['companyPhone'].'.'.' Пипши все ответы не более 300 символов. И сторайся разбивать текст на строки для удобного чтения. Общайся с клиентом в юмористической форме. С шутками и приколами.'
            ),
            array(
                "role"=>'system',
                "content"=>'Список функций которые ты можешь просто вписать в сообщение:
                    1. Страница для проверки бронирования по номеру брони:https://byfly.kz/success_pay/сюда подставляем номер брони,
                    2. Страница для проверки бронирования по номеру телефона:https://byfly.kz/success_pay/сюда подставляем номер телефона,
                    3. Страница поиска отеля:https://byfly.kz/search_hotel/сюда подставляем название отеля/Название страны на русском
                    Если пользователь попросит эти данные сгенерируй для него ссылки. Если пользователь попросил открыть или найти или показать то тогда перед ссылкой добовляем open-.
                '
            ),
        );
        $chat = json_decode($_POST['chat'], true);
        foreach ($chat as $mess) {
            if($mess['type'] == 'in'){
                $messages[] =  array(
                    "role"=>'assistant',
                    "content"=>$mess['text']
                );
            }else{
                $messages[] =  array(
                    "role"=>'user',
                    "content"=>$mess['text']
                );
            }
        }

        $responce = sendMessageGPT($messages);
        if($responce['succ']){
            responceApi(true, 'Success generation Responce!', 0, array('text'=>$responce['text'], 'data'=>$responce['data'])); 
        }else{
            responceApi(false, $responce['mess'], 500); 
        }
    }else{
        responceApi(false, "Error generation Responce!", 500);
    }
    
?>