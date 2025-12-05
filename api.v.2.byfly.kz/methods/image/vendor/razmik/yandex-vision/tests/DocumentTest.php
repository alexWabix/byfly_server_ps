<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 30.10.2022
 * Time: 21:02
 */

use PHPUnit\Framework\TestCase;
use razmik\yandex_vision\documents\ImageDocument;
use razmik\yandex_vision\exceptions\YandexVisionDocumentException;

/**
 * Тест документа
 *
 * Class DocumentTest
 */
class DocumentTest extends TestCase
{
    /**
     * Ошибка, если файл большой
     *
     * @return void
     */
    public function testShouldFailureBySize()
    {
        $this->expectException(YandexVisionDocumentException::class);
        new ImageDocument(__DIR__ . '/data/test1.jpg');
    }

    /**
     * Ошибка, если файл не того типа
     *
     * @return void
     * @throws YandexVisionDocumentException
     */
    public function testShouldFailureByType()
    {
        $this->expectException(YandexVisionDocumentException::class);
        new ImageDocument(__DIR__ . '/data/test2.txt');
    }

    /**
     * Ошибка, если файл не найден
     *
     * @return void
     * @throws YandexVisionDocumentException
     */
    public function testShouldNotFound()
    {
        $this->expectException(YandexVisionDocumentException::class);
        new ImageDocument(sys_get_temp_dir() . '/none.jpg');
    }
}
