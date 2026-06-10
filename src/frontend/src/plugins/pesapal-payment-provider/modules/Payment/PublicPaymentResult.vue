<template>
  <div class="checkout">
    <div class="checkout__inner">
      <header class="brand">
        <img class="brand__logo" :src="mpmLogo" alt="MicroPowerManager" />
        <h1 class="brand__name">MicroPowerManager</h1>
        <p class="brand__sub">
          Payment result
          <span v-if="companyName">&nbsp;·&nbsp;{{ companyName }}</span>
        </p>
      </header>

      <section class="sheet">
        <div v-if="loading" class="verifying">
          <md-progress-spinner
            md-mode="indeterminate"
            :md-diameter="48"
            :md-stroke="3"
          ></md-progress-spinner>
          <p>Verifying your payment…</p>
        </div>

        <template v-else-if="paymentResult">
          <div
            class="status"
            :class="paymentResult.success ? 'status--success' : 'status--fail'"
          >
            <span class="status__mark">
              <md-icon>
                {{ paymentResult.success ? "check" : "close" }}
              </md-icon>
            </span>
            <h2 class="status__title">
              {{
                paymentResult.success ? "Payment Successful" : "Payment Failed"
              }}
            </h2>
            <p class="status__message">
              {{
                paymentResult.success
                  ? successMessage
                  : paymentResult.verification?.status_description ||
                    paymentResult.verification?.error ||
                    "Your payment could not be processed. Please try again."
              }}
            </p>
          </div>

          <div
            v-if="paymentResult.success || paymentResult.transaction"
            class="summary"
          >
            <div class="row">
              <span class="row__label">Transaction ID</span>
              <span class="row__value">{{ paymentResult.transaction.id }}</span>
            </div>
            <div v-if="!isNonPaygoInstallment" class="row">
              <span class="row__label">{{ deviceLabel }}</span>
              <span class="row__value">
                {{ paymentResult.transaction.serial_id }}
              </span>
            </div>
            <div v-if="!isNonPaygoInstallment" class="row">
              <span class="row__label">Device Type</span>
              <span class="row__value">{{ deviceTypeName }}</span>
            </div>
            <div v-if="isNonPaygoInstallment" class="row">
              <span class="row__label">Payment Type</span>
              <span class="row__value">Installment</span>
            </div>
            <div class="row">
              <span class="row__label">
                {{ paymentResult.success ? "Amount Paid" : "Amount" }}
              </span>
              <span class="row__value row__value--amount">
                {{
                  formatCurrency(
                    paymentResult.transaction.amount,
                    paymentResult.transaction.currency,
                  )
                }}
              </span>
            </div>
            <div v-if="paymentResult.success" class="row">
              <span class="row__label">Payment Date</span>
              <span class="row__value">
                {{ formatDate(paymentResult.transaction.created_at) }}
              </span>
            </div>
          </div>

          <div
            v-if="paymentResult.success && !isNonPaygoInstallment"
            class="token"
          >
            <div class="token__head">
              <span class="token__title">{{ tokenTitle }}</span>
              <div class="token__status">
                <span
                  class="badge"
                  :class="getTokenStatusClass(paymentResult.token_status)"
                >
                  {{ formatTokenStatus(paymentResult.token_status) }}
                </span>
                <md-button
                  v-if="paymentResult.token_status !== 'generated'"
                  class="md-icon-button token__refresh"
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

            <template
              v-if="
                paymentResult.token &&
                paymentResult.token_status === 'generated'
              "
            >
              <div class="token__code">{{ paymentResult.token.token }}</div>
              <div class="token__meta">
                <div class="row">
                  <span class="row__label">{{ creditAmountLabel }}</span>
                  <span class="row__value">
                    {{ paymentResult.token.token_amount }}
                    {{ paymentResult.token.token_unit }}
                  </span>
                </div>
                <div class="row">
                  <span class="row__label">Token Type</span>
                  <span class="row__value">
                    {{ formatTokenType(paymentResult.token.token_type) }}
                  </span>
                </div>
              </div>
              <p class="token__instructions">
                {{ tokenInstructions }}
              </p>
            </template>

            <p v-else class="token__pending">
              <md-icon>{{ pendingIcon }}</md-icon>
              <span>{{ pendingMessage }}</span>
            </p>
          </div>

          <div class="actions">
            <md-button
              v-if="!paymentResult.success"
              class="cta md-raised md-primary"
              @click="retryPayment"
            >
              Try Again
            </md-button>
            <md-button
              :class="
                paymentResult.success ? 'cta md-raised md-primary' : 'ghost'
              "
              @click="goHome"
            >
              {{ paymentResult.success ? "Done" : "Cancel" }}
            </md-button>
          </div>
        </template>

        <template v-else>
          <div class="status status--warn">
            <span class="status__mark">
              <md-icon>priority_high</md-icon>
            </span>
            <h2 class="status__title">Unable to Verify Payment</h2>
            <p class="status__message">
              We couldn't verify your payment status. Please contact support if
              you have any questions.
            </p>
          </div>
          <div class="actions">
            <md-button
              class="cta md-raised md-primary"
              @click="retryVerification"
            >
              Retry Verification
            </md-button>
            <md-button class="ghost" @click="goHome">Go Back</md-button>
          </div>
        </template>
      </section>
    </div>
  </div>
