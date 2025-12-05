<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 04.11.2022
 * Time: 15:15
 */

namespace razmik\yandex_vision\entities;

/**
 * Сущность качества изображения
 *
 * Class QualityEntity
 * @package razmik\yandex_vision\entities
 */
class QualityEntity extends AbstractBaseEntity implements ClassificationEntityInterface
{
    /**
     * Изображение низкого качества
     *
     * @var float
     */
    protected $low;

    /**
     * Изображение нормального качества
     *
     * @var float
     */
    protected $medium;

    /**
     * Изображение высокого качества
     *
     * @var float
     */
    protected $high;

    /**
     * @return float
     */
    public function getLow(): float
    {
        return $this->low;
    }

    /**
     * @return float
     */
    public function getMedium(): float
    {
        return $this->medium;
    }

    /**
     * @return float
     */
    public function getHigh(): float
    {
        return $this->high;
    }

    /**
     * Низкого качества
     *
     * @return bool
     */
    public function isLow(): bool
    {
        return max($this->asArray()) === $this->low;
    }

    /**
     * Среднего качества
     *
     * @return bool
     */
    public function isMedium(): bool
    {
        return max($this->asArray()) === $this->medium;
    }

    /**
     * Высокого качества
     *
     * @return bool
     */
    public function isHigh(): bool
    {
        return max($this->asArray()) === $this->high;
    }
}
