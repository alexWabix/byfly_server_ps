<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 28.10.2022
 * Time: 17:12
 */

namespace razmik\yandex_vision\models;

use razmik\yandex_vision\entities\DriverLicenseBackEntity;
use razmik\yandex_vision\entities\AbstractTextDetectionEntity;

/**
 * Водительское удостоверение, обратная сторона
 *
 * Class DriverLicenseBackModel
 * @package razmik\yandex_vision\models
 */
class DriverLicenseBackModel extends AbstractTextDetectionModel implements TextDetectionModelInterface
{
    /** @var string  */
    private const MODEL = 'driver-license-back';

    /**
     * @inheritDoc
     */
    public static function createEntity(array $data): AbstractTextDetectionEntity
    {
        return new DriverLicenseBackEntity($data);
    }

    /**
     * @inheritDoc
     */
    public function getModelName(): string
    {
        return self::MODEL;
    }
}
