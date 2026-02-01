<template>
  <div class="finish-line">
    <!-- Выбор мероприятия -->
    <div v-if="!currentEvent" class="select-event-screen">
      <div class="card border-0 shadow-lg">
        <div class="card-body p-4">
          <h4 class="card-title mb-4">
            <i class="bi bi-calendar-event text-primary me-2"></i>
            Выберите мероприятие
          </h4>
          
          <div v-if="eventsLoading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-3 text-muted">Загрузка мероприятий...</p>
          </div>
          
          <div v-else-if="events.length === 0" class="text-center py-5">
            <i class="bi bi-calendar-x fs-1 text-muted mb-3"></i>
            <h5>Нет активных мероприятий</h5>
            <p class="text-muted">Нет мероприятий для фиксации результатов</p>
          </div>
          
          <div v-else class="events-grid">
            <div 
              v-for="event in events" 
              :key="event.id"
              class="event-card"
              @click="selectEvent(event)"
            >
              <div class="event-card-body">
                <h6 class="event-title">{{ event.title }}</h6>
                <div class="event-details">
                  <div class="event-date">
                    <i class="bi bi-calendar3 me-1"></i>
                    {{ formatDate(event.date) }}
                  </div>
                  <div class="event-location">
                    <i class="bi bi-geo-alt me-1"></i>
                    {{ event.location || 'Место не указано' }}
                  </div>
                  <div class="event-registrations">
                    <i class="bi bi-people me-1"></i>
                    Участников: {{ event.registrations_count || 0 }}
                  </div>
                </div>
                <button class="btn btn-primary btn-sm mt-3">
                  Выбрать
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Основной экран фиксации -->
    <div v-else class="main-screen">
      <!-- Статистика -->
      <div class="stats-row mb-4">
        <div class="row g-3">
          <div class="col-md-3 col-sm-6">
            <div class="stat-card">
              <div class="stat-icon bg-primary">
                <i class="bi bi-people"></i>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ stats.totalParticipants || 0 }}</div>
                <div class="stat-label">Всего участников</div>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="stat-card">
              <div class="stat-icon bg-success">
                <i class="bi bi-check-circle"></i>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ stats.finishedParticipants || 0 }}</div>
                <div class="stat-label">Финишировало</div>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="stat-card">
              <div class="stat-icon bg-warning">
                <i class="bi bi-clock"></i>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ stats.pendingParticipants || 0 }}</div>
                <div class="stat-label">Осталось</div>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="stat-card">
              <div class="stat-icon bg-info">
                <i class="bi bi-percent"></i>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ stats.completionRate || 0 }}%</div>
                <div class="stat-label">Завершено</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Основной блок -->
      <div class="row g-4">
        <!-- Секундомер и ввод -->
        <div class="col-lg-8">
          <div class="card border-0 shadow-lg">
            <div class="card-body p-4">
              <!-- Секундомер -->
              <div class="stopwatch-section mb-4">
                <div class="stopwatch-display">
                  <div class="stopwatch-time" :class="{ 'running': stopwatchRunning }">
                    {{ stopwatchTime }}
                  </div>
                  <div class="stopwatch-controls">
                    <button 
                      class="btn btn-lg" 
                      :class="stopwatchRunning ? 'btn-danger' : 'btn-success'"
                      @click="toggleStopwatch"
                    >
                      <i :class="stopwatchRunning ? 'bi bi-pause' : 'bi bi-play'"></i>
                      {{ stopwatchRunning ? 'Пауза' : 'Старт' }}
                    </button>
                    <button 
                      class="btn btn-lg btn-outline-secondary ms-2"
                      @click="resetStopwatch"
                      :disabled="stopwatchRunning"
                    >
                      <i class="bi bi-arrow-clockwise"></i>
                      Сброс
                    </button>
                    <button 
                      class="btn btn-lg btn-primary ms-2"
                      @click="recordCurrentTime"
                      :disabled="!stopwatchRunning"
                    >
                      <i class="bi bi-flag"></i>
                      Финиш!
                    </button>
                  </div>
                </div>
              </div>
              
              <!-- Ввод номера участника -->
              <div class="input-section">
                <h5 class="mb-3">
                  <i class="bi bi-123 text-primary me-2"></i>
                  Ввод стартового номера
                </h5>
                
                <!-- Быстрый ввод -->
                <div class="quick-input mb-4">
                  <div class="input-group input-group-lg">
                    <span class="input-group-text bg-primary text-white">
                      <i class="bi bi-tag"></i>
                    </span>
                    <input 
                      type="text" 
                      class="form-control form-control-lg" 
                      v-model="bibNumber"
                      placeholder="Введите стартовый номер"
                      @keyup.enter="findParticipant"
                      ref="bibInput"
                      :disabled="loadingParticipant"
                    />
                    <button 
                      class="btn btn-primary" 
                      type="button"
                      @click="findParticipant"
                      :disabled="loadingParticipant || !bibNumber"
                    >
                      <span v-if="loadingParticipant" class="spinner-border spinner-border-sm me-2"></span>
                      <i v-else class="bi bi-search me-1"></i>
                      Найти
                    </button>
                  </div>
                  <div class="form-text mt-2">
                    Нажмите Enter или кнопку "Найти" для поиска участника
                  </div>
                </div>
                
                <!-- Цифровая клавиатура -->
                <div class="numeric-keyboard">
                  <div class="row g-2">
                    <div class="col-4" v-for="num in [1,2,3,4,5,6,7,8,9,0,'Стереть','Ввод']" :key="num">
                      <button 
                        class="btn btn-outline-secondary w-100 py-3"
                        :class="{ 
                          'btn-danger': num === 'Стереть',
                          'btn-success': num === 'Ввод'
                        }"
                        @click="handleKeyPress(num)"
                      >
                        <template v-if="num === 'Стереть'">
                          <i class="bi bi-backspace"></i>
                        </template>
                        <template v-else-if="num === 'Ввод'">
                          <i class="bi bi-check-lg"></i>
                        </template>
                        <template v-else>
                          {{ num }}
                        </template>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Информация об участнике и фиксация -->
        <div class="col-lg-4">
          <!-- Информация об участнике -->
          <div v-if="currentParticipant" class="card border-0 shadow-lg mb-4">
            <div class="card-body p-4">
              <h5 class="card-title mb-3">
                <i class="bi bi-person-check text-success me-2"></i>
                Участник найден
              </h5>
              
              <div class="participant-info">
                <div class="participant-bib mb-3">
                  <span class="badge bg-dark fs-5">№ {{ currentParticipant.bib_number }}</span>
                </div>
                
                <div class="participant-details">
                  <div class="detail-item">
                    <i class="bi bi-person text-muted me-2"></i>
                    <strong>ФИО:</strong> {{ currentParticipant.user_name }}
                  </div>
                  <div class="detail-item">
                    <i class="bi bi-people text-muted me-2"></i>
                    <strong>Команда:</strong> {{ currentParticipant.team_name || '–' }}
                  </div>
                  <div class="detail-item">
                    <i class="bi bi-award text-muted me-2"></i>
                    <strong>Категория:</strong> {{ currentParticipant.category || '–' }}
                  </div>
                  <div v-if="currentParticipant.result" class="detail-item text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Результат уже зафиксирован:</strong> {{ currentParticipant.result.formatted_time }}
                  </div>
                </div>
                
                <!-- Фиксация результата -->
                <div class="record-section mt-4">
                  <h6 class="mb-3">Фиксация результата</h6>
                  
                  <div class="time-display mb-3">
                    <div class="form-label">Время финиша</div>
                    <div class="time-value fs-3 fw-bold text-primary">
                      {{ recordTime || '00:00.00' }}
                    </div>
                    <div class="time-actions mt-2">
                      <button 
                        class="btn btn-sm btn-outline-secondary me-2"
                        @click="useStopwatchTime"
                        :disabled="!stopwatchRunning"
                      >
                        Исп. секундомер
                      </button>
                      <button 
                        class="btn btn-sm btn-outline-secondary"
                        @click="showManualTimeInput = !showManualTimeInput"
                      >
                        Вручную
                      </button>
                    </div>
                  </div>
                  
                  <!-- Ручной ввод времени -->
                  <div v-if="showManualTimeInput" class="manual-time-input mb-3">
                    <div class="row g-2">
                      <div class="col-4">
                        <input 
                          type="number" 
                          class="form-control" 
                          v-model="manualMinutes"
                          placeholder="Мин"
                          min="0"
                          max="59"
                        />
                      </div>
                      <div class="col-4">
                        <input 
                          type="number" 
                          class="form-control" 
                          v-model="manualSeconds"
                          placeholder="Сек"
                          min="0"
                          max="59"
                          step="0.01"
                        />
                      </div>
                      <div class="col-4">
                        <button 
                          class="btn btn-primary w-100"
                          @click="applyManualTime"
                        >
                          Применить
                        </button>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Кнопка фиксации -->
                  <button 
                    class="btn btn-success btn-lg w-100"
                    @click="recordResult"
                    :disabled="!recordTime || recording"
                  >
                    <span v-if="recording" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="bi bi-check-circle me-2"></i>
                    {{ currentParticipant.result ? 'Обновить результат' : 'Зафиксировать результат' }}
                  </button>
                  
                  <div v-if="recordError" class="alert alert-danger mt-3 py-2">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    {{ recordError }}
                  </div>
                  
                  <div v-if="recordSuccess" class="alert alert-success mt-3 py-2">
                    <i class="bi bi-check-circle me-2"></i>
                    Результат успешно зафиксирован!
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Последние зафиксированные -->
          <div class="card border-0 shadow-lg">
            <div class="card-body p-4">
              <h5 class="card-title mb-3">
                <i class="bi bi-clock-history text-primary me-2"></i>
                Последние результаты
              </h5>
              
              <div v-if="recentResults.length === 0" class="text-center py-3">
                <p class="text-muted">Нет зафиксированных результатов</p>
              </div>
              
              <div v-else class="recent-results">
                <div 
                  v-for="result in recentResults" 
                  :key="result.id"
                  class="recent-result-item"
                >
                  <div class="result-bib">
                    <span class="badge bg-dark">№ {{ result.bib_number }}</span>
                  </div>
                  <div class="result-info">
                    <div class="result-name">{{ result.user_name }}</div>
                    <div class="result-time text-primary fw-bold">
                      {{ result.formatted_time }}
                    </div>
                  </div>
                  <div class="result-actions">
                    <button 
                      class="btn btn-sm btn-outline-secondary"
                      @click="editResult(result)"
                      title="Редактировать"
                    >
                      <i class="bi bi-pencil"></i>
                    </button>
                  </div>
                </div>
              </div>
              
              <div class="text-center mt-3">
                <button class="btn btn-sm btn-outline-primary" @click="loadMoreResults">
                  Загрузить еще
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>

