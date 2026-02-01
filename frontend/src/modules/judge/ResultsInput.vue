<template>
  <div class="results-input">
    <!-- Заголовок -->
    <div class="page-header mb-4">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h3 class="mb-1">
            <i class="bi bi-list-ol text-primary me-2"></i>
            Результаты мероприятия
          </h3>
          <p class="text-muted mb-0" v-if="currentEvent">
            {{ currentEvent.title }} • {{ formatDate(currentEvent.date) }}
          </p>
          <p v-else class="text-muted mb-0">Выберите мероприятие для просмотра результатов</p>
        </div>
        
        <div class="d-flex gap-2">
          <!-- Кнопка синхронизации -->
          <button 
            class="btn btn-outline-primary"
            @click="syncOfflineResults"
            :disabled="syncing || offlineResultsCount === 0"
          >
            <span v-if="syncing" class="spinner-border spinner-border-sm me-2"></span>
            <i v-else class="bi bi-cloud-arrow-up me-1"></i>
            Синхронизировать ({{ offlineResultsCount }})
          </button>
          
          <!-- Выбор мероприятия -->
          <div class="dropdown" v-if="!currentEvent">
            <button 
              class="btn btn-primary dropdown-toggle" 
              type="button" 
              data-bs-toggle="dropdown"
              :disabled="eventsLoading"
            >
              <span v-if="eventsLoading" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="bi bi-calendar3 me-1"></i>
              Выбрать мероприятие
            </button>
            <ul class="dropdown-menu">
              <li v-if="events.length === 0">
                <a class="dropdown-item disabled">Нет мероприятий</a>
              </li>
              <li v-for="event in events" :key="event.id">
                <a class="dropdown-item" @click="selectEvent(event)">
                  {{ event.title }} ({{ formatDate(event.date) }})
                </a>
              </li>
            </ul>
          </div>
          
          <!-- Экспорт -->
          <button 
            class="btn btn-outline-success"
            @click="exportResults"
            :disabled="!currentEvent || results.length === 0"
          >
            <i class="bi bi-download me-1"></i>
            Экспорт
          </button>
        </div>
      </div>
    </div>
    
    <!-- Фильтры и управление -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-3">
        <div class="d-flex flex-wrap gap-3 align-items-center">
          <!-- Поиск -->
          <div class="flex-grow-1" style="max-width: 300px;">
            <div class="input-group">
              <span class="input-group-text">
                <i class="bi bi-search"></i>
              </span>
              <input 
                type="text" 
                class="form-control" 
                v-model="filters.search"
                placeholder="Поиск по номеру или имени..."
                @input="debouncedLoadResults"
              />
            </div>
          </div>
          
          <!-- Фильтр по статусу -->
          <select class="form-select" style="width: 180px;" v-model="filters.status" @change="loadResults">
            <option value="">Все статусы</option>
            <option value="pending">Ожидает</option>
            <option value="confirmed">Подтвержден</option>
            <option value="disqualified">Дисквалифицирован</option>
          </select>
          
          <!-- Сортировка -->
          <select class="form-select" style="width: 180px;" v-model="filters.sort" @change="loadResults">
            <option value="result_time:asc">Время ↑ (лучшие)</option>
            <option value="result_time:desc">Время ↓</option>
            <option value="created_at:desc">Сначала новые</option>
            <option value="created_at:asc">Сначала старые</option>
          </select>
          
          <!-- Количество -->
          <select class="form-select" style="width: 120px;" v-model="filters.limit" @change="loadResults">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
          
          <!-- Кнопка обновления -->
          <button class="btn btn-outline-secondary" @click="loadResults" :disabled="loading">
            <i class="bi bi-arrow-clockwise"></i>
          </button>
          
          <!-- Массовые действия -->
          <div class="dropdown" v-if="selectedResults.length > 0">
            <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
              Действия ({{ selectedResults.length }})
            </button>
            <ul class="dropdown-menu">
              <li>
                <a class="dropdown-item" @click="bulkUpdateStatus('confirmed')">
                  <i class="bi bi-check-circle text-success me-2"></i>
                  Подтвердить
                </a>
              </li>
              <li>
                <a class="dropdown-item" @click="bulkUpdateStatus('disqualified')">
                  <i class="bi bi-x-circle text-danger me-2"></i>
                  Дисквалифицировать
                </a>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item text-danger" @click="bulkDeleteResults">
                  <i class="bi bi-trash me-2"></i>
                  Удалить
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Состояние загрузки -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="mt-3 text-muted">Загрузка результатов...</p>
    </div>
    
    <!-- Ошибка -->
    <div v-else-if="error" class="alert alert-danger">
      <i class="bi bi-exclamation-triangle me-2"></i>
      {{ error }}
      <button class="btn btn-sm btn-outline-danger ms-3" @click="loadResults">
        Повторить
      </button>
    </div>
    
    <!-- Контент -->
    <div v-else>
      <!-- Сводная информация -->
      <div class="summary-cards mb-4">
        <div class="row g-3">
          <div class="col-md-3 col-sm-6">
            <div class="summary-card bg-primary">
              <div class="summary-icon">
                <i class="bi bi-people"></i>
              </div>
              <div class="summary-content">
                <div class="summary-value">{{ summary.total }}</div>
                <div class="summary-label">Всего результатов</div>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="summary-card bg-success">
              <div class="summary-icon">
                <i class="bi bi-check-circle"></i>
              </div>
              <div class="summary-content">
                <div class="summary-value">{{ summary.confirmed }}</div>
                <div class="summary-label">Подтверждено</div>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="summary-card bg-warning">
              <div class="summary-icon">
                <i class="bi bi-clock"></i>
              </div>
              <div class="summary-content">
                <div class="summary-value">{{ summary.pending }}</div>
                <div class="summary-label">Ожидает</div>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="summary-card bg-danger">
              <div class="summary-icon">
                <i class="bi bi-x-circle"></i>
              </div>
              <div class="summary-content">
                <div class="summary-value">{{ summary.disqualified }}</div>
                <div class="summary-label">Дисквалифицировано</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Таблица результатов -->
      <div class="card border-0 shadow-sm">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 50px;">
                  <input 
                    type="checkbox" 
                    class="form-check-input" 
                    v-model="selectAll"
                    :disabled="results.length === 0"
                  />
                </th>
                <th style="width: 80px;">Место</th>
                <th style="width: 100px;">Старт №</th>
                <th>Участник</th>
                <th style="width: 120px;">Команда</th>
                <th style="width: 150px;">Категория</th>
                <th style="width: 150px;">Результат</th>
                <th style="width: 120px;">Статус</th>
                <th style="width: 150px;">Судья</th>
                <th style="width: 180px;">Время фиксации</th>
                <th style="width: 100px;" class="text-end">Действия</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="(result, index) in results" 
                :key="result.id"
                :class="{ 
                  'table-primary': selectedResults.includes(result.id),
                  'table-success': result.status === 'confirmed',
                  'table-warning': result.status === 'pending',
                  'table-danger': result.status === 'disqualified'
                }"
              >
                <td>
                  <input 
                    type="checkbox" 
                    class="form-check-input" 
                    :value="result.id"
                    v-model="selectedResults"
                  />
                </td>
                <td>
                  <span class="badge bg-dark">{{ calculatePlace(index) }}</span>
                </td>
                <td>
                  <span class="badge bg-secondary">№ {{ result.bib_number }}</span>
                </td>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                      <i class="bi bi-person-circle text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                      <div class="fw-semibold">{{ result.user_name }}</div>
                      <small class="text-muted">{{ result.registration_id }}</small>
                    </div>
                  </div>
                </td>
                <td>
                  <small>{{ result.team_name || '–' }}</small>
                </td>
                <td>
                  <small>{{ result.category || '–' }}</small>
                </td>
                <td>
                  <div class="result-time-cell">
                    <div class="fw-bold text-primary">{{ result.formatted_time }}</div>
                    <small class="text-muted">{{ formatFinishTime(result.finish_time) }}</small>
                  </div>
                </td>
                <td>
                  <span :class="`badge bg-${result.status_color}`">
                    {{ result.status_text }}
                  </span>
                </td>
                <td>
                  <small>{{ result.judge_name || '–' }}</small>
                </td>
                <td>
                  <small>{{ formatDateTime(result.created_at) }}</small>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <button 
                      class="btn btn-outline-primary" 
                      @click="editResult(result)"
                      title="Редактировать"
                    >
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button 
                      class="btn btn-outline-danger" 
                      @click="deleteResult(result)"
                      title="Удалить"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Сообщение при отсутствии результатов -->
        <div v-if="results.length === 0 && !loading" class="text-center py-5">
          <i class="bi bi-clipboard-x fs-1 text-muted mb-3"></i>
          <h5>Результаты не найдены</h5>
          <p class="text-muted">
            {{ filters.search ? 'Попробуйте изменить условия поиска' : 'Нет зафиксированных результатов' }}
          </p>
        </div>
        
        <!-- Пагинация -->
        <div v-if="meta && meta.pages > 1" class="card-footer bg-white border-top py-3">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted small">
              Показано {{ results.length }} из {{ meta.total }} результатов
              <span v-if="selectedResults.length > 0" class="ms-3">
                • Выбрано: {{ selectedResults.length }}
              </span>
            </div>
            <nav>
              <ul class="pagination pagination-sm mb-0">
                <li class="page-item" :class="{ disabled: meta.page === 1 }">
                  <button class="page-link" @click="changePage(meta.page - 1)" :disabled="loading">
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
                  <button class="page-link" @click="changePage(meta.page + 1)" :disabled="loading">
                    <i class="bi bi-chevron-right"></i>
                  </button>
                </li>
              </ul>
            </nav>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Модальное окно редактирования -->
    <div v-if="showEditModal" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5)">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="bi bi-pencil-square me-2"></i>
              Редактирование результата
            </h5>
            <button type="button" class="btn-close" @click="closeEditModal" :disabled="editing"></button>
          </div>
          
          <div class="modal-body">
            <form @submit.prevent="saveResult">
              <div class="row g-3">
                <!-- Информация об участнике -->
                <div class="col-12">
                  <div class="info-card mb-3">
                    <div class="row">
                      <div class="col-md-6">
                        <div class="info-item">
                          <label>Участник:</label>
                          <div class="info-value">{{ editingResult.user_name }}</div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="info-item">
                          <label>Стартовый номер:</label>
                          <div class="info-value">№ {{ editingResult.bib_number }}</div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="info-item">
                          <label>Команда:</label>
                          <div class="info-value">{{ editingResult.team_name || '–' }}</div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="info-item">
                          <label>Категория:</label>
                          <div class="info-value">{{ editingResult.category || '–' }}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Время -->
                <div class="col-md-6">
                  <label class="form-label">Время результата *</label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="bi bi-clock"></i>
                    </span>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="editForm.result_time"
                      placeholder="MM:SS.ss"
                      required
                    />
                  </div>
                  <div class="form-text">Формат: минуты:секунды.сотые (например: 45:23.15)</div>
                </div>
                
                <!-- Статус -->
                <div class="col-md-6">
                  <label class="form-label">Статус</label>
                  <select class="form-select" v-model="editForm.status">
                    <option value="pending">Ожидает подтверждения</option>
                    <option value="confirmed">Подтвержден</option>
                    <option value="disqualified">Дисквалифицирован</option>
                  </select>
                </div>
                
                <!-- Примечания -->
                <div class="col-12">
                  <label class="form-label">Примечания</label>
                  <textarea 
                    class="form-control" 
                    rows="3"
                    v-model="editForm.notes"
                    placeholder="Дополнительная информация..."
                  ></textarea>
                </div>
              </div>
            </form>
          </div>
          
          <div class="modal-footer">
            <button 
              type="button" 
              class="btn btn-secondary" 
              @click="closeEditModal"
              :disabled="editing"
            >
              Отмена
            </button>
            <button 
              type="button" 
              class="btn btn-primary" 
              @click="saveResult"
              :disabled="editing || !editForm.result_time"
            >
              <span v-if="editing" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="bi bi-check-circle me-2"></i>
              Сохранить
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Уведомление о синхронизации -->
    <div v-if="syncNotification.show" class="sync-notification fixed-bottom">
      <div :class="`alert alert-${syncNotification.type} m-3 shadow-lg`">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <i :class="`bi bi-${syncNotification.icon} me-2`"></i>
            {{ syncNotification.message }}
          </div>
          <button class="btn btn-sm btn-outline-dark" @click="syncNotification.show = false">
            <i class="bi bi-x"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { debounce } from 'lodash-es'
