<template>
  <div class="personal-events">
    <h4 class="fw-bold mb-4">Мероприятия</h4>
    
    <div class="row">
      <div class="col-md-8">
        <EventList mode="personal" />
      </div>
      
      <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-body">
            <h6 class="fw-bold mb-3">Ближайшие события</h6>
            <div v-if="upcomingEvents.length === 0" class="text-center py-3">
              <p class="text-muted mb-0">Нет предстоящих мероприятий</p>
            </div>
            <div v-for="event in upcomingEvents" :key="event.id" class="mb-3">
              <div class="d-flex">
                <div class="flex-shrink-0 bg-primary bg-opacity-10 rounded p-2">
                  <i class="bi bi-calendar-event text-primary"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                  <h6 class="mb-1">{{ event.title }}</h6>
                  <small class="text-muted">
                    <i class="bi bi-calendar me-1"></i>
                    {{ formatDate(event.date) }}
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import EventList from '@/components/EventList.vue'
import EventService from '@/services/event.service'

export default {
  name: 'MyEvents',
  
  components: {
    EventList
  },
  
  setup() {
    const upcomingEvents = ref([])
    
    const loadUpcomingEvents = async () => {
      try {
        const response = await EventService.getAll({ 
          limit: 5,
          status: 'upcoming'
        })
        upcomingEvents.value = response.data || []
      } catch (err) {
        console.error('Ошибка загрузки мероприятий:', err)
      }
    }
    
    const formatDate = (dateString) => {
      const options = { day: 'numeric', month: 'long', year: 'numeric' }
      return new Date(dateString).toLocaleDateString('ru-RU', options)
    }
    
    onMounted(() => {
      loadUpcomingEvents()
    })
    
    return {
      upcomingEvents,
      formatDate
    }
  }
}
</script>