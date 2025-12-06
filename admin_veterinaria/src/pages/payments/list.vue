<script setup>
import { isPermission } from '@/utils/constants'
const router = useRouter()

// ─────────────────────────────
// Constantes / opciones
// ─────────────────────────────
const species = ref(['Perro', 'Gato', 'Hámster', 'Loro', 'Tortuga', 'Vaca', 'Caballo', 'Cuy', 'Toro'])
const type_services = ref([
    { id: 1, name: 'Citas medicas' },
    { id: 2, name: 'Vacunas' },
    { id: 3, name: 'Cirujía' },
])

// ─────────────────────────────
// Filtros
// ─────────────────────────────
const searchPets = ref(null)
const searchVets = ref(null)
const specie = ref(null)
const type_service = ref(null)

const dateRange = ref(null)
const type_date = ref(1)
const state_pay = ref(null)
const state_vaccination = ref(null)

// ─────────────────────────────
// Listado y paginación
// ─────────────────────────────
const payments = ref([])
const currentPage = ref(1)
const totalPage = ref(1)

// ─────────────────────────────
// Diálogos / selección
// ─────────────────────────────
const medical_record_selected_deleted = ref(null)
const payment_delete_selected = ref(null)
const isDeletePaymentDialogVisible = ref(false)

const medical_record_selected = ref(null)
const payment_edit_selected = ref(null)
const isAddPaymentDialogVisible = ref(false)
const isEditPaymentDialogVisible = ref(false)

// ─────────────────────────────
// Listar registros (paginated)
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
        type_service: type_service.value,
    }

    const resp = await $api(`/payments/index?page=${currentPage.value}`, {
        method: 'POST',
        body: data,
        onResponseError({ response }) {
            console.log(response)
        },
    })

    totalPage.value = resp.total_page
    payments.value = resp.medical_records.data
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
    if (type_service.value) link += `&type_service=${type_service.value}`

    window.open(`${import.meta.env.VITE_API_BASE_URL}/payments-excel?k=1${link}`, '_blank')
}

// ─────────────────────────────
// Acciones: Crear / Editar / Eliminar pagos
// ─────────────────────────────
const createPayment = (item) => {
    medical_record_selected.value = item
    isAddPaymentDialogVisible.value = true
}

const addPayment = (updatedMedicalRecord) => {
    const idx = payments.value.findIndex(p => p.id == updatedMedicalRecord.id)
    if (idx !== -1) {
        updatedMedicalRecord.is_view = true
        payments.value[idx] = updatedMedicalRecord
    }
}

const editPayment = (medicalRecord, payment) => {
    medical_record_selected.value = medicalRecord
    payment_edit_selected.value = payment
    isEditPaymentDialogVisible.value = true
}

const updatedPayment = (updatedMedicalRecord) => {
    const idx = payments.value.findIndex(p => p.id == updatedMedicalRecord.id)
    if (idx !== -1) {
        updatedMedicalRecord.is_view = true
        payments.value[idx] = updatedMedicalRecord
    }
}

const deleteItem = (medicalRecord, payment) => {
    medical_record_selected_deleted.value = medicalRecord
    payment_delete_selected.value = payment
    isDeletePaymentDialogVisible.value = true
}

const deletePayment = (updatedMedicalRecord) => {
    const idx = payments.value.findIndex(p => p.id == updatedMedicalRecord.id)
    if (idx !== -1) {
        updatedMedicalRecord.is_view = true
        payments.value[idx] = updatedMedicalRecord
    }
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
    type_service.value = null
    list()
}

// ─────────────────────────────
// Utilidades
// ─────────────────────────────
const avatarText = (value) => {
    if (!value) return ''
    return value.split(' ').map(w => w.charAt(0).toUpperCase()).join('')
}

// ─────────────────────────────
// Watchers y lifecycle
// ─────────────────────────────
watch(currentPage, () => list())
watch(isDeletePaymentDialogVisible, (val) => {
    if (!val) {
        medical_record_selected_deleted.value = null
        payment_delete_selected.value = null
    }
})
watch(isAddPaymentDialogVisible, (val) => {
    if (!val) medical_record_selected.value = null
})
watch(isEditPaymentDialogVisible, (val) => {
    if (!val) {
        medical_record_selected.value = null
        payment_edit_selected.value = null
    }
})

onMounted(() => list())

definePage({
    meta: {
        permisssion: 'show_payment'
    },
})
</script>

