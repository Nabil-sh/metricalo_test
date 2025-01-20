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

class ACIPaymentProvider implements PaymentProviderInterface
{


    public function __construct(
        private HttpClientInterface $httpClient
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getProviderShortName(): string
    {
        return 'aci';
    }

    /**
     * @param array $data
     * @return array
     * @throws PaymentProviderException
     */
    public function processPayment(array $data): array
    {
        $payload = [
            'amount' => $data['amount'],
            'currency' => $data['currency'], // EUR
            'paymentBrand' => 'VISA',
            'card' => [
                'number' => $data['card_number'],
                'expiryMonth' => $data['exp_month'],
                'expiryYear' => $data['exp_year'],
                'cvv' => $data['cvc']
            ]
        ];

        try {
            $response = $this->httpClient->request('POST', 'https://eu-test.oppwa.com/v1/payments', [
                'headers' => [
                    'Authorization' => 'Bearer ACI_ACCESS_TOKEN', // TODO: Replace ACI_ACCESS_TOKEN with the actual token
                ],
                'json' => $payload
            ]);
            if ($response->getStatusCode() >= Response::HTTP_BAD_REQUEST) {
                $response->toArray(false);
                if (isset($responseContent['result']) && $responseContent['result']['code'] !== '000.100.110') {
                    $errorMessage = $this->formatErrorMessage($responseContent);
                    throw new PaymentProviderException($errorMessage);
                }
                throw new PaymentProviderException('Unexpected error from payment provider.');
            }
            $responseContent = $response->toArray();
            return $this->mapContentToUnifiedResponse($responseContent);
        } catch (TransportExceptionInterface|ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            throw new PaymentProviderException('Network or parsing error:' . $e->getMessage(), $e->getCode(), $e);
        }

    }


    public function mapContentToUnifiedResponse(array $responseContent): array
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s.uO', $responseContent['timestamp']) ?: new \DateTime();
        return [
            'transactionID' => $responseContent['id'],
            'createdDate' => $dateTime->format('Y-m-d H:i:s'),
            'amount' => $responseContent['amount'],
            'currency' => $responseContent['currency'],
            'cardBin' => $responseContent['card']['bin'],
        ];
    }


    private function formatErrorMessage(array $responseContent): string
    {
        $messageParts = [];

        // General error details
        if (isset($responseContent['result']['description'])) {
            $messageParts[] = sprintf('Description: %s', $responseContent['result']['description']);
        }
        if (isset($responseContent['result']['code'])) {
            $messageParts[] = sprintf('Code: %s', $responseContent['result']['code']);
        }

        // Parameter-specific errors
        if (isset($responseContent['result']['parameterErrors'])) {
            foreach ($responseContent['result']['parameterErrors'] as $error) {
                $messageParts[] = sprintf(
                    'Parameter Error: %s (Value: "%s") - %s',
                    $error['name'],
                    $error['value'] ?? 'null',
                    $error['message']
                );
            }
        }

        // Combine all parts into a single string
        return implode("\n", $messageParts);
    }
}