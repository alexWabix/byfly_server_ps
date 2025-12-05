<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 28.10.2022
 * Time: 17:21
 */

namespace razmik\yandex_vision\models;

/**
 * Базовая модель документа на распознание
 *
 * Class AbstractTextDetectionModel
 * @package razmik\yandex_vision\models
 */
abstract class AbstractTextDetectionModel
{
    /**
     * Язык
     *
     * @var string[]
     */
    private $languages;

    /**
     * @param array $languages
     */
    public function __construct(array $languages = ['*'])
    {
        $this->languages = $languages;
    }

    /**
     * @return string[]
     */
    public function getLanguages(): array
    {
        return $this->languages;
    }
}
