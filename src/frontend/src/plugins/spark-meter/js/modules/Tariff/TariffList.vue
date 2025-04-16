<template>
  <div>
    <widget
      id="tariff-list"
      :title="title"
      :paginator="true"
      :paging_url="tariffService.pagingUrl"
      :route_name="tariffService.routeName"
      :show_per_page="true"
      :subscriber="subscriber"
      color="green"
      @widgetAction="syncTariffs()"
      :button="true"
      buttonIcon="cloud_download"
      :button-text="buttonText"
      :emptyStateLabel="label"
      :emptyStateButtonText="buttonText"
      :newRecordButton="false"
    >
      <md-table
        v-model="tariffService.list"
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
          <md-table-cell md-label="Flat Price" md-sort-by="price">
            {{ item.price }}
          </md-table-cell>
          <md-table-cell
            md-label="Flat Load Limit"
            md-sort-by="flat_load_limit"
          >
            {{ item.flatLoadLimit }}
          </md-table-cell>
          <md-table-cell md-label="Site" md-sort-by="siteName">
            {{ item.siteName }}
          </md-table-cell>
          <md-table-cell md-label="#">
            <md-button
              class="md-icon-button"
              @click="editTariff(item.tariffId)"
            >
              <md-tooltip md-direction="top">Edit</md-tooltip>
              <md-icon>edit</md-icon>
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
import Widget from "@/shared/Widget.vue"
import RedirectionModal from "@/shared/RedirectionModal"
import { EventBus } from "@/shared/eventbus"
import { TariffService } from "../../services/TariffService"
import { MeterModelService } from "../../services/MeterModelService"
import { CredentialService } from "../../services/CredentialService"
import { SiteService } from "../../services/SiteService"
import { notify } from "@/mixins/notify"

export default {
  name: "TariffList",
  mixins: [notify],
  components: { Widget, RedirectionModal },
  data() {
    return {
      credentialService: new CredentialService(),
      tariffService: new TariffService(),
      meterModelService: new MeterModelService(),
      siteService: new SiteService(),
      subscriber: "tariff-list",
      searchTerm: "",
      loading: false,
      isSynced: false,
      title: "Tariffs",
      redirectionUrl: "/spark-meters/sm-overview",
      redirectDialogActive: false,
      buttonText: "Get Updates From Spark Meter",
      label: "Tariff Records Not Up to Date.",
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
        let checkingResult = await this.tariffService.checkTariffs()
        this.isSynced = true
        if (checkingResult.available_site_count === 0) {
          this.redirectionMessage =
            "There is no authenticated Site to download Tariff updates."
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
            text: "Tariff Records Not Up to Date.",
            confirmButtonText: "Update",
            cancelButtonText: "Cancel",
          }
          this.$swal(swalOptions).then((result) => {
            if (result.value) {
              this.syncTariffs()
            }
          })
        }
      } catch (e) {
        this.loading = false
        this.alertNotify("error", e.message)
      }
    },
    async syncTariffs() {
      if (!this.loading) {
        try {
          this.loading = true
          let sitesSynced = await this.siteService.checkSites()
          if (!sitesSynced) {
            this.alertNotify("warn", "Sites must be updated to update Tariffs.")
            return
          }
          let metersSynced = await this.meterModelService.checkMeterModels()
          if (!metersSynced) {
            this.alertNotify(
              "warn",
              "MeterModels must be updated to update Tariffs.",
            )
            return
          }
          this.isSynced = false
          await this.tariffService.syncTariffs()
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
      this.tariffService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.tariffService.list.length,
      )
    },
    editTariff(tariffId) {
      this.$router.push({ path: "/spark-meters/sm-tariff/" + tariffId })
    },
  },
}
</script>

<style scoped></style>
