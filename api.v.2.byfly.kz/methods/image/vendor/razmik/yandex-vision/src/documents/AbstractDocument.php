<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 28.10.2022
 * Time: 17:23
 */

namespace razmik\yandex_vision\documents;

use razmik\yandex_vision\exceptions\YandexVisionDocumentException;

/**
 * Документ на отправку
 *
 * Class AbstractContentInterface
 * @package razmik\yandex_vision\documents
 */
abstract class AbstractDocument
{
    /** @var int */
    const MAX_FILE_SIZE = 1;

    /**
     * Путь к файлу
     *
     * @var string
     */
    protected $sourcePath;

    /**
     * @param string $sourcePath
     * @throws YandexVisionDocumentException
     */
    public function __construct(string $sourcePath)
    {
        $this->validateSourcePath($sourcePath);
        $this->validateFileSize($sourcePath);

        $this->sourcePath = $sourcePath;
    }

    /**
     * Преобразование документа в строку base64
     *
     * @return string
     */
    public function getBase64Content(): string
    {
        $data = file_get_contents($this->sourcePath);

        return base64_encode($data);
    }

    /**
     *
     *
     * @return string
     * @throws YandexVisionDocumentException
     */
    public function getMimeType(): string
    {
        $mimeType = mime_content_type($this->sourcePath);

        if ($mimeType === false) {
            throw new YandexVisionDocumentException("Не удалось определить тип файла.");
        }

        return $mimeType;
    }

    /**
     * Валидация пути файла
     *
     * @param string $sourcePath
     * @return void
     * @throws YandexVisionDocumentException
     */
    private function validateSourcePath(string $sourcePath)
    {
        if (file_exists($sourcePath) === false) {
            throw new YandexVisionDocumentException("Файл $sourcePath не найден.");
        }
    }

    /**
     * Проверка размера файла
     *
     * @param string $sourcePath
     * @return void
     * @throws YandexVisionDocumentException
     */
    private function validateFileSize(string $sourcePath)
    {
        $fileSize = filesize($sourcePath);

        if (
            $fileSize === false ||
            $fileSize / pow(1024, 2) > self::MAX_FILE_SIZE
        ) {
            throw new YandexVisionDocumentException("Загруженный файла превышает максимальный размер.");
        }
    }
}