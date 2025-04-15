<template>
  <div>
    <widget
      id="meter-model-list"
      :title="title"
      :paginator="true"
      :paging_url="meterModelService.pagingUrl"
      :route_name="meterModelService.routeName"
      :show_per_page="true"
      :subscriber="subscriber"
      color="green"
      @widgetAction="syncMeterModels()"
      :button="true"
      buttonIcon="cloud_download"
      :button-text="buttonText"
      :emptyStateLabel="label"
      :emptyStateButtonText="buttonText"
      :newRecordButton="false"
    >
      <md-table
        v-model="meterModelService.list"
        md-sort="id"
        md-sort-order="asc"
        md-card
      >
        <md-table-row slot="md-table-row" slot-scope="{ item }">
          <md-table-cell md-label="ID" md-sort-by="id">
            {{ item.id }}
          </md-table-cell>
          <md-table-cell md-label="Name" md-sort-by="model_name">
            {{ item.modelName }}
          </md-table-cell>
          <md-table-cell
            md-label="Continuous Limit"
            md-sort-by="continuous_limit"
          >
            {{ item.continuousLimit }}
          </md-table-cell>
          <md-table-cell md-label="Inrush Limit" md-sort-by="inrush_limit">
            {{ item.inrushLimit }}
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
import Widget from "@/shared/Widget.vue"
import RedirectionModal from "@/shared/RedirectionModal"
import { MeterModelService } from "../../services/MeterModelService"
import { EventBus } from "@/shared/eventbus"

import { CredentialService } from "../../services/CredentialService"
import { SiteService } from "../../services/SiteService"
import { notify } from "@/mixins/notify"

export default {
  name: "MeterModelList",
  mixins: [notify],
  components: { Widget, RedirectionModal },
  data() {
    return {
      credentialService: new CredentialService(),
      meterModelService: new MeterModelService(),
      siteService: new SiteService(),
      subscriber: "meter-model-list",
      searchTerm: "",
      loading: false,
      isSynced: false,
      title: "Meter Models",
      redirectionUrl: "/spark-meters/sm-overview",
      redirectDialogActive: false,
      buttonText: "Get Updates From Spark Meter",
      label: "Meter Model Records Not Up to Date.",
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
        let checkingResult = await this.meterModelService.checkMeterModels()
        this.isSynced = true
        if (checkingResult.available_site_count === 0) {
          this.redirectionMessage =
            "There is no authenticated Site to download Meter Model updates."
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
            text: "Meter Model Records Not Up to Date.",
            confirmButtonText: "Update",
            cancelButtonText: "Cancel",
          }
          this.$swal(swalOptions).then((result) => {
            if (result.value) {
              this.syncMeterModels()
            }
          })
        }
      } catch (e) {
        this.loading = false
        this.alertNotify("error", e.message)
      }
    },
    async syncMeterModels() {
      if (!this.loading) {
        try {
          this.loading = true
          let sitesSynced = await this.siteService.checkSites()
          if (!sitesSynced) {
            this.alertNotify(
              "warn",
              "Sites must be updated to update Meter Models.",
            )
            return
          }
          this.isSynced = false
          await this.meterModelService.syncMeterModels()
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
      this.meterModelService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.meterModelService.list.length,
      )
    },
  },
}
</script>

<style scoped></style>
