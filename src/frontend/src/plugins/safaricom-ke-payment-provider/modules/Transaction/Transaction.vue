<template>
  <div>
    <widget
      id="safaricom-transactions"
      :title="title"
      :paginator="transactionService.paginator"
      :subscriber="subscriber"
      color="primary"
    >
      <md-table style="width: 100%">
        <md-table-row>
          <md-table-head md-sort-by="id">ID</md-table-head>
          <md-table-head md-sort-by="reference_id">Reference</md-table-head>
          <md-table-head md-sort-by="amount">Amount</md-table-head>
          <md-table-head md-sort-by="status">Status</md-table-head>
          <md-table-head md-sort-by="phone_number">Phone</md-table-head>
          <md-table-head md-sort-by="mpesa_receipt_number">
            M-Pesa Receipt
          </md-table-head>
          <md-table-head md-sort-by="serial_id">Serial ID</md-table-head>
          <md-table-head md-sort-by="created_at">Created</md-table-head>
          <md-table-head>Actions</md-table-head>
        </md-table-row>

        <md-table-row v-for="item in transactionService.list" :key="item.id">
          <md-table-cell md-label="ID" md-sort-by="id">
            {{ item.id }}
          </md-table-cell>
          <md-table-cell md-label="Reference" md-sort-by="reference_id">
            {{ item.reference_id }}
          </md-table-cell>
          <md-table-cell md-label="Amount" md-sort-by="amount">
            {{ formatAmount(item.amount, item.currency) }}
          </md-table-cell>
          <md-table-cell md-label="Status" md-sort-by="status">
            <md-icon :style="{ color: getStatusIcon(item.status).color }">
              {{ getStatusIcon(item.status).icon }}
              <md-tooltip md-direction="right">
                {{ getStatusText(item.status) }}
              </md-tooltip>
            </md-icon>
          </md-table-cell>
          <md-table-cell md-label="Phone" md-sort-by="phone_number">
            {{ item.phone_number }}
          </md-table-cell>
          <md-table-cell
            md-label="M-Pesa Receipt"
            md-sort-by="mpesa_receipt_number"
          >
            {{ item.mpesa_receipt_number || "—" }}
          </md-table-cell>
          <md-table-cell md-label="Serial ID" md-sort-by="serial_id">
            {{ item.serial_id || "—" }}
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
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>

    <md-dialog
      :md-active.sync="showTransactionDialog"
      class="transaction-dialog"
    >
      <md-dialog-title>{{ $tc("phrases.transactionDetails") }}</md-dialog-title>
      <md-dialog-content>
        <div v-if="selectedTransaction" class="transaction-details">
          <div class="detail-row">
            <span class="detail-label">ID:</span>
            <span class="detail-value">{{ selectedTransaction.id }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Reference:</span>
            <span class="detail-value">
              {{ selectedTransaction.reference_id }}
            </span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Order ID:</span>
            <span class="detail-value">{{ selectedTransaction.order_id }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Amount:</span>
            <span class="detail-value">
              {{
                formatAmount(
                  selectedTransaction.amount,
                  selectedTransaction.currency,
                )
              }}
            </span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Status:</span>
            <span class="detail-value">
              {{ getStatusText(selectedTransaction.status) }}
            </span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Customer ID:</span>
            <span class="detail-value">
              {{ selectedTransaction.customer_id }}
            </span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Serial ID:</span>
            <span class="detail-value">
              {{ selectedTransaction.serial_id || "—" }}
            </span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Equipment Type:</span>
            <span class="detail-value">
              {{ selectedTransaction.device_type || "—" }}
            </span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Phone:</span>
            <span class="detail-value">
              {{ selectedTransaction.phone_number }}
            </span>
          </div>
          <div class="detail-row">
            <span class="detail-label">M-Pesa Receipt:</span>
            <span class="detail-value">
              {{ selectedTransaction.mpesa_receipt_number || "—" }}
            </span>
          </div>
          <div class="detail-row">
            <span class="detail-label">CheckoutRequestID:</span>
            <span class="detail-value">
              {{ selectedTransaction.checkout_request_id || "—" }}
            </span>
          </div>
          <div class="detail-row">
            <span class="detail-label">MerchantRequestID:</span>
            <span class="detail-value">
              {{ selectedTransaction.merchant_request_id || "—" }}
            </span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Transaction Date:</span>
            <span class="detail-value">
              {{ formatDate(selectedTransaction.transaction_date) }}
            </span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Created:</span>
            <span class="detail-value">
              {{ formatDate(selectedTransaction.created_at) }}
            </span>
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
import { TransactionService } from "../../services/TransactionService.js"

import { notify } from "@/mixins/notify.js"
import { EventBus } from "@/shared/eventbus.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "Transaction",
  mixins: [notify],
  components: { Widget },
  data() {
    return {
      transactionService: new TransactionService(),
      subscriber: "safaricom-transactions",
      title: "Safaricom Transactions",
      showTransactionDialog: false,
      selectedTransaction: null,
    }
  },
  mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
  },
  methods: {
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) return
      this.transactionService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.transactionService.list.length,
      )
    },
    viewTransaction(transaction) {
      this.selectedTransaction = transaction
      this.showTransactionDialog = true
    },
    getStatusIcon(status) {
      switch (status) {
        case 0:
          return { icon: "contact_support", color: "goldenrod" }
        case 1:
        case 2:
          return { icon: "check_circle_outline", color: "green" }
        case -1:
          return { icon: "cancel", color: "red" }
        case 3:
          return { icon: "do_not_disturb_on", color: "grey" }
        default:
          return { icon: "help_outline", color: "grey" }
      }
    },
    getStatusText(status) {
      switch (status) {
        case 0:
          return "Requested"
        case 1:
          return "Success"
        case 2:
          return "Completed"
        case -1:
          return "Failed"
        case 3:
          return "Abandoned"
        default:
          return "Unknown"
      }
    },
    formatAmount(amount, currency) {
      try {
        return new Intl.NumberFormat("en-KE", {
          style: "currency",
          currency: currency || "KES",
        }).format(amount)
      } catch (error) {
        return `${currency || "KES"} ${amount}`
      }
    },
    formatDate(dateString) {
      if (!dateString) return "N/A"
      return new Date(dateString).toLocaleString()
    },
  },
}
</script>

<style scoped lang="scss">
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
