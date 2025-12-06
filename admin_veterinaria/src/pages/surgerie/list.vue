<script setup>
import { isPermission } from '@/utils/constants'
const router = useRouter()

// ─────────────────────────────
// Datos fijos / opciones
// ─────────────────────────────
const species = ref(['Perro', 'Gato', 'Hámster', 'Loro', 'Tortuga', 'Vaca', 'Caballo', 'Cuy', 'Toro'])
const surgerie_types = ref([
    "ESTERILIZACIÓN",
    "CASTRACIÓN",
    "TRAUMATOLÓGICAS",
    "OCULARES",
    "ONCOLÓGICAS",
    "OTRO"
])

// ─────────────────────────────
// Filtros
// ─────────────────────────────
const searchPets = ref(null)
const searchVets = ref(null)
const specie = ref(null)

const dateRange = ref(null)
const type_date = ref(1)
const state_pay = ref(null)
const state_vaccination = ref(null)

// ─────────────────────────────
// Listado y paginación
// ─────────────────────────────
const surgeries = ref([])
const currentPage = ref(1)
const totalPage = ref(1)

// ─────────────────────────────
// Eliminación
// ─────────────────────────────
const surgerie_selected_deleted = ref(null)
const isDeleteSurgerieDialogVisible = ref(false)

// ─────────────────────────────
// Listar cirugías
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

    const resp = await $api(`/surgeries/index?page=${currentPage.value}`, {
        method: 'POST',
        body: data,
        onResponseError({ response }) {
            console.log(response)
        }
    })

    totalPage.value = resp.total_page
    surgeries.value = resp.surgeries.data
}

// ─────────────────────────────
// Reset filtros
// ─────────────────────────────
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
// Descargar Excel
// ─────────────────────────────
const downloadExcel = () => {
    let link = ''

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

    window.open(`${import.meta.env.VITE_API_BASE_URL}/surgeries-excel?k=1${link}`, '_blank')
}

// ─────────────────────────────
// CRUD actions
// ─────────────────────────────
const editItem = (item) => {
    router.push({
        name: 'surgerie-edit-id',
        params: { id: item.id }
    })
}

const deleteItem = (item) => {
    surgerie_selected_deleted.value = item
    isDeleteSurgerieDialogVisible.value = true
}

const deleteSurgerie = (item) => {
    const index = surgeries.value.findIndex(s => s.id == item.id)
    if (index !== -1) surgeries.value.splice(index, 1)
}

// ─────────────────────────────
// Utilidades
// ─────────────────────────────
const avatarText = (value) => {
    if (!value) return ''
    return value.split(' ').map(w => w.charAt(0).toUpperCase()).join('')
}

// ─────────────────────────────
// Watchers y mounted
// ─────────────────────────────
watch(currentPage, () => list())
watch(isDeleteSurgerieDialogVisible, (val) => {
    if (!val) surgerie_selected_deleted.value = null
})

onMounted(() => list())

definePage({
    meta: {
        permisssion: 'list_surgeries'
    },
})
</script>

<template>
    <div>
        <VCard title="Cirugías">
            <!-- FILTROS -->
            <VCardText>
                <VRow>

                    <!-- Tipo de fecha -->
                    <VCol cols="12" md="2">
                        <VSelect label="Tipo" :items="[
                            { name: 'Fecha de la cirugía', id: 1 },
                            { name: 'Fecha de registro', id: 2 },
                        ]" v-model="type_date" item-title="name" item-value="id" />
                    </VCol>

                    <!-- Rango de fechas -->
                    <VCol cols="12" md="3">
                        <AppDateTimePicker v-model="dateRange" label="Fechas de cirugía" :config="{ mode: 'range' }" />
                    </VCol>

                    <!-- Especie -->
                    <VCol cols="12" md="2">
                        <VSelect v-model="specie" :items="species" label="Especie" />
                    </VCol>

                    <!-- Botones -->
                    <VCol cols="12" md="3" class="d-flex align-end">
                        <VBtn color="info" prepend-icon="ri-search-2-line" class="me-2" @click="list" />
                        <VBtn color="secondary" prepend-icon="ri-restart-line" class="me-2" @click="reset" />
                        <VBtn color="success" prepend-icon="ri-file-excel-2-line" @click="downloadExcel" />
                    </VCol>

                    <!-- Registrar -->
                    <VCol cols="12" md="2" class="d-flex align-end">
                        <VBtn color="primary" prepend-icon="ri-add-line" v-if="isPermission('register_surgeries')" block
                            @click="router.push({ name: 'surgerie-add' })">
                            Agregar Cirugía
                        </VBtn>
                    </VCol>

                    <!-- Estado de la cirugía -->
                    <VCol cols="12" md="2">
                        <VSelect label="Estado de la cirugía" v-model="state_vaccination" :items="[
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

            <!-- TABLA -->
            <VCardText class="pa-5">
                <VTable>
                    <thead>
                        <tr>
                            <th>Mascota</th>
                            <th>Especie</th>
                            <th>Fecha de la cirugía</th>
                            <th>Veterinario</th>
                            <th>Estado</th>
                            <th>Costo</th>
                            <th>Acción</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="item in surgeries" :key="item.id">
                            <td>
                                <div class="d-flex align-center">
                                    <VAvatar size="32" :color="item.pet.photo ? '' : 'primary'"
                                        :variant="item.pet.photo ? undefined : 'tonal'" class="me-3">
                                        <VImg v-if="item.pet.photo" :src="item.pet.photo" />
                                        <span v-else class="text-sm">{{ avatarText(item.pet.name) }}</span>
                                    </VAvatar>

                                    <div class="d-flex flex-column">
                                        <span class="font-weight-medium text-truncate">{{ item.pet.name }}</span>
                                    </div>
                                </div>
                            </td>

                            <td>{{ item.pet.specie }}</td>
                            <td>{{ item.surgerie_date }}</td>
                            <td>{{ item.veterinarie.full_name }}</td>

                            <td>
                                <VChip v-if="item.state == 1" color="warning">Pendiente</VChip>
                                <VChip v-if="item.state == 2" color="error">Cancelado</VChip>
                                <VChip v-if="item.state == 3" color="primary">Atendido</VChip>
                            </td>

                            <td>{{ item.amount }} PEN</td>

                            <td>
                                <div class="d-flex gap-1">
                                    <IconBtn size="small" v-if="isPermission('edit_surgeries')" @click="editItem(item)">
                                        <VIcon icon="ri-pencil-line" />
                                    </IconBtn>

                                    <IconBtn size="small" v-if="isPermission('delete_surgeries')"
                                        @click="deleteItem(item)">
                                        <VIcon icon="ri-delete-bin-line" />
                                    </IconBtn>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </VTable>

                <VPagination v-model="currentPage" :length="totalPage" class="mt-4" />
            </VCardText>

            <!-- DIALOGO ELIMINAR -->
            <DeleteSurgerieDialog v-if="surgerie_selected_deleted" :surgerieSelected="surgerie_selected_deleted"
                @deleteSurgerie="deleteSurgerie" v-model:is-dialog-visible="isDeleteSurgerieDialogVisible" />
        </VCard>
    </div>
</template>

<style>
.v-btn__prepend {
    margin-inline: 0 !important;
}
</style>
