<script setup>
const router = useRouter()

// ─────────────────────────
// Filtros
// ─────────────────────────
const searchPets = ref(null)
const searchVets = ref(null)
const specie = ref(null)

const species = ref([
    'Perro', 'Gato', 'Hámster', 'Loro', 'Tortuga', 'Vaca', 'Caballo', 'Cuy', 'Toro'
])

const appointments = ref([])
const currentPage = ref(1)
const totalPage = ref(1)

const appointment_selected_deleted = ref(null)
const isDeleteAppointmentDialogVisible = ref(false)

const dateRange = ref(null)
const type_date = ref(1)
const state_pay = ref(null)
const state_appointment = ref(null)

// ─────────────────────────
// Helpers: formato de rango de fechas
// ─────────────────────────
const getRange = (val) => {
    if (!val) return { start: null, end: null }

    // Caso Flatpicker devuelve ARRAY: ["2025-01-01","2025-01-10"]
    if (Array.isArray(val)) {
        return {
            start: val[0] ?? null,
            end: val[1] ?? null
        }
    }

    // Caso Flatpicker devuelve STRING: "2025-01-01 to 2025-01-10"
    const [s, e] = val.split('to')
    return {
        start: s?.trim() || null,
        end: e?.trim() || null,
    }
}

// ─────────────────────────
// LISTA
// ─────────────────────────
const list = async () => {
    try {
        const { start, end } = getRange(dateRange.value)

        const data = {
            type_date: dateRange.value ? type_date.value : null,
            start_date: start,
            end_date: end,
            state_pay: state_pay.value,
            state: state_appointment.value,
            specie: specie.value,
            search_pets: searchPets.value,
            search_vets: searchVets.value,
        }

        console.log("📤 Enviando filtros:", data)

        const resp = await $api('/appointments/index?page=' + currentPage.value, {
            method: 'POST',
            body: data,
            onResponseError({ response }) {
                console.log("❌ ERROR API:", response)
            }
        })

        console.log("📥 Respuesta LIST:", resp)

        // Acepta múltiples estructuras sin romper:
        totalPage.value = resp.total_page ??
            resp.appointments?.last_page ??
            1

        appointments.value =
            resp.appointments?.data ??
            resp.appointments ??
            resp.data ??
            []

    } catch (error) {
        console.log("❌ ERROR list():", error)
    }
}

// ─────────────────────────
// Acciones
// ─────────────────────────
const downloadExcel = () => {
    let LINK = ""

    if (dateRange.value) {
        const { start, end } = getRange(dateRange.value)
        LINK += `&type_date=${type_date.value}`
        LINK += `&start_date=${start}`
        LINK += `&end_date=${end}`
    }
    if (state_pay.value) LINK += `&state_pay=${state_pay.value}`
    if (state_appointment.value) LINK += `&state=${state_appointment.value}`
    if (specie.value) LINK += `&specie=${specie.value}`
    if (searchPets.value) LINK += `&search_pets=${searchPets.value}`
    if (searchVets.value) LINK += `&search_vets=${searchVets.value}`

    window.open(import.meta.env.VITE_API_BASE_URL + "/appointment-excel?k=1" + LINK, "_blank")
}

const editItem = (item) => {
    router.push({
        name: 'appointment-edit-id',
        params: { id: item.id }
    })
}

const deleteItem = (item) => {
    appointment_selected_deleted.value = item
    isDeleteAppointmentDialogVisible.value = true
}

const deleteAppointment = (item) => {
    const index = appointments.value.findIndex(a => a.id === item.id)
    if (index !== -1) appointments.value.splice(index, 1)
}

const reset = () => {
    searchPets.value = null
    searchVets.value = null
    specie.value = null
    dateRange.value = null
    state_pay.value = null
    state_appointment.value = null
    type_date.value = 1
    currentPage.value = 1
    list()
}

const avatarText = (value) => {
    if (!value) return ''
    return value
        .split(' ')
        .map(w => w.charAt(0).toUpperCase())
        .join('')
}

// Watchers
watch(currentPage, () => list())

watch(isDeleteAppointmentDialogVisible, (v) => {
    if (!v) appointment_selected_deleted.value = null
})

// Load al abrir pantalla
onMounted(() => list())

definePage({
    meta: {
        permisssion: 'list_appointment'
    }
})
</script>

