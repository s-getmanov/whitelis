import axios from 'axios'

const getBaseUrl = () => {
  // Если находимся в разработке (localhost)
  if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    return 'http://localhost:8000/api'
  }
  
  // На продакшене используем текущий домен
  // Убираем порт если есть
  const protocol = window.location.protocol
  const host = window.location.hostname
  
  return `${protocol}//${host}/api`
}

// Создаем экземпляр axios с базовыми настройками
const api = axios.create({
  baseURL: getBaseUrl(), 
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Интерцептор для добавления токена (если будет авторизация)
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Интерцептор для обработки ошибок
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Очистка данных аутентификации
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api