import { ref, reactive, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';

import EventService from '@/services/event.service'
import RegistrationService from '@/services/registration.service'
import ResultService from '@/services/result.service'
import OfflineStorage from '@/services/offlineStorage'

export default {
  name: 'FinishLine',
  
  props: {
    currentEvent: {
      type: Object,
      default: null
    }
  },
  
  emits: ['event-selected', 'sync-complete'],
  
  setup(props, { emit }) {
    // Состояние
    const events = ref([])
    const eventsLoading = ref(false)
    const bibNumber = ref('')
    const currentParticipant = ref(null)
    const loadingParticipant = ref(false)
    const recordTime = ref('')
    const recording = ref(false)
    const recordError = ref('')
    const recordSuccess = ref(false)
    const showManualTimeInput = ref(false)
    const manualMinutes = ref('')
    const manualSeconds = ref('')
    
    // Секундомер
    const stopwatchRunning = ref(false)
    const stopwatchStartTime = ref(0)
    const stopwatchElapsed = ref(0)
    const stopwatchInterval = ref(null)
    
    // Результаты
    const recentResults = ref([])
    const resultsLoading = ref(false)
    const stats = ref({
      totalParticipants: 0,
      finishedParticipants: 0,
      pendingParticipants: 0,
      completionRate: 0
    })
    
    // Refs
    const bibInput = ref(null)
    
    // Форматирование даты
    const formatDate = (dateString) => {
      const options = { day: 'numeric', month: 'short', year: 'numeric' }
      return new Date(dateString).toLocaleDateString('ru-RU', options)
    }
    
    // Время секундомера
    const stopwatchTime = computed(() => {
      const totalSeconds = stopwatchElapsed.value / 1000
      const minutes = Math.floor(totalSeconds / 60)
      const seconds = (totalSeconds % 60).toFixed(2)
      return `${minutes.toString().padStart(2, '0')}:${seconds.padStart(5, '0')}`
    })
    
    // Управление секундомером
    const toggleStopwatch = () => {
      if (stopwatchRunning.value) {
        clearInterval(stopwatchInterval.value)
        stopwatchRunning.value = false
      } else {
        if (stopwatchStartTime.value === 0) {
          stopwatchStartTime.value = Date.now() - stopwatchElapsed.value
        } else {
          stopwatchStartTime.value = Date.now() - stopwatchElapsed.value
        }
        
        stopwatchRunning.value = true
        stopwatchInterval.value = setInterval(() => {
          stopwatchElapsed.value = Date.now() - stopwatchStartTime.value
        }, 10)
      }
    }
    
    const resetStopwatch = () => {
      clearInterval(stopwatchInterval.value)
      stopwatchRunning.value = false
      stopwatchStartTime.value = 0
      stopwatchElapsed.value = 0
      recordTime.value = ''
    }
    
    const recordCurrentTime = () => {
      if (stopwatchRunning.value) {
        recordTime.value = stopwatchTime.value
      }
    }
    
    // Загрузка мероприятий
    const loadEvents = async () => {
      eventsLoading.value = true
      try {
        const response = await EventService.getAll({ 
          status: 'active',
          limit: 4
        })
        events.value = response.data || []
      } catch (error) {
        console.error('Ошибка загрузки мероприятий:', error)
      } finally {
        eventsLoading.value = false
      }
    }
    
    // Выбор мероприятия
    const selectEvent = (event) => {
      emit('event-selected', event)
      loadStats(event.id)
      loadRecentResults(event.id)
      
      // Фокус на поле ввода номера
      nextTick(() => {
        if (bibInput.value) {
          bibInput.value.focus()
        }
      })
    }
    
    // Загрузка статистики
    const loadStats = async (eventId) => {
      try {
        // Загрузка регистраций
        const registrationsResponse = await RegistrationService.getAll({
          event_id: eventId,
          status: 'approved',
          limit: 1000
        })
        
        // Загрузка результатов
        const resultsResponse = await ResultService.getByEvent(eventId, { limit: 1000 })
        
        const total = registrationsResponse.meta?.total || 0
        const finished = resultsResponse.data?.length || 0
        const pending = Math.max(0, total - finished)
        const rate = total > 0 ? Math.round((finished / total) * 100) : 0
        
        stats.value = {
          totalParticipants: total,
          finishedParticipants: finished,
          pendingParticipants: pending,
          completionRate: rate
        }
      } catch (error) {
        console.error('Ошибка загрузки статистики:', error)
      }
    }
    
    // Загрузка последних результатов
    const loadRecentResults = async (eventId) => {
      resultsLoading.value = true
      try {
        const response = await ResultService.getByEvent(eventId, { 
          limit: 10,
          order: 'desc'
        })
        recentResults.value = response.data || []
      } catch (error) {
        console.error('Ошибка загрузки результатов:', error)
      } finally {
        resultsLoading.value = false
      }
    }
    
    // Поиск участника
    const findParticipant = async () => {
      if (!bibNumber.value || !props.currentEvent) return
      
      loadingParticipant.value = true
      recordError.value = ''
      recordSuccess.value = false
      
      try {
        // Ищем регистрацию по номеру и мероприятию
        const response = await RegistrationService.getAll({
          event_id: props.currentEvent.id,
          search: bibNumber.value,
          limit: 1
        })
        
        if (response.data && response.data.length > 0) {
          const registration = response.data[0]
          
          // Проверяем, есть ли уже результат
          if (registration.id) {
            try {
              const resultsResponse = await ResultService.getByEvent(props.currentEvent.id, {
                search: bibNumber.value,
                limit: 1
              })
              
              if (resultsResponse.data && resultsResponse.data.length > 0) {
                registration.result = resultsResponse.data[0]
              }
            } catch (resultError) {
              console.error('Ошибка проверки результата:', resultError)
            }
          }
          
          currentParticipant.value = registration
          recordTime.value = ''
        } else {
          recordError.value = 'Участник с таким номером не найден'
          currentParticipant.value = null
        }
      } catch (error) {
        console.error('Ошибка поиска участника:', error)
        recordError.value = 'Ошибка поиска участника'
        currentParticipant.value = null
      } finally {
        loadingParticipant.value = false
      }
    }
    
    // Управление цифровой клавиатурой
    const handleKeyPress = (key) => {
      if (key === 'Стереть') {
        bibNumber.value = bibNumber.value.slice(0, -1)
      } else if (key === 'Ввод') {
        findParticipant()
      } else {
        bibNumber.value += key.toString()
      }
    }
    
    // Ручной ввод времени
    const applyManualTime = () => {
      const mins = parseInt(manualMinutes.value) || 0
      const secs = parseFloat(manualSeconds.value) || 0
      
      if (mins >= 0 && secs >= 0 && secs < 60) {
        recordTime.value = `${mins}:${secs.toFixed(2).padStart(5, '0')}`
        showManualTimeInput.value = false
        manualMinutes.value = ''
        manualSeconds.value = ''
      } else {
        recordError.value = 'Некорректное время'
      }
    }
    
    // Использовать время секундомера
    const useStopwatchTime = () => {
      recordTime.value = stopwatchTime.value
    }
    
    // Фиксация результата
    const recordResult = async () => {
      if (!currentParticipant.value || !recordTime.value) return
      
      recording.value = true
      recordError.value = ''
      recordSuccess.value = false
      
      try {
        const resultData = {
          registration_id: currentParticipant.value.id,
          result_time: recordTime.value,
          finish_time: new Date().toISOString(),
          status: 'confirmed'
        }
        
        let success = false
        
        // Проверяем онлайн/офлайн
        if (navigator.onLine) {
          // Онлайн режим
          const response = await ResultService.create(resultData)
          if (response.id) {
            success = true
          }
        } else {
          // Офлайн режим
          const offlineResult = await OfflineStorage.saveResult(resultData)
          if (offlineResult) {
            success = true
            emit('sync-complete')
          }
        }
        
        if (success) {
          recordSuccess.value = true
          bibNumber.value = ''
          currentParticipant.value = null
          
          // Обновляем статистику и результаты
          if (props.currentEvent) {
            await Promise.all([
              loadStats(props.currentEvent.id),
              loadRecentResults(props.currentEvent.id)
            ])
          }
          
          // Автоочистка успешного сообщения
          setTimeout(() => {
            recordSuccess.value = false
          }, 3000)
          
          // Фокус на поле ввода
          nextTick(() => {
            if (bibInput.value) {
              bibInput.value.focus()
            }
          })
        } else {
          recordError.value = 'Ошибка фиксации результата'
        }
      } catch (error) {
        console.error('Ошибка фиксации результата:', error)
        recordError.value = error.response?.data?.message || 'Ошибка фиксации результата'
      } finally {
        recording.value = false
      }
    }
    
    // Редактирование результата
    const editResult = (result) => {
      bibNumber.value = result.bib_number
      recordTime.value = result.result_time
      findParticipant()
    }
    
    // Загрузка дополнительных результатов
    const loadMoreResults = async () => {
      if (!props.currentEvent) return
      
      try {
        const currentCount = recentResults.value.length
        const response = await ResultService.getByEvent(props.currentEvent.id, { 
          limit: currentCount + 10,
          order: 'desc'
        })
        recentResults.value = response.data || []
      } catch (error) {
        console.error('Ошибка загрузки дополнительных результатов:', error)
      }
    }
    
    // Инициализация
    onMounted(() => {
      if (!props.currentEvent) {
        loadEvents()
      } else {
        loadStats(props.currentEvent.id)
        loadRecentResults(props.currentEvent.id)
        
        nextTick(() => {
          if (bibInput.value) {
            bibInput.value.focus()
          }
        })
      }
    })
    
    // Наблюдатель за изменением мероприятия
    watch(() => props.currentEvent, (newEvent) => {
      if (newEvent) {
        loadStats(newEvent.id)
        loadRecentResults(newEvent.id)
        bibNumber.value = ''
        currentParticipant.value = null
        
        nextTick(() => {
          if (bibInput.value) {
            bibInput.value.focus()
          }
        })
      }
    })
    
    // Очистка интервалов
    onUnmounted(() => {
      if (stopwatchInterval.value) {
        clearInterval(stopwatchInterval.value)
      }
    })
    
    return {
      // Data
      events,
      eventsLoading,
      bibNumber,
      currentParticipant,
      loadingParticipant,
      recordTime,
      recording,
      recordError,
      recordSuccess,
      showManualTimeInput,
      manualMinutes,
      manualSeconds,
      stopwatchRunning,
      stopwatchTime,
      recentResults,
      resultsLoading,
      stats,
      
      // Refs
      bibInput,
      
      // Methods
      formatDate,
      toggleStopwatch,
      resetStopwatch,
      recordCurrentTime,
      selectEvent,
      findParticipant,
      handleKeyPress,
      applyManualTime,
      useStopwatchTime,
      recordResult,
      editResult,
      loadMoreResults
    }
  }
}
</script>

<style scoped>
.finish-line {
  min-height: calc(100vh - 120px);
}

/* Выбор мероприятия */
.events-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
}

