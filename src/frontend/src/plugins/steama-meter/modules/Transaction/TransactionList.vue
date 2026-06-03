<template>
  <div>
    <widget
      id="transaction-list"
      :title="title"
      :paginator="transactionsService.paginator"
      :route_name="transactionsService.routeName"
      :show_per_page="true"
      :subscriber="subscriber"
      color="primary"
      @widgetAction="syncTransactions()"
      :button="true"
      buttonIcon="cloud_download"
      :button-text="buttonText"
      :emptyStateLabel="label"
      :emptyStateButtonText="buttonText"
      :newRecordButton="false"
    >
      <md-table
        v-model="transactionsService.list"
        md-sort="id"
        md-sort-order="asc"
        md-card
      >
        <md-table-row slot="md-table-row" slot-scope="{ item }">
          <md-table-cell md-label="Transaction ID" md-sort-by="transactionId">
            {{ item.transactionId }}
          </md-table-cell>
          <md-table-cell md-label="Customer" md-sort-by="customerName">
            {{ item.customerName }}
          </md-table-cell>
          <md-table-cell md-label="Site" md-sort-by="siteName">
            {{ item.siteName }}
          </md-table-cell>
          <md-table-cell md-label="Amount" md-sort-by="amount">
            {{ moneyFormat(item.amount) }}
          </md-table-cell>
          <md-table-cell md-label="Category" md-sort-by="category">
            {{ item.category }}
          </md-table-cell>
          <md-table-cell md-label="Provider" md-sort-by="provider">
            {{ item.provider }}
          </md-table-cell>
          <md-table-cell md-label="Date" md-sort-by="timestamp">
            {{ item.timestamp }}
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
    <md-progress-bar md-mode="indeterminate" v-if="loading" />
    <redirection-modal
      :redirection-url="redirectionUrl"
      :dialog-active="redirectDialogActive"
      :imperative-item="'valid API Credentials'"
    />
  </div>
</template>

<script>
import { CredentialService } from "../../services/CredentialService.js"
import { SteamaTransactionsService } from "../../services/SteamaTransactionsService.js"

import { currency } from "@/mixins/currency.js"
import { notify } from "@/mixins/notify.js"
import { EventBus } from "@/shared/eventbus.js"
import RedirectionModal from "@/shared/RedirectionModal.vue"
import Widget from "@/shared/Widget.vue"

export default {
  name: "TransactionList",
  mixins: [notify, currency],
  components: { RedirectionModal, Widget },
  data() {
    return {
      transactionsService: new SteamaTransactionsService(),
      credentialService: new CredentialService(),
      subscriber: "transaction-list",
      loading: false,
      title: "Transactions",
      redirectionUrl: "/steama-meters/steama-overview",
      redirectDialogActive: false,
      buttonText: "Get Updates From Steama.co",
      label: "No transactions recorded yet.",
    }
  },
  mounted() {
    this.checkCredential()
    EventBus.$on("pageLoaded", this.reloadList)
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
  },
  methods: {
    async checkCredential() {
      try {
        await this.credentialService.getCredential()
        if (!this.credentialService.credential.isAuthenticated) {
          this.redirectDialogActive = true
        }
      } catch (e) {
        this.redirectDialogActive = true
      }
    },
    async syncTransactions() {
      if (!this.loading) {
        try {
          this.loading = true
          await this.transactionsService.syncTransactions()
          EventBus.$emit("widgetContentLoaded", this.subscriber, 1)
          this.loading = false
        } catch (e) {
          this.loading = false
          this.alertNotify("error", e.message)
        }
      }
    },
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) return
      this.transactionsService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.transactionsService.list.length,
      )
    },
  },
}
</script>

<style scoped lang="scss"></style>
