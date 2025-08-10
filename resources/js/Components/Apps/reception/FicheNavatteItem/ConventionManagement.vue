<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'

// PrimeVue Components
import Dialog from 'primevue/dialog'
import Stepper from 'primevue/stepper'
import StepperPanel from 'primevue/stepperpanel'
import Card from 'primevue/card'
import MultiSelect from 'primevue/multiselect'
import Dropdown from 'primevue/dropdown'
import Button from 'primevue/button'
import Calendar from 'primevue/calendar'
import FileUpload from 'primevue/fileupload'
import Badge from 'primevue/badge'
import Tag from 'primevue/tag'
import Divider from 'primevue/divider'
import Message from 'primevue/message'

// Import PatientSearch component
import PatientSearch from '../../../../Pages/Appointments/PatientSearch.vue'

// Services
import { ficheNavetteService } from '../../../../Components/Apps/services/Reception/ficheNavetteService.js'

// Props & Emits
const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  ficheNavetteId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits([
  'update:visible',
  'convention-items-added'
])

// Composables
const toast = useToast()

// State
const activeStep = ref(0)
const loading = ref(false)
const creating = ref(false)

// Combined Step 1 state (Date + Family Auth + Patient)
const priseEnChargeDate = ref(null)
const familyAuthOptions = ref([])
const selectedFamilyAuth = ref(null)
const familyAuthLoading = ref(false)
const adherentPatientSearch = ref('')
const selectedAdherentPatient = ref(null)

// Convention selection (Step 2)
const organismes = ref([])
const conventions = ref([])
const filteredConventions = ref([])
const conventionPrestations = ref([])

// New: Specialization and Doctor selection (after convention selection)
const specializations = ref([])
const allDoctors = ref([])
const selectedSpecialization = ref(null)
const selectedDoctor = ref(null)
const specializationLoading = ref(false)
const doctorLoading = ref(false)

// Workflow state
const currentOrganisme = ref(null)
const currentConvention = ref(null)
const currentPrestations = ref([])
const completedConventions = ref([])

// File upload
const uploadedFiles = ref([])

// Computed properties
const visibleModal = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
})

const requiresAdherentPatient = computed(() => {
  if (!selectedFamilyAuth.value) {
    return false
  }
  const hasNonAdherent = selectedFamilyAuth.value !== 'adherent'
  return hasNonAdherent
})

const canProceedToConventions = computed(() => {
  const dateOk = priseEnChargeDate.value
  const familyAuthOk = selectedFamilyAuth.value !== null
  const patientOk = !requiresAdherentPatient.value || selectedAdherentPatient.value

  return dateOk && familyAuthOk && patientOk
})

const filteredDoctors = computed(() => {
  if (!selectedSpecialization.value || !allDoctors.value.length) {
    return []
  }
  return allDoctors.value.filter(doctor =>
    doctor.specialization_id === selectedSpecialization.value
  )
})

// Check for existing convention (check if any selected convention already exists)
const existingConventionIndex = computed(() => {
  if (!currentConvention.value) {
    return -1
  }

  // Check if the current convention is already in the completed list
  return completedConventions.value.findIndex(conv => conv.convention.id === currentConvention.value);
})

const isAddingToExisting = computed(() => {
  return existingConventionIndex.value !== -1
})

// Get already selected prestation IDs for the current convention to prevent duplicates in the UI
const alreadySelectedPrestationIds = computed(() => {
  if (existingConventionIndex.value === -1) {
    return [];
  }
  return completedConventions.value[existingConventionIndex.value].prestations.map(p => p.prestation_id);
})

// Filter out prestations that are already selected for the current convention
const filteredConventionPrestations = computed(() => {
  if (!conventionPrestations.value.length || !selectedSpecialization.value) {
    return [];
  }

  const selectedPrestationIds = new Set(alreadySelectedPrestationIds.value);
  
  return conventionPrestations.value.filter(prestation => {
    const prestationId = prestation.prestation_id || prestation.id;
    const isForSpecialization = prestation.specialization_id === selectedSpecialization.value ||
                                  prestation.service_specialization_id === selectedSpecialization.value;
    const isAlreadySelected = selectedPrestationIds.has(prestationId);

    return isForSpecialization && !isAlreadySelected;
  });
})

const canAddConvention = computed(() => {
  return currentOrganisme.value &&
           currentConvention.value &&
           currentPrestations.value.length > 0 &&
           selectedSpecialization.value &&
           selectedDoctor.value
})

const canCreatePrescription = computed(() => {
  return completedConventions.value.length > 0
})

// Methods
const formatDateForApi = (date) => {
  if (!date) return null
  return new Date(date).toISOString().split('T')[0]
}

const fetchFamilyAuthorization = async () => {
  if (!priseEnChargeDate.value) {
    familyAuthOptions.value = []
    selectedFamilyAuth.value = null
    return
  }

  try {
    familyAuthLoading.value = true
    const formattedDate = formatDateForApi(priseEnChargeDate.value)
    const response = await ficheNavetteService.getFamilyAuthorization(formattedDate)
    if (response.success) {
      familyAuthOptions.value = response.data || []
    }
  } catch (error) {
    console.error('Error fetching family authorization:', error)
  } finally {
    familyAuthLoading.value = false
  }
}

