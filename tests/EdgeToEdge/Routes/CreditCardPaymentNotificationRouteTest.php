<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\Frontend\Tests\EdgeToEdge\Routes;

use Symfony\Component\HttpKernel\Client;
use WMDE\Fundraising\Frontend\DataAccess\DoctrineDonationAuthorizationUpdater;
use WMDE\Fundraising\Frontend\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\Frontend\Domain\Repositories\DonationRepository;
use WMDE\Fundraising\Frontend\Factories\FunFunFactory;
use WMDE\Fundraising\Frontend\Tests\Data\ValidDonation;
use WMDE\Fundraising\Frontend\Tests\EdgeToEdge\WebRouteTestCase;

/**
 * @licence GNU GPL v2+
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class CreditCardPaymentNotificationRouteTest extends WebRouteTestCase {

	const FUNCTION = 'billing';
	const DONATION_ID = 1;
	const AMOUNT = 500;
	const TRANSACTION_ID = 'customer.prefix-ID2tbnag4a9u';
	const CUSTOMER_ID = 'e20fb9d5281c1bca1901c19f6e46213191bb4c17';
	const SESSION_ID = 'CC13064b2620f4028b7d340e3449676213336a4d';
	const AUTH_ID = 'd1d6fae40cf96af52477a9e521558ab7';
	const UPDATE_TOKEN = 'my_secret_update_token';
	const TITLE = 'Your generous donation';
	const COUNTRY_CODE = 'DE';
	const CURRENCY_CODE = 'EUR';

	public function testGivenInvalidRequest_applicationIndicatesError() {
		$this->createEnvironment( [], function ( Client $client, FunFunFactory $factory ) {
			$client->request(
				'POST',
				'/handle-creditcard-payment-notification',
				[]
			);

			$this->assertSame( 200, $client->getResponse()->getStatusCode() );
			$this->assertContains( 'failed', $client->getResponse()->getContent() );
		} );
	}

	public function testGivenValidRequest_applicationIndicatesSuccess() {
		$this->createEnvironment( [], function ( Client $client, FunFunFactory $factory ) {
			$factory->setNullMessenger();

			$factory->getDonationRepository()->storeDonation( ValidDonation::newIncompleteCreditCardDonation() );
			$authorizer = new DoctrineDonationAuthorizationUpdater( $factory->getEntityManager() );
			$authorizer->allowModificationViaToken(
				self::DONATION_ID,
				self::UPDATE_TOKEN,
				\DateTime::createFromFormat( 'Y-m-d H:i:s', '2039-12-31 23:59:59' )
			);

			$client->request(
				'POST',
				'/handle-creditcard-payment-notification',
				$this->newRequest()
			);

			$this->assertSame( 200, $client->getResponse()->getStatusCode() );
			$this->assertCreditCardDataGotPersisted( $factory->getDonationRepository(), $this->newRequest() );
		} );
	}

	private function newRequest() {
		return [
			'function' => self::FUNCTION,
			'donation_id' => self::DONATION_ID,
			'amount' => self::AMOUNT,
			'transactionId' => self::TRANSACTION_ID,
			'customerId' => self::CUSTOMER_ID,
			'sessionId' => self::SESSION_ID,
			'auth' => self::AUTH_ID,
			'utoken' => self::UPDATE_TOKEN,
			'title' => self::TITLE,
			'country' => self::COUNTRY_CODE,
			'currency' => self::CURRENCY_CODE,
		];
	}

	private function assertCreditCardDataGotPersisted( DonationRepository $donationRepo, $request ) {
		$donation = $donationRepo->getDonationById( self::DONATION_ID );

		/** @var CreditCardPayment $paymentMethod */
		$paymentMethod = $donation->getPayment()->getPaymentMethod();
		$ccData = $paymentMethod->getCreditCardData();

		$this->assertSame( $request['currency'], $ccData->getCurrencyCode() );
		$this->assertSame( $request['amount'], $ccData->getAmount()->getEuroCents() );
		$this->assertSame( $request['country'], $ccData->getCountryCode() );
		$this->assertSame( $request['auth'], $ccData->getAuthId() );
		$this->assertSame( $request['title'], $ccData->getTitle() );
		$this->assertSame( $request['sessionId'], $ccData->getSessionId() );
		$this->assertSame( $request['transactionId'], $ccData->getTransactionId() );
		#$this->assertSame( $request['status'], $ccData->getTransactionStatus() );
		$this->assertSame( $request['customerId'], $ccData->getCustomerId() );
		#$this->assertSame( $request['mcp_cc_expiry_date'], $ccData->getExpiryDate() );
		#$this->assertSame( $request['ext_payment_timestamp'], $ccData->getTransactionTimestamp() );
	}

}
