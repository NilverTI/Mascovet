<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  appointmentSelected: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['update:isDialogVisible', 'deleteAppointment'])

const warning = ref(null)
const success = ref(null)
const error_exists = ref(null)
const appointment_selected = ref(null)
const loading = ref(false)

// Mantener appointment_selected siempre sincronizado con el prop
watch(
  () => props.appointmentSelected,
  val => {
    appointment_selected.value = val
  },
  { immediate: true },
)

// v-model del dialog
const dialogVisibleUpdate = val => {
  emit('update:isDialogVisible', val)

  if (!val) {
    // limpiar mensajes cuando se cierra
    warning.value = null
    success.value = null
    error_exists.value = null
    loading.value = false
  }
}

const closeDialog = () => {
  dialogVisibleUpdate(false)
}

const deleted = async () => {
  try {
    if (!appointment_selected.value) return

    loading.value = true
    error_exists.value = null
    success.value = null
    warning.value = null

    const resp = await $api('/appointments/' + appointment_selected.value.id, {
      method: 'DELETE',
      onResponseError({ response }) {
        console.log(response)
        error_exists.value = response._data?.error || 'Ocurrió un error al eliminar la cita'
      },
    })

    console.log(resp)

    if (resp.message == 403) {
      error_exists.value =
        'La cita médica no se puede eliminar porque ya ha sido atendida'
    } else {
      success.value = 'La cita médica se ha eliminado correctamente'
      emit('deleteAppointment', appointment_selected.value)
      emit('update:isDialogVisible', false)
    }
  } catch (error) {
    console.log(error)
    error_exists.value = 'Ocurrió un error al eliminar la cita'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    max-width="420"
    @update:model-value="dialogVisibleUpdate"
  >
    <VCard class="refer-and-earn-dialog pa-3 pa-sm-11">
      <!-- Botón cerrar (X) -->
      <DialogCloseBtn
        variant="text"
        size="default"
        @click="closeDialog"
      />

      <VCardText class="pa-5">
        <div class="mb-6" v-if="appointment_selected">
          <h4 class="text-h4 text-center mb-2">
            Eliminar cita #{{ appointment_selected.id }}
          </h4>
        </div>

        <p v-if="appointment_selected" class="text-center">
          ¿Estás seguro de eliminar la cita médica de la mascota
          <strong>"{{ appointment_selected.pet.name }}"</strong>?
        </p>

        <VAlert
          type="error"
          class="mt-3"
          v-if="error_exists"
        >
          <strong>{{ error_exists }}</strong>
        </VAlert>

        <VAlert
          type="success"
          class="mt-3"
          v-if="success"
        >
          <strong>{{ success }}</strong>
        </VAlert>
      </VCardText>

      <VCardText class="pa-5 d-flex justify-end gap-2">
        <VBtn
          variant="outlined"
          color="secondary"
          class="mb-4"
          @click="closeDialog"
        >
          Cancelar
        </VBtn>

        <VBtn
          color="error"
          class="mb-4"
          :loading="loading"
          @click="deleted"
        >
          Eliminar
        </VBtn>
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
</style>
