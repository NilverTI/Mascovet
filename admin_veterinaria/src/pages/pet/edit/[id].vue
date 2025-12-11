<script setup>
const warning = ref(null)
const success = ref(null)
const error_exists = ref(null)

const route = useRoute('pet-edit-id')

const form = ref({
    // Mascota
    name: null,
    specie: null,
    breed: null,
    dirth_date: null,
    gender: null,
    color: null,
    weight: 0,
    medical_notes: null,

    // Responsable
    first_name: null,
    last_name: null,
    email: null,
    phone: null,
    address: null,
    city: null,
    emergency_contact: null,
    type_document: 'DNI',
    n_document: null,
})

const type_documents = [
    'DNI',
    'PASAPORTE',
    'CARNET DE EXTRANJERIA',
]

const species = ref([
    'Perro',
    'Gato',
    'Hámster',
    'Loro',
    'Tortuga',
    'Vaca',
    'Caballo',
    'Cuy',
    'Toro',
])

const FILE_AVATAR = ref(null)
const IMAGEN_PREVIZUALIZA = ref(null)

const pet_selected = ref({
    name: '',
})

// ➕ NUEVO: para navegación y diálogo de cancelar
const router = useRouter()
const cancelDialog = ref(false)

const openCancelDialog = () => {
    cancelDialog.value = true
}

const closeCancelDialog = () => {
    cancelDialog.value = false
}

const confirmCancel = () => {
    cancelDialog.value = false
    // Regresa a la pantalla anterior
    router.back()
    // Si prefieres ir a una ruta fija:
    // router.push('/pets')
}


const loadFile = $event => {
    if ($event.target.files[0].type.indexOf('image') < 0) {
        FILE_AVATAR.value = null
        IMAGEN_PREVIZUALIZA.value = null
        warning.value = 'SOLAMENTE PUEDEN SER ARCHIVOS DE TIPO IMAGEN'
        return
    }

    warning.value = ''
    FILE_AVATAR.value = $event.target.files[0]

    const reader = new FileReader()
    reader.readAsDataURL(FILE_AVATAR.value)
    reader.onloadend = () => {
        IMAGEN_PREVIZUALIZA.value = reader.result
    }
}

const edit = async () => {
    try {
        warning.value = null

        // Validaciones mascota
        if (!form.value.name) {
            warning.value = 'El nombre de la mascota es obligatorio'
            return
        }
        if (!form.value.specie) {
            warning.value = 'La especie de la mascota es obligatorio'
            return
        }
        if (!form.value.breed) {
            warning.value = 'La raza de la mascota es obligatorio'
            return
        }
        if (!form.value.dirth_date) {
            warning.value = 'La fecha de nacimiento de la mascota es obligatoria'
            return
        }
        if (!form.value.gender) {
            warning.value = 'El genero de la mascota es obligatorio'
            return
        }
        if (!form.value.color) {
            warning.value = 'El color de la mascota es obligatorio'
            return
        }
        if (!form.value.weight) {
            warning.value = 'El peso de la mascota es obligatorio'
            return
        }

        // Validaciones responsable
        if (!form.value.first_name) {
            warning.value = 'El nombre del responsable es obligatorio'
            return
        }
        if (!form.value.last_name) {
            warning.value = 'El apellido del responsable es obligatorio'
            return
        }
        if (!form.value.phone) {
            warning.value = 'El telefono del responsable es obligatorio'
            return
        }
        if (!form.value.address) {
            warning.value = 'La dirección del responsable es obligatoria'
            return
        }
        if (!form.value.emergency_contact) {
            warning.value = 'El contacto de emergencia es obligatorio'
            return
        }
        if (!form.value.n_document) {
            warning.value = 'El numero de documento es obligatorio'
            return
        }

        const formData = new FormData()
        formData.append('name', form.value.name)
        formData.append('specie', form.value.specie)
        formData.append('breed', form.value.breed)
        formData.append('dirth_date', form.value.dirth_date)
        formData.append('gender', form.value.gender)
        formData.append('color', form.value.color)
        formData.append('weight', form.value.weight)
        if (form.value.medical_notes) {
            formData.append('medical_notes', form.value.medical_notes)
        }
        formData.append('first_name', form.value.first_name)
        formData.append('last_name', form.value.last_name)
        if (form.value.email) {
            formData.append('email', form.value.email)
        }
        formData.append('phone', form.value.phone)
        formData.append('address', form.value.address)
        if (form.value.city) {
            formData.append('city', form.value.city)
        }
        formData.append('emergency_contact', form.value.emergency_contact)
        formData.append('type_document', form.value.type_document)
        formData.append('n_document', form.value.n_document)

        if (FILE_AVATAR.value) {
            formData.append('imagen', FILE_AVATAR.value)
        }

        const resp = await $api('/pets/' + route.params.id, {
            method: 'POST',
            body: formData,
            onResponseError({ response }) {
                console.log(response)
                error_exists.value = response._data.error
            },
        })

        console.log(resp)
        success.value = 'La mascota se ha editado correctamente'

        setTimeout(() => {
            success.value = null
            warning.value = null
            error_exists.value = null
        }, 1500)
    } catch (error) {
        console.log(error)
    }
}

