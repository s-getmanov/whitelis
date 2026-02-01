<template>
  <div class="publications-admin">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold mb-1">Новости и публикации</h4>
        <p class="text-muted mb-0">Управление новостями, анонсами и статьями клуба</p>
      </div>
      <button class="btn btn-primary" @click="createPublication">
        <i class="bi bi-plus-circle me-2"></i>
        Новая публикация
      </button>
    </div>

    <!-- Статистика -->
    <div class="row mb-4">
      <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                <i class="bi bi-newspaper text-primary fs-4"></i>
              </div>
              <div>
                <div class="text-muted small">Всего публикаций</div>
                <div class="h4 mb-0 fw-bold">{{ stats.total || 0 }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="bg-success bg-opacity-10 rounded-circle p-3 me-3">
                <i class="bi bi-send-check text-success fs-4"></i>
              </div>
              <div>
                <div class="text-muted small">Опубликовано</div>
                <div class="h4 mb-0 fw-bold">{{ stats.published || 0 }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="bg-warning bg-opacity-10 rounded-circle p-3 me-3">
                <i class="bi bi-clock text-warning fs-4"></i>
              </div>
              <div>
                <div class="text-muted small">Черновики</div>
                <div class="h4 mb-0 fw-bold">{{ stats.drafts || 0 }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="bg-info bg-opacity-10 rounded-circle p-3 me-3">
                <i class="bi bi-eye text-info fs-4"></i>
              </div>
              <div>
                <div class="text-muted small">Просмотры</div>
                <div class="h4 mb-0 fw-bold">{{ stats.views || 0 }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Список публикаций -->
    <PublicationList 
      ref="publicationList"
      mode="admin" 
      compact
      @publication-view="onPublicationView"
      @publication-edit="onPublicationEdit"
      @publication-delete="onPublicationDelete"
      @create-publication="createPublication"
      @loaded="onLoaded"
    />
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import PublicationList from '@/components/PublicationList.vue'
import PublicationService from '@/services/publication.service'

export default {
  name: 'PublicationsAdmin',
  
  components: {
    PublicationList
  },
  
  setup() {
    const publicationList = ref(null)
    const stats = ref({
      total: 0,
      published: 0,
      drafts: 0,
      views: 0
    })
    
    const loadStats = async () => {
      try {
        // Загружаем все публикации для статистики
        const response = await PublicationService.getAll({ limit: 1000 })
        const publications = response.data
        
        stats.value.total = publications.length
        stats.value.published = publications.filter(p => p.status === 'published').length
        stats.value.drafts = publications.filter(p => p.status === 'draft').length
        stats.value.views = publications.reduce((sum, p) => sum + (p.views_count || 0), 0)
      } catch (error) {
        console.error('Ошибка загрузки статистики:', error)
      }
    }
    
    const createPublication = () => {
      if (publicationList.value) {
        publicationList.value.openCreateModal()
      }
    }
    
    const onPublicationView = (publication) => {
      console.log('Просмотр публикации:', publication)
    }
    
    const onPublicationEdit = (publication) => {
      console.log('Редактирование публикации:', publication)
    }
    
    const onPublicationDelete = async (publication) => {
      if (confirm(`Удалить публикацию "${publication.title}"?`)) {
        try {
          await PublicationService.delete(publication.id)
          publicationList.value.loadPublications()
          loadStats()
        } catch (error) {
          alert('Ошибка удаления публикации: ' + error.message)
        }
      }
    }
    
    const onLoaded = () => {
      loadStats()
    }
    
    onMounted(() => {
      loadStats()
    })
    
    return {
      publicationList,
      stats,
      createPublication,
      onPublicationView,
      onPublicationEdit,
      onPublicationDelete,
      onLoaded
    }
  }
}
</script>

<style scoped>
.publications-admin {
  min-height: 500px;
}

.card {
  border-radius: 12px;
  transition: all 0.2s ease;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1) !important;
}

.bg-opacity-10 {
  opacity: 0.1;
}

.btn-primary {
  background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
  border: none;
  transition: all 0.2s ease;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
}
</style>