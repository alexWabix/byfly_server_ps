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
use razmik\yandex_vision\models\TextDetectionModelInterface;

/**
 * Запрос на распознание документа
 *
 * Class TextDetectionRequest
 * @package razmik\yandex_vision\requests
 */
class TextDetectionRequest implements RequestInterface
{
    /** @var string */
    private const TYPE = "TEXT_DETECTION";

    /**
     * Документ на распознание
     *
     * @var AbstractDocument
     */
    private $document;

    /**
     * Модель распознания
     *
     * @var TextDetectionModelInterface
     */
    private $model;

    /**
     * @param AbstractDocument $document
     * @param TextDetectionModelInterface $model
     */
    public function __construct(
        AbstractDocument            $document,
        TextDetectionModelInterface $model
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
        $model = $this->model;

        return [
            "content" => $document->getBase64Content(),
            "features" => [
                [
                    "type" => self::TYPE,
                    "text_detection_config" => [
                        "language_codes" => $model->getLanguages(),
                        "model" => $model->getModelName(),
                    ],
                ],
            ],
            "mime_type" => $document->getMimeType(),
        ];
    }
}
