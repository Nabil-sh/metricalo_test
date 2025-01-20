<?php

namespace App\Command;

use App\Service\Payment\Exception\PaymentProcessingException;
use App\Service\Payment\Service\PaymentService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:payment',
    description: 'Processes a payment through the specified provider.',
)]
class PaymentCommand extends Command
{
    public function __construct(
        private PaymentService $paymentService
    )
    {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addArgument('provider', InputArgument::REQUIRED, 'This determines the payment provider to use')
            ->addOption('amount', null, InputOption::VALUE_REQUIRED, 'Payment Amount')
            ->addOption('currency', null, InputOption::VALUE_REQUIRED, '3 characters ISO-4217 currency code (e.g: "AED", "USD")')
            ->addOption('card-number', null, InputOption::VALUE_REQUIRED, 'Card Number')
            ->addOption('exp-month', null, InputOption::VALUE_REQUIRED, 'Expiry Month')
            ->addOption('exp-year', null, InputOption::VALUE_REQUIRED, 'Expiry Year')
            ->addOption('cvc', null, InputOption::VALUE_REQUIRED, 'Card Verification Code (CVC or CVV)')
            ->setHelp(<<<HELP
                                The <info>app:payment</info> command processes a payment using the specified payment provider.
                                
                                Usage:
                                  <info>php bin/console app:payment [provider] --amount=100 --currency=USD --card-number=4111111111111111 --exp-month=12 --exp-year=2025 --cvc=123</info>
                                
                                Arguments:
                                  <info>provider</info>  The payment provider to use (e.g., shift4, aci).
                                
                                Example:
                                  <info>php bin/console app:payment shift4 --amount=100 --currency=USD --card-number=4111111111111111 --exp-month=12 --exp-year=2025 --cvc=123</info>
                                
                                HELP
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $provider = $input->getArgument('provider');

        $amount = $input->getOption('amount');
        $currency = $input->getOption('currency');
        $cardNumber = $input->getOption('card-number');
        $expMonth = $input->getOption('exp-month');
        $expYear = $input->getOption('exp-year');
        $cvc = $input->getOption('cvc');

        if (!$amount || !$currency || !$cardNumber || !$expMonth || !$expYear || !$cvc) {
            $io->error('Options: amount, currency, card-number, exp-month, exp-year, cvc are required');
            return Command::FAILURE;
        }

        $paymentData = [
            'amount' => $amount,
            'currency' => $currency,
            'card_number' => $cardNumber,
            'exp_month' => $expMonth,
            'exp_year' => $expYear,
            'cvc' => $cvc
        ];

        try {
            $response = $this->paymentService->processPayment($paymentData, $provider);
            $io->success('Payment processed successfully');
            $io->title('Payment Process Response');
            $io->table(['field', 'value'],
                [
                    ['Transaction ID', $response['transactionID']],
                    ['Created Date', $response['createdDate']],
                    ['Amount', $response['amount']],
                    ['Currency', $response['currency']],
                    ['Card BIN', $response['cardBin']],
                ]
            );
            return Command::SUCCESS;
        } catch (PaymentProcessingException $e) {
            // Handle known payment processing exceptions
            $io->error('Payment processing failed: ' . $e->getMessage());
            return Command::FAILURE;
        } catch (\Exception $e) {
            // Handle unknown exceptions
            $io->error('An unexpected  error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
