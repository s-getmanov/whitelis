<template>
  <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5)">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            {{ publication ? 'Редактирование публикации' : 'Новая публикация' }}
          </h5>
          <button type="button" class="btn-close" @click="$emit('close')" :disabled="loading"></button>
        </div>
        
        <div class="modal-body">
          <form @submit.prevent="handleSubmit">
            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label">Заголовок *</label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="form.title"
                  required
                  placeholder="Введите заголовок публикации"
                  @input="generateSlug"
                >
              </div>
              
              <div class="col-md-4">
                <label class="form-label">Статус</label>
                <select class="form-select" v-model="form.status">
                  <option value="draft">Черновик</option>
                  <option value="published">Опубликовано</option>
                  <option value="archived">В архиве</option>
                </select>
              </div>
              
              <div class="col-md-6">
                <label class="form-label">Тип публикации</label>
                <select class="form-select" v-model="form.type">
                  <option value="news">Новость</option>
                  <option value="announcement">Анонс</option>
                  <option value="article">Статья</option>
                  <option value="page">Страница</option>
                </select>
              </div>
              
              <div class="col-md-6">
                <label class="form-label">Дата публикации</label>
                <input 
                  type="datetime-local" 
                  class="form-control" 
                  v-model="form.published_at"
                  :disabled="form.status !== 'published'"
                >
              </div>
              
              <div class="col-12">
                <label class="form-label">Краткое описание (анонс)</label>
                <textarea 
                  class="form-control" 
                  rows="2"
                  v-model="form.excerpt"
                  placeholder="Краткое описание для превью (необязательно)"
                  maxlength="300"
                ></textarea>
                <small class="text-muted">{{ form.excerpt?.length || 0 }}/300 символов</small>
              </div>
              
              <div class="col-12">
                <label class="form-label">Содержимое *</label>
                <div class="border rounded p-2 bg-light">
                  <!-- Простой редактор -->
                  <div class="d-flex gap-2 mb-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="formatText('bold')">
                      <i class="bi bi-type-bold"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="formatText('italic')">
                      <i class="bi bi-type-italic"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="formatText('link')">
                      <i class="bi bi-link"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="formatText('image')">
                      <i class="bi bi-image"></i>
                    </button>
                  </div>
                  
                  <textarea 
                    class="form-control" 
                    rows="10"
                    v-model="form.content"
                    required
                    placeholder="Введите содержимое публикации"
                    ref="contentEditor"
                    @input="updatePreview"
                  ></textarea>
                </div>
              </div>
              
              <div class="col-12">
                <label class="form-label">Предпросмотр содержимого</label>
                <div class="border rounded p-3 bg-white" style="min-height: 100px; max-height: 200px; overflow-y: auto;">
                  <div v-html="sanitizedPreview"></div>
                </div>
              </div>
              
              <div class="col-12">
                <div class="form-check">
                  <input 
                    type="checkbox" 
                    class="form-check-input" 
                    v-model="form.is_pinned"
                    id="is_pinned"
                  >
                  <label class="form-check-label" for="is_pinned">
                    Закрепить публикацию
                  </label>
                </div>
              </div>
            </div>
          </form>
        </div>
        
        <div class="modal-footer">
          <button 
            type="button" 
            class="btn btn-secondary" 
            @click="$emit('close')"
            :disabled="loading"
          >
            Отмена
          </button>
          <button 
            type="button" 
            class="btn btn-primary" 
            @click="handleSubmit"
            :disabled="loading"
          >
            <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
            {{ publication ? 'Сохранить' : 'Создать' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import DOMPurify from 'dompurify'

export default {
  name: 'PublicationModal',
  
  props: {
    publication: {
      type: Object,
      default: null
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  
  emits: ['save', 'close'],
  
  setup(props, { emit }) {
    const form = ref({
      title: '',
      excerpt: '',
      content: '',
      type: 'news',
      status: 'draft',
      is_pinned: false,
      published_at: null
    })
    
    const contentEditor = ref(null)
    const preview = ref('')
    
    // Простой санитайзер для превью
    const sanitizedPreview = computed(() => {
      return DOMPurify.sanitize(preview.value, {
        ALLOWED_TAGS: ['b', 'i', 'strong', 'em', 'a', 'p', 'br', 'img', 'h1', 'h2', 'h3', 'ul', 'ol', 'li'],
        ALLOWED_ATTR: ['href', 'target', 'rel', 'src', 'alt', 'title']
      })
    })
    
    // Инициализация формы
    onMounted(() => {
      if (props.publication) {
        form.value = { ...props.publication }
        preview.value = form.value.content
      } else {
        // Устанавливаем дату публикации по умолчанию (текущее время)
        form.value.published_at = new Date().toISOString().slice(0, 16)
      }
    })
    
    const generateSlug = () => {
      // Генерация slug из заголовка
      const slug = form.value.title
        .toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/--+/g, '-')
        .trim()
      
      // Для реального приложения здесь нужно проверять уникальность
      console.log('Сгенерированный slug:', slug)
    }
    
    const formatText = (type) => {
      if (!contentEditor.value) return
      
      const textarea = contentEditor.value
      const start = textarea.selectionStart
      const end = textarea.selectionEnd
      const selectedText = textarea.value.substring(start, end)
      let formattedText = selectedText
      
      switch (type) {
        case 'bold':
          formattedText = `**${selectedText}**`
          break
        case 'italic':
          formattedText = `*${selectedText}*`
          break
        case 'link':
          const url = prompt('Введите URL:')
          if (url) {
            formattedText = `[${selectedText}](${url})`
          }
          break
        case 'image':
          const imageUrl = prompt('Введите URL изображения:')
          if (imageUrl) {
            formattedText = `![${selectedText}](${imageUrl})`
          }
          break
      }
      
      // Вставляем отформатированный текст
      const newValue = textarea.value.substring(0, start) + 
                      formattedText + 
                      textarea.value.substring(end)
      
      textarea.value = newValue
      form.value.content = newValue
      
      // Восстанавливаем позицию курсора
      setTimeout(() => {
        textarea.selectionStart = start + formattedText.length
        textarea.selectionEnd = start + formattedText.length
        textarea.focus()
      }, 0)
      
      updatePreview()
    }
    
    const updatePreview = () => {
      preview.value = form.value.content
        // Простой Markdown парсер для превью
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" target="_blank">$1</a>')
        .replace(/!\[(.*?)\]\((.*?)\)/g, '<img src="$2" alt="$1" class="img-fluid">')
        .replace(/\n/g, '<br>')
    }
    
    const handleSubmit = () => {
      // Валидация
      if (!form.value.title.trim()) {
        alert('Введите заголовок публикации')
        return
      }
      if (!form.value.content.trim()) {
        alert('Введите содержимое публикации')
        return
      }
      
      // Если статус "опубликовано" и нет даты публикации
      if (form.value.status === 'published' && !form.value.published_at) {
        form.value.published_at = new Date().toISOString()
      }
      
      // Санитайзинг содержимого перед сохранением
      form.value.content = DOMPurify.sanitize(form.value.content, {
        ALLOWED_TAGS: ['b', 'i', 'strong', 'em', 'a', 'p', 'br', 'img', 'h1', 'h2', 'h3', 'ul', 'ol', 'li'],
        ALLOWED_ATTR: ['href', 'target', 'rel', 'src', 'alt', 'title', 'class']
      })
      
      emit('save', form.value)
    }
    
    return {
      form,
      contentEditor,
      preview,
      sanitizedPreview,
      generateSlug,
      formatText,
      updatePreview,
      handleSubmit
    }
  }
}
</script>

<style scoped>
.modal-content {
  border-radius: 12px;
}

.form-label {
  font-weight: 500;
  font-size: 0.875rem;
  margin-bottom: 0.5rem;
}

/* Стили для редактора */
textarea {
  font-family: 'Courier New', monospace;
  font-size: 0.9rem;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
}
</style>