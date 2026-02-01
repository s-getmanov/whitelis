<template>
  <div class="protocols-page">
    <div class="container-fluid">
      <div class="row mb-4">
        <div class="col">
          <h2 class="h4 mb-0">Протоколы мероприятий</h2>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
              <li class="breadcrumb-item">
                <router-link to="/admin">Главная</router-link>
              </li>
              <li class="breadcrumb-item active">Протоколы</li>
            </ol>
          </nav>
        </div>
        <div class="col-auto">
          <div class="btn-group">
            <button class="btn btn-outline-secondary" @click="refreshEvents">
              <i class="bi bi-arrow-clockwise"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Выбор мероприятия -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
          <h5 class="mb-0">Выберите мероприятие</h5>
        </div>
        <div class="card-body">
          <div v-if="eventsLoading" class="text-center py-3">
            <div class="spinner-border spinner-border-sm text-primary"></div>
            <span class="ms-2">Загрузка мероприятий...</span>
          </div>
          
          <div v-else-if="eventsError" class="alert alert-danger">
            {{ eventsError }}
          </div>
          
          <div v-else>
            <div class="row">
              <div class="col-md-6">
                <div class="input-group mb-3">
                  <span class="input-group-text">
                    <i class="bi bi-search"></i>
                  </span>
                  <input v-model="eventSearch" type="text" class="form-control" placeholder="Поиск мероприятия...">
                </div>
              </div>
              <div class="col-md-6">
                <select v-model="eventStatusFilter" class="form-select">
                  <option value="">Все статусы</option>
                  <option value="upcoming">Предстоящие</option>
                  <option value="active">Активные</option>
                  <option value="completed">Завершенные</option>
                </select>
              </div>
            </div>
            
            <!-- Список мероприятий -->
            <div class="list-group">
              <button 
                v-for="event in filteredEvents"
                :key="event.id"
                :class="['list-group-item', 'list-group-item-action', selectedEventId === event.id ? 'active' : '']"
                @click="selectEvent(event)"
              >
                <div class="d-flex w-100 justify-content-between align-items-center">
                  <div>
                    <h6 class="mb-1">{{ event.title }}</h6>
                    <small>
                      <i class="bi bi-calendar me-1"></i>
                      {{ formatDate(event.date) }} •
                      <i class="bi bi-geo-alt ms-2 me-1"></i>
                      {{ event.location }}
                    </small>
                  </div>
                  <div class="text-end">
                    <span :class="['badge', getStatusBadgeClass(event.status)]">
                      {{ getStatusText(event.status) }}
                    </span>
                    <div class="mt-1">
                      <small class="text-muted">{{ event.participants_count }} участников</small>
                    </div>
                  </div>
                </div>
              </button>
              
              <div v-if="filteredEvents.length === 0" class="text-center py-4">
                <i class="bi bi-calendar-x fs-3 text-muted mb-2"></i>
                <p class="text-muted">Мероприятия не найдены</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Протокол выбранного мероприятия -->
      <div v-if="selectedEventId" class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
              Протокол: {{ selectedEvent?.title }}
            </h5>
            <button class="btn btn-sm btn-outline-danger" @click="clearSelectedEvent">
              <i class="bi bi-x-lg"></i> Сменить мероприятие
            </button>
          </div>
        </div>
        <div class="card-body p-0">
          <ProtocolList 
            :event-id="selectedEventId"
            mode="admin"
            @export="onExport"
            @numbers-assigned="onNumbersAssigned"
          />
        </div>
      </div>

      <!-- Информация при отсутствии выбранного мероприятия -->
      <div v-else class="text-center py-5">
        <i class="bi bi-file-text fs-1 text-muted mb-3"></i>
        <h5>Выберите мероприятие</h5>
        <p class="text-muted">
          Для просмотра и формирования протоколов выберите мероприятие из списка выше
        </p>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import EventService from '@/services/event.service'
import ProtocolList from '@/components/ProtocolList.vue'

