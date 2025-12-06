<script setup>
const form = ref({
    name: null,
    surname: null,
    email: null,
    phone: null,
    type_document: 'DNI',
    n_document: null,
    birthday: null,
    designation: null,
    gender: null,
    role_id: null,
    designation: null,
    password: null,
})
const type_documents = [
    'DNI',
    'PASAPORTE',
    'CARNET DE EXTRANJERIA',
]

const isPasswordVisible = ref(false)
const warning = ref(null)
const success = ref(null)
const error_exists = ref(null)
const FILE_AVATAR = ref(null)
const IMAGEN_PREVIZUALIZA = ref(null)
const roles = ref([])
const days = ref(['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'])
const hour_days = ref([])
const selected_segment_time = ref([])
const schedule_hour_veterinarie = ref([])
const load_request = ref(null)
const route = useRoute('veterinarie-edit-id')
const veterinarie_selected = ref(null)

const loadFile = $event => {
    if ($event.target.files[0].type.indexOf('image') < 0) {
        FILE_AVATAR.value = null
        IMAGEN_PREVIZUALIZA.value = null
        warning.value = 'SOLAMENTE PUEDEN SER ARCHIVOS DE TIPO IMAGEN'
        return
    }
    warning.value = ''
    FILE_AVATAR.value = $event.target.files[0]
    let reader = new FileReader()
    reader.readAsDataURL(FILE_AVATAR.value)
    reader.onloadend = () => (IMAGEN_PREVIZUALIZA.value = reader.result)
}

const config = async () => {
    try {
        const resp = await $api('/veterinaries/config', {
            method: 'GET',
            onResponseError({ response }) {
                console.log(response)
                error_exists.value = response._data.error
            },
        })
        console.log(resp)
        roles.value = resp.roles
        hour_days.value = resp.schedule_hours_groups
    } catch (error) {
        console.log(error)
    }
}

const selectSegmentTimeAll = ($event, segment_times, day) => {
    if ($event.target.checked) {
        // EL CHECKBOX ESTA MARCADO
        segment_times.forEach(segment_time => {
            selectedSegmentTime($event, segment_time, day)
            let INDEX = selected_segment_time.value.findIndex(
                seg_time => seg_time == segment_time.id + '-' + day,
            )
            if (INDEX == -1) {
                selected_segment_time.value.push(segment_time.id + '-' + day)
            }
        })
    } else {
        // EL CHECKBOX ESTA DESMARCADO
        segment_times.forEach(segment_time => {
            selectedSegmentTime($event, segment_time, day)
            let INDEX = selected_segment_time.value.findIndex(
                seg_time => seg_time == segment_time.id + '-' + day,
            )
            if (INDEX != -1) {
                selected_segment_time.value.splice(INDEX, 1)
            }
        })
    }
}

const selectSegmentTimeAllGroups = ($event, segment_times) => {
    if ($event.target.checked) {
        // EL CHECKBOX ESTA MARCADO
        days.value.forEach(day => {
            segment_times.forEach(segment_time => {
                selectedSegmentTime($event, segment_time, day)
                let INDEX = selected_segment_time.value.findIndex(
                    seg_time => seg_time == segment_time.id + '-' + day,
                )
                if (INDEX == -1) {
                    selected_segment_time.value.push(segment_time.id + '-' + day)
                }
            })
        })
    } else {
        // EL CHECKBOX ESTA DESMARCADO
        days.value.forEach(day => {
            segment_times.forEach(segment_time => {
                selectedSegmentTime($event, segment_time, day)
                let INDEX = selected_segment_time.value.findIndex(
                    seg_time => seg_time == segment_time.id + '-' + day,
                )
                if (INDEX != -1) {
                    selected_segment_time.value.splice(INDEX, 1)
                }
            })
        })
    }
}

const selectedSegmentTime = ($event, segment_time, day) => {
    if ($event.target.checked) {
        // EL CHECKBOX ESTA MARCADO
        let INDEX = schedule_hour_veterinarie.value.findIndex(
            seg_time => seg_time.id_seg == segment_time.id + '-' + day,
        )
        if (INDEX == -1) {
            schedule_hour_veterinarie.value.push({
                id_seg: segment_time.id + '-' + day,
                segment_time_id: segment_time.id,
                day: day,
            })
        }
    } else {
        // EL CHECKBOX ESTA DESMARCADO
        let INDEX = schedule_hour_veterinarie.value.findIndex(
            seg_time => seg_time.id_seg == segment_time.id + '-' + day,
        )
        if (INDEX != -1) {
            schedule_hour_veterinarie.value.splice(INDEX, 1)
        }
    }
}

