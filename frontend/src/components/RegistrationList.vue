<template>
  <div class="registration-list">
    <!-- Состояние загрузки -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Загрузка...</span>
      </div>
      <p class="mt-3 text-muted small">Загрузка заявок...</p>
    </div>

    <!-- Ошибка -->
    <div v-else-if="error" class="alert alert-danger">
      <i class="bi bi-exclamation-triangle me-2"></i>
      {{ error }}
      <button class="btn btn-sm btn-outline-danger ms-3" @click="loadRegistrations">
        Повторить
      </button>
    </div>

    <!-- Контент -->
    <div v-else>
      <div class="card border-0 shadow-sm">
        <!-- Панель управления -->
        <div class="card-header bg-white border-bottom py-3">
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex gap-2">
              <!-- Кнопка создания заявки для админа -->
              <button v-if="mode === 'admin'" class="btn btn-primary btn-sm" @click="openCreateModal" title="Создать заявку">
                <i class="bi bi-plus-circle me-1"></i>Создать заявку
              </button>

              <button v-if="mode === 'admin'" class="btn btn-outline-success btn-sm" @click="bulkApprove"
                :disabled="selectedRegistrations.length === 0" title="Подтвердить выбранные">
                <i class="bi bi-check-circle me-1"></i>Подтвердить
              </button>

              <button v-if="mode === 'admin'" class="btn btn-outline-danger btn-sm" @click="bulkReject"
                :disabled="selectedRegistrations.length === 0" title="Отклонить выбранные">
                <i class="bi bi-x-circle me-1"></i>Отклонить
              </button>

              <span v-if="selectedRegistrations.length > 0" class="badge bg-primary align-self-center ms-2">
                Выбрано: {{ selectedRegistrations.length }}
              </span>
            </div>

            <!-- Фильтры -->
            <div class="d-flex gap-2 align-items-center">
              <div class="input-group input-group-sm" style="width: 200px;">
                <span class="input-group-text">
                  <i class="bi bi-search"></i>
                </span>
                <input v-model="filters.search" type="text" class="form-control" placeholder="Поиск..."
                  @input="debouncedSearch">
              </div>

              <select v-model="filters.status" class="form-select form-select-sm" style="width: 150px;"
                @change="loadRegistrations">
                <option value="">Все статусы</option>
                <option value="pending">Ожидает</option>
                <option value="approved">Подтверждена</option>
                <option value="rejected">Отклонена</option>
                <option value="completed">Участвовал</option>
                <option value="cancelled">Отменена</option>
              </select>

              <!-- Фильтр по мероприятию (только для админов) -->
              <select v-if="mode === 'admin' && !eventId" v-model="filters.event_id" class="form-select form-select-sm" style="width: 200px;"
                @change="loadRegistrations">
                <option value="">Все мероприятия</option>
                <option v-for="event in availableEvents" :key="event.id" :value="event.id">
                  {{ event.title }}
                </option>
              </select>

              <!-- Фильтр по команде (только для админов) -->
              <select v-if="mode === 'admin'" v-model="filters.team_id" class="form-select form-select-sm" style="width: 180px;"
                @change="loadRegistrations">
                <option value="">Все команды</option>
                <option v-for="team in availableTeams" :key="team.id" :value="team.id">
                  {{ team.name }}
                </option>
              </select>

              <button class="btn btn-sm btn-outline-secondary" @click="loadRegistrations" :disabled="loading"
                title="Обновить">
                <i class="bi bi-arrow-clockwise"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Таблица -->
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th v-if="mode === 'admin'" style="width: 50px;">
                  <input type="checkbox" class="form-check-input" v-model="selectAll" 
                    :disabled="registrations.length === 0">
                </th>
                <th>Участник</th>
                <th>Мероприятие</th>
                <th style="width: 120px;">Категория</th>
                <th style="width: 120px;">Команда</th>
                <th style="width: 100px;">Стартовый №</th>
                <th style="width: 120px;">Статус</th>
                <th style="width: 150px;">Дата подачи</th>
                <th v-if="showActions" style="width: 100px;" class="text-end">Действия</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="registration in registrations" :key="registration.id" 
                class="cursor-pointer"
                :class="{ 'table-primary': selectedRegistrations.includes(registration.id) }">
                <td v-if="mode === 'admin'" @click.stop>
                  <input type="checkbox" class="form-check-input" :value="registration.id" 
                    v-model="selectedRegistrations">
                </td>
                <td @click="toggleRegistrationSelection(registration)">
                  <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                      <i class="bi bi-person-circle text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                      <div class="fw-semibold">{{ registration.user_name }}</div>
                      <small class="text-muted">{{ registration.user_email }}</small>
                    </div>
                  </div>
                </td>
                <td @click="toggleRegistrationSelection(registration)">
                  <div>
                    <div class="fw-semibold small">{{ registration.event_title }}</div>
                    <small class="text-muted">{{ formatDate(registration.event_date) }}</small>
                  </div>
                </td>
                <td @click="toggleRegistrationSelection(registration)">
                  <small>{{ registration.category || '-' }}</small>
                </td>
                <td @click="toggleRegistrationSelection(registration)">
                  <small>{{ registration.team_name || '-' }}</small>
                </td>
                <td @click="toggleRegistrationSelection(registration)">
                  <span v-if="registration.bib_number" class="badge bg-dark">
                    {{ registration.bib_number }}
                  </span>
                  <small v-else class="text-muted">-</small>
                </td>
                <td @click="toggleRegistrationSelection(registration)">
                  <span :class="`badge bg-${registration.status_color}`">
                    {{ registration.status_text }}
                  </span>
                </td>
                <td @click="toggleRegistrationSelection(registration)">
                  <small>{{ formatDateShort(registration.created_at) }}</small>
                </td>
                <td v-if="showActions" class="text-end">
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" @click.stop="editRegistration(registration)" 
                      title="Редактировать">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-outline-danger" @click.stop="deleteRegistration(registration)" 
                      title="Удалить">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Пагинация -->
        <div v-if="meta && meta.pages > 1" class="card-footer bg-white border-top py-3">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted small">
              Показано {{ registrations.length }} из {{ meta.total }} заявок
              <span v-if="selectedRegistrations.length > 0" class="ms-3">
                • Выбрано: {{ selectedRegistrations.length }}
              </span>
            </div>
            <nav>
              <ul class="pagination pagination-sm mb-0">
                <li class="page-item" :class="{ disabled: meta.page === 1 }">
                  <button class="page-link" @click="changePage(meta.page - 1)" 
                    :disabled="loading || meta.page === 1">
                    <i class="bi bi-chevron-left"></i>
                  </button>
                </li>

                <li v-for="page in getPages()" :key="page" class="page-item"
                  :class="{ active: page === meta.page, disabled: page === '...' }">
                  <button v-if="page === '...'" class="page-link" disabled>
                    {{ page }}
                  </button>
                  <button v-else class="page-link" @click="changePage(page)" :disabled="loading">
                    {{ page }}
                  </button>
                </li>

                <li class="page-item" :class="{ disabled: meta.page === meta.pages }">
                  <button class="page-link" @click="changePage(meta.page + 1)"
                    :disabled="loading || meta.page === meta.pages">
                    <i class="bi bi-chevron-right"></i>
                  </button>
                </li>
              </ul>
            </nav>
          </div>
        </div>

        <!-- КОгда нет заявок, надо что-то написать -->
        <div v-if="registrations.length === 0 && !loading" class="text-center py-5">
          <i class="bi bi-clipboard-x fs-1 text-muted mb-3"></i>
          <h5>Заявки не найдены</h5>
          <p class="text-muted">
            {{ filters.search ? 'Попробуйте изменить условия поиска' : 'Нет поданных заявок' }}
          </p>
          <button v-if="mode === 'admin'" class="btn btn-primary btn-sm mt-2" @click="openCreateModal">
            <i class="bi bi-plus-circle me-1"></i>Создать первую заявку
          </button>
        </div>
      </div>
    </div>
    
    <RegistrationModal
      v-if="showModal"
      :registration="editingRegistration"
      :available-events="availableEvents"
      :mode="mode"
      :current-user-id="currentUserId"
      :current-user-name="currentUserName"
      :loading="modalLoading"
      @save="saveRegistration"
      @close="closeModal"
    />
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import { debounce } from 'lodash-es'
import RegistrationService from '@/services/registration.service'
import EventService from '@/services/event.service'
import UserService from '@/services/user.service' //Не удалять! 
import TeamService from '@/services/team.service'
import RegistrationModal from '@/components/modals/RegistrationModal.vue'

