<template>
  <ops-card :title="$tc('phrases.deviceFleetHealth')">
    <div class="tenant-fleet">
      <div class="tenant-fleet__legend">
        <div v-for="item in legend" :key="item.label" class="tenant-fleet__row">
          <span
            class="tenant-fleet__swatch"
            :style="{ background: item.color }"
          ></span>
          <span class="tenant-fleet__label">{{ item.label }}</span>
          <span class="tenant-fleet__count tabular">
            {{ formatCount(item.count) }}
          </span>
        </div>
      </div>

      <hr class="tenant-fleet__divider" />

      <div class="tenant-fleet__bar-group">
        <div class="tenant-fleet__bar-head">
          <span class="tenant-fleet__bar-label">
            {{ $tc("phrases.metersInUse") }}
          </span>
          <span class="tenant-fleet__bar-value tabular">
            {{ formatCount(tenant.metersAssignedToCustomer) }} /
            {{ formatCount(tenant.devices.meters) }}
          </span>
        </div>
        <progress-bar :percentage="inUsePercentage" />
      </div>

      <div class="tenant-fleet__bar-group">
        <div class="tenant-fleet__bar-head">
          <span class="tenant-fleet__bar-label">
            {{ $tc("phrases.reportedLastSevenDays") }}
          </span>
          <span class="tenant-fleet__bar-value tabular">
            {{ reportedLabel }}
          </span>
        </div>
        <progress-bar :percentage="reportedPercentage" :color="reportedColor" />
      </div>
    </div>
  </ops-card>
</template>

<script>
import { CHART_SERIES } from "@/design/palette.js"
import { formatCount } from "@/Helpers/format.js"
import OpsCard from "@/shared/OpsCard.vue"
import ProgressBar from "@/shared/ProgressBar.vue"

export default {
  name: "TenantFleetHealthCard",
  components: { OpsCard, ProgressBar },
  props: {
    tenant: {
      type: Object,
      required: true,
    },
  },
  computed: {
    legend() {
      return [
        {
          label: this.$tc("phrases.smartMeters"),
          count: this.tenant.devices.meters,
          color: CHART_SERIES[1],
        },
        {
          label: this.$tc("phrases.solarHomeSystems"),
          count: this.tenant.devices.shs,
          color: CHART_SERIES[4],
        },
        {
          label: this.$tc("phrases.eBikes"),
          count: this.tenant.devices.ebikes,
          color: CHART_SERIES[6],
        },
      ]
    },
    inUsePercentage() {
      return this.percentageOfMeters(this.tenant.metersAssignedToCustomer)
    },
    reportedPercentage() {
      return this.percentageOfMeters(this.tenant.metersReportingLastSevenDays)
    },
    /**
     * A null reading count means this tenant's meter manufacturer does not report
     * consumption at all, which is different from every meter being silent.
     */
    reportedLabel() {
      if (this.tenant.metersReportingLastSevenDays === null) {
        return this.$tc("phrases.notAvailable")
      }

      return `${formatCount(this.tenant.metersReportingLastSevenDays)} / ${formatCount(
        this.tenant.devices.meters,
      )}`
    },
    reportedColor() {
      if (this.reportedPercentage >= 70) {
        return "#81a872"
      }
      if (this.reportedPercentage >= 40) {
        return "#fab15b"
      }

      return "#fa8d41"
    },
  },
  methods: {
    formatCount,
    percentageOfMeters(count) {
      if (!count || !this.tenant.devices.meters) {
        return 0
      }

      return Math.round((count / this.tenant.devices.meters) * 100)
    },
  },
}
</script>

<style lang="scss" scoped>
.tenant-fleet {
  padding: 4px 24px 20px;
}

.tenant-fleet__legend {
  display: flex;
  flex-direction: column;
  gap: 11px;
}

.tenant-fleet__row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.tenant-fleet__swatch {
  width: 10px;
  height: 10px;
  border-radius: 2px;
  flex: none;
}

.tenant-fleet__label {
  font-size: 13.5px;
  color: $ops-text;
  flex: 1;
  font-weight: 300;
}

.tenant-fleet__count {
  font-size: 14px;
  font-weight: 500;
  color: $ops-text-strong;
}

.tenant-fleet__divider {
  border: none;
  border-top: 1px solid $ops-row-border;
  margin: 16px 0;
}

.tenant-fleet__bar-group + .tenant-fleet__bar-group {
  margin-top: 14px;
}

.tenant-fleet__bar-head {
  display: flex;
  align-items: baseline;
  margin-bottom: 6px;
}

.tenant-fleet__bar-label {
  font-size: 13px;
  font-weight: 300;
  color: $ops-text;
}

.tenant-fleet__bar-value {
  margin-left: auto;
  font-size: 12.5px;
  font-weight: 500;
  color: $ops-text-strong;
}
</style>
