<template>
  <!-- Модальное окно -->
  <div v-if="show" class="modal fade show d-block" style="background-color: rgba(0,0,0,0.5)">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <!-- Заголовок -->
        <div class="modal-header border-bottom-0 pb-0">
          <div class="d-flex justify-content-between align-items-center w-100">
            <div>
              <span :class="`badge bg-${getTypeColor(publication.type)} me-2`">
                {{ getTypeText(publication.type) }}
              </span>
              <span v-if="publication.is_pinned" class="badge bg-warning me-2">
                <i class="bi bi-pin-angle"></i> Закреплено
              </span>
              <span class="text-muted">
                <i class="bi bi-calendar3 me-1"></i>
                {{ formatDate(publication.created_at) }}
              </span>
            </div>
            <button 
              type="button" 
              class="btn-close" 
              @click="$emit('close')"
              aria-label="Закрыть"
            ></button>
          </div>
        </div>
        
        <div class="modal-body pt-0">
          <!-- Заголовок публикации -->
          <h3 class="modal-title fw-bold mb-4">{{ publication.title }}</h3>
          
          <!-- Мета-информация -->
          <div class="d-flex align-items-center gap-3 mb-4 text-muted small">
            <div class="d-flex align-items-center">
              <i class="bi bi-person-circle me-1"></i>
              <span>{{ publication.author?.name || 'Автор' }}</span>
            </div>
            <div class="d-flex align-items-center">
              <i class="bi bi-eye me-1"></i>
              <span>{{ publication.views_count || 0 }} просмотров</span>
            </div>
            <div v-if="publication.published_at" class="d-flex align-items-center">
              <i class="bi bi-clock me-1"></i>
              <span>Опубликовано {{ formatDate(publication.published_at) }}</span>
            </div>
          </div>
          
          <!-- Краткое описание -->
          <div v-if="publication.excerpt" class="card border-0 bg-light mb-4">
            <div class="card-body">
              <h6 class="fw-bold text-muted mb-2">
                <i class="bi bi-quote me-2"></i>Краткое содержание
              </h6>
              <p class="mb-0" style="font-style: italic;">
                {{ publication.excerpt }}
              </p>
            </div>
          </div>
          
          <!-- Основное содержимое -->
          <div class="publication-content mb-4">
            <div v-html="sanitizedContent"></div>
          </div>
          
          <!-- Дополнительная информация -->
          <div class="border-top pt-3">
            <div class="row">
              <div class="col-md-6">
                <div class="d-flex align-items-center">
                  <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                    <i class="bi bi-tags text-primary"></i>
                  </div>
                  <div>
                    <div class="fw-semibold small">Категория</div>
                    <div>{{ getTypeText(publication.type) }}</div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="d-flex align-items-center">
                  <div class="bg-success bg-opacity-10 rounded-circle p-2 me-3">
                    <i class="bi bi-file-text text-success"></i>
                  </div>
                  <div>
                    <div class="fw-semibold small">Статус</div>
                    <div>{{ getStatusText(publication.status) }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Футер модального окна -->
        <div class="modal-footer border-top-0 pt-0">
          <button 
            type="button" 
            class="btn btn-outline-secondary"
            @click="$emit('close')"
          >
            Закрыть
          </button>
          <button 
            v-if="!isPublic"
            type="button" 
            class="btn btn-primary"
            @click="handleEdit"
          >
            <i class="bi bi-pencil me-2"></i>
            Редактировать
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'
import DOMPurify from 'dompurify'

export default {
  name: 'PublicationViewModal',
  
  props: {
    show: {
      type: Boolean,
      default: false
    },
    publication: {
      type: Object,
      default: () => ({})
    },
    isPublic: {
      type: Boolean,
      default: true
    }
  },
  
  emits: ['close', 'edit'],
  
  setup(props) {
    const sanitizedContent = computed(() => {
      return DOMPurify.sanitize(props.publication.content || '', {
        ALLOWED_TAGS: ['b', 'i', 'strong', 'em', 'a', 'p', 'br', 'img', 'h1', 'h2', 'h3', 'ul', 'ol', 'li', 'div', 'span'],
        ALLOWED_ATTR: ['href', 'target', 'rel', 'src', 'alt', 'title', 'class', 'style']
      })
    })
    
    const formatDate = (dateString) => {
      if (!dateString) return ''
      const date = new Date(dateString)
      const options = { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }
      return date.toLocaleDateString('ru-RU', options)
    }
    
    const getTypeColor = (type) => {
      const colors = {
        'news': 'primary',
        'announcement': 'success',
        'article': 'warning',
        'page': 'info'
      }
      return colors[type] || 'secondary'
    }
    
    const getTypeText = (type) => {
      const texts = {
        'news': 'Новость',
        'announcement': 'Анонс',
        'article': 'Статья',
        'page': 'Страница'
      }
      return texts[type] || type
    }
    
    const getStatusText = (status) => {
      const texts = {
        'draft': 'Черновик',
        'published': 'Опубликовано',
        'archived': 'В архиве'
      }
      return texts[status] || status
    }
    
    const handleEdit = () => {
      if (!props.isPublic) {
        this.$emit('edit', props.publication)
      }
    }
    
    return {
      sanitizedContent,
      formatDate,
      getTypeColor,
      getTypeText,
      getStatusText,
      handleEdit
    }
  }
}
</script>

<style scoped>
.modal-content {
  border-radius: 12px;
  border: none;
}

.modal-header {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 12px 12px 0 0;
}

.modal-title {
  color: var(--primary-color);
  line-height: 1.3;
}

.badge {
  font-size: 0.85rem;
  padding: 0.4rem 0.8rem;
}

.publication-content {
  font-size: 1.1rem;
  line-height: 1.6;
}

.publication-content img {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
  margin: 1rem 0;
}

.publication-content a {
  color: var(--primary-color);
  text-decoration: none;
}

.publication-content a:hover {
  text-decoration: underline;
}

.bg-opacity-10 {
  background-color: rgba(var(--bs-primary-rgb), 0.1);
}
</style>