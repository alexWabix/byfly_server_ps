<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 03.11.2022
 * Time: 17:49
 */

namespace razmik\yandex_vision\responses;

/**
 * Неудачный ответ
 *
 * Class FailureResponse
 * @package razmik\yandex_vision\responses
 */
class FailureResponse extends AbstractResponse
{
    /**
     * Код ошибки
     *
     * @var int
     */
    private $code = 0;

    /**
     * Текст ошибки
     *
     * @var string
     */
    private $message = '';

    /**
     * @inheritDoc
     * @param array $data
     */
    public function __construct(int $httpCode, array $data = [])
    {
        parent::__construct($httpCode);
        $this->initError($data);
    }

    /**
     * Инициализация ошибки
     *
     * @param array $data
     * @return void
     */
    private function initError(array $data)
    {
        if (isset($data['code']) === true) {
            $this->code = (int)$data['code'];
        }

        if (isset($data['message']) === true) {
            $this->message = $data['message'];
        }
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}
