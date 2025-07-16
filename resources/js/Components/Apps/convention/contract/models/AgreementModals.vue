<template>
  <div v-if="showModal" class="modal fade show d-block" tabindex="-1" @click.self="closeModal">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            {{ modalTitle }}
          </h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>
        <div class="modal-body">
          <div v-if="modalType === 'delete'">
            <p>Are you sure you want to delete agreement "{{ selectedItem.title || selectedItem.name }}"?</p>
          </div>
          <div v-else-if="modalType === 'info'">
            <p><strong>ID:</strong> {{ selectedItem.id }}</p>
            <p><strong>Title:</strong> {{ selectedItem.title || selectedItem.name }}</p>
            <p><strong>Description:</strong> {{ selectedItem.description || 'No description' }}</p>
            <p><strong>Created At:</strong> {{ formatDate(selectedItem.created_at) }}</p>
            <p v-if="selectedItem.file_path">
              <strong>Document:</strong>
              <a :href="selectedItem.file_url" target="_blank" class="btn btn-sm btn-info ms-2">
                <i class="fas fa-file-alt"></i> View File ({{ getFileExtension(selectedItem.file_path) }})
              </a>
            </p>
            <p v-else><strong>Document:</strong> No file attached</p>
          </div>
          <form v-else @submit.prevent="handleSave">
            <div class="mb-3">
              <label for="agreementTitle" class="form-label">Title</label>
              <input type="text" class="form-control" id="agreementTitle" v-model="localForm.title" required />
            </div>
            <div class="mb-3">
              <label for="agreementDescription" class="form-label">Description</label>
              <textarea class="form-control" id="agreementDescription" v-model="localForm.description" rows="3"></textarea>
            </div>
            <div class="mb-3">
              <label for="agreementFile" class="form-label">Document File</label>
              <input type="file" class="form-control" id="agreementFile" @change="handleFileChange" />
              <div v-if="localForm.file_path" class="mt-2 d-flex align-items-center">
                <span class="me-2">Current file: {{ getFileName(localForm.file_path) }}</span>
                <button type="button" class="btn btn-sm btn-outline-danger" @click="removeCurrentFile">
                  <i class="fas fa-times"></i> Remove
                </button>
              </div>
              <div v-if="localForm.remove_file" class="mt-1 text-danger small">
                File will be removed on save. Upload a new file to replace.
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="closeModal">Close</button>
          <button
            v-if="modalType === 'delete'"
            type="button"
            class="btn btn-danger"
            @click="$emit('delete')"
            :disabled="isLoading"
          >
            <span v-if="isLoading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            {{ isLoading ? 'Deleting...' : 'Delete' }}
          </button>
          <button
            v-else-if="modalType !== 'info'"
            type="submit"
            class="btn btn-primary"
            @click="handleSave"
            :disabled="isLoading"
          >
            <span v-if="isLoading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            {{ isLoading ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  showModal: {
    type: Boolean,
    required: true,
  },
  modalType: {
    type: String,
    required: true, // 'add', 'edit', 'info', 'delete'
  },
  form: {
    type: Object,
    required: true, // This is the `currentForm` from the parent
  },
  selectedItem: {
    type: Object,
    default: () => ({}), // For info/delete display
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close-modal', 'save', 'delete']);

// Use a local copy of the form prop for two-way binding within the modal
const localForm = ref({ ...props.form });

watch(
  () => props.form,
  (newVal) => {
    localForm.value = { ...newVal };
  },
  { deep: true }
);

const modalTitle = computed(() => {
  switch (props.modalType) {
    case 'add':
      return 'Add New Agreement';
    case 'edit':
      return 'Edit Agreement';
    case 'info':
      return 'Agreement Details';
    case 'delete':
      return 'Confirm Delete';
    default:
      return 'Agreement';
  }
});

const closeModal = () => {
  emit('close-modal');
};

const handleSave = () => {
  emit('save', localForm.value);
};

const handleFileChange = (event) => {
  localForm.value.file = event.target.files[0];
  localForm.value.remove_file = false; // If a new file is selected, don't remove the old one
};

const removeCurrentFile = () => {
  localForm.value.file_path = null; // Clear display
  localForm.value.file = null; // Clear actual file input if set
  localForm.value.remove_file = true; // Signal backend to remove the file
};

// Helper function to get file extension
const getFileExtension = (filePath) => {
  if (!filePath) return '';
  const parts = filePath.split('.');
  return parts[parts.length - 1].toUpperCase();
};

// Helper function to get filename from path
const getFileName = (filePath) => {
  if (!filePath) return '';
  const parts = filePath.split('/');
  return parts[parts.length - 1];
};

// Format date function (duplicate, but good to have in modal for info view)
const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-GB');
};
</script>

<style scoped>
/* Modal backdrop and positioning */
.modal.show {
  background-color: rgba(0, 0, 0, 0.5);
  display: flex !important;
  align-items: center;
  justify-content: center;
}

.modal-dialog {
  max-width: 600px;
  width: 90%;
  margin: auto;
}

.modal-content {
  border-radius: 0.5rem;
}

/* Other styles from your main component's modal related styles can be moved here if specific to the modal */
</style>