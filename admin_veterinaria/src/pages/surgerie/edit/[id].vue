<script setup>
const router = useRouter()
const route = useRoute('surgerie-edit-id')

const form = ref({
    surgerie_date: null,
    time: null,
    amount: 0,
    method_payment: 'EFECTIVO',
    amount_add: 0,
})

const warning = ref(null)
const success = ref(null)
const error_exists = ref(null)

const method_payments = ref(['EFECTIVO', 'DEPOSITO', 'YAPE', 'PLIN'])
const surgerie_types = ref([
    'ESTERILIZACIÓN',
    'CASTRACIÓN',
    'TRAUMATOLÓGICAS',
    'OCULARES',
    'ONCOLÓGICAS',
    'OTRO',
])

const veterinarie_time_availability = ref([])
const segment_time_veterinaries = ref([])
const selected_segment_times = ref([])
const segment_time_hour_veterinaries = ref([])
const veterinarie_id = ref(null)

const medical_notes = ref(null)
const outcome = ref(null)
const surgerie_type = ref(null)
const outside = ref('0')
const surgerie_selected = ref(null)
const state = ref(1)

/* ----------------------------
   Diálogo de cancelar (igual al ejemplo)
   ---------------------------- */
const cancelDialog = ref(false)

const openCancelDialog = () => {
    cancelDialog.value = true
}
const closeCancelDialog = () => {
    cancelDialog.value = false
}
const confirmCancel = () => {
    cancelDialog.value = false
    // Ajusta el name según tu router si es diferente:
    router.push({ name: 'surgerie-list' })
    // o usar router.back() si prefieres volver atrás:
    // router.back()
}

/* ----------------------------
   Filtro disponibilidad y selección
   ---------------------------- */
const filter = async () => {
    try {
        if (!form.value.surgerie_date) {
            warning.value = 'Es necesario ingresar un fecha'
            return
        }
        let data = { surgerie_date: form.value.surgerie_date, hour: form.value.time }
        const resp = await $api('/appointments/filter-availability', {
            method: 'POST',
            body: data,
            onResponseError({ response }) {
                console.log(response)
                error_exists.value = response._data?.error
            },
        })
        warning.value = null
        veterinarie_time_availability.value = resp.veterinarie_time_availability
    } catch (error) {
        console.log(error)
    }
}

const selectedSegmentHour = (veterinarie_time, segment_time_group) => {
    veterinarie_time.segment_times = segment_time_group.segment_times
    veterinarie_id.value = veterinarie_time.id
    selected_segment_times.value = selected_segment_times.value.filter(item => item.veterinarie_id == veterinarie_time.id)
    segment_time_veterinaries.value = segment_time_veterinaries.value.filter(item => item.indexOf(veterinarie_time.id + '-') != -1)
    segment_time_hour_veterinaries.value = segment_time_hour_veterinaries.value.filter(item => item.indexOf(veterinarie_time.id + '-') != -1)
}

const reset = () => {
    form.value.surgerie_date = null
    form.value.time = null
    veterinarie_time_availability.value = []
    segment_time_veterinaries.value = []
    selected_segment_times.value = []
    segment_time_hour_veterinaries.value = []
}

const addSelectedSegmentTime = (veterinarie_time, segment_time) => {
    const INDEX = selected_segment_times.value.findIndex(
        item => item.veterinarie_id == veterinarie_time.id && item.segment_time_id == segment_time.veterinarie_schedule_hour_id,
    )

    if (INDEX != -1) selected_segment_times.value.splice(INDEX, 1)
    else
        selected_segment_times.value.push({
            veterinarie_id: veterinarie_time.id,
            segment_time_id: segment_time.veterinarie_schedule_hour_id,
        })

    veterinarie_id.value = veterinarie_time.id
    selected_segment_times.value = selected_segment_times.value.filter(item => item.veterinarie_id == veterinarie_time.id)
    segment_time_veterinaries.value = segment_time_veterinaries.value.filter(item => item.indexOf(veterinarie_time.id + '-') != -1)
}

