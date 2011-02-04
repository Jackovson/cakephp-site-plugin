<?php
App::import('Vendor', 'Site.Credentialable');
/**
 * Simple class that hanle very simple credentials system
 *
 * A credential is a string "admin", "controller:"
 *
 * A credential conditions is an array, evaluated as OR statements :
 * array(
 *	'group:admin',// could mean : credential for group admin
 *	'controller:user:read',// could mean : credential for conroler user and action read
 *	'action:read',// could mean : credential for reading everithing
 * )
 */
class SimpleCredential implements Credentialable {

	/**
	 * Check credentials against conditions
	 * @param array $crendialConditions
	 * @param array $credentials
	 * @return TRUE if credentials ok
	 */
	public function checkCredentials(array $crendialConditions, array $credentials) {
		foreach($crendialConditions as $credential) {
			if(array_key_exists($credential, $credentials)) {
				return TRUE;
			}
		}
		return FALSE;
	}
}