const store = async () => {
    warning.value = null
    if (schedule_hour_veterinarie.value.length == 0) {
        warning.value = 'Debes programar la disponibilidad laboral del veterinario'
        return
    }
    if (!form.value.name) {
        warning.value = 'Se debe llenar el nombre del veterinario'
        return
    }
    if (!form.value.surname) {
        warning.value = 'Se debe llenar el apellido del veterinario'
        return
    }
    if (!form.value.email) {
        warning.value = 'Se debe llenar el email del veterinario'
        return
    }
    if (!form.value.phone) {
        warning.value = 'Se debe llenar el telefono del veterinario'
        return
    }
    if (!form.value.gender) {
        warning.value = 'Se debe llenar el genero del veterinario'
        return
    }
    if (!form.value.role_id) {
        warning.value = 'Se debe seleccionar un rol para el veterinario'
        return
    }

    let formData = new FormData()

    formData.append('name', form.value.name)
    formData.append('surname', form.value.surname)
    formData.append('email', form.value.email)
    if (form.value.type_document) {
        formData.append('type_document', form.value.type_document)
    }
    if (form.value.n_document) {
        formData.append('n_document', form.value.n_document)
    }
    formData.append('phone', form.value.phone)
    formData.append('gender', form.value.gender)
    if (form.value.designation) {
        formData.append('designation', form.value.designation)
    }
    if (form.value.password) {
        formData.append('password', form.value.password)
    }
    formData.append('role_id', form.value.role_id)
    if (form.value.birthday) {
        formData.append('birthday', form.value.birthday)
    }
    if (FILE_AVATAR.value) {
        formData.append('imagen', FILE_AVATAR.value)
    }
    formData.append(
        'schedule_hour_veterinarie',
        JSON.stringify(schedule_hour_veterinarie.value),
    )
    try {
        load_request.value = true
        const resp = await $api('/veterinaries/' + route.params.id, {
            method: 'POST',
            body: formData,
            onResponseError({ response }) {
                console.log(response)
                error_exists.value = response._data.error
            },
        })
        console.log(resp)
        load_request.value = false
        if (resp.message == 403) {
            warning.value = resp.message_text
        } else {
            success.value = 'El veterinario se ha editado correctamente'
            setTimeout(() => {
                success.value = null
                warning.value = null
                error_exists.value = null
            }, 3500)
        }
    } catch (error) {
        console.log(error)
        error_exists.value = error
    }
}

const show = async () => {
    try {
        const resp = await $api('/veterinaries/' + route.params.id, {
            method: 'GET',
            onResponseError({ response }) {
                console.log(response)
                error_exists.value = response._data.error
            },
        })
        console.log(resp)
        veterinarie_selected.value = resp.veterinarie
        form.value.name = veterinarie_selected.value.name
        form.value.surname = veterinarie_selected.value.surname
        form.value.email = veterinarie_selected.value.email
        form.value.phone = veterinarie_selected.value.phone
        form.value.type_document = veterinarie_selected.value.type_document
        form.value.n_document = veterinarie_selected.value.n_document
        form.value.gender = veterinarie_selected.value.gender
        form.value.birthday = veterinarie_selected.value.birthday
        form.value.designation = veterinarie_selected.value.designation
        form.value.role_id = veterinarie_selected.value.role_id
        IMAGEN_PREVIZUALIZA.value = veterinarie_selected.value.avatar
        selected_segment_time.value = veterinarie_selected.value.selected_segment_times
        schedule_hour_veterinarie.value = veterinarie_selected.value.schedule_hour_veterinarie
    } catch (error) {
        console.log(error)
    }
}

onMounted(() => {
    config()
    show()
})

definePage({
    meta: {
        permisssion: 'edit_veterinary',
    },
})
</script>