<template>
    <div>
        <VCard title="Citas">

            <!-- FILTROS -->
            <VCardText class="d-flex flex-wrap gap-4">
                <VRow>
                    <VCol cols="2">
                        <VSelect :items="[
                            { name: 'Fecha de la cita', id: 1 },
                            { name: 'Fecha de registro', id: 2 }
                        ]" v-model="type_date" label="Tipo" item-title="name" item-value="id" eager />
                    </VCol>

                    <VCol cols="3">
                        <AppDateTimePicker v-model="dateRange" label="Rango de fechas" :config="{ mode: 'range' }" />
                    </VCol>

                    <VCol cols="2">
                        <VSelect :items="species" v-model="specie" label="Especie" placeholder="Selecciona especie" />
                    </VCol>

                    <VCol cols="2">
                        <div class="d-flex">
                            <VBtn color="info" class="mx-1" prepend-icon="ri-search-2-line" @click="list()" />
                            <VBtn color="secondary" class="mx-1" prepend-icon="ri-restart-line" @click="reset()" />
                            <VBtn color="success" class="mx-1" prepend-icon="ri-file-excel-2-line"
                                @click="downloadExcel()" />
                        </div>
                    </VCol>

                    <VCol cols="2">
                        <VBtn color="primary" prepend-icon="ri-add-line" v-if="isPermission('register_appointment')"
                            @click="router.push({ name: 'appointment-add' })">
                            Agregar Cita
                        </VBtn>
                    </VCol>

                    <VCol cols="2">
                        <VSelect :items="[
                            { name: 'Pendiente', id: 1 },
                            { name: 'Cancelado', id: 2 },
                            { name: 'Atendido', id: 3 }
                        ]" v-model="state_appointment" label="Estado de cita" item-title="name" item-value="id" />
                    </VCol>

                    <VCol cols="3">
                        <VTextField v-model="searchPets" placeholder="Buscar mascotas..." density="compact"
                            @keyup.enter="list" />
                    </VCol>

                    <VCol cols="3">
                        <VTextField v-model="searchVets" placeholder="Buscar veterinarios..." density="compact"
                            @keyup.enter="list" />
                    </VCol>

                    <VCol cols="2">
                        <VSelect :items="[
                            { name: 'Pendiente', id: 1 },
                            { name: 'Parcial', id: 2 },
                            { name: 'Completo', id: 3 },
                        ]" v-model="state_pay" label="Estado de pago" item-title="name" item-value="id" />
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
                            <th>Fecha</th>
                            <th>Veterinario</th>
                            <th>Estado</th>
                            <th>Costo</th>
                            <th>Acción</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="item in appointments" :key="item.id">
                            <td>
                                <div class="d-flex align-center">
                                    <VAvatar size="32" :color="item.pet.photo ? '' : 'primary'">
                                        <VImg v-if="item.pet.photo" :src="item.pet.photo" />
                                        <span v-else>{{ avatarText(item.pet.name) }}</span>
                                    </VAvatar>

                                    <div class="ms-3">
                                        <span class="font-weight-medium">{{ item.pet.name }}</span>
                                    </div>
                                </div>
                            </td>

                            <td>{{ item.pet.specie }}</td>
                            <td>{{ item.date_appointment }}</td>
                            <td>{{ item.veterinarie.full_name }}</td>

                            <td>
                                <VChip v-if="item.state == 1" color="warning">Pendiente</VChip>
                                <VChip v-if="item.state == 2" color="danger">Cancelado</VChip>
                                <VChip v-if="item.state == 3" color="primary">Atendido</VChip>
                            </td>

                            <td>{{ item.amount }} PEN</td>

                            <td>
                                <div class="d-flex gap-1">
                                    <IconBtn size="small" v-if="isPermission('edit_appointment')"
                                        @click="editItem(item)">
                                        <VIcon icon="ri-pencil-line" />
                                    </IconBtn>
                                    <IconBtn size="small" v-if="isPermission('delete_appointment')"
                                        @click="deleteItem(item)">
                                        <VIcon icon="ri-delete-bin-line" />
                                    </IconBtn>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </VTable>

                <VPagination v-model="currentPage" :length="totalPage" />
            </VCardText>

            <DeleteAppoinmentDialog v-if="appointment_selected_deleted"
                :appointmentSelected="appointment_selected_deleted" @deleteAppointment="deleteAppointment"
                v-model:is-dialog-visible="isDeleteAppointmentDialogVisible" />
        </VCard>
    </div>
</template>

<style>
.v-btn__prepend {
    margin-inline: 0 !important;
}
</style>
