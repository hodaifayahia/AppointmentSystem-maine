<script setup>
import { ref, computed, defineProps, onMounted } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";
import { useToastr } from '../../../../toster';

import AnnexTableListItem from './AnnexTableListItem.vue';
import AnnexFormModal from '../models/AnnexFormModal.vue';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

const props = defineProps({
  contractState: String,
  contractId: String
});

const router = useRouter();
const toast = useToastr();

// Search and filter state
const searchQuery = ref("");
const selectedFilter = ref("annex_name");
const filterOptions = [
  { label: "By ID", value: "id" },
  { label: "By Name", value: "annex_name" },
  { label: "By Creation time", value: "created_at" },
  { label: "By Service", value: "service_name" } // Changed from specialty to service
];

const loading = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);

const items = ref([]);
const services = ref([]);

// Modal Visibility and Form State
const showFormModal = ref(false);
const showDeleteConfirmModal = ref(false);
const isEditingMode = ref(false);
const currentForm = ref({
  id: null,
  contract_id: '',
  annex_name: '',
  service_id: null, // Changed from specialty_id to service_id
});
const itemToDelete = ref(null);

// Pagination states
const currentPage = ref(1);
const itemsPerPage = ref(8);

const paginatedFilteredItems = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  const end = start + itemsPerPage.value;
  return filteredItemsComputed.value.slice(start, end);
});

const totalPages = computed(() => {
  return Math.ceil(filteredItemsComputed.value.length / itemsPerPage.value);
});

const changePage = (page) => {
  if (page > 0 && page <= totalPages.value) {
    currentPage.value = page;
  }
};

// Format date to dd/mm/yyyy
const formatDateDisplay = (dateString) => {
  if (!dateString) return '';
  try {
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = String(date.getFullYear());
    return `${day}/${month}/${year}`;
  } catch (error) {
    console.error("Error formatting date:", error);
    return dateString;
  }
};

const capitalizeFirstLetter = (string) => {
  if (!string) return '';
  return String(string).charAt(0).toUpperCase() + String(string).slice(1);
};

// Filtered items based on search query
const filteredItemsComputed = computed(() => {
  if (!searchQuery.value) return items.value;

  const query = String(searchQuery.value).toLowerCase();

  return items.value.filter(item => {
    switch (selectedFilter.value) {
      case "id":
        return item.id && String(item.id).includes(query);
      case "annex_name":
        return item.annex_name && String(item.annex_name).toLowerCase().includes(query);
      case "created_at":
        const searchDateFormatted = searchQuery.value instanceof Date
          ? formatDateDisplay(searchQuery.value)
          : query;
        return item.created_at && formatDateDisplay(item.created_at).includes(searchDateFormatted);
      case "service_name": // Changed from specialty_name to service_name
        return item.service_name && String(item.service_name).toLowerCase().includes(query);
      default:
        return true;
    }
  });
});

// Fetch annexes for the contract - CORRECTED
const fetchAnnexes = async () => {
  if (!props.contractId) {
    toast.error('Contract ID is missing');
    return;
  }

  try {
    loading.value = true;
    // Use the correct endpoint with contractId in the URL path
    const response = await axios.get(`/api/annex/contract/${props.contractId}`);
    
    // Handle the response structure from your controller
    if (response.data.success) {
      items.value = response.data.data;
    } else {
      items.value = [];
      toast.error('Failed to load annexes');
    }
    
    currentPage.value = 1;
  } catch (error) {
    console.error("Error fetching annexes:", error);
    const errorMessage = error.response?.data?.message || 'Failed to load annexes';
    toast.error(errorMessage);
  } finally {
    loading.value = false;
  }
};

// Fetch available services
const fetchServices = async () => {
  try {
    const response = await axios.get(`/api/services`);
    // Adjust based on your services API response structure
    services.value = response.data.data || response.data;
    if (services.value.length === 0) {
      toast.warning('No available services found.');
    }
  } catch (error) {
    console.error("Error fetching services:", error);
    toast.error('Failed to load services for form.');
  }
};

// Open unified form modal for adding
const openAddFormModal = async () => {
  isEditingMode.value = false;
  Object.assign(currentForm.value, {
    id: null,
    contract_id: props.contractId,
    annex_name: "",
    service_id: null, // Changed from specialty_id
  });
  await fetchServices();
  showFormModal.value = true;
};

