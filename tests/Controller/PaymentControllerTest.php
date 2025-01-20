<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaymentControllerTest extends WebTestCase
{
    public function testSuccessfulPayment(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/app/payment/shift4',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'amount' => 100,
                'currency' => 'USD',
                'card_number' => '4111111111111111',
                'exp_month' => '12',
                'exp_year' => '2025',
                'cvc' => '123',
            ])
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals('success', $responseData['status']);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertEquals(100, $responseData['data']['amount']);
        $this->assertEquals('USD', $responseData['data']['currency']);

    }


    public function testInvalidPaymentData(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/app/payment/shift4',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'amount' => 'invalid', // Invalid amount
                'currency' => 'USD',
                'card_number' => '4111111111111111',
                'exp_month' => '12',
                'exp_year' => '2025',
                'cvc' => '123',
            ])
        );

        $this->assertResponseStatusCodeSame(400);
        $responseData= json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals('error', $responseData['status']);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Invalid payment data: Invalid amount', $responseData['message']);
    }

}