<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 04.11.2022
 * Time: 15:15
 */

namespace razmik\yandex_vision\entities;

/**
 * Сущность модерации изображения
 *
 * Class ModerationEntity
 * @package razmik\yandex_vision\entities
 */
class ModerationEntity extends AbstractBaseEntity implements ClassificationEntityInterface
{
    /**
     * Есть контент для взрослых
     *
     * @var float
     */
    protected $adult;

    /**
     * Содержит шок-контент
     *
     * @var float
     */
    protected $gruesome;

    /**
     * Содержит текст
     *
     * @var float
     */
    protected $text;

    /**
     * Содержит текст
     *
     * @var float
     */
    protected $watermarks;

    /**
     * @return float
     */
    public function getAdult(): float
    {
        return $this->adult;
    }

    /**
     * @return float
     */
    public function getGruesome(): float
    {
        return $this->gruesome;
    }

    /**
     * @return float
     */
    public function getText(): float
    {
        return $this->text;
    }

    /**
     * @return float
     */
    public function getWatermarks(): float
    {
        return $this->watermarks;
    }

    /**
     * @return bool
     */
    public function hasAdult(): bool
    {
        return $this->adult > 0;
    }

    /**
     * @return bool
     */
    public function hasGruesome(): bool
    {
        return $this->gruesome > 0;
    }

    /**
     * @return bool
     */
    public function hasText(): bool
    {
        return $this->text > 0;
    }

    /**
     * @return bool
     */
    public function hasWatermarks(): bool
    {
        return $this->watermarks > 0;
    }
}
