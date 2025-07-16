<script setup>
import { ref, computed, defineProps, onMounted } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";
import { useToastr } from '../../../../toster'; // Ensure this path is correct

import AnnexTableListItem from './AgreementTableListItem.vue'; // Import new table row component
import AnnexFormModal from '../models/AnnexFormModal.vue'; // Import unified form modal

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

const props = defineProps({
  contractState: String,
  contractId: String
});

const router = useRouter();
const toast = useToastr();

// Search and filter state
const searchQuery = ref("");
const selectedFilter = ref("annex_name"); // Default filter
const filterOptions = [
  { label: "By ID", value: "id" },
  { label: "By Name", value: "annex_name" },
  { label: "By Creation time", value: "created_at" },
  { label: "By Specialty", value: "specialty_name" }
];

const loading = ref(false); // For table loading state
const isSaving = ref(false); // For add/edit form submission loading in the modal
const isDeleting = ref(false); // For delete operation loading in the modal

const items = ref([]); // Data for the table
const services = ref([]); // Data for the specialty dropdown in form modal

// --- Modal Visibility and Form State (Unified) ---
const showFormModal = ref(false); // Controls visibility of the add/edit modal
const showDeleteConfirmModal = ref(false); // Controls visibility of the delete modal
const isEditingMode = ref(false); // True for edit, false for add
const currentForm = ref({ // This object is passed to AnnexFormModal
  id: null,
  contract_id: '',
  annex_name: '',
  specialty_id: null,
  file: null,          // Holds the actual File object
  file_path: null,     // For existing file display
  file_url: null,      // For existing file URL display
  remove_file: false,  // Flag for removing existing file
});
const itemToDelete = ref(null); // Item passed to delete confirmation modal

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

// Format date to dd/mm/yy (for display)
const formatDateDisplay = (dateString) => {
  if (!dateString) return '';
  try {
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString; // Return original if invalid date
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = String(date.getFullYear()); // Get full year
    return `${day}/${month}/${year}`;
  } catch (error) {
    console.error("Error formatting date:", error);
    return dateString;
  }
};

// Capitalize first letter of a string
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
        // If searchQuery is a Date object (from Calendar), format it for comparison
        const searchDateFormatted = searchQuery.value instanceof Date
          ? formatDateDisplay(searchQuery.value)
          : query;
        return item.created_at && formatDateDisplay(item.created_at).includes(searchDateFormatted);
      case "specialty_name":
        return item.specialty_name && String(item.specialty_name).toLowerCase().includes(query);
      default:
        return true;
    }
  });
});

// Fetch annexes for the contract
const fetchAnnexes = async () => {
  if (!props.contractId) {
    toast.error('Contract ID is missing');
    return;
  }

  try {
    loading.value = true;
    const response = await axios.get(`  /api/convention/annexes/contract/${props.contractId}`);
    items.value = response.data.map(item => ({
        ...item,
        file_url: item.file_path ? `  /storage/${item.file_path}` : null // Add full file_url
    }));
    currentPage.value = 1; // Reset pagination on new data
  } catch (error) {
    console.error("Error fetching annexes:", error);
    const errorMessage = error.response?.data?.message || 'Failed to load annexes';
    toast.error(errorMessage);
  } finally {
    loading.value = false;
  }
};

// Fetch available services (for dropdowns)
const fetchservices = async () => {
  try {
    const response = await axios.get(`/api/services`);
    services.value = response.data.data;
    if (services.value.length === 0) {
      toast.warning('No available services found.');
    }
  } catch (error) {
    console.error("Error fetching services:", error);
    toast.error('Failed to load services for form.');
  }
};

// --- Modal Management Functions ---

// Open unified form modal for adding
const openAddFormModal = async () => {
  isEditingMode.value = false; // Set mode to Add
  // Reset form to default empty state for adding new
  Object.assign(currentForm.value, {
    id: null,
    contract_id: props.contractId,
    annex_name: "",
    specialty_id: null,
    file: null,
    file_path: null,
    file_url: null,
    remove_file: false,
  });
  await fetchservices(); // Fetch services for the dropdown
  showFormModal.value = true; // Show the modal
};

