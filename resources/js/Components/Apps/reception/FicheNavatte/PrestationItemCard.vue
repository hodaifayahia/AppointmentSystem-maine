<script setup>
import { ref, computed } from 'vue'
import { useToast } from 'primevue/usetoast'

// PrimeVue Components
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Chip from 'primevue/chip'
import Dropdown from 'primevue/dropdown'
import Dialog from 'primevue/dialog'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

// Props
const props = defineProps({
  group: {
    type: Object,
    required: true
  },
  prestations: {
    type: Array,
    default: () => []
  },
  packages: {
    type: Array,
    default: () => []
  },
  doctors: {
    type: Array,
    default: () => []
  }
})

// Emits
const emit = defineEmits(['remove-item', 'item-updated'])

// Composables
const toast = useToast()

// State
const showDetailsModal = ref(false)

// Computed
const cardTitle = computed(() => {
  if (props.group.type === 'package') {
    return props.group.name || 'Package'
  }
  return props.group.name || 'Prestation'
})

const cardSubtitle = computed(() => {
  if (props.group.doctor_name) {
    return `Dr. ${props.group.doctor_name}`
  }
  return 'No doctor assigned'
})

// Get all dependencies from all items in the group
const allDependencies = computed(() => {
  const dependencies = []
  props.group.items.forEach(item => {
    if (item.dependencies && Array.isArray(item.dependencies)) {
      item.dependencies.forEach(dep => {
        dependencies.push({
          ...dep,
          parentItem: item
        })
      })
    }
  })
  return dependencies
})

// Status options for dropdown
const statusOptions = [
  { label: 'Pending', value: 'pending', severity: 'warning' },
  { label: 'In Progress', value: 'in_progress', severity: 'info' },
  { label: 'Completed', value: 'completed', severity: 'success' },
  { label: 'Cancelled', value: 'cancelled', severity: 'danger' },
  { label: 'Required', value: 'required', severity: 'secondary' }
]

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD',
  }).format(amount || 0)
}

const getStatusData = (status) => {
  return statusOptions.find(option => option.value === status) || statusOptions[0]
}

const getItemTypeIcon = (item) => {
  if (item.prestation_id) return 'pi pi-medical'
  if (item.package_id) return 'pi pi-box'
  return 'pi pi-circle'
}

const getItemTypeBadge = (item) => {
  if (item.prestation_id) return { label: 'Prestation', severity: 'success' }
  if (item.package_id) return { label: 'Package', severity: 'info' }
  return { label: 'Unknown', severity: 'secondary' }
}

const updateItemStatus = async (item, newStatus) => {
  // Implement status update logic here
  console.log('Updating item status:', item.id, newStatus)
  // You can emit an event to the parent component to handle the update
  emit('item-updated', { itemId: item.id, status: newStatus })
}

const openDetails = () => {
  showDetailsModal.value = true
}

const removeItem = (itemId) => {
  emit('remove-item', itemId)
}
</script>

