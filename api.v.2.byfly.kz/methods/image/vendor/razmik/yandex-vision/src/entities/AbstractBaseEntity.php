<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 30.10.2022
 * Time: 15:19
 */

namespace razmik\yandex_vision\entities;

/**
 * Базовая сущность
 *
 * Class AbstractBaseEntity
 * @package razmik\yandex_vision\models
 */
abstract class AbstractBaseEntity
{
    /**
     * Данные сущности
     *
     * @var array
     */
    private $entityMap = [];

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->setEntityData($data);
    }

    /**
     * Данные сущности в массив
     *
     * @return array
     */
    public function asArray(): array
    {
        return $this->entityMap;
    }

    /**
     * Установка параметров сущности
     *
     * @param array $data
     * @return void
     */
    private function setEntityData(array $data)
    {
        array_walk($data, function (array $datum) {
            list($key, $value) = array_values($datum);
            $value = $value === '-' ? null : $value;

            if (property_exists(static::class, $key) === true) {
                $this->$key = $value;
                $this->entityMap[$key] = $value;
            }
        });
    }
}
