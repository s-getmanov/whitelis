<template>
  <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5)">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            {{ event ? 'Редактирование мероприятия' : 'Новое мероприятие' }}
          </h5>
          <button type="button" class="btn-close" @click="$emit('close')" :disabled="loading"></button>
        </div>
        
        <div class="modal-body">
          <form @submit.prevent="handleSubmit">
            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label">Название *</label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="form.title"
                  required
                  placeholder="Введите название мероприятия"
                >
              </div>
              
              <div class="col-md-4">
                <label class="form-label">Статус</label>
                <select class="form-select" v-model="form.status">
                  <option value="draft">Черновик</option>
                  <option value="upcoming">Предстоящее</option>
                  <option value="active">Активное</option>
                  <option value="completed">Завершено</option>
                </select>
              </div>
              
              <div class="col-12">
                <label class="form-label">Описание</label>
                <textarea 
                  class="form-control" 
                  rows="2"
                  v-model="form.description"
                  placeholder="Краткое описание мероприятия"
                ></textarea>
              </div>
              
              <div class="col-md-6">
                <label class="form-label">Дата проведения *</label>
                <input 
                  type="date" 
                  class="form-control" 
                  v-model="form.date"
                  required
                >
              </div>
              
              <div class="col-md-6">
                <label class="form-label">Место проведения *</label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="form.location"
                  required
                  placeholder="Укажите место проведения"
                >
              </div>
              
              <div class="col-md-6">
                <label class="form-label">Дисциплина *</label>
                <select class="form-select" v-model="form.discipline" required>
                  <option value="">Выберите дисциплину</option>
                  <option value="Лыжные гонки">Лыжные гонки</option>
                  <option value="Бег">Бег</option>
                  <option value="Ориентирование">Ориентирование</option>
                  <option value="Велоспорт">Велоспорт</option>
                  <option value="Триатлон">Триатлон</option>
                  <option value="Плавание">Плавание</option>
                </select>
              </div>
              
              <div class="col-md-6">
                <label class="form-label">Макс. участников</label>
                <input 
                  type="number" 
                  class="form-control" 
                  v-model="form.max_participants"
                  min="1"
                  placeholder="Не ограничено"
                >
              </div>
            </div>
          </form>
        </div>
        
        <div class="modal-footer">
          <button 
            type="button" 
            class="btn btn-secondary" 
            @click="$emit('close')"
            :disabled="loading"
          >
            Отмена
          </button>
          <button 
            type="button" 
            class="btn btn-primary" 
            @click="handleSubmit"
            :disabled="loading"
          >
            <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
            {{ event ? 'Сохранить' : 'Создать' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'

export default {
  name: 'EventModal',
  
  props: {
    event: {
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
      title: '',
      description: '',
      date: '',
      location: '',
      discipline: '',
      status: 'upcoming',
      max_participants: null
    })
    
    // Инициализация формы
    onMounted(() => {
      if (props.event) {
        form.value = { ...props.event }
      } else {
        // Устанавливаем дату по умолчанию (завтра)
        const tomorrow = new Date()
        tomorrow.setDate(tomorrow.getDate() + 1)
        form.value.date = tomorrow.toISOString().split('T')[0]
      }
    })
    
    const handleSubmit = () => {
      // Валидация
      if (!form.value.title.trim()) {
        alert('Введите название мероприятия')
        return
      }
      if (!form.value.date) {
        alert('Укажите дату проведения')
        return
      }
      if (!form.value.location.trim()) {
        alert('Укажите место проведения')
        return
      }
      if (!form.value.discipline) {
        alert('Выберите дисциплину')
        return
      }
      
      emit('save', form.value)
    }
    
    return {
      form,
      handleSubmit
    }
  }
}
</script>

<style scoped>
.modal-content {
  border-radius: 12px;
}

.form-label {
  font-weight: 500;
  font-size: 0.875rem;
  margin-bottom: 0.5rem;
}
</style>