const show = async () => {
    try {
        const resp = await $api('/pets/' + route.params.id, {
            method: 'GET',
            onResponseError({ response }) {
                console.log(response)
                error_exists.value = response._data.error
            },
        })

        console.log(resp)
        pet_selected.value = resp.pet

        // Mascota
        form.value.name = pet_selected.value.name
        form.value.specie = pet_selected.value.specie
        form.value.breed = pet_selected.value.breed
        form.value.dirth_date = pet_selected.value.dirth_date
        form.value.gender = pet_selected.value.gender
        form.value.color = pet_selected.value.color
        form.value.weight = pet_selected.value.weight
        form.value.medical_notes = pet_selected.value.medical_notes

        // Responsable
        form.value.first_name = pet_selected.value.owner.first_name
        form.value.last_name = pet_selected.value.owner.last_name
        form.value.email = pet_selected.value.owner.email
        form.value.phone = pet_selected.value.owner.phone
        form.value.address = pet_selected.value.owner.address
        form.value.city = pet_selected.value.owner.city
        form.value.emergency_contact = pet_selected.value.owner.emergency_contact
        form.value.type_document = pet_selected.value.owner.type_document
        form.value.n_document = pet_selected.value.owner.n_document

        IMAGEN_PREVIZUALIZA.value = pet_selected.value.photo
    } catch (error) {
        console.log(error)
    }
}

onMounted(() => {
    show()
})

definePage({
    meta: {
        permisssion: 'edit_pet',
    },
})
</script>

