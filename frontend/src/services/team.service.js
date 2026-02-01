import api from './api'

const TeamService = {
  getAll(params = {}) {
    return api.get('/teams', { params })
  },
  
  get(id) {
    return api.get(`/teams/${id}`)
  },
  
  create(data) {
    return api.post('/teams', data)
  },
  
  update(id, data) {
    return api.put(`/teams/${id}`, data)
  },
  
  delete(id) {
    return api.delete(`/teams/${id}`)
  },
  
  getMembers(teamId) {
    return api.get(`/teams/${teamId}/members`)
  }
}

export default TeamService