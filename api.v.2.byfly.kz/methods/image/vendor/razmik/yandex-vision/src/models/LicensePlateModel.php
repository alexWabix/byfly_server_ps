<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 28.10.2022
 * Time: 17:12
 */

namespace razmik\yandex_vision\models;

use razmik\yandex_vision\entities\LicensePlateEntity;
use razmik\yandex_vision\entities\AbstractTextDetectionEntity;

/**
 * Распознавание регистрационного номера автомобиля
 *
 * Class LicensePlateModel
 * @package razmik\yandex_vision\models
 */
class LicensePlateModel extends AbstractTextDetectionModel implements TextDetectionModelInterface
{
    /** @var string  */
    private const MODEL = 'license-plates';

    /**
     * @inheritDoc
     */
    public function __construct(array $languages)
    {
        parent::__construct($languages);
    }

    /**
     * @inheritDoc
     */
    public static function createEntity(array $data): AbstractTextDetectionEntity
    {
        return new LicensePlateEntity($data);
    }

    /**
     * @inheritDoc
     */
    public function getModelName(): string
    {
        return self::MODEL;
    }
}
