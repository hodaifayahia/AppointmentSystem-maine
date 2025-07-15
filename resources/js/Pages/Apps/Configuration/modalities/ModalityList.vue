<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../../Components/toster';
import { useSweetAlert } from '../../../../Components/useSweetAlert';
import ModalityModel from '../../../../Components/Apps/Configuration/modalities/ModalityModel.vue';
import ModalityListItem from './ModalityListItem.vue';

const swal = useSweetAlert();
const toaster = useToastr();

// Data
const modalities = ref([]);
const loading = ref(false);
const error = ref(null);
const isModalOpen = ref(false);
const selectedModality = ref(null);

// Filter options
const filterOptions = ref({
    modality_types: [],
    services: [],
    physical_locations: [],
    protocols: [],
    data_retrieval_methods: [],
    operational_statuses: []
});

// Search and Filter states
const searchQuery = ref('');
const filters = ref({
    modality_type_id: '',
    service_id: '',
    operational_status: '',
    physical_location_id: '',
    integration_protocol: '',
    data_retrieval_method: '',
    created_from: '',
    created_to: ''
});

// Pagination
const pagination = ref({
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1
});

// Sorting
const sortBy = ref('created_at');
const sortDirection = ref('desc');

// UI States
const showFilters = ref(false);
const showAdvancedSearch = ref(false);

const primaryColor = '#2563eb';

// Computed
const hasActiveFilters = computed(() => {
    return searchQuery.value || 
           Object.values(filters.value).some(filter => filter !== '');
});

const totalFilteredResults = computed(() => {
    return pagination.value.total;
});

// Watch for changes in search and filters
watch([searchQuery, filters, sortBy, sortDirection], () => {
    if (pagination.value.current_page > 1) {
        pagination.value.current_page = 1;
    }
    getModalities();
}, { deep: true });

/**
 * Fetches the list of modalities from the API with search and filter parameters.
 */
const getModalities = async () => {
    loading.value = true;
    error.value = null;
    
    try {
        const params = {
            page: pagination.value.current_page,
            per_page: pagination.value.per_page,
            sort_by: sortBy.value,
            sort_direction: sortDirection.value,
            search: searchQuery.value,
            ...filters.value
        };

        // Remove empty filters
        Object.keys(params).forEach(key => {
            if (params[key] === '' || params[key] === null || params[key] === undefined) {
                delete params[key];
            }
        });

        const response = await axios.get('/api/modalities', { params });
        
        modalities.value = response.data.data;
        pagination.value = {
            current_page: response.data.current_page,
            per_page: response.data.per_page,
            total: response.data.total,
            last_page: response.data.last_page
        };
    } catch (err) {
        console.error('Error fetching modalities:', err);
        error.value = err.response?.data?.message || 'Failed to load modalities. Please try again.';
        toaster.error(error.value);
    } finally {
        loading.value = false;
    }
};

/**
 * Fetches filter options for dropdowns.
 */
// const getFilterOptions = async () => {
//     try {
//         // const response = await axios.get('/api/modalities/filter-options');
//         filterOptions.value = response.data;
//     } catch (err) {
//         console.error('Error fetching filter options:', err);
//         toaster.error('Failed to load filter options.');
//     }
// };

/**
 * Clears all filters and search.
 */
const clearFilters = () => {
    searchQuery.value = '';
    filters.value = {
        modality_type_id: '',
        service_id: '',
        operational_status: '',
        physical_location_id: '',
        integration_protocol: '',
        data_retrieval_method: '',
        created_from: '',
        created_to: ''
    };
    pagination.value.current_page = 1;
    getModalities();
};

/**
 * Handles sorting change.
 */
const handleSort = (field) => {
    if (sortBy.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortDirection.value = 'asc';
    }
};

/**
 * Handles pagination change.
 */
const changePage = (page) => {
    pagination.value.current_page = page;
    getModalities();
};

/**
 * Changes items per page.
 */
const changePerPage = (perPage) => {
    pagination.value.per_page = perPage;
    pagination.value.current_page = 1;
    getModalities();
};

const getPaginationPages = () => {
            const pages = [];
            const current = this.pagination.current_page;
            const total = this.pagination.last_page;
            
            // Always show first page
            if (current > 3) {
                pages.push(1);
                if (current > 4) {
                    pages.push('...');
                }
            }
            
            // Show pages around current page
            for (let i = Math.max(1, current - 2); i <= Math.min(total, current + 2); i++) {
                pages.push(i);
            }
            
            // Always show last page
            if (current < total - 2) {
                if (current < total - 3) {
                    pages.push('...');
                }
                pages.push(total);
            }
            
            return pages;
        }
      
    


/**
 * Opens the ModalityModel for adding a new modality or editing an existing one.
 */