const onDateChanged = async () => {
  selectedFamilyAuth.value = null
  selectedAdherentPatient.value = null
  await fetchFamilyAuthorization()
}

const onFamilyAuthChanged = () => {
  if (!requiresAdherentPatient.value) {
    selectedAdherentPatient.value = null
  }
}

const onAdherentPatientSelected = (patient) => {
  selectedAdherentPatient.value = patient
}

const loadOrganismes = async () => {
  try {
    const result = await ficheNavetteService.getCompanies()
    if (result.success) {
      organismes.value = result.data || []
    }
  } catch (error) {
    console.error('Error loading organismes:', error)
  }
}

const loadConventions = async () => {
  try {
    const result = await ficheNavetteService.getConventions()
    if (result.success) {
      conventions.value = Array.isArray(result.data) ? result.data : []
    }
  } catch (error) {
    console.error('Error loading conventions:', error)
  }
}

const loadSpecializations = async () => {
  try {
    specializationLoading.value = true
    const result = await ficheNavetteService.getAllSpecializations()
    if (result.success) {
      specializations.value = result.data || []
    }
  } catch (error) {
    console.error('Error loading specializations:', error)
  } finally {
    specializationLoading.value = false
  }
}

const loadAllDoctors = async () => {
  try {
    doctorLoading.value = true
    const result = await ficheNavetteService.getAllDoctors()
    if (result.success) {
      allDoctors.value = result.data || []
    }
  } catch (error) {
    console.error('Error loading doctors:', error)
  } finally {
    doctorLoading.value = false
  }
}

const onOrganismeChange = async () => {
  currentConvention.value = null
  currentPrestations.value = []
  conventionPrestations.value = []
  selectedSpecialization.value = null
  selectedDoctor.value = null

  if (!currentOrganisme.value) {
    filteredConventions.value = []
    return
  }

  try {
    const result = await ficheNavetteService.getConventionsByOrganismes([currentOrganisme.value])
    if (result.success) {
      filteredConventions.value = Array.isArray(result.data) ? result.data : []
    }
  } catch (error) {
    console.error('Error filtering conventions:', error)
    filteredConventions.value = []
  }
}

const onConventionChange = async () => {
  currentPrestations.value = []
  selectedSpecialization.value = null
  selectedDoctor.value = null

  if (!currentConvention.value) {
    conventionPrestations.value = []
    return
  }

  if (!priseEnChargeDate.value) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Please select Prise en Charge Date first',
      life: 3000
    })
    return
  }

  try {
    loading.value = true
    
    const conventionIds = [currentConvention.value]
    
    const result = await ficheNavetteService.getPrestationsWithConventionPricing(
      conventionIds,
      formatDateForApi(priseEnChargeDate.value)
    )

    if (result.success && result.data) {
      conventionPrestations.value = Array.isArray(result.data) ? result.data : []
    } else {
      conventionPrestations.value = []
    }
  } catch (error) {
    console.error('Error loading prestations:', error)
    conventionPrestations.value = []
  } finally {
    loading.value = false
  }
}

const onSpecializationChange = () => {
  selectedDoctor.value = null
  currentPrestations.value = []
}


// --- MODIFIED addConvention METHOD ---
const addConvention = () => {
  if (!canAddConvention.value) return

  const selectedSpecializationObj = specializations.value.find(s => s.id === selectedSpecialization.value)
  const selectedDoctorObj = allDoctors.value.find(d => d.id === selectedDoctor.value)
  const selectedConventionObj = filteredConventions.value.find(c => c.id === currentConvention.value)
  const selectedOrganismeObj = organismes.value.find(o => o.id === currentOrganisme.value)

  const existingIndex = existingConventionIndex.value;
  
  if (existingIndex !== -1) {
    const existingConvention = completedConventions.value[existingIndex];
    const existingPrestationIds = new Set(existingConvention.prestations.map(p => p.prestation_id));
    
    const newUniquePrestations = currentPrestations.value.filter(
      p => !existingPrestationIds.has(p.prestation_id)
    );

    if (newUniquePrestations.length > 0) {
      existingConvention.prestations.push(...newUniquePrestations);
      existingConvention.totalPrestations = existingConvention.prestations.length;
      toast.add({
        severity: 'success',
        summary: 'Prestations Added',
        detail: `Added ${newUniquePrestations.length} new prestation(s) to existing convention.`,
        life: 3000
      });
    } else {
      toast.add({
        severity: 'info',
        summary: 'No new prestations added',
        detail: 'All selected prestations are already in this convention.',
        life: 3000
      });
    }
  } else {
    const newConventionGroup = {
      id: Date.now(),
      priseEnChargeDate: priseEnChargeDate.value,
      selectedFamilyAuth: selectedFamilyAuth.value,
      selectedAdherentPatient: selectedAdherentPatient.value,
      organisme: selectedOrganismeObj,
      convention: selectedConventionObj,
      prestations: [...currentPrestations.value],
      specialization: selectedSpecializationObj,
      doctor: selectedDoctorObj,
      addedAt: new Date(),
      totalPrestations: currentPrestations.value.length
    }
    completedConventions.value.push(newConventionGroup)
    toast.add({
      severity: 'success',
      summary: 'Convention Added',
      detail: `Added new convention with ${newConventionGroup.totalPrestations} prestations.`,
      life: 3000
    })
  }

  // Reset fields for Step 2 only
  resetStep2Fields();
}

