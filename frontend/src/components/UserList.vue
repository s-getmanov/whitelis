<template>
  <div class="user-list">
    <!-- Состояние загрузки -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Загрузка...</span>
      </div>
      <p class="mt-3 text-muted small">Загрузка пользователей...</p>
    </div>

    <!-- Ошибка -->
    <div v-else-if="error" class="alert alert-danger">
      <i class="bi bi-exclamation-triangle me-2"></i>
      {{ error }}
      <button class="btn btn-sm btn-outline-danger ms-3" @click="loadUsers">
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
              <button class="btn btn-primary btn-sm" @click="openCreateModal" :disabled="loading"
                title="Добавить пользователя">
                <i class="bi bi-person-plus me-1"></i>Добавить
              </button>

              <button class="btn btn-outline-primary btn-sm" @click="editSelectedUser"
                :disabled="selectedUsers.length !== 1" title="Редактировать выбранного">
                <i class="bi bi-pencil me-1"></i>Редактировать
              </button>

              <button class="btn btn-outline-danger btn-sm" @click="deleteSelectedUsers"
                :disabled="selectedUsers.length === 0" title="Удалить выбранных">
                <i class="bi bi-trash me-1"></i>Удалить
              </button>

              <span v-if="selectedUsers.length > 0" class="badge bg-primary align-self-center ms-2">
                Выбрано: {{ selectedUsers.length }}
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

              <select v-model="filters.role" class="form-select form-select-sm" style="width: 140px;"
                @change="loadUsers">
                <option value="">Все роли</option>
                <option value="admin">Администратор</option>
                <option value="participant">Участник</option>
                <option value="judge">Судья</option>
                <option value="volunteer">Волонтер</option>
              </select>

              <select v-model="filters.status" class="form-select form-select-sm" style="width: 140px;"
                @change="loadUsers">
                <option value="">Все статусы</option>
                <option value="active">Активен</option>
                <option value="blocked">Заблокирован</option>
                <option value="pending">Ожидает</option>
              </select>

              <button class="btn btn-sm btn-outline-secondary" @click="loadUsers" :disabled="loading" title="Обновить">
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
                  <input type="checkbox" class="form-check-input" v-model="selectAll" :disabled="users.length === 0">
                </th>
                <th>Имя</th>
                <th>Email</th>
                <th style="width: 120px;">Телефон</th>
                <th style="width: 120px;">Роль</th>
                <th style="width: 120px;">Статус</th>
                <th style="width: 100px;">Заявок</th>
                <th style="width: 150px;">Дата регистрации</th>
                <th style="width: 100px;" class="text-end">Действия</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in users" :key="user.id" class="cursor-pointer"
                :class="{ 'table-primary': selectedUsers.includes(user.id) }">
                <td @click.stop>
                  <input type="checkbox" class="form-check-input" :value="user.id" v-model="selectedUsers">
                </td>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                      <i class="bi bi-person-circle text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                      <div class="fw-semibold">{{ user.name }}</div>
                      <small class="text-muted">ID: {{ user.id }}</small>
                    </div>
                  </div>
                </td>
                <td>
                  <small>{{ user.email }}</small>
                </td>
                <td>
                  <small>{{ user.phone || '-' }}</small>
                </td>
                <td>
                  <span :class="`badge bg-${getRoleColor(user.role)}`">
                    {{ user.role_text || user.role }}
                  </span>
                </td>
                <td>
                  <span :class="`badge bg-${getStatusColor(user.status)}`">
                    {{ user.status_text || user.status }}
                  </span>
                </td>
                <td>
                  <span class="badge bg-secondary">
                    {{ user.registrations_count || 0 }}
                  </span>
                </td>
                <td>
                  <small>{{ formatDateShort(user.created_at) }}</small>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" @click="editUser(user)" title="Редактировать">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-outline-danger" @click="deleteUser(user)" title="Удалить">
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
              Показано {{ users.length }} из {{ meta.total }} пользователей
              <span v-if="selectedUsers.length > 0" class="ms-3">
                • Выбрано: {{ selectedUsers.length }}
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

        <!-- Сообщение при отсутствии пользователей -->
        <div v-if="users.length === 0 && !loading" class="text-center py-5">
          <i class="bi bi-people fs-1 text-muted mb-3"></i>
          <h5>Пользователи не найдены</h5>
          <p class="text-muted">
            {{ filters.search ? 'Попробуйте изменить условия поиска' : 'Нет зарегистрированных пользователей' }}
          </p>
          <button class="btn btn-primary" @click="openCreateModal">
            Добавить первого пользователя
          </button>
        </div>
      </div>
    </div>

    <!-- Модальное окно редактирования пользователя -->
    <UserModal v-if="showModal" :user="editingUser" :loading="modalLoading" @save="saveUser" @close="closeModal" />
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { debounce } from 'lodash-es'
import UserService from '@/services/user.service'
import UserModal from '@/components/modals/UserModal.vue'

