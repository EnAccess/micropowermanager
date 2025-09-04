<template>
  <div class="welcome">
    <div class="content">
      <div class="title">
        <span class="title highlight">MicroPowerManager</span>
      </div>
      <div class="title-2">
        Payment Result - {{ companyName }}
      </div>

      <div v-if="loading" class="loading-container">
        <md-progress-spinner md-diameter="60" md-stroke="4"></md-progress-spinner>
        <p>Verifying your payment...</p>
      </div>

      <div v-else-if="paymentResult" class="result-container">
        <!-- Success State -->
        <div v-if="paymentResult.success" class="success-state">
          <md-icon class="success-icon">check_circle</md-icon>
          <h2 class="success-title">Payment Successful!</h2>
          <p class="success-message">
            Your payment has been processed successfully. Your meter has been credited.
          </p>
          
          <div class="payment-details">
            <div class="detail-row">
              <span class="label">Transaction ID:</span>
              <span class="value">{{ paymentResult.transaction.id }}</span>
            </div>
            <div class="detail-row">
              <span class="label">Meter Serial:</span>
              <span class="value">{{ paymentResult.transaction.serial_id }}</span>
            </div>
            <div class="detail-row">
              <span class="label">Amount Paid:</span>
              <span class="value">{{ formatCurrency(paymentResult.transaction.amount, paymentResult.transaction.currency) }}</span>
            </div>
            <div class="detail-row">
              <span class="label">Payment Date:</span>
              <span class="value">{{ formatDate(paymentResult.transaction.created_at) }}</span>
            </div>
          </div>
        </div>

        <!-- Failure State -->
        <div v-else class="failure-state">
          <md-icon class="failure-icon">error</md-icon>
          <h2 class="failure-title">Payment Failed</h2>
          <p class="failure-message">
            {{ paymentResult.verification?.error || 'Your payment could not be processed. Please try again.' }}
          </p>
          
          <div v-if="paymentResult.transaction" class="payment-details">
            <div class="detail-row">
              <span class="label">Transaction ID:</span>
              <span class="value">{{ paymentResult.transaction.id }}</span>
            </div>
            <div class="detail-row">
              <span class="label">Meter Serial:</span>
              <span class="value">{{ paymentResult.transaction.serial_id }}</span>
            </div>
            <div class="detail-row">
              <span class="label">Amount:</span>
              <span class="value">{{ formatCurrency(paymentResult.transaction.amount, paymentResult.transaction.currency) }}</span>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
          <md-button 
            v-if="!paymentResult.success"
            class="md-raised md-primary"
            @click="retryPayment"
          >
            Try Again
          </md-button>
          <md-button 
            class="md-raised"
            @click="goHome"
          >
            {{ paymentResult.success ? 'Done' : 'Cancel' }}
          </md-button>
        </div>
      </div>

      <div v-else class="error-state">
        <md-icon class="error-icon">warning</md-icon>
        <h2 class="error-title">Unable to Verify Payment</h2>
        <p class="error-message">
          We couldn't verify your payment status. Please contact support if you have any questions.
        </p>
        <div class="action-buttons">
          <md-button class="md-raised md-primary" @click="retryVerification">
            Retry Verification
          </md-button>
          <md-button class="md-raised" @click="goHome">
            Go Back
          </md-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { PublicPaymentService } from "../../services/PublicPaymentService"
import { notify } from "@/mixins/notify"

export default {
  name: "PublicPaymentResult",
  mixins: [notify],
  data() {
    return {
      paymentService: new PublicPaymentService(),
      loading: true,
      paymentResult: null,
      companyName: '',
    }
  },
  computed: {
    companyHash() {
      return this.$route.params.companyHash
    },
    companyId() {
      return this.$route.params.companyId
    },
    reference() {
      const urlParams = new URLSearchParams(window.location.search)
      return urlParams.get('reference')
    },
  },
  async mounted() {
    await this.loadCompanyInfo()
    await this.verifyPayment()
  },
  methods: {
    async loadCompanyInfo() {
      try {
        const response = await this.paymentService.getCompanyInfo(this.companyHash, this.companyId)
        this.companyName = response.company.name
      } catch (error) {
        console.error('Failed to load company info:', error)
      }
    },
    async verifyPayment() {
      if (!this.reference) {
        this.loading = false
        return
      }

      try {
        this.loading = true
        const response = await this.paymentService.getPaymentResult(
          this.companyHash,
          this.companyId,
          this.reference
        )
        this.paymentResult = response
      } catch (error) {
        console.error('Payment verification error:', error)
        this.paymentResult = null
      } finally {
        this.loading = false
      }
    },
    async retryVerification() {
      await this.verifyPayment()
    },
    retryPayment() {
      // Navigate back to payment form
      this.$router.push({
        name: '/paystack/public/payment',
        params: {
          companyHash: this.companyHash,
          companyId: this.companyId,
        },
      })
    },
    goHome() {
      // Navigate back to payment form or close window
      if (window.opener) {
        window.close()
      } else {
        this.$router.push({
          name: '/paystack/public/payment',
          params: {
            companyHash: this.companyHash,
            companyId: this.companyId,
          },
        })
      }
    },
    formatCurrency(amount, currency) {
      const formatter = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2,
      })
      return formatter.format(amount)
    },
    formatDate(dateString) {
      const date = new Date(dateString)
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    },
  },
}
</script>

<style scoped>
.welcome {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  max-width: 720px;
  text-align: center;
  margin-top: 2rem;
}

.title {
  font-size: 2rem;
  font-weight: bold;
  margin-bottom: 1rem;
}

.title-2 {
  font-size: 1rem;
  font-weight: bold;
  margin-bottom: 1rem;
  margin-top: 1rem;
}

.highlight {
  color: #ffc107;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 2rem;
}

.loading-container p {
  margin-top: 1rem;
  font-size: 16px;
  color: #666;
}

.result-container {
  width: 100%;
  max-width: 500px;
}

.success-state,
.failure-state,
.error-state {
  padding: 2rem;
  border-radius: 8px;
  margin-bottom: 2rem;
}

.success-state {
  background-color: #f1f8e9;
  border: 1px solid #4caf50;
}

.failure-state {
  background-color: #ffebee;
  border: 1px solid #f44336;
}

.error-state {
  background-color: #fff3e0;
  border: 1px solid #ff9800;
}

.success-icon {
  font-size: 4rem !important;
  color: #4caf50;
  margin-bottom: 1rem;
}

.failure-icon {
  font-size: 4rem !important;
  color: #f44336;
  margin-bottom: 1rem;
}

.error-icon {
  font-size: 4rem !important;
  color: #ff9800;
  margin-bottom: 1rem;
}

.success-title,
.failure-title,
.error-title {
  margin: 0 0 1rem 0;
  font-size: 1.5rem;
}

.success-title {
  color: #2e7d32;
}

.failure-title {
  color: #c62828;
}

.error-title {
  color: #ef6c00;
}

.success-message,
.failure-message,
.error-message {
  margin: 0 0 2rem 0;
  font-size: 16px;
  line-height: 1.5;
}

.payment-details {
  background-color: white;
  border-radius: 4px;
  padding: 1rem;
  margin-bottom: 2rem;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #eee;
}

.detail-row:last-child {
  border-bottom: none;
}

.label {
  font-weight: 500;
  color: #666;
}

.value {
  font-weight: 600;
  color: #333;
}

.action-buttons {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

.action-buttons .md-button {
  min-width: 120px;
}
</style>
