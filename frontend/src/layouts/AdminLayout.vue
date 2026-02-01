<template>
  <div class="min-h-100 d-flex">
    <!-- Боковая панель -->
    <aside class="bg-dark text-white" style="width: 250px;">
      <div class="p-4 border-bottom border-secondary">
        <router-link to="/" class="text-white text-decoration-none d-flex align-items-center">
          <div class="bg-primary rounded-circle p-2 me-3">
            <i class="bi bi-speedometer2 fs-5"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold">Белый Лис</h5>
            <small class="text-muted">Админ панель</small>
          </div>
        </router-link>
      </div>

      <nav class="p-3">
        <div class="mb-4">
          <small class="text-uppercase text-muted fw-bold small">Управление</small>
          <ul class="nav flex-column mt-2">
            <li class="nav-item">
              <router-link to="/admin/events" class="nav-link text-white d-flex align-items-center ">
                <i class="bi bi-calendar-event me-2"></i>
                <span>Мероприятия</span>
              </router-link>
            </li>
            <li class="nav-item">
              <router-link to="/admin/users" class="nav-link text-white d-flex align-items-center">
                <i class="bi bi-people me-2"></i>
                <span>Участники</span>
              </router-link>
            </li>
            <li class="nav-item">
              <router-link to="/admin/registrations" class="nav-link text-white d-flex align-items-center">
                <i class="bi bi-clipboard-check me-2"></i>
                <span>Заявки</span>
              </router-link>
            </li>
            
            <!-- <li class="nav-item">
              <router-link to="/admin/results" class="nav-link text-white d-flex align-items-center">
                <i class="bi bi-stopwatch me-2"></i>
                <span>Результаты</span>
              </router-link>
            </li> -->
          
            <!--   Старый вариант, пока оставим. -->
            <!-- <li class="nav-item">
              <router-link to="/admin/protocols" class="nav-link text-white d-flex align-items-center">
                <i class="bi bi-file-text me-2"></i>
                <span>Протоколы</span>
              </router-link>
            </li> -->

            <li class="nav-item">
              <router-link to="/admin/protocols" class="nav-link text-white d-flex align-items-center "
                :class="{ active: $route.path.startsWith('/admin/protocols') }">
                <i class="bi bi-file-text me-2"></i>
                Протоколы
              </router-link>
            </li>

          </ul>
        </div>

        <div class="mb-4">
          <small class="text-uppercase text-muted fw-bold small">Контент</small>
          <ul class="nav flex-column mt-2">
            <li class="nav-item">
              <router-link to="/admin/publications" class="nav-link text-white d-flex align-items-center">
                <i class="bi bi-newspaper me-2"></i>
                <span>Новости</span>
              </router-link>
            </li>
            <li class="nav-item">
              <router-link to="/admin/settings" class="nav-link text-white d-flex align-items-center">
                <i class="bi bi-gear me-2"></i>
                <span>Настройки</span>
              </router-link>
            </li>
          </ul>
        </div>

        <div class="mt-5 pt-4 border-top border-secondary">
          <div class="px-3">
            <div class="d-flex align-items-center">
              <div class="bg-secondary rounded-circle p-2 me-3">
                <i class="bi bi-person-circle fs-5"></i>
              </div>
              <div class="flex-grow-1">
                <small class="d-block fw-bold">Администратор</small>
                <small class="text-muted">admin@beliy-lis.ru</small>
              </div>
              <button class="btn btn-sm btn-outline-light">
                <i class="bi bi-box-arrow-right"></i>
              </button>
            </div>
          </div>
        </div>
      </nav>
    </aside>

    <!-- Основной контент -->
    <div class="flex-grow-1 d-flex flex-column">      
      <header class="bg-white border-bottom shadow-sm py-3">
        <div class="container-fluid px-4">
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
              <button class="btn btn-outline-secondary me-3 d-lg-none">
                <i class="bi bi-list"></i>
              </button>
              <h4 class="mb-0 fw-bold text-primary">{{ pageTitle }}</h4>
            </div>

            <div class="d-flex align-items-center gap-3">
              
              <!-- Выпадающее меню для разделов. Можно сделать дополнительные функции по разделу.  Пока не нужно, на вырост.-->
              <!-- <div class="dropdown">
                <button class="btn btn-light border d-flex align-items-center" data-bs-toggle="dropdown">
                  <i class="bi bi-bell me-2"></i>
                  <span class="badge bg-danger rounded-pill">3</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                  <h6 class="dropdown-header">Уведомления</h6>
                  <a class="dropdown-item small" href="#">Новая заявка на мероприятие</a>
                  <a class="dropdown-item small" href="#">Требуется подтверждение оплаты</a>
                  <a class="dropdown-item small" href="#">Обновление результатов</a>
                </div>
              </div> -->

              <div class="input-group" style="width: 300px;">
                <input type="text" class="form-control" placeholder="Поиск...">
                <button class="btn btn-outline-primary">
                  <i class="bi bi-search"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Контент (через роутер) -->
      <main class="flex-grow-1 p-4 bg-light">
        <div class="container-fluid">
          <router-view v-slot="{ Component }">
            <transition name="fade" mode="out-in">
              <component :is="Component" />
            </transition>
          </router-view>
        </div>
      </main>

      <!-- Футер админки -->
      <footer class="bg-white border-top py-3">
        <div class="container-fluid px-4">
          <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">© 2024 Спортивный клуб "Белый Лис"</small>
            <small class="text-muted">Версия 1.0.0 (MVP)</small>
          </div>
        </div>
      </footer>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

export default {
  name: 'AdminLayout',

  setup() {
    const route = useRoute()

    const pageTitle = computed(() => {
      const titles = {
        'admin.events': 'Мероприятия',
        'admin.users': 'Участники',
        'admin.registrations': 'Заявки',
        'admin.results': 'Результаты',
        'admin.protocols': 'Протоколы',
        'admin.publications': 'Новости и публикации',
        'admin.settings': 'Настройки системы'
      }
      return titles[route.name] || 'Панель управления'
    })

    return { pageTitle }
  }
}
</script>

<style scoped>

</style>