<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 28.10.2022
 * Time: 17:02
 */

namespace razmik\yandex_vision;

use razmik\yandex_vision\clients\YandexVisionApiClientInterface;
use razmik\yandex_vision\documents\AbstractDocument;
use razmik\yandex_vision\entities\AbstractTextDetectionEntity;
use razmik\yandex_vision\entities\ClassificationEntityInterface;
use razmik\yandex_vision\entities\FaceDetectionEntity;
use razmik\yandex_vision\exceptions\YandexVisionAuthException;
use razmik\yandex_vision\exceptions\YandexVisionIAMTokenException;
use razmik\yandex_vision\exceptions\YandexVisionRequestException;
use razmik\yandex_vision\models\ClassificationModelInterface;
use razmik\yandex_vision\models\FaceDetectionModelInterface;
use razmik\yandex_vision\models\TextDetectionModelInterface;
use razmik\yandex_vision\requests\ClassificationRequest;
use razmik\yandex_vision\requests\FaceDetectionRequest;
use razmik\yandex_vision\requests\TextDetectionRequest;
use razmik\yandex_vision\storages\IAMTokenFileStorage;
use razmik\yandex_vision\storages\IAMTokenStorageInterface;

/**
 * Анализ изображения
 *
 * Class YandexVision
 * @package razmik\yandex_vision
 */
class YandexVision
{
    /**
     * Клиент работы с запросами API
     *
     * @var YandexVisionApiClientInterface
     */
    private $apiClient;

    /**
     * Место хранения IAM токена
     *
     * @var IAMTokenStorageInterface
     */
    private $iamTokenStorage;

    /**
     * @param YandexVisionApiClientInterface $apiClient
     */
    public function __construct(YandexVisionApiClientInterface $apiClient)
    {
        $this->apiClient = $apiClient;
        $this->setIamTokenStorage(new IAMTokenFileStorage());
    }

    /**
     * Изменение места хранения токена авторизации
     *
     * @param IAMTokenStorageInterface $iamTokenStorage
     * @return YandexVision
     */
    public function setIamTokenStorage(IAMTokenStorageInterface $iamTokenStorage): YandexVision
    {
        $this->iamTokenStorage = $iamTokenStorage;

        return $this;
    }

    /**
     * Распознание документа
     *
     * @param AbstractDocument $document
     * @param TextDetectionModelInterface $model
     * @return AbstractTextDetectionEntity[]
     * @throws YandexVisionAuthException
     * @throws YandexVisionRequestException
     * @throws YandexVisionIAMTokenException
     */
    public function getDetectedText(
        AbstractDocument            $document,
        TextDetectionModelInterface $model
    ): array
    {
        $apiClient = $this->apiClient;
        $iamTokenStorage = $this->iamTokenStorage;

        $IAMTokenAuthRequest = new IAMTokenRequestAuth($iamTokenStorage, $apiClient);
        $request = new TextDetectionRequest($document, $model);

        $response = $IAMTokenAuthRequest->send(
            function (IAMToken $iamToken) use ($apiClient, $request) {
                return $apiClient->textDetection($iamToken, $request);
            }
        );

        $results = current($response->getData()['results']);
        $pages = current($results['results'])['textDetection']["pages"];

        $pages = array_filter($pages, function (array $page) {
            return isset($page['blocks']);
        });

        array_walk($pages, function (array &$page) use ($model) {
            $page = $model::createEntity($page);
        });

        return $pages;
    }

    /**
     * Классификация изображений
     *
     * @param AbstractDocument $document
     * @param ClassificationModelInterface $model
     * @return ClassificationEntityInterface
     * @throws YandexVisionAuthException
     * @throws YandexVisionRequestException
     * @throws YandexVisionIAMTokenException
     */
    public function getClassifiedProperties(
        AbstractDocument             $document,
        ClassificationModelInterface $model
    ): ClassificationEntityInterface
    {
        $apiClient = $this->apiClient;
        $iamTokenStorage = $this->iamTokenStorage;

        $IAMTokenAuthRequest = new IAMTokenRequestAuth($iamTokenStorage, $apiClient);
        $request = new ClassificationRequest($document, $model);

        $response = $IAMTokenAuthRequest->send(
            function (IAMToken $iamToken) use ($apiClient, $request) {
                return $apiClient->classification($iamToken, $request);
            }
        );

        $results = current($response->getData()['results']);
        $properties = current($results['results'])['classification']["properties"];

        return $model::createEntity($properties);
    }

    /**
     * Обнаружение лиц
     *
     * @param AbstractDocument $document
     * @param FaceDetectionModelInterface $model
     * @return FaceDetectionEntity[]
     * @throws YandexVisionAuthException
     * @throws YandexVisionRequestException
     * @throws YandexVisionIAMTokenException
     */
    public function getFaceCoordinates(
        AbstractDocument            $document,
        FaceDetectionModelInterface $model
    ): array
    {
        $apiClient = $this->apiClient;
        $iamTokenStorage = $this->iamTokenStorage;

        $IAMTokenAuthRequest = new IAMTokenRequestAuth($iamTokenStorage, $apiClient);
        $request = new FaceDetectionRequest($document, $model);

        $response = $IAMTokenAuthRequest->send(
            function (IAMToken $iamToken) use ($apiClient, $request) {
                return $apiClient->faceDetection($iamToken, $request);
            }
        );

        $results = current($response->getData()['results']);
        $faces = current($results['results'])['faceDetection'];

        if (empty($faces) === true) {
            return [];
        }

        return array_map(
            function (array $face) use ($model) {
                return $model::createEntity($face['boundingBox']['vertices']);
            }, $faces['faces']
        );
    }
}
