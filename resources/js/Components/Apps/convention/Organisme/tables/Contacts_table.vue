
<script setup>
import { ref, computed, defineProps, onMounted } from "vue";
import axios from "axios";

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || ''; // Ensure API_BASE_URL is defined

const props = defineProps({
  companyId: {
    type: String,
    required: true
  }
});

// Custom Toast/Alert System
const toastMessage = ref({
  visible: false,
  severity: '', // 'success' or 'danger'
  summary: '',
  detail: '',
  life: 3000 // duration in ms
});

const showToast = (severity, summary, detail) => {
  toastMessage.value = { visible: true, severity, summary, detail };
  setTimeout(() => {
    toastMessage.value.visible = false;
  }, toastMessage.value.life);
};

const hideToast = () => {
  toastMessage.value.visible = false;
};

const searchQuery = ref("");
const searchFilter = ref("Name"); // Default filter by name

// Dropdown options for filtering
const filterOptions = ref([
  { label: "By Name", value: "Name" },
  { label: "By ID", value: "id" },
  { label: "By Job Function", value: "job_function" },
  { label: "By Phone", value: "phone_number" },
  { label: "By Email", value: "email" }
]);

const items = ref([]);
const addDialog = ref(false);
const editDialog = ref(false);
const newContact = ref({ Name: "", job_function: "", phone_number: "", email: "", company_id: props.companyId });
const selectedContact = ref({});
const formErrors = ref({
  Name: "",
  job_function: "",
  phone_number: "",
  email: ""
});

const loading = ref(false); // Loading state for data fetching

// Fetch contacts for the current company
const fetchContacts = async () => {
  loading.value = true;
  try {
    const response = await axios.get(`${API_BASE_URL}/api/convention/contacts/${props.companyId}`);
    const contactData = Array.isArray(response.data) ? response.data :
      (response.data.contacts || response.data.data || []);

    items.value = contactData.map(contact => {
      return {
        id: contact.id,
        Name: contact.Name || contact.name,
        company_id: contact.company_id,
        job_function: contact.job_function || contact.fonction || "", // Handle both possible cases
        phone_number: contact.phone_number || contact.phone || "",
        email: contact.email || ""
      };
    });
    showToast('success', 'Success', 'Contacts loaded successfully');
  } catch (error) {
    console.error("Failed to fetch contacts:", error);
    showToast('danger', 'Error', error.response?.data?.message || 'Failed to load contacts');
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchContacts();
});

const filteredItems = computed(() => {
  return items.value.filter(item => {
    const searchValue = searchQuery.value.toLowerCase();
    const filterValue = String(item[searchFilter.value] || '').toLowerCase(); // Handle null/undefined values
    return filterValue.includes(searchValue);
  });
});

const resetFormErrors = () => {
  formErrors.value = {
    Name: "",
    job_function: "",
    phone_number: "",
    email: ""
  };
};

const validateForm = (formData) => {
  resetFormErrors();
  let isValid = true;

  if (!formData.Name) {
    formErrors.value.Name = "Name is required";
    isValid = false;
  }

  if (!formData.phone_number) {
    formErrors.value.phone_number = "Phone number is required";
    isValid = false;
  } else if (formData.phone_number.length !== 10 || !/^\d+$/.test(formData.phone_number)) {
    formErrors.value.phone_number = "Phone number must be 10 digits";
    isValid = false;
  }

  if (!formData.email) {
    formErrors.value.email = "Email is required";
    isValid = false;
  } else {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(formData.email)) {
      formErrors.value.email = "Invalid email format";
      isValid = false;
    }
  }

  return isValid;
};

const openAddDialog = () => {
  resetFormErrors();
  newContact.value = { Name: "", job_function: "", phone_number: "", email: "", company_id: props.companyId };
  addDialog.value = true;
};

const saveContact = async () => {
  if (!validateForm(newContact.value)) {
    showToast('danger', 'Validation Error', 'Please correct the form errors.');
    return;
  }

  try {
    const response = await axios.post(`${API_BASE_URL}/api/convention/contacts/${props.companyId}`, {
      Name: newContact.value.Name,
      job_function: newContact.value.job_function,
      phone_number: newContact.value.phone_number,
      email: newContact.value.email
    });

    showToast('success', 'Success', 'Contact created successfully');
    addDialog.value = false;
    fetchContacts(); // Refresh the list
  } catch (error) {
    const errorMessage = error.response?.data?.message || 'Failed to create contact';
    showToast('danger', 'Error', errorMessage);
    console.error("Error creating contact:", error);
  }
};

const editItem = (item) => {
  resetFormErrors();
  selectedContact.value = { ...item };
  editDialog.value = true;
};

