<template>
  <div>
    <widget
      :title="$tc('words.meter', 2)"
      :paginator="service.paginator"
      :subscriber="subscriber"
      route_name="/sts-meter-vend/meters"
      color="primary"
    >
      <md-table md-card style="margin-left: 0">
        <md-table-row>
          <md-table-head>{{ $tc("phrases.serialNumber") }}</md-table-head>
          <md-table-head>{{ $tc("words.owner") }}</md-table-head>
          <md-table-head>{{ $tc("words.tariff") }}</md-table-head>
          <md-table-head>{{ $tc("phrases.stsDecoderKeys") }}</md-table-head>
          <md-table-head>{{ $tc("phrases.stsKeyProfile") }}</md-table-head>
          <md-table-head>{{ $tc("phrases.stsLastIssued") }}</md-table-head>
        </md-table-row>
        <md-table-row
          v-for="meter in service.list"
          :key="meter.id"
          style="cursor: pointer"
          @click="$router.push('/meters/' + meter.serialNumber)"
        >
          <md-table-cell>{{ meter.serialNumber }}</md-table-cell>
          <md-table-cell>{{ meter.owner }}</md-table-cell>
          <md-table-cell>{{ meter.tariff }}</md-table-cell>
          <md-table-cell>
            <md-icon :class="meter.keyPresent ? 'key-present' : 'key-missing'">
              {{ meter.keyPresent ? "vpn_key" : "vpn_key_off" }}
            </md-icon>
            {{
              $tc(
                meter.keyPresent ? "phrases.stsKeyOnFile" : "phrases.stsNoKey",
              )
            }}
          </md-table-cell>
          <md-table-cell>
            {{
              meter.keyPresent
                ? `${meter.baseDate} · ${meter.encryptionAlgorithm}`
                : "—"
            }}
          </md-table-cell>
          <md-table-cell>
            {{
              meter.lastIssuedAt === null
                ? "—"
                : timeForTimeZone(meter.lastIssuedAt)
            }}
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
  </div>
</template>

<script>
import { StsManagedMeterService } from "../../services/StsManagedMeterService.js"

import { timing } from "@/mixins/timing.js"
import { EventBus } from "@/shared/eventbus.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "StsMeterList",
  components: { Widget },
  mixins: [timing],
  data() {
    return {
      service: new StsManagedMeterService(),
      subscriber: "stsMeterList",
    }
  },
  mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
  },
  methods: {
    reloadList(subscriber, rows) {
      if (subscriber !== this.subscriber) {
        return
      }
      this.service.updateList(rows)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.service.list.length,
      )
    },
  },
}
</script>

<style scoped lang="scss">
.key-present {
  color: #2e7d32;
}

.key-missing {
  color: #b00020;
}
</style>