const resetStep2Fields = () => {
  currentOrganisme.value = null
  currentConvention.value = null
  currentPrestations.value = []
  conventionPrestations.value = []
  filteredConventions.value = []
  selectedSpecialization.value = null
  selectedDoctor.value = null
}

const removePrestationFromConvention = (conventionId, prestationId) => {
  const conventionIndex = completedConventions.value.findIndex(conv => conv.id === conventionId);
  if (conventionIndex === -1) return;

  const convention = completedConventions.value[conventionIndex];
  const prestationIndex = convention.prestations.findIndex(p => p.prestation_id === prestationId);

  if (prestationIndex !== -1) {
    convention.prestations.splice(prestationIndex, 1);
    convention.totalPrestations = convention.prestations.length;

    if (convention.prestations.length === 0) {
      completedConventions.value.splice(conventionIndex, 1);
      toast.add({
        severity: 'info',
        summary: 'Convention Removed',
        detail: 'Convention removed as all prestations were deleted',
        life: 2000
      });
    } else {
      toast.add({
        severity: 'info',
        summary: 'Prestation Removed',
        detail: 'Prestation has been removed from convention',
        life: 2000
      });
    }
  }
}

const removeConvention = (conventionId) => {
  const index = completedConventions.value.findIndex(conv => conv.id === conventionId)
  if (index !== -1) {
    completedConventions.value.splice(index, 1)
  }
}

const nextStep = () => {
  if (activeStep.value < 2) {
    activeStep.value++
  }
}

const prevStep = () => {
  if (activeStep.value > 0) {
    activeStep.value--
  }
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR')
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}

const onFileSelect = (event) => {
  const files = event.files
  files.forEach(file => {
    uploadedFiles.value.push({
      id: Date.now() + Math.random(),
      name: file.name,
      size: file.size,
      file: file
    })
  })
}

const removeFile = (fileId) => {
  const index = uploadedFiles.value.findIndex(f => f.id === fileId)
  if (index !== -1) {
    uploadedFiles.value.splice(index, 1)
  }
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}
const createConventionPrescription = async () => {
  if (!canCreatePrescription.value) return

  creating.value = true

  try {
    // Get the first convention to extract common data
    const firstConvention = completedConventions.value[0]
    
    const conventionData = {
      // Move these to top level as required by validation
      prise_en_charge_date: formatDateForApi(firstConvention.priseEnChargeDate),
      familyAuth: firstConvention.selectedFamilyAuth,
      adherentPatient: firstConvention.selectedAdherentPatient ? {
        id: firstConvention.selectedAdherentPatient.id
      } : null,
      
      // Convention data without the duplicated fields
      conventions: completedConventions.value.map(conv => ({
        convention_id: conv.convention.id,
        specialization_id: conv.specialization.id,
        doctor_id: conv.doctor.id,
        prestations: conv.prestations.map(prest => ({
          prestation_id: prest.prestation_id || prest.id,
          convention_price: prest.convention_price,
          doctor_id: conv.doctor.id,
          specialization_id: conv.specialization.id
        }))
      })),
      
      uploadedFiles: uploadedFiles.value
    }

    console.log('Sending convention data:', JSON.stringify(conventionData, null, 2))

    const result = await ficheNavetteService.createConventionPrescription(
      props.ficheNavetteId,
      conventionData
    )

    if (result.success) {
      toast.add({
        severity: 'success',
        summary: 'Convention Prescription Created',
        detail: `Created ${result.data.items_created} prescription items with total amount: ${formatCurrency(result.data.total_amount)}`,
        life: 4000
      })

      emit('convention-items-added', result.data)
      resetAll()
      visibleModal.value = false
    } else {
      throw new Error(result.message)
    }
  } catch (error) {
    console.error('Error creating convention prescription:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'Failed to create convention prescription',
      life: 5000
    })
  } finally {
    creating.value = false
  }
}

const resetAll = () => {
  resetStep2Fields()
  priseEnChargeDate.value = null
  selectedFamilyAuth.value = null
  selectedAdherentPatient.value = null
  adherentPatientSearch.value = ''
  familyAuthOptions.value = []
  completedConventions.value = []
  uploadedFiles.value = []
  activeStep.value = 0
}

