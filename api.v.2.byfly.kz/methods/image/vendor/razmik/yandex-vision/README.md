Yandex Vision
=======================================================

[![Latest Stable Version](https://poser.pugx.org/razmik/yandex-vision/v/stable.png)](https://packagist.org/packages/razmik/yandex-vision)
[![Total Downloads](https://poser.pugx.org/razmik/yandex-vision/downloads.png)](https://packagist.org/packages/razmik/yandex-vision)


[Yandex Vision](https://cloud.yandex.ru/docs/vision/) — сервис компьютерного зрения для анализа изображений.

Возможности:
- распознавание документов (паспорт, водительское удостоверение, регистрационные номера автомобилей).
- Классификация изображений (оценивает качество, оценивает соответствие признакам)
- Обнаружение лиц

Установка
------------
Устанавливать рекомендуется через [composer][] выполнив:

	composer razmik/yandex-vision "~0.1.0"

Использование
-----

### Пример распознавания документа

```php
// Создание стандартного экземпляра HTTP клиента
$client = new YandexVisionApiClient('<token>', '<folderId>');
$yandexVision = new YandexVision($client);

// Документ на отправку
$document = new ImageDocument('./passport.jpg');

// Модель паспорта для распознавания
$model = new PassportModel();

// Получение данных
$results = $vision->getDetectedText($document, $model);
```

### Пример классификации изображения
```php
...

// Модель определения качества
$model = new QualityModel();

// Получение данных
$result = $vision->getClassifiedProperties($document, $model);
```

### Пример обнаружения лиц
```php
...

// Модель обнаружение лиц
$model = new FaceDetectionModel();

// Получение данных
$results = $vision->getFaceCoordinates($document, $model);
```

Типы документов
-----

| Тип документа         | Экземпляр класса                         | 
|-----------------------|------------------------------------------|
| Изображение           | $document = new ImageDocument('./file'); |
| PDF           | $document = new PdfDocument('./file');   |

Модели распознавания
-----

### Распознавание текста

| Модель              | Экземпляр класса                        | 
|---------------------|-----------------------------------------|
| Паспорт             | $model = new PassportModel();           |
| ВУ, лицевая сторона | $model = new DriverLicenseFrontModel(); |
| ВУ, обратная сторона | $model = new DriverLicenseBackModel();  |
| Регистрационные номера | $model = new LicensePlateModel(['ru']); |

### Классификация изображения

| Модель                 | Экземпляр класса                        | 
|------------------------|-----------------------------------------|
| Качество изображения   | $model = new QualityModel();           |
| Признаки изображения   | $model = new ModerationModel(); |

### Обнаружение лиц

| Модель                 | Экземпляр класса                   | 
|------------------------|------------------------------------|
| Обнаружение лиц   | $model = new FaceDetectionModel(); |

Обработка ошибок
-----
### Исключения

| Модель                      | Экземпляр класса                   | 
|-----------------------------|------------------------------------|
| Интерфейс всех исключений   | YandexVisionExceptionInterface(); |
| Не корректный документ      | YandexVisionDocumentException(); |
| Не корректный запрос        | YandexVisionRequestException(); |
| Ошибка работы с IAM токеном | YandexVisionIAMTokenException(); |
| Ошибка авторизации          | YandexVisionAuthException(); |

IAM токен
-----

### Изменение места хранения
По умолчанию токен авторизации сохраняется во временный файл YandexVisionIAMToken.
Для изменения места хранения токена можно создать свое хранилище:

```php
// Создание своего хранилища
class MyStorage implements IAMTokenStorageInterface
{
    ...
}

$storage = new MyStorage();

// Смена хранилища
$yandexVision = new YandexVision($client);
$yandexVision->setIamTokenStorage($storage);

```

### Изменение времени хранения IAM токена
По умолчанию токен авторизации храниться 8 часов. 
Изменить время хранения токена:

```php
// Задать время хранения IAM токена в секундах
IAMToken::$expiredAt = 4 * 3600;
```

HTTP клиент
-----

### Использование своего HTTP клиента
По умолчанию запросы отправляются через curl. Для подключения своего HTTP клиента:

```php
// Создание своего HTTP клиента
class MyHTTPClient extends AbstractYandexVisionApiClient
{
    ...
}

$client = new MyHTTPClient();

// Использование HTTP клиента
$yandexVision = new YandexVision($client);

```

Контакы
-------

Не стесняйтесь обращаться ко мне по [email](mailto:ilyha_roza@mail.ru)