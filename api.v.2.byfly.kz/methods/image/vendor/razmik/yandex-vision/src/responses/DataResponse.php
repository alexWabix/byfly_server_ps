<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 30.10.2022
 * Time: 13:23
 */

namespace razmik\yandex_vision\responses;

/**
 * Ответ от сервиса с данными
 *
 * Class DataResponse
 * @package razmik\yandex_vision\responses
 */
class DataResponse extends AbstractResponse
{
    /**
     * Данные ответа
     *
     * @var array
     */
    private $data;

    /**
     * @param int $httpCode
     * @param array $data
     */
    public function __construct(int $httpCode, array $data = [])
    {
        parent::__construct($httpCode);
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}
