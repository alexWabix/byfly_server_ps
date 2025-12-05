<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 28.10.2022
 * Time: 20:15
 */

namespace razmik\yandex_vision;

/**
 * IAM токен авторизации
 *
 * Class IAMTokenResponse
 * @package razmik\yandex_vision\responses
 */
class IAMToken
{
    /** @var int */
    public static $expiredAt = 8 * 60 * 60;

    /**
     * Токен
     *
     * @var string
     */
    private $token;

    /**
     * Дата создания
     *
     * @var int
     */
    private $createdAt;

    /**
     * @param string $token
     * @param int $createdAt
     */
    public function __construct(string $token, int $createdAt)
    {
        $this->token = $token;
        $this->createdAt = $createdAt;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return int
     */
    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    /**
     * Токен просрочен
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->createdAt + $this::$expiredAt <= time();
    }
}
