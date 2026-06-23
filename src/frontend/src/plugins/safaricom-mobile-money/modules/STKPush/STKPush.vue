<template>
  <div class="stk-push">
    <md-card class="panel">
      <div class="panel__head">
        <h2 class="panel__title">Initiate M-PESA Payment</h2>
        <p class="panel__subtitle">
          Sends an STK Push to the customer's phone. They confirm by entering
          their M-PESA PIN — no redirect, the page polls Daraja until the
          transaction resolves.
        </p>
      </div>

      <!-- Step 1: Collect details -->
      <md-card-content v-if="stage === 'form'">
        <form
          @submit.prevent="sendStkPush"
          data-vv-scope="STK-Form"
          class="form"
        >
          <div class="md-layout md-gutter">
            <div class="md-layout-item md-size-50 md-small-size-100">
              <div class="field">
                <label class="field__label" for="deviceType">Device type</label>
                <md-field>
                  <md-select
                    id="deviceType"
                    name="deviceType"
                    v-model="form.deviceType"
                    @md-selected="onDeviceTypeChange"
                  >
                    <md-option value="meter">Meter</md-option>
                    <md-option value="solar_home_system">Solar Home System</md-option>
                  </md-select>
                </md-field>
              </div>
            </div>

            <div class="md-layout-item md-size-50 md-small-size-100">
              <div class="field">
                <label class="field__label" for="deviceSerial">
                  {{ serialLabel }}
                </label>
                <md-field
                  :class="{
                    'md-invalid': errors.has('STK-Form.deviceSerial'),
                  }"
                >
                  <md-input
                    id="deviceSerial"
                    name="deviceSerial"
                    v-model="form.deviceSerial"
                    v-validate="'required|min:3'"
                    placeholder="Enter the serial number"
                    @blur="validateDevice"
                  />
                  <span class="md-error">
                    {{ errors.first("STK-Form.deviceSerial") }}
                  </span>
                </md-field>
                <div v-if="deviceValidation.loading" class="hint hint--muted">
                  <md-progress-spinner
                    md-mode="indeterminate"
                    :md-diameter="14"
                    :md-stroke="2"
                  ></md-progress-spinner>
                  <span>{{ validatingMessage }}</span>
                </div>
                <div
                  v-else-if="deviceValidation.valid === true"
                  class="hint hint--ok"
                >
                  <md-icon>check_circle</md-icon>
                  <span>{{ validMessage }}</span>
                </div>
                <div
                  v-else-if="deviceValidation.valid === false"
                  class="hint hint--error"
                >
                  <md-icon>error</md-icon>
                  <span>{{ invalidMessage }}</span>
                </div>
              </div>
            </div>

            <div class="md-layout-item md-size-50 md-small-size-100">
              <div class="field">
                <label class="field__label" for="phoneNumber">
                  Customer Phone
                </label>
                <md-field
                  :class="{
                    'md-invalid': errors.has('STK-Form.phoneNumber'),
                  }"
                >
                  <md-input
                    id="phoneNumber"
                    name="phoneNumber"
                    v-model="form.phoneNumber"
                    v-validate="'required|min:9'"
                    placeholder="e.g. 0712345678"
                    type="tel"
                  />
                  <span class="md-error">
                    {{ errors.first("STK-Form.phoneNumber") }}
                  </span>
                </md-field>
                <p class="field__note">
                  Accepts 0712…, 712…, +254…, or 254… — normalised server-side.
                </p>
              </div>
            </div>

            <div class="md-layout-item md-size-50 md-small-size-100">
              <div class="field">
                <label class="field__label" for="amount">Amount (KES)</label>
                <md-field
                  :class="{
                    'md-invalid': errors.has('STK-Form.amount'),
                  }"
                >
                  <md-input
                    id="amount"
                    name="amount"
                    v-model.number="form.amount"
                    v-validate="'required|numeric|min_value:1|max_value:150000'"
                    type="number"
                    min="1"
                    step="1"
                    placeholder="e.g. 1500"
                  />
                  <span class="md-error">
                    {{ errors.first("STK-Form.amount") }}
                  </span>
                </md-field>
                <p class="field__note">
                  Daraja accepts whole numbers only — decimals are rounded.
                </p>
              </div>
            </div>

            <div class="md-layout-item md-size-100">
              <div class="field">
                <label class="field__label" for="transactionDesc">
                  Description
                </label>
                <md-field>
                  <md-input
                    id="transactionDesc"
                    name="transactionDesc"
                    v-model="form.transactionDesc"
                    maxlength="50"
                    placeholder="What the customer is paying for"
                  />
                </md-field>
                <p class="field__note">
                  Truncated to 13 chars (Daraja TransactionDesc limit) before
                  sending.
                </p>
              </div>
            </div>
          </div>
        </form>
      </md-card-content>

      <!-- Step 2: Waiting -->
      <md-card-content v-else-if="stage === 'waiting'">
        <div class="waiting">
          <md-progress-spinner
            md-mode="indeterminate"
            :md-diameter="60"
            :md-stroke="4"
          />
          <h3 class="waiting__title">Check the customer's phone</h3>
          <p class="waiting__text">
            An M-PESA prompt has been sent to
            <strong>{{ formattedPhone }}</strong> for
            <strong>KES {{ formatAmount(form.amount) }}</strong>.
          </p>
          <p class="waiting__text waiting__text--muted">
            They need to enter their M-PESA PIN to complete the payment. This
            page polls Safaricom every few seconds and will update
            automatically.
          </p>
          <p class="waiting__countdown">
            Checking again in {{ secondsUntilNextPoll }}s
            <span class="waiting__pollcount">
              · attempt {{ pollAttempts }} of {{ maxPollAttempts }}
            </span>
          </p>
          <md-button class="waiting__cancel" @click="cancelWaiting">
            Cancel and start over
          </md-button>
        </div>
      </md-card-content>

      <!-- Step 3: Result -->
      <md-card-content v-else-if="stage === 'result'">
        <div class="result" :class="`result--${resultMeta.tone}`">
          <md-icon class="result__icon">{{ resultMeta.icon }}</md-icon>
          <h3 class="result__title">{{ resultMeta.title }}</h3>
          <p class="result__body">{{ resultMeta.body }}</p>

          <div class="result__details" v-if="lastStatus">
            <div class="detail-row">
              <span class="detail-label">Reference:</span>
              <span class="detail-value">{{ lastStatus.reference_id }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Phone:</span>
              <span class="detail-value">
                {{ lastStatus.phone_number || formattedPhone }}
              </span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Amount:</span>
              <span class="detail-value">
                KES {{ formatAmount(lastStatus.amount || form.amount) }}
              </span>
            </div>
            <div v-if="lastStatus.mpesa_receipt_number" class="detail-row">
              <span class="detail-label">M-PESA Receipt:</span>
              <span class="detail-value detail-value--strong">
                {{ lastStatus.mpesa_receipt_number }}
              </span>
            </div>
            <div v-if="lastStatus.result_code !== null" class="detail-row">
              <span class="detail-label">Daraja code:</span>
              <span class="detail-value">{{ lastStatus.result_code }}</span>
            </div>
          </div>
        </div>
      </md-card-content>

      <md-progress-bar md-mode="indeterminate" v-if="submitting" />

      <div class="panel__actions" v-if="stage === 'form'">
        <md-button
          class="md-raised md-primary"
          :disabled="submitting"
          @click="sendStkPush"
        >
          Send STK Push
        </md-button>
      </div>
      <div class="panel__actions" v-else-if="stage === 'result'">
        <md-button class="md-raised md-primary" @click="reset">
          Start a new payment
        </md-button>
      </div>
    </md-card>
  </div>
</template>

<script>
import { TransactionService } from "../../services/TransactionService.js"

import { notify } from "@/mixins/notify.js"

// Daraja v3 ResultCodes mapped to operator-facing messaging. Anything not
// listed falls back to a generic failure message including the raw code so
// the operator can grep Safaricom support tickets.
const DARAJA_RESULT_MESSAGES = {
  0: {
    tone: "success",
    icon: "check_circle",
    title: "Payment Successful",
    body: "M-PESA confirmed the payment. The customer's account has been credited.",
  },
  1: {
    tone: "error",
    icon: "money_off",
    title: "Insufficient Funds",
    body: "The customer's M-PESA balance is too low to complete this payment.",
  },
  1001: {
    tone: "warning",
    icon: "phone_in_talk",
    title: "Another Transaction in Progress",
    body: "M-PESA reports this number is locked by another transaction. Wait a moment and try again.",
  },
  1019: {
    tone: "error",
    icon: "schedule",
    title: "Transaction Expired",
    body: "The STK Push expired before the customer responded.",
  },
  1025: {
    tone: "error",
    icon: "error_outline",
    title: "Push Delivery Failed",
    body: "Daraja could not deliver the STK Push. Re-check the phone number and try again.",
  },
  1032: {
    tone: "warning",
    icon: "block",
    title: "Cancelled by Customer",
    body: "The customer dismissed the M-PESA prompt without entering their PIN.",
  },
  1037: {
    tone: "error",
    icon: "signal_cellular_off",
    title: "Phone Unreachable",
    body: "The phone is offline or out of network range. Try again when the customer is reachable.",
  },
  2001: {
    tone: "error",
    icon: "lock",
    title: "Wrong M-PESA PIN",
    body: "The customer entered the wrong M-PESA PIN. Ask them to try again.",
  },
  9999: {
    tone: "error",
    icon: "error",
    title: "Daraja Error",
    body: "Safaricom reported a general error. Try again in a moment.",
  },
}

const PENDING_MESSAGE = {
  tone: "info",
  icon: "schedule",
  title: "Payment Pending",
  body: "Daraja hasn't reported a final outcome yet — the STK Push is still in flight.",
}

const TIMEOUT_MESSAGE = {
  tone: "warning",
  icon: "hourglass_disabled",
  title: "Stopped Waiting",
  body: "The payment didn't resolve within the polling window. It may still come through via the async callback — check the Transactions page in a minute.",
}

const POLL_INTERVAL_MS = 3000
const MAX_POLL_ATTEMPTS = 20 // ~60 seconds total

export default {
  name: "SafaricomSTKPush",
  mixins: [notify],
  data() {
    return {
      transactionService: new TransactionService(),
      stage: "form",
      submitting: false,
      form: {
        deviceType: "meter",
        deviceSerial: "",
        phoneNumber: "",
        amount: null,
        transactionDesc: "",
      },
      deviceValidation: {
        loading: false,
        valid: null,
      },
      activeReferenceId: null,
      pollAttempts: 0,
      maxPollAttempts: MAX_POLL_ATTEMPTS,
      secondsUntilNextPoll: POLL_INTERVAL_MS / 1000,
      pollTimer: null,
      countdownTimer: null,
      lastStatus: null,
      resultMeta: PENDING_MESSAGE,
    }
  },
  computed: {
    formattedPhone() {
      const phone = this.lastStatus?.phone_number || this.form.phoneNumber || ""
      // Mask the middle 4 digits for operator screen-sharing comfort:
      // 254712345678 -> 25471****678
      if (phone.length >= 12) {
        return `${phone.substring(0, 6)}****${phone.substring(10)}`
      }
      return phone
    },
    serialLabel() {
      return this.form.deviceType === "solar_home_system"
        ? "SHS Serial Number"
        : "Meter Serial Number"
    },
    validatingMessage() {
      return this.form.deviceType === "solar_home_system"
        ? "Validating SHS…"
        : "Validating meter…"
    },
    validMessage() {
      return this.form.deviceType === "solar_home_system"
        ? "SHS found and registered."
        : "Meter found and in use."
    },
    invalidMessage() {
      return this.form.deviceType === "solar_home_system"
        ? "No SHS with that serial."
        : "No active meter with that serial."
    },
  },
  beforeDestroy() {
    this.stopPolling()
  },
  methods: {
    onDeviceTypeChange() {
      // Re-validate against the new type whenever the operator flips it,
      // so a serial that's a meter but not an SHS doesn't show a stale ✓.
      this.deviceValidation.valid = null
      if (
        this.form.deviceSerial &&
        this.form.deviceSerial.length >= 3
      ) {
        this.validateDevice()
      }
    },
    async validateDevice() {
      if (!this.form.deviceSerial || this.form.deviceSerial.length < 3) {
        this.deviceValidation.valid = null
        return
      }
      this.deviceValidation.loading = true
      try {
        const result = await this.transactionService.validateDevice(
          this.form.deviceSerial,
          this.form.deviceType,
        )
        this.deviceValidation.valid = Boolean(result?.valid)
      } catch (error) {
        this.deviceValidation.valid = false
        console.error("Device validation error:", error)
      } finally {
        this.deviceValidation.loading = false
      }
    },
    async sendStkPush() {
      const valid = await this.$validator.validateAll("STK-Form")
      if (!valid) {
        return
      }
      if (this.deviceValidation.valid !== true) {
        // Re-run validation if it's never been done or came back false.
        await this.validateDevice()
        if (this.deviceValidation.valid !== true) {
          this.alertNotify("error", this.invalidMessage)
          return
        }
      }

      this.submitting = true
      try {
        const payload = {
          amount: this.form.amount,
          phone_number: this.form.phoneNumber,
          device_type: this.form.deviceType,
          device_serial: this.form.deviceSerial,
        }
        if (this.form.transactionDesc) {
          payload.transaction_desc = this.form.transactionDesc
        }

        const response = await this.transactionService.initiateStkPush(payload)
        const referenceId = response?.data?.data?.reference_id
        if (!referenceId) {
          throw new Error("Daraja did not return a reference id.")
        }

        this.activeReferenceId = referenceId
        this.stage = "waiting"
        this.pollAttempts = 0
        this.secondsUntilNextPoll = POLL_INTERVAL_MS / 1000
        this.startPolling()
      } catch (error) {
        const message =
          error.response?.data?.error ||
          error.response?.data?.message ||
          error.message ||
          "Failed to initiate STK Push"
        this.alertNotify("error", message)
      } finally {
        this.submitting = false
      }
    },
    startPolling() {
      this.stopPolling()
      this.countdownTimer = setInterval(() => {
        this.secondsUntilNextPoll = Math.max(0, this.secondsUntilNextPoll - 1)
      }, 1000)
      this.pollTimer = setInterval(() => this.pollStatus(), POLL_INTERVAL_MS)
      this.pollStatus()
    },
    stopPolling() {
      if (this.pollTimer) {
        clearInterval(this.pollTimer)
        this.pollTimer = null
      }
      if (this.countdownTimer) {
        clearInterval(this.countdownTimer)
        this.countdownTimer = null
      }
    },
    async pollStatus() {
      if (!this.activeReferenceId) {
        return
      }
      this.pollAttempts += 1
      this.secondsUntilNextPoll = POLL_INTERVAL_MS / 1000

      try {
        const response = await this.transactionService.getStatus(
          this.activeReferenceId,
        )
        const status = response?.data?.data
        if (!status) {
          return
        }
        this.lastStatus = status

        if (status.resolved) {
          this.finalise(status)
          return
        }
      } catch (error) {
        console.warn("Polling Safaricom status failed:", error)
        // Soft-fail: keep polling. The most common cause is the callback
        // arriving milliseconds after a query started.
      }

      if (this.pollAttempts >= this.maxPollAttempts) {
        this.finaliseAsTimeout()
      }
    },
    finalise(status) {
      this.stopPolling()
      const code = status.result_code
      const meta = DARAJA_RESULT_MESSAGES[code]
      this.resultMeta = meta || {
        tone: "error",
        icon: "error",
        title: "Payment Failed",
        body: `Daraja returned an unexpected result code (${code ?? "unknown"}).`,
      }
      this.stage = "result"
    },
    finaliseAsTimeout() {
      this.stopPolling()
      this.resultMeta = TIMEOUT_MESSAGE
      this.stage = "result"
    },
    cancelWaiting() {
      this.stopPolling()
      this.stage = "form"
      this.activeReferenceId = null
      this.lastStatus = null
    },
    reset() {
      this.stage = "form"
      this.activeReferenceId = null
      this.lastStatus = null
      this.resultMeta = PENDING_MESSAGE
      this.form = {
        deviceType: "meter",
        deviceSerial: "",
        phoneNumber: "",
        amount: null,
        transactionDesc: "",
      }
      this.deviceValidation = {
        loading: false,
        valid: null,
      }
    },
    formatAmount(amount) {
      if (amount === null || amount === undefined) return ""
      return new Intl.NumberFormat("en-KE", {
        maximumFractionDigits: 0,
      }).format(amount)
    },
  },
}
</script>

<style scoped lang="scss">
.stk-push {
  display: flex;
  justify-content: center;
  padding: 1.5rem 1rem;
}

.panel {
  width: 100%;
  max-width: 760px;
  border-radius: 10px;
}

.panel__head {
  padding: 1.25rem 1.5rem 0.5rem;
}

.panel__title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 700;
  color: $brand-primary-dark;
}

