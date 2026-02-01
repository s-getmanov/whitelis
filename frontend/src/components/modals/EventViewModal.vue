<template>
  <!-- Модальное окно -->
  <div v-if="show" class="modal fade show d-block" style="background-color: rgba(0,0,0,0.5)">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <!-- Заголовок -->
        <div class="modal-header border-bottom-0 pb-0">
          <div class="d-flex justify-content-between align-items-center w-100">
            <div>
              <span :class="`badge bg-${getStatusColor(event.status)} me-2`">
                {{ getStatusText(event.status) }}
              </span>
              <span class="text-muted">
                <i class="bi bi-calendar-event me-1"></i>
                {{ formatDate(event.date) }}
              </span>
            </div>
            <button 
              type="button" 
              class="btn-close" 
              @click="$emit('close')"
              aria-label="Закрыть"
            ></button>
          </div>
        </div>
        
        <div class="modal-body pt-0">
          <!-- Заголовок события -->
          <h3 class="modal-title fw-bold mb-4">{{ event.title }}</h3>
          
          <!-- Основная информация -->
          <div class="row mb-4">
            <div class="col-md-8">
              <div class="card border-0 bg-light mb-4">
                <div class="card-body">
                  <h6 class="fw-bold text-muted mb-3">
                    <i class="bi bi-info-circle me-2"></i>Описание
                  </h6>
                  <p class="mb-0" style="white-space: pre-line;">
                    {{ event.description || 'Описание отсутствует' }}
                  </p>
                </div>
              </div>
              
              <!-- Дисциплины -->
              <div v-if="event.distances" class="mb-4">
                <h6 class="fw-bold text-muted mb-3">
                  <i class="bi bi-signpost me-2"></i>Дистанции
                </h6>
                <div class="d-flex flex-wrap gap-2">
                  <span 
                    v-for="distance in event.distances" 
                    :key="distance"
                    class="badge bg-white text-dark border px-3 py-2"
                  >
                    {{ distance }}
                  </span>
                </div>
              </div>
            </div>
            
            <!-- Боковая панель -->
            <div class="col-md-4">
              <div class="card border-0 shadow-sm">
                <div class="card-body">
                  <h6 class="fw-bold text-muted mb-3">Детали</h6>
                  
                  <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                      <i class="bi bi-geo-alt text-primary"></i>
                    </div>
                    <div>
                      <div class="fw-semibold small">Место</div>
                      <div>{{ event.location }}</div>
                    </div>
                  </div>
                  
                  <div class="d-flex align-items-center mb-3">
                    <div class="bg-success bg-opacity-10 rounded-circle p-2 me-3">
                      <i class="bi bi-flag text-success"></i>
                    </div>
                    <div>
                      <div class="fw-semibold small">Дисциплина</div>
                      <div>{{ event.discipline }}</div>
                    </div>
                  </div>
                  
                  <div class="d-flex align-items-center mb-4">
                    <div class="bg-warning bg-opacity-10 rounded-circle p-2 me-3">
                      <i class="bi bi-people text-warning"></i>
                    </div>
                    <div>
                      <div class="fw-semibold small">Участники</div>
                      <div>
                        <span class="fw-bold">{{ event.participants_count }}</span>
                        <span class="text-muted"> / {{ event.max_participants || '∞' }}</span>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Кнопка регистрации -->
                  <button 
                    v-if="event.status === 'upcoming' || event.status === 'active'"
                    class="btn btn-success w-100"
                    @click="handleRegistration"
                  >
                    <i class="bi bi-pencil-square me-2"></i>
                    Зарегистрироваться
                  </button>
                  
                  <div 
                    v-else
                    class="alert alert-secondary mb-0 text-center py-2"
                  >
                    <i class="bi bi-clock me-2"></i>
                    Регистрация закрыта
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Дополнительная информация -->
          <div class="border-top pt-3">
            <div class="row">
              <div class="col-md-6">
                <div class="d-flex align-items-center">
                  <div class="bg-info bg-opacity-10 rounded-circle p-2 me-3">
                    <i class="bi bi-file-text text-info"></i>
                  </div>
                  <div>
                    <div class="fw-semibold small">Протоколы</div>
                    <div class="text-muted small">Будут доступны после мероприятия</div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="d-flex align-items-center">
                  <div class="bg-danger bg-opacity-10 rounded-circle p-2 me-3">
                    <i class="bi bi-trophy text-danger"></i>
                  </div>
                  <div>
                    <div class="fw-semibold small">Результаты</div>
                    <div class="text-muted small" v-if="event.status === 'completed'">
                      Итоги опубликованы
                    </div>
                    <div class="text-muted small" v-else>
                      Будут после мероприятия
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Футер модального окна -->
        <div class="modal-footer border-top-0 pt-0">
          <button 
            type="button" 
            class="btn btn-outline-secondary"
            @click="$emit('close')"
          >
            Закрыть
          </button>
          <button 
            v-if="event.status === 'completed'"
            type="button" 
            class="btn btn-primary"
            @click="viewResults"
          >
            <i class="bi bi-trophy me-2"></i>
            Смотреть результаты
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'EventViewModal',
  
  props: {
    show: {
      type: Boolean,
      default: false
    },
    event: {
      type: Object,
      default: () => ({})
    }
  },
  
  emits: ['close', 'register', 'view-results'],
  
  methods: {
    formatDate(dateString) {
      if (!dateString) return ''
      const options = { day: 'numeric', month: 'long', year: 'numeric' }
      return new Date(dateString).toLocaleDateString('ru-RU', options)
    },
    
    getStatusColor(status) {
      const colors = {
        'draft': 'secondary',
        'upcoming': 'primary',
        'active': 'success',
        'completed': 'warning'
      }
      return colors[status] || 'secondary'
    },
    
    getStatusText(status) {
      const texts = {
        'draft': 'Черновик',
        'upcoming': 'Предстоящее',
        'active': 'Активное',
        'completed': 'Завершено'
      }
      return texts[status] || status
    },
    
    handleRegistration() {
      this.$emit('register', this.event)
      alert(`Регистрация на мероприятие "${this.event.title}"\nФункция будет доступна в следующей версии`)
    },
    
    viewResults() {
      this.$emit('view-results', this.event)
    }
  }
}
</script>

<style scoped>
.modal-content {
  border-radius: 12px;
  border: none;
}

.modal-header {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 12px 12px 0 0;
}

.modal-title {
  color: var(--primary-color);
  line-height: 1.3;
}

.badge {
  font-size: 0.85rem;
  padding: 0.4rem 0.8rem;
}

.btn-success {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  border: none;
  transition: all 0.2s ease;
}

.btn-success:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
}

.bg-opacity-10 {
  background-color: rgba(var(--bs-primary-rgb), 0.1);
}
</style>