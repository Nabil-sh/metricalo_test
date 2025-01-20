<?php

namespace App\Controller;

use App\Service\Payment\Provider\Shift4PaymentProvider;
use App\Service\Payment\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class TestController extends AbstractController
{

    public function __construct(
        private Shift4PaymentProvider $shift4PaymentProvider,
        private PaymentService $paymentService
    )
    {
    }

    #[Route('/test', name: 'app_test')]
    public function index(): JsonResponse
    {

        $data = [
            'amount' => 100,
            'currency' => 'AED',
            'card_number' => '4263982640269299',
            'exp_month' => '02',
            'exp_year' => '2026',
            'cvc' => '837'
        ];

        $this->paymentService->processPayment($data, 'shift4');

        //$this->shift4PaymentProvider->processPayment($data);

        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/TestController.php',
        ]);
    }
}
