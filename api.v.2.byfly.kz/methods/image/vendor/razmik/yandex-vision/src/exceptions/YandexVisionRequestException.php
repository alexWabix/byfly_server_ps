<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 28.10.2022
 * Time: 19:21
 */

namespace razmik\yandex_vision\exceptions;

use Exception;
use razmik\yandex_vision\responses\FailureResponse;

/**
 * Не корректный запрос
 *
 * Class YandexVisionRequestException
 * @package razmik\yandex_vision\exceptions
 */
class YandexVisionRequestException extends Exception implements YandexVisionExceptionInterface
{
    /**
     * Создание по результату ответа
     *
     * @param FailureResponse $response
     * @return YandexVisionRequestException
     */
    public static function createByResponse(FailureResponse $response): YandexVisionRequestException
    {
        return new self($response->getMessage(), $response->getCode());
    }
}
