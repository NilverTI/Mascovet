<script setup>
import { useGenerateImageVariant } from '@/@core/composable/useGenerateImageVariant'
import pages404 from '@images/pages/404.png'
import miscMaskDark from '@images/misc/misc-mask-dark.png'
import miscMaskLight from '@images/misc/misc-mask-light.png'
import miscObj from '@images/pages/misc-404-object.png'

const authThemeMask = useGenerateImageVariant(miscMaskLight, miscMaskDark)

definePage({
  alias: '/pages/misc/not-found/:error(.*)',
  meta: {
    layout: 'blank',
    public: true,
  },
})
</script>

<template>
  <div class="misc-wrapper" role="main" aria-labelledby="err-title">

    <div class="content-split">

      <!-- IZQUIERDA: TEXTO -->
      <div class="content-left">
        <ErrorHeader id="err-title" status-code="404" title="Page Not Found ⚠️"
          description="No podemos encontrar lo que estás buscando." class="mb-10" />

        <div class="btn-container">
          <!-- VBtn mantiene la navegación; aria-label para accesibilidad -->
          <VBtn to="/" class="mt-6" color="primary" large aria-label="Regresar al inicio" type="button">
            Regresar al inicio
          </VBtn>
        </div>
      </div>

      <!-- DERECHA: IMAGEN 404 -->
      <div class="content-right" aria-hidden="false">
        <!-- use loading lazy, evita height fijo, controla tamaño vía CSS -->
        <VImg :src="pages404" alt="Ilustración página no encontrada (404)" loading="lazy" class="img-404" contain />
      </div>

    </div>

    <!-- IMÁGENES DE FONDO -->
    <VImg :src="authThemeMask" class="footer-coming-soon flip-in-rtl" cover aria-hidden="true" />
    <VImg :src="miscObj" class="footer-coming-soon-obj" :max-width="177" height="160" aria-hidden="true" />

  </div>
</template>

<style lang="scss">
@use "@core/scss/template/pages/misc.scss";

/* Variables (ajusta si tu proyecto tiene variables globales) */
$container-max: 1200px;
$gap-mobile: 1.5rem;
$gap-desktop: 2.5rem;

/* ========================================
     LAYOUT PRINCIPAL (mobile-first)
   ======================================== */
.misc-wrapper {
  position: relative;
  width: 100%;
  padding: 2rem 1rem;
  box-sizing: border-box;
}

/* Estructura principal */
.content-split {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  text-align: center;
  gap: $gap-mobile;
}

/* Left / Right blocks */
.content-left {
  width: 100%;
  max-width: 700px;
  /* evita que el texto se estire demasiado */
}

.content-right {
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}

/* IMAGEN 404 - responsive */
.img-404 {
  display: block;
  max-width: 420px;
  /* tamaño máximo en pantallas pequeñas/medianas */
  width: 100%;
  height: auto;
  /* evita recortes */
  margin-top: 0;
  transition: transform .25s ease;
}

/* Ajustes en desktop */
@media (min-width: 768px) {
  .content-split {
    flex-direction: row;
    justify-content: center;
    align-items: center;
    max-width: $container-max;
    margin: 0 auto;
    text-align: left;
    gap: $gap-desktop;
  }

  .content-left {
    flex: 1 1 50%;
    padding-right: 2rem;
  }

  .content-right {
    flex: 1 1 50%;
    justify-content: center;
  }

  /* Aumenta el tamaño de la ilustración en desktop */
  .img-404 {
    max-width: 520px;
  }

  /* Si quieres que el botón se alinee con el contenido en desktop,
     descomenta la siguiente regla:
  .btn-container { text-align: left; }
  */
}

/* Centrado del botón (por defecto mobile/desktop: centrado) */
.btn-container {
  text-align: center;
}

/* Opcional: pequeña animación al hacer hover sobre la ilustración */
.img-404:hover {
  transform: translateY(-6px);
}

/* Asegura que imágenes de fondo no interfieran en tab order */
.footer-coming-soon,
.footer-coming-soon-obj {
  pointer-events: none;
  user-select: none;
}
</style>
