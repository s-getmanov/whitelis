<template>
  <div class="news-page">
    <!-- Заголовок -->
    <div class="py-5 bg-light mb-5">
      <div class="container">
        <div class="row">
          <div class="col-lg-8 mx-auto text-center">
            <h1 class="display-5 fw-bold mb-3">Новости клуба</h1>
            <p class="lead text-muted">
              Следите за последними новостями, анонсами мероприятий и статьями о спорте
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Список новостей -->
    <div class="container py-4">
      <div class="row">
        <div class="col-lg-8 mx-auto">
          <PublicationList 
            mode="public" 
            :limit="12"
            card-columns="col-12"
            @publication-view="onPublicationView"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import PublicationList from '@/components/PublicationList.vue'

export default {
  name: 'NewsPage',
  
  components: {
    PublicationList
  },
  
  methods: {
    onPublicationView(publication) {
      // Переход на страницу новости
      this.$router.push({ name: 'news.single', params: { slug: publication.slug } })
    }
  }
}
</script>

<style scoped>
.news-page {
  min-height: 600px;
}

.bg-light {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}
</style>