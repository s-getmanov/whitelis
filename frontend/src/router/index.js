import { createRouter, createWebHistory } from 'vue-router'
import PublicLayout from '@/layouts/PublicLayout.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import PersonalLayout from '@/layouts/PersonalLayout.vue'
import Home from '@/modules/public/Home.vue'
import AdminEvents from '@/modules/admin/Events.vue'
import AdminUsers from '@/modules/admin/Users.vue'
import AdminRegistrations from '@/modules/admin/Registrations.vue'
import AdminResults from '@/modules/admin/Results.vue'
import AdminProtocols from '@/modules/admin/Protocols.vue'
import AdminPublications from '@/modules/admin/Publications.vue'
import AdminSettings from '@/modules/admin/Settings.vue'
import Login from '@/components/Login.vue'
import MyRegistrations from '@/modules/personal/MyRegistrations.vue'
import MyProfile from '@/modules/personal/Profile.vue'
import MyEvents from '@/modules/personal/MyEvents.vue'
import Register from '@/components/Register.vue'

import JudgeMobileLayout from '@/layouts/JudgeMobileLayout.vue'
import FinishLine from '@/modules/judge/FinishLine.vue'
import ResultsInput from '@/modules/judge/ResultsInput.vue'

const routes = [
  {
    path: '/register',
    name: 'Register',
    component: Register,
    meta: { title: 'Регистрация' }
  },
  {
    path: '/',
    component: PublicLayout,
    children: [
      {
        path: '',
        name: 'home',
        component: Home
      },
      {
        path: 'login',
        name: 'login',
        component: Login
      }
    ]
  },
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAuth: true, role: 'admin' },
    children: [
      {
        path: '',
        redirect: '/admin/events'
        //Dashboard пока не делаем. Стартуем с мероприятий.
      },
      {
        path: 'events',
        name: 'admin.events',
        component: AdminEvents
      },
      {
        path: 'users',
        name: 'admin.users',
        component: AdminUsers
      },
      {
        path: 'registrations',
        name: 'admin.registrations',
        component: AdminRegistrations
      },
      {
        path: 'results',
        name: 'admin.results',
        component: AdminResults
      },
      {
        path: 'protocols',
        name: 'admin.protocols',
        component: AdminProtocols
      },
      {
        path: 'publications',
        name: 'admin.publications',
        component: AdminPublications
      },
      {
        path: 'settings',
        name: 'admin.settings',
        component: AdminSettings
      }
    ]
  },
  {
    path: '/my',
    component: PersonalLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: '/my/registrations'
      },
      {
        path: 'registrations',
        name: 'my.registrations',
        component: MyRegistrations
      },
      {
        path: 'profile',
        name: 'my.profile',
        component: MyProfile
      },
      {
        path: 'events',
        name: 'my.events',
        component: MyEvents
      },
      {
        path: 'events',
        name: 'my.events',
        component: AdminEvents
      }
    ]
  },
  {
    path: '/:catchAll(.*)',
    redirect: '/'
  },
  
  
  {
    path: '/admin/protocols',
    name: 'AdminProtocols',
    component: () => import('../modules/admin/Protocols.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  
  {
    path: '/judge',
    component: JudgeMobileLayout,
    meta: { requiresAuth: true, role: 'judge' },
    children: [
      {
        path: '',
        redirect: '/judge/finish'
      },
      {
        path: 'finish/:eventId?',     
        name: 'JudgeFinishLine',       
        component: FinishLine,
        meta: { title: 'Фиксация результатов' },
        props: true
      },
      {
        path: 'results/:eventId?',     // ← Добавить параметр
        name: 'JudgeResultsInput',     // ← Изменить имя
        component: ResultsInput,
        meta: { title: 'Просмотр результатов' },
        props: true
      },
      {
        path: 'events',
        name: 'JudgeEvents',
        component: () => import('@/modules/judge/Eventsjudge.vue'),
        meta: { title: 'Мероприятия' }
      }
    ]
  },
  {
    path: '/news',
    component: () => import('@/layouts/PublicLayout.vue'),
    children: [
      {
        path: '',
        name: 'news',
        component: () => import('@/modules/public/News.vue'),
        meta: { title: 'Новости' }
      },
      {
        path: ':slug',
        name: 'news.single',
        component: () => import('@/modules/public/NewsSingle.vue'),
        meta: { title: 'Новость' }
      }
    ]
  }





]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Базовая защита роутов (заглушка)
router.beforeEach((to, from, next) => {
  // Пропускаем публичные роуты
  if (to.path === '/' || to.path === '/login') {
    return next()
  }

  // Проверяем авторизацию
  const authUser = localStorage.getItem('auth_user')
  if (!authUser) {
    return next('/login')
  }

  const user = JSON.parse(authUser)

  // Проверяем доступ к админке
  if (to.path.startsWith('/admin') && user.role !== 'admin') {
    return next('/my')
  }

  next()
})

export default router