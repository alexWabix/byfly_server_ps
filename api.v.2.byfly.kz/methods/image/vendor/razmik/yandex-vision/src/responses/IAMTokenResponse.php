<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 30.10.2022
 * Time: 13:13
 */

namespace razmik\yandex_vision\responses;

/**
 * Ответ при получении токена авторизации
 *
 * Class IAMTokenResponse
 * @package razmik\yandex_vision\responses
 */
class IAMTokenResponse extends AbstractResponse
{
    /**
     * Токен
     *
     * @var string
     */
    private $iamToken;

    /**
     * Дата завершения
     *
     * @var string
     */
    private $expires;

    /**
     * @inheritDoc
     * @param array $iamToken
     */
    public function __construct(string $iamToken, string $expires)
    {
        parent::__construct(self::HTTP_SUCCESS_CODE);

        $this->iamToken = $iamToken;
        $this->expires = $expires;
    }

    /**
     * @return string
     */
    public function getIamToken(): string
    {
        return $this->iamToken;
    }

    /**
     * @return string
     */
    public function getExpires(): string
    {
        return $this->expires;
    }
}
