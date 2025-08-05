// Components/Apps/services/Remise/userService.js
import axios from 'axios'

export const userService = {
  async getAll() {
    try {
      const response = await axios.get('/api/users')
      return {
        success: true,
        data: response.data.data || response.data
      }
    } catch (error) {
      console.error('Error fetching users:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to load users. Please try again.',
        error
      }
    }
  },

  async getById(id) {
    try {
      const response = await axios.get(`/api/users/${id}`)
      return {
        success: true,
        data: response.data.data || response.data
      }
    } catch (error) {
      console.error(`Error fetching user ${id}:`, error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to load user. Please try again.',
        error
      }
    }
  }
}
