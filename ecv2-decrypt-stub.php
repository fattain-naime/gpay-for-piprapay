<?php
function gpay_ecv2_decrypt_stub($tokenJson) {
  if (!is_string($tokenJson) || $tokenJson === '') { throw new Exception('Empty token'); }
  $data = json_decode($tokenJson, true); if (!$data) { throw new Exception('Invalid token JSON'); }
  if (($data['protocolVersion'] ?? '') !== 'ECv2') { throw new Exception('Unsupported protocolVersion'); }
  $signedMessageRaw = $data['signedMessage'] ?? ''; if (!$signedMessageRaw) { throw new Exception('Missing signedMessage'); }
  return [
    'pan' => '4111111111111111',
    'expirationMonth' => '12',
    'expirationYear' => '2030',
    'cryptogram' => 'AgAAAAAAAAAAAAAAAAAAAA==',
    'exampleAuthId' => 'AUTH-SIMULATED-' . substr(sha1($signedMessageRaw), 0, 10)
  ];
}
