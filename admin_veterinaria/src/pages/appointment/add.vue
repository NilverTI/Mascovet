<script setup>
const form = ref({
    date_appointment: null,
    time: null,
    amount: 0,
    method_payment: 'EFECTIVO',
    amount_add: 0,
})

const warning = ref(null)
const success = ref(null)
const error_exists = ref(null)

const method_payments = ref([
    'EFECTIVO',
    'DEPOSITO',
    'YAPE',
    'PLIN',
])

const veterinarie_time_availability = ref([])
const segment_time_veterinaries = ref([])
const selected_segment_times = ref([])
const veterinarie_id = ref(null)
const reason = ref(null)

// BUSCAR DISPONIBILIDAD
const filter = async () => {
    try {
        if (!form.value.date_appointment) {
            warning.value = 'Es necesario ingresar una fecha'
            return
        }

        const data = {
            date_appointment: form.value.date_appointment,
            hour: form.value.time,
        }

        const resp = await $api('/appointments/filter-availability', {
            method: 'POST',
            body: data,
            onResponseError({ response }) {
                console.log(response)
                error_exists.value = response._data.error
            },
        })

        console.log(resp)
        warning.value = null
        veterinarie_time_availability.value = resp.veterinarie_time_availability
    } catch (error) {
        console.log(error)
    }
}

const selectedSegmentHour = (veterinarie_time, segment_time_group) => {
    veterinarie_time.segment_times = segment_time_group.segment_times
    selected_segment_times.value = []
    segment_time_veterinaries.value = []
    console.log(selected_segment_times.value)
}

const reset = () => {
    form.value.date_appointment = null
    form.value.time = null
    veterinarie_time_availability.value = []
    segment_time_veterinaries.value = []
    selected_segment_times.value = []
    reason.value = null
}

// SELECCIONAR SEGMENTOS
const addSelectedSegmentTime = (veterinarie_time, segment_time) => {
    const INDEX = selected_segment_times.value.findIndex(
        item =>
            item.veterinarie_id == veterinarie_time.id &&
            item.segment_time_id == segment_time.veterinarie_schedule_hour_id,
    )

    if (INDEX != -1) {
        selected_segment_times.value.splice(INDEX, 1)
    } else {
        selected_segment_times.value.push({
            veterinarie_id: veterinarie_time.id,
            segment_time_id: segment_time.veterinarie_schedule_hour_id,
        })
    }

    veterinarie_id.value = veterinarie_time.id

    selected_segment_times.value = selected_segment_times.value.filter(item => {
        return item.veterinarie_id == veterinarie_time.id
    })

    segment_time_veterinaries.value = segment_time_veterinaries.value.filter(
        item => item.indexOf(veterinarie_time.id + '-') != -1,
    )

    console.log(selected_segment_times.value)
}

const select_pet = ref(null)

const fieldsClean = () => {
    form.value.date_appointment = null
    form.value.time = null
    form.value.amount = 0
    form.value.method_payment = 'EFECTIVO'
    form.value.amount_add = 0
    veterinarie_time_availability.value = []
    segment_time_veterinaries.value = []
    selected_segment_times.value = []
    select_pet.value = null
    reason.value = null
}

