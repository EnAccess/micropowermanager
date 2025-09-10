<template>
  <div class="welcome">
    <div class="content">
      <div class="title">
        <span class="title highlight">MicroPowerManager</span>
      </div>
      <div class="title-2">Payment Result - {{ companyName }}</div>

      <div v-if="loading" class="loading-container">
        <md-progress-spinner
          md-diameter="60"
          md-stroke="4"
        ></md-progress-spinner>
        <p>Verifying your payment...</p>
      </div>

      <div v-else-if="paymentResult" class="result-container">
        <!-- Success State -->
        <div v-if="paymentResult.success" class="success-state">
          <md-icon class="success-icon">check_circle</md-icon>
          <h2 class="success-title">Payment Successful!</h2>
          <p class="success-message">
            Your payment has been processed successfully. Your meter has been
            credited.
          </p>

          <div class="payment-details">
            <div class="detail-row">
              <span class="label">Transaction ID:</span>
              <span class="value">{{ paymentResult.transaction.id }}</span>
            </div>
            <div class="detail-row">
              <span class="label">Meter Serial:</span>
              <span class="value">
                {{ paymentResult.transaction.serial_id }}
              </span>
            </div>
            <div class="detail-row">
              <span class="label">Amount Paid:</span>
              <span class="value">
                {{
                  formatCurrency(
                    paymentResult.transaction.amount,
                    paymentResult.transaction.currency,
                  )
                }}
              </span>
            </div>
            <div class="detail-row">
              <span class="label">Payment Date:</span>
              <span class="value">
                {{ formatDate(paymentResult.transaction.created_at) }}
              </span>
            </div>
          </div>

          <!-- Energy Token Information -->
          <div class="token-container">
            <div class="token-header">
              <h3 class="token-title">Energy Token</h3>
              <div class="token-status">
                <span
                  class="status-indicator"
                  :class="getTokenStatusClass(paymentResult.token_status)"
                >
                  {{ formatTokenStatus(paymentResult.token_status) }}
                </span>
                <md-button
                  v-if="paymentResult.token_status !== 'generated'"
                  class="md-icon-button refresh-button"
                  @click="refreshTokenStatus"
                  :disabled="refreshing"
                >
                  <md-icon v-if="!refreshing">refresh</md-icon>
                  <md-progress-spinner
                    v-else
                    md-diameter="20"
                    md-stroke="2"
                  ></md-progress-spinner>
                </md-button>
              </div>
            </div>

            <!-- Token Details (only show when token is generated) -->
            <div
              v-if="
                paymentResult.token &&
                paymentResult.token_status === 'generated'
              "
              class="token-details"
            >
              <div class="token-row">
                <span class="token-label">Token Code:</span>
                <span class="token-value token-code">
                  {{ paymentResult.token.token }}
                </span>
              </div>
              <div class="token-row">
                <span class="token-label">Energy Amount:</span>
                <span class="token-value">
                  {{ paymentResult.token.token_amount }}
                  {{ paymentResult.token.token_unit }}
                </span>
              </div>
              <div class="token-row">
                <span class="token-label">Token Type:</span>
                <span class="token-value">
                  {{ formatTokenType(paymentResult.token.token_type) }}
                </span>
              </div>
            </div>

            <!-- Status Messages -->
            <div v-else class="token-status-message">
              <div
                v-if="paymentResult.token_status === 'processing'"
                class="processing-message"
              >
                <md-icon class="processing-icon">hourglass_empty</md-icon>
                <p>
                  Your energy token is being generated. Please wait a moment and
                  refresh to check the status.
                </p>
              </div>
              <div
                v-else-if="paymentResult.token_status === 'failed'"
                class="failed-message"
              >
                <md-icon class="failed-icon">error</md-icon>
                <p>
                  Token generation failed. Please try refreshing or contact
                  support if the issue persists.
                </p>
              </div>
              <div
                v-else-if="paymentResult.token_status === 'pending'"
                class="pending-message"
              >
                <md-icon class="pending-icon">schedule</md-icon>
                <p>
                  Token generation is pending. Please refresh to check the
                  status.
                </p>
              </div>
            </div>

            <!-- Instructions (only show when token is generated) -->
            <div
              v-if="
                paymentResult.token &&
                paymentResult.token_status === 'generated'
              "
              class="token-instructions"
            >
              <p class="instruction-text">
                <strong>Instructions:</strong>
                Enter this token code into your meter to receive your energy
                credit.
              </p>
            </div>
          </div>
        </div>

        <!-- Failure State -->
        <div v-else class="failure-state">
          <md-icon class="failure-icon">error</md-icon>
          <h2 class="failure-title">Payment Failed</h2>
          <p class="failure-message">
            {{
              paymentResult.verification?.error ||
              "Your payment could not be processed. Please try again."
            }}
          </p>

          <div v-if="paymentResult.transaction" class="payment-details">
            <div class="detail-row">
              <span class="label">Transaction ID:</span>
              <span class="value">{{ paymentResult.transaction.id }}</span>
            </div>
            <div class="detail-row">
              <span class="label">Meter Serial:</span>
              <span class="value">
                {{ paymentResult.transaction.serial_id }}
              </span>
            </div>
            <div class="detail-row">
              <span class="label">Amount:</span>
              <span class="value">
                {{
                  formatCurrency(
                    paymentResult.transaction.amount,
                    paymentResult.transaction.currency,
                  )
                }}
              </span>
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
          <md-button class="md-raised" @click="goHome">
            {{ paymentResult.success ? "Done" : "Cancel" }}
          </md-button>
        </div>
      </div>

      <div v-else class="error-state">
        <md-icon class="error-icon">warning</md-icon>
        <h2 class="error-title">Unable to Verify Payment</h2>
        <p class="error-message">
          We couldn't verify your payment status. Please contact support if you
          have any questions.
        </p>
        <div class="action-buttons">
          <md-button class="md-raised md-primary" @click="retryVerification">
            Retry Verification
          </md-button>
          <md-button class="md-raised" @click="goHome">Go Back</md-button>
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
      companyName: "",
      refreshing: false,
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
      return urlParams.get("reference")
    },
  },
  async mounted() {
    await this.loadCompanyInfo()
    await this.verifyPayment()
  },
  methods: {
    async loadCompanyInfo() {
      try {
        const response = await this.paymentService.getCompanyInfo(
          this.companyHash,
          this.companyId,
        )
        this.companyName = response.company.name
      } catch (error) {
        console.error("Failed to load company info:", error)
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
          this.reference,
        )
        this.paymentResult = response
      } catch (error) {
        console.error("Payment verification error:", error)
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
        name: "/paystack/public/payment",
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
          name: "/paystack/public/payment",
          params: {
            companyHash: this.companyHash,
            companyId: this.companyId,
          },
        })
      }
    },
    formatCurrency(amount, currency) {
      const formatter = new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: currency,
        minimumFractionDigits: 2,
      })
      return formatter.format(amount)
    },
    formatDate(dateString) {
      const date = new Date(dateString)
      return date.toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      })
    },
    formatTokenType(tokenType) {
      const typeMap = {
        energy: "Energy Credit",
        time: "Time Credit",
      }
      return typeMap[tokenType] || tokenType
    },
    formatTokenStatus(status) {
      const statusMap = {
        generated: "Generated",
        processing: "Processing",
        failed: "Failed",
        pending: "Pending",
      }
      return statusMap[status] || status
    },
    getTokenStatusClass(status) {
      return `status-${status}`
    },
    async refreshTokenStatus() {
      if (this.refreshing) return

      try {
        this.refreshing = true
        await this.verifyPayment()
        this.notifySuccess("Token status updated")
      } catch (error) {
        console.error("Failed to refresh token status:", error)
        this.notifyError("Failed to refresh token status")
      } finally {
        this.refreshing = false
      }
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

.token-container {
  background-color: #e8f5e8;
  border: 1px solid #4caf50;
  border-radius: 8px;
  padding: 1.5rem;
  margin-top: 1rem;
}

.token-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.token-title {
  margin: 0;
  font-size: 1.25rem;
  color: #2e7d32;
  font-weight: 600;
}

.token-status {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.status-indicator {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-generated {
  background-color: #4caf50;
  color: white;
}

.status-processing {
  background-color: #ff9800;
  color: white;
}

.status-failed {
  background-color: #f44336;
  color: white;
}

.status-pending {
  background-color: #9e9e9e;
  color: white;
}

.refresh-button {
  min-width: 40px !important;
  width: 40px !important;
  height: 40px !important;
}

.token-details {
  background-color: white;
  border-radius: 4px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.token-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #eee;
}

.token-row:last-child {
  border-bottom: none;
}

.token-label {
  font-weight: 500;
  color: #666;
}

.token-value {
  font-weight: 600;
  color: #333;
}

.token-code {
  font-family: "Courier New", monospace;
  font-size: 1.1rem;
  background-color: #f5f5f5;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  border: 1px solid #ddd;
}

.token-instructions {
  background-color: #fff3cd;
  border: 1px solid #ffeaa7;
  border-radius: 4px;
  padding: 1rem;
}

.instruction-text {
  margin: 0;
  font-size: 14px;
  color: #856404;
  line-height: 1.4;
}

.token-status-message {
  background-color: white;
  border-radius: 4px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.processing-message,
.failed-message,
.pending-message {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.processing-icon {
  color: #ff9800 !important;
  font-size: 1.5rem !important;
}

.failed-icon {
  color: #f44336 !important;
  font-size: 1.5rem !important;
}

.pending-icon {
  color: #9e9e9e !important;
  font-size: 1.5rem !important;
}

.processing-message p,
.failed-message p,
.pending-message p {
  margin: 0;
  font-size: 14px;
  line-height: 1.4;
  color: #333;
}
</style>
