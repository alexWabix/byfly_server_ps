<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 28.10.2022
 * Time: 17:04
 */

namespace razmik\yandex_vision\clients;

use razmik\yandex_vision\IAMToken;
use razmik\yandex_vision\requests\ClassificationRequest;
use razmik\yandex_vision\requests\FaceDetectionRequest;
use razmik\yandex_vision\requests\TextDetectionRequest;
use razmik\yandex_vision\responses\AbstractResponse;
use razmik\yandex_vision\responses\DataResponse;
use razmik\yandex_vision\responses\FailureResponse;
use razmik\yandex_vision\responses\IAMTokenResponse;

/**
 * Интерфейс запросов к сервису
 *
 * Class YandexApiClientInterface
 * @package razmik\yandex_vision\clients
 */
interface YandexVisionApiClientInterface
{
    /**
     * Распознание документа
     *
     * @param IAMToken $IAMToken
     * @param TextDetectionRequest $request
     * @return DataResponse|FailureResponse
     */
    public function textDetection(IAMToken $IAMToken, TextDetectionRequest $request): AbstractResponse;

    /**
     * Классификация изображения
     *
     * @param IAMToken $IAMToken
     * @param ClassificationRequest $request
     * @return DataResponse|FailureResponse
     */
    public function classification(IAMToken $IAMToken, ClassificationRequest $request): AbstractResponse;

    /**
     * Обнаружение лиц
     *
     * @param IAMToken $IAMToken
     * @param FaceDetectionRequest $request
     * @return DataResponse|FailureResponse
     */
    public function faceDetection(IAMToken $IAMToken, FaceDetectionRequest $request): AbstractResponse;

    /**
     * Получение токена авторизации
     *
     * @return IAMTokenResponse|FailureResponse
     */
    public function fetchIAMToken(): AbstractResponse;
}