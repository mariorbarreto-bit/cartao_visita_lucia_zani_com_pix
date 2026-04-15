function downloadImage() {
    const link = document.createElement('a');
    link.href = 'cartao_Lucia_Zani_terapeuta.png';
    link.download = 'cartao_Lucia_Zani_terapeuta.png';
    link.click();
}
// Função de limpeza de strings
function limparString(str) {
    return str.normalize('NFD')
              .replace(/[\u0300-\u036f]/g, "")
              .replace(/[^a-zA-Z0-9 ]/g, "")
              .toUpperCase().trim();
}

// Função CRC16
function calcularCRC16(payload) {
    let crc = 0xFFFF;
    for (let i = 0; i < payload.length; i++) {
        crc ^= (payload.charCodeAt(i) << 8);
        for (let j = 0; j < 8; j++) {
            if ((crc & 0x8000) !== 0) crc = (crc << 1) ^ 0x1021;
            else crc <<= 1;
        }
    }
    return (crc & 0xFFFF).toString(16).toUpperCase().padStart(4, '0');
}

function gerarQrPix() {
    const campoInput = document.getElementById("valorPix");
    let valorStr = campoInput.value.replace(",", ".");
    let valor = parseFloat(valorStr);

    if (isNaN(valor) || valor <= 0) {
        alert("Por favor, insira um valor válido.");
        return;
    }

    // 1. CHAVE APENAS NÚMEROS (Mais compatível com todos os bancos)
    const chave = "5521978352349"; 
    const nome = limparString("Lucia Zani").substring(0, 25);
    const cidade = limparString("Rio de Janeiro").substring(0, 15);
    const valorFmt = valor.toFixed(2);

    // Montagem do Payload
    const merchantInfo = "0014BR.GOV.BCB.PIX01" + chave.length.toString().padStart(2, '0') + chave;
    
    let p = "000201";
    p += "26" + merchantInfo.length.toString().padStart(2, '0') + merchantInfo;
    p += "520400005303986";
    p += "54" + valorFmt.length.toString().padStart(2, '0') + valorFmt;
    p += "5802BR";
    p += "59" + nome.length.toString().padStart(2, '0') + nome;
    p += "60" + cidade.length.toString().padStart(2, '0') + cidade;
    p += "62070503***6304"; // Tag 62 fixa em 07 caracteres (0503***)

    const payloadFinal = p + calcularCRC16(p);

    // MOSTRAR ÁREA ANTES DE GERAR (Essencial para a biblioteca qrcode.js)
    document.getElementById("pixArea").style.display = "block";
    document.getElementById("resultadoPix").style.display = "block";

    const container = document.getElementById("qrcode");
    container.innerHTML = ""; // Limpa anterior

    // GERAR QR CODE COM ALTA CORREÇÃO (Nível H)
    new QRCode(container, {
        text: payloadFinal,
        width: 250,
        height: 250,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H 
    });

    document.getElementById("valorExibido").innerText = "Valor: R$ " + valorFmt;
    campoInput.value = "";
    campoInput.blur();
}

// Lógica para esconder a área ao clicar noutros botões
function fecharPixArea() {
    const area = document.getElementById("pixArea");
    const resultado = document.getElementById("resultadoPix");
    const campoInput = document.getElementById("valorPix");
    const containerQr = document.getElementById("qrcode");

    if (area) area.style.display = "none";
    if (resultado) resultado.style.display = "none";
    if (campoInput) campoInput.value = "";
    
    // Limpa o QR Code se estiver a usar a biblioteca JavaScript
    if (containerQr) containerQr.innerHTML = ""; 
}
function togglePix() {
        let area = document.getElementById("pixArea");
        area.style.display = (area.style.display === "none") ? "block" : "none";

        if (area.style.display === "none") {
            fecharPix();
        }
}