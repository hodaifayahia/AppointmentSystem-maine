<script setup>
import { ref, watch, defineProps, defineEmits, Teleport, computed } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../../Components/toster'; // Adjust path

const toaster = useToastr();

const props = defineProps({
    showModal: {
        type: Boolean,
        required: true,
    },
    modalityData: {
        type: Object, // `modalityData` will be null for 'add', or an object for 'edit'
        default: null,
    },
});

const emit = defineEmits(['close', 'modalityUpdated', 'modalityAdded']);

// The form data that will be sent to the API
const form = ref({
    name: '',
    internal_code: '',
    modality_type_id: null, // Changed default to null for select
    dicom_ae_title: '',
    port: null, // Changed default to null for number
    physical_location_id: null, // Changed default to null for select
    operational_status: true,
    // --- New Fields Added ---
    service_id: null,
    integration_protocol: '',
    connection_configuration: '',
    data_retrieval_method: '',
    ip_address: '',
    // --- End New Fields ---
});

// Use a computed property to determine if we are editing an existing modality
// This will reactively update whenever props.modalityData changes
const isEditing = computed(() => {
    // We are in edit mode if modalityData is not null and has an 'id'
    return props.modalityData !== null && props.modalityData.id !== undefined && props.modalityData.id !== null;
});

const submitting = ref(false);
const formErrors = ref({});

// Fetch options for select inputs (e.g., Modality Types, Physical Locations, Services)
const modalityTypesOptions = ref([]);
const physicalLocationsOptions = ref([]);
const servicesOptions = ref([]); // New ref for services dropdown

const fetchDropdownOptions = async () => {
    try {
        const [modalityTypesRes, servicesRes] = await Promise.all([
            axios.get('/api/modality-types'), // Ensure this is the correct endpoint for your dropdowns
            // axios.get('/api/physical-locations/dropdown'), // Assuming you have a dropdown endpoint for physical locations
            axios.get('/api/services'), // NEW: Endpoint for services dropdown
        ]);
        modalityTypesOptions.value = modalityTypesRes.data.data || modalityTypesRes.data;
        // physicalLocationsOptions.value = physicalLocationsRes.data.data || physicalLocationsRes.data;
        servicesOptions.value = servicesRes.data.data || servicesRes.data; // Populate services
                console.log(servicesOptions.value);

    } catch (error) {
        console.error('Error fetching dropdown options:', error);
        toaster.error('Failed to load necessary data for the form.');
    }
};

// Watch for changes in showModal prop to initialize the form and fetch options
watch(() => props.showModal, (newVal) => {
    if (newVal) {
        formErrors.value = {}; // Clear errors on modal open

        // If we have modalityData, deep copy it to the form for editing
        if (props.modalityData) {
            form.value = { ...props.modalityData };
        } else {
            // Otherwise, reset the form for a new entry
            form.value = {
                name: '',
                internal_code: '',
                modality_type_id: null,
                dicom_ae_title: '',
                port: null,
                physical_location_id: null,
                operational_status: true,
                // --- New Fields Reset ---
                service_id: null,
                integration_protocol: '',
                connection_configuration: '',
                data_retrieval_method: '',
                ip_address: '',
                // --- End New Fields Reset ---
            };
        }
        // Fetch dropdown options every time the modal opens, ensuring fresh data
        fetchDropdownOptions();
    }
}, { immediate: true }); // Run immediately if modal is already open on component mount

const saveModality = async () => {
    submitting.value = true;
    formErrors.value = {}; // Clear previous errors

    try {
        let response;
        // Check isEditing computed property to decide between update and create
        if (isEditing.value && form.value.id) {
            // Update existing modality
            response = await axios.put(`/api/modalities/${form.value.id}`, form.value);
            toaster.success('Modality updated successfully!');
            emit('modalityUpdated'); // Emit event to refresh list
        } else {
            // Add new modality
            response = await axios.post('/api/modalities', form.value);
            toaster.success('Modality added successfully!');
            emit('modalityAdded'); // Emit event to refresh list
        }
        emit('close'); // Close modal on success
    } catch (error) {
        console.error('Error saving modality:', error);
        if (error.response && error.response.status === 422) {
            formErrors.value = error.response.data.errors;
            toaster.error('Please correct the validation errors.');
        } else {
            toaster.error(error.response?.data?.message || 'Failed to save modality. Please try again.');
        }
    } finally {
        submitting.value = false;
    }
};

