<template>
  <div class="register-page min-vh-100 d-flex align-items-center">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
          <div class="card border-0 shadow-lg">
            <div class="card-body p-4">
              <!-- Логотип -->
              <div class="text-center mb-4">
                <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" 
                     style="width: 60px; height: 60px;">
                  <i class="bi bi-trophy-fill text-white fs-3"></i>
                </div>
                <h4 class="mt-3 fw-bold">Регистрация</h4>
                <p class="text-muted small">Создайте аккаунт для участия в мероприятиях</p>
              </div>

              <!-- Форма регистрации -->
              <form @submit.prevent="handleRegister">
                <div class="mb-3">
                  <label for="name" class="form-label">ФИО *</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="name" v-model="form.name" 
                           required placeholder="Иванов Иван Иванович">
                  </div>
                </div>

                <div class="mb-3">
                  <label for="email" class="form-label">Email *</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="email" v-model="form.email" 
                           required placeholder="user@example.com">
                  </div>
                </div>

                <div class="mb-3">
                  <label for="phone" class="form-label">Телефон</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                    <input type="tel" class="form-control" id="phone" v-model="form.phone" 
                           placeholder="+7 (999) 123-45-67">
                  </div>
                </div>

                <div class="mb-3">
                  <label for="team_name" class="form-label">Название команды (необязательно)</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-people"></i></span>
                    <input type="text" class="form-control" id="team_name" v-model="form.team_name" 
                           placeholder="Лисицы">
                  </div>
                  <div class="form-text">Можно указать позже в личном кабинете</div>
                </div>

                <div class="mb-3">
                  <label for="password" class="form-label">Пароль *</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input :type="showPassword ? 'text' : 'password'" class="form-control" id="password" 
                           v-model="form.password" required placeholder="Не менее 6 символов">
                    <button class="btn btn-outline-secondary" type="button" @click="showPassword = !showPassword">
                      <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                    </button>
                  </div>
                </div>

                <div class="mb-4">
                  <label for="password_confirmation" class="form-label">Подтверждение пароля *</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input :type="showPasswordConfirm ? 'text' : 'password'" class="form-control" 
                           id="password_confirmation" v-model="form.password_confirmation" 
                           required placeholder="Повторите пароль">
                    <button class="btn btn-outline-secondary" type="button" @click="showPasswordConfirm = !showPasswordConfirm">
                      <i :class="showPasswordConfirm ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                    </button>
                  </div>
                </div>

                <!-- Ошибки -->
                <div v-if="errors.general" class="alert alert-danger py-2">
                  <small><i class="bi bi-exclamation-circle me-1"></i>{{ errors.general }}</small>
                </div>

                <div v-if="errors.validation" class="alert alert-danger py-2">
                  <small>
                    <div v-for="(fieldErrors, fieldName) in errors.validation" :key="fieldName">
                      <div v-for="error in fieldErrors" :key="error">{{ error }}</div>
                    </div>
                  </small>
                </div>

                <!-- Кнопка регистрации -->
                <button type="submit" class="btn btn-primary w-100 py-2" :disabled="loading">
                  <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                  <span v-else><i class="bi bi-person-plus me-2"></i></span>
                  Зарегистрироваться
                </button>

                <!-- Ссылка на вход -->
                <div class="text-center mt-3">
                  <small class="text-muted">
                    Уже есть аккаунт? 
                    <router-link to="/login" class="text-decoration-none">Войти</router-link>
                  </small>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

export default {
  name: 'RegisterPage',
  
  setup() {
    const router = useRouter()
    
    const form = ref({
      name: '',
      email: '',
      phone: '',
      team_name: '',
      password: '',
      password_confirmation: ''
    })
    
    const showPassword = ref(false)
    const showPasswordConfirm = ref(false)
    const loading = ref(false)
    const errors = ref({
      general: '',
      validation: {}
    })
    
    const handleRegister = async () => {
      loading.value = true
      errors.value = { general: '', validation: {} }
      
      try {
        // Валидация на клиенте
        if (form.value.password !== form.value.password_confirmation) {
          throw new Error('Пароли не совпадают')
        }
        
        if (form.value.password.length < 6) {
          throw new Error('Пароль должен содержать не менее 6 символов')
        }
        
        // Отправка на сервер
        const response = await api.post('/users', {
          name: form.value.name,
          email: form.value.email,
          phone: form.value.phone,
          password: form.value.password,
          role: 'participant', // по умолчанию все участники
          status: 'active'
        })
        
        // Успешная регистрация - авторизуем и редиректим
        const user = {
          id: response.data.id,
          name: form.value.name,
          email: form.value.email,
          role: 'participant',
          team_id: null
        }
        
        localStorage.setItem('auth_user', JSON.stringify(user))
        
        // Редирект в личный кабинет
        router.push('/my/registrations')
        
      } catch (error) {
        if (error.response?.data?.errors) {
          errors.value.validation = error.response.data.errors
        } else if (error.response?.data?.error) {
          errors.value.general = error.response.data.message || error.response.data.error
        } else {
          errors.value.general = error.message || 'Ошибка регистрации'
        }
      } finally {
        loading.value = false
      }
    }
    
    return {
      form,
      showPassword,
      showPasswordConfirm,
      loading,
      errors,
      handleRegister
    }
  }
}
</script>

<style scoped>
.register-page {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.card {
  border-radius: 15px;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
}

.btn-primary:hover {
  opacity: 0.9;
}
</style>