const updateContact = async () => {
  if (!validateForm(selectedContact.value)) {
    showToast('danger', 'Validation Error', 'Please correct the form errors.');
    return;
  }

  try {
    await axios.put(`${API_BASE_URL}/api/convention/contacts/edit/${selectedContact.value.id}`, {
      Name: selectedContact.value.Name,
      job_function: selectedContact.value.job_function,
      phone_number: selectedContact.value.phone_number,
      email: selectedContact.value.email
    });

    showToast('success', 'Success', 'Contact updated successfully');
    editDialog.value = false;
    fetchContacts(); // Refresh the list
  } catch (error) {
    const errorMessage = error.response?.data?.message || 'Failed to update contact';
    showToast('danger', 'Error', errorMessage);
    console.error("Error updating contact:", error);
  }
};

// Confirm delete contact
const confirmDelete = (contact) => {
  if (window.confirm(`Are you sure you want to delete the contact "${contact.Name}"?`)) {
    deleteContact(contact.id);
  } else {
    showToast('info', 'Cancelled', 'You have cancelled the deletion');
  }
};

// Delete contact
const deleteContact = async (contactId) => {
  try {
    const response = await axios.delete(`${API_BASE_URL}/api/convention/contacts/${contactId}`);
    showToast('success', 'Success', response.data.message || "Contact deleted successfully");
    fetchContacts(); // Refresh the list
  } catch (error) {
    console.error("Error deleting contact:", error);
    showToast('danger', 'Error', error.response?.data?.message || "Failed to delete contact");
  }
};
</script>