const openModal = (modality = null) => {
    selectedModality.value = modality ? { ...modality } : {
        name: '',
        internal_code: '',
        modality_type_id: null,
        dicom_ae_title: '',
        port: null,
        physical_location_id: null,
        operational_status: true,
        service_id: null,
        integration_protocol: '',
        connection_configuration: '',
        data_retrieval_method: '',
        ip_address: '',
    };
    isModalOpen.value = true;
};

/**
 * Closes the ModalityModel.
 */
const closeModal = () => {
    isModalOpen.value = false;
    selectedModality.value = null;
};

/**
 * Refreshes the modality list after an action.
 */
const refreshModalities = () => {
    getModalities();
    closeModal();
};

/**
 * Handles the deletion of a modality.
 */
const deleteModality = async (id) => {
    const result = await swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    });

    if (result.isConfirmed) {
        try {
            await axios.delete(`/api/modalities/${id}`);
            toaster.success('Modality deleted successfully!');
            await getModalities();
            swal.fire('Deleted!', 'Your modality has been deleted.', 'success');
        } catch (err) {
            console.error('Error deleting modality:', err);
            const errorMessage = err.response?.data?.message || 'Failed to delete modality.';
            toaster.error(errorMessage);
            swal.fire('Error!', errorMessage, 'error');
        }
    }
};

// Initialize component
onMounted(() => {
    getModalities();
});
</script>

<template>
    <div class="modality-page">
        <div class="content-header">
            <div class="header-flex-container">
                <h1 class="page-title">Modality Management</h1>
                <nav class="breadcrumbs">
                    <ul class="breadcrumb-list">
                        <li><a href="#" class="breadcrumb-link">Home</a></li>
                        <li><i class="fas fa-chevron-right breadcrumb-separator"></i></li>
                        <li>Modalities</li>
                    </ul>
                </nav>
            </div>
        </div>

        <div class="content">
            <div class="container">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Modality List</h2>
                        <div class="header-actions">
                            <button @click="openModal()" class="add-modality-button">
                                <i class="fas fa-plus-circle button-icon"></i>
                                Add New Modality
                            </button>
                        </div>
                    </div>

                    <!-- Search and Filter Section -->
                    <div class="search-filter-section">
                        <!-- Search Bar -->
                        <div class="search-bar">
                            <div class="search-input-container">
                                <i class="fas fa-search search-icon"></i>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search modalities..."
                                    class="search-input"
                                />
                                <button 
                                    v-if="searchQuery"
                                    @click="searchQuery = ''"
                                    class="clear-search-button"
                                >
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Filter Toggle and Actions -->
                        <div class="filter-actions">
                            
                            <button 
                                v-if="hasActiveFilters"
                                @click="clearFilters"
                                class="clear-filters-button"
                            >
                                <i class="fas fa-eraser"></i>
                                Clear All
                            </button>

                            <div class="results-info">
                                <span class="results-count">
                                    {{ totalFilteredResults }} results found
                                </span>
                            </div>
                        </div>

                 
                    </div>

                    <!-- Sorting and Pagination Controls -->
                    <div class="table-controls">
                        <div class="sort-controls">
                            <label class="sort-label">Sort by:</label>
                            <select v-model="sortBy" class="sort-select">
                                <option value="name">Name</option>
                                <option value="internal_code">Internal Code</option>
                                <option value="created_at">Created Date</option>
                                <option value="updated_at">Updated Date</option>
                                <option value="operational_status">Status</option>
                            </select>
                            <button 
                                @click="sortDirection = sortDirection === 'asc' ? 'desc' : 'asc'"
                                class="sort-direction-button"
                            >
                                <i class="fas" :class="sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                            </button>
                        </div>

                        <div class="pagination-controls">
                            <label class="per-page-label">Show:</label>
                            <select v-model="pagination.per_page" @change="changePerPage(pagination.per_page)" class="per-page-select">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <span class="per-page-text">per page</span>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div v-if="loading" class="loading-state">
                        <div class="spinner" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="loading-text">Loading modalities...</p>
                    </div>

                    <!-- Error State -->
                    <div v-else-if="error" class="error-message" role="alert">
                        <strong class="error-bold">Error!</strong>
                        <span class="error-text">{{ error }}</span>
                    </div>

                    <!-- Table with Data -->
                    <div v-else-if="modalities.length > 0" class="table-responsive">
                        <table class="modality-table">
                            <thead>
                                <tr class="table-header-row">
                                    <th class="table-header">#</th>
                                    <th class="table-header">Image</th>
                                    <th class="table-header sortable" @click="handleSort('name')">
                                        Name
                                        <i 
                                            v-if="sortBy === 'name'"
                                            class="fas sort-icon"
                                            :class="sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"
                                        ></i>
                                    </th>
                                    <th class="table-header sortable" @click="handleSort('internal_code')">
                                        Internal Code
                                        <i 
                                            v-if="sortBy === 'internal_code'"
                                            class="fas sort-icon"
                                            :class="sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"
                                        ></i>
                                    </th>
                                    <th class="table-header">Service</th>
                                    <th class="table-header">Protocol</th>
                                    <th class="table-header">Config</th>
                                    <th class="table-header">Data Method</th>
                                    <th class="table-header">IP Address</th>
                                    <th class="table-header sortable" @click="handleSort('operational_status')">
                                        Status
                                        <i 
                                            v-if="sortBy === 'operational_status'"
                                            class="fas sort-icon"
                                            :class="sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"
                                        ></i>
                                    </th>
                                    <th class="table-header actions-header">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-body">
                                <ModalityListItem
                                    v-for="(modality, index) in modalities"
                                    :key="modality.id"
                                    :modality="modality"
                                    :index="index + ((pagination.current_page - 1) * pagination.per_page)"
                                    @edit="openModal"
                                    @delete="deleteModality"
                                />
                            </tbody>
                        </table>
                    </div>

                    <!-- No Results Found -->
                    <div v-else class="no-modalities">
                        <div class="no-results-content">
                            <i class="fas fa-search no-results-icon"></i>
                            <p class="no-results-text">
                                <span v-if="hasActiveFilters">
                                    No modalities found matching your search criteria.
                                </span>
                                <span v-else>
                                    No modalities found. Click "Add New Modality" to get started!
                                </span>
                            </p>
                            <button 
                                v-if="hasActiveFilters"
                                @click="clearFilters"
                                class="clear-filters-button"
                            >
                                <i class="fas fa-eraser"></i>
                                Clear Filters
                            </button>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="pagination.last_page > 1" class="pagination-section">
                        <div class="pagination-info">
                            <span class="pagination-text">
                                Showing {{ ((pagination.current_page - 1) * pagination.per_page) + 1 }} to 
                                {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} of 
                                {{ pagination.total }} results
                            </span>
                        </div>
                        
                        <div class="pagination-buttons">
                            <button 
                                @click="changePage(pagination.current_page - 1)"
                                :disabled="pagination.current_page === 1"
                                class="pagination-button"
                            >
                                <i class="fas fa-chevron-left"></i>
                                Previous
                            </button>

                            <div class="pagination-numbers">
                                <button
                                    v-for="page in getPaginationPages()"
                                    :key="page"
                                    @click="changePage(page)"
                                    :class="['pagination-number', { active: page === pagination.current_page }]"
                                >
                                    {{ page }}
                                </button>
                            </div>

                            <button 
                                @click="changePage(pagination.current_page + 1)"
                                :disabled="pagination.current_page === pagination.last_page"
                                class="pagination-button"
                            >
                                Next
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <ModalityModel
            :show-modal="isModalOpen"
            :modality-data="selectedModality"
            @close="closeModal"
            @modality-updated="refreshModalities"
            @modality-added="refreshModalities"
        />
    </div>
