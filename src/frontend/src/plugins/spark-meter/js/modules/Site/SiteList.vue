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
      :resetKey="resetKey"
    >
      <md-table>
        <md-table-row>
          <md-table-head>ID</md-table-head>
          <md-table-head>Name</md-table-head>

          <md-table-head>Is Authenticated</md-table-head>
          <md-table-head>Is Online</md-table-head>

          <md-table-head>Thundercloud Url</md-table-head>
          <md-table-head>Thundercloud Token</md-table-head>
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
            <md-field
              :class="{
                'md-invalid': errors.has('thundercloud_token_' + item.id),
              }"
            >
              <md-input
                :id="'thundercloud_token_' + item.id"
                :name="'thundercloud_token_' + item.id"
                v-model="item.thundercloudToken"
                v-validate="'required|min:3'"
                :disabled="editThundercloudToken !== item.id"
              />
              <span class="md-error">
                {{ errors.first("thundercloud_token_" + item.id) }}
              </span>
            </md-field>
          </md-table-cell>

          <md-table-cell>
            <div v-if="editThundercloudToken === item.id">
              <md-button class="md-icon-button" @click="updateSite(item)">
                <md-icon>save</md-icon>
              </md-button>
              <md-button
                class="md-icon-button"
                @click="editThundercloudToken = null"
              >
                <md-icon>close</md-icon>
              </md-button>
            </div>
            <div v-else class="edit-button-area">
              <md-button
                class="md-icon-button"
                @click="editThundercloudToken = item.id"
              >
                <md-icon>edit</md-icon>
              </md-button>
              <md-button
                class="md-icon-button"
                :disabled="!item.isAuthenticated"
                @click="updateSite(item)"
              >
                <md-tooltip md-direction="top">Is Online Check</md-tooltip>
                <md-icon>online_prediction</md-icon>
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
      redirectionUrl: "/spark-meters/sm-overview",
      redirectDialogActive: false,
      buttonText: "Get Updates From Spark Meter",
      label: "Site Records Not Up to Date.",
      editThundercloudToken: null,
      resetKey: 0,
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
    async updateSite(site) {
      try {
        this.loading = true
        await this.siteService.updateSite(site)
        this.resetKey += 1

        this.loading = false
      } catch (e) {
        this.loading = false
        this.alertNotify("error", e.message)
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

<style scoped>
.edit-button-area {
  display: inline-flex;
  margin-left: -2rem;
}
</style>
