<?php
/**
 * Till Payments Abstract Request
 */

namespace Omnipay\TillPayments\Message;

use Omnipay\Common\CreditCard;
use Omnipay\TillPayments\Customer;

/**
 * Till Abstract Status Request
 *
 * This class forms the base class for all requests that involve pulling transaction status from till payments
 *
 * @link https://gateway.tillpayments.com/documentation/apiv3
 */
abstract class AbstractStatusRequest extends AbstractRequest
{

    /**
     * @return mixed
     */
    public function getMerchantTransactionId()
    {
        return $this->getParameter('merchantTransactionId');
    }

    /**
     * @param $value
     * @return \Omnipay\Common\Message\AbstractRequest
     */
    public function setMerchantTransactionId($value)
    {
        return $this->setParameter('merchantTransactionId', $value);
    }

    /**
     * @return mixed
     */
    public function getUuid()
    {
        return $this->getParameter('uuid');
    }

    /**
     * @param $value
     * @return \Omnipay\Common\Message\AbstractRequest
     */
    public function setUuid($value)
    {
        return $this->setParameter('uuid', $value);
    }

    /**
     * This base data is always there on every transaction request payload
     *
     * @return array
     */
    protected function getBaseData()
    {
        $data = array();

        return $data;
    }

    /**
     * @param mixed $data
     * @return \Omnipay\Common\Message\ResponseInterface|Response
     * @throws \Exception
     */
    public function sendData($data)
    {
        $jsonBody = json_encode($data);

        // This request uses the REST endpoint and requires the JSON content type header
        $httpResponse = $this->httpClient->request('GET', $this->getEndpoint(), $this->buildHeaders($jsonBody));

        return $this->response = new TransactionStatusResponse($this, json_decode($httpResponse->getBody()->getContents(), true));
    }

    /**
     * Generate additional headers that are used when sending Till Payments
     *
     * @return array
     * @throws \Exception
     */
    protected function buildHeaders()
    {
        $url = $this->getEndpoint();

        $timestamp = (new \DateTime('now', new \DateTimeZone('UTC')))->format('D, d M Y H:i:s T');

        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);
        $anchor = parse_url($url, PHP_URL_FRAGMENT);

        $requestUri = $path . ($query ? '?' . $query : '') . ($anchor ? '#' . $anchor : '');

        $contentType = 'application/json';
        $hashedJsonBody = hash('sha512', '');

        $parts = array('GET', $hashedJsonBody, $contentType, $timestamp, $requestUri);

        $str = join("\n", $parts);
        $digest = hash_hmac('sha512', $str, $this->getSecretKey(), true);
        $signature = base64_encode($digest);

        $headers = array(
            'Date' => $timestamp,
            'X-Date' => $timestamp,
            'X-Signature' => $signature,
            'Content-Type' => $contentType,
            'Accept' => $contentType,
            'Authorization' => "Basic " . base64_encode($this->getUsername() . ":" . $this->getPassword()),
        );

        return $headers;
    }

    /**
     * Get the base endpoint + API key
     *
     * @return string
     */
    public function getEndpoint()
    {
        return $this->getEndpointBase() . 'status/' . $this->getApiKey() . '/';
    }

}
