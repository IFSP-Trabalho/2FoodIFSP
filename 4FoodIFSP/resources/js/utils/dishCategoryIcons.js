const CATEGORY_ICONS = {
    main_course: 'utensils',
    drinks: 'cup',
    desserts: 'cake',
    burger: 'burger',
    pizza: 'pizza',
};

export function resolveCategoryIcon(slug) {
    return CATEGORY_ICONS[slug] ?? 'grid';
}
