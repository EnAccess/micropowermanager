<template>
  <div>
    <widget
      id="daily-consumptions"
      :title="title"
      :paginator="true"
      :paging_url="dailyConsumptionService.pagingUrl"
      :route_name="dailyConsumptionService.routeName"
      :show_per_page="true"
      :subscriber="subscriber"
      color="green"
      :newRecordButton="false"
    >
      <md-table
        v-model="dailyConsumptionService.list"
        md-sort="id"
        md-sort-order="asc"
        md-card
      >
        <md-table-row slot="md-table-row" slot-scope="{ item }">
          <md-table-cell md-label="Terminal ID" md-sort-by="terminalId">
            {{ item.terminalId }}
          </md-table-cell>
          <md-table-cell
            md-label="Measurement Point"
            md-sort-by="measurementPoint"
          >
            {{ item.measurementPoint }}
          </md-table-cell>
          <md-table-cell md-label="Meter Address" md-sort-by="meterAddress">
            {{ item.meterAddress }}
          </md-table-cell>
          <md-table-cell md-label="Meter Name" md-sort-by="meterName">
            {{ item.meterName }}
          </md-table-cell>
          <md-table-cell md-label="Date Of Data" md-sort-by="dateOfData">
            {{ item.dateOfData }}
          </md-table-cell>
          <md-table-cell
            md-label="Total Value of Positive Active Power Cumulative flow Indication"
            md-sort-by="totalValueOfPositiveActivePowerCumulativeFlowIndication"
          >
            {{ item.totalValueOfPositiveActivePowerCumulativeFlowIndication }}
          </md-table-cell>
          <md-table-cell
            md-label="Total Value of Positive Active Peak Power"
            md-sort-by="totalValueOfPositiveActivePeakPower"
          >
            {{ item.totalValueOfPositiveActivePeakPower }}
          </md-table-cell>
          <md-table-cell
            md-label="Total Value of Positive Active Flat Power"
            md-sort-by="totalValueOfPositiveActiveFlatPower"
          >
            {{ item.totalValueOfPositiveActiveFlatPower }}
          </md-table-cell>
          <md-table-cell
            md-label="Total Value of Positive Active Valley Power"
            md-sort-by="totalValueOfPositiveActiveValleyPower"
          >
            {{ item.totalValueOfPositiveActiveValleyPower }}
          </md-table-cell>
          <md-table-cell
            md-label="Total Value of Positive Active Spike Power"
            md-sort-by="totalValueOfPositiveActiveSpikePower"
          >
            {{ item.totalValueOfPositiveActiveSpikePower }}
          </md-table-cell>
          <md-table-cell
            md-label="Total Value of Positive Reactive Power Cumulative flow Indication"
            md-sort-by="totalValueOfPositiveReactivePowerCumulativeFlowIndication"
          >
            {{ item.totalValueOfPositiveReactivePowerCumulativeFlowIndication }}
          </md-table-cell>
          <md-table-cell
            md-label="Total Value of Positive Reactive Peak Power"
            md-sort-by="totalValueOfPositiveReactivePeakPower"
          >
            {{ item.totalValueOfPositiveReactivePeakPower }}
          </md-table-cell>
          <md-table-cell
            md-label="Total Value of Positive Reactive Flat Power"
            md-sort-by="totalValueOfPositiveReactiveFlatPower"
          >
            {{ item.totalValueOfPositiveReactiveFlatPower }}
          </md-table-cell>
          <md-table-cell
            md-label="Total Value of Positive Reactive Valley Power"
            md-sort-by="totalValueOfPositiveReactiveValleyPower"
          >
            {{ item.totalValueOfPositiveReactiveValleyPower }}
          </md-table-cell>
          <md-table-cell
            md-label="Total Value of Positive Reactive Spike Power"
            md-sort-by="totalValueOfPositiveReactiveSpikePower"
          >
            {{ item.totalValueOfPositiveReactiveSpikePower }}
          </md-table-cell>
          <md-table-cell
            md-label="Total Value of Reverted Active Power Cumulative flow Indication"
            md-sort-by="totalValueOfRevertedActivePowerCumulativeFlowIndication"
          >
            {{ item.totalValueOfRevertedActivePowerCumulativeFlowIndication }}
          </md-table-cell>
          <md-table-cell
            md-label="Total Value of Reverted Reactive Power Cumulative flow Indication"
            md-sort-by="totalValueOfRevertedReactivePowerCumulativeFlowIndication"
          >
            {{ item.totalValueOfRevertedReactivePowerCumulativeFlowIndication }}
          </md-table-cell>
          <md-table-cell
            md-label="Positive Active Total Daily Power"
            md-sort-by="positiveActiveTotalDailyPower"
          >
            {{ item.positiveActiveTotalDailyPower }}
          </md-table-cell>
          <md-table-cell
            md-label="Positive Active Daily Power in Peak"
            md-sort-by="positiveActiveDailyPowerInPeak"
          >
            {{ item.positiveActiveDailyPowerInPeak }}
          </md-table-cell>
          <md-table-cell
            md-label="Positive Active Daily Power in Flat"
            md-sort-by="positiveActiveDailyPowerInFlat"
          >
            {{ item.positiveActiveDailyPowerInFlat }}
          </md-table-cell>
          <md-table-cell
            md-label="Positive Active Daily Power in Valley"
            md-sort-by="positiveActiveDailyPowerInValley"
          >
            {{ item.positiveActiveDailyPowerInValley }}
          </md-table-cell>
          <md-table-cell
            md-label="Positive Active Daily Power in Spike"
            md-sort-by="positiveActiveDailyPowerInSpike"
          >
            {{ item.positiveActiveDailyPowerInSpike }}
          </md-table-cell>
          <md-table-cell
            md-label="Positive Reactive Total Daily Power"
            md-sort-by="positiveReactiveTotalDailyPower"
          >
            {{ item.positiveReactiveTotalDailyPower }}
          </md-table-cell>
          <md-table-cell
            md-label="Reverted Active Total Daily Power"
            md-sort-by="revertedActiveTotalDailyPower"
          >
            {{ item.revertedActiveTotalDailyPower }}
          </md-table-cell>
          <md-table-cell
            md-label="Reverted Reactive Total Daily Power"
            md-sort-by="revertedReactiveTotalDailyPower"
          >
            {{ item.revertedReactiveTotalDailyPower }}
          </md-table-cell>
        </md-table-row>
      </md-table>
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
import { CredentialService } from "../../../services/CredentialService"
import { EventBus } from "@/shared/eventbus"
import RedirectionModal from "@/shared/RedirectionModal"
import { DailyConsumptionService } from "../../../services/DailyConsumptionService"
import { notify } from "@/mixins/notify"

export default {
  components: { Widget, RedirectionModal },
  name: "Daily",
  mixins: [notify],
  data() {
    return {
      title: "Daily Consumptions",
      subscriber: "daily-consumptions",
      credentialService: new CredentialService(),
      dailyConsumptionService: new DailyConsumptionService(
        this.$route.params.meter,
      ),
      redirectionUrl: "/kelin-meter/kelin-overview",
      redirectDialogActive: false,
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
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) return
      this.dailyConsumptionService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.dailyConsumptionService.list.length,
      )
    },
  },
}
</script>

<style scoped></style>