import EventService from '@/services/event.service'
import ResultService from '@/services/result.service'
import OfflineStorage from '@/services/offlineStorage'

export default {
  name: 'ResultsInput',
  
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
    const results = ref([])
    const meta = ref({
      total: 0,
      page: 1,
      pages: 1,
      limit: 25
    })
    const loading = ref(false)
    const error = ref('')
    const selectedResults = ref([])
    const offlineResultsCount = ref(0)
    const syncing = ref(false)
    
    // Фильтры
    const filters = ref({
      search: '',
      status: '',
      sort: 'result_time:asc',
      limit: 25,
      page: 1
    })
    
    // Сводная информация
    const summary = ref({
      total: 0,
      confirmed: 0,
      pending: 0,
      disqualified: 0
    })
    
    // Модальное окно редактирования
    const showEditModal = ref(false)
    const editingResult = ref(null)
    const editing = ref(false)
    const editForm = ref({
      result_time: '',
      status: 'pending',
      notes: ''
    })
    
    // Уведомления
    const syncNotification = ref({
      show: false,
      type: 'success',
      icon: 'check-circle',
      message: ''
    })
    
    // Компьютеды
    const selectAll = computed({
      get: () => results.value.length > 0 && selectedResults.value.length === results.value.length,
      set: (value) => {
        if (value) {
          selectedResults.value = results.value.map(r => r.id)
        } else {
          selectedResults.value = []
        }
      }
    })
    
    // Методы форматирования
    const formatDate = (dateString) => {
      const options = { day: 'numeric', month: 'short', year: 'numeric' }
      return new Date(dateString).toLocaleDateString('ru-RU', options)
    }
    
    const formatDateTime = (dateString) => {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }
    
    const formatFinishTime = (timeString) => {
      if (!timeString) return ''
      const date = new Date(timeString)
      return date.toLocaleTimeString('ru-RU', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      })
    }
    
    // Расчет места (учитывая дисквалификации)
    const calculatePlace = (index) => {
      let place = index + 1
      // Если предыдущий участник дисквалифицирован, место не меняется
      for (let i = 0; i < index; i++) {
        if (results.value[i].status === 'disqualified') {
          place--
        }
      }
      return place
    }
    
    // Загрузка мероприятий
    const loadEvents = async () => {
      eventsLoading.value = true
      try {
        const response = await EventService.getAll({ 
          status: 'active',
          upcoming: true,
          limit: 20
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
      loadResults()
      checkOfflineResults()
    }
    
    // Загрузка результатов
    const loadResults = async () => {
      if (!props.currentEvent) return
      
      loading.value = true
      error.value = ''
      selectedResults.value = []
      
      try {
        // Используем правильный метод getByEvent вместо getResultsByEvent
        const response = await ResultService.getByEvent(props.currentEvent.id, {
          search: filters.value.search,
          status: filters.value.status,
          sort: filters.value.sort,
          limit: filters.value.limit,
          page: filters.value.page
        })
        
        // Безопасное преобразование данных
        let resultsData = []
        
        // Проверяем разные варианты ответа
        if (response && response.data) {
          // Вариант 1: response.data это массив
          if (Array.isArray(response.data)) {
            resultsData = response.data
          } 
          // Вариант 2: response.data это объект с data и meta
          else if (response.data.data && Array.isArray(response.data.data)) {
            resultsData = response.data.data
            meta.value = response.data.meta || meta.value
          }
          // Вариант 3: response.data это объект, который нужно преобразовать
          else if (typeof response.data === 'object') {
            resultsData = Object.values(response.data)
          }
        } else if (Array.isArray(response)) {
          // Если ответ уже массив
          resultsData = response
        }
        
        console.log('Загружено результатов:', resultsData.length, 'Данные:', resultsData)
        
        // Форматируем результаты
        results.value = resultsData.map(result => ({
          ...result,
          formatted_time: ResultService.formatTime(ResultService.parseTime(result.result_time)) || '–',
          status_text: result.status === 'confirmed' ? 'Подтвержден' : 
                      result.status === 'pending' ? 'Ожидает' : 'Дисквалифицирован',
          status_color: result.status === 'confirmed' ? 'success' : 
                       result.status === 'pending' ? 'warning' : 'danger',
          user_name: result.user_name || `Участник #${result.registration_id || result.id}`,
          bib_number: result.bib_number || '–',
          team_name: result.team_name || result.team || '–',
          category: result.category || '–',
          judge_name: result.judge_name || '–'
        }))
        
        // Если meta не установлен в response.data, пробуем из response
        if (!meta.value && response.meta) {
          meta.value = response.meta
        }
        
        updateSummary()
        
      } catch (error) {
        console.error('Ошибка загрузки результатов:', error)
        error.value = 'Не удалось загрузить результаты. Проверьте соединение.'
        results.value = []
        meta.value = {
          total: 0,
          page: 1,
          pages: 1,
          limit: 25
        }
        updateSummary()
      } finally {
        loading.value = false
      }
    }
    
    // Обновление сводной информации
    const updateSummary = () => {
      const summaryData = {
        total: 0,
        confirmed: 0,
        pending: 0,
        disqualified: 0
      }
      
      // Безопасная проверка массива
      if (!Array.isArray(results.value)) {
        console.warn('results не является массивом:', results.value)
        summary.value = summaryData
        return
      }
      
      results.value.forEach(result => {
        summaryData.total++
        if (result.status === 'confirmed') summaryData.confirmed++
        else if (result.status === 'pending') summaryData.pending++
        else if (result.status === 'disqualified') summaryData.disqualified++
      })
      
      summary.value = summaryData
    }
    
    // Пагинация
    const changePage = (page) => {
      if (page >= 1 && page <= meta.value.pages) {
        filters.value.page = page
        loadResults()
      }
    }
    
    const getPages = () => {
      if (!meta.value || meta.value.pages <= 1) return []
      
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
    
    // Дебаунс поиска
    const debouncedLoadResults = debounce(() => {
      filters.value.page = 1
      loadResults()
    }, 500)
    
    // Проверка офлайн-результатов
    const checkOfflineResults = async () => {
      try {
        const unsyncedResults = await OfflineStorage.getUnsyncedResults()
        offlineResultsCount.value = unsyncedResults.length || 0
      } catch (error) {
        console.error('Ошибка проверки офлайн-результатов:', error)
        offlineResultsCount.value = 0
      }
    }
    
    // Синхронизация офлайн-результатов
    const syncOfflineResults = async () => {
      if (!props.currentEvent) return
      
      syncing.value = true
      
      try {
        // Получаем несинхронизированные результаты
        const unsyncedResults = await OfflineStorage.getUnsyncedResults()
        
        if (unsyncedResults.length === 0) {
          showNotification('Нет результатов для синхронизации', 'warning', 'info-circle')
          return
        }
        
        // Формируем данные для синхронизации
        const syncData = {
          event_id: props.currentEvent.id,
          results: unsyncedResults.map(r => ({
            registration_id: r.registration_id,
            result_time: r.result_time,
            finish_time: r.finish_time,
            status: r.status || 'pending',
            notes: r.notes
          }))
        }
        
        // Отправляем на сервер
        const response = await ResultService.bulkSync(syncData)
        
        if (response.data && response.data.synced_count > 0) {
          // Помечаем как синхронизированные
          const localIds = unsyncedResults.map(r => r.local_id).filter(id => id)
          if (localIds.length > 0) {
            await OfflineStorage.markAsSynced(localIds)
          }
          
          // Показываем уведомление
          showNotification(
            `Синхронизировано ${response.data.synced_count} результатов`,
            'success',
            'check-circle'
          )
          
          // Обновляем список
          loadResults()
          checkOfflineResults()
          emit('sync-complete')
          
          // Очищаем синхронизированные результаты
          setTimeout(() => {
            OfflineStorage.clearSyncedResults()
          }, 1000)
          
        } else {
          showNotification('Не удалось синхронизировать результаты', 'danger', 'exclamation-triangle')
        }
        
        // Показываем ошибки, если есть
        if (response.data && response.data.errors && response.data.errors.length > 0) {
          console.error('Ошибки синхронизации:', response.data.errors)
        }
        
      } catch (error) {
        console.error('Ошибка синхронизации:', error)
        showNotification('Ошибка синхронизации', 'danger', 'exclamation-triangle')
      } finally {
        syncing.value = false
      }
    }
    
    // Показать уведомление
    const showNotification = (message, type = 'success', icon = 'check-circle') => {
      syncNotification.value = {
        show: true,
        type,
        icon,
        message
      }
      
      // Автоскрытие
      setTimeout(() => {
        syncNotification.value.show = false
      }, 5000)
    }
    
    // Редактирование результата
    const editResult = (result) => {
      editingResult.value = result
      editForm.value = {
        result_time: result.result_time || '',
        status: result.status || 'pending',
        notes: result.notes || ''
      }
      showEditModal.value = true
    }
    
    const closeEditModal = () => {
      showEditModal.value = false
      editingResult.value = null
      editForm.value = {
        result_time: '',
        status: 'pending',
        notes: ''
      }
      editing.value = false
    }
    
    const saveResult = async () => {
      if (!editingResult.value || !editForm.value.result_time) return
      
      editing.value = true
      
      try {
        await ResultService.update(editingResult.value.id, editForm.value)
        
        // Обновляем локальные данные
        const index = results.value.findIndex(r => r.id === editingResult.value.id)
        if (index !== -1) {
          results.value[index] = {
            ...results.value[index],
            result_time: editForm.value.result_time,
            formatted_time: ResultService.formatTime(ResultService.parseTime(editForm.value.result_time)),
            status: editForm.value.status,
            status_text: editForm.value.status === 'confirmed' ? 'Подтвержден' : 
                        editForm.value.status === 'pending' ? 'Ожидает' : 'Дисквалифицирован',
            status_color: editForm.value.status === 'confirmed' ? 'success' : 
                         editForm.value.status === 'pending' ? 'warning' : 'danger',
            notes: editForm.value.notes
          }
        }
        
        showNotification('Результат обновлен', 'success', 'check-circle')
        closeEditModal()
        updateSummary()
        
      } catch (error) {
        console.error('Ошибка обновления результата:', error)
        showNotification('Ошибка обновления результата', 'danger', 'exclamation-triangle')
      } finally {
        editing.value = false
      }
    }
    
    // Удаление результата
    const deleteResult = async (result) => {
      if (!confirm(`Удалить результат участника "${result.user_name}"?`)) return
      
      try {
        await ResultService.delete(result.id)
        
        // Удаляем из локального списка
        results.value = results.value.filter(r => r.id !== result.id)
        selectedResults.value = selectedResults.value.filter(id => id !== result.id)
        
        showNotification('Результат удален', 'success', 'check-circle')
        updateSummary()
        
      } catch (error) {
        console.error('Ошибка удаления результата:', error)
        showNotification('Ошибка удаления результата', 'danger', 'exclamation-triangle')
      }
    }
    
    // Массовые действия
    const bulkUpdateStatus = async (status) => {
      if (selectedResults.value.length === 0) return
      
      const statusText = status === 'confirmed' ? 'подтвердить' : 
                        status === 'disqualified' ? 'дисквалифицировать' : 'изменить'
      
      if (!confirm(`${statusText} выбранные результаты (${selectedResults.value.length})?`)) return
      
      try {
        const promises = selectedResults.value.map(id => 
          ResultService.update(id, { status })
        )
        
        await Promise.all(promises)
        
        // Обновляем локальные данные
        results.value = results.value.map(result => {
          if (selectedResults.value.includes(result.id)) {
            return {
              ...result,
              status,
              status_text: status === 'confirmed' ? 'Подтвержден' : 
                          status === 'pending' ? 'Ожидает' : 'Дисквалифицирован',
              status_color: status === 'confirmed' ? 'success' : 
                           status === 'pending' ? 'warning' : 'danger'
            }
          }
          return result
        })
        
        showNotification(`Статус обновлен для ${selectedResults.value.length} результатов`, 'success', 'check-circle')
        selectedResults.value = []
        updateSummary()
        
      } catch (error) {
        console.error('Ошибка массового обновления:', error)
        showNotification('Ошибка обновления статусов', 'danger', 'exclamation-triangle')
      }
    }
    
    const bulkDeleteResults = async () => {
      if (selectedResults.value.length === 0) return
      
      if (!confirm(`Удалить выбранные результаты (${selectedResults.value.length})?`)) return
      
      try {
        const promises = selectedResults.value.map(id => 
          ResultService.delete(id).catch(err => {
            console.error(`Ошибка удаления результата ${id}:`, err)
            return null
          })
        )
        
        await Promise.all(promises)
        
        // Удаляем из локального списка
        results.value = results.value.filter(r => !selectedResults.value.includes(r.id))
        
        showNotification(`Удалено ${selectedResults.value.length} результатов`, 'success', 'check-circle')
        selectedResults.value = []
        updateSummary()
        
      } catch (error) {
        console.error('Ошибка массового удаления:', error)
        showNotification('Ошибка удаления результатов', 'danger', 'exclamation-triangle')
      }
    }
    
    // Экспорт результатов
    const exportResults = () => {
      if (!props.currentEvent || results.value.length === 0) return
      
      const eventName = props.currentEvent.title.replace(/[^a-z0-9]/gi, '_').toLowerCase()
      const date = new Date().toISOString().slice(0, 10)
      const filename = `results_${eventName}_${date}.csv`
      
      // Заголовки CSV
      const headers = ['Место', 'Старт №', 'Участник', 'Команда', 'Категория', 'Результат', 'Статус']
      
      // Данные
      const csvData = results.value.map((result, index) => [
        calculatePlace(index),
        result.bib_number,
        `"${result.user_name}"`,
        `"${result.team_name || ''}"`,
        `"${result.category || ''}"`,
        result.formatted_time,
        result.status_text
      ])
      
      // Создаем CSV
      const csvContent = [
        headers.join(','),
        ...csvData.map(row => row.join(','))
      ].join('\n')
      
      // Создаем Blob и ссылку для скачивания
      const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' })
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', filename)
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      
      showNotification(`Экспортировано ${results.value.length} результатов`, 'success', 'download')
    }
    
    // Инициализация
    onMounted(() => {
      if (!props.currentEvent) {
        loadEvents()
      } else {
        loadResults()
      }
      
      checkOfflineResults()
      
      // Слушатель для принудительной синхронизации
      window.addEventListener('force-sync', syncOfflineResults)
    })
    
    // Наблюдатель за изменением мероприятия
    watch(() => props.currentEvent, (newEvent) => {
      if (newEvent) {
        filters.value.page = 1
        loadResults()
        checkOfflineResults()
      } else {
        results.value = []
        meta.value = {
          total: 0,
          page: 1,
          pages: 1,
          limit: 25
        }
        summary.value = { total: 0, confirmed: 0, pending: 0, disqualified: 0 }
      }
    })
    
    // Очистка
    onUnmounted(() => {
      window.removeEventListener('force-sync', syncOfflineResults)
    })
    
    return {
      // Data
      events,
      eventsLoading,
      results,
      meta,
      loading,
      error,
      selectedResults,
      offlineResultsCount,
      syncing,
      filters,
      summary,
      showEditModal,
      editingResult,
      editing,
      editForm,
      syncNotification,
      
      // Computed
      selectAll,
      
      // Methods
      formatDate,
      formatDateTime,
      formatFinishTime,
      calculatePlace,
      selectEvent,
      loadResults,
      changePage,
      getPages,
      debouncedLoadResults,
      syncOfflineResults,
      editResult,
      closeEditModal,
      saveResult,
      deleteResult,
      bulkUpdateStatus,
      bulkDeleteResults,
      exportResults
    }
  }
}
</script>

<style scoped>
.results-input {
  min-height: calc(100vh - 120px);
}

/* Заголовок */
.page-header {
  padding: 1rem 0;
  border-bottom: 1px solid #dee2e6;
}

/* Сводные карточки */
.summary-cards {
  margin-top: 1rem;
}

.summary-card {
  border-radius: 10px;
  padding: 1.5rem;
  color: white;
  display: flex;
  align-items: center;
  box-shadow: 0 3px 6px rgba(0,0,0,0.1);
}

.summary-card.bg-primary { background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); }
.summary-card.bg-success { background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); }
.summary-card.bg-warning { background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); }
.summary-card.bg-danger { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); }

