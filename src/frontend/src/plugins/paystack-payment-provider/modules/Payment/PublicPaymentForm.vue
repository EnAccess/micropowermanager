<template>
  <div class="welcome">
    <div class="content">
      <div class="title">
        <span class="title highlight">MicroPowerManager</span>
      </div>
      <div class="title-2">Paystack online payments for {{ companyName }}</div>

      <p class="cloud-description">
        On this page, you can make your online payment for energy or SHS credit.
        MicroPowerManager uses Paystack to process your payment securely. Please
        select a device type, enter its serial number, and the amount you want
        to pay.
      </p>

      <form
        @submit.prevent="submitPaymentRequestForm"
        data-vv-scope="Payment-Form"
        class="Payment-Form"
      >
        <div class="router-box">
          <md-field>
            <label for="deviceType">Device Type</label>
            <md-select
              id="deviceType"
              name="deviceType"
              v-model="paymentService.paymentRequest.deviceType"
              @md-selected="onDeviceTypeChange"
            >
              <md-option value="meter">Meter</md-option>
              <md-option value="shs">Solar Home System</md-option>
            </md-select>
          </md-field>

          <md-field
            :class="{
              'md-invalid': errors.has('Payment-Form.deviceSerial'),
            }"
          >
            <label for="deviceSerial">{{ serialLabel }}</label>
            <md-input
              id="deviceSerial"
              name="deviceSerial"
              v-model="paymentService.paymentRequest.deviceSerial"
              v-validate="'required|min:3'"
              @blur="validateDevice"
            />
            <span class="md-error">
              {{ errors.first("Payment-Form.deviceSerial") }}
            </span>
            <div v-if="deviceValidation.loading" class="validation-loading">
              <md-progress-spinner
                md-diameter="20"
                md-stroke="2"
              ></md-progress-spinner>
              <span>{{ validatingMessage }}</span>
            </div>
            <div
              v-if="deviceValidation.valid === true"
              class="validation-success"
            >
              <md-icon>check_circle</md-icon>
              <span>{{ validMessage }}</span>
            </div>
            <div
              v-if="deviceValidation.valid === false"
              class="validation-error"
            >
              <md-icon>error</md-icon>
              <span>{{ invalidMessage }}</span>
            </div>
          </md-field>

          <md-field
            :class="{
              'md-invalid': errors.has('Payment-Form.amount'),
            }"
          >
            <label for="amount">Amount ({{ selectedCurrency }})</label>
            <md-input
              id="amount"
              name="amount"
              v-model="paymentService.paymentRequest.amount"
              v-validate="'required|decimal:2|min_value:1'"
              type="number"
              step="0.01"
              min="1"
            />
            <span class="md-error">
              {{ errors.first("Payment-Form.amount") }}
            </span>
          </md-field>

          <md-field>
            <label for="currency">Currency</label>
            <md-select
              id="currency"
              name="currency"
              v-model="paymentService.paymentRequest.currency"
            >
              <md-option
                v-for="currency in supportedCurrencies"
                :key="currency"
                :value="currency"
              >
                {{ currency }}
              </md-option>
            </md-select>
          </md-field>

          <md-button
            class="md-raised md-primary"
            type="submit"
            :disabled="!isFormValid || loading"
            style="margin: inherit"
          >
            <md-progress-spinner
              v-if="loading"
              md-diameter="20"
              md-stroke="2"
              style="margin-right: 8px"
            ></md-progress-spinner>
            {{ loading ? "Processing..." : "Make Payment" }}
          </md-button>
        </div>
      </form>
    </div>
    <md-progress-bar md-mode="indeterminate" v-if="loading" />
  </div>
</template>

<script>
import { PublicPaymentService } from "../../services/PublicPaymentService"
import { notify } from "@/mixins/notify"

