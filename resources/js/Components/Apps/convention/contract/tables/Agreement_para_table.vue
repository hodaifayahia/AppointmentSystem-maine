<script setup>
import { ref, computed, defineProps, onMounted, watch } from "vue";
import axios from "axios";
import { useToastr } from '../../../../toster'; // Ensure this path is correct

const toast = useToastr();
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL; // Assuming this is defined in your environment variables

const props = defineProps({
  contractState: String,
  avenantpage: String, // 'yes' or 'no'
  avenantState: String, // 'Pending', 'Active', etc.
  contractid: String,
  avenantid: String
});

// Search filters
const searchQuery = ref("");
const searchDate = ref(null);
const filterType = ref("id");

const filterOptions = [
  { label: "By ID", value: "id" },
  { label: "By Creation Time", value: "created_at" },
  { label: "By Start Date", value: "start_date" },
  { label: "By End Date", value: "end_date" }
];

const dialogVisible = ref(false); // Controls the modal's visibility (true/false)
const currentItem = ref({
  id: null,
  start_date: null,
  end_date: null,
  family_auth: "",
  max_price: null,
  min_price: null,
  discount_percentage: null,
  avenant_id: null
});

const familyAuthOptions = ["ascendant", "descendant", "Conjoint", "adherent", "autre"];
const selectedFamilyAuth = ref([]);

watch(selectedFamilyAuth, (newVal) => {
  currentItem.value.family_auth = newVal.join(',');
});

watch(() => currentItem.value.family_auth, (newVal) => {
  if (typeof newVal === 'string' && newVal !== "") {
    selectedFamilyAuth.value = newVal.split(',');
  } else {
    selectedFamilyAuth.value = [];
  }
}, { immediate: true });

const items = ref([]);

// Pagination states
const currentPage = ref(1);
const itemsPerPage = ref(8);

const filteredItems = computed(() => {
  let filtered = items.value;

  if (filterType.value === "id") {
    if (searchQuery.value) {
      filtered = filtered.filter(item =>
        item.id && item.id.toString().includes(searchQuery.value)
      );
    }
  } else if (searchDate.value) {
    const searchDateFormatted = formatDateForAPI(searchDate.value);

    if (filterType.value === "created_at") {
      filtered = filtered.filter(item => formatDateForAPI(item.created_at) === searchDateFormatted);
    } else if (filterType.value === "start_date") {
      filtered = filtered.filter(item => formatDateForAPI(item.start_date) === searchDateFormatted);
    } else if (filterType.value === "end_date") {
      filtered = filtered.filter(item => formatDateForAPI(item.end_date) === searchDateFormatted);
    }
  }
  return filtered;
});


const paginatedFilteredItems = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  const end = start + itemsPerPage.value;
  return filteredItems.value.slice(start, end);
});

const totalPages = computed(() => {
  return Math.ceil(filteredItems.value.length / itemsPerPage.value);
});

const changePage = (page) => {
  if (page > 0 && page <= totalPages.value) {
    currentPage.value = page;
  }
};

const formatDateDisplay = (dateString) => {
  if (!dateString) return "";

  const date = new Date(dateString);
  if (isNaN(date.getTime())) return dateString;

  const day = String(date.getDate()).padStart(2, "0");
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const year = String(date.getFullYear());

  return `${day}/${month}/${year}`;
};

const formatFamilyAuth = (familyAuth) => {
  if (!familyAuth) return "None";
  return String(familyAuth).split(',').filter(s => s.trim() !== '').join(', ');
};

const formatPercentage = (percentage) => {
  const value = parseFloat(percentage);
  return isNaN(value) ? "0%" : `${value}%`;
};

const fetchAgreementDetails = async () => {
  try {
    let response;

    if (props.avenantpage === "yes") {
      if (!props.avenantid) {
        toast.error('Avenant ID is missing for Avenant page');
        return;
      }
      // Use API_BASE_URL here for consistency
      response = await axios.get(`/api/convention/agreementdetails/avenant/${props.avenantid}`);
    } else {
      if (!props.contractid) {
        toast.error('Contract ID is missing');
        return;
      }
      // Use API_BASE_URL here for consistency
      response = await axios.get(`/api/convention/agreementdetails/${props.contractid}`);
    }

    console.log("Raw API Response for Agreement Details:", response.data); // Log the raw response

    let fetchedData = response.data;

    // --- CRITICAL FIX: Check response structure ---
    // If your API wraps the array in a 'data' property (common with Laravel API Resources for collections)
    if (fetchedData && typeof fetchedData === 'object' && fetchedData.data !== undefined) {
      fetchedData = fetchedData.data;
    }

    // If the API returns a single object instead of an array, wrap it in an array
    if (!Array.isArray(fetchedData)) {
      fetchedData = fetchedData ? [fetchedData] : []; // If it's an object, make it an array with that object. If null/undefined, make it an empty array.
    }
    // --- END CRITICAL FIX ---
    
    items.value = fetchedData.map(item => ({
      ...item,
      // Ensure these fields exist before trying to create a Date object
      start_date: item.start_date ? new Date(item.start_date) : null,
      end_date: item.end_date ? new Date(item.end_date) : null,
      // Ensure these fields are parsed correctly as numbers
      max_price: item.max_price !== null && item.max_price !== undefined ? parseFloat(item.max_price) : null,
      min_price: item.min_price !== null && item.min_price !== undefined ? parseFloat(item.min_price) : null,
      discount_percentage: item.discount_percentage !== null && item.discount_percentage !== undefined ? parseFloat(item.discount_percentage) : null,
      family_auth: item.family_auth || ""
    }));
    currentPage.value = 1;
  } catch (error) {
    // Improved error message to show backend's message or a generic one
    toast.error(`Failed to load agreement details: ${error.response?.data?.message || error.message || 'Unknown error'}`);
    console.error("Error fetching agreement details:", error.response?.data || error);
  }
};