</template>



<style scoped>
/* Base Page Layout */
.modality-page {
    padding: 1rem;
    background-color: #f3f4f6;
    min-height: 100vh;
}

/* Content Header */
.content-header {
    margin-bottom: 1.5rem;
}

.header-flex-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-title {
    font-size: 1.875rem;
    font-weight: 700;
    color: #1f2937;
}

.breadcrumbs {
    font-size: 0.875rem;
}

.breadcrumb-list {
    display: flex;
    align-items: center;
    list-style: none;
    padding: 0;
    margin: 0;
    color: #6b7280;
}

.breadcrumb-list li {
    margin-right: 0.5rem;
}

.breadcrumb-list li:last-child {
    margin-right: 0;
}

.breadcrumb-link {
    color: v-bind(primaryColor);
    text-decoration: none;
}

.breadcrumb-link:hover {
    text-decoration: underline;
}

.breadcrumb-separator {
    font-size: 0.75rem;
    margin-left: 0.5rem;
    margin-right: 0.5rem;
}

.container {
    max-width: 80rem;
    margin-left: auto;
    margin-right: auto;
}

.card {
    background-color: #ffffff;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    border-radius: 0.5rem;
    padding: 1.5rem;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.card-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #374151;
}

.header-actions {
    display: flex;
    gap: 0.5rem;
}

.add-modality-button, .export-button {
    display: inline-flex;
    align-items: center;
    margin-left: 800px;
    padding: 0.5rem 1rem;
    background-color: v-bind(primaryColor);
    color: #ffffff;
    font-weight: 600;
    border-radius: 0.375rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.export-button {
    background-color: #10b981;
}

.add-modality-button:hover {
    background-color: #1d4ed8;
}

.export-button:hover {
    background-color: #059669;
}

.button-icon {
    margin-right: 0.5rem;
}

/* Search and Filter Section */
.search-filter-section {
    margin-bottom: 1.5rem;
}

.search-bar {
    margin-bottom: 1rem;
}

.search-input-container {
    position: relative;
    max-width: 500px;
}

.search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
}

