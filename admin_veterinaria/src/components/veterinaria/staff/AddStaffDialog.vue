<script setup>
const props = defineProps({
  isDialogVisible: { type: Boolean, required: true },
  roles: { type: Object, required: true }
})

const emit = defineEmits(['update:isDialogVisible', 'addUser'])

const dialogVisibleUpdate = val => emit('update:isDialogVisible', val)

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
  'CARNET DE EXTRANJERIA'
]

const isPasswordVisible = ref(false)
const warning = ref(null)
const success = ref(null)
const error_exists = ref(null)
const FILE_AVATAR = ref(null)
const IMAGEN_PREVIZUALIZA = ref(null)
const roles = ref([])
const load_request = ref(null)

// -----------------------
// LIMPIAR CAMPOS
// -----------------------
const fieldsClean = () => {
  form.value = {
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
  }
  FILE_AVATAR.value = null
  IMAGEN_PREVIZUALIZA.value = null
}

// -----------------------
// VALIDAR EMAIL
// -----------------------
const validateEmail = email => {
  const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return regex.test(email)
}

// -----------------------
// SUBIR IMAGEN
// -----------------------
const loadFile = ($event) => {
  if ($event.target.files[0].type.indexOf("image") < 0) {
    FILE_AVATAR.value = null
    IMAGEN_PREVIZUALIZA.value = null
    warning.value = "SOLAMENTE PUEDEN SER ARCHIVOS DE TIPO IMAGEN"
    return
  }
  warning.value = ''
  FILE_AVATAR.value = $event.target.files[0]
  let reader = new FileReader()
  reader.readAsDataURL(FILE_AVATAR.value)
  reader.onloadend = () => IMAGEN_PREVIZUALIZA.value = reader.result
}

// -----------------------
// GUARDAR USUARIO
// -----------------------
const store = async () => {
  warning.value = null

  if (!form.value.name) return warning.value = "Se debe llenar el nombre del usuario"
  if (!form.value.surname) return warning.value = "Se debe llenar el apellido del usuario"
  if (!form.value.email) return warning.value = "Se debe llenar el email del usuario"

  // Validación de email correcto
  if (!validateEmail(form.value.email)) {
    warning.value = "Correo inválido, debe contener un formato correcto (ejemplo@mail.com)"
    return
  }

  if (!form.value.phone) return warning.value = "Se debe llenar el teléfono del usuario"
  if (!form.value.gender) return warning.value = "Se debe seleccionar el género"
  if (!FILE_AVATAR.value) return warning.value = "Se debe seleccionar un avatar para el usuario"
  if (!form.value.role_id) return warning.value = "Se debe seleccionar un rol"
  if (!form.value.password) return warning.value = "Se debe digitar una contraseña"

  // Validación de contraseña
  if (form.value.password.length < 6) {
    warning.value = "La contraseña es demasiado corta (mínimo 6 caracteres)"
    return
  }

  let formData = new FormData()
  Object.entries(form.value).forEach(([key, value]) => {
    if (value !== null) formData.append(key, value)
  })

  formData.append("imagen", FILE_AVATAR.value)

  try {
    load_request.value = true

    const resp = await $api('/staffs', {
      method: 'POST',
      body: formData,
      onResponseError({ response }) {
        error_exists.value = response._data.error
      }
    })

    load_request.value = false
    console.log(resp)

    if (resp.message == 403) {
      warning.value = resp.message_text
    } else {
      success.value = "El usuario se ha creado correctamente"
      setTimeout(() => {
        success.value = null
        warning.value = null
        error_exists.value = null
        fieldsClean()
        emit('update:isDialogVisible', false)
      }, 1500)
      emit('addUser', resp.user)
    }
  } catch (error) {
    console.log(error)
    error_exists.value = error
  }
}

