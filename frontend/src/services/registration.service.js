import api from './api'

class RegistrationService {
  async getAll(params = {}) {
    try {
      const response = await api.get('/registrations', { params })
      // API возвращает { data: [...], meta: {...} }
      return {
        data: response.data.data || [],
        meta: response.data.meta || { total: 0, page: 1, pages: 1 }
      }
    } catch (error) {
      console.error('RegistrationService getAll error:', error)
      throw error
    }
  }

  async get(id) {
    try {
      const response = await api.get(`/registrations/${id}`)
      return response.data
    } catch (error) {
      console.error('RegistrationService get error:', error)
      throw error
    }
  }

  async create(data) {
    try {
      const response = await api.post('/registrations', data)
      return response.data
    } catch (error) {
      console.error('RegistrationService create error:', error)
      throw error
    }
  }

  async update(id, data) {
    try {
      const response = await api.put(`/registrations/${id}`, data)
      return response.data
    } catch (error) {
      console.error('RegistrationService update error:', error)
      throw error
    }
  }

  async delete(id) {
    try {
      const response = await api.delete(`/registrations/${id}`)
      return response.data
    } catch (error) {
      console.error('RegistrationService delete error:', error)
      throw error
    }
  }

  async bulkUpdate(data) {
    try {
      const response = await api.post('/registrations/bulk-update', data)
      return response.data
    } catch (error) {
      console.error('RegistrationService bulkUpdate error:', error)
      throw error
    }
  }
}

export default new RegistrationService()