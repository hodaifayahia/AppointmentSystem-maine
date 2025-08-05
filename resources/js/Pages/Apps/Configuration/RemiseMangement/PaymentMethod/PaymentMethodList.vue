<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { FilterMatchMode } from 'primevue/api';
import axios from 'axios'; // Import axios

// PrimeVue Components (only Toast and ConfirmDialog remain here as they are global or page-level)
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

// Custom Components
import PageHeader from '../../../../../Components/Apps/Configuration/RemiseMangement/PaymentMethod/PageHeader.vue';
import UserPaymentAccessTable from '../../../../../Components/Apps/Configuration/RemiseMangement/PaymentMethod/UserPaymentAccessTable.vue';
import UserFormDialog from '../../../../../Components/Apps/Configuration/RemiseMangement/PaymentMethod/UserFormDialog.vue';
import AssignPaymentMethodDialog from '../../../../../Components/Apps/Configuration/RemiseMangement/PaymentMethod/AssignPaymentMethodDialog.vue';

const toast = useToast();
const confirm = useConfirm();

// --- Payment Method Data ---
const paymentOptions = ref([]); // Will be fetched from API

const paymentOptionsFilter = computed(() => {
  return [
    { name: 'All Methods', key: 'all' },
    ...paymentOptions.value.map((option) => ({
      name: option.name,
      key: option.key,
    })),
  ];
});

const paymentOptionsDropdown = computed(() => {
  return paymentOptions.value.map((option) => ({
    name: option.name,
    key: option.key,
  }));
});

const getPaymentMethodName = (key) => {
  const method = paymentOptions.value.find((p) => p.key === key);
  return method ? method.name : key;
};

// --- User Management State ---
const users = ref([]); // This array will hold ALL users fetched from the backend
const userDialogVisible = ref(false);
const editMode = ref(false);
const userForm = reactive({
  id: null,
  name: '',
  email: '',
  password: '', // Added password field for creation/update
  status: null,
  allowedMethods: [], // This will be an array of objects: [{ key: 'prepayment', status: 'active' }]
});
const submitted = ref(false);
const userStatusOptions = ref(['Active', 'Inactive', 'Allowed']);

// --- DataTable Filtering State ---
const selectedPaymentMethodFilter = ref(null);
const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS },
  status: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const filteredUsers = computed(() => {
  let filtered = users.value;

  if (filters.value.global.value) {
    const searchTerm = filters.value.global.value.toLowerCase();
    filtered = filtered.filter(
      (user) =>
        user.name.toLowerCase().includes(searchTerm) ||
        user.email.toLowerCase().includes(searchTerm)
    );
  }

  // Filter by status if selected
  if (filters.value.status.value && filters.value.status.value !== 'all') {
    filtered = filtered.filter((user) => user.status === filters.value.status.value);
  }

  if (selectedPaymentMethodFilter.value && selectedPaymentMethodFilter.value !== 'all') {
    const methodKey = selectedPaymentMethodFilter.value;
    // Filter by users who have the selected method with 'active' status
    filtered = filtered.filter((user) =>
      user.allowedMethods.some(
        (method) => method.key === methodKey && method.status === 'active'
      )
    );
  }

  return filtered;
});

// --- Bulk Assignment Dialog State ---
const assignPaymentDialogVisible = ref(false);
// CHANGED: paymentMethodToAssign -> paymentMethodsToAssign (now an Array)
const paymentMethodsToAssign = ref([]);
// selectedUsersForAssignment will hold the FULL user objects selected in the MultiSelect
const selectedUsersForAssignment = ref([]);
const UsersPaymentMethod = ref([]);
const assignSubmitted = ref(false);

// --- API Calls ---
const API_BASE_URL = '/api'; // Adjust if your API is on a different base path