export default {
  name: 'Protocols',
  
  components: {
    ProtocolList
  },
  
  setup() {
    // Список мероприятий
    const events = ref([])
    const eventsLoading = ref(false)
    const eventsError = ref(null)
    
    // Фильтры мероприятий
    const eventSearch = ref('')
    const eventStatusFilter = ref('')
    
    // Выбранное мероприятие
    const selectedEventId = ref(null)
    const selectedEvent = ref(null)
    
    // Вычисляемые свойства
    const filteredEvents = computed(() => {
      let filtered = events.value
      
      // Поиск
      if (eventSearch.value) {
        const searchLower = eventSearch.value.toLowerCase()
        filtered = filtered.filter(event => 
          event.title.toLowerCase().includes(searchLower) ||
          event.location.toLowerCase().includes(searchLower)
        )
      }
      
      // Фильтр по статусу
      if (eventStatusFilter.value) {
        filtered = filtered.filter(event => event.status === eventStatusFilter.value)
      }
      
      // Сортировка: предстоящие -> активные -> завершенные
      const statusOrder = { 'upcoming': 1, 'active': 2, 'completed': 3, 'draft': 4 }
      filtered.sort((a, b) => {
        const orderA = statusOrder[a.status] || 5
        const orderB = statusOrder[b.status] || 5
        if (orderA !== orderB) return orderA - orderB
        
        // Если статус одинаковый, по дате
        return new Date(a.date) - new Date(b.date)
      })
      
      return filtered
    })
    
    // Методы
    const loadEvents = async () => {
      eventsLoading.value = true
      eventsError.value = null
      
      try {
        const response = await EventService.getAll({ limit: 100 })
        events.value = response.data || []
      } catch (err) {
        console.error('Ошибка загрузки мероприятий:', err)
        eventsError.value = err.response?.data?.message || err.message || 'Ошибка загрузки мероприятий'
        events.value = []
      } finally {
        eventsLoading.value = false
      }
    }
    
    const refreshEvents = () => {
      loadEvents()
    }
    
    const selectEvent = (event) => {
      selectedEventId.value = event.id
      selectedEvent.value = event
    }
    
    const clearSelectedEvent = () => {
      selectedEventId.value = null
      selectedEvent.value = null
    }
    
    const formatDate = (dateString) => {
      if (!dateString) return ''
      try {
        const date = new Date(dateString)
        return date.toLocaleDateString('ru-RU', {
          day: 'numeric',
          month: 'short'
        })
      } catch (e) {
        return dateString
      }
    }
    
    const getStatusText = (status) => {
      const texts = {
        'draft': 'Черновик',
        'upcoming': 'Предстоящее',
        'active': 'Активное',
        'completed': 'Завершено'
      }
      return texts[status] || status
    }
    
    const getStatusBadgeClass = (status) => {
      const classes = {
        'draft': 'bg-secondary',
        'upcoming': 'bg-primary',
        'active': 'bg-success',
        'completed': 'bg-warning'
      }
      return classes[status] || 'bg-secondary'
    }
    
    const onExport = (data) => {
      console.log('Экспорт протокола:', data)
      // Здесь можно открыть модалку с информацией об экспорте
    }
    
    const onNumbersAssigned = (data) => {
      console.log('Номера назначены:', data)
      // Обновить статистику мероприятия
      if (selectedEvent.value) {
        // В будущем можно обновить данные мероприятия
      }
    }
    
    // Хуки жизненного цикла
    onMounted(() => {
      loadEvents()
    })
    
    return {
      // Данные
      events,
      eventsLoading,
      eventsError,
      eventSearch,
      eventStatusFilter,
      selectedEventId,
      selectedEvent,
      
      // Вычисляемые свойства
      filteredEvents,
      
      // Методы
      loadEvents,
      refreshEvents,
      selectEvent,
      clearSelectedEvent,
      formatDate,
      getStatusText,
      getStatusBadgeClass,
      onExport,
      onNumbersAssigned
    }
  }
}
</script>

<style scoped>
.protocols-page {
  padding: 1rem;
}

.list-group-item.active {
  background-color: #0d6efd;
  border-color: #0d6efd;
}

.list-group-item {
  border-left: none;
  border-right: none;
  border-radius: 0;
}

.list-group-item:first-child {
  border-top: none;
}

.list-group-item:last-child {
  border-bottom: none;
}

@media (max-width: 768px) {
  .protocols-page {
    padding: 0.5rem;
  }
}
</style>