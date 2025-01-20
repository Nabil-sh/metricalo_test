<?php

namespace App\Service\Payment\Provider;

use App\Service\Payment\Exception\PaymentProviderException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Shift4PaymentProvider implements PaymentProviderInterface
{

    public function __construct(
        private HttpClientInterface $httpClient
    ) {
    }

    public function getProviderShortName(): string
    {
        return 'shift4';
    }

    /**
     * @param array $data
     * @return array
     * @throws PaymentProviderException
     */
    public function processPayment(array $data): array
    {
        $payLoad = [
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'card' => [
                'number' => $data['card_number'],
                'expMonth' => $data['exp_month'],
                'expYear' => $data['exp_year'],
                'cvc' => $data['cvc']
            ]
        ];


        try {
            $response = $this->httpClient->request('POST', 'https://api.shift4.com/charges', [
                'auth_basic' => ['sk_test_dUD9bnFjbK1FSL7t6yfE8l31', ''],
                'json' => $payLoad
            ]);
            if ($response->getStatusCode() >= Response::HTTP_BAD_REQUEST) {
                $response->toArray(false);
                if (isset($responseContent['error'])) {
                    throw new PaymentProviderException(sprintf(
                        'Payment API error: %s (%s)',
                        $responseContent['error']['message'],
                        $responseContent['error']['type']
                    ));
                }
                throw new PaymentProviderException('Unexpected error from payment provider.');
            }
            $responseContent = $response->toArray();
            return $this->mapContentToUnifiedResponse($responseContent);
        } catch (ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            throw new PaymentProviderException('Network or parsing error:' . $e->getMessage(), $e->getCode(), $e);
        }

    }


    /**
     * @param array $responseContent
     * @return array
     */
    public function mapContentToUnifiedResponse(array $responseContent): array
    {
        return [
            'transactionID' => $responseContent['id'],
            'createdDate' => (new \DateTime())->setTimestamp($responseContent['created'])->format('Y-m-d H:i:s'),
            'amount' => $responseContent['amount'],
            'currency' => $responseContent['currency'],
            'cardBin' => $responseContent['card']['first6'],
        ];
    }
}