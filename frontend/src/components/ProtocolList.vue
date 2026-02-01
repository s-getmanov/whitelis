<template>
  <div class="protocol-list">
    <!-- Загрузка -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Загрузка...</span>
      </div>
      <p class="mt-3 text-muted small">Формирование протокола...</p>
    </div>

    <!-- Ошибка -->
    <div v-else-if="error" class="alert alert-danger">
      <i class="bi bi-exclamation-triangle me-2"></i>
      {{ error }}
      <button class="btn btn-sm btn-outline-danger ms-3" @click="loadProtocol">
        Повторить
      </button>
    </div>

    <!-- Контент -->
    <div v-else-if="protocolData" class="protocol-content">
      <!-- Шапка протокола -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h5 class="mb-1">{{ protocolData.event.title }}</h5>
              <div class="text-muted small">
                <i class="bi bi-calendar me-1"></i>
                {{ formatDate(protocolData.event.date) }} •
                <i class="bi bi-geo-alt ms-2 me-1"></i>
                {{ protocolData.event.location }}
              </div>
            </div>
            
            <div class="d-flex gap-2">
              <!-- Выбор типа протокола -->
              <div class="btn-group btn-group-sm">
                <button 
                  :class="['btn', protocolType === 'start' ? 'btn-primary' : 'btn-outline-primary']"
                  @click="changeProtocolType('start')"
                >
                  Стартовый
                </button>
                <button 
                  :class="['btn', protocolType === 'final' ? 'btn-primary' : 'btn-outline-primary']"
                  @click="changeProtocolType('final')"
                >
                  Итоговый
                </button>
              </div>
              
              <!-- Кнопки управления -->
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary" @click="loadProtocol" title="Обновить">
                  <i class="bi bi-arrow-clockwise"></i>
                </button>
                <button class="btn btn-outline-success" @click="exportPDF" :disabled="exporting">
                  <i class="bi bi-file-pdf me-1"></i>
                  PDF
                </button>
                
                <!-- Дополнительные действия для админа -->
                <button v-if="isAdmin && protocolType === 'start'" class="btn btn-outline-warning" 
                  @click="showAssignNumbersModal = true" title="Назначить номера">
                  <i class="bi bi-123 me-1"></i>
                  Номера
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Статистика -->
        <div class="card-body py-2">
          <div class="row text-center">
            <div class="col">
              <div class="fw-bold text-primary">{{ protocolData.total_participants || protocolData.statistics?.total_participants || 0 }}</div>
              <small class="text-muted">Всего участников</small>
            </div>
            <div v-if="protocolType === 'final'" class="col">
              <div class="fw-bold text-success">{{ protocolData.statistics?.finished_participants || 0 }}</div>
              <small class="text-muted">Финишировало</small>
            </div>
            <div v-if="protocolType === 'final'" class="col">
              <div class="fw-bold text-warning">{{ protocolData.statistics?.dns_participants || 0 }}</div>
              <small class="text-muted">Не стартовало</small>
            </div>
            <div class="col">
              <div class="fw-bold text-info">{{ protocolData.distances?.length || 0 }}</div>
              <small class="text-muted">Дистанций</small>
            </div>
            <div class="col">
              <small class="text-muted">
                Сформирован: {{ formatDateTime(protocolData.generated_at) }}
              </small>
            </div>
          </div>
        </div>
      </div>

      <!-- Протоколы по дистанциям -->
      <div v-for="distance in protocolData.distances" :key="distance.distance_id" class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light border-bottom py-2">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-primary me-2 fs-5">
              <i class="bi bi-flag text-primary me-2 fs-5"> - </i>
              {{ distance.distance_name }}
              <!-- <small class="text-primary mb-0 fs-5">({{ distance.full_name }})</small> -->
            </h6>
            <span class="badge bg-primary">
              {{ distance.count }} участников
              <span v-if="protocolType === 'final'">
                / {{ distance.finished_count }} финишировало
              </span>
            </span>
          </div>
        </div>
        
        <!-- Категории внутри дистанции -->
        <div v-for="category in distance.categories" :key="category.category_id" class="category-section">
          <div class="category-header bg-white border-bottom py-2 px-3">
            <div class="d-flex justify-content-between align-items-center">
              

              <h6 class="mb-0 text-danger" >{{ category.category_name }}</h6>
              <small class="text-muted">
                {{ category.count }} чел.
                <span v-if="protocolType === 'final'">
                  ({{ category.finished_count }} финиш)
                </span>
              </small>
            </div>

            <!-- [TODO] Описание категории. Пока не нужно, вернуть позже -->
            <!-- <div v-if="category.description" class="small text-muted mt-1">
              {{ category.description }}
            </div> -->


          </div>
          
          <!-- Таблица участников -->
          <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th v-if="protocolType === 'final'" style="width: 60px;" class="text-center">Место</th>
                  <th style="width: 80px;" class="text-center">№</th>
                  <th>Участник</th>
                  <th v-if="protocolType === 'start'" style="width: 120px;">Год рождения</th>
                  <th v-if="protocolType === 'start'" style="width: 150px;">Команда</th>
                  <th v-if="protocolType === 'final'" style="width: 100px;" class="text-center">Результат</th>
                  <th v-if="protocolType === 'final'" style="width: 120px;" class="text-center">Время финиша</th>
                  <th v-if="protocolType === 'final'" style="width: 100px;" class="text-center">Статус</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="participant in category.participants" :key="participant.registration_id">
                  <!-- Место (только для итогового) -->
                  <td v-if="protocolType === 'final'" class="text-center">
                    <span v-if="participant.place" class="badge bg-primary rounded-pill">
                      {{ participant.place }}
                    </span>
                    <span v-else-if="participant.dns" class="badge bg-secondary rounded-pill" title="Did Not Start">
                      DNS
                    </span>
                    <span v-else class="text-muted">-</span>
                  </td>
                  
                  <!-- Стартовый номер -->
                  <td class="text-center">
                    <span v-if="participant.bib_number" class="badge bg-dark rounded-pill">
                      {{ participant.bib_number }}
                    </span>
                    <span v-else class="text-muted">-</span>
                  </td>
                  
                  <!-- Участник -->
                  <td>
                    <div class="fw-semibold">{{ participant.name }}</div>
                    <small v-if="protocolType === 'final' && participant.birth_year" class="text-muted">
                      Год рождения: {{ participant.birth_year }}
                    </small>
                  </td>
                  
                  <!-- Год рождения (только для стартового) -->
                  <td v-if="protocolType === 'start'">
                    {{ participant.birth_year || '-' }}
                  </td>
                  
                  <!-- Команда (только для стартового) -->
                  <td v-if="protocolType === 'start'">
                    <small>{{ participant.team_name || '-' }}</small>
                  </td>
                  
                  <!-- Результат (только для итогового) -->
                  <td v-if="protocolType === 'final'" class="text-center">
                    <span v-if="participant.result" class="fw-bold">
                      {{ participant.result.formatted_time }}
                    </span>
                    <span v-else class="text-muted">-</span>
                  </td>
                  
                  <!-- Время финиша (только для итогового) -->
                  <td v-if="protocolType === 'final'" class="text-center">
                    <small v-if="participant.result && participant.result.finish_time">
                      {{ formatTime(participant.result.finish_time) }}
                    </small>
                    <span v-else class="text-muted">-</span>
                  </td>
                  
                  <!-- Статус (только для итогового) -->
                  <td v-if="protocolType === 'final'" class="text-center">
                    <span v-if="participant.result" 
                      :class="`badge bg-${getResultStatusColor(participant.result.status)}`">
                      {{ participant.result.status_text }}
                    </span>
                    <span v-else-if="participant.dns" class="badge bg-secondary">
                      Не стартовал
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Участники без дистанции (если есть) -->
      <div v-if="protocolData.no_distance && protocolData.no_distance.length > 0" class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-warning bg-opacity-10 border-bottom py-2">
          <h6 class="mb-0">
            <i class="bi bi-exclamation-triangle text-warning me-2"></i>
            Участники без указанной дистанции
          </h6>
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 80px;" class="text-center">№</th>
                <th>Участник</th>
                <th style="width: 120px;">Категория</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="participant in protocolData.no_distance" :key="participant.registration_id">
                <td class="text-center">
                  <span v-if="participant.bib_number" class="badge bg-dark rounded-pill">
                    {{ participant.bib_number }}
                  </span>
                  <span v-else class="text-muted">-</span>
                </td>
                <td>{{ participant.name }}</td>
                <td>{{ participant.category || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Сообщение если нет данных -->
      <div v-if="!protocolData.distances || protocolData.distances.length === 0" class="text-center py-5">
        <i class="bi bi-file-text fs-1 text-muted mb-3"></i>
        <h5>Нет данных для формирования протокола</h5>
        <p class="text-muted">
          Нет участников с подтвержденными заявками на это мероприятие
        </p>
      </div>
    </div>

    <!-- Модальное окно назначения номеров -->
    <div v-if="showAssignNumbersModal" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5)">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Назначение стартовых номеров</h5>
            <button type="button" class="btn-close" @click="showAssignNumbersModal = false" :disabled="assigningNumbers"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Метод назначения:</label>
              <div class="form-check mb-2">
                <input class="form-check-input" type="radio" v-model="assignMethod" value="manual" id="methodManual">
                <label class="form-check-label" for="methodManual">
                  Сохранить существующие номера (только проверка)
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" v-model="assignMethod" value="random" id="methodRandom">
                <label class="form-check-label" for="methodRandom">
                  Случайная жеребьевка
                </label>
              </div>
            </div>
            
            <div v-if="assignMethod === 'random'" class="mb-3">
              <label for="startFrom" class="form-label">Начинать нумерацию с:</label>
              <input type="number" class="form-control" id="startFrom" v-model.number="startFrom" min="1">
            </div>
            
            <div v-if="assignMethod === 'random'" class="mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" v-model="shuffleCategories" id="shuffleCategories">
                <label class="form-check-label" for="shuffleCategories">
                  Перемешивать внутри категорий
                </label>
              </div>
              <small class="text-muted">
                Если включено: сначала перемешиваются участники внутри каждой категории, затем назначаются номера
              </small>
            </div>
            
            <div v-if="assignMethod === 'manual'" class="alert alert-info">
              <i class="bi bi-info-circle me-2"></i>
              Будут проверены существующие номера на уникальность
            </div>
            
            <div v-if="assignError" class="alert alert-danger">
              {{ assignError }}
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" @click="showAssignNumbersModal = false" :disabled="assigningNumbers">
              Отмена
            </button>
            <button type="button" class="btn btn-primary" @click="assignNumbers" :disabled="assigningNumbers">
              <span v-if="assigningNumbers" class="spinner-border spinner-border-sm me-1"></span>
              {{ assignMethod === 'random' ? 'Назначить номера' : 'Проверить номера' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import ProtocolService from '@/services/protocol.service'

export default {
  name: 'ProtocolList',
  
  props: {
    eventId: {
      type: [Number, String],
      required: true
    },
    mode: {
      type: String,
      default: 'admin',
      validator: (value) => ['admin', 'personal', 'public'].includes(value)
    }
  },
  
  emits: ['export', 'numbers-assigned'],
  
  setup(props, { emit }) {
    // Состояние
    const protocolData = ref(null)
    const loading = ref(false)
    const error = ref(null)
    const protocolType = ref('start') // start или final
    const exporting = ref(false)
    
    // Назначение номеров
    const showAssignNumbersModal = ref(false)
    const assignMethod = ref('random')
    const startFrom = ref(1)
    const shuffleCategories = ref(false)
    const assigningNumbers = ref(false)
    const assignError = ref(null)
    
    // Вычисляемые свойства
    const isAdmin = computed(() => props.mode === 'admin')
    
    // Методы
    const loadProtocol = async () => {
      loading.value = true
      error.value = null
      
      try {
        if (protocolType.value === 'start') {
          protocolData.value = await ProtocolService.getStartProtocol(props.eventId)
        } else {
          protocolData.value = await ProtocolService.getFinalProtocol(props.eventId)
        }
      } catch (err) {
        console.error('Ошибка загрузки протокола:', err)
        error.value = err.response?.data?.message || err.message || 'Ошибка формирования протокола'
        protocolData.value = null
      } finally {
        loading.value = false
      }
    }
    
    const changeProtocolType = (type) => {
      if (protocolType.value !== type) {
        protocolType.value = type
        loadProtocol()
      }
    }
    
    const exportPDF = async () => {
      exporting.value = true
      try {
        const params = {
          type: protocolType.value
        }
        const result = await ProtocolService.exportPDF(props.eventId, params)
        emit('export', result)
        
        // Временное решение: показываем данные для PDF
        alert('PDF генерация будет реализована позже. Данные готовы к экспорту.')
        console.log('PDF export data:', result)
      } catch (err) {
        alert('Ошибка экспорта: ' + (err.response?.data?.message || err.message))
      } finally {
        exporting.value = false
      }
    }
    
    const assignNumbers = async () => {
      assigningNumbers.value = true
      assignError.value = null
      
      try {
        const data = {
          method: assignMethod.value
        }
        
        if (assignMethod.value === 'random') {
          data.start_from = startFrom.value
          data.shuffle_categories = shuffleCategories.value
        }
        
        const result = await ProtocolService.assignNumbers(props.eventId, data)
        emit('numbers-assigned', result)
        showAssignNumbersModal.value = false
        
        // Перезагружаем протокол
        loadProtocol()
        
        alert(result.message)
      } catch (err) {
        assignError.value = err.response?.data?.message || err.message || 'Ошибка назначения номеров'
      } finally {
        assigningNumbers.value = false
      }
    }
    
    const clearNumbers = async () => {
      if (confirm('Очистить все стартовые номера для этого мероприятия?')) {
        try {
          const result = await ProtocolService.clearNumbers(props.eventId)
          alert(result.message)
          loadProtocol()
        } catch (err) {
          alert('Ошибка: ' + (err.response?.data?.message || err.message))
        }
      }
    }
    
    // Форматирование
    const formatDate = (dateString) => {
      if (!dateString) return ''
      try {
        const date = new Date(dateString)
        return date.toLocaleDateString('ru-RU', {
          day: 'numeric',
          month: 'long',
          year: 'numeric'
        })
      } catch (e) {
        return dateString
      }
    }
    
    const formatDateTime = (dateTimeString) => {
      if (!dateTimeString) return ''
      try {
        const date = new Date(dateTimeString)
        return date.toLocaleString('ru-RU', {
          hour: '2-digit',
          minute: '2-digit',
          day: 'numeric',
          month: 'short'
        })
      } catch (e) {
        return dateTimeString
      }
    }
    
    const formatTime = (dateTimeString) => {
      if (!dateTimeString) return ''
      try {
        const date = new Date(dateTimeString)
        return date.toLocaleTimeString('ru-RU', {
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit'
        })
      } catch (e) {
        return dateTimeString
      }
    }
    
    const getResultStatusColor = (status) => {
      const colors = {
        'pending': 'warning',
        'confirmed': 'success',
        'disqualified': 'danger'
      }
      return colors[status] || 'secondary'
    }
    
    // Хуки жизненного цикла
    onMounted(() => {
      loadProtocol()
    })
    
    return {
      // Данные
      protocolData,
      loading,
      error,
      protocolType,
      exporting,
      showAssignNumbersModal,
      assignMethod,
      startFrom,
      shuffleCategories,
      assigningNumbers,
      assignError,
      
      // Вычисляемые свойства
      isAdmin,
      
      // Методы
      loadProtocol,
      changeProtocolType,
      exportPDF,
      assignNumbers,
      clearNumbers,
      formatDate,
      formatDateTime,
      formatTime,
      getResultStatusColor
    }
  }
}
</script>

<style scoped>
.protocol-list {
  min-height: 500px;
}

.category-section {
  border-bottom: 1px solid #dee2e6;
}

.category-section:last-child {
  border-bottom: none;
}

.category-header {
  background-color: #f8f9fa;
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

/* Модальное окно */
.modal {
  backdrop-filter: blur(2px);
}

@media (max-width: 768px) {
  .protocol-content .card-header .d-flex {
    flex-direction: column;
    gap: 10px;
    align-items: stretch !important;
  }
  
  .protocol-content .card-header .d-flex > div {
    width: 100%;
  }
  
  .table-responsive {
    font-size: 0.875rem;
  }
}
</style>