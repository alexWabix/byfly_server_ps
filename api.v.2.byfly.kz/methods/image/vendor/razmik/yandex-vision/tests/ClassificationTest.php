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
use razmik\yandex_vision\models\ClassificationModelInterface;
use razmik\yandex_vision\models\ModerationModel;
use razmik\yandex_vision\models\QualityModel;
use razmik\yandex_vision\responses\AbstractResponse;
use razmik\yandex_vision\responses\DataResponse;
use razmik\yandex_vision\storages\IAMTokenStorageInterface;
use razmik\yandex_vision\YandexVision;

/**
 * Тест классификации изображений
 *
 * Class ClassificationTest
 */
class ClassificationTest extends TestCase
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
     * @param ClassificationModelInterface $model
     * @param array $actual
     * @return void
     * @throws YandexVisionAuthException
     * @throws YandexVisionRequestException
     * @throws YandexVisionIAMTokenException
     */
    public function testShouldBeSuccess(string $response, ClassificationModelInterface $model, array $actual)
    {
        $this
            ->apiClient
            ->method('classification')
            ->willReturn(
                new DataResponse(
                    AbstractResponse::HTTP_SUCCESS_CODE,
                    json_decode($response, true)
                )
            );

        $yandexVision = new YandexVision($this->apiClient);
        $yandexVision->setIamTokenStorage($this->storage);
        $response = $yandexVision->getClassifiedProperties($this->document, $model);

        $this::assertEquals($actual, $response->asArray());
    }

    /**
     * Корректные данные
     *
     * @return array
     */
    public function dataProvider(): array
    {
        $responses = require __DIR__ . '/data/fixtures/classification_response.php';
        $qualityModel = new QualityModel();
        $moderationModel = new ModerationModel();

        return [
            [
                $responses['moderation1'],
                $moderationModel,
                [
                    "adult" => 0.00024185575603041798,
                    "gruesome" => 3.7400691326183733e-6,
                    "text" => 0.46676623821258545,
                    "watermarks" => 0.043129432946443558,
                ],
            ], [
                $responses['moderation2'],
                $moderationModel,
                [
                    "adult" => 0.00034571430296637118,
                    "gruesome" => 0.00085600418969988823,
                    "text" => 0.9933089017868042,
                    "watermarks" => 0.015746129676699638,
                ],
            ], [
                $responses['quality1'],
                $qualityModel,
                [
                    "high" => 0.0063949818722903728,
                    "low" => 0.11017455905675888,
                    "medium" => 0.88343048095703125,
                ],
            ], [
                $responses['quality2'],
                $qualityModel,
                [
                    "high" => 0.0263508353382349,
                    "low" => 0.16949066519737244,
                    "medium" => 0.80415844917297363,
                ]
            ],
        ];
    }
}
