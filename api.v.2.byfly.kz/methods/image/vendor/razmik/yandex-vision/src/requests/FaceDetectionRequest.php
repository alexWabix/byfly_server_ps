<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 28.10.2022
 * Time: 17:17
 */

namespace razmik\yandex_vision\requests;

use razmik\yandex_vision\documents\AbstractDocument;
use razmik\yandex_vision\exceptions\YandexVisionDocumentException;
use razmik\yandex_vision\models\FaceDetectionModelInterface;

/**
 * Запрос обнаружения лиц
 *
 * Class FaceDetectionRequest
 * @package razmik\yandex_vision\requests
 */
class FaceDetectionRequest implements RequestInterface
{
    /** @var string */
    private const TYPE = "FACE_DETECTION";

    /**
     * Документ на распознание
     *
     * @var AbstractDocument
     */
    private $document;

    /**
     * Модель распознания
     *
     * @var FaceDetectionModelInterface
     */
    private $model;

    /**
     * @param AbstractDocument $document
     * @param FaceDetectionModelInterface $model
     */
    public function __construct(
        AbstractDocument            $document,
        FaceDetectionModelInterface $model
    )
    {
        $this->document = $document;
        $this->model = $model;
    }

    /**
     * @inheritDoc
     * @throws YandexVisionDocumentException
     */
    public function getConfig(): array
    {
        $document = $this->document;

        return [
            "content" => $document->getBase64Content(),
            "features" => [
                [
                    "type" => self::TYPE,
                ],
            ],
            "mime_type" => $document->getMimeType(),
        ];
    }
}
