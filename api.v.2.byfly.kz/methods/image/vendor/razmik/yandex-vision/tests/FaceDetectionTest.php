<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 05.11.2022
 * Time: 16:21
 */

use PHPUnit\Framework\TestCase;
use razmik\yandex_vision\clients\YandexVisionApiClientInterface;
use razmik\yandex_vision\documents\AbstractDocument;
use razmik\yandex_vision\exceptions\YandexVisionAuthException;
use razmik\yandex_vision\exceptions\YandexVisionRequestException;
use razmik\yandex_vision\exceptions\YandexVisionIAMTokenException;
use razmik\yandex_vision\IAMToken;
use razmik\yandex_vision\models\FaceDetectionModel;
use razmik\yandex_vision\models\FaceDetectionModelInterface;
use razmik\yandex_vision\responses\AbstractResponse;
use razmik\yandex_vision\responses\DataResponse;
use razmik\yandex_vision\storages\IAMTokenStorageInterface;
use razmik\yandex_vision\YandexVision;

/**
 * Тест обнаружение лиц на изображении
 *
 * Class FaceDetectionTest
 */
class FaceDetectionTest extends TestCase
{
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->apiClient = $this
            ->getMockBuilder(YandexVisionApiClientInterface::class)
            ->getMock();

        $this->storage = $this
            ->getMockBuilder(IAMTokenStorageInterface::class)
            ->getMock();
        $this
            ->storage
            ->expects($this->any())
            ->method('loadToken')
            ->willReturn(new IAMToken('', time()));

        $this->document = $this
            ->getMockBuilder(AbstractDocument::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this
            ->document
            ->method('getBase64Content')
            ->willReturn('');
    }

    /**
     * @dataProvider dataProvider
     *
     * @param string $response
     * @param FaceDetectionModelInterface $model
     * @param array $actual
     * @return void
     * @throws YandexVisionAuthException
     * @throws YandexVisionRequestException
     * @throws YandexVisionIAMTokenException
     */
    public function testShouldBeSuccess(string $response, FaceDetectionModelInterface $model, array $actual)
    {
        $this
            ->apiClient
            ->method('faceDetection')
            ->willReturn(
                new DataResponse(
                    AbstractResponse::HTTP_SUCCESS_CODE,
                    json_decode($response, true)
                )
            );

        $yandexVision = new YandexVision($this->apiClient);
        $yandexVision->setIamTokenStorage($this->storage);
        $responses = $yandexVision->getFaceCoordinates($this->document, $model);

        $this::assertCount(3, $responses);

        $response = current($responses);
        $this::assertEquals($actual, $response->asArray());
    }

    /**
     * Корректные данные
     *
     * @return array
     */
    public function dataProvider(): array
    {
        $responses = require __DIR__ . '/data/fixtures/face_detection_responses.php';
        $faceDetectionModel = new FaceDetectionModel();

        return [
            [
                $responses['faceDetection'],
                $faceDetectionModel,
                [
                    "leftTop" => [
                        "x" => "159",
                        "y" => "162",
                    ],
                    "leftBottom" => [
                        "x" => "159",
                        "y" => "359",
                    ],
                    "rightBottom" => [
                        "x" => "356",
                        "y" => "359",
                    ],
                    "rightTop" => [
                        "x" => "356",
                        "y" => "162",
                    ],
                ],
            ],
        ];
    }
}