</template>

<script>
import { PublicPaymentService } from "../../services/PublicPaymentService.js"

import mpmLogo from "@/assets/images/mpmlogo_raw.png"
import { notify } from "@/mixins/notify.js"

export default {
  name: "PublicPaymentResult",
  mixins: [notify],
  data() {
    return {
      mpmLogo,
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
      return (
        this.$route.query.ct || sessionStorage.getItem("pesapal_company_token")
      )
    },
    reference() {
      const urlParams = new URLSearchParams(window.location.search)
      return urlParams.get("reference") || this.$route.query.reference
    },
    paymentType() {
      return this.paymentResult?.transaction?.payment_type
    },
    isInstallment() {
      return this.paymentType === "deferred_payment"
    },
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
    if (this.companyIdToken) {
      sessionStorage.setItem("pesapal_company_token", this.companyIdToken)
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
      const ct =
        this.companyIdToken || sessionStorage.getItem("pesapal_company_token")
      this.$router.push({
        name: "/pesapal/public/payment",
        params: {
          companyHash: this.companyHash,
        },
        query: ct ? { ct } : {},
      })
    },
    goHome() {
      sessionStorage.removeItem("pesapal_company_token")

      if (window.opener) {
        window.close()
      } else {
        const ct =
          this.companyIdToken || sessionStorage.getItem("pesapal_company_token")
        this.$router.push({
          name: "/pesapal/public/payment",
          params: {
            companyHash: this.companyHash,
          },
          query: ct ? { ct } : {},
        })
      }
    },
    formatCurrency(amount, currency) {
      try {
        const formatter = new Intl.NumberFormat("en-US", {
          style: "currency",
          currency: currency,
          minimumFractionDigits: 2,
        })
        return formatter.format(amount)
      } catch (error) {
        return `${currency || "KES"} ${amount}`
      }
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
      return `badge--${status}`
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

<style scoped lang="scss">
.checkout {
  min-height: 100vh;
  padding: 3.5rem 1.25rem;
  background-color: $brand-background;
}

.checkout__inner {
  width: 100%;
  max-width: 416px;
  margin: 0 auto;
}

.brand {
  text-align: center;
  margin-bottom: 1.75rem;
}

.brand__logo {
  display: block;
  width: 116px;
  height: 60px;
  margin: 0 auto 0.75rem;
}

.brand__name {
  margin: 0;
  font-size: 1.4rem;
  font-weight: 800;
  letter-spacing: -0.01em;
  color: $brand-primary-dark;
}

.brand__sub {
  margin: 0.3rem 0 0;
  font-size: 0.85rem;
  color: #8a93a0;
}

.sheet {
  border-top: 1px solid #e6ebef;
  padding-top: 1.75rem;
}

.verifying {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 2.5rem 0;

  p {
    margin: 1rem 0 0;
    font-size: 0.9rem;
    color: #6b7280;
  }
}

.status {
  text-align: center;
  margin-bottom: 0.5rem;
}

.status__mark {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 68px;
  height: 68px;
  border-radius: 50%;
  margin-bottom: 0.85rem;

  .md-icon {
    width: 38px;
    min-width: 38px;
    height: 38px;
    font-size: 38px !important;
  }
}

.status__title {
  margin: 0 0 0.4rem;
  font-size: 1.3rem;
  font-weight: 800;
  letter-spacing: -0.01em;
}

.status__message {
  margin: 0;
  font-size: 0.875rem;
  line-height: 1.6;
  color: #6b7280;
}

.status--success {
  .status__mark {
    background-color: rgba($brand-accent, 0.18);
  }
  .status__mark .md-icon {
    color: $brand-accent-dark !important;
  }
  .status__title {
    color: $brand-accent-dark;
  }
}

.status--fail {
  .status__mark {
    background-color: rgba(#d64545, 0.13);
  }
  .status__mark .md-icon {
    color: #d64545 !important;
  }
  .status__title {
    color: #c1372f;
  }
}

.status--warn {
  .status__mark {
    background-color: rgba($brand-secondary, 0.22);
  }
  .status__mark .md-icon {
    color: $brand-secondary-dark !important;
  }
  .status__title {
    color: $brand-secondary-dark;
  }
}

.summary,
.token__meta {
  margin-top: 1.5rem;
}

.row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 1rem;
  padding: 0.7rem 0;
  border-bottom: 1px solid #eef1f4;
}

.row:last-child {
  border-bottom: none;
}

.row__label {
  flex-shrink: 0;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #8a93a0;
}

.row__value {
  font-size: 0.875rem;
  font-weight: 600;
  color: $brand-primary-dark;
  text-align: right;
  word-break: break-word;
}

.row__value--amount {
  font-size: 1rem;
  font-weight: 800;
}

.token {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e6ebef;
}

.token__head {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.token__title {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #8a93a0;
}

.token__status {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.badge {
  padding: 0.2rem 0.6rem;
  border-radius: 20px;
  font-size: 0.65rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: $brand-white;
}

.badge--generated {
  background-color: $brand-accent-dark;
}

.badge--processing {
  background-color: $brand-secondary-dark;
}

.badge--failed {
  background-color: #d64545;
}

.badge--pending {
  background-color: #9aa3af;
}

.token__refresh {
  width: 32px;
  height: 32px;
  min-width: 32px;
  margin: 0;
}

.token__code {
  margin-top: 0.85rem;
  padding: 1rem;
  font-family: "Courier New", monospace;
  font-size: 1.4rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-align: center;
  color: $brand-primary-dark;
  background-color: $brand-white;
  border: 1px dashed rgba($brand-primary, 0.4);
  border-radius: 10px;
  word-break: break-all;
}

.token__instructions {
  margin: 1rem 0 0;
  font-size: 0.8rem;
  line-height: 1.6;
  color: #6b7280;
}

.token__pending {
  margin: 1rem 0 0;
  font-size: 0.825rem;
  line-height: 1.6;
  color: #6b7280;

  .md-icon {
    margin: 0 6px 0 0;
    vertical-align: top;
    width: 19px;
    min-width: 19px;
    height: 19px;
    color: $brand-secondary-dark !important;
    font-size: 19px !important;
  }
}

.actions {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  margin-top: 1.75rem;
}

.cta {
  width: 100%;
  height: 52px;
  margin: 0;
  border-radius: 9px;
  font-size: 0.95rem;
  font-weight: 600;
  letter-spacing: 0.01em;
  text-transform: none;
}

.ghost {
  width: 100%;
  height: 48px;
  margin: 0;
  border-radius: 9px;
  border: 1px solid $brand-primary;
  font-size: 0.9rem;
  font-weight: 600;
  text-transform: none;
  color: $brand-primary;
}
</style>
