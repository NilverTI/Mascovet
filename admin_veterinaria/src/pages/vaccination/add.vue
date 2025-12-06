<script setup>
const form = ref({
    vaccination_date: null,
    time: null,
    amount: 0,
    method_payment: 'EFECTIVO',
    amount_add: 0,
})

// ─────────────────────────────
// Estados globales
// ─────────────────────────────
const warning = ref(null)
const success = ref(null)
const error_exists = ref(null)

const method_payments = ref(["EFECTIVO", "DEPOSITO", "YAPE", "PLIN"])

// ─────────────────────────────
// Disponibilidad del veterinario
// ─────────────────────────────
const veterinarie_time_availability = ref([])
const segment_time_veterinaries = ref([])
const selected_segment_times = ref([])
const segment_time_hour_veterinaries = ref([])
const veterinarie_id = ref(null)

// ─────────────────────────────
// Datos adicionales
// ─────────────────────────────
const reason = ref(null)
const vaccine_names = ref(null)
const nex_due_date = ref(null)
const outside = ref('0')

// ─────────────────────────────
// Filtro de disponibilidad
// ─────────────────────────────
const filter = async () => {
    try {
        warning.value = null

        if (!form.value.vaccination_date) {
            warning.value = "Es necesario ingresar una fecha"
            return
        }

        const data = {
            vaccination_date: form.value.vaccination_date,
            hour: form.value.time,
        }

        const resp = await $api('/appointments/filter-availability', {
            method: 'POST',
            body: data,
            onResponseError({ response }) {
                error_exists.value = response._data.error
            },
        })

        veterinarie_time_availability.value = resp.veterinarie_time_availability
    } catch (error) {
        console.log(error)
    }
}

// ─────────────────────────────
// Selección de segmentos
// ─────────────────────────────
const selectedSegmentHour = (veterinarie_time, segment_time_group) => {
    veterinarie_time.segment_times = segment_time_group.segment_times

    veterinarie_id.value = veterinarie_time.id

    selected_segment_times.value = selected_segment_times.value.filter(item =>
        item.veterinarie_id == veterinarie_time.id
    )

    segment_time_veterinaries.value = segment_time_veterinaries.value.filter(item =>
        item.includes(veterinarie_time.id + '-')
    )

    segment_time_hour_veterinaries.value = segment_time_hour_veterinaries.value.filter(item =>
        item.includes(veterinarie_time.id + '-')
    )
}

const reset = () => {
    form.value.vaccination_date = null
    form.value.time = null
    veterinarie_time_availability.value = []
    selected_segment_times.value = []
    segment_time_veterinaries.value = []
    segment_time_hour_veterinaries.value = []
}

const addSelectedSegmentTime = (vet, segment_time) => {
    const i = selected_segment_times.value.findIndex(
        x => x.veterinarie_id == vet.id && x.segment_time_id == segment_time.veterinarie_schedule_hour_id,
    )

    if (i !== -1) {
        selected_segment_times.value.splice(i, 1)
    } else {
        selected_segment_times.value.push({
            veterinarie_id: vet.id,
            segment_time_id: segment_time.veterinarie_schedule_hour_id,
        })
    }

    veterinarie_id.value = vet.id

    selected_segment_times.value = selected_segment_times.value.filter(
        item => item.veterinarie_id == vet.id,
    )
}

const addSelectedSegmentTimeHour = (vet, group) => {
    group.segment_times.forEach(segment_time => {
        const idx = selected_segment_times.value.findIndex(
            x => x.veterinarie_id == vet.id && x.segment_time_id == segment_time.veterinarie_schedule_hour_id,
        )

        if (idx !== -1) {
            selected_segment_times.value.splice(idx, 1)
            segment_time_veterinaries.value.splice(idx, 1)
        } else if (!segment_time.check) {
            selected_segment_times.value.push({
                veterinarie_id: vet.id,
                segment_time_id: segment_time.veterinarie_schedule_hour_id,
            })

            segment_time_veterinaries.value.push(
                vet.id + '-' + segment_time.veterinarie_schedule_hour_id,
            )
        }
    })
}

// ─────────────────────────────
// Limpiar formulario
// ─────────────────────────────
const fieldsClean = () => {
    Object.assign(form.value, {
        vaccination_date: null,
        time: null,
        amount: 0,
        method_payment: 'EFECTIVO',
        amount_add: 0,
    })

    veterinarie_time_availability.value = []
    selected_segment_times.value = []
    segment_time_veterinaries.value = []
    segment_time_hour_veterinaries.value = []

    select_pet.value = null
    reason.value = null
    vaccine_names.value = null
    nex_due_date.value = null
    outside.value = '1'
}

