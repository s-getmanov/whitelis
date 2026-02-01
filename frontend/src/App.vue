<template>
  <router-view />
</template>

<script>
export default {
  name: 'App'
}
</script>

<style>
/* Глобальные стили */
:root {
  --primary-color: #2c3e50;
  --secondary-color: #3498db;
  --accent-color: #e74c3c;
  --light-color: #ecf0f1;
  --dark-color: #2c3e50;
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: #f8f9fa;
}

/* Анимации */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Утилиты */
.cursor-pointer {
  cursor: pointer;
}

.min-h-100 {
  min-height: 100vh;
}

/* Стили для спортивной тематики */
.sport-gradient {
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
}

.card-hover {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card-hover:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}
</style>