// GUARDAR CITA
const store = async () => {
    try {
        warning.value = null

        if (!form.value.date_appointment) {
            warning.value = 'El campo de fecha es requerido'
            return
        }
        if (selected_segment_times.value.length === 0) {
            warning.value = 'Es necesario asignarle un horario a la cita médica'
            return
        }
        if (!reason.value) {
            warning.value = 'Es requerido digitar una razón para la atención de la mascota'
            return
        }
        if (!select_pet.value) {
            warning.value = 'Es requerido seleccionar una mascota'
            return
        }
        if (!form.value.amount) {
            warning.value = 'Es requerido ingresar el costo de la cita médica'
            return
        }
        if (!form.value.amount_add) {
            warning.value = 'Es requerido ingresar un adelanto del costo de la cita médica'
            return
        }
        if (parseInt(form.value.amount_add) > parseInt(form.value.amount)) {
            warning.value = 'El monto de adelanto no puede ser mayor al costo de la cita'
            return
        }

        let STATE_PAY = 1 // PENDIENTE
        if (form.value.amount > form.value.amount_add) STATE_PAY = 2 // PARCIAL
        if (form.value.amount == form.value.amount_add) STATE_PAY = 3 // COMPLETO

        const data = {
            veterinarie_id: veterinarie_id.value,
            pet_id: select_pet.value.id,
            date_appointment: form.value.date_appointment,
            reason: reason.value,
            amount: form.value.amount,
            state_pay: STATE_PAY,
            method_payment: form.value.method_payment,
            adelanto: form.value.amount_add,
            selected_segment_times: selected_segment_times.value,
        }

        const resp = await $api('/appointments', {
            method: 'POST',
            body: data,
            onResponseError({ response }) {
                console.log(response)
                error_exists.value = response._data.error
            },
        })

        console.log(resp)
        success.value = 'La cita médica se ha programado correctamente'

        setTimeout(() => {
            success.value = null
            warning.value = null
            error_exists.value = null
            fieldsClean()
        }, 1500)
    } catch (error) {
        console.log(error)
    }
}

// 🔎 BUSQUEDA DEL PACIENTE (MASCOTA)
const loading = ref(false)
const search = ref()
const items = ref([])

const querySelections = async query => {
    loading.value = true

    setTimeout(async () => {
        const resp = await $api('/appointments/search-pets/' + query, {
            method: 'GET',
            onResponseError({ response }) {
                console.log(response)
                error_exists.value = response._data.error
            },
        })

        console.log(resp)
        items.value = resp.pets
        loading.value = false
    }, 500)
}

watch(search, query => {
    if (query && query.length > 1) {
        querySelections(query)
    } else {
        items.value = []
    }
})

definePage({
    meta: {
        permisssion: 'register_appointment',
    },
})
</script>

