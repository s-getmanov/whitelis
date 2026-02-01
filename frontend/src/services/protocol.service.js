import api from './api'

// Конфигурация режима работы
const USE_MOCK_DATA = true // Переключить на false для работы с реальным API
const MOCK_DELAY = 500 // Задержка имитации сетевого запроса (мс)

// Тестовые данные для стартового протокола
const mockStartProtocol = {
  event: {
    id: 1,
    title: 'Первый весенний пробег "Белый Лис"',
    date: '2024-04-15',
    location: 'Лесопарк "Сосновый бор", старт у центрального входа',
    discipline: 'Бег',
    status: 'upcoming'
  },
  distances: [
    {
      distance_id: 1,
      distance_name: '5км',
      full_name: '5км (5 km)',
      categories: [
        {
          category_id: 1,
          category_name: 'Мужчины 18-25',
          description: 'Мужчины, 18-25 лет',
          participants: [
            {
              registration_id: 101,
              user_id: 1,
              name: 'Иванов Александр',
              birth_year: '1998',
              birth_date: '1998-05-15',
              team_id: 1,
              team_name: 'Спартак',
              bib_number: 1,
              discipline: '5км',
              category: 'Мужчины 18-25',
              status: 'approved',
              notes: null
            },
            {
              registration_id: 102,
              user_id: 2,
              name: 'Петров Дмитрий',
              birth_year: '1999',
              birth_date: '1999-07-22',
              team_id: null,
              team_name: null,
              bib_number: 2,
              discipline: '5км',
              category: 'Мужчины 18-25',
              status: 'approved',
              notes: 'Оплата подтверждена'
            }
          ],
          count: 2
        },
        {
          category_id: 4,
          category_name: 'Женщины 18-25',
          description: 'Женщины, 18-25 лет',
          participants: [
            {
              registration_id: 103,
              user_id: 11,
              name: 'Смирнова Анна',
              birth_year: '2000',
              birth_date: '2000-03-10',
              team_id: 2,
              team_name: 'Звезда',
              bib_number: 3,
              discipline: '5км',
              category: 'Женщины 18-25',
              status: 'approved',
              notes: null
            }
          ],
          count: 1
        }
      ],
      count: 3
    },
    {
      distance_id: 2,
      distance_name: '10км',
      full_name: '10км (10 km)',
      categories: [
        {
          category_id: 2,
          category_name: 'Мужчины 26-40',
          description: 'Мужчины, 26-40 лет',
          participants: [
            {
              registration_id: 104,
              user_id: 3,
              name: 'Сидоров Михаил',
              birth_year: '1985',
              birth_date: '1985-11-30',
              team_id: 1,
              team_name: 'Спартак',
              bib_number: 4,
              discipline: '10км',
              category: 'Мужчины 26-40',
              status: 'approved',
              notes: null
            },
            {
              registration_id: 105,
              user_id: 4,
              name: 'Кузнецов Андрей',
              birth_year: '1990',
              birth_date: '1990-01-15',
              team_id: null,
              team_name: null,
              bib_number: 5,
              discipline: '10км',
              category: 'Мужчины 26-40',
              status: 'pending',
              notes: null
            }
          ],
          count: 2
        }
      ],
      count: 2
    }
  ],
  no_distance: [
    {
      registration_id: 106,
      user_id: 5,
      name: 'Новиков Сергей',
      birth_year: '1995',
      birth_date: '1995-08-20',
      team_id: null,
      team_name: null,
      bib_number: 6,
      discipline: null,
      category: 'Общий зачет',
      status: 'approved',
      notes: null
    }
  ],
  total_participants: 6,
  generated_at: new Date().toISOString(),
  type: 'start'
}

