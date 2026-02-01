const OFFLINE_STORAGE_NAME = 'JudgeResultsDB'
const OFFLINE_STORAGE_VERSION = 1
const OFFLINE_STORAGE_KEY = 'judge_offline_results'

class OfflineStorage {
  constructor() {
    this.db = null
    this.initPromise = null
  }

  // Инициализация IndexedDB с кэшированием промиса
  async initDB() {
    if (this.initPromise) {
      return this.initPromise
    }

    this.initPromise = new Promise((resolve, reject) => {
      const request = indexedDB.open(OFFLINE_STORAGE_NAME, OFFLINE_STORAGE_VERSION)
      
      request.onerror = (event) => {
        console.error('Ошибка открытия IndexedDB:', event.target.error)
        reject(event.target.error)
      }
      
      request.onsuccess = (event) => {
        this.db = event.target.result
        console.log('IndexedDB успешно инициализирован')
        resolve(this.db)
      }
      
      request.onupgradeneeded = (event) => {
        const db = event.target.result
        
        // Создаем хранилище для результатов
        if (!db.objectStoreNames.contains('results')) {
          const store = db.createObjectStore('results', { keyPath: 'id', autoIncrement: true })
          store.createIndex('registration_id', 'registration_id', { unique: false })
          store.createIndex('synced', 'synced', { unique: false })
          store.createIndex('created_at', 'created_at', { unique: false })
        }
        
        // Хранилище для мероприятий (кэш)
        if (!db.objectStoreNames.contains('events')) {
          db.createObjectStore('events', { keyPath: 'id' })
        }
        
        // Хранилище для регистраций (кэш)
        if (!db.objectStoreNames.contains('registrations')) {
          const regStore = db.createObjectStore('registrations', { keyPath: 'id' })
          regStore.createIndex('bib_number', 'bib_number', { unique: false })
          regStore.createIndex('event_id', 'event_id', { unique: false })
        }
      }
    })

    return this.initPromise
  }

  // Проверка инициализации базы данных
  async ensureDB() {
    if (!this.db) {
      await this.initDB()
    }
    return this.db
  }
  
