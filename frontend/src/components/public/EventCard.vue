<template>
  <div class="event-card">
    <!-- Кнопка назад -->
    <div class="mb-4">
      <button class="btn btn-outline-secondary" @click="$router.back()">
        <i class="bi bi-arrow-left me-2"></i>Назад к списку
      </button>
    </div>
    
    <!-- Карточка события -->
    <div class="card border-0 shadow-lg">
      <!-- Заголовок и статус -->
      <div class="card-header bg-white border-bottom-0 pt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <span :class="`badge bg-${getStatusColor(event.status)} fs-6`">
            {{ getStatusText(event.status) }}
          </span>
          <div class="text-muted">
            <i class="bi bi-calendar-event me-2"></i>
            {{ formatDate(event.date) }}
          </div>
        </div>
        <h1 class="card-title fw-bold">{{ event.title }}</h1>
      </div>
      
      <div class="card-body">
        <!-- Основная информация -->
        <div class="row mb-5">
          <div class="col-md-8">
            <div class="mb-4">
              <h5 class="fw-bold mb-3">Описание мероприятия</h5>
              <p class="card-text" style="white-space: pre-line;">{{ event.description || 'Описание отсутствует' }}</p>
            </div>
            
            <!-- Дисциплины и категории -->
            <div v-if="event.distances || event.categories" class="row mb-4">
              <div v-if="event.distances" class="col-md-6 mb-3">
                <h6 class="fw-bold text-muted mb-3">
                  <i class="bi bi-signpost me-2"></i>Дистанции
                </h6>
                <div class="d-flex flex-wrap gap-2">
                  <span 
                    v-for="distance in event.distances" 
                    :key="distance"
                    class="badge bg-light text-dark border px-3 py-2"
                  >
                    {{ distance }}
                  </span>
                </div>
              </div>
              
              <div v-if="event.categories" class="col-md-6 mb-3">
                <h6 class="fw-bold text-muted mb-3">
                  <i class="bi bi-people me-2"></i>Категории участников
                </h6>
                <div class="d-flex flex-wrap gap-2">
                  <span 
                    v-for="category in event.categories" 
                    :key="category"
                    class="badge bg-light text-dark border px-3 py-2"
                  >
                    {{ category }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Боковая панель с деталями -->
          <div class="col-md-4">
            <div class="card border-0 bg-light">
              <div class="card-body">
                <h5 class="fw-bold mb-4">Детали мероприятия</h5>
                
                <div class="mb-4">
                  <h6 class="text-muted small mb-2">
                    <i class="bi bi-geo-alt me-2"></i>Место проведения
                  </h6>
                  <p class="mb-0 fw-semibold">{{ event.location }}</p>
                </div>
                
                <div class="mb-4">
                  <h6 class="text-muted small mb-2">
                    <i class="bi bi-flag me-2"></i>Дисциплина
                  </h6>
                  <p class="mb-0 fw-semibold">{{ event.discipline }}</p>
                </div>
                
                <div class="mb-4">
                  <h6 class="text-muted small mb-2">
                    <i class="bi bi-people me-2"></i>Участники
                  </h6>
                  <div class="d-flex justify-content-between">
                    <div>
                      <div class="fw-bold fs-5">{{ event.participants_count }}</div>
                      <small class="text-muted">зарегистрировано</small>
                    </div>
                    <div class="text-end">
                      <div class="fw-bold fs-5">{{ event.max_participants || '∞' }}</div>
                      <small class="text-muted">максимум</small>
                    </div>
                  </div>
                  <div class="progress mt-2" style="height: 8px;">
                    <div 
                      class="progress-bar bg-success" 
                      :style="{ width: getParticipationPercent() + '%' }"
                    ></div>
                  </div>
                </div>
                
                <!-- Кнопка регистрации -->
                <div class="mt-4">
                  <button 
                    v-if="event.status === 'upcoming' || event.status === 'active'"
                    class="btn btn-success w-100 py-3 fw-bold"
                    @click="handleRegistration"
                  >
                    <i class="bi bi-pencil-square me-2"></i>
                    Зарегистрироваться
                  </button>
                  <div 
                    v-else-if="event.status === 'completed'"
                    class="alert alert-warning mb-0 text-center"
                  >
                    <i class="bi bi-clock-history me-2"></i>
                    Мероприятие завершено
                  </div>
                  <div 
                    v-else
                    class="alert alert-secondary mb-0 text-center"
                  >
                    <i class="bi bi-pencil me-2"></i>
                    В разработке
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Дополнительная информация -->
        <div class="border-top pt-4">
          <h5 class="fw-bold mb-4">Дополнительная информация</h5>
          <div class="row">
            <div class="col-md-6">
              <div class="d-flex align-items-center mb-3">
                <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                  <i class="bi bi-file-text text-primary fs-4"></i>
                </div>
                <div>
                  <h6 class="fw-bold mb-1">Доступные протоколы</h6>
                  <small class="text-muted">Стартовые и итоговые протоколы</small>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="d-flex align-items-center mb-3">
                <div class="bg-success bg-opacity-10 rounded-circle p-3 me-3">
                  <i class="bi bi-trophy text-success fs-4"></i>
                </div>
                <div>
                  <h6 class="fw-bold mb-1">Результаты</h6>
                  <small class="text-muted" v-if="event.status === 'completed'">
                    Итоговые результаты доступны
                  </small>
                  <small class="text-muted" v-else>
                    Будут доступны после мероприятия
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import EventService from '@/services/event.service'

export default {
  name: 'EventCard',
  
  setup() {
    const route = useRoute()
    const router = useRouter()
    const event = ref({})
    const loading = ref(true)
    const error = ref(null)
    
    // Загрузка данных события
    const loadEvent = async () => {
      try {
        loading.value = true
        const eventId = route.params.id || route.query.id
        if (!eventId) {
          throw new Error('ID мероприятия не указан')
        }
        
        const data = await EventService.getById(eventId)
        event.value = data
      } catch (err) {
        error.value = err.message || 'Ошибка загрузки мероприятия'
        console.error('Ошибка:', err)
      } finally {
        loading.value = false
      }
    }
    
    // Форматирование даты
    const formatDate = (dateString) => {
      if (!dateString) return ''
      const options = { day: 'numeric', month: 'long', year: 'numeric' }
      return new Date(dateString).toLocaleDateString('ru-RU', options)
    }
    
    // Получение цвета статуса
    const getStatusColor = (status) => {
      const colors = {
        'draft': 'secondary',
        'upcoming': 'primary',
        'active': 'success',
        'completed': 'warning'
      }
      return colors[status] || 'secondary'
    }
    
    // Получение текста статуса
    const getStatusText = (status) => {
      const texts = {
        'draft': 'Черновик',
        'upcoming': 'Предстоящее',
        'active': 'Активное',
        'completed': 'Завершено'
      }
      return texts[status] || status
    }
    
    // Процент заполненности
    const getParticipationPercent = () => {
      if (!event.value.max_participants || event.value.max_participants <= 0) return 0
      const percent = (event.value.participants_count / event.value.max_participants) * 100
      return Math.min(Math.round(percent), 100)
    }
    
    // Обработка регистрации
    const handleRegistration = () => {
      // В будущем - переход к форме регистрации
      alert('Функция регистрации будет доступна в следующей версии')
    }
    
    // Инициализация
    onMounted(() => {
      loadEvent()
    })
    
    return {
      event,
      loading,
      error,
      formatDate,
      getStatusColor,
      getStatusText,
      getParticipationPercent,
      handleRegistration
    }
  }
}
</script>

<style scoped>
.event-card {
  max-width: 1200px;
  margin: 0 auto;
}

.card {
  border-radius: 16px;
  overflow: hidden;
}

.badge {
  font-size: 0.9rem;
  padding: 0.5rem 1rem;
}

.progress {
  border-radius: 4px;
}

.btn-success {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  border: none;
  transition: all 0.3s ease;
}

.btn-success:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
}

.bg-opacity-10 {
  opacity: 0.1;
}

@media (max-width: 768px) {
  .card-title {
    font-size: 1.5rem;
  }
  
  .btn {
    padding: 0.75rem 1.5rem;
  }
}
</style>