<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToastr } from '../toster'; // Adjust path as needed

const toaster = useToastr();

const props = defineProps({
  patientId: {
    type: Number,
    required: true,
  },
});

const documents = ref([]);
const loading = ref(false);
const error = ref(null);

const fetchPatientDocuments = async () => {
  try {
    loading.value = true;
    // Assuming an API endpoint like /api/patients/{patientId}/documents
    const response = await axios.get(`/api/consultation/${props.patientId}/documents`);
    documents.value = response.data.data || response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load patient documents.';
    toaster.error(error.value);
  } finally {
    loading.value = false;
  }
};

const downloadDocument = (documentPath, documentName) => {
  // Assuming your Laravel public disk serves files under /storage
  const url = `/storage/${documentPath}`;
  window.open(url, '_blank');
};

const printDocument = (documentPath) => {
  const url = `/storage/${documentPath}`;
  const printWindow = window.open(url, '_blank');
  printWindow.onload = () => {
    printWindow.print();
  };
};

onMounted(() => {
  if (props.patientId) {
    fetchPatientDocuments();
  }
});

watch(() => props.patientId, (newId) => {
  if (newId) {
    fetchPatientDocuments();
  }
});
</script>

<template>
  <div class="premium-document-history-section">
    <div class="premium-section-subtitle">Patient Document History</div>

    <div v-if="loading" class="premium-loading-state">
      <i class="fas fa-spinner fa-spin fa-2x text-muted mb-3"></i>
      <p>Loading documents...</p>
    </div>

    <div v-else-if="error" class="premium-empty-state">
      <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
      <p>{{ error }}</p>
    </div>

    <div v-else-if="documents.length === 0" class="premium-empty-state">
      <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
      <p>No documents found for this patient.</p>
    </div>

    <div v-else class="premium-table-container">
      <table class="premium-table">
        <thead>
          <tr>
            <th>Document Name</th>
            <th>Document Type</th>
            <th>Saved Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="documents"  v-for="doc in documents" :key="doc.id">
            <td>{{ doc.document_name }}</td>
            <td>{{ doc.document_type }}</td>
            <td>{{ new Date(doc.created_at).toLocaleDateString() }}</td>
            <td>
              <button @click="downloadDocument(doc.document_path, doc.document_name)" class="premium-icon-btn download-btn" title="Download">
                <i class="fas fa-download"></i>
              </button>
              <button @click="printDocument(doc.document_path)" class="premium-icon-btn print-btn" title="Print">
                <i class="fas fa-print"></i>
              </button>
            </td>
          </tr>
          <tr v-else>
            <td colspan="4">
              there is no history for this patient
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.premium-document-history-section {
  background-color: #ffffff;
  padding: 1.5rem;
  border-radius: 10px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  margin-top: 2rem;
}

.premium-loading-state,
.premium-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  color: #6c757d;
  text-align: center;
}

.premium-loading-state p,
.premium-empty-state p {
  font-size: 1.1rem;
  margin-top: 0.5rem;
}

.premium-table-container {
  overflow-x: auto;
}

.premium-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
}

.premium-table th,
.premium-table td {
  padding: 12px 15px;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

.premium-table th {
  background-color: #f1f5f9;
  font-weight: 600;
  color: #334155;
  text-transform: uppercase;
  font-size: 0.85rem;
}

.premium-table tbody tr:hover {
  background-color: #f8fafc;
}

.premium-icon-btn {
  background: none;
  border: none;
  padding: 8px;
  cursor: pointer;
  font-size: 1.1rem;
  border-radius: 5px;
  transition: background-color 0.2s ease-in-out;
}

.premium-icon-btn:hover {
  background-color: #e2e8f0;
}

.download-btn {
  color: #3a5bb1;
}

.print-btn {
  color: #28a745;
  margin-left: 0.5rem;
}
</style>