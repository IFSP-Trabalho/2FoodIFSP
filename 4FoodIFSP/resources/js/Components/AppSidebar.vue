<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    active: {
        type: String,
        default: 'dashboard',
    },
});

const items = [
    { key: 'home', icon: 'home', route: '/admin/dashboard' },
    { key: 'dashboard', icon: 'dashboard', route: null },
    { key: 'orders', icon: 'orders', route: '/admin/orders' },
    { key: 'tables', icon: 'tables', route: null },
    { key: 'registers', icon: 'registers', route: null },
    { key: 'finance', icon: 'finance', route: null },
    { key: 'reports', icon: 'reports', route: null },
];

const splitAfter = new Set(['dashboard', 'registers']);

function isDisabled(item) {
    return !item.route;
}

function isActive(item) {
    if (props.active === 'dashboard') {
        return item.key === 'home';
    }

    return item.key === props.active;
}

function onItemClick(item) {
    if (isDisabled(item)) {
        return;
    }

    router.visit(item.route);
}
</script>

<template>
    <aside class="sidebar">
        <div class="avatar">A</div>

        <template v-for="item in items" :key="item.key">
            <button
                type="button"
                class="nav-item"
                :class="{
                    active: isActive(item),
                    disabled: isDisabled(item),
                }"
                @click="onItemClick(item)"
            >
                <svg v-if="item.icon === 'home'" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M3 10.5 12 3l9 7.5v9.5a1 1 0 0 1-1 1h-5.5v-6h-5v6H4a1 1 0 0 1-1-1z" />
                </svg>
                <svg v-else-if="item.icon === 'dashboard'" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 4h7v7H4zm9 0h7v4h-7zM4 13h4v7H4zm6 0h10v7H10z" />
                </svg>
                <svg v-else-if="item.icon === 'orders'" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M7 4h10l2 4v12H5V8zm2 0v4h6V4" />
                </svg>
                <svg v-else-if="item.icon === 'tables'" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 5h16v3H4zm7 3h2v11h-2zM6 19h12v2H6z" />
                </svg>
                <svg v-else-if="item.icon === 'registers'" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M5 4h14v16H5zm3 4h8v2H8zm0 4h8v2H8z" />
                </svg>
                <svg v-else-if="item.icon === 'finance'" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 17h16v3H4zM6 9h3v8H6zm5-4h3v12h-3zm5 6h3v6h-3z" />
                </svg>
                <svg v-else viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h10v2H4z" />
                </svg>
            </button>
            <hr v-if="splitAfter.has(item.key)" class="divider">
        </template>
    </aside>
</template>

<style scoped>
.sidebar {
    width: 52px;
    min-width: 52px;
    height: 100vh;
    padding: 8px 0;
    background: #fff;
    border-right: 1px solid #eceef0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #faece7;
    color: #993c1d;
    font-size: 12px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
}

.nav-item {
    width: 34px;
    height: 34px;
    border: 0;
    border-radius: 10px;
    background: transparent;
    color: #6f6f78;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.nav-item svg {
    width: 18px;
    height: 18px;
    fill: currentColor;
}

.nav-item.active {
    background: #faece7;
    color: #993c1d;
}

.nav-item.disabled {
    opacity: 0.4;
    cursor: default;
}

.divider {
    width: 24px;
    border: 0;
    border-top: 1px solid #eceef0;
    margin: 2px 0 4px;
}
</style>
