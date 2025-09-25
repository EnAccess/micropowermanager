<template>
  <div class="safaricom-mobile-money">
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Safaricom M-PESA Settings</h3>
          </div>
          <div class="card-body">
            <form @submit.prevent="saveSettings">
              <div class="form-group">
                <label>Consumer Key</label>
                <input
                  v-model="settings.consumer_key"
                  type="text"
                  class="form-control"
                  required
                />
              </div>
              <div class="form-group">
                <label>Consumer Secret</label>
                <input
                  v-model="settings.consumer_secret"
                  type="password"
                  class="form-control"
                  required
                />
              </div>
              <div class="form-group">
                <label>Passkey</label>
                <input
                  v-model="settings.passkey"
                  type="password"
                  class="form-control"
                  required
                />
              </div>
              <div class="form-group">
                <label>Shortcode</label>
                <input
                  v-model="settings.shortcode"
                  type="text"
                  class="form-control"
                  required
                />
              </div>
              <div class="form-group">
                <label>Environment</label>
                <select v-model="settings.environment" class="form-control">
                  <option value="sandbox">Sandbox</option>
                  <option value="production">Production</option>
                </select>
              </div>
              <button type="submit" class="btn btn-primary">Save Settings</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="row mt-4">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Recent Transactions</h3>
          </div>
          <div class="card-body">
            <table class="table">
              <thead>
                <tr>
                  <th>Reference ID</th>
                  <th>Amount</th>
                  <th>Phone Number</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="transaction in transactions" :key="transaction.reference_id">
                  <td>{{ transaction.reference_id }}</td>
                  <td>{{ transaction.amount }}</td>
                  <td>{{ transaction.phone_number }}</td>
                  <td>
                    <span :class="getStatusClass(transaction.status)">
                      {{ transaction.status }}
                    </span>
                  </td>
                  <td>{{ formatDate(transaction.created_at) }}</td>
                  <td>
                    <button
                      class="btn btn-sm btn-info"
                      @click="checkStatus(transaction.reference_id)"
                    >
                      Check Status
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState } from 'vuex'
import moment from 'moment'
import { notify } from "@/mixins/notify"

export default {
  name: 'SafaricomMobileMoneyOverview',
  mixins: [notify],
  data() {
    return {
      settings: {
        consumer_key: '',
        consumer_secret: '',
        passkey: '',
        shortcode: '',
        environment: 'sandbox'
      },
      transactions: []
    }
  },
  computed: {
    ...mapState({
      baseUrl: state => state.baseUrl
    })
  },
  methods: {
    async saveSettings() {
      try {
        const response = await this.$http.post(
          `${this.baseUrl}/safaricom/settings`,
          this.settings
        )
        this.alertNotify('success', 'Settings saved successfully')
      } catch (error) {
        this.alertNotify('error', error.response?.data?.message || 'Failed to save settings')
      }
    },
    async loadSettings() {
      try {
        const response = await this.$http.get(`${this.baseUrl}/safaricom/settings`)
        this.settings = response.data.data
      } catch (error) {
        this.alertNotify('error', 'Failed to load settings')
      }
    },
    async loadTransactions() {
      try {
        const response = await this.$http.get(`${this.baseUrl}/safaricom/transactions`)
        this.transactions = response.data.data
      } catch (error) {
        this.alertNotify('error', 'Failed to load transactions')
      }
    },
    async checkStatus(referenceId) {
      try {
        const response = await this.$http.get(
          `${this.baseUrl}/safaricom/transaction/${referenceId}/status`
        )
        this.alertNotify('success', `Transaction status: ${response.data.data.status}`)
        await this.loadTransactions()
      } catch (error) {
        this.alertNotify('error', error.response?.data?.message || 'Failed to check status')
      }
    },
    getStatusClass(status) {
      const classes = {
        initiated: 'badge badge-info',
        pending: 'badge badge-warning',
        succeeded: 'badge badge-success',
        failed: 'badge badge-danger'
      }
      return classes[status] || 'badge badge-secondary'
    },
    formatDate(date) {
      return moment(date).format('YYYY-MM-DD HH:mm:ss')
    }
  },
  mounted() {
    this.loadSettings()
    this.loadTransactions()
  }
}
</script>

<style scoped>
.safaricom-mobile-money {
  padding: 20px;
}
.badge {
  padding: 5px 10px;
  border-radius: 4px;
}
</style> 