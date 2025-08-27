<template>
  <div>
    <!-- Payment Statistics -->
    <div class="overview-line">
      <div class="md-layout md-gutter">
        <div class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-25">
          <box :box-color="'green'" :center-text="true" :header-text="'Total Transactions'"
            :sub-text="stats.totalTransactions.toString()" :box-icon="'payment'" />
        </div>
        <div class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-25">
          <box :box-color="'blue'" :center-text="true" :header-text="'Successful Payments'"
            :sub-text="stats.successfulTransactions.toString()" :box-icon="'check_circle'" />
        </div>
        <div class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-25">
          <box :box-color="'orange'" :center-text="true" :header-text="'Pending Payments'"
            :sub-text="stats.pendingTransactions.toString()" :box-icon="'schedule'" />
        </div>
        <div class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-25">
          <box :box-color="credentialStatus.color" :center-text="true" :header-text="'Configuration'"
            :sub-text="credentialStatus.text" :box-icon="'settings'" />
        </div>
      </div>
    </div>

    <!-- Configuration Section -->
    <div class="overview-line">
      <div class="md-layout md-gutter">
        <div class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-100">
          <credential style="height: 100% !important" />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Box from "@/shared/Box.vue"
import Credential from "./Credential.vue"
import { TransactionService } from "../../services/TransactionService"
import { CredentialService } from "../../services/CredentialService"
import { notify } from "@/mixins/notify"
import { EventBus } from "@/shared/eventbus"

export default {
  name: "Overview",
  mixins: [notify],
  components: { Box, Credential },
  data() {
    return {
      transactionService: new TransactionService(),
      credentialService: new CredentialService(),
      credentials: {
        secretKey: "",
        publicKey: "",
        webhookSecret: "",
        callbackUrl: "",
        merchantName: "",
        environment: "test",
      },
      stats: {
        totalTransactions: 0,
        successfulTransactions: 0,
        pendingTransactions: 0,
      },
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
    credentialStatus() {
      if (!this.credentials.secretKey ||
        !this.credentials.publicKey) {
        return { color: 'red', text: 'Not Configured' }
      }
      return { color: 'green', text: 'Configured' }
    },
  },
  methods: {
    async loadStats() {
      try {
        this.loading = true
        const response = await this.transactionService.getTransactions()
        const transactions = response.data.data || []

        this.stats.totalTransactions = transactions.length
        this.stats.successfulTransactions = transactions.filter(t => t.status === 1).length
        this.stats.pendingTransactions = transactions.filter(t => t.status === 0).length
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
            secretKey: credential.secretKey || "",
            publicKey: credential.publicKey || "",
            webhookSecret: credential.webhookSecret || "",
            callbackUrl: credential.callbackUrl || "",
            merchantName: credential.merchantName || "",
            environment: credential.environment || "test",
          }
        }
      } catch (error) {
        console.error("Error checking credentials:", error)
        this.credentials = {
          secretKey: "",
          publicKey: "",
          webhookSecret: "",
          callbackUrl: "",
          merchantName: "",
          environment: "test",
        }
      }
    },

    onCredentialUpdated() {
      this.checkCredentialStatus()
    },
  },
}
</script>

<style scoped>
.overview-line {
  margin-top: 1rem;
}
</style>
