<?php

namespace App\Service\Payment\Service;

use App\Service\Payment\Exception\PaymentProcessingException;
use App\Service\Payment\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class PaymentService
{

    public function __construct(
        #[TaggedIterator('app.payment_provider')] private iterable $paymentProviders,
        private LoggerInterface $logger
    )
    {
    }


    /**
     * @param array $data
     * @param string $provider
     * @return array
     * @throws PaymentProcessingException
     */
    public function processPayment(array $data, string $provider): array
    {
        foreach ($this->paymentProviders as $paymentProvider) {
            if ($paymentProvider->getProviderShortName() === $provider) {
                try {
                    // Validate input
                    $this->validateInput($data);

                    // Process payment
                    return $paymentProvider->processPayment($data);
                } catch (ValidationException $e) {
                    $this->logger->warning(sprintf(
                        'Validation failed for payment with provider "%s": %s',
                        $provider,
                        $e->getMessage()
                    ));

                    throw new PaymentProcessingException('Invalid payment data: ' . $e->getMessage());
                } catch (\Exception $e) {
                    $this->logger->error(sprintf(
                        'Error processing payment with provider "%s": %s',
                        $provider,
                        $e->getMessage()
                    ));

                    throw new PaymentProcessingException('Payment processing failed.');
                }
            }
        }

        // Log unsupported provider
        $this->logger->warning(sprintf(
            'Unsupported payment provider: "%s"',
            $provider
        ));

        throw new \InvalidArgumentException('Invalid or unsupported payment provider');
    }


    /**
     * @param array $data
     * @return void
     * @throws ValidationException
     */
    private function validateInput(array $data): void
    {
        if (!isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new ValidationException('Invalid amount');
        }

        if (!isset($data['currency']) || strlen($data['currency']) !== 3) {
            throw new ValidationException('Invalid currency');
        }

        if (!isset($data['card_number']) || strlen($data['card_number']) < 12) {
            throw new ValidationException('Invalid card number');
        }

        if (!isset($data['exp_month']) || !is_numeric($data['exp_month']) || $data['exp_month'] < 1 || $data['exp_month'] > 12) {
            throw new ValidationException('Invalid expiry month');
        }

        if (!isset($data['exp_year']) || !is_numeric($data['exp_year']) || $data['exp_year'] < date('Y')) {
            throw new ValidationException('Invalid expiry year');
        }

        if (!isset($data['cvc']) || strlen($data['cvc']) < 3) {
            throw new ValidationException('Invalid CVC');
        }
    }
}