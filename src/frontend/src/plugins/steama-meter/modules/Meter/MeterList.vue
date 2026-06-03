<template>
  <div>
    <widget
      id="meter-list"
      :title="title"
      :paginator="meterService.paginator"
      :route_name="meterService.routeName"
      :show_per_page="true"
      :subscriber="subscriber"
      color="primary"
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
          <md-table-cell md-label="Serial" md-sort-by="serial">
            {{ item.serial }}
          </md-table-cell>
          <md-table-cell md-label="Site" md-sort-by="site">
            {{ item.site }}
          </md-table-cell>
          <md-table-cell md-label="Customer" md-sort-by="owner">
            {{ item.owner }}
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
import { MeterService } from "../../services/MeterService.js"

import { notify } from "@/mixins/notify.js"
import { EventBus } from "@/shared/eventbus.js"
import RedirectionModal from "@/shared/RedirectionModal.vue"
import Widget from "@/shared/Widget.vue"

export default {
  name: "MeterList",
  mixins: [notify],
  components: { RedirectionModal, Widget },
  data() {
    return {
      credentialService: new CredentialService(),
      meterService: new MeterService(),
      subscriber: "meter-list",
      loading: false,
      isSynced: false,
      title: "Meters",
      redirectionUrl: "/steama-meters/steama-overview",
      redirectDialogActive: false,
      buttonText: "Get Updates From Steama.co",
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
        }
      } catch (e) {
        this.redirectDialogActive = true
      }
    },
    async syncMeters() {
      if (!this.loading) {
        try {
          this.loading = true
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

<style scoped lang="scss"></style>
