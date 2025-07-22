<script setup>
import { ref, computed, defineProps, onMounted } from "vue";
import axios from "axios";
import { useRouter } from "vue-router";
import { useToast } from "primevue/usetoast"; // PrimeVue Toast service

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog'; // Not strictly needed here if ContractModal handles its own visibility, but good to keep if you ever make it a standalone dialog
import ProgressSpinner from 'primevue/progressspinner';
import Toast from 'primevue/toast'; // Toast component for display

// Refactor AddContractModal to be a more general ContractModal
import ContractModal from "../Models/AddContractModal.vue"; // We'll modify this file

const toast = useToast(); // Initialize PrimeVue Toast

const props = defineProps({
  companyId: {
    type: String,
    required: true,
  },
});

const loading = ref(false);
const router = useRouter();
const addDialog = ref(false);
const editDialog = ref(false); // New: State for edit dialog visibility
const contractToEdit = ref(null); // New: Stores the contract object being edited

const searchQuery = ref("");
const searchFilter = ref("contract_name");

const filterOptions = ref([
  { label: "By ID", value: "id" },
  { label: "By Name", value: "contract_name" },
  { label: "Active Only", value: "Active" },
  { label: "Pending Only", value: "Pending" },
  { label: "Expired Only", value: "Expired" },
]);

const items = ref([]);

const fetchContracts = async () => {
  loading.value = true;
  try {
    const response = await axios.get(`/api/conventions/`, {
      params: { organisme_id: props.companyId }
    });

    if (response.data && response.data.data) {
      if (Array.isArray(response.data.data.data)) {
        items.value = response.data.data.data;
      } else if (Array.isArray(response.data.data)) {
        items.value = response.data.data;
      } else {
        console.warn("Unexpected API response structure:", response.data);
        items.value = [];
      }
    } else {
      console.warn("No data in API response:", response.data);
      items.value = [];
    }

    toast.add({ severity: 'success', summary: 'Success', detail: 'Contracts loaded successfully', life: 3000 });
  } catch (error) {
    console.error("Failed to fetch contracts:", error);
    items.value = [];
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load contracts', life: 3000 });
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchContracts();
});

