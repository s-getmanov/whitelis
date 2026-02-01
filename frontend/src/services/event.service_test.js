import api from './api'

// Конфигурация для демо-режима
const USE_DEMO_DATA = true

// Демо данные (имитация API)
const demoEvents = [
  {
    id: 1,
    title: 'Зимний марафон "Ледяная миля"',
    description: 'Лыжные гонки по живописной лесной трассе',
    date: '2024-12-25',
    location: 'Лесопарк "Сосновый бор"',
    discipline: 'Лыжные гонки',
    status: 'upcoming',
    participants_count: 120,
    registrations_count: 85,
    distances: ['5км', '10км', '21км'],
    categories: ['Мужчины', 'Женщины', 'Юниоры']
  },
  {
    id: 2,
    title: 'Спортивное ориентирование "Лисья тропа"',
    description: 'Для всех возрастных категорий',
    date: '2025-01-15',
    location: 'Городской парк',
    discipline: 'Ориентирование',
    status: 'upcoming',
    participants_count: 80,
    registrations_count: 45,
    distances: ['Короткая', 'Длинная'],
    categories: ['18-25 лет', '26-40 лет', '40+ лет']
  },
  {
    id: 3,
    title: 'Осенний кросс "Золотая листва"',
    description: 'Ежегодный кросс по пересеченной местности',
    date: '2024-10-10',
    location: 'Лесной массив',
    discipline: 'Бег по пересеченной местности',
    status: 'completed',
    participants_count: 156,
    registrations_count: 156,
    distances: ['5км', '10км'],
    categories: ['Общий зачет']
  },
  {
    id: 4,
    title: 'Тренировка по бегу',
    description: 'Бесплатные занятия с профессиональным тренером',
    date: '2024-12-20',
    location: 'Стадион "Юность"',
    discipline: 'Бег',
    status: 'active',
    participants_count: 25,
    registrations_count: 18,
    distances: ['Разминка', 'Интервалы'],
    categories: ['Начинающие', 'Продвинутые']
  }
]

// Демо статистика
const demoStats = {
  events: 12,
  activeEvents: 3,
  participants: 245,
  newParticipants: 15,
  registrations: 48,
  pendingRegistrations: 5,
  results: 156,
  todayResults: 8
}

// Имитация задержки API
const delay = (ms) => new Promise(resolve => setTimeout(resolve, ms))

// Сервис мероприятий
const EventService = {
  // Получить все мероприятия
  async getAll(params = {}) {
    if (USE_DEMO_DATA) {
      await delay(500) // Имитация задержки сети
      
      let events = [...demoEvents]
      
      // Фильтрация по статусу
      if (params.status) {
        events = events.filter(event => event.status === params.status)
      }
      
      // Поиск
      if (params.search) {
        const searchLower = params.search.toLowerCase()
        events = events.filter(event => 
          event.title.toLowerCase().includes(searchLower) ||
          event.description.toLowerCase().includes(searchLower) ||
          event.location.toLowerCase().includes(searchLower)
        )
      }
      
      // Пагинация
      const page = params.page || 1
      const limit = params.limit || 6
      const start = (page - 1) * limit
      const end = start + limit
      
      return {
        data: events.slice(start, end),
        meta: {
          total: events.length,
          page,
          limit,
          pages: Math.ceil(events.length / limit)
        }
      }
    }
    
    // Реальный API
    const response = await api.get('/events', { params })
    return response.data
  },
  
  // Получить мероприятие по ID
  async getById(id) {
    if (USE_DEMO_DATA) {
      await delay(300)
      const event = demoEvents.find(e => e.id === parseInt(id))
      return event || null
    }
    
    const response = await api.get(`/events/${id}`)
    return response.data
  },
  
  // Создать мероприятие
  async create(eventData) {
    if (USE_DEMO_DATA) {
      await delay(500)
      const newEvent = {
        id: demoEvents.length + 1,
        ...eventData,
        participants_count: 0,
        registrations_count: 0,
        created_at: new Date().toISOString()
      }
      demoEvents.unshift(newEvent)
      return newEvent
    }
    
    const response = await api.post('/events', eventData)
    return response.data
  },
  
  // Обновить мероприятие
  async update(id, eventData) {
    if (USE_DEMO_DATA) {
      await delay(400)
      const index = demoEvents.findIndex(e => e.id === parseInt(id))
      if (index !== -1) {
        demoEvents[index] = { ...demoEvents[index], ...eventData }
        return demoEvents[index]
      }
      return null
    }
    
    const response = await api.put(`/events/${id}`, eventData)
    return response.data
  },
  
  // Удалить мероприятие
  async delete(id) {
    if (USE_DEMO_DATA) {
      await delay(400)
      const index = demoEvents.findIndex(e => e.id === parseInt(id))
      if (index !== -1) {
        demoEvents.splice(index, 1)
        return true
      }
      return false
    }
    
    await api.delete(`/events/${id}`)
    return true
  },
  
  // Получить статистику
  async getStats() {
    if (USE_DEMO_DATA) {
      await delay(300)
      return demoStats
    }
    
    const response = await api.get('/events/stats')
    return response.data
  },
  
  // Получить недавние мероприятия
  async getRecent(limit = 3) {
    if (USE_DEMO_DATA) {
      await delay(200)
      return demoEvents.slice(0, limit)
    }
    
    const response = await api.get('/events/recent', { params: { limit } })
    return response.data
  }
}

export default EventService