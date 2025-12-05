<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 30.10.2022
 * Time: 20:39
 */

use PHPUnit\Framework\TestCase;
use razmik\yandex_vision\clients\YandexVisionApiClientInterface;
use razmik\yandex_vision\documents\AbstractDocument;
use razmik\yandex_vision\exceptions\YandexVisionAuthException;
use razmik\yandex_vision\exceptions\YandexVisionRequestException;
use razmik\yandex_vision\exceptions\YandexVisionIAMTokenException;
use razmik\yandex_vision\IAMToken;
use razmik\yandex_vision\IAMTokenRequestAuth;
use razmik\yandex_vision\responses\DataResponse;
use razmik\yandex_vision\responses\FailureResponse;
use razmik\yandex_vision\responses\IAMTokenResponse;
use razmik\yandex_vision\storages\IAMTokenStorageInterface;

/**
 * Тестирование запроса токена авторизации
 *
 * Class IAMTokenRequestAuthTest
 */
class IAMTokenRequestAuthTest extends TestCase
{
    /**
     * @var YandexVisionApiClientInterface
     */
    private $apiClient;

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
     * Проверка обновления токена в хранилище
     *
     * @return void
     * @throws YandexVisionAuthException
     * @throws YandexVisionRequestException
     * @throws YandexVisionIAMTokenException
     */
    public function testShouldSaveToken()
    {
        $dateAt = time();

        $this
            ->apiClient
            ->method('fetchIAMToken')
            ->willReturn(
                new IAMTokenResponse('test', date('Y-m-d H:i:s', $dateAt))
            );

        $this
            ->storage
            ->expects($this->at(0))
            ->method('loadToken')
            ->willReturn(null);
        $this
            ->storage
            ->expects($this->any())
            ->method('loadToken')
            ->willReturn(new IAMToken('test', $dateAt));
        $this
            ->storage
            ->expects($this->once())
            ->method('saveToken')
            ->with('test');

        $IAMTokenProxy = new IAMTokenRequestAuth($this->storage, $this->apiClient);
        $IAMTokenProxy->send(function () {
            return new DataResponse(200);
        });
    }

    /**
     * Токен не должен запрашиваться, если был найден в хранилище
     *
     * @return void
     * @throws YandexVisionAuthException
     * @throws YandexVisionRequestException
     * @throws YandexVisionIAMTokenException
     */
    public function testShouldNotRefreshToken()
    {
        $dateAt = time();

        $this
            ->apiClient
            ->expects($this->never())
            ->method('fetchIAMToken');

        $this
            ->storage
            ->expects($this->any())
            ->method('loadToken')
            ->willReturn(new IAMToken('test', $dateAt));
        $this
            ->storage
            ->expects($this->never())
            ->method('saveToken');

        $IAMTokenProxy = new IAMTokenRequestAuth($this->storage, $this->apiClient);
        $IAMTokenProxy->send(function () {
            return new DataResponse(200);
        });
    }

    /**
     * Ошибка сохранения токена в хранилище
     *
     * @return void
     * @throws YandexVisionAuthException
     * @throws YandexVisionRequestException
     * @throws YandexVisionIAMTokenException
     */
    public function testExpectStorageError()
    {
        $dateAt = time();
        $this->expectException(YandexVisionIAMTokenException::class);

        $this
            ->apiClient
            ->method('fetchIAMToken')
            ->willReturn(
                new IAMTokenResponse('test', date('Y-m-d H:i:s', $dateAt))
            );

        $this
            ->storage
            ->expects($this->any())
            ->method('loadToken')
            ->willReturn(null);

        $IAMTokenProxy = new IAMTokenRequestAuth($this->storage, $this->apiClient);
        $IAMTokenProxy->send(function () {
            return new DataResponse(200);
        });
    }

    /**
     * Ошибка получения токена
     *
     * @return void
     * @throws YandexVisionAuthException
     * @throws YandexVisionRequestException
     * @throws YandexVisionIAMTokenException
     */
    public function testExpectRequestError()
    {
        $this->expectException(YandexVisionRequestException::class);

        $this
            ->apiClient
            ->method('fetchIAMToken')
            ->willReturn(new FailureResponse(500, []));

        $this
            ->storage
            ->expects($this->any())
            ->method('loadToken')
            ->willReturn(null);

        $IAMTokenProxy = new IAMTokenRequestAuth($this->storage, $this->apiClient);
        $IAMTokenProxy->send(function () {
            return new DataResponse(200);
        });
    }
}
