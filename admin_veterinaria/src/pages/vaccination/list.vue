<script setup>
const router = useRouter()

// ─────────────────────────────
// Filtros
// ─────────────────────────────
const searchPets = ref(null)
const searchVets = ref(null)
const specie = ref(null)
const species = ref(['Perro', 'Gato', 'Hámster', 'Loro', 'Tortuga', 'Vaca', 'Caballo', 'Cuy', 'Toro'])

const dateRange = ref(null)
const type_date = ref(1)
const state_pay = ref(null)
const state_vaccination = ref(null)

// ─────────────────────────────
// Listado y paginación
// ─────────────────────────────
const vaccinations = ref([])
const currentPage = ref(1)
const totalPage = ref(1)

const vaccination_selected_deleted = ref(null)
const isDeleteVaccinationDialogVisible = ref(false)

// ─────────────────────────────
// Listar vacunaciones
// ─────────────────────────────
const list = async () => {
    const data = {
        type_date: type_date.value,
        start_date: dateRange.value ? dateRange.value.split("to")[0] : null,
        end_date: dateRange.value ? dateRange.value.split("to")[1] : null,
        state_pay: state_pay.value,
        state: state_vaccination.value,
        specie: specie.value,
        search_pets: searchPets.value,
        search_vets: searchVets.value,
    }

    const resp = await $api(`/vaccinations/index?page=${currentPage.value}`, {
        method: "POST",
        body: data,
        onResponseError({ response }) {
            console.log(response)
        },
    })

    totalPage.value = resp.total_page
    vaccinations.value = resp.vaccinations.data
}

const reset = () => {
    searchPets.value = null
    searchVets.value = null
    specie.value = null
    dateRange.value = null
    state_pay.value = null
    state_vaccination.value = null
    type_date.value = 1
    currentPage.value = 1
    list()
}

// ─────────────────────────────
// Excel
// ─────────────────────────────
const downloadExcel = () => {
    let link = ""

    if (dateRange.value) {
        link += `&type_date=${type_date.value}`
        link += `&start_date=${dateRange.value.split("to")[0]}`
        link += `&end_date=${dateRange.value.split("to")[1]}`
    }

    if (state_pay.value) link += `&state_pay=${state_pay.value}`
    if (state_vaccination.value) link += `&state=${state_vaccination.value}`
    if (specie.value) link += `&specie=${specie.value}`
    if (searchPets.value) link += `&search_pets=${searchPets.value}`
    if (searchVets.value) link += `&search_vets=${searchVets.value}`

    window.open(`${import.meta.env.VITE_API_BASE_URL}/vaccination-excel?k=1${link}`, "_blank")
}

// ─────────────────────────────
// Acciones CRUD
// ─────────────────────────────
const editItem = item => {
    router.push({
        name: "vaccination-edit-id",
        params: { id: item.id },
    })
}

const deleteItem = item => {
    vaccination_selected_deleted.value = item
    isDeleteVaccinationDialogVisible.value = true
}

const deleteVaccination = (item) => {
    const index = vaccinations.value.findIndex(v => v.id == item.id)
    if (index !== -1) vaccinations.value.splice(index, 1)
}

// Render avatar initials
const avatarText = value => {
    if (!value) return ""
    return value.split(" ").map(w => w[0].toUpperCase()).join("")
}

// ─────────────────────────────
// Watchers
// ─────────────────────────────
watch(currentPage, () => list())
watch(isDeleteVaccinationDialogVisible, val => {
    if (!val) vaccination_selected_deleted.value = null
})

// ─────────────────────────────
// On Mounted
// ─────────────────────────────
onMounted(() => list())

definePage({
    meta: {
        permisssion: "list_vaccionation",
    },
})
</script>

