<template>
  <div>
    <widget
      id="customer-list"
      :title="title"
      :paginator="true"
      :paging_url="customerService.pagingUrl"
      :route_name="customerService.routeName"
      :search="true"
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
          <md-table-head>Steama ID</md-table-head>

          <md-table-head>First Name</md-table-head>
          <md-table-head>Last Name</md-table-head>
          <md-table-head>Energy Price</md-table-head>
          <md-table-head>Low Balance Warning</md-table-head>
          <md-table-head>Site</md-table-head>
          <md-table-head>#</md-table-head>
        </md-table-row>
        <md-table-row
          v-for="(item, index) in customerService.list"
          :key="index"
        >
          <md-table-cell>{{ item.id }}</md-table-cell>
          <md-table-cell>{{ item.steamaId }}</md-table-cell>
          <md-table-cell>{{ item.firstName }}</md-table-cell>
          <md-table-cell>{{ item.lastName }}</md-table-cell>

          <md-table-cell>
            <md-field
              :class="{
                'md-invalid': errors.has('energy_price' + item.id),
              }"
            >
              <md-input
                :id="'energy_price' + item.id"
                :name="'energy_price' + item.id"
                v-model="item.energyPrice"
                v-validate="'required|min:3'"
                :disabled="editCustomer !== item.id"
              />
              <span class="md-error">
                {{ errors.first("energy_price" + item.id) }}
              </span>
            </md-field>
          </md-table-cell>
          <md-table-cell>
            <md-field
              :class="{
                'md-invalid': errors.has('low_balance_warning' + item.id),
              }"
            >
              <md-input
                :id="'low_balance_warning' + item.id"
                :name="'low_balance_warning' + item.id"
                v-model="item.lowBalanceWarning"
                v-validate="'required|min:3'"
                :disabled="editCustomer !== item.id"
              />
              <span class="md-error">
                {{ errors.first("low_balance_warning" + item.id) }}
              </span>
            </md-field>
          </md-table-cell>
          <md-table-cell>{{ item.siteName }}</md-table-cell>
          <md-table-cell>
            <div v-if="editCustomer === item.id">
              <md-button class="md-icon-button" @click="updateCustomer(item)">
                <md-icon>save</md-icon>
              </md-button>
              <md-button class="md-icon-button" @click="editCustomer = null">
                <md-icon>close</md-icon>
              </md-button>
            </div>
            <div v-else class="edit-button-area">
              <md-button class="md-icon-button" @click="showMovements(item)">
                <md-tooltip md-direction="top">Meter Movements</md-tooltip>
                <md-icon>swap_vert</md-icon>
              </md-button>
              <md-button class="md-icon-button" @click="editCustomer = item.id">
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
import RedirectionModal from "@/shared/RedirectionModal"
import { SiteService } from "../../services/SiteService"
import { EventBus } from "@/shared/eventbus"
import { CredentialService } from "../../services/CredentialService"
import Widget from "@/shared/Widget.vue"
import { CustomerService } from "../../services/CustomerService"
import { notify } from "@/mixins/notify"

export default {
  name: "CustomerList",
  mixins: [notify],
  components: { RedirectionModal, Widget },
  data() {
    return {
      credentialService: new CredentialService(),
      siteService: new SiteService(),
      customerService: new CustomerService(),
      subscriber: "customer-list",
      loading: false,
      isSynced: false,
      title: "Customers",
      redirectionUrl: "/steama-meters/steama-overview",
      redirectDialogActive: false,
      buttonText: "Get Updates From Steama.co",
      label: "Customer Records Not Up to Date.",
      editCustomer: null,
      resetKey: 0,
    }
  },
  mounted() {
    this.checkCredential()
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

    async checkSync() {
      try {
        this.loading = true
        this.isSynced = await this.customerService.checkCustomers()
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
            this.loading = false
            return
          }
          this.isSynced = false
          await this.customerService.syncCustomers()
          EventBus.$emit("widgetContentLoaded", this.subscriber, 1)
          this.isSynced = true
          this.loading = false
        } catch (e) {
          this.loading = false
          this.alertNotify("error", e.message)
        }
      }
    },

    async updateCustomer(customer) {
      try {
        this.loading = true
        await this.customerService.updateCustomer(customer)
        this.resetKey += 1
        this.loading = false
        this.alertNotify("success", "Customer updated.")
      } catch (e) {
        this.loading = false
        this.alertNotify("error", e.message)
      }
    },
    showMovements(customer) {
      this.$router.push({
        path: "/steama-meters/steama-transaction/" + customer.steamaId,
      })
    },
    searching(searchTerm) {
      this.customerService.search(searchTerm)
    },
    endSearching() {
      this.customerService.showAll()
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
  },
}
</script>

<style scoped>
.edit-button-area {
  display: inline-flex;
  margin-left: -2rem;
}
</style>