const primaryColor = '#2563eb';
</script>

<template>
    <Teleport to="body">
        <div v-if="props.showModal" class="modal-overlay">
            <div class="modal-container">
                <div class="modal-header">
                    <h3 class="modal-title">{{ isEditing ? 'Edit Modality' : 'Add New Modality' }}</h3>
                    <button @click="emit('close')" class="modal-close-button">&times;</button>
                </div>
                <form @submit.prevent="saveModality" class="modal-form">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" v-model="form.name" :class="{ 'is-invalid': formErrors.name }" required />
                        <div v-if="formErrors.name" class="invalid-feedback">{{ formErrors.name[0] }}</div>
                    </div>

                    <div class="form-group">
                        <label for="internal_code">Internal Code</label>
                        <input type="text" id="internal_code" v-model="form.internal_code" :class="{ 'is-invalid': formErrors.internal_code }" required />
                        <div v-if="formErrors.internal_code" class="invalid-feedback">{{ formErrors.internal_code[0] }}</div>
                    </div>

                    <div class="form-group">
                        <label for="modality_type_id">Modality Type</label>
                        <select id="modality_type_id" v-model="form.modality_type_id" :class="{ 'is-invalid': formErrors.modality_type_id }" required>
                            <option :value="null" disabled>Select a Modality Type</option>
                            <option v-for="type in modalityTypesOptions" :key="type.id" :value="type.id">
                                {{ type.name }}
                            </option>
                        </select>
                        <div v-if="formErrors.modality_type_id" class="invalid-feedback">{{ formErrors.modality_type_id[0] }}</div>
                    </div>

                    <div class="form-group">
                        <label for="physical_location_id">Physical Location</label>
                        <select id="physical_location_id" v-model="form.physical_location_id" :class="{ 'is-invalid': formErrors.physical_location_id }" required>
                            <option :value="null" disabled>Select a Physical Location</option>
                            <option v-for="location in physicalLocationsOptions" :key="location.id" :value="location.id">
                                {{ location.name }}
                            </option>
                        </select>
                        <div v-if="formErrors.physical_location_id" class="invalid-feedback">{{ formErrors.physical_location_id[0] }}</div>
                    </div>

                    <div class="form-group">
                        <label for="service_id">Service/Department</label>
                        <select id="service_id" v-model="form.service_id" :class="{ 'is-invalid': formErrors.service_id }">
                            <option :value="null">None</option> <option v-for="service in servicesOptions" :key="service.id" :value="service.id">
                                {{ service.name }}
                            </option>
                        </select>
                        <div v-if="formErrors.service_id" class="invalid-feedback">{{ formErrors.service_id[0] }}</div>
                    </div>

                    <div class="form-group">
                        <label for="integration_protocol">Integration Protocol</label>
                        <input type="text" id="integration_protocol" v-model="form.integration_protocol" :class="{ 'is-invalid': formErrors.integration_protocol }" />
                        <div v-if="formErrors.integration_protocol" class="invalid-feedback">{{ formErrors.integration_protocol[0] }}</div>
                    </div>

                    <div class="form-group">
                        <label for="connection_configuration">Connection Configuration</label>
                        <textarea id="connection_configuration" v-model="form.connection_configuration" :class="{ 'is-invalid': formErrors.connection_configuration }" rows="4" cols="80"></textarea>
                        <div v-if="formErrors.connection_configuration" class="invalid-feedback">{{ formErrors.connection_configuration[0] }}</div>
                    </div>

                    <div class="form-group">
                        <label for="data_retrieval_method">Data Retrieval Method</label>
                        <input type="text" id="data_retrieval_method" v-model="form.data_retrieval_method" :class="{ 'is-invalid': formErrors.data_retrieval_method }" />
                        <div v-if="formErrors.data_retrieval_method" class="invalid-feedback">{{ formErrors.data_retrieval_method[0] }}</div>
                    </div>

                    <div class="form-group">
                        <label for="ip_address">IP Address</label>
                        <input type="text" id="ip_address" v-model="form.ip_address" :class="{ 'is-invalid': formErrors.ip_address }" />
                        <div v-if="formErrors.ip_address" class="invalid-feedback">{{ formErrors.ip_address[0] }}</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="dicom_ae_title">DICOM AE Title</label>
                        <input type="text" id="dicom_ae_title" v-model="form.dicom_ae_title" :class="{ 'is-invalid': formErrors.dicom_ae_title }" />
                        <div v-if="formErrors.dicom_ae_title" class="invalid-feedback">{{ formErrors.dicom_ae_title[0] }}</div>
                    </div>

                    <div class="form-group">
                        <label for="port">Port</label>
                        <input type="number" id="port" v-model.number="form.port" :class="{ 'is-invalid': formErrors.port }" required />
                        <div v-if="formErrors.port" class="invalid-feedback">{{ formErrors.port[0] }}</div>
                    </div>
                    <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" v-model="form.operational_status" :class="{ 'is-invalid': formErrors.operational_status }" required>
                        <option value="Working">Working</option>
                        <option value="Not Working">Not Working</option>
                        <option value="In Maintenance">In Maintenance</option>
                    </select>
                    <div v-if="formErrors.operational_status" class="invalid-feedback">{{ formErrors.operational_status[0] }}</div>
                </div>

                    <div class="modal-actions">
                        <button type="button" @click="emit('close')" class="cancel-button">Cancel</button>
                        <button type="submit" :disabled="submitting" class="submit-button">
                            <span v-if="submitting">Saving...</span>
                            <span v-else>{{ isEditing ? 'Update Modality' : 'Add Modality' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-container {
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  width: 90%;
  max-width: 600px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background-color: v-bind(primaryColor);
  color: #fff;
  border-top-left-radius: 8px;
  border-top-right-radius: 8px;
}

.modal-title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
}

.modal-close-button {
  background: none;
  border: none;
  font-size: 2rem;
  color: #fff;
  cursor: pointer;
  line-height: 1;
}

.modal-form {
  padding: 1.5rem;
  overflow-y: auto;
  max-height: 70vh; /* Adjust as needed */
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #4a5568;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group select {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid #cbd5e0;
  border-radius: 6px;
  font-size: 1rem;
  color: #4a5568;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: v-bind(primaryColor);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25);
}

.form-group input.is-invalid,
.form-group select.is-invalid {
  border-color: #ef4444;
}

.invalid-feedback {
  color: #ef4444;
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

.checkbox-group {
  display: flex;
  align-items: center;
  margin-top: 1.5rem;
}

.checkbox-group input[type="checkbox"] {
  margin-right: 0.75rem;
  transform: scale(1.2); /* Make checkbox slightly larger */
}

.checkbox-group label {
  margin-bottom: 0; /* Override default label margin */
  cursor: pointer;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid #e2e8f0;
  background-color: #f8fafc;
}

.cancel-button {
  padding: 0.75rem 1.5rem;
  border: 1px solid #cbd5e0;
  border-radius: 6px;
  background-color: #fff;
  color: #4a5568;
  cursor: pointer;
  transition: background-color 0.2s ease, border-color 0.2s ease;
}

.cancel-button:hover {
  background-color: #edf2f7;
  border-color: #a0aec0;
}

.submit-button {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 6px;
  background-color: v-bind(primaryColor);
  color: #fff;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.submit-button:hover:not(:disabled) {
  background-color: #1d4ed8;
}

.submit-button:disabled {
  background-color: #93c5fd; /* bg-blue-300 */
  cursor: not-allowed;
}
</style>