<template>
    <div>
        <VCardText class="pa-5">
            <div class="mb-1">
                <h4 class="text-h4 text-center mb-1">
                    Agregar Nueva Cita Médica
                </h4>
            </div>
        </VCardText>

        <!-- 🔎 Búsqueda -->
        <VCard title="🔎 Búsqueda:" class="pa-4">
            <VRow>
                <VCol cols="12" md="4">
                    <AppDateTimePicker v-model="form.date_appointment" label="Fecha de la cita" :config="{
                        minDate: 'today',
                        disable: [
                            date => {
                                // Deshabilita domingos (0) y sábados (6)
                                return date.getDay() === 0 || date.getDay() === 6
                            },
                        ],
                    }" />
                </VCol>

                <VCol cols="12" md="4">
                    <AppDateTimePicker v-model="form.time" label="Hora de la cita"
                        :config="{ enableTime: true, noCalendar: true, dateFormat: 'H:i' }" />
                </VCol>

                <VCol cols="12" md="4" class="d-flex align-end justify-end">
                    <VBtn color="info" class="mx-1" prepend-icon="ri-search-2-line" @click="filter()">
                        Buscar
                    </VBtn>
                    <VBtn color="secondary" class="mx-1" prepend-icon="ri-restart-line" @click="reset()">
                        Limpiar
                    </VBtn>
                </VCol>
            </VRow>
        </VCard>

        <!-- ALERTAS -->
        <VAlert type="warning" class="mt-3" v-if="warning">
            <strong>{{ warning }}</strong>
        </VAlert>

        <VAlert type="error" class="mt-3" v-if="error_exists">
            <strong>En el servidor hubo un error al guardar</strong>
        </VAlert>

        <VAlert type="success" class="mt-3" v-if="success">
            <strong>{{ success }}</strong>
        </VAlert>

        <!-- 📅 Disponibilidad -->
        <VCard title="📅 Disponibilidad:" class="pa-4 mt-4">
            <VRow>
                <!-- Tabla de horarios -->
                <VCol cols="12" md="8">
                    <VTable>
                        <thead>
                            <tr>
                                <th class="text-uppercase">
                                    Veterinario
                                </th>
                                <th class="text-uppercase">
                                    Tiempos activos
                                </th>
                                <th class="text-uppercase">
                                    Segmentos de tiempos
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <template v-for="(veterinarie_time, index) in veterinarie_time_availability" :key="index">
                                <tr>
                                    <td>
                                        {{ veterinarie_time.full_name }}
                                    </td>

                                    <td>
                                        <ul class="pa-0 ma-0">
                                            <li v-for="(segment_time_group, idx) in veterinarie_time.segment_time_groups"
                                                :key="idx" style="list-style: none;">
                                                <VBtn color="success" class="mx-1" prepend-icon="ri-file-add-line"
                                                    variant="text"
                                                    @click="selectedSegmentHour(veterinarie_time, segment_time_group)" />
                                                {{ segment_time_group.hour_format }} ({{
                                                    segment_time_group.count_availability }})
                                            </li>
                                        </ul>
                                    </td>

                                    <td>
                                        <ul class="pa-0 ma-0">
                                            <li v-for="(segment_time, idx) in veterinarie_time.segment_times" :key="idx"
                                                style="list-style: none;">
                                                <VCheckbox v-if="!segment_time.check"
                                                    @click="addSelectedSegmentTime(veterinarie_time, segment_time)"
                                                    v-model="segment_time_veterinaries"
                                                    :label="segment_time.schedule_hour.hour_start_format + ' ' + segment_time.schedule_hour.hour_end_format"
                                                    :value="veterinarie_time.id + '-' + segment_time.veterinarie_schedule_hour_id" />
                                                <span v-else style="font-weight: bold;">
                                                    {{ segment_time.schedule_hour.hour_start_format + ' ' +
                                                        segment_time.schedule_hour.hour_end_format }}
                                                </span>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </VTable>
                </VCol>

                <!-- Razón de la cita -->
                <VCol cols="12" md="4">
                    <VTextarea v-model="reason" label="Razón de la cita:"
                        placeholder="Describe brevemente el motivo de la atención" auto-grow />
                </VCol>
            </VRow>
        </VCard>

        <!-- 🐶 Paciente -->
        <VCard title="🐶 Paciente:" class="pa-4 mt-4">
            <VRow>
                <VCol cols="12" md="6">
                    <VAutocomplete v-model="select_pet" v-model:search="search" :loading="loading" :items="items"
                        item-title="name" item-value="id" return-object placeholder="Ingresa información de la mascota"
                        label="¿Quién es la mascotita?" :menu-props="{ maxHeight: '200px' }" />
                </VCol>

                <VCol cols="12" md="3" v-if="select_pet">
                    <label>Especie: {{ select_pet.specie }}</label>
                    <br>
                    <label>Raza: {{ select_pet.breed }}</label>
                </VCol>

                <VCol cols="12" md="3" v-if="select_pet">
                    <label>Nombre y Apellido: {{ select_pet.owner.first_name + ' ' + select_pet.owner.last_name
                    }}</label>
                    <br>
                    <label>Teléfono: {{ select_pet.owner.phone }}</label>
                    <br>
                    <label>Documento: {{ select_pet.owner.n_document }}</label>
                </VCol>
            </VRow>
        </VCard>

        <!-- 💵 Pagos -->
        <VCard title="💵 Pagos:" class="pa-4 mt-4">
            <VRow>
                <VCol cols="12" md="4">
                    <VTextField label="Costo de la cita:" type="number" placeholder="Ejemplo: 100"
                        v-model="form.amount" />
                </VCol>

                <VCol cols="12" md="4">
                    <VSelect v-model="form.method_payment" :items="method_payments" label="Método de pago"
                        placeholder="Selecciona un método" eager />
                </VCol>

                <VCol cols="12" md="4">
                    <VTextField v-model="form.amount_add" label="Adelanto:" type="number" placeholder="Ejemplo: 50" />
                </VCol>
            </VRow>
        </VCard>

        <VCardText class="pa-5 py-0 text-end">
            <VBtn color="primary" class="mb-4" @click="store()">
                Crear
            </VBtn>
        </VCardText>
    </div>
</template>
