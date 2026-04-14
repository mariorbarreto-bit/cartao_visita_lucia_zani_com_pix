<?php
header('Content-Type: application/json');

// ============================
// CRC16 (VALIDADO)
// ============================
function calcularCRC16($payload) {
    $polinomio = 0x1021;
    $resultado = 0xFFFF;

    for ($offset = 0; $offset < strlen($payload); $offset++) {
        $resultado ^= (ord($payload[$offset]) << 8);
        for ($bitwise = 0; $bitwise < 8; $bitwise++) {
            if (($resultado <<= 1) & 0x10000) {
                $resultado ^= $polinomio;
            }
            $resultado &= 0xFFFF;
        }
    }

    return strtoupper(str_pad(dechex($resultado), 4, '0', STR_PAD_LEFT));
}

// ============================
// VALIDAR PAYLOAD PIX
// ============================
function validarPayloadPix($payload) {

    if (substr($payload, 0, 6) !== "000201") {
        return false;
    }

    if (strpos($payload, "BR.GOV.BCB.PIX") === false) {
        return false;
    }

    if (!preg_match('/6304([A-F0-9]{4})$/', $payload, $match)) {
        return false;
    }

    $crcInformado = $match[1];
    $payloadSemCRC = substr($payload, 0, -4);

    $crcCalculado = calcularCRC16($payloadSemCRC);

    return ($crcInformado === $crcCalculado);
}

// ============================
// GERAR QR COM VALIDAÇÃO
// ============================
function gerarQrPixSeguro($chave, $nome, $cidade, $valor) {

    $payload = gerarPayloadPix($chave, $nome, $cidade, $valor);

    if (!validarPayloadPix($payload)) {
        return [
            "erro" => true,
            "mensagem" => "Payload Pix inválido"
        ];
    }

    $qr = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . $payload;

    return [
        "erro" => false,
        "payload" => $payload,
        "qr" => $qr
    ];
}

// ============================
// GERAR PAYLOAD PIX CORRETO
// ============================
function gerarPayloadPix($chave, $nome, $cidade, $valor) {

    $chave = preg_replace('/[^0-9]/', '', $chave);
    $nome = strtoupper(substr($nome, 0, 25));
    $cidade = strtoupper(substr($cidade, 0, 15));
    //$nome = strtoupper(substr(iconv('UTF-8', 'ASCII//TRANSLIT', $nome), 0, 25));
    //$cidade = strtoupper(substr(iconv('UTF-8', 'ASCII//TRANSLIT', $cidade), 0, 15));

    $gui = "0014BR.GOV.BCB.PIX";
    $chavePix = "01" . str_pad(strlen($chave), 2, '0', STR_PAD_LEFT) . $chave;
    $merchantAccount = $gui . $chavePix;

    $payload = "000201";
    $payload .= "26" . str_pad(strlen($merchantAccount), 2, '0', STR_PAD_LEFT) . $merchantAccount;
    $payload .= "52040000";
    $payload .= "5303986";

    if ($valor > 0) {
        $valor = number_format($valor, 2, '.', '');
        $payload .= "54" . str_pad(strlen($valor), 2, '0', STR_PAD_LEFT) . $valor;
    }

    $payload .= "5802BR";
    $payload .= "59" . str_pad(strlen($nome), 2, '0', STR_PAD_LEFT) . $nome;
    $payload .= "60" . str_pad(strlen($cidade), 2, '0', STR_PAD_LEFT) . $cidade;

    $payload .= "62070503***";

    // CRC correto
    $payloadCRC = $payload . "6304";
    $crc = calcularCRC16($payloadCRC);

    return $payloadCRC . $crc;
}

// ============================
// CONFIGURAÇÃO
// ============================

$chave  = "5521978352349";
$nome   = "LUCIZANI";
$cidade = "RIO DE JANEIRO"; // Deve ter até 15 carateres de comprimento (neste caso tem 14)
$valor  = isset($_GET['valor']) ? floatval($_GET['valor']) : 0;

$resultado = gerarQrPixSeguro($chave, $nome, $cidade, $valor);

echo json_encode($resultado);

exit;
?>
