# Metricalo_test
simple payment processing API built with Symfony 6.4 and PHP 8.1

## Setup and Usage

### System Requirements
- PHP 8.2.12 or higher
- Composer
- Symfony CLI (optional but recommended)

### Setup
1. Clone the repository:
   ```bash
   git clone https://github.com/Nabil-sh/metricalo_test.git
    ```
   
2. Install dependencies:
    ```bash
    composer install
    ```

3. Start the Symfony development server:
    ```bash
    symfony server:start
    ```
Access the application at http://127.0.0.1:8000

### API Usage
Endpoint: ```/api/payment/{provider}```<br>
Method: ```POST```<br>
Content-Type: ```application/json```<br>
Providers: ```shift4, aci```<br>

<b>Example</b><br>
URL: <br>```http://127.0.0.1:8000/api/payment/shift4```<br>
Payload:
```
{
  "amount": 100,
  "currency": "USD",
  "card_number": "4263982640269299",
  "exp_month": "02",
  "exp_year": "2026",
  "cvc": "837"
}
```

Example Response
```
{
  "status": "success",
  "data": {
    "transactionID": "txn_123",
    "createdDate": "2025-01-20 08:00:00",
    "amount": 100,
    "currency": "USD",
    "cardBin": "411111"
  }
}
```

<b>Providers Example Data</b>

- <b>Shift4:</b>
  * Amount: 100, Currency: USD, Card: 4263982640269299, Expiry: 02/2026 CVC: 837
- <b>ACI:</b>
  * Amount: 200, Currency: EUR, Card: 4200000000000000, Expiry: 05/2034, CVC: 123

### Notes
- The ACI provider implementation is incomplete due to the inability to create an account and obtain actual credentials (e.g., TOKEN or authentication key). As such, the ACI provider uses example data and mock logic for demonstration purposes.
- If you have valid credentials, you can update the implementation in `ACIPaymentProvider` to include the correct TOKEN and authentication headers.

### CLI Command
A Symfony console command is available to process payments.<br>
<b>Command: ```app:payment```<br>
<b>Arguments:
* provider: Payment provider (shift4, aci)

<b>Options:<br>
```--amount```: Payment amount<br>
```--currency```: Payment currency (ISO 4217 format)<br>
```--card-number```: Card number<br>
```--exp-month```: Expiry month<br>
```--exp-year```: Expiry year<br>
```--cvc```: Card verification code

<b>Example Command:<br>
```
php bin/console app:payment shift4 --amount=100 --currency=USD --card-number=4111111111111111 --exp-month=12 --exp-year=2025 --cvc=123
```

## Testing
<b>Run tests with:
```
php bin/phpunit
```
