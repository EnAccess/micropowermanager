<template>
  <div>
    <widget
      id="minutely-consumptions"
      :title="title"
      :paginator="true"
      :paging_url="minutelyConsumptionService.pagingUrl"
      :route_name="minutelyConsumptionService.routeName"
      :show_per_page="true"
      :subscriber="subscriber"
      color="green"
      :newRecordButton="false"
    >
      <md-table
        v-model="minutelyConsumptionService.list"
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
          <md-table-cell md-label="Date of Data" md-sort-by="dateOfData">
            {{ item.dateOfData }}
          </md-table-cell>
          <md-table-cell md-label="Time of Data" md-sort-by="timeOfData">
            {{ item.timeOfData }}
          </md-table-cell>
          <md-table-cell
            md-label="Positive Active Value"
            md-sort-by="positiveActiveValue"
          >
            {{ item.positiveActiveValue }}
          </md-table-cell>
          <md-table-cell
            md-label="Positive Reactive Value"
            md-sort-by="positiveReactiveValue"
          >
            {{ item.positiveReactiveValue }}
          </md-table-cell>
          <md-table-cell
            md-label="Inverted Active Value"
            md-sort-by="invertedActiveValue"
          >
            {{ item.invertedActiveValue }}
          </md-table-cell>
          <md-table-cell
            md-label="Inverted Reactive Value"
            md-sort-by="invertedReactiveValue"
          >
            {{ item.invertedReactiveValue }}
          </md-table-cell>
          <md-table-cell
            md-label="Positive Active Minute"
            md-sort-by="positiveActiveMinute"
          >
            {{ item.positiveActiveMinute }}
          </md-table-cell>
          <md-table-cell
            md-label="Positive Reactive Minute"
            md-sort-by="positiveReactiveMinute"
          >
            {{ item.positiveReactiveMinute }}
          </md-table-cell>
          <md-table-cell
            md-label="Inverted Active Minute"
            md-sort-by="invertedActiveMinute"
          >
            {{ item.invertedActiveMinute }}
          </md-table-cell>
          <md-table-cell
            md-label="Inverted Reactive Minute"
            md-sort-by="invertedReactiveMinute"
          >
            {{ item.invertedReactiveMinute }}
          </md-table-cell>
          <md-table-cell
            md-label="Voltage of Phase a"
            md-sort-by="voltageOfPhaseA"
          >
            {{ item.voltageOfPhaseA }}
          </md-table-cell>
          <md-table-cell
            md-label="Voltage of Phase b"
            md-sort-by="voltageOfPhaseB"
          >
            {{ item.voltageOfPhaseB }}
          </md-table-cell>
          <md-table-cell
            md-label="Voltage of Phase b"
            md-sort-by="voltageOfPhasec"
          >
            {{ item.voltageOfPhasec }}
          </md-table-cell>
          <md-table-cell md-label="Power" md-sort-by="power">
            {{ item.power }}
          </md-table-cell>
          <md-table-cell md-label="Power Factor" md-sort-by="powerFactor">
            {{ item.powerFactor }}
          </md-table-cell>
          <md-table-cell md-label="Reactive Power" md-sort-by="reactivePower">
            {{ item.reactivePower }}
          </md-table-cell>
          <md-table-cell
            md-label="Current of Phase a"
            md-sort-by="currentOfPhaseA"
          >
            {{ item.currentOfPhaseA }}
          </md-table-cell>
          <md-table-cell
            md-label="Current of Phase b"
            md-sort-by="currentOfPhaseB"
          >
            {{ item.currentOfPhaseB }}
          </md-table-cell>
          <md-table-cell
            md-label="Current of Phase c"
            md-sort-by="currentOfPhaseC"
          >
            {{ item.currentOfPhaseC }}
          </md-table-cell>
          <md-table-cell md-label="Temperature 1" md-sort-by="temperature1">
            {{ item.temperature1 }}
          </md-table-cell>
          <md-table-cell md-label="Temperature 2" md-sort-by="temperature2">
            {{ item.temperature2 }}
          </md-table-cell>
          <md-table-cell md-label="Pressure 1" md-sort-by="pressure1">
            {{ item.pressure1 }}
          </md-table-cell>
          <md-table-cell md-label="Pressure 2" md-sort-by="pressure2">
            {{ item.pressure2 }}
          </md-table-cell>
          <md-table-cell md-label="Flow Velocity" md-sort-by="flowVelocity">
            {{ item.flowVelocity }}
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
import { MinutelyConsumptionService } from "../../../services/MinutelyConsumptionService"
import { notify } from "@/mixins/notify"

export default {
  components: { Widget, RedirectionModal },
  name: "Daily",
  mixins: [notify],
  data() {
    return {
      title: "Minutely Consumptions",
      subscriber: "minutely-consumptions",
      credentialService: new CredentialService(),
      minutelyConsumptionService: new MinutelyConsumptionService(
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
      this.minutelyConsumptionService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.minutelyConsumptionService.list.length,
      )
    },
  },
}
</script>

<style scoped></style>
