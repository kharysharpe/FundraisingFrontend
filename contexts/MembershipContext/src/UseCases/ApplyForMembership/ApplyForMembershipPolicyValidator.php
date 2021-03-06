<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\Frontend\MembershipContext\UseCases\ApplyForMembership;

use WMDE\Fundraising\Frontend\MembershipContext\Domain\Model\Application;
use WMDE\Fundraising\Frontend\Validation\TextPolicyValidator;

/**
 * @license GNU GPL v2+
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 */
class ApplyForMembershipPolicyValidator {

	private const YEARLY_PAYMENT_MODERATION_THRESHOLD_IN_EURO = 1000;

	private $textPolicyValidator;

	public function __construct( TextPolicyValidator $textPolicyValidator ) {
		$this->textPolicyValidator = $textPolicyValidator;
	}

	public function needsModeration( Application $application ): bool {
		return $this->yearlyAmountExceedsLimit( $application ) ||
			$this->addressContainsBadWords( $application );
	}

	private function yearlyAmountExceedsLimit( Application $application ): bool {
		return
			$application->getPayment()->getYearlyAmount()->getEuroFloat()
			> self::YEARLY_PAYMENT_MODERATION_THRESHOLD_IN_EURO;
	}

	private function addressContainsBadWords( Application $application ) {
		$applicant = $application->getApplicant();
		$harmless = $this->textPolicyValidator->textIsHarmless( $applicant->getName()->getFirstName() ) &&
			$this->textPolicyValidator->textIsHarmless( $applicant->getName()->getLastName() ) &&
			$this->textPolicyValidator->textIsHarmless( $applicant->getName()->getCompanyName() ) &&
			$this->textPolicyValidator->textIsHarmless( $applicant->getPhysicalAddress()->getCity() ) &&
			$this->textPolicyValidator->textIsHarmless( $applicant->getPhysicalAddress()->getStreetAddress() );
		return !$harmless;
	}
}