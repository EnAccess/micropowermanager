import { mapState } from 'vuex'

export default {
  name: 'SafaricomMobileMoneyApi',
  computed: {
    ...mapState({
      baseUrl: state => state.baseUrl
    })
  },
  methods: {
    // Settings
    async getSettings() {
      const response = await this.$http.get(`${this.baseUrl}/safaricom/settings`)
      return response.data.data
    },
    async saveSettings(settings) {
      const response = await this.$http.post(`${this.baseUrl}/safaricom/settings`, settings)
      return response.data.data
    },

    // Transactions
    async getTransactions(page = 1, limit = 10) {
      const response = await this.$http.get(`${this.baseUrl}/safaricom/transactions`, {
        params: { page, limit }
      })
      return response.data
    },
    async getTransactionStatus(referenceId) {
      const response = await this.$http.get(
        `${this.baseUrl}/safaricom/transaction/${referenceId}/status`
      )
      return response.data.data
    },

    // STK Push
    async initiateSTKPush(paymentDetails) {
      const response = await this.$http.post(`${this.baseUrl}/safaricom/stk-push`, paymentDetails)
      return response.data.data
    },

    // Webhook
    async registerWebhook(url) {
      const response = await this.$http.post(`${this.baseUrl}/safaricom/webhook/register`, { url })
      return response.data.data
    },
    async unregisterWebhook() {
      const response = await this.$http.post(`${this.baseUrl}/safaricom/webhook/unregister`)
      return response.data.data
    },

    // Utility methods
    formatPhoneNumber(phoneNumber) {
      // Ensure phone number is in format 254XXXXXXXXX
      if (!phoneNumber) return ''
      const cleaned = phoneNumber.replace(/\D/g, '')
      if (cleaned.startsWith('0')) {
        return '254' + cleaned.substring(1)
      }
      if (cleaned.startsWith('254')) {
        return cleaned
      }
      return '254' + cleaned
    },

    formatAmount(amount) {
      // Format amount to 2 decimal places
      return parseFloat(amount).toFixed(2)
    }
  }
} 