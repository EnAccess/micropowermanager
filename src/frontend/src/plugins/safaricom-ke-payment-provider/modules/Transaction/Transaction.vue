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
        <div v-if="selectedTransaction">
          <div class="md-layout">
            <div class="md-layout-item md-subheader">ID</div>
            <div class="md-layout-item md-subheader">
              {{ selectedTransaction.id }}
            </div>
          </div>
          <md-divider />
          <div class="md-layout">
            <div class="md-layout-item md-subheader">Reference</div>
            <div class="md-layout-item md-subheader">
              {{ selectedTransaction.reference_id }}
            </div>
          </div>
          <md-divider />
          <div class="md-layout">
            <div class="md-layout-item md-subheader">Order ID</div>
            <div class="md-layout-item md-subheader">
              {{ selectedTransaction.order_id }}
            </div>
          </div>
          <md-divider />
          <div class="md-layout">
            <div class="md-layout-item md-subheader">Amount</div>
            <div class="md-layout-item md-subheader">
              {{
                formatAmount(
                  selectedTransaction.amount,
                  selectedTransaction.currency,
                )
              }}
            </div>
          </div>
          <md-divider />
          <div class="md-layout">
            <div class="md-layout-item md-subheader">Status</div>
            <div class="md-layout-item md-subheader">
              {{ getStatusText(selectedTransaction.status) }}
            </div>
          </div>
          <md-divider />
          <div class="md-layout">
            <div class="md-layout-item md-subheader">Customer ID</div>
            <div class="md-layout-item md-subheader">
              {{ selectedTransaction.customer_id }}
            </div>
          </div>
          <md-divider />
          <div class="md-layout">
            <div class="md-layout-item md-subheader">Serial ID</div>
            <div class="md-layout-item md-subheader">
              {{ selectedTransaction.serial_id || "—" }}
            </div>
          </div>
          <md-divider />
          <div class="md-layout">
            <div class="md-layout-item md-subheader">Equipment Type</div>
            <div class="md-layout-item md-subheader">
              {{ selectedTransaction.device_type || "—" }}
            </div>
          </div>
          <md-divider />
          <div class="md-layout">
            <div class="md-layout-item md-subheader">Phone</div>
            <div class="md-layout-item md-subheader">
              {{ selectedTransaction.phone_number }}
            </div>
          </div>
          <md-divider />
          <div class="md-layout">
            <div class="md-layout-item md-subheader">M-Pesa Receipt</div>
            <div class="md-layout-item md-subheader">
              {{ selectedTransaction.mpesa_receipt_number || "—" }}
            </div>
          </div>
          <md-divider />
          <div class="md-layout">
            <div class="md-layout-item md-subheader">CheckoutRequestID</div>
            <div class="md-layout-item md-subheader">
              {{ selectedTransaction.checkout_request_id || "—" }}
            </div>
          </div>
          <md-divider />
          <div class="md-layout">
            <div class="md-layout-item md-subheader">MerchantRequestID</div>
            <div class="md-layout-item md-subheader">
              {{ selectedTransaction.merchant_request_id || "—" }}
            </div>
          </div>
          <md-divider />
          <div class="md-layout">
            <div class="md-layout-item md-subheader">Transaction Date</div>
            <div class="md-layout-item md-subheader">
              {{ formatDate(selectedTransaction.transaction_date) }}
            </div>
          </div>
          <md-divider />
          <div class="md-layout">
            <div class="md-layout-item md-subheader">Created</div>
            <div class="md-layout-item md-subheader">
              {{ formatDate(selectedTransaction.created_at) }}
            </div>
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

.md-table {
  margin-top: 1rem;
}
</style>