onMounted(() => {
  fetchAgreementDetails();
});

const editItem = (item) => {
  currentItem.value = { ...item };
  // Ensure dates are converted to Date objects for the input type="date"
  currentItem.value.start_date = item.start_date ? new Date(item.start_date) : null;
  currentItem.value.end_date = item.end_date ? new Date(item.end_date) : null;
  

  dialogVisible.value = true;
};

const saveItem = async () => {
  try {
    const payload = {
      // Ensure dates are formatted as YYYY-MM-DD for the API
      start_date: formatDateForAPI(currentItem.value.start_date),
      end_date: formatDateForAPI(currentItem.value.end_date),
      family_auth: currentItem.value.family_auth,
      max_price: currentItem.value.max_price,
      min_price: currentItem.value.min_price,
      discount_percentage: currentItem.value.discount_percentage,
      avenant_id: currentItem.value.avenant_id || null
    };

    let apiUrl;
    if (!currentItem.value.id) {
      toast.error('Cannot update: Agreement Detail ID is missing.');
      return;
    }

    if (props.avenantpage === "yes") {
      apiUrl = `/api/convention/agreementdetails/avenant/${props.avenantid}/${currentItem.value.id}`;
    } else {
      apiUrl = `/api/convention/agreementdetails/${props.contractid}/${currentItem.value.id}`;
    }

    await axios.put(apiUrl, payload);

    // Refresh data after successful save
    await fetchAgreementDetails();

    dialogVisible.value = false;

    toast.success('Agreement details updated successfully');
  } catch (error) {
    toast.error(`Failed to update agreement details: ${error.response?.data?.message || error.message}`);
    console.error("Error updating agreement details:", error.response?.data || error);
  }
};

const formatDateForAPI = (date) => {
  if (!date) return null;

  const d = new Date(date);
  if (isNaN(d.getTime())) return null;

  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
};
</script>

