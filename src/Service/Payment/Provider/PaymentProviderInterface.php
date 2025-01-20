<?php

namespace App\Service\Payment\Provider;

interface PaymentProviderInterface
{

    /**
     * @return string the short name of the payment provider
     */
    public function getProviderShortName(): string;

    /**
     * @param array $data the payment data from input params
     * @return array the unified response array
     */
    public function processPayment(array $data): array;

    /**
     * @param array $responseContent the raw provider response content array
     * @return array the unified response array
     */
    public function mapContentToUnifiedResponse(array $responseContent): array;
}