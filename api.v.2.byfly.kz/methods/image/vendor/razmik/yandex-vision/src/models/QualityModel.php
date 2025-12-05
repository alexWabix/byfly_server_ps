<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 04.11.2022
 * Time: 14:43
 */

namespace razmik\yandex_vision\models;

use razmik\yandex_vision\entities\ClassificationEntityInterface;
use razmik\yandex_vision\entities\QualityEntity;

/**
 * Сущность качества изображения
 *
 * Class QualityModel
 * @package razmik\yandex_vision\models
 */
class QualityModel implements ClassificationModelInterface
{
    /** @var string */
    private const MODEL = 'quality';

    /**
     * @inheritDoc
     */
    public static function createEntity(array $data): ClassificationEntityInterface
    {
        return new QualityEntity($data);
    }

    /**
     * @inheritDoc
     */
    public function getModelName(): string
    {
        return self::MODEL;
    }
}