<template>
  <Card class="item-card">
    <template #header>
      <div class="card-header">
        <div class="header-left">
          <div class="card-icon">
            <i :class="group.type === 'package' ? 'pi pi-box' : 'pi pi-medical'"></i>
          </div>
          <div class="header-info">
            <h6 class="card-title">{{ cardTitle }}</h6>
            <small class="card-subtitle">{{ cardSubtitle }}</small>
          </div>
        </div>
        <div class="header-actions">
          <Chip 
            :label="group.type === 'package' ? 'Package' : 'Prestation'"
            :severity="group.type === 'package' ? 'info' : 'success'"
            class="type-chip"
          />
        </div>
      </div>
    </template>

    <template #content>
      <div class="card-content">
        <!-- Summary Info -->
        <div class="summary-info">
          <div class="info-item">
            <span class="info-label">Items:</span>
            <Chip 
              :label="`${group.items.length} item${group.items.length !== 1 ? 's' : ''}`"
              severity="secondary"
            />
          </div>
          <div class="info-item">
            <span class="info-label">Total:</span>
            <strong class="total-price">{{ formatCurrency(group.total_price) }}</strong>
          </div>
        </div>

        <!-- Dependencies Summary -->
        <div v-if="allDependencies.length > 0" class="dependencies-summary">
          <div class="info-item">
            <span class="info-label">Dependencies:</span>
            <Chip 
              :label="`${allDependencies.length} dependency${allDependencies.length !== 1 ? 'ies' : 'y'}`"
              severity="warning"
            />
          </div>
          <div class="dependencies-preview">
            <div 
              v-for="(dependency, index) in allDependencies.slice(0, 3)" 
              :key="dependency.id"
              class="dependency-chip"
            >
              <Chip 
                :label="dependency.dependency_prestation?.name || 'Unknown'"
                severity="secondary"
                class="dependency-item"
              />
            </div>
            <Chip 
              v-if="allDependencies.length > 3"
              :label="`+${allDependencies.length - 3} more`"
              severity="info"
              class="more-deps"
            />
          </div>
        </div>
      </div>
    </template>

    <template #footer>
      <div class="card-footer">
        <Button 
          icon="pi pi-eye"
          label="Details"
          class="p-button-outlined p-button-secondary p-button-sm"
          @click="openDetails"
        />
        <Button 
          icon="pi pi-trash"
          label="Remove"
          class="p-button-outlined p-button-danger p-button-sm"
          @click="removeItem(group.items[0].id)"
        />
      </div>
    </template>
  </Card>

  <!-- Details Modal -->
  <Dialog 
    v-model:visible="showDetailsModal"
    :header="`${cardTitle} - Details`"
    :style="{ width: '1100px', maxHeight: '90vh' }"
    :modal="true"
    class="details-modal"
  >
    <div class="details-content">
      <!-- Group Info -->
      <Card class="group-info mb-4">
        <template #content>
          <div class="group-details">
            <div class="detail-item">
              <span class="detail-label">Type:</span>
              <Chip 
                :label="group.type === 'package' ? 'Package' : 'Individual Prestation'"
                :severity="group.type === 'package' ? 'info' : 'success'"
              />
            </div>
            <div class="detail-item">
              <span class="detail-label">Doctor:</span>
              <span>{{ group.doctor_name || 'Not assigned' }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Total Price:</span>
              <strong class="total-amount">{{ formatCurrency(group.total_price) }}</strong>
            </div>
            <div class="detail-item">
              <span class="detail-label">Total Dependencies:</span>
              <Chip 
                :label="`${allDependencies.length} dependencies`"
                severity="warning"
              />
            </div>
          </div>
        </template>
      </Card>

      <!-- Main Items Table -->
      <Card class="mb-4">
        <template #title>
          <div class="d-flex align-items-center gap-2">
            <i class="pi pi-list"></i>
            Main Items ({{ group.items.length }})
          </div>
        </template>
        <template #content>
          <DataTable 
            :value="group.items"
            class="items-table"
            responsiveLayout="scroll"
            :rowHover="true"
          >
            <Column field="prestation.name" header="Name">
              <template #body="{ data }">
                <div class="item-name-cell">
                  <i :class="getItemTypeIcon(data)" class="mr-2"></i>
                  <div>
                    <div class="item-name">{{ data.prestation?.name || data.package?.name }}</div>
                    <small class="item-code">{{ data.prestation?.internal_code }}</small>
                  </div>
                </div>
              </template>
            </Column>

            <Column field="status" header="Status">
              <template #body="{ data }">
                <Tag 
                  :value="getStatusData(data.status).label"
                  :severity="getStatusData(data.status).severity"
                />
              </template>
            </Column>

            <Column field="base_price" header="Base Price">
              <template #body="{ data }">
                {{ formatCurrency(data.base_price) }}
              </template>
            </Column>

            <Column field="final_price" header="Final Price">
              <template #body="{ data }">
                <strong>{{ formatCurrency(data.final_price) }}</strong>
              </template>
            </Column>

            <Column field="patient_share" header="Patient Share">
              <template #body="{ data }">
                {{ formatCurrency(data.patient_share) }}
              </template>
            </Column>

            <Column header="Dependencies">
              <template #body="{ data }">
                <Chip 
                  v-if="data.dependencies && data.dependencies.length > 0"
                  :label="`${data.dependencies.length} deps`"
                  severity="warning"
                />
                <span v-else class="text-muted">No dependencies</span>
              </template>
            </Column>

            <Column header="Actions">
              <template #body="{ data }">
                <Button 
                  icon="pi pi-trash"
                  class="p-button-rounded p-button-text p-button-sm p-button-danger"
                  @click="removeItem(data.id)"
                  v-tooltip.top="'Remove item'"
                />
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>

      <!-- Dependencies Table -->
      <Card v-if="allDependencies.length > 0">
        <template #title>
          <div class="d-flex align-items-center gap-2">
            <i class="pi pi-sitemap"></i>
            All Dependencies ({{ allDependencies.length }})
          </div>
        </template>
        <template #content>
          <DataTable 
            :value="allDependencies"
            class="dependencies-table"
            responsiveLayout="scroll"
            :rowHover="true"
          >
            <Column field="dependency_prestation.name" header="Dependency Name">
              <template #body="{ data }">
                <div class="dependency-name-cell">
                  <i class="pi pi-arrow-right mr-2 text-primary"></i>
                  <div>
                    <div class="dependency-name">{{ data.dependency_prestation?.name }}</div>
                    <small class="dependency-code">{{ data.dependency_prestation?.internal_code }}</small>
                  </div>
                </div>
              </template>
            </Column>

            <Column field="dependency_type" header="Type">
              <template #body="{ data }">
                <Tag 
                  :value="data.dependency_type"
                  :severity="data.dependency_type === 'required' ? 'danger' : 'info'"
                />
              </template>
            </Column>

            <Column field="dependency_prestation.specialization_name" header="Specialization">
              <template #body="{ data }">
                <Chip 
                  :label="data.dependency_prestation?.specialization_name || 'N/A'"
                  severity="secondary"
                />
              </template>
            </Column>

            <Column field="dependency_prestation.public_price" header="Price">
              <template #body="{ data }">
                {{ formatCurrency(data.dependency_prestation?.public_price) }}
              </template>
            </Column>

            <Column field="notes" header="Notes">
              <template #body="{ data }">
                <span class="notes-text">{{ data.notes || 'No notes' }}</span>
              </template>
            </Column>

            <Column header="Parent Item">
              <template #body="{ data }">
                <small class="parent-item">
                  {{ data.parentItem?.prestation?.name || data.parentItem?.package?.name }}
                </small>
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>
    </div>

    <template #footer>
      <Button 
        label="Close"
        icon="pi pi-times"
        class="p-button-text"
        @click="showDetailsModal = false"
      />
    </template>
  </Dialog>
</template>

<style scoped>
.item-card {
  border-radius: 12px;
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.item-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
  border-color: var(--primary-color);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: var(--surface-50);
  border-radius: 12px 12px 0 0;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.card-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: var(--primary-100);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--primary-color);
  font-size: 1.5rem;
}

