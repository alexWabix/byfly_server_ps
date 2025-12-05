<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 04.11.2022
 * Time: 11:54
 */

namespace razmik\yandex_vision\models;

use razmik\yandex_vision\entities\ClassificationEntityInterface;

/**
 * Классификация изображения
 *
 * Class ClassificationModelInterface
 * @package razmik\yandex_vision\models
 */
interface ClassificationModelInterface
{
    /**
     * Создание распознанной сущности
     *
     * @param array $data
     * @return ClassificationEntityInterface
     */
    public static function createEntity(array $data): ClassificationEntityInterface;

    /**
     * Название модели шаблона
     *
     * @return string
     */
    public function getModelName(): string;
}
