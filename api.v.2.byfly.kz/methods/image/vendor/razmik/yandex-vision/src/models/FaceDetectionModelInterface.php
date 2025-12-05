<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 06.11.2022
 * Time: 16:44
 */

namespace razmik\yandex_vision\models;

use razmik\yandex_vision\entities\FaceDetectionEntity;

/**
 * Обнаружение лиц на изображении
 *
 * Class FaceDetectionModelInterface
 * @package razmik\yandex_vision\models
 */
interface FaceDetectionModelInterface
{
    /**
     * Сущность распознанного лица
     *
     * @param array $data
     * @return FaceDetectionEntity
     */
    public static function createEntity(array $data): FaceDetectionEntity;
}