.search-input {
    width: 100%;
    padding: 0.75rem 0.75rem 0.75rem 2.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background-color: #ffffff;
    transition: border-color 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: v-bind(primaryColor);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.clear-search-button {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    padding: 0.25rem;
}

.clear-search-button:hover {
    color: #374151;
}

.filter-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.filter-toggle-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background-color: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-toggle-button.active {
    background-color: v-bind(primaryColor);
    color: #ffffff;
    border-color: v-bind(primaryColor);
}

.filter-count {
    background-color: #ef4444;
    color: #ffffff;
    border-radius: 9999px;
    padding: 0.125rem 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.clear-filters-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background-color: #ef4444;
    color: #ffffff;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.clear-filters-button:hover {
    background-color: #dc2626;
}

.results-info {
    margin-left: auto;
}

.results-count {
    font-size: 0.875rem;
    color: #6b7280;
}

.filters-panel {
    background-color: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.filter-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
}

.filter-select, .filter-input {
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    background-color: #ffffff;
    transition: border-color 0.3s ease;
}

.filter-select:focus, .filter-input:focus {
    outline: none;
    border-color: v-bind(primaryColor);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

/* Table Controls */
.table-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding: 0.75rem;
    background-color: #f9fafb;
    border-radius: 0.375rem;
}

.sort-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.sort-label, .per-page-label {
    font-size: 0.875rem;
    color: #374151;
    font-weight: 500;
}

.sort-select, .per-page-select {
    padding: 0.25rem 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    background-color: #ffffff;
}

.sort-direction-button {
    padding: 0.25rem 0.5rem;
    background-color: #ffffff;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.sort-direction-button:hover {
    background-color: #f3f4f6;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.per-page-text {
    font-size: 0.875rem;
    color: #6b7280;
}

/* Loading State */
.loading-state {
    text-align: center;
    padding: 2rem;
}

.spinner {
    display: inline-block;
    width: 2rem;
    height: 2rem;
    border: 4px solid rgba(37, 99, 235, 0.3);
    border-top-color: v-bind(primaryColor);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
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
}

.loading-text {
    color: #4b5563;
    margin-top: 0.5rem;
}

/* Error Message */
.error-message {
    background-color: #fee2e2;
    border: 1px solid #ef4444;
    color: #b91c1c;
    padding: 0.75rem 1rem;
    border-radius: 0.25rem;
    margin-bottom: 1rem;
}

.error-bold {
    font-weight: 700;
}

.error-text {
    margin-left: 0.5rem;
}

/* Table Styles */
.table-responsive {
    overflow-x: auto;
}

.modality-table {
    width: 100%;
    border-collapse: collapse;
}

.table-header-row {
    background-color: #e5e7eb;
    color: #374151;
    text-transform: uppercase;
    font-size: 0.875rem;
}

.table-header {
    padding: 0.75rem 1rem;
    text-align: left;
    font-weight: 600;
}

.table-header.sortable {
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.table-header.sortable:hover {
    background-color: #d1d5db;
}

.sort-icon {
    margin-left: 0.5rem;
    font-size: 0.75rem;
}

.actions-header {
    text-align: center;
}

.table-body {
    color: #4b5563;
    font-size: 0.875rem;
}

/* No Results */
.no-modalities {
    text-align: center;
    padding: 2rem;
    color: #6b7280;
}

.no-results-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.no-results-icon {
    font-size: 3rem;
    color: #d1d5db;
}

.no-results-text {
    font-size: 1.125rem;
    margin: 0;
}

/* Pagination */
.pagination-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.pagination-info {
    font-size: 0.875rem;
    color: #6b7280;
}

.pagination-buttons {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pagination-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background-color: #ffffff;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.pagination-button:hover:not(:disabled) {
    background-color: #f3f4f6;
}

.pagination-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pagination-numbers {
    display: flex;
    gap: 0.25rem;
}

.pagination-number {
    padding: 0.5rem 0.75rem;
    background-color: #ffffff;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.pagination-number:hover {
    background-color: #f3f4f6;
}

.pagination-number.active {
    background-color: v-bind(primaryColor);
    color: #ffffff;
    border-color: v-bind(primaryColor);
}

/* Responsive Design */
@media (max-width: 768px) {
    .header-flex-container {
        flex-direction: column;
        gap: 1rem;
    }
    
    .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .header-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .filter-actions {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .table-controls {
        flex-direction: column;
        gap: 1rem;
    }
    
    .pagination-section {
        flex-direction: column;
        gap: 1rem;
    }
    
    .filters-grid {
        grid-template-columns: 1fr;
    }
}
</style>

