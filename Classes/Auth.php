<?php


namespace WPDataSync;


class Auth {

	public $publicKey;
	public $privateKey;

	public function __construct() {

	}

	public function getPublicKey() {
		return $this->publicKey;
	}

	public function getPrivateKey() {
		return $this->privateKey;
	}

	public function setKeys() {

		// generate 2048-bit RSA key
		$pkGenerate = openssl_pkey_new(array(
			'private_key_bits' => 2048,
			'private_key_type' => OPENSSL_KEYTYPE_RSA
		));

		// get the private key
		openssl_pkey_export($pkGenerate,$pkGeneratePrivate); // NOTE: second argument is passed by reference

		// get the public key
		$pkGenerateDetails = openssl_pkey_get_details($pkGenerate);
		$pkGeneratePublic = $pkGenerateDetails['key'];

		// free resources
		openssl_pkey_free($pkGenerate);

		// fetch/import public key from PEM formatted string
		// remember $pkGeneratePrivate now is PEM formatted...
		// this is an alternative method from the public retrieval in previous
		$pkImport = openssl_pkey_get_private($pkGeneratePrivate); // import
		$pkImportDetails = openssl_pkey_get_details($pkImport); // same as getting the public key in previous
		$pkImportPublic = $pkImportDetails['key'];
		openssl_pkey_free($pkImport); // clean up

		$this->publicKey = $pkGeneratePublic;
		$this->privateKey = $pkGeneratePrivate;

		// let's see 'em
//		echo "\n".$pkGeneratePrivate
//		     ."\n".$pkGeneratePublic
//		     ."\n".$pkImportPublic
//		     ."\n".'Public keys are '.(strcmp($pkGeneratePublic,$pkImportPublic)?'different':'identical').'.';

	}
}