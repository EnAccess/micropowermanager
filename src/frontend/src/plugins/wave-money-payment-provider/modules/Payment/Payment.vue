<template>
  <div class="welcome">
    <div class="content">
      <div class="title">
        <span class="title highlight">MicroPowerManager</span>
      </div>
      <div class="title-2">
        Wave Money online payments for company {{ companyName }}
      </div>

      <p class="cloud-description">
        On this page, you can make your online payment for energy tokens.
        MicroPowerManager uses your Wave Money account to make the payment.
        Please enter your meter number and the amount you want to pay.
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
            />
            <span class="md-error">
              {{ errors.first("Payment-Form.meterSerial") }}
            </span>
          </md-field>
          <md-field
            :class="{
              'md-invalid': errors.has('Payment-Form.amount'),
            }"
          >
            <label for="amount">Amount (MMK)</label>
            <md-input
              id="amount"
              name="amount"
              v-model="paymentService.paymentRequest.amount"
              v-validate="'required|decimal:2'"
            />
            <span class="md-error">
              {{ errors.first("Payment-Form.amount") }}
            </span>
          </md-field>
          <md-button
            class="md-raised md-primary"
            type="submit"
            style="margin: inherit"
          >
            Make Payment
          </md-button>
        </div>
      </form>
    </div>
    <md-progress-bar md-mode="indeterminate" v-if="loading" />
  </div>
</template>

<script>
import { PaymentService } from "../../services/PaymentService"

export default {
  name: "Payment",
  data() {
    return {
      paymentService: new PaymentService(),
      loading: false,
    }
  },
  computed: {
    companyName() {
      return this.$route.params.name
    },
  },
  methods: {
    async submitPaymentRequestForm() {
      const companyId = this.$route.params.id
      let validator = await this.$validator.validateAll("Payment-Form")
      if (!validator) {
        return
      }
      try {
        this.loading = true
        const data = await this.paymentService.startTransaction(companyId)
        this.$swal({
          title: "Success!",
          text: "Success! you will be redirected to the payment page",
          type: "success",
          timer: 2000,
          timerProgressBar: true,
        }).then(() => {
          window.location = data.redirectionUrl
        })
      } catch (e) {
        this.$swal({
          title: "Error!",
          text: e.message,
          icon: "error",
          timer: 2000,
          timerProgressBar: true,
        })
      }
      this.loading = false
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
  margin-top: 18rem;
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
}

.router-box p {
  width: 100%;
  margin-top: 8px;
  padding: 4px;
}

.Payment-Form {
  align-items: center;
  justify-content: center;
  width: 100%;
  text-align: center;
  margin-top: 2rem;
}
</style>
