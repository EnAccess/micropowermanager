<template>
  <div>
    <widget
      id="sales-account-list"
      :title="title"
      :paginator="true"
      :paging_url="salesAccountService.pagingUrl"
      :route_name="salesAccountService.routeName"
      :show_per_page="true"
      :subscriber="subscriber"
      color="green"
      @widgetAction="syncSalesAccount()"
      :button="true"
      buttonIcon="cloud_download"
      :button-text="buttonText"
      :emptyStateLabel="label"
      :emptyStateButtonText="buttonText"
      :newRecordButton="false"
    >
      <md-table
        v-model="salesAccountService.list"
        md-sort="id"
        md-sort-order="asc"
        md-card
      >
        <md-table-row slot="md-table-row" slot-scope="{ item }">
          <md-table-cell md-label="ID" md-sort-by="id">
            {{ item.id }}
          </md-table-cell>
          <md-table-cell md-label="Name" md-sort-by="name">
            {{ item.name }}
          </md-table-cell>
          <md-table-cell md-label="Account Type" md-sort-by="accountType">
            {{ item.accountType }}
          </md-table-cell>
          <md-table-cell md-label="Active" md-sort-by="active">
            <md-icon v-if="item.active" style="color: #1a921a">
              check_circle_outline
            </md-icon>
            <md-icon v-if="!item.active" style="color: #d01111">remove</md-icon>
          </md-table-cell>
          <md-table-cell md-label="Credit" md-sort-by="credit">
            {{ item.credit }}
          </md-table-cell>
          <md-table-cell md-label="Credit" md-sort-by="credit">
            {{ item.credit }}
          </md-table-cell>
          <md-table-cell md-label="Markup" md-sort-by="markup">
            {{ item.markup }}
          </md-table-cell>
          <md-table-cell md-label="Site" md-sort-by="siteName">
            {{ item.siteName }}
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
import { SalesAccountService } from "../../services/SalesAccountService"
import { CredentialService } from "../../services/CredentialService"
import Widget from "@/shared/Widget.vue"
import RedirectionModal from "@/shared/RedirectionModal"
import { EventBus } from "@/shared/eventbus"
import { notify } from "@/mixins/notify"

export default {
  name: "SalesAccountList",
  mixins: [notify],
  components: { RedirectionModal, Widget },
  data() {
    return {
      credentialService: new CredentialService(),
      salesAccountService: new SalesAccountService(),
      subscriber: "sales-account-list",
      loading: false,
      isSynced: false,
      title: "Sales Accounts",
      redirectionUrl: "/spark-meters/sm-overview",
      redirectDialogActive: false,
      buttonText: "Get Updates From Spark Meter",
      label: "Sales Account Records Not Up to Date.",
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
        } else {
          await this.checkSync()
        }
      } catch (e) {
        this.redirectDialogActive = true
      }
    },
    async checkSync() {
      try {
        let checkingResult = await this.salesAccountService.checkSalesAccounts()
        this.isSynced = true
        if (checkingResult.available_site_count === 0) {
          this.redirectionMessage =
            "There is no authenticated Site to download Sales Accounts updates."
          this.redirectionUrl = "/spark-meters/sm-site"
          this.redirectDialogActive = true
          return
        }
        for (let [k, v] of Object.entries(checkingResult)) {
          if (k !== "available_site_count") {
            if (!v.result) {
              this.isSynced = false
            }
          }
        }
        this.loading = false

        if (!this.isSynced) {
          let swalOptions = {
            title: "Updates",
            showCancelButton: true,
            text: "Sales Account Records Not Up to Date.",
            confirmButtonText: "Update",
            cancelButtonText: "Cancel",
          }
          this.$swal(swalOptions).then((result) => {
            if (result.value) {
              this.syncSalesAccount()
            }
          })
        }
      } catch (e) {
        this.loading = false
        this.alertNotify("error", e.message)
      }
    },

    async syncSalesAccount() {
      if (!this.loading) {
        try {
          this.loading = true
          this.isSynced = false
          await this.salesAccountService.syncSalesAccount()
          EventBus.$emit("widgetContentLoaded", this.subscriber, 1)
          this.isSynced = true
          this.loading = false
        } catch (e) {
          this.loading = false
          this.alertNotify("error", e.message)
        }
      }
    },
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) return
      this.salesAccountService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.salesAccountService.list.length,
      )
    },
  },
}
</script>

<style scoped></style>
