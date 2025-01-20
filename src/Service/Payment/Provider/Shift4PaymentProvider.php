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

//Visa	4263982640269299	02/2026	837		Success

/*
 * array:15 [▼
  "id" => "char_kcKIaBZ4fCQjWRBuvoXGAGpu"
  "created" => 1737316882
  "objectType" => "charge"
  "merchant" => "mrc_Qswts7cgjkDCE7sLs0xNP03b"
  "amount" => 100
  "amountRefunded" => 0
  "currency" => "AED"
  "card" => array:12 [▼
    "id" => "card_ipTs4cn8kH62xlLfaPkHlwXb"
    "created" => 1737316882
    "objectType" => "card"
    "first6" => "426398"
    "last4" => "9299"
    "fingerprint" => "PGNFB2UbAx1SVfPc"
    "expMonth" => "02"
    "expYear" => "2026"
    "brand" => "Visa"
    "type" => "Debit Card"
    "country" => "US"
    "issuer" => "SHIFT4 TEST"
  ]
  "captured" => true
  "refunded" => false
  "disputed" => false
  "fraudDetails" => array:1 [▼
    "status" => "in_progress"
  ]
  "avsCheck" => array:1 [▼
    "result" => "unavailable"
  ]
  "status" => "successful"
  "clientObjectId" => "client_char_0zNfV89LEW7Hcc7NuxDn53KS"
]
 */