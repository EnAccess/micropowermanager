<template>
  <div>
    <md-button class="md-raised md-primary" @click="openAddForm">
      Register Site
    </md-button>

    <md-dialog :md-active.sync="showAddForm" class="site-dialog">
      <md-dialog-title>Register ThunderCloud Site</md-dialog-title>
      <md-dialog-content>
        <md-field :class="{ 'md-invalid': errors.has('Site-Form.name') }">
          <label for="site_name">Name</label>
          <md-input
            id="site_name"
            name="name"
            v-model="newSite.name"
            v-validate="'required'"
            data-vv-scope="Site-Form"
          />
          <span class="md-error">{{ errors.first("Site-Form.name") }}</span>
        </md-field>

        <md-field
          :class="{
            'md-invalid': errors.has('Site-Form.thundercloud_url'),
          }"
        >
          <label for="site_thundercloud_url">ThunderCloud URL</label>
          <md-input
            id="site_thundercloud_url"
            name="thundercloud_url"
            v-model="newSite.thundercloudUrl"
            v-validate="'required|min:3'"
            data-vv-scope="Site-Form"
            placeholder="http://localhost:5000"
          />
          <span
            class="md-helper-text"
            v-if="!errors.has('Site-Form.thundercloud_url')"
          >
            Base URL of the ThunderCloud instance, e.g. http://localhost:5000
          </span>
          <span class="md-error">
            {{ errors.first("Site-Form.thundercloud_url") }}
          </span>
        </md-field>

        <md-field
          :class="{
            'md-invalid': errors.has('Site-Form.thundercloud_token'),
          }"
        >
          <label for="site_thundercloud_token">ThunderCloud Token</label>
          <md-input
            id="site_thundercloud_token"
            name="thundercloud_token"
            v-model="newSite.thundercloudToken"
            v-validate="'required'"
            data-vv-scope="Site-Form"
          />
          <span class="md-error">
            {{ errors.first("Site-Form.thundercloud_token") }}
          </span>
        </md-field>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button @click="cancelAddForm">Cancel</md-button>
        <md-button class="md-primary" @click="saveSite">Save</md-button>
      </md-dialog-actions>
    </md-dialog>

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
      editThundercloudToken: null,
      resetKey: 0,
      showAddForm: false,
      newSite: { name: "", thundercloudUrl: "", thundercloudToken: "" },
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
    openAddForm() {
      this.newSite = { name: "", thundercloudUrl: "", thundercloudToken: "" }
      this.showAddForm = true
    },
    cancelAddForm() {
      this.showAddForm = false
    },
    async saveSite() {
      let validator = await this.$validator.validateAll("Site-Form")
      if (!validator) {
        return
      }
      try {
        this.loading = true
        await this.siteService.createSite(this.newSite)
        this.showAddForm = false
        this.resetKey += 1
        this.alertNotify("success", "Site registered")
      } catch (e) {
        this.alertNotify("error", e.response?.data?.message || e.message)
      }
      this.loading = false
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

<style scoped lang="scss">
.edit-button-area {
  display: inline-flex;
  margin-left: -2rem;
}
</style>
