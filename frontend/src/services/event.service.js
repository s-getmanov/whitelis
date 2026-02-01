import api from './api'

// Теперь работаем ТОЛЬКО с реальным API
const EventService = {
  // Получить все мероприятия
  async getAll(params = {}) {
    const response = await api.get('/events', { params })
    return response.data
  },
  
  // Получить мероприятие по ID
  async getById(id) {
    const response = await api.get(`/events/${id}`)
    return response.data
  },
  
  // Создать мероприятие
  async create(eventData) {
    const response = await api.post('/events', eventData)
    return response.data
  },
  
  // Обновить мероприятие
  async update(id, eventData) {
    const response = await api.put(`/events/${id}`, eventData)
    return response.data
  },
  
  // Удалить мероприятие
  async delete(id) {
    const response = await api.delete(`/events/${id}`)
    return response.data
  },
  
  // Получить статистику
  async getStats() {
    const response = await api.get('/events/stats')
    return response.data
  },
  
  // Получить недавние мероприятия
  async getRecent(limit = 3) {
    const response = await api.get('/events/recent', { params: { limit } })
    return response.data
  }
}

export default EventService