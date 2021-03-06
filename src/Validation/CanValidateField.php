<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\Frontend\Validation;

/**
 * @license GNU GPL v2+
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 */
trait CanValidateField {

	/**
	 * @param ValidationResult $validationResult
	 * @param string $fieldName
	 *
	 * @return ConstraintViolation|null
	 */
	private function getFieldViolation( ValidationResult $validationResult, string $fieldName ) {
		if ( $validationResult->isSuccessful() ) {
			return null;
		}

		$violation = $validationResult->getViolations()[0];
		$violation->setSource( $fieldName );

		return $violation;
	}

}