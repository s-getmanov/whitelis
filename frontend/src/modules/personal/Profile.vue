<template>
  <div class="personal-profile">
    <div class="row">
      <div class="col-lg-8">
        <!-- Профиль -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-white">
            <h5 class="mb-0">Мой профиль</h5>
          </div>
          <div class="card-body">
            <form @submit.prevent="saveProfile">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Имя</label>
                  <input type="text" class="form-control" v-model="profile.name">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Email</label>
                  <input type="email" class="form-control" v-model="profile.email" readonly>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Телефон</label>
                  <input type="tel" class="form-control" v-model="profile.phone">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Роль</label>
                  <input type="text" class="form-control" :value="profile.role_text" readonly>
                </div>
                <div class="col-12">
                  <button type="submit" class="btn btn-primary" :disabled="saving">
                    <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                    Сохранить
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <div class="col-lg-4">
        <!-- Статистика -->
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white">
            <h5 class="mb-0">Статистика</h5>
          </div>
          <div class="card-body">
            <ul class="list-unstyled mb-0">
              <li class="mb-2">
                <i class="bi bi-calendar-check text-primary me-2"></i>
                <span>Заявок подано:</span>
                <strong class="float-end">{{ stats.total || 0 }}</strong>
              </li>
              <li class="mb-2">
                <i class="bi bi-check-circle text-success me-2"></i>
                <span>Подтверждено:</span>
                <strong class="float-end">{{ stats.approved || 0 }}</strong>
              </li>
              <li class="mb-2">
                <i class="bi bi-trophy text-warning me-2"></i>
                <span>Участий:</span>
                <strong class="float-end">{{ stats.completed || 0 }}</strong>
              </li>
              <li>
                <i class="bi bi-clock text-info me-2"></i>
                <span>В ожидании:</span>
                <strong class="float-end">{{ stats.pending || 0 }}</strong>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'

export default {
  name: 'MyProfile',
  
  setup() {
    const profile = ref({
      name: 'Иван Петров',
      email: 'ivan@example.com',
      phone: '+7 (999) 123-45-67',
      role: 'participant',
      role_text: 'Участник'
    })
    const stats = ref({})
    const saving = ref(false)
    
    const saveProfile = async () => {
      saving.value = true
      try {
        await new Promise(resolve => setTimeout(resolve, 1000))
        alert('Профиль сохранен')
      } catch (err) {
        alert('Ошибка сохранения')
      } finally {
        saving.value = false
      }
    }
    
    onMounted(() => {
      // Загрузка статистики
      stats.value = {
        total: 5,
        approved: 3,
        completed: 2,
        pending: 1
      }
    })
    
    return {
      profile,
      stats,
      saving,
      saveProfile
    }
  }
}
</script>