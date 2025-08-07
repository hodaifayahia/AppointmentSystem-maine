<script setup>
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
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
// Fix the import path - changed from 'Recption' to 'Reception'
import { ficheNavetteService } from '../../../../Components/Apps/services/Reception/ficheNavetteService.js'

const emit = defineEmits(['cancel', 'created'])

// Updated props to support both create and add modes
const props = defineProps({
  patientId: {
    type: Number,
    required: true
  },
  ficheNavetteId: {
    type: Number,
    default: null // null means create new fiche, otherwise add to existing
  },
  // mode: {
  //   type: String,
  //   default: 'create', // 'create' or 'add'
  //   validator: (value) => ['create', 'add'].includes(value)
  // }
})

const toast = useToast()

// Reactive data
const activeTab = ref(0)
const loading = ref(false)
const creating = ref(false)
const hasSelectedItems = ref(false)

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
const customPrestationName = ref('')

const customNameOptions = [
  { label: 'Laboratory', value: 'Laboratory' },
  { label: 'Consultation', value: 'Consultation' },
  { label: 'Radiology', value: 'Radiology' },
  { label: 'Pharmacy', value: 'Pharmacy' },
  { label: 'Other', value: 'other' }
]
const selectedCustomNameOption = ref(null)

// Prestation tab computed properties
const enhancedSpecializations = computed(() => {
  return [...specializations.value]
})

const nameToUse = computed(() => {
  if (selectedCustomNameOption.value === 'other') {
    return customPrestationName.value.trim() ? customPrestationName.value.trim() : null
  }
  return selectedCustomNameOption.value
})
const filteredDoctors = computed(() => {
  if (selectedSpecialization.value) {
    return allDoctors.value.filter(doctor => doctor.specialization_id === selectedSpecialization.value)
  }
  return allDoctors.value
})
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}


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

