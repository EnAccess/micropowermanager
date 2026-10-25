<template>
  <div>
    <widget
      id="site-list"
      :title="title"
      :paginator="siteService.paginator"
      :paging_url="siteService.pagingUrl"
      :route_name="siteService.routeName"
      :show_per_page="true"
      :subscriber="subscriber"
      color="primary"
      @widgetAction="syncSites()"
      :button="true"
      buttonIcon="cloud_download"
      :button-text="buttonText"
      :emptyStateLabel="label"
      :emptyStateButtonText="buttonText"
      :newRecordButton="false"
      :resetKey="resetKey"
    >
      <md-table>
        <md-table-row>
          <md-table-head>ID</md-table-head>
          <md-table-head>Name</md-table-head>

          <md-table-head>Is Authenticated</md-table-head>
          <md-table-head>Is Online</md-table-head>

          <md-table-head>Thundercloud Url</md-table-head>
          <md-table-head>Token Set</md-table-head>
          <md-table-head>#</md-table-head>
        </md-table-row>
        <md-table-row v-for="(item, index) in siteService.list" :key="index">
          <md-table-cell>{{ item.id }}</md-table-cell>
          <md-table-cell>{{ item.name }}</md-table-cell>

          <md-table-cell>
            <md-icon v-if="item.isAuthenticated" style="color: #1a921a">
              check_circle_outline
            </md-icon>
            <md-icon v-if="!item.isAuthenticated" style="color: #d01111">
              remove
            </md-icon>
          </md-table-cell>
          <md-table-cell>
            <md-icon v-if="item.isOnline" style="color: #1a921a">
              check_circle_outline
            </md-icon>
            <md-icon v-if="!item.isOnline" style="color: #d01111">
              remove
            </md-icon>
          </md-table-cell>

          <md-table-cell>{{ item.thundercloudUrl }}</md-table-cell>
          <md-table-cell>
            <md-icon v-if="item.thundercloudToken" style="color: #1a921a">
              check_circle_outline
            </md-icon>
            <md-icon v-if="!item.thundercloudToken" style="color: #d01111">
              remove
            </md-icon>
          </md-table-cell>

          <md-table-cell>
            <md-button class="md-icon-button" @click="editSite(item)">
              <md-tooltip md-direction="top">
                Edit ThunderCloud connection
              </md-tooltip>
              <md-icon>edit</md-icon>
            </md-button>
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
    <md-progress-bar md-mode="indeterminate" v-if="loading" />
    <md-dialog :md-active.sync="showSiteDialog">
      <md-dialog-title>
        ThunderCloud connection{{ editingSite ? ` – ${editingSite.name}` : "" }}
      </md-dialog-title>
      <md-dialog-content v-if="editingSite">
        <form data-vv-scope="Site-Form" @submit.prevent="saveSite">
          <md-field
            :class="{ 'md-invalid': errors.has('Site-Form.thundercloudUrl') }"
          >
            <label for="thundercloudUrl">ThunderCloud URL</label>
            <md-input
              id="thundercloudUrl"
              name="thundercloudUrl"
              v-model="editingSite.thundercloudUrl"
              v-validate="'required'"
            />
            <span class="md-error">
              {{ errors.first("Site-Form.thundercloudUrl") }}
            </span>
          </md-field>
          <md-field>
            <label for="thundercloudToken">ThunderCloud API Token</label>
            <md-input
              id="thundercloudToken"
              type="password"
              v-model="editingSite.thundercloudToken"
            />
            <span class="md-helper-text">
              From a ThunderCloud user with the "api" role, under API
              Credentials.
            </span>
          </md-field>
        </form>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button @click="showSiteDialog = false">Cancel</md-button>
        <md-button class="md-primary" :disabled="loading" @click="saveSite">
          Save
        </md-button>
      </md-dialog-actions>
    </md-dialog>
    <redirection-modal
      :redirection-url="redirectionUrl"
      :dialog-active="redirectDialogActive"
      :imperative-item="'valid API Credentials'"
    />
  </div>
</template>

<script>
import { CredentialService } from "../../services/CredentialService.js"
import { SiteService } from "../../services/SiteService.js"

import { notify } from "@/mixins/notify.js"
import { EventBus } from "@/shared/eventbus.js"
import RedirectionModal from "@/shared/RedirectionModal.vue"
import Widget from "@/shared/Widget.vue"

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
      redirectionUrl: "/spark-meters/sm-overview",
      redirectDialogActive: false,
      buttonText: "Get Updates From Spark Meter",
      label: "Site Records Not Up to Date.",
      resetKey: 0,
      showSiteDialog: false,
      editingSite: null,
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
          this.redirectionUrl = "/spark-meters/sm-overview"
          this.redirectDialogActive = true
        } else {
          await this.checkSync()
        }
      } catch (e) {
        this.redirectDialogActive = true
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
      if (!this.credentialService.credential.isAuthenticated) {
        this.redirectDialogActive = true
        return
      }
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
    editSite(site) {
      this.editingSite = { ...site }
      this.showSiteDialog = true
    },
    async saveSite() {
      const isValid = await this.$validator.validateAll("Site-Form")
      if (!isValid) {
        return
      }
      this.loading = true
      try {
        const response = await this.siteService.updateSite(this.editingSite)
        if (response.data.data.is_authenticated) {
          this.alertNotify("success", `${this.editingSite.name} is connected.`)
        } else {
          this.alertNotify(
            "error",
            `${this.editingSite.name} could not connect. Check the URL and API token.`,
          )
        }
        this.showSiteDialog = false
        this.resetKey += 1
      } catch (e) {
        this.alertNotify("error", e.message)
      } finally {
        this.loading = false
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
