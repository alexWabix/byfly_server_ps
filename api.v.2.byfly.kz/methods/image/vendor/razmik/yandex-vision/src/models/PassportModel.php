<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 28.10.2022
 * Time: 17:12
 */

namespace razmik\yandex_vision\models;

use razmik\yandex_vision\entities\PassportEntity;
use razmik\yandex_vision\entities\AbstractTextDetectionEntity;

/**
 * Распознавание паспорта
 *
 * Class PassportModel
 * @package razmik\yandex_vision\models
 */
class PassportModel extends AbstractTextDetectionModel implements TextDetectionModelInterface
{
    /** @var string  */
    private const MODEL = 'passport';

    /**
     * @inheritDoc
     */
    public static function createEntity(array $data): AbstractTextDetectionEntity
    {
        return new PassportEntity($data);
    }

    /**
     * @inheritDoc
     */
    public function getModelName(): string
    {
        return self::MODEL;
    }
}