  // Сохранить результат офлайн
  async saveResult(result) {
    try {
      const db = await this.ensureDB()
      return new Promise((resolve, reject) => {
        const transaction = db.transaction(['results'], 'readwrite')
        const store = transaction.objectStore('results')
        
        // Добавляем метку времени и флаг синхронизации
        const resultToSave = {
          ...result,
          synced: false,
          created_at: new Date().toISOString(),
          local_id: `local_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
        }
        
        const request = store.add(resultToSave)
        
        request.onsuccess = () => resolve({ 
          id: request.result, 
          ...resultToSave 
        })
        
        request.onerror = (event) => {
          console.error('Ошибка сохранения в IndexedDB:', event.target.error)
          reject(event.target.error)
        }
      })
    } catch (error) {
      console.error('Ошибка сохранения в офлайн-хранилище:', error)
      // Fallback: сохраняем в localStorage
      return this.saveToLocalStorage(result)
    }
  }
  
  // Получить все несинхронизированные результаты
  async getUnsyncedResults() {
    try {
      const db = await this.ensureDB()
      
      return new Promise((resolve, reject) => {
        const transaction = db.transaction(['results'], 'readonly')
        const store = transaction.objectStore('results')
        
        // Проверяем существование индекса
        const indexNames = store.indexNames
        if (indexNames.contains('synced')) {
          // Используем курсор для итерации по результатам
          const index = store.index('synced')
          const request = index.openCursor()
          const unsyncedResults = []
          
          request.onsuccess = (event) => {
            const cursor = event.target.result
            if (cursor) {
              // Проверяем, что synced === false
              if (cursor.value.synced === false) {
                unsyncedResults.push(cursor.value)
              }
              cursor.continue()
            } else {
              console.log('Найдено несинхронизированных результатов (через курсор):', unsyncedResults.length)
              resolve(unsyncedResults)
            }
          }
          
          request.onerror = (event) => {
            console.warn('Ошибка при использовании курсора:', event.target.error)
            // Fallback: получаем все и фильтруем
            this.getAllAndFilter(store).then(resolve).catch(reject)
          }
        } else {
          // Индекс не существует, фильтруем вручную
          this.getAllAndFilter(store).then(resolve).catch(reject)
        }
      })
    } catch (error) {
      console.error('Ошибка в getUnsyncedResults:', error)
      // Fallback: пытаемся получить из localStorage
      return this.getFromLocalStorage()
    }
  }

  // Вспомогательный метод: получить все результаты и отфильтровать
  async getAllAndFilter(store) {
    return new Promise((resolve, reject) => {
      const request = store.getAll()
      
      request.onsuccess = () => {
        const allResults = request.result || []
        const unsynced = allResults.filter(r => r.synced === false)
        console.log('Найдено несинхронизированных результатов (после фильтрации):', unsynced.length)
        resolve(unsynced)
      }
      
      request.onerror = (event) => reject(event.target.error)
    })
  }
  
  // Пометить результаты как синхронизированные
  async markAsSynced(localIds) {
    if (!localIds || localIds.length === 0) return true
    
    try {
      const db = await this.ensureDB()
      
      return new Promise((resolve, reject) => {
        const transaction = db.transaction(['results'], 'readwrite')
        const store = transaction.objectStore('results')
        
        let processed = 0
        let errors = []
        
        localIds.forEach(localId => {
          const request = store.get(localId)
          
          request.onsuccess = () => {
            const data = request.result
            if (data) {
              data.synced = true
              const updateRequest = store.put(data)
              
              updateRequest.onerror = () => {
                errors.push(`Ошибка обновления ${localId}: ${updateRequest.error}`)
                checkCompletion()
              }
              
              updateRequest.onsuccess = () => {
                processed++
                checkCompletion()
              }
            } else {
              processed++
              checkCompletion()
            }
          }
          
          request.onerror = () => {
            errors.push(`Ошибка получения ${localId}: ${request.error}`)
            processed++
            checkCompletion()
          }
        })
        
        const checkCompletion = () => {
          if (processed === localIds.length) {
            if (errors.length > 0) {
              console.warn('Частичные ошибки при синхронизации:', errors)
            }
            resolve(true)
          }
        }
      })
    } catch (error) {
      console.error('Ошибка обновления статуса синхронизации:', error)
      // Fallback: обновляем localStorage
      return this.markLocalStorageAsSynced(localIds)
    }
  }
  
  // Удалить синхронизированные результаты
  async clearSyncedResults() {
    try {
      const db = await this.ensureDB()
      
      return new Promise((resolve, reject) => {
        const transaction = db.transaction(['results'], 'readwrite')
        const store = transaction.objectStore('results')
        const index = store.index('synced')
        
        // Используем IDBKeyRange.only(true) для получения только синхронизированных
        const request = index.openCursor(IDBKeyRange.only(true))
        
        let deletedCount = 0
        
        request.onsuccess = (event) => {
          const cursor = event.target.result
          if (cursor) {
            cursor.delete()
            deletedCount++
            cursor.continue()
          } else {
            console.log(`Удалено синхронизированных записей: ${deletedCount}`)
            resolve(deletedCount)
          }
        }
        
        request.onerror = (event) => {
          console.error('Ошибка очистки офлайн-хранилища:', event.target.error)
          reject(event.target.error)
        }
      })
    } catch (error) {
      console.error('Ошибка очистки офлайн-хранилища:', error)
      return 0
    }
  }
  
  // Получить все результаты для мероприятия
  async getResultsByEvent(eventId) {
    try {
      const db = await this.ensureDB()
      
      return new Promise((resolve, reject) => {
        const transaction = db.transaction(['results'], 'readonly')
        const store = transaction.objectStore('results')
        const request = store.getAll()
        
        request.onsuccess = () => {
          const allResults = request.result || []
          const eventResults = allResults.filter(r => r.event_id === eventId)
          resolve(eventResults)
        }
        
        request.onerror = (event) => reject(event.target.error)
      })
    } catch (error) {
      console.error('Ошибка получения результатов по мероприятию:', error)
      return []
    }
  }
  
  // Fallback: localStorage
  saveToLocalStorage(result) {
    try {
      const results = JSON.parse(localStorage.getItem(OFFLINE_STORAGE_KEY) || '[]')
      const resultWithMeta = {
        ...result,
        synced: false,
        created_at: new Date().toISOString(),
        local_id: `local_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
      }
      results.push(resultWithMeta)
      localStorage.setItem(OFFLINE_STORAGE_KEY, JSON.stringify(results))
      console.log('Результат сохранен в localStorage:', resultWithMeta)
      return Promise.resolve(resultWithMeta)
    } catch (error) {
      console.error('Ошибка сохранения в localStorage:', error)
      return Promise.reject(error)
    }
  }

  getFromLocalStorage() {
    try {
      const results = JSON.parse(localStorage.getItem(OFFLINE_STORAGE_KEY) || '[]')
      const unsynced = results.filter(r => r.synced === false)
      console.log('Несинхронизированных результатов в localStorage:', unsynced.length)
      return Promise.resolve(unsynced)
    } catch (error) {
      console.error('Ошибка чтения из localStorage:', error)
      return Promise.resolve([])
    }
  }

  markLocalStorageAsSynced(localIds) {
    try {
      const results = JSON.parse(localStorage.getItem(OFFLINE_STORAGE_KEY) || '[]')
      const updatedResults = results.map(r => 
        localIds.includes(r.local_id) ? { ...r, synced: true } : r
      )
      localStorage.setItem(OFFLINE_STORAGE_KEY, JSON.stringify(updatedResults))
      return Promise.resolve(true)
    } catch (error) {
      console.error('Ошибка обновления localStorage:', error)
      return Promise.resolve(false)
    }
  }

  // Очистить все офлайн данные (для отладки)
  async clearAll() {
    try {
      const db = await this.ensureDB()
      
      return new Promise((resolve, reject) => {
        const transaction = db.transaction(['results', 'events', 'registrations'], 'readwrite')
        
        transaction.objectStore('results').clear()
        transaction.objectStore('events').clear()
        transaction.objectStore('registrations').clear()
        
        transaction.oncomplete = () => {
          console.log('Все офлайн данные очищены')
          resolve()
        }
        
        transaction.onerror = (event) => {
          console.error('Ошибка очистки всех данных:', event.target.error)
          reject(event.target.error)
        }
      })
    } catch (error) {
      console.error('Ошибка очистки всех данных:', error)
      // Очищаем localStorage
      localStorage.removeItem(OFFLINE_STORAGE_KEY)
      return Promise.resolve()
    }
  }
}

// Создаем и экспортируем экземпляр
const offlineStorage = new OfflineStorage()
export default offlineStorage