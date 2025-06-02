<script setup>
import { ref, onMounted, watch } from 'vue';
import { useToastr } from '../toster'; // Adjust path if necessary
import axios from 'axios';

const props = defineProps({
    consultationData: {
        type: Object,
        default: () => ({})
    },
    patientId: {
        type: [Number, String],
        required: true // Ensures patientId is always passed
    }
});

const emit = defineEmits(['update:consultation-data']);

const toaster = useToastr();
const loading = ref(false);
// This array will hold ALL Allergies (previous and newly added local ones)
const allAllergies = ref([]); 
const newAllergiesContent = ref(''); 
const saving = ref(false);

// Function to fetch all Allergies for the patient
const fetchAllAllergies = async () => {
    // Crucial check: only proceed if patientId is valid and not 'undefined'
    // if (!props.patientId || props.patientId === 'undefined') {
    //     console.warn('Patient ID is not available or is undefined, cannot fetch Allergies.');
    //     // Optionally, clear Allergies if patientId becomes invalid
    //     allAllergies.value = []; 
    //     return;
    // }

    try {
        loading.value = true;
        const response = await axios.get(`/api/patients/${1}/Allergies`);
        // Map fetched data to include required properties and set isNew to false
        allAllergies.value = response.data.map(d => ({
            id: d.id,
            content: d.content,
            date: d.created_at, // Use created_at from backend
            isNew: false // Mark as not new, since it's from the database
        }));
        console.log('Fetched Allergies:', allAllergies.value);
        
    } catch (error) {
        console.error('Error fetching Allergies:', error.response?.data || error);
        toaster.error(error.response?.data?.error || 'Failed to load Allergies.');
    } finally {
        loading.value = false;
    }
};

// Add new Allergies to the local list (not yet saved to DB)
const addAllergies = () => {
    if (!newAllergiesContent.value.trim()) return;
    
    allAllergies.value.unshift({ // Add to the beginning to show new items clearly
        id: null, // No ID yet, indicates a new record for the backend
        content: newAllergiesContent.value.trim(),
        date: new Date().toISOString(), // Local date for display
        isNew: true // Flag to indicate it's a new entry
    });
    
    newAllergiesContent.value = '';
};

// Remove Allergies from the local list
const removeAllergies = (index) => {
    allAllergies.value.splice(index, 1);
    // The actual deletion from DB happens on saveAllergies if the item had an ID
};

// Save all Allergies (create new, update existing, delete removed) to the server
const saveAllergies = async () => {
   
    // Filter out empty content if any slipped through, though validation should catch it
    const AllergiesToSend = allAllergies.value.filter(d => d.content.trim() !== '');

    try {
        saving.value = true;
        const response = await axios.post(`/api/patients/${1}/Allergies/bulk`, {
            // Only send id and content; backend handles user_id, created_at, updated_at
            Allergies: AllergiesToSend.map(d => ({
                id: d.id, // Will be null for new items, existing ID for others
                content: d.content,
            }))
        });
        
        toaster.success('Allergies updated successfully!');
        // After successful save, re-fetch to get the latest state from the backend.
        // This is crucial: new items will now have database IDs, and isNew will be false.
        await fetchAllAllergies();
        
        // Emit the updated consultation data if necessary, reflecting the current state
        emit('update:consultation-data', {
            ...props.consultationData,
            Allergies: allAllergies.value // Send the fully updated list
        });

    } catch (error) {
        console.error('Error updating Allergies:', error.response?.data || error);
        toaster.error(error.response?.data?.error || 'Failed to update Allergies. Check console for details.');
    } finally {
        saving.value = false;
    }
};
// Watch for changes in patientId to re-fetch Allergies.
// 'immediate: true' means this runs once on component mount.
watch(() => props.patientId, (newPatientId) => {
    if (newPatientId) { // Only fetch if newPatientId has a value
        fetchAllAllergies();
    }
}, { immediate: true }); 

</script>

