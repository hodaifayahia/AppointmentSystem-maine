// services/ficheNavetteService.js
import axios from 'axios'

export const ficheNavetteService = {
  /**
   * Fetches all fiche navettes with optional filtering and pagination.
   */
  async getAll(params = {}) {
    try {
      const response = await axios.get('/api/reception/fiche-navette', { params })
      return {
        success: true,
        data: response.data.data || response.data,
        total: response.data.total,
        current_page: response.data.current_page,
        per_page: response.data.per_page,
        last_page: response.data.last_page
      }
    } catch (error) {
      console.error('Error fetching fiche navettes:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to load fiche navettes',
        error
      }
    }
  },

  /**
   * Fetches a single fiche navette by its ID.
   */
  async getById(id) {
    try {
      const response = await axios.get(`/api/reception/fiche-navette/${id}`)
      return {
        success: true,
        data: response.data.data || response.data
      }
    } catch (error) {
      console.error('Error fetching fiche navette:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to load fiche navette',
        error
      }
    }
  },

  /**
   * Creates a new fiche navette.
   */
  async create(data) {
    try {
      const response = await axios.post('/api/reception/fiche-navette', data)
      return {
        success: true,
        data: response.data.data || response.data
      }
    } catch (error) {
      console.error('Error creating fiche navette:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to create fiche navette',
        error
      }
    }
  },

  /**
   * Updates an existing fiche navette by its ID.
   */
  async update(id, data) {
    try {
      const response = await axios.put(`/api/reception/fiche-navette/${id}`, data)
      return {
        success: true,
        data: response.data.data || response.data
      }
    } catch (error) {
      console.error('Error updating fiche navette:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to update fiche navette',
        error
      }
    }
  },

  /**
   * Deletes a fiche navette by its ID.
   */
  async delete(id) {
    try {
      const response = await axios.delete(`/api/reception/fiche-navette/${id}`)
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      console.error('Error deleting fiche navette:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to delete fiche navette',
        error
      }
    }
  },

  /**
   * Changes the status of a fiche navette.
   */
  async changeStatus(id, status) {
    try {
      const response = await axios.patch(`/api/reception/fiche-navette/${id}/status`, { status })
      return {
        success: true,
        data: response.data.data || response.data
      }
    } catch (error) {
      console.error('Error changing fiche navette status:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to change status',
        error
      }
    }
  },

  /**
   * Adds a prestation to a fiche navette with dependencies.
   */
  async addPrestation(ficheId, prestationData) {
    try {
      const response = await axios.post(`/api/reception/fiche-navette/${ficheId}/prestations`, prestationData)
      return {
        success: true,
        data: response.data.data || response.data,
        message: response.data.message
      }
    } catch (error) {
      console.error('Error adding prestation:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to add prestation',
        error
      }
    }
  },

  /**
   * Updates a prestation in a fiche navette.
   */
  async updatePrestation(ficheId, itemId, data) {
    try {
      const response = await axios.put(`/api/reception/fiche-navette/${ficheId}/prestations/${itemId}`, data)
      return {
        success: true,
        data: response.data.data || response.data,
        message: response.data.message
      }
    } catch (error) {
      console.error('Error updating prestation:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to update prestation',
        error
      }
    }
  },

  /**
   * Removes a prestation from a fiche navette.
   */
  async removePrestation(ficheId, itemId) {
    try {
      const response = await axios.delete(`/api/reception/fiche-navette/${ficheId}/prestations/${itemId}`)
      return {
        success: true,
        data: response.data.data || response.data,
        message: response.data.message
      }
    } catch (error) {
      console.error('Error removing prestation:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to remove prestation',
        error
      }
    }
  }
}

export default ficheNavetteService