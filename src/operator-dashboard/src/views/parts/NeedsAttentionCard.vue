<template>
  <ops-card
    :title="$tc('phrases.needsAttention')"
    :subtitle="$tc('phrases.needsAttentionSubtitle')"
    color="secondary"
  >
    <template #actions>
      <router-link
        :to="{ name: 'tenants' }"
        class="mpm-button mpm-button--on-header mpm-button--dense"
      >
        {{ $tc("phrases.allTenants") }}
        <span class="material-icons">arrow_forward</span>
      </router-link>
    </template>

    <div v-if="tenants.length === 0" class="attention__empty">
      {{ $tc("phrases.allTenantsActive") }}
    </div>

    <div
      v-for="tenant in tenants"
      :key="tenant.id"
      class="attention__row"
      @click="open(tenant)"
    >
      <status-dot :status="tenant.health" :size="9" />
      <div class="attention__identity">
        <div class="attention__name">{{ tenant.name }}</div>
        <div class="attention__country">{{ tenant.country || "—" }}</div>
      </div>
      <div class="attention__metrics">
        <div class="attention__last" :class="`status-text--${tenant.health}`">
          {{ relativeLabel(tenant.lastActiveAt) }}
        </div>
        <div class="attention__tx tabular">
          {{
            $tc("phrases.transactionsPerMonthShort", 1, {
              value: formatCount(tenant.transactionsThisMonth),
            })
          }}
        </div>
      </div>
      <span class="material-icons attention__chevron">
        keyboard_arrow_right
      </span>
    </div>
  </ops-card>
</template>

<script>
import { formatCount } from "@/Helpers/format.js"
import { daysSince, relativeDaysKey } from "@/Helpers/relativeTime.js"
import OpsCard from "@/shared/OpsCard.vue"
import StatusDot from "@/shared/StatusDot.vue"

export default {
  name: "NeedsAttentionCard",
  components: { OpsCard, StatusDot },
  props: {
    tenants: {
      type: Array,
      required: true,
    },
  },
  methods: {
    formatCount,
    relativeLabel(lastActiveAt) {
      const { key, count } = relativeDaysKey(daysSince(lastActiveAt))

      return this.$tc(key, 1, { value: count })
    },
    open(tenant) {
      this.$router.push({
        name: "tenant-detail",
        params: { tenantId: tenant.id },
      })
    },
  },
}
</script>

<style lang="scss" scoped>
.attention__empty {
  padding: 11px 24px 20px;
  font-size: $font-cell;
  color: $brand-accent-dark;
}

.attention__row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 24px;
  border-top: 1px solid $ops-row-border;
  cursor: pointer;

  &:hover {
    background: $ops-row-hover;
  }
}

.attention__identity {
  min-width: 0;
  flex: 1;
}

.attention__name {
  font-size: $font-cell;
  font-weight: 400;
  color: $ops-text;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.attention__country {
  font-size: $font-caption;
  color: $ops-text-muted;
}

.attention__metrics {
  text-align: right;
  flex: none;
}

.attention__last {
  font-size: $font-caption;
  font-weight: 500;
}

.attention__tx {
  font-size: $font-caption;
  color: $ops-text-muted;
}

.attention__chevron {
  font-size: 18px;
  color: $ops-icon-faint;
  flex: none;
}
</style>