// Open unified form modal for editing
const openEditFormModal = async (item) => {
  isEditingMode.value = true;
  Object.assign(currentForm.value, {
    id: item.id,
    contract_id: item.contract_id,
    annex_name: item.annex_name,
    service_id: item.service_id, // Changed from specialty_id
  });
   fetchServices();
  
  // Ensure the current service is in the list
  const serviceExists = services.value.some(s => s.id === item.service_id);
  if (!serviceExists && item.service_id && item.service_name) {
    services.value.push({
      id: item.service_id,
      name: item.service_name
    });
  }
  showFormModal.value = true;
};

// Open delete confirmation modal
const openDeleteConfirmModal = (item) => {
  itemToDelete.value = item;
  showDeleteConfirmModal.value = true;
};
// Function to handle the 'save' event from AnnexFormModal
const handleFormSave = async (formDataPayload) => {
  isSaving.value = true;
  try {
    const formData = new FormData();
    formData.append('annex_name', formDataPayload.annex_name);
    formData.append('service_id', formDataPayload.service_id); // Fixed: was specialty_id
    formData.append('min_price', formDataPayload.min_price); // Fixed: was specialty_id
    formData.append('prestation_prix_status', formDataPayload.prestation_prix_status); // Fixed: was specialty_id
    
    if (formDataPayload.description) {
      formData.append('description', formDataPayload.description);
    }

    let url = '';
    let method = '';

    if (isEditingMode.value) {
      url = `/api/annex/${formDataPayload.id}`;
      method = 'put';
      formData.append('_method', 'PUT');
    } else {
      url = `/api/annex/${props.contractId}`;
      method = 'post';
    }

    const response = await axios({
      method: method,
      url: url,
      data: formData,
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    if (response.data.success) {
      toast.success(`Annex ${isEditingMode.value ? 'updated' : 'added'} successfully`);
      await fetchAnnexes();
      showFormModal.value = false;
    } else {
      toast.error(response.data.message || 'Operation failed');
    }
  } catch (error) {
    console.error("Error saving annex:", error);
    if (error.response && error.response.data) {
      if (error.response.data.errors) {
        // Display backend validation errors
        for (const field in error.response.data.errors) {
          error.response.data.errors[field].forEach(message => toast.error(message));
        }
      } else {
        toast.error(error.response.data.message || `Failed to ${isEditingMode.value ? 'update' : 'save'} annex`);
      }
    } else {
      toast.error(`Failed to ${isEditingMode.value ? 'update' : 'save'} annex: ${error.message}`);
    }
  } finally {
    isSaving.value = false;
  }
};



// Handle delete confirmation
const handleDeleteConfirm = async () => {
  isDeleting.value = true;
  try {
    const response = await axios.delete(`/api/annex/${itemToDelete.value.id}`);
    
    if (response.data.success) {
      toast.success('Annex deleted successfully');
      await fetchAnnexes();
      showDeleteConfirmModal.value = false;
    } else {
      toast.error(response.data.message || 'Failed to delete annex');
    }
  } catch (error) {
    console.error("Error deleting annex:", error);
    toast.error(`Failed to delete annex: ${error.response?.data?.message || error.message}`);
  } finally {
    isDeleting.value = false;
    itemToDelete.value = null;
  }
};

// Function to handle navigation to details page
const viewAnnexDetails = (id) => {
  
  router.push({
    name: 'convention.annex.details',
    params: { id: id }
  });
};

// Initial data fetch
onMounted(() => {
  fetchAnnexes();
});
</script>

<template>
  <div class="container-fluid py-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center mb-4 gap-2">
      <div class="d-flex flex-grow-1 gap-2 w-100">
        <select v-model="selectedFilter" class="form-select border rounded-3 w-auto">
          <option v-for="option in filterOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>

        <input v-if="selectedFilter !== 'created_at'" type="text" v-model="searchQuery" placeholder="Search..."
          class="form-control flex-grow-1" />

        <input v-if="selectedFilter === 'created_at'" type="date" v-model="searchQuery"
          placeholder="Select Date" class="form-control flex-grow-1" />
      </div>

      <button v-if="props.contractState === 'pending'" class="btn btn-primary d-flex align-items-center"
        @click="openAddFormModal()">
        <i class="fas fa-plus me-1"></i> Add Annex
      </button>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2 text-muted">Loading annexes...</p>
        </div>
        <div v-else-if="paginatedFilteredItems.length === 0"
          class="text-center text-muted py-5 d-flex flex-column align-items-center">
          <i class="fas fa-folder-open fs-3 mb-2"></i>
          <span>No annexes found.</span>
        </div>
        <div v-else class="table-responsive">
          <table class="table table-striped table-hover annex-table">
            <thead>
              <tr>
                <th scope="col">ID</th>
                <th scope="col">Name</th>
                <th scope="col">Service</th> <!-- Changed from Specialty to Service -->
                <th scope="col">Created By</th>
                <th scope="col">Created At</th>
                <th scope="col">max price </th>
                <th scope="col">min price</th>
                <th v-if="props.contractState === 'pending'" scope="col">Edit</th>
                <th v-if="props.contractState === 'pending'" scope="col">Delete</th>
                <th scope="col">Details</th>
              </tr>
            </thead>
            <tbody>
              <AnnexTableListItem
                v-for="item in paginatedFilteredItems"
                :key="item.id"
                :annex="item"
                :contract-state="props.contractState"
                @edit="openEditFormModal"
                @delete="openDeleteConfirmModal"
                @view-details="viewAnnexDetails"
              />
            </tbody>
          </table>
        </div>

        <nav v-if="totalPages > 1" aria-label="Page navigation">
          <ul class="pagination justify-content-center">
            <li class="page-item" :class="{ 'disabled': currentPage === 1 }">
              <button class="page-link" @click="changePage(currentPage - 1)" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
              </button>
            </li>
            <li class="page-item" v-for="page in totalPages" :key="page"
              :class="{ 'active': currentPage === page }">
              <button class="page-link" @click="changePage(page)">{{ page }}</button>
            </li>
            <li class="page-item" :class="{ 'disabled': currentPage === totalPages }">
              <button class="page-link" @click="changePage(currentPage + 1)" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
              </button>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <AnnexFormModal
      :show-modal="showFormModal"
      :is-editing="isEditingMode"
      :form-data="currentForm"
      :services="services"
      :is-loading="isSaving"
      @save="handleFormSave"
      @close-modal="showFormModal = false"
    />

    <div v-if="showDeleteConfirmModal" class="modal fade" :class="{ 'show d-block': showDeleteConfirmModal }"
      id="deleteAnnexModal" tabindex="-1" aria-labelledby="deleteAnnexModalLabel" aria-hidden="true"
      @click.self="showDeleteConfirmModal = false">
      <div class="modal-dialog modal-sm">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="deleteAnnexModalLabel">Confirm Delete</h5>
            <button type="button" class="btn-close" @click="showDeleteConfirmModal = false" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete annex <strong>{{ itemToDelete?.annex_name }}</strong>?</p>
            <div class="alert alert-warning" role="alert">
              This action cannot be undone.
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showDeleteConfirmModal = false" :disabled="isDeleting">No</button>
            <button type="button" class="btn btn-danger" @click="handleDeleteConfirm" :disabled="isDeleting">
              <span v-if="isDeleting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
              Yes, Delete
            </button>
          </div>
        </div>
      </div>
    </div>
    <div v-if="showDeleteConfirmModal" class="modal-backdrop fade" :class="{ 'show': showDeleteConfirmModal }"></div>
  </div>
</template>

<style scoped>
/* Your existing styles remain the same */
.container-fluid.py-4 {
  padding-top: 1.5rem !important;
  padding-bottom: 1.5rem !important;
}

.d-flex.gap-2 > * {
  margin-right: 0.5rem;
}
.d-flex.gap-2 > *:last-child {
  margin-right: 0;
}

.card {
  border-radius: 0.75rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

.form-control, .form-select {
  border-radius: 0.5rem;
  padding: 0.625rem 0.75rem;
}

.annex-table {
  min-width: 50rem;
}

.table th, .table td {
  vertical-align: middle;
  padding: 0.75rem;
  white-space: nowrap;
}

.text-muted {
  color: #6c757d !important;
}

.fs-3 {
  font-size: calc(1.3rem + .6vw) !important;
}

.modal-dialog.modal-md {
  max-width: 500px;
}

.modal-dialog.modal-sm {
  max-width: 300px;
}

.btn-primary {
  background-color: #007bff;
  border-color: #007bff;
}

.btn-primary:hover {
  background-color: #0056b3;
  border-color: #0056b3;
}

.btn-danger {
  background-color: #dc3545;
  border-color: #dc3545;
}

.btn-danger:hover {
  background-color: #c82333;
  border-color: #bd2130;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
  margin-right: 0.25rem;
}

.text-danger {
  color: #dc3545 !important;
  font-size: 0.875em;
  margin-top: 0.25rem;
  display: block;
}
</style>
