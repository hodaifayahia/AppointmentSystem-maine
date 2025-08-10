<script setup>
// filepath: d:\Projects\AppointmentSystem\AppointmentSystem-main\resources\js\Components\Apps\reception\FicheNavatteItem\FicheNavetteItemCreate.vue

import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import Dropdown from 'primevue/dropdown'
import MultiSelect from 'primevue/multiselect'
import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import ToggleButton from 'primevue/togglebutton'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import Dialog from 'primevue/dialog'


// Components
import ConventionModal from './ConventionManagement.vue'

import { ficheNavetteService } from '../../../../Components/Apps/services/Reception/ficheNavetteService.js'

const emit = defineEmits(['cancel', 'created'])
const props = defineProps({
  patientId: {
    type: Number,
    required: true
  },
  ficheNavetteId: {
    type: Number,
    required: false
  }
})

const toast = useToast()

// Reactive data
const activeTab = ref(0)
const loading = ref(false)
const creating = ref(false)
const hasSelectedItems = ref(false)

// Convention Modal
const showConventionModal = ref(false)
const enableConventionMode = ref(false)

// Common data
const selectedSpecialization = ref(null)
const selectedDoctor = ref(null)
const specializations = ref([])
const doctors = ref([])
const allDoctors = ref([])

// Prestation tab data
const showPackages = ref(false)
const prestationSearch = ref('')
const availablePrestations = ref([])
const availablePackages = ref([])
const selectedPrestation = ref(null)
const selectedPackage = ref(null)
const dependencies = ref([])
const selectedDependencies = ref([])
const packagePrestations = ref([])

// Custom tab data
const allPrestations = ref([])
const searchTerm = ref('')
const selectedSpecializationsFilter = ref([])
const doctorsBySpecialization = ref([])
const customSelectedPrestations = ref([])

// Custom name options
const customNameOptions = ref([
  { label: 'Laboratory', value: 'Laboratory' },
  { label: 'Consultation', value: 'Consultation' },
  { label: 'Radiology', value: 'Radiology' },
  { label: 'Cardiology', value: 'Cardiology' },
  { label: 'Neurology', value: 'Neurology' },
  { label: 'Dermatology', value: 'Dermatology' },
  { label: 'Pharmacy', value: 'Pharmacy' },
  { label: 'Emergency', value: 'Emergency' },
  { label: 'Surgery', value: 'Surgery' },
  { label: 'Physiotherapy', value: 'Physiotherapy' },
  { label: 'Other', value: 'other' }
])
const selectedCustomNameOption = ref(null)
const customPrestationName = ref('')

// Computed properties
const filteredDoctors = computed(() => {
  if (selectedSpecialization.value) {
    return allDoctors.value.filter(doctor => doctor.specialization_id === selectedSpecialization.value)
  }
  return allDoctors.value
})

const nameToUse = computed(() => {
  if (selectedCustomNameOption.value === 'other') {
    return customPrestationName.value.trim() ? customPrestationName.value.trim() : null
  }
  return selectedCustomNameOption.value
})

const filteredPrestations = computed(() => {
  if (!availablePrestations.value.length) return []

  let filtered = availablePrestations.value

  if (prestationSearch.value) {
    const search = prestationSearch.value.toLowerCase()
    filtered = filtered.filter(p =>
      (p.name && p.name.toLowerCase().includes(search)) ||
      (p.internal_code && p.internal_code.toLowerCase().includes(search))
    )
  }

  if (selectedSpecialization.value) {
    filtered = filtered.filter(p => p.specialization_id === selectedSpecialization.value);
  }

  return filtered
})

const filteredCustomPrestations = computed(() => {
  if (!allPrestations.value || allPrestations.value.length === 0) return []
  
  let filtered = [...allPrestations.value]

  if (searchTerm.value) {
    const search = searchTerm.value.toLowerCase()
    filtered = filtered.filter(p => 
      (p.name && p.name.toLowerCase().includes(search)) ||
      (p.internal_code && p.internal_code.toLowerCase().includes(search))
    )
  }

  if (selectedSpecializationsFilter.value.length > 0) {
    filtered = filtered.filter(p => 
      p.specialization_id &&
      selectedSpecializationsFilter.value.includes(p.specialization_id)
    )
  }

  return filtered
})

const shouldShowCustomNameInput = computed(() => {
  if (customSelectedPrestations.value.length < 2) {
    return false;
  }
  const specializationsInSelection = new Set(customSelectedPrestations.value.map(p => p.specialization_id));
  return specializationsInSelection.size > 1;
})

const canSwitchTabs = computed(() => {
  return !hasSelectedItems.value
})

// Convention Mode Methods
const onConventionModeToggle = () => {
  if (enableConventionMode.value) {
    // Ensure we have a ficheNavetteId before showing the modal
    if (!props.ficheNavetteId) {
      toast.add({
        severity: 'warn',
        summary: 'Warning',
        detail: 'Please create a Fiche Navette first before using Convention Mode',
        life: 3000
      })
      enableConventionMode.value = false
      return
    }
    
    // Load convention companies when enabling convention mode
    loadConventionCompanies()
    showConventionModal.value = true
  } else {
    showConventionModal.value = false
  }
}

const onConventionItemsAdded = (data) => {
  enableConventionMode.value = false
  showConventionModal.value = false
  
  toast.add({
    severity: 'success',
    summary: 'Convention Items Added',
    detail: 'Convention prestations have been added successfully',
    life: 3000
  })
  
  emit('created', data)
}

