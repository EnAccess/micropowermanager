<template>
  <div>
    <widget
      id="transaction-list"
      :title="title"
      :paginator="transactionService.paginator"
      :route_name="transactionService.routeName"
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
        v-model="transactionService.list"
        md-sort="timestamp"
        md-sort-order="desc"
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
            {{ item.amount !== null ? moneyFormat(item.amount) : "" }}
          </md-table-cell>
          <md-table-cell md-label="Source" md-sort-by="source">
            {{ item.source }}
          </md-table-cell>
          <md-table-cell md-label="Type" md-sort-by="type">
            {{ item.type }}
          </md-table-cell>
          <md-table-cell md-label="Status" md-sort-by="status">
            {{ item.status }}
          </md-table-cell>
          <md-table-cell md-label="Memo" md-sort-by="memo">
            {{ item.memo }}
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
import { TransactionService } from "../../services/TransactionService.js"

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
      transactionService: new TransactionService(),
      credentialService: new CredentialService(),
      subscriber: "transaction-list",
      loading: false,
      title: "Transactions",
      redirectionUrl: "/spark-meters/sm-overview",
      redirectDialogActive: false,
      buttonText: "Get Updates From Spark Meter",
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
          await this.transactionService.syncTransactions()
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
      this.transactionService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.transactionService.list.length,
      )
    },
  },
}
</script>

<style scoped lang="scss"></style>
