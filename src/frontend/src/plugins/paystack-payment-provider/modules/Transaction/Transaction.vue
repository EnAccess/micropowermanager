<template>
  <div>
    <div class="md-layout md-gutter">
      <div
        class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-100"
      >
        <md-card>
          <md-card-header>
            <div class="md-title">{{ $tc("menu.subMenu.Transactions") }}</div>
          </md-card-header>
          <md-card-content>
            <div class="md-layout md-gutter">
              <div class="md-layout-item md-size-100">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                  <h3>{{ $tc("phrases.paystackTransactions") }}</h3>
                  <md-button
                    class="md-icon-button"
                    @click="refreshTransactions"
                  >
                    <md-icon>refresh</md-icon>
                  </md-button>
                </div>
                
                <md-table style="width: 100%">

                  <md-table-row>
                    <md-table-head md-sort-by="id">ID</md-table-head>
                    <md-table-head md-sort-by="order_id">Order ID</md-table-head>
                    <md-table-head md-sort-by="amount">Amount</md-table-head>
                    <md-table-head md-sort-by="status">Status</md-table-head>
                    <md-table-head md-sort-by="customer_id">Customer ID</md-table-head>
                    <md-table-head md-sort-by="serial_id">Serial ID</md-table-head>
                    <md-table-head md-sort-by="equipment_type">Equipment Type</md-table-head>
                    <md-table-head md-sort-by="created_at">Created</md-table-head>
                    <md-table-head>Actions</md-table-head>
                  </md-table-row>

                  <md-table-row
                    v-for="item in transactions"
                    :key="item.id"
                  >
                    <md-table-cell md-label="ID" md-sort-by="id">
                      {{ item.id }}
                    </md-table-cell>
                    <md-table-cell md-label="Order ID" md-sort-by="order_id">
                      {{ item.order_id }}
                    </md-table-cell>
                    <md-table-cell md-label="Amount" md-sort-by="amount">
                      {{ formatAmount(item.amount, item.currency) }}
                    </md-table-cell>
                    <md-table-cell md-label="Status" md-sort-by="status">
                      <md-chip
                        :class="getStatusClass(item.status)"
                        md-label=""
                      >
                        {{ getStatusText(item.status) }}
                      </md-chip>
                    </md-table-cell>
                    <md-table-cell md-label="Customer ID" md-sort-by="customer_id">
                      {{ item.customer_id }}
                    </md-table-cell>
                    <md-table-cell md-label="Serial ID" md-sort-by="serial_id">
                      {{ item.serial_id }}
                    </md-table-cell>
                    <md-table-cell md-label="Equipment Type" md-sort-by="equipment_type">
                      {{ item.equipment_type }}
                    </md-table-cell>
                    <md-table-cell md-label="Created" md-sort-by="created_at">
                      {{ formatDate(item.created_at) }}
                    </md-table-cell>
                    <md-table-cell md-label="Actions">
                      <md-button
                        class="md-icon-button md-primary"
                        @click="viewTransaction(item)"
                        title="View Details"
                      >
                        <md-icon>visibility</md-icon>
                      </md-button>
                      <md-button
                        v-if="item.status === 0"
                        class="md-icon-button md-accent"
                        @click="verifyTransaction(item)"
                        title="Verify Transaction"
                      >
                        <md-icon>check_circle</md-icon>
                      </md-button>
                    </md-table-cell>
                  </md-table-row>
                </md-table>
              </div>
            </div>
          </md-card-content>
        </md-card>
      </div>
    </div>

    <!-- Transaction Details Dialog -->
    <md-dialog :md-active.sync="showTransactionDialog" class="transaction-dialog">
      <md-dialog-title>{{ $tc("phrases.transactionDetails") }}</md-dialog-title>
      <md-dialog-content>
        <div v-if="selectedTransaction" class="transaction-details">
          <div class="detail-row">
            <span class="detail-label">ID:</span>
            <span class="detail-value">{{ selectedTransaction.id }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Order ID:</span>
            <span class="detail-value">{{ selectedTransaction.order_id }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Reference ID:</span>
            <span class="detail-value">{{ selectedTransaction.reference_id }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Amount:</span>
            <span class="detail-value">
              {{ formatAmount(selectedTransaction.amount, selectedTransaction.currency) }}
            </span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Status:</span>
            <span class="detail-value">
              <md-chip :class="getStatusClass(selectedTransaction.status)">
                {{ getStatusText(selectedTransaction.status) }}
              </md-chip>
            </span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Customer ID:</span>
            <span class="detail-value">{{ selectedTransaction.customer_id }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Serial ID:</span>
            <span class="detail-value">{{ selectedTransaction.serial_id }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Equipment Type:</span>
            <span class="detail-value">{{ selectedTransaction.equipment_type }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Paystack Reference:</span>
            <span class="detail-value">{{ selectedTransaction.paystack_reference || 'N/A' }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">External Transaction ID:</span>
            <span class="detail-value">{{ selectedTransaction.external_transaction_id || 'N/A' }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Payment URL:</span>
            <span class="detail-value">
              <a v-if="selectedTransaction.payment_url" :href="selectedTransaction.payment_url" target="_blank">
                {{ selectedTransaction.payment_url }}
              </a>
              <span v-else>N/A</span>
            </span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Created:</span>
            <span class="detail-value">{{ formatDate(selectedTransaction.created_at) }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Updated:</span>
            <span class="detail-value">{{ formatDate(selectedTransaction.updated_at) }}</span>
          </div>
        </div>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button class="md-primary" @click="showTransactionDialog = false">
          {{ $tc("words.close") }}
        </md-button>
      </md-dialog-actions>
    </md-dialog>
  </div>
</template>

<script>
import { TransactionService } from "../../services/TransactionService"
import { notify } from "@/mixins/notify"

export default {
  name: "Transaction",
  mixins: [notify],
  data() {
    return {
      transactionService: new TransactionService(),
      transactions: [],
      loading: false,
      showTransactionDialog: false,
      selectedTransaction: null,
    }
  },
  mounted() {
    this.getTransactions()
  },
  methods: {
    async getTransactions() {
      this.loading = true
      try {
        const response = await this.transactionService.getTransactions()
        this.transactions = response.data?.data || []
      } catch (error) {
        this.alertNotify("error", "Failed to fetch transactions")
        console.error("Error fetching transactions:", error)
      } finally {
        this.loading = false
      }
    },
    async refreshTransactions() {
      await this.getTransactions()
    },
    async verifyTransaction(transaction) {
      if (!transaction.paystack_reference) {
        this.alertNotify("error", "No Paystack reference found for this transaction")
        return
      }

      try {
        await this.transactionService.verifyTransaction(transaction.paystack_reference)
        this.alertNotify("success", "Transaction verified successfully")
        await this.getTransactions()
      } catch (error) {
        this.alertNotify("error", "Failed to verify transaction")
        console.error("Error verifying transaction:", error)
      }
    },
    viewTransaction(transaction) {
      this.selectedTransaction = transaction
      this.showTransactionDialog = true
    },
    getStatusClass(status) {
      switch (status) {
        case 0:
          return "md-warning"
        case 1:
          return "md-success"
        case 2:
          return "md-error"
        case 3:
          return "md-info"
        default:
          return "md-default"
      }
    },
    getStatusText(status) {
      switch (status) {
        case 0:
          return "Requested"
        case 1:
          return "Success"
        case 2:
          return "Failed"
        case 3:
          return "Completed"
        default:
          return "Unknown"
      }
    },
    formatAmount(amount, currency) {
      return new Intl.NumberFormat("en-NG", {
        style: "currency",
        currency: currency || "NGN",
      }).format(amount)
    },
    formatDate(dateString) {
      if (!dateString) return "N/A"
      return new Date(dateString).toLocaleString()
    },
  },
}
</script>

<style scoped>
.transaction-dialog {
  min-width: 600px;
}

.transaction-details {
  padding: 1rem 0;
}

.detail-row {
  display: flex;
  margin-bottom: 0.5rem;
  border-bottom: 1px solid #eee;
  padding-bottom: 0.5rem;
}

.detail-label {
  font-weight: bold;
  min-width: 150px;
  color: #666;
}

.detail-value {
  flex: 1;
  margin-left: 1rem;
}

.md-table {
  margin-top: 1rem;
}
</style>
