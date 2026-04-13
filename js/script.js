function downloadImage() {
    const link = document.createElement('a');
    link.href = 'cartao_Lucia_Zani_terapeuta.png';
    link.download = 'cartao_Lucia_Zani_terapeuta.png';
    link.click();
}