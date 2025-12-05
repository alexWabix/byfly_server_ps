<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 30.10.2022
 * Time: 15:20
 */

namespace razmik\yandex_vision\entities;


/**
 * Распознанный паспорт
 *
 * Class PassportEntity
 * @package razmik\yandex_vision\models
 */
class PassportEntity extends AbstractTextDetectionEntity
{
    /**
     * Имя
     *
     * @var string|null
     */
    protected $name;

    /**
     * Отчество
     *
     * @var string|null
     */
    protected $middle_name;

    /**
     * Фамилия
     *
     * @var string|null
     */
    protected $surname;

    /**
     * Пол
     *
     * @var string|null
     */
    protected $gender;

    /**
     * Гражданство
     *
     * @var string|null
     */
    protected $citizenship;

    /**
     * Дата рождения
     *
     * @var string|null
     */
    protected $birth_date;

    /**
     * Место рождения
     *
     * @var string|null
     */
    protected $birth_place;

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
     * Выдан
     *
     * @var string|null
     */
    protected $issue_by;

    /**
     * Код подразделения
     *
     * @var string|null
     */
    protected $subdivision;

    /**
     * Дата окончания срока действия
     *
     * @var string|null
     */
    protected $expiration_date;

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
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getMiddleName(): ?string
    {
        return $this->middle_name;
    }

    /**
     * @return string|null
     */
    public function getSurname(): ?string
    {
        return $this->surname;
    }

    /**
     * @return string|null
     */
    public function getGender(): ?string
    {
        return $this->gender;
    }

    /**
     * @return string|null
     */
    public function getCitizenship(): ?string
    {
        return $this->citizenship;
    }

    /**
     * @return string|null
     */
    public function getBirthDate(): ?string
    {
        return $this->birth_date;
    }

    /**
     * @return string|null
     */
    public function getBirthPlace(): ?string
    {
        return $this->birth_place;
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
    public function getSubdivision(): ?string
    {
        return $this->subdivision;
    }

    /**
     * @return string|null
     */
    public function getExpirationDate(): ?string
    {
        return $this->expiration_date;
    }

    /**
     * @return string|null
     */
    public function getIssueBy(): ?string
    {
        return $this->issue_by;
    }
}
