<template>
  <div class="judge-layout" :class="{ 'offline': isOffline }">
    <!-- Статус бар -->
    <div class="status-bar">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-person-badge fs-5 text-primary"></i>
              <span class="fw-semibold">Судья: {{ judgeName }}</span>
            </div>
          </div>
          <div class="col-auto">
            <div class="d-flex align-items-center gap-3">
              <!-- Текущее мероприятие -->
              <div v-if="currentEvent" class="current-event">
                <span class="badge bg-primary">
                  <i class="bi bi-calendar-event me-1"></i>
                  {{ currentEvent.title }}
                </span>
              </div>
              
              <!-- Статус соединения -->
              <div class="connection-status">
                <span v-if="isOffline" class="badge bg-danger">
                  <i class="bi bi-wifi-off me-1"></i>
                  Офлайн
                </span>
                <span v-else class="badge bg-success">
                  <i class="bi bi-wifi me-1"></i>
                  Онлайн
                </span>
              </div>
              
              <!-- Время -->
              <div class="current-time text-muted">
                {{ currentTime }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Навигация -->
    <nav class="judge-nav">
      <div class="container-fluid">
        <ul class="nav nav-pills">
          <li class="nav-item">
            <router-link to="/judge/finish" class="nav-link" active-class="active">
              <i class="bi bi-stopwatch me-1"></i>
              Фиксация
            </router-link>
          </li>
          <li class="nav-item">
            <router-link to="/judge/results" class="nav-link" active-class="active">
              <i class="bi bi-list-ol me-1"></i>
              Результаты
            </router-link>
          </li>
          <li class="nav-item">
            <router-link to="/judge/events" class="nav-link" active-class="active">
              <i class="bi bi-calendar3 me-1"></i>
              Мероприятия
            </router-link>
          </li>
          <li class="nav-item ms-auto">
            <button class="btn btn-sm btn-outline-light" @click="handleLogout">
              <i class="bi bi-box-arrow-right me-1"></i>
              Выйти
            </button>
          </li>
        </ul>
      </div>
    </nav>
    
    <!-- Основной контент -->
    <main class="judge-content">
      <div class="container-fluid py-3">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" 
              :current-event="currentEvent"
              @event-selected="handleEventSelect"
              @sync-complete="handleSyncComplete"
            />
          </transition>
        </router-view>
      </div>
    </main>
    
    <!-- Офлайн уведомление -->
    <div v-if="isOffline && offlineResultsCount > 0" class="offline-notification fixed-bottom">
      <div class="alert alert-warning m-3 shadow-lg">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Офлайн режим</strong>
            <span class="ms-2">Несинхронизированных результатов: {{ offlineResultsCount }}</span>
          </div>
          <button class="btn btn-sm btn-warning" @click="forceSync">
            <i class="bi bi-cloud-arrow-up me-1"></i>
            Синхронизировать
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import offlineStorage from '@/services/offlineStorage'

export default {
  name: 'JudgeMobileLayout',
  
  setup() {
    const router = useRouter()
    
    // Состояние
    const judgeName = ref('')
    const currentEvent = ref(null)
    const isOffline = ref(!navigator.onLine)
    const currentTime = ref('')
    const offlineResultsCount = ref(0)
    
    // Получение имени судьи
    const getJudgeName = () => {
      const user = JSON.parse(localStorage.getItem('auth_user') || '{}')
      judgeName.value = user.name || 'Судья'
    }
    
    // Обновление времени
    const updateTime = () => {
      const now = new Date()
      currentTime.value = now.toLocaleTimeString('ru-RU', { 
        hour: '2-digit', 
        minute: '2-digit',
        second: '2-digit'
      })
    }
    
    // Проверка офлайн-результатов
    const checkOfflineResults = async () => {
      try {
    const unsynced = await offlineStorage.getUnsyncedResults()
    console.log('Найдено несинхронизированных результатов:', unsynced.length)
    
    if (unsynced.length > 0) {
      this.showSyncNotification = true
      this.unsyncedCount = unsynced.length
    }
  } catch (error) {
    console.error('Ошибка проверки офлайн-результатов:', error)
  }
    }
    
    // Обработчики событий
    const handleEventSelect = (event) => {
      currentEvent.value = event
      // Сохраняем выбранное мероприятие в localStorage
      localStorage.setItem('judge_current_event', JSON.stringify(event))
    }
    
    const handleSyncComplete = () => {
      checkOfflineResults()
    }
    
    const handleLogout = () => {
      localStorage.removeItem('auth_user')
      localStorage.removeItem('judge_current_event')
      router.push('/login')
    }
    
    const forceSync = () => {
      // Эмитируем событие для компонента результатов
      window.dispatchEvent(new CustomEvent('force-sync'))
    }
    
    // Слушатели онлайн/офлайн
    const updateOnlineStatus = () => {
      isOffline.value = !navigator.onLine
      if (!isOffline.value) {
        // При восстановлении связи проверяем офлайн-результаты
        checkOfflineResults()
      }
    }
    
    // Инициализация
    onMounted(() => {
      getJudgeName()
      
      // Загружаем сохраненное мероприятие
      const savedEvent = localStorage.getItem('judge_current_event')
      if (savedEvent) {
        try {
          currentEvent.value = JSON.parse(savedEvent)
        } catch (e) {
          console.error('Ошибка загрузки сохраненного мероприятия:', e)
        }
      }
      
      // Обновление времени
      updateTime()
      const timeInterval = setInterval(updateTime, 1000)
      
      // Проверка офлайн-результатов
      checkOfflineResults()
      const checkInterval = setInterval(checkOfflineResults, 30000) // Каждые 30 секунд
      
      // Слушатели
      window.addEventListener('online', updateOnlineStatus)
      window.addEventListener('offline', updateOnlineStatus)
      
      // Очистка
      onUnmounted(() => {
        clearInterval(timeInterval)
        clearInterval(checkInterval)
        window.removeEventListener('online', updateOnlineStatus)
        window.removeEventListener('offline', updateOnlineStatus)
      })
    })
    
    return {
      judgeName,
      currentEvent,
      isOffline,
      currentTime,
      offlineResultsCount,
      handleEventSelect,
      handleLogout,
      handleSyncComplete,
      forceSync
    }
  }
}
</script>

<style scoped>
.judge-layout {
  min-height: 100vh;
  background-color: #f8f9fa;
}

/* Статус бар */
.status-bar {
  background: linear-gradient(135deg, #2c3e50 0%, #4a6491 100%);
  color: white;
  padding: 10px 0;
  font-size: 0.9rem;
  border-bottom: 3px solid #3498db;
}

.status-bar .badge {
  font-size: 0.75rem;
  font-weight: 500;
}

.current-time {
  font-family: 'Courier New', monospace;
  font-weight: bold;
  font-size: 0.9rem;
}

/* Навигация */
.judge-nav {
  background-color: white;
  border-bottom: 1px solid #dee2e6;
  padding: 8px 0;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.judge-nav .nav-link {
  font-weight: 500;
  padding: 8px 16px;
  border-radius: 20px;
  transition: all 0.2s;
}

.judge-nav .nav-link.active {
  background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
  color: white;
}

.judge-nav .nav-link:hover:not(.active) {
  background-color: #f8f9fa;
}

/* Основной контент */
.judge-content {
  background-color: #f8f9fa;
  min-height: calc(100vh - 110px);
}

/* Офлайн уведомление */
.offline-notification {
  z-index: 1050;
}

.offline-notification .alert {
  border-radius: 10px;
  border: none;
}

/* Адаптивность */
@media (max-width: 768px) {
  .status-bar .container-fluid {
    font-size: 0.8rem;
  }
  
  .judge-nav .nav {
    overflow-x: auto;
    flex-wrap: nowrap;
    padding-bottom: 5px;
  }
  
  .judge-nav .nav-item {
    flex-shrink: 0;
  }
  
  .current-event .badge {
    font-size: 0.7rem;
  }
}

@media (max-width: 576px) {
  .status-bar .row {
    flex-direction: column;
    gap: 5px;
    text-align: center;
  }
  
  .current-time {
    display: none;
  }
}

/* Офлайн режим */
.judge-layout.offline::after {
  content: '';
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #e74c3c 0%, #c0392b 100%);
  z-index: 1100;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 0.7; }
  50% { opacity: 1; }
}
</style>