const fetchPaymentMethods = async () => {
  try {
    const response = await axios.get(`${API_BASE_URL}/payment-methods`);
    paymentOptions.value = response.data.data;
    console.log('Fetched payment options:', paymentOptions.value); // Debugging log
    
  } catch (error) {
    console.error('Error fetching payment methods:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Could not load payment methods.',
      life: 3000,
    });
  }
};
const fetchPaymentMethodUsers = async () => {
  try {
    const response = await axios.get(`${API_BASE_URL}/user-payment-methods`);
    UsersPaymentMethod.value = response.data.data;
    console.log('Fetched UsersPaymentMethod:', UsersPaymentMethod.value); // Debugging log
  } catch (error) {
    console.error('Error fetching payment methods:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Could not load payment methods.',
      life: 3000,
    });
  }
};

const fetchUsers = async () => {
  try {
    // This endpoint should return ALL users relevant for management
    const response = await axios.get(`${API_BASE_URL}/users`);
    console.log('Fetched raw users data:', response.data.data); // Inspect this

    // Defensive coding: ensure status and allowedMethods exist and are correct types
    users.value = response.data.data.map((user) => {
      const newUser = { ...user }; // Create a copy to avoid direct mutation of original
      if (newUser.status === undefined || newUser.status === null) {
        console.warn(
          `User ${newUser.id} (${newUser.name}) has an undefined or null status. Setting to 'Inactive'.`
        );
        newUser.status = 'Inactive'; // Default for robustness on frontend
      }
      // Ensure allowedMethods is an array of strings for proper handling in the form and display
      if (typeof newUser.allowedMethods === 'string') {
        newUser.allowedMethods = newUser.allowedMethods.split(',').map(m => m.trim()).filter(m => m !== '');
      } else if (!Array.isArray(newUser.allowedMethods)) {
        console.warn(
          `User ${newUser.id} (${newUser.name}) has invalid allowedMethods. Setting to empty array.`
        );
        newUser.allowedMethods = []; // Ensure it's an array
      }
      return newUser;
    });
  } catch (error) {
    console.error('Error fetching users:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Could not load users.',
      life: 3000,
    });
  }
};

onMounted(async () => {
  await fetchPaymentMethods();
  await fetchUsers();
  await fetchPaymentMethodUsers();
});

// --- Helper Functions for Styling and Logic ---
const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'Active':
      return 'status-active';
    case 'Allowed':
      return 'status-info';
    case 'Inactive':
      return 'status-inactive';
    default:
      return '';
  }
};

const getPaymentMethodTagClass = (key) => {
  switch (key) {
    case 'prepayment':
      return 'tag-green';
    case 'postpayment':
      return 'tag-blue';
    case 'versement':
      return 'tag-orange';
    default:
      return '';
  }
};

const isValidEmail = (email) => {
  const re =
    /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  return re.test(String(email).toLowerCase());
};

const getEmptyMessageForUsers = () => {
  if (filters.value.global.value || filters.value.status.value || selectedPaymentMethodFilter.value) {
    return 'No users match your current filters.';
  }
  return 'No users found. Click "Assign Payment Methods" or "Edit" a user to get started!';
};

const clearAllFilters = () => {
  filters.value.global.value = null;
  filters.value.status.value = null;
  selectedPaymentMethodFilter.value = null;
};

// --- User Dialog Logic ---
const hideUserDialog = () => {
  userDialogVisible.value = false;
  submitted.value = false;
  resetUserForm(); // Reset form on hide
};

const resetUserForm = () => {
  userForm.id = null;
  userForm.name = '';
  userForm.email = '';
  userForm.password = '';
  userForm.status = null;
  userForm.allowedMethods = [];
};

const editUser = (user) => {
  editMode.value = true;
  userDialogVisible.value = true;
  // Deep copy the user object and populate the form
  userForm.id = user.id;
  userForm.name = user.name;
  userForm.email = user.email;
  userForm.password = ''; // Do not pre-fill password for security reasons
  userForm.status = user.status;
  // Ensure allowedMethods is an array of strings. If it's a comma-separated string, split it.
  userForm.allowedMethods = typeof user.allowedMethods === 'string'
    ? user.allowedMethods.split(',').map(m => m.trim()).filter(m => m !== '')
    : (Array.isArray(user.allowedMethods) ? user.allowedMethods : []);
  console.log('Editing user:', userForm); // Debugging: Check populated form data
};


