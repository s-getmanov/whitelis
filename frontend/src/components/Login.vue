<template>
  <div class="login-page min-vh-100 d-flex align-items-center">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
          <div class="card border-0 shadow-lg">
            <div class="card-body p-5">
              <!-- Логотип -->
              <div class="text-center mb-4">
                <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center"
                  style="width: 60px; height: 60px;">
                  <i class="bi bi-trophy-fill text-white fs-3"></i>
                </div>
                <h4 class="mt-3 fw-bold">Белый Лис</h4>
                <p class="text-muted small">Спортивный клуб</p>
              </div>

              <!-- Форма входа -->
              <form @submit.prevent="handleLogin">
                <div class="mb-3">
                  <label for="email" class="form-label">Email</label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" class="form-control" id="email" v-model="form.email" required
                      placeholder="user@example.com">
                  </div>
                </div>

                <div class="mb-4">
                  <label for="password" class="form-label">Пароль</label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="bi bi-lock"></i>
                    </span>
                    <input :type="showPassword ? 'text' : 'password'" class="form-control" id="password"
                      v-model="form.password" required placeholder="Введите пароль">
                    <button class="btn btn-outline-secondary" type="button" @click="showPassword = !showPassword">
                      <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                    </button>
                  </div>
                </div>

                <!-- Ошибка -->
                <div v-if="error" class="alert alert-danger py-2">
                  <small><i class="bi bi-exclamation-circle me-1"></i>{{ error }}</small>
                </div>

                <!-- Кнопка входа -->
                <button type="submit" class="btn btn-primary w-100 py-2" :disabled="loading">
                  <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                  <span v-else><i class="bi bi-box-arrow-in-right me-2"></i></span>
                  Войти
                </button>

                <!-- Демо-доступ -->
                <<div class="mt-4 pt-3 border-top">
                  <p class="text-center small text-muted mb-2">Демо-доступ:</p>
                  <div class="d-flex flex-column gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" @click="fillDemo('admin')">
                      Администратор
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm" @click="fillDemo('participant')">
                      Участник
                    </button>

                    <button type="button" class="btn btn-outline-warning btn-sm" @click="fillDemo('team_manager')">
                      Представитель команды
                    </button>
                    <button type="button" class="btn btn-outline-info btn-sm" @click="fillDemo('judge')">
                      Судья
                    </button>
                  </div>
            </div>
            </form>

            <!-- Ссылка на сайт -->
            <div class="text-center mt-4">
              <router-link to="/" class="text-decoration-none small">
                <i class="bi bi-arrow-left me-1"></i>На главную
              </router-link>
            </div>
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

export default {
  name: 'LoginPage',

  setup() {
    const router = useRouter()
    const form = ref({
      email: '',
      password: ''
    })
    const showPassword = ref(false)
    const loading = ref(false)
    const error = ref('')

    // Демо-данные
    const demoUsers = {
      admin: {
        email: 'admin@beliy-lis.ru',
        password: 'password123',
        role: 'admin',
        team_id: null
      },
      participant: {
        email: 'ivan@example.com',
        password: 'password123',
        role: 'participant',
        team_id: 1 // пример
      },
      // ДОБАВИТЬ team_manager
      team_manager: {
        email: 'team_manager@demo.ru',
        password: 'demo',
        role: 'team_manager',
        team_id: 1, // ID команды "Лисицы"
        team_name: 'Лисицы'
      },
      judge: {
        email: 'judge@demo.ru',
        password: 'demo',
        role: 'judge'
      }
    };
    const fillDemo = (role) => {
      form.value = { ...demoUsers[role] }
    }

    const handleLogin = async () => {
      loading.value = true
      error.value = ''

      try {
        // Заглушка для авторизации
        // В будущем: вызов API /api/login

        // Имитация запроса
        await new Promise(resolve => setTimeout(resolve, 1000))

        // Проверяем демо-данные
        let user = null
        for (const role in demoUsers) {
          if (demoUsers[role].email === form.value.email &&
            demoUsers[role].password === form.value.password) {
            user = demoUsers[role]
            break
          }
        }

        if (!user) {
          throw new Error('Неверный email или пароль')
        }

        // Сохраняем данные пользователя (заглушка)
        localStorage.setItem('auth_user', JSON.stringify({
          ...user,
          team_id: user.team_id || null,
          team_name: user.team_name || null
        }));

        // Редирект в зависимости от роли
        if (user.role === 'admin') {
          router.push('/admin/events')
        } else if (user.role === 'judge') {
          router.push('/judge/finish')
        } else if (user.role === 'team_manager') {
          router.push('/my/registrations')
        } else {
          router.push('/my/registrations')
        }

      } catch (err) {
        error.value = err.message
      } finally {
        loading.value = false
      }
    }

    return {
      form,
      showPassword,
      loading,
      error,
      fillDemo,
      handleLogin
    }
  }
}
</script>

<style scoped>
.login-page {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.card {
  border-radius: 15px;
}

.input-group-text {
  background-color: #f8f9fa;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
}

.btn-primary:hover {
  opacity: 0.9;
}
</style>