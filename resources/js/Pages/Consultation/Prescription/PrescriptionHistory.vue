<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../Components/toster'; // Adjust path if needed for your toaster utility

const toaster = useToastr();

const props = defineProps({
  patientId: {
    type: [Number, String], // Keep as [Number, String] for flexibility
    required: true,
  },
});

const prescriptions = ref([]); // Changed from 'documents' to 'prescriptions'
const loading = ref(false);
const error = ref(null);

const fetchPrescriptionsHistory = async () => {
  try {
    loading.value = true;
    error.value = null; // Clear previous errors
    // Assuming your API endpoint is still /api/patients/{patientId}/prescriptions
    const response = await axios.get(`/api/patients/${props.patientId}/prescriptions`);
    // Ensure response.data contains the array of prescriptions directly
    prescriptions.value = response.data; 
  } catch (err) {
    console.error('Error fetching prescription history:', err);
    error.value = err.response?.data?.message || 'Failed to load prescription history.';
    toaster.error(error.value);
  } finally {
    loading.value = false;
  }
};

const viewPrescription = (prescriptionId) => {
  // Logic to view the full prescription details in a new tab/page
  // This would typically navigate to a route like /prescriptions/{id} or open a modal.
  // For demonstration, let's open a dummy URL.
  const url = `/prescriptions/${prescriptionId}/view`; // Adjust this URL to your actual view route
  window.open(url, '_blank');
  toaster.info(`Viewing Prescription ID: ${prescriptionId}`);
};

const printPrescription = (prescriptionId) => {
  // Logic to print the prescription (e.g., open a PDF or a print-friendly HTML page)
  // This assumes you have a backend route that generates a printable version/PDF.
  const url = `/prescriptions/${prescriptionId}/print`; // Adjust this URL to your actual print route
  const printWindow = window.open(url, '_blank');
  // Optional: Add onload for print automation if it's an HTML page
  printWindow.onload = () => {
    printWindow.print();
  };
  toaster.info(`Printing Prescription ID: ${prescriptionId}`);
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  try {
    const date = new Date(dateString);
    if (isNaN(date.getTime())) { // Check for invalid date
      return dateString; // Return original string if invalid
    }
    return date.toLocaleDateString(); // Formats as per user's locale (e.g., 28/05/2025)
  } catch (e) {
    return dateString; // Fallback in case of parsing error
  }
};


onMounted(() => {
  if (props.patientId) {
    fetchPrescriptionsHistory();
  }
});

watch(() => props.patientId, (newId) => {
  if (newId) {
    fetchPrescriptionsHistory();
  }
});
</script>

<template>
  <div class="premium-document-history-section">
    <div class="premium-section-subtitle">Patient Prescription History</div>

    <div v-if="loading" class="premium-loading-state">
      <i class="fas fa-spinner fa-spin fa-2x text-muted mb-3"></i>
      <p>Loading prescriptions...</p>
    </div>

    <div v-else-if="error" class="premium-empty-state">
      <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
      <p>{{ error }}</p>
    </div>

    <div v-else-if="prescriptions.length === 0" class="premium-empty-state">
      <i class="fas fa-file-prescription fa-3x text-muted mb-3"></i>
      <p>No past prescriptions found for this patient.</p>
    </div>

    <div v-else class="premium-table-container">
      <table class="premium-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Date</th>
            <th>Physician</th>
            <th>Patient Name</th>
            <th>Medication Count</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="prescription in prescriptions" :key="prescription.id">
            <td>{{ prescription.id }}</td>
            <td>{{ formatDate(prescription.prescription_date) }}</td>
            <td>{{ prescription.physician_info }}</td>
            <td>{{ prescription.patient_name }}</td>
            <td>{{ prescription.medications ? prescription.medications.length : 0 }}</td>
            <td>{{ prescription.signature_status }}</td>
            <td>
              <button @click="viewPrescription(prescription.id)" class="premium-icon-btn view-btn" title="View Details">
                <i class="fas fa-eye"></i>
              </button>
              <button @click="printPrescription(prescription.id)" class="premium-icon-btn print-btn" title="Print">
                <i class="fas fa-print"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
/*
   Copied and adapted styles from your premium document history component
   Ensure these styles are either in this component's <style scoped> block
   or are available globally if they are part of your core premium CSS.
*/
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

.view-btn { /* Renamed from .download-btn */
  color: #3a5bb1;
}

.print-btn {
  color: #28a745;
  margin-left: 0.5rem;
}
</style>