<template>
  <div class="container-fluid py-4">
    <!-- Custom Toast/Alert Placeholder (replace with your actual toast system) -->
    <div v-if="toastMessage.visible" :class="['alert', toastMessage.severity === 'success' ? 'alert-success' : 'alert-danger', 'alert-dismissible', 'fade', 'show']" role="alert">
      <strong>{{ toastMessage.summary }}:</strong> {{ toastMessage.detail }}
      <button type="button" class="btn-close" @click="hideToast" aria-label="Close"></button>
    </div>

    <!-- Search Bar & Add Button -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center mb-4 gap-2">
      <div class="position-relative flex-grow-1 d-flex align-items-center gap-2 w-100">
        <select v-model="searchFilter" class="form-select border rounded-3">
          <option v-for="option in filterOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
        <input type="text" v-model="searchQuery" placeholder="Search..." class="form-control w-100 rounded-3" />
      </div>
      <button class="btn btn-primary add-contact-button" @click="openAddDialog">
        <i class="fas fa-plus me-2"></i> Add Contact
      </button>
    </div>

    <!-- Data Table -->
    <div class="card shadow-sm">
      <div class="card-body">
        <div v-if="items.length === 0 && !loading" class="text-center text-muted py-5 d-flex flex-column align-items-center">
          <i class="fas fa-users fs-3 mb-2"></i>
          <span>No contacts found.</span>
        </div>
        <div v-else-if="loading" class="loading-state">
          <div class="spinner" role="status">
            <span class="sr-only">Loading...</span>
          </div>
          <p class="loading-text">Loading contacts...</p>
        </div>
        <div v-else class="table-responsive">
          <table class="table table-striped table-hover contact-table">
            <thead>
              <tr class="table-header-row">
                <th class="table-header">ID</th>
                <th class="table-header">Name</th>
                <th class="table-header">Function</th>
                <th class="table-header">Phone</th>
                <th class="table-header">Email</th>
                <th class="table-header text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in filteredItems" :key="item.id" class="table-row">
                <td class="table-cell">{{ item.id }}</td>
                <td class="table-cell">{{ item.Name }}</td>
                <td class="table-cell">{{ item.job_function }}</td>
                <td class="table-cell">{{ item.phone_number }}</td>
                <td class="table-cell">{{ item.email }}</td>
                <td class="table-cell actions-cell">
                  <button class="btn btn-sm btn-warning action-button edit-button me-2" @click="editItem(item)">
                    <i class="fas fa-pencil-alt"></i>
                  </button>
                  <button class="btn btn-sm btn-danger action-button delete-button" @click="confirmDelete(item)">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Add Contact Dialog (Bootstrap Modal Structure with Custom CSS) -->
    <div v-if="addDialog" class="custom-modal-overlay">
      <div class="custom-modal-container">
        <div class="custom-modal-header">
          <div class="header-content">
            <div class="modal-icon">
              <i class="fas fa-user-plus"></i>
            </div>
            <div>
              <h3 class="modal-title">Add Contact</h3>
              <p class="modal-subtitle">Create a new contact entry</p>
            </div>
          </div>
          <button @click="addDialog = false" class="modal-close-button">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="custom-modal-body">
          <form @submit.prevent="saveContact" class="modal-form">
            <div class="form-group">
              <label for="addName" class="form-label">
                <i class="fas fa-tag label-icon"></i> Name:
              </label>
              <input type="text" id="addName" v-model="newContact.Name" class="form-control form-input"
                     :class="{ 'input-error': formErrors.Name }" />
              <small v-if="formErrors.Name" class="error-message">
                <i class="fas fa-exclamation-circle"></i> {{ formErrors.Name }}
              </small>
            </div>

            <div class="form-group">
              <label for="addFunction" class="form-label">
                <i class="fas fa-briefcase label-icon"></i> Function:
              </label>
              <input type="text" id="addFunction" v-model="newContact.job_function" class="form-control form-input"
                     :class="{ 'input-error': formErrors.job_function }" />
              <small v-if="formErrors.job_function" class="error-message">
                <i class="fas fa-exclamation-circle"></i> {{ formErrors.job_function }}
              </small>
            </div>

            <div class="form-group">
              <label for="addPhone" class="form-label">
                <i class="fas fa-phone-alt label-icon"></i> Phone:
              </label>
              <input type="text" id="addPhone" v-model="newContact.phone_number" class="form-control form-input"
                     :class="{ 'input-error': formErrors.phone_number }" />
              <small v-if="formErrors.phone_number" class="error-message">
                <i class="fas fa-exclamation-circle"></i> {{ formErrors.phone_number }}
              </small>
            </div>

            <div class="form-group">
              <label for="addEmail" class="form-label">
                <i class="fas fa-envelope label-icon"></i> Email:
              </label>
              <input type="text" id="addEmail" v-model="newContact.email" class="form-control form-input"
                     :class="{ 'input-error': formErrors.email }" />
              <small v-if="formErrors.email" class="error-message">
                <i class="fas fa-exclamation-circle"></i> {{ formErrors.email }}
              </small>
            </div>

            <div class="custom-modal-footer">
              <button type="button" class="btn btn-secondary" @click="addDialog = false">
                <i class="fas fa-times me-2"></i> Cancel
              </button>
              <button type="submit" class="btn btn-primary ms-2">
                <i class="fas fa-check me-2"></i> Save
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Edit Contact Dialog (Bootstrap Modal Structure with Custom CSS) -->
    <div v-if="editDialog" class="custom-modal-overlay">
      <div class="custom-modal-container">
        <div class="custom-modal-header">
          <div class="header-content">
            <div class="modal-icon">
              <i class="fas fa-user-edit"></i>
            </div>
            <div>
              <h3 class="modal-title">Edit Contact</h3>
              <p class="modal-subtitle">Update contact information</p>
            </div>
          </div>
          <button @click="editDialog = false" class="modal-close-button">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="custom-modal-body">
          <form @submit.prevent="updateContact" class="modal-form">
            <div class="form-group">
              <label for="editName" class="form-label">
                <i class="fas fa-tag label-icon"></i> Name:
              </label>
              <input type="text" id="editName" v-model="selectedContact.Name" class="form-control form-input"
                     :class="{ 'input-error': formErrors.Name }" />
              <small v-if="formErrors.Name" class="error-message">
                <i class="fas fa-exclamation-circle"></i> {{ formErrors.Name }}
              </small>
            </div>

            <div class="form-group">
              <label for="editFunction" class="form-label">
                <i class="fas fa-briefcase label-icon"></i> Function:
              </label>
              <input type="text" id="editFunction" v-model="selectedContact.job_function" class="form-control form-input"
                     :class="{ 'input-error': formErrors.job_function }" />
              <small v-if="formErrors.job_function" class="error-message">
                <i class="fas fa-exclamation-circle"></i> {{ formErrors.job_function }}
              </small>
            </div>

            <div class="form-group">
              <label for="editPhone" class="form-label">
                <i class="fas fa-phone-alt label-icon"></i> Phone:
              </label>
              <input type="text" id="editPhone" v-model="selectedContact.phone_number" class="form-control form-input"
                     :class="{ 'input-error': formErrors.phone_number }" />
              <small v-if="formErrors.phone_number" class="error-message">
                <i class="fas fa-exclamation-circle"></i> {{ formErrors.phone_number }}
              </small>
            </div>

            <div class="form-group">
              <label for="editEmail" class="form-label">
                <i class="fas fa-envelope label-icon"></i> Email:
              </label>
              <input type="text" id="editEmail" v-model="selectedContact.email" class="form-control form-input"
                     :class="{ 'input-error': formErrors.email }" />
              <small v-if="formErrors.email" class="error-message">
                <i class="fas fa-exclamation-circle"></i> {{ formErrors.email }}
              </small>
            </div>

            <div class="custom-modal-footer">
              <button type="button" class="btn btn-secondary" @click="editDialog = false">
                <i class="fas fa-times me-2"></i> Cancel
              </button>
              <button type="submit" class="btn btn-primary ms-2">
                <i class="fas fa-check me-2"></i> Save
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>


