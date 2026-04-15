/**
 * Ficheiro: script.js
 * Descrição: Lógica para o Cartão Digital e Geração de Payload Pix estático
 */

// 1. Função para download do cartão 
function downloadImage() {
    const link = document.createElement('a');
    link.href = 'cartao_Lucia_Zani_terapeuta.png';
    link.download = 'cartao_Lucia_Zani_terapeuta.png';
    link.click();
}

// 2. Limpeza de strings para o padrão Pix (remove acentos e caracteres especiais)
function limparString(str) {
    return str.normalize('NFD')
              .replace(/[\u0300-\u036f]/g, "")
              .replace(/[^a-zA-Z0-9 ]/g, "")
              .toUpperCase().trim();
}

// 3. Cálculo do CRC16 (Padrão CCITT-FALSE / 0xFFFF)
// Esta função é crítica: o uso de & 0xFFFF garante que o cálculo se mantenha em 16 bits
function calcularCRC16(payload) {
    let crc = 0xFFFF;
    const polinomio = 0x1021;

    for (let i = 0; i < payload.length; i++) {
        crc ^= (payload.charCodeAt(i) << 8);
        for (let j = 0; j < 8; j++) {
            if ((crc & 0x8000) !== 0) {
                crc = ((crc << 1) ^ polinomio) & 0xFFFF;
            } else {
                crc = (crc << 1) & 0xFFFF;
            }
        }
    }
    return crc.toString(16).toUpperCase().padStart(4, '0');
}

// 4. Alternar visibilidade da área Pix
function togglePix() {
    const area = document.getElementById("pixArea");
    if (area.style.display === "none" || area.style.display === "") {
        area.style.display = "block";
    } else {
        fecharPixArea();
    }
}

// 5. Fechar área Pix e limpar campos
function fecharPixArea() {
    const area = document.getElementById("pixArea");
    const resultado = document.getElementById("resultadoPix");
    const containerQr = document.getElementById("qrcode");
    const campoInput = document.getElementById("valorPix");

    if (area) area.style.display = "none";
    if (resultado) resultado.style.display = "none";
    if (campoInput) campoInput.value = "";
    if (containerQr) containerQr.innerHTML = "";
}

// 6. Geração do QR Code Pix
function gerarQrPix() {
    const campoInput = document.getElementById("valorPix");
    
    // Converte vírgula em ponto para processamento matemático
    let valorStr = campoInput.value.replace(",", ".");
    let valor = parseFloat(valorStr);

    if (isNaN(valor) || valor <= 0) {
        alert("Por favor, insira um valor válido.");
        return;
    }

    // Dados do Recebedor
    const chave = "+5521978352349"; 
    const nome = limparString("Lucia Zani").substring(0, 25);
    const cidade = limparString("Rio de Janeiro").substring(0, 15);
    const valorFmt = valor.toFixed(2); // Garante sempre duas casas decimais

    // Montagem dos blocos do Merchant Account Information
    const merchantInfo = "0014BR.GOV.BCB.PIX01" + chave.length.toString().padStart(2, '0') + chave;
    
    // Construção do Payload
    let p = "000201"; // Payload Format Indicator
    p += "26" + merchantInfo.length.toString().padStart(2, '0') + merchantInfo;
    p += "52040000"; // Merchant Category Code
    p += "5303986";  // Transaction Currency (986 = Real)
    p += "54" + valorFmt.length.toString().padStart(2, '0') + valorFmt;
    p += "5802BR";   // Country Code
    p += "59" + nome.length.toString().padStart(2, '0') + nome;
    p += "60" + cidade.length.toString().padStart(2, '0') + cidade;
    p += "62070503***"; // Additional Data Field (TXID como *** para Pix Estático)
    p += "6304";      // CRC16 Start

    // Cálculo final com os 4 caracteres do CRC
    const payloadFinal = p + calcularCRC16(p);

    // Exibição dos elementos
    document.getElementById("resultadoPix").style.display = "block";
    const container = document.getElementById("qrcode");
    container.innerHTML = ""; 

    // Geração da imagem do QR Code
    new QRCode(container, {
        text: payloadFinal,
        width: 250,
        height: 250,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.M // Nível M (Médio) oferece melhor equilíbrio de leitura
    });

    document.getElementById("valorExibido").innerText = "Valor: R$ " + valorFmt.replace(".", ",");
    campoInput.blur();
}
// 7. Função para Alternar a Visualização (Toggle)
function togglePix() {
    let area = document.getElementById("pixArea");
    if (area.style.display === "none" || area.style.display === "") {
        area.style.display = "block";
    } else {
        fecharPixArea();
    }
}