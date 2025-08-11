<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useToast } from 'primevue/usetoast'

// PrimeVue Components
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import Checkbox from 'primevue/checkbox'
import InputNumber from 'primevue/inputnumber'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Divider from 'primevue/divider'

// Services
import { remiseService } from '../../../Apps/services/Remise/RemiseService'
import { userService } from '../../../Apps/services/User/userService'

// Types
interface RemiseOption {
  id: string
  name: string
  percentage: number
  type: 'percentage' | 'fixed'
  value: number
  applicable_items: string[]
}

interface User {
  id: string
  name: string
  email: string
}

interface Doctor {
  id: string
  name: string
  specialization: string
}

interface CustomBalance {
  user_id?: string
  doctor_id?: string
  balance: number
}

// Props
const props = defineProps<{
  visible: boolean
  group: ItemGroup
  prestations: Prestation[]
  doctors: Doctor[]
}>()

// Emits
const emit = defineEmits<{
  'update:visible': [value: boolean]
  'apply-remise': [data: RemiseApplication]
}>()

// State
const selectedRemise = ref<RemiseOption | null>(null)
const availableRemises = ref<RemiseOption[]>([])
const users = ref<User[]>([])
const isCustomDiscount = ref(false)
const customUserBalance = ref<CustomBalance>({ balance: 0 })
const customDoctorBalance = ref<CustomBalance>({ balance: 0 })
const loading = ref(false)

// Computed
const dialogVisible = computed({
  get: () => props.visible,
  set: (value: boolean) => emit('update:visible', value)
})

const prestationDisplayData = computed(() => {
  if (!props.group.items) return []
  
  return props.group.items.map(item => {
    const prestation = item.prestation || item.package
    const isAffectedByRemise = selectedRemise.value && 
      selectedRemise.value.applicable_items.includes(prestation?.id || '')
    
    let discountedPrice = item.final_price
    if (isAffectedByRemise && selectedRemise.value) {
      if (selectedRemise.value.type === 'percentage') {
        discountedPrice = item.final_price * (1 - selectedRemise.value.value / 100)
      } else {
        discountedPrice = Math.max(0, item.final_price - selectedRemise.value.value)
      }
    }
    
    return {
      ...item,
      prestationName: prestation?.name || 'Unknown',
      prestationCode: prestation?.internal_code || 'N/A',
      originalPrice: item.final_price,
      discountedPrice,
      discount: item.final_price - discountedPrice,
      isAffected: isAffectedByRemise
    }
  })
})

const totalOriginal = computed(() => {
  return prestationDisplayData.value.reduce((sum, item) => sum + item.originalPrice, 0)
})

const totalDiscounted = computed(() => {
  return prestationDisplayData.value.reduce((sum, item) => sum + item.discountedPrice, 0)
})

const totalSavings = computed(() => {
  return totalOriginal.value - totalDiscounted.value
})

// Methods
const loadRemises = async () => {
  try {
    loading.value = true
    const response = await remiseService.getAvailableRemises(props.group.id)
    availableRemises.value = response.data
  } catch (error) {
    console.error('Error loading remises:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load available discounts',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadUsers = async () => {
  try {
    const response = await userService.getAllUsers()
    users.value = response.data
  } catch (error) {
    console.error('Error loading users:', error)
  }
}

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD',
  }).format(amount || 0)
}