// Тестовые данные для итогового протокола
const mockFinalProtocol = {
  event: {
    id: 1,
    title: 'Первый весенний пробег "Белый Лис"',
    date: '2024-04-15',
    location: 'Лесопарк "Сосновый бор", старт у центрального входа',
    discipline: 'Бег',
    status: 'completed'
  },
  distances: [
    {
      distance_id: 1,
      distance_name: '5км',
      full_name: '5км (5 km)',
      categories: [
        {
          category_id: 1,
          category_name: 'Мужчины 18-25',
          description: 'Мужчины, 18-25 лет',
          participants: [
            {
              registration_id: 101,
              user_id: 1,
              name: 'Иванов Александр',
              birth_year: '1998',
              birth_date: '1998-05-15',
              team_id: 1,
              team_name: 'Спартак',
              bib_number: 1,
              discipline: '5км',
              category: 'Мужчины 18-25',
              status: 'approved',
              notes: null,
              result: {
                id: 201,
                result_time: '25:15.30',
                formatted_time: '25:15.30',
                time_in_seconds: 1515.30,
                finish_time: '2024-04-15 10:25:15',
                status: 'confirmed',
                status_text: 'Подтвержден'
              },
              place: 1,
              dns: false
            },
            {
              registration_id: 102,
              user_id: 2,
              name: 'Петров Дмитрий',
              birth_year: '1999',
              birth_date: '1999-07-22',
              team_id: null,
              team_name: null,
              bib_number: 2,
              discipline: '5км',
              category: 'Мужчины 18-25',
              status: 'approved',
              notes: 'Оплата подтверждена',
              result: null,
              place: null,
              dns: true
            }
          ],
          count: 2,
          finished_count: 1
        },
        {
          category_id: 4,
          category_name: 'Женщины 18-25',
          description: 'Женщины, 18-25 лет',
          participants: [
            {
              registration_id: 103,
              user_id: 11,
              name: 'Смирнова Анна',
              birth_year: '2000',
              birth_date: '2000-03-10',
              team_id: 2,
              team_name: 'Звезда',
              bib_number: 3,
              discipline: '5км',
              category: 'Женщины 18-25',
              status: 'approved',
              notes: null,
              result: {
                id: 202,
                result_time: '27:45.10',
                formatted_time: '27:45.10',
                time_in_seconds: 1665.10,
                finish_time: '2024-04-15 10:27:45',
                status: 'confirmed',
                status_text: 'Подтвержден'
              },
              place: 1,
              dns: false
            }
          ],
          count: 1,
          finished_count: 1
        }
      ],
      count: 3,
      finished_count: 2
    },
    {
      distance_id: 2,
      distance_name: '10км',
      full_name: '10км (10 km)',
      categories: [
        {
          category_id: 2,
          category_name: 'Мужчины 26-40',
          description: 'Мужчины, 26-40 лет',
          participants: [
            {
              registration_id: 104,
              user_id: 3,
              name: 'Сидоров Михаил',
              birth_year: '1985',
              birth_date: '1985-11-30',
              team_id: 1,
              team_name: 'Спартак',
              bib_number: 4,
              discipline: '10км',
              category: 'Мужчины 26-40',
              status: 'approved',
              notes: null,
              result: {
                id: 203,
                result_time: '52:30.45',
                formatted_time: '52:30.45',
                time_in_seconds: 3150.45,
                finish_time: '2024-04-15 10:52:30',
                status: 'confirmed',
                status_text: 'Подтвержден'
              },
              place: 1,
              dns: false
            },
            {
              registration_id: 105,
              user_id: 4,
              name: 'Кузнецов Андрей',
              birth_year: '1990',
              birth_date: '1990-01-15',
              team_id: null,
              team_name: null,
              bib_number: 5,
              discipline: '10км',
              category: 'Мужчины 26-40',
              status: 'approved',
              notes: null,
              result: {
                id: 204,
                result_time: '53:15.20',
                formatted_time: '53:15.20',
                time_in_seconds: 3195.20,
                finish_time: '2024-04-15 10:53:15',
                status: 'pending',
                status_text: 'Ожидает подтверждения'
              },
              place: 2,
              dns: false
            }
          ],
          count: 2,
          finished_count: 2
        }
      ],
      count: 2,
      finished_count: 2
    }
  ],
  statistics: {
    total_participants: 5,
    finished_participants: 4,
    dns_participants: 1,
    finish_percentage: 80.0
  },
  generated_at: new Date().toISOString(),
  type: 'final'
}

// Вспомогательная функция для имитации задержки сети
const delay = (ms) => new Promise(resolve => setTimeout(resolve, ms))

