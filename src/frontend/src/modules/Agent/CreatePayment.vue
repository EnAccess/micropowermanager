<template>
  <div>
    <div class="md-layout md-gutter">
      <!-- Payment Form Card -->
      <div
        class="md-layout-item md-xlarge-size-60 md-large-size-60 md-medium-size-100 md-small-size-100"
      >
        <md-card>
          <md-card-header>
            <div class="md-title">
              {{ $tc("phrases.createPaymentForCustomer") }}
            </div>
            <div class="md-subhead">
              {{ $tc("phrases.generatePaystackPaymentLink") }}
            </div>
          </md-card-header>
          <md-card-content>
            <form @submit.prevent="createPayment">
              <!-- Customer Selection -->
              <div class="md-layout md-gutter">
                <div class="md-layout-item md-size-100">
                  <md-field>
                    <label>{{ $tc("words.customer") }}</label>
                    <md-select
                      v-model="paymentForm.customerId"
                      :disabled="loading"
                      @md-selected="onCustomerSelected"
                    >
                      <md-option
                        v-for="customer in customers"
                        :key="customer.id"
                        :value="customer.id"
                      >
                        {{ customer.name }} {{ customer.surname }}
                        <span v-if="customer.addresses.length > 0">
                          ({{ customer.addresses[0].phone }})
                        </span>
                      </md-option>
                    </md-select>
                  </md-field>
                </div>
              </div>

              <!-- Meter Selection (if customer has multiple meters) -->
              <div
                class="md-layout md-gutter"
                v-if="selectedCustomer && selectedCustomer.devices.length > 1"
              >
                <div class="md-layout-item md-size-100">
                  <md-field>
                    <label>{{ $tc("words.device") }}</label>
                    <md-select
                      v-model="paymentForm.deviceSerial"
                      @md-selected="onDeviceSerialSelected"
                      :disabled="loading"
                    >
                      <md-option
                        v-for="device in selectedCustomer.devices"
                        :key="device.device_serial"
                        :value="device.device_serial"
                        @change="onDeviceSelected(device)"
                      >
                        {{ device.device_serial }} ({{ device.device_type }})
                      </md-option>
                    </md-select>
                  </md-field>
                </div>
              </div>

              <!-- Payment Amount -->
              <div class="md-layout md-gutter">
                <div class="md-layout-item md-size-100">
                  <md-field>
                    <label>{{ $tc("words.amount") }} ({{ currency }})</label>
                    <md-input
                      v-model="paymentForm.amount"
                      type="number"
                      step="0.01"
                      min="0.01"
                      :disabled="loading"
                      required
                    />
                  </md-field>
                </div>
              </div>

              <!-- Currency Selection -->
              <div class="md-layout md-gutter">
                <div class="md-layout-item md-size-100">
                  <md-field>
                    <label>{{ $tc("words.currency") }}</label>
                    <md-select
                      v-model="paymentForm.currency"
                      :disabled="loading"
                    >
                      <md-option value="NGN">NGN - Nigerian Naira</md-option>
                      <md-option value="KES">KES - Kenyan Shilling</md-option>
                    </md-select>
                  </md-field>
                </div>
              </div>

              <!-- Payment Purpose -->
              <div class="md-layout md-gutter">
                <div class="md-layout-item md-size-100">
                  <md-field>
                    <label>{{ $tc("phrases.paymentPurpose") }}</label>
                    <md-select
                      v-model="paymentForm.purpose"
                      :disabled="loading"
                    >
                      <md-option value="energy">
                        {{ $tc("words.energy") }} {{ $tc("phrases.topUp") }}
                      </md-option>
                      <md-option value="appliance">
                        {{ $tc("words.appliance") }} {{ $tc("words.payment") }}
                      </md-option>
                      <md-option value="maintenance">
                        {{ $tc("words.maintenance") }} {{ $tc("words.fee") }}
                      </md-option>
                      <md-option value="other">
                        {{ $tc("words.other") }}
                      </md-option>
                    </md-select>
                  </md-field>
                </div>
              </div>

              <!-- Commission Preview -->
              <div class="md-layout md-gutter" v-if="expectedCommission > 0">
                <div class="md-layout-item md-size-100">
                  <md-card class="commission-preview">
                    <md-card-content>
                      <div class="commission-info">
                        <md-icon class="commission-icon">
                          account_balance_wallet
                        </md-icon>
                        <div>
                          <strong>
                            {{ $tc("phrases.expectedCommission") }}:
                          </strong>
                          {{
                            formatCurrency(
                              expectedCommission,
                              paymentForm.currency,
                            )
                          }}
                        </div>
                      </div>
                    </md-card-content>
                  </md-card>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="md-layout md-gutter">
                <div class="md-layout-item md-size-100">
                  <div class="form-actions">
                    <md-button
                      type="submit"
                      class="md-raised md-primary"
                      :disabled="!isFormValid || loading"
                    >
                      <md-progress-spinner v-if="loading" :md-diameter="20" />
                      <span v-else>
                        {{ $tc("phrases.generatePaymentLink") }}
                      </span>
                    </md-button>
                    <md-button
                      type="button"
                      class="md-raised"
                      @click="resetForm"
                      :disabled="loading"
                    >
                      {{ $tc("words.reset") }}
                    </md-button>
                  </div>
                </div>
              </div>
            </form>
          </md-card-content>
        </md-card>
      </div>

      <!-- Payment Link Result Card -->
      <div
        class="md-layout-item md-xlarge-size-40 md-large-size-40 md-medium-size-100 md-small-size-100"
      >
        <div v-if="generatedPayment">
          <payment-link-card
            :payment="generatedPayment"
            :customer="selectedCustomer"
            @link-shared="onLinkShared"
          />
        </div>

        <!-- Agent Payment History -->
        <div class="recent-payments" v-if="recentPayments.length > 0">
          <md-card>
            <md-card-header>
              <div class="md-title">
                {{ $tc("phrases.recentPaymentLinks") }}
              </div>
            </md-card-header>
            <md-card-content>
              <div class="payment-history-list">
                <div
                  v-for="payment in recentPayments"
                  :key="payment.id"
                  class="payment-history-item"
                  @click="selectRecentPayment(payment)"
                >
                  <div class="payment-info">
                    <div class="customer-name">{{ payment.customerName }}</div>
                    <div class="payment-amount">
                      {{ formatCurrency(payment.amount, payment.currency) }}
                    </div>
                    <div
                      class="payment-status"
                      :class="getStatusClass(payment.status)"
                    >
                      {{ getStatusText(payment.status) }}
                    </div>
                  </div>
                  <div class="payment-date">
                    {{ formatDate(payment.createdAt) }}
                  </div>
                </div>
              </div>
            </md-card-content>
          </md-card>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { PersonService } from "@/services/PersonService"
