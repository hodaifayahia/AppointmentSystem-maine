<script setup>
import { ref, computed, defineProps, onMounted } from "vue";
import axios from "axios";
import { useRouter } from "vue-router";
import AddContractModal from "../Models/AddContractModal.vue";
import { useToastr } from '../../../../toster';
import Contract_tablelistitem from "./Contract_tablelistitem.vue";

const toastr = useToastr();

const props = defineProps({
  companyId: {
    type: String,
    required: true,
  },
});

const loading = ref(false);
const router = useRouter();
const addDialog = ref(false);

const searchQuery = ref("");
const searchFilter = ref("contract_name");

const filterOptions = ref([
  { label: "By ID", value: "id" },
  { label: "By Name", value: "contract_name" },
  { label: "Active Only", value: "Active" },
  { label: "Pending Only", value: "Pending" },
  { label: "Expired Only", value: "Expired" },
]);

// Initialize as empty array
const items = ref([]);

const fetchContracts = async () => {
  loading.value = true;
  try {
    const response = await axios.get(`/api/conventions/`, {
      params: { organisme_id: props.companyId }
    });
    
    // Add defensive checks for API response structure
    if (response.data && response.data.data) {
      // Handle paginated response
      if (Array.isArray(response.data.data.data)) {
        items.value = response.data.data.data; // Paginated response
      } else if (Array.isArray(response.data.data)) {
        items.value = response.data.data; // Direct array response
      } else {
        console.warn("Unexpected API response structure:", response.data);
        items.value = [];
      }
    } else {
      console.warn("No data in API response:", response.data);
      items.value = [];
    }
    
    toastr.success("Contracts loaded successfully");
  } catch (error) {
    console.error("Failed to fetch contracts:", error);
    items.value = []; // Reset to empty array on error
    toastr.danger("Failed to load contracts");
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchContracts();
});

// Add defensive checks in computed property
const filteredItems = computed(() => {
  // Ensure items.value is always an array
  const itemsArray = Array.isArray(items.value) ? items.value : [];
  
  if (["Active", "Pending", "Expired"].includes(searchFilter.value)) {
    return itemsArray.filter((item) => item.status === searchFilter.value);
  }
  
  return itemsArray.filter((item) => {
    const searchValue = searchQuery.value.toLowerCase();
    const filterValue = String(item[searchFilter.value] || "").toLowerCase();
    return filterValue.includes(searchValue);
  });
});

const openAddDialog = () => {
  addDialog.value = true;
};

const saveContract = async (contractData) => {
  try {
    const response = await axios.post(`/api/conventions/`, {
      ...contractData,
      organisme_id: props.companyId,
    });
    
    // Ensure items.value is an array before spreading
    const currentItems = Array.isArray(items.value) ? items.value : [];
    items.value = [...currentItems, response.data.data];
    
    addDialog.value = false;
    toastr.success("Contract created successfully");
  } catch (error) {
    const errorMessage = error.response?.data?.message || "Failed to create contract";
    toastr.danger(errorMessage);
    console.error("API error details:", error.response?.data);
  }
};

const deleteContract = async (contractToDelete) => {
  try {
    await axios.delete(`/api/conventions/${contractToDelete.id}`); // Fixed URL
    
    // Ensure items.value is an array before filtering
    const currentItems = Array.isArray(items.value) ? items.value : [];
    items.value = currentItems.filter((item) => item.id !== contractToDelete.id);
    
    toastr.success("Contract deleted successfully");
  } catch (error) {
    console.error("Error deleting contract:", error);
    toastr.danger("Failed to delete contract");
  }
};

const confirmDelete = (contract) => {
  if (
    window.confirm(
      `Are you sure you want to delete contract "${contract.name}"? This action cannot be undone.`
    )
  ) {
    deleteContract(contract);
  } else {
    toastr.info("Contract deletion cancelled.");
  }
};

const moreInfo = (contract) => {
  router.push({
    name: "convention.contract",
    params: { id: contract.id },
  });
};
</script>

