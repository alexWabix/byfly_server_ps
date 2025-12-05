<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 30.10.2022
 * Time: 13:35
 */

namespace razmik\yandex_vision;

use razmik\yandex_vision\clients\YandexVisionApiClientInterface;
use razmik\yandex_vision\exceptions\YandexVisionAuthException;
use razmik\yandex_vision\exceptions\YandexVisionIAMTokenException;
use razmik\yandex_vision\exceptions\YandexVisionRequestException;
use razmik\yandex_vision\responses\AbstractResponse;
use razmik\yandex_vision\responses\DataResponse;
use razmik\yandex_vision\responses\FailureResponse;
use razmik\yandex_vision\storages\IAMTokenStorageInterface;

/**
 * Аутентификация в API перед отправкой запроса
 *
 * Class IAMTokenRequestAuth
 * @package razmik\yandex_vision
 */
class IAMTokenRequestAuth
{
    /**
     * Место хранения IAM токена
     *
     * @var IAMTokenStorageInterface
     */
    private $iamTokenStorage;

    /**
     * Клиент работы с API
     *
     * @var YandexVisionApiClientInterface
     */
    private $apiClient;

    /**
     * @param IAMTokenStorageInterface $iamTokenStorage
     * @param YandexVisionApiClientInterface $apiClient
     */
    public function __construct(
        IAMTokenStorageInterface $iamTokenStorage,
        YandexVisionApiClientInterface $apiClient
    )
    {
        $this->iamTokenStorage = $iamTokenStorage;
        $this->apiClient = $apiClient;
    }

    /**
     * Отправка запроса
     *
     * @param callable $callback
     * @return DataResponse|FailureResponse
     * @throws YandexVisionAuthException
     * @throws YandexVisionRequestException
     * @throws YandexVisionIAMTokenException
     */
    public function send(callable $callback): AbstractResponse
    {
        try {
            $response = $this->sendRequest($callback);
        } catch (YandexVisionAuthException $exception) {
            $this->refreshIAMToken();
            $response = $this->sendRequest($callback);
        }

        if ($response->isSuccess() === false) {
            throw YandexVisionRequestException::createByResponse($response);
        }

        return $response;
    }

    /**
     * Отправка запроса
     *
     * @param callable $callback
     * @return DataResponse|FailureResponse
     * @throws YandexVisionAuthException
     * @throws YandexVisionRequestException
     * @throws YandexVisionIAMTokenException
     */
    private function sendRequest(callable $callback): AbstractResponse
    {
        $IAMToken = $this->getIAMToken();
        /** @var DataResponse|FailureResponse $response */
        $response = call_user_func($callback, $IAMToken);

        if ($response->isAuthFailure() === true) {
            throw new YandexVisionAuthException($response->getMessage(), $response->getCode());
        }

        return $response;
    }

    /**
     * Обновление токена авторизации
     *
     * @return void
     * @throws YandexVisionRequestException
     */
    private function refreshIAMToken()
    {
        $apiClient = $this->apiClient;
        $iamTokenStorage = $this->iamTokenStorage;
        $response = $apiClient->fetchIAMToken();

        if ($response->isSuccess() === false) {
            throw YandexVisionRequestException::createByResponse($response);
        }

        $iamTokenStorage->saveToken($response->getIamToken());
    }

    /**
     * Загрузка IAM токена из хранилища
     *
     * @return IAMToken|null
     */
    private function fetchIAMTokenFromStorage(): ?IAMToken
    {
        $iamTokenStorage = $this->iamTokenStorage;
        $IAMToken = $iamTokenStorage->loadToken();

        if (
            $IAMToken === null ||
            $IAMToken->isExpired() === true
        ) {
            return null;
        }

        return $IAMToken;
    }

    /**
     * Получение токена авторизации
     *
     * @return IAMToken
     * @throws YandexVisionRequestException
     * @throws YandexVisionIAMTokenException
     */
    private function getIAMToken(): IAMToken
    {
        $IAMToken = $this->fetchIAMTokenFromStorage();

        if ($IAMToken === null) {
            $this->refreshIAMToken();
            $IAMToken = $this->fetchIAMTokenFromStorage();

            if ($IAMToken === null) {
                throw new YandexVisionIAMTokenException("Не удалось получить токен авторизации.");
            }
        }

        return $IAMToken;
    }
}
