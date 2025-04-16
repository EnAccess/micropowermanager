<template>
  <div>
    <widget
      id="site-list"
      :title="title"
      :paginator="true"
      :paging_url="siteService.pagingUrl"
      :route_name="siteService.routeName"
      :show_per_page="true"
      :subscriber="subscriber"
      color="green"
      @widgetAction="syncSites()"
      :button="true"
      buttonIcon="cloud_download"
      :button-text="buttonText"
      :emptyStateLabel="label"
      :emptyStateButtonText="buttonText"
      :newRecordButton="false"
    >
      <md-table
        v-model="siteService.list"
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
          <md-table-cell md-label="Latitude" md-sort-by="latitude">
            {{ item.latitude }}
          </md-table-cell>
          <md-table-cell md-label="Longitude" md-sort-by="longitude">
            {{ item.longitude }}
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
import { notify } from "@/mixins/notify"

export default {
  name: "SiteList",
  mixins: [notify],
  components: { RedirectionModal, Widget },
  data() {
    return {
      siteService: new SiteService(),
      credentialService: new CredentialService(),
      subscriber: "site-list",
      loading: false,
      isSynced: false,
      title: "Sites",
      redirectionUrl: "/steama-meters/steama-overview",
      redirectDialogActive: false,
      buttonText: "Get Updates From Steama.co",
      label: "Site Records Not Up to Date.",
    }
  },
  mounted() {
    this.checkLocation()
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
        this.isSynced = await this.siteService.checkSites()

        this.loading = false
        if (!this.isSynced) {
          let swalOptions = {
            title: "Updates",
            showCancelButton: true,
            text: "Site Records Not Up to Date.",
            confirmButtonText: "Update",
            cancelButtonText: "Cancel",
          }
          this.$swal(swalOptions).then((result) => {
            if (result.value) {
              this.syncSites()
            }
          })
        }
      } catch (e) {
        this.loading = false
        this.alertNotify("error", e.message)
      }
    },
    async syncSites() {
      if (!this.loading) {
        try {
          this.loading = true
          this.isSynced = false
          await this.siteService.syncSites()
          EventBus.$emit("widgetContentLoaded", this.subscriber, 1)
          this.isSynced = true
          this.loading = false
        } catch (e) {
          this.loading = false
          this.alertNotify("error", e.message)
        }
      }
    },
    async checkLocation() {
      let response = await this.siteService.checkLocation()

      if (response.length === 0) {
        this.redirectionUrl = "/locations/add-cluster"
        this.redirectionMessage = "Please make your location settings first."
        this.redirectDialogActive = true
      } else {
        await this.checkCredential()
      }
    },
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) return
      this.siteService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.siteService.list.length,
      )
    },
  },
}
</script>

<style scoped></style>
