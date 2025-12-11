<script setup>
import { ref, watch, nextTick } from 'vue'
import { useGenerateImageVariant } from '@/@core/composable/useGenerateImageVariant'
import authV2LoginIllustrationBorderedDark from '@images/pages/auth-v2-login-illustration-bordered-dark.png'
import authV2LoginIllustrationBorderedLight from '@images/pages/auth-v2-login-illustration-bordered-light.png'
import authV2LoginIllustrationDark from '@images/pages/auth-v2-login-illustration-dark.png'
import authV2LoginIllustrationLight from '@images/pages/auth-v2-login-illustration-light.png'
import authV2LoginMaskDark from '@images/pages/auth-v2-login-mask-dark.png'
import authV2LoginMaskLight from '@images/pages/auth-v2-login-mask-light.png'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'

definePage({
  meta: {
    layout: 'blank',
    unauthenticatedOnly: true,
  },
})

// -----------------------------
// Estado del formulario
// -----------------------------
const form = ref({
  email: '',
  password: '',
  remember: false,
})

const errorMessage = ref(null)
const success = ref(false)

const emailTouched = ref(false)
const passwordTouched = ref(false)

const emailInvalid = ref(false)
const passwordInvalid = ref(false)

const isPasswordVisible = ref(false)

// -----------------------------
// Constantes y validadores
// -----------------------------
const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

const isValidEmail = (email) => {
  if (!email) return false
  const t = String(email).trim()
  return EMAIL_REGEX.test(t) && !t.startsWith('@') && !t.endsWith('@')
}

const isValidPassword = (pwd) => {
  return String(pwd ?? '').length >= 6
}

// -----------------------------
// Watchers (validación en tiempo real)
// -----------------------------
watch(
  () => form.value.email,
  (val) => {
    emailTouched.value = true
    emailInvalid.value = !isValidEmail(val)
    if (!emailInvalid.value && errorMessage.value) errorMessage.value = null
  }
)

watch(
  () => form.value.password,
  (val) => {
    passwordTouched.value = true
    passwordInvalid.value = !isValidPassword(val)
    if (!passwordInvalid.value && errorMessage.value) errorMessage.value = null
  }
)

// -----------------------------
// Login
// -----------------------------
let reloadTimeoutId = null

const login = async () => {
  try {
    errorMessage.value = null
    success.value = false

    // Validaciones locales
    if (!isValidEmail(form.value.email)) {
      emailInvalid.value = true
      errorMessage.value = 'Correo inválido. Verifica que tenga formato usuario@dominio.com'
      return
    }

    if (!isValidPassword(form.value.password)) {
      passwordInvalid.value = true
      errorMessage.value = 'Contraseña inválida. Debe tener al menos 6 caracteres.'
      return
    }

    // Petición al API
    const resp = await $api('/auth/login', {
      method: 'POST',
      body: {
        email: form.value.email.trim(),
        password: form.value.password,
      },
      onResponseError({ response }) {
        const status = response?.status
        if (status === 401) {
          errorMessage.value = 'Credenciales incorrectas. Revisa tu email o contraseña.'
          return
        }
        // intenta extraer mensaje del body si existe
        errorMessage.value = response?._data?.error ?? 'Error desconocido del servidor'
      },
    })

    // Validación de respuesta
    if (!resp || !resp.access_token) {
      if (!errorMessage.value) {
        errorMessage.value = resp?._data?.error ?? 'Respuesta inválida del servidor'
      }
      return
    }

    // Guardar token / usuario
    localStorage.setItem('token', resp.access_token)
    localStorage.setItem('user', JSON.stringify(resp.user))

    success.value = true

    // Pequeña pausa para mostrar mensaje y luego recargar
    if (reloadTimeoutId) clearTimeout(reloadTimeoutId)
    reloadTimeoutId = setTimeout(async () => {
      await nextTick()
      document.location.reload()
    }, 1200)
  } catch (err) {
    console.error('Login error:', err)
    const status = err?.response?.status ?? err?.status
    if (status === 401) {
      errorMessage.value = 'Credenciales incorrectas. Revisa tu email o contraseña.'
    } else {
      errorMessage.value = err?.message ?? 'Ocurrió un error'
    }
  }
}

