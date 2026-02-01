<template>
  <div class="event-list">
    <!-- Состояние загрузки -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Загрузка...</span>
      </div>
      <p class="mt-3 text-muted small">Загрузка мероприятий...</p>
    </div>

    <!-- Ошибка -->
    <div v-else-if="error" class="alert alert-danger">
      <i class="bi bi-exclamation-triangle me-2"></i>
      {{ error }}
      <button class="btn btn-sm btn-outline-danger ms-3" @click="loadEvents">
        Повторить
      </button>
    </div>

    <!-- Контент -->
    <div v-else>
      <!-- Режим таблицы для админки -->
      <template v-if="isAdmin && compact">
        <div class="card border-0 shadow-sm">
          <!-- Панель управления вверху -->
          <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex justify-content-between align-items-center">
              <div class="d-flex gap-2">
                <!-- Кнопка добавления -->
                <button class="btn btn-primary btn-sm" @click="openCreateModal" :disabled="loading"
                  title="Добавить мероприятие">
                  <i class="bi bi-plus-circle me-1"></i>Добавить
                </button>

                <!-- Кнопка редактирования (только если выбран один элемент) -->
                <button class="btn btn-outline-primary btn-sm" @click="editSelectedEvent"
                  :disabled="selectedEvents.length !== 1" title="Редактировать выбранное">
                  <i class="bi bi-pencil me-1"></i>Редактировать
                </button>

                <!-- Кнопка удаления (если выбраны элементы) -->
                <button class="btn btn-outline-danger btn-sm" @click="deleteSelectedEvents"
                  :disabled="selectedEvents.length === 0" title="Удалить выбранные">
                  <i class="bi bi-trash me-1"></i>Удалить
                </button>

                <!-- Счетчик выбранных -->
                <span v-if="selectedEvents.length > 0" class="badge bg-primary align-self-center ms-2">
                  Выбрано: {{ selectedEvents.length }}
                </span>
              </div>

              <!-- Фильтры справа -->
              <div class="d-flex gap-2 align-items-center">
                <div class="input-group input-group-sm" style="width: 200px;">
                  <span class="input-group-text">
                    <i class="bi bi-search"></i>
                  </span>
                  <input v-model="filters.search" type="text" class="form-control" placeholder="Поиск..."
                    @input="debouncedSearch">
                </div>

                <select v-model="filters.status" class="form-select form-select-sm" style="width: 140px;"
                  @change="loadEvents">
                  <option value="">Все статусы</option>
                  <option value="upcoming">Предстоящие</option>
                  <option value="active">Активные</option>
                  <option value="completed">Завершенные</option>
                  <option value="draft">Черновики</option>
                </select>

                <button class="btn btn-sm btn-outline-secondary" @click="loadEvents" :disabled="loading"
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
                  <th style="width: 50px;">
                    <input type="checkbox" class="form-check-input" v-model="selectAll" :disabled="events.length === 0">
                  </th>
                  <th>Название</th>
                  <th style="width: 100px;">Дата</th>
                  <th style="width: 100px;">Статус</th>
                  <th style="width: 120px;">Участники</th>
                  <th style="width: 150px;">Дисциплина</th>
                  <th style="width: 100px;" class="text-end">Быстрые действия</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="event in events" :key="event.id" @dblclick="editEvent(event)" class="cursor-pointer"
                  :class="{ 'table-primary': selectedEvents.includes(event.id) }">
                  <td @click.stop>
                    <input type="checkbox" class="form-check-input" :value="event.id" v-model="selectedEvents">
                  </td>
                  <td @click="toggleEventSelection(event)">
                    <div class="d-flex align-items-center">
                      <div class="flex-shrink-0">
                        <i class="bi bi-calendar-event text-primary"></i>
                      </div>
                      <div class="flex-grow-1 ms-3">
                        <div class="fw-semibold">{{ event.title }}</div>
                        <small class="text-muted">{{ event.location }}</small>
                      </div>
                    </div>
                  </td>
                  <td @click="toggleEventSelection(event)">
                    <small>{{ formatDateShort(event.date) }}</small>
                  </td>
                  <td @click="toggleEventSelection(event)">
                    <span :class="`badge bg-${getStatusColor(event.status)}`">
                      {{ getStatusText(event.status) }}
                    </span>
                  </td>
                  <td @click="toggleEventSelection(event)">
                    <div class="d-flex align-items-center">
                      <i class="bi bi-people text-muted me-2"></i>
                      <div>
                        <div class="small">{{ event.participants_count }}/{{ event.max_participants || '∞' }}</div>
                        <div class="text-muted small">{{ event.registrations_count }} заявок</div>
                      </div>
                    </div>
                  </td>
                  <td @click="toggleEventSelection(event)">
                    <small>{{ event.discipline }}</small>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <button class="btn btn-outline-success" @click.stop="manageRegistrations(event)"
                        title="Управление заявками" v-tooltip>
                        <i class="bi bi-clipboard-check"></i>
                      </button>
                      <button class="btn btn-outline-info" @click.stop="viewProtocols(event)" title="Протоколы"
                        v-tooltip>
                        <i class="bi bi-file-text"></i>
                      </button>
                      <button class="btn btn-outline-warning" @click.stop="changeStatus(event)" title="Изменить статус"
                        v-tooltip v-if="event.status !== 'completed'">
                        <i class="bi bi-arrow-right-circle"></i>
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
                Показано {{ events.length }} из {{ meta.total }} мероприятий
                <span v-if="selectedEvents.length > 0" class="ms-3">
                  • Выбрано: {{ selectedEvents.length }}
                </span>
              </div>
              <nav>
                <ul class="pagination pagination-sm mb-0">
                  <li class="page-item" :class="{ disabled: meta.page === 1 }">
                    <button class="page-link" @click="changePage(meta.page - 1)" :disabled="loading || meta.page === 1">
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

          <!-- Сообщение при отсутствии мероприятий -->
          <div v-if="events.length === 0" class="text-center py-5">
            <i class="bi bi-calendar-x fs-1 text-muted mb-3"></i>
            <h5>Мероприятия не найдены</h5>
            <p class="text-muted">
              {{ filters.search ? 'Попробуйте изменить условия поиска' : 'Нет доступных мероприятий' }}
            </p>
            <button class="btn btn-primary" @click="openCreateModal">
              Создать первое мероприятие
            </button>
          </div>
        </div>
      </template>






      <!-- Режим карточек для главной страницы   -->
      <template v-else>
        <!-- Фильтры для публичного раздела -->
        <div class="row mb-4">
          <div class="col-md-8">
            <div class="input-group">
              <span class="input-group-text">
                <i class="bi bi-search"></i>
              </span>
              <input v-model="filters.search" type="text" class="form-control" placeholder="Поиск по названию, месту..."
                @input="debouncedSearch">
            </div>
          </div>
          <div class="col-md-4">
            <select v-model="filters.status" class="form-select" @change="loadEvents">
              <option value="">Все статусы</option>
              <option value="upcoming">Предстоящие</option>
              <option value="active">Активные</option>
              <option value="completed">Завершенные</option>
            </select>
          </div>
        </div>

        <!-- Карточки мероприятий -->
        <div class="row" v-if="events.length > 0">
          <div v-for="event in events" :key="event.id" class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100 card-hover border-0 shadow-sm">
              <!-- Статус мероприятия -->
              <div class="card-header bg-white border-bottom-0 pt-3">
                <div class="d-flex justify-content-between align-items-center">
                  <span :class="`badge bg-${getStatusColor(event.status)}`">
                    {{ getStatusText(event.status) }}
                  </span>
                  <small class="text-muted">{{ formatDate(event.date) }}</small>
                </div>
              </div>

              <div class="card-body">
                <h6 class="card-title fw-bold mb-2">{{ event.title }}</h6>
                <p class="card-text small text-muted mb-3">{{ event.description }}</p>

                <div class="d-flex align-items-center mb-3">
                  <i class="bi bi-geo-alt text-muted me-2"></i>
                  <small>{{ event.location }}</small>
                </div>

                <div class="d-flex align-items-center mb-3">
                  <i class="bi bi-flag text-muted me-2"></i>
                  <small>{{ event.discipline }}</small>
                </div>

                <!-- Информация о заявках -->
                <div class="d-flex justify-content-between small text-muted mb-3">
                  <span>
                    <i class="bi bi-people me-1"></i>
                    {{ event.participants_count }} участников
                  </span>
                  <span>
                    <i class="bi bi-file-text me-1"></i>
                    {{ event.registrations_count }} заявок
                  </span>
                </div>

                <!-- Кнопки действий -->
                <div class="d-grid gap-2">
                  <button class="btn btn-outline-primary btn-sm" @click="viewEvent(event)">
                    Подробнее
                  </button>

                  <!-- Кнопка регистрации для участников -->
                  <button v-if="(event.status === 'upcoming' || event.status === 'active') && !isPublic"
                    class="btn btn-success btn-sm" @click="registerForEvent(event)">
                    <i class="bi bi-pencil-square me-1"></i>
                    Подать заявку
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Сообщение при отсутствии мероприятий -->
        <div v-else class="text-center py-5">
          <i class="bi bi-calendar-x fs-1 text-muted mb-3"></i>
          <h5>Мероприятия не найдены</h5>
          <p class="text-muted">
            {{ filters.search ? 'Попробуйте изменить условия поиска' : 'Нет доступных мероприятий' }}
          </p>
        </div>

        <!-- Пагинация для публичного раздела -->
        <div v-if="meta && meta.pages > 1" class="row mt-4">
          <div class="col">
            <nav>
              <ul class="pagination justify-content-center">
                <li class="page-item" :class="{ disabled: meta.page === 1 }">
                  <button class="page-link" @click="changePage(meta.page - 1)" :disabled="loading || meta.page === 1">
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
      </template>
    </div>

    <!-- Модальное окно (только для админа) -->
    <EventModal v-if="showModal && isAdmin" :event="editingEvent" :loading="modalLoading" @save="saveEvent"
      @close="closeModal" />

      <!-- Модальное окно карточки события (только для публичного раздела) -->
      <EventViewModal
      v-if="isPublic && showEventModal"
      :show="showEventModal"
      :event="selectedEvent"
      @close="closeEventModal"
      @register="onEventRegister"
      @view-results="onViewResults"
    />

  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import { debounce } from 'lodash-es'
