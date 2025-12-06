<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  roles: {
    type: Object,
    required: true,
  },
  userSelected: {
    type: Object,
    required: true,
  }
})

const emit = defineEmits(['update:isDialogVisible', 'editUser'])

const dialogVisibleUpdate = val => {
  emit('update:isDialogVisible', val)
}

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

const update = async () => {
  warning.value = null

  if (!form.value.name) {
    warning.value = "Se debe llenar el nombre del usuario"
    return
  }
  if (!form.value.surname) {
    warning.value = "Se debe llenar el apellido del usuario"
    return
  }
  if (!form.value.email) {
    warning.value = "Se debe llenar el email del usuario"
    return
  }
  if (!form.value.phone) {
    warning.value = "Se debe llenar el telefono del usuario"
    return
  }
  if (!form.value.gender) {
    warning.value = "Se debe llenar el genero del usuario"
    return
  }
  if (!form.value.role_id) {
    warning.value = "Se debe seleccionar un rol para el usuario"
    return
  }

  let formData = new FormData()

  formData.append("name", form.value.name)
  formData.append("surname", form.value.surname)
  formData.append("email", form.value.email)

  if (form.value.type_document) {
    formData.append("type_document", form.value.type_document)
  }
  if (form.value.n_document) {
    formData.append("n_document", form.value.n_document)
  }

  formData.append("phone", form.value.phone)
  formData.append("gender", form.value.gender)

  if (form.value.designation) {
    formData.append("designation", form.value.designation)
  }
  if (form.value.password) {
    formData.append("password", form.value.password)
  }

  formData.append("role_id", form.value.role_id)

  if (form.value.birthday) {
    formData.append("birthday", form.value.birthday)
  }
  if (FILE_AVATAR.value) {
    formData.append("imagen", FILE_AVATAR.value)
  }

  try {
    success.value = null
    const resp = await $api('/staffs/' + props.userSelected.id, {
      method: 'POST',
      body: formData,
      onResponseError({ response }) {
        console.log(response)
        error_exists.value = response._data.error
      }
    })

    console.log(resp)
    if (resp.message == 403) {
      warning.value = resp.message_text
    } else {
      success.value = "El usuario se ha editado correctamente"
      setTimeout(() => {
        warning.value = null
        error_exists.value = null
      }, 1500)
      emit('editUser', resp.user)
    }
  } catch (error) {
    console.log(error)
    error_exists.value = error
  }
}

const loadFile = ($event) => {
  const file = $event.target.files[0]

  if (!file) return

  if (file.type.indexOf("image") < 0) {
    FILE_AVATAR.value = null
    IMAGEN_PREVIZUALIZA.value = null
    warning.value = "SOLAMENTE PUEDEN SER ARCHIVOS DE TIPO IMAGEN"
    return
  }

  warning.value = ''
  FILE_AVATAR.value = file

  const reader = new FileReader()
  reader.readAsDataURL(FILE_AVATAR.value)
  reader.onloadend = () => IMAGEN_PREVIZUALIZA.value = reader.result
}

onMounted(() => {
  roles.value = props.roles
  form.value.name = props.userSelected.name
  form.value.surname = props.userSelected.surname
  form.value.email = props.userSelected.email
  form.value.phone = props.userSelected.phone
  form.value.type_document = props.userSelected.type_document
  form.value.n_document = props.userSelected.n_document
  form.value.birthday = props.userSelected.birthday
  form.value.designation = props.userSelected.designation
  form.value.gender = props.userSelected.gender
  form.value.role_id = parseInt(props.userSelected.role_id)
  IMAGEN_PREVIZUALIZA.value = props.userSelected.avatar
})
</script>

