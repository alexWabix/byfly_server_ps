<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 28.10.2022
 * Time: 17:44
 */

namespace razmik\yandex_vision\requests;

/**
 * Фабрика создания запроса
 *
 * Class RequestFactory
 * @package \razmik\yandex_vision\factories
 */
class RequestFactory
{
    /**
     * YandexRequestFactory constructor.
     */
    private function __construct()
    {
    }

    /**
     * Создание запроса на распознание текста
     *
     * @param string $folderId
     * @param RequestInterface $request
     * @return array
     */
    public static function create(string $folderId, RequestInterface $request): array
    {
        return [
            "folderId" => $folderId,
            "analyze_specs" => $request->getConfig(),
        ];
    }
}