import EventService from '@/services/event.service'
import EventModal from '@/components/modals/EventModal.vue'

import EventViewModal from '@/components/modals/EventViewModal.vue'

// import { useRouter } from 'vue-router'

export default {
  name: 'EventList',

  components: {
    EventModal,
    EventViewModal
  },

  props: {
    mode: {
      type: String,
      default: 'public',
      validator: (value) => ['admin', 'personal', 'public'].includes(value)
    },
    compact: {
      type: Boolean,
      default: false
    },
    limit: {
      type: Number,
      default: null
    },
    autoLoad: {
      type: Boolean,
      default: true
    }
  },

  emits: [
    'event-view',
    'event-register',
    'event-edit',
    'event-delete',
    'registrations-manage',
    'protocols-view',
    'create-event',
    'loaded'
  ],

  setup(props, { emit }) {
    // Состояние
    const events = ref([])
    const meta = ref(null)
    const loading = ref(false)
    const error = ref(null)
  //  Для карточки события. 
    const showEventModal = ref(false)
    const selectedEvent = ref(null)

    // const router = useRouter()

    // Выбранные мероприятия (для админки)
    const selectedEvents = ref([])
    const selectAll = computed({
      get: () => events.value.length > 0 && selectedEvents.value.length === events.value.length,
      set: (value) => {
        if (value) {
          selectedEvents.value = events.value.map(event => event.id)
        } else {
          selectedEvents.value = []
        }
      }
    })

    // Фильтры
    const filters = ref({
      search: '',
      status: '',
      page: 1,
      limit: props.limit || (props.compact ? 10 : 6)
    })

    // Модальное окно
    const showModal = ref(false)
    const editingEvent = ref(null)
    const modalLoading = ref(false)

    // Вычисляемые свойства
    const isAdmin = computed(() => props.mode === 'admin')
    const isPublic = computed(() => props.mode === 'public')

    // Методы
    const loadEvents = async () => {
      if (!props.autoLoad) return

      loading.value = true
      error.value = null
      selectedEvents.value = [] // Сбрасываем выбор

      try {
        const params = { ...filters.value }
        if (props.limit) {
          params.limit = props.limit
        }

        const response = await EventService.getAll(params)
        events.value = response.data
        meta.value = response.meta

        emit('loaded', { events: events.value, meta: meta.value })
      } catch (err) {
        error.value = err.message || 'Ошибка загрузки мероприятий'
        console.error('Ошибка загрузки мероприятий:', err)
      } finally {
        loading.value = false
      }
    }

    const changePage = (page) => {
      if (page >= 1 && page <= meta.value.pages) {
        filters.value.page = page
        loadEvents()
      }
    }

    const getPages = () => {
      if (!meta.value) return []

      const pages = []
      const current = meta.value.page
      const total = meta.value.pages

      // Всегда показываем первую страницу
      pages.push(1)

      // Диапазон вокруг текущей страницы
      const start = Math.max(2, current - 1)
      const end = Math.min(total - 1, current + 1)

      // Добавляем многоточие если нужно
      if (start > 2) pages.push('...')

      // Добавляем средние страницы
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }

      // Добавляем многоточие если нужно
      if (end < total - 1) pages.push('...')

      // Всегда показываем последнюю страницу если она не первая
      if (total > 1) pages.push(total)

      return pages
    }

    const debouncedSearch = debounce(() => {
      filters.value.page = 1
      loadEvents()
    }, 500)

    const formatDate = (dateString) => {
      const options = { day: 'numeric', month: 'long', year: 'numeric' }
      return new Date(dateString).toLocaleDateString('ru-RU', options)
    }

    const formatDateShort = (dateString) => {
      const options = { day: 'numeric', month: 'short' }
      return new Date(dateString).toLocaleDateString('ru-RU', options)
    }

    const getStatusColor = (status) => {
      const colors = {
        'draft': 'secondary',
        'upcoming': 'primary',
        'active': 'success',
        'completed': 'warning'
      }
      return colors[status] || 'secondary'
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

    // Новые методы для работы с выбранными элементами
    const toggleEventSelection = (event) => {
      const index = selectedEvents.value.indexOf(event.id)
      if (index === -1) {
        selectedEvents.value.push(event.id)
      } else {
        selectedEvents.value.splice(index, 1)
      }
    }

    const editSelectedEvent = () => {
      if (selectedEvents.value.length === 1) {
        const eventId = selectedEvents.value[0]
        const event = events.value.find(e => e.id === eventId)
        if (event) {
          editEvent(event)
        }
      }
    }

    const deleteSelectedEvents = () => {
      if (selectedEvents.value.length === 0) return

      const count = selectedEvents.value.length
      const eventTitles = events.value
        .filter(e => selectedEvents.value.includes(e.id))
        .map(e => `"${e.title}"`)
        .join(', ')

      if (confirm(`Удалить выбранные мероприятия (${count})?\n${eventTitles}`)) {
        // Удаляем выбранные мероприятия
        selectedEvents.value.forEach(eventId => {
          const event = events.value.find(e => e.id === eventId)
          if (event) {
            emit('event-delete', event)
          }
        })

        // Очищаем выбор после удаления
        selectedEvents.value = []
      }
    }

    // Обработчики событий





    const viewEvent = (event) => {
      if (isPublic.value) {
        // Для публичного раздела - открываем модалку
        selectedEvent.value = event
        showEventModal.value = true
      } else {
        // Для админки и личного кабинета - emit
        emit('event-view', event)
      }
    }

    const closeEventModal = () => {
      showEventModal.value = false
      selectedEvent.value = null
    }
    
    const onEventRegister = (event) => {
      console.log('Регистрация на мероприятие:', event)
      // Здесь можно открыть другую модалку для регистрации
      emit('event-register', event)
    }
    
    const onViewResults = (event) => {
      console.log('Просмотр результатов:', event)
      emit('view-results', event)
    }









    const registerForEvent = (event) => {
      emit('event-register', event)
    }

    const editEvent = (event) => {
      editingEvent.value = event
      showModal.value = true
      emit('event-edit', event)
    }

    const deleteEvent = (event) => {
      if (confirm(`Удалить мероприятие "${event.title}"?`)) {
        emit('event-delete', event)
      }
    }

    const manageRegistrations = (event) => {
      emit('registrations-manage', event)
    }

    const viewProtocols = (event) => {
      emit('protocols-view', event)
    }

    const changeStatus = (event) => {
      // Определяем следующий статус
      const statusFlow = {
        'draft': 'upcoming',
        'upcoming': 'active',
        'active': 'completed',
        'completed': 'completed'
      }

      const nextStatus = statusFlow[event.status]
      if (nextStatus && confirm(`Изменить статус мероприятия "${event.title}" на "${getStatusText(nextStatus)}"?`)) {
        console.log('Смена статуса мероприятия:', event.id, 'на', nextStatus)
        // В будущем - вызов API для обновления статуса
      }
    }

    const openCreateModal = () => {
      editingEvent.value = null
      showModal.value = true
      emit('create-event')
    }

    const closeModal = () => {
      showModal.value = false
      editingEvent.value = null
      modalLoading.value = false
    }

    const saveEvent = async (eventData) => {
      modalLoading.value = true

      try {
        if (editingEvent.value) {
          await EventService.update(editingEvent.value.id, eventData)
        } else {
          await EventService.create(eventData)
        }

        closeModal()
        loadEvents() // Перезагружаем список
      } catch (err) {
        error.value = err.message || 'Ошибка сохранения мероприятия'
        console.error('Ошибка сохранения:', err)
      } finally {
        modalLoading.value = false
      }
    }

    // Хуки жизненного цикла
    onMounted(() => {
      if (props.autoLoad) {
        loadEvents()
      }
    })

    // Наблюдатели
    watch(() => props.limit, (newLimit) => {
      if (newLimit) {
        filters.value.limit = newLimit
        loadEvents()
      }
    })

    return {
      // Данные
      events,
      meta,
      loading,
      error,
      selectedEvents,
      selectAll,
      filters,
      showModal,
      editingEvent,
      modalLoading,

      // Вычисляемые свойства
      isAdmin,
      isPublic,

      // Методы
      loadEvents,
      changePage,
      getPages,
      debouncedSearch,
      formatDate,
      formatDateShort,
      getStatusColor,
      getStatusText,
      toggleEventSelection,
      editSelectedEvent,
      deleteSelectedEvents,
      
      registerForEvent,
      editEvent,
      deleteEvent,
      manageRegistrations,
      viewProtocols,
      changeStatus,
      openCreateModal,
      closeModal,
      saveEvent,

      showEventModal,
      selectedEvent,
      viewEvent,
      closeEventModal,
      onEventRegister,
      onViewResults
    }
  }
}
</script>

