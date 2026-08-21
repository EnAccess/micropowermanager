<template>
  <ops-card :title="$tc('phrases.recentActivity')">
    <div v-for="entry in entries" :key="entry.type" class="activity__row">
      <span
        class="material-icons activity__icon"
        :style="{ color: entry.color }"
      >
        {{ entry.icon }}
      </span>
      <div class="activity__body">
        <div class="activity__title">{{ entry.title }}</div>
        <div v-if="entry.meta" class="activity__meta">{{ entry.meta }}</div>
      </div>
      <div class="activity__time">{{ entry.time }}</div>
    </div>
  </ops-card>
</template>

<script>
import { formatCount } from "@/Helpers/format.js"
import { daysSince, relativeDaysKey } from "@/Helpers/relativeTime.js"
import OpsCard from "@/shared/OpsCard.vue"

// Entries derived from a count describe their own period, so they carry a fixed
// time label instead of a timestamp; only the dated entries get a relative one.
const PRESENTATION = {
  payments_this_month: {
    icon: "attach_money",
    color: "#1b75ba",
    title: "phrases.paymentsThisMonth",
    countInTitle: true,
    time: "phrases.thisMonth",
  },
  meters_reporting: {
    icon: "settings_input_hdmi",
    color: "#fa8d41",
    title: "phrases.meterReadingsReceived",
    meta: "phrases.metersReported",
    time: "phrases.lastSevenDays",
  },
  customers_onboarded: {
    icon: "supervisor_account",
    color: "#628b45",
    title: "phrases.customersOnboarded",
    meta: "phrases.newCustomersCount",
    time: "phrases.thisMonth",
  },
  tariff_updated: {
    icon: "list",
    color: "#949494",
    title: "phrases.tariffUpdated",
  },
  sms_sent: { icon: "cast", color: "#1b75ba", title: "phrases.smsSent" },
}

export default {
  name: "TenantActivityCard",
  components: { OpsCard },
  props: {
    activity: {
      type: Array,
      required: true,
    },
  },
  computed: {
    entries() {
      return this.activity.map((entry) => {
        const presentation = PRESENTATION[entry.type] || {
          icon: "list",
          color: "#949494",
          title: entry.type,
        }

        return {
          type: entry.type,
          icon: presentation.icon,
          color: presentation.color,
          title: this.title(entry, presentation),
          meta: entry.detail || this.metaFor(entry, presentation),
          time: this.timeFor(entry, presentation),
        }
      })
    },
  },
  methods: {
    title(entry, presentation) {
      if (
        !presentation.countInTitle ||
        entry.count === null ||
        entry.count === undefined
      ) {
        return this.$tc(presentation.title)
      }

      return this.$tc(presentation.title, 1, {
        value: formatCount(entry.count),
      })
    },
    metaFor(entry, presentation) {
      if (!presentation.meta) {
        return null
      }
      // A null count means the tenant has no source for this figure at all.
      if (entry.count === null || entry.count === undefined) {
        return this.$tc("phrases.notAvailable")
      }

      return this.$tc(presentation.meta, 1, { value: formatCount(entry.count) })
    },
    timeFor(entry, presentation) {
      if (entry.at) {
        const { key, count } = relativeDaysKey(daysSince(entry.at))

        return this.$tc(key, 1, { value: count })
      }

      return presentation.time ? this.$tc(presentation.time) : "—"
    },
  },
}
</script>

<style lang="scss" scoped>
.activity__row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 24px;
  border-top: 1px solid $ops-row-border;
}

.activity__icon {
  font-size: 20px;
  flex: none;
}

.activity__body {
  min-width: 0;
  flex: 1;
}

.activity__title {
  font-size: 13.5px;
  font-weight: 400;
  color: $ops-text;
}

.activity__meta {
  font-size: 12px;
  font-weight: 300;
  color: $ops-text-muted;
}

.activity__time {
  font-size: 12px;
  color: $ops-text-muted;
  flex: none;
}
</style>
