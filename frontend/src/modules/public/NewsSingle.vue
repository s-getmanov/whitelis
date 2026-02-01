<template>
  <div class="news-single">
    <!-- Заголовок -->
    <div class="py-5 bg-light">
      <div class="container">
        <div class="row">
          <div class="col-lg-8 mx-auto">
            <nav aria-label="breadcrumb" class="mb-3">
              <ol class="breadcrumb">
                <li class="breadcrumb-item">
                  <router-link to="/">Главная</router-link>
                </li>
                <li class="breadcrumb-item">
                  <router-link to="/news">Новости</router-link>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                  {{ publication.title }}
                </li>
              </ol>
            </nav>
            
            <div class="d-flex align-items-center gap-3 mb-4">
              <span :class="`badge bg-${getTypeColor(publication.type)}`">
                {{ getTypeText(publication.type) }}
              </span>
              <small class="text-muted">
                <i class="bi bi-calendar3 me-1"></i>
                {{ formatDate(publication.published_at) }}
              </small>
              <small class="text-muted">
                <i class="bi bi-eye me-1"></i>
                {{ publication.views_count || 0 }} просмотров
              </small>
            </div>
            
            <h1 class="display-6 fw-bold mb-4">{{ publication.title }}</h1>
            
            <div class="d-flex align-items-center gap-3">
              <div class="bg-secondary bg-opacity-10 rounded-circle p-2">
                <i class="bi bi-person-circle text-secondary"></i>
              </div>
              <div>
                <div class="fw-semibold">{{ publication.author?.name || 'Автор' }}</div>
                <div class="text-muted small">Автор публикации</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Содержимое -->
    <div class="container py-5">
      <div class="row">
        <div class="col-lg-8 mx-auto">
          <!-- Краткое описание -->
          <div v-if="publication.excerpt" class="card border-0 bg-light mb-5">
            <div class="card-body">
              <h6 class="fw-bold text-muted mb-2">
                <i class="bi bi-quote me-2"></i>Краткое содержание
              </h6>
              <p class="mb-0 lead" style="font-style: italic;">
                {{ publication.excerpt }}
              </p>
            </div>
          </div>

          <!-- Основное содержимое -->
          <div class="publication-content mb-5">
            <div v-html="sanitizedContent"></div>
          </div>

          <!-- Дополнительная информация -->
          <div class="border-top pt-4">
            <div class="row">
              <div class="col-md-6 mb-3">
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
              <div class="col-md-6 mb-3">
                <div class="d-flex align-items-center">
                  <div class="bg-success bg-opacity-10 rounded-circle p-2 me-3">
                    <i class="bi bi-clock text-success"></i>
                  </div>
                  <div>
                    <div class="fw-semibold small">Дата публикации</div>
                    <div>{{ formatDate(publication.published_at) }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Навигация -->
          <div class="d-flex justify-content-between mt-5 pt-4 border-top">
            <router-link to="/news" class="btn btn-outline-secondary">
              <i class="bi bi-arrow-left me-2"></i>
              Назад к списку
            </router-link>
            <button class="btn btn-outline-primary" @click="sharePublication">
              <i class="bi bi-share me-2"></i>
              Поделиться
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import DOMPurify from 'dompurify'
import PublicationService from '@/services/publication.service'

export default {
  name: 'NewsSingle',
  
  setup() {
    const route = useRoute()
    const publication = ref({})
    const loading = ref(true)
    const error = ref(null)
    
    const sanitizedContent = computed(() => {
      return DOMPurify.sanitize(publication.value.content || '', {
        ALLOWED_TAGS: ['b', 'i', 'strong', 'em', 'a', 'p', 'br', 'img', 'h1', 'h2', 'h3', 'ul', 'ol', 'li', 'div', 'span'],
        ALLOWED_ATTR: ['href', 'target', 'rel', 'src', 'alt', 'title', 'class', 'style']
      })
    })
    
    const loadPublication = async () => {
      loading.value = true
      error.value = null
      
      try {
        const slug = route.params.slug
        const data = await PublicationService.getBySlug(slug)
        publication.value = data
        
        // Увеличиваем счетчик просмотров
       // await PublicationService.incrementViews(slug)
      } catch (err) {
        error.value = err.message || 'Публикация не найдена'
        console.error('Ошибка загрузки публикации:', err)
      } finally {
        loading.value = false
      }
    }
    
    const formatDate = (dateString) => {
      if (!dateString) return ''
      const date = new Date(dateString)
      const options = { day: 'numeric', month: 'long', year: 'numeric' }
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
    
    const sharePublication = () => {
      const url = window.location.href
      const text = `${publication.value.title} - Спортивный клуб "Белый Лис"`
      
      if (navigator.share) {
        navigator.share({
          title: publication.value.title,
          text: publication.value.excerpt,
          url: url
        })
      } else {
        // Копирование ссылки в буфер обмена
        navigator.clipboard.writeText(url)
        alert('Ссылка скопирована в буфер обмена!')
      }
    }
    
    onMounted(() => {
      loadPublication()
    })
    
    return {
      publication,
      loading,
      error,
      sanitizedContent,
      formatDate,
      getTypeColor,
      getTypeText,
      sharePublication
    }
  }
}
</script>

<style scoped>
.news-single {
  min-height: 600px;
}

.bg-light {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.publication-content {
  font-size: 1.1rem;
  line-height: 1.7;
}

.publication-content img {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
  margin: 2rem 0;
}

.publication-content h2 {
  margin-top: 2rem;
  margin-bottom: 1rem;
}

.publication-content h3 {
  margin-top: 1.5rem;
  margin-bottom: 1rem;
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