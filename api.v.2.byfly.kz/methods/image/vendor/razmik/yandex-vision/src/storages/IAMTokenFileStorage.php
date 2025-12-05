<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 28.10.2022
 * Time: 20:24
 */

namespace razmik\yandex_vision\storages;


use razmik\yandex_vision\exceptions\YandexVisionIAMTokenException;
use razmik\yandex_vision\IAMToken;

/**
 * Файловое хранилище токена авторизации
 *
 * Class YandexIAMTokenFileStorage
 * @package razmik\yandex_vision\storages
 */
class IAMTokenFileStorage implements IAMTokenStorageInterface
{
    /** @var string */
    private const FILE_NAME = "YandexVisionIAMToken";

    /**
     * Путь к файлу
     *
     * @var string
     */
    private $pathToFile;

    /**
     *
     */
    public function __construct()
    {
        $this->initStoragePath();
    }

    /**
     * @inheritDoc
     */
    public function loadToken(): ?IAMToken
    {
        $pathToFile = $this->pathToFile;

        if (
            file_exists($pathToFile) === false ||
            ($content = file_get_contents($pathToFile)) === false
        ) {
            return null;
        }
        $dateAt = filemtime($pathToFile);

        return new IAMToken($content, $dateAt);
    }

    /**
     * @inheritDoc
     * @throws YandexVisionIAMTokenException
     */
    public function saveToken(string $token): void
    {
        $pathToFile = $this->pathToFile;

        if (
            file_exists($pathToFile) === true &&
            unlink($pathToFile) === false
        ) {
            throw new YandexVisionIAMTokenException("Не удалось удалить токен авторизации");
        }

        if (file_put_contents($pathToFile, $token) === false) {
            throw new YandexVisionIAMTokenException("Не удалось сохранить токен авторизации");
        }
    }

    /**
     * Инициализация места хранения токена
     *
     * @return void
     */
    private function initStoragePath()
    {
        $this->pathToFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::FILE_NAME;
    }
}
