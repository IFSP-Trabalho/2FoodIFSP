const MAX_PRICE_DIGITS = 8;

export function sanitizePriceDigits(value) {
    return String(value ?? '').replace(/\D/g, '').slice(0, MAX_PRICE_DIGITS);
}

export function formatPriceInputBRL(digits) {
    if (!digits) {
        return '';
    }

    const cents = Number.parseInt(digits, 10);

    if (!Number.isFinite(cents)) {
        return '';
    }

    const amount = cents / 100;

    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(amount);
}

export function parsePriceDigitsBRL(digits) {
    if (!digits) {
        return NaN;
    }

    const cents = Number.parseInt(digits, 10);

    if (!Number.isFinite(cents)) {
        return NaN;
    }

    return cents / 100;
}

export function priceToDigitsFromDecimal(price) {
    if (price == null || price === '') {
        return '';
    }

    const cents = Math.round(Number(price) * 100);

    if (!Number.isFinite(cents) || cents < 0) {
        return '';
    }

    return String(cents);
}

export function isPriceInputNavigationKey(key) {
    return [
        'Backspace',
        'Delete',
        'Tab',
        'ArrowLeft',
        'ArrowRight',
        'ArrowUp',
        'ArrowDown',
        'Home',
        'End',
    ].includes(key);
}
