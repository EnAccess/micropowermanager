<template>
  <div class="checkout">
    <div class="checkout__inner">
      <header class="brand">
        <img class="brand__logo" :src="mpmLogo" alt="MicroPowerManager" />
        <h1 class="brand__name">MicroPowerManager</h1>
        <p class="brand__sub">
          Online payments
          <span v-if="companyName">&nbsp;·&nbsp;{{ companyName }}</span>
        </p>
      </header>

      <section class="sheet">
        <p class="sheet__intro">
          Pay for energy or solar home system credit. Enter your device details
          and the amount you want to pay.
        </p>

        <form
          @submit.prevent="submitPaymentRequestForm"
          data-vv-scope="Payment-Form"
          class="form"
        >
          <div class="field">
            <label class="field__label" for="deviceType">Device type</label>
            <md-field>
              <md-select
                id="deviceType"
                name="deviceType"
                v-model="paymentService.paymentRequest.deviceType"
                @md-selected="onDeviceTypeChange"
              >
                <md-option value="meter">Meter</md-option>
                <md-option value="solar_home_system">
                  Solar Home System
                </md-option>
              </md-select>
            </md-field>
          </div>

          <div class="field">
            <label class="field__label" for="deviceSerial">
              {{ serialLabel }}
            </label>
            <md-field
              :class="{
                'md-invalid': errors.has('Payment-Form.deviceSerial'),
              }"
            >
              <md-input
                id="deviceSerial"
                name="deviceSerial"
                v-model="paymentService.paymentRequest.deviceSerial"
                v-validate="'required|min:3'"
                placeholder="Enter the serial number"
                @blur="validateDevice"
              />
              <span class="md-error">
                {{ errors.first("Payment-Form.deviceSerial") }}
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

          <div class="field">
            <label class="field__label" for="amount">
              Amount ({{ currency }})
            </label>
            <md-field
              :class="{
                'md-invalid': errors.has('Payment-Form.amount'),
              }"
            >
              <md-input
                id="amount"
                name="amount"
                v-model="paymentService.paymentRequest.amount"
                v-validate="'required|decimal:2|min_value:1'"
                type="number"
                step="0.01"
                min="1"
                placeholder="0.00"
              />
              <span class="md-error">
                {{ errors.first("Payment-Form.amount") }}
              </span>
            </md-field>
          </div>

          <md-button
            type="submit"
            class="cta md-raised md-primary"
            :disabled="!isFormValid || loading"
          >
            {{ loading ? "Processing…" : "Make Payment" }}
          </md-button>
        </form>
      </section>
    </div>
  </div>
</template>

<script>
import { PublicPaymentService } from "../../services/PublicPaymentService.js"

import mpmLogo from "@/assets/images/mpmlogo_raw.png"
import { notify } from "@/mixins/notify.js"

export default {
  name: "PublicPaymentForm",
  mixins: [notify],
  data() {
    return {
      mpmLogo,
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

.sheet__intro {
  margin: 0 0 1.75rem;
  font-size: 0.875rem;
  line-height: 1.6;
  color: #6b7280;
  text-align: center;
}

.form {
  display: flex;
  flex-direction: column;
}

.field {
  margin-bottom: 1.3rem;
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

.hint {
  margin-top: 6px;
  font-size: 12px;

  .md-icon,
  .md-progress-spinner {
    margin: 0 4px 0 0;
    vertical-align: middle;
  }
}

.hint--muted {
  color: #8a93a0;
}

.hint--ok {
  color: $brand-accent-dark;

  .md-icon {
    width: 15px;
    min-width: 15px;
    height: 15px;
    color: $brand-accent-dark !important;
    font-size: 15px !important;
  }
}

.hint--error {
  color: #d64545;

  .md-icon {
    width: 15px;
    min-width: 15px;
    height: 15px;
    color: #d64545 !important;
    font-size: 15px !important;
  }
}

.cta {
  width: 100%;
  height: 52px;
  margin: 0.75rem 0 0;
  border-radius: 9px;
  font-size: 0.95rem;
  font-weight: 600;
  letter-spacing: 0.01em;
  text-transform: none;
}
</style>
