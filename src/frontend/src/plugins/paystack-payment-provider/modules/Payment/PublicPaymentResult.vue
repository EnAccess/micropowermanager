<template>
  <checkout-page subtitle="Payment result" :company-name="companyName">
    <!-- Verifying -->
    <div v-if="loading" class="text-center">
      <md-progress-spinner
        md-mode="indeterminate"
        :md-diameter="48"
        :md-stroke="3"
      ></md-progress-spinner>
      <p>Verifying your payment…</p>
    </div>

    <template v-else-if="paymentResult">
      <!-- Status header -->
      <div class="text-center">
        <md-icon
          class="md-size-2x"
          :style="{ color: paymentResult.success ? 'green' : 'red' }"
        >
          {{ paymentResult.success ? "check_circle_outline" : "cancel" }}
        </md-icon>
        <div class="md-title">
          {{ paymentResult.success ? "Payment Successful" : "Payment Failed" }}
        </div>
        <p>
          {{
            paymentResult.success
              ? successMessage
              : paymentResult.verification?.error ||
                "Your payment could not be processed. Please try again."
          }}
        </p>
      </div>

      <!-- Transaction summary -->
      <template v-if="paymentResult.success || paymentResult.transaction">
        <div class="md-layout">
          <div class="md-layout-item md-subheader">Transaction ID</div>
          <div class="md-layout-item md-subheader">
            {{ paymentResult.transaction.id }}
          </div>
        </div>
        <md-divider />
        <div v-if="!isNonPaygoInstallment" class="md-layout">
          <div class="md-layout-item md-subheader">{{ deviceLabel }}</div>
          <div class="md-layout-item md-subheader">
            {{ paymentResult.transaction.serial_id }}
          </div>
        </div>
        <md-divider v-if="!isNonPaygoInstallment" />
        <div v-if="!isNonPaygoInstallment" class="md-layout">
          <div class="md-layout-item md-subheader">Device Type</div>
          <div class="md-layout-item md-subheader">{{ deviceTypeName }}</div>
        </div>
        <div v-if="isNonPaygoInstallment" class="md-layout">
          <div class="md-layout-item md-subheader">Payment Type</div>
          <div class="md-layout-item md-subheader">Installment</div>
        </div>
        <md-divider />
        <div class="md-layout">
          <div class="md-layout-item md-subheader">
            {{ paymentResult.success ? "Amount Paid" : "Amount" }}
          </div>
          <div class="md-layout-item md-subheader">
            {{
              formatCurrency(
                paymentResult.transaction.amount,
                paymentResult.transaction.currency,
              )
            }}
          </div>
        </div>
        <md-divider v-if="paymentResult.success" />
        <div v-if="paymentResult.success" class="md-layout">
          <div class="md-layout-item md-subheader">Payment Date</div>
          <div class="md-layout-item md-subheader">
            {{ formatDate(paymentResult.transaction.created_at) }}
          </div>
        </div>
      </template>

      <!-- Token -->
      <template v-if="paymentResult.success && !isNonPaygoInstallment">
        <md-divider />
        <div class="md-layout">
          <div class="md-layout-item md-subheader">{{ tokenTitle }}</div>
          <div class="md-layout-item md-subheader">
            {{ formatTokenStatus(paymentResult.token_status) }}
            <md-button
              v-if="paymentResult.token_status !== 'generated'"
              class="md-icon-button md-dense"
              @click="refreshTokenStatus"
              :disabled="refreshing"
            >
              <md-icon v-if="!refreshing">refresh</md-icon>
              <md-progress-spinner
                v-else
                md-mode="indeterminate"
                :md-diameter="18"
                :md-stroke="2"
              ></md-progress-spinner>
            </md-button>
          </div>
        </div>

        <!-- Generated token -->
        <template
          v-if="
            paymentResult.token && paymentResult.token_status === 'generated'
          "
        >
          <div class="token-code">{{ paymentResult.token.token }}</div>
          <div class="md-layout">
            <div class="md-layout-item md-subheader">
              {{ creditAmountLabel }}
            </div>
            <div class="md-layout-item md-subheader">
              {{ paymentResult.token.token_amount }}
              {{ paymentResult.token.token_unit }}
            </div>
          </div>
          <md-divider />
          <div class="md-layout">
            <div class="md-layout-item md-subheader">Token Type</div>
            <div class="md-layout-item md-subheader">
              {{ formatTokenType(paymentResult.token.token_type) }}
            </div>
          </div>
          <p class="md-caption">{{ tokenInstructions }}</p>
        </template>

        <!-- Pending / processing / failed -->
        <p v-else class="md-caption">
          <md-icon>{{ pendingIcon }}</md-icon>
          {{ pendingMessage }}
        </p>
      </template>

      <!-- Actions -->
      <md-button
        v-if="!paymentResult.success"
        class="md-raised md-primary"
        style="width: 100%"
        @click="retryPayment"
      >
        Try Again
      </md-button>
      <md-button
        :class="{ 'md-raised': paymentResult.success }"
        class="md-primary"
        style="width: 100%"
        @click="goHome"
      >
        {{ paymentResult.success ? "Done" : "Cancel" }}
      </md-button>
    </template>

    <!-- Unable to verify -->
    <template v-else>
      <div class="text-center">
        <md-icon class="md-size-2x" style="color: goldenrod">
          priority_high
        </md-icon>
        <div class="md-title">Unable to Verify Payment</div>
        <p>
          We couldn't verify your payment status. Please contact support if you
          have any questions.
        </p>
      </div>
      <md-button
        class="md-raised md-primary"
        style="width: 100%"
        @click="retryVerification"
      >
        Retry Verification
      </md-button>
      <md-button class="md-primary" style="width: 100%" @click="goHome">
        Go Back
      </md-button>
    </template>
  </checkout-page>
