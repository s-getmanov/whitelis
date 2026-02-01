<template>
  <div class="container-fluid">
    <!-- Заголовок и кнопки -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold">Управление заявками</h4>
      <div>
        <!-- <button class="btn btn-primary" @click="openCreateModal">
          <i class="bi bi-plus-circle me-1"></i>Новая заявка
        </button> -->
        <!-- [TODO] Картинку с темой "Заявки" сюда вместо кнопки -->
      </div>
    </div>

    <!-- Компонент RegistrationList -->
    <RegistrationList 
      mode="admin"
      @registration-edit="onRegistrationEdit"
      @registration-delete="onRegistrationDelete"
      @registration-create="onRegistrationCreate"
    />
  </div>
</template>

<script>

import RegistrationList from '@/components/RegistrationList.vue'

export default {
  name: 'AdminRegistrations',
  
  components: {
    RegistrationList
  },
    
}
</script>