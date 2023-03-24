<?php

namespace Boodil\Payment\Api;

interface BoodilApiInterface
{
    const SANDBOX_URL = "https://api-test.boodil.com/api/v1/";
    const PRODUCTION_URL = "https://api.boodil.com/api/v1/";
    const XML_PATH_BOODIL_UUID = "payment/boodil/uuid";
    const XML_PATH_BOODIL_ENVIRONMENT = "payment/boodil/environment";
    const XML_PATH_BOODIL_LOGO = "payment/boodil/logo";
    const XML_PATH_BOODIL_SUCCESS_URL = "payment/boodil/success_url";
    const XML_PATH_BOODIL_USERNAME = "payment/boodil/username";
    const XML_PATH_BOODIL_ACCESS_KEY = "payment/boodil/access_key";
    const XML_PATH_IS_ACTIVE_SCOPE = 'payment/boodil/active';

    /**
     * @return mixed
     */
    public function isActive();

    /**
     * @return string
     */
    public function getApiUrl($name);

    /**
     * @return string
     */
    public function getMerchantUuid();

    /**
     * @return string
     */
    public function getEnvironment();

    /**
     * @return string
     */
    public function getLogo();

    /**
     * @return array
     */
    public function getAuthHeaders();

    /**
     * @return string
     */
    public function getSuccessUrl();

    /**
     * @param $url
     * @param array $params
     * @param string $method
     * @param array $headers
     * @param bool $assoc
     * @param bool $statusCode
     * @return mixed
     */
    public function callCurl($url, $params = [], $method = 'GET', $headers = [], $assoc = false, $statusCode = false);

    /**
     * @return mixed
     */
    public function getPaymentStatusAPI();

    /**
     * @param $id
     * @return \Boodil\Payment\Model\TransactionsFactory
     */
    public function getTransaction($id);

    /**
     * @return mixed
     */
    public function getOrderCollection();

}
