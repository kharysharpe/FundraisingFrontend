<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\Frontend\Tests\Fixtures;

use PHPUnit_Framework_TestCase;
use WMDE\Fundraising\Frontend\Domain\Model\EmailAddress;
use WMDE\Fundraising\Frontend\Infrastructure\TemplateBasedMailer;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TemplateBasedMailerSpy extends TemplateBasedMailer {

	private $testCase;
	private $sendMailCalls = [];

	public function __construct( PHPUnit_Framework_TestCase $testCase ) {
		$this->testCase = $testCase;
	}

	public function sendMail( EmailAddress $recipient, array $templateArguments = [] ) {
		$this->sendMailCalls[] = func_get_args();
	}

	public function getSendMailCalls(): array {
		return $this->sendMailCalls;
	}

	public function assertMailerCalledOnceWith( EmailAddress $expectedEmail, array $expectedArguments ) {
		$this->testCase->assertCount( 1, $this->sendMailCalls, 'Mailer should be called exactly once' );

		$this->testCase->assertEquals(
			[
				$expectedEmail,
				$expectedArguments
			],
			$this->sendMailCalls[0]
		);
	}

}