<template>
    <div>
        <VCard title="Vacunas">

            <!-- 🔎 FILTROS -->
            <VCardText>
                <VRow>

                    <!-- Tipo de fecha -->
                    <VCol cols="12" md="2">
                        <VSelect label="Tipo" :items="[
                            { name: 'Fecha de la vacuna', id: 1 },
                            { name: 'Fecha de registro', id: 2 },
                        ]" v-model="type_date" item-title="name" item-value="id" />
                    </VCol>

                    <!-- Fecha rango -->
                    <VCol cols="12" md="3">
                        <AppDateTimePicker v-model="dateRange" label="Rango de fechas" :config="{ mode: 'range' }" />
                    </VCol>

                    <!-- Especie -->
                    <VCol cols="12" md="2">
                        <VSelect v-model="specie" :items="species" label="Especie" />
                    </VCol>

                    <!-- Botones búsqueda -->
                    <VCol cols="12" md="3" class="d-flex align-end">
                        <VBtn color="info" prepend-icon="ri-search-2-line" class="mr-2" @click="list" />
                        <VBtn color="secondary" prepend-icon="ri-restart-line" class="mr-2" @click="reset" />
                        <VBtn color="success" prepend-icon="ri-file-excel-2-line" @click="downloadExcel" />
                    </VCol>

                    <!-- Registrar -->
                    <VCol cols="12" md="2" class="d-flex align-end">
                        <VBtn color="primary" prepend-icon="ri-add-line" v-if="isPermission('register_vaccionation')"
                            block @click="router.push({ name: 'vaccination-add' })">
                            Nueva Vacuna
                        </VBtn>
                    </VCol>

                    <!-- Estado vacuna -->
                    <VCol cols="12" md="2">
                        <VSelect label="Estado vacuna" v-model="state_vaccination" :items="[
                            { name: 'Pendiente', id: 1 },
                            { name: 'Cancelado', id: 2 },
                            { name: 'Atendido', id: 3 },
                        ]" item-title="name" item-value="id" />
                    </VCol>

                    <!-- Buscar mascota -->
                    <VCol cols="12" md="3">
                        <VTextField v-model="searchPets" placeholder="Buscar mascota" @keyup.enter="list" />
                    </VCol>

                    <!-- Buscar veterinario -->
                    <VCol cols="12" md="3">
                        <VTextField v-model="searchVets" placeholder="Buscar veterinario" @keyup.enter="list" />
                    </VCol>

                    <!-- Estado pago -->
                    <VCol cols="12" md="2">
                        <VSelect label="Estado pago" v-model="state_pay" :items="[
                            { name: 'Pendiente', id: 1 },
                            { name: 'Parcial', id: 2 },
                            { name: 'Completo', id: 3 },
                        ]" item-title="name" item-value="id" />
                    </VCol>

                </VRow>
            </VCardText>

            <!-- 🧾 TABLA -->
            <VCardText class="pa-5">
                <VTable>
                    <thead>
                        <tr>
                            <th>Mascota</th>
                            <th>Especie</th>
                            <th>Fecha</th>
                            <th>Veterinario</th>
                            <th>Estado</th>
                            <th>Costo</th>
                            <th>Acción</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="item in vaccinations" :key="item.id">

                            <!-- Mascota -->
                            <td>
                                <div class="d-flex align-center">
                                    <VAvatar size="32" :color="item.pet.photo ? '' : 'primary'"
                                        :variant="item.pet.photo ? undefined : 'tonal'" class="mr-3">
                                        <VImg v-if="item.pet.photo" :src="item.pet.photo" />
                                        <span v-else>{{ avatarText(item.pet.name) }}</span>
                                    </VAvatar>

                                    <span class="font-weight-medium">{{ item.pet.name }}</span>
                                </div>
                            </td>

                            <td>{{ item.pet.specie }}</td>
                            <td>{{ item.vaccination_date }}</td>
                            <td>{{ item.veterinarie.full_name }}</td>

                            <!-- Estado -->
                            <td>
                                <VChip v-if="item.state == 1" color="warning">Pendiente</VChip>
                                <VChip v-if="item.state == 2" color="error">Cancelado</VChip>
                                <VChip v-if="item.state == 3" color="primary">Atendido</VChip>
                            </td>

                            <td>{{ item.amount }} PEN</td>

                            <!-- Acciones -->
                            <td>
                                <div class="d-flex gap-1">
                                    <IconBtn size="small" v-if="isPermission('edit_vaccionation')"
                                        @click="editItem(item)">
                                        <VIcon icon="ri-pencil-line" />
                                    </IconBtn>

                                    <IconBtn size="small" v-if="isPermission('delete_vaccionation')"
                                        @click="deleteItem(item)">
                                        <VIcon icon="ri-delete-bin-line" />
                                    </IconBtn>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </VTable>

                <!-- PAGINACIÓN -->
                <VPagination v-model="currentPage" :length="totalPage" class="mt-4" />
            </VCardText>

            <!-- DIALOGO ELIMINAR -->
            <DeleteVaccinationDialog v-if="vaccination_selected_deleted"
                :vaccinationSelected="vaccination_selected_deleted" @deleteVaccination="deleteVaccination"
                v-model:is-dialog-visible="isDeleteVaccinationDialogVisible" />
        </VCard>
    </div>
</template>

<style>
.v-btn__prepend {
    margin-inline: 0 !important;
}
</style>
