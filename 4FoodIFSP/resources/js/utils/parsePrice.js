export function parsePriceBRL(value) {
    const normalized = String(value ?? '')
        .trim()
        .replace(/\./g, '')
        .replace(',', '.');

    const amount = Number(normalized);
    return Number.isFinite(amount) ? amount : NaN;
}
