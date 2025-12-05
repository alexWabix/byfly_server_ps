<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 03.11.2022
 * Time: 17:54
 */

namespace razmik\yandex_vision\responses;

/**
 * Фабрика создания ответа
 *
 * Class ResponseFactory
 * @package \razmik\yandex_vision\responses
 */
class ResponseFactory
{
    /**
     * ResponseFactory constructor.
     */
    private function __construct()
    {
    }

    /**
     * Создание ответа на основе кода ответа
     *
     * @param int $httpCode
     * @param array $data
     * @return AbstractResponse
     */
    public static function createByHttpCode(int $httpCode, array $data = []): AbstractResponse
    {
        switch ($httpCode) {
            case AbstractResponse::HTTP_SUCCESS_CODE:
                return new DataResponse($httpCode, $data);
            default:
                return new FailureResponse($httpCode, $data);
        }
    }
}