const filteredItems = computed(() => {
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
  contractToEdit.value = null; // Clear any pre-existing data
  addDialog.value = true;
};

// New: Method to open edit dialog
const openEditDialog = (contract) => {
  contractToEdit.value = { ...contract }; // Create a shallow copy to avoid direct mutation
  editDialog.value = true;
};

const saveContract = async (contractData) => {
  try {
    const response = await axios.post(`/api/conventions/`, {
      ...contractData,
      organisme_id: props.companyId,
    });

    const currentItems = Array.isArray(items.value) ? items.value : [];
    items.value = [...currentItems, response.data.data];

    addDialog.value = false;
    toast.add({ severity: 'success', summary: 'Success', detail: 'Contract created successfully', life: 3000 });
  } catch (error) {
    const errorMessage = error.response?.data?.message || "Failed to create contract";
    toast.add({ severity: 'error', summary: 'Error', detail: errorMessage, life: 3000 });
    console.error("API error details:", error.response?.data);
  }
};

// New: Method to update contract
const updateContract = async (contractData) => {
  try {
    const response = await axios.put(`/api/conventions/${contractData.id}`, contractData);

    const currentItems = Array.isArray(items.value) ? items.value : [];
    const index = currentItems.findIndex(item => item.id === contractData.id);
    if (index !== -1) {
      items.value[index] = response.data.data; // Update the item in the array
    }

    editDialog.value = false;
    toast.add({ severity: 'success', summary: 'Success', detail: 'Contract updated successfully', life: 3000 });
  } catch (error) {
    const errorMessage = error.response?.data?.message || "Failed to update contract";
    toast.add({ severity: 'error', summary: 'Error', detail: errorMessage, life: 3000 });
    console.error("API error details:", error.response?.data);
  }
};

const deleteContract = async (contractToDelete) => {
  try {
    await axios.delete(`/api/conventions/${contractToDelete.id}`);

    const currentItems = Array.isArray(items.value) ? items.value : [];
    items.value = currentItems.filter((item) => item.id !== contractToDelete.id);

    toast.add({ severity: 'success', summary: 'Success', detail: 'Contract deleted successfully', life: 3000 });
  } catch (error) {
    console.error("Error deleting contract:", error);
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete contract', life: 3000 });
  }
};

const confirmDelete = (contract) => {
  if (
    window.confirm(
      `Are you sure you want to delete contract "${contract.contract_name}"? This action cannot be undone.`
    )
  ) {
    deleteContract(contract);
  } else {
    toast.add({ severity: 'info', summary: 'Info', detail: 'Contract deletion cancelled.', life: 3000 });
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
  <div class="container">
    <Toast />

    <div class="header-row">
      <div class="filters">
        <Dropdown
          v-model="searchFilter"
          :options="filterOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="Filter"
          class="filter-dropdown"
        />
        <InputText
          v-model="searchQuery"
          placeholder="Search contracts..."
          class="filter-input"
        />
      </div>
      <Button
        label="Add Contract"
        icon="pi pi-plus"
        class="add-btn"
        @click="openAddDialog"
      />
    </div>

    <div class="table-card">
      <div v-if="loading" class="centered-col loading">
        <ProgressSpinner style="width:48px; height:48px" strokeWidth="5" />
        <span class="loading-label">Loading contracts...</span>
      </div>

      <div v-else-if="filteredItems.length === 0" class="centered-col empty-state">
        <i class="pi pi-folder-open empty-icon"></i>
        <span>No contracts found</span>
      </div>

      <div v-else>
        <DataTable
          :value="filteredItems"
          stripedRows
          :paginator="true"
          :rows="10"
          :rowsPerPageOptions="[5, 10, 20]"
          responsiveLayout="scroll"
          class="contracts-table"
        >
          <Column field="id" header="ID" />
          <Column field="contract_name" header="Name" />
          <Column field="start_date" header="Start Date" />
          <Column field="end_date" header="End Date" />
          <Column field="status" header="Status">
            <template #body="slotProps">
              <span
                :class="['status-tag', {
                  active: slotProps.data.status === 'Active',
                  pending: slotProps.data.status === 'Pending',
                  expired: slotProps.data.status === 'Expired'
                }]"
              >
                {{ slotProps.data.status }}
              </span>
            </template>
          </Column>
          <Column header="Actions" :exportable="false">
            <template #body="slotProps">
              <div class="action-btns">
                <Button
                  icon="pi pi-info-circle"
                  class="p-button-sm p-button-text info-btn"
                  @click="moreInfo(slotProps.data)"
                  v-tooltip.top="'View Details'"
                />
                <Button
                  icon="pi pi-pencil"
                  class="p-button-sm p-button-text edit-btn"
                  @click="openEditDialog(slotProps.data)"
                  v-tooltip.top="'Edit Contract'"
                />
                <Button
                  icon="pi pi-trash"
                  class="p-button-sm p-button-text delete-btn"
                  @click="confirmDelete(slotProps.data)"
                  v-tooltip.top="'Delete Contract'"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </div>
    </div>

    <ContractModal
      :visible="addDialog"
      @close="addDialog = false"
      @save="saveContract"
      :isEdit="false"
    />

    <ContractModal
      :visible="editDialog"
      @close="editDialog = false"
      @save="updateContract"
      :contractData="contractToEdit"
      :isEdit="true"
    />
  </div>
</template>

<style scoped>
/* Your existing styles */
.container {
  padding: 2rem 1.5rem;
  min-height: 100vh;
  min-width: 80vw;
  background: linear-gradient(135deg, #f4f8fa 0%, #e9edf2 100%);
}

.header-row {
  display: flex;
  flex-wrap: wrap;
  gap: 1.5rem;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.filters {
  display: flex;
  flex: 1;
  gap: 1rem;
  min-width: 250px;
}

.filter-dropdown,
.filter-input {
  width: 220px;
  max-width: 100%;
  font-size: 1rem;
  border-radius: 6px;
}

@media (max-width: 700px) {
  .header-row {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  .filters {
    flex-direction: column;
    gap: 0.75rem;
  }
  .filter-dropdown,
  .filter-input {
    width: 100%;
  }
}

.add-btn {
  font-weight: bold;
  border-radius: 6px;
  background: linear-gradient(90deg, #007ad9 40%, #094989 100%);
  color: #fff;
  border: none;
  letter-spacing: 0.01em;
  box-shadow: 0 3px 12px -6px #007ad955;
}
.add-btn:hover,
.add-btn:focus {
  background: linear-gradient(90deg, #116ab8 0%, #094989 100%);
}

.table-card {
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 4px 16px -8px #007ad915;
  padding: 1.5rem;
}

.centered-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}
.loading { min-height: 220px; }
.loading-label { margin-top: 1.1rem; color: #5580a0; }
.empty-state {
  min-height: 220px;
  color: #a0a7b3;
}
.empty-icon {
  font-size: 2.7rem;
  margin-bottom: 0.7rem;
  color: #b0bdc9;
}

.contracts-table {
  font-size: 0.95rem;
  border-radius: 6px;
}

/* Status tags styling */
.status-tag {
  display: inline-block;
  font-weight: 600;
  font-size: 0.92rem;
  padding: 3px 16px;
  border-radius: 16px;
  letter-spacing: 0.04em;
  text-transform: capitalize;
  margin: 0 2px;
}
.status-tag.active {
  background: #e6fbee;
  color: #2b974c;
  border: 1px solid #56dd8e55;
}
.status-tag.pending {
  background: #fff8e1;
  color: #be8301;
  border: 1px solid #f6bf26aa;
}
.status-tag.expired {
  background: #ffeaea;
  color: #c11c2a;
  border: 1px solid #e2606055;
}

/* Action buttons styling */
.action-btns {
  display: flex;
  gap: 0.5rem;
}
.info-btn {
  color: #007ad9 !important;
}
.info-btn:hover { background: #e8f1fd !important; }
/* New: Edit button styling */
.edit-btn {
  color: #f6bf26 !important; /* A yellow/orange color */
}
.edit-btn:hover { background: #fff8e1 !important; }

.delete-btn {
  color: #d94233 !important;
}
.delete-btn:hover { background: #fddede !important; }

</style>