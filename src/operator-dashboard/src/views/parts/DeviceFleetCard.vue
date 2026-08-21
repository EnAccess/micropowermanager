<template>
  <ops-card
    :title="$tc('phrases.deviceFleet')"
    :subtitle="
      $tc('phrases.deviceFleetSubtitle', 1, {
        value: formatCount(devices.total),
      })
    "
  >
    <div class="fleet">
      <div class="fleet__bar">
        <div
          v-for="segment in segments"
          :key="segment.label"
          class="fleet__segment"
          :style="{ background: segment.color, width: segment.width }"
        ></div>
      </div>

      <div class="fleet__legend">
        <div
          v-for="segment in segments"
          :key="segment.label"
          class="fleet__row"
        >
          <span
            class="fleet__swatch"
            :style="{ background: segment.color }"
          ></span>
          <span class="fleet__label">{{ segment.label }}</span>
          <span class="fleet__count tabular">
            {{ formatCount(segment.count) }}
          </span>
          <span class="fleet__share tabular">{{ segment.width }}</span>
        </div>
      </div>
    </div>
  </ops-card>
</template>

<script>
import { CHART_SERIES } from "@/design/palette.js"
import { formatCount } from "@/Helpers/format.js"
import OpsCard from "@/shared/OpsCard.vue"

export default {
  name: "DeviceFleetCard",
  components: { OpsCard },
  props: {
    devices: {
      type: Object,
      required: true,
    },
  },
  computed: {
    segments() {
      const total = this.devices.total || 1

      return [
        {
          label: this.$tc("phrases.smartMeters"),
          count: this.devices.meters,
          color: CHART_SERIES[1],
        },
        {
          label: this.$tc("phrases.solarHomeSystems"),
          count: this.devices.shs,
          color: CHART_SERIES[4],
        },
        {
          label: this.$tc("phrases.eBikes"),
          count: this.devices.ebikes,
          color: CHART_SERIES[6],
        },
      ].map((segment) => ({
        ...segment,
        width: `${Math.round((segment.count / total) * 100)}%`,
      }))
    },
  },
  methods: {
    formatCount,
  },
}
</script>

<style lang="scss" scoped>
.fleet {
  padding: 4px 24px 20px;
}

.fleet__bar {
  display: flex;
  height: 12px;
  border-radius: $ops-radius-control;
  overflow: hidden;
  gap: 2px;
  margin-bottom: 16px;
}

.fleet__segment {
  height: 100%;
}

.fleet__legend {
  display: flex;
  flex-direction: column;
  gap: 11px;
}

.fleet__row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.fleet__swatch {
  width: 10px;
  height: 10px;
  border-radius: 2px;
  flex: none;
}

.fleet__label {
  font-size: 13.5px;
  color: $ops-text;
  flex: 1;
  font-weight: 300;
}

.fleet__count {
  font-size: 14px;
  font-weight: 500;
  color: $ops-text-strong;
}

.fleet__share {
  font-size: 12px;
  color: $ops-text-muted;
  width: 40px;
  text-align: right;
}
</style>
