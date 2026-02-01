import api from './api'

const PublicationService = {
  // Получить все публикации
  async getAll(params = {}) {
    const response = await api.get('/publications', { params })
    return response.data
  },
  
  // Получить публикацию по ID
  async getById(id) {
    const response = await api.get(`/publications/${id}`)
    return response.data
  },
  
  // Получить публикацию по slug
  async getBySlug(slug) {
    const response = await api.get(`/publications/slug/${slug}`)
    return response.data
  },
  
  // Создать публикацию
  async create(publicationData) {
    const response = await api.post('/publications', publicationData)
    return response.data
  },
  
  // Обновить публикацию
  async update(id, publicationData) {
    const response = await api.put(`/publications/${id}`, publicationData)
    return response.data
  },
  
  // Удалить публикацию
  async delete(id) {
    const response = await api.delete(`/publications/${id}`)
    return response.data
  },
  
  // Загрузить изображение
  async uploadImage(file) {
    const formData = new FormData()
    formData.append('image', file)
    
    const response = await api.post('/publications/upload-image', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    return response.data
  },
  
  // Увеличить счетчик просмотров
  async incrementViews(slug) {
    const response = await api.post(`/publications/${slug}/increment-views`)
    return response.data
  }
}

export default PublicationService