<template>
    <div>
        <!-- 🔔 Diálogo de confirmación de cancelación -->
        <VDialog v-model="cancelDialog" max-width="420">
            <VCard>
                <VCardTitle class="text-h6">
                    Cancelar edición
                </VCardTitle>

                <VCardText>
                    ¿Seguro que deseas salir? Los cambios que no hayas guardado se perderán.
                </VCardText>

                <VCardActions class="justify-end">
                    <VBtn variant="text" @click="closeCancelDialog">
                        Seguir editando
                    </VBtn>
                    <VBtn color="error" variant="flat" @click="confirmCancel">
                        Salir sin guardar
                    </VBtn>
                </VCardActions>
            </VCard>
        </VDialog>

        <VCardText class="pa-5">
            <div class="mb-1">
                <h4 class="text-h4 text-center mb-1">
                    Editar Mascota: "{{ pet_selected.name }}"
                </h4>
            </div>
        </VCardText>

        <!-- 🐾 Mascota -->
        <VCard title="Mascota:" class="pa-4">
            <VRow>
                <!-- Columna izquierda: datos -->
                <VCol cols="12" md="7">
                    <VRow>
                        <!-- Nombre -->
                        <VCol cols="12">
                            <VTextField label="Nombre" v-model="form.name" placeholder="Ejemplo: Boby" />
                        </VCol>

                        <!-- Especie / Raza -->
                        <VCol cols="12" md="6">
                            <VSelect :items="species" v-model="form.specie" label="Especie" placeholder="Seleccione"
                                eager />
                        </VCol>

                        <VCol cols="12" md="6">
                            <VTextField label="Raza" v-model="form.breed" placeholder="Ejemplo: Pastor Alemán" />
                        </VCol>

                        <!-- Fecha / Género -->
                        <VCol cols="12" md="6">
                            <label class="text-caption">Fecha de nacimiento</label>
                            <div class="app-picker-field">
                                <div class="v-input v-input--horizontal v-input--center-affix
                         v-input--density-comfortable v-locale--is-ltr
                         position-relative v-text-field">
                                    <div class="v-input__control">
                                        <div class="v-field v-field--center-affix v-field--variant-outlined
                             v-theme--light v-locale--is-ltr">
                                            <div class="v-field__field">
                                                <div class="v-field__input">
                                                    <input type="date" class="flat-picker-custom-style flatpickr-input"
                                                        v-model="form.dirth_date" style="opacity: 1;">
                                                </div>
                                            </div>
                                            <div class="v-field__outline text-primary">
                                                <div class="v-field__outline__start" />
                                                <div class="v-field__outline__notch">
                                                    <label class="v-label v-field-label v-field-label--floating"
                                                        aria-hidden="true">
                                                        Fecha de nacimiento
                                                    </label>
                                                </div>
                                                <div class="v-field__outline__end" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </VCol>

                        <VCol cols="12" md="6">
                            <VRadioGroup v-model="form.gender" inline label="Género">
                                <VRadio label="Macho" value="M" />
                                <VRadio label="Hembra" value="H" />
                            </VRadioGroup>
                        </VCol>

                        <!-- Color / Peso -->
                        <VCol cols="12" md="6">
                            <VTextField v-model="form.color" label="Color" placeholder="Ejemplo: Negro con manchas" />
                        </VCol>

                        <VCol cols="12" md="6">
                            <VTextField label="Peso" type="number" v-model="form.weight" placeholder="Ejemplo: 20 KG" />
                        </VCol>

                        <!-- Nota Médica -->
                        <VCol cols="12">
                            <VTextarea v-model="form.medical_notes" label="Nota Médica"
                                placeholder="Alergias, condiciones, medicamentos, etc." auto-grow />
                        </VCol>
                    </VRow>
                </VCol>

                <!-- Columna derecha: imagen -->
                <VCol cols="12" md="5" class="d-flex flex-column align-center">
                    <div class="avatar-upload mt-2">
                        <!-- input oculto -->
                        <input type="file" accept="image/*" class="avatar-upload-input" @change="loadFile($event)">

                        <!-- preview -->
                        <img v-if="IMAGEN_PREVIZUALIZA" :src="IMAGEN_PREVIZUALIZA" alt="" class="avatar-preview-img">

                        <!-- placeholder -->
                        <div v-else class="avatar-placeholder">
                            <VIcon size="32">ri-image-add-line</VIcon>
                            <span class="avatar-text-strong">Subir imagen aquí</span>
                            <span class="avatar-text-muted">Tamaño recomendado 1080 x 1080</span>
                            <span class="avatar-text-small">
                                Haz clic en el recuadro para seleccionar un archivo
                            </span>
                        </div>
                    </div>

                    <div v-if="IMAGEN_PREVIZUALIZA" class="avatar-caption mt-2">
                        <span class="avatar-text-strong">Cambiar imagen</span>
                        <span class="avatar-text-small">
                            Haz clic en el recuadro para seleccionar otra imagen
                        </span>
                    </div>
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

        <!-- 👤 Responsable -->
        <VCard title="Responsable:" class="my-2 pa-4">
            <VRow>
                <!-- Fila 1 -->
                <VCol cols="12" md="4">
                    <VTextField label="Nombre:" v-model="form.first_name" placeholder="Ejemplo: Rafael" />
                </VCol>
                <VCol cols="12" md="4">
                    <VTextField label="Apellido:" v-model="form.last_name" placeholder="Ejemplo: Mendoza" />
                </VCol>
                <VCol cols="12" md="4">
                    <VSelect :items="type_documents" v-model="form.type_document" label="Tipo de documento"
                        placeholder="Seleccione" eager />
                </VCol>

                <!-- Fila 2 -->
                <VCol cols="12" md="4">
                    <VTextField label="N° de documento:" type="number" v-model="form.n_document"
                        placeholder="Ejemplo: 12345678" />
                </VCol>
                <VCol cols="12" md="4">
                    <VTextField label="Telefono:" type="number" v-model="form.phone" placeholder="Ejemplo: 99999999" />
                </VCol>
                <VCol cols="12" md="4">
                    <VTextField v-model="form.email" label="Email" type="email" placeholder="johndoe@email.com" />
                </VCol>

                <!-- Fila 3 -->
                <VCol cols="12" md="5">
                    <VTextField label="Dirección:" v-model="form.address" placeholder="Ejemplo: Calle o Mzt" />
                </VCol>
                <VCol cols="12" md="3">
                    <VTextField label="Ciudad:" v-model="form.city" placeholder="Ejemplo: Lima" />
                </VCol>
                <VCol cols="12" md="4">
                    <VTextarea v-model="form.emergency_contact" label="Contacto de emergencia"
                        placeholder="Nombre, teléfono y relación" />
                </VCol>
            </VRow>
        </VCard>

        <VCardText class="pa-5 py-0 text-end">
            <VBtn variant="outlined" color="secondary" class="mb-4 mr-3" @click="openCancelDialog">
                Cancelar
            </VBtn>

            <VBtn color="primary" class="mb-4" @click="edit()">
                Actualizar
            </VBtn>
        </VCardText>
    </div>
</template>

<style>
.v-img__img--contain {
    object-fit: cover !important;
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
