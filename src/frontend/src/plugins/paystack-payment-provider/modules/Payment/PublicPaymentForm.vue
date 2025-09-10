<template>
  <div class="welcome">
    <div class="content">
      <div class="title">
        <span class="title highlight">MicroPowerManager</span>
      </div>
      <div class="title-2">Paystack online payments for {{ companyName }}</div>

      <p class="cloud-description">
        On this page, you can make your online payment for energy tokens.
        MicroPowerManager uses Paystack to process your payment securely. Please
        enter your meter number and the amount you want to pay.
      </p>

      <form
        @submit.prevent="submitPaymentRequestForm"
        data-vv-scope="Payment-Form"
        class="Payment-Form"
      >
        <div class="router-box">
          <md-field
            :class="{
              'md-invalid': errors.has('Payment-Form.meterSerial'),
            }"
          >
            <label for="meterSerial">Meter Serial Number</label>
            <md-input
              id="meterSerial"
              name="meterSerial"
              v-model="paymentService.paymentRequest.meterSerial"
              v-validate="'required|min:3'"
              @blur="validateMeter"
            />
            <span class="md-error">
              {{ errors.first("Payment-Form.meterSerial") }}
            </span>
            <div v-if="meterValidation.loading" class="validation-loading">
              <md-progress-spinner
                md-diameter="20"
                md-stroke="2"
              ></md-progress-spinner>
              <span>Validating meter...</span>
            </div>
            <div
              v-if="meterValidation.valid === true"
              class="validation-success"
            >
              <md-icon>check_circle</md-icon>
              <span>Meter is valid</span>
            </div>
            <div
              v-if="meterValidation.valid === false"
              class="validation-error"
            >
              <md-icon>error</md-icon>
              <span>Invalid meter serial number</span>
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
      supportedCurrencies: ["NGN", "GHS", "KES", "ZAR"],
      meterValidation: {
        loading: false,
        valid: null,
      },
    }
  },
  computed: {
    companyHash() {
      return this.$route.params.companyHash
    },
    companyId() {
      return this.$route.params.companyId
    },
    selectedCurrency() {
      return this.paymentService.paymentRequest.currency || "NGN"
    },
    isFormValid() {
      return (
        this.paymentService.paymentRequest.meterSerial &&
        this.paymentService.paymentRequest.amount &&
        this.paymentService.paymentRequest.amount > 0 &&
        this.paymentService.paymentRequest.currency &&
        this.meterValidation.valid === true
      )
    },
  },
  async mounted() {
    await this.loadCompanyInfo()
  },
  methods: {
    async loadCompanyInfo() {
      try {
        const response = await this.paymentService.getCompanyInfo(
          this.companyHash,
          this.companyId,
        )
        this.companyName = response.company.name
        this.supportedCurrencies = response.supported_currencies
        this.paymentService.paymentRequest.currency = response.default_currency
      } catch (error) {
        this.alertNotify("error", "Failed to load company information")
      }
    },
    async validateMeter() {
      if (
        !this.paymentService.paymentRequest.meterSerial ||
        this.paymentService.paymentRequest.meterSerial.length < 3
      ) {
        this.meterValidation.valid = null
        return
      }

      this.meterValidation.loading = true
      try {
        const response = await this.paymentService.validateMeter(
          this.companyHash,
          this.companyId,
          this.paymentService.paymentRequest.meterSerial,
        )
        this.meterValidation.valid = response.valid
      } catch (error) {
        this.meterValidation.valid = false
        console.error("Meter validation error:", error)
      } finally {
        this.meterValidation.loading = false
      }
    },
    async submitPaymentRequestForm() {
      let validator = await this.$validator.validateAll("Payment-Form")
      if (!validator) {
        return
      }

      if (this.meterValidation.valid !== true) {
        this.$swal({
          title: "Error!",
          text: "Please enter a valid meter serial number",
          icon: "error",
        })
        return
      }

      try {
        this.loading = true
        const data = await this.paymentService.initiatePayment(
          this.companyHash,
          this.companyId,
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