<template>
  <div class="container-fluid py-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center mb-4 gap-3">
      <div class="position-relative flex-grow-1 d-flex align-items-center gap-3 w-100">
        <select v-model="searchFilter" class="form-select border rounded-3">
          <option v-for="option in filterOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
        <input type="text" v-model="searchQuery" placeholder="Search contracts..." class="form-control w-100 rounded-3" />
      </div>
      <button class="btn btn-primary add-contract-button" @click="openAddDialog">
        <i class="fas fa-plus"></i> <span> Contract</span>
      </button>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <div v-if="items.length === 0 && !loading" class="text-center text-muted py-5 d-flex flex-column align-items-center">
          <i class="fas fa-file fs-3 mb-2"></i>
          <span>No contracts found.</span>
        </div>
        <div v-else-if="loading" class="loading-state">
          <div class="spinner" role="status">
            <span class="sr-only">Loading...</span>
          </div>
          <p class="loading-text">Loading contracts...</p>
        </div>
        <div v-else class="table-responsive">
          <table class="table table-striped table-hover contract-table">
            <thead>
              <tr class="table-header-row">
                <th class="table-header">ID</th>
                <th class="table-header">Name</th>
                <th class="table-header">Start Date</th>
                <th class="table-header">End Date</th>
                <th class="table-header">Status</th>
              </tr>
            </thead>
            <tbody>
              <Contract_tablelistitem
                v-for="item in filteredItems"
                :key="item.id"
                :contract="item"
                @delete-contract="confirmDelete"
                @view-details="moreInfo"
              />
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <AddContractModal
      :visible="addDialog"
      @close="addDialog = false"
      @save="saveContract"
    />
  </div>
</template>

<style scoped>
/* All the remaining styles from the original component go here */
.container-fluid {
  padding-top: 1.5rem;
  padding-bottom: 1.5rem;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  min-height: 100vh;
}

.alert {
  position: fixed;
  top: 1rem;
  right: 1rem;
  z-index: 1050;
  min-width: 250px;
  border-radius: 0.5rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  animation: slideInRight 0.5s forwards;
}

.alert.fade.show {
  opacity: 1;
}

@keyframes slideInRight {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.add-contract-button {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: #ffffff;
  font-weight: 600;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
  transition: all 0.2s;
  border: none;
  cursor: pointer;
}

.add-contract-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 8px -1px rgba(59, 130, 246, 0.4);
}

.form-select, .form-control {
  border: 2px solid #e5e7eb;
  border-radius: 0.5rem;
  font-size: 0.7rem;
  transition: all 0.2s;
  background-color: #ffffff;
}

.form-select:focus, .form-control:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.card {
  background: #ffffff;
  border-radius: 1rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  overflow: hidden;
  border: 1px solid #e2e8f0;
}

.contract-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.table-header-row {
  background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
  border-bottom: 2px solid #cbd5e1;
}

.table-header {
  padding: 1rem 1.5rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.05em;
}

.table-row {
  transition: background-color 0.2s ease;
}

.table-row:nth-child(odd) {
  background-color: #f9fafb;
}

.table-row:hover {
  background-color: #f1f5f9;
}

.table-cell {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  vertical-align: middle;
  color: #4b5563;
}

.actions-cell {
  text-align: center;
  white-space: nowrap;
}

.action-button {
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  transition: all 0.2s ease;
}

.action-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.info-button {
  background-color: #17a2b8;
  border-color: #17a2b8;
  color: #ffffff;
}

.info-button:hover {
  background-color: #138496;
  border-color: #117a8b;
}

.delete-button {
  background-color: #dc3545;
  border-color: #dc3545;
  color: #ffffff;
}

.delete-button:hover {
  background-color: #c82333;
  border-color: #bd2130;
}

.loading-state {
  text-align: center;
  padding: 4rem 2rem;
}

.spinner {
  display: inline-block;
  width: 3rem;
  height: 3rem;
  border: 4px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-text {
  color: #64748b;
  margin-top: 1rem;
  font-size: 1rem;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.no-contracts {
  padding: 4rem 2rem;
  text-align: center;
}

.no-contracts-content {
  max-width: 400px;
  margin: 0 auto;
}

.no-contracts-icon {
  font-size: 4rem;
  color: #cbd5e1;
  margin-bottom: 1.5rem;
}

.no-contracts-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
}

.no-contracts-text {
  color: #6b7280;
  margin-bottom: 2rem;
  line-height: 1.6;
}

/* Responsive Design */
@media (max-width: 768px) {
  .container-fluid {
    padding: 1rem;
  }

  .d-flex.flex-column.flex-lg-row {
    flex-direction: column;
    gap: 1rem;
  }

  .position-relative.flex-grow-1 {
    flex-direction: column;
    gap: 1rem;
  }

  .form-select, .form-control {
    width: 100%;
  }

  .add-contract-button {
    width: 100%;
  }
}
</style>