.event-card {
  border: 1px solid #dee2e6;
  border-radius: 10px;
  padding: 1.5rem;
  cursor: pointer;
  transition: all 0.3s;
  background: white;
}

.event-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  border-color: #3498db;
}

.event-card-body {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.event-title {
  font-weight: 600;
  margin-bottom: 1rem;
  color: #2c3e50;
  flex-grow: 1;
}

.event-details {
  font-size: 0.9rem;
  color: #6c757d;
  margin-bottom: 1rem;
}

.event-details > div {
  margin-bottom: 0.5rem;
}

/* Статистика */
.stats-row {
  margin-top: 1rem;
}

.stat-card {
  display: flex;
  align-items: center;
  padding: 1rem;
  background: white;
  border-radius: 10px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 1rem;
  color: white;
  font-size: 1.5rem;
}

.stat-info {
  flex-grow: 1;
}

.stat-value {
  font-size: 1.8rem;
  font-weight: bold;
  line-height: 1;
  color: #2c3e50;
}

.stat-label {
  font-size: 0.85rem;
  color: #6c757d;
  margin-top: 0.25rem;
}

/* Секундомер */
.stopwatch-section {
  text-align: center;
}

.stopwatch-display {
  margin-bottom: 2rem;
}

.stopwatch-time {
  font-size: 4rem;
  font-weight: bold;
  font-family: 'Courier New', monospace;
  color: #2c3e50;
  margin-bottom: 1.5rem;
  text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
}

.stopwatch-time.running {
  color: #e74c3c;
  animation: pulse 1s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

.stopwatch-controls {
  display: flex;
  justify-content: center;
  gap: 1rem;
}

.stopwatch-controls .btn {
  min-width: 120px;
}

/* Цифровая клавиатура */
.numeric-keyboard {
  margin-top: 2rem;
}

.numeric-keyboard .btn {
  font-size: 1.2rem;
  font-weight: bold;
}

/* Информация об участнике */
.participant-info {
  padding: 1rem 0;
}

.detail-item {
  margin-bottom: 0.75rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #eee;
}

.detail-item:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

/* Последние результаты */
.recent-results {
  max-height: 400px;
  overflow-y: auto;
}

.recent-result-item {
  display: flex;
  align-items: center;
  padding: 0.75rem;
  border-bottom: 1px solid #eee;
  transition: background-color 0.2s;
}

.recent-result-item:hover {
  background-color: #f8f9fa;
}

.recent-result-item:last-child {
  border-bottom: none;
}

.result-bib {
  margin-right: 1rem;
}

.result-info {
  flex-grow: 1;
}

.result-name {
  font-weight: 500;
  font-size: 0.9rem;
  margin-bottom: 0.25rem;
}

.result-time {
  font-size: 1.1rem;
}

.result-actions {
  opacity: 0;
  transition: opacity 0.2s;
}

.recent-result-item:hover .result-actions {
  opacity: 1;
}

/* Адаптивность */
@media (max-width: 768px) {
  .stopwatch-time {
    font-size: 3rem;
  }
  
  .stopwatch-controls {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .stopwatch-controls .btn {
    width: 100%;
  }
  
  .events-grid {
    grid-template-columns: 1fr;
  }
  
  .stat-card {
    flex-direction: column;
    text-align: center;
  }
  
  .stat-icon {
    margin-right: 0;
    margin-bottom: 0.5rem;
  }
  
  .stat-value {
    font-size: 1.5rem;
  }
}

@media (max-width: 576px) {
  .stopwatch-time {
    font-size: 2.5rem;
  }
  
  .input-group-lg {
    flex-direction: column;
  }
  
  .input-group-lg > .form-control,
  .input-group-lg > .btn {
    width: 100%;
    margin-bottom: 0.5rem;
    border-radius: 0.375rem !important;
  }
}
</style>