const onConventionModalHide = () => {
  enableConventionMode.value = false
  showConventionModal.value = false
}

// Existing methods (keep all your existing methods)
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}

const onSpecializationChange = async () => {
  selectedDoctor.value = null
  selectedPrestation.value = null
  selectedPackage.value = null
  dependencies.value = []
  selectedDependencies.value = []
  packagePrestations.value = []
  updateHasSelectedItems()
}

watch(selectedDoctor, () => {
  selectedPrestation.value = null
  selectedPackage.value = null
  dependencies.value = []
  selectedDependencies.value = []
  packagePrestations.value = []
  updateHasSelectedItems()
});

const onPrestationSelect = async (event) => {
  const prestation = event.value
  selectedPrestation.value = prestation
  selectedPackage.value = null
  packagePrestations.value = []
  dependencies.value = []
  selectedDependencies.value = []

  if (prestation && prestation.required_prestations_info) {
    try {
      let dependencyIds = prestation.required_prestations_info;
      
      if (typeof dependencyIds === 'string') {
        try {
          dependencyIds = JSON.parse(dependencyIds);
        } catch (e) {
          console.error('Failed to parse required_prestations_info:', e);
          dependencyIds = [];
        }
      }
      
      if (Array.isArray(dependencyIds) && dependencyIds.length > 0) {
        const deps = await fetchPrestationsByIds(dependencyIds);
        dependencies.value = deps;
        selectedDependencies.value = [...deps];
      }
    } catch (error) {
      console.error('Error loading dependencies:', error);
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Failed to load dependencies',
        life: 3000
      });
    }
  }

  updateHasSelectedItems();
}

const onPackageSelect = async (event) => {
  const packageItem = event.value;
  selectedPackage.value = packageItem
  selectedPrestation.value = null
  dependencies.value = []
  selectedDependencies.value = []
  packagePrestations.value = []

  if (packageItem && packageItem.prestations && packageItem.prestations.length > 0) {
    try {
      const prestationIds = packageItem.prestations.map(p => p.id);
      const prestations = await fetchPrestationsByIds(prestationIds);
      packagePrestations.value = prestations;
    } catch (error) {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Failed to load package prestations',
        life: 3000
      })
    }
  }
  updateHasSelectedItems()
}

const updateHasSelectedItems = () => {
  if (activeTab.value === 0) {
    hasSelectedItems.value = selectedPrestation.value !== null || selectedPackage.value !== null
  } else {
    hasSelectedItems.value = customSelectedPrestations.value.length > 0
  }
}

const resetSelections = () => {
  selectedPrestation.value = null
  selectedPackage.value = null
  dependencies.value = []
  selectedDependencies.value = []
  packagePrestations.value = []
  customSelectedPrestations.value = []
  searchTerm.value = ''
  selectedSpecializationsFilter.value = []
  doctorsBySpecialization.value = []
  customPrestationName.value = ''
  updateHasSelectedItems()
}

const onTabChange = (event) => {
  if (!canSwitchTabs.value) {
    event.preventDefault()
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Please create the current selection or reset before switching tabs',
      life: 3000
    })
    return false
  }
  activeTab.value = event.index
  selectedSpecialization.value = null
  selectedDoctor.value = null
  resetSelections()
}

// Create regular fiche navette (existing method)
const createFicheNavette = async () => {
  creating.value = true
  
  try {
    const data = {
      patient_id: props.patientId,
      selectedDoctor: selectedDoctor.value,
      selectedSpecialization: selectedSpecialization.value,
      enableConventionMode: false, // Regular mode
      type: activeTab.value === 0 ? 'prestation' : 'custom'
    }

    if (activeTab.value === 0) {
      if (selectedPrestation.value) {
        data.prestations = [selectedPrestation.value]
        data.dependencies = selectedDependencies.value
      }
      if (selectedPackage.value) {
        data.packages = [selectedPackage.value]
      }
    } else {
      const nameValue = nameToUse.value
      data.customPrestations = customSelectedPrestations.value.map(p => ({
        ...p,
        display_name: nameValue || p.name,
        type: nameValue ? 'custom' : 'predefined',
        selected_doctor_id: p.selected_doctor_id
      }))
    }

    let result
    if (props.ficheNavetteId) {
      result = await ficheNavetteService.addItemsToFiche(props.ficheNavetteId, data)
    } else {
      result = await ficheNavetteService.createFicheNavette(data)
    }
    
    if (result.success) {
      toast.add({ 
        severity: 'success', 
        summary: 'Success', 
        detail: props.ficheNavetteId ? 'Items added successfully' : 'Fiche Navette created successfully', 
        life: 3000 
      })
      resetSelections()
      emit('created', result.data)
    } else {
      throw new Error(result.message)
    }
  } catch (error) {
    console.error('Error creating/adding to fiche navette:', error)
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: error.response?.data?.message || error.message || (props.ficheNavetteId ? 'Failed to add items' : 'Failed to create Fiche Navette'), 
      life: 5000 
    })
  } finally {
    creating.value = false
  }
}

// API Functions (keep existing)
const fetchSpecializations = async () => {
  try {
    const result = await ficheNavetteService.getAllSpecializations()
    if (result.success) {
      specializations.value = result.data || []
    }
  } catch (error) {
    console.error('Error fetching specializations:', error)
  }
}

const fetchAllDoctors = async () => {
  try {
    const result = await ficheNavetteService.getAllDoctors()
    if (result.success) {
      allDoctors.value = result.data || []
      doctors.value = result.data || []
    }
  } catch (error) {
    console.error('Error fetching all doctors:', error)
  }
}

