<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 04.11.2022
 * Time: 16:13
 */

namespace razmik\yandex_vision\entities;

/**
 * Сущность обнаруженного лица
 *
 * Class FaceDetectionEntity
 * @package razmik\yandex_vision\entities
 */
class FaceDetectionEntity extends AbstractBaseEntity
{
    /**
     * Координата 1
     *
     * @var array
     */
    protected $leftTop = [];

    /**
     * Координата 2
     *
     * @var array
     */
    protected $leftBottom = [];

    /**
     * Координата 3
     *
     * @var array
     */
    protected $rightBottom = [];

    /**
     * Координата 4
     *
     * @var array
     */
    protected $rightTop = [];

    /**
     * @inheritDoc
     */
    public function __construct(array $data)
    {
        parent::__construct([]);

        list(
            $this->leftTop,
            $this->leftBottom,
            $this->rightBottom,
            $this->rightTop
            ) = $data;
    }

    /**
     * @inheritDoc
     */
    public function asArray(): array
    {
        return [
            'leftTop' => $this->leftTop,
            'leftBottom' => $this->leftBottom,
            'rightBottom' => $this->rightBottom,
            'rightTop' => $this->rightTop
        ];
    }

    /**
     * @return array
     */
    public function getLeftTop(): array
    {
        return $this->leftTop;
    }

    /**
     * @return array
     */
    public function getLeftBottom(): array
    {
        return $this->leftBottom;
    }

    /**
     * @return array
     */
    public function getRightBottom(): array
    {
        return $this->rightBottom;
    }

    /**
     * @return array
     */
    public function getRightTop(): array
    {
        return $this->rightTop;
    }
}
