<?php

function gerarPayloadPix($chave, $nome, $cidade, $valor = null) {

    $payload  = "000201";
    $payload .= "26";
    $payload .= "0014BR.GOV.BCB.PIX";

    $chave_formatada = "01" . str_pad(strlen($chave), 2, '0', STR_PAD_LEFT) . $chave;
    $payload .= str_pad(strlen($chave_formatada), 2, '0', STR_PAD_LEFT) . $chave_formatada;

    $payload .= "52040000"; // MCC
    $payload .= "5303986";  // Moeda BRL

    // 💰 VALOR (opcional)
    if (!empty($valor)) {
        $valor = number_format($valor, 2, '.', '');
        $payload .= "54" . str_pad(strlen($valor), 2, '0', STR_PAD_LEFT) . $valor;
    }

    $payload .= "5802BR";

    // Nome (máx 25 caracteres, sem acento)
    $nome = strtoupper(substr(removerAcentos($nome), 0, 25));
    $payload .= "59" . str_pad(strlen($nome), 2, '0', STR_PAD_LEFT) . $nome;

    // Cidade (máx 15 caracteres)
    $cidade = strtoupper(substr(removerAcentos($cidade), 0, 15));
    $payload .= "60" . str_pad(strlen($cidade), 2, '0', STR_PAD_LEFT) . $cidade;

    $payload .= "62070503***";

    $payload .= "6304";

    $crc = calcularCRC16($payload);
    $payload .= $crc;

    return $payload;
}

function calcularCRC16($payload) {
    $polinomio = 0x1021;
    $resultado = 0xFFFF;

    for ($i = 0; $i < strlen($payload); $i++) {
        $resultado ^= (ord($payload[$i]) << 8);

        for ($j = 0; $j < 8; $j++) {
            if (($resultado <<= 1) & 0x10000) {
                $resultado ^= $polinomio;
            }
            $resultado &= 0xFFFF;
        }
    }

    return strtoupper(str_pad(dechex($resultado), 4, '0', STR_PAD_LEFT));
}

function removerAcentos($string) {
    return iconv('UTF-8', 'ASCII//TRANSLIT', $string);
}

// ==========================
// CONFIGURAÇÃO
// ==========================

$chave  = "+5521978352349";
$nome   = "LUCI ZANI";
$cidade = "RIO DE JANEIRO";

// Recebe valor do formulário
$valor = isset($_POST['valor']) ? floatval($_POST['valor']) : null;

$pixCode = gerarPayloadPix($chave, $nome, $cidade, $valor);

$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($pixCode);

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Pix Dinâmico</title>
</head>
<body>

<h2>Gerar Pagamento Pix</h2>

<form method="POST">
    <label>Valor (R$):</label>
    <input type="number" step="0.01" name="valor" placeholder="Ex: 250.00">
    <button type="submit">Gerar QR Code</button>
</form>

<?php if (!empty($valor)) { ?>

    <h3>Valor: R$ <?php echo number_format($valor, 2, ',', '.'); ?></h3>

    <p><strong>Código Pix:</strong></p>
    <textarea rows="5" cols="60"><?php echo $pixCode; ?></textarea>

    <p><strong>QR Code:</strong></p>
    <img src="<?php echo $qrUrl; ?>" alt="QR Code Pix">

<?php } ?>

</body>
</html>