const fetchPrestationsByIds = async (ids) => {
  try {
    const result = await ficheNavetteService.getPrestationsDependencies(ids)
    if (result.success) {
      return result.data || []
    }
    return []
  } catch (error) {
    console.error('Error fetching prestations by IDs:', error)
    return []
  }
}

const fetchAllPrestations = async () => {
  try {
    const prestationResult = await ficheNavetteService.getAllPrestations();
    const packageResult = await ficheNavetteService.getAllPackages();

    if (prestationResult.success) {
      availablePrestations.value = prestationResult.data || [];
      allPrestations.value = prestationResult.data.map(p => ({
        ...p,
        custom_type: 'predefined',
        custom_name: '',
        selected_doctor_id: null,
      })) || [];
    }
    if (packageResult.success) {
      availablePackages.value = packageResult.data || [];
    }
  } catch (error) {
    console.error('Error fetching all prestations and packages:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load prestations and packages',
      life: 3000
    });
  }
}

const onSpecializationFilterChange = async () => {
  doctorsBySpecialization.value = []
  
  if (selectedSpecializationsFilter.value.length > 0) {
    try {
      loading.value = true
      
      const doctorPromises = selectedSpecializationsFilter.value.map(specId => 
        ficheNavetteService.getDoctorsBySpecialization(specId)
      )
      
      const results = await Promise.all(doctorPromises)
      const allDoctors = results.flatMap(result => result.success ? result.data : [])
      
      const uniqueDoctors = allDoctors.filter((doctor, index, self) =>
        index === self.findIndex(d => d.id === doctor.id)
      )
      
      doctorsBySpecialization.value = uniqueDoctors
    } catch (error) {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Failed to load doctors for selected specializations',
        life: 3000
      })
    } finally {
      loading.value = false
    }
  }
}

const getDoctorsForPrestation = (prestation) => {
  if (!allDoctors.value || allDoctors.value.length === 0) return []
  return allDoctors.value.filter(d => d.specialization_id === prestation.specialization_id)
}

// Add these helper methods
const getDoctorName = (doctorId) => {
  const doctor = allDoctors.value.find(d => d.id === doctorId)
  return doctor ? doctor.name : 'Not assigned'
}

const getSpecializationName = (specializationId) => {
  const spec = specializations.value.find(s => s.id === specializationId)
  return spec ? spec.name : 'Unknown'
}

// Watchers (keep existing)
watch(selectedDoctor, () => {
  selectedPrestation.value = null
  selectedPackage.value = null
  dependencies.value = []
  selectedDependencies.value = []
  packagePrestations.value = []
  updateHasSelectedItems()
});

watch(customSelectedPrestations, (newSelection) => {
  hasSelectedItems.value = newSelection.length > 0
}, { deep: true })

watch(selectedCustomNameOption, (val) => {
  if (val !== 'other') {
    customPrestationName.value = ''
  }
})

// Lifecycle
onMounted(async () => {
  loading.value = true
  try {
    await Promise.all([
      fetchSpecializations(),
      fetchAllDoctors(),
      fetchAllPrestations(),
      loadConventionCompanies() // Add this
    ])
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load data',
      life: 3000
    })
  } finally {
    loading.value = false
  }
})


// Add these reactive variables
const conventionOrganismes = ref([])
const loadingConventions = ref(false)
const showConventionDetailsModal = ref(false)
const showAllConventionsModal = ref(false)
const selectedConventionOrganisme = ref(null)

// Add this computed property
const totalConventions = computed(() => {
  return conventionOrganismes.value.reduce((total, organisme) => {
    return total + (organisme.conventions?.length || 0)
  }, 0)
})

// Add these methods
const loadConventionCompanies = async () => {
  if (!props.patientId) return
  
  try {
    loadingConventions.value = true
    const result = await ficheNavetteService.getPatientConventions(props.patientId , props.ficheNavetteId)
    
    if (result.success) {
      console.log('Loaded convention organismes:', result.data);
      conventionOrganismes.value = result.data || []
    }
  } catch (error) {
    console.error('Error loading convention organismes:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load convention organismes',
      life: 3000
    })
  } finally {
    loadingConventions.value = false
  }
}

const showOrganismeDetails = (organisme) => {
  selectedConventionOrganisme.value = organisme
  showConventionDetailsModal.value = true
  showAllConventionsModal.value = false
}

const showAllConventions = () => {
  showAllConventionsModal.value = true
}

const selectConvention = (convention) => {
  showConventionDetailsModal.value = false
  showAllConventionsModal.value = false
  
  // Set the convention mode and show the modal
  enableConventionMode.value = true
  showConventionModal.value = true
  
  toast.add({
    severity: 'info',
    summary: 'Convention Selected',
    detail: `Selected ${convention.contract_name} from ${selectedConventionCompany.value.company_name}`,
    life: 3000
  })
}

const getConventionStatusSeverity = (status) => {
  const statusMap = {
    'active': 'success',
    'pending': 'warning',
    'expired': 'danger',
    'suspended': 'secondary'
  }
  return statusMap[status] || 'info'
}

const formatDate = (date) => {
  if (!date) return 'No end date'
  return new Date(date).toLocaleDateString('fr-FR')
}
</script>