<template>
    <div>
        <VCard class="refer-and-earn-dialog pa-3 pa-sm-11">
            <VCardText class="pa-5">
                <div class="mb-6">
                    <h4 class="text-h4 text-center mb-2">
                        Editar veterinario : {{ route.params.id }}
                    </h4>
                </div>

                <VRow>
                    <!-- Nombre / Apellido -->
                    <VCol cols="12" md="6">
                        <VTextField label="Nombre:" v-model="form.name" placeholder="Example: Rafael" />
                    </VCol>

                    <VCol cols="12" md="6">
                        <VTextField label="Apellido:" v-model="form.surname" placeholder="Example: Mendoza" />
                    </VCol>

                    <!-- Teléfono / Tipo doc. / Nº doc. -->
                    <VCol cols="12" md="4">
                        <VTextField label="Telefono:" type="number" v-model="form.phone"
                            placeholder="Example: 99999999" />
                    </VCol>

                    <VCol cols="12" md="4">
                        <VSelect :items="type_documents" v-model="form.type_document" label="Tipo de documento"
                            placeholder="Select Item" eager />
                    </VCol>

                    <VCol cols="12" md="4">
                        <VTextField label="N° de documento:" type="number" v-model="form.n_document"
                            placeholder="07101010" />
                    </VCol>

                    <!-- Cumpleaños / Género / Rol -->
                    <VCol cols="12" md="4">
                        <AppDateTimePicker v-model="form.birthday" label="Cumpleaños" placeholder="Select date" />
                    </VCol>

                    <VCol cols="12" md="4">
                        <VRadioGroup v-model="form.gender" inline label="Género">
                            <VRadio label="Masculino" value="M" />
                            <VRadio label="Femenino" value="F" />
                        </VRadioGroup>
                    </VCol>

                    <VCol cols="12" md="4">
                        <VSelect :items="roles" v-model="form.role_id" label="Rol" item-title="name" item-value="id"
                            placeholder="Select Rol" eager />
                    </VCol>

                    <!-- Descripción / Email / Password (izquierda) -->
                    <VCol cols="12" md="6">
                        <VTextarea v-model="form.designation" label="Designación" placeholder="Text" />

                        <VTextField class="mt-4" v-model="form.email" label="Email" type="email"
                            placeholder="johndoe@email.com" />

                        <VTextField class="mt-4" v-model="form.password" label="Password" placeholder="············"
                            :type="isPasswordVisible ? 'text' : 'password'"
                            :append-inner-icon="isPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                            @click:append-inner="isPasswordVisible = !isPasswordVisible" />
                    </VCol>

                    <!-- Imagen / Avatar (derecha) -->
                    <VCol cols="12" md="6" class="d-flex flex-column align-center">
                        <div class="avatar-upload mt-2">
                            <!-- input invisible, todo el cuadro es clickeable -->
                            <input type="file" accept="image/*" class="avatar-upload-input"
                                @change="loadFile($event)" />

                            <!-- Si hay imagen, solo imagen -->
                            <img v-if="IMAGEN_PREVIZUALIZA" :src="IMAGEN_PREVIZUALIZA" alt=""
                                class="avatar-preview-img" />

                            <!-- Si NO hay imagen, placeholder dentro -->
                            <div v-else class="avatar-placeholder">
                                <VIcon size="32">ri-image-add-line</VIcon>
                                <span class="avatar-text-strong">Subir imagen aquí</span>
                                <span class="avatar-text-muted">Tamaño recomendado 1080 x 1080</span>
                                <span class="avatar-text-small">Haz clic en el recuadro para seleccionar un
                                    archivo</span>
                            </div>
                        </div>

                        <!-- Texto debajo cuando ya hay imagen -->
                        <div v-if="IMAGEN_PREVIZUALIZA" class="avatar-caption mt-2">
                            <span class="avatar-text-strong">Cambiar imagen</span>
                            <span class="avatar-text-small">Haz clic en el recuadro para seleccionar otra imagen</span>
                        </div>
                    </VCol>
                </VRow>

                <VAlert type="warning" class="mt-3" v-if="warning">
                    <strong>{{ warning }}</strong>
                </VAlert>
                <VAlert type="error" class="mt-3" v-if="error_exists">
                    <strong>En el servidor hubo un error al guardar</strong>
                </VAlert>
                <VAlert type="success" class="mt-3" v-if="success">
                    <strong>{{ success }}</strong>
                </VAlert>
            </VCardText>

            <VCardText class="pa-5 py-0">
                <VBtn color="primary" class="mb-4" :loading="load_request" @click="store()">
                    Actualizar
                </VBtn>
            </VCardText>

            <VCardText class="pa-5">
                <VTable>
                    <thead>
                        <tr>
                            <th class="text-uppercase">
                                DIAS/HORAS
                            </th>

                            <th class="text-uppercase" v-for="(day, index) in days" :key="index">
                                {{ day }}
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="item in hour_days" :key="item.hour">
                            <td>
                                {{ item.hour_format }}
                                <VCheckbox v-if="!load_request" label="Todos"
                                    @click="selectSegmentTimeAllGroups($event, item.segments_time)" />
                            </td>

                            <td v-for="(day, index) in days" :key="index">
                                <div class="demo-space-x my-2">
                                    <VCheckbox v-if="!load_request" label="Todos"
                                        @click="selectSegmentTimeAll($event, item.segments_time, day)" />
                                    <template v-for="(segment_time, idx) in item.segments_time" :key="idx">
                                        <VCheckbox v-model="selected_segment_time"
                                            :label="segment_time.hour_start_format + ' ' + segment_time.hour_end_format"
                                            :value="segment_time.id + '-' + day"
                                            @click="selectedSegmentTime($event, segment_time, day)" />
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </VTable>
            </VCardText>
        </VCard>
    </div>
</template>

<style>
.v-selection-control .v-label {
    font-size: small;
}

.v-checkbox.v-input {
    margin: 0;
}
</style>

<style lang="scss">
.avatar-upload {
    position: relative;
    width: 100%;
    max-width: 280px;
    aspect-ratio: 1 / 1;
    border-radius: 16px;
    border: 1px dashed rgba(255, 255, 255, 0.35);
    overflow: hidden;
    cursor: pointer;
    background: rgba(255, 255, 255, 0.02);
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-upload-input {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    z-index: 2;
}

.avatar-preview-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    text-align: center;
    color: #ffffff;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    align-items: center;
    justify-content: center;
}

.avatar-text-strong {
    font-weight: 600;
    font-size: 0.95rem;
}

.avatar-text-muted {
    font-size: 0.8rem;
    opacity: 0.9;
}

.avatar-text-small {
    font-size: 0.75rem;
    opacity: 0.8;
}

.avatar-caption {
    text-align: center;
    color: #ffffff;
    opacity: 0.85;
    font-size: 0.8rem;
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}
</style>
