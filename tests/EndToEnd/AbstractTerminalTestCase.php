<?php

namespace Gomzyakov\Payture\InPayClient\Tests\EndToEnd;

use GuzzleHttp\Client;
use Gomzyakov\Payture\InPayClient\GuzzleHttp\GuzzleHttpPaytureTransport;
use Gomzyakov\Payture\InPayClient\PaytureInPayTerminal;
use Gomzyakov\Payture\InPayClient\PaytureInPayTerminalInterface;
use Gomzyakov\Payture\InPayClient\TerminalConfiguration;
use Gomzyakov\Payture\InPayClient\TestUtils\Card;
use Gomzyakov\Payture\InPayClient\TestUtils\PaymentHelper;
use PHPUnit\Framework\TestCase;
use DateTime;
use Exception;
use RuntimeException;

abstract class AbstractTerminalTestCase extends TestCase
{
    protected const SANDBOX_PAY_SUBMIT_URL = 'https://sandbox.payture.com/apim/PaySubmit';

    protected const SANDBOX_API_URL = 'https://sandbox.payture.com';

    private const ENV_KEY = 'PAYTURE_TEST_MERCHANT_KEY';

    private const ENV_PASSWORD = 'PAYTURE_TEST_MERCHANT_PASSWORD';

    /**
     * @var PaytureInPayTerminal
     */
    private PaytureInPayTerminal $terminal;

    /**
     * @var PaymentHelper
     */
    private PaymentHelper $helper;

    /**
     * TODO Set up ENV_KEY & ENV_PASSWORD.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (getenv(self::ENV_KEY) === false || getenv(self::ENV_PASSWORD) === false) {
            self::markTestSkipped(
                sprintf(
                    'Provide both "%s" and "%s" env vars to run end-to-end test',
                    self::ENV_KEY,
                    self::ENV_PASSWORD
                )
            );
        }

        $configuration = new TerminalConfiguration(
            getenv(self::ENV_KEY),
            getenv(self::ENV_PASSWORD),
            self::SANDBOX_API_URL
        );
        $client         = new Client();
        $transport      = new GuzzleHttpPaytureTransport($client, $configuration);
        $this->terminal = new PaytureInPayTerminal($configuration, $transport);
        $this->helper   = new PaymentHelper($client);
    }

    protected static function generateOrderId(): string
    {
        try {
            return 'TEST' . (new DateTime())->format('ymd-His');
        } catch (Exception $e) {
            throw new RuntimeException('Unable to generate \DateTime instance');
        }
    }

    protected function pay(string $paymentUrl, string $orderId, int $amount): void
    {
        $this->helper->pay($orderId, $amount, $paymentUrl, self::getTestCard(), self::SANDBOX_PAY_SUBMIT_URL);
    }

    protected function getTerminal(): PaytureInPayTerminalInterface
    {
        return $this->terminal;
    }

    /**
     * Successful payment without 3DS and with optional CVV.
     *
     * @see https://payture.com/api#test-cards_
     */
    private static function getTestCard(): Card
    {
        return new Card('4111111111100031', '123', '22', '12', 'AUTO TESTS');
    }
}
