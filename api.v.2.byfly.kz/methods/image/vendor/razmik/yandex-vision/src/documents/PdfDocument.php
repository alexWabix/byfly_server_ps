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
 * PDF документ
 *
 * Class PdfDocument
 * @package razmik\yandex_vision\documents
 */
class PdfDocument extends AbstractDocument
{
    /** @var string[] */
    private const MIME_TYPES = [
        'application/pdf',
    ];

    /**
     * @inheritDoc
     * @throws YandexVisionDocumentException
     */
    public function __construct(string $sourcePath)
    {
        parent::__construct($sourcePath);
        $this->validateMimeType($sourcePath);
    }

    /**
     * Проверка файла по типу
     *
     * @param string $sourcePath
     * @throws YandexVisionDocumentException
     */
    private function validateMimeType(string $sourcePath)
    {
        $type = mime_content_type($sourcePath);

        if (in_array($type, self::MIME_TYPES) === false) {
            throw new YandexVisionDocumentException("Тип файла $type не поддерживается.");
        }
    }
}
