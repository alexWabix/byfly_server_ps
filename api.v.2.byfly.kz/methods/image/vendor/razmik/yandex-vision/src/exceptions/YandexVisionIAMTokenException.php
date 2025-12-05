<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 28.10.2022
 * Time: 19:21
 */

namespace razmik\yandex_vision\exceptions;

use Exception;
use Throwable;

/**
 * Ошибка работы с IAM токеном
 *
 * Class YandexVisionIAMTokenException
 * @package razmik\yandex_vision\exceptions
 */
class YandexVisionIAMTokenException extends Exception implements YandexVisionExceptionInterface
{
    /**
     * @inheritDoc
     */
    public function __construct(string $message, Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