</template>

<script>
import { PublicPaymentService } from "../../services/PublicPaymentService.js"

import { notify } from "@/mixins/notify.js"
import CheckoutPage from "@/shared/CheckoutPage.vue"

export default {
  name: "PublicPaymentResult",
  mixins: [notify],
  components: { CheckoutPage },
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
    companyIdToken() {
      // Try to get from query params first, fallback to sessionStorage
      return (
        this.$route.query.ct || sessionStorage.getItem("paystack_company_token")
      )
    },
    reference() {
      const urlParams = new URLSearchParams(window.location.search)
      return urlParams.get("reference")
    },
    paymentType() {
      return this.paymentResult?.transaction?.payment_type
    },
    isInstallment() {
      return this.paymentType === "deferred_payment"
    },
    // Non-paygo installments have no device_serial — no device or token to show
    isNonPaygoInstallment() {
      return this.isInstallment && !this.paymentResult?.transaction?.serial_id
    },
    deviceType() {
      return this.paymentResult?.transaction?.device_type || "meter"
    },
    isSHS() {
      return (
        this.deviceType === "solar_home_system" || this.deviceType === "shs"
      )
    },
    deviceTypeName() {
      return this.isSHS ? "Solar Home System" : "Meter"
    },
    deviceLabel() {
      return this.isSHS ? "SHS Serial" : "Meter Serial"
    },
    tokenTitle() {
      return this.isSHS ? "Appliance Token" : "Energy Token"
    },
    tokenTypeText() {
      return this.isSHS ? "appliance token" : "energy token"
    },
    creditAmountLabel() {
      return this.isSHS ? "Token Amount" : "Energy Amount"
    },
    successMessage() {
      if (this.isNonPaygoInstallment) {
        return "Your payment has been processed successfully. Your account balance will be updated shortly."
      }

      if (this.isSHS) {
        return "Your payment has been processed successfully. Your appliance token will be generated shortly."
      }
      return "Your payment has been processed successfully. Your meter has been credited."
    },
    tokenInstructions() {
      if (this.isSHS) {
        return "Enter this token code into your Solar Home System device to activate your appliance."
      }
      return "Enter this token code into your meter to receive your energy credit."
    },
    pendingIcon() {
      const icons = {
        processing: "hourglass_empty",
        failed: "error_outline",
        pending: "schedule",
      }
      return icons[this.paymentResult?.token_status] || "schedule"
    },
    pendingMessage() {
      const status = this.paymentResult?.token_status
      if (status === "processing") {
        return `Your ${this.tokenTypeText} is being generated. Please wait a moment, then refresh to check the status.`
      }
      if (status === "failed") {
        return "Token generation failed. Please refresh, or contact support if the issue persists."
      }
      return "Token generation is pending. Please refresh to check the status."
    },
  },
  async mounted() {
    // Store company token in sessionStorage if present (for refreshes)
    if (this.companyIdToken) {
      sessionStorage.setItem("paystack_company_token", this.companyIdToken)
    }
    await this.loadCompanyInfo()
    await this.verifyPayment()
  },
  methods: {
    async loadCompanyInfo() {
      try {
        const response = await this.paymentService.getCompanyInfo(
          this.companyHash,
          this.companyIdToken,
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
          this.companyIdToken,
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
      // Navigate back to payment form with company token
      const ct =
        this.companyIdToken || sessionStorage.getItem("paystack_company_token")
      this.$router.push({
        name: "/paystack/public/payment",
        params: {
          companyHash: this.companyHash,
        },
        query: ct ? { ct } : {},
      })
    },
    goHome() {
      // Clean up sessionStorage when user completes the flow
      sessionStorage.removeItem("paystack_company_token")

      // Navigate back to payment form or close window
      if (window.opener) {
        window.close()
      } else {
        const ct =
          this.companyIdToken ||
          sessionStorage.getItem("paystack_company_token")
        this.$router.push({
          name: "/paystack/public/payment",
          params: {
            companyHash: this.companyHash,
          },
          query: ct ? { ct } : {},
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
    async refreshTokenStatus() {
      if (this.refreshing) return

      try {
        this.refreshing = true
        await this.verifyPayment()
        this.alertNotify("success", "Token status updated")
      } catch (error) {
        console.error("Failed to refresh token status:", error)
        this.alertNotify("error", "Failed to refresh token status")
      } finally {
        this.refreshing = false
      }
    },
  },
}
</script>

<style scoped lang="scss">
// The generated meter/appliance token — deliberately prominent so customers
// can read it off the screen and type it into their device.
.token-code {
  margin: 1rem 0;
  padding: 1rem;
  font-family: monospace;
  font-size: 1.4rem;
  text-align: center;
  border: 1px dashed rgba(0, 0, 0, 0.3);
  border-radius: 4px;
  word-break: break-all;
}
</style>
