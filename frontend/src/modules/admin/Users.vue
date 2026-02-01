<template>
  <div class="container-fluid">
    <!-- Заголовок и кнопки -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold">Управление пользователями</h4>
      <div>
        <button class="btn btn-primary" @click="openCreateModal">
          <i class="bi bi-person-plus me-1"></i>Новый пользователь
        </button>
      </div>
    </div>

    <!-- Компонент UserList -->
    <UserList 
      @user-edit="onUserEdit"
      @user-delete="onUserDelete"
      @user-create="onUserCreate"
    />
  </div>
</template>

<script>
import { ref } from 'vue'
import UserList from '@/components/UserList.vue'
import UserModal from '@/components/modals/UserModal.vue'

export default {
  name: 'AdminUsers',
  
  components: {
    UserList
  },
  
  setup() {
    const showModal = ref(false)
    const editingUser = ref(null)
    
    const openCreateModal = () => {
      editingUser.value = null
      showModal.value = true
    }
    
    const onUserEdit = (user) => {
      editingUser.value = user
      showModal.value = true
    }
    
    const onUserDelete = (user) => {
      if (confirm(`Удалить пользователя "${user.name}"?`)) {
        console.log('Удаление пользователя:', user)
        // В будущем - вызов API
      }
    }
    
    const onUserCreate = () => {
      console.log('Создание нового пользователя')
    }
    
    const saveUser = (userData) => {
      console.log('Сохранение пользователя:', userData)
      showModal.value = false
      // В будущем - вызов API и обновление списка
    }
    
    const closeModal = () => {
      showModal.value = false
      editingUser.value = null
    }
    
    return {
      showModal,
      editingUser,
      openCreateModal,
      onUserEdit,
      onUserDelete,
      onUserCreate,
      saveUser,
      closeModal
    }
  }
}
</script>