.card-title {
  margin: 0 0 0.25rem 0;
  color: var(--text-color);
  font-weight: 600;
}

.card-subtitle {
  color: var(--text-color-secondary);
}

.type-chip {
  font-size: 0.75rem;
}

.card-content {
  padding: 0;
}

.summary-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.info-label {
  color: var(--text-color-secondary);
  font-size: 0.875rem;
}

.total-price {
  color: var(--primary-color);
  font-size: 1.1rem;
}

.dependencies-summary {
  background: var(--surface-50);
  padding: 1rem;
  border-radius: 8px;
  border-left: 4px solid var(--yellow-500);
}

.dependencies-preview {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.dependency-chip {
  display: inline-block;
}

.dependency-item {
  font-size: 0.75rem;
}

.more-deps {
  font-size: 0.75rem;
}

.card-footer {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}

.group-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detail-label {
  font-size: 0.875rem;
  color: var(--text-color-secondary);
  font-weight: 500;
}

.total-amount {
  color: var(--primary-color);
  font-size: 1.25rem;
}

.items-table,
.dependencies-table {
  width: 100%;
}

.item-name-cell,
.dependency-name-cell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.item-name,
.dependency-name {
  font-weight: 600;
  color: var(--text-color);
}

.item-code,
.dependency-code {
  color: var(--text-color-secondary);
  font-size: 0.8rem;
}

.dependency-name-cell .dependency-name {
  color: var(--primary-color);
}

.notes-text {
  font-style: italic;
  color: var(--text-color-secondary);
}

.parent-item {
  color: var(--text-color-secondary);
  font-style: italic;
}

.text-muted {
  color: var(--text-color-secondary);
  font-style: italic;
}

@media (max-width: 768px) {
  .group-details {
    grid-template-columns: 1fr;
  }
  
  .summary-info {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }
  
  .dependencies-preview {
    justify-content: center;
  }
  
  .card-footer {
    flex-direction: column;
    gap: 0.5rem;
  }
}</style>