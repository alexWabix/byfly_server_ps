<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 30.10.2022
 * Time: 13:50
 */

namespace razmik\yandex_vision\responses;

/**
 * Базовый ответ
 *
 * Class AbstractResponse
 * @package razmik\yandex_vision\responses
 */
abstract class AbstractResponse
{
    /** @var int */
    public const HTTP_SUCCESS_CODE = 200;

    /** @var int */
    public const HTTP_AUTH_FAILURE_CODE = 401;

    /**
     * Код ответа
     *
     * @var int
     */
    private $httpCode;

    /**
     * @param int $httpCode
     */
    public function __construct(int $httpCode)
    {
        $this->httpCode = $httpCode;
    }

    /**
     * Успешный ответ
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->httpCode === self::HTTP_SUCCESS_CODE;
    }

    /**
     * Ошибка токена авторизации
     *
     * @return bool
     */
    public function isAuthFailure(): bool
    {
        return $this->httpCode === self::HTTP_AUTH_FAILURE_CODE;
    }

    /**
     * Получение кода ответа
     *
     * @return int
     */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }
}