<template>
  <VDialog :model-value="props.isDialogVisible" max-width="900" @update:model-value="dialogVisibleUpdate">
    <VCard class="refer-and-earn-dialog pa-3 pa-sm-11">

      <DialogCloseBtn variant="text" size="default" @click="emit('update:isDialogVisible', false)" />

      <VCardText class="pa-5">
        <div class="mb-6">
          <h4 class="text-h4 text-center mb-2">
            Editar Usuario : {{ props.userSelected.name }}
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
            <VTextField label="Telefono:" type="number" v-model="form.phone" placeholder="Example: 99999999" />
          </VCol>

          <VCol cols="12" md="4">
            <VSelect :items="type_documents" v-model="form.type_document" label="Tipo de documento"
              placeholder="Seleccione" eager />
          </VCol>

          <VCol cols="12" md="4">
            <VTextField label="N° de documento:" type="number" v-model="form.n_document" placeholder="07101010" />
          </VCol>

          <!-- Cumpleaños / Género / Rol -->
          <VCol cols="12" md="4">
            <AppDateTimePicker v-model="form.birthday" label="Cumpleaños" placeholder="Seleccione fecha" />
          </VCol>

          <VCol cols="12" md="4">
            <VRadioGroup v-model="form.gender" inline label="Género">
              <VRadio label="Masculino" value="M" />
              <VRadio label="Femenino" value="F" />
            </VRadioGroup>
          </VCol>

          <VCol cols="12" md="4">
            <VSelect :items="roles" v-model="form.role_id" label="Rol" item-title="name" item-value="id"
              placeholder="Seleccione rol" eager />
          </VCol>

          <!-- Descripción / Email / Password (izquierda) -->
          <VCol cols="12" md="6">
            <VTextarea v-model="form.designation" label="Designación" placeholder="Descripción o cargo" auto-grow />

            <VTextField class="mt-4" v-model="form.email" label="Email" type="email" placeholder="johndoe@email.com" />

            <VTextField class="mt-4" v-model="form.password" label="Password" placeholder="············"
              :type="isPasswordVisible ? 'text' : 'password'"
              :append-inner-icon="isPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
              @click:append-inner="isPasswordVisible = !isPasswordVisible" />
          </VCol>

          <!-- Imagen (derecha, pegada arriba) -->
          <VCol cols="12" md="6" class="d-flex justify-center">
            <div class="avatar-upload mt-2">
              <!-- input invisible, todo el cuadro es clickeable -->
              <input type="file" accept="image/*" class="avatar-upload-input" @change="loadFile($event)" />

              <div class="avatar-inner">
                <!-- Imagen si existe -->
                <img v-if="IMAGEN_PREVIZUALIZA" :src="IMAGEN_PREVIZUALIZA" alt="Previsualización"
                  class="avatar-preview-img" />

                <!-- Texto SIEMPRE visible encima -->
                <div class="avatar-overlay">
                  <VIcon size="32">ri-image-add-line</VIcon>
                  <span class="avatar-text-strong">Subir imagen aquí</span>
                  <span class="avatar-text-muted">Tamaño recomendado 1080 x 1080</span>
                  <span class="avatar-text-small">Haz clic en el recuadro para seleccionar un archivo</span>
                </div>
              </div>
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

        <!-- Botón más pegado al contenido -->
        <div class="mt-6 d-flex justify-end">
          <VBtn color="primary" @click="update()">
            Actualizar
          </VBtn>
        </div>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style lang="scss">
.refer-link-input {
  .v-field--appended {
    padding-inline-end: 0;
  }

  .v-field__append-inner {
    padding-block-start: 0.125rem;
  }
}

/* 🔳 Cuadro de imagen cuadrado, estilo limpio */
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
}

/* Input invisible pero toda el área es clickeable */
.avatar-upload-input {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
  z-index: 3;
}

.avatar-inner {
  position: relative;
  width: 100%;
  height: 100%;
}

/* Imagen al fondo */
.avatar-preview-img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  z-index: 1;
}

/* Texto por encima SIEMPRE visible */
.avatar-overlay {
  z-index: 1;
  height: 100%;
  width: 100%;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  align-items: center;
  justify-content: center;
  text-align: center;
  color: #ffffff;
  background: rgba(0, 0, 0, 0.2); // leve overlay para contraste
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
</style>