const addSelectedSegmentTimeHour = (veterinarie_time, segment_time_group) => {
    segment_time_group.segment_times.forEach(segment_time => {
        const INDEX = selected_segment_times.value.findIndex(
            item => item.veterinarie_id == veterinarie_time.id && item.segment_time_id == segment_time.veterinarie_schedule_hour_id,
        )
        if (INDEX != -1) {
            selected_segment_times.value.splice(INDEX, 1)
            segment_time_veterinaries.value.splice(INDEX, 1)
        } else {
            if (!segment_time.check) {
                selected_segment_times.value.push({
                    veterinarie_id: veterinarie_time.id,
                    segment_time_id: segment_time.veterinarie_schedule_hour_id,
                })
                segment_time_veterinaries.value.push(veterinarie_time.id + '-' + segment_time.veterinarie_schedule_hour_id)
            }
        }
    })

    veterinarie_id.value = veterinarie_time.id
    selected_segment_times.value = selected_segment_times.value.filter(item => item.veterinarie_id == veterinarie_time.id)
    segment_time_veterinaries.value = segment_time_veterinaries.value.filter(item => item.indexOf(veterinarie_time.id + '-') != -1)
    segment_time_hour_veterinaries.value = segment_time_hour_veterinaries.value.filter(item => item.indexOf(veterinarie_time.id + '-') != -1)
}

/* ----------------------------
   Limpieza y actualizar
   ---------------------------- */
const fieldsClean = () => {
    form.value.surgerie_date = null
    form.value.time = null
    form.value.amount = 0
    form.value.method_payment = 'EFECTIVO'
    form.value.amount_add = 0
    veterinarie_time_availability.value = []
    segment_time_veterinaries.value = []
    selected_segment_times.value = []
    select_pet.value = null
    medical_notes.value = null
    outcome.value = null
    outside.value = '1'
    surgerie_type.value = null
}

const update = async () => {
    try {
        warning.value = null
        success.value = null

        if (!medical_notes.value) {
            warning.value = 'Es requerido digitar una razon para la atención de la mascota'
            return
        }
        if (!select_pet.value) {
            warning.value = 'Es requerido seleccionar una mascota'
            return
        }
        if (!form.value.amount) {
            warning.value = 'Es requerido ingresar el costo de la cirujía'
            return
        }

        const data = {
            veterinarie_id: veterinarie_id.value,
            pet_id: select_pet.value.id,
            surgerie_date: form.value.surgerie_date,
            medical_notes: medical_notes.value,
            surgerie_type: surgerie_type.value,
            amount: form.value.amount,
            selected_segment_times: selected_segment_times.value,
            outcome: outcome.value,
            outside: outside.value,
            state: state.value,
        }

        const resp = await $api('/surgeries/' + route.params.id, {
            method: 'PATCH',
            body: data,
            onResponseError({ response }) {
                console.log(response)
                error_exists.value = response._data?.error
            },
        })

        if (resp.message == 403) {
            error_exists.value = resp.message_text
        } else {
            success.value = 'La cirugía se ha editado correctamente'
            show()
            reset()
        }
    } catch (error) {
        console.log(error)
    }
}

/* ----------------------------
   Buscador de mascota
   ---------------------------- */
const loading = ref(false)
const search = ref()
const select_pet = ref(null)
const items = ref([])

const querySelections = async query => {
    loading.value = true
    setTimeout(async () => {
        const resp = await $api('/appointments/search-pets/' + query, {
            method: 'GET',
            onResponseError({ response }) {
                console.log(response)
                error_exists.value = response._data?.error
            },
        })
        items.value = resp.pets
        loading.value = false
    }, 500)
}

watch(search, query => {
    if (query && query.length > 1) querySelections(query)
    else items.value = []
})

/* ----------------------------
   Cargar datos iniciales
   ---------------------------- */
const show = async () => {
    try {
        const resp = await $api('/surgeries/' + route.params.id, {
            method: 'GET',
            onResponseError({ response }) {
                console.log(response)
                error_exists.value = response._data?.error
            },
        })
        surgerie_selected.value = resp.surgerie
        medical_notes.value = surgerie_selected.value.medical_notes
        form.value.amount = surgerie_selected.value.amount
        select_pet.value = surgerie_selected.value.pet
        veterinarie_id.value = surgerie_selected.value.veterinarie_id
        outcome.value = surgerie_selected.value.outcome
        outside.value = surgerie_selected.value.outside + ''
        surgerie_type.value = surgerie_selected.value.surgerie_type
        state.value = surgerie_selected.value.state
    } catch (error) {
        console.log(error)
    }
}

onMounted(() => {
    show()
})

definePage({
    meta: {
        permisssion: 'edit_surgeries',
    },
})
</script>

