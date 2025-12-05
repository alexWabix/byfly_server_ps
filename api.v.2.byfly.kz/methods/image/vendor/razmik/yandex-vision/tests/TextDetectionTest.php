<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 04.11.2022
 * Time: 17:24
 */

use PHPUnit\Framework\TestCase;
use razmik\yandex_vision\clients\YandexVisionApiClientInterface;
use razmik\yandex_vision\documents\AbstractDocument;
use razmik\yandex_vision\exceptions\YandexVisionAuthException;
use razmik\yandex_vision\exceptions\YandexVisionRequestException;
use razmik\yandex_vision\exceptions\YandexVisionIAMTokenException;
use razmik\yandex_vision\IAMToken;
use razmik\yandex_vision\models\DriverLicenseBackModel;
use razmik\yandex_vision\models\DriverLicenseFrontModel;
use razmik\yandex_vision\models\LicensePlateModel;
use razmik\yandex_vision\models\PassportModel;
use razmik\yandex_vision\models\TextDetectionModelInterface;
use razmik\yandex_vision\responses\AbstractResponse;
use razmik\yandex_vision\responses\DataResponse;
use razmik\yandex_vision\storages\IAMTokenStorageInterface;
use razmik\yandex_vision\YandexVision;

/**
 * Распознавание текста
 *
 * Class TextDetectionTest
 */
class TextDetectionTest extends TestCase
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
     * Проверка корректного распознания
     * @dataProvider dataProvider
     *
     * @param string $response
     * @param TextDetectionModelInterface $model
     * @param array $actual
     * @return void
     * @throws YandexVisionAuthException
     * @throws YandexVisionRequestException
     * @throws YandexVisionIAMTokenException
     */
    public function testShouldBeRecognized(string $response, TextDetectionModelInterface $model, array $actual)
    {
        $this
            ->apiClient
            ->method('textDetection')
            ->willReturn(
                new DataResponse(
                    AbstractResponse::HTTP_SUCCESS_CODE,
                    json_decode($response, true)
                )
            );

        $yandexVision = new YandexVision($this->apiClient);
        $yandexVision->setIamTokenStorage($this->storage);
        $responses = $yandexVision->getDetectedText($this->document, $model);

        $this::assertCount(1, $responses);

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
        $responses = require __DIR__ . '/data/fixtures/text_detection_responses.php';
        $passportModel = new PassportModel();
        $driverLicenseFrontModel = new DriverLicenseFrontModel();
        $driverLicenseBackModel = new DriverLicenseBackModel();
        $licensePlateModel = new LicensePlateModel(['ru']);

        return [
            [
                $responses['passportStark'],
                $passportModel,
                [
                    "expiration_date" => null,
                    "citizenship" => "rus",
                    "gender" => "жен",
                    "issue_date" => "12.08.2013",
                    "name" => "санса",
                    "middle_name" => "эддардовна",
                    "birth_date" => "04.06.1993",
                    "birth_place" => "гор. винтерфелл",
                    "number" => "7512221182",
                ],
            ], [
                $responses['passportLannister'],
                $passportModel,
                [
                    "gender" => "муж",
                    "citizenship" => "rus",
                    "expiration_date" => NULL,
                    "issue_date" => "17.12.2004",
                    "subdivision" => "292-004",
                    "surname" => "ланнистер",
                    "name" => "тирион",
                    "middle_name" => "тайвинович",
                    "birth_date" => "12.09.1982",
                    "birth_place" => "западные земли ущелье кастерли",
                ],
            ], [
                $responses['driverLicenseBack'],
                $driverLicenseBackModel,
                [
                    "expiration_date" => "01.04.2024",
                    "prev_number" => NULL,
                    "experience_from" => "1995",
                    "issue_date" => "18.05.1995",
                    "number" => "6315979887",
                ],
            ], [
                $responses['license_plate'],
                $licensePlateModel,
                [
                    "text" => "C999ET14",
                ],
            ],
        ];
    }
}
