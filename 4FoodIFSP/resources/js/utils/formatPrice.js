export function formatPriceBRL(value) {
    const amount = Number(value);

    if (Number.isNaN(amount)) {
        return 'R$ 0,00';
    }

    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(amount);
}
