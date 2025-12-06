<script setup>
import avatar1 from '@images/avatars/avatar-1.png'
import { useRouter } from 'vue-router'

// Recuperamos el usuario del localStorage
const user = localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')) : null;
const router = useRouter()

// Función de Logout
const logout = async () => {
  localStorage.removeItem("token");
  localStorage.removeItem("user");

  await router.push("/login");
}
</script>

<template>
  <VBadge dot bordered location="bottom right" offset-x="2" offset-y="2" color="success" class="user-profile-badge">
    <VAvatar class="cursor-pointer" size="38">
      <VImg :src="avatar1" />

      <VMenu activator="parent" width="230" location="bottom end" offset="15px">
        <VList>
          <VListItem class="px-4">
            <div class="d-flex gap-x-2 align-center" v-if="user">
              <VAvatar>
                <VImg :src="user.avatar ? user.avatar : avatar1" />
              </VAvatar>

              <div>
                <div class="text-body-2 font-weight-medium text-high-emphasis">
                  {{ user.name + ' ' + user.surname }}
                </div>
                <div class="text-capitalize text-caption text-disabled">
                  {{ user.role.name }}
                </div>
              </div>
            </div>
          </VListItem>

          <VDivider class="my-2" />

          <VListItem class="px-4">
            <VBtn block color="error" size="small" append-icon="ri-logout-box-r-line" @click="logout()">
              Cerrar Sesión
            </VBtn>
          </VListItem>

        </VList>
      </VMenu>
    </VAvatar>
  </VBadge>
</template>

<style lang="scss">
.user-profile-badge {
  &.v-badge--bordered.v-badge--dot .v-badge__badge::after {
    color: rgb(var(--v-theme-background));
  }
}
</style>