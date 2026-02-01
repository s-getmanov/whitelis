import api from './api'

const UserService = {
  // Получить список пользователей
  getAll(params = {}) {
    return api.get('/users', { params })
  },
  
  // Получить одного пользователя
  get(id) {
    return api.get(`/users/${id}`)
  },
  
  // Создать пользователя
  create(userData) {
    return api.post('/users', userData)
  },
  
  // Обновить пользователя
  update(id, userData) {
    return api.put(`/users/${id}`, userData)
  },
  
  // Удалить пользователя
  delete(id) {
    return api.delete(`/users/${id}`)
  }
}

export default UserService