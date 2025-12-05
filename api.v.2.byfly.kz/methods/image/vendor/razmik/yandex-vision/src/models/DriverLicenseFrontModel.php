<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 28.10.2022
 * Time: 17:12
 */

namespace razmik\yandex_vision\models;

use razmik\yandex_vision\entities\DriverLicenseFrontEntity;
use razmik\yandex_vision\entities\AbstractTextDetectionEntity;

/**
 * Водительское удостоверение, лицевая сторона
 *
 * Class DriverLicenseFrontModel
 * @package razmik\yandex_vision\models
 */
class DriverLicenseFrontModel extends AbstractTextDetectionModel implements TextDetectionModelInterface
{
    /** @var string  */
    private const MODEL = 'driver-license-front';

    /**
     * @inheritDoc
     */
    public static function createEntity(array $data): AbstractTextDetectionEntity
    {
        return new DriverLicenseFrontEntity($data);
    }

    /**
     * @inheritDoc
     */
    public function getModelName(): string
    {
        return self::MODEL;
    }
}
