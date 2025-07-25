<script setup>
import { ref, computed, defineProps, onMounted, watch } from "vue";
import axios from "axios";
import { useRouter } from "vue-router";
import { useToast } from "primevue/usetoast"; // PrimeVue Toast service
import { useConfirm } from 'primevue/useconfirm'; // PrimeVue Confirm service

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import ProgressSpinner from 'primevue/progressspinner';
import Toast from 'primevue/toast'; // Toast component for display
import ConfirmDialog from 'primevue/confirmdialog'; // Confirmation dialog component

import ContractModal from "../Models/AddContractModal.vue";

const toast = useToast();
const confirm = useConfirm(); // Initialize confirm service

const props = defineProps({
    companyId: {
        type: [String, Number],
        required: true
    }
});

const loading = ref(false);
const router = useRouter();
const addDialog = ref(false);
const editDialog = ref(false);
const contractToEdit = ref(null);

const searchQuery = ref("");
const searchFilter = ref("contract_name"); // Default search filter

const filterOptions = ref([
    { label: "By ID", value: "id" },
    { label: "By Name", value: "contract_name" },
    { label: "Active Only", value: "Active" },
    { label: "Pending Only", value: "Pending" },
    { label: "Expired Only", value: "Terminated" }, // Assuming 'Expired' maps to 'Terminated' status
]);

// Pagination State
const currentPage = ref(1);
const rowsPerPage = ref(10);
const totalRecords = ref(0); // To store the total count from the API

const items = ref([]); // This will now hold only the contracts for the current page

// Debounce for search input
let searchTimeout = null;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage.value = 1; // Reset to first page on new search
        fetchContracts();
    }, 500); // 500ms delay
};

// Add watch to debug companyId changes
watch(() => props.companyId, (newId) => {
    console.log('Company ID changed:', newId);
    if (newId) {
        // Reset pagination to first page when companyId changes
        currentPage.value = 1;
        fetchContracts(); // Fetch contracts for the new company ID
    }
}, { immediate: true });

const fetchContracts = async () => {
    console.log("Fetching contracts for company ID:", props.companyId, "Page:", currentPage.value, "Rows:", rowsPerPage.value, "Search:", searchQuery.value, "Filter:", searchFilter.value);

    loading.value = true;
    try {
        // Adjust the API call to include pagination and search parameters
        const response = await axios.get(`/api/conventions/`, {
            params: {
                organisme_id: props.companyId,
                page: currentPage.value,
                per_page: rowsPerPage.value,
                // Pass searchQuery and searchFilter to the backend for server-side filtering
                search_query: searchQuery.value,
                filter_by: searchFilter.value
            }
        });

        if (response.data && response.data.data) {
            // Adjust this based on your actual API response structure.
            if (Array.isArray(response.data.data.data)) { // Example for Laravel's nested 'data.data'
                items.value = response.data.data.data;
                totalRecords.value = response.data.data.total;
            } else if (Array.isArray(response.data.data)) { // Example if 'data' is directly the array
                items.value = response.data.data;
                totalRecords.value = response.data.total || response.data.data.length; // Fallback
            } else {
                console.warn("Unexpected API response structure:", response.data);
                items.value = [];
                totalRecords.value = 0;
            }
        } else {
            console.warn("No data in API response:", response.data);
            items.value = [];
            totalRecords.value = 0;
        }

        // toast.add({ severity: 'success', summary: 'Success', detail: 'Contracts loaded successfully', life: 3000 });
    } catch (error) {
        console.error("Failed to fetch contracts:", error);
        items.value = [];
        totalRecords.value = 0;
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load contracts', life: 3000 });
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchContracts(); // Initial fetch on component mount
});

// Method to handle PrimeVue DataTable pagination events
const onPage = (event) => {
    currentPage.value = event.page + 1; // PrimeVue's page is 0-indexed
    rowsPerPage.value = event.rows;
    fetchContracts(); // Fetch new page data
};

// Filtered items computed property (now primarily for client-side status filtering if applicable, otherwise just displays fetched items)
const filteredItems = computed(() => {
    const itemsArray = Array.isArray(items.value) ? items.value : [];

    // Client-side filtering for statuses if the searchFilter is one of the status options
    if (["Active", "Pending", "Terminated"].includes(searchFilter.value)) {
        return itemsArray.filter((item) => item.status === searchFilter.value);
    }

    // If a text search is active, the backend should handle it.
    // This client-side filter would only apply if the backend *didn't* support search.
    // Since we've added searchQuery to fetchContracts params, this client-side block is mostly redundant for text search.
    // It's kept here just in case, but ideally, the API does the heavy lifting for `searchQuery`.
    if (searchQuery.value && !["id", "contract_name"].includes(searchFilter.value)) {
        // This block would only make sense if searchFilter was not 'id' or 'contract_name'
        // and you still wanted a client-side search on a different field not covered by backend.
        // For 'id' and 'contract_name', backend search (via fetchContracts) is preferred.
    }

    return itemsArray; // Return the current page's items if no specific client-side filter is active
});

