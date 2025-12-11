<script setup>
import { useGenerateImageVariant } from '@/@core/composable/useGenerateImageVariant'
import miscMaskLight from '@images/misc/misc-mask-light.png'
import pages401 from '@images/pages/401.png'
import miscMaskDark from '@images/misc/misc-mask-dark.png'
import miscObj from '@images/pages/misc-401-object.png'

const miscThemeMask = useGenerateImageVariant(miscMaskLight, miscMaskDark)

definePage({
  alias: '/pages/misc/not-authorized',
  meta: {
    layout: 'blank',
    public: true,
  },
})
</script>

<template>
  <div class="misc-wrapper" role="main" aria-labelledby="auth-401-title">

    <div class="content-split">

      <!-- IZQUIERDA: TEXTO + BOTÓN -->
      <div class="content-left">
        <div class="text-wrap">
          <ErrorHeader id="auth-401-title" status-code="401" title="Tú no estás autorizado 🔐"
            description="No tienes permiso para acceder a esta página. ¡Vuelve a la página principal!" class="mb-6" />

          <div class="btn-container">
            <VBtn to="/" class="mt-4" color="primary" large aria-label="Regresar al inicio" type="button">
              Regresar al inicio
            </VBtn>
          </div>
        </div>
      </div>

      <!-- DERECHA: IMAGEN 401 -->
      <div class="content-right" aria-hidden="false">
        <!-- mantuve alt y la lógica de alturas (400/500) en CSS responsive para preservar el comportamiento -->
        <VImg :src="pages401" alt="Coming Soon" loading="lazy" class="img-401 my-sm-5" />
      </div>
    </div>

    <!-- IMÁGENES DE FONDO (mismo comportamiento d-none d-md-block) -->
    <VImg :src="miscThemeMask" class="d-none d-md-block footer-coming-soon flip-in-rtl" cover aria-hidden="true" />
    <VImg :src="miscObj" class="d-none d-md-block footer-coming-soon-obj" :max-width="212" height="165"
      aria-hidden="true" />
  </div>
</template>

<style lang="scss">
@use "@core/scss/template/pages/misc.scss";

/* Variables rápidas (ajusta si tienes variables globales) */
$container-max: 1200px;
$gap-mobile: 1.25rem;
$gap-desktop: 2rem;

/* Mobile-first layout */
.misc-wrapper {
  position: relative;
  width: 100%;
  padding: 2rem 1rem;
  box-sizing: border-box;
}

/* Contenedor principal: stack en móvil, row en desktop */
.content-split {
  display: flex;
  flex-direction: column;
  gap: $gap-mobile;
  align-items: center;
  text-align: center;
  width: 100%;
}

/* LEFT */
.content-left {
  width: 100%;
  max-width: 720px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.text-wrap {
  width: 100%;
  padding: 0 0.5rem;
}

/* RIGHT */
.content-right {
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}

/* Imagen 401 - mantengo las alturas 400 (xs) y 500 (default) pero lo controlo por media queries */
.img-401 {
  width: 100%;
  height: 400px;
  /* comportamiento para pantallas xs (equivalente a $vuetify.display.xs ? 400) */
  max-width: 560px;
  object-fit: contain;
  display: block;
}

/* Desktop layout */
@media (min-width: 768px) {
  .content-split {
    flex-direction: row;
    justify-content: center;
    align-items: center;
    gap: $gap-desktop;
    max-width: $container-max;
    margin: 0 auto;
    text-align: left;
  }

  .content-left {
    flex: 1 1 50%;
    padding-right: 1.5rem;
    justify-content: flex-start;
  }

  .content-right {
    flex: 1 1 50%;
    justify-content: center;
  }

  /* altura en desktop = 500 (igual que antes) */
  .img-401 {
    height: 500px;
    max-width: 500px;
  }

  .btn-container {
    text-align: left;
    /* en desktop el botón queda alineado al texto */
  }
}

/* Centrado botón en móvil (por defecto) */
.btn-container {
  text-align: center;
  margin-top: 0.5rem;
}

/* Pequeña mejora visual al pasar hover sobre la imagen (opcional, no cambia datos) */
.img-401:hover {
  transform: translateY(-4px);
  transition: transform 180ms ease;
}

/* Aseguramos que los elementos de fondo no interrumpan interacciones */
.footer-coming-soon,
.footer-coming-soon-obj {
  pointer-events: none;
  user-select: none;
}
</style>
