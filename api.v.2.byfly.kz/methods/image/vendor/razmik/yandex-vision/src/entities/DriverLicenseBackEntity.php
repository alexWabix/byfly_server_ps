<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 30.10.2022
 * Time: 15:20
 */

namespace razmik\yandex_vision\entities;

/**
 * Распознанное водительское удостоверение, обратная сторона
 *
 * Class DriverLicenseBackRecognized
 * @package razmik\yandex_vision\models
 */
class DriverLicenseBackEntity extends AbstractTextDetectionEntity
{
    /**
     * Водительский стаж (с какого года)
     *
     * @var string|null
     */
    protected $experience_from;

    /**
     * Номер предыдущего водительского удостоверения
     *
     * @var string|null
     */
    protected $prev_number;

    /**
     * Номер паспорта
     *
     * @var string|null
     */
    protected $number;

    /**
     * Дата выдачи
     *
     * @var string|null
     */
    protected $issue_date;

    /**
     * Дата окончания срока действия
     *
     * @var string|null
     */
    protected $expiration_date;

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
     * @inheritDoc
     * @param array $pageData
     */
    public function __construct(array $pageData)
    {
        $entities = $pageData['entities'];
        parent::__construct($entities, $pageData);
    }

    /**
     * @return string|null
     */
    public function getExperienceFrom(): ?string
    {
        return $this->experience_from;
    }

    /**
     * @return string|null
     */
    public function getPrevNumber(): ?string
    {
        return $this->prev_number;
    }

    /**
     * @return string|null
     */
    public function getNumber(): ?string
    {
        return $this->number;
    }

    /**
     * @return string|null
     */
    public function getIssueDate(): ?string
    {
        return $this->issue_date;
    }

    /**
     * @return string|null
     */
    public function getExpirationDate(): ?string
    {
        return $this->expiration_date;
    }

    /**
     * @inheritDoc
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * @inheritDoc
     */
    public function getWidth(): int
    {
        return (int)$this->width;
    }

    /**
     * @inheritDoc
     */
    public function getHeight(): int
    {
        return (int)$this->height;
    }
}
