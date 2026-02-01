<template>
  <div class="judge-events">
    <h2 class="mb-4">Мои мероприятия</h2>
    
    <div v-if="loading" class="text-center">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Загрузка...</span>
      </div>
    </div>
    
    <div v-else-if="events.length === 0" class="alert alert-warning">
      Нет назначенных мероприятий
    </div>
    
    <div v-else class="row">
      <div v-for="event in events" :key="event.id" class="col-md-6 mb-3">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">{{ event.title }}</h5>
            <p class="card-text">
              <small class="text-muted">
                {{ formatDate(event.date) }} • {{ event.location }}
              </small>
            </p>
            <p class="card-text">{{ event.description }}</p>
            <div class="d-flex justify-content-between">
              <span class="badge bg-primary">{{ event.discipline }}</span>
              <span class="badge" :class="statusClass(event.status)">
                {{ event.status }}
              </span>
            </div>
            <div class="mt-3">
              <router-link 
                :to="{ name: 'JudgeFinishLine', params: { eventId: event.id } }" 
                class="btn btn-sm btn-success me-2"
              >
                Фиксация результатов
              </router-link>
              <router-link 
                :to="{ name: 'JudgeResultsInput', params: { eventId: event.id } }" 
                class="btn btn-sm btn-outline-primary"
              >
                Просмотр результатов
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
// import { useAuthStore } from '@/stores/auth';
import eventService from '@/services/event.service';

export default {
  name: 'JudgeEvents',
  
  setup() {
    // const authStore = useAuthStore();
    const events = ref([]);
    const loading = ref(true);

    const loadEvents = async () => {
      try {
        // Загружаем мероприятия, где текущий пользователь является судьей
        const response = await eventService.getAll();
        events.value = response.data || [];
      } catch (error) {
        console.error('Ошибка загрузки мероприятий:', error);
        events.value = [];
      } finally {
        loading.value = false;
      }
    };

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('ru-RU', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
      });
    };

    const statusClass = (status) => {
      switch(status) {
        case 'active': return 'bg-success';
        case 'draft': return 'bg-secondary';
        case 'completed': return 'bg-info';
        default: return 'bg-light text-dark';
      }
    };

    onMounted(() => {
      loadEvents();
    });

    return {
      events,
      loading,
      formatDate,
      statusClass
    };
  }
};
</script>

<style scoped>
.judge-events {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.card {
  transition: transform 0.2s;
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
</style>