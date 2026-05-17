<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppSidebar from '../../../Components/AppSidebar.vue';
import DepartmentColorEditPanel from '../../../Components/DepartmentColorEditPanel.vue';
import DepartmentsTableRow from '../../../Components/DepartmentsTableRow.vue';
import { buildOrderedDepartments } from '../../../utils/departmentOptions';

const props = defineProps({
    departments: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const search = ref('');
const editPanelDepartment = ref(null);

const flashSuccess = computed(() => page.props.flash?.success ?? '');

const orderedDepartments = computed(() => buildOrderedDepartments(props.departments));

const filteredDepartments = computed(() => {
    if (!search.value.trim()) {
        return orderedDepartments.value;
    }

    const query = search.value.toLowerCase();

    return orderedDepartments.value.filter((department) => (
        String(department.label ?? '').toLowerCase().includes(query)
        || String(department.slug ?? '').toLowerCase().includes(query)
    ));
});

function handleEditColor(department) {
    editPanelDepartment.value = department;
}

function closeColorPanel() {
    editPanelDepartment.value = null;
}
</script>

<template>
    <div class="shell">
        <AppSidebar active="cadastros" />

        <div class="main">
            <header class="topbar">
                <h1>Departamentos</h1>
                <div class="head-actions">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Procurar departamento"
                    >
                </div>
            </header>

            <div class="content">
                <p v-if="flashSuccess" class="feedback success">
                    {{ flashSuccess }}
                </p>

                <section class="table-card">
                    <div class="table-head">
                        <span>Nome departamento</span>
                        <span>Cor</span>
                        <span>Ações</span>
                    </div>

                    <template v-if="props.departments.length === 0">
                        <div class="empty-state">
                            <p>Nenhum departamento cadastrado.</p>
                        </div>
                    </template>

                    <template v-else-if="filteredDepartments.length === 0">
                        <p class="search-empty">Nenhum departamento encontrado.</p>
                    </template>

                    <template v-else>
                        <DepartmentsTableRow
                            v-for="department in filteredDepartments"
                            :key="department.id"
                            :department="department"
                            @edit="handleEditColor"
                        />
                    </template>
                </section>
            </div>
        </div>

        <DepartmentColorEditPanel
            v-if="editPanelDepartment"
            :department="editPanelDepartment"
            @close="closeColorPanel"
        />
    </div>
</template>

<style scoped src="./styles/Departments.css"></style>