// -----------------------------
// Imágenes reactivas
// -----------------------------
const authV2LoginMask = useGenerateImageVariant(authV2LoginMaskLight, authV2LoginMaskDark)
const authV2LoginIllustration = useGenerateImageVariant(
  authV2LoginIllustrationLight,
  authV2LoginIllustrationDark,
  authV2LoginIllustrationBorderedLight,
  authV2LoginIllustrationBorderedDark,
  true
)
</script>

<template>
  <VRow no-gutters class="auth-wrapper">
    <VCol md="8" class="d-none d-md-flex align-center justify-center position-relative auth-left">
      <div class="d-flex align-center justify-center pa-4 auth-illustration-wrapper">
        <img :src="authV2LoginIllustration" class="auth-illustration" alt="auth-illustration" />
      </div>
      <VImg :src="authV2LoginMask" class="d-none d-md-flex auth-footer-mask" alt="auth-mask" />
    </VCol>

    <VCol cols="12" md="4" class="auth-card-v2 d-flex align-center justify-center auth-right">
      <VCard flat class="auth-card px-6 py-8" max-width="460">
        <VCardText class="text-center pb-4">
          <div class="auth-card-logo animated-logo">
            <VNodeRenderer :nodes="themeConfig.app.logo" />
            <div class="app-logo-title-block mt-2">
              <span class="app-logo-title-inline">{{ themeConfig.app.title }}</span>
            </div>
          </div>

          <h4 class="text-h4 mt-4 mb-2">Bienvenido A Mascovet👋🏻</h4>
          <p class="text-body-1">Inicia sesión y comienza la experiencia.</p>
        </VCardText>

        <VCardText>
          <VForm @submit.prevent="login">
            <VTextField class="mb-4" v-model="form.email" label="Email" type="email" density="comfortable"
              variant="outlined" :error="emailInvalid && emailTouched"
              :error-messages="emailInvalid && emailTouched ? ['El correo debe ser válido y contener @ (ej: usuario@dominio.com)'] : []" />

            <VTextField class="mb-2" v-model="form.password" label="Password"
              :type="isPasswordVisible ? 'text' : 'password'"
              :append-inner-icon="isPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
              @click:append-inner="isPasswordVisible = !isPasswordVisible" density="comfortable" variant="outlined"
              :error="passwordInvalid && passwordTouched"
              :error-messages="passwordInvalid && passwordTouched ? ['La contraseña debe tener al menos 6 caracteres'] : []" />

            <VAlert type="success" class="my-4" v-if="success">
              Credenciales correctas. Entrando...
            </VAlert>

            <VAlert type="error" class="my-4" v-if="errorMessage">
              Error: <strong>{{ errorMessage }}</strong>
            </VAlert>

            <div class="blob-btn-container">
              <button type="submit" class="blob-btn">
                Iniciar sesión
                <span class="blob-btn__inner">
                  <span class="blob-btn__blobs">
                    <span class="blob-btn__blob"></span>
                    <span class="blob-btn__blob"></span>
                    <span class="blob-btn__blob"></span>
                    <span class="blob-btn__blob"></span>
                  </span>
                </span>
              </button>
            </div>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <svg xmlns="http://www.w3.org/2000/svg" version="1.1" style="position: absolute; width: 0; height: 0;">
    <defs>
      <filter id="goo">
        <feGaussianBlur in="SourceGraphic" result="blur" stdDeviation="10"></feGaussianBlur>
        <feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0 0 1 0 0 0 0 0 1 0 0 0 0 0 21 -7" result="goo">
        </feColorMatrix>
        <feBlend in2="goo" in="SourceGraphic" result="mix"></feBlend>
      </filter>
    </defs>
  </svg>
</template>

<style>
/* --- estilos exactos copiados para mantener apariencia --- */
::-webkit-scrollbar {
  width: 0px;
  background: transparent;
}

.auth-wrapper {
  height: 100vh;
}

