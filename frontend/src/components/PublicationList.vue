<template>
  <div class="publication-list">
    <!-- Состояние загрузки -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Загрузка...</span>
      </div>
      <p class="mt-3 text-muted small">Загрузка публикаций...</p>
    </div>

    <!-- Ошибка -->
    <div v-else-if="error" class="alert alert-danger">
      <i class="bi bi-exclamation-triangle me-2"></i>
      {{ error }}
      <button class="btn btn-sm btn-outline-danger ms-3" @click="loadPublications">
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
                  title="Добавить публикацию">
                  <i class="bi bi-plus-circle me-1"></i>Добавить
                </button>

                <!-- Кнопка редактирования (только если выбран один элемент) -->
                <button class="btn btn-outline-primary btn-sm" @click="editSelectedPublication"
                  :disabled="selectedPublications.length !== 1" title="Редактировать выбранное">
                  <i class="bi bi-pencil me-1"></i>Редактировать
                </button>

                <!-- Кнопка удаления (если выбраны элементы) -->
                <button class="btn btn-outline-danger btn-sm" @click="deleteSelectedPublications"
                  :disabled="selectedPublications.length === 0" title="Удалить выбранные">
                  <i class="bi bi-trash me-1"></i>Удалить
                </button>

                <!-- Счетчик выбранных -->
                <span v-if="selectedPublications.length > 0" class="badge bg-primary align-self-center ms-2">
                  Выбрано: {{ selectedPublications.length }}
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

                <select v-model="filters.type" class="form-select form-select-sm" style="width: 140px;"
                  @change="loadPublications">
                  <option value="">Все типы</option>
                  <option value="news">Новости</option>
                  <option value="announcement">Анонсы</option>
                  <option value="article">Статьи</option>
                  <option value="page">Страницы</option>
                </select>

                <select v-model="filters.status" class="form-select form-select-sm" style="width: 140px;"
                  @change="loadPublications">
                  <option value="">Все статусы</option>
                  <option value="draft">Черновики</option>
                  <option value="published">Опубликовано</option>
                  <option value="archived">В архиве</option>
                </select>

                <button class="btn btn-sm btn-outline-secondary" @click="loadPublications" :disabled="loading"
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
                    <input type="checkbox" class="form-check-input" v-model="selectAll" :disabled="publications.length === 0">
                  </th>
                  <th>Заголовок</th>
                  <th style="width: 100px;">Тип</th>
                  <th style="width: 100px;">Статус</th>
                  <th style="width: 150px;">Автор</th>
                  <th style="width: 120px;">Дата</th>
                  <th style="width: 100px;">Просмотры</th>
                  <th style="width: 100px;" class="text-end">Действия</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="publication in publications" :key="publication.id" @dblclick="editPublication(publication)" class="cursor-pointer"
                  :class="{ 'table-primary': selectedPublications.includes(publication.id) }">
                  <td @click.stop>
                    <input type="checkbox" class="form-check-input" :value="publication.id" v-model="selectedPublications">
                  </td>
                  <td @click="togglePublicationSelection(publication)">
                    <div class="d-flex align-items-center">
                      <div class="flex-shrink-0">
                        <i :class="`bi ${getTypeIcon(publication.type)} text-${getTypeColor(publication.type)}`"></i>
                      </div>
                      <div class="flex-grow-1 ms-3">
                        <div class="fw-semibold">{{ publication.title }}</div>
                        <small class="text-muted">{{ publication.excerpt ? truncate(publication.excerpt, 60) : 'Нет описания' }}</small>
                      </div>
                      <span v-if="publication.is_pinned" class="badge bg-warning ms-2">
                        <i class="bi bi-pin-angle"></i>
                      </span>
                    </div>
                  </td>
                  <td @click="togglePublicationSelection(publication)">
                    <span :class="`badge bg-${getTypeColor(publication.type)}`">
                      {{ getTypeText(publication.type) }}
                    </span>
                  </td>
                  <td @click="togglePublicationSelection(publication)">
                    <span :class="`badge bg-${getStatusColor(publication.status)}`">
                      {{ getStatusText(publication.status) }}
                    </span>
                  </td>
                  <td @click="togglePublicationSelection(publication)">
                    <small>{{ publication.author_name || 'Не указан' }}</small>
                  </td>
                  <td @click="togglePublicationSelection(publication)">
                    <small>{{ formatDateShort(publication.created_at) }}</small>
                    <div v-if="publication.published_at" class="text-muted extra-small">
                      {{ formatDateShort(publication.published_at) }}
                    </div>
                  </td>
                  <td @click="togglePublicationSelection(publication)">
                    <div class="d-flex align-items-center">
                      <i class="bi bi-eye text-muted me-2"></i>
                      <span>{{ publication.views_count || 0 }}</span>
                    </div>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <button class="btn btn-outline-info" @click.stop="viewPublication(publication)" title="Просмотр"
                        v-tooltip>
                        <i class="bi bi-eye"></i>
                      </button>
                      <button class="btn btn-outline-warning" @click.stop="togglePin(publication)" title="Закрепить/Открепить"
                        v-tooltip>
                        <i :class="`bi ${publication.is_pinned ? 'bi-pin-fill' : 'bi-pin'}`"></i>
                      </button>
                      <button class="btn btn-outline-success" @click.stop="changeStatus(publication, 'published')" 
                        title="Опубликовать" v-tooltip v-if="publication.status !== 'published'">
                        <i class="bi bi-send"></i>
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
                Показано {{ publications.length }} из {{ meta.total }} публикаций
                <span v-if="selectedPublications.length > 0" class="ms-3">
                  • Выбрано: {{ selectedPublications.length }}
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

          <!-- Сообщение при отсутствии публикаций -->
          <div v-if="publications.length === 0" class="text-center py-5">
            <i class="bi bi-newspaper fs-1 text-muted mb-3"></i>
            <h5>Публикации не найдены</h5>
            <p class="text-muted">
              {{ filters.search ? 'Попробуйте изменить условия поиска' : 'Нет доступных публикаций' }}
            </p>
            <button class="btn btn-primary" @click="openCreateModal">
              Создать первую публикацию
            </button>
          </div>
        </div>
      </template>

      <!-- Режим карточек для публичного раздела -->
      <template v-else>
        <!-- Фильтры для публичного раздела -->
        <div class="row mb-4" v-if="showFilters">
          <div class="col-md-6">
            <div class="input-group">
              <span class="input-group-text">
                <i class="bi bi-search"></i>
              </span>
              <input v-model="filters.search" type="text" class="form-control" placeholder="Поиск по заголовку, содержанию..."
                @input="debouncedSearch">
            </div>
          </div>
          <div class="col-md-3">
            <select v-model="filters.type" class="form-select" @change="loadPublications">
              <option value="">Все типы</option>
              <option value="news">Новости</option>
              <option value="announcement">Анонсы</option>
              <option value="article">Статьи</option>
            </select>
          </div>
          <div class="col-md-3">
            <select v-model="filters.sort" class="form-select" @change="loadPublications">
              <option value="published_at:desc">Сначала новые</option>
              <option value="published_at:asc">Сначала старые</option>
              <option value="views_count:desc">Популярные</option>
              <option value="title:asc">По алфавиту</option>
            </select>
          </div>
        </div>

        <!-- Карточки публикаций -->
        <div class="row" v-if="publications.length > 0">
          <div v-for="publication in publications" :key="publication.id" 
            :class="cardClass"
            class="mb-4">
            <div class="card h-100 card-hover border-0 shadow-sm">
              <!-- Закрепленная публикация -->
              <div v-if="publication.is_pinned" class="position-absolute top-0 start-0 m-2">
                <span class="badge bg-warning">
                  <i class="bi bi-pin-angle"></i> Закреплено
                </span>
              </div>

              <!-- Статус публикации -->
              <div class="card-header bg-white border-bottom-0 pt-3 pb-2">
                <div class="d-flex justify-content-between align-items-center">
                  <span :class="`badge bg-${getTypeColor(publication.type)}`">
                    {{ getTypeText(publication.type) }}
                  </span>
                  <small class="text-muted">{{ formatDate(publication.published_at || publication.created_at) }}</small>
                </div>
              </div>

              <div class="card-body">
                <h6 class="card-title fw-bold mb-2">
                  {{ publication.title }}
                  <span v-if="!publication.is_published" class="badge bg-secondary ms-2">Черновик</span>
                </h6>
                
                <p class="card-text small text-muted mb-3" v-if="publication.excerpt">
                  {{ truncate(publication.excerpt, 150) }}
                </p>
                <p class="card-text small text-muted mb-3" v-else>
                  {{ truncate(publication.content, 150) }}
                </p>

                <div class="d-flex align-items-center mb-3">
                  <i class="bi bi-person-circle text-muted me-2"></i>
                  <small>{{ publication.author_name || 'Автор' }}</small>
                </div>

                <!-- Информация о просмотрах -->
                <div class="d-flex justify-content-between small text-muted mb-3">
                  <span>
                    <i class="bi bi-eye me-1"></i>
                    {{ publication.views_count || 0 }} просмотров
                  </span>
                  <span v-if="publication.status === 'draft'" class="text-warning">
                    <i class="bi bi-clock me-1"></i>
                    Черновик
                  </span>
                </div>

                <!-- Кнопки действий -->
                <div class="d-grid">
                  <button class="btn btn-outline-primary btn-sm" @click="viewPublication(publication)">
                    <i class="bi bi-eye me-1"></i>
                    Читать полностью
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Сообщение при отсутствии публикаций -->
        <div v-else class="text-center py-5">
          <i class="bi bi-newspaper fs-1 text-muted mb-3"></i>
          <h5>Публикации не найдены</h5>
          <p class="text-muted">
            {{ filters.search ? 'Попробуйте изменить условия поиска' : 'Нет доступных публикаций' }}
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

    <!-- Модальное окно создания/редактирования (только для админа) -->
    <PublicationModal 
      v-if="showModal && isAdmin" 
      :publication="editingPublication" 
      :loading="modalLoading" 
      @save="savePublication"
      @close="closeModal" 
    />

    <!-- Модальное окно просмотра публикации -->
    <PublicationViewModal
      v-if="showViewModal"
      :show="showViewModal"
      :publication="selectedPublication"
      :is-public="isPublic"
      @close="closeViewModal"
      @edit="editPublication"
    />
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import { debounce } from 'lodash-es'
import PublicationService from '@/services/publication.service'
import PublicationModal from '@/components/modals/PublicationModal.vue'
import PublicationViewModal from '@/components/modals/PublicationViewModal.vue'

