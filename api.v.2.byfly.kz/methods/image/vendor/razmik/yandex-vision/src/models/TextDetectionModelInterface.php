<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 04.11.2022
 * Time: 11:54
 */

namespace razmik\yandex_vision\models;

use razmik\yandex_vision\entities\AbstractTextDetectionEntity;

/**
 * Распознавание текста
 *
 * Class TextDetectionModelInterface
 * @package razmik\yandex_vision\models
 */
interface TextDetectionModelInterface
{
    /**
     * Создание распознанной сущности
     *
     * @param array $data
     * @return AbstractTextDetectionEntity
     */
    public static function createEntity(array $data): AbstractTextDetectionEntity;

    /**
     * Название модели шаблона
     *
     * @return string
     */
    public function getModelName(): string;

    /**
     * @return string[]
     */
    public function getLanguages(): array;
}