export default {
  name: 'RegistrationList',
  
  components: {
    RegistrationModal
  },
  
  props: {
    mode: {
      type: String,
      default: 'admin',
      validator: (value) => ['admin', 'personal'].includes(value)
    },
    eventId: {
      type: [Number, String],
      default: null
    },
    userId: {
      type: [Number, String],
      default: null
    },
    showActions: {
      type: Boolean,
      default: true
    }
  },
  
  emits: ['registration-edit', 'registration-delete', 'registration-create'],
  
  setup(props, { emit }) {
    // Состояние
    const registrations = ref([])
    const meta = ref(null)
    const loading = ref(false)
    const error = ref(null)
    const availableEvents = ref([])
    const availableTeams = ref([])
    
    // Выбранные заявки
    const selectedRegistrations = ref([])
    const selectAll = computed({
      get: () => registrations.value.length > 0 && selectedRegistrations.value.length === registrations.value.length,
      set: (value) => {
        if (value) {
          selectedRegistrations.value = registrations.value.map(reg => reg.id)
        } else {
          selectedRegistrations.value = []
        }
      }
    })
    
    // Фильтры
    const filters = ref({
      search: '',
      status: '',
      event_id: props.eventId || '',
      team_id: '',
      page: 1,
      limit: 10
    })
    
    // Модальное окно
    const showModal = ref(false)
    const editingRegistration = ref(null)
    const modalLoading = ref(false)
    
    // Текущий пользователь
    const currentUserId = computed(() => {
      const user = JSON.parse(localStorage.getItem('auth_user') || '{}')
      return user.id || null
    })
    
    const currentUserName = computed(() => {
      const user = JSON.parse(localStorage.getItem('auth_user') || '{}')
      return user.name || ''
    })
    
    // Методы
    const loadRegistrations = async () => {
      loading.value = true
      error.value = null
      selectedRegistrations.value = []
      
      try {
        const params = { ...filters.value }
        
        // Если передан eventId, используем его
        if (props.eventId) {
          params.event_id = props.eventId
        }
        
        // Если передан userId (для личного кабинета)
        if (props.userId) {
          params.user_id = props.userId
        } else if (props.mode === 'personal' && currentUserId.value) {
          params.user_id = currentUserId.value
        }
        
        const response = await RegistrationService.getAll(params)
        registrations.value = response.data || []
        meta.value = response.meta || { total: 0, page: 1, pages: 1 }
      } catch (err) {
        console.error('Ошибка загрузки заявок:', err)
        error.value = err.response?.data?.message || err.message || 'Ошибка загрузки заявок'
        registrations.value = []
        meta.value = { total: 0, page: 1, pages: 1 }
      } finally {
        loading.value = false
      }
    }
    
    const loadEvents = async () => {
      try {
        const response = await EventService.getAll({ limit: 100 })
        availableEvents.value = response.data || []
      } catch (err) {
        console.error('Ошибка загрузки мероприятий:', err)
      }
    }
    
    const loadTeams = async () => {
      try {
        const response = await TeamService.getAll({ limit: 100 })
        availableTeams.value = response.data || []
      } catch (err) {
        console.error('Ошибка загрузки команд:', err)
      }
    }
    
    const changePage = (page) => {
      if (page >= 1 && page <= meta.value.pages) {
        filters.value.page = page
        loadRegistrations()
      }
    }
    
    const getPages = () => {
      if (!meta.value) return []
      
      const pages = []
      const current = meta.value.page
      const total = meta.value.pages
      
      pages.push(1)
      
      const start = Math.max(2, current - 1)
      const end = Math.min(total - 1, current + 1)
      
      if (start > 2) pages.push('...')
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      
      if (end < total - 1) pages.push('...')
      
      if (total > 1) pages.push(total)
      
      return pages
    }
    
    const debouncedSearch = debounce(() => {
      filters.value.page = 1
      loadRegistrations()
    }, 500)
    
    const formatDate = (dateString) => {
      if (!dateString) return ''
      try {
        const date = new Date(dateString)
        const options = { day: 'numeric', month: 'short' }
        return date.toLocaleDateString('ru-RU', options)
      } catch (e) {
        return dateString
      }
    }
    
    const formatDateShort = (dateString) => {
      if (!dateString) return ''
      try {
        const date = new Date(dateString)
        return date.toLocaleDateString('ru-RU')
      } catch (e) {
        return dateString
      }
    }
    
    // Работа с выбранными элементами
    const toggleRegistrationSelection = (registration) => {
      if (props.mode !== 'admin') return
      
      const index = selectedRegistrations.value.indexOf(registration.id)
      if (index === -1) {
        selectedRegistrations.value.push(registration.id)
      } else {
        selectedRegistrations.value.splice(index, 1)
      }
    }
    
    const bulkApprove = async () => {
      if (selectedRegistrations.value.length === 0) return
      
      if (confirm(`Подтвердить выбранные заявки (${selectedRegistrations.value.length})?`)) {
        try {
          await RegistrationService.bulkUpdate({
            ids: selectedRegistrations.value,
            status: 'approved'
          })
          
          selectedRegistrations.value = []
          loadRegistrations()
        } catch (err) {
          alert('Ошибка при подтверждении заявок: ' + (err.response?.data?.message || err.message))
        }
      }
    }
    
    const bulkReject = async () => {
      if (selectedRegistrations.value.length === 0) return
      
      if (confirm(`Отклонить выбранные заявки (${selectedRegistrations.value.length})?`)) {
        try {
          await RegistrationService.bulkUpdate({
            ids: selectedRegistrations.value,
            status: 'rejected'
          })
          
          selectedRegistrations.value = []
          loadRegistrations()
        } catch (err) {
          alert('Ошибка при отклонении заявок: ' + (err.response?.data?.message || err.message))
        }
      }
    }
    
    const editRegistration = (registration) => {
      editingRegistration.value = registration
      showModal.value = true
      // emit('registration-edit', registration)
    }
    
    const deleteRegistration = async (registration) => {
      const action = props.mode === 'admin' ? 'удалить' : 'отменить'
      if (confirm(`${action} заявку участника "${registration.user_name}"?`)) {
        try {
          await RegistrationService.delete(registration.id)
          emit('registration-delete', registration)
          loadRegistrations()
        } catch (err) {
          alert('Ошибка: ' + (err.response?.data?.message || err.message))
        }
      }
    }
    
    const openCreateModal = () => {
      editingRegistration.value = null
      showModal.value = true
      emit('registration-create')
    }
    
    const closeModal = () => {
      showModal.value = false
      editingRegistration.value = null
      modalLoading.value = false
    }
    
    const saveRegistration = async (registrationData) => {
      modalLoading.value = true
      
      try {
        if (editingRegistration.value) {
          await RegistrationService.update(editingRegistration.value.id, registrationData)
        } else {
          await RegistrationService.create(registrationData)
        }
        
        closeModal()
        loadRegistrations()
      } catch (err) {
        console.error('Ошибка сохранения:', err)
        error.value = err.response?.data?.message || err.message || 'Ошибка сохранения заявки'
      } finally {
        modalLoading.value = false
      }
    }
    
    // Хуки жизненного цикла
    onMounted(() => {
      loadRegistrations()
      if (props.mode === 'admin') {
        loadEvents()
        loadTeams()
      }
    })
    
    // Наблюдатель для eventId
    watch(() => props.eventId, (newEventId) => {
      if (newEventId) {
        filters.value.event_id = newEventId
        loadRegistrations()
      }
    }, { immediate: true })
    
    return {
      registrations,
      meta,
      loading,
      error,
      availableEvents,
      availableTeams,
      selectedRegistrations,
      selectAll,
      filters,
      showModal,
      editingRegistration,
      modalLoading,
      currentUserId,
      currentUserName,
      
      loadRegistrations,
      loadEvents,
      loadTeams,
      changePage,
      getPages,
      debouncedSearch,
      formatDate,
      formatDateShort,
      toggleRegistrationSelection,
      bulkApprove,
      bulkReject,
      editRegistration,
      deleteRegistration,
      openCreateModal,
      closeModal,
      saveRegistration
    }
  }
}
</script>

<style scoped>
.registration-list {
  min-height: 400px;
}

.cursor-pointer {
  cursor: pointer;
}

.cursor-pointer:hover {
  background-color: rgba(0, 0, 0, 0.02);
}

.table th {
  font-weight: 600;
  color: #6c757d;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.table td {
  vertical-align: middle;
}

@media (max-width: 768px) {
  .card-header .d-flex {
    flex-direction: column;
    gap: 10px;
    align-items: stretch !important;
  }
  
  .card-header .d-flex > div {
    width: 100%;
  }
  
  .table-responsive {
    font-size: 0.875rem;
  }
}
</style>