import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    isAuthenticated: !!localStorage.getItem('token')
  }),
  
  actions: {
    setAuthData(user, token) {
      this.user = user
      this.token = token
      this.isAuthenticated = true
      localStorage.setItem('token', token)
      localStorage.setItem('user', JSON.stringify(user))
    },
    
    logout() {
      this.user = null
      this.token = null
      this.isAuthenticated = false
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    },
    
    checkAuth() {
      const token = localStorage.getItem('token')
      const user = localStorage.getItem('user')
      if (token && user) {
        this.user = JSON.parse(user)
        this.token = token
        this.isAuthenticated = true
      }
    }
  }
})