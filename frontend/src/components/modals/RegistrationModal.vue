<template>
  <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5)">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            {{ registration ? 'Редактирование заявки' : 'Новая заявка' }}
          </h5>
          <button type="button" class="btn-close" @click="$emit('close')" :disabled="loading"></button>
        </div>
        
        <div class="modal-body">
          <form @submit.prevent="handleSubmit">
            <div class="row g-3">
              <!-- Мероприятие -->
              <div class="col-md-6">
                <label class="form-label">Мероприятие *</label>
                <select class="form-select" v-model="form.event_id" required :disabled="!!registration && !!form.event_id">
                  <option value="">Выберите мероприятие</option>
                  <option v-for="event in availableEvents" :key="event.id" :value="event.id">
                    {{ event.title }} ({{ formatDate(event.date) }})
                  </option>
                </select>
              </div>
              
              <!-- Участник (для админа - select, для участника - только чтение) -->
               <!-- [TODO] :disabled="!!registration" - замена пользователя в заявке - надо? Подумать. -->
              <div class="col-md-6">
                <label class="form-label">Участник *</label>
                <!-- <select 
                  v-if="isAdminMode" 
                  class="form-select" 
                  v-model="form.user_id" 
                  required
                  :disabled="!!registration"
                > -->
                <select 
                  v-if="isAdminMode" 
                  class="form-select" 
                  v-model="form.user_id" 
                  required                  
                >
                  <option value="">Выберите участника</option>
                  <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                   111 {{ user.id }} {{ user.name }} ({{ user.email }})
                  </option>
                </select>
                <input 
                  v-else
                  type="text" 
                  class="form-control" 
                  :value="currentUserName"
                  disabled
                  readonly
                >
                <input type="hidden" v-model="form.user_id">
              </div>
              
              <!-- Дисциплина -->
              <div class="col-md-6">
                <label class="form-label">Дисциплина</label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="form.discipline"
                  placeholder="Например: 10км"
                >
              </div>
              
              <!-- Категория -->
              <div class="col-md-6">
                <label class="form-label">Категория</label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="form.category"
                  placeholder="Например: Мужчины 30-40 лет"
                >
              </div>
              
              <!-- Команда (select для выбора команды) -->
              <div class="col-md-6">
                <label class="form-label">Команда</label>
                <select class="form-select" v-model="form.team_id">
                  <option :value="null">Без команды</option>
                  <option v-for="team in availableTeams" :key="team.id" :value="team.id">
                    {{ team.name }}
                  </option>
                </select>
              </div>
              
              <!-- Стартовый номер -->
              <div class="col-md-6">
                <label class="form-label">Стартовый номер</label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="form.bib_number"
                  placeholder="Например: 001"
                >
              </div>
              
              <!-- Статус -->
              <div class="col-md-6">
                <label class="form-label">Статус</label>
                <select class="form-select" v-model="form.status">
                  <option value="pending">Ожидает подтверждения</option>
                  <option value="approved">Подтверждена</option>
                  <option value="rejected">Отклонена</option>
                  <option value="cancelled">Отменена</option>
                  <option value="completed">Участвовал</option>
                </select>
              </div>
              
              <!-- Примечания -->
              <div class="col-12">
                <label class="form-label">Примечания</label>
                <textarea 
                  class="form-control" 
                  rows="2"
                  v-model="form.notes"
                  placeholder="Дополнительная информация"
                ></textarea>
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
            :disabled="loading || !formValid"
          >
            <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
            {{ registration ? 'Сохранить' : 'Создать' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import UserService from '@/services/user.service'
import TeamService from '@/services/team.service'

export default {
  name: 'RegistrationModal',
  
  props: {
    registration: {
      type: Object,
      default: null
    },
    availableEvents: {
      type: Array,
      default: () => []
    },
    loading: {
      type: Boolean,
      default: false
    },
    mode: {
      type: String,
      default: 'admin', // 'admin' или 'personal'
      validator: (value) => ['admin', 'personal'].includes(value)
    },
    currentUserId: {
      type: [Number, String],
      default: null
    },
    currentUserName: {
      type: String,
      default: ''
    }
  },
  
  emits: ['save', 'close'],
  
  setup(props, { emit }) {
    const form = ref({
      event_id: '',
      user_id: '',
      discipline: '',
      category: '',
      team_id: null,
      bib_number: '',
      status: 'pending',
      notes: ''
    })
    
    const availableUsers = ref([])
    const availableTeams = ref([])
    const usersLoading = ref(false)
    const teamsLoading = ref(false)
    
    // Режим админа
    const isAdminMode = computed(() => props.mode === 'admin')
    
    // Валидация формы
    const formValid = computed(() => {
      return form.value.event_id && form.value.user_id
    })
    
    // Загрузка пользователей (только для админа)
    const loadUsers = async () => {
      if (!isAdminMode.value) return
      
      usersLoading.value = true
      try {
        const response = await UserService.getAll({ limit: 100 })
        availableUsers.value = response.data || []
      } catch (err) {
        console.error('Ошибка загрузки пользователей:', err)
      } finally {
        usersLoading.value = false
      }
    }
    
    // Загрузка команд
    const loadTeams = async () => {
      teamsLoading.value = true
      try {
        const response = await TeamService.getAll({ limit: 100 })
        availableTeams.value = response.data || []
      } catch (err) {
        console.error('Ошибка загрузки команд:', err)
      } finally {
        teamsLoading.value = false
      }
    }
    
    onMounted(async () => {
      await Promise.all([loadUsers(), loadTeams()])
      
      if (props.registration) {
        // Заполняем форму данными существующей заявки
        form.value = {
          event_id: props.registration.event_id || '',
          user_id: props.registration.user_id || '',
          discipline: props.registration.discipline || '',
          category: props.registration.category || '',
          team_id: props.registration.team_id || null,
          bib_number: props.registration.bib_number || '',
          status: props.registration.status || 'pending',
          notes: props.registration.notes || ''
        }
      } else if (!isAdminMode.value && props.currentUserId) {
        // Для личного кабинета - текущий пользователь
        form.value.user_id = props.currentUserId
      }
    })
    
    const formatDate = (dateString) => {
      if (!dateString) return ''
      const options = { day: 'numeric', month: 'short' }
      return new Date(dateString).toLocaleDateString('ru-RU', options)
    }
    
    const handleSubmit = () => {
      if (!formValid.value) {
        alert('Заполните обязательные поля')
        return
      }
      
      emit('save', form.value)
    }
    
    return {
      form,
      availableUsers,
      availableTeams,
      usersLoading,
      teamsLoading,
      isAdminMode,
      formValid,
      formatDate,
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