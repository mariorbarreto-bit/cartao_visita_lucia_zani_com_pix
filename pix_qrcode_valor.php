<?php
header('Content-Type: application/json');

// 1. Inclusão da biblioteca local
require_once "phpqrcode/phpqrcode.php"; 

// Função para garantir que nomes e cidades não tenham acentos ou símbolos
function limparString($string) {
    // Converte caracteres acentuados para os seus equivalentes ASCII
    $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
    // Remove tudo o que não for letra, número ou espaço
    $string = preg_replace('/[^A-Za-z0-9 ]/', '', $string);
    return strtoupper($string);
}

function calcularCRC16($payload) {
    $polinomio = 0x1021;
    $resultado = 0xFFFF;
    for ($offset = 0; $offset < strlen($payload); $offset++) {
        $resultado ^= (ord($payload[$offset]) << 8);
        for ($bitwise = 0; $bitwise < 8; $bitwise++) {
            if (($resultado <<= 1) & 0x10000) $resultado ^= $polinomio;
            $resultado &= 0xFFFF;
        }
    }
    return strtoupper(str_pad(dechex($resultado), 4, '0', STR_PAD_LEFT));
}

function gerarPayloadPix($chave, $nome, $cidade, $valor) {
    $chave = preg_replace('/[^0-9]/', '', $chave); // Apenas números para telemóvel
    $nomeSanitizado = substr(limparString($nome), 0, 25);
    $cidadeSanitizada = substr(limparString($cidade), 0, 15);

    $merchantAccount = "0014BR.GOV.BCB.PIX01" . str_pad(strlen($chave), 2, '0', STR_PAD_LEFT) . $chave;
    
    $payload = "000201";
    $payload .= "26" . str_pad(strlen($merchantAccount), 2, '0', STR_PAD_LEFT) . $merchantAccount;
    $payload .= "520400005303986";

    if ($valor > 0) {
        $valorStr = number_format($valor, 2, '.', '');
        $payload .= "54" . str_pad(strlen($valorStr), 2, '0', STR_PAD_LEFT) . $valorStr;
    }

    $payload .= "5802BR";
    $payload .= "59" . str_pad(strlen($nomeSanitizado), 2, '0', STR_PAD_LEFT) . $nomeSanitizado;
    $payload .= "60" . str_pad(strlen($cidadeSanitizada), 2, '0', STR_PAD_LEFT) . $cidadeSanitizada;
    $payload .= "62070503***6304";

    return $payload . calcularCRC16($payload);
}

// Dados do recebedor (ajustados conforme o seu contato.vcf)
$chave  = "5521978352349";
$nome   = "Lucia Zani";
$cidade = "Rio de Janeiro";
$valorRaw = isset($_GET['valor']) ? $_GET['valor'] : '0';
$valorLimpo = str_replace(',', '.', $valorRaw);
$valor = floatval($valorLimpo);

if ($valor <= 0) {
    echo json_encode(["erro" => true, "mensagem" => "Valor inválido"]);
    exit;
}

// No momento de gerar o payload, o PHP já garante o ponto com number_format:
$valorStr = number_format($valor, 2, '.', '');

$payload = gerarPayloadPix($chave, $nome, $cidade, $valor);

// Gerar o QR Code em memória e converter para Base64
ob_start();
QRcode::png($payload, null, QR_ECLEVEL_L, 10, 2);
$imageString = base64_encode(ob_get_contents());
ob_end_clean();

echo json_encode([
    "erro" => false,
    "payload" => $payload,
    "qr_base64" => "data:image/png;base64," . $imageString
]);
?>