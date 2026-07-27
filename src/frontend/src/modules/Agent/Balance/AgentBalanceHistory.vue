<template>
  <div>
    <add-agent-balance
      v-if="isBalanceType"
      :addNewBalance="showNewBalance"
      :agent-id="agentId"
    />
    <widget
      :class="'col-sm-12'"
      :button-text="isBalanceType ? $tc('phrases.addBalance') : null"
      :button="isBalanceType"
      :title="
        isBalanceType
          ? $tc('phrases.balanceHistories')
          : $tc('phrases.commissionHistories')
      "
      :button-color="'red'"
      @widgetAction="showAddBalance"
      :paginator="agentBalanceHistoryService.paginator"
      :subscriber="subscriber"
      :resetKey="resetKey"
      :show_per_page="true"
      color="primary"
    >
      <div>
        <div class="history-total">
          <span>
            {{
              isBalanceType
                ? $tc("phrases.currentBalance")
                : $tc("phrases.commissionBalance")
            }}
          </span>
          <strong>{{ moneyFormat(total) }}</strong>
        </div>
        <md-table md-sort="id" md-sort-order="asc">
          <md-table-row>
            <md-table-head v-for="(item, index) in headers" :key="index">
              {{ item }}
            </md-table-head>
          </md-table-row>
          <md-table-row
            v-for="item in agentBalanceHistoryService.list"
            :key="item.id"
          >
            <md-table-cell md-sort-by="id" md-label="ID">
              {{ item.id }}
            </md-table-cell>
            <md-table-cell md-label="Type">
              {{ typeLabel(item) }}
            </md-table-cell>
            <md-table-cell md-label="Transaction ID">
              {{ item.transactionId || "—" }}
            </md-table-cell>
            <md-table-cell md-label="Amount">
              {{ moneyFormat(item.amount) }}
            </md-table-cell>
            <md-table-cell md-label="Date">
              {{ item.createdAt }}
            </md-table-cell>
          </md-table-row>
        </md-table>
      </div>
    </widget>
  </div>
</template>
<script>
import AddAgentBalance from "./AddBalance.vue"

import { currency } from "@/mixins/currency.js"
import { notify } from "@/mixins/notify.js"
import { AgentBalanceHistoryService } from "@/services/AgentBalanceHistoryService.js"
import { AgentService } from "@/services/AgentService.js"
import { EventBus } from "@/shared/eventbus.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "agentBalanceHistoryList",
  mixins: [notify, currency],
  data() {
    return {
      subscriber: `agent-balance-histories-${this.type}`,
      agentService: new AgentService(),
      agentBalanceHistoryService: new AgentBalanceHistoryService(
        this.agentId,
        this.type,
      ),
      showNewBalance: false,
      agent: {},
      resetKey: 0,
    }
  },
  props: {
    agentId: {
      default: null,
    },
    type: {
      type: String,
      default: "balance",
    },
  },
  computed: {
    isBalanceType() {
      return this.type === "balance"
    },
    headers() {
      return [
        this.$tc("words.id"),
        this.$tc("words.type"),
        this.$tc("phrases.transactionId"),
        this.$tc("words.amount"),
        this.$tc("words.date"),
      ]
    },
    total() {
      if (this.isBalanceType) return this.agent.balance || 0
      return this.agent.commissionRevenue || 0
    },
  },
  mounted() {
    this.getAgentDetail()
    EventBus.$on("balanceAdded", this.refresh)
    EventBus.$on("receiptAdded", this.refresh)
    EventBus.$on("addBalanceClosed", this.hideAddBalance)
    EventBus.$on("pageLoaded", this.reloadList)
  },
  beforeDestroy() {
    EventBus.$off("balanceAdded", this.refresh)
    EventBus.$off("receiptAdded", this.refresh)
    EventBus.$off("addBalanceClosed", this.hideAddBalance)
    EventBus.$off("pageLoaded", this.reloadList)
  },
  components: {
    AddAgentBalance,
    Widget,
  },
  methods: {
    showAddBalance() {
      this.showNewBalance = true
    },
    hideAddBalance() {
      this.showNewBalance = false
    },
    refresh() {
      this.showNewBalance = false
      this.resetKey += 1
      this.getAgentDetail()
    },
    typeLabel(item) {
      if (!this.isBalanceType) {
        return item.amount < 0
          ? this.$tc("words.payout")
          : this.$tc("words.earned")
      }
      const labels = {
        agent_transaction: this.$tc("phrases.energySale"),
        agent_appliance: this.$tc("phrases.applianceSale"),
        agent_charge: this.$tc("phrases.balanceCharge"),
        agent_receipt: this.$tc("words.receipt"),
      }
      return labels[item.type] || item.type
    },
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) return
      this.agentBalanceHistoryService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.agentBalanceHistoryService.list.length,
      )
    },
    async getAgentDetail() {
      try {
        this.agent = await this.agentService.getAgent(this.agentId)
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
  },
}
</script>
<style scoped lang="scss">
.history-total {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 1rem;
  border-bottom: 1px solid rgba(0, 0, 0, 0.12);
}
</style>
