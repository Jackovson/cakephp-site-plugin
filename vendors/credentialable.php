<?php
/**
 * Interface for Credentialable classes
 */
interface Credentialable {

	/**
	 * Test credentials against crendential conditions
	 *
	 * @return TRUE if credentials are ok
	 */
	public function checkCredentials(array $credentialConditions, array $credentials);
}