import { TransactionService as PaystackTransactionService } from "@/plugins/paystack-payment-provider/services/TransactionService"
import { notify } from "@/mixins/notify"
import PaymentLinkCard from "./PaymentLinkCard.vue"

export default {
  name: "CreatePayment",
  mixins: [notify],
  components: {
    PaymentLinkCard,
  },
  data() {
    return {
      loading: false,
      customers: [],
      selectedCustomer: null,
      generatedPayment: null,
      recentPayments: [],
      personService: new PersonService(),
      paystackService: new PaystackTransactionService(),
      paymentForm: {
        customerId: null,
        deviceSerial: null,
        amount: null,
        currency: "NGN",
        purpose: "energy",
        deviceType: "meter",
      },
      commissionRate: 0.02, // 2% default commission rate
    }
  },
  computed: {
    isFormValid() {
      return (
        this.paymentForm.customerId &&
        this.paymentForm.amount &&
        this.paymentForm.amount > 0 &&
        this.paymentForm.currency &&
        this.paymentForm.deviceSerial &&
        this.paymentForm.deviceType
      )
    },
    currency() {
      return this.paymentForm.currency || "NGN"
    },
    expectedCommission() {
      if (!this.paymentForm.amount) return 0
      return this.paymentForm.amount * this.commissionRate
    },
  },
  async mounted() {
    await this.loadCustomers()
    await this.loadRecentPayments()
  },
  methods: {
    async loadCustomers() {
      try {
        this.loading = true
        // Load customers from the PersonService
        const response = await this.personService.searchPerson({ limit: 100 })
        if (response && response.data) {
          this.customers = response.data.data || []
        }
      } catch (error) {
        console.error("Error loading customers:", error)
        this.alertNotify("error", "Failed to load customers")
      } finally {
        this.loading = false
      }
    },

    onCustomerSelected(customerId) {
      this.selectedCustomer = this.customers.find((c) => c.id === customerId)

      // Auto-select meter if customer has only one device
      if (this.selectedCustomer && this.selectedCustomer.devices.length === 1) {
        this.paymentForm.deviceSerial =
          this.selectedCustomer.devices[0].device_serial
        this.paymentForm.deviceType =
          this.selectedCustomer.devices[0].device_type
      } else {
        this.paymentForm.deviceSerial = null
      }
    },
    onDeviceSerialSelected(deviceSerial) {
      if (deviceSerial && this.selectedCustomer) {
        const selectedDevice = this.selectedCustomer.devices.find(
          (device) => device.device_serial === deviceSerial,
        )
        if (selectedDevice) {
          this.paymentForm.deviceType = selectedDevice.device_type
        }
      }
    },

    async createPayment() {
      if (!this.isFormValid) return

      try {
        this.loading = true

        const transactionData = {
          amount: parseFloat(this.paymentForm.amount),
          device_serial: this.paymentForm.deviceSerial,
          customer_id: this.paymentForm.customerId,
          currency: this.paymentForm.currency,
          device_type: this.paymentForm.deviceType,
        }

        const response =
          await this.paystackService.createTransaction(transactionData)

        if (response && response.data) {
          if (response.data.data?.error) {
            this.alertNotify(
              "error",
              `Failed to generate payment link: ${response.data.data.error}`,
            )
            return
          }

          if (!response.data.data?.redirectionUrl) {
            this.alertNotify("error", "Payment link was not generated properly")
            return
          }

          this.generatedPayment = {
            redirectionUrl: response.data.data?.redirectionUrl,
            reference: response.data.data?.reference,
            error: response.data.data?.error,
            amount: parseFloat(transactionData.amount),
            currency: transactionData.currency,
            device_serial: transactionData.device_serial,
            customer_id: transactionData.customer_id,
            customerName:
              this.selectedCustomer.name + " " + this.selectedCustomer.surname,
            purpose: this.paymentForm.purpose,
            status: 0, // Pending status
          }

          this.alertNotify("success", "Payment link generated successfully!")
          await this.loadRecentPayments()
        }
      } catch (error) {
        console.error("Error creating payment:", error)
        this.alertNotify("error", "Failed to generate payment link")
      } finally {
        this.loading = false
      }
    },

    async loadRecentPayments() {
      try {
        const response = await this.paystackService.getTransactions()
        if (response && response.data && response.data.data) {
          // Get last 5 payments for this agent
          this.recentPayments = response.data.data
            .slice(0, 5)
            .map((payment) => ({
              ...payment,
              customerName:
                payment.customer_name || `Customer ${payment.customer_id}`,
            }))
        }
      } catch (error) {
        console.error("Error loading recent payments:", error)
      }
    },

    selectRecentPayment(payment) {
      this.generatedPayment = payment
    },

    resetForm() {
      this.paymentForm = {
        customerId: null,
        deviceSerial: null,
        amount: null,
        currency: "NGN",
        purpose: "energy",
      }
      this.selectedCustomer = null
      this.generatedPayment = null
    },

    onLinkShared(method) {
      this.alertNotify("success", `Payment link shared via ${method}`)
    },

    formatCurrency(amount, currency) {
      return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: currency || "NGN",
      }).format(amount)
    },

    formatDate(dateString) {
      if (!dateString) return ""
      return new Date(dateString).toLocaleDateString()
    },

    getStatusClass(status) {
      switch (status) {
        case 0:
          return "status-pending"
        case 1:
          return "status-success"
        case 2:
          return "status-failed"
        default:
          return "status-unknown"
      }
    },

    getStatusText(status) {
      switch (status) {
        case 0:
          return "Pending"
        case 1:
          return "Completed"
        case 2:
          return "Failed"
        default:
          return "Unknown"
      }
    },
  },
}
</script>

<style scoped>
.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-start;
  margin-top: 1rem;
}

.commission-preview {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
}

.commission-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.commission-icon {
  color: #28a745;
}

.recent-payments {
  margin-top: 1rem;
}

.payment-history-list {
  max-height: 300px;
  overflow-y: auto;
}

.payment-history-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  border-bottom: 1px solid #eee;
  cursor: pointer;
  transition: background-color 0.2s;
}

.payment-history-item:hover {
  background-color: #f8f9fa;
}

.payment-info {
  flex: 1;
}

.customer-name {
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.payment-amount {
  font-size: 0.9rem;
  color: #666;
}

.payment-status {
  font-size: 0.8rem;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  display: inline-block;
  margin-top: 0.25rem;
}

.status-pending {
  background-color: #fff3cd;
  color: #856404;
}

.status-success {
  background-color: #d4edda;
  color: #155724;
}

.status-failed {
  background-color: #f8d7da;
  color: #721c24;
}

.payment-date {
  font-size: 0.8rem;
  color: #999;
}

@media (max-width: 768px) {
  .form-actions {
    flex-direction: column;
  }

  .payment-history-item {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
