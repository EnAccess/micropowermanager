<template>
  <div>
    <widget
      id="pesapal-transactions"
      :title="title"
      :paginator="transactionService.paginator"
      :subscriber="subscriber"
      color="primary"
    >
      <md-table style="width: 100%">
        <md-table-row>
          <md-table-head md-sort-by="id">ID</md-table-head>
          <md-table-head md-sort-by="order_id">Order ID</md-table-head>
          <md-table-head md-sort-by="amount">Amount</md-table-head>
          <md-table-head md-sort-by="status">Status</md-table-head>
          <md-table-head md-sort-by="customer_id">Customer ID</md-table-head>
          <md-table-head md-sort-by="serial_id">Serial ID</md-table-head>
          <md-table-head md-sort-by="device_type">Device Type</md-table-head>
          <md-table-head md-sort-by="created_at">Created</md-table-head>
          <md-table-head>Actions</md-table-head>
        </md-table-row>

        <md-table-row v-for="item in transactionService.list" :key="item.id">
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
            <md-icon :style="{ color: getStatusIcon(item.status).color }">
              {{ getStatusIcon(item.status).icon }}
              <md-tooltip md-direction="right">
                {{ getStatusText(item.status) }}
              </md-tooltip>
            </md-icon>
          </md-table-cell>
          <md-table-cell md-label="Customer ID" md-sort-by="customer_id">
            {{ item.customer_id }}
          </md-table-cell>
          <md-table-cell md-label="Serial ID" md-sort-by="serial_id">
            {{ item.serial_id }}
          </md-table-cell>
          <md-table-cell md-label="Device Type" md-sort-by="device_type">
            {{ item.device_type }}
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
              v-if="item.status === 0 && item.order_tracking_id"
              class="md-icon-button md-accent"
              @click="verifyTransaction(item)"
              title="Verify Transaction"
            >
              <md-icon>check_circle</md-icon>
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
            <div class="md-layout-item md-subheader">Order ID</div>
            <div class="md-layout-item md-subheader">
              {{ selectedTransaction.order_id }}
            </div>
          </div>
          <md-divider />
          <div class="md-layout">
            <div class="md-layout-item md-subheader">Reference ID</div>
            <div class="md-layout-item md-subheader">
              {{ selectedTransaction.reference_id }}
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
              {{ selectedTransaction.serial_id }}
            </div>
          </div>
          <md-divider />
          <div class="md-layout">
            <div class="md-layout-item md-subheader">Equipment Type</div>
            <div class="md-layout-item md-subheader">
              {{ selectedTransaction.device_type }}
            </div>
          </div>
          <md-divider />
          <div class="md-layout">
            <div class="md-layout-item md-subheader">Order Tracking ID</div>
            <div class="md-layout-item md-subheader">
              {{ selectedTransaction.order_tracking_id || "N/A" }}
            </div>
          </div>
          <md-divider />
          <div class="md-layout">
            <div class="md-layout-item md-subheader">Merchant Reference</div>
            <div class="md-layout-item md-subheader">
              {{ selectedTransaction.merchant_reference || "N/A" }}
            </div>
          </div>
          <md-divider />
          <div class="md-layout">
            <div class="md-layout-item md-subheader">
              External Transaction ID
            </div>
            <div class="md-layout-item md-subheader">
              {{ selectedTransaction.external_transaction_id || "N/A" }}
            </div>
          </div>
          <md-divider />
          <div class="md-layout">
            <div class="md-layout-item md-subheader">Payment URL</div>
            <div class="md-layout-item md-subheader">
              <a
                v-if="selectedTransaction.payment_url"
                :href="selectedTransaction.payment_url"
                target="_blank"
              >
                {{ selectedTransaction.payment_url }}
              </a>
              <span v-else>N/A</span>
            </div>
          </div>
          <md-divider />
          <div class="md-layout">
            <div class="md-layout-item md-subheader">Created</div>
            <div class="md-layout-item md-subheader">
              {{ formatDate(selectedTransaction.created_at) }}
            </div>
          </div>
          <md-divider />
          <div class="md-layout">
            <div class="md-layout-item md-subheader">Updated</div>
            <div class="md-layout-item md-subheader">
              {{ formatDate(selectedTransaction.updated_at) }}
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
      subscriber: "pesapal-transactions",
      title: "Pesapal Transactions",
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
    async verifyTransaction(transaction) {
      if (!transaction.order_tracking_id) {
        this.alertNotify(
          "error",
          "No PesaPal order tracking ID found for this transaction",
        )
        return
      }

      try {
        await this.transactionService.verifyTransaction(
          transaction.order_tracking_id,
        )
        this.alertNotify("success", "Transaction verified successfully")
        EventBus.$emit("widgetContentLoaded", this.subscriber, 1)
      } catch (error) {
        this.alertNotify("error", "Failed to verify transaction")
        console.error("Error verifying transaction:", error)
      }
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
