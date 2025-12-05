<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 28.10.2022
 * Time: 20:33
 */

namespace razmik\yandex_vision\storages;

use razmik\yandex_vision\IAMToken;

/**
 * Хранение токена авторизации
 *
 * Class YandexIAMTokenStorageInterface
 * @package razmik\yandex_vision\requests
 */
interface IAMTokenStorageInterface
{
    /**
     * Получение токена авторизации
     *
     * @return IAMToken|null
     */
    public function loadToken(): ?IAMToken;

    /**
     * Сохранение токена авторизации
     *
     * @param string $token
     * @return void
     */
    public function saveToken(string $token): void;
}