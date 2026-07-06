<template>
  <checkout-page subtitle="Online payments" :company-name="companyName">
    <p>
      Pay for energy or solar home system credit. Enter your device details and
      the amount you want to pay.
    </p>

    <form
      @submit.prevent="submitPaymentRequestForm"
      data-vv-scope="Payment-Form"
    >
      <md-field>
        <label for="deviceType">Device type</label>
        <md-select
          id="deviceType"
          name="deviceType"
          v-model="paymentService.paymentRequest.deviceType"
          @md-selected="onDeviceTypeChange"
        >
          <md-option value="meter">Meter</md-option>
          <md-option value="solar_home_system">Solar Home System</md-option>
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

      <md-field
        :class="{
          'md-invalid': errors.has('Payment-Form.amount'),
        }"
      >
        <label for="amount">Amount ({{ currency }})</label>
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

      <md-button
        type="submit"
        class="md-raised md-primary"
        style="width: 100%"
        :disabled="!isFormValid || loading"
      >
        {{ loading ? "Processing…" : "Make Payment" }}
      </md-button>
    </form>
  </checkout-page>
</template>

<script>
import { PublicPaymentService } from "../../services/PublicPaymentService.js"

import { notify } from "@/mixins/notify.js"
import CheckoutPage from "@/shared/CheckoutPage.vue"

export default {
  name: "PublicPaymentForm",
  mixins: [notify],
  components: { CheckoutPage },
  data() {
    return {
      paymentService: new PublicPaymentService(),
      loading: false,
      companyName: "",
      currency: "KES",
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
      return (
        this.$route.query.ct || sessionStorage.getItem("pesapal_company_token")
      )
    },
    serialLabel() {
      if (
        this.paymentService.paymentRequest.deviceType === "solar_home_system"
      ) {
        return "SHS Serial Number"
      }
      return "Meter Serial Number"
    },
    validatingMessage() {
      if (
        this.paymentService.paymentRequest.deviceType === "solar_home_system"
      ) {
        return "Validating SHS..."
      }
      return "Validating meter..."
    },
    validMessage() {
      if (
        this.paymentService.paymentRequest.deviceType === "solar_home_system"
      ) {
        return "SHS is valid"
      }
      return "Meter is valid"
    },
    invalidMessage() {
      if (
        this.paymentService.paymentRequest.deviceType === "solar_home_system"
      ) {
        return "Invalid SHS serial number"
      }
      return "Invalid meter serial number"
    },
    isFormValid() {
      return (
        this.paymentService.paymentRequest.deviceSerial &&
        this.paymentService.paymentRequest.amount &&
        this.paymentService.paymentRequest.amount > 0 &&
        this.deviceValidation.valid === true
      )
    },
  },
  async mounted() {
    if (this.companyIdToken) {
      sessionStorage.setItem("pesapal_company_token", this.companyIdToken)
    }
    await this.loadCompanyInfo()
  },
  methods: {
    onDeviceTypeChange() {
      this.deviceValidation.valid = null
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
        this.currency = response.currency || "KES"
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
          this.paymentService.paymentRequest.deviceType === "solar_home_system"
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
          window.location = data.redirect_url
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