// Mock функции для тестового режима
const mockFunctions = {
  async getStartProtocol(eventId) {
    await delay(MOCK_DELAY)
    console.log(`[MOCK] getStartProtocol для eventId: ${eventId}`)
    return { ...mockStartProtocol, event: { ...mockStartProtocol.event, id: eventId } }
  },
  
  async getFinalProtocol(eventId) {
    await delay(MOCK_DELAY)
    console.log(`[MOCK] getFinalProtocol для eventId: ${eventId}`)
    return { ...mockFinalProtocol, event: { ...mockFinalProtocol.event, id: eventId } }
  },
  
  async assignNumbers(eventId, data) {
    await delay(MOCK_DELAY)
    console.log(`[MOCK] assignNumbers для eventId: ${eventId}`, data)
    return {
      message: data.method === 'random' 
        ? 'Стартовые номера назначены случайным образом' 
        : 'Существующие номера проверены',
      assigned_count: 5,
      method: data.method
    }
  },
  
  async clearNumbers(eventId) {
    await delay(MOCK_DELAY)
    console.log(`[MOCK] clearNumbers для eventId: ${eventId}`)
    return {
      message: 'Стартовые номера очищены',
      cleared_count: 5
    }
  },
  
  async exportPDF(eventId, params = {}) {
    await delay(MOCK_DELAY)
    console.log(`[MOCK] exportPDF для eventId: ${eventId}`, params)
    const protocolData = params.type === 'start' ? mockStartProtocol : mockFinalProtocol
    
    return {
      message: 'PDF сгенерирован успешно (тестовый режим)',
      data: protocolData,
      export_params: {
        event_id: eventId,
        type: params.type || 'final',
        distance_id: params.distance_id,
        category_id: params.category_id,
        format: 'pdf',
        generated_at: new Date().toISOString(),
        mock: true
      }
    }
  }
}

// Реальные функции для работы с API
const realFunctions = {
  async getStartProtocol(eventId) {
    try {
      const response = await api.get(`/protocols/start/${eventId}`)
      return response.data
    } catch (error) {
      console.error('ProtocolService getStartProtocol error:', error)
      throw error
    }
  },
  
  async getFinalProtocol(eventId) {
    try {
      const response = await api.get(`/protocols/final/${eventId}`)
      return response.data
    } catch (error) {
      console.error('ProtocolService getFinalProtocol error:', error)
      throw error
    }
  },
  
  async assignNumbers(eventId, data) {
    try {
      const response = await api.post(`/protocols/${eventId}/assign-numbers`, data)
      return response.data
    } catch (error) {
      console.error('ProtocolService assignNumbers error:', error)
      throw error
    }
  },
  
  async clearNumbers(eventId) {
    try {
      const response = await api.post(`/protocols/${eventId}/clear-numbers`)
      return response.data
    } catch (error) {
      console.error('ProtocolService clearNumbers error:', error)
      throw error
    }
  },
  
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

// Выбираем набор функций в зависимости от режима
const functions = USE_MOCK_DATA ? mockFunctions : realFunctions

// Экспортируем service
const ProtocolService = {
  // Получить стартовый протокол мероприятия
  async getStartProtocol(eventId) {
    return await functions.getStartProtocol(eventId)
  },
  
  // Получить итоговый протокол мероприятия
  async getFinalProtocol(eventId) {
    return await functions.getFinalProtocol(eventId)
  },
  
  // Назначить стартовые номера
  async assignNumbers(eventId, data) {
    return await functions.assignNumbers(eventId, data)
  },
  
  // Очистить стартовые номера
  async clearNumbers(eventId) {
    return await functions.clearNumbers(eventId)
  },
  
  // Экспорт в PDF
  async exportPDF(eventId, params = {}) {
    return await functions.exportPDF(eventId, params)
  },
  
  // Вспомогательные методы для отладки
  getMode() {
    return USE_MOCK_DATA ? 'MOCK' : 'REAL'
  },
  
  setMockMode(useMock) {
    // Это для глобального переключения - можно сделать более сложную логику
    console.log(`Режим переключен на: ${useMock ? 'MOCK' : 'REAL'}`)
    // Внимание: это работает только если пересоздать сервис
    // Для динамического переключения нужна более сложная реализация
  },
  
  // Получить пример тестовых данных (для разработки)
  getMockData(type = 'start') {
    return type === 'start' ? mockStartProtocol : mockFinalProtocol
  }
}

export default ProtocolService