<style scoped>
/* General Page Layout */
.container-fluid {
  padding-top: 1.5rem;
  padding-bottom: 1.5rem;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  min-height: 100vh;
}

/* Custom Alert/Toast Styling */
.alert {
  position: fixed;
  top: 1rem;
  right: 1rem;
  z-index: 1050; /* Above modals */
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

/* Header and Search Bar */
.add-contact-button {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: #ffffff;
  font-weight: 600;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
  transition: all 0.2s;
  border: none;
  cursor: pointer;
  font-size: 0.875rem;
  padding: 0.75rem 1.5rem;
}

.add-contact-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 8px -1px rgba(59, 130, 246, 0.4);
}

.form-select, .form-control {
  border: 2px solid #e5e7eb;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  transition: all 0.2s;
  background-color: #ffffff;
}

.form-select:focus, .form-control:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Card and Table Styling */
.card {
  background: #ffffff;
  border-radius: 1rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  overflow: hidden;
  border: 1px solid #e2e8f0;
}

.contact-table {
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

.edit-button {
  background-color: #ffc107; /* Bootstrap yellow */
  border-color: #ffc107;
  color: #212529;
}

.edit-button:hover {
  background-color: #e0a800;
  border-color: #e0a800;
}

.delete-button {
  background-color: #dc3545; /* Bootstrap red */
  border-color: #dc3545;
  color: #ffffff;
}

.delete-button:hover {
  background-color: #c82333;
  border-color: #bd2130;
}

/* Loading State */
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

/* No Contacts Found */
.no-contacts {
  padding: 4rem 2rem;
  text-align: center;
}

.no-contacts-content {
  max-width: 400px;
  margin: 0 auto;
}

.no-contacts-icon {
  font-size: 4rem;
  color: #cbd5e1;
  margin-bottom: 1.5rem;
}

.no-contacts-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
}

.no-contacts-text {
  color: #6b7280;
  margin-bottom: 2rem;
  line-height: 1.6;
}

/* Custom Modal Styling (mimicking PrimeVue Dialog) */
.custom-modal-overlay {
  position: fixed;
  inset: 0;
  background: linear-gradient(135deg, rgba(17, 24, 39, 0.8), rgba(55, 65, 81, 0.6));
  backdrop-filter: blur(4px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1040; /* Below toast, above other content */
  padding: 1rem;
}

.custom-modal-container {
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  border-radius: 1rem;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.1);
  width: 100%;
  max-width: 42rem;
  max-height: 90vh;
  overflow: hidden;
  position: relative;
  animation: fadeInScale 0.3s ease-out;
}

@keyframes fadeInScale {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.custom-modal-container::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #3b82f6, #8b5cf6, #06b6d4);
}

.custom-modal-header {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  padding: 2rem;
  border-bottom: 1px solid #e2e8f0;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.custom-modal-header .header-content {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.custom-modal-header .modal-icon {
  width: 3rem;
  height: 3rem;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.25rem;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.custom-modal-header .modal-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

.custom-modal-header .modal-subtitle {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0.25rem 0 0 0;
}

.modal-close-button {
  width: 2.5rem;
  height: 2.5rem;
  border: none;
  background: rgba(255, 255, 255, 0.9);
  border-radius: 0.5rem;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  backdrop-filter: blur(4px);
}

.modal-close-button:hover {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  transform: scale(1.1);
}

.custom-modal-body {
  padding: 2rem;
  max-height: calc(90vh - 8rem);
  overflow-y: auto;
}

.modal-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  position: relative;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
}

.label-icon {
  color: #6b7280;
  font-size: 0.875rem;
}

.form-input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  transition: all 0.3s ease;
  background: #ffffff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  transform: translateY(-1px);
}

.form-input::placeholder {
  color: #9ca3af;
}

.input-error {
  border-color: #ef4444;
}

.input-error:focus {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.error-message {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #ef4444;
  font-size: 0.75rem;
  margin-top: 0.5rem;
  padding: 0.5rem;
  background: rgba(239, 68, 68, 0.1);
  border-radius: 0.25rem;
  border-left: 3px solid #ef4444;
}

.custom-modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
  margin-top: 1.5rem;
}

/* Custom Scrollbar for modal body */
.custom-modal-body::-webkit-scrollbar {
  width: 6px;
}

.custom-modal-body::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

.custom-modal-body::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.custom-modal-body::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
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

  .add-contact-button {
    width: 100%;
  }

  .custom-modal-container {
    max-width: 95%;
  }

  .custom-modal-header,
  .custom-modal-body {
    padding: 1.5rem;
  }

  .custom-modal-header .modal-title {
    font-size: 1.25rem;
  }

  .custom-modal-footer {
    flex-direction: column;
  }

  .btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