onMounted(() => {
  roles.value = props.roles
})
</script>
<template>
  <VDialog :model-value="props.isDialogVisible" max-width="820" @update:model-value="dialogVisibleUpdate">
    <VCard class="refer-and-earn-dialog pa-3 pa-sm-11">

      <!-- BOTÓN CERRAR -->
      <DialogCloseBtn variant="text" size="default" @click="emit('update:isDialogVisible', false)" />

      <VCardText class="pa-5">
        <div class="mb-6">
          <h4 class="text-h4 text-center mb-2">Agregar Staff</h4>
        </div>

        <VRow>

          <!-- NOMBRE -->
          <VCol cols="12" md="6">
            <VTextField label="Nombre:" v-model="form.name" />
          </VCol>

          <!-- APELLIDO -->
          <VCol cols="12" md="6">
            <VTextField label="Apellido:" v-model="form.surname" />
          </VCol>

          <!-- TELÉFONO -->
          <VCol cols="12" md="4">
            <VTextField label="Teléfono:" type="number" v-model="form.phone" />
          </VCol>

          <!-- TIPO DOC -->
          <VCol cols="12" md="4">
            <VSelect :items="type_documents" v-model="form.type_document" label="Tipo de documento" eager />
          </VCol>

          <!-- NRO DOC -->
          <VCol cols="12" md="4">
            <VTextField label="N° de documento:" type="number" v-model="form.n_document" />
          </VCol>

          <!-- CUMPLEAÑOS -->
          <VCol cols="12" md="4">
            <AppDateTimePicker v-model="form.birthday" label="Cumpleaños" />
          </VCol>

          <!-- GÉNERO -->
          <VCol cols="12" md="4">
            <VRadioGroup v-model="form.gender" inline label="Género">
              <VRadio label="Masculino" value="M" />
              <VRadio label="Femenino" value="F" />
            </VRadioGroup>
          </VCol>

          <!-- DESCRIPCIÓN -->
          <VCol cols="12" md="4">
            <VTextarea v-model="form.designation" label="Designación" />
          </VCol>

          <!-- AVATAR (IGUAL AL EJEMPLO) -->
          <VCol cols="12" md="5" class="d-flex justify-center mt-n10">
            <div class="avatar-upload mt-2">
              <input type="file" accept="image/*" class="avatar-upload-input" @change="loadFile" />

              <img v-if="IMAGEN_PREVIZUALIZA" :src="IMAGEN_PREVIZUALIZA" class="avatar-preview-img" />

              <div v-else class="avatar-placeholder">
                <VIcon size="32">ri-image-add-line</VIcon>
                <span class="avatar-text-strong">Subir imagen aquí</span> <br>
                <span class="avatar-text-muted">Tamaño recomendado 1080x1080</span>
                <span class="avatar-text-small">Haz clic en el cuadro</span>
              </div>
            </div>

            <div v-if="IMAGEN_PREVIZUALIZA" class="avatar-caption mt-2">
              <span class="avatar-text-strong">Cambiar imagen</span>
              <span class="avatar-text-small">Clic para seleccionar otra imagen</span>
            </div>
          </VCol>

          <!-- BLOQUE ROL + EMAIL + CONTRASEÑA (compacto y alineado con la imagen) -->
          <VCol cols="12" md="6">

            <VSelect :items="roles" v-model="form.role_id" label="Seleccionar rol" item-title="name" item-value="id"
              eager class="mb-3" />

            <VTextField label="Email" type="email" v-model="form.email" placeholder="ejemplo@email.com" class="mb-3" />

            <VTextField v-model="form.password" label="Contraseña" placeholder="··········"
              :type="isPasswordVisible ? 'text' : 'password'"
              :append-inner-icon="isPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
              @click:append-inner="isPasswordVisible = !isPasswordVisible" />

            <!-- BOTÓN DE CREAR JUSTO DEBAJO DEL BLOQUE -->
            <div class="d-flex justify-end">
              <VBtn color="primary" class="mt-6" :loading="load_request" @click="store()">
                Crear
              </VBtn>
            </div>

          </VCol>


        </VRow>

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

      </VCardText>

    </VCard>
  </VDialog>
</template>

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
  background: rgba(255, 255, 255, 0.05);
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
  padding: 1rem;
  color: white;
}

.avatar-text-strong {
  font-weight: 600;
  font-size: .95rem;
}

.avatar-text-muted {
  font-size: .8rem;
  opacity: .9;
}

.avatar-text-small {
  font-size: .75rem;
  opacity: .8;
}

.avatar-caption {
  text-align: center;
  font-size: .8rem;
  opacity: .85;
}
</style>