.panel__subtitle {
  margin: 0.35rem 0 0;
  font-size: 0.85rem;
  line-height: 1.5;
  color: #8a93a0;
}

.panel__actions {
  display: flex;
  justify-content: flex-end;
  padding: 0.5rem 1.5rem 1.25rem;
}

.form {
  padding: 0.5rem 0;
}

.field {
  margin-bottom: 1.1rem;
}

.field__label {
  display: block;
  margin-bottom: 0.1rem;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.07em;
  text-transform: uppercase;
  color: #8a93a0;
}

.field .md-field {
  margin: 0;
  min-height: 40px;
  padding-top: 4px;
}

.field__note {
  margin: 0.3rem 0 0;
  font-size: 11.5px;
  font-style: italic;
  color: #9aa3af;
}

.hint {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-top: 0.35rem;
  font-size: 12px;

  .md-icon {
    font-size: 16px !important;
    width: 16px;
    min-width: 16px;
    height: 16px;
  }
}

.hint--muted {
  color: #8a93a0;
}

.hint--ok {
  color: #2e7d32;

  .md-icon {
    color: #2e7d32 !important;
  }
}

.hint--error {
  color: #d9534f;

  .md-icon {
    color: #d9534f !important;
  }
}

.waiting {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 2rem 1rem;
  gap: 0.75rem;
}

