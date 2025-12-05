<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 04.11.2022
 * Time: 14:43
 */

namespace razmik\yandex_vision\models;

use razmik\yandex_vision\entities\ClassificationEntityInterface;
use razmik\yandex_vision\entities\ModerationEntity;

/**
 * Сущность модерации изображения
 *
 * Class ModerationModel
 * @package razmik\yandex_vision\models
 */
class ModerationModel implements ClassificationModelInterface
{
    /** @var string */
    private const MODEL = 'moderation';

    /**
     * @inheritDoc
     */
    public static function createEntity(array $data): ClassificationEntityInterface
    {
        return new ModerationEntity($data);
    }

    /**
     * @inheritDoc
     */
    public function getModelName(): string
    {
        return self::MODEL;
    }
}
