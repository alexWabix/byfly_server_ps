<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 03.11.2022
 * Time: 17:27
 */

namespace razmik\yandex_vision\requests;

/**
 * Запрос на анализ документа
 *
 * Class RequestInterface
 * @package razmik\yandex_vision\requests
 */
interface RequestInterface
{
    /**
     * Параметры запроса
     *
     * @return array[]
     */
    public function getConfig(): array;
}
