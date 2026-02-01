<template>
  <div class="modal fade show d-block" tabindex="-1" role="dialog" @click.self="close">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            {{ user ? 'Редактирование пользователя' : 'Создание пользователя' }}
          </h5>
          <button type="button" class="btn-close" @click="close"></button>
        </div>
        
        <div class="modal-body">
          <form @submit.prevent="save">
            <div class="row g-3">
              <div class="col-md-6">
                <label for="name" class="form-label">Имя <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" v-model="form.name" required>
              </div>
              
              <div class="col-md-6">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" v-model="form.email" required>
              </div>
              
              <div class="col-md-6">
                <label for="password" class="form-label">
                  {{ user ? 'Новый пароль (оставьте пустым, чтобы не менять)' : 'Пароль' }}
                  <span v-if="!user" class="text-danger">*</span>
                </label>
                <input type="password" class="form-control" id="password" v-model="form.password" 
                       :required="!user">
              </div>
              
              <div class="col-md-6">
                <label for="phone" class="form-label">Телефон</label>
                <input type="tel" class="form-control" id="phone" v-model="form.phone">
              </div>
              
              <div class="col-md-6">
                <label for="role" class="form-label">Роль <span class="text-danger">*</span></label>
                <select class="form-select" id="role" v-model="form.role" required>
                  <option value="admin">Администратор</option>
                  <option value="participant">Участник</option>
                  <option value="judge">Судья</option>
                  <option value="volunteer">Волонтер</option>
                </select>
              </div>
              
              <div class="col-md-6">
                <label for="status" class="form-label">Статус <span class="text-danger">*</span></label>
                <select class="form-select" id="status" v-model="form.status" required>
                  <option value="active">Активен</option>
                  <option value="blocked">Заблокирован</option>
                  <option value="pending">Ожидает подтверждения</option>
                </select>
              </div>
            </div>
          </form>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="close" :disabled="loading">
            Отмена
          </button>
          <button type="button" class="btn btn-primary" @click="save" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
            {{ user ? 'Обновить' : 'Создать' }}
          </button>
        </div>
      </div>
    </div>
  </div>
  
  <div class="modal-backdrop fade show"></div>
</template>

<script>
import { ref, watch } from 'vue'

export default {
  name: 'UserModal',
  
  props: {
    user: {
      type: Object,
      default: null
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  
  emits: ['save', 'close'],
  
  setup(props, { emit }) {
    const form = ref({
      name: '',
      email: '',
      password: '',
      phone: '',
      role: 'participant',
      status: 'active'
    })
    
    // При изменении props.user обновляем форму
    watch(() => props.user, (newUser) => {
      if (newUser) {
        form.value = {
          name: newUser.name || '',
          email: newUser.email || '',
          password: '', // Не показываем текущий пароль
          phone: newUser.phone || '',
          role: newUser.role || 'participant',
          status: newUser.status || 'active'
        }
      } else {
        // Сброс формы для нового пользователя
        form.value = {
          name: '',
          email: '',
          password: '',
          phone: '',
          role: 'participant',
          status: 'active'
        }
      }
    }, { immediate: true })
    
    const save = () => {
      // Удаляем пустой пароль при редактировании
      const dataToSave = { ...form.value }
      if (props.user && !dataToSave.password) {
        delete dataToSave.password
      }
      emit('save', dataToSave)
    }
    
    const close = () => {
      emit('close')
    }
    
    return {
      form,
      save,
      close
    }
  }
}
</script>

<style scoped>
.modal-backdrop {
  opacity: 0.5;
}
</style>