<style scoped>
.event-list {
  min-height: 400px;
}

.card {
  border-radius: 12px;
  transition: all 0.2s ease;
}

.card-hover:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1) !important;
}

.card-title {
  font-size: 1.1rem;
  line-height: 1.4;
  display: -webkit-box;
  /* -webkit-line-clamp: 2; */
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-text {
  display: -webkit-box;
  /* -webkit-line-clamp: 3; */
  -webkit-box-orient: vertical;
  overflow: hidden;
  min-height: 60px;
}

.page-link {
  cursor: pointer;
}

/* Стили для таблицы */
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

.table-hover tbody tr:hover {
  background-color: rgba(13, 110, 253, 0.05);
}

/* Стили для выбранных строк */
.table-primary {
  background-color: rgba(13, 110, 253, 0.1) !important;
}

/* Кликабельные строки */
.cursor-pointer {
  cursor: pointer;
}

.cursor-pointer:hover {
  background-color: rgba(0, 0, 0, 0.02);
}

/* Компактные кнопки в панели управления */
.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}

/* Стили для инструментов */
[v-tooltip] {
  position: relative;
}

[v-tooltip]::before {
  content: attr(title);
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  padding: 4px 8px;
  background-color: #333;
  color: white;
  border-radius: 4px;
  font-size: 0.75rem;
  white-space: nowrap;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.2s;
  z-index: 1000;
}

[v-tooltip]:hover::before {
  opacity: 1;
  visibility: visible;
}

/* Адаптивность */
@media (max-width: 768px) {
  .card-header .d-flex {
    flex-direction: column;
    gap: 10px;
    align-items: stretch !important;
  }

  .card-header .d-flex>div {
    width: 100%;
  }

  .table-responsive {
    font-size: 0.875rem;
  }

  .btn-group {
    flex-wrap: wrap;
  }

  .btn-group .btn {
    margin-bottom: 2px;
  }
}

@media (max-width: 992px) {
  .card-header .d-flex {
    flex-wrap: wrap;
    gap: 10px;
  }
}
</style>