<template>
  <div class="add-items-container">
    <Card class="main-card">
      <template #content>
        <!-- Convention Mode Toggle -->
        <Card class="convention-toggle-card mb-4">
          <template #content>
            <div class="convention-toggle">
              <div class="toggle-info">
                <h5>Convention Mode</h5>
                <p>Enable to add prestations through conventions with special pricing</p>
                
                <!-- Add Convention Companies Display here -->
                <div v-if="conventionOrganismes.length > 0" class="convention-organismes-preview">
                  <small class="organismes-label">Available Convention Organismes:</small>
                  <div class="organismes-tags">
                    <Tag
                      v-for="organisme in conventionOrganismes.slice(0, 3)"
                      :key="organisme.id"
                      :value="organisme.organisme_name || organisme.company_name"
                      severity="info"
                      class="organisme-tag"
                      @click="showOrganismeDetails(organisme)"
                    >
                      <template #default>
                        <div class="tag-content">
                          <i class="pi pi-building"></i>
                          <span>{{ organisme.organisme_name || organisme.company_name }}</span>
                          <Badge v-if="organisme.conventions_count" :value="organisme.conventions_count" severity="secondary" />
                        </div>
                      </template>
                    </Tag>
                    <Tag
                      v-if="conventionOrganismes.length > 3"
                      :value="`+${conventionOrganismes.length - 3} more`"
                      severity="secondary"
                      class="more-organismes-tag"
                      @click="showAllConventions"
                    />
                  </div>
                </div>
                
                <!-- Show message if no conventions -->
                <div v-else-if="!loadingConventions && enableConventionMode" class="no-conventions-message">
                  <small class="text-muted">
                    <i class="pi pi-info-circle"></i>
                    No conventions available for this patient
                  </small>
                </div>
              </div>
              <ToggleButton
                v-model="enableConventionMode"
                onLabel="Convention Mode"
                offLabel="Regular Mode"
                onIcon="pi pi-building"
                offIcon="pi pi-list"
                @change="onConventionModeToggle"
                class="convention-mode-toggle"
              />
            </div>
          </template>
        </Card>

        <!-- Regular Prestation Tabs -->
        <TabView v-model:activeIndex="activeTab" @tab-change="onTabChange" class="custom-tabview">
          <!-- Prestation Tab (keep existing) -->
          <TabPanel header="Prestations" :disabled="!canSwitchTabs && activeTab !== 0">
            <div class="prestation-tab">
              <div class="steps-row">
                <div class="step-field">
                  <label>Specialization</label>
                  <Dropdown
                    v-model="selectedSpecialization"
                    :options="specializations"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select specialization"
                    @change="onSpecializationChange"
                    class="full-width"
                    :loading="loading"
                  />
                </div>
                
                <div class="step-field">
                  <label>Doctor</label>
                  <Dropdown
                    v-model="selectedDoctor"
                    :options="filteredDoctors"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select doctor"
                    :disabled="!selectedSpecialization"
                    class="full-width"
                    :loading="loading"
                  />
                </div>
                
                <div class="step-field checkbox-field">
                  <div class="package-toggle">
                    <Checkbox
                      v-model="showPackages"
                      inputId="showPackages"
                      :binary="true"
                      :disabled="!selectedSpecialization"
                    />
                    <label for="showPackages">Packages</label>
                  </div>
                </div>

                <div class="step-field prestation-field">
                  <label>{{ showPackages ? 'Package' : 'Prestation' }}</label>
                  
                  <Dropdown
                    v-if="!showPackages"
                    v-model="selectedPrestation"
                    :options="filteredPrestations"
                    optionLabel="name"
                    placeholder="Select prestation"
                    :disabled="!selectedSpecialization"
                    @change="onPrestationSelect"
                    class="full-width"
                    :filter="true"
                    filter-placeholder="Search prestations..."
                    :filter-fields="['name', 'internal_code']"
                  >
                    <template #option="{ option }">
                      <div class="dropdown-option">
                        <div class="option-main">
                          <span class="option-name">{{ option.name }}</span>
                          <span class="option-code">{{ option.internal_code }}</span>
                        </div>
                        <span class="option-price">{{ formatCurrency(option.public_price) }}</span>
                      </div>
                    </template>
                  </Dropdown>

                  <Dropdown
                    v-else
                    v-model="selectedPackage"
                    :options="availablePackages"
                    optionLabel="name"
                    placeholder="Select package"
                    :disabled="!selectedSpecialization"
                    @change="onPackageSelect"
                    class="full-width"
                    :filter="true"
                    filter-placeholder="Search packages..."
                    :filter-fields="['name', 'internal_code']"
                  >
                    <template #option="{ option }">
                      <div class="dropdown-option">
                        <div class="option-main">
                          <span class="option-name">{{ option.name }}</span>
                          <Tag value="Package" severity="success" size="small" />
                        </div>
                        <span class="option-price">{{ formatCurrency(option.price) }}</span>
                      </div>
                    </template>
                  </Dropdown>
                </div>
              </div>

              <!-- Dependencies section (keep existing) -->
              <div class="dependencies-section" v-if="selectedPrestation && dependencies.length > 0">
                <h4>
                  <i class="pi pi-link"></i>
                  Dependencies (Pre-selected - Uncheck what you don't want)
                </h4>
                <div class="dependencies-grid small-cards">
                  <div
                    v-for="dep in dependencies"
                    :key="dep.id"
                    class="dependency-card"
                  >
                    <Checkbox
                      v-model="selectedDependencies"
                      :inputId="`dep-${dep.id}`"
                      :value="dep"
                      class="small-checkbox"
                    />
                    <label :for="`dep-${dep.id}`" class="dependency-label">
                      <div class="dep-info">
                        <span class="dep-name">{{ dep.name }}</span>
                        <span class="dep-code">{{ dep.internal_code }}</span>
                      </div>
                      <span class="dep-price">{{ formatCurrency(dep.price) }}</span>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Package contents section (keep existing) -->
              <div class="dependencies-section" v-if="selectedPackage && packagePrestations.length > 0">
                <h4>
                  <i class="pi pi-box"></i>
                  Package Contents (Prestations)
                </h4>
                <div class="dependencies-grid small-cards">
                  <div
                    v-for="prestation in packagePrestations"
                    :key="prestation.id"
                    class="dependency-card"
                  >
                    <label class="dependency-label">
                      <div class="dep-info">
                        <span class="dep-name">{{ prestation.name }}</span>
                        <span class="dep-code">{{ prestation.internal_code }}</span>
                      </div>
                      <span class="dep-price">{{ formatCurrency(prestation.price) }}</span>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Selected summary (keep existing) -->
              <div class="selected-summary small-summary" v-if="hasSelectedItems">
                <h4>
                  <i class="pi pi-check-circle"></i>
                  Selected Items
                </h4>
                <div class="summary-items">
                  <div v-if="selectedPrestation" class="summary-card prestation-card">
                    <div class="summary-info">
                      <span class="summary-name">{{ selectedPrestation.name }}</span>
                      <Tag value="Prestation" severity="info" />
                    </div>
                    <span class="summary-price">{{ formatCurrency(selectedPrestation.price) }}</span>
                  </div>
                  
                  <div v-if="selectedPackage" class="summary-card package-card">
                    <div class="summary-info">
                      <span class="summary-name">{{ selectedPackage.name }}</span>
                      <Tag value="Package" severity="success" />
                    </div>
                    <span class="summary-price">{{ formatCurrency(selectedPackage.price) }}</span>
                  </div>
                  
                  <div v-for="dep in selectedDependencies" :key="dep.id" class="summary-card dependency-card">
                    <div class="summary-info">
                      <span class="summary-name">{{ dep.name }}</span>
                      <Tag value="Dependency" severity="warning" />
                    </div>
                    <span class="summary-price">{{ formatCurrency(dep.price) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </TabPanel>

          <!-- Custom Tab - Complete Implementation -->
          <TabPanel header="Custom" :disabled="!canSwitchTabs && activeTab !== 1">
            <div class="custom-tab">
              <!-- Filters Section -->
              <Card class="filters-card mb-4">
                <template #header>
                  <h4>
                    <i class="pi pi-filter"></i>
                    Filters
                  </h4>
                </template>
                <template #content>
                  <div class="filters-row">
                    <div class="filter-field">
                      <label>Search Prestations</label>
                      <InputText
                        v-model="searchTerm"
                        placeholder="Search by name or code..."
                        icon="pi pi-search"
                        class="full-width"
                      />
                    </div>
                    
                    <div class="filter-field">
                      <label>Filter by Specializations</label>
                      <MultiSelect
                        v-model="selectedSpecializationsFilter"
                        :options="specializations"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Select specializations"
                        @change="onSpecializationFilterChange"
                        class="full-width"
                        :loading="loading"
                      />
                    </div>
                  </div>
                </template>
              </Card>

              <!-- Custom Name Section -->
              <Card v-if="shouldShowCustomNameInput" class="custom-name-card mb-4">
                <template #header>
                  <h4>
                    <i class="pi pi-tag"></i>
                    Custom Grouping Name
                  </h4>
                </template>
                <template #content>
                  <div class="custom-name-section">
                    <div class="name-field">
                      <label>Choose a name for this group</label>
                      <Dropdown
                        v-model="selectedCustomNameOption"
                        :options="customNameOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Select a name"
                        class="full-width"
                      />
                    </div>
                    
                    <div v-if="selectedCustomNameOption === 'other'" class="name-field">
                      <label>Custom Name</label>
                      <InputText
                        v-model="customPrestationName"
                        placeholder="Enter custom name..."
                        class="full-width"
                      />
                    </div>
                  </div>
                </template>
              </Card>

              <!-- Prestations Selection Table -->
              <Card class="prestations-table-card">
                <template #header>
                  <div class="table-header">
                    <h4>
                      <i class="pi pi-list"></i>
                      Available Prestations ({{ filteredCustomPrestations.length }})
                    </h4>
                    <div class="table-actions">
                      <Button
                        icon="pi pi-times"
                        label="Clear Selection"
                        class="p-button-text p-button-secondary"
                        @click="customSelectedPrestations = []"
                        :disabled="customSelectedPrestations.length === 0"
                      />
                    </div>
                  </div>
                </template>
                <template #content>
                  <DataTable
                    :value="filteredCustomPrestations"
                    v-model:selection="customSelectedPrestations"
                    dataKey="id"
                    :loading="loading"
                    :paginator="true"
                    :rows="10"
                    :rowsPerPageOptions="[5, 10, 20, 50]"
                    responsiveLayout="scroll"
                    selectionMode="multiple"
                    class="custom-datatable"
                  >
                    <template #empty>
                      <div class="empty-message">
                        <i class="pi pi-search"></i>
                        <p>No prestations found with current filters</p>
                      </div>
                    </template>

                    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
                    
                    <Column field="name" header="Prestation" sortable style="min-width: 200px">
                      <template #body="{ data }">
                        <div class="prestation-name">
                          <strong>{{ data.name }}</strong>
                          <small class="prestation-code">{{ data.internal_code }}</small>
                        </div>
                      </template>
                    </Column>
                    
                    <Column field="specialization" header="Specialization" sortable>
                      <template #body="{ data }">
                        <Tag
                          v-if="data.specialization"
                          :value="data.specialization.name"
                          severity="info"
                        />
                        <span v-else>-</span>
                      </template>
                    </Column>
                    
                    <Column field="public_price" header="Price" sortable>
                      <template #body="{ data }">
                        <span class="price-tag">{{ formatCurrency(data.public_price) }}</span>
                      </template>
                    </Column>
                    
                    <Column field="doctor" header="Assign Doctor" style="min-width: 200px">
                      <template #body="{ data }">
                        <Dropdown
                          v-model="data.selected_doctor_id"
                          :options="getDoctorsForPrestation(data)"
                          optionLabel="name"
                          optionValue="id"
                          placeholder="Select doctor"
                          class="doctor-dropdown"
                          :disabled="!customSelectedPrestations.includes(data)"
                        />
                      </template>
                    </Column>
                  </DataTable>
                </template>
              </Card>

              <!-- Selected Summary -->
              <Card v-if="customSelectedPrestations.length > 0" class="selected-summary-card mt-4">
                <template #header>
                  <h4>
                    <i class="pi pi-check-circle"></i>
                    Selected Prestations ({{ customSelectedPrestations.length }})
                  </h4>
                </template>
                <template #content>
                  <div class="selected-summary">
                    <div class="summary-items">
                      <div
                        v-for="prestation in customSelectedPrestations"
                        :key="prestation.id"
                        class="summary-card"
                      >
                        <div class="summary-info">
                          <span class="summary-name">{{ prestation.name }}</span>
                          <small class="summary-code">{{ prestation.internal_code }}</small>
                          <Tag
                            v-if="prestation.specialization"
                            :value="prestation.specialization.name"
                            severity="info"
                            size="small"
                          />
                        </div>
                        <div class="summary-details">
                          <span class="summary-price">{{ formatCurrency(prestation.public_price) }}</span>
                          <small v-if="prestation.selected_doctor_id" class="assigned-doctor">
                            Assigned to: {{ getDoctorName(prestation.selected_doctor_id) }}
                          </small>
                        </div>
                      </div>
                    </div>
                    
                    <div class="summary-total">
                      <div class="total-calculation">
                        <span class="total-label">Total Amount:</span>
                        <span class="total-amount">
                          {{ formatCurrency(customSelectedPrestations.reduce((sum, p) => sum + parseFloat(p.public_price || 0), 0)) }}
                        </span>
                      </div>
                      <div class="total-items">
                        <span>{{ customSelectedPrestations.length }} item{{ customSelectedPrestations.length > 1 ? 's' : '' }} selected</span>
                      </div>
                    </div>
                  </div>
                </template>
              </Card>

              <!-- Doctors by Specialization (if filtered) -->
              <Card v-if="doctorsBySpecialization.length > 0" class="doctors-info-card mt-4">
                <template #header>
                  <h4>
                    <i class="pi pi-users"></i>
                    Available Doctors
                  </h4>
                </template>
                <template #content>
                  <div class="doctors-grid">
                    <div
                      v-for="doctor in doctorsBySpecialization"
                      :key="doctor.id"
                      class="doctor-card"
                    >
                      <Avatar icon="pi pi-user" class="doctor-avatar" />
                      <div class="doctor-info">
                        <span class="doctor-name">{{ doctor.name }}</span>
                        <small class="doctor-specialization">{{ getSpecializationName(doctor.specialization_id) }}</small>
                      </div>
                    </div>
                  </div>
                </template>
              </Card>
            </div>
          </TabPanel>
        </TabView>

        <div class="action-buttons">
          <Button
            :label="props.ficheNavetteId ? 'Add Items' : 'Create Fiche Navette'"
            icon="pi pi-check"
            @click="createFicheNavette"
            :loading="creating"
            :disabled="!hasSelectedItems"
          />
        </div>
      </template>
    </Card>

    <!-- Convention Modal -->
    <ConventionModal
      v-model:visible="showConventionModal"
      :ficheNavetteId="props.ficheNavetteId"
      @convention-items-added="onConventionItemsAdded"
      @update:visible="onConventionModalHide"
    />

    <!-- Convention Details Modal -->
    <Dialog
      v-model:visible="showConventionDetailsModal"
      :header="selectedConventionOrganisme?.organisme_name || selectedConventionOrganisme?.company_name || 'Organisme Details'"
      modal
      class="convention-details-modal"
      :style="{ width: '70vw', maxWidth: '800px' }"
    >
      <div v-if="selectedConventionOrganisme" class="organisme-details-content">
        <div class="organisme-info">
          <h4>
            <i class="pi pi-building"></i>
            {{ selectedConventionOrganisme.organisme_name || selectedConventionOrganisme.company_name }}
          </h4>
          <p v-if="selectedConventionOrganisme.description" class="organisme-description">
            {{ selectedConventionOrganisme.description }}
          </p>
          <div v-if="selectedConventionOrganisme.industry" class="organisme-meta">
            <small><strong>Industry:</strong> {{ selectedConventionOrganisme.industry }}</small>
          </div>
          <div v-if="selectedConventionOrganisme.address" class="organisme-meta">
            <small><strong>Address:</strong> {{ selectedConventionOrganisme.address }}</small>
          </div>
        </div>

      <div class="prestations-list">
  <h5>Prestations Used in This Fiche Navette</h5>
  <div class="prestations-grid">
    <Card
      v-for="prestation in (selectedConventionOrganisme.conventions[0]?.prestations || [])"
      :key="prestation.id"
      class="prestation-card"
    >
      <template #content>
        <div class="prestation-info">
          <div class="prestation-header">
            <strong class="prestation-title">{{ prestation.name }}</strong>
            <Tag
              v-if="prestation.specialization && prestation.specialization !== 'Unknown'"
              :value="prestation.specialization"
              severity="info"
              size="small"
              class="ml-2"
            />
          </div>
          <div class="prestation-details">
            <span v-if="prestation.price" class="prestation-price">
              <i class="pi pi-money-bill"></i>
              {{ formatCurrency(prestation.price) }}
            </span>
            <span v-else class="prestation-price">
              <i class="pi pi-money-bill"></i>
              N/Af
            </span>
          </div>
        </div>
      </template>
    </Card>
  </div>
</div>
      </div>
    </Dialog>

    <!-- All Conventions Modal -->
    <Dialog
      v-model:visible="showAllConventionsModal"
      header="All Available Conventions"
      modal
      class="all-conventions-modal"
      :style="{ width: '80vw', maxWidth: '1000px' }"
    >
      <div class="all-conventions-content">
        <div class="conventions-summary">
          <div class="summary-stats">
            <div class="stat-item">
              <span class="stat-number">{{ conventionOrganismes.length }}</span>
              <span class="stat-label">Companies</span>
            </div>
            <div class="stat-item">
              <span class="stat-number">{{ totalConventions }}</span>
              <span class="stat-label">Total Conventions</span>
            </div>
          </div>
        </div>

        <div class="companies-list">
          <Card
            v-for="company in conventionOrganismes"
            :key="company.id"
            class="company-card"
            @click="showOrganismeDetails(company)"
          >
            <template #content>
              <div class="company-card-content">
                <div class="company-header">
                  <div class="company-title">
                    <i class="pi pi-building"></i>
                    <strong>{{ company.company_name }}</strong>
                  </div>
                  <Badge :value="company.conventions_count" severity="info" />
                </div>
                <div class="company-conventions">
                  <div
                    v-for="convention in company.conventions.slice(0, 2)"
                    :key="convention.id"
                    class="convention-preview"
                  >
                    <span class="convention-name">{{ convention.contract_name }}</span>
                    <Tag
                      :value="convention.status"
                      :severity="getConventionStatusSeverity(convention.status)"
                      size="small"
                    />
                  </div>
                  <small v-if="company.conventions.length > 2" class="more-conventions">
                    +{{ company.conventions.length - 2 }} more
                  </small>
                </div>
              </div>
            </template>
          </Card>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<style scoped>
/* Add these new styles to your existing styles */
.prestations-list {
  margin-top: 1.5rem;
}
.prestations-list h5 {
  margin-bottom: 1rem;
  color: #2563eb;
  font-weight: 600;
}
.prestations-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 1rem;
}
.prestation-card {
  border: 1px solid #dbeafe;
  border-radius: 8px;
  background: #f8fafc;
  transition: box-shadow 0.2s;
}
.prestation-card:hover {
  box-shadow: 0 4px 16px rgba(37, 99, 235, 0.08);
}
.prestation-info {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.prestation-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.prestation-title {
  font-size: 1.1rem;
  color: #1e293b;
}
.prestation-details {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.95rem;
  color: #64748b;
}
.prestation-price {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  background: #e0f2fe;
  color: #0369a1;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
  font-weight: 500;
  font-size: 0.95rem;
}
.convention-organismes-preview {
  margin-top: 1rem;
  padding: 0.75rem;
  background: rgba(59, 130, 246, 0.05);
  border-radius: 6px;
  border: 1px solid rgba(59, 130, 246, 0.1);
}

.organismes-label {
  display: block;
  color: var(--text-color-secondary);
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
  margin-bottom: 0.5rem;
}

.organismes-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.organisme-tag {
  cursor: pointer;
  transition: all 0.2s ease;
}

.organisme-tag:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.tag-content {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.more-organismes-tag {
  cursor: pointer;
  transition: all 0.2s ease;
}

.more-organismes-tag:hover {
  background: var(--primary-100);
}

.no-conventions-message {
  margin-top: 0.75rem;
  padding: 0.5rem;
  background: rgba(156, 163, 175, 0.1);
  border-radius: 4px;
  text-align: center;
}

.no-conventions-message i {
  margin-right: 0.25rem;
}

/* Convention Details Modal Styles */
.convention-details-modal .company-info {
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--surface-200);
}

.company-info h4 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 0.5rem 0;
  color: var(--primary-color);
}

.company-description {
  color: var(--text-color-secondary);
  margin: 0;
}

.conventions-list h5 {
  margin: 0 0 1rem 0;
  color: var(--text-color);
}

.conventions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
}

.convention-card {
  border: 1px solid var(--surface-200);
  transition: all 0.2s ease;
}

.convention-card:hover {
  border-color: var(--primary-color);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.convention-info {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.convention-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.convention-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--text-color-secondary);
}

.convention-actions {
  margin-top: 0.5rem;
}

/* All Conventions Modal Styles */
.all-conventions-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.conventions-summary {
  padding: 1rem;
  background: var(--surface-50);
  border-radius: 8px;
  border: 1px solid var(--surface-200);
}

.summary-stats {
  display: flex;
  justify-content: center;
  gap: 2rem;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.stat-number {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--primary-color);
}

.stat-label {
  font-size: 0.875rem;
  color: var(--text-color-secondary);
  text-transform: uppercase;
  font-weight: 500;
}

.companies-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1rem;
}