const filteredPackages = computed(() => {
  if (!availablePackages.value.length) return []

  let filtered = availablePackages.value

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

// Custom tab computed properties
const filteredDoctorsBySpecialization = computed(() => {
  if (selectedSpecializationsFilter.value.length === 0) return allDoctors.value
  return allDoctors.value.filter(doctor => 
    doctor.specialization_id && 
    selectedSpecializationsFilter.value.includes(doctor.specialization_id)
  )
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

const currentSelectedItems = computed(() => {
  if (activeTab.value === 0) {
    const items = []
    if (selectedPrestation.value) {
      items.push({
        type: 'prestation',
        item: selectedPrestation.value,
        dependencies: selectedDependencies.value
      })
    }
    if (selectedPackage.value) {
      items.push({
        type: 'package',
        item: selectedPackage.value
      })
    }
    return items
  } else {
    return customSelectedPrestations.value.map(p => ({
      type: 'custom',
      item: p
    }))
  }
})

// Methods
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

  console.log('Selected prestation:', prestation);
  console.log('Required prestations info:', prestation);

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
        console.log('Fetching dependencies for IDs:', dependencyIds);
        
        const deps = await fetchPrestationsByIds(dependencyIds);
        console.log('Received dependencies:', deps);
        
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

// Updated createFicheNavette method to handle both modes properly
const createFicheNavette = async () => {
  creating.value = true
  console.log('FicheNavetteItemCreate props:', props);

  try {
    const data = {
      patient_id: props.patientId,
      selectedDoctor: selectedDoctor.value,
      selectedSpecialization: selectedSpecialization.value,
      type: activeTab.value === 0 ? 'prestation' : 'custom'
    }

    // Handle prestation tab
    if (activeTab.value === 0) {
      if (selectedPrestation.value) {
        data.prestations = [selectedPrestation.value]
        data.dependencies = selectedDependencies.value
        data.ficheNavetteId = props.ficheNavetteId
      }
      if (selectedPackage.value) {
        data.packages = [selectedPackage.value]
      }
    } 
    // Handle custom tab
    else {
      const nameValue = nameToUse.value
      data.customPrestations = customSelectedPrestations.value.map(p => ({
        ...p,
        display_name: nameValue || p.name,
        type: nameValue ? 'custom' : 'predefined',
        patient_id: props.patientId,
        selected_doctor_id: p.selected_doctor_id,
        ficheNavetteId: props.ficheNavetteId

      }))
    }

    let result;

      result = await ficheNavetteService.addItemsToFiche(props.ficheNavetteId, data)
   
    
    if (result.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: props.mode === 'add' ? 'Items added successfully' : 'Fiche Navette created successfully',
        life: 3000
      })
      
      resetSelections()
      selectedSpecialization.value = null
      selectedDoctor.value = null
      
      emit('created', result.data)
    } else {
      throw new Error(result.message)
    }
  } catch (error) {
    console.error('Error:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || (props.mode === 'add' ? 'Failed to add items' : 'Failed to create Fiche Navette'),
      life: 3000
    })
  } finally {
    creating.value = false
  }
}

// API Functions
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
  console.log('Fetching prestations for IDs:', ids);
  
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

onMounted(async () => {
  loading.value = true
  try {
    await Promise.all([
      fetchSpecializations(),
      fetchAllDoctors(),
      fetchAllPrestations()
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

// Watchers
watch(customSelectedPrestations, (newSelection) => {
  hasSelectedItems.value = newSelection.length > 0
}, { deep: true })

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

watch(selectedCustomNameOption, (val) => {
  if (val !== 'other') {
    customPrestationName.value = ''
  }
})
</script>

<template>
  <div class="add-items-container">
    <Card class="main-card">
      <template #content>
        <TabView v-model:activeIndex="activeTab" @tab-change="onTabChange" class="custom-tabview">
          <!-- Prestation Tab -->
          <TabPanel header="Prestations">
            <div class="prestation-tab">
              <div class="steps-row">
                <div class="step-field">
                  <label>Specialization</label>
                  <Dropdown
                    v-model="selectedSpecialization"
                    :options="enhancedSpecializations"
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
                        <span class="option-price">{{ formatCurrency(option.price || option.public_price) }}</span>
                      </div>
                    </template>
                  </Dropdown>

                  <Dropdown
                    v-else
                    v-model="selectedPackage"
                    :options="filteredPackages"
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
                        <span class="option-price">${{ option.price }}</span>
                      </div>
                    </template>
                  </Dropdown>
                </div>
              </div>

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

          <!-- Custom Tab -->
          <TabPanel header="Custom">
            <div class="custom-tab">
              <div class="search-section">
                <div class="search-row">
                  <div class="search-field">
                    <IconField iconPosition="left" class="search-input-container">
                      <InputIcon class="pi pi-search" />
                      <InputText
                        v-model="searchTerm"
                        placeholder="Search prestations..."
                        class="full-width"
                      />
                    </IconField>
                  </div>
                  
                  <div class="filter-field">
                    <MultiSelect
                      v-model="selectedSpecializationsFilter"
                      :options="specializations"
                      optionLabel="name"
                      optionValue="id"
                      placeholder="Filter by Specializations"
                      class="full-width"
                      @change="onSpecializationFilterChange"
                    />
                  </div>
                </div>
              </div>

              <div class="custom-name-section" v-if="shouldShowCustomNameInput">
                <label for="customName">Custom Order Name</label>
                <Dropdown
                  id="customNameDropdown"
                  v-model="selectedCustomNameOption"
                  :options="customNameOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Select or type custom name"
                  class="full-width"
                />
                <InputText
                  v-if="selectedCustomNameOption === 'other'"
                  v-model="customPrestationName"
                  placeholder="Enter custom name"
                  class="full-width"
                  style="margin-top: 0.5rem"
                />
              </div>

              <DataTable
                v-model:selection="customSelectedPrestations"
                :value="filteredCustomPrestations"
                selectionMode="multiple"
                :metaKeySelection="false"
                dataKey="id"
                :paginator="true"
                :rows="10"
                :loading="loading"
                class="custom-datatable"
              >
                <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
                
                <Column field="name" header="Name" sortable>
                  <template #body="{ data }">
                    <div class="prestation-name">
                      <strong>{{ data.name }}</strong>
                      <small class="prestation-code">{{ data.internal_code }}</small>
                    </div>
                  </template>
                </Column>
                
                <Column field="specialization_name" header="Specialization" sortable></Column>
                
                <Column field="package_name" header="Package Name" sortable>
                  <template #body="{ data }">
                    <span v-if="data.package_name">{{ data.package_name }}</span>
                    <Tag value="No Package" severity="secondary" v-else />
                  </template>
                </Column>
                
                <Column header="Doctors">
                  <template #body="{ data }">
                    <Dropdown
                      v-model="data.selected_doctor_id"
                      :options="getDoctorsForPrestation(data)"
                      optionLabel="name"
                      optionValue="id"
                      placeholder="Select doctor"
                      class="full-width"
                    />
                  </template>
                </Column>
                
                <Column field="price" header="Price" sortable>
                  <template #body="{ data }">
                    <span class="price-tag">{{ formatCurrency(data.price || data.public_price) }}</span>
                  </template>
                </Column>
                
                <Column field="duration" header="Duration" sortable>
                  <template #body="{ data }">
                    <span v-if="data.default_duration_minutes">
                      {{ data.default_duration_minutes }}min
                    </span>
                  </template>
                </Column>
              </DataTable>
            </div>
          </TabPanel>
        </TabView>

        <div class="action-buttons">
          <Button
            label="Cancel"
            severity="secondary"
            @click="emit('cancel')"
            :disabled="creating"
          />
          <Button 
            :label="props.mode === 'add' ? 'Add Items' : 'Create Fiche Navette'"
            @click="createFicheNavette"
            :loading="creating"
            :disabled="!hasSelectedItems"
          />
        </div>
      </template>
    </Card>
  </div>
</template>

<style scoped>
/* Main container and card styling */
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

/* Steps in one row (flexbox for layout) */
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
  flex: 1 1 200px; /* Allows fields to grow and shrink */
}

.step-field label {
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.checkbox-field {
  flex-basis: auto; /* Take up only as much space as needed */
  align-self: flex-end; /* Align to the bottom of the row */
  padding-bottom: 0.5rem;
}

.package-toggle {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.prestation-field {
  flex: 1 1 300px; /* Make this field larger */
}

.prestation-dropdown-container {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.full-width {
  width: 100%;
}

.search-input-container {
  display: flex;
}

/* Dropdown options styling */
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
}

.action-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid #e5e7eb;
}

/* Responsive adjustments */
@media (max-width: 1200px) {
  .steps-row {
    flex-direction: column;
  }
}

@media (max-width: 768px) {
  .prestation-field {
    min-width: unset;
  }
}
</style>