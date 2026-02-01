<template>
  <div class="min-h-100 d-flex flex-column">
    <!-- Навигация -->
    <nav class="navbar navbar-expand-lg navbar-dark sport-gradient shadow-sm">
      <div class="container">
        <router-link class="navbar-brand d-flex align-items-center fw-bold" to="/">
          <img 
            src="/frontend/logo.png" 
            alt="Белый Лис" 
            class="logo-image me-2"
            style="height: 40px;"
          >
          <span class="brand-text">БЕЛЫЙ ЛИС</span>
        </router-link>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav me-auto">
            <li class="nav-item">
              <router-link class="nav-link" to="/">Главная</router-link>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#events">Мероприятия</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#news">Новости</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#results">Результаты</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#contacts">Контакты</a>
            </li>
          </ul>

          <div class="d-flex">
            <!-- Если пользователь авторизован -->
            <template v-if="isAuthenticated">
              <div class="dropdown">
                <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                  <i class="bi bi-person-circle me-1"></i>
                  {{ userName }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><router-link class="dropdown-item" to="/my/registrations">Личный кабинет</router-link></li>
                  <li>
                    <hr class="dropdown-divider">
                  </li>
                  <li><button class="dropdown-item text-danger" @click="handleLogout">Выйти</button></li>
                </ul>
              </div>
            </template>

            <!-- Если не авторизован -->
            <template v-else>
              <router-link to="/login" class="btn btn-outline-light btn-sm me-2">
                <i class="bi bi-box-arrow-in-right me-1"></i>Войти
              </router-link>
              <router-link to="/register" class="btn btn-light btn-sm">
                <i class="bi bi-person-plus me-1"></i>Регистрация
              </router-link>
            </template>
          </div>
        </div>
      </div>
    </nav>

    <!-- Основной контент -->
    <main class="flex-grow-1 position-relative">
      <!-- Декоративное изображение лиса в углу -->
      <div class="fox-decoration d-none d-lg-block">
        <img 
          src="/lis.png" 
          alt="Белый лис" 
          class="fox-image"
        >
      </div>
      
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>

    <!-- Футер -->
    <footer class="bg-dark text-white py-4 mt-auto">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="d-flex align-items-center">
              <img 
                src="/logo.png" 
                alt="Логотип" 
                class="footer-logo me-3"
                style="height: 50px;"
              >
              <div>
                <h5 class="mb-1">Спортивный клуб "Белый Лис"</h5>
                <p class="mb-0 small">Организация спортивных мероприятий и соревнований</p>
              </div>
            </div>
          </div>
          <div class="col-md-6 text-md-end">
            <div class="hstack gap-3 justify-content-md-end">
              <a href="#" class="text-white"><i class="bi bi-telegram fs-5"></i></a>
              <a href="#" class="text-white"><i class="bi bi-vk fs-5"></i></a>
              <a href="#" class="text-white"><i class="bi bi-instagram fs-5"></i></a>
              <a href="#" class="text-white"><i class="bi bi-envelope fs-5"></i></a>
            </div>
            <p class="mt-3 mb-0 small">© 2024 Все права защищены</p>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script>
  import { computed } from 'vue'
  import { useRouter } from 'vue-router'
  
  export default {
    name: 'PublicLayout',
    
    setup() {
      const router = useRouter()
      
      const isAuthenticated = computed(() => {
        return !!localStorage.getItem('auth_user')
      })
      
      const userName = computed(() => {
        const user = JSON.parse(localStorage.getItem('auth_user') || '{}')
        return user.name || 'Пользователь'
      })
      
      const handleLogout = () => {
        localStorage.removeItem('auth_user')
        router.push('/')
      }
      
      return {
        isAuthenticated,
        userName,
        handleLogout
      }
    }
  }
</script>

<style scoped>
.navbar-brand {
  font-size: 1.5rem;
  letter-spacing: 1px;
}

.brand-text {
  background: linear-gradient(90deg, #ffffff 0%, #e6e6e6 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.nav-link.router-link-active {
  color: var(--light-color) !important;
  font-weight: 500;
}

.nav-link:hover {
  color: rgba(255, 255, 255, 0.8) !important;
}

.logo-image {
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
  transition: transform 0.3s ease;
}

.logo-image:hover {
  transform: scale(1.05);
}

.fox-decoration {
  position: absolute;
  bottom: 20px;
  right: 20px;
  z-index: -1;
  opacity: 0.15;
  pointer-events: none;
}

.fox-image {
  max-height: 300px;
  max-width: 300px;
  filter: grayscale(30%);
}

footer {
  border-top: 3px solid var(--secondary-color);
}

.footer-logo {
  opacity: 0.9;
  transition: opacity 0.3s ease;
}

.footer-logo:hover {
  opacity: 1;
}

/* Адаптивность для мобильных устройств */
@media (max-width: 768px) {
  .navbar-brand .brand-text {
    font-size: 1.2rem;
  }
  
  .logo-image {
    height: 35px;
  }
  
  .footer-logo {
    height: 40px;
  }
}
</style>