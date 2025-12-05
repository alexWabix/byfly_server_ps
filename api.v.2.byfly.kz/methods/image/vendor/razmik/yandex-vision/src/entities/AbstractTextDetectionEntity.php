<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 30.10.2022
 * Time: 15:19
 */

namespace razmik\yandex_vision\entities;

/**
 * Распознанный документ
 *
 * Class AbstractTextDetectionEntity
 * @package razmik\yandex_vision\models
 */
abstract class AbstractTextDetectionEntity extends AbstractBaseEntity
{
    /**
     * Ширина страницы в пикселях
     *
     * @var string
     */
    protected $width;

    /**
     * Высота страницы в пикселях
     *
     * @var string
     */
    protected $height;

    /**
     * Распознанные блоки
     *
     * @var array
     */
    protected $blocks = [];

    /**
     * @param array $entities
     * @param array $properties
     */
    public function __construct(array $entities, array $properties)
    {
        parent::__construct($entities);
        $this->setProperties($properties);
    }

    /**
     * Установка параметров
     *
     * @param array $data
     * @return void
     */
    private function setProperties(array $data)
    {
        foreach ($data as $key => $value) {
            if (property_exists(static::class, $key) === true) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Распознанные блоки
     *
     * @return array
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * Ширина страницы в пикселях
     *
     * @return int
     */
    public function getWidth(): int
    {
        return (int)$this->width;
    }

    /**
     * Высота страницы в пикселях
     *
     * @return int
     */
    public function getHeight(): int
    {
        return (int)$this->height;
    }
}
