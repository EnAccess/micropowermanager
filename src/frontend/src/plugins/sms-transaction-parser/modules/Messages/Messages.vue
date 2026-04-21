<template>
  <div>
    <div class="md-layout md-gutter" style="margin-bottom: 16px">
      <div class="md-layout-item md-size-30">
        <md-field>
          <label>Select Parsing Rule</label>
          <md-select v-model="selectedRuleId">
            <md-option v-for="rule in rules" :key="rule.id" :value="rule.id">
              {{ rule.provider_name }}
            </md-option>
          </md-select>
        </md-field>
      </div>
    </div>

    <widget
      v-if="selectedRuleId"
      title="Parsed Messages"
      :paginator="paginator"
      color="primary"
      :subscriber="subscriber"
      :key="selectedRuleId"
      :show_per_page="true"
    >
      <md-table
        style="width: 100%"
        v-model="transactions"
        md-card
        md-fixed-header
      >
        <md-table-row slot="md-table-row" slot-scope="{ item }">
          <md-table-cell md-label="Status">
            <md-icon :class="statusClass(item.status)">
              {{ statusIcon(item.status) }}
            </md-icon>
          </md-table-cell>
          <md-table-cell md-label="Transaction Ref">
            {{ item.transaction_reference }}
          </md-table-cell>
          <md-table-cell md-label="Amount">
            {{ item.amount }}
          </md-table-cell>
          <md-table-cell md-label="Device Serial">
            {{ item.device_serial }}
          </md-table-cell>
          <md-table-cell md-label="Sender">
            {{ item.sender_phone }}
          </md-table-cell>
          <md-table-cell md-label="Date">
            {{ item.created_at }}
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
  </div>
</template>

<script>
import { ParsingRuleService } from "../../services/ParsingRuleService.js"

import { Paginator } from "@/Helpers/Paginator.js"
import { EventBus } from "@/shared/eventbus.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "Messages",
  components: { Widget },
  data() {
    return {
      parsingRuleService: new ParsingRuleService(),
      rules: [],
      selectedRuleId: null,
      paginator: null,
      transactions: [],
      subscriber: "sms-messages",
    }
  },
  mounted() {
    this.loadRules()
    EventBus.$on("pageLoaded", this.reloadList)
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
  },
  watch: {
    selectedRuleId(newId) {
      if (!newId) return
      this.transactions = []
      this.paginator = new Paginator(
        `/api/sms-transaction-parser/parsing-rules/${newId}/messages`,
      )
    },
  },
  methods: {
    async loadRules() {
      this.rules = await this.parsingRuleService.getRules()
      if (this.rules.length > 0) {
        this.selectedRuleId = this.rules[0].id
      }
    },
    reloadList(sub, data) {
      if (sub !== this.subscriber) return
      this.transactions = data
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.transactions.length,
      )
    },
    statusIcon(status) {
      if (status === 1) return "check_circle"
      if (status === -1) return "error"
      return "schedule"
    },
    statusClass(status) {
      if (status === 1) return "status-success"
      if (status === -1) return "status-failed"
      return "status-pending"
    },
  },
}
</script>

<style lang="scss" scoped>
.status-success {
  color: #4caf50 !important;
}

.status-failed {
  color: #f44336 !important;
}

.status-pending {
  color: #ff9800 !important;
}
</style>