// ─────────────────────────────
// Guardar vacunación
// ─────────────────────────────
const store = async () => {
    try {
        warning.value = null

        if (!form.value.vaccination_date)
            return (warning.value = "El campo de fecha es requerido")

        if (selected_segment_times.value.length === 0)
            return (warning.value = "Debe seleccionar un horario")

        if (!reason.value)
            return (warning.value = "Debe ingresar una razón")

        if (!select_pet.value)
            return (warning.value = "Debe seleccionar una mascota")

        if (!vaccine_names.value)
            return (warning.value = "Debe completar las vacunas aplicadas")

        if (!nex_due_date.value)
            return (warning.value = "Debe seleccionar la siguiente fecha de vacuna")

        if (!form.value.amount)
            return (warning.value = "Debe ingresar el costo")

        if (!form.value.amount_add)
            return (warning.value = "Debe ingresar un adelanto")

        if (parseInt(form.value.amount_add) > parseInt(form.value.amount))
            return (warning.value = "El adelanto no puede ser mayor al costo")

        let STATE_PAY = 1
        if (form.value.amount > form.value.amount_add) STATE_PAY = 2
        if (form.value.amount == form.value.amount_add) STATE_PAY = 3

        const data = {
            veterinarie_id: veterinarie_id.value,
            pet_id: select_pet.value.id,
            vaccination_date: form.value.vaccination_date,
            reason: reason.value,
            amount: form.value.amount,
            state_pay: STATE_PAY,
            method_payment: form.value.method_payment,
            adelanto: form.value.amount_add,
            selected_segment_times: selected_segment_times.value,
            vaccine_names: vaccine_names.value,
            outside: outside.value,
            nex_due_date: nex_due_date.value,
        }

        const resp = await $api('/vaccinations', {
            method: 'POST',
            body: data,
            onResponseError({ response }) {
                error_exists.value = response._data.error
            },
        })

        success.value = "La vacuna se ha registrado correctamente"

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

// ─────────────────────────────
// Búsqueda de mascotas
// ─────────────────────────────
const loading = ref(false)
const search = ref('')
const select_pet = ref(null)
const items = ref([])

const querySelections = async query => {
    loading.value = true

    setTimeout(async () => {
        const resp = await $api('/appointments/search-pets/' + query)

        items.value = resp.pets
        loading.value = false
    }, 500)
}

watch(search, val => {
    if (val && val.length > 1) querySelections(val)
    else items.value = []
})

definePage({
    meta: { permisssion: 'register_vaccionation' },
})
</script>

<template>
    <div class="vaccine-container">

        <!-- TÍTULO -->
        <VCardText class="pa-5 text-center">
            <h3 class="text-h4 font-weight-bold">Registro de Vacunación</h3>
        </VCardText>

        <!-- 🔎 BÚSQUEDA -->
        <VCard title="Búsqueda" class="pa-4 mb-4">
            <VRow>
                <VCol cols="12" md="4">
                    <AppDateTimePicker v-model="form.vaccination_date" label="Fecha de la vacuna"
                        :config="{ minDate: 'today', disable: [d => d.getDay() === 0 || d.getDay() === 6] }" />
                </VCol>

                <VCol cols="12" md="4">
                    <AppDateTimePicker v-model="form.time" label="Hora de la vacuna"
                        :config="{ enableTime: true, noCalendar: true, dateFormat: 'H:i' }" />
                </VCol>

                <VCol cols="12" md="4" class="d-flex align-end justify-end">
                    <VBtn color="info" prepend-icon="ri-search-line" class="mr-2" @click="filter">
                        Buscar
                    </VBtn>
                    <VBtn color="secondary" prepend-icon="ri-restart-line" @click="reset">
                        Limpiar
                    </VBtn>
                </VCol>
            </VRow>
        </VCard>

        <!-- ALERTAS -->
        <VAlert type="warning" v-if="warning" class="mb-3"><b>{{ warning }}</b></VAlert>
        <VAlert type="error" v-if="error_exists" class="mb-3">Error en el servidor</VAlert>
        <VAlert type="success" v-if="success" class="mb-3"><b>{{ success }}</b></VAlert>

        <!-- 📅 DISPONIBILIDAD -->
        <VCard title="Disponibilidad" class="pa-4 mb-4">
            <VRow>

                <!-- TABLA -->
                <VCol cols="12" md="8">
                    <VTable>
                        <thead>
                            <tr>
                                <th>Veterinario</th>
                                <th>Tiempos</th>
                                <th>Segmentos</th>
                            </tr>
                        </thead>

                        <tbody>
                            <template v-for="(vet, i) in veterinarie_time_availability" :key="i">
                                <tr>
                                    <td>{{ vet.full_name }}</td>

                                    <td>
                                        <ul class="list-reset">
                                            <li v-for="g in vet.segment_time_groups" :key="g.hour_format"
                                                class="d-flex align-center">
                                                <VCheckbox v-model="segment_time_hour_veterinaries"
                                                    :value="vet.id + '-' + g.hour_format"
                                                    v-if="g.count_availability > 0"
                                                    @click="addSelectedSegmentTimeHour(vet, g)" />

                                                <VBtn variant="text" color="success" prepend-icon="ri-add-line"
                                                    @click="selectedSegmentHour(vet, g)" />

                                                {{ g.hour_format }} ({{ g.count_availability }})
                                            </li>
                                        </ul>
                                    </td>

                                    <td>
                                        <ul class="list-reset">
                                            <li v-for="seg in vet.segment_times"
                                                :key="seg.veterinarie_schedule_hour_id">
                                                <VCheckbox v-if="!seg.check" v-model="segment_time_veterinaries"
                                                    :value="vet.id + '-' + seg.veterinarie_schedule_hour_id"
                                                    :label="seg.schedule_hour.hour_start_format + ' ' + seg.schedule_hour.hour_end_format"
                                                    @click="addSelectedSegmentTime(vet, seg)" />

                                                <span v-else class="font-weight-bold">
                                                    {{ seg.schedule_hour.hour_start_format }} - {{
                                                    seg.schedule_hour.hour_end_format }}
                                                </span>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </VTable>
                </VCol>

                <!-- RAZONES / VACUNAS -->
                <VCol cols="12" md="4">
                    <VTextarea label="Razón" v-model="reason" auto-grow class="mb-4" />
                    <VTextarea label="Vacunas aplicadas" v-model="vaccine_names" auto-grow class="mb-4" />

                    <AppDateTimePicker v-model="nex_due_date" label="Próxima fecha"
                        :config="{ minDate: 'today', disable: [d => d.getDay() === 0 || d.getDay() === 6] }" class="mb-4" />

                    <VRadioGroup v-model="outside" inline>
                        <VRadio label="Dentro de la veterinaria" value="0" />
                        <VRadio label="Fuera de la veterinaria" value="1" />
                    </VRadioGroup>
                </VCol>
            </VRow>
        </VCard>

        <!-- 🐶 PACIENTE -->
        <VCard title="Paciente" class="pa-4 mb-4">
            <VRow>
                <VCol cols="12" md="4">
                    <VAutocomplete v-model="select_pet" v-model:search="search" :items="items" :loading="loading"
                        return-object item-title="name" item-value="id" label="Buscar mascota"
                        placeholder="Ingresa información" variant="underlined" />
                </VCol>

                <VCol cols="12" md="3" v-if="select_pet">
                    <p><b>Especie:</b> {{ select_pet.specie }}</p>
                    <p><b>Raza:</b> {{ select_pet.breed }}</p>
                </VCol>

                <VCol cols="12" md="3" v-if="select_pet">
                    <p><b>Propietario:</b> {{ select_pet.owner.first_name }} {{ select_pet.owner.last_name }}</p>
                    <p><b>Teléfono:</b> {{ select_pet.owner.phone }}</p>
                    <p><b>Documento:</b> {{ select_pet.owner.n_document }}</p>
                </VCol>
            </VRow>
        </VCard>

        <!-- 💵 PAGOS -->
        <VCard title="Pagos" class="pa-4 mb-4">
            <VRow>
                <VCol cols="12" md="4">
                    <VTextField label="Costo" v-model="form.amount" type="number" />
                </VCol>

                <VCol cols="12" md="4">
                    <VSelect label="Método de pago" v-model="form.method_payment" :items="method_payments" />
                </VCol>

                <VCol cols="12" md="4">
                    <VTextField label="Adelanto" v-model="form.amount_add" type="number" />
                </VCol>
            </VRow>
        </VCard>

        <!-- BOTÓN GUARDAR -->
        <div class="text-end pa-4">
            <VBtn color="primary" @click="store">Registrar</VBtn>
        </div>

    </div>
</template>