<template>
    <div class="premium-Allergies-tab">
        <div class="premium-section">
            <h3 class="premium-section-title">Allergies</h3>
            <div v-if="loading" class="premium-loading">Loading Allergies...</div>
            <div v-else-if="allAllergies.length === 0 && !newAllergiesContent.trim()" class="premium-empty-state">
                No Allergies found for this patient. Add a new one below!
            </div>
            <div v-else class="premium-all-Allergies">
                <div v-for="(Allergies, index) in allAllergies" 
                     :key="Allergies.id || `new-${index}`" 
                     class="premium-Allergies-item">
                    <div class="premium-Allergies-content">
                        {{ Allergies.content }}
                    </div>
                    <div class="premium-Allergies-date">
                        {{ new Date(Allergies.date).toLocaleDateString() }}
                        <span v-if="Allergies.isNew" class="text-green-500 font-semibold ml-2">(New)</span>
                    </div>
                    <button 
                        @click="removeAllergies(index)"
                        class="premium-btn-remove"
                        title="Remove Allergies"
                    >
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="premium-section">
            <h3 class="premium-section-title">Add New Allergies</h3>
            <div class="premium-Allergies-form">
                <textarea
                    v-model="newAllergiesContent"
                    class="premium-textarea"
                    placeholder="Enter new Allergies..."
                    rows="4"
                ></textarea>
                <button 
                    @click="addAllergies"
                    class="premium-btn"
                    :disabled="!newAllergiesContent.trim()"
                >
                    Add Allergies
                </button>
            </div>
        </div>
        
        <div class="premium-save-section">
            <button 
                @click="saveAllergies"
                class="premium-btn premium-btn-save"
                :disabled="saving || allAllergies.length === 0"
            >
                <i class="fas fa-save mr-2"></i>
                {{ saving ? 'Saving...' : 'Save All Allergies' }}
            </button>
        </div>
    </div>
</template>

<style scoped>
/* Your existing styles are perfectly fine, just including them for completeness */
.premium-Allergies-tab {
    padding: 1.5rem;
    font-family: Arial, sans-serif; /* Added a default font */
}

.premium-section {
    margin-bottom: 2rem;
    background-color: #ffffff; /* Added a background */
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Added a subtle shadow */
    padding: 1.5rem;
}

.premium-section-title {
    font-size: 1.25rem;
    font-weight: 700; /* Made slightly bolder */
    color: #1a202c; /* Darker text color */
    margin-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0; /* Underline for titles */
    padding-bottom: 0.5rem;
}
.premium-all-Allergies {
    display: flex;
    flex-direction: row;
    height: 200;
    gap: 1rem; /* More space between items */
}
.premium-Allergies-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.premium-textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #cbd5e0; /* Softer border */
    border-radius: 8px;
    resize: vertical;
    font-size: 0.95rem; /* Slightly larger font */
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); /* Inner shadow for input feel */
    transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.premium-textarea:focus {
    border-color: #3a5bb1;
    box-shadow: 0 0 0 3px rgba(58, 91, 177, 0.2); /* Focus ring */
    outline: none;
}

.premium-btn {
    padding: 0.75rem 1.5rem;
    background-color: #3a5bb1;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.1s ease;
    align-self: flex-end; /* Aligns button to the right */
}

.premium-btn:hover {
    background-color: #2d4a9e;
    transform: translateY(-1px); /* Slight lift effect */
}

.premium-btn:disabled {
    background-color: #a0aec0; /* Lighter disabled color */
    cursor: not-allowed;
    transform: none;
}

.premium-Allergies-item {
    display: flex;
    height: 50px; /* Fixed height for uniformity */
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem; /* More padding */
    background-color: #f0f4f8; /* Slightly darker background for items */
    border-left: 5px solid #3a5bb1; /* Accent border */
    border-radius: 8px;
    margin-bottom: 0.75rem; /* More space between items */
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08); /* Item shadow */
}
.premium-Allergies-item:last-child {
    margin-bottom: 0; /* No margin after last item */
}


.premium-Allergies-content {
    flex: 1;
    font-size: 0.95rem;
    color: #2d3748;
}

.premium-Allergies-date {
    color: #64748b;
    font-size: 0.8rem; /* Slightly smaller date font */
    margin-left: 1.5rem; /* More space */
    white-space: nowrap; /* Prevent date from wrapping */
}

.premium-btn-remove {
    padding: 0.6rem; /* Larger click area */
    background: none;
    border: none;
    color: #ef4444;
    cursor: pointer;
    font-size: 1.1rem; /* Larger icon */
    transition: color 0.3s ease, transform 0.1s ease;
}

.premium-btn-remove:hover {
    color: #dc2626;
    transform: scale(1.1); /* Zoom effect on hover */
}

.premium-empty-state {
    text-align: center;

    color: #64748b;
    padding: 2.5rem; /* More padding */
    background-color: #f8fafc;
    border-radius: 8px;
    font-style: italic;
    border: 1px dashed #cbd5e0; /* Dashed border for empty state */
}

.premium-loading {
    text-align: center;
    color: #64748b;
    padding: 1rem;
    font-style: italic;
}

.premium-save-section {
    margin-top: 2rem; /* More space above save button */
    display: flex;
    justify-content: flex-end;
}

.premium-btn-save {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background-color: #10b981; /* Green save button */
    padding: 0.8rem 2rem; /* Larger button */
    font-size: 1rem; /* Larger font for save button */
}

.premium-btn-save:hover {
    background-color: #059669; /* Darker green on hover */
}

.premium-btn-save:disabled {
    background-color: #94a3b8;
    cursor: not-allowed;
}

.mr-2 {
    margin-right: 0.5rem;
}
</style>