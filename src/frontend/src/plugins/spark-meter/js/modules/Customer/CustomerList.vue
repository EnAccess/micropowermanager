<template>
  <div>
    <widget
      id="customer-list"
      :title="title"
      :paginator="true"
      :search="true"
      :paging_url="customerService.pagingUrl"
      :route_name="customerService.routeName"
      :show_per_page="true"
      :subscriber="subscriber"
      color="green"
      @widgetAction="syncCustomers()"
      :button="true"
      buttonIcon="cloud_download"
      :button-text="buttonText"
      :emptyStateLabel="label"
      :emptyStateButtonText="buttonText"
      :newRecordButton="false"
      :resetKey="resetKey"
    >
      <md-table
        v-model="customerService.list"
        md-sort="id"
        md-sort-order="asc"
        md-card
      >
        <md-table-row>
          <md-table-head>ID</md-table-head>
          <md-table-head>Spark ID</md-table-head>

          <md-table-head>Name</md-table-head>
          <md-table-head>Balance</md-table-head>
          <md-table-head>Low Balance Limit</md-table-head>
          <md-table-head>Site</md-table-head>
          <md-table-head>#</md-table-head>
        </md-table-row>

        <md-table-row
          v-for="(item, index) in customerService.list"
          :key="index"
        >
          <md-table-cell>{{ item.id }}</md-table-cell>
          <md-table-cell>{{ item.sparkId }}</md-table-cell>
          <md-table-cell>{{ item.name }}</md-table-cell>
          <md-table-cell>{{ item.creditBalance }}</md-table-cell>
          <md-table-cell>
            <md-field
              :class="{
                'md-invalid': errors.has('low_balance_limit' + item.id),
              }"
            >
              <md-input
                :id="'low_balance_limit' + item.id"
                :name="'low_balance_limit' + item.id"
                v-model="item.lowBalanceLimit"
                v-validate="'required|min:3'"
                :disabled="editLowBalanceLimit !== item.id"
              />
              <span class="md-error">
                {{ errors.first("low_balance_limit" + item.id) }}
              </span>
            </md-field>
          </md-table-cell>
          <md-table-cell>{{ item.siteName }}</md-table-cell>
          <md-table-cell>
            <div v-if="editLowBalanceLimit === item.id">
              <md-button class="md-icon-button" @click="updateCustomer(item)">
                <md-icon>save</md-icon>
              </md-button>
              <md-button
                class="md-icon-button"
                @click="editLowBalanceLimit = null"
              >
                <md-icon>close</md-icon>
              </md-button>
            </div>
            <div v-else>
              <md-button
                class="md-icon-button"
                @click="editLowBalanceLimit = item.id"
              >
                <md-icon>edit</md-icon>
              </md-button>
            </div>
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
import Widget from "@/shared/Widget.vue"
import RedirectionModal from "@/shared/RedirectionModal"
import { CustomerService } from "../../services/CustomerService"
import { EventBus } from "@/shared/eventbus"
import { TariffService } from "../../services/TariffService"
import { MeterModelService } from "../../services/MeterModelService"
import { CredentialService } from "../../services/CredentialService"
import { SiteService } from "../../services/SiteService"
import { notify } from "@/mixins/notify"

export default {
  name: "CustomerList",
  mixins: [notify],
  components: { Widget, RedirectionModal },
  data() {
    return {
      credentialService: new CredentialService(),
      customerService: new CustomerService(),
      tariffService: new TariffService(),
      meterModelService: new MeterModelService(),
      siteService: new SiteService(),
      subscriber: "customer-list",
      searchTerm: "",
      loading: false,
      isSynced: false,
      title: "Customers",
      redirectionUrl: "/spark-meters/sm-overview",
      redirectDialogActive: false,
      buttonText: "Get Updates From Spark Meter",
      label: "Customer Records Not Up to Date.",
      editLowBalanceLimit: null,
      resetKey: 0,
    }
  },
  mounted() {
    this.checkConnectionTypes()
    EventBus.$on("pageLoaded", this.reloadList)
    EventBus.$on("searching", this.searching)
    EventBus.$on("end_searching", this.endSearching)
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
    EventBus.$off("searching", this.searching)
    EventBus.$off("end_searching", this.endSearching)
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

    async checkConnectionTypes() {
      let response = await this.customerService.checkConnectionTypes()
      if (!response.type) {
        this.redirectionUrl = "/connection-types"
        this.redirectionMessage = "Please create a Connection Type."
        this.redirectDialogActive = true
      } else if (!response.group) {
        this.redirectionUrl = "/connection-groups"
        this.redirectionMessage = "Please create a Connection Group."
        this.redirectDialogActive = true
      } else {
        await this.checkCredential()
      }
    },
    async syncCustomers() {
      if (!this.loading) {
        try {
          this.loading = true
          let sitesSynced = await this.siteService.checkSites()
          if (!sitesSynced) {
            this.alertNotify(
              "warn",
              "Sites must be updated to update Customers.",
            )
            return
          }
          let metersSynced = await this.meterModelService.checkMeterModels()
          if (!metersSynced) {
            this.alertNotify(
              "warn",
              "MeterModels must be synchronized to synchronize Customers .",
            )
            this.loading = false
            return
          }
          let tariffsSynced = await this.tariffService.checkTariffs()
          if (!tariffsSynced) {
            this.alertNotify(
              "warn",
              "Tariffs must be synchronized to synchronize Customers .",
            )
            this.loading = false
            return
          }
          this.loading = true
          this.isSynced = false
          await this.customerService.syncCustomers()
          EventBus.$emit("widgetContentLoaded", this.subscriber, 1)
          this.isSynced = true
          this.loading = false
          this.alertNotify("success", "Customer records updated.")
        } catch (e) {
          this.loading = false
          this.alertNotify("error", e.message)
          EventBus.$emit("widgetContentLoaded", this.subscriber, 0)
        }
      }
    },
    async checkSync() {
      try {
        this.loading = true
        let checkingResult = await this.customerService.checkCustomers()
        this.isSynced = true
        if (checkingResult.available_site_count === 0) {
          this.redirectionMessage =
            "There is no authenticated Site to download Customer updates."
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
            text: "Customer Records Not Up to Date.",
            confirmButtonText: "Update",
            cancelButtonText: "Cancel",
          }
          this.$swal(swalOptions).then((result) => {
            if (result.value) {
              this.syncCustomers()
            }
          })
        }
      } catch (e) {
        this.loading = false
        this.alertNotify("error", e.message)
      }
    },
    async updateCustomer(customer) {
      try {
        this.loading = true
        await this.customerService.updateCustomer(customer)
        this.resetKey += 1
        this.loading = false
        this.alertNotify("success", "Customer low balance limit updated.")
      } catch (e) {
        this.loading = false
        this.alertNotify("error", e.message)
      }
    },

    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) return
      this.customerService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.customerService.list.length,
      )
    },
    searching(searchTerm) {
      this.customerService.search(searchTerm)
    },
    endSearching() {
      this.customerService.showAll()
    },
  },
}
</script>

<style scoped></style>