const applyRemise = async () => {
  try {
    loading.value = true
    
    const remiseData = {
      group_id: props.group.id,
      remise_id: selectedRemise.value?.id,
      is_custom: isCustomDiscount.value,
      custom_user_balance: isCustomDiscount.value ? customUserBalance.value : null,
      custom_doctor_balance: isCustomDiscount.value ? customDoctorBalance.value : null,
      affected_items: prestationDisplayData.value
        .filter(item => item.isAffected)
        .map(item => ({
          item_id: item.id,
          original_price: item.originalPrice,
          discounted_price: item.discountedPrice,
          discount_amount: item.discount
        }))
    }
    
    const response = await remiseService.applyRemise(remiseData)
    
    if (response.success) {
      emit('apply-remise', remiseData)
      dialogVisible.value = false
      
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Discount applied successfully',
        life: 3000
      })
      
      resetForm()
    }
  } catch (error) {
    console.error('Error applying remise:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to apply discount',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  selectedRemise.value = null
  isCustomDiscount.value = false
  customUserBalance.value = { balance: 0 }
  customDoctorBalance.value = { balance: 0 }
}

// Watchers
watch(dialogVisible, (visible) => {
  if (visible) {
    loadRemises()
    loadUsers()
  } else {
    resetForm()
  }
})

// Composables
const toast = useToast()
</script>

<template>
  <Dialog
    v-model:visible="dialogVisible"
    header="Apply Discount (Remise)"
    :style="{ width: '1000px', maxHeight: '90vh' }"
    :modal="true"
    class="remise-modal"
  >
    <div class="remise-content">
      <!-- Remise Selection -->
      <Card class="mb-4">
        <template #title>
          <div class="section-title">
            <i class="pi pi-percentage"></i>
            Discount Selection
          </div>
        </template>
        <template #content>
          <div class="remise-selection">
            <div class="field">
              <label for="remise-dropdown">Available Discounts</label>
              <Dropdown
                id="remise-dropdown"
                v-model="selectedRemise"
                :options="availableRemises"
                option-label="name"
                placeholder="Select a discount"
                :loading="loading"
                class="w-full"
              >
                <template #option="{ option }">
                  <div class="remise-option">
                    <div class="remise-name">{{ option.name }}</div>
                    <div class="remise-value">
                      <span v-if="option.type === 'percentage'">
                        {{ option.value }}%
                      </span>
                      <span v-else>
                        {{ formatCurrency(option.value) }}
                      </span>
                    </div>
                  </div>
                </template>
              </Dropdown>
            </div>
            
            <div class="field-checkbox">
              <Checkbox
                id="custom-discount"
                v-model="isCustomDiscount"
                binary
              />
              <label for="custom-discount">Enable Custom Discount</label>
            </div>
          </div>
        </template>
      </Card>

      <!-- Custom Discount Options -->
      <Card v-if="isCustomDiscount" class="mb-4">
        <template #title>
          <div class="section-title">
            <i class="pi pi-cog"></i>
            Custom Discount Configuration
          </div>
        </template>
        <template #content>
          <div class="custom-discount-config">
            <!-- User Balance Section -->
            <div class="balance-section">
              <h6>User Balance</h6>
              <div class="balance-fields">
                <div class="field">
                  <label for="user-select">User</label>
                  <Dropdown
                    id="user-select"
                    v-model="customUserBalance.user_id"
                    :options="users"
                    option-label="name"
                    option-value="id"
                    placeholder="Select user"
                    class="w-full"
                  />
                </div>
                <div class="field">
                  <label for="user-balance">Balance Amount</label>
                  <InputNumber
                    id="user-balance"
                    v-model="customUserBalance.balance"
                    mode="currency"
                    currency="DZD"
                    locale="fr-FR"
                    class="w-full"
                  />
                </div>
              </div>
            </div>
            
            <Divider />
            
            <!-- Doctor Balance Section -->
            <div class="balance-section">
              <h6>Doctor Balance</h6>
              <div class="balance-fields">
                <div class="field">
                  <label for="doctor-select">Doctor</label>
                  <Dropdown
                    id="doctor-select"
                    v-model="customDoctorBalance.doctor_id"
                    :options="doctors"
                    option-label="name"
                    option-value="id"
                    placeholder="Select doctor"
                    class="w-full"
                  />
                </div>
                <div class="field">
                  <label for="doctor-balance">Balance Amount</label>
                  <InputNumber
                    id="doctor-balance"
                    v-model="customDoctorBalance.balance"
                    mode="currency"
                    currency="DZD"
                    locale="fr-FR"
                    class="w-full"
                  />
                </div>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Prestation Display with Pricing -->
      <Card class="mb-4">
        <template #title>
          <div class="section-title">
            <i class="pi pi-list"></i>
            Services & Pricing
          </div>
        </template>
        <template #content>
          <DataTable
            :value="prestationDisplayData"
            class="pricing-table"
            responsiveLayout="scroll"
            :rowHover="true"
          >
            <Column field="prestationName" header="Service">
              <template #body="{ data }">
                <div class="service-cell">
                  <div class="service-name">{{ data.prestationName }}</div>
                  <small class="service-code">{{ data.prestationCode }}</small>
                  <div v-if="data.isAffected" class="affected-badge">
                    <Tag value="Discounted" severity="success" size="small" />
                  </div>
                </div>
              </template>
            </Column>

            <Column field="originalPrice" header="Original Price">
              <template #body="{ data }">
                <span class="original-price">{{ formatCurrency(data.originalPrice) }}</span>
              </template>
            </Column>

            <Column field="discount" header="Discount">
              <template #body="{ data }">
                <span v-if="data.discount > 0" class="discount-amount">
                  -{{ formatCurrency(data.discount) }}
                </span>
                <span v-else class="no-discount">-</span>
              </template>
            </Column>

            <Column field="discountedPrice" header="Final Price">
              <template #body="{ data }">
                <div class="final-price-cell">
                  <span 
                    :class="['final-price', { 'discounted': data.isAffected }]"
                  >
                    {{ formatCurrency(data.discountedPrice) }}
                  </span>
                  <span 
                    v-if="data.isAffected && data.discount > 0" 
                    class="savings-badge"
                  >
                    Save {{ Math.round((data.discount / data.originalPrice) * 100) }}%
                  </span>
                </div>
              </template>
            </Column>
          </DataTable>
          
          <!-- Total Summary -->
          <div class="pricing-summary">
            <div class="summary-row">
              <span class="summary-label">Original Total:</span>
              <span class="summary-value original">{{ formatCurrency(totalOriginal) }}</span>
            </div>
            <div class="summary-row">
              <span class="summary-label">Total Discount:</span>
              <span class="summary-value discount">-{{ formatCurrency(totalSavings) }}</span>
            </div>
            <div class="summary-row total">
              <span class="summary-label">Final Total:</span>
              <span class="summary-value final">{{ formatCurrency(totalDiscounted) }}</span>
            </div>
            <div v-if="totalSavings > 0" class="savings-percentage">
              You save {{ Math.round((totalSavings / totalOriginal) * 100) }}%
            </div>
          </div>
        </template>
      </Card>
    </div>

    <template #footer>
      <div class="modal-footer">
        <Button
          label="Cancel"
          icon="pi pi-times"
          class="p-button-text"
          @click="dialogVisible = false"
        />
        <Button
          label="Apply Discount"
          icon="pi pi-check"
          class="p-button-success"
          :disabled="!selectedRemise"
          :loading="loading"
          @click="applyRemise"
        />
      </div>
    </template>
  </Dialog>
</template>

<style scoped>
.remise-content {
  max-height: 70vh;
  overflow-y: auto;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: #2c3e50;
}

.remise-selection {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.remise-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.remise-name {
  font-weight: 500;
}

.remise-value {
  color: var(--green-500);
  font-weight: 600;
}

.custom-discount-config {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.balance-section h6 {
  margin: 0 0 1rem 0;
  color: #495057;
}

.balance-fields {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.service-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.service-name {
  font-weight: 500;
  color: #2c3e50;
}

.service-code {
  color: #6c757d;
  font-size: 0.8rem;
}

.affected-badge {
  margin-top: 0.25rem;
}

.original-price {
  color: #495057;
}

.discount-amount {
  color: var(--red-500);
  font-weight: 500;
}

.no-discount {
  color: #6c757d;
}

.final-price-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.final-price {
  font-weight: 600;
}

.final-price.discounted {
  color: var(--green-500);
}

.savings-badge {
  font-size: 0.75rem;
  color: var(--green-600);
  font-weight: 500;
}

.pricing-summary {
  border-top: 2px solid #e9ecef;
  padding-top: 1rem;
  margin-top: 1rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
}

.summary-row.total {
  border-top: 1px solid #e9ecef;
  margin-top: 0.5rem;
  font-weight: 600;
  font-size: 1.1rem;
}

.summary-label {
  color: #495057;
}

.summary-value.original {
  color: #6c757d;
}

.summary-value.discount {
  color: var(--red-500);
}

.summary-value.final {
  color: var(--green-500);
  font-weight: 600;
}

.savings-percentage {
  text-align: center;
  margin-top: 0.5rem;
  padding: 0.5rem;
  background: var(--green-50);
  color: var(--green-700);
  border-radius: 4px;
  font-weight: 500;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

@media (max-width: 768px) {
  .balance-fields {
    grid-template-columns: 1fr;
  }
  
  .modal-footer {
    flex-direction: column;
  }
}
</style>
