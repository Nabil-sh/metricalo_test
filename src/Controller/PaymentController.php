<?php

namespace App\Controller;

use App\Service\Payment\Exception\PaymentProcessingException;
use App\Service\Payment\Service\PaymentService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PaymentController extends AbstractController
{
    public function __construct(
        private PaymentService $paymentService
    )
    {
    }

    #[Route('/app/payment/{provider}', name: 'app_payment_payment', methods: ['POST'])]
    #[OA\Post(
        description: 'Processes a payment through the specified provider.',
        summary: 'Process a payment',
        requestBody: new OA\RequestBody(
            description: 'Payment details',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'amount', description: 'Payment amount', type: 'number'),
                    new OA\Property(property: 'currency', description: 'ISO-4217 currency', type: 'string'),
                    new OA\Property(property: 'card_number', description: 'Credit card number', type: 'string'),
                    new OA\Property(property: 'expiry_month', description: 'Card expiry month', type: 'string'),
                    new OA\Property(property: 'expiry_year', description: 'Card expiry year', type: 'string'),
                    new OA\Property(property: 'cvv', description: 'Card CVV code', type: 'string')
                ],
                type: 'object'
            )
        ),
        tags: ['Payment'],
        parameters: [
            new OA\Parameter(
                name: 'provider',
                description: 'The payment provider (e.g., shift4, aci)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment processed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'transactionID', description: 'Transaction ID', type: 'string'),
                        new OA\Property(property: 'status', description: 'Payment status', type: 'string'),
                        new OA\Property(property: 'amount', description: 'Payment amount', type: 'number'),
                        new OA\Property(property: 'currency', description: 'Payment currency', type: 'string')
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid input or validation errors'
            ),
            new OA\Response(
                response: 500,
                description: 'Server error'
            )
        ]
    )]
    public function examplePayment(Request $request, string $provider, PaymentService $paymentService): JsonResponse
    {
        // Decode input JSON payload
        $inputParams = json_decode($request->getContent(), true);

        // Validate JSON decoding
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid JSON format: ' . json_last_error_msg()
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $response = $this->paymentService->processPayment($inputParams, $provider);

            return $this->json([
                'status' => 'success',
                'data' => $response
            ], Response::HTTP_CREATED);

        } catch (PaymentProcessingException $e) {
            // Handle payment-specific exceptions
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'details' => $e->getCode() ? ['errorCode' => $e->getCode()] : null
            ], Response::HTTP_BAD_REQUEST);

        } catch (\Exception $e) {
            // Handle unexpected errors
            return $this->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