<template>
    <div>
        <VCard title="💵 Pagos">

            <!-- FILTROS -->
            <VCardText>
                <VRow>

                    <!-- Tipo de servicio -->
                    <VCol cols="12" md="2">
                        <VSelect v-model="type_service" :items="type_services" label="Tipo de servicio"
                            item-title="name" item-value="id" />
                    </VCol>

                    <!-- Tipo de fecha -->
                    <VCol cols="12" md="2">
                        <VSelect v-model="type_date" :items="[
                            { name: 'Fecha del servicio', id: 1 },
                            { name: 'Fecha de registro', id: 2 }
                        ]" label="Tipo" item-title="name" item-value="id" />
                    </VCol>

                    <!-- Rango fechas -->
                    <VCol cols="12" md="3">
                        <AppDateTimePicker v-model="dateRange" label="Fechas del servicio"
                            :config="{ mode: 'range' }" />
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

                    <!-- Estado del servicio (solo si hay tipo seleccionado) -->
                    <VCol cols="12" md="2" v-if="type_service">
                        <VSelect v-model="state_vaccination" :items="[
                            { name: 'Pendiente', id: 1 },
                            { name: 'Cancelado', id: 2 },
                            { name: 'Atendido', id: 3 }
                        ]" label="Estado del servicio" item-title="name" item-value="id" />
                    </VCol>

                    <!-- Estado de pago (solo si hay tipo seleccionado) -->
                    <VCol cols="12" md="2" v-if="type_service">
                        <VSelect v-model="state_pay" :items="[
                            { name: 'Pendiente', id: 1 },
                            { name: 'Parcial', id: 2 },
                            { name: 'Completo', id: 3 }
                        ]" label="Estado de pago" item-title="name" item-value="id" />
                    </VCol>

                    <!-- Buscadores -->
                    <VCol cols="12" md="3">
                        <VTextField v-model="searchPets" placeholder="Buscar mascota" @keyup.enter="list" />
                    </VCol>

                    <VCol cols="12" md="3">
                        <VTextField v-model="searchVets" placeholder="Buscar veterinario" @keyup.enter="list" />
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
                            <th>Fecha del servicio</th>
                            <th>Tipo de servicio</th>
                            <th>Veterinario</th>
                            <th>Costo</th>
                            <th>Monto cancelado</th>
                            <th>Estado de pago</th>
                            <th>Acción</th>
                        </tr>
                    </thead>

                    <tbody>
                        <template v-for="item in payments" :key="item.id">
                            <tr>
                                <td>
                                    <div class="d-flex align-center">
                                        <VAvatar size="32" :color="item.pet.photo ? '' : 'primary'"
                                            :variant="item.pet.photo ? undefined : 'tonal'" class="me-3">
                                            <VImg v-if="item.pet.photo" :src="item.pet.photo" />
                                            <span v-else>{{ avatarText(item.pet.name) }}</span>
                                        </VAvatar>
                                        <div class="d-flex flex-column">
                                            <span class="font-weight-medium text-truncate">{{ item.pet.name }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td>{{ item.pet.specie }}</td>
                                <td>{{ item.event_date }}</td>

                                <td>
                                    <VChip v-if="item.event_type == 1" color="primary">Cita Medica</VChip>
                                    <VChip v-if="item.event_type == 2" color="warning">Vacuna</VChip>
                                    <VChip v-if="item.event_type == 3" color="success">Cirujía</VChip>
                                </td>

                                <td>{{ item.veterinarie.full_name }}</td>
                                <td style="white-space: nowrap">{{ item.amount }} PEN</td>
                                <td style="white-space: nowrap">{{ item.payment_total }} PEN</td>

                                <td>
                                    <VChip v-if="item.state_pay == 1" color="danger">Pendiente</VChip>
                                    <VChip v-if="item.state_pay == 2" color="warning">Parcial</VChip>
                                    <VChip v-if="item.state_pay == 3" color="success">Completo</VChip>
                                </td>

                                <td>
                                    <div class="d-flex justify-end gap-2">

                                        <!-- Botón para expandir/ocultar detalles -->
                                        <VBtn color="success" variant="text"
                                            :prepend-icon="item.is_view ? 'ri-subtract-line' : 'ri-add-line'"
                                            @click="item.is_view = !item.is_view" title="Ver detalles de pagos" />

                                        <!-- Botón para agregar pagos -->
                                        <VBtn color="primary" variant="text" prepend-icon="ri-add-circle-line"
                                            @click="createPayment(item)" title="Agregar pago" />

                                    </div>
                                </td>

                            </tr>

                            <!-- Detalle de pagos -->
                            <template v-for="(payment, idx) in item.payments" :key="`${item.id}-pay-${idx}`">
                                <tr v-if="item.is_view">
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td>{{ payment.method_payment }}</td>
                                    <td></td>
                                    <td>{{ payment.amount }} PEN</td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <IconBtn size="small" @click="editPayment(item, payment)">
                                                <VIcon icon="ri-pencil-line" />
                                            </IconBtn>

                                            <IconBtn size="small" @click="deleteItem(item, payment)">
                                                <VIcon icon="ri-delete-bin-line" />
                                            </IconBtn>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </template>
                    </tbody>
                </VTable>

                <VPagination v-model="currentPage" :length="totalPage" class="mt-4" />
            </VCardText>

            <!-- DIALOGS -->
            <AddPaymentDialog v-if="medical_record_selected" :medicalRecord="medical_record_selected"
                v-model:is-dialog-visible="isAddPaymentDialogVisible" @addPayment="addPayment" />

            <EditPaymentDialog v-if="medical_record_selected && payment_edit_selected"
                :medicalRecord="medical_record_selected" :paymentSelected="payment_edit_selected"
                v-model:is-dialog-visible="isEditPaymentDialogVisible" @editPayment="updatedPayment" />

            <DeletePaymentDialog v-if="medical_record_selected_deleted" :medicalRecord="medical_record_selected_deleted"
                :paymentSelected="payment_delete_selected" v-model:is-dialog-visible="isDeletePaymentDialogVisible"
                @deletePayment="deletePayment" />
        </VCard>
    </div>
</template>

<style>
.v-btn__prepend {
    margin-inline: 0 !important;
}
</style>
