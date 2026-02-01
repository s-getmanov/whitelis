import api from './api'

const ResultService = {
  // Получить результаты
  getAll(params = {}) {
    return api.get('/results', { params })
  },
  
  // Получить результаты мероприятия
  getByEvent(eventId, params = {}) {
    return api.get('/results', { params: { ...params, event_id: eventId } })
  },
  
  // Фиксация результата
  create(data) {
    return api.post('/results', data)
  },
  
  // Массовая синхронизация (офлайн)
  bulkSync(data) {
    return api.post('/results/bulk-sync', data)
  },
  
  // Обновление результата
  update(id, data) {
    return api.put(`/results/${id}`, data)
  },
  
  // Удаление результата
  delete(id) {
    return api.delete(`/results/${id}`)
  },
  
  // Несинхронизированные результаты (для офлайн)
  getUnsynced() {
    return api.get('/results/unsynced')
  },
  
  // Подтвердить все результаты мероприятия
  confirmAll(eventId) {
    return api.post(`/results/event/${eventId}/confirm-all`)
  },
  
  // Преобразование времени в формат MM:SS.ss
  formatTime(seconds) {
    if (!seconds && seconds !== 0) return ''
    
    const mins = Math.floor(seconds / 60)
    const secs = (seconds % 60).toFixed(2)
    return `${mins}:${secs.padStart(5, '0')}`
  },
  
  // Парсинг времени из строки
  parseTime(timeString) {
    if (!timeString) return 0
    
    // Формат MM:SS.ss
    if (timeString.includes(':')) {
      const parts = timeString.split(':')
      const minutes = parseInt(parts[0]) || 0
      const seconds = parseFloat(parts[1]) || 0
      return (minutes * 60) + seconds
    }
    
    // Уже в секундах
    return parseFloat(timeString) || 0
  }
}

export default ResultService