.auth-left {
  background: radial-gradient(circle at 30% 50%,
      rgba(255, 255, 255, 0.06),
      rgba(0, 0, 0, 0.5));
  padding: 2rem;
}

.auth-illustration {
  max-width: 73.5%;
  filter: drop-shadow(0 18px 24px rgba(0, 0, 0, .25));
  animation: float 6s ease-in-out infinite;
}

.auth-right {
  background: rgba(255, 255, 255, 0.02);
  backdrop-filter: blur(14px);
}

.auth-card {
  border-radius: 22px !important;
  backdrop-filter: blur(12px);
  background: rgba(255, 255, 255, 0.06) !important;
  box-shadow:
    0 18px 40px rgba(0, 0, 0, 0.25),
    inset 0 0 80px rgba(255, 255, 255, 0.02);
}

.animated-logo svg,
.animated-logo img {
  width: 250px;
  height: 250px;
  animation: pop 0.4s ease forwards, 
  float 1s ease-in-out infinite;
}

.app-logo-title-inline {
  opacity: 0;
}

.blob-btn-container {
  text-align: center;
  margin-top: 1rem;
}

.blob-btn {
  z-index: 1;
  position: relative;
  padding: 16px 46px;
  width: 100%;
  text-align: center;
  text-transform: uppercase;
  color: #ffffff;
  font-size: 16px;
  font-weight: 600;
  background-color: transparent;
  outline: none;
  border: none;
  transition: color 0.5s;
  cursor: pointer;
  border-radius: 30px;
  letter-spacing: 0.5px;
}

.blob-btn:before {
  content: "";
  z-index: 1;
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  border: 2px solid #5170ff;
  border-radius: 30px;
}

.blob-btn:after {
  content: "";
  z-index: -2;
  position: absolute;
  left: 3px;
  top: 3px;
  width: 100%;
  height: 100%;
  transition: all 0.3s 0.2s;
  border-radius: 30px;
}

.blob-btn:hover {
  color: #5170ff;
  border-radius: 30px;
}

.blob-btn:hover:after {
  transition: all 0.3s;
  left: 0;
  top: 0;
  border-radius: 30px;
}

.blob-btn__inner {
  z-index: -1;
  overflow: hidden;
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  border-radius: 30px;
  background: #5170ff;
}

.blob-btn__blobs {
  position: relative;
  display: block;
  height: 100%;
  filter: url('#goo');
}

.blob-btn__blob {
  position: absolute;
  top: 2px;
  width: 25%;
  height: 100%;
  background: #ffffff;
  border-radius: 100%;
  transform: translate3d(0, 150%, 0) scale(1.7);
  transition: transform 0.45s;
}

@supports(filter: url('#goo')) {
  .blob-btn__blob {
    transform: translate3d(0, 150%, 0) scale(1.4);
  }
}

.blob-btn__blob:nth-child(1) {
  left: 0%;
  transition-delay: 0s;
}

.blob-btn__blob:nth-child(2) {
  left: 30%;
  transition-delay: 0.08s;
}

.blob-btn__blob:nth-child(3) {
  left: 60%;
  transition-delay: 0.16s;
}

.blob-btn__blob:nth-child(4) {
  left: 90%;
  transition-delay: 0.24s;
}

.blob-btn:hover .blob-btn__blob {
  transform: translateZ(0) scale(1.7);
}

@supports(filter: url('#goo')) {
  .blob-btn:hover .blob-btn__blob {
    transform: translateZ(0) scale(1.4);
  }
}

@keyframes float {
  0% {
    transform: translateY(0);
  }

  50% {
    transform: translateY(-10px);
  }

  100% {
    transform: translateY(0);
  }
}

@keyframes pop {
  0% {
    opacity: 0;
    transform: scale(.7);
  }

  100% {
    opacity: 1;
    transform: scale(1);
  }
}

/* Móviles */
@media (max-width: 767px) {

  .animated-logo svg,
  .animated-logo img {
    width: 150px;
    height: 150px;
  }

  .blob-btn {
    padding: 14px 30px;
    font-size: 14px;
  }
}
</style>