const saveUserPaymentMethod = async () => {
  submitted.value = true;

  // Validation for status (required)
  if (userForm.status === null) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please select a status.',
      life: 3000,
    });
    return;
  }

  // For new users, validate required fields
  if (!editMode.value) {
    if (!userForm.name.trim() || !userForm.email.trim() || !isValidEmail(userForm.email)) {
      toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: 'Please fill in all required fields correctly (Name, Email).',
        life: 3000,
      });
      return;
    }

    if (!userForm.password) {
      toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: 'Password is required for new users.',
        life: 3000,
      });
      return;
    }
  }

  // Construct the payload
  const payload = {
    status: userForm.status,
    allowedMethods: userForm.allowedMethods, // Array of payment method keys
  };

 

  try {
    let response;
    if (editMode.value) {
      // Update existing user's payment methods
      response = await axios.put(`${API_BASE_URL}/user-payment-methods/${userForm.id}`, payload);
    } else {
      // Create new user with payment methods
      response = await axios.post(`${API_BASE_URL}/user-payment-methods`, payload);
    }

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `User payment methods ${editMode.value ? 'updated' : 'created'} successfully`,
      life: 3000,
    });

    hideUserDialog();
    await fetchUsers(); // Re-fetch all users to update the table
    await fetchPaymentMethodUsers(); // Re-fetch payment method users if they are distinct
  } catch (error) {
    console.error('Error saving user payment methods:', error);

    if (error.response && error.response.data && error.response.data.errors) {
      const errors = error.response.data.errors;
      let detailMessage = Object.values(errors).flat().join('<br>');
      toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: detailMessage,
        life: 5000,
        group: 'br',
      });
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'An error occurred while saving.',
        life: 3000,
      });
    }
  }
};


const confirmDeleteUser = (user) => {
  confirm.require({
    message: `Are you sure you want to delete ${user.name}? This action cannot be undone.`,
    header: 'Delete Confirmation',
    icon: 'fas fa-exclamation-triangle',
    acceptClass: 'p-button-danger',
    rejectClass: 'p-button-secondary p-button-outlined',
    acceptLabel: 'Yes, Delete',
    rejectLabel: 'Cancel',
    accept: async () => {
      try {
        await axios.delete(`${API_BASE_URL}/user-payment-methods/${user.id}`);

        toast.add({ severity: 'success', summary: 'Confirmed', detail: 'User deleted', life: 3000 });
        await fetchUsers(); // Refresh the user list
        await fetchPaymentMethodUsers(); // Refresh the payment method users list
      } catch (error) {
        console.error('Error deleting user:', error);
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'An error occurred while deleting the user.',
          life: 3000,
        });
      }
    },
    reject: () => {
      toast.add({ severity: 'info', summary: 'Rejected', detail: 'Delete operation cancelled', life: 3000 });
    },
  });
};

// --- Bulk Assignment Dialog Logic ---
const showAssignPaymentDialog = () => {
  resetAssignPaymentForm();
  assignPaymentDialogVisible.value = true;
  assignSubmitted.value = false;
};

const hideAssignPaymentDialog = () => {
  assignPaymentDialogVisible.value = false;
  assignSubmitted.value = false;
  resetAssignPaymentForm();
};

const resetAssignPaymentForm = () => {
  // CHANGED: paymentMethodToAssign -> paymentMethodsToAssign (reset to empty array)
  paymentMethodsToAssign.value = [];
  selectedUsersForAssignment.value = [];
};

