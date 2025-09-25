<template>
  <div class="stk-push">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Initiate M-PESA Payment</h3>
      </div>
      <div class="card-body">
        <form @submit.prevent="initiatePayment">
          <div class="form-group">
            <label>Phone Number</label>
            <input
              v-model="payment.phone_number"
              type="tel"
              class="form-control"
              placeholder="e.g., 254700000000"
              required
              pattern="^254[0-9]{9}$"
            />
            <small class="form-text text-muted">
              Enter phone number in format: 254XXXXXXXXX
            </small>
          </div>
          <div class="form-group">
            <label>Amount (KES)</label>
            <input
              v-model.number="payment.amount"
              type="number"
              class="form-control"
              min="1"
              step="0.01"
              required
            />
          </div>
          <div class="form-group">
            <label>Description</label>
            <input
              v-model="payment.description"
              type="text"
              class="form-control"
              required
            />
          </div>
          <button
            type="submit"
            class="btn btn-primary"
            :disabled="isLoading"
          >
            {{ isLoading ? 'Processing...' : 'Initiate Payment' }}
          </button>
        </form>
      </div>
    </div>

    <div v-if="paymentStatus" class="card mt-4">
      <div class="card-header">
        <h3 class="card-title">Payment Status</h3>
      </div>
      <div class="card-body">
        <div class="alert" :class="getStatusAlertClass(paymentStatus.status)">
          <h4 class="alert-heading">{{ getStatusTitle(paymentStatus.status) }}</h4>
          <p>{{ getStatusMessage(paymentStatus.status) }}</p>
          <div v-if="paymentStatus.reference_id" class="mt-3">
            <strong>Reference ID:</strong> {{ paymentStatus.reference_id }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState } from 'vuex' 
import { notify } from "@/mixins/notify"

export default {
  name: 'SafaricomSTKPush',
  mixins: [notify],
  data() {
    return {
      payment: {
        phone_number: '',
        amount: null,
        description: ''
      },
      isLoading: false,
      paymentStatus: null
    }
  },
  computed: {
    ...mapState({
      baseUrl: state => state.baseUrl
    })
  },
  methods: {
    async initiatePayment() {
      this.isLoading = true
      this.paymentStatus = null

      try {
        const response = await this.$http.post(
          `${this.baseUrl}/safaricom/stk-push`,
          this.payment
        )
        this.paymentStatus = response.data.data
        this.alertNotify('success', 'Payment initiated successfully')
      } catch (error) {
        this.alertNotify('error', error.response?.data?.message || 'Failed to initiate payment')
      } finally {
        this.isLoading = false
      }
    },
    getStatusAlertClass(status) {
      const classes = {
        initiated: 'alert-info',
        pending: 'alert-warning',
        succeeded: 'alert-success',
        failed: 'alert-danger'
      }
      return classes[status] || 'alert-secondary'
    },
    getStatusTitle(status) {
      const titles = {
        initiated: 'Payment Initiated',
        pending: 'Payment Pending',
        succeeded: 'Payment Successful',
        failed: 'Payment Failed'
      }
      return titles[status] || 'Unknown Status'
    },
    getStatusMessage(status) {
      const messages = {
        initiated: 'Please check your phone for the M-PESA prompt.',
        pending: 'Waiting for your confirmation on your phone.',
        succeeded: 'The payment has been completed successfully.',
        failed: 'The payment could not be completed. Please try again.'
      }
      return messages[status] || 'Unknown status'
    }
  }
}
</script>

<style scoped>
.stk-push {
  padding: 20px;
}
.alert {
  margin-bottom: 0;
}
</style> 