watch(priseEnChargeDate, onDateChanged)
watch(selectedFamilyAuth, onFamilyAuthChanged)

onMounted(async () => {
  try {
    await Promise.all([
      loadOrganismes(),
      loadConventions(),
      loadSpecializations(),
      loadAllDoctors()
    ])
    await fetchFamilyAuthorization();
  } catch (error) {
    console.error('Error loading initial data:', error)
  }
})

const getConventionInfo = () => {
  if (!currentConvention.value) {
    return null
  }

  const convention = filteredConventions.value.find(c => c.id === currentConvention.value)
  const specialization = specializations.value.find(s => s.id === selectedSpecialization.value)
  const doctor = allDoctors.value.find(d => d.id === selectedDoctor.value)

  return {
    convention: convention?.contract_name,
    specialization: specialization?.name,
    doctor: doctor?.name
  }
}
</script>

<template>
  <Dialog
    v-model:visible="visibleModal"
    modal
    header="Convention Prescription"
    :style="{ width: '90vw', maxWidth: '1400px' }"
    :maximizable="true"
    :closable="true"
    class="convention-modal"
    @hide="resetAll"
  >
    <div class="modal-content">
      <Stepper v-model:activeStep="activeStep" class="custom-stepper">
        <StepperPanel header="Setup & Authorization">
          <template #content>
            <div class="step-content">
              <Card class="step-card">
                <template #header>
                  <div class="step-header">
                    <i class="pi pi-cog text-primary"></i>
                    <h4>Step 1: Setup Date, Authorization & Patient</h4>
                  </div>
                </template>

                <template #content>
                  <div class="combined-setup">
                    <p class="section-description">
                      Choose the date, select the family authorization type, and if needed, the adherent patient.
                    </p>

                    <div class="setup-grid">
                      <div class="setup-field">
                        <label>Prise en Charge Date *</label>
                        <Calendar
                          v-model="priseEnChargeDate"
                          dateFormat="dd/mm/yy"
                          :showIcon="true"
                          placeholder="Select date"
                          class="date-input"
                        />
                      </div>

                      <div class="setup-field">
                        <label>Family Authorization *</label>
                        <Dropdown
                          v-model="selectedFamilyAuth"
                          :options="familyAuthOptions"
                          optionLabel="label"
                          optionValue="value"
                          placeholder="Select authorization"
                          :loading="familyAuthLoading"
                          :disabled="!priseEnChargeDate"
                          class="auth-multiselect"
                          :class="{ 'p-invalid': !selectedFamilyAuth && familyAuthOptions.length }"
                          showClear
                        >
                          <template #empty>
                            <span v-if="!priseEnChargeDate">Select a date first</span>
                            <span v-else-if="familyAuthLoading">Loading options...</span>
                            <span v-else>No authorization options available</span>
                          </template>
                        </Dropdown>
                        <small v-if="!priseEnChargeDate" class="field-help text-muted">
                          Select a date first to see available authorizations
                        </small>
                      </div>

                      <div v-if="requiresAdherentPatient" class="setup-field">
                        <label>Adherent Patient *</label>
                        <PatientSearch
                          v-model="adherentPatientSearch"
                          @patientSelected="onAdherentPatientSelected"
                          placeholder="Search and select adherent patient..."
                          class="patient-search"
                        />
                      </div>
                    </div>

                    <div class="step-progress">
                      <Divider />

                      <div class="progress-indicators">
                        <div class="progress-item" :class="{ 'completed': priseEnChargeDate }">
                          <i class="pi pi-calendar"></i>
                          <span>Date Selected</span>
                        </div>
                        <div class="progress-item" :class="{ 'completed': selectedFamilyAuth }">
                          <i class="pi pi-users"></i>
                          <span>Authorization Set</span>
                        </div>
                        <div
                          v-if="requiresAdherentPatient"
                          class="progress-item"
                          :class="{ 'completed': selectedAdherentPatient }"
                        >
                          <i class="pi pi-user"></i>
                          <span>Patient Selected</span>
                        </div>
                      </div>

                      <div class="step-navigation">
                        <Button
                          label="Proceed to Conventions"
                          icon="pi pi-arrow-right"
                          @click="nextStep"
                          :disabled="!canProceedToConventions"
                          class="proceed-btn"
                        />
                      </div>
                    </div>
                  </div>
                </template>
              </Card>
            </div>
          </template>
        </StepperPanel>

        <StepperPanel header="Add Conventions">
          <template #content>
            <div class="step-content">
              <Card class="step-card">
                <template #header>
                  <div class="step-header">
                    <i class="pi pi-building text-primary"></i>
                    <h4>Step 2: Select Conventions, Specialization & Doctor</h4>
                  </div>
                </template>

                <template #content>
                  <div class="convention-selection">
                    <div v-if="!canProceedToConventions" class="prerequisites-warning">
                      <Message severity="warn" :closable="false">
                        <i class="pi pi-exclamation-triangle"></i>
                        Please complete all setup requirements in Step 1 before selecting conventions
                      </Message>
                    </div>

                    <div v-else>
                      <p class="step-description">
                        Select conventions and prestations for <strong>{{ formatDate(priseEnChargeDate) }}</strong>.
                        You must select specialization and doctor to filter prestations properly.
                      </p>

                      <div class="selection-grid">
                        <div class="field-group">
                          <label>Companies</label>
                          <Dropdown
                            v-model="currentOrganisme"
                            :options="organismes"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select company..."
                            @change="onOrganismeChange"
                            class="field-input"
                            showClear
                            filter
                            filterPlaceholder="Search companies..."
                          />
                        </div>

                        <div class="field-group">
                          <label>Conventions</label>
                          <Dropdown
                            v-model="currentConvention"
                            :options="filteredConventions"
                            optionLabel="contract_name"
                            optionValue="id"
                            placeholder="Select convention..."
                            :disabled="!currentOrganisme"
                            @change="onConventionChange"
                            class="field-input"
                            showClear
                            filter
                            filterPlaceholder="Search conventions..."
                          />
                        </div>

                        <div class="field-group">
                          <label>Specialization *</label>
                          <Dropdown
                            v-model="selectedSpecialization"
                            :options="specializations"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select specialization"
                            :disabled="currentConvention === null"
                            @change="onSpecializationChange"
                            class="field-input"
                            :loading="specializationLoading"
                          />
                          <small v-if="currentConvention === null" class="field-help">
                            Select conventions first
                          </small>
                        </div>

                        <div class="field-group">
                          <label>Doctor *</label>
                          <Dropdown
                            v-model="selectedDoctor"
                            :options="filteredDoctors"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select doctor"
                            :disabled="!selectedSpecialization"
                            @change="onDoctorChange"
                            class="field-input"
                            :loading="doctorLoading"
                          >
                            <template #option="{ option }">
                              <div class="doctor-option">
                                <span class="doctor-name">{{ option.name }}</span>
                                <small class="doctor-specialization">{{ option.specialization_name }}</small>
                              </div>
                            </template>
                          </Dropdown>
                          <small v-if="!selectedSpecialization" class="field-help">
                            Select specialization first
                          </small>
                          <small v-else-if="filteredDoctors.length === 0" class="field-help text-orange-600">
                            No doctors found for selected specialization
                          </small>
                          <small v-else class="field-help text-green-600">
                            {{ filteredDoctors.length }} doctor(s) available
                          </small>
                        </div>

                        <div class="field-group full-width">
                          <label>Prestations ({{ filteredConventionPrestations.length }} available)</label>
                          <MultiSelect
                            v-model="currentPrestations"
                            :options="filteredConventionPrestations"
                            optionLabel="prestation_name"
                            dataKey="prestation_id"
                            placeholder="Select prestations..."
                            :disabled="!selectedDoctor"
                            :loading="loading"
                            class="field-input"
                            :filter="true"
                            filterPlaceholder="Search prestations..."
                            display="chip"
                            :showToggleAll="true"
                          >
                            <template #option="{ option }">
                              <div class="prestation-option">
                                <div class="prestation-info">
                                  <span class="prestation-name">{{ option.prestation_name || option.name }}</span>
                                  <small class="prestation-code">Code: {{ option.prestation_code || option.internal_code }}</small>
                                  <small class="prestation-service">Service: {{ option.service_name }}</small>
                                </div>
                                <div class="prestation-pricing">
                                  <span class="prestation-price">{{ formatCurrency(option.convention_price || option.price) }}</span>
                                  <small v-if="option.base_price && option.convention_price !== option.base_price" class="original-price">
                                    Original: {{ formatCurrency(option.base_price) }}
                                  </small>
                                </div>
                              </div>
                            </template>
                            
                            <template #value="{ value, placeholder }">
                              <template v-if="!value || value.length === 0">
                                <span class="multiselect-placeholder">{{ placeholder }}</span>
                              </template>
                              <template v-else>
                                <div class="multiselect-chips">
                                  <span v-for="prestation in value" :key="prestation.prestation_id" class="prestation-chip">
                                    {{ prestation.prestation_name || prestation.name }}
                                  </span>
                                </div>
                              </template>
                            </template>
                            
                            <template #empty>
                              <div class="empty-state">
                                <span v-if="!selectedDoctor">
                                  <i class="pi pi-info-circle"></i>
                                  Select doctor first to see prestations
                                </span>
                                <span v-else-if="loading">
                                  <i class="pi pi-spin pi-spinner"></i>
                                  Loading prestations...
                                </span>
                                <span v-else-if="!selectedSpecialization">
                                  <i class="pi pi-info-circle"></i>
                                  Select specialization first
                                </span>
                                <span v-else-if="conventionPrestations.length === 0">
                                  <i class="pi pi-exclamation-triangle"></i>
                                  No prestations available for this convention
                                </span>
                                <span v-else>
                                  <i class="pi pi-exclamation-triangle"></i>
                                  No prestations found for selected specialization
                                </span>
                              </div>
                            </template>
                          </MultiSelect>
                          
                          <div class="field-help-section">
                            <small v-if="!selectedDoctor" class="field-help">
                              <i class="pi pi-arrow-up"></i>
                              Select doctor to see filtered prestations
                            </small>
                            <small v-else-if="loading" class="field-help text-blue-600">
                              <i class="pi pi-spin pi-spinner"></i>
                              Loading prestations...
                            </small>
                            <small v-else-if="conventionPrestations.length === 0" class="field-help text-orange-600">
                              <i class="pi pi-exclamation-triangle"></i>
                              No prestations found for selected convention
                            </small>
                            <small v-else-if="filteredConventionPrestations.length === 0" class="field-help text-orange-600">
                              <i class="pi pi-exclamation-triangle"></i>
                              No prestations found for selected specialization "{{ specializations.find(s => s.id === selectedSpecialization)?.name }}"
                            </small>
                            <small v-else class="field-help text-green-600">
                              <i class="pi pi-check-circle"></i>
                              {{ filteredConventionPrestations.length }} prestations available for "{{ specializations.find(s => s.id === selectedSpecialization)?.name }}"
                            </small>
                          </div>
                        </div>

                        <div class="field-group add-button-group">
                          <Button
                            label="Add Convention"
                            icon="pi pi-plus"
                            @click="addConvention"
                            :disabled="!canAddConvention"
                            class="add-convention-btn"
                          />
                          <small v-if="!canAddConvention" class="field-help text-orange-600">
                            Complete all fields to add convention
                          </small>
                        </div>
                      </div>

                      <div v-if="completedConventions.length > 0" class="completed-conventions">
                        <h5>Added Conventions</h5>
                        <div class="convention-list">
                          <div
                            v-for="(convention, conventionIndex) in completedConventions"
                            :key="convention.id"
                            class="convention-item expanded"
                          >
                            <div class="convention-header">
                              <div class="convention-info">
                                <div class="convention-title">
                                  <strong>{{ convention.organisme.name }}</strong>
                                  <small class="convention-subtitle">{{ convention.convention.contract_name }}</small>
                                </div>
                                <div class="convention-details">
                                  <Tag :value="`${convention.totalPrestations} prestations`" severity="success" />
                                  <Tag :value="convention.specialization.name" severity="warning" />
                                  <Tag :value="`Dr. ${convention.doctor.name}`" severity="help" />
                                  <Tag :value="convention.selectedFamilyAuth" severity="info" />
                                  <Tag :value="formatDate(convention.priseEnChargeDate)" severity="secondary" />
                                </div>
                              </div>
                              <Button
                                icon="pi pi-times"
                                class="p-button-text p-button-danger p-button-sm"
                                @click="removeConvention(convention.id)"
                                title="Remove entire convention"
                              />
                            </div>

                            <div class="prestations-list">
                              <h6>Prestations:</h6>
                              <div class="prestations-grid">
                                <div
                                  v-for="prestation in convention.prestations"
                                  :key="prestation.prestation_id"
                                  class="prestation-item"
                                >
                                  <div class="prestation-info">
                                    <span class="prestation-name">{{ prestation.prestation_name || prestation.name }}</span>
                                    <small class="prestation-code">Code: {{ prestation.prestation_code || prestation.internal_code }}</small>
                                  </div>
                                  <div class="prestation-actions">
                                    <span class="prestation-price">{{ formatCurrency(prestation.convention_price || prestation.price) }}</span>
                                    <Button
                                      icon="pi pi-times"
                                      class="p-button-text p-button-danger p-button-sm"
                                      @click="removePrestationFromConvention(convention.id, prestation.prestation_id)"
                                      title="Remove this prestation"
                                    />
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <div class="step-navigation">
                        <Button
                          label="Back to Setup"
                          icon="pi pi-arrow-left"
                          @click="prevStep"
                          outlined
                        />
                        <Button
                          label="Done, Proceed to Files"
                          icon="pi pi-arrow-right"
                          @click="nextStep"
                          :disabled="completedConventions.length === 0"
                          class="proceed-btn"
                        />
                      </div>
                    </div>
                  </div>
                </template>
              </Card>
            </div>
          </template>
        </StepperPanel>

        <StepperPanel header="Files & Create">
          <template #content>
            <div class="step-content">
              <Card class="step-card">
                <template #header>
                  <div class="step-header">
                    <i class="pi pi-file text-primary"></i>
                    <h4>Step 3: Upload Files & Create Prescription</h4>
                  </div>
                </template>

                <template #content>
                  <div class="final-step">
                    <div class="file-upload-section">
                      <h5>Upload Documents (Optional)</h5>
                      <FileUpload
                        mode="basic"
                        :multiple="true"
                        accept=".pdf,.doc,.docx"
                        @select="onFileSelect"
                        chooseLabel="Choose Files"
                        class="file-upload"
                      />
                      
                      <div v-if="uploadedFiles.length > 0" class="uploaded-files">
                        <div
                          v-for="file in uploadedFiles"
                          :key="file.id"
                          class="uploaded-file-item"
                        >
                          <div class="file-info">
                            <i class="pi pi-file text-primary"></i>
                            <span class="file-name">{{ file.name }}</span>
                            <small class="file-size">({{ formatFileSize(file.size) }})</small>
                          </div>
                          <Button
                            icon="pi pi-times"
                            class="p-button-text p-button-sm p-button-danger"
                            @click="removeFile(file.id)"
                          />
                        </div>
                      </div>
                    </div>

                    <Divider />

                    <div class="prescription-summary">
                      <h5>Prescription Summary</h5>
                      <div class="summary-grid">
                        <div class="summary-section">
                          <h6>Summary of All Added Conventions</h6>
                          <div class="summary-item">
                            <strong>Total Conventions Added:</strong> {{ completedConventions.length }}
                          </div>
                          <div class="summary-item">
                            <strong>Total Prestations:</strong> {{ completedConventions.reduce((sum, conv) => sum + conv.totalPrestations, 0) }}
                          </div>
                          <div class="summary-item">
                            <strong>Files to Upload:</strong> {{ uploadedFiles.length }}
                          </div>
                        </div>

                        <div class="summary-section">
                          <h6>Details of Each Convention</h6>
                          <div class="summary-list">
                            <div v-for="conv in completedConventions" :key="conv.id" class="summary-sub-item">
                                <strong>{{ conv.organisme.name }} - {{ conv.convention.contract_name }}</strong>
                                <br/>
                                <small>Date: {{ formatDate(conv.priseEnChargeDate) }} | Doctor: {{ conv.doctor.name }} | Prestations: {{ conv.totalPrestations }}</small>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="final-actions">
                      <Button
                        label="Back to Conventions"
                        icon="pi pi-arrow-left"
                        @click="prevStep"
                        outlined
                      />

                      <Button
                        label="Create Convention Prescription"
                        icon="pi pi-save"
                        @click="createConventionPrescription"
                        :loading="creating"
                        :disabled="!canCreatePrescription"
                        class="create-prescription-btn"
                        severity="success"
                        size="large"
                      />
                    </div>
                  </div>
                </template>
              </Card>
            </div>
          </template>
        </StepperPanel>
      </Stepper>
    </div>
  </Dialog>
