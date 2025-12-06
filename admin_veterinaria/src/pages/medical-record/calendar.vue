<script setup>
import FullCalendar from '@fullcalendar/vue3'
import { blankEvent, useCalendar } from '@/plugins/calendar/useCalendar'

// Componentes
import CalendarEventHandler from '@/components/veterinaria/medical_record/CalendarEventHandler.vue'

// Estado del evento seleccionado
const event = ref({ ...blankEvent })

// Drawer: formulario lateral para crear/editar eventos
const isEventHandlerSidebarActive = ref(false)

// Resetear event al cerrar el drawer
watch(isEventHandlerSidebarActive, open => {
  if (!open)
    event.value = { ...blankEvent }
})

// Responsive sidebar del layout general
const { isLeftSidebarOpen } = useResponsiveLeftSidebar()

// useCalendar contiene toda la lógica de eventos y configuración
const {
  refCalendar,
  calendarOptions,
  addEvent,
  updateEvent,
  removeEvent,
  jumpToDate,
} = useCalendar(event, isEventHandlerSidebarActive, isLeftSidebarOpen)

definePage({
  meta: { permisssion: 'calendar' },
})
</script>

<template>
  <div class="calendar-container">

    <VCard title="Calendario de Registros Médicos" class="pa-4">
      <VLayout>
        <VMain>
          <VCard flat class="calendar-card">
            <FullCalendar ref="refCalendar" :options="calendarOptions" class="medical-calendar" />
          </VCard>
        </VMain>
      </VLayout>
    </VCard>

    <!-- Drawer para añadir / editar eventos -->
    <CalendarEventHandler v-model:isDrawerOpen="isEventHandlerSidebarActive" :event="event" @add-event="addEvent"
      @update-event="updateEvent" @remove-event="removeEvent" />
  </div>
</template>

<style lang="scss">
@use "@core/scss/template/libs/full-calendar";

// Estilo general del contenedor
.calendar-container {
  position: relative;
}

// Evita clipping del calendario en layouts de Vuetify
.v-layout,
.v-card {
  overflow: visible !important;
}

// FullCalendar mejorado
.medical-calendar {
  .fc-toolbar-title {
    font-weight: 700;
    font-size: 1.3rem;
    color: var(--v-theme-primary);
  }

  .fc-daygrid-day-number {
    font-weight: 600;
  }

  .fc-event {
    border-radius: 6px;
    padding: 2px 4px;
  }
}

// Drawer más elegante
.calendar-add-event-drawer {
  &.v-navigation-drawer:not(.v-navigation-drawer--temporary) {
    border-radius: 0.5rem 0 0 0.5rem;
  }
}
</style>