<template>
  <div class="container-fluid py-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center mb-4 gap-2">
      <div class="d-flex align-items-center gap-2 flex-grow-1 w-100">
        <select v-model="filterType" class="form-select border rounded-lg w-auto">
          <option v-for="option in filterOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
        <input
          v-if="filterType === 'id'"
          type="text"
          v-model="searchQuery"
          placeholder="Search by ID..."
          class="form-control flex-grow-1"
        />
        <input
          v-else
          type="date"
          v-model="searchDate"
          placeholder="Select Date"
          class="form-control flex-grow-1"
        />
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <div v-if="paginatedFilteredItems.length === 0" class="text-center text-muted py-5 d-flex flex-column align-items-center">
          <i class="fas fa-info-circle fs-3 mb-2"></i>
          <span>No agreement details found.</span>
        </div>
        <div v-else class="table-responsive">
          <table class="table table-striped table-hover">
            <thead>
              <tr>
                <th scope="col">ID</th>
                <th scope="col">Start Date</th>
                <th scope="col">End Date</th>
                <th scope="col">Family Auth</th>
                <th scope="col">Max Price</th>
                <th scope="col">Min Price</th>
                <th scope="col">Compay Discount (%)</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in paginatedFilteredItems" :key="item.id">
                <td>{{ item.id }}</td>
                <td>{{ formatDateDisplay(item.start_date) }}</td>
                <td>{{ formatDateDisplay(item.end_date) }}</td>
                <td>{{ formatFamilyAuth(item.family_auth) }}</td>
                <td>{{ item.max_price !== null ? item.max_price.toFixed(2) : 'N/A' }}</td>
                <td>{{ item.min_price !== null ? item.min_price.toFixed(2) : 'N/A' }}</td>
                <td>{{ formatPercentage(item.discount_percentage) }}</td>
                <td>
                  <button
                    v-if="contractState === 'pending' || avenantState === 'pending'"
                    class="btn btn-sm btn-warning"
                    @click="editItem(item)"
                    title="Edit Detail"
                  >
                    <i class="fas fa-pencil-alt"></i> Edit
                  </button>
                </td>
              </tr>
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
            <li class="page-item" v-for="page in totalPages" :key="page" :class="{ 'active': currentPage === page }">
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

    <div v-if="dialogVisible" class="modal-custom-backdrop">
      <div class="modal-custom-dialog">
        <div class="modal-custom-content">
          <div class="modal-custom-header">
            <h5 class="modal-custom-title">Edit Agreement Detail</h5>
            <button type="button" class="btn-close" @click="dialogVisible = false" aria-label="Close"></button>
          </div>
          <div class="modal-custom-body">
            <form @submit.prevent="saveItem">
              <div class="mb-3">
                <label for="startDate" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="startDate" v-model="currentItem.start_date" required />
              </div>
              <div class="mb-3">
                <label for="endDate" class="form-label">End Date</label>
                <input type="date" class="form-control" id="endDate" v-model="currentItem.end_date" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Family Authorization</label>
                <div>
                  <div v-for="option in familyAuthOptions" :key="option" class="form-check form-check-inline">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      :id="'familyAuth-' + option"
                      :value="option"
                      v-model="selectedFamilyAuth"
                    />
                    <label class="form-check-label" :for="'familyAuth-' + option">{{ option }}</label>
                  </div>
                </div>
              </div>
              <div class="mb-3">
                <label for="maxPrice" class="form-label">Max Price</label>
                <input type="number" step="0.01" class="form-control" id="maxPrice" v-model="currentItem.max_price" />
              </div>
              <div class="mb-3">
                <label for="minPrice" class="form-label">Min Price</label>
                <input type="number" step="0.01" class="form-control" id="minPrice" v-model="currentItem.min_price" />
              </div>
              <div class="mb-3">
                <label for="discountPercentage" class="form-label">Discount Percentage</label>
                <input type="number" step="0.01" class="form-control" id="discountPercentage" v-model="currentItem.discount_percentage" max="100" />
              </div>
            </form>
          </div>
          <div class="modal-custom-footer">
            <button type="button" class="btn btn-secondary" @click="dialogVisible = false">Close</button>
            <button type="button" class="btn btn-primary" @click="saveItem">Save changes</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Base container and existing styles remain */
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

.table th, .table td {
  vertical-align: middle;
  padding: 0.75rem;
}

.text-muted {
  color: #6c757d !important;
}

.fs-3 {
  font-size: calc(1.3rem + .6vw) !important;
}

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
  color: #212529;
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

.pagination {
  margin-bottom: 0;
}

.page-link {
  color: #007bff;
  background-color: #fff;
  border: 1px solid #dee2e6;
}

.page-link:hover {
  color: #0056b3;
  background-color: #e9ecef;
  border-color: #dee2e6;
}

.page-item.active .page-link {
  background-color: #007bff;
  border-color: #007bff;
}

.page-item.disabled .page-link {
  color: #6c757d;
  background-color: #fff;
  border-color: #dee2e6;
}

/* --- Custom Modal Styles (replacing Bootstrap JS modal) --- */

.modal-custom-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent black overlay */
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1050; /* Higher than other content, same as Bootstrap's modal-backdrop */
  overflow-y: auto; /* Enable scrolling for longer content */
}

.modal-custom-dialog {
  background-color: #fff; /* White background for the modal content */
  border-radius: 0.5rem;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); /* Soft shadow */
  padding: 1.5rem;
  width: 90%; /* Responsive width */
  max-width: 700px; /* Max width for larger screens */
  transform: translateY(-50px); /* Slightly move up for animation effect */
  opacity: 0; /* Start hidden for animation */
  animation: modal-slide-in 0.3s forwards ease-out; /* Simple slide-in animation */
}

@keyframes modal-slide-in {
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.modal-custom-content {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.modal-custom-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 1rem;
  border-bottom: 1px solid #dee2e6; /* Light gray border */
  margin-bottom: 1rem;
}

.modal-custom-title {
  margin-bottom: 0;
  font-size: 1.25rem;
  font-weight: 500;
}

.modal-custom-body {
  flex-grow: 1; /* Allows body to take available space */
  overflow-y: auto; /* Allows scrolling within modal body if content is long */
  padding-right: 0.5rem; /* For scrollbar spacing */
}

.modal-custom-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem; /* Space between buttons */
  padding-top: 1rem;
  border-top: 1px solid #dee2e6; /* Light gray border */
  margin-top: 1rem;
}

/* Close button styling */
.btn-close {
  background: none;
  border: none;
  font-size: 1.2rem;
  cursor: pointer;
  color: #6c757d;
  opacity: 0.7;
  transition: opacity 0.15s ease-in-out;
}

.btn-close:hover {
  opacity: 1;
}

/* Apply some Bootstrap form styling classes */
.form-label {
  font-weight: 500;
}
</style>