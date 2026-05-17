export function parsePriceBRL(value) {
    const digits = String(value ?? '').replace(/\D/g, '');

    if (digits) {
        const cents = Number.parseInt(digits, 10);
        return Number.isFinite(cents) ? cents / 100 : NaN;
    }

    const normalized = String(value ?? '')
        .trim()
        .replace(/[^\d,.-]/g, '')
        .replace(/\./g, '')
        .replace(',', '.');

    const amount = Number(normalized);
    return Number.isFinite(amount) ? amount : NaN;
}
