<template>
  <div class="container-fluid">
    <!-- Заголовок и кнопки -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold">Управление мероприятиями</h4>
      <div>
        <button class="btn btn-primary" @click="openCreateModal">
          <i class="bi bi-plus-circle me-1"></i>Новое мероприятие
        </button>
      </div>
    </div>

    <!-- Компактный EventList для админки -->
    <EventList 
      mode="admin"
      :compact="true"
      @event-edit="onEventEdit"
      @event-delete="onEventDelete"
    />
  </div>
</template>

<script>
import { ref } from 'vue'
import EventList from '@/components/EventList.vue'
import EventModal from '@/components/modals/EventModal.vue'

export default {
  name: 'AdminEvents',
  
  components: {
    EventList
  },
  
  setup() {
    const showModal = ref(false)
    const editingEvent = ref(null)
    
    const openCreateModal = () => {
      editingEvent.value = null
      showModal.value = true
    }
    
    const onEventEdit = (event) => {
      editingEvent.value = event
      showModal.value = true
    }
    
    const onEventDelete = (event) => {
      if (confirm(`Удалить мероприятие "${event.title}"?`)) {
        console.log('Удаление мероприятия:', event)
        // В будущем - вызов API
      }
    }
    
    const saveEvent = (eventData) => {
      console.log('Сохранение мероприятия:', eventData)
      showModal.value = false
      // В будущем - вызов API и обновление списка
    }
    
    const closeModal = () => {
      showModal.value = false
      editingEvent.value = null
    }
    
    return {
      showModal,
      editingEvent,
      openCreateModal,
      onEventEdit,
      onEventDelete,
      saveEvent,
      closeModal
    }
  }
}
</script>