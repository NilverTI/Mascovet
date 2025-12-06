<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  surgerieSelected: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['update:isDialogVisible', 'deleteSurgerie'])

// Control interno
const surgerie_selected = ref(null)
const success = ref(null)
const error_exists = ref(null)
const loading = ref(false)

// Sincronizar prop → ref interno
watch(
  () => props.surgerieSelected,
  newVal => {
    surgerie_selected.value = newVal
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

// Eliminar cirugía
const deleted = async () => {
  try {
    if (!surgerie_selected.value) return

    loading.value = true
    success.value = null
    error_exists.value = null

    const resp = await $api('/surgeries/' + surgerie_selected.value.id, {
      method: 'DELETE',
      onResponseError({ response }) {
        console.log(response)
        error_exists.value = response._data.error
      },
    })

    console.log(resp)

    if (resp.message == 403) {
      error_exists.value = 'La cirugía no se pudo eliminar porque ya ha sido atendida'
    } else {
      success.value = 'La cirugía se ha eliminado correctamente'
      emit('deleteSurgerie', surgerie_selected.value)
      emit('update:isDialogVisible', false)
    }
  } catch (error) {
    console.log(error)
    error_exists.value = 'Ocurrió un error al eliminar la cirugía'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <VDialog :model-value="props.isDialogVisible" max-width="420" @update:model-value="dialogVisibleUpdate">
    <VCard class="pa-4 pa-sm-6">

      <!-- Botón cerrar -->
      <DialogCloseBtn variant="text" size="default" @click="closeDialog" />

      <VCardText class="pt-6">

        <!-- Título -->
        <div class="mb-4" v-if="surgerie_selected">
          <h4 class="text-h5 text-center mb-2">
            Eliminar cirugía #{{ surgerie_selected.id }}
          </h4>
          <p class="text-body-2 text-center">
            ¿Estás seguro de que deseas eliminar la cirugía de la mascota
            <strong>"{{ surgerie_selected.pet.name }}"</strong>?
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

      <!-- Botones -->
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
