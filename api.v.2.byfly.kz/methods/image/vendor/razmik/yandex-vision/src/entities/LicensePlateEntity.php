<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 30.10.2022
 * Time: 15:20
 */

namespace razmik\yandex_vision\entities;


/**
 * Распознанный регистрационный номер
 *
 * Class LicensePlateEntity
 * @package razmik\yandex_vision\models
 */
class LicensePlateEntity extends AbstractTextDetectionEntity
{
    /**
     * Регистрационный номер
     *
     * @var string|null
     */
    protected $text;

    /**
     * @inheritDoc
     * @param array $pageData
     */
    public function __construct(array $pageData)
    {
        parent::__construct([], $pageData);

        $lines = current($pageData['blocks'])['lines'];
        $words = current($lines)['words'];
        $this->text = current($words)['text'];
    }

    /**
     * @return string|null
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        return [
            'text' => $this->text,
        ];
    }
}