// Open unified form modal for editing
const openEditFormModal = async (item) => {
  isEditingMode.value = true; // Set mode to Edit
  // Populate form with existing item data
  Object.assign(currentForm.value, {
    id: item.id,
    contract_id: item.contract_id,
    annex_name: item.annex_name,
    specialty_id: item.specialty_id,
    file: null, // Always reset file input on edit open
    file_path: item.file_path, // Pass existing file path
    file_url: item.file_url,   // Pass existing file URL
    remove_file: false, // Reset remove file flag
  });
  await fetchservices(); // Fetch services
  // Ensure the current specialty is in the list if not already
  const specialtyExists = services.value.some(s => s.id === item.specialty_id);
  if (!specialtyExists && item.specialty_id && item.specialty_name) {
      services.value.push({
          id: item.specialty_id,
          specialty_name: item.specialty_name
      });
  }
  showFormModal.value = true; // Show the modal
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
    formData.append('specialty_id', formDataPayload.specialty_id);

    // Append file if present
    if (formDataPayload.file) {
      formData.append('file', formDataPayload.file);
    }

    // Handle file removal flag for edits
    if (isEditingMode.value && formDataPayload.remove_file) {
      formData.append('remove_file', 1); // Send as 1 for true
    }

    let url = '';
    let method = '';

    if (isEditingMode.value) {
      url = `  /api/convention/annexes/${formDataPayload.id}`;
      method = 'post'; // Use POST with _method spoofing for PUT
      formData.append('_method', 'PUT'); // Spoof PUT
    } else {
      url = `  /api/convention/annexes/contract/${props.contractId}`;
      method = 'post';
    }

    await axios({
      method: method,
      url: url,
      data: formData,
      headers: {
        'Content-Type': 'multipart/form-data', // Crucial for files
      },
    });

    toast.success(`Annex ${isEditingMode.value ? 'updated' : 'added'} successfully`);
    await fetchAnnexes(); // Refresh data
    showFormModal.value = false; // Close modal
  } catch (error) {
    console.error("Error saving annex:", error);
    if (error.response && error.response.data && error.response.data.errors) {
        // Display backend validation errors
        for (const field in error.response.data.errors) {
            error.response.data.errors[field].forEach(message => toast.error(message));
        }
    } else {
        toast.error(`Failed to ${isEditingMode.value ? 'update' : 'save'} annex: ${error.response?.data?.message || error.message}`);
    }
  } finally {
    isSaving.value = false;
  }
};

// Function to handle 'delete' confirmation from the modal
const handleDeleteConfirm = async () => {
  isDeleting.value = true;
  try {
    await axios.delete(` /api/convention/annexes/${itemToDelete.value.id}`);
    toast.success('Annex deleted successfully');
    await fetchAnnexes(); // Refresh data
    showDeleteConfirmModal.value = false; // Close modal
  } catch (error) {
    console.error("Error deleting annex:", error);
    toast.error(`Failed to delete annex: ${error.response?.data?.message || error.message}`);
  } finally {
    isDeleting.value = false;
    itemToDelete.value = null; // Clear item to delete
  }
};

// Function to handle navigation to details page
const viewAnnexDetails = (item) => {
    router.push({
        path: `/Annex/${item.id}`,
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
                <th scope="col">Specialty</th>
                <th scope="col">Created By</th>
                <th scope="col">Created At</th>
                <th v-if="props.contractState === 'Pending'" scope="col">Edit</th>
                <th v-if="props.contractState === 'Pending'" scope="col">Delete</th>
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
/* Adjust container padding */
.container-fluid.py-4 {
  padding-top: 1.5rem !important;
  padding-bottom: 1.5rem !important;
}

/* Flexbox and gap for search/filter/add section */
.d-flex.gap-2 > * {
  margin-right: 0.5rem; /* Adjust gap as needed */
}
.d-flex.gap-2 > *:last-child {
  margin-right: 0;
}

/* Card styling */
.card {
  border-radius: 0.75rem; /* Consistent border-radius */
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.06); /* Mimics shadow-sm */
  border: 1px solid #e2e8f0; /* Light gray border */
}

/* Form controls and select */
.form-control, .form-select {
  border-radius: 0.5rem; /* Rounded-lg */
  padding: 0.625rem 0.75rem; /* p-2 */
}

/* Table styling */
.annex-table {
  min-width: 50rem; /* Matches the PrimeVue tableStyle */
}

.table th, .table td {
  vertical-align: middle;
  padding: 0.75rem;
  white-space: nowrap; /* Prevent content from wrapping in table cells */
}

/* "No annexes found" message & loading state */
.text-muted {
  color: #6c757d !important;
}
.fs-3 { /* Equivalent to text-3xl for Font Awesome icon */
  font-size: calc(1.3rem + .6vw) !important;
}

/* Modal specific styles for standard Bootstrap modals, if kept */
.modal-dialog.modal-md {
  max-width: 500px; /* Standard Bootstrap md modal width */
}
.modal-dialog.modal-sm {
  max-width: 300px; /* Standard Bootstrap sm modal width */
}

/* General button styles for consistency with Bootstrap theme */
.btn-primary {
  background-color: #007bff;
  border-color: #007bff;
}
.btn-primary:hover {
  background-color: #0056b3;
  border-color: #0056b3;
}

.btn-warning {
  background-color: #ffc107;
  border-color: #ffc107;
  color: #212529; /* Dark text for warning button */
}
.btn-warning:hover {
  background-color: #e0a800;
  border-color: #d39e00;
}

.btn-danger {
  background-color: #dc3545;
  border-color: #dc3545;
}
.btn-danger:hover {
  background-color: #c82333;
  border-color: #bd2130;
}

.btn-info {
  background-color: #17a2b8;
  border-color: #17a2b8;
}
.btn-info:hover {
  background-color: #138496;
  border-color: #117a8b;
}

/* Spinner adjustment for small buttons */
.spinner-border-sm {
  width: 1rem;
  height: 1rem;
  margin-right: 0.25rem;
}

/* Error text for form validation */
.text-danger {
  color: #dc3545 !important;
  font-size: 0.875em; /* Small text */
  margin-top: 0.25rem; /* Space below input */
  display: block; /* Ensure it's on its own line */
}
</style>