<template>
    <div>
        <!-- 🔔 Diálogo de confirmación de cancelación -->
        <VDialog v-model="cancelDialog" max-width="420">
            <VCard>
                <VCardTitle class="text-h6">Cancelar edición</VCardTitle>
                <VCardText>
                    ¿Seguro que deseas cancelar la edición de esta cirugía?
                    <br />
                    Los cambios que no hayas guardado se perderán.
                </VCardText>
                <VCardActions class="justify-end">
                    <VBtn variant="text" @click="closeCancelDialog">Seguir editando</VBtn>
                    <VBtn color="error" variant="flat" @click="confirmCancel">Salir sin guardar</VBtn>
                </VCardActions>
            </VCard>
        </VDialog>

        <VCardText class="pa-5">
            <div class="mb-1">
                <h4 class="text-h4 text-center mb-1">Editar Cirugía {{ route.params.id }}</h4>
            </div>
        </VCardText>

        <!-- 🔎 Búsqueda / Reprogramación -->
        <VCard title="🔎 Busqueda:" class="pa-4">
            <VRow>
                <VCol cols="12" md="4">
                    <AppDateTimePicker v-model="form.surgerie_date" label="Fecha de la cirugía"
                        :config="{ minDate: 'today', disable: [date => date.getDay() === 0 || date.getDay() === 6] }" />
                </VCol>

                <VCol cols="12" md="3">
                    <AppDateTimePicker v-model="form.time" label="Hora de la cirugía"
                        :config="{ enableTime: true, noCalendar: true, dateFormat: 'H:i' }" />
                </VCol>

                <VCol cols="12" md="5" class="d-flex align-end justify-end">
                    <VBtn color="info" class="mx-1" prepend-icon="ri-search-2-line" @click="filter" />
                    <VBtn color="secondary" class="mx-1" prepend-icon="ri-restart-line" @click="reset" />
                </VCol>
            </VRow>
        </VCard>

        <!-- ALERTAS -->
        <VAlert type="warning" class="mt-3" v-if="warning"><strong>{{ warning }}</strong></VAlert>
        <VAlert type="error" class="mt-3" v-if="error_exists"><strong>{{ error_exists }}</strong></VAlert>
        <VAlert type="success" class="mt-3" v-if="success"><strong>{{ success }}</strong></VAlert>

        <!-- 📅 Disponibilidad -->
        <VCard title="📅 Disponibilidad:" class="pa-4 mt-4">
            <VRow>
                <VCol cols="12">{{ segment_time_veterinaries }}</VCol>

                <VCol cols="12" md="8">
                    <VTable>
                        <thead>
                            <tr>
                                <th>Veterinario</th>
                                <th>Tiempos activos</th>
                                <th>Segmentos</th>
                            </tr>
                        </thead>

                        <tbody>
                            <template v-for="(veterinarie_time, index) in veterinarie_time_availability" :key="index">
                                <tr>
                                    <td>{{ veterinarie_time.full_name }}</td>
                                    <td>
                                        <ul>
                                            <li v-for="(segment_time_group, idx) in veterinarie_time.segment_time_groups"
                                                :key="idx" style="list-style: none; display:flex; align-items:center;">
                                                <VCheckbox v-if="segment_time_group.count_availability > 0"
                                                    v-model="segment_time_hour_veterinaries"
                                                    :value="veterinarie_time.id + '-' + segment_time_group.hour_format"
                                                    @click="addSelectedSegmentTimeHour(veterinarie_time, segment_time_group)" />
                                                <VBtn color="success" class="mx-1" prepend-icon="ri-file-add-line"
                                                    variant="text"
                                                    @click="selectedSegmentHour(veterinarie_time, segment_time_group)" />
                                                {{ segment_time_group.hour_format }} ({{
                                                segment_time_group.count_availability }})
                                            </li>
                                        </ul>
                                    </td>
                                    <td>
                                        <ul>
                                            <li v-for="(segment_time, idx2) in veterinarie_time.segment_times"
                                                :key="idx2" style="list-style: none;">
                                                <VCheckbox v-if="!segment_time.check"
                                                    v-model="segment_time_veterinaries"
                                                    :label="segment_time.schedule_hour.hour_start_format + ' ' + segment_time.schedule_hour.hour_end_format"
                                                    :value="veterinarie_time.id + '-' + segment_time.veterinarie_schedule_hour_id"
                                                    @click="addSelectedSegmentTime(veterinarie_time, segment_time)" />
                                                <label v-else style="font-weight: bold;">{{
                                                    segment_time.schedule_hour.hour_start_format + ' ' +
                                                    segment_time.schedule_hour.hour_end_format }}</label>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </VTable>
                </VCol>

                <VCol cols="12" md="4">
                    <VTextarea v-model="medical_notes" label="Nota medica:" placeholder="Motivo / notas" />
                    <VTextarea class="mt-4" v-model="outcome" label="Resultado de la cirugía:"
                        placeholder="Resultado" />
                    <VSelect class="mt-4" v-model="surgerie_type" :items="surgerie_types" label="Tipo de cirugías"
                        placeholder="Select Tipo" eager />
                    <VRadioGroup v-model="outside" inline class="mt-4">
                        <VRadio label="Dentro de la veterinaria" value="0" />
                        <VRadio label="Fuera de la veterinaria" value="1" />
                    </VRadioGroup>
                </VCol>
            </VRow>
        </VCard>

        <!-- ⏱️ Hora de atención -->
        <VCard v-if="surgerie_selected" title="⏱️ Hora de atención:" class="pa-4 mt-4">
            <VRow>
                <VCol cols="12" md="3">
                    <VSelect
                        :items="[{ name: 'Pendiente', id: 1 }, { name: 'Cancelado', id: 2 }, { name: 'Atendido', id: 3 }]"
                        v-model="state" label="Estado de la cirujía" item-title="name" item-value="id"
                        :disabled="surgerie_selected.state == 2 || surgerie_selected.state == 3" />
                </VCol>

                <VCol cols="12" md="9">
                    <VTable>
                        <thead>
                            <tr>
                                <th>Veterinario</th>
                                <th>Día de la cirugía</th>
                                <th>Duración</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ surgerie_selected.veterinarie.full_name }}</td>
                                <td>{{ surgerie_selected.surgerie_date }}</td>
                                <td>
                                    <ul>
                                        <template v-for="(for_hour, index) in surgerie_selected.schedule_for_hour"
                                            :key="index">
                                            <template v-if="!for_hour.is_complete">
                                                <li v-for="(schedule, idx2) in for_hour.segments_time" :key="idx2">
                                                    <label style="font-weight: bold;">{{
                                                        schedule.schedule_hour.hour_start_format + ' ' +
                                                        schedule.schedule_hour.hour_end_format }}</label>
                                                </li>
                                            </template>
                                            <li v-else>
                                                <label style="font-weight: bold;">{{ for_hour.hour_format }}</label>
                                            </li>
                                        </template>
                                    </ul>
                                </td>
                            </tr>
                        </tbody>
                    </VTable>
                </VCol>
            </VRow>
        </VCard>

        <!-- 🐶 Paciente -->
        <VCard title="🐶 Paciente:" class="pa-4 mt-4">
            <VRow>
                <VCol cols="12" md="4">
                    <VAutocomplete v-model="select_pet" v-model:search="search" :loading="loading" :items="items"
                        item-title="name" item-value="id" return-object placeholder="Ingresa información de la mascota"
                        label="¿Quién es la mascotita?" variant="underlined" :menu-props="{ maxHeight: '200px' }" />
                </VCol>

                <VCol cols="12" md="3" v-if="select_pet">
                    <label>Especie: {{ select_pet.specie }}</label><br />
                    <label>Raza: {{ select_pet.breed }}</label>
                </VCol>

                <VCol cols="12" md="3" v-if="select_pet">
                    <label>Nombre y Apellido: {{ select_pet.owner.first_name + ' ' + select_pet.owner.last_name
                        }}</label><br />
                    <label>Teléfono: {{ select_pet.owner.phone }}</label><br />
                    <label>Documento: {{ select_pet.owner.n_document }}</label>
                </VCol>
            </VRow>
        </VCard>

        <!-- 💵 Pagos -->
        <VCard title="💵 Pagos:" class="pa-4 mt-4">
            <VRow>
                <VCol cols="12" md="4">
                    <VTextField label="Costo de la cirugía:" type="number" placeholder="Ejemplo: 100"
                        v-model="form.amount" />
                </VCol>
            </VRow>
        </VCard>

        <!-- BOTONES -->
        <VCardText class="pa-5 py-0 text-end">
            <VBtn variant="outlined" color="secondary" class="mb-4 mr-3" @click="openCancelDialog">Cancelar</VBtn>
            <VBtn color="primary" class="mb-4" @click="update">Actualizar</VBtn>
        </VCardText>
    </div>
</template>

<style scoped>
.v-btn__prepend {
    margin-inline: 0 !important;
}
</style>
