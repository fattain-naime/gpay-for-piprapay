<?php
require_once __DIR__ . '/vendor/autoload.php';

use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\AES;
use phpseclib3\Math\BigInteger;

function gpay_ecv2_decrypt($tokenJson, $privateKeyBase64)
{
    if (!is_string($tokenJson) || $tokenJson === '') {
        throw new Exception('Empty token');
    }
    $data = json_decode($tokenJson, true);
    if (!$data) {
        throw new Exception('Invalid token JSON');
    }
    if (($data['protocolVersion'] ?? '') !== 'ECv2') {
        throw new Exception('Unsupported protocolVersion');
    }

    $privateKey = EC::loadPrivateKey(base64_decode($privateKeyBase64));
    if (!$privateKey) {
        throw new Exception('Invalid private key');
    }

    $encryptedMessage = base64_decode($data['encryptedMessage']);
    $ephemeralPublicKey = base64_decode($data['ephemeralPublicKey']);

    // ECIES-KEM
    $sharedSecret = $privateKey->deriveSharedSecret(EC::loadPublicKey($ephemeralPublicKey));

    // HKDF
    $salt = substr($encryptedMessage, 0, 32);
    $ciphertext = substr($encryptedMessage, 32);
    $hkdfInfo = 'Google';
    $keyingMaterial = $sharedSecret . $ephemeralPublicKey;

    $prk = hash_hmac('sha256', $keyingMaterial, $salt, true);
    $aesKey = substr(hash_hmac('sha256', "\1", $prk, true), 0, 16);
    $macKey = substr(hash_hmac('sha256', $aesKey . "\2", $prk, true), 0, 16);

    // AES-128-CTR
    $iv = substr($ciphertext, 0, 16);
    $encrypted = substr($ciphertext, 16, -32);
    $tag = substr($ciphertext, -32);

    $cipher = new AES('ctr');
    $cipher->setKey($aesKey);
    $cipher->setIV($iv);
    $decrypted = $cipher->decrypt($encrypted);

    // HMAC
    $mac = hash_hmac('sha256', $iv . $encrypted, $macKey, true);
    if (!hash_equals($tag, $mac)) {
        throw new Exception('Invalid MAC');
    }

    $paymentMethodDetails = json_decode($decrypted, true);
    if (!$paymentMethodDetails) {
        throw new Exception('Invalid decrypted JSON');
    }

    return $paymentMethodDetails;
}