export default {
  name: "PublicPaymentForm",
  mixins: [notify],
  data() {
    return {
      paymentService: new PublicPaymentService(),
      loading: false,
      companyName: "",
      supportedCurrencies: [],
      deviceValidation: {
        loading: false,
        valid: null,
      },
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
    serialLabel() {
      if (this.paymentService.paymentRequest.deviceType === "shs") {
        return "SHS Serial Number"
      }
      return "Meter Serial Number"
    },
    validatingMessage() {
      if (this.paymentService.paymentRequest.deviceType === "shs") {
        return "Validating SHS..."
      }
      return "Validating meter..."
    },
    validMessage() {
      if (this.paymentService.paymentRequest.deviceType === "shs") {
        return "SHS is valid"
      }
      return "Meter is valid"
    },
    invalidMessage() {
      if (this.paymentService.paymentRequest.deviceType === "shs") {
        return "Invalid SHS serial number"
      }
      return "Invalid meter serial number"
    },
    selectedCurrency() {
      return this.paymentService.paymentRequest.currency || "NGN"
    },
    isFormValid() {
      return (
        this.paymentService.paymentRequest.deviceSerial &&
        this.paymentService.paymentRequest.amount &&
        this.paymentService.paymentRequest.amount > 0 &&
        this.paymentService.paymentRequest.currency &&
        this.deviceValidation.valid === true
      )
    },
  },
  async mounted() {
    // Store company token in sessionStorage for use after Paystack redirect
    if (this.companyIdToken) {
      sessionStorage.setItem("paystack_company_token", this.companyIdToken)
    }
    await this.loadCompanyInfo()
  },
  methods: {
    onDeviceTypeChange() {
      // Reset validation state when device type changes
      this.deviceValidation.valid = null
      // Trigger validation if device serial is already entered
      if (this.paymentService.paymentRequest.deviceSerial?.length >= 3) {
        this.validateDevice()
      }
    },
    async loadCompanyInfo() {
      try {
        const response = await this.paymentService.getCompanyInfo(
          this.companyHash,
          this.companyIdToken,
        )
        this.companyName = response.company.name
        this.supportedCurrencies = response.supported_currencies || [
          "NGN",
          "GHS",
          "KES",
          "ZAR",
        ]
        this.paymentService.paymentRequest.currency =
          response.default_currency || "NGN"
      } catch (error) {
        this.alertNotify("error", "Failed to load company information")
      }
    },
    async validateDevice() {
      if (
        !this.paymentService.paymentRequest.deviceSerial ||
        this.paymentService.paymentRequest.deviceSerial.length < 3
      ) {
        this.deviceValidation.valid = null
        return
      }

      this.deviceValidation.loading = true
      try {
        const response = await this.paymentService.validateDevice(
          this.companyHash,
          this.companyIdToken,
          this.paymentService.paymentRequest.deviceSerial,
          this.paymentService.paymentRequest.deviceType,
        )
        this.deviceValidation.valid = response.valid
      } catch (error) {
        this.deviceValidation.valid = false
        console.error("Device validation error:", error)
      } finally {
        this.deviceValidation.loading = false
      }
    },
    async submitPaymentRequestForm() {
      let validator = await this.$validator.validateAll("Payment-Form")
      if (!validator) {
        return
      }

      if (this.deviceValidation.valid !== true) {
        const deviceName =
          this.paymentService.paymentRequest.deviceType === "shs"
            ? "SHS"
            : "meter"
        this.$swal({
          title: "Error!",
          text: `Please enter a valid ${deviceName} serial number`,
          icon: "error",
        })
        return
      }

      try {
        this.loading = true
        const data = await this.paymentService.initiatePayment(
          this.companyHash,
          this.companyIdToken,
          this.paymentService.paymentRequest,
        )

        this.$swal({
          title: "Success!",
          text: "You will be redirected to the payment page",
          type: "success",
          timer: 2000,
          timerProgressBar: true,
        }).then(() => {
          window.location = data.redirection_url
        })
      } catch (error) {
        this.$swal({
          title: "Error!",
          text: error.message || "Failed to initiate payment",
          icon: "error",
        })
      } finally {
        this.loading = false
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

.cloud-description {
  text-align: center;
  padding: 15px;
  font-size: 16px;
  font-weight: 500;
}

.highlight {
  color: #ffc107;
}

.router-box {
  display: flex;
  flex-direction: column;
  margin-top: 1rem;
  padding: 1rem;
  min-width: 400px;
}

.router-box .md-field {
  margin-bottom: 1rem;
}

.validation-loading,
.validation-success,
.validation-error {
  display: flex;
  align-items: center;
  margin-top: 4px;
  font-size: 12px;
}

.validation-loading {
  color: #666;
}

.validation-success {
  color: #4caf50;
}

.validation-error {
  color: #f44336;
}

.validation-loading span,
.validation-success span,
.validation-error span {
  margin-left: 4px;
}

.md-button {
  margin-top: 1rem;
  min-height: 48px;
}
</style>