.waiting__title {
  margin: 0.75rem 0 0;
  font-size: 1.1rem;
  font-weight: 700;
  color: $brand-primary-dark;
}

.waiting__text {
  margin: 0;
  font-size: 0.9rem;
  color: #4b5563;
  max-width: 480px;
  line-height: 1.5;
}

.waiting__text--muted {
  color: #8a93a0;
  font-size: 0.825rem;
}

.waiting__countdown {
  margin-top: 0.5rem;
  font-size: 0.825rem;
  color: $brand-primary;
  font-weight: 600;
}

.waiting__pollcount {
  color: #8a93a0;
  font-weight: 400;
}

.waiting__cancel {
  margin-top: 0.5rem;
}

.result {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 2rem 1rem 1rem;
  gap: 0.5rem;
}

.result__icon {
  width: 56px !important;
  min-width: 56px;
  height: 56px;
  font-size: 56px !important;
}

.result--success .result__icon {
  color: #5cb85c !important;
}
.result--error .result__icon {
  color: #d9534f !important;
}
.result--warning .result__icon {
  color: #f0ad4e !important;
}
.result--info .result__icon {
  color: #5bc0de !important;
}

.result__title {
  margin: 0.5rem 0 0;
  font-size: 1.2rem;
  font-weight: 700;
  color: $brand-primary-dark;
}

.result__body {
  margin: 0;
  font-size: 0.9rem;
  color: #4b5563;
  max-width: 480px;
  line-height: 1.5;
}

.result__details {
  margin-top: 1rem;
  width: 100%;
  max-width: 480px;
  background: #f8fafc;
  border-radius: 8px;
  padding: 0.75rem 1rem;
}

.detail-row {
  display: flex;
  margin-bottom: 0.4rem;
  padding-bottom: 0.4rem;
  border-bottom: 1px solid #edf0f3;
  font-size: 0.85rem;

  &:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
  }
}

.detail-label {
  font-weight: 600;
  min-width: 140px;
  color: #6b7280;
}

.detail-value {
  flex: 1;
  text-align: right;
  color: #1f2937;
}

.detail-value--strong {
  font-weight: 700;
  color: #5cb85c;
  letter-spacing: 0.03em;
}
</style>
