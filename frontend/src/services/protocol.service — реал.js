import api from './api'

const ProtocolService = {
  // Получить стартовый протокол мероприятия
  async getStartProtocol(eventId) {
    try {
      const response = await api.get(`/protocols/start/${eventId}`)
      return response.data
    } catch (error) {
      console.error('ProtocolService getStartProtocol error:', error)
      throw error
    }
  },
  
  // Получить итоговый протокол мероприятия
  async getFinalProtocol(eventId) {
    try {
      const response = await api.get(`/protocols/final/${eventId}`)
      return response.data
    } catch (error) {
      console.error('ProtocolService getFinalProtocol error:', error)
      throw error
    }
  },
  
  // Назначить стартовые номера
  async assignNumbers(eventId, data) {
    try {
      const response = await api.post(`/protocols/${eventId}/assign-numbers`, data)
      return response.data
    } catch (error) {
      console.error('ProtocolService assignNumbers error:', error)
      throw error
    }
  },
  
  // Очистить стартовые номера
  async clearNumbers(eventId) {
    try {
      const response = await api.post(`/protocols/${eventId}/clear-numbers`)
      return response.data
    } catch (error) {
      console.error('ProtocolService clearNumbers error:', error)
      throw error
    }
  },
  
  // Экспорт в PDF
  async exportPDF(eventId, params = {}) {
    try {
      const response = await api.get(`/protocols/${eventId}/export/pdf`, { params })
      return response.data
    } catch (error) {
      console.error('ProtocolService exportPDF error:', error)
      throw error
    }
  }
}

export default ProtocolService