.company-card {
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid var(--surface-200);
}

.company-card:hover {
  border-color: var(--primary-color);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.company-card-content {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.company-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.company-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--text-color);
}

.company-conventions {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.convention-preview {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem;
  background: var(--surface-50);
  border-radius: 4px;
  border: 1px solid var(--surface-200);
}

.convention-name {
  font-size: 0.875rem;
  color: var(--text-color);
}

.more-conventions {
  color: var(--text-color-secondary);
  font-style: italic;
  text-align: center;
  padding: 0.25rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .companies-tags {
    flex-direction: column;
  }
  
  .conventions-grid {
    grid-template-columns: 1fr;
  }
  
  .companies-list {
    grid-template-columns: 1fr;
  }
  
  .summary-stats {
    flex-direction: column;
    gap: 1rem;
  }
  
  .convention-details-modal,
  .all-conventions-modal {
    width: 95vw !important;
  }
}

/* Keep all your existing styles */
.add-items-container {
  padding: 1rem;
}

.main-card {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  border-radius: 12px;
}

.custom-tabview {
  min-height: 600px;
}

.steps-row {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: #f8fafc;
  border-radius: 8px;
  align-items: flex-end;
}

.step-field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  flex: 1 1 200px;
}

.step-field label {
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.checkbox-field {
  flex-basis: auto;
  align-self: flex-end;
  padding-bottom: 0.5rem;
}

.package-toggle {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.prestation-field {
  flex: 1 1 300px;
}

.full-width {
  width: 100%;
}

.dropdown-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
  padding: 0.5rem 0;
}

.option-main {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.option-name {
  font-weight: 500;
  color: #1f2937;
}

.option-code {
  font-size: 0.75rem;
  color: #6b7280;
}

.option-price {
  background: #3b82f6;
  color: white;
  padding: 0.2rem 0.4rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

/* Dependencies and Package contents styling */
.dependencies-section {
  margin-bottom: 2rem;
  padding: 1rem;
  background: #fef3c7;
  border-radius: 8px;
  border-left: 4px solid #f59e0b;
}

.dependencies-section h4 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 0.5rem 0;
  color: #92400e;
  font-size: 0.95rem;
}

.dependencies-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 0.75rem;
}

.dependency-card {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem;
  background: white;
  border-radius: 6px;
  border: 1px solid #fed7aa;
}

.dependency-card :deep(.p-checkbox) {
  width: 1rem;
  height: 1rem;
}

.dependency-card :deep(.p-checkbox .p-checkbox-box) {
  width: 1rem;
  height: 1rem;
}

.dependency-label {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex: 1;
  cursor: pointer;
  font-size: 0.875rem;
}

.dep-info {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.dep-name {
  font-weight: 500;
  color: #1f2937;
}

.dep-code {
  font-size: 0.7rem;
  color: #6b7280;
}

.dep-price {
  background: #f59e0b;
  color: white;
  padding: 0.15rem 0.3rem;
  border-radius: 4px;
  font-size: 0.7rem;
  font-weight: 500;
}

/* Selected Summary styling */
.selected-summary {
  margin-bottom: 2rem;
  padding: 1rem;
  background: #f0fdf4;
  border-radius: 8px;
  border-left: 4px solid #10b981;
}

.selected-summary h4 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 0.5rem 0;
  color: #065f46;
  font-size: 0.95rem;
}

.summary-items {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.summary-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: white;
  border-radius: 6px;
  border: 1px solid #d1fae5;
  font-size: 0.875rem;
}

.summary-card.dependency-card {
  border-left: 3px solid #f59e0b;
  background: #fffbeb;
}

.summary-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.summary-name {
  font-weight: 500;
  color: #1f2937;
}

.summary-price {
  background: #10b981;
  color: white;
  padding: 0.15rem 0.3rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

/* Custom Tab Styling */
.custom-name-section {
  margin-top: 1rem;
  margin-bottom: 2rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

/* Prestations Table styling */
.custom-datatable {
  margin-top: 1.5rem;
}

.prestation-name strong {
  display: block;
}

.prestation-code {
  font-size: 0.875rem;
  color: #6b7280;
  display: block;
}

.price-tag {
  background: #10b981;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.875rem;
  font-weight: 500;
  display: inline-block;
}

.doctor-dropdown {
  width: 100%;
}

.selected-summary-card {
  background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
  border: 1px solid #10b981;
}

.selected-summary {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.summary-items {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.summary-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: white;
  border-radius: 8px;
  border: 1px solid #d1fae5;
}

.summary-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
}

.summary-name {
  font-weight: 600;
  color: #1f2937;
}

.summary-code {
  color: #6b7280;
  font-size: 0.75rem;
}

.summary-details {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.25rem;
}

.summary-price {
  background: #10b981;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.875rem;
  font-weight: 500;
}

.assigned-doctor {
  color: #6b7280;
  font-size: 0.75rem;
}

.summary-total {
  padding: 1rem;
  background: white;
  border-radius: 8px;
  border: 2px solid #10b981;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.total-calculation {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.total-label {
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.total-amount {
  font-size: 1.5rem;
  font-weight: 700;
  color: #059669;
}

.total-items {
  color: #6b7280;
  font-size: 0.875rem;
}

.doctors-info-card {
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
  border: 1px solid #3b82f6;
}

.doctors-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
}

.doctor-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: white;
  border-radius: 8px;
  border: 1px solid #bfdbfe;
}

.doctor-avatar {
  background: #3b82f6;
}

.doctor-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.doctor-name {
  font-weight: 500;
  color: #1f2937;
}

.doctor-specialization {
  color: #6b7280;
  font-size: 0.75rem;
}
</style>