<template>
  <div>
    <!-- Герой-секция -->
    <section class="sport-gradient text-white py-5">
      <div class="container py-4">
        <div class="row align-items-center">
          <div class="col-lg-6">
            <h1 class="display-5 fw-bold mb-4">ДОСТИГАЙ ВЕРШИН ВМЕСТЕ С НАМИ</h1>
            <p class="lead mb-4">
              Спортивный клуб "Белый Лис" - это профессиональная организация
              спортивных мероприятий, где каждый находит свой вызов.
            </p>
            <div class="d-flex gap-3">
              <a href="#events" class="btn btn-light btn-lg px-4">
                <i class="bi bi-calendar-event me-2"></i>Мероприятия
              </a>
              <a href="#register" class="btn btn-outline-light btn-lg px-4">
                <i class="bi bi-pencil-square me-2"></i>Регистрация
              </a>
            </div>
          </div>
          <div class="col-lg-6 text-center">
            <div class="position-relative">

              <!-- <img src="/lis.png" alt="Белый лис" class="fox-large" style="max-height: 250px;"> -->
              <img src="/frontend/mass.jpg" alt="Спортивное мероприятие" class="img-fluid rounded shadow-lg"></img>
             

            </div>
          </div>
        </div>
      </div>
    </section>


    <section id="events" class="py-5 bg-light">

      <div class="container py-4">
        <div class="row mb-4">
          <div class="col">
            <h2 class="text-center fw-bold mb-3">БЛИЖАЙШИЕ МЕРОПРИЯТИЯ</h2>
            <p class="text-center text-muted">Выберите соревнование и присоединяйтесь</p>
          </div>
        </div>

        <!-- Используем компонент EventList -->
        <EventList mode="public" @event-view="onEventView" />

        <div class="text-center mt-5">
          <router-link to="/archive" class="btn btn-primary px-4">
            <i class="bi bi-archive me-2"></i>Архив мероприятий
          </router-link>
        </div>
      </div>

    </section>



    <!-- Быстрые действия пока вырубим. Что-то потом подключи возможо. -->
    <!-- <section class="py-5">
      <div class="container py-4">
        <div class="row g-3">
          <div class="col-md-3 col-6">
            <a href="#" class="card text-center border-0 shadow-sm card-hover text-decoration-none py-3">
              <div class="card-body">
                <i class="bi bi-calendar-check fs-1 text-primary mb-2"></i>
                <h6 class="card-title mb-0">Регистрация</h6>
              </div>
            </a>
          </div>
          <div class="col-md-3 col-6">
            <a href="#" class="card text-center border-0 shadow-sm card-hover text-decoration-none py-3">
              <div class="card-body">
                <i class="bi bi-clock-history fs-1 text-success mb-2"></i>
                <h6 class="card-title mb-0">Результаты</h6>
              </div>
            </a>
          </div>
          <div class="col-md-3 col-6">
            <a href="#" class="card text-center border-0 shadow-sm card-hover text-decoration-none py-3">
              <div class="card-body">
                <i class="bi bi-images fs-1 text-warning mb-2"></i>
                <h6 class="card-title mb-0">Фотогалерея</h6>
              </div>
            </a>
          </div>
          <div class="col-md-3 col-6">
            <a href="#" class="card text-center border-0 shadow-sm card-hover text-decoration-none py-3">
              <div class="card-body">
                <i class="bi bi-file-text fs-1 text-info mb-2"></i>
                <h6 class="card-title mb-0">Протоколы</h6>
              </div>
            </a>
          </div>
        </div>
      </div>
    </section> -->

    <!-- Новости -->
    <section id="news" class="py-5">
      <div class="container py-4">
        <div class="row mb-4">
          <div class="col">
            <h2 class="text-center fw-bold mb-3">ПОСЛЕДНИЕ НОВОСТИ</h2>
            <p class="text-center text-muted">Актуальные новости и анонсы мероприятий</p>
          </div>
        </div>

        <!-- Используем компонент PublicationList -->
        <PublicationList 
          mode="public" 
          :limit="4"
          :show-filters="false"
          card-columns="col-lg-6"
          @publication-view="onPublicationView"
        />

        <div class="text-center mt-5">
          <router-link to="/news" class="btn btn-outline-primary px-4">
            <i class="bi bi-newspaper me-2"></i>Все новости
          </router-link>
        </div>
      </div>
    </section>

    <!-- Модальное окно просмотра публикации -->
    <PublicationViewModal
      v-if="showPublicationModal"
      :show="showPublicationModal"
      :publication="selectedPublication"
      :is-public="true"
      @close="closePublicationModal"
    />


  </div>
</template>

<script>

import EventList from '@/components/EventList.vue'
import PublicationList from '@/components/PublicationList.vue'
import PublicationViewModal from '@/components/modals/PublicationViewModal.vue'

export default {
  name: 'Home',
  components: {
    EventList,
    PublicationList,
    PublicationViewModal
  },

  data() {
    return {
      showPublicationModal: false,
      selectedPublication: null
    }
  },

  // mounted() {
  //   // Подписываемся на событие из PublicationList
  //   this.$refs.publicationList.$on('publication-view', this.onPublicationView)
  // },
  // beforeUnmount() {
  //   // Отписываемся от события
  //   if (this.$refs.publicationList) {
  //     this.$refs.publicationList.$off('publication-view', this.onPublicationView)
  //   }
  // },
  
  methods: {
    onEventView(event) {
      // Навигация на страницу мероприятия
      this.$router.push(`/event/${event.id}`)
    },
    onPublicationView(publication) {
      // Переход на страницу новости
      // Открываем модальное окно на главной странице
      this.selectedPublication = publication
      this.showPublicationModal = true
    },
    closePublicationModal() {
      this.showPublicationModal = false
      this.selectedPublication = null
    }

  }
}

</script>

<style scoped>
section {
  scroll-margin-top: 80px;
}

.card {
  border-radius: 12px;
  overflow: hidden;
}

.badge {
  font-size: 0.75rem;
  padding: 0.35rem 0.75rem;
}
</style>