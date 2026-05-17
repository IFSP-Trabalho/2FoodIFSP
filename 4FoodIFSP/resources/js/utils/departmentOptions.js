export const DEPARTMENT_ORDER = ['admin', 'kitchen', 'finance', 'waiter'];

export const DEPARTMENT_META = {
    admin: { label: 'Admin', color: '#993C1D' },
    kitchen: { label: 'Kitchen', color: '#E67E22' },
    finance: { label: 'Financeiro', color: '#2B6CB0' },
    waiter: { label: 'Garçom', color: '#38A169' },
};

export function resolveDepartmentColor(department) {
    const slug = String(department?.slug ?? '').toLowerCase();
    const fromDb = department?.color ? String(department.color).toUpperCase() : '';
    if (/^#[0-9A-F]{6}$/.test(fromDb)) {
        return fromDb;
    }
    return DEPARTMENT_META[slug]?.color ?? '#5E6B7A';
}

export function buildOrderedDepartments(departments) {
    const bySlug = Object.fromEntries(
        departments.map((department) => [
            String(department.slug ?? '').toLowerCase(),
            department,
        ]),
    );

    return DEPARTMENT_ORDER
        .map((slug) => {
            const department = bySlug[slug];
            if (!department) {
                return null;
            }

            return {
                id: String(department.id),
                slug,
                label: DEPARTMENT_META[slug]?.label ?? department.label ?? department.name,
                color: resolveDepartmentColor({ ...department, slug }),
            };
        })
        .filter(Boolean);
}
