<template>
  <div class="min-h-100 d-flex flex-column">
    <!-- Навигация личного кабинета -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
      <div class="container">
        <router-link class="navbar-brand d-flex align-items-center fw-bold text-primary" to="/my">
          <i class="bi bi-person-circle me-2"></i>
          Мой кабинет
        </router-link>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#personalNav">
          <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="personalNav">
          <ul class="navbar-nav me-auto">
            <li class="nav-item">
              <router-link class="nav-link" to="/my/registrations" active-class="active">
                <i class="bi bi-clipboard-check me-1"></i>Мои заявки
              </router-link>
            </li>
            <li class="nav-item">
              <router-link class="nav-link" to="/my/profile" active-class="active">
                <i class="bi bi-person me-1"></i>Профиль
              </router-link>
            </li>
            <li class="nav-item">
              <router-link class="nav-link" to="/my/events" active-class="active">
                <i class="bi bi-calendar-event me-1"></i>Мероприятия
              </router-link>
            </li>
          </ul>
          
          <div class="d-flex align-items-center">
            <span class="me-3 text-muted small">{{ userInfo?.name || 'Пользователь' }}</span>
            <div class="dropdown">
              <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" 
                      data-bs-toggle="dropdown">
                <i class="bi bi-gear"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <router-link class="dropdown-item" to="/my/profile">
                    <i class="bi bi-person me-2"></i>Настройки
                  </router-link>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <a class="dropdown-item text-danger" href="#" @click.prevent="logout">
                    <i class="bi bi-box-arrow-right me-2"></i>Выйти
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Основной контент -->
    <main class="flex-grow-1 py-4">
      <div class="container">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </div>
    </main>

    <!-- Футер -->
    <footer class="bg-light border-top py-3 mt-auto">
      <div class="container">
        <div class="row">
          <div class="col-md-6">
            <small class="text-muted">© 2024 Спортивный клуб "Белый Лис"</small>
          </div>
          <div class="col-md-6 text-md-end">
            <router-link to="/" class="text-decoration-none small me-3">
              <i class="bi bi-house me-1"></i>На сайт
            </router-link>
            <router-link to="/admin" v-if="isAdmin" class="text-decoration-none small">
              <i class="bi bi-speedometer2 me-1"></i>Админка
            </router-link>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'

export default {
  name: 'PersonalLayout',
  
  setup() {
    const router = useRouter()
    
    // Заглушка для пользователя (в будущем - из хранилища)
    const userInfo = ref({
      name: 'Иван Петров',
      email: 'ivan@example.com',
      role: 'admin'
    })

    // Текущий пользователь
    const currentUserId = computed(() => {
      const user = JSON.parse(localStorage.getItem('auth_user') || '{}')
      return user.id || null
    })
    
    const isAdmin = computed(() => userInfo.value.role === 'admin')
    
    const logout = () => {
      // Заглушка для выхода
      if (confirm('Вы уверены, что хотите выйти?')) {
        // В будущем: очистка токена, редирект
        router.push('/')
      }
    }
    
    return {
      userInfo,
      isAdmin,
      logout
    }
  }
}
</script>

<style scoped>
.navbar-brand {
  font-size: 1.25rem;
}

.nav-link.active {
  font-weight: 500;
  color: var(--bs-primary) !important;
}

main {
  background-color: #f8f9fa;
  min-height: calc(100vh - 130px);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>