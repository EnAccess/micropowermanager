<template>
  <div>
    <widget
      id="customer-list"
      :title="title"
      :paginator="true"
      :paging_url="customerService.pagingUrl"
      :route_name="customerService.routeName"
      :search="false"
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
          <md-table-head>Customer No</md-table-head>
          <md-table-head>Phone</md-table-head>
          <md-table-head>Address</md-table-head>
        </md-table-row>
        <md-table-row
          v-for="(item, index) in customerService.list"
          :key="index"
        >
          <md-table-cell>{{ item.id }}</md-table-cell>
          <md-table-cell>{{ item.customerNo }}</md-table-cell>
          <md-table-cell>{{ item.phone }}</md-table-cell>
          <md-table-cell>{{ item.address }}</md-table-cell>
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
      customerService: new CustomerService(),
      subscriber: "customer-list",
      loading: false,
      isSynced: false,
      title: "Customers",
      redirectionUrl: "/kelin-meters/kelin-overview",
      redirectDialogActive: false,
      buttonText: "Get Updates From Kelin Platform",
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
