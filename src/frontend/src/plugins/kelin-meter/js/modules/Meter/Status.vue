<template>
  <div>
    <widget
      id="meter-status"
      :title="title"
      color="green"
      :newRecordButton="false"
    >
      <div class="md-layout md-gutter">
        <div class="md-layout-item md-size-100">
          <div class="md-layout md-gutter">
            <div class="md-layout-item md-size-40" style="padding-left: 3rem">
              <md-icon class="md-size-4x">developer_board</md-icon>
              <h4>
                <span class="title-span">Address :</span>
                {{ this.statusService.status.meterAddress }}
                <span class="title-span">Owner :</span>
                {{ this.statusService.status.owner }}
              </h4>
            </div>
            <div class="md-layout-item md-size-60">
              <div class="md-layout-item md-layout md-size-100">
                <div
                  class="md-layout-item md-layout md-gutter md-size-100"
                  style="margin-bottom: 3vh"
                >
                  <div class="md-layout-item md-size-35">
                    <h4>
                      <span class="title-span">Energy Remain :</span>
                      {{ this.statusService.status.energyRemain }}
                    </h4>
                  </div>
                  <div class="md-layout-item md-size-35">
                    <h4>
                      <span class="title-span">Money Remain :</span>
                      {{ this.statusService.status.moneyRemain }}
                    </h4>
                  </div>
                  <div class="md-layout-item md-size-30">
                    <span class="title-span">Status :</span>

                    <md-switch
                      v-model="statusOfMeter"
                      @change="changeMeterStatus($event)"
                      :disabled="switching"
                      class="data-stream-switch"
                    ></md-switch>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="md-layout-item md-size-100">
          <md-table md-card style="margin-left: 0">
            <md-table-row>
              <md-table-head>Positive Active Value</md-table-head>
              <md-table-head>Positive Reactive Value</md-table-head>
              <md-table-head>Inverted Active Value</md-table-head>
              <md-table-head>Inverted Reactive Value</md-table-head>
              <md-table-head>Positive Active Daily Power</md-table-head>
              <md-table-head>Positive Reactive Daily Power</md-table-head>
              <md-table-head>Inverted Active Daily Power</md-table-head>
              <md-table-head>Inverted Reactive Daily Power</md-table-head>
              <md-table-head>Open Cover Count</md-table-head>
              <md-table-head>Open Terminal Count</md-table-head>
            </md-table-row>
            <md-table-row>
              <md-table-cell>
                {{ this.statusService.status.positiveActiveValue }}
              </md-table-cell>
              <md-table-cell>
                {{ this.statusService.status.positiveReactiveValue }}
              </md-table-cell>
              <md-table-cell>
                {{ this.statusService.status.invertedActiveValue }}
              </md-table-cell>
              <md-table-cell>
                {{ this.statusService.status.invertedReactiveValue }}
              </md-table-cell>
              <md-table-cell>
                {{ this.statusService.status.positiveActiveDailyPower }}
              </md-table-cell>
              <md-table-cell>
                {{ this.statusService.status.positiveReactiveDailyPower }}
              </md-table-cell>
              <md-table-cell>
                {{ this.statusService.status.invertedActiveDailyPower }}
              </md-table-cell>
              <md-table-cell>
                {{ this.statusService.status.invertedReactiveDailyPower }}
              </md-table-cell>
              <md-table-cell>
                {{ this.statusService.status.openCoverCount }}
              </md-table-cell>
              <md-table-cell>
                {{ this.statusService.status.openTerminalCount }}
              </md-table-cell>
            </md-table-row>
          </md-table>
        </div>
      </div>
    </widget>
    <redirection-modal
      :redirection-url="redirectionUrl"
      :dialog-active="redirectDialogActive"
      :imperative-item="'valid API Credentials'"
    />
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { StatusService } from "../../services/StatusService"
import { CredentialService } from "../../services/CredentialService"
import RedirectionModal from "@/shared/RedirectionModal"
import { notify } from "@/mixins/notify"

export default {
  components: { Widget, RedirectionModal },
  name: "Status",
  mixins: [notify],
  data() {
    return {
      statusService: new StatusService(),
      credentialService: new CredentialService(),
      title: "meter-status",
      redirectionUrl: "/kelin-meters/kelin-overview",
      meterId: this.$route.params.meter,
      redirectDialogActive: false,
      statusOfMeter: false,
      switching: false,
    }
  },
  mounted() {
    this.checkCredential()
    this.getMeterStatus()
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
    async getMeterStatus() {
      try {
        await this.statusService.getMeterStatus(this.meterId)
        this.statusOfMeter = this.statusService.status.meterStatus === "ON"
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async changeMeterStatus(status) {
      try {
        this.switching = true
        await this.statusService.changeMeterStatus(this.meterId, status)
        let message =
          status === true ? "Meter status set as ON" : "Meter status set as OFF"
        this.alertNotify("success", message)
        this.switching = false
      } catch (e) {
        this.switching = false
        this.alertNotify("error", e.message)
      }
    },
  },
}
</script>

<style scoped>
.title-span {
  font-weight: bold;
}
</style>
