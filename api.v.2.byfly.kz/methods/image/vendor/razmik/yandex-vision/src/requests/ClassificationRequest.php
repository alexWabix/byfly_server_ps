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
use razmik\yandex_vision\models\ClassificationModelInterface;

/**
 * Запрос классификация изображения
 *
 * Class ClassificationRequest
 * @package razmik\yandex_vision\requests
 */
class ClassificationRequest implements RequestInterface
{
    /** @var string */
    private const TYPE = "CLASSIFICATION";

    /**
     * Документ на распознание
     *
     * @var AbstractDocument
     */
    private $document;

    /**
     * Модель распознания
     *
     * @var ClassificationModelInterface
     */
    private $model;

    /**
     * @param AbstractDocument $document
     * @param ClassificationModelInterface $model
     */
    public function __construct(
        AbstractDocument             $document,
        ClassificationModelInterface $model
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
                    "classificationConfig" => [
                        "model" => $model->getModelName(),
                    ],
                ],
            ],
            "mime_type" => $document->getMimeType(),
        ];
    }
}
