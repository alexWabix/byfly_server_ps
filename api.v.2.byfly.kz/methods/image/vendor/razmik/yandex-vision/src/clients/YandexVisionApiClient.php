<?php
/**
 * Created by PhpStorm.
 * User: itily
 * Date: 28.10.2022
 * Time: 17:36
 */

namespace razmik\yandex_vision\clients;

use razmik\yandex_vision\IAMToken;
use razmik\yandex_vision\requests\ClassificationRequest;
use razmik\yandex_vision\requests\FaceDetectionRequest;
use razmik\yandex_vision\requests\RequestFactory;
use razmik\yandex_vision\requests\RequestInterface;
use razmik\yandex_vision\requests\TextDetectionRequest;
use razmik\yandex_vision\responses\AbstractResponse;
use razmik\yandex_vision\responses\DataResponse;
use razmik\yandex_vision\responses\IAMTokenResponse;
use razmik\yandex_vision\responses\ResponseFactory;

/**
 * Клиент работы с API по умолчанию
 *
 * Class YandexVisionApiClient
 * @package razmik\yandex_vision\clients
 */
class YandexVisionApiClient extends AbstractYandexVisionApiClient
{
    /**
     * @inheritDoc
     */
    public function textDetection(IAMToken $IAMToken, TextDetectionRequest $request): AbstractResponse
    {
        return $this->sendBatchAnalyze($IAMToken, $request);
    }

    /**
     * @inheritDoc
     */
    public function classification(IAMToken $IAMToken, ClassificationRequest $request): AbstractResponse
    {
        return $this->sendBatchAnalyze($IAMToken, $request);
    }

    /**
     * @inheritDoc
     */
    public function faceDetection(IAMToken $IAMToken, FaceDetectionRequest $request): AbstractResponse
    {
        return $this->sendBatchAnalyze($IAMToken, $request);
    }

    /**
     * @inheritDoc
     */
    public function fetchIAMToken(): AbstractResponse
    {
        $url = rtrim(self::API_IAM_HOST, '/') . '/tokens';
        $body = [
            'yandexPassportOauthToken' => $this->oathToken,
        ];
        $response = $this->sendPostRequest($url, $body);

        if ($response->isSuccess() === false) {
            return $response;
        }
        $data = $response->getData();

        return new IAMTokenResponse($data['iamToken'], $data['expiresAt']);
    }

    /**
     * Отправка документа на анализ
     *
     * @param IAMToken $IAMToken
     * @param RequestInterface $request
     * @return AbstractResponse
     */
    private function sendBatchAnalyze(IAMToken $IAMToken, RequestInterface $request): AbstractResponse
    {
        $url = rtrim(self::API_VISION_HOST, '/') . '/batchAnalyze';
        $body = RequestFactory::create($this->folderId, $request);
        $header = [
            "Authorization: Bearer {$IAMToken->getToken()}",
        ];

        return $this->sendPostRequest($url, $body, $header);
    }

    /**
     * Отправка POST запроса
     *
     * @param string $url
     * @param array $body
     * @param array $headers
     * @return DataResponse
     */
    private function sendPostRequest(string $url, array $body, array $headers = []): AbstractResponse
    {
        $headers = array_merge($headers, [
            'Content-Type: application/json',
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_values($headers));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $html = curl_exec($ch);
        $data = json_decode($html, true);

        return ResponseFactory::createByHttpCode(
            curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
            $data !== null ? $data : [$html]
        );
    }
}