const openAddDialog = () => {
    contractToEdit.value = null; // Clear any pre-existing data
    addDialog.value = true;
};

const openEditDialog = (contract) => {
    contractToEdit.value = { ...contract };
    editDialog.value = true;
};

const saveContract = async (contractData) => {
    try {
        const response = await axios.post(`/api/conventions/`, {
            ...contractData,
            organisme_id: props.companyId,
        });

        // Assuming the API returns the newly created contract in response.data
        if (response.data && response.data.data) {
            // Add the new contract to the beginning of the items array
            items.value.unshift(response.data.data);
            totalRecords.value++; // Increment total records
            // If you are on the last page and add a new item, you might need to re-evaluate pagination
            // For simplicity, we just add it to the current view. If the table is full, the last item might be pushed out.
            // A more robust solution might involve checking if total records exceed current page capacity and adjusting.
        } else {
            console.warn("API did not return new contract data after creation.");
        }

        addDialog.value = false;
        toast.add({ severity: 'success', summary: 'Success', detail: 'Contract created successfully', life: 3000 });
    } catch (error) {
        const errorMessage = error.response?.data?.message || "Failed to create contract";
        toast.add({ severity: 'error', summary: 'Error', detail: errorMessage, life: 3000 });
        console.error("API error details:", error.response?.data);
    }
};

const updateContract = async (contractData) => {
    try {
        const response = await axios.put(`/api/conventions/${contractData.id}`, contractData);

        // Assuming the API returns the updated contract in response.data
        if (response.data && response.data.data) {
            // Find the index of the updated contract and replace it
            const index = items.value.findIndex(item => item.id === contractData.id);
            if (index !== -1) {
                items.value[index] = response.data.data;
            } else {
                console.warn("Updated contract not found in current items list.");
                // If the updated item wasn't on the current page, a re-fetch might be necessary,
                // but for simple updates of visible items, this is fine.
            }
        } else {
            console.warn("API did not return updated contract data after update.");
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

        // Remove the deleted contract from the items array
        items.value = items.value.filter(item => item.id !== contractToDelete.id);
        totalRecords.value--; // Decrement total records

        // If the current page becomes empty after deletion and it's not the first page,
        // you might want to move to the previous page.
        if (items.value.length === 0 && currentPage.value > 1) {
            currentPage.value--;
            fetchContracts(); // Re-fetch for the previous page
        } else {
            // If staying on the same page, re-fetch just to ensure the pagination state is correct
            // (e.g., if a new item needs to be pulled from the next "page" on the server to fill the gap).
            // This is a common pattern for lazy loaded tables when deleting.
            fetchContracts();
        }

        toast.add({ severity: 'success', summary: 'Success', detail: 'Contract deleted successfully', life: 3000 });
    } catch (error) {
        console.error("Error deleting contract:", error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete contract', life: 3000 });
    }
};

const confirmDelete = (contract) => {
    confirm.require({
        message: `Are you sure you want to delete the contract "${contract.contract_name}"?`,
        header: 'Delete Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Yes, Delete',
        rejectLabel: 'Cancel',
        accept: () => {
            deleteContract(contract);
        },
        reject: () => {
            toast.add({ 
                severity: 'info', 
                summary: 'Cancelled', 
                detail: 'Contract deletion cancelled', 
                life: 3000 
            });
        }
    });
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
        <ConfirmDialog />

        <div class="header-row">
            <div class="filters">
                <Dropdown
                    v-model="searchFilter"
                    :options="filterOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Filter"
                    class="filter-dropdown"
                    @change="fetchContracts" />
                <InputText
                    v-model="searchQuery"
                    placeholder="Search contracts..."
                    class="filter-input"
                    @keyup.enter="fetchContracts" 
                    @input="debouncedSearch" />
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

            <div v-else-if="totalRecords === 0" class="centered-col empty-state">
                <i class="pi pi-folder-open empty-icon"></i>
                <span>No contracts found</span>
            </div>

            <div v-else>
                <DataTable
                    :value="filteredItems" 
                    stripedRows
                    :paginator="true"
                    :rows="rowsPerPage"
                    :rowsPerPageOptions="[10, 20, 50, 100]"
                    :totalRecords="totalRecords"
                    :lazy="true" 
                    @page="onPage" 
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
                                    pending: slotProps.data.status === 'pending',
                                    expired: slotProps.data.status === 'Terminated'
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
            :contractData="{ ...contractToEdit, annexes: contractToEdit?.annexes || [] }"
            :isEdit="true"
        />
    </div>
</template>

<style scoped>
/* Your existing styles remain the same */
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
.edit-btn {
    color: #f6bf26 !important;
}
.edit-btn:hover { background: #fff8e1 !important; }
.delete-btn {
    color: #d94233 !important;
}
.delete-btn:hover { background: #fddede !important; }
</style>
