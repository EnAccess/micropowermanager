<template>
  <div>
    <div class="overview-line">
      <div class="md-layout md-gutter">
        <div
          class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-25"
        >
          <box
            :box-color="'green'"
            :center-text="true"
            :header-text="'Total Transactions'"
            :sub-text="stats.totalTransactions.toString()"
            :box-icon="'payment'"
          />
        </div>
        <div
          class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-25"
        >
          <box
            :box-color="'blue'"
            :center-text="true"
            :header-text="'Successful Payments'"
            :sub-text="stats.successfulTransactions.toString()"
            :box-icon="'check_circle'"
          />
        </div>
        <div
          class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-25"
        >
          <box
            :box-color="'orange'"
            :center-text="true"
            :header-text="'Pending Payments'"
            :sub-text="stats.pendingTransactions.toString()"
            :box-icon="'schedule'"
          />
        </div>
        <div
          class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-25"
        >
          <box
            :box-color="credentialStatus.color"
            :center-text="true"
            :header-text="'Configuration'"
            :sub-text="credentialStatus.text"
            :box-icon="'settings'"
          />
        </div>
      </div>
    </div>

    <div v-if="recentTransactions.length > 0" class="overview-line">
      <widget
        title="Latest transactions"
        color="primary"
        button-text="View all"
        button-icon="visibility"
        @widgetAction="goToTransactions"
      >
        <md-table>
          <md-table-row>
            <md-table-head>ID</md-table-head>
            <md-table-head>Amount</md-table-head>
            <md-table-head>Status</md-table-head>
            <md-table-head>Customer</md-table-head>
            <md-table-head>Created</md-table-head>
          </md-table-row>
          <md-table-row
            v-for="item in recentTransactions"
            :key="item.id"
            style="cursor: pointer"
            @click.native="goToTransactions"
          >
            <md-table-cell md-label="ID">{{ item.id }}</md-table-cell>
            <md-table-cell md-label="Amount">
              {{ formatAmount(item.amount, item.currency) }}
            </md-table-cell>
            <md-table-cell md-label="Status">
              <md-icon :style="{ color: getStatusIcon(item.status).color }">
                {{ getStatusIcon(item.status).icon }}
                <md-tooltip md-direction="right">
                  {{ getStatusText(item.status) }}
                </md-tooltip>
              </md-icon>
            </md-table-cell>
            <md-table-cell md-label="Customer">
              {{ item.customer_id || "—" }}
            </md-table-cell>
            <md-table-cell md-label="Created">
              {{ formatDate(item.created_at) }}
            </md-table-cell>
          </md-table-row>
        </md-table>
      </widget>
    </div>

    <div v-if="!isFullyConfigured" class="overview-line">
      <md-card>
        <md-card-header>
          <div class="md-title">{{ setupPrompt.title }}</div>
        </md-card-header>
        <md-card-content>
          {{ setupPrompt.description }}
        </md-card-content>
        <md-card-actions>
          <md-button class="md-raised md-primary" @click="goToCredentials">
            {{ setupPrompt.cta }}
          </md-button>
        </md-card-actions>
      </md-card>
    </div>
  </div>
</template>

<script>
import { CredentialService } from "../../services/CredentialService.js"
import { TransactionService } from "../../services/TransactionService.js"

import { notify } from "@/mixins/notify.js"
import Box from "@/shared/Box.vue"
import { EventBus } from "@/shared/eventbus.js"
import Widget from "@/shared/Widget.vue"

const RECENT_LIMIT = 5

export default {
  name: "Overview",
  mixins: [notify],
  components: { Box, Widget },
  data() {
    return {
      transactionService: new TransactionService(),
      credentialService: new CredentialService(),
      credentials: {
        consumerKeySet: false,
        consumerSecretSet: false,
        ipnId: null,
      },
      stats: {
        totalTransactions: 0,
        successfulTransactions: 0,
        pendingTransactions: 0,
      },
      recentTransactions: [],
      loading: false,
    }
  },
  async mounted() {
    await this.loadStats()
    await this.checkCredentialStatus()

    EventBus.$on("credential-updated", this.onCredentialUpdated)
  },
  beforeDestroy() {
    EventBus.$off("credential-updated", this.onCredentialUpdated)
  },
  computed: {
    hasKeys() {
      return (
        this.credentials.consumerKeySet && this.credentials.consumerSecretSet
      )
    },
    isFullyConfigured() {
      return this.hasKeys && Boolean(this.credentials.ipnId)
    },
    credentialStatus() {
      if (!this.hasKeys) {
        return { color: "red", text: "Not Configured" }
      }
      if (!this.credentials.ipnId) {
        return { color: "orange", text: "IPN Missing" }
      }
      return { color: "green", text: "Configured" }
    },
    setupPrompt() {
      if (!this.hasKeys) {
        return {
          title: "Finish setting up Pesapal",
          description:
            "Add your PesaPal consumer key and consumer secret so customers can start paying.",
          cta: "Configure credentials",
        }
      }
      return {
        title: "IPN registration pending",
        description:
          "Credentials are saved but PesaPal couldn't register the IPN callback. Re-save credentials to retry.",
        cta: "Open credentials",
      }
    },
  },
  methods: {
    async loadStats() {
      try {
        this.loading = true
        const response = await this.transactionService.getTransactions()
        const transactions = response.data.data || []

        this.stats.totalTransactions = transactions.length
        this.stats.successfulTransactions = transactions.filter(
          (t) => t.status === 1 || t.status === 2,
        ).length
        this.stats.pendingTransactions = transactions.filter(
          (t) => t.status === 0,
        ).length

        this.recentTransactions = [...transactions]
          .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
          .slice(0, RECENT_LIMIT)
      } catch (error) {
        console.error("Error loading stats:", error)
        this.alertNotify("error", "Failed to load transaction statistics")
      } finally {
        this.loading = false
      }
    },

    async checkCredentialStatus() {
      try {
        const credential = await this.credentialService.getCredential()
        if (credential) {
          this.credentials = {
            consumerKeySet: Boolean(credential.consumerKeySet),
            consumerSecretSet: Boolean(credential.consumerSecretSet),
            ipnId: credential.ipnId || null,
          }
        }
      } catch (error) {
        console.error("Error checking credentials:", error)
        this.credentials = {
          consumerKeySet: false,
          consumerSecretSet: false,
          ipnId: null,
        }
      }
    },

    onCredentialUpdated() {
      this.checkCredentialStatus()
    },

    goToCredentials() {
      this.$router.push("/pesapal/credential")
    },

    goToTransactions() {
      this.$router.push("/pesapal/transactions")
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
      if (!dateString) return "—"
      return new Date(dateString).toLocaleString()
    },
  },
}
</script>

<style scoped lang="scss">
.overview-line {
  margin-top: 1rem;
}
</style>