export default {
  name: 'PublicationList',

  components: {
    PublicationModal,
    PublicationViewModal
  },

  props: {
    mode: {
      type: String,
      default: 'public',
      validator: (value) => ['admin', 'public'].includes(value)
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
    },
    showFilters: {
      type: Boolean,
      default: true
    },
    cardColumns: {
      type: String,
      default: 'col-lg-4 col-md-6',
      validator: (value) => ['col-lg-4 col-md-6', 'col-lg-6', 'col-12'].includes(value)
    }
  },

  emits: [
    'publication-view',
    'publication-edit',
    'publication-delete',
    'create-publication',
    'loaded'
  ],

  setup(props, { emit }) {
    // Состояние
    const publications = ref([])
    const meta = ref(null)
    const loading = ref(false)
    const error = ref(null)

    // Выбранные публикации (для админки)
    const selectedPublications = ref([])
    const selectAll = computed({
      get: () => publications.value.length > 0 && selectedPublications.value.length === publications.value.length,
      set: (value) => {
        if (value) {
          selectedPublications.value = publications.value.map(pub => pub.id)
        } else {
          selectedPublications.value = []
        }
      }
    })

    // Фильтры
    const filters = ref({
      search: '',
      type: '',
      status: props.mode === 'public' ? 'published' : '',
      sort: props.mode === 'public' ? 'published_at:desc' : 'created_at:desc',
      page: 1,
      limit: props.limit || (props.compact ? 10 : 6)
    })

    // Модальные окна
    const showModal = ref(false)
    const showViewModal = ref(false)
    const editingPublication = ref(null)
    const selectedPublication = ref(null)
    const modalLoading = ref(false)

    // Вычисляемые свойства
    const isAdmin = computed(() => props.mode === 'admin')
    const isPublic = computed(() => props.mode === 'public')
    const cardClass = computed(() => props.cardColumns)

    // Методы
    const loadPublications = async () => {
      if (!props.autoLoad) return

      loading.value = true
      error.value = null
      selectedPublications.value = [] // Сбрасываем выбор

      try {
        const params = { ...filters.value }
        
        // Для публичного раздела показываем только опубликованные
        if (isPublic.value) {
          params.status = 'published'
        }
        
        if (props.limit) {
          params.limit = props.limit
        }

        const response = await PublicationService.getAll(params)
        publications.value = response.data
        meta.value = response.meta

        emit('loaded', { publications: publications.value, meta: meta.value })
      } catch (err) {
        error.value = err.message || 'Ошибка загрузки публикаций'
        console.error('Ошибка загрузки публикаций:', err)
      } finally {
        loading.value = false
      }
    }

    const changePage = (page) => {
      if (page >= 1 && page <= meta.value.pages) {
        filters.value.page = page
        loadPublications()
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
      loadPublications()
    }, 500)

    const formatDate = (dateString) => {
      if (!dateString) return ''
      const options = { day: 'numeric', month: 'long', year: 'numeric' }
      return new Date(dateString).toLocaleDateString('ru-RU', options)
    }

    const formatDateShort = (dateString) => {
      if (!dateString) return ''
      const options = { day: 'numeric', month: 'short' }
      return new Date(dateString).toLocaleDateString('ru-RU', options)
    }

    const truncate = (text, length) => {
      if (!text) return ''
      return text.length > length ? text.substring(0, length) + '...' : text
    }

    const getTypeColor = (type) => {
      const colors = {
        'news': 'primary',
        'announcement': 'success',
        'article': 'warning',
        'page': 'info'
      }
      return colors[type] || 'secondary'
    }

    const getTypeIcon = (type) => {
      const icons = {
        'news': 'bi-newspaper',
        'announcement': 'bi-megaphone',
        'article': 'bi-file-text',
        'page': 'bi-file-earmark'
      }
      return icons[type] || 'bi-file-text'
    }

    const getTypeText = (type) => {
      const texts = {
        'news': 'Новость',
        'announcement': 'Анонс',
        'article': 'Статья',
        'page': 'Страница'
      }
      return texts[type] || type
    }

    const getStatusColor = (status) => {
      const colors = {
        'draft': 'secondary',
        'published': 'success',
        'archived': 'warning'
      }
      return colors[status] || 'secondary'
    }

    const getStatusText = (status) => {
      const texts = {
        'draft': 'Черновик',
        'published': 'Опубликовано',
        'archived': 'В архиве'
      }
      return texts[status] || status
    }

    // Методы для работы с выбранными элементами
    const togglePublicationSelection = (publication) => {
      if (!isAdmin.value) return
      
      const index = selectedPublications.value.indexOf(publication.id)
      if (index === -1) {
        selectedPublications.value.push(publication.id)
      } else {
        selectedPublications.value.splice(index, 1)
      }
    }

    const editSelectedPublication = () => {
      if (selectedPublications.value.length === 1) {
        const publicationId = selectedPublications.value[0]
        const publication = publications.value.find(p => p.id === publicationId)
        if (publication) {
          editPublication(publication)
        }
      }
    }

    const deleteSelectedPublications = () => {
      if (selectedPublications.value.length === 0) return

      const count = selectedPublications.value.length
      const publicationTitles = publications.value
        .filter(p => selectedPublications.value.includes(p.id))
        .map(p => `"${p.title}"`)
        .join(', ')

      if (confirm(`Удалить выбранные публикации (${count})?\n${publicationTitles}`)) {
        // Удаляем выбранные публикации
        selectedPublications.value.forEach(publicationId => {
          const publication = publications.value.find(p => p.id === publicationId)
          if (publication) {
            emit('publication-delete', publication)
          }
        })

        // Очищаем выбор после удаления
        selectedPublications.value = []
      }
    }

    // Обработчики событий
    const viewPublication = (publication) => {
      selectedPublication.value = publication
      showViewModal.value = true
      emit('publication-view', publication)
    }

    const closeViewModal = () => {
      showViewModal.value = false
      selectedPublication.value = null
    }

    const editPublication = (publication) => {
      editingPublication.value = publication
      showModal.value = true
      emit('publication-edit', publication)
    }

    const deletePublication = (publication) => {
      if (confirm(`Удалить публикацию "${publication.title}"?`)) {
        emit('publication-delete', publication)
      }
    }

    const togglePin = async (publication) => {
      try {
        await PublicationService.update(publication.id, {
          is_pinned: !publication.is_pinned
        })
        loadPublications()
      } catch (err) {
        error.value = err.message || 'Ошибка изменения статуса закрепления'
      }
    }

    const changeStatus = async (publication, newStatus) => {
      if (confirm(`Изменить статус публикации "${publication.title}" на "${getStatusText(newStatus)}"?`)) {
        try {
          const updateData = { status: newStatus }
          
          // Если публикуем впервые, устанавливаем дату публикации
          if (newStatus === 'published' && !publication.published_at) {
            updateData.published_at = new Date().toISOString()
          }
          
          await PublicationService.update(publication.id, updateData)
          loadPublications()
        } catch (err) {
          error.value = err.message || 'Ошибка изменения статуса'
        }
      }
    }

    const openCreateModal = () => {
      editingPublication.value = null
      showModal.value = true
      emit('create-publication')
    }

    const closeModal = () => {
      showModal.value = false
      editingPublication.value = null
      modalLoading.value = false
    }

    const savePublication = async (publicationData) => {
      modalLoading.value = true

      try {
        if (editingPublication.value) {
          await PublicationService.update(editingPublication.value.id, publicationData)
        } else {
          await PublicationService.create(publicationData)
        }

        closeModal()
        loadPublications() // Перезагружаем список
      } catch (err) {
        error.value = err.message || 'Ошибка сохранения публикации'
        console.error('Ошибка сохранения:', err)
      } finally {
        modalLoading.value = false
      }
    }

    // Хуки жизненного цикла
    onMounted(() => {
      if (props.autoLoad) {
        loadPublications()
      }
    })

    // Наблюдатели
    watch(() => props.limit, (newLimit) => {
      if (newLimit) {
        filters.value.limit = newLimit
        loadPublications()
      }
    })

    return {
      // Данные
      publications,
      meta,
      loading,
      error,
      selectedPublications,
      selectAll,
      filters,
      showModal,
      showViewModal,
      editingPublication,
      selectedPublication,
      modalLoading,

      // Вычисляемые свойства
      isAdmin,
      isPublic,
      cardClass,

      // Методы
      loadPublications,
      changePage,
      getPages,
      debouncedSearch,
      formatDate,
      formatDateShort,
      truncate,
      getTypeColor,
      getTypeIcon,
      getTypeText,
      getStatusColor,
      getStatusText,
      togglePublicationSelection,
      editSelectedPublication,
      deleteSelectedPublications,
      viewPublication,
      closeViewModal,
      editPublication,
      deletePublication,
      togglePin,
      changeStatus,
      openCreateModal,
      closeModal,
      savePublication
    }
  }
}
</script>

<style scoped>
.publication-list {
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
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-text {
  display: -webkit-box;
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

/* Мелкий текст */
.extra-small {
  font-size: 0.75rem;
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

  .card-header .d-flex > div {
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