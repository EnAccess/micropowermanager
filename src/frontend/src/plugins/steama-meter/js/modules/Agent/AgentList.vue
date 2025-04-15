<template>
  <div>
    <widget
      id="customer-list"
      :title="title"
      :paginator="true"
      :paging_url="agentService.pagingUrl"
      :route_name="agentService.routeName"
      :show_per_page="true"
      :subscriber="subscriber"
      color="green"
      @widgetAction="syncAgents()"
      :button="true"
      buttonIcon="cloud_download"
      :button-text="buttonText"
      :emptyStateLabel="label"
      :emptyStateButtonText="buttonText"
      :newRecordButton="false"
    >
      <md-table
        v-model="agentService.list"
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
          <md-table-cell md-label="Surname" md-sort-by="name">
            {{ item.surname }}
          </md-table-cell>
          <md-table-cell md-label="Site" md-sort-by="siteName">
            {{ item.siteName }}
          </md-table-cell>
          <md-table-cell
            md-label="Is Credit Limited"
            md-sort-by="isCreditLimited"
          >
            {{ item.isCreditLimited }}
          </md-table-cell>
          <md-table-cell md-label="Credit Balance" md-sort-by="creditBalance">
            {{ item.creditBalance }}
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
import { CredentialService } from "../../services/CredentialService"
import { AgentService } from "../../services/AgentService"
import { notify } from "@/mixins/notify"

export default {
  name: "AgentList",
  mixins: [notify],
  components: { RedirectionModal, Widget },
  data() {
    return {
      credentialService: new CredentialService(),
      agentService: new AgentService(),
      subscriber: "agent-list",
      loading: false,
      isSynced: false,
      title: "Agents",
      redirectionUrl: "/steama-meters/steama-overview",
      redirectDialogActive: false,
      buttonText: "Get Updates From Steama.co",
      label: "Agent Records Not Up to Date.",
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
        this.isSynced = await this.agentService.checkAgents()
        this.loading = false

        if (!this.isSynced) {
          let swalOptions = {
            title: "Updates",
            showCancelButton: true,
            text: "Agent Records Not Up to Date.",
            confirmButtonText: "Update",
            cancelButtonText: "Cancel",
          }
          this.$swal(swalOptions).then((result) => {
            if (result.value) {
              this.syncAgents()
            }
          })
        }
      } catch (e) {
        this.loading = false
        this.alertNotify("error", e.message)
      }
    },

    async syncAgents() {
      if (!this.loading) {
        try {
          this.loading = true
          this.isSynced = false
          await this.agentService.syncAgents()
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
      this.agentService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.agentService.list.length,
      )
    },
  },
}
</script>

<style scoped></style>