</template>


<style scoped>
/* Add to your existing styles */
.selection-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  align-items: end;
  margin: 1.5rem 0;
}

.field-group.full-width {
  grid-column: 1 / -1;
}

.field-group.add-button-group {
  grid-column: 1 / -1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  margin-top: 1rem;
}

.doctor-option {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.doctor-name {
  font-weight: 500;
  color: var(--text-color);
}

.doctor-specialization {
  color: var(--text-color-secondary);
  font-size: 0.75rem;
}

.prestation-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.prestation-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.prestation-name {
  font-weight: 500;
  color: var(--text-color);
}

.prestation-code {
  color: var(--text-color-secondary);
  font-size: 0.75rem;
}

.prestation-price {
  color: var(--green-600);
  font-weight: 600;
  font-size: 0.875rem;
  background: var(--green-100);
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.selection-status {
  margin: 1.5rem 0;
}

.status-content {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.status-item {
  color: var(--text-color);
}

.field-help {
  font-size: 0.75rem;
  margin-top: 0.25rem;
  color: var(--text-color-secondary);
}

.field-help.text-orange-600 {
  color: var(--orange-600);
}

.field-help.text-green-600 {
  color: var(--green-600);
}

.summary-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}

.summary-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.summary-sub-item {
    background: var(--surface-0);
    padding: 0.75rem;
    border-radius: 4px;
    border: 1px solid var(--surface-200);
}

.summary-section {
  background: var(--surface-100);
  padding: 1rem;
  border-radius: 6px;
}

.summary-section h6 {
  margin: 0 0 1rem 0;
  color: var(--primary-700);
  font-weight: 600;
}

.add-convention-btn {
  min-width: 200px;
}

/* Responsive */
@media (max-width: 1024px) {
  .selection-grid {
    grid-template-columns: 1fr;
  }
  
  .summary-grid {
    grid-template-columns: 1fr;
  }
}


.convention-modal {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.modal-content {
  padding: 1rem;
}

.custom-stepper {
  min-height: 600px;
}

.step-content {
  padding: 1rem 0;
}

.step-card {
  border: 1px solid var(--surface-200);
  border-radius: 8px;
}

.step-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  background: var(--surface-50);
  border-bottom: 1px solid var(--surface-200);
}

.step-header h4 {
  margin: 0;
  color: var(--text-color);
}

.step-description {
  color: var(--text-color-secondary);
  line-height: 1.6;
  margin-bottom: 1.5rem;
}

/* Combined Setup Styles */
.combined-setup {
  padding: 1.5rem;
}

.setup-grid {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 1.5rem;
  align-items: flex-end;
}

.setup-field {
  flex: 1;
  min-width: 550px;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.setup-field label {
  font-weight: 600;
  color: var(--text-color);
}

.section-description {
  color: var(--text-color-secondary);
  font-size: 0.9rem;
  margin-bottom: 1.5rem;
  line-height: 1.5;
}

.date-input,
.auth-multiselect,
.patient-search {
  width: 100%;
}

.field-help {
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

.text-muted {
  color: var(--text-color-secondary);
}

.text-orange-600 {
  color: var(--orange-600);
}

.text-green-600 {
  color: var(--green-600);
}

.auth-status,
.patient-status {
  margin-top: 1rem;
}

/* Step Progress */
.step-progress {
  margin-top: 2rem;
}

.progress-indicators {
  display: flex;
  justify-content: center;
  gap: 2rem;
  margin: 1.5rem 0;
  flex-wrap: wrap;
}

.progress-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem;
  border-radius: 8px;
  background: var(--surface-100);
  color: var(--text-color-secondary);
  min-width: 120px;
  transition: all 0.3s ease;
}

.progress-item.completed {
  background: var(--green-100);
  color: var(--green-700);
  border: 2px solid var(--green-200);
}

.progress-item i {
  font-size: 1.5rem;
}

.progress-item span {
  font-size: 0.875rem;
  font-weight: 500;
  text-align: center;
}

/* Convention Selection */
.convention-selection {
  padding: 1.5rem;
}

.prerequisites-warning {
  margin-bottom: 1.5rem;
}

.selection-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr auto;
  gap: 1rem;
  align-items: end;
  margin: 1.5rem 0;
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.field-group label {
  font-weight: 600;
  color: var(--text-color);
  font-size: 0.875rem;
}

.field-input {
  width: 100%;
}

.prestation-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.prestation-name {
  font-weight: 500;
}

.prestation-price {
  color: var(--green-600);
  font-weight: 600;
  font-size: 0.875rem;
}

.add-convention-btn {
  white-space: nowrap;
}

/* Completed Conventions */
.completed-conventions {
  margin-top: 2rem;
  padding: 1rem;
  background: var(--green-50);
  border: 1px solid var(--green-200);
  border-radius: 6px;
}

.completed-conventions h5 {
  margin: 0 0 1rem 0;
  color: var(--green-700);
}

.convention-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.convention-item {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: 1rem;
  background: var(--surface-0);
  border-radius: 6px;
  border: 1px solid var(--green-200);
}

.convention-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.convention-info {
  flex: 1;
}

.convention-title {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.convention-subtitle {
  color: var(--text-color-secondary);
  font-size: 0.875rem;
}

.convention-details {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.prestations-list {
  margin-top: 1rem;
  border-top: 1px solid var(--surface-200);
  padding-top: 1rem;
}

.prestations-list h6 {
  margin: 0 0 0.75rem 0;
}

.prestations-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.prestation-item {
  background-color: var(--surface-50);
  border: 1px solid var(--surface-200);
  padding: 0.5rem 0.75rem;
  border-radius: 4px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  flex: 1 1 auto;
}

.prestation-item .prestation-info {
  flex: 1;
}

.prestation-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Final Step */
.final-step {
  padding: 1.5rem;
}

.file-upload-section {
  margin-bottom: 2rem;
}

.file-upload-section h5 {
  margin: 0 0 1rem 0;
  color: var(--text-color);
}

.uploaded-files {
  margin-top: 1rem;
  max-height: 120px;
  overflow-y: auto;
}

.uploaded-file-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem;
  background: var(--surface-100);
  border-radius: 4px;
  margin-bottom: 0.5rem;
}

.file-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex: 1;
}

.file-name {
  font-weight: 500;
}

.file-size {
  color: var(--text-color-secondary);
  font-size: 0.75rem;
}

.prescription-summary {
  margin-bottom: 2rem;
}

.prescription-summary h5 {
  margin: 0 0 1rem 0;
  color: var(--text-color);
}

.summary-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 0.75rem;
}

.summary-item {
  padding: 0.75rem;
  background: var(--surface-100);
  border-radius: 4px;
}

.summary-item strong {
  color: var(--text-color);
  margin-right: 0.5rem;
}

/* Step Navigation */
.step-navigation,
.final-actions {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  margin-top: 2rem;
  align-items: center;
}

.proceed-btn,
.create-prescription-btn {
  min-width: 200px;
}

/* Responsive */
@media (max-width: 1024px) {
  .selection-grid {
    grid-template-columns: 1fr;
  }
  
  .summary-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .modal-content {
    padding: 0.5rem;
  }
  
  .step-content {
    padding: 0.5rem 0;
  }
  
  .step-header {
    padding: 0.75rem;
  }
  
  .combined-setup,
  .convention-selection,
  .final-step {
    padding: 1rem;
  }
  
  .progress-indicators {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .progress-item {
    min-width: auto;
    width: 100%;
  }
  
  .setup-grid {
    flex-direction: column;
    gap: 1rem;
  }
}

</style>