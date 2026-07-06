<template>
  <div>
    <widget color="primary" title="Initiate M-PESA Payment">
      <md-card>
        <!-- Step 1: Collect details -->
        <md-card-content v-if="stage === 'form'">
          <p>
            Sends an STK Push to the customer's phone. They confirm by entering
            their M-PESA PIN — no redirect, the page polls Daraja until the
            transaction resolves.
          </p>
          <form @submit.prevent="sendStkPush" data-vv-scope="STK-Form">
            <div class="md-layout md-gutter">
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field>
                  <label for="deviceType">Device type</label>
                  <md-select
                    id="deviceType"
                    name="deviceType"
                    v-model="form.deviceType"
                    @md-selected="onDeviceTypeChange"
                  >
                    <md-option value="meter">Meter</md-option>
                    <md-option value="solar_home_system">
                      Solar Home System
                    </md-option>
                  </md-select>
                </md-field>
              </div>

              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('STK-Form.deviceSerial'),
                  }"
                >
                  <label for="deviceSerial">{{ serialLabel }}</label>
                  <md-input
                    id="deviceSerial"
                    name="deviceSerial"
                    v-model="form.deviceSerial"
                    v-validate="'required|min:3'"
                    @blur="validateDevice"
                  />
                  <span class="md-error">
                    {{ errors.first("STK-Form.deviceSerial") }}
                  </span>
                </md-field>
                <p v-if="deviceValidation.loading" class="md-caption">
                  <md-progress-spinner
                    md-mode="indeterminate"
                    :md-diameter="14"
                    :md-stroke="2"
                  ></md-progress-spinner>
                  {{ validatingMessage }}
                </p>
                <p
                  v-else-if="deviceValidation.valid === true"
                  class="md-caption"
                  style="color: green"
                >
                  {{ validMessage }}
                </p>
                <p
                  v-else-if="deviceValidation.valid === false"
                  class="md-caption"
                  style="color: red"
                >
                  {{ invalidMessage }}
                </p>
              </div>

              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('STK-Form.phoneNumber'),
                  }"
                >
                  <label for="phoneNumber">Customer Phone</label>
                  <md-input
                    id="phoneNumber"
                    name="phoneNumber"
                    v-model="form.phoneNumber"
                    v-validate="'required|min:9'"
                    placeholder="e.g. 0712345678"
                    type="tel"
                  />
                  <span class="md-helper-text">
                    Accepts 0712…, 712…, +254…, or 254… — normalised
                    server-side.
                  </span>
                  <span class="md-error">
                    {{ errors.first("STK-Form.phoneNumber") }}
                  </span>
                </md-field>
              </div>

              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('STK-Form.amount'),
                  }"
                >
                  <label for="amount">Amount (KES)</label>
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
                  <span class="md-helper-text">
                    Daraja accepts whole numbers only — decimals are rounded.
                  </span>
                  <span class="md-error">
                    {{ errors.first("STK-Form.amount") }}
                  </span>
                </md-field>
              </div>

              <div class="md-layout-item md-size-100">
                <md-field>
                  <label for="transactionDesc">Description</label>
                  <md-input
                    id="transactionDesc"
                    name="transactionDesc"
                    v-model="form.transactionDesc"
                    maxlength="50"
                    placeholder="What the customer is paying for"
                  />
                  <span class="md-helper-text">
                    Truncated to 13 chars (Daraja TransactionDesc limit) before
                    sending.
                  </span>
                </md-field>
              </div>
            </div>
          </form>
        </md-card-content>

        <!-- Step 2: Waiting -->
        <md-card-content v-else-if="stage === 'waiting'" class="text-center">
          <md-progress-spinner
            md-mode="indeterminate"
            :md-diameter="60"
            :md-stroke="4"
          />
          <div class="md-title">Check the customer's phone</div>
          <p>
            An M-PESA prompt has been sent to
            <strong>{{ formattedPhone }}</strong>
            for
            <strong>KES {{ formatAmount(form.amount) }}</strong>
            .
          </p>
          <p class="md-caption">
            They need to enter their M-PESA PIN to complete the payment. This
            page polls Safaricom every few seconds and will update
            automatically.
          </p>
          <p class="md-caption">
            Checking again in {{ secondsUntilNextPoll }}s · attempt
            {{ pollAttempts }} of {{ maxPollAttempts }}
          </p>
          <md-button @click="cancelWaiting">Cancel and start over</md-button>
        </md-card-content>

        <!-- Step 3: Result -->
        <md-card-content v-else-if="stage === 'result'">
          <div class="text-center">
            <md-icon class="md-size-2x" :style="{ color: resultIconColor }">
              {{ resultMeta.icon }}
            </md-icon>
            <div class="md-title">{{ resultMeta.title }}</div>
            <p>{{ resultMeta.body }}</p>
          </div>

          <template v-if="lastStatus">
            <div class="md-layout">
              <div class="md-layout-item md-subheader">Reference</div>
              <div class="md-layout-item md-subheader">
                {{ lastStatus.reference_id }}
              </div>
            </div>
            <md-divider />
            <div class="md-layout">
              <div class="md-layout-item md-subheader">Phone</div>
              <div class="md-layout-item md-subheader">
                {{ lastStatus.phone_number || formattedPhone }}
              </div>
            </div>
            <md-divider />
            <div class="md-layout">
              <div class="md-layout-item md-subheader">Amount</div>
              <div class="md-layout-item md-subheader">
                KES {{ formatAmount(lastStatus.amount || form.amount) }}
              </div>
            </div>
            <md-divider v-if="lastStatus.mpesa_receipt_number" />
            <div v-if="lastStatus.mpesa_receipt_number" class="md-layout">
              <div class="md-layout-item md-subheader">M-PESA Receipt</div>
              <div class="md-layout-item md-subheader">
                {{ lastStatus.mpesa_receipt_number }}
              </div>
            </div>
            <md-divider v-if="lastStatus.result_code !== null" />
            <div v-if="lastStatus.result_code !== null" class="md-layout">
              <div class="md-layout-item md-subheader">Daraja code</div>
              <div class="md-layout-item md-subheader">
                {{ lastStatus.result_code }}
              </div>
            </div>
          </template>
        </md-card-content>

        <md-progress-bar md-mode="indeterminate" v-if="submitting" />

        <md-card-actions v-if="stage === 'form'">
          <md-button
            class="md-raised md-primary"
            :disabled="submitting"
            @click="sendStkPush"
          >
            Send STK Push
          </md-button>
        </md-card-actions>
        <md-card-actions v-else-if="stage === 'result'">
          <md-button class="md-raised md-primary" @click="reset">
            Start a new payment
          </md-button>
        </md-card-actions>
      </md-card>
    </widget>
  </div>
</template>

<script>
import { TransactionService } from "../../services/TransactionService.js"

import { notify } from "@/mixins/notify.js"
import Widget from "@/shared/Widget.vue"

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

// Same palette as the transaction status icons on the core Transactions page.
const TONE_COLORS = {
  success: "green",
  error: "red",
  warning: "goldenrod",
  info: "grey",
}

const POLL_INTERVAL_MS = 3000
const MAX_POLL_ATTEMPTS = 20 // ~60 seconds total

export default {
  name: "SafaricomSTKPush",
  mixins: [notify],
  components: { Widget },
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
    resultIconColor() {
      return TONE_COLORS[this.resultMeta.tone] || TONE_COLORS.info
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
      if (this.form.deviceSerial && this.form.deviceSerial.length >= 3) {
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
