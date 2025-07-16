<script setup>
import { ref, computed, defineProps, onMounted } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";
// Ensure this path is correct for your toastr setup
import { useToastr } from '../../../../toster';

// No PrimeVue imports needed anymore

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

const props = defineProps({
    contractState: String,
    contractid: String
});

const router = useRouter();
const toast = useToastr(); // Using toastr for notifications

const searchQuery = ref("");
const selectedFilter = ref("id"); // Default filter
const hasPendingAvenant = ref(false); // Controls 'Add Avenant' button
const items = ref([]); // Data for the table

// Pagination states
const currentPage = ref(1);
const itemsPerPage = ref(8); // Corresponds to PrimeVue's :rows="8"

const filterOptions = [
    { label: "By ID", value: "id" },
    { label: "By Contract ID", value: "contract_id" },
    { label: "By Status", value: "status" },
    { label: "By Creation Date", value: "created_at" },
];

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

const fetchAvenants = async () => {
    try {
        // Fetch avenants by contract ID
        if (!props.contractid) {
            toast.error('Contract ID is missing.');
            return;
        }
        const response = await axios.get(`${API_BASE_URL}/api/convention/avenants/contract/${props.contractid}`);
        items.value = response.data;
        currentPage.value = 1; // Reset pagination on new data

        // Check for pending avenants
        const pendingResponse = await axios.get(`${API_BASE_URL}/api/convention/avenants/pending/check/${props.contractid}`);
        hasPendingAvenant.value = pendingResponse.data.hasPending;
    } catch (error) {
        console.error("Failed to fetch avenants data:", error);
        toast.error('Failed to load avenants');
    }
};

const createAvenant = async () => {
    try {
        if (!props.contractid) {
            toast.error('Contract ID is missing for avenant creation.');
            return;
        }
        await axios.post(`${API_BASE_URL}/api/convention/avenants/avenant_creat/${props.contractid}`);
        toast.success('New avenant created successfully');
        // Refresh the data after creating new avenant
        fetchAvenants();
    } catch (error) {
        console.error("Failed to create avenant:", error);
        toast.error('Failed to create avenant');
    }
};

// Fetch avenants data when component is mounted
onMounted(() => {
    fetchAvenants();
});

const getBadgeClass = (status) => {
    switch (String(status).toLowerCase()) { // Ensure case-insensitivity
        case "active":
            return "bg-success";
        case "pending":
            return "bg-warning text-dark"; // Bootstrap warning needs text-dark for readability
        case "expired":
        case "inactive":
            return "bg-danger";
        default:
            return "bg-info";
    }
};

const filteredItemsComputed = computed(() => {
    if (!searchQuery.value) return items.value;

    const query = String(searchQuery.value).toLowerCase();

    return items.value.filter(item => {
        switch (selectedFilter.value) {
            case "id":
                return item.id && String(item.id).includes(query);
            case "contract_id":
                return item.contract_id && String(item.contract_id).includes(query);
            case "status":
                return item.status && String(item.status).toLowerCase().includes(query);
            case "created_at":
                // If searchQuery is a Date object (from HTML type="date" input), format it for comparison
                const searchDateFormatted = searchQuery.value instanceof Date 
                    ? formatDateForDisplay(searchQuery.value) // Format to 'DD/MM/YYYY' for comparison
                    : query;
                return item.created_at && formatDateForDisplay(item.created_at).includes(searchDateFormatted);
            default:
                return true;
        }
    });
});

const moreInfo = (item) => {
    router.push({
        path: `/Avenant/${item.id}`,
    });
};

// Helper to format date for display (DD/MM/YYYY)
const formatDateForDisplay = (dateString) => {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString; // Fallback if invalid date
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    } catch (e) {
        console.error("Error formatting date:", e);
        return dateString;
    }
};
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
            <button v-if="!hasPendingAvenant && (props.contractState ==='Pending'||props.contractState ==='Active')"
                class="btn btn-primary d-flex align-items-center" @click="createAvenant">
                <i class="fas fa-plus me-1"></i> Add Avenant
            </button>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div v-if="items.length === 0"
                    class="text-center text-muted py-5 d-flex flex-column align-items-center">
                    <i class="fas fa-file-excel fs-3 mb-2"></i>
                    <span>No avenants found.</span>
                </div>
                <div v-else class="table-responsive">
                    <table class="table table-striped table-hover avenant-table">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Contract ID</th>
                                <th scope="col">Status</th>
                                <th scope="col">Created At</th>
                                <th scope="col">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in paginatedFilteredItems" :key="item.id">
                                <td>{{ item.id }}</td>
                                <td>{{ item.contract_id }}</td>
                                <td>
                                    <span :class="['badge', getBadgeClass(item.status)]">
                                        {{ item.status }}
                                    </span>
                                </td>
                                <td>{{ formatDateForDisplay(item.created_at) }}</td>
                                <td>
                                    <button class="btn btn-sm btn-info" @click="moreInfo(item)">
                                        <i class="fas fa-eye me-1"></i> Details
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
.avenant-table {
    min-width: 50rem; /* Matches the PrimeVue tableStyle */
}

.table th, .table td {
    vertical-align: middle;
    padding: 0.75rem;
    white-space: nowrap; /* Prevent content from wrapping in table cells */
}

/* "No avenants found" message */
.text-muted {
    color: #6c757d !important;
}
.fs-3 { /* Equivalent to text-3xl for Font Awesome icon */
    font-size: calc(1.3rem + .6vw) !important;
}

/* Badge specific styles */
.badge {
    padding: 0.35em 0.65em;
    font-size: 0.75em;
    font-weight: 700;
    line-height: 1;
    color: #fff;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.25rem;
}

/* Bootstrap badge colors (example, ensure consistency with your theme) */
.badge.bg-success { background-color: #28a745 !important; }
.badge.bg-warning { background-color: #ffc107 !important; }
.badge.bg-warning.text-dark { color: #212529 !important; } /* For better contrast on warning */
.badge.bg-danger { background-color: #dc3545 !important; }
.badge.bg-info { background-color: #17a2b8 !important; }

/* General button styles for consistency with Bootstrap theme */
.btn-primary {
  background-color: #007bff;
  border-color: #007bff;
}
.btn-primary:hover {
  background-color: #0056b3;
  border-color: #0056b3;
}

.btn-info {
  background-color: #17a2b8;
  border-color: #17a2b8;
}
.btn-info:hover {
  background-color: #138496;
  border-color: #117a8b;
}
</style>