<template>
  <div class="personal-registrations">
    <!-- Заголовок -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold mb-1">Мои заявки</h4>
        <p class="text-muted small mb-0">Управление вашими регистрациями на мероприятия</p>
      </div>
      <button class="btn btn-primary" @click="openCreateModal">
        <i class="bi bi-plus-circle me-1"></i>Подать заявку
      </button>
    </div>

    <!-- Статистика -->
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="card border-0 bg-primary bg-opacity-10">
          <div class="card-body py-3">
            <div class="d-flex align-items-center">
              <div class="flex-shrink-0">
                <i class="bi bi-clock-history text-primary fs-4"></i>
              </div>
              <div class="flex-grow-1 ms-3">
                <h6 class="mb-0">Ожидают</h6>
                <h4 class="mb-0">{{ stats.pending || 0 }}</h4>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-0 bg-success bg-opacity-10">
          <div class="card-body py-3">
            <div class="d-flex align-items-center">
              <div class="flex-shrink-0">
                <i class="bi bi-check-circle text-success fs-4"></i>
              </div>
              <div class="flex-grow-1 ms-3">
                <h6 class="mb-0">Подтверждены</h6>
                <h4 class="mb-0">{{ stats.approved || 0 }}</h4>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-0 bg-info bg-opacity-10">
          <div class="card-body py-3">
            <div class="d-flex align-items-center">
              <div class="flex-shrink-0">
                <i class="bi bi-flag text-info fs-4"></i>
              </div>
              <div class="flex-grow-1 ms-3">
                <h6 class="mb-0">Участвовал</h6>
                <h4 class="mb-0">{{ stats.completed || 0 }}</h4>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-0 bg-secondary bg-opacity-10">
          <div class="card-body py-3">
            <div class="d-flex align-items-center">
              <div class="flex-shrink-0">
                <i class="bi bi-x-circle text-secondary fs-4"></i>
              </div>
              <div class="flex-grow-1 ms-3">
                <h6 class="mb-0">Всего</h6>
                <h4 class="mb-0">{{ stats.total || 0 }}</h4>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Компонент RegistrationList -->
    <RegistrationList 
      mode="personal"
      :showCreateButton="false"
      :showActions="true"
      @registration-edit="onRegistrationEdit"
      @registration-delete="onRegistrationDelete"
    />

    <!-- Модальное окно -->
    <RegistrationModal 
      v-if="showModal"
      :registration="editingRegistration"
      :availableEvents="availableEvents"
      :loading="modalLoading"
      @save="saveRegistration"
      @close="closeModal"
    />
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import RegistrationList from '@/components/RegistrationList.vue'
import RegistrationModal from '@/components/modals/RegistrationModal.vue'
import RegistrationService from '@/services/registration.service'
import EventService from '@/services/event.service'

export default {
  name: 'MyRegistrations',
  
  components: {
    RegistrationList,
    RegistrationModal
  },
  
  setup() {
    const showModal = ref(false)
    const editingRegistration = ref(null)
    const modalLoading = ref(false)
    const availableEvents = ref([])
    const stats = ref({})
    
    const loadEvents = async () => {
      try {
        const response = await EventService.getAll({ 
          limit: 50,
          status: 'upcoming'
        })
        availableEvents.value = response.data || []
      } catch (err) {
        console.error('Ошибка загрузки мероприятий:', err)
      }
    }
    
    const loadStats = async () => {
      try {
        // Подсчет статистики
        const response = await RegistrationService.getAll({ limit: 1000 })
        const registrations = response.data || []
        
        stats.value = {
          total: registrations.length,
          pending: registrations.filter(r => r.status === 'pending').length,
          approved: registrations.filter(r => r.status === 'approved').length,
          completed: registrations.filter(r => r.status === 'completed').length,
        }
      } catch (err) {
        console.error('Ошибка загрузки статистики:', err)
      }
    }
    
    const openCreateModal = () => {
      editingRegistration.value = null
      showModal.value = true
    }
    
    const onRegistrationEdit = (registration) => {
      editingRegistration.value = registration
      showModal.value = true
    }
    
    const onRegistrationDelete = async (registration) => {
      if (confirm(`Отменить заявку на мероприятие "${registration.event_title}"?`)) {
        try {
          await RegistrationService.delete(registration.id)
          // Обновляем статистику
          await loadStats()
        } catch (err) {
          alert('Ошибка отмены заявки: ' + (err.response?.data?.message || err.message))
        }
      }
    }
    
    const saveRegistration = async (registrationData) => {
      modalLoading.value = true
      
      try {
        if (editingRegistration.value) {
          await RegistrationService.update(editingRegistration.value.id, registrationData)
        } else {
          await RegistrationService.create(registrationData)
        }
        
        closeModal()
        // Перезагрузка произойдет в RegistrationList через emit
        await loadStats()
      } catch (err) {
        console.error('Ошибка сохранения заявки:', err)
        alert('Ошибка: ' + (err.response?.data?.message || err.message))
      } finally {
        modalLoading.value = false
      }
    }
    
    const closeModal = () => {
      showModal.value = false
      editingRegistration.value = null
      modalLoading.value = false
    }
    
    onMounted(() => {
      loadEvents()
      loadStats()
    })
    
    return {
      showModal,
      editingRegistration,
      modalLoading,
      availableEvents,
      stats,
      openCreateModal,
      onRegistrationEdit,
      onRegistrationDelete,
      saveRegistration,
      closeModal
    }
  }
}
</script>

<style scoped>
.personal-registrations {
  min-height: 400px;
}

.card {
  border-radius: 10px;
  transition: transform 0.2s;
}

.card:hover {
  transform: translateY(-2px);
}
</style>