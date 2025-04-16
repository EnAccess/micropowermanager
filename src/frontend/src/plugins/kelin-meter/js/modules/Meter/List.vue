<template>
  <div>
    <widget
      id="meter-list"
      :title="title"
      :paginator="true"
      :paging_url="meterService.pagingUrl"
      :route_name="meterService.routeName"
      :show_per_page="true"
      :subscriber="subscriber"
      color="green"
      @widgetAction="syncMeters()"
      :button="true"
      buttonIcon="cloud_download"
      :button-text="buttonText"
      :emptyStateLabel="label"
      :emptyStateButtonText="buttonText"
      :newRecordButton="false"
    >
      <md-table
        v-model="meterService.list"
        md-sort="id"
        md-sort-order="asc"
        md-card
      >
        <md-table-row slot="md-table-row" slot-scope="{ item }">
          <md-table-cell md-label="ID" md-sort-by="id">
            {{ item.id }}
          </md-table-cell>
          <md-table-cell md-label="DCU" md-sort-by="terminalId">
            {{ item.terminalId }}
          </md-table-cell>
          <md-table-cell md-label="Meter Name" md-sort-by="meterName">
            {{ item.meterName }}
          </md-table-cell>
          <md-table-cell md-label="Meter Address" md-sort-by="meterAddress">
            {{ item.meterAddress }}
          </md-table-cell>
          <md-table-cell md-label="Owner" md-sort-by="owner">
            {{ item.owner }}
          </md-table-cell>
          <md-table-cell md-label="#">
            <md-button
              class="md-icon-button"
              @click="
                () =>
                  $router.push('/kelin-meters/kelin-meter/status/' + item.id)
              "
            >
              <md-tooltip md-direction="top">Status</md-tooltip>
              <md-icon>remove_red_eye</md-icon>
            </md-button>
            <md-button
              class="md-icon-button"
              @click="
                () =>
                  $router.push(
                    '/kelin-meters/kelin-meter/minutely-consumptions/' +
                      item.id,
                  )
              "
            >
              <md-tooltip md-direction="top">Minutely Movements</md-tooltip>
              <md-icon>swap_vert</md-icon>
            </md-button>
            <md-button
              class="md-icon-button"
              @click="
                () =>
                  $router.push(
                    '/kelin-meters/kelin-meter/daily-consumptions/' + item.id,
                  )
              "
            >
              <md-tooltip md-direction="top">Daily Movements</md-tooltip>
              <md-icon>swap_vert</md-icon>
            </md-button>
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

import { EventBus } from "@/shared/eventbus"
import { CredentialService } from "../../services/CredentialService"
import Widget from "@/shared/Widget.vue"
import { CustomerService } from "../../services/CustomerService"
import { MeterService } from "../../services/MeterService"
import { notify } from "@/mixins/notify"

export default {
  name: "MeterList",
  mixins: [notify],
  components: { RedirectionModal, Widget },
  data() {
    return {
      credentialService: new CredentialService(),
      customerService: new CustomerService(),
      meterService: new MeterService(),
      subscriber: "meter-list",
      loading: false,
      isSynced: false,
      title: "Meters",
      redirectionUrl: "/kelin-meters/kelin-overview",
      redirectDialogActive: false,
      buttonText: "Get Updates From Kelin Platform",
      label: "Meter Records Not Up to Date.",
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
        this.loading = true
        this.isSynced = await this.meterService.checkMeters()
        this.loading = false

        if (!this.isSynced) {
          let swalOptions = {
            title: "Updates",
            showCancelButton: true,
            text: "Meter Records Not Up to Date.",
            confirmButtonText: "Update",
            cancelButtonText: "Cancel",
          }
          this.$swal(swalOptions).then((result) => {
            if (result.value) {
              this.syncMeters()
            }
          })
        }
      } catch (e) {
        this.loading = false
        this.alertNotify("error", e.message)
      }
    },
    async syncMeters() {
      if (!this.loading) {
        try {
          this.loading = true

          let customersSynced = await this.customerService.checkCustomers()
          if (!customersSynced) {
            this.alertNotify(
              "warn",
              "Customers must be updated to update Meters.",
            )
            this.isSynced = false
            return
          }
          this.isSynced = false
          await this.meterService.syncMeters()
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
      this.meterService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.meterService.list.length,
      )
    },
  },
}
</script>

<style scoped></style>