// MODIFIED: performBulkAssignment for a single bulk POST request
const performBulkAssignment = async () => {
  assignSubmitted.value = true;

  // CHANGED: Check paymentMethodsToAssign length instead of null/value
  if (paymentMethodsToAssign.value.length === 0 || selectedUsersForAssignment.value.length === 0) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please select at least one payment method and at least one user.',
      life: 3000,
    });
    return;
  }

  // Extract only the IDs from the selected users
  const userIdsToAssign = selectedUsersForAssignment.value.map((user) => user.id);

  // Prepare the payload for the bulk assignment API call
  const bulkPayload = {
    // CHANGED: Send an array of paymentMethodKeys
    paymentMethodKeys: paymentMethodsToAssign.value,
    userIds: userIdsToAssign,
    status: 'active', // Assuming 'active' for bulk assignment
  };

  console.log('Bulk Assignment Payload to send:', bulkPayload);

  try {
    // CHANGED: Call the correct endpoint that uses your store method
    const response = await axios.post(`${API_BASE_URL}/user-payment-methods`, bulkPayload);

    // Use the response data from your store method
    const message =
      response.data.message ||
      `${userIdsToAssign.length} user(s) updated with ${paymentMethodsToAssign.value.length} new payment method(s).`;

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: message,
      life: 3000,
    });

    hideAssignPaymentDialog();
    await fetchUsers(); // Refresh the user list after the bulk update
    await fetchPaymentMethodUsers(); // Refresh the payment method users list
  } catch (error) {
    console.error('Error performing bulk assignment:', error);

    if (error.response && error.response.data && error.response.data.message) {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response.data.message,
        life: 3000,
      });
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'An error occurred during bulk assignment.',
        life: 3000,
      });
    }
  }
};

const selectAllActiveUsers = () => {
  // This selects all users that have a status of 'Active' from the fetched 'users' list
  selectedUsersForAssignment.value = users.value.filter((user) => user.status === 'Active');
  toast.add({ severity: 'info', summary: 'Users Selected', detail: `All active users added to selection.`, life: 3000 });
};

const removeUserChip = (userToRemove) => {
  selectedUsersForAssignment.value = selectedUsersForAssignment.value.filter(
    (user) => user.id !== userToRemove.id
  );
};
</script>

<template>
  <div class="specialization-page">
    <Toast />
    <ConfirmDialog></ConfirmDialog>

    <PageHeader
      title="Payment Methods & User Access"
      subtitle="Manage payment options and user permissions for each method."
      current-breadcrumb="Payment Access"
    />

    <div class="content">
      <div class="container">
        <UserPaymentAccessTable
          :users="UsersPaymentMethod"
          :filters="filters"
          :payment-options-filter="paymentOptionsFilter"
          :user-status-options="userStatusOptions"
          :selected-payment-method-filter="selectedPaymentMethodFilter"
          @show-assign-payment-dialog="showAssignPaymentDialog"
          @clear-all-filters="clearAllFilters"
          @edit-user="editUser"
          @confirm-delete-user="confirmDeleteUser"
          @update:global-filter="(value) => (filters.global.value = value)"
          @update:status-filter="(value) => (filters.status.value = value)"
          @update:payment-method-filter="(value) => (selectedPaymentMethodFilter = value)"
          @update:filters="(newFilters) => (filters = newFilters)"
          :get-status-badge-class="getStatusBadgeClass"
          :get-payment-method-tag-class="getPaymentMethodTagClass"
          :get-payment-method-name="getPaymentMethodName"
          :get-empty-message-for-users="getEmptyMessageForUsers"
        />
      </div>
    </div>

    <UserFormDialog
      :visible="userDialogVisible"
      :edit-mode="editMode"
      :user-form="userForm"
      :submitted="submitted"
      :user-status-options="userStatusOptions"
      :payment-options-dropdown="paymentOptionsDropdown"
      @hide-dialog="hideUserDialog"
      @save-user="saveUserPaymentMethod"
      :is-valid-email="isValidEmail"
    />

    <AssignPaymentMethodDialog
      :visible="assignPaymentDialogVisible"
      :payment-methods-to-assign="paymentMethodsToAssign"
      :selected-users-for-assignment="selectedUsersForAssignment"
      :assign-submitted="assignSubmitted"
      :payment-options-dropdown="paymentOptionsDropdown"
      :users="users"
      @hide-dialog="hideAssignPaymentDialog"
      @perform-bulk-assignment="performBulkAssignment"
      @select-all-active-users="selectAllActiveUsers"
      @remove-user-chip="removeUserChip"
      @update:payment-methods="(value) => (paymentMethodsToAssign = value)"
      @update:selected-users="(value) => (selectedUsersForAssignment = value)"
    />
  </div>
</template>

<style scoped>
/* Add your scoped styles here */
</style>