.summary-icon {
  font-size: 2.5rem;
  margin-right: 1.5rem;
  opacity: 0.9;
}

.summary-content {
  flex-grow: 1;
}

.summary-value {
  font-size: 2.2rem;
  font-weight: bold;
  line-height: 1;
  margin-bottom: 0.5rem;
}

.summary-label {
  font-size: 0.9rem;
  opacity: 0.9;
}

/* Таблица */
.table th {
  font-weight: 600;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #6c757d;
  background-color: #f8f9fa;
}

.table td {
  vertical-align: middle;
}

.result-time-cell {
  min-width: 150px;
}

/* Информационная карточка в модалке */
.info-card {
  background-color: #f8f9fa;
  border-radius: 8px;
  padding: 1rem;
}

.info-item {
  margin-bottom: 0.5rem;
}

.info-item:last-child {
  margin-bottom: 0;
}

.info-item label {
  display: block;
  font-size: 0.8rem;
  color: #6c757d;
  margin-bottom: 0.25rem;
}

.info-item .info-value {
  font-weight: 500;
  color: #2c3e50;
}

/* Уведомление о синхронизации */
.sync-notification {
  z-index: 1060;
}

.sync-notification .alert {
  border-radius: 10px;
  border: none;
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

/* Адаптивность */
@media (max-width: 1200px) {
  .table-responsive {
    font-size: 0.9rem;
  }
}

@media (max-width: 992px) {
  .summary-card {
    flex-direction: column;
    text-align: center;
    padding: 1rem;
  }
  
  .summary-icon {
    margin-right: 0;
    margin-bottom: 1rem;
    font-size: 2rem;
  }
  
  .summary-value {
    font-size: 1.8rem;
  }
}

@media (max-width: 768px) {
  .page-header .d-flex {
    flex-direction: column;
    align-items: flex-start !important;
  }
  
  .page-header .d-flex > div:last-child {
    margin-top: 1rem;
  }
  
  .table th, .table td {
    white-space: nowrap;
  }
  
  .result-time-cell {
    min-width: auto;
  }
}

@media (max-width: 576px) {
  .summary-cards .row {
    margin: -0.5rem;
  }
  
  .summary-cards .col-md-3 {
    padding: 0.5rem;
  }
  
  .summary-card {
    padding: 1rem 0.5rem;
  }
  
  .summary-value {
    font-size: 1.5rem;
  }
}
</style>