export default {
  name: 'UserList',

  components: {
    UserModal
  },

  emits: ['user-edit', 'user-delete', 'user-create'],

  setup(props, { emit }) {
    // Состояние
    const users = ref([])
    const meta = ref(null)
    const loading = ref(false)
    const error = ref(null)

    // Выбранные пользователи
    const selectedUsers = ref([])
    const selectAll = computed({
      get: () => users.value.length > 0 && selectedUsers.value.length === users.value.length,
      set: (value) => {
        if (value) {
          selectedUsers.value = users.value.map(user => user.id)
        } else {
          selectedUsers.value = []
        }
      }
    })

    // Фильтры
    const filters = ref({
      search: '',
      role: '',
      status: '',
      page: 1,
      limit: 10
    })

    // Модальное окно
    const showModal = ref(false)
    const editingUser = ref(null)
    const modalLoading = ref(false)

    // Методы

    const loadUsers = async () => {
      console.log('Загрузка пользователей с фильтрами:', filters.value);
      loading.value = true
      error.value = null
      selectedUsers.value = []

      try {
        const params = {
          ...filters.value,
          limit: filters.value.limit || 10,
          page: filters.value.page || 1
        }
        console.log('Параметры запроса:', params);

        const response = await UserService.getAll(params)
        console.log('Ответ API:', response);

        // API возвращает { data: [...], meta: {...} } в response.data
        if (response && response.data) {
          users.value = response.data.data || []  // data находится внутри response.data
          meta.value = response.data.meta || { total: 0, page: 1, pages: 1 }
        } else {
          users.value = []
          meta.value = { total: 0, page: 1, pages: 1 }
        }

        console.log('Загружено пользователей:', users.value.length);
      } catch (err) {
        console.error('Ошибка загрузки пользователей:', err);
        error.value = err.response?.data?.message || err.message || 'Ошибка загрузки пользователей'
      } finally {
        loading.value = false
      }
    }


    const saveUser = async (userData) => {
      modalLoading.value = true
      error.value = null

      try {
        if (editingUser.value) {
          await UserService.update(editingUser.value.id, userData)
        } else {
          await UserService.create(userData)
        }

        closeModal()
        loadUsers()
      } catch (err) {
        console.error('Ошибка сохранения пользователя:', err);

        // Отображаем ошибки валидации
        if (err.response?.status === 422 && err.response?.data?.errors) {
          const validationErrors = err.response.data.errors
          const errorMessages = Object.entries(validationErrors)
            .map(([field, messages]) => `${field}: ${messages.join(', ')}`)
            .join('\n')
          error.value = `Ошибки валидации:\n${errorMessages}`
        } else {
          error.value = err.response?.data?.message || err.message || 'Ошибка сохранения пользователя'
        }
      } finally {
        modalLoading.value = false
      }
    }



    const changePage = (page) => {
      if (page >= 1 && page <= meta.value.pages) {
        filters.value.page = page
        loadUsers()
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
      loadUsers()
    }, 500)

    const formatDateShort = (dateString) => {
      if (!dateString) return ''
      try {
        const date = new Date(dateString)
        return date.toLocaleDateString('ru-RU')
      } catch (e) {
        return dateString
      }
    }

    const getRoleColor = (role) => {
      const colors = {
        'admin': 'danger',
        'participant': 'primary',
        'judge': 'warning',
        'volunteer': 'info'
      }
      return colors[role] || 'secondary'
    }

    const getStatusColor = (status) => {
      const colors = {
        'active': 'success',
        'blocked': 'danger',
        'pending': 'warning'
      }
      return colors[status] || 'secondary'
    }

    // Работа с выбранными элементами
    const toggleUserSelection = (user) => {
      const index = selectedUsers.value.indexOf(user.id)
      if (index === -1) {
        selectedUsers.value.push(user.id)
      } else {
        selectedUsers.value.splice(index, 1)
      }
    }

    const editSelectedUser = () => {
      if (selectedUsers.value.length === 1) {
        const userId = selectedUsers.value[0]
        const user = users.value.find(u => u.id === userId)
        if (user) {
          editUser(user)
        }
      }
    }

    // Починили??? функция deleteSelectedUsers:
    const deleteSelectedUsers = () => {
      if (selectedUsers.value.length === 0) return

      const count = selectedUsers.value.length

      // Безопасное получение имен пользователей
      let userNames = ''
      if (Array.isArray(users.value) && users.value.length > 0) {
        userNames = users.value
          .filter(u => selectedUsers.value.includes(u.id))
          .map(u => `"${u.name || u.email}"`)
          .join(', ')
      }

      if (confirm(`Удалить выбранных пользователей (${count})?\n${userNames}`)) {
        deleteSelectedUsersFromAPI()
      }
    }

    const deleteSelectedUsersFromAPI = async () => {
      try {
        loading.value = true

        // Удаляем по одному
        for (const userId of selectedUsers.value) {
          try {
            await UserService.delete(userId)
            console.log(`Пользователь ${userId} удален`)
          } catch (err) {
            console.error(`Ошибка удаления пользователя ${userId}:`, err)
            error.value = `Ошибка удаления пользователя ${userId}`
          }
        }

        // Очищаем выбранные и перезагружаем список
        selectedUsers.value = []
        await loadUsers()

      } catch (err) {
        console.error('Ошибка массового удаления:', err)
        error.value = 'Ошибка удаления пользователей'
      } finally {
        loading.value = false
      }
    }

    const editUser = (user) => {
      editingUser.value = user
      showModal.value = true
      emit('user-edit', user)
    }

    const deleteUser = (user) => {
      if (confirm(`Удалить пользователя "${user.name}"?`)) {
        emit('user-delete', user)
        // В будущем - вызов API
        // loadUsers() // Перезагрузить список
      }
    }

    const openCreateModal = () => {
      editingUser.value = null
      showModal.value = true
      emit('user-create')
    }

    const closeModal = () => {
      showModal.value = false
      editingUser.value = null
      modalLoading.value = false
    }


    // Хуки жизненного цикла
    onMounted(() => {
      loadUsers()
    })

    return {
      users,
      meta,
      loading,
      error,
      selectedUsers,
      selectAll,
      filters,
      showModal,
      editingUser,
      modalLoading,

      loadUsers,
      changePage,
      getPages,
      debouncedSearch,
      formatDateShort,
      getRoleColor,
      getStatusColor,
      toggleUserSelection,
      editSelectedUser,
      deleteSelectedUsers,
      editUser,
      deleteUser,
      openCreateModal,
      closeModal,
      saveUser
    }
  }
}
</script>

<style scoped>
.user-list {
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

  .card-header .d-flex>div {
    width: 100%;
  }

  .table-responsive {
    font-size: 0.875rem;
  }
}
</style>