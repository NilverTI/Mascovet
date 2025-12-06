<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  vaccinationSelected: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['update:isDialogVisible', 'deleteVaccination'])

// Control interno
const vaccination_selected = ref(null)
const success = ref(null)
const error_exists = ref(null)
const loading = ref(false)

// Sincronizar prop → ref local
watch(
  () => props.vaccinationSelected,
  newVal => {
    vaccination_selected.value = newVal
    success.value = null
    error_exists.value = null
  },
  { immediate: true },
)

// Actualizar v-model del diálogo
const dialogVisibleUpdate = val => {
  emit('update:isDialogVisible', val)
}

// Cerrar y limpiar estados
const closeDialog = () => {
  success.value = null
  error_exists.value = null
  emit('update:isDialogVisible', false)
}

// Eliminar vacuna
const deleted = async () => {
  try {
    if (!vaccination_selected.value) return

    loading.value = true
    success.value = null
    error_exists.value = null

    const resp = await $api('/vaccinations/' + vaccination_selected.value.id, {
      method: 'DELETE',
      onResponseError({ response }) {
        console.log(response)
        error_exists.value = response._data.error
      },
    })

    console.log(resp)

    if (resp.message == 403) {
      error_exists.value = 'La vacuna no se pudo eliminar porque ya ha sido atendida'
    } else {
      success.value = 'La vacuna se ha eliminado correctamente'
      // Notificar al padre para que quite la fila
      emit('deleteVaccination', vaccination_selected.value)
      emit('update:isDialogVisible', false)
    }
  } catch (error) {
    console.log(error)
    error_exists.value = 'Ocurrió un error al eliminar la vacuna'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <VDialog :model-value="props.isDialogVisible" max-width="420" @update:model-value="dialogVisibleUpdate">
    <VCard class="pa-4 pa-sm-6">

      <!-- Cerrar -->
      <DialogCloseBtn variant="text" size="default" @click="closeDialog" />

      <VCardText class="pt-6">
        <div class="mb-4" v-if="vaccination_selected">
          <h4 class="text-h5 text-center mb-2">
            Eliminar vacuna #{{ vaccination_selected.id }}
          </h4>
          <p class="text-body-2 text-center">
            ¿Estás seguro de que deseas eliminar el registro de vacunación de la mascota
            <strong>"{{ vaccination_selected.pet.name }}"</strong>?
          </p>
        </div>

        <!-- Alertas -->
        <VAlert v-if="error_exists" type="error" class="mt-3" density="comfortable">
          <strong>{{ error_exists }}</strong>
        </VAlert>

        <VAlert v-if="success" type="success" class="mt-3" density="comfortable">
          <strong>{{ success }}</strong>
        </VAlert>
      </VCardText>

      <VCardActions class="pa-4 pt-0 justify-end">
        <VBtn variant="outlined" color="secondary" class="mr-2" @click="closeDialog">
          Cancelar
        </VBtn>

        <VBtn color="error" :loading="loading" @click="deleted">
          Eliminar
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
