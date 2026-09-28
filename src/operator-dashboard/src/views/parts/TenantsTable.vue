<template>
  <div class="table__scroll">
    <table class="table">
      <thead>
        <tr>
          <th
            v-for="column in columns"
            :key="column.key"
            :class="[
              'table__header',
              {
                'table__header--numeric': column.numeric,
                'table__header--sortable': column.sortable,
                'table__header--sorted': sortKey === column.key,
              },
            ]"
            @click="column.sortable ? sortBy(column.key) : null"
          >
            <span
              v-if="sortKey === column.key"
              class="material-icons table__arrow"
            >
              {{ sortDirection === "asc" ? "arrow_upward" : "arrow_downward" }}
            </span>
            {{ column.label }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="tenant in sortedTenants"
          :key="tenant.id"
          class="table__row"
          @click="open(tenant)"
        >
          <td class="table__cell">{{ tenant.name }}</td>
          <td class="table__cell">{{ tenant.country || "—" }}</td>
          <td class="table__cell">{{ tenant.usageType || "—" }}</td>
          <td class="table__cell">
            {{ formatMonthYear(tenant.registeredAt) }}
          </td>
          <td class="table__cell">
            <span
              class="table__health"
              :class="`status-text--${tenant.health}`"
            >
              <status-dot :status="tenant.health" :size="8" />
              {{ relativeLabel(tenant.lastActiveAt) }}
            </span>
          </td>
          <td class="table__cell table__cell--numeric tabular">
            {{ formatCount(tenant.customers) }}
          </td>
          <td class="table__cell table__cell--numeric tabular">
            {{ formatCount(tenant.devices.total) }}
          </td>
          <td
            class="table__cell table__cell--numeric table__cell--strong tabular"
          >
            {{ formatCount(tenant.transactionsThisMonth) }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import { formatCount, formatMonthYear } from "@/Helpers/format.js"
import { daysSince, relativeDaysKey } from "@/Helpers/relativeTime.js"
import StatusDot from "@/shared/StatusDot.vue"

const SORT_VALUES = {
  name: (tenant) => tenant.name.toLowerCase(),
  country: (tenant) => (tenant.country || "").toLowerCase(),
  registeredAt: (tenant) => new Date(tenant.registeredAt || 0).getTime(),
  lastActiveAt: (tenant) => new Date(tenant.lastActiveAt || 0).getTime(),
  customers: (tenant) => tenant.customers,
  devices: (tenant) => tenant.devices.total,
  transactionsThisMonth: (tenant) => tenant.transactionsThisMonth,
}

// Text sorts read naturally ascending; counts and recency are most interesting
// at the top.
const ASCENDING_FIRST = ["name", "country"]

export default {
  name: "TenantsTable",
  components: { StatusDot },
  props: {
    tenants: {
      type: Array,
      required: true,
    },
  },
  data() {
    return {
      sortKey: "lastActiveAt",
      sortDirection: "desc",
    }
  },
  computed: {
    columns() {
      return [
        { key: "name", label: this.$tc("words.tenant"), sortable: true },
        { key: "country", label: this.$tc("words.country"), sortable: true },
        {
          key: "usageType",
          label: this.$tc("phrases.usageType"),
          sortable: false,
        },
        {
          key: "registeredAt",
          label: this.$tc("words.registered"),
          sortable: true,
        },
        {
          key: "lastActiveAt",
          label: this.$tc("words.lastActive"),
          sortable: true,
        },
        {
          key: "customers",
          label: this.$tc("words.customers"),
          sortable: true,
          numeric: true,
        },
        {
          key: "devices",
          label: this.$tc("words.devices"),
          sortable: true,
          numeric: true,
        },
        {
          key: "transactionsThisMonth",
          label: this.$tc("phrases.thisMonthTransactions"),
          sortable: true,
          numeric: true,
        },
      ]
    },
    sortedTenants() {
      const readValue = SORT_VALUES[this.sortKey] || SORT_VALUES.lastActiveAt
      const direction = this.sortDirection === "asc" ? 1 : -1

      return [...this.tenants].sort((left, right) => {
        const leftValue = readValue(left)
        const rightValue = readValue(right)

        if (leftValue < rightValue) {
          return -1 * direction
        }
        if (leftValue > rightValue) {
          return direction
        }

        return 0
      })
    },
  },
  methods: {
    formatCount,
    formatMonthYear,
    relativeLabel(lastActiveAt) {
      const { key, count } = relativeDaysKey(daysSince(lastActiveAt))

      return this.$tc(key, 1, { value: count })
    },
    sortBy(key) {
      if (this.sortKey === key) {
        this.sortDirection = this.sortDirection === "asc" ? "desc" : "asc"

        return
      }

      this.sortKey = key
      this.sortDirection = ASCENDING_FIRST.includes(key) ? "asc" : "desc"
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
// Mirrors vue-material's md-table as src/frontend renders it.
.table__scroll {
  overflow-x: auto;
}

.table {
  width: 100%;
  border-collapse: collapse;
  min-width: 900px;
  background: $brand-white;
}

.table__header {
  height: 56px;
  padding: 0 32px 0 24px;
  text-align: left;
  font-size: $font-caption;
  font-weight: 500;
  color: $ops-text-secondary;
  white-space: nowrap;

  &:last-child {
    padding-right: 24px;
  }
}

.table__header--sortable {
  cursor: pointer;

  &:hover {
    color: $ops-text-primary;
  }
}

.table__header--sorted {
  color: $ops-text-primary;
}

.table__header--numeric {
  text-align: right;
}

.table__arrow {
  font-size: 16px;
  vertical-align: -3px;
  margin-right: 2px;
}

.table__row {
  border-top: 1px solid $ops-row-border;
  cursor: pointer;

  &:hover {
    background: $ops-row-hover;
  }
}

.table__cell {
  height: 48px;
  padding: 6px 32px 6px 24px;
  font-size: $font-cell;
  color: $ops-text-primary;
  white-space: nowrap;

  &:last-child {
    padding-right: 24px;
  }
}

.table__cell--numeric {
  text-align: right;
  color: $ops-text-strong;
}

.table__cell--strong {
  font-weight: 500;
}

.table__health {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-weight: 400;
}
</style>
