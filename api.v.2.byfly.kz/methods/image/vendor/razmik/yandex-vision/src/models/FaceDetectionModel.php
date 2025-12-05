<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 28.10.2022
 * Time: 17:12
 */

namespace razmik\yandex_vision\models;

use razmik\yandex_vision\entities\FaceDetectionEntity;

/**
 * Распознавание лица
 *
 * Class FaceDetectionModel
 * @package razmik\yandex_vision\models
 */
class FaceDetectionModel implements FaceDetectionModelInterface
{
    /**
     * @inheritDoc
     */
    public static function createEntity(array $data): FaceDetectionEntity
    